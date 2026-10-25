<?php
// ============================================================
// FILE: application/controllers/PurchaseReturns.php
// Purchase Return / Debit Note management
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class PurchaseReturns extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Purchase_return_model');
        $this->load->model('Product_model');
    }

    // ─── Supplier model helper ────────────────────────────────
    private function _suppliers() {
        return $this->db->where('status', 1)->order_by('name')->get('suppliers')->result();
    }

    // ─── List all debit notes ─────────────────────────────────
    public function index() {
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $sid  = (int)$this->input->get('supplier_id', TRUE);

        $data['page_title']  = 'Purchase Returns / Debit Notes';
        $data['returns']     = $this->Purchase_return_model->get_all($from, $to, $sid ?: null);
        $data['suppliers']   = $this->_suppliers();
        $data['from']        = $from;
        $data['to']          = $to;
        $data['supplier_id'] = $sid;
        $this->render('purchase_returns/index', $data);
    }

    // ─── Create form ──────────────────────────────────────────
    public function create() {
        $data['page_title'] = 'New Purchase Return / Debit Note';
        $data['suppliers']  = $this->_suppliers();
        $data['products']   = $this->Product_model->get_all();
        $data['dn_number']  = $this->Purchase_return_model->generate_number();
        $this->render('purchase_returns/create', $data);
    }

    // ─── AJAX: get purchase items by purchase_id ──────────────
    public function get_purchase_ajax($purchase_id) {
        if (!$this->input->is_ajax_request()) show_404();
        $purchase = $this->db->where('id', $purchase_id)->get('purchases')->row();
        if (!$purchase) {
            echo json_encode(['success' => false, 'message' => 'Purchase not found']);
            return;
        }
        $purchase->items = $this->db->select('pi.*, p.name as product_name, p.hsn_code, p.unit')
                                    ->from('purchase_items pi')
                                    ->join('products p', 'p.id = pi.product_id', 'left')
                                    ->where('pi.purchase_id', $purchase_id)
                                    ->get()->result();
        echo json_encode(['success' => true, 'purchase' => $purchase]);
    }

    // ─── AJAX: search purchases by supplier ───────────────────
    public function get_purchases_by_supplier($supplier_id) {
        if (!$this->input->is_ajax_request()) show_404();
        $rows = $this->db->select('id, purchase_no, invoice_no, invoice_date, total_amount')
                         ->where('supplier_id', $supplier_id)
                         ->where('status !=', 'returned')
                         ->order_by('invoice_date', 'DESC')
                         ->get('purchases')->result();
        echo json_encode($rows);
    }

    // ─── Save debit note ──────────────────────────────────────
    public function save() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('purchase_returns');

        $this->load->library('form_validation');
        $this->form_validation->set_rules('return_number', 'Debit Note No', 'required|trim');
        $this->form_validation->set_rules('supplier_id',   'Supplier',      'required|numeric');
        $this->form_validation->set_rules('return_date',   'Return Date',   'required');

        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', validation_errors(' ', ' | '));
            redirect('purchase_returns/create');
        }

        $items_raw     = $this->input->post('items', TRUE) ?? [];
        $items         = [];
        $is_interstate = (int)$this->input->post('is_interstate', TRUE);

        foreach ($items_raw as $item) {
            if (empty($item['product_id']) && empty($item['product_name'])) continue;
            if ((float)($item['qty'] ?? 0) <= 0) continue;

            $qty      = (float)$item['qty'];
            $rate     = (float)$item['rate'];
            $disc_pct = (float)($item['discount_percent'] ?? 0);
            $gst_pct  = (float)($item['gst_percent'] ?? 0);
            $half_gst = $gst_pct / 2;

            $taxable  = round($qty * $rate * (1 - $disc_pct/100), 2);

            if ($is_interstate) {
                $igst_a = round($taxable * $gst_pct / 100, 2);
                $cgst_a = $sgst_a = 0;
                $cpct = $spct = 0; $ipct = $gst_pct;
            } else {
                $cgst_a = round($taxable * $half_gst / 100, 2);
                $sgst_a = $cgst_a;
                $igst_a = 0;
                $cpct = $spct = $half_gst; $ipct = 0;
            }
            $gst_a = $cgst_a + $sgst_a + $igst_a;
            $total = round($taxable + $gst_a, 2);

            $items[] = [
                'product_id'      => (int)($item['product_id'] ?? 0) ?: null,
                'product_name'    => $item['product_name'],
                'hsn_code'        => $item['hsn_code'] ?? '',
                'qty'             => $qty,
                'unit'            => $item['unit'] ?? 'PCS',
                'rate'            => $rate,
                'price'           => $rate,
                'discount_percent'=> $disc_pct,
                'taxable_amount'  => $taxable,
                'taxable_amt'     => $taxable,
                'gst_percent'     => $gst_pct,
                'cgst_percent'    => $cpct,
                'sgst_percent'    => $spct,
                'igst_percent'    => $ipct,
                'cgst_amount'     => $cgst_a,
                'sgst_amount'     => $sgst_a,
                'igst_amount'     => $igst_a,
                'gst_amount'      => $gst_a,
                'itc_reversed'    => $gst_a,
                'total'           => $total,
            ];
        }

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Add at least one item to the return.');
            redirect('purchase_returns/create');
        }

        $taxable_total  = array_sum(array_column($items, 'taxable_amt'));
        $cgst_total     = array_sum(array_column($items, 'cgst_amount'));
        $sgst_total     = array_sum(array_column($items, 'sgst_amount'));
        $igst_total     = array_sum(array_column($items, 'igst_amount'));
        $gst_total      = $cgst_total + $sgst_total + $igst_total;
        $grand_total    = round($taxable_total + $gst_total, 2);

        $subtotal = 0;
        foreach ($items as $it) $subtotal += round($it['qty'] * $it['rate'], 2);
        $discount_total = round($subtotal - $taxable_total, 2);

        $header = [
            'prn_no'         => $this->input->post('return_number', TRUE),
            'return_number'  => $this->input->post('return_number', TRUE),
            'debit_note_no'  => $this->input->post('return_number', TRUE),
            'purchase_id'    => (int)$this->input->post('purchase_id',   TRUE) ?: null,
            'supplier_id'    => (int)$this->input->post('supplier_id',   TRUE),
            'return_date'    => $this->input->post('return_date',   TRUE),
            'reason'         => $this->input->post('reason',        TRUE),
            'is_interstate'  => $is_interstate,
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

        $id = $this->Purchase_return_model->create($header, $items);

        if ($id) {
            $this->session->set_flashdata('success', 'Debit Note created successfully.');
            redirect('purchase_returns/view/' . $id);
        } else {
            $err = $this->db->error();
            $this->session->set_flashdata('error', 'Failed to save debit note. ' . ($err['message'] ?? ''));
            redirect('purchase_returns/create');
        }
    }

    // ─── Edit debit note (draft status only) ──────────────────
    public function edit($id) {
        $return = $this->Purchase_return_model->get_by_id($id);
        if (!$return) show_404();
        if ($return->status === 'cancelled') {
            $this->session->set_flashdata('error', 'Cancelled debit notes cannot be edited.');
            redirect('purchase_returns');
        }

        $data['page_title'] = 'Edit Debit Note: ' . $return->return_number;
        $data['suppliers']  = $this->_suppliers();
        $data['products']   = $this->Product_model->get_all();
        $data['return']     = $return;
        $data['items']      = $this->db->where('purchase_return_id', $id)->get('purchase_return_items')->result();
        $this->render('purchase_returns/edit', $data);
    }

    // ─── Save edited debit note ───────────────────────────────
    public function update($id) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('purchase_returns');
        $return = $this->Purchase_return_model->get_by_id($id);
        if (!$return || $return->status === 'cancelled') {
            $this->session->set_flashdata('error', 'Cannot update this debit note.');
            redirect('purchase_returns');
        }

        $items_raw    = $this->input->post('items', TRUE) ?? [];
        $items        = [];
        $is_interstate = (int)$this->input->post('is_interstate', TRUE);

        foreach ($items_raw as $item) {
            if (empty($item['product_id']) && empty($item['product_name'])) continue;
            if ((float)($item['qty'] ?? 0) <= 0) continue;
            $qty      = (float)$item['qty'];
            $rate     = (float)$item['rate'];
            $disc_pct = (float)($item['discount_percent'] ?? 0);
            $gst_pct  = (float)($item['gst_percent'] ?? 0);
            $half_gst = $gst_pct / 2;
            $taxable  = round($qty * $rate * (1 - $disc_pct/100), 2);
            if ($is_interstate) {
                $igst_a = round($taxable * $gst_pct / 100, 2); $cgst_a = $sgst_a = 0;
                $cpct = $spct = 0; $ipct = $gst_pct;
            } else {
                $cgst_a = round($taxable * $half_gst / 100, 2); $sgst_a = $cgst_a; $igst_a = 0;
                $cpct = $spct = $half_gst; $ipct = 0;
            }
            $gst_a = $cgst_a + $sgst_a + $igst_a;
            $items[] = ['product_id'=>(int)($item['product_id']??0)?:null,'product_name'=>$item['product_name'],'hsn_code'=>$item['hsn_code']??'','qty'=>$qty,'unit'=>$item['unit']??'PCS','rate'=>$rate,'price'=>$rate,'discount_percent'=>$disc_pct,'taxable_amt'=>$taxable,'gst_percent'=>$gst_pct,'cgst_percent'=>$cpct,'sgst_percent'=>$spct,'igst_percent'=>$ipct,'cgst_amount'=>$cgst_a,'sgst_amount'=>$sgst_a,'igst_amount'=>$igst_a,'gst_amount'=>$gst_a,'total'=>round($taxable+$gst_a,2)];
        }

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Add at least one item.');
            redirect('purchase_returns/edit/' . $id);
        }

        $taxable_total = array_sum(array_column($items,'taxable_amt'));
        $gst_total     = array_sum(array_column($items,'gst_amount'));
        $grand_total   = round($taxable_total + $gst_total, 2);
        $subtotal = array_sum(array_map(fn($i)=>round($i['qty']*$i['rate'],2), $items));

        $header = [
            'purchase_id'   => (int)$this->input->post('purchase_id',TRUE)?:null,
            'supplier_id'   => (int)$this->input->post('supplier_id',TRUE),
            'return_date'   => $this->input->post('return_date',TRUE),
            'reason'        => $this->input->post('reason',TRUE),
            'is_interstate' => $is_interstate,
            'subtotal'      => $subtotal,
            'discount'      => round($subtotal-$taxable_total,2),
            'taxable_amount'=> $taxable_total,
            'cgst_amount'   => array_sum(array_column($items,'cgst_amount')),
            'sgst_amount'   => array_sum(array_column($items,'sgst_amount')),
            'igst_amount'   => array_sum(array_column($items,'igst_amount')),
            'total_gst'     => $gst_total,
            'total_amount'  => $grand_total,
            'notes'         => $this->input->post('notes',TRUE),
        ];

        $this->db->trans_start();
        $this->db->where('id', $id)->update('purchase_returns', $header);
        $this->db->where('purchase_return_id', $id)->delete('purchase_return_items');
        foreach ($items as &$it) { $it['purchase_return_id'] = $id; $it['prn_id'] = $id; }
        $this->db->insert_batch('purchase_return_items', $items);
        $this->db->trans_complete();

        if ($this->db->trans_status() !== FALSE) {
            $this->session->set_flashdata('success', 'Debit note updated successfully.');
            redirect('purchase_returns/view/' . $id);
        } else {
            $this->session->set_flashdata('error', 'Update failed.');
            redirect('purchase_returns/edit/' . $id);
        }
    }

    // ─── AJAX: search debit notes ─────────────────────────────
    public function search_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $q = trim($this->input->get('q', TRUE));
        $results = $this->db->select('pr.id, pr.return_number, pr.return_date, pr.total_amount, pr.status, s.name as supplier_name')
            ->from('purchase_returns pr')
            ->join('suppliers s', 's.id = pr.supplier_id', 'left')
            ->like('pr.return_number', $q)
            ->or_like('s.name', $q)
            ->limit(20)
            ->get()->result();
        echo json_encode($results);
    }

    // ─── View / Print Debit Note ──────────────────────────────
    public function view($id) {
        $return = $this->Purchase_return_model->get_by_id($id);
        if (!$return) show_404();

        $data['page_title'] = 'Debit Note: ' . $return->return_number;
        $data['return']     = $return;
        $data['settings']   = $this->_get_settings();
        $this->render('purchase_returns/view', $data);
    }

    // ─── Print (full-page) ────────────────────────────────────
    public function print_debit_note($id) {
        $return = $this->Purchase_return_model->get_by_id($id);
        if (!$return) show_404();

        $data['return']   = $return;
        $data['settings'] = $this->_get_settings();
        $this->load->view('purchase_returns/print', $data);
    }

    // ─── Cancel ───────────────────────────────────────────────
    public function cancel($id) {
        if ($this->Purchase_return_model->cancel($id)) {
            $this->session->set_flashdata('success', 'Debit note cancelled and stock reversed.');
        } else {
            $this->session->set_flashdata('error', 'Could not cancel debit note.');
        }
        redirect('purchase_returns');
    }

    private function _get_settings() {
        $rows = $this->db->get('company_settings')->result();
        $s = [];
        foreach ($rows as $r) $s[$r->key_name] = $r->key_value;
        return $s;
    }
}
