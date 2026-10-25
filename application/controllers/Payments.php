<?php
// ============================================================
// FILE: application/controllers/Payments.php
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Payment_model', 'Invoice_model', 'Purchase_model',
                            'Customer_model', 'Supplier_model']);
        $this->load->library('form_validation');
    }

    // -------------------------------------------------------
    // List all payments
    // -------------------------------------------------------
    public function index() {
        $data['page_title']  = 'Payments';
        $data['payments']    = $this->Payment_model->get_all();
        $data['total_in']    = $this->Payment_model->total_by_type('received');
        $data['total_out']   = $this->Payment_model->total_by_type('paid');
        $this->render('payments/index', $data);
    }

    // -------------------------------------------------------
    // Record payment received from customer (against invoice)
    // -------------------------------------------------------
    public function receive() {
        $data['page_title'] = 'Record Customer Payment';
        // Only customers who actually owe money, labelled "Name — phone — Due ₹x"
        // (due = same unpaid/partial bills the knock-off panel loads).
        $data['customers'] = [];
        $due_rows = $this->db->query(
            "SELECT c.id, c.name, c.phone, SUM(i.due_amount) AS due
               FROM invoices i JOIN customers c ON c.id = i.customer_id
              WHERE i.status IN ('unpaid','partial') AND i.due_amount > 0.004
              GROUP BY c.id, c.name, c.phone
              ORDER BY c.name")->result();
        foreach ($due_rows as $r) {
            $data['customers'][$r->id] = $r->name
                . (trim((string)$r->phone) !== '' ? ' — ' . trim($r->phone) : '')
                . ' — Due ₹' . number_format((float)$r->due, 2);
        }
        $data['invoices']   = $this->Invoice_model->get_unpaid_for_select();

        $this->form_validation->set_rules('customer_id',   'Customer',       'required|integer');
        $this->form_validation->set_rules('amount',        'Amount',         'required|decimal|greater_than[0]');
        $this->form_validation->set_rules('payment_mode',  'Payment Mode',   'required');
        $this->form_validation->set_rules('payment_date',  'Payment Date',   'required');

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('payments/receive', $data);
            return;
        }

        $mode = $this->input->post('knockoff_mode', TRUE) === 'auto' ? 'auto' : 'bill';
        if ($mode === 'bill') {
            $this->form_validation->set_rules('invoice_id', 'Invoice', 'required|integer');
        }

        if ($this->form_validation->run() === FALSE) {
            $this->render('payments/receive', $data);
            return;
        }

        $customer_id = (int)$this->input->post('customer_id', TRUE);
        $amount      = (float)$this->input->post('amount',    TRUE);
        $common = [
            'payment_mode' => $this->input->post('payment_mode', TRUE),
            'reference_no' => $this->input->post('reference_no', TRUE),
            'payment_date' => $this->input->post('payment_date', TRUE),
            'notes'        => $this->input->post('notes',        TRUE),
            'created_by'   => $this->session->userdata('user_id'),
            'shift_id'     => $this->session->userdata('shift_id'),
        ];

        if ($mode === 'auto') {
            $this->_auto_knockoff_customer($customer_id, $amount, $common);
            redirect('payments');
        }

        $invoice_id = (int)$this->input->post('invoice_id', TRUE);
        $invoice    = $this->Invoice_model->get_by_id($invoice_id);

        if (!$invoice) {
            $this->session->set_flashdata('error', 'Invoice not found.');
            redirect('payments/receive');
        }

        // Cap payment at due amount
        if ($amount > $invoice->due_amount) {
            $amount = $invoice->due_amount;
        }

        $payment_no = $this->Payment_model->generate_payment_no();

        $payment_data = array_merge($common, [
            'payment_no'   => $payment_no,
            'type'         => 'received',
            'ref_type'     => 'invoice',
            'ref_id'       => $invoice_id,
            'party_id'     => $customer_id,
            'party_type'   => 'customer',
            'amount'       => $amount,
        ]);

        $new_paid = $invoice->paid_amount + $amount;
        $new_due  = $invoice->total_amount - $new_paid;
        $new_status = $new_due <= 0 ? 'paid' : 'partial';

        $this->db->trans_start();
        $this->Payment_model->create($payment_data);
        // Update invoice paid/due
        $this->db->where('id', $invoice_id)->update('invoices', [
            'paid_amount' => $new_paid,
            'due_amount'  => max(0, $new_due),
            'status'      => $new_status,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        // Reduce customer outstanding balance
        $this->Customer_model->decrease_balance($customer_id, $amount);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error. Payment not recorded.');
            redirect('payments/receive');
        }

        $this->log_activity('payments', 'payment_receipt', 'Recorded customer payment of ₹' . number_format($amount, 2) . ' for invoice #' . $invoice->invoice_no, $invoice_id, $payment_no);
        $this->session->set_flashdata('success',
            'Payment of ₹' . number_format($amount, 2) . ' recorded against ' . $invoice->invoice_no . '.');
        redirect('payments');
    }

    // Auto knock-off: apply one lump payment across a customer's unpaid invoices, oldest first
    private function _auto_knockoff_customer($customer_id, $amount, $common) {
        $customer = $this->Customer_model->get_by_id($customer_id);
        if (!$customer) {
            $this->session->set_flashdata('error', 'Customer not found.');
            return;
        }

        $invoices  = $this->Invoice_model->get_unpaid_by_customer_oldest_first($customer_id);
        $total_due = array_sum(array_column($invoices, 'due_amount'));

        // Same capping rule as bill-wise payment — never record more than what's actually owed
        if ($amount > $total_due) {
            $amount = $total_due;
        }
        if ($amount <= 0) {
            $this->session->set_flashdata('error', 'This customer has no outstanding due to knock off.');
            return;
        }

        $remaining = $amount;
        $applied   = [];

        $this->db->trans_start();
        foreach ($invoices as $inv) {
            if ($remaining <= 0) break;
            $pay_amt = min($remaining, (float)$inv->due_amount);
            if ($pay_amt <= 0) continue;

            $payment_no = $this->Payment_model->generate_payment_no();
            $this->Payment_model->create(array_merge($common, [
                'payment_no' => $payment_no,
                'type'       => 'received',
                'ref_type'   => 'invoice',
                'ref_id'     => $inv->id,
                'party_id'   => $customer_id,
                'party_type' => 'customer',
                'amount'     => $pay_amt,
            ]));

            $new_paid = $inv->paid_amount + $pay_amt;
            $new_due  = $inv->total_amount - $new_paid;
            $this->db->where('id', $inv->id)->update('invoices', [
                'paid_amount' => $new_paid,
                'due_amount'  => max(0, $new_due),
                'status'      => $new_due <= 0 ? 'paid' : 'partial',
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

            $applied[] = $inv->invoice_no . ' (₹' . number_format($pay_amt, 2) . ')';
            $remaining -= $pay_amt;
        }

        $this->Customer_model->decrease_balance($customer_id, $amount);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error. Payment not recorded.');
            return;
        }

        $msg = 'Auto knock-off: ₹' . number_format($amount, 2) . ' received from ' . $customer->name
             . ' — applied to ' . count($applied) . ' bill(s): ' . implode(', ', $applied) . '.';
        $this->log_activity('payments', 'payment_receipt', $msg, 0, $customer->name);
        $this->session->set_flashdata('success', $msg);
    }

    // -------------------------------------------------------
    // Record payment made to supplier (against purchase)
    // -------------------------------------------------------
    public function pay_supplier() {
        $data['page_title'] = 'Record Supplier Payment';
        $data['suppliers']  = $this->Supplier_model->get_all_for_select();
        $data['suppliers_gst'] = $this->Supplier_model->get_all_for_select_with_gst_flag();
        $data['purchases']  = $this->Purchase_model->get_unpaid_for_select();
        $data['bank_accounts'] = $this->db->where('status', 1)->order_by('is_default', 'DESC')->get('bank_accounts')->result();

        $this->form_validation->set_rules('supplier_id',  'Supplier',     'required|integer');
        $this->form_validation->set_rules('amount',       'Amount',       'required|decimal|greater_than[0]');
        $this->form_validation->set_rules('payment_mode', 'Payment Mode', 'required');
        $this->form_validation->set_rules('payment_date', 'Payment Date', 'required');

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('payments/pay_supplier', $data);
            return;
        }

        $mode = $this->input->post('knockoff_mode', TRUE) === 'auto' ? 'auto' : 'bill';
        if ($mode === 'bill') {
            $this->form_validation->set_rules('purchase_id', 'Purchase', 'required|integer');
        }

        if ($this->form_validation->run() === FALSE) {
            $this->render('payments/pay_supplier', $data);
            return;
        }

        $supplier_id = (int)$this->input->post('supplier_id', TRUE);
        $amount      = (float)$this->input->post('amount',    TRUE);
        $common = [
            'payment_mode'     => $this->input->post('payment_mode', TRUE),
            'reference_no'     => $this->input->post('reference_no', TRUE),
            'bank_account_id'  => $this->input->post('bank_account_id', TRUE) ?: null,
            'payment_date'     => $this->input->post('payment_date', TRUE),
            'notes'            => $this->input->post('notes',        TRUE),
            'created_by'       => $this->session->userdata('user_id'),
        ];

        if ($mode === 'auto') {
            $this->_auto_knockoff_supplier($supplier_id, $amount, $common);
            redirect('payments');
        }

        $purchase_id = (int)$this->input->post('purchase_id', TRUE);
        $purchase    = $this->Purchase_model->get_by_id($purchase_id);

        if (!$purchase) {
            $this->session->set_flashdata('error', 'Purchase not found.');
            redirect('payments/pay_supplier');
        }

        if ($amount > $purchase->due_amount) {
            $amount = $purchase->due_amount;
        }

        $payment_no = $this->Payment_model->generate_payment_no();

        $payment_data = array_merge($common, [
            'payment_no' => $payment_no,
            'type'       => 'paid',
            'ref_type'   => 'purchase',
            'ref_id'     => $purchase_id,
            'party_id'   => $supplier_id,
            'party_type' => 'supplier',
            'amount'     => $amount,
        ]);

        $new_paid = $purchase->paid_amount + $amount;
        $new_due  = $purchase->total_amount - $new_paid;

        $this->db->trans_start();
        $this->Payment_model->create($payment_data);
        $this->db->where('id', $purchase_id)->update('purchases', [
            'paid_amount' => $new_paid,
            'due_amount'  => max(0, $new_due),
            'status'      => $new_due <= 0 ? 'received' : 'partial',
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        $this->db->trans_complete();

        $this->session->set_flashdata('success',
            'Supplier payment of ₹' . number_format($amount, 2) . ' recorded.');
        redirect('payments');
    }

    // Auto knock-off: apply one lump payment across a supplier's unpaid bills, oldest first
    private function _auto_knockoff_supplier($supplier_id, $amount, $common) {
        $supplier = $this->Supplier_model->get_by_id($supplier_id);
        if (!$supplier) {
            $this->session->set_flashdata('error', 'Supplier not found.');
            return;
        }

        $purchases = $this->Purchase_model->get_unpaid_by_supplier_oldest_first($supplier_id);
        $total_due = array_sum(array_column($purchases, 'due_amount'));

        if ($amount > $total_due) {
            $amount = $total_due;
        }
        if ($amount <= 0) {
            $this->session->set_flashdata('error', 'This supplier has no outstanding due to knock off.');
            return;
        }

        $remaining = $amount;
        $applied   = [];

        $this->db->trans_start();
        foreach ($purchases as $pur) {
            if ($remaining <= 0) break;
            $pay_amt = min($remaining, (float)$pur->due_amount);
            if ($pay_amt <= 0) continue;

            $payment_no = $this->Payment_model->generate_payment_no();
            $this->Payment_model->create(array_merge($common, [
                'payment_no' => $payment_no,
                'type'       => 'paid',
                'ref_type'   => 'purchase',
                'ref_id'     => $pur->id,
                'party_id'   => $supplier_id,
                'party_type' => 'supplier',
                'amount'     => $pay_amt,
            ]));

            $new_paid = $pur->paid_amount + $pay_amt;
            $new_due  = $pur->total_amount - $new_paid;
            $this->db->where('id', $pur->id)->update('purchases', [
                'paid_amount' => $new_paid,
                'due_amount'  => max(0, $new_due),
                'status'      => $new_due <= 0 ? 'received' : 'partial',
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

            $applied[] = $pur->purchase_no . ' (₹' . number_format($pay_amt, 2) . ')';
            $remaining -= $pay_amt;
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error. Payment not recorded.');
            return;
        }

        $msg = 'Auto knock-off: ₹' . number_format($amount, 2) . ' paid to ' . $supplier->name
             . ' — applied to ' . count($applied) . ' bill(s): ' . implode(', ', $applied) . '.';
        $this->session->set_flashdata('success', $msg);
    }

    // -------------------------------------------------------
    // AJAX: get unpaid invoices by customer
    // -------------------------------------------------------
    public function get_invoices_by_customer($customer_id = null) {
        if (!$this->input->is_ajax_request()) show_404();
        $invoices = $this->Invoice_model->get_unpaid_by_customer($customer_id);
        header('Content-Type: application/json');
        echo json_encode($invoices);
    }

    // -------------------------------------------------------
    // AJAX: get unpaid purchases by supplier
    // -------------------------------------------------------
    public function get_purchases_by_supplier($supplier_id = null) {
        if (!$this->input->is_ajax_request()) show_404();
        $purchases = $this->Purchase_model->get_unpaid_by_supplier($supplier_id);
        header('Content-Type: application/json');
        echo json_encode($purchases);
    }

    // -------------------------------------------------------
    // Cheque printing
    // -------------------------------------------------------
    private $cheque_layout_defaults = [
        'f_party_top'   => 22.0, 'f_party_left'   => 36.0,  'f_party_size'   => 11,
        'f_amount_top'  => 22.0, 'f_amount_left'  => 156.0, 'f_amount_size'  => 11,
        'f_words_top'   => 36.0, 'f_words_left'   => 22.0,  'f_words_size'   => 10,
        'f_date_top'    => 10.0, 'f_date_left'    => 158.0, 'f_date_size'    => 10,
        'f_acpayee_top' => 18.0, 'f_acpayee_left' => 8.0,   'f_acpayee_size' => 8,
        'f_acpayee_show'=> 1,
    ];

    public function print_cheque($id = null) {
        $payment = $this->Payment_model->get_cheque_print_data((int)$id);
        if (!$payment) show_404();

        $defaults = $this->cheque_layout_defaults;
        $layout   = $payment->bank_account_id ? $this->Payment_model->get_cheque_layout($payment->bank_account_id) : null;

        $L = $defaults;
        if ($layout) {
            foreach ($L as $k => $v) $L[$k] = isset($layout->$k) ? (float)$layout->$k : $v;
            $L['f_acpayee_show'] = isset($layout->f_acpayee_show) ? (int)$layout->f_acpayee_show : $defaults['f_acpayee_show'];
        }

        $this->load->view('payments/cheque_print', [
            'payment'  => $payment,
            'L'        => $L,
            'defaults' => $defaults,
        ]);
    }

    public function save_cheque_layout() {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');

        $bank_account_id = (int)$this->input->post('bank_account_id');
        if (!$bank_account_id) {
            echo json_encode(['ok' => false, 'err' => 'No bank account on this payment.']);
            return;
        }

        $fields = ['f_party_top','f_party_left','f_party_size','f_amount_top','f_amount_left','f_amount_size',
                   'f_words_top','f_words_left','f_words_size','f_date_top','f_date_left','f_date_size',
                   'f_acpayee_top','f_acpayee_left','f_acpayee_size'];
        $vals = [];
        foreach ($fields as $f) $vals[$f] = (float)$this->input->post($f);
        $vals['f_acpayee_show'] = $this->input->post('f_acpayee_show') ? 1 : 0;

        $this->Payment_model->save_cheque_layout($bank_account_id, $vals);
        echo json_encode(['ok' => true]);
    }
}
