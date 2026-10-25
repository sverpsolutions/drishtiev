<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Super Admin Emergency Day Close recovery process.
 * Lets a Super Admin force-close every open cashier shift and the active
 * business day when a normal Day Close is blocked (e.g. a counter crashed
 * mid-shift). Every action here is superadmin-only and fully audited.
 */
class EmergencyDayClose extends Auth_Controller {

    const REASONS = [
        'System Crash', 'SQL Server Failure', 'Windows Crash', 'Power Failure',
        'Hardware Failure', 'Network Failure', 'Machine Damaged',
        'Emergency Manual Close', 'Other',
    ];

    private $_has_bd_loc = false;

    public function __construct() {
        parent::__construct();
        $this->require_admin();
        $this->_has_bd_loc = $this->db->field_exists('location_id', 'business_days');
    }

    // -------------------------------------------------------
    // Review screen
    // -------------------------------------------------------
    public function index() {
        $loc = $this->active_location_id() ?: 0;
        $this->db->where('status', 'open');
        if ($this->_has_bd_loc && $loc > 0) {
            $this->db->group_start()->where('location_id', $loc)->or_where('location_id', 0)->group_end();
        }
        $open_day = $this->db->get('business_days')->row();
        if (!$open_day) {
            $this->session->set_flashdata('error', 'No open Business Day found.');
            redirect('dashboard');
        }

        $bdate = $open_day->business_date;

        $this->db->select('cashier_shifts.*, users.name as cashier_name')
                 ->join('users', 'users.id = cashier_shifts.cashier_id')
                 ->where('cashier_shifts.business_date', $bdate)
                 ->order_by('cashier_shifts.status', 'ASC')
                 ->order_by('cashier_shifts.id', 'ASC');
        if ($loc > 0) {
            $this->db->group_start()
                ->where('users.location_id', $loc)
                ->or_where('users.location_id IS NULL', null, false)
            ->group_end();
        }
        $shifts = $this->db->get('cashier_shifts')->result();

        foreach ($shifts as &$s) {
            $sys = $this->_calc_shift_system_totals($s);
            $s->sys_totals = $sys;
        }

        $open_shifts = array_values(array_filter($shifts, fn($s) => $s->status === 'open'));

        $data['page_title']    = 'Emergency Day Management';
        $data['open_day']      = $open_day;
        $data['shifts']        = $shifts;
        $data['open_shifts']   = $open_shifts;
        $data['total_counters']  = count($shifts);
        $data['closed_counters'] = count($shifts) - count($open_shifts);
        $data['reasons']       = self::REASONS;

        $this->render('emergency_day_close/index', $data);
    }

    // -------------------------------------------------------
    // Force close + Day Close + auto-open next day
    // -------------------------------------------------------
    public function execute() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('emergency_day_close');

        $confirm = $this->input->post('confirm_phrase', TRUE);
        $reason  = $this->input->post('reason', TRUE);
        $remarks = $this->input->post('remarks', TRUE);
        $machine_name = $this->input->post('machine_name', TRUE);

        if ($confirm !== 'EMERGENCY CLOSE') {
            $this->session->set_flashdata('error', 'You must type EMERGENCY CLOSE exactly to confirm.');
            redirect('emergency_day_close');
        }
        if (empty($reason) || !in_array($reason, self::REASONS, true)) {
            $this->session->set_flashdata('error', 'A reason is required.');
            redirect('emergency_day_close');
        }
        if ($reason === 'Other' && !trim((string)$remarks)) {
            $this->session->set_flashdata('error', 'Remarks are required when reason is "Other".');
            redirect('emergency_day_close');
        }

        $loc = $this->active_location_id() ?: 0;
        $this->db->where('status', 'open');
        if ($this->_has_bd_loc && $loc > 0) {
            $this->db->group_start()->where('location_id', $loc)->or_where('location_id', 0)->group_end();
        }
        $open_day = $this->db->get('business_days')->row();
        if (!$open_day) {
            $this->session->set_flashdata('error', 'No open Business Day found.');
            redirect('dashboard');
        }
        $bdate = $open_day->business_date;
        $uid   = $this->session->userdata('user_id');
        $uname = $this->session->userdata('name');
        $now   = date('Y-m-d H:i:s');

        $this->db->select('cashier_shifts.*, users.name as cashier_name')
                 ->join('users', 'users.id = cashier_shifts.cashier_id')
                 ->where('cashier_shifts.business_date', $bdate)
                 ->where('cashier_shifts.status', 'open');
        if ($loc > 0) {
            $this->db->group_start()
                ->where('users.location_id', $loc)
                ->or_where('users.location_id IS NULL', null, false)
            ->group_end();
        }
        $open_shifts = $this->db->get('cashier_shifts')->result();

        $this->db->trans_start();

        // 1. Force-close every open shift, accepting system totals as declared
        $counter_names = [];
        foreach ($open_shifts as $shift) {
            $sys = $this->_calc_shift_system_totals($shift);
            $this->db->where('id', $shift->id)->update('cashier_shifts', [
                'closed_at'           => $now,
                'closed_by'           => $uid,
                'status'              => 'closed',
                'is_emergency_closed' => 1,
                'emergency_reason'    => $reason,
                'system_cash'         => $sys['cash'],
                'system_upi'          => $sys['upi'],
                'system_card'         => $sys['card'],
                'system_bank'         => $sys['bank'],
                'system_wallet'       => $sys['wallet'],
                'system_credit'       => $sys['credit'],
                'system_gift_voucher' => $sys['gift_voucher'],
                'actual_cash'         => $sys['cash'],
                'actual_upi'          => $sys['upi'],
                'actual_card'         => $sys['card'],
                'actual_bank'         => $sys['bank'],
                'actual_wallet'       => $sys['wallet'],
                'actual_gift_voucher' => $sys['gift_voucher'],
                'diff_cash'           => 0, 'diff_upi'  => 0, 'diff_card'   => 0,
                'diff_bank'           => 0, 'diff_wallet' => 0, 'diff_gift_voucher' => 0,
                'diff_total'          => 0,
                'short_amount'        => 0,
                'excess_amount'       => 0,
                'close_remarks'       => 'Emergency Closed by Super Admin. Reason: ' . $reason . (trim((string)$remarks) ? ' — ' . $remarks : ''),
            ]);
            $counter_names[] = $shift->cashier_name . ' (' . ($shift->system_name ?: 'Shift #' . $shift->id) . ')';
            $this->log_activity('shift_close', 'emergency_close', 'Emergency force-closed shift #' . $shift->id . ' for ' . $shift->cashier_name, $shift->id, 'Shift #' . $shift->id, $uid, $uname);
        }

        // 2. Best-effort DB backup (never blocks the process)
        $backup_status = $this->_attempt_db_backup($bdate);

        // 3. Consolidated Day Close summary (same math as Shifts::day_close())
        $summary = $this->_calc_day_summary($bdate);

        $this->db->where('id', $open_day->id)->update('business_days', [
            'closed_by'           => $uid,
            'closed_at'           => $now,
            'status'              => 'closed',
            'is_emergency_close'  => 1,
            'emergency_reason'    => $reason,
            'emergency_closed_by' => $uid,
            'total_sales'         => $summary['total_sales'],
            'total_cash'          => $summary['total_cash'],
            'total_upi'           => $summary['total_upi'],
            'total_card'          => $summary['total_card'],
            'total_bank'          => $summary['total_bank'],
            'total_wallet'        => $summary['total_wallet'],
            'total_gift_voucher'  => $summary['total_gift_voucher'],
            'total_discounts'     => $summary['total_discounts'],
            'total_taxes'         => $summary['total_taxes'],
            'total_refunds'       => $summary['total_refunds'],
            'total_shortage'      => $summary['total_shortage'],
            'total_excess'        => $summary['total_excess'],
        ]);

        // 4. Permanent audit record
        $this->db->insert('emergency_day_close_log', [
            'business_date'         => $bdate,
            'business_day_id'       => $open_day->id,
            'closed_by'             => $uid,
            'closed_by_name'        => $uname,
            'closed_at'             => $now,
            'ip_address'            => $this->input->ip_address(),
            'machine_name'          => $machine_name,
            'active_counters_count' => count($open_shifts),
            'counter_names'         => implode(', ', $counter_names),
            'reason'                => $reason,
            'remarks'               => $remarks,
            'sales_amount'          => $summary['total_sales'],
            'backup_status'         => $backup_status,
            'created_at'            => $now,
        ]);
        $log_id = $this->db->insert_id();

        $this->log_activity('day_close', 'emergency_close', 'Emergency Day Close for ' . $bdate . '. Reason: ' . $reason . '. Counters force-closed: ' . count($open_shifts), $open_day->id, $bdate, $uid, $uname);

        // 5. Auto-open next business day (same location)
        $bd_loc = ($this->_has_bd_loc && isset($open_day->location_id)) ? $open_day->location_id : 0;
        $next_date = date('Y-m-d', strtotime($bdate . ' +1 day'));
        $this->db->where('business_date', $next_date);
        if ($this->_has_bd_loc) $this->db->where('location_id', $bd_loc);
        $next_exists = $this->db->get('business_days')->row();
        if (!$next_exists) {
            $next_insert = [
                'business_date' => $next_date,
                'opened_by'     => $uid,
                'opened_at'     => $now,
                'status'        => 'open',
                'open_remarks'  => 'Auto-opened after Emergency Day Close of ' . $bdate,
            ];
            if ($this->_has_bd_loc) $next_insert['location_id'] = $bd_loc;
            $this->db->insert('business_days', $next_insert);
            $this->log_activity('day_open', 'auto_open', 'Auto-opened Business Day ' . $next_date . ' after Emergency Day Close', $this->db->insert_id(), $next_date, $uid, $uname);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error. Emergency Day Close was not completed — nothing was changed.');
            redirect('emergency_day_close');
        }

        $this->session->set_flashdata('success', 'Emergency Day Close completed for ' . date('d-M-Y', strtotime($bdate)) . '. Business Day ' . date('d-M-Y', strtotime($next_date)) . ' is now open.');
        redirect('emergency_day_close/view/' . $log_id);
    }

    // -------------------------------------------------------
    // History report
    // -------------------------------------------------------
    public function history() {
        $data['page_title'] = 'Emergency Day Close History';
        $data['logs'] = $this->db->order_by('id', 'DESC')->get('emergency_day_close_log')->result();
        $this->render('emergency_day_close/history', $data);
    }

    // -------------------------------------------------------
    // Single record detail
    // -------------------------------------------------------
    public function view($id = null) {
        if (!$id) redirect('emergency_day_close/history');
        $log = $this->db->where('id', $id)->get('emergency_day_close_log')->row();
        if (!$log) { $this->session->set_flashdata('error', 'Record not found.'); redirect('emergency_day_close/history'); }

        $data['page_title']  = 'Emergency Day Close — ' . date('d-M-Y', strtotime($log->business_date));
        $data['log']         = $log;
        $data['business_day']= $log->business_day_id ? $this->db->where('id', $log->business_day_id)->get('business_days')->row() : null;

        $this->render('emergency_day_close/view', $data);
    }

    // ── helpers ──────────────────────────────────────────────

    // Same per-shift system-collection math as Shifts::shift_close() uses,
    // returned as an array instead of touching the session/DB.
    private function _calc_shift_system_totals($shift) {
        $invoices = $this->db->where('shift_id', $shift->id)
                             ->where('status !=', 'cancelled')
                             ->get('invoices')->result();

        $cash = $upi = $card = $bank = $wallet = $credit = $gift_voucher = 0;
        $bills = 0; $last_bill_no = null; $last_bill_time = null;

        foreach ($invoices as $inv) {
            $bills++;
            if (!$last_bill_time || strtotime($inv->created_at) > strtotime($last_bill_time)) {
                $last_bill_time = $inv->created_at;
                $last_bill_no   = $inv->invoice_no;
            }
            if ($inv->payment_mode === 'split') {
                $splits = $this->db->where('invoice_id', $inv->id)->get('invoice_payment_splits')->result();
                foreach ($splits as $sp) {
                    switch ($sp->payment_mode) {
                        case 'cash':                             $cash += $sp->amount; break;
                        case 'upi':                              $upi  += $sp->amount; break;
                        case 'card':                             $card += $sp->amount; break;
                        case 'neft': case 'cheque': case 'bank': $bank += $sp->amount; break;
                        case 'wallet':                           $wallet += $sp->amount; break;
                        case 'gift_voucher':                     $gift_voucher += $sp->amount; break;
                    }
                }
            } else {
                switch ($inv->payment_mode) {
                    case 'cash':                             $cash += $inv->paid_amount; break;
                    case 'upi':                              $upi  += $inv->paid_amount; break;
                    case 'card':                             $card += $inv->paid_amount; break;
                    case 'neft': case 'cheque': case 'bank': $bank += $inv->paid_amount; break;
                    case 'wallet':                           $wallet += $inv->paid_amount; break;
                    case 'gift_voucher':                     $gift_voucher += $inv->paid_amount; break;
                }
            }
            $credit += $inv->due_amount;
        }

        $payments = $this->db->where('shift_id', $shift->id)->where('type', 'received')->get('payments')->result();
        foreach ($payments as $p) {
            switch ($p->payment_mode) {
                case 'cash':                             $cash += $p->amount; break;
                case 'upi':                              $upi  += $p->amount; break;
                case 'card':                             $card += $p->amount; break;
                case 'neft': case 'cheque': case 'bank': $bank += $p->amount; break;
                case 'wallet':                           $wallet += $p->amount; break;
                case 'gift_voucher':                     $gift_voucher += $p->amount; break;
            }
        }

        // Refunds paid out during this shift reduce the relevant drawer total
        // — otherwise force-closing would auto-declare a total that ignores
        // real cash that was already handed back to a customer.
        $shift_end = $shift->closed_at ?: date('Y-m-d H:i:s');
        $refunds = $this->db->where('created_at >=', $shift->opened_at)
                            ->where('created_at <=', $shift_end)
                            ->where('status', 'processed')
                            ->get('refunds')->result();
        $refund_total = 0;
        foreach ($refunds as $rf) {
            $refund_total += $rf->refund_amount;
            switch ($rf->refund_mode) {
                case 'cash':                             $cash -= $rf->refund_amount; break;
                case 'upi':                              $upi  -= $rf->refund_amount; break;
                case 'card':                             $card -= $rf->refund_amount; break;
                case 'neft': case 'cheque': case 'bank': $bank -= $rf->refund_amount; break;
            }
        }

        return [
            'cash' => $cash, 'upi' => $upi, 'card' => $card, 'bank' => $bank,
            'wallet' => $wallet, 'credit' => $credit, 'gift_voucher' => $gift_voucher,
            'refund_total' => $refund_total,
            'bills' => $bills, 'last_bill_no' => $last_bill_no, 'last_bill_time' => $last_bill_time,
        ];
    }

    // Same consolidated-day math as Shifts::day_close() uses.
    private function _calc_day_summary($bdate) {
        $invoices = $this->db->where('invoice_date', $bdate)
                             ->where('status !=', 'cancelled')
                             ->get('invoices')->result();

        $total_sales = $total_cash = $total_upi = $total_card = 0;
        $total_bank = $total_wallet = $total_gift_voucher = 0;
        $total_discounts = $total_taxes = 0;

        foreach ($invoices as $inv) {
            $total_sales     += $inv->total_amount;
            $total_discounts += $inv->discount ?? 0;
            $total_taxes     += $inv->total_gst ?? 0;
            switch ($inv->payment_mode) {
                case 'cash':                             $total_cash         += $inv->paid_amount; break;
                case 'upi':                              $total_upi          += $inv->paid_amount; break;
                case 'card':                             $total_card         += $inv->paid_amount; break;
                case 'neft': case 'cheque': case 'bank': $total_bank         += $inv->paid_amount; break;
                case 'wallet':                           $total_wallet       += $inv->paid_amount; break;
                case 'gift_voucher':                     $total_gift_voucher += $inv->paid_amount; break;
            }
        }

        $payments = $this->db->where('payment_date', $bdate)->where('type', 'received')->get('payments')->result();
        foreach ($payments as $p) {
            switch ($p->payment_mode) {
                case 'cash':                             $total_cash         += $p->amount; break;
                case 'upi':                              $total_upi          += $p->amount; break;
                case 'card':                             $total_card         += $p->amount; break;
                case 'neft': case 'cheque': case 'bank': $total_bank         += $p->amount; break;
                case 'wallet':                           $total_wallet       += $p->amount; break;
                case 'gift_voucher':                     $total_gift_voucher += $p->amount; break;
            }
        }

        $shifts = $this->db->where('business_date', $bdate)->get('cashier_shifts')->result();
        $total_shortage = 0; $total_excess = 0;
        foreach ($shifts as $s) {
            $total_shortage += $s->short_amount;
            $total_excess   += $s->excess_amount;
        }

        // Refunds processed on this business date — reduce Net Sales and the
        // relevant payment-mode total (credit notes don't move real cash)
        $refunds = $this->db->where('DATE(created_at)', $bdate)->where('status', 'processed')->get('refunds')->result();
        $total_refunds = 0;
        foreach ($refunds as $rf) {
            $total_refunds += $rf->refund_amount;
            switch ($rf->refund_mode) {
                case 'cash':                             $total_cash -= $rf->refund_amount; break;
                case 'upi':                              $total_upi  -= $rf->refund_amount; break;
                case 'card':                             $total_card -= $rf->refund_amount; break;
                case 'neft': case 'cheque': case 'bank': $total_bank -= $rf->refund_amount; break;
            }
        }
        $total_sales -= $total_refunds;

        return [
            'total_sales' => $total_sales, 'total_cash' => $total_cash, 'total_upi' => $total_upi,
            'total_card' => $total_card, 'total_bank' => $total_bank, 'total_wallet' => $total_wallet,
            'total_gift_voucher' => $total_gift_voucher, 'total_discounts' => $total_discounts,
            'total_taxes' => $total_taxes, 'total_refunds' => $total_refunds,
            'total_shortage' => $total_shortage, 'total_excess' => $total_excess,
        ];
    }

    // Best-effort mysqldump. Shared hosting frequently disables shell_exec —
    // this must never throw or block the emergency close either way.
    private function _attempt_db_backup($bdate) {
        try {
            if (!function_exists('shell_exec')) {
                return 'skipped (shell_exec unavailable on this host)';
            }
            $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
            if (in_array('shell_exec', $disabled, true)) {
                return 'skipped (shell_exec disabled)';
            }

            $db_conf = $this->db;
            // This directory lives under the public webroot (APPPATH's parent),
            // so it MUST be locked down — otherwise a guessed URL could expose
            // a full database dump. Deny-all .htaccess is written alongside it.
            $backup_dir = APPPATH . '../backups';
            if (!is_dir($backup_dir)) {
                @mkdir($backup_dir, 0755, true);
            }
            if (!is_dir($backup_dir) || !is_writable($backup_dir)) {
                return 'skipped (backup directory not writable)';
            }
            $htaccess = $backup_dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Require all denied\nDeny from all\n");
            }

            // Use a defaults file rather than passing the password on the
            // command line, which would otherwise be visible to other users
            // via `ps` on shared hosting.
            $defaults_file = $backup_dir . '/.my_' . uniqid() . '.cnf';
            $cnf = "[client]\nuser=" . $db_conf->username . "\npassword=" . $db_conf->password . "\nhost=" . $db_conf->hostname . "\n";
            file_put_contents($defaults_file, $cnf);
            chmod($defaults_file, 0600);

            $filename = $backup_dir . '/emergency_' . $bdate . '_' . date('His') . '.sql';
            $cmd = sprintf(
                'mysqldump --defaults-extra-file=%s %s > %s 2>&1',
                escapeshellarg($defaults_file),
                escapeshellarg($db_conf->database),
                escapeshellarg($filename)
            );
            @shell_exec($cmd);
            @unlink($defaults_file);

            if (file_exists($filename) && filesize($filename) > 0) {
                return 'backup created: ' . basename($filename);
            }
            @unlink($filename);
            return 'failed (mysqldump did not produce output — likely unavailable on this host)';
        } catch (\Throwable $e) {
            return 'failed (' . $e->getMessage() . ')';
        }
    }
}
