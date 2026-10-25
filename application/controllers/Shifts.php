<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shifts extends Auth_Controller {

    private $_has_bd_loc = false;

    public function __construct() {
        parent::__construct();
        $this->load->library('form_validation');
        $this->_has_bd_loc = $this->db->field_exists('location_id', 'business_days');
    }

    // -------------------------------------------------------
    // Day Open Process
    // -------------------------------------------------------
    public function day_open() {
        $this->require_admin();
        if (!$this->can_module('mod_day_open')) {
            $this->session->set_flashdata('error', 'No permission for Day Open.');
            redirect('dashboard');
        }

        $loc = $this->active_location_id() ?: 0;

        $this->db->where('status', 'open');
        if ($this->_has_bd_loc) $this->db->where('location_id', $loc);
        $open_day = $this->db->get('business_days')->row();
        if ($open_day) {
            $this->session->set_flashdata('error', 'A Business Day is already open: ' . date('d-M-Y', strtotime($open_day->business_date)));
            redirect('dashboard');
        }

        $data['page_title'] = 'Day Open';
        $data['suggested_date'] = date('Y-m-d');
        $this->db->where('business_date', date('Y-m-d'))->where('status', 'closed');
        if ($this->_has_bd_loc) $this->db->where('location_id', $loc);
        $data['today_closed_day'] = $this->db->get('business_days')->row();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('shifts/day_open', $data);
            return;
        }

        $business_date = $this->input->post('business_date', TRUE);
        if (empty($business_date)) {
            $this->session->set_flashdata('error', 'Business date is required.');
            $this->render('shifts/day_open', $data);
            return;
        }

        $this->db->where('business_date', $business_date);
        if ($this->_has_bd_loc) $this->db->where('location_id', $loc);
        $exists = $this->db->get('business_days')->row();
        if ($exists) {
            $this->session->set_flashdata('error', 'Business Date ' . date('d-M-Y', strtotime($business_date)) . ' was already opened/closed.');
            $this->render('shifts/day_open', $data);
            return;
        }

        $insert_data = [
            'business_date' => $business_date,
            'opened_by'     => $this->session->userdata('user_id'),
            'opened_at'     => date('Y-m-d H:i:s'),
            'status'        => 'open',
            'open_remarks'  => $this->input->post('open_remarks', TRUE),
        ];
        if ($this->_has_bd_loc) $insert_data['location_id'] = $loc;
        $this->db->insert('business_days', $insert_data);
        $insert_id = $this->db->insert_id();

        $this->log_activity('day_open', 'open', 'Opened Business Day: ' . $business_date, $insert_id, $business_date);
        $this->session->set_flashdata('success', 'Business Day ' . date('d-M-Y', strtotime($business_date)) . ' opened successfully!');
        redirect('dashboard');
    }

    // -------------------------------------------------------
    // Reopen a same-day business day that was already closed (e.g. Day Close hit by
    // mistake, or a genuine early close that now needs more sales/billing before
    // midnight). Deliberately scoped to TODAY's date only — reopening an older,
    // already-reported historical day is not safe to do from here (would let new
    // transactions land on a day whose totals/reports were already finalized).
    // -------------------------------------------------------
    public function reopen_business_day() {
        $this->require_admin();
        if (!$this->can_module('mod_day_open')) {
            $this->session->set_flashdata('error', 'No permission for Day Open.');
            redirect('dashboard');
        }
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('shifts/day_open');

        $loc = $this->active_location_id() ?: 0;

        $this->db->where('status', 'open');
        if ($this->_has_bd_loc) $this->db->where('location_id', $loc);
        $open_day = $this->db->get('business_days')->row();
        if ($open_day) {
            $this->session->set_flashdata('error', 'A Business Day is already open: ' . date('d-M-Y', strtotime($open_day->business_date)));
            redirect('dashboard');
        }

        $today = date('Y-m-d');
        $this->db->where('business_date', $today)->where('status', 'closed');
        if ($this->_has_bd_loc) $this->db->where('location_id', $loc);
        $closed_today = $this->db->get('business_days')->row();
        if (!$closed_today) {
            $this->session->set_flashdata('error', "Today's Business Day was never opened — use Day Open instead.");
            redirect('shifts/day_open');
        }

        $this->db->where('id', $closed_today->id)->update('business_days', [
            'status'    => 'open',
            'closed_by' => null,
            'closed_at' => null,
        ]);

        $this->log_activity('day_reopen', 'open', 'Reopened Business Day: ' . $today, $closed_today->id, $today);
        $this->session->set_flashdata('success', 'Business Day ' . date('d-M-Y', strtotime($today)) . ' reopened — you can bill again.');
        redirect('dashboard');
    }

    // -------------------------------------------------------
    // Day Close Process
    // -------------------------------------------------------
    public function day_close() {
        $this->require_admin();
        if (!$this->can_module('mod_day_close')) {
            $this->session->set_flashdata('error', 'No permission for Day Close.');
            redirect('dashboard');
        }

        $loc = $this->active_location_id() ?: 0;

        $this->db->where('status', 'open');
        if ($this->_has_bd_loc) $this->db->where('location_id', $loc);
        $open_day = $this->db->get('business_days')->row();
        if (!$open_day) {
            $this->session->set_flashdata('error', 'No open Business Day found to close.');
            redirect('dashboard');
        }

        $bdate = $open_day->business_date;

        // Validation 1: Any shift is still open (at this location)?
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

        $has_open_shifts = !empty($open_shifts);

        // Validation 2: Any transaction pending (draft invoices)?
        $this->db->where('DATE(invoice_date)', $bdate)->where('status', 'draft');
        if ($loc > 0) $this->db->where('location_id', $loc);
        $draft_invoices = $this->db->get('invoices')->result();

        $data['page_title']     = 'Day Close: ' . date('d-M-Y', strtotime($bdate));
        $data['open_day']       = $open_day;
        $data['open_shifts']    = $open_shifts;
        $data['draft_invoices'] = $draft_invoices;
        $data['validation_failed'] = $has_open_shifts || !empty($draft_invoices);

        // Consolidated Summary Calculations (scoped to this location)
        $this->db->where('invoice_date', $bdate)->where('status !=', 'cancelled');
        if ($loc > 0) $this->db->where('location_id', $loc);
        $invoices = $this->db->get('invoices')->result();

        $total_sales = 0; $total_subtotal = 0; $total_cash = 0; $total_upi = 0; $total_card = 0;
        $total_bank = 0; $total_wallet = 0; $total_gift_voucher = 0;
        $total_discounts = 0; $total_taxes = 0;
        $total_bills = 0; $cancelled_bills = 0;

        foreach ($invoices as $inv) {
            $total_bills++;
            // total_amount is already net of discount (taxable + GST), so it
            // represents Net Sales. subtotal (pre-discount) is Gross Sales.
            $total_sales     += $inv->total_amount;
            $total_subtotal  += $inv->subtotal ?? $inv->total_amount;
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
        $this->db->where('DATE(invoice_date)', $bdate)->where('status','cancelled');
        if ($loc > 0) $this->db->where('location_id', $loc);
        $cancelled_bills = $this->db->count_all_results('invoices');

        // Payments received later on this day
        if ($loc > 0) {
            $payments = $this->db->select('payments.*')
                ->from('payments')
                ->join('invoices pmt_inv', "pmt_inv.id = payments.ref_id AND payments.ref_type = 'invoice'", 'inner')
                ->where('payments.payment_date', $bdate)
                ->where('payments.type', 'received')
                ->where('pmt_inv.location_id', $loc)
                ->get()->result();
        } else {
            $payments = $this->db->where('payment_date', $bdate)->where('type', 'received')->get('payments')->result();
        }
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

        // Refunds processed on this business date
        if ($loc > 0) {
            $refunds = $this->db->select('refunds.*')
                ->from('refunds')
                ->join('invoices rf_inv', 'rf_inv.id = refunds.invoice_id', 'inner')
                ->where('DATE(refunds.created_at)', $bdate)
                ->where('refunds.status', 'processed')
                ->where('rf_inv.location_id', $loc)
                ->get()->result();
        } else {
            $refunds = $this->db->where('DATE(created_at)', $bdate)->where('status', 'processed')->get('refunds')->result();
        }
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

        // Shifts summary list (location-scoped)
        $this->db->select('cashier_shifts.*, users.name as cashier_name')
                 ->join('users', 'users.id = cashier_shifts.cashier_id')
                 ->where('cashier_shifts.business_date', $bdate);
        if ($loc > 0) {
            $this->db->group_start()
                ->where('users.location_id', $loc)
                ->or_where('users.location_id IS NULL', null, false)
            ->group_end();
        }
        $shifts = $this->db->get('cashier_shifts')->result();

        $total_shortage = 0;
        $total_excess = 0;
        foreach ($shifts as $s) {
            $total_shortage += $s->short_amount;
            $total_excess += $s->excess_amount;
        }

        $data['shifts']              = $shifts;
        $data['total_sales']         = $total_sales;
        $data['total_subtotal']      = $total_subtotal;
        $data['total_bills']         = $total_bills;
        $data['cancelled_bills']     = $cancelled_bills;
        $data['total_cash']          = $total_cash;
        $data['total_upi']           = $total_upi;
        $data['total_card']          = $total_card;
        $data['total_bank']          = $total_bank;
        $data['total_wallet']        = $total_wallet;
        $data['total_gift_voucher']  = $total_gift_voucher;
        $data['total_discounts']     = $total_discounts;
        $data['total_taxes']         = $total_taxes;
        $data['total_refunds']       = $total_refunds;
        $data['total_shortage']      = $total_shortage;
        $data['total_excess']        = $total_excess;

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('shifts/day_close', $data);
            return;
        }

        if ($data['validation_failed']) {
            $this->session->set_flashdata('error', 'Cannot Close Day: Fix open shifts / draft invoices first.');
            $this->render('shifts/day_close', $data);
            return;
        }

        $update_data = [
            'closed_by'           => $this->session->userdata('user_id'),
            'closed_at'           => date('Y-m-d H:i:s'),
            'status'              => 'closed',
            'total_sales'         => $total_sales,
            'total_cash'          => $total_cash,
            'total_upi'           => $total_upi,
            'total_card'          => $total_card,
            'total_bank'          => $total_bank,
            'total_wallet'        => $total_wallet,
            'total_gift_voucher'  => $total_gift_voucher,
            'total_discounts'     => $total_discounts,
            'total_taxes'         => $total_taxes,
            'total_refunds'       => $total_refunds,
            'total_shortage'      => $total_shortage,
            'total_excess'        => $total_excess,
        ];

        $this->db->where('id', $open_day->id)->update('business_days', $update_data);

        $this->log_activity('day_close', 'close', 'Closed Business Day: ' . $bdate, $open_day->id, $bdate);
        $this->session->set_flashdata('success', 'Business Day ' . date('d-M-Y', strtotime($bdate)) . ' closed successfully!');
        redirect('dashboard');
    }

    // -------------------------------------------------------
    // Shift Open
    // -------------------------------------------------------
    public function shift_open() {
        if (!$this->can_module('mod_shift_open')) {
            $this->session->set_flashdata('error', 'No permission for Shift Open.');
            redirect('dashboard');
        }

        // Check active business day (accept location-specific or global)
        $loc = $this->active_location_id() ?: 0;
        $this->db->where('status', 'open');
        if ($this->_has_bd_loc && $loc > 0) {
            $this->db->group_start()->where('location_id', $loc)->or_where('location_id', 0)->group_end();
        }
        $open_day = $this->db->get('business_days')->row();
        if (!$open_day) {
            $this->session->set_flashdata('error', 'Operation blocked: A Business Day must be opened by Manager/Admin first.');
            redirect('dashboard');
        }

        // Check if cashier already has an open shift
        $uid = $this->session->userdata('user_id');
        $open_shift = $this->db->where('cashier_id', $uid)->where('status', 'open')->get('cashier_shifts')->row();
        if ($open_shift) {
            $this->session->set_flashdata('error', 'You already have an open shift.');
            redirect('billing/create');
        }

        $data['page_title'] = 'Open Shift';
        $data['business_date'] = $open_day->business_date;
        $data['cashier_name'] = $this->session->userdata('name');

        // Suggest opening cash from last closed shift's carry forward
        $data['suggested_opening'] = 0;
        $last_shift = $this->db->where('status', 'closed')
            ->order_by('closed_at', 'DESC')->limit(1)
            ->get('cashier_shifts')->row();
        if ($last_shift) {
            if ($this->db->table_exists('cash_transactions')) {
                $this->load->model('Cash_management_model');
                $ls_loc = 0;
                $ls_cashier = $this->db->where('id', $last_shift->cashier_id)->get('users')->row();
                if ($ls_cashier && !empty($ls_cashier->location_id)) $ls_loc = (int)$ls_cashier->location_id;
                if (!$ls_loc) {
                    $ls_loc = $this->active_location_id();
                    if (!$ls_loc) { $first = $this->db->get('locations')->row(); $ls_loc = $first ? (int)$first->id : 1; }
                }
                $cs = $this->Cash_management_model->get_cash_summary($ls_loc, $last_shift->business_date);
                $data['suggested_opening'] = $cs['carry_forward'] > 0 ? $cs['carry_forward'] : (float)$last_shift->actual_cash;
            } else {
                $data['suggested_opening'] = (float)$last_shift->actual_cash;
            }
        }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('shifts/shift_open', $data);
            return;
        }

        $opening_cash = (float)$this->input->post('opening_cash');
        $remarks      = $this->input->post('remarks', TRUE);
        $shift_name   = $this->input->post('shift_name', TRUE) ?: 'General';
        $system_name  = $this->input->post('system_name', TRUE);
        $terminal_no  = $this->input->post('terminal_no', TRUE);

        $insert_data = [
            'business_date' => $open_day->business_date,
            'cashier_id'    => $uid,
            'shift_name'    => $shift_name,
            'system_name'   => $system_name,
            'terminal_no'   => $terminal_no,
            'opened_at'     => date('Y-m-d H:i:s'),
            'opening_cash'  => $opening_cash,
            'remarks'       => $remarks,
            'status'        => 'open'
        ];

        $this->db->insert('cashier_shifts', $insert_data);
        $shift_id = $this->db->insert_id();

        $this->session->set_userdata('shift_id', $shift_id);
        $this->log_activity('shift_open', 'open', 'Opened shift with cash: ₹' . number_format($opening_cash, 2), $shift_id, 'Shift #' . $shift_id);

        $this->session->set_flashdata('success', 'Your shift has been opened. Ready for billing!');
        redirect('billing/create');
    }

    // -------------------------------------------------------
    // Shift Close
    // -------------------------------------------------------
    public function shift_close() {
        if (!$this->can_module('mod_shift_close')) {
            $this->session->set_flashdata('error', 'No permission for Shift Close.');
            redirect('dashboard');
        }

        $uid = $this->session->userdata('user_id');
        $shift = $this->db->where('cashier_id', $uid)->where('status', 'open')->get('cashier_shifts')->row();
        if (!$shift) {
            $this->session->set_flashdata('error', 'You do not have any active open shift.');
            redirect('dashboard');
        }

        // Auto-heal: link any invoices created by this cashier during this shift
        // that lost their shift_id (e.g. due to session re-login between shift open and billing)
        $this->db->where('created_by', $uid)
                 ->where('shift_id IS NULL', null, false)
                 ->where('status !=', 'cancelled')
                 ->where('created_at >=', $shift->opened_at)
                 ->update('invoices', ['shift_id' => $shift->id]);

        // Calculate System Collections
        // 1. Invoices paid during this shift
        $invoices = $this->db->where('shift_id', $shift->id)
                             ->where('status !=', 'cancelled')
                             ->get('invoices')->result();
        $sys_cash = 0; $sys_upi = 0; $sys_card = 0; $sys_bank = 0;
        $sys_wallet = 0; $sys_credit = 0; $sys_gift_voucher = 0;
        $total_bills = 0; $total_discounts = 0; $total_gst = 0;
        // A credit/due bill settled later via Payments has that settlement added
        // to invoices.paid_amount AND recorded as its own payments row (counted
        // in step 2) — so take only what was paid at billing time from the invoice.
        $later_paid = $this->_later_paid_map(array_column($invoices, 'id'));
        foreach ($invoices as $inv) {
            $inv->paid_amount = max(0, (float)$inv->paid_amount - ($later_paid[$inv->id] ?? 0));
            $total_bills++;
            $total_discounts += $inv->discount ?? 0;
            $total_gst       += $inv->total_gst ?? 0;
            if ($inv->payment_mode === 'split') {
                $splits = $this->db->where('invoice_id', $inv->id)->get('invoice_payment_splits')->result();
                foreach ($splits as $sp) {
                    switch ($sp->payment_mode) {
                        case 'cash':                            $sys_cash         += $sp->amount; break;
                        case 'upi':                             $sys_upi          += $sp->amount; break;
                        case 'card':                            $sys_card         += $sp->amount; break;
                        case 'neft': case 'cheque': case 'bank':$sys_bank         += $sp->amount; break;
                        case 'wallet':                          $sys_wallet       += $sp->amount; break;
                        case 'gift_voucher':                    $sys_gift_voucher += $sp->amount; break;
                    }
                }
            } else {
                switch ($inv->payment_mode) {
                    case 'cash':                            $sys_cash         += $inv->paid_amount; break;
                    case 'upi':                             $sys_upi          += $inv->paid_amount; break;
                    case 'card':                            $sys_card         += $inv->paid_amount; break;
                    case 'neft': case 'cheque': case 'bank':$sys_bank         += $inv->paid_amount; break;
                    case 'wallet':                          $sys_wallet       += $inv->paid_amount; break;
                    case 'gift_voucher':                    $sys_gift_voucher += $inv->paid_amount; break;
                }
            }
            $sys_credit += (float)$inv->due_amount + ($later_paid[$inv->id] ?? 0);
        }

        // 2. Payments received during this shift
        $payments = $this->db->where('shift_id', $shift->id)
                             ->where('type', 'received')
                             ->get('payments')->result();
        foreach ($payments as $p) {
            switch ($p->payment_mode) {
                case 'cash':                            $sys_cash         += $p->amount; break;
                case 'upi':                             $sys_upi          += $p->amount; break;
                case 'card':                            $sys_card         += $p->amount; break;
                case 'neft': case 'cheque': case 'bank':$sys_bank         += $p->amount; break;
                case 'wallet':                          $sys_wallet       += $p->amount; break;
                case 'gift_voucher':                    $sys_gift_voucher += $p->amount; break;
            }
        }

        // 3. Refunds paid out during this shift reduce the relevant drawer
        // total — otherwise a cashier who correctly handed back cash gets
        // flagged with a false "shortage" at close time.
        $shift_end = $shift->closed_at ?: date('Y-m-d H:i:s');
        $sc_user = $this->db->where('id', $shift->cashier_id)->get('users')->row();
        $sc_loc  = ($sc_user && !empty($sc_user->location_id)) ? (int)$sc_user->location_id : 0;
        $this->db->select('refunds.*')
                 ->from('refunds')
                 ->join('invoices rf_inv', 'rf_inv.id = refunds.invoice_id', 'inner')
                 ->where('refunds.created_at >=', $shift->opened_at)
                 ->where('refunds.created_at <=', $shift_end)
                 ->where('refunds.status', 'processed');
        if ($sc_loc) $this->db->where('rf_inv.location_id', $sc_loc);
        $refunds = $this->db->get()->result();
        $total_refunds = 0;
        foreach ($refunds as $rf) {
            $total_refunds += $rf->refund_amount;
            switch ($rf->refund_mode) {
                case 'cash':                             $sys_cash -= $rf->refund_amount; break;
                case 'upi':                              $sys_upi  -= $rf->refund_amount; break;
                case 'card':                             $sys_card -= $rf->refund_amount; break;
                case 'neft': case 'cheque': case 'bank': $sys_bank -= $rf->refund_amount; break;
            }
        }

        $total_sales = array_sum(array_column($invoices, 'total_amount')) - $total_refunds;
        $expected_cash = $shift->opening_cash + $sys_cash;

        $data['page_title']        = 'Shift Close';
        $data['shift']             = $shift;
        $data['sys_cash']          = $sys_cash;
        $data['sys_upi']           = $sys_upi;
        $data['sys_card']          = $sys_card;
        $data['sys_bank']          = $sys_bank;
        $data['sys_wallet']        = $sys_wallet;
        $data['sys_credit']        = $sys_credit;
        $data['sys_gift_voucher']  = $sys_gift_voucher;
        $data['total_sales']       = $total_sales;
        $data['total_refunds']     = $total_refunds;
        $data['total_bills']       = $total_bills;
        $data['total_discounts']   = $total_discounts;
        $data['total_gst']         = $total_gst;
        $data['expected_cash']     = $expected_cash;

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('shifts/shift_close', $data);
            return;
        }

        // Read actual entries
        $act_cash         = (float)$this->input->post('actual_cash');
        $act_upi          = (float)$this->input->post('actual_upi');
        $act_card         = (float)$this->input->post('actual_card');
        $act_bank         = (float)$this->input->post('actual_bank');
        $act_wallet       = (float)$this->input->post('actual_wallet');
        $act_gift_voucher = (float)$this->input->post('actual_gift_voucher');
        $close_remarks    = $this->input->post('close_remarks', TRUE);

        // Denomination data
        $denomination = [
            500   => (int)$this->input->post('denom_count_500'),
            200   => (int)$this->input->post('denom_count_200'),
            100   => (int)$this->input->post('denom_count_100'),
            50    => (int)$this->input->post('denom_count_50'),
            20    => (int)$this->input->post('denom_count_20'),
            10    => (int)$this->input->post('denom_count_10'),
            5     => (int)$this->input->post('denom_count_5'),
            2     => (int)$this->input->post('denom_count_2'),
            1     => (int)$this->input->post('denom_count_1'),
            'coins' => (float)$this->input->post('denom_coins'),
        ];

        // Differences for all modes
        $diff_cash         = $act_cash         - $sys_cash;
        $diff_upi          = $act_upi          - $sys_upi;
        $diff_card         = $act_card         - $sys_card;
        $diff_bank         = $act_bank         - $sys_bank;
        $diff_wallet       = $act_wallet       - $sys_wallet;
        $diff_gift_voucher = $act_gift_voucher - $sys_gift_voucher;

        $sys_total = $sys_cash + $sys_upi + $sys_card + $sys_bank + $sys_wallet + $sys_gift_voucher;
        $act_total = $act_cash + $act_upi + $act_card + $act_bank + $act_wallet + $act_gift_voucher;
        $diff_total = $act_total - $sys_total;

        // Shortage & Excess across all modes
        $shortage = 0; $excess = 0;
        foreach ([$diff_cash, $diff_upi, $diff_card, $diff_bank, $diff_wallet, $diff_gift_voucher] as $diff) {
            if ($diff < 0) $shortage += abs($diff);
            else           $excess   += $diff;
        }

        $update_data = [
            'closed_at'            => date('Y-m-d H:i:s'),
            'status'               => 'closed',
            'system_cash'          => $sys_cash,
            'system_upi'           => $sys_upi,
            'system_card'          => $sys_card,
            'system_bank'          => $sys_bank,
            'system_wallet'        => $sys_wallet,
            'system_credit'        => $sys_credit,
            'system_gift_voucher'  => $sys_gift_voucher,
            'actual_cash'          => $act_cash,
            'actual_upi'           => $act_upi,
            'actual_card'          => $act_card,
            'actual_bank'          => $act_bank,
            'actual_wallet'        => $act_wallet,
            'actual_gift_voucher'  => $act_gift_voucher,
            'diff_cash'            => $diff_cash,
            'diff_upi'             => $diff_upi,
            'diff_card'            => $diff_card,
            'diff_bank'            => $diff_bank,
            'diff_wallet'          => $diff_wallet,
            'diff_gift_voucher'    => $diff_gift_voucher,
            'diff_total'           => $diff_total,
            'short_amount'         => $shortage,
            'excess_amount'        => $excess,
            'denomination_data'    => json_encode($denomination),
            'close_remarks'        => $close_remarks,
        ];

        $this->db->where('id', $shift->id)->update('cashier_shifts', $update_data);
        $this->session->unset_userdata('shift_id');

        $desc = "Closed shift #{$shift->id}. Shortage: ₹" . number_format($shortage, 2) . ", Excess: ₹" . number_format($excess, 2);
        $this->log_activity('shift_close', 'close', $desc, $shift->id, 'Shift #' . $shift->id);

        $this->session->set_flashdata('success', 'Shift closed successfully. Summary saved!');
        redirect('shifts/shift_report/' . $shift->id);
    }

    // invoice_id => amount collected later via Payments (knock-off of due)
    private function _later_paid_map($invoice_ids) {
        $map = [];
        if (empty($invoice_ids) || !$this->db->table_exists('payments')) return $map;
        $rows = $this->db->select('ref_id, SUM(amount) AS amt')
                         ->where('type', 'received')
                         ->where('ref_type', 'invoice')
                         ->where_in('ref_id', $invoice_ids)
                         ->group_by('ref_id')
                         ->get('payments')->result();
        foreach ($rows as $r) $map[$r->ref_id] = (float)$r->amt;
        return $map;
    }

    // Credit (due) bills raised in a shift — bill no, customer, amount, credit given
    private function _shift_credit_bills($shift_id) {
        $bills = $this->db->select('i.id, i.invoice_no, i.invoice_date, i.total_amount, i.paid_amount, i.due_amount, c.name AS customer_name, c.phone AS customer_phone')
                          ->from('invoices i')
                          ->join('customers c', 'c.id = i.customer_id', 'left')
                          ->where('i.shift_id', (int)$shift_id)
                          ->where('i.status !=', 'cancelled')
                          ->order_by('i.id', 'ASC')
                          ->get()->result();
        $later_paid = $this->_later_paid_map(array_column($bills, 'id'));
        $out = [];
        foreach ($bills as $b) {
            $later  = $later_paid[$b->id] ?? 0;
            $credit = round((float)$b->due_amount + $later, 2);
            if ($credit < 0.01) continue;
            $b->credit_amount = $credit;
            $b->paid_at_bill  = max(0, round((float)$b->paid_amount - $later, 2));
            $b->collected     = $later;
            $out[] = $b;
        }
        return $out;
    }

    // -------------------------------------------------------
    // Printable Shift Report (HTML & PDF)
    // -------------------------------------------------------
    public function shift_report($id = null) {
        if (!$id) redirect('dashboard');
        
        $shift = $this->db->select('cashier_shifts.*, users.name as cashier_name')
                          ->join('users', 'users.id = cashier_shifts.cashier_id')
                          ->where('cashier_shifts.id', $id)
                          ->get('cashier_shifts')->row();
        if (!$shift) show_404();

        // Calculate sales during this shift
        $sales = $this->db->select_sum('total_amount')->select_sum('paid_amount')
                          ->where('shift_id', $id)->where('status !=', 'cancelled')
                          ->get('invoices')->row();

        $shift_end = $shift->closed_at ?: date('Y-m-d H:i:s');
        $sr_user = $this->db->where('id', $shift->cashier_id)->get('users')->row();
        $sr_loc  = ($sr_user && !empty($sr_user->location_id)) ? (int)$sr_user->location_id : 0;
        $this->db->select_sum('refunds.refund_amount', 'total')
                 ->select('COUNT(refunds.id) as refund_count')
                 ->from('refunds')
                 ->join('invoices rf_inv', 'rf_inv.id = refunds.invoice_id', 'inner')
                 ->where('refunds.created_at >=', $shift->opened_at)
                 ->where('refunds.created_at <=', $shift_end)
                 ->where('refunds.status', 'processed');
        if ($sr_loc) $this->db->where('rf_inv.location_id', $sr_loc);
        $shift_refunds = $this->db->get()->row();

        // items sold
        $items_sold = $this->db->select_sum('invoice_items.qty', 'total_qty')
                               ->join('invoices', 'invoices.id = invoice_items.invoice_id')
                               ->where('invoices.shift_id', $id)->where('invoices.status !=', 'cancelled')
                               ->get('invoice_items')->row();

        // receipt nos
        $receipts = $this->db->select_min('invoice_no', 'first_receipt')
                             ->select_max('invoice_no', 'last_receipt')
                             ->where('shift_id', $id)->where('status !=', 'cancelled')
                             ->get('invoices')->row();

        // Payment mode changes (check if table exists to prevent DB error on older instances)
        $payment_mode_changes = 0;
        if ($this->db->table_exists('audit_payment_mode_changes')) {
            $payment_mode_changes = $this->db->join('invoices', 'invoices.id = audit_payment_mode_changes.invoice_id')
                                             ->where('invoices.shift_id', $id)
                                             ->count_all_results('audit_payment_mode_changes');
        }
                                         
        // bill count
        $bill_count = $this->db->where('shift_id', $id)->where('status !=', 'cancelled')->count_all_results('invoices');

        $data['page_title']  = 'Shift Summary Report #' . $id;
        $data['shift']       = $shift;
        $data['total_refunds'] = $shift_refunds->total ?? 0;
        $data['refund_count'] = $shift_refunds->refund_count ?? 0;
        $data['total_sales'] = ($sales->total_amount ?? 0) - $data['total_refunds'];
        $data['total_disc'] = $sales->discount ?? 0;
        $data['bill_count'] = $bill_count;
        $data['first_receipt'] = $receipts->first_receipt ?? '';
        $data['last_receipt'] = $receipts->last_receipt ?? '';
        $data['items_sold'] = $items_sold->total_qty ?? 0;
        $data['payment_mode_changes'] = $payment_mode_changes;
        $data['credit_bills'] = $this->_shift_credit_bills($id);
        $data['company']     = $this->db->get('company_settings')->row_array();

        // Cash Management integration (pay-in, pay-out, handovers)
        if ($this->db->table_exists('cash_transactions')) {
            $this->load->model('Cash_management_model');
            $loc_id = 0;
            $cashier = $this->db->where('id', $shift->cashier_id)->get('users')->row();
            if ($cashier && !empty($cashier->location_id)) $loc_id = (int)$cashier->location_id;
            if (!$loc_id) {
                $loc_id = $this->active_location_id();
                if (!$loc_id) { $first = $this->db->get('locations')->row(); $loc_id = $first ? (int)$first->id : 1; }
            }
            $shift_times = [$shift->opened_at, $shift->closed_at ?: date('Y-m-d H:i:s')];
            $data['cash_summary']      = $this->Cash_management_model->get_cash_summary($loc_id, $shift->business_date, (int)$shift->id, $shift_times);
            $data['cash_payins']       = $this->Cash_management_model->get_transactions($loc_id, $shift->business_date, (int)$shift->id, 'pay_in', $shift_times);
            $data['cash_payouts']      = $this->Cash_management_model->get_transactions($loc_id, $shift->business_date, (int)$shift->id, 'pay_out', $shift_times);
            $data['cash_handovers']    = $this->Cash_management_model->get_handovers($loc_id, $shift->business_date, (int)$shift->id, $shift_times);
        }

        if ($this->input->get('pdf') === '1') {
            $autoload = APPPATH . '../vendor/autoload.php';
            if (file_exists($autoload)) {
                $this->load->library('pdf_generator');
                $html = $this->load->view('shifts/shift_report_pdf', $data, TRUE);
                $this->pdf_generator->generate($html, 'Shift_Report_' . $id, 'download');
                return;
            }
        }

        $this->render('shifts/shift_report', $data);
    }

    // -------------------------------------------------------
    // 3-inch Thermal Print — Shift Close Receipt
    // -------------------------------------------------------
    public function shift_thermal($id = null) {
        if (!$id) redirect('dashboard');

        $shift = $this->db->select('cashier_shifts.*, users.name as cashier_name')
                          ->join('users', 'users.id = cashier_shifts.cashier_id')
                          ->where('cashier_shifts.id', $id)
                          ->get('cashier_shifts')->row();
        if (!$shift) show_404();

        // Sales in shift
        $sales = $this->db->select_sum('total_amount')->select_sum('discount')->select_sum('total_gst')
                          ->where('shift_id', $id)->where('status !=', 'cancelled')
                          ->get('invoices')->row();
        $bill_count       = $this->db->where('shift_id', $id)->where('status !=', 'cancelled')->count_all_results('invoices');
        $cancelled_count  = $this->db->where('shift_id', $id)->where('status', 'cancelled')->count_all_results('invoices');

        // Refunds (sales_returns) during this shift window
        $st_user = $this->db->where('id', $shift->cashier_id)->get('users')->row();
        $st_loc  = ($st_user && !empty($st_user->location_id)) ? (int)$st_user->location_id : 0;
        $this->db->select('COUNT(*) as cnt, SUM(sr.total_amount) as total, SUM(sr.total_gst) as gst')
                 ->from('sales_returns sr')
                 ->join('invoices sr_inv', 'sr_inv.id = sr.invoice_id', 'inner')
                 ->where('sr.created_at >=', $shift->opened_at)
                 ->where('sr.created_at <=', $shift->closed_at ?: date('Y-m-d H:i:s'))
                 ->where('sr.status !=', 'cancelled');
        if ($st_loc) $this->db->where('sr_inv.location_id', $st_loc);
        $refund_row = $this->db->get()->row();

        // Refunds table (direct cash refunds)
        $this->db->select('COUNT(refunds.id) as cnt, SUM(refunds.refund_amount) as total')
                 ->from('refunds')
                 ->join('invoices rf_inv', 'rf_inv.id = refunds.invoice_id', 'inner')
                 ->where('refunds.created_at >=', $shift->opened_at)
                 ->where('refunds.created_at <=', $shift->closed_at ?: date('Y-m-d H:i:s'))
                 ->where('refunds.refund_mode', 'cash')
                 ->where('refunds.status', 'processed');
        if ($st_loc) $this->db->where('rf_inv.location_id', $st_loc);
        $cash_refund_row = $this->db->get()->row();

        // Price changes / invoice edits during this shift
        $price_changes = $this->db->select('invoice_edit_log.invoice_no, invoice_edit_log.old_total, invoice_edit_log.new_total, invoice_edit_log.edited_by_name, invoice_edit_log.edited_at, invoice_edit_log.reason')
                                  ->join('invoices', 'invoices.id = invoice_edit_log.invoice_id')
                                  ->where('invoices.shift_id', $id)
                                  ->order_by('invoice_edit_log.edited_at', 'ASC')
                                  ->get('invoice_edit_log')->result();

        // items sold
        $items_sold = $this->db->select_sum('invoice_items.qty', 'total_qty')
                               ->join('invoices', 'invoices.id = invoice_items.invoice_id')
                               ->where('invoices.shift_id', $id)->where('invoices.status !=', 'cancelled')
                               ->get('invoice_items')->row();

        // receipt nos
        $receipts = $this->db->select_min('invoice_no', 'first_receipt')
                             ->select_max('invoice_no', 'last_receipt')
                             ->where('shift_id', $id)->where('status !=', 'cancelled')
                             ->get('invoices')->row();

        // Payment mode changes
        $payment_mode_changes = 0;
        if ($this->db->table_exists('audit_payment_mode_changes')) {
            $payment_mode_changes = $this->db->join('invoices', 'invoices.id = audit_payment_mode_changes.invoice_id')
                                             ->where('invoices.shift_id', $id)
                                             ->count_all_results('audit_payment_mode_changes');
        }

        $this->load->model('Invoice_model');
        $company = $this->Invoice_model->get_company_settings();

        $data['shift']            = $shift;
        $data['total_sales']      = $sales->total_amount ?? 0;
        $data['total_gst']        = $sales->total_gst ?? 0;
        $data['total_disc']       = $sales->discount ?? 0;
        $data['bill_count']       = $bill_count;
        $data['cancelled_count']  = $cancelled_count;
        $data['refund_count']     = $refund_row->cnt ?? 0;
        $data['refund_total']     = $refund_row->total ?? 0;
        $data['refund_gst']       = $refund_row->gst ?? 0;
        $data['cash_refund_count']= $cash_refund_row->cnt ?? 0;
        $data['cash_refund_total']= $cash_refund_row->total ?? 0;
        $data['first_receipt']    = $receipts->first_receipt ?? '';
        $data['last_receipt']     = $receipts->last_receipt ?? '';
        $data['items_sold']       = $items_sold->total_qty ?? 0;
        $data['payment_mode_changes'] = $payment_mode_changes;
        $data['credit_bills']     = $this->_shift_credit_bills($id);
        $data['price_changes']    = $price_changes;
        $data['company']          = $company;
        $data['denom']            = !empty($shift->denomination_data) ? json_decode($shift->denomination_data, true) : [];

        // Cash Management integration
        if ($this->db->table_exists('cash_transactions')) {
            $this->load->model('Cash_management_model');
            $loc_id = 0;
            $cashier = $this->db->where('id', $shift->cashier_id)->get('users')->row();
            if ($cashier && !empty($cashier->location_id)) $loc_id = (int)$cashier->location_id;
            if (!$loc_id) {
                $loc_id = $this->active_location_id();
                if (!$loc_id) { $first = $this->db->get('locations')->row(); $loc_id = $first ? (int)$first->id : 1; }
            }
            $shift_times = [$shift->opened_at, $shift->closed_at ?: date('Y-m-d H:i:s')];
            $data['cash_summary']      = $this->Cash_management_model->get_cash_summary($loc_id, $shift->business_date, (int)$shift->id, $shift_times);
            $data['cash_payins']       = $this->Cash_management_model->get_transactions($loc_id, $shift->business_date, (int)$shift->id, 'pay_in', $shift_times);
            $data['cash_payouts']      = $this->Cash_management_model->get_transactions($loc_id, $shift->business_date, (int)$shift->id, 'pay_out', $shift_times);
            $data['cash_handovers']    = $this->Cash_management_model->get_handovers($loc_id, $shift->business_date, (int)$shift->id, $shift_times);
        }

        // standalone page — no ERP layout
        $this->load->view('shifts/shift_thermal', $data);
    }

    // -------------------------------------------------------
    // Shift Reports List — reprint any shift's receipt
    // -------------------------------------------------------
    public function shift_reports_list() {
        $from  = $this->input->get('from')     ?: date('Y-m-01');
        $to    = $this->input->get('to')       ?: date('Y-m-d');
        $uid   = $this->input->get('cashier')  ?: '';
        $status= $this->input->get('status')   ?: 'closed';

        $loc_id = $this->active_location_id();
        $this->db->select('cashier_shifts.*, users.name as cashier_name, users.location_id as cashier_location_id')
                 ->join('users', 'users.id = cashier_shifts.cashier_id')
                 ->where('DATE(cashier_shifts.opened_at) >=', $from)
                 ->where('DATE(cashier_shifts.opened_at) <=', $to);
        if ($uid)    $this->db->where('cashier_shifts.cashier_id', $uid);
        if ($status) $this->db->where('cashier_shifts.status', $status);
        if ($loc_id) {
            $this->db->group_start()
                ->where('users.location_id', $loc_id)
                ->or_where('users.location_id IS NULL', null, false)
            ->group_end();
        }
        $shifts = $this->db->order_by('cashier_shifts.opened_at', 'DESC')->get('cashier_shifts')->result();

        // Enrich each shift with bill count, sales, and handover totals
        $has_cash_mgmt = $this->db->table_exists('cash_handovers');
        foreach ($shifts as &$s) {
            $row = $this->db->select_sum('total_amount')->select_sum('total_gst')
                            ->where('shift_id', $s->id)->where('status !=', 'cancelled')
                            ->get('invoices')->row();
            $s->bill_count   = $this->db->where('shift_id', $s->id)->where('status !=', 'cancelled')->count_all_results('invoices');
            $s_end = $s->closed_at ?: date('Y-m-d H:i:s');
            $s_loc = !empty($s->cashier_location_id) ? (int)$s->cashier_location_id : 0;
            $this->db->select_sum('refunds.refund_amount', 'total')
                     ->from('refunds')
                     ->join('invoices rf_inv', 'rf_inv.id = refunds.invoice_id', 'inner')
                     ->where('refunds.created_at >=', $s->opened_at)
                     ->where('refunds.created_at <=', $s_end)
                     ->where('refunds.status', 'processed');
            if ($s_loc) $this->db->where('rf_inv.location_id', $s_loc);
            $refund_row = $this->db->get()->row();
            $s->total_refunds = $refund_row->total ?? 0;
            $s->total_sales  = ($row->total_amount ?? 0) - $s->total_refunds;

            $s->handover_total = 0;
            if ($has_cash_mgmt) {
                $ho = $this->db->select_sum('handover_amount')
                               ->where('status !=', 'cancelled')
                               ->group_start()
                                   ->where('shift_id', $s->id)
                                   ->or_group_start()
                                       ->where('shift_id IS NULL', null, false)
                                       ->where('created_at >=', $s->opened_at)
                                       ->where('created_at <=', $s_end)
                                   ->group_end()
                               ->group_end()
                               ->get('cash_handovers')->row();
                $s->handover_total = (float)($ho->handover_amount ?? 0);
            }
        }

        $this->db->select('id, name')->order_by('name');
        if ($loc_id) {
            $this->db->group_start()
                ->where('location_id', $loc_id)
                ->or_where('location_id IS NULL', null, false)
            ->group_end();
        }
        $cashiers = $this->db->get('users')->result();

        $this->load->model('Invoice_model');
        $data['page_title'] = 'Shift Reports';
        $data['shifts']     = $shifts;
        $data['cashiers']   = $cashiers;
        $data['from']       = $from;
        $data['to']         = $to;
        $data['filter_uid'] = $uid;
        $data['filter_status'] = $status;
        $data['company']    = $this->Invoice_model->get_company_settings();
        $this->render('shifts/shift_reports_list', $data);
    }

    // -------------------------------------------------------
    // Cashier Shortage / Excess Report
    // -------------------------------------------------------
    public function cashier_shortage_report() {
        $from   = $this->input->get('from')    ?: date('Y-m-01');
        $to     = $this->input->get('to')      ?: date('Y-m-d');
        $uid    = $this->input->get('cashier') ?: '';
        $view   = $this->input->get('view')    ?: 'summary'; // summary | detail

        // ── base query ──────────────────────────────────────────
        $loc_id = $this->active_location_id();
        $this->db
            ->select('cashier_shifts.*, users.name AS cashier_name, users.location_id AS cashier_location_id')
            ->join('users', 'users.id = cashier_shifts.cashier_id')
            ->where('cashier_shifts.status', 'closed')
            ->where('DATE(cashier_shifts.opened_at) >=', $from)
            ->where('DATE(cashier_shifts.opened_at) <=', $to);
        if ($uid) $this->db->where('cashier_shifts.cashier_id', $uid);
        if ($loc_id) {
            $this->db->group_start()
                ->where('users.location_id', $loc_id)
                ->or_where('users.location_id IS NULL', null, false)
            ->group_end();
        }
        $shifts = $this->db->order_by('cashier_shifts.opened_at', 'ASC')->get('cashier_shifts')->result();

        // ── enrich each shift ────────────────────────────────────
        foreach ($shifts as &$s) {
            $r = $this->db->select_sum('total_amount')->select_sum('total_gst')->select_sum('discount')
                          ->where('shift_id', $s->id)->where('status !=', 'cancelled')
                          ->get('invoices')->row();
            $s->bill_count   = $this->db->where('shift_id', $s->id)->where('status !=', 'cancelled')->count_all_results('invoices');
            $s_end = $s->closed_at ?: date('Y-m-d H:i:s');
            $s_loc = !empty($s->cashier_location_id) ? (int)$s->cashier_location_id : 0;
            $this->db->select_sum('refunds.refund_amount', 'total')
                     ->from('refunds')
                     ->join('invoices rf_inv', 'rf_inv.id = refunds.invoice_id', 'inner')
                     ->where('refunds.created_at >=', $s->opened_at)
                     ->where('refunds.created_at <=', $s_end)
                     ->where('refunds.status', 'processed');
            if ($s_loc) $this->db->where('rf_inv.location_id', $s_loc);
            $refund_row = $this->db->get()->row();
            $s->total_refunds = $refund_row->total ?? 0;
            $s->total_sales  = ($r->total_amount ?? 0) - $s->total_refunds;
            $s->total_gst    = $r->total_gst    ?? 0;
            $s->total_disc   = $r->discount     ?? 0;
            $s->net_diff     = (float)$s->excess_amount - (float)$s->short_amount;
        }

        // ── summary rolled up per cashier ────────────────────────
        $summary = [];
        foreach ($shifts as $s) {
            $cid = $s->cashier_id;
            if (!isset($summary[$cid])) {
                $summary[$cid] = [
                    'cashier_id'   => $cid,
                    'cashier_name' => $s->cashier_name,
                    'shifts'       => 0,
                    'bills'        => 0,
                    'total_sales'  => 0,
                    'short_total'  => 0,
                    'excess_total' => 0,
                    'net_diff'     => 0,
                    'worst_short'  => 0,   // biggest single shortage
                    'worst_shift'  => null,
                ];
            }
            $summary[$cid]['shifts']       += 1;
            $summary[$cid]['bills']        += (int)$s->bill_count;
            $summary[$cid]['total_sales']  += (float)$s->total_sales;
            $summary[$cid]['short_total']  += (float)$s->short_amount;
            $summary[$cid]['excess_total'] += (float)$s->excess_amount;
            $summary[$cid]['net_diff']     += (float)$s->net_diff;
            if ((float)$s->short_amount > $summary[$cid]['worst_short']) {
                $summary[$cid]['worst_short'] = (float)$s->short_amount;
                $summary[$cid]['worst_shift'] = $s->id;
            }
        }
        // sort by total shortage desc
        usort($summary, fn($a,$b) => $b['short_total'] <=> $a['short_total']);

        // ── daily totals (for chart data) ───────────────────────
        $daily = [];
        foreach ($shifts as $s) {
            $day = date('Y-m-d', strtotime($s->opened_at));
            if (!isset($daily[$day])) $daily[$day] = ['short'=>0,'excess'=>0,'sales'=>0];
            $daily[$day]['short']  += (float)$s->short_amount;
            $daily[$day]['excess'] += (float)$s->excess_amount;
            $daily[$day]['sales']  += (float)$s->total_sales;
        }

        $this->db->select('id,name')->order_by('name');
        if ($loc_id) {
            $this->db->group_start()
                ->where('location_id', $loc_id)
                ->or_where('location_id IS NULL', null, false)
            ->group_end();
        }
        $cashiers = $this->db->get('users')->result();
        $this->load->model('Invoice_model');

        $data['page_title']   = 'Cashier Shortage & Excess Report';
        $data['shifts']       = $shifts;
        $data['summary']      = $summary;
        $data['daily']        = $daily;
        $data['cashiers']     = $cashiers;
        $data['from']         = $from;
        $data['to']           = $to;
        $data['filter_uid']   = $uid;
        $data['view']         = $view;
        $data['company']      = $this->Invoice_model->get_company_settings();

        // totals across all
        $data['grand_short']  = array_sum(array_column($shifts, 'short_amount'));
        $data['grand_excess'] = array_sum(array_column($shifts, 'excess_amount'));
        $data['grand_sales']  = array_sum(array_column($shifts, 'total_sales'));
        $data['grand_shifts'] = count($shifts);

        $this->render('shifts/cashier_shortage_report', $data);
    }

    // -------------------------------------------------------
    // One-time DB migration: add location_id to business_days
    // DELETE THIS METHOD after running on all instances!
    // -------------------------------------------------------
    public function migrate_business_days_location() {
        $this->require_superadmin();
        header('Content-Type: text/plain');
        $results = [];

        $col_check = $this->db->query("SHOW COLUMNS FROM business_days LIKE 'location_id'");
        if ($col_check->num_rows() === 0) {
            $r = $this->db->query("ALTER TABLE business_days ADD COLUMN location_id INT(11) NOT NULL DEFAULT 0 AFTER business_date");
            $results[] = 'Add column location_id: ' . ($r ? 'OK' : 'FAIL');
        } else {
            $results[] = 'Column location_id: already exists';
        }

        $idx_check = $this->db->query("SHOW INDEX FROM business_days WHERE Key_name = 'business_date'");
        if ($idx_check->num_rows() > 0) {
            $r = $this->db->query("ALTER TABLE business_days DROP INDEX business_date");
            $results[] = 'Drop old unique key: ' . ($r ? 'OK' : 'FAIL');
        } else {
            $results[] = 'Drop old unique key: skipped (not found)';
        }

        $idx2_check = $this->db->query("SHOW INDEX FROM business_days WHERE Key_name = 'business_date_location'");
        if ($idx2_check->num_rows() > 0) {
            $results[] = 'Composite unique key: already exists';
        } else {
            $r = $this->db->query("ALTER TABLE business_days ADD UNIQUE KEY business_date_location (business_date, location_id)");
            $results[] = 'Add composite unique key: ' . ($r ? 'OK' : 'FAIL');
        }

        echo "Migration Results:\n";
        foreach ($results as $r) echo "  - $r\n";

        $desc = $this->db->query("DESCRIBE business_days");
        echo "\nColumns:\n";
        foreach ($desc->result_array() as $row) {
            echo "  {$row['Field']} ({$row['Type']}) {$row['Key']}\n";
        }

        $rows = $this->db->order_by('id', 'DESC')->limit(10)->get('business_days');
        echo "\nRecent rows:\n";
        foreach ($rows->result() as $row) {
            echo "  id={$row->id} date={$row->business_date} loc=" . ($row->location_id ?? 'N/A') . " status={$row->status}\n";
        }
        echo "\nDone!\n";
        exit;
    }

    // -------------------------------------------------------
    // Placeholder router for hotel operations
    // -------------------------------------------------------
    public function placeholder($module_name = '') {
        $data['page_title']  = 'Hotel Ops: ' . str_replace('_', ' ', $module_name);
        $data['module_name'] = str_replace('_', ' ', $module_name);
        $this->render('shifts/placeholder', $data);
    }

    public function diagnose_shift($id = null) {
        $this->require_admin();
        if (!$id) { echo 'Pass shift id'; return; }

        $shift = $this->db->where('id', $id)->get('cashier_shifts')->row();
        if (!$shift) { echo 'Shift not found'; return; }

        header('Content-Type: text/html; charset=utf-8');
        echo "<h3>Shift #{$id} Diagnostic</h3>";
        echo "<p>Cashier ID: {$shift->cashier_id} | Opened: {$shift->opened_at} | Closed: {$shift->closed_at}</p>";
        echo "<p>Opening Cash: {$shift->opening_cash} | Stored system_cash: {$shift->system_cash}</p>";

        // 1. Invoice payment mode breakdown
        $modes = $this->db->select('payment_mode, COUNT(*) as cnt, SUM(paid_amount) as total_paid, SUM(total_amount) as total_amt')
                          ->where('shift_id', $id)->where('status !=', 'cancelled')
                          ->group_by('payment_mode')->get('invoices')->result();
        echo "<h4>Invoices by payment_mode (shift_id={$id})</h4><table border=1 cellpadding=4>";
        echo "<tr><th>payment_mode</th><th>Count</th><th>paid_amount</th><th>total_amount</th></tr>";
        foreach ($modes as $m) {
            echo "<tr><td>{$m->payment_mode}</td><td>{$m->cnt}</td><td>{$m->total_paid}</td><td>{$m->total_amt}</td></tr>";
        }
        echo "</table>";

        // 2. Split payment breakdown
        $splits = $this->db->select('ips.payment_mode, COUNT(*) as cnt, SUM(ips.amount) as total')
                           ->from('invoice_payment_splits ips')
                           ->join('invoices i', 'i.id = ips.invoice_id')
                           ->where('i.shift_id', $id)->where('i.status !=', 'cancelled')
                           ->group_by('ips.payment_mode')->get()->result();
        echo "<h4>Split payment breakdown</h4><table border=1 cellpadding=4>";
        echo "<tr><th>split mode</th><th>Count</th><th>Total</th></tr>";
        foreach ($splits as $s) {
            echo "<tr><td>{$s->payment_mode}</td><td>{$s->cnt}</td><td>{$s->total}</td></tr>";
        }
        echo "</table>";

        // 3. Unlinked invoices during shift window
        $unlinked = $this->db->select('id, invoice_no, payment_mode, paid_amount, total_amount, created_by, shift_id, created_at')
                             ->where('created_at >=', $shift->opened_at)
                             ->where('created_at <=', $shift->closed_at ?: date('Y-m-d H:i:s'))
                             ->where('status !=', 'cancelled')
                             ->where('shift_id !=', $id)
                             ->get('invoices')->result();
        echo "<h4>Invoices during shift window NOT linked to shift #{$id}</h4><table border=1 cellpadding=4>";
        echo "<tr><th>ID</th><th>Invoice#</th><th>Mode</th><th>Paid</th><th>Total</th><th>created_by</th><th>shift_id</th><th>created_at</th></tr>";
        foreach ($unlinked as $u) {
            echo "<tr><td>{$u->id}</td><td>{$u->invoice_no}</td><td>{$u->payment_mode}</td><td>{$u->paid_amount}</td><td>{$u->total_amount}</td><td>{$u->created_by}</td><td>{$u->shift_id}</td><td>{$u->created_at}</td></tr>";
        }
        if (empty($unlinked)) echo "<tr><td colspan=8>None</td></tr>";
        echo "</table>";

        // 4. Payments received during shift
        $payments = $this->db->where('shift_id', $id)->get('payments')->result();
        echo "<h4>Payments received (shift_id={$id})</h4><table border=1 cellpadding=4>";
        echo "<tr><th>ID</th><th>Mode</th><th>Amount</th><th>Type</th></tr>";
        foreach ($payments as $p) {
            echo "<tr><td>{$p->id}</td><td>{$p->payment_mode}</td><td>{$p->amount}</td><td>{$p->type}</td></tr>";
        }
        if (empty($payments)) echo "<tr><td colspan=4>None</td></tr>";
        echo "</table>";

        // 5. Sample invoices (first 10 cash-mode)
        $sample = $this->db->where('shift_id', $id)->where('status !=', 'cancelled')
                           ->order_by('id')->limit(15)->get('invoices')->result();
        echo "<h4>First 15 invoices in shift</h4><table border=1 cellpadding=4>";
        echo "<tr><th>ID</th><th>Invoice#</th><th>Mode</th><th>multi_payment</th><th>Paid</th><th>Total</th><th>Due</th><th>created_by</th></tr>";
        foreach ($sample as $s) {
            $mp = isset($s->multi_payment) ? $s->multi_payment : 'N/A';
            echo "<tr><td>{$s->id}</td><td>{$s->invoice_no}</td><td>{$s->payment_mode}</td><td>{$mp}</td><td>{$s->paid_amount}</td><td>{$s->total_amount}</td><td>{$s->due_amount}</td><td>{$s->created_by}</td></tr>";
        }
        echo "</table>";
    }
}
