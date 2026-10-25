<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CronDayend extends CI_Controller {

    private $_has_bd_loc = false;

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->_has_bd_loc = $this->db->field_exists('location_id', 'business_days');
    }

    private function _auth() {
        $key = $this->input->get('key', TRUE);
        $valid_key = $this->db->where('key_name', 'cron_secret_key')
                              ->get('company_settings')->row();
        if (!$valid_key || empty($valid_key->key_value) || $key !== $valid_key->key_value) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
    }

    public function auto_close() {
        $this->_auth();
        header('Content-Type: application/json');
        $now = date('Y-m-d H:i:s');
        $results = [];

        $locations = $this->_get_locations();

        foreach ($locations as $loc_id) {
            $this->db->where('status', 'open');
            if ($this->_has_bd_loc) $this->db->where('location_id', $loc_id);
            $open_day = $this->db->get('business_days')->row();

            if (!$open_day) {
                $results[] = ['location' => $loc_id, 'action' => 'skip', 'reason' => 'No open business day'];
                continue;
            }

            $bdate = $open_day->business_date;

            $this->db->select('cashier_shifts.*, users.name as cashier_name')
                     ->join('users', 'users.id = cashier_shifts.cashier_id')
                     ->where('cashier_shifts.business_date', $bdate)
                     ->where('cashier_shifts.status', 'open');
            if ($loc_id > 0) {
                $this->db->group_start()
                    ->where('users.location_id', $loc_id)
                    ->or_where('users.location_id IS NULL', null, false)
                ->group_end();
            }
            $open_shifts = $this->db->get('cashier_shifts')->result();

            $this->db->trans_start();

            foreach ($open_shifts as $shift) {
                $sys = $this->_calc_shift_totals($shift);
                $this->db->where('id', $shift->id)->update('cashier_shifts', [
                    'closed_at'           => $now,
                    'status'              => 'closed',
                    'is_emergency_closed' => 1,
                    'emergency_reason'    => 'Auto Day End (System Cron)',
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
                    'diff_cash' => 0, 'diff_upi' => 0, 'diff_card' => 0,
                    'diff_bank' => 0, 'diff_wallet' => 0, 'diff_gift_voucher' => 0,
                    'diff_total'    => 0,
                    'short_amount'  => 0,
                    'excess_amount' => 0,
                    'close_remarks' => 'Auto-closed by System Cron at ' . $now,
                ]);
            }

            $summary = $this->_calc_day_summary($bdate, $loc_id);

            $this->db->where('id', $open_day->id)->update('business_days', [
                'closed_by'          => 0,
                'closed_at'          => $now,
                'status'             => 'closed',
                'total_sales'        => $summary['total_sales'],
                'total_cash'         => $summary['total_cash'],
                'total_upi'          => $summary['total_upi'],
                'total_card'         => $summary['total_card'],
                'total_bank'         => $summary['total_bank'],
                'total_wallet'       => $summary['total_wallet'],
                'total_gift_voucher' => $summary['total_gift_voucher'],
                'total_discounts'    => $summary['total_discounts'],
                'total_taxes'        => $summary['total_taxes'],
                'total_refunds'      => $summary['total_refunds'],
                'total_shortage'     => $summary['total_shortage'],
                'total_excess'       => $summary['total_excess'],
            ]);

            $this->_log('day_close', 'auto_close', 'Auto Day Close (Cron) for ' . $bdate . '. Shifts force-closed: ' . count($open_shifts), $open_day->id, $bdate);

            $this->db->trans_complete();

            $results[] = [
                'location'       => $loc_id,
                'action'         => 'closed',
                'business_date'  => $bdate,
                'shifts_closed'  => count($open_shifts),
                'total_sales'    => $summary['total_sales'],
            ];
        }

        echo json_encode(['status' => 'ok', 'time' => $now, 'results' => $results], JSON_PRETTY_PRINT);
    }

    public function auto_open() {
        $this->_auth();
        header('Content-Type: application/json');
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $results = [];

        $locations = $this->_get_locations();

        foreach ($locations as $loc_id) {
            $this->db->where('status', 'open');
            if ($this->_has_bd_loc) $this->db->where('location_id', $loc_id);
            $already_open = $this->db->get('business_days')->row();

            if ($already_open) {
                $results[] = ['location' => $loc_id, 'action' => 'skip', 'reason' => 'Already open: ' . $already_open->business_date];
                continue;
            }

            $this->db->where('business_date', $today);
            if ($this->_has_bd_loc) $this->db->where('location_id', $loc_id);
            $exists = $this->db->get('business_days')->row();
            if ($exists) {
                $results[] = ['location' => $loc_id, 'action' => 'skip', 'reason' => 'Today already exists with status: ' . $exists->status];
                continue;
            }

            $insert = [
                'business_date' => $today,
                'opened_by'     => 0,
                'opened_at'     => $now,
                'status'        => 'open',
                'open_remarks'  => 'Auto-opened by System Cron',
            ];
            if ($this->_has_bd_loc) $insert['location_id'] = $loc_id;
            $this->db->insert('business_days', $insert);
            $insert_id = $this->db->insert_id();

            $this->_log('day_open', 'auto_open', 'Auto Day Open (Cron) for ' . $today, $insert_id, $today);

            $results[] = [
                'location'      => $loc_id,
                'action'        => 'opened',
                'business_date' => $today,
            ];
        }

        echo json_encode(['status' => 'ok', 'time' => $now, 'results' => $results], JSON_PRETTY_PRINT);
    }

    private function _get_locations() {
        if (!$this->_has_bd_loc) return [0];

        $rows = $this->db->select('id')->get('locations')->result();
        if (empty($rows)) return [0];

        return array_map(fn($r) => (int)$r->id, $rows);
    }

    private function _calc_shift_totals($shift) {
        $invoices = $this->db->where('shift_id', $shift->id)
                             ->where('status !=', 'cancelled')
                             ->get('invoices')->result();

        $cash = $upi = $card = $bank = $wallet = $credit = $gift_voucher = 0;

        foreach ($invoices as $inv) {
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

        $shift_end = $shift->closed_at ?: date('Y-m-d H:i:s');
        $refunds = $this->db->where('created_at >=', $shift->opened_at)
                            ->where('created_at <=', $shift_end)
                            ->where('status', 'processed')
                            ->get('refunds')->result();
        foreach ($refunds as $rf) {
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
        ];
    }

    private function _calc_day_summary($bdate, $loc_id = 0) {
        $this->db->where('invoice_date', $bdate)->where('status !=', 'cancelled');
        if ($loc_id > 0) $this->db->where('location_id', $loc_id);
        $invoices = $this->db->get('invoices')->result();

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

        $this->db->where('payment_date', $bdate)->where('type', 'received');
        $payments = $this->db->get('payments')->result();
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

    private function _log($type, $action, $description, $ref_id = null, $ref_code = null) {
        if ($this->db->table_exists('activity_log')) {
            $this->db->insert('activity_log', [
                'user_id'     => 0,
                'user_name'   => 'System Cron',
                'type'        => $type,
                'action'      => $action,
                'description' => $description,
                'ref_id'      => $ref_id,
                'ref_code'    => $ref_code,
                'ip_address'  => $this->input->ip_address(),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
