<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PurchaseSuggestions extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Purchase_suggestion_model','Supplier_model']);
    }

    public function index() {
        $data['page_title'] = 'Purchase Suggestions';
        $supplier_id = (int)$this->input->get('supplier_id');
        $brand_id    = (int)$this->input->get('brand_id');
        $days        = (int)$this->input->get('days') ?: 30;
        $from        = $this->input->get('from_date', true);
        $to          = $this->input->get('to_date',   true);
        $loc_id      = $this->active_location_id();

        $data['supplier_id'] = $supplier_id;
        $data['brand_id']    = $brand_id;
        $data['days']        = $days;
        $data['from_date']   = $from;
        $data['to_date']     = $to;
        $data['suppliers']   = $this->Supplier_model->get_all_for_select();
        $data['brands']      = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('brands')->result();
        $data['suggestions'] = $supplier_id || $brand_id || $from
            ? $this->Purchase_suggestion_model->get_suggestions($supplier_id, $days, $from ?: null, $to ?: null, $loc_id, $brand_id)
            : [];
        $this->render('purchase_suggestions/index', $data);
    }

    // AJAX: get live suggestions as JSON
    public function ajax() {
        $supplier_id = (int)$this->input->get('supplier_id');
        $brand_id    = (int)$this->input->get('brand_id');
        $days        = (int)$this->input->get('days') ?: 30;
        $from        = $this->input->get('from_date', true) ?: null;
        $to          = $this->input->get('to_date',   true) ?: null;
        $suggestions = $this->Purchase_suggestion_model->get_suggestions($supplier_id, $days, $from, $to, $this->active_location_id(), $brand_id);
        $this->output->set_content_type('application/json')->set_output(json_encode($suggestions));
    }
}
