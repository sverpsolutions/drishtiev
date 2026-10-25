<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Estimates extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Estimate_model', 'Customer_model', 'Product_model', 'Invoice_model']);
        $this->load->library('form_validation');
    }

    // -----------------------------------------------
    // LIST all estimates
    // -----------------------------------------------
    public function index() {
        $filters = [
            'status'        => $this->input->get('status'),
            'date_from'     => $this->input->get('date_from'),
            'date_to'       => $this->input->get('date_to'),
            'customer_name' => $this->input->get('customer_name'),
        ];

        $data['page_title'] = 'Estimates / Quotations';
        $data['estimates']  = $this->Estimate_model->get_all($filters);
        $data['filters']    = $filters;
        $this->render('estimates/index', $data);
    }

    // -----------------------------------------------
    // CREATE estimate
    // -----------------------------------------------
    public function create() {
        $data['page_title'] = 'New Estimate';
        $data['customers']  = $this->Customer_model->get_all();
        $data['products']   = $this->Product_model->get_all();
        $data['company']    = $this->Invoice_model->get_company_settings();
        $data['settings']   = $this->_get_settings();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('estimates/create', $data);
            return;
        }

        // Collect line items
        $product_ids = $this->input->post('product_id');
        $qtys        = $this->input->post('qty');
        $rates       = $this->input->post('rate');
        $discounts   = $this->input->post('item_discount');
        $tax_pcts    = $this->input->post('tax_percent');
        $descs       = $this->input->post('description');

        if (empty($product_ids) || !is_array($product_ids)) {
            $this->session->set_flashdata('error', 'Add at least one item.');
            $this->render('estimates/create', $data);
            return;
        }

        $items       = [];
        $subtotal    = 0;
        $total_disc  = 0;
        $total_tax   = 0;
        $settings    = $data['settings'];
        $stock_warns = [];

        foreach ($product_ids as $i => $pid) {
            $pid = (int)$pid;
            if (!$pid) continue;

            $qty      = (float)($qtys[$i] ?? 1);
            $rate     = (float)($rates[$i] ?? 0);
            $disc_pct = (float)($discounts[$i] ?? 0);
            $tax_pct  = (float)($tax_pcts[$i] ?? 0);
            $desc     = $descs[$i] ?? '';

            // Stock check
            if (!empty($settings['enable_estimate_stock_check'])) {
                $stock = $this->Estimate_model->check_stock($pid, $qty);
                if (!$stock['ok']) {
                    if (!empty($settings['block_estimate_if_no_stock'])) {
                        $this->session->set_flashdata('error', "Insufficient stock for: {$stock['name']} (Available: {$stock['available']})");
                        $this->render('estimates/create', $data);
                        return;
                    }
                    $stock_warns[] = "⚠ Low stock: {$stock['name']} (Available: {$stock['available']})";
                }
            }

            $product = $this->Product_model->get_by_id($pid);
            // Rate is GST-inclusive: it's the final price quoted to the customer.
            // Taxable value + tax are extracted backwards out of that price.
            $disc_amt = round($rate * $qty * ($disc_pct / 100), 2);
            $total    = round(($rate * $qty) - $disc_amt, 2);
            $taxable  = round($total / (1 + $tax_pct / 100), 2);
            $tax_amt  = round($total - $taxable, 2);

            $subtotal   += $rate * $qty;
            $total_disc += $disc_amt;
            $total_tax  += $tax_amt;

            // Get cost_price from products table directly
            $cost_row = $this->db->select('cost_price, purchase_price')->where('id',$pid)->get('products')->row();
            $cost_price = $cost_row ? (float)($cost_row->cost_price ?: $cost_row->purchase_price) : 0;

            $items[] = [
                'product_id'  => $pid,
                'item_code'   => $product->item_code ?? '',
                'description' => $desc ?: ($product->name ?? ''),
                'qty'         => $qty,
                'unit'        => $product->unit ?? 'PCS',
                'rate'        => $rate,
                'discount'    => $disc_pct,
                'tax_percent' => $tax_pct,
                'tax_amount'  => $tax_amt,
                'total'       => $total,
                'cost_price'  => $cost_price,
            ];
        }

        // total already includes tax (GST-inclusive rate), so don't add total_tax again
        $total_amount = $subtotal - $total_disc;

        $estimate_data = [
            'estimate_no'    => $this->Estimate_model->generate_estimate_no(),
            'customer_id'    => (int)$this->input->post('customer_id') ?: NULL,
            'customer_name'  => $this->input->post('customer_name', TRUE),
            'customer_mobile'=> $this->input->post('customer_mobile', TRUE),
            'estimate_date'  => $this->input->post('estimate_date', TRUE),
            'valid_until'    => $this->input->post('valid_until', TRUE) ?: NULL,
            'subtotal'       => $subtotal,
            'total_discount' => $total_disc,
            'total_tax'      => $total_tax,
            'total_amount'   => $total_amount,
            'notes'          => $this->input->post('notes', TRUE),
            'status'         => 'pending',
            'created_by'     => $this->session->userdata('user_id'),
        ];

        $estimate_id = $this->Estimate_model->create($estimate_data, $items);

        if ($estimate_id) {
            // Log audit
            $this->_audit_log('estimate', 'create', $estimate_id, $estimate_data['estimate_no'],
                NULL, json_encode($items), 0, $total_amount, 'Estimate created');

            if (!empty($stock_warns)) {
                $this->session->set_flashdata('warning', implode('<br>', $stock_warns));
            }
            $this->session->set_flashdata('success', 'Estimate ' . $estimate_data['estimate_no'] . ' created.');
            redirect('estimates/view/' . $estimate_id);
        } else {
            $this->session->set_flashdata('error', 'Failed to create estimate.');
            $this->render('estimates/create', $data);
        }
    }

    // -----------------------------------------------
    // VIEW estimate
    // -----------------------------------------------
    public function view($id) {
        $estimate = $this->Estimate_model->get_by_id($id);
        if (!$estimate) show_404();

        $data['page_title'] = 'Estimate #' . $estimate->estimate_no;
        $data['estimate']   = $estimate;
        $data['items']      = $this->Estimate_model->get_items($id);
        $data['company']    = $this->Invoice_model->get_company_settings();
        $this->render('estimates/view', $data);
    }

    // -----------------------------------------------
    // EDIT estimate (only pending)
    // -----------------------------------------------
    public function edit($id) {
        $estimate = $this->Estimate_model->get_by_id($id);
        if (!$estimate || $estimate->status !== 'pending') {
            $this->session->set_flashdata('error', 'Only pending estimates can be edited.');
            redirect('estimates');
        }

        $data['page_title'] = 'Edit Estimate #' . $estimate->estimate_no;
        $data['estimate']   = $estimate;
        $data['items']      = $this->Estimate_model->get_items($id);
        $data['customers']  = $this->Customer_model->get_all();
        $data['products']   = $this->Product_model->get_all();
        $data['company']    = $this->Invoice_model->get_company_settings();
        $data['settings']   = $this->_get_settings();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('estimates/edit', $data);
            return;
        }

        // Same logic as create
        $product_ids = $this->input->post('product_id');
        $qtys        = $this->input->post('qty');
        $rates       = $this->input->post('rate');
        $discounts   = $this->input->post('item_discount');
        $tax_pcts    = $this->input->post('tax_percent');
        $descs       = $this->input->post('description');

        $items = []; $subtotal = 0; $total_disc = 0; $total_tax = 0;

        foreach ($product_ids as $i => $pid) {
            $pid = (int)$pid; if (!$pid) continue;
            $qty     = (float)($qtys[$i] ?? 1);
            $rate    = (float)($rates[$i] ?? 0);
            $disc    = (float)($discounts[$i] ?? 0);
            $tax     = (float)($tax_pcts[$i] ?? 0);
            $product = $this->Product_model->get_by_id($pid);
            $cost_row2 = $this->db->select('cost_price, purchase_price')->where('id',$pid)->get('products')->row();
            $cost_price2 = $cost_row2 ? (float)($cost_row2->cost_price ?: $cost_row2->purchase_price) : 0;

            // Rate is GST-inclusive: it's the final price quoted to the customer.
            // Taxable value + tax are extracted backwards out of that price.
            $disc_amt = round($rate * $qty * ($disc / 100), 2);
            $total    = round(($rate * $qty) - $disc_amt, 2);
            $taxable  = round($total / (1 + $tax / 100), 2);
            $tax_amt  = round($total - $taxable, 2);

            $subtotal += $rate * $qty; $total_disc += $disc_amt; $total_tax += $tax_amt;

            $items[] = [
                'product_id'  => $pid,
                'item_code'   => $product->item_code ?? '',
                'description' => $descs[$i] ?? ($product->name ?? ''),
                'qty'         => $qty, 'unit' => $product->unit ?? 'PCS',
                'rate'        => $rate, 'discount' => $disc,
                'tax_percent' => $tax, 'tax_amount' => $tax_amt,
                'total'       => $total, 'cost_price' => $cost_price2,
            ];
        }

        // total already includes tax (GST-inclusive rate), so don't add total_tax again
        $total_amount  = $subtotal - $total_disc;
        $old_total     = $estimate->total_amount;

        $update_data = [
            'customer_id'    => (int)$this->input->post('customer_id') ?: NULL,
            'customer_name'  => $this->input->post('customer_name', TRUE),
            'customer_mobile'=> $this->input->post('customer_mobile', TRUE),
            'estimate_date'  => $this->input->post('estimate_date', TRUE),
            'valid_until'    => $this->input->post('valid_until', TRUE) ?: NULL,
            'subtotal'       => $subtotal,
            'total_discount' => $total_disc,
            'total_tax'      => $total_tax,
            'total_amount'   => $total_amount,
            'notes'          => $this->input->post('notes', TRUE),
        ];

        if ($this->Estimate_model->update($id, $update_data, $items)) {
            $this->_audit_log('estimate', 'edit', $id, $estimate->estimate_no,
                null, null, $old_total, $total_amount, 'Estimate modified');
            $this->session->set_flashdata('success', 'Estimate updated.');
            redirect('estimates/view/' . $id);
        } else {
            $this->session->set_flashdata('error', 'Update failed.');
            $this->render('estimates/edit', $data);
        }
    }

    // -----------------------------------------------
    // CLOSE as Cash Sale
    // -----------------------------------------------
    public function close_cash($id) {
        $estimate = $this->Estimate_model->get_by_id($id);
        if (!$estimate || $estimate->status !== 'pending') {
            $this->session->set_flashdata('error', 'Invalid operation.');
            redirect('estimates');
        }

        $user_id = $this->session->userdata('user_id');
        if ($this->Estimate_model->close_as_cash($id, $user_id)) {
            $this->_audit_log('estimate', 'close_cash', $id, $estimate->estimate_no,
                null, null, $estimate->total_amount, $estimate->total_amount, 'Closed as Cash Sale (No Bill)');
            $this->session->set_flashdata('success', 'Estimate closed as Cash Sale. Stock deducted.');
        } else {
            $this->session->set_flashdata('error', 'Failed to close estimate.');
        }
        redirect('estimates/view/' . $id);
    }

    // -----------------------------------------------
    // CANCEL estimate
    // -----------------------------------------------
    public function cancel($id) {
        $estimate = $this->Estimate_model->get_by_id($id);
        if (!$estimate || $estimate->status !== 'pending') {
            $this->session->set_flashdata('error', 'Only pending estimates can be cancelled.');
            redirect('estimates');
        }

        if ($this->Estimate_model->cancel($id)) {
            $this->_audit_log('estimate', 'cancel', $id, $estimate->estimate_no,
                null, null, $estimate->total_amount, 0, 'Estimate cancelled');
            $this->session->set_flashdata('success', 'Estimate cancelled.');
        } else {
            $this->session->set_flashdata('error', 'Failed to cancel.');
        }
        redirect('estimates');
    }

    // -----------------------------------------------
    // CONVERT to Invoice (1-click)
    // -----------------------------------------------
    public function convert_to_invoice($id) {
        $estimate = $this->Estimate_model->get_by_id($id);
        if (!$estimate || $estimate->status !== 'pending') {
            $this->session->set_flashdata('error', 'Only pending estimates can be converted.');
            redirect('estimates');
        }

        // Pass estimate_id to billing create
        $this->session->set_flashdata('convert_estimate_id', $id);
        redirect('billing/create?from_estimate=' . $id);
    }

    // -----------------------------------------------
    // PRINT estimate
    // -----------------------------------------------
    public function print_estimate($id) {
        $estimate = $this->Estimate_model->get_by_id($id);
        if (!$estimate) show_404();

        $data['estimate'] = $estimate;
        $data['items']    = $this->Estimate_model->get_items($id);
        $data['company']  = $this->Invoice_model->get_company_settings();
        $this->load->view('estimates/print', $data);
    }

    // -----------------------------------------------
    // AJAX: Stock check
    // -----------------------------------------------
    public function ajax_stock_check() {
        $this->output->set_content_type('application/json');
        $product_id = (int)$this->input->post('product_id');
        $qty        = (float)$this->input->post('qty');

        if (!$product_id || !$qty) {
            echo json_encode(['ok' => TRUE, 'available' => 0]);
            return;
        }

        $result = $this->Estimate_model->check_stock($product_id, $qty);
        echo json_encode($result);
    }

    // -----------------------------------------------
    // AJAX: Search product by code or name
    // -----------------------------------------------
    public function ajax_search_product() {
        $this->output->set_content_type('application/json');
        $q = $this->input->get('q', TRUE);

        if (strlen($q) < 1) { echo json_encode([]); return; }

        // Search by item_code (exact), or name (like)
        $this->db->select('p.id, p.item_code, p.name AS product_name, p.selling_price as rate, GREATEST(p.stock_qty, 0) AS stock_qty, p.unit, COALESCE(h.igst_percent, p.gst_percent) as gst_percent, p.cost_price')
                 ->from('products p')
                 ->join('hsn_master h', 'h.id = p.hsn_id', 'left');
        $this->load->helper('product_search');
        if ($ws = product_words_sql($q, ['p.name', 'p.item_code'])) {
            $this->db->where($ws, NULL, FALSE);
        } else {
            $this->db->group_start()
                    ->where('p.item_code', $q)
                    ->or_like('p.name', $q)
                    ->or_like('p.item_code', $q)
                 ->group_end();
        }
        $this->db->where('p.status', 1)
                 ->limit(10);

        $rows = $this->db->get()->result();
        echo json_encode($rows);
    }

    // -----------------------------------------------
    // Private helpers
    // -----------------------------------------------
    private function _get_settings() {
        $row = $this->db->get('company_settings')->row_array();
        return $row ?: [];
    }

    private function _audit_log($module, $action, $record_id, $record_no,
                                 $old_data, $new_data, $old_amount, $new_amount, $desc) {
        $user_id   = $this->session->userdata('user_id');
        $user_name = $this->session->userdata('name');

        $this->db->insert('audit_logs', [
            'module'      => $module,
            'action'      => $action,
            'record_id'   => $record_id,
            'record_no'   => $record_no,
            'old_data'    => $old_data,
            'new_data'    => $new_data,
            'old_amount'  => $old_amount,
            'new_amount'  => $new_amount,
            'description' => $desc,
            'user_id'     => $user_id,
            'user_name'   => $user_name,
            'ip_address'  => $this->input->ip_address(),
        ]);
    }
}
