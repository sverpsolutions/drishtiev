<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rfq extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Rfq_model','Supplier_model','Product_model']);
    }

    public function index() {
        $data['page_title'] = 'Request for Quotation';
        $data['rfqs']       = $this->Rfq_model->get_all();
        $this->render('rfq/index', $data);
    }

    public function create() {
        $data['page_title'] = 'New RFQ';
        $data['suppliers']  = $this->Supplier_model->get_all();
        $data['products']   = $this->Product_model->get_all();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('rfq/create', $data);
            return;
        }

        $product_ids  = $this->input->post('product_id');
        $quantities   = $this->input->post('qty_required');
        $units        = $this->input->post('unit');
        $target_rates = $this->input->post('target_rate');
        $item_notes   = $this->input->post('item_notes');
        $supplier_ids = $this->input->post('supplier_ids') ?: [];
        if (empty($product_ids) || empty($supplier_ids)) {
            $this->session->set_flashdata('error', 'Select products and at least one supplier.');
            $this->render('rfq/create', $data);
            return;
        }

        $items = [];
        foreach ((array)$product_ids as $i => $pid) {
            if (!$pid) continue;
            $items[] = [
                'product_id'   => (int)$pid,
                'qty'          => (float)($quantities[$i] ?? 1),
                'unit'         => $units[$i] ?? 'PCS',
                'target_price' => (float)($target_rates[$i] ?? 0) ?: null,
                'notes'        => $item_notes[$i] ?? '',
            ];
        }

        $rfq_data = [
            'title'       => $this->input->post('title', true),
            'valid_until' => $this->input->post('deadline', true),
            'notes'       => $this->input->post('notes', true),
            'buyer_id'    => $this->session->userdata('user_id'),
        ];

        $rfq_id = $this->Rfq_model->create($rfq_data, $items, $supplier_ids);
        $this->session->set_flashdata('success', 'RFQ sent to ' . count($supplier_ids) . ' suppliers.');
        redirect("rfq/view/$rfq_id");
    }

    public function view($id = null) {
        if (!$id) redirect('rfq');
        $data['page_title']   = 'RFQ Details';
        $data['rfq']          = $this->Rfq_model->get_by_id($id);
        $data['items']        = $this->Rfq_model->get_items($id);
        $data['suppliers']    = $this->Rfq_model->get_suppliers($id);
        $data['quotations']   = $this->Rfq_model->get_quotations($id);
        $this->render('rfq/view', $data);
    }

    public function compare($id = null) {
        if (!$id) redirect('rfq');
        $data['page_title'] = 'Compare Quotations';
        $data['rfq']        = $this->Rfq_model->get_by_id($id);
        $data['items']      = $this->Rfq_model->get_items($id);
        $data['suppliers']  = $this->Rfq_model->get_suppliers($id);
        $data['quotations'] = $this->Rfq_model->get_quotations($id);
        // Build comparison matrix
        $matrix = [];
        foreach ($data['items'] as $item) {
            $matrix[$item->id] = ['item' => $item, 'quotes' => []];
            foreach ($data['quotations'] as $q) {
                if ($q->rfq_item_id == $item->id) {
                    $matrix[$item->id]['quotes'][$q->supplier_id] = $q;
                }
            }
        }
        $data['matrix'] = $matrix;
        $this->render('rfq/compare', $data);
    }

    public function convert_to_po($id = null) {
        if (!$id) redirect('rfq');
        // Get selected quotations and build PO
        $supplier_id = (int)$this->input->post('supplier_id');
        redirect("purchase_orders/create?supplier_id=$supplier_id&rfq_id=$id");
    }
}
