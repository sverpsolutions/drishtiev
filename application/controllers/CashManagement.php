<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CashManagement extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Cash_management_model', 'Location_model']);
        $this->_auto_migrate();
    }

    private function _auto_migrate() {
        foreach (['cash_categories', 'cash_transactions', 'cash_handovers', 'cash_adjustments'] as $t) {
            if (!$this->db->table_exists($t)) { $missing = true; break; }
        }
        if (!empty($missing)) {
            $sql = file_get_contents(FCPATH . 'sql/57_cash_management.sql');
            foreach (explode(';', $sql) as $stmt) {
                $stmt = trim($stmt);
                if ($stmt) $this->db->query($stmt);
            }
        }
    }

    private function _current_business_date($loc_id = 0) {
        $q = $this->db->select('business_date')->where('status', 'open');
        if ($this->db->field_exists('location_id', 'business_days') && $loc_id > 0) {
            $q->group_start()->where('location_id', $loc_id)->or_where('location_id', 0)->group_end();
        }
        $row = $q->order_by('id', 'DESC')->get('business_days')->row();
        return $row ? $row->business_date : date('Y-m-d');
    }

    // ── Dashboard ───────────────────────────────────────────

    public function index() {
        $loc_id = $this->active_location_id();
        if (!$loc_id) {
            $first = $this->db->where('status', 1)->order_by('id')->get('locations')->row();
            $loc_id = $first ? $first->id : 1;
        }

        $biz_date = $this->input->get('date') ?: $this->_current_business_date($loc_id);
        $shift_id = $this->input->get('shift_id') ?: null;

        $data['page_title']    = 'Cash Management';
        $data['locations']     = $this->Location_model->get_all();
        $data['current_loc']   = (int)$loc_id;
        $data['business_date'] = $biz_date;
        $data['shift_id']      = $shift_id;
        $data['summary']       = $this->Cash_management_model->get_cash_summary($loc_id, $biz_date, $shift_id);
        $data['sales']         = $this->Cash_management_model->get_sales_breakdown($loc_id, $biz_date, $shift_id);
        $data['ledger']        = $this->Cash_management_model->get_ledger_entries($loc_id, $biz_date, $shift_id);
        $data['transactions']  = $this->Cash_management_model->get_transactions($loc_id, $biz_date, $shift_id);
        $data['handovers']     = $this->Cash_management_model->get_handovers($loc_id, $biz_date, $shift_id);
        $data['payin_cats']    = $this->Cash_management_model->get_categories('pay_in');
        $data['payout_cats']   = $this->Cash_management_model->get_categories('pay_out');
        $data['users']         = $this->db->select('id, name, role')->where('status', 1)->order_by('name')->get('users')->result();

        $this->db->select('cs.id, cs.shift_name, cs.cashier_id, u.name AS cashier_name, cs.status, cs.opening_cash')
            ->from('cashier_shifts cs')
            ->join('users u', 'u.id = cs.cashier_id', 'left')
            ->where('cs.business_date', $biz_date);
        if ($loc_id) {
            $this->db->group_start()
                ->where('u.location_id', $loc_id)
                ->or_where('u.location_id IS NULL', null, false)
            ->group_end();
        }
        $data['shifts'] = $this->db->order_by('cs.id')->get()->result();

        $this->render('cash_management/index', $data);
    }

    // ── Pay-In ──────────────────────────────────────────────

    public function save_payin() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('cashManagement');

        $loc_id    = (int)$this->input->post('location_id', TRUE) ?: ($this->active_location_id() ?: 1);
        $biz_date  = $this->input->post('business_date', TRUE) ?: date('Y-m-d');
        $shift_id  = $this->input->post('shift_id', TRUE) ?: ($this->session->userdata('shift_id') ?: null);
        $amount    = (float)$this->input->post('amount', TRUE);

        if ($amount <= 0) {
            $this->session->set_flashdata('error', 'Amount must be greater than zero.');
            redirect('cashManagement?date=' . $biz_date);
        }

        $data = [
            'txn_no'        => $this->Cash_management_model->get_next_txn_no('pay_in'),
            'type'          => 'pay_in',
            'location_id'   => $loc_id,
            'business_date' => $biz_date,
            'shift_id'      => $shift_id,
            'user_id'       => $this->session->userdata('user_id'),
            'category_id'   => (int)$this->input->post('category_id', TRUE) ?: null,
            'amount'        => $amount,
            'description'   => $this->input->post('description', TRUE),
            'reference_no'  => $this->input->post('reference_no', TRUE),
            'created_by'    => $this->session->userdata('user_id'),
        ];

        $id = $this->Cash_management_model->save_transaction($data);
        $this->log_activity('cash_management', 'pay_in', 'Pay-In ' . $data['txn_no'] . ' — Rs.' . number_format($amount, 2), $id);
        $this->session->set_flashdata('success', 'Pay-In ' . $data['txn_no'] . ' recorded — Rs. ' . number_format($amount, 2));
        redirect('cashManagement?date=' . $biz_date);
    }

    // ── Pay-Out ─────────────────────────────────────────────

    public function save_payout() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('cashManagement');

        $loc_id    = (int)$this->input->post('location_id', TRUE) ?: ($this->active_location_id() ?: 1);
        $biz_date  = $this->input->post('business_date', TRUE) ?: date('Y-m-d');
        $shift_id  = $this->input->post('shift_id', TRUE) ?: ($this->session->userdata('shift_id') ?: null);
        $amount    = (float)$this->input->post('amount', TRUE);

        if ($amount <= 0) {
            $this->session->set_flashdata('error', 'Amount must be greater than zero.');
            redirect('cashManagement?date=' . $biz_date);
        }

        $data = [
            'txn_no'        => $this->Cash_management_model->get_next_txn_no('pay_out'),
            'type'          => 'pay_out',
            'location_id'   => $loc_id,
            'business_date' => $biz_date,
            'shift_id'      => $shift_id,
            'user_id'       => $this->session->userdata('user_id'),
            'category_id'   => (int)$this->input->post('category_id', TRUE) ?: null,
            'amount'        => $amount,
            'paid_to'       => $this->input->post('paid_to', TRUE),
            'description'   => $this->input->post('description', TRUE),
            'reference_no'  => $this->input->post('reference_no', TRUE),
            'created_by'    => $this->session->userdata('user_id'),
        ];

        $id = $this->Cash_management_model->save_transaction($data);
        $this->log_activity('cash_management', 'pay_out', 'Pay-Out ' . $data['txn_no'] . ' — Rs.' . number_format($amount, 2), $id);
        $this->session->set_flashdata('success', 'Pay-Out ' . $data['txn_no'] . ' recorded — Rs. ' . number_format($amount, 2));
        redirect('cashManagement?date=' . $biz_date);
    }

    // ── Void Transaction ────────────────────────────────────

    public function void_transaction() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('cashManagement');
        $this->require_admin();

        $id = (int)$this->input->post('txn_id', TRUE);
        $reason = $this->input->post('void_reason', TRUE);
        $txn = $this->Cash_management_model->get_transaction($id);
        if (!$txn) {
            $this->session->set_flashdata('error', 'Transaction not found.');
            redirect('cashManagement');
        }

        $this->Cash_management_model->void_transaction($id, $this->session->userdata('user_id'), $reason);
        $this->log_activity('cash_management', 'void', 'Voided ' . $txn->txn_no . ' — Rs.' . number_format($txn->amount, 2) . ' Reason: ' . $reason, $id);
        $this->session->set_flashdata('success', $txn->txn_no . ' has been voided.');
        redirect('cashManagement?date=' . $txn->business_date);
    }

    // ── Cash Handover ───────────────────────────────────────

    public function handover() {
        $loc_id   = $this->active_location_id();
        if (!$loc_id) {
            $first = $this->db->where('status', 1)->order_by('id')->get('locations')->row();
            $loc_id = $first ? $first->id : 1;
        }
        $biz_date = $this->input->get('date') ?: $this->_current_business_date($loc_id);
        $shift_id = $this->input->get('shift_id') ?: ($this->session->userdata('shift_id') ?: null);

        $data['page_title']    = 'Cash Handover';
        $data['locations']     = $this->Location_model->get_all();
        $data['current_loc']   = (int)$loc_id;
        $data['business_date'] = $biz_date;
        $data['shift_id']      = $shift_id;
        $data['summary']       = $this->Cash_management_model->get_cash_summary($loc_id, $biz_date, $shift_id);
        $data['users']         = $this->db->select('id, name, role')->where('status', 1)->order_by('name')->get('users')->result();
        $data['handovers']     = $this->Cash_management_model->get_handovers($loc_id, $biz_date, $shift_id);
        $this->render('cash_management/handover', $data);
    }

    public function save_handover() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('cashManagement');

        $loc_id          = (int)$this->input->post('location_id', TRUE) ?: ($this->active_location_id() ?: 1);
        $biz_date        = $this->input->post('business_date', TRUE) ?: date('Y-m-d');
        $shift_id        = $this->input->post('shift_id', TRUE) ?: null;
        $actual_cash     = (float)$this->input->post('actual_cash', TRUE);
        $handover_amount = (float)$this->input->post('handover_amount', TRUE);
        $to_user_id      = (int)$this->input->post('to_user_id', TRUE);

        if ($handover_amount <= 0) {
            $this->session->set_flashdata('error', 'Handover amount must be greater than zero.');
            redirect('cashManagement/handover?date=' . $biz_date);
        }
        if (!$to_user_id) {
            $this->session->set_flashdata('error', 'Please select who you are handing over to.');
            redirect('cashManagement/handover?date=' . $biz_date);
        }

        $summary = $this->Cash_management_model->get_cash_summary($loc_id, $biz_date, $shift_id);
        $expected = $summary['expected_cash'];
        $remaining = $actual_cash - $handover_amount;
        $short = max(0, $expected - $actual_cash);
        $excess = max(0, $actual_cash - $expected);

        $data = [
            'handover_no'     => $this->Cash_management_model->get_next_handover_no(),
            'location_id'     => $loc_id,
            'business_date'   => $biz_date,
            'shift_id'        => $shift_id,
            'from_user_id'    => $this->session->userdata('user_id'),
            'to_user_id'      => $to_user_id,
            'expected_cash'   => $expected,
            'actual_cash'     => $actual_cash,
            'handover_amount' => $handover_amount,
            'remaining_cash'  => $remaining,
            'short_amount'    => $short,
            'excess_amount'   => $excess,
            'remarks'         => $this->input->post('remarks', TRUE),
        ];

        $id = $this->Cash_management_model->save_handover($data);

        if ($short > 0 || $excess > 0) {
            $this->Cash_management_model->save_adjustment([
                'location_id'     => $loc_id,
                'business_date'   => $biz_date,
                'shift_id'        => $shift_id,
                'user_id'         => $this->session->userdata('user_id'),
                'type'            => $short > 0 ? 'short' : 'excess',
                'amount'          => $short > 0 ? $short : $excess,
                'reason'          => 'Auto-detected during handover #' . $data['handover_no'],
                'remarks'         => $this->input->post('difference_remarks', TRUE),
                'approval_status' => 'pending',
                'created_by'      => $this->session->userdata('user_id'),
            ]);
        }

        $this->log_activity('cash_management', 'handover', $data['handover_no'] . ' — Handed Rs.' . number_format($handover_amount, 2) . ' to user #' . $to_user_id, $id);
        $this->session->set_flashdata('success', 'Cash Handover ' . $data['handover_no'] . ' completed — Rs. ' . number_format($handover_amount, 2) . ' handed over.');
        redirect('cashManagement?date=' . $biz_date);
    }

    // ── Categories ──────────────────────────────────────────

    public function categories() {
        $this->require_admin();
        $data['page_title']   = 'Cash Categories';
        $data['payin_cats']   = $this->Cash_management_model->get_categories('pay_in', false);
        $data['payout_cats']  = $this->Cash_management_model->get_categories('pay_out', false);
        $this->render('cash_management/categories', $data);
    }

    public function save_category() {
        $this->require_admin();
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('cashManagement/categories');

        $id   = (int)$this->input->post('id', TRUE);
        $data = [
            'name'        => $this->input->post('name', TRUE),
            'type'        => $this->input->post('type', TRUE),
            'description' => $this->input->post('description', TRUE),
            'status'      => (int)$this->input->post('status', TRUE),
            'sort_order'  => (int)$this->input->post('sort_order', TRUE),
        ];

        $this->Cash_management_model->save_category($data, $id ?: null);
        $this->session->set_flashdata('success', 'Category saved.');
        redirect('cashManagement/categories');
    }

    // ── Ledger Report ───────────────────────────────────────

    public function ledger() {
        $loc_id    = $this->input->get('location_id') ?: ($this->active_location_id() ?: null);
        $from_date = $this->input->get('from_date') ?: date('Y-m-01');
        $to_date   = $this->input->get('to_date') ?: date('Y-m-d');
        $type      = $this->input->get('type') ?: null;

        $data['page_title']    = 'Cash Ledger Report';
        $data['locations']     = $this->Location_model->get_all();
        $data['current_loc']   = $loc_id;
        $data['from_date']     = $from_date;
        $data['to_date']       = $to_date;
        $data['filter_type']   = $type;
        $data['transactions']  = $this->Cash_management_model->get_transactions_range($loc_id, $from_date, $to_date, $type);
        $data['handovers']     = $this->Cash_management_model->get_handovers_range($loc_id, $from_date, $to_date);
        $this->render('cash_management/ledger', $data);
    }

    // ── AJAX: Cash summary for current context ──────────────

    public function summary_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');
        $loc_id   = (int)$this->input->get('location_id') ?: ($this->active_location_id() ?: 1);
        $biz_date = $this->input->get('date') ?: date('Y-m-d');
        $shift_id = $this->input->get('shift_id') ?: null;
        echo json_encode([
            'success' => true,
            'summary' => $this->Cash_management_model->get_cash_summary($loc_id, $biz_date, $shift_id),
            'sales'   => $this->Cash_management_model->get_sales_breakdown($loc_id, $biz_date, $shift_id),
        ]);
    }

    // ── Adjustment approval ─────────────────────────────────

    public function approve_adjustment() {
        $this->require_admin();
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('cashManagement');

        $id     = (int)$this->input->post('adj_id', TRUE);
        $status = $this->input->post('approval_status', TRUE);
        $this->Cash_management_model->approve_adjustment($id, $this->session->userdata('user_id'), $status);
        $this->log_activity('cash_management', 'approve_adj', ucfirst($status) . ' cash adjustment #' . $id, $id);
        $this->session->set_flashdata('success', 'Adjustment ' . $status . '.');
        redirect('cashManagement');
    }
}
