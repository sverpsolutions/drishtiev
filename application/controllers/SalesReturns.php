<?php
// ============================================================
// FILE: application/controllers/SalesReturns.php
// Sales Return / Credit Note management
// Appears in GSTR-1 Section 9B
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class SalesReturns extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Sales_return_model');
        $this->load->model('Customer_model');
        $this->load->model('Product_model');
        $this->load->model('Invoice_model');
    }

    // ─── List all credit notes ────────────────────────────────
    public function index() {
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $cid  = (int)$this->input->get('customer_id', TRUE);

        $data['page_title']  = 'Sales Returns / Credit Notes';
        $data['returns']     = $this->Sales_return_model->get_all($from, $to, $cid ?: null);
        $data['customers']   = $this->Customer_model->get_all();
        $data['from']        = $from;
        $data['to']          = $to;
        $data['customer_id'] = $cid;
        $this->render('sales_returns/index', $data);
    }

    // ─── Create credit note form ──────────────────────────────
    public function create() {
        $data['page_title'] = 'New Sales Return / Credit Note';
        $data['customers']  = $this->Customer_model->get_all_for_select();
        $data['products']   = $this->Product_model->get_all();
        $data['cn_number']  = $this->Sales_return_model->generate_number();
        $this->render('sales_returns/create', $data);
    }

    // ─── AJAX: get invoice items by invoice_id ────────────────
    public function get_invoice_ajax($invoice_id) {
        if (!$this->input->is_ajax_request()) show_404();
        $invoice = $this->Invoice_model->get_by_id($invoice_id);
        if (!$invoice) {
            echo json_encode(['success' => false, 'message' => 'Invoice not found']);
            return;
        }
        $invoice->items = $this->Invoice_model->get_items($invoice_id);
        echo json_encode(['success' => true, 'invoice' => $invoice]);
    }

    // ─── AJAX: search invoices by customer ────────────────────
    public function get_invoices_by_customer($customer_id) {
        if (!$this->input->is_ajax_request()) show_404();
        $rows = $this->db->select('id, invoice_no, invoice_date, total_amount, due_amount')
                         ->where('customer_id', $customer_id)
                         ->where('status !=', 'cancelled')
                         ->order_by('invoice_date', 'DESC')
                         ->get('invoices')->result();
        echo json_encode($rows);
    }

    // ─── Save credit note (POST) ──────────────────────────────
    public function save() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('sales_returns');

        $this->load->library('form_validation');
        $this->form_validation->set_rules('return_number',  'Credit Note No', 'required|trim');
        $this->form_validation->set_rules('customer_id',    'Customer',       'required|numeric');
        $this->form_validation->set_rules('return_date',    'Return Date',    'required');
        $this->form_validation->set_rules('reason',         'Reason',         'trim');

        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', validation_errors(' ', ' | '));
            redirect('sales_returns/create');
        }

        $items_raw = $this->input->post('items', TRUE) ?? [];
        $items     = [];
        $is_interstate = (int)$this->input->post('is_interstate', TRUE);

        foreach ($items_raw as $item) {
            if (empty($item['product_id']) && empty($item['product_name'])) continue;
            if ((float)($item['qty'] ?? 0) <= 0) continue;

            $qty      = (float)$item['qty'];
            $rate     = (float)$item['rate'];
            $disc_pct = (float)($item['discount_percent'] ?? 0);
            $gst_pct  = (float)($item['gst_percent'] ?? 0);
            $half_gst = $gst_pct / 2;

            // Rate is GST-inclusive: it's the actual price the customer paid.
            // Taxable value + GST are extracted backwards out of that price.
            $total    = round($qty * $rate * (1 - $disc_pct/100), 2);
            $taxable  = round($total / (1 + $gst_pct / 100), 2);
            $gst_a    = round($total - $taxable, 2);

            if ($is_interstate) {
                $igst_a = $gst_a;
                $cgst_a = $sgst_a = 0;
                $cpct = $spct = 0; $ipct = $gst_pct;
            } else {
                $cgst_a = round($gst_a / 2, 2);
                $sgst_a = round($gst_a - $cgst_a, 2);
                $igst_a = 0;
                $cpct = $spct = $half_gst; $ipct = 0;
            }

            $items[] = [
                'product_id'      => (int)($item['product_id'] ?? 0) ?: null,
                'product_name'    => $item['product_name'],
                'hsn_code'        => $item['hsn_code'] ?? '',
                'qty'             => $qty,
                'unit'            => $item['unit'] ?? 'PCS',
                'rate'            => $rate,
                'discount_percent'=> $disc_pct,
                'taxable_amt'     => $taxable,
                'gst_percent'     => $gst_pct,
                'cgst_percent'    => $cpct,
                'sgst_percent'    => $spct,
                'igst_percent'    => $ipct,
                'cgst_amount'     => $cgst_a,
                'sgst_amount'     => $sgst_a,
                'igst_amount'     => $igst_a,
                'gst_amount'      => $gst_a,
                'total'           => $total,
            ];
        }

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Add at least one item to the return.');
            redirect('sales_returns/create');
        }

        // Compute header totals
        $subtotal      = array_sum(array_column($items, 'taxable_amt')) + array_sum(array_column($items, 'discount_percent') /* raw subtotal */);
        $taxable_total = array_sum(array_column($items, 'taxable_amt'));
        $cgst_total    = array_sum(array_column($items, 'cgst_amount'));
        $sgst_total    = array_sum(array_column($items, 'sgst_amount'));
        $igst_total    = array_sum(array_column($items, 'igst_amount'));
        $gst_total     = $cgst_total + $sgst_total + $igst_total;
        $grand_total   = round($taxable_total + $gst_total, 2);

        // Recalculate subtotal as sum of qty*rate before discount
        $subtotal = 0;
        foreach ($items as $it) {
            $subtotal += round($it['qty'] * $it['rate'], 2);
        }
        $discount_total = round($subtotal - $taxable_total, 2);

        $header = [
            'return_number'  => $this->input->post('return_number',  TRUE),
            'credit_note_no' => $this->input->post('return_number',  TRUE),
            'invoice_id'     => (int)$this->input->post('invoice_id',    TRUE) ?: null,
            'customer_id'    => (int)$this->input->post('customer_id',   TRUE),
            'return_date'    => $this->input->post('return_date',    TRUE),
            'reason'         => $this->input->post('reason',         TRUE),
            'is_interstate'  => $is_interstate,
            'refund_method'  => $this->input->post('refund_method',  TRUE) ?: 'balance',
            'subtotal'       => $subtotal,
            'discount'       => $discount_total,
            'taxable_amount' => $taxable_total,
            'cgst_amount'    => $cgst_total,
            'sgst_amount'    => $sgst_total,
            'igst_amount'    => $igst_total,
            'total_gst'      => $gst_total,
            'total_amount'   => $grand_total,
            'notes'          => $this->input->post('notes', TRUE),
            'status'         => 'confirmed',
            'created_by'     => $this->session->userdata('user_id'),
        ];

        $id = $this->Sales_return_model->create($header, $items);

        if ($id) {
            $this->session->set_flashdata('success', 'Credit Note created successfully.');
            redirect('sales_returns/view/' . $id);
        } else {
            $err = $this->db->error();
            $this->session->set_flashdata('error', 'Failed to save credit note. ' . ($err['message'] ?? ''));
            redirect('sales_returns/create');
        }
    }

    // ─── View / Print Credit Note ─────────────────────────────
    public function view($id) {
        $return = $this->Sales_return_model->get_by_id($id);
        if (!$return) show_404();

        $data['page_title'] = 'Credit Note: ' . $return->return_number;
        $data['return']     = $return;
        $data['settings']   = $this->_get_settings();
        $this->render('sales_returns/view', $data);
    }

    // ─── Print (full-page print view) ─────────────────────────
    public function print_credit_note($id) {
        $return = $this->Sales_return_model->get_by_id($id);
        if (!$return) show_404();

        $data['return']   = $return;
        $data['settings'] = $this->_get_settings();
        $this->load->view('sales_returns/print', $data);  // no layout
    }

    // ─── Cancel ───────────────────────────────────────────────
    public function cancel($id) {
        if ($this->Sales_return_model->cancel($id)) {
            $this->session->set_flashdata('success', 'Credit note cancelled and stock reversed.');
        } else {
            $this->session->set_flashdata('error', 'Could not cancel credit note.');
        }
        redirect('sales_returns');
    }

    // ─── Helpers ──────────────────────────────────────────────
    private function _get_settings() {
        $rows = $this->db->get('company_settings')->result();
        $s = [];
        foreach ($rows as $r) $s[$r->key_name] = $r->key_value;
        return $s;
    }
}
