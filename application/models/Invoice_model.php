<?php
// ============================================================
// FILE: application/models/Invoice_model.php
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice_model extends CI_Model {

    public function get_all_with_customer($from = null, $to = null, $location_id = null) {
        $this->db
            ->select('invoices.*, customers.name as customer_name, customers.type as customer_type,
                      locations.name as location_name, locations.code as location_code')
            ->join('customers', 'customers.id = invoices.customer_id')
            ->join('locations', 'locations.id = invoices.location_id', 'left');
        if ($from) $this->db->where('invoices.invoice_date >=', $from);
        if ($to)   $this->db->where('invoices.invoice_date <=', $to);
        if ($location_id) $this->db->where('invoices.location_id', $location_id);
        return $this->db
            ->order_by('invoices.id', 'DESC')
            ->get('invoices')
            ->result();
    }

    public function get_by_id($id) {
        return $this->db
            ->select('invoices.*, customers.name as customer_name, customers.phone as customer_phone,
                      customers.address as customer_address, customers.city as customer_city,
                      customers.state as customer_state, customers.gst_number as customer_gstin,
                      customers.type as customer_type,
                      users.name as created_by_name')
            ->join('customers', 'customers.id = invoices.customer_id')
            ->join('users',     'users.id = invoices.created_by')
            ->where('invoices.id', $id)
            ->get('invoices')
            ->row();
    }

    public function get_items($invoice_id) {
        return $this->db
            ->select('invoice_items.*, products.description, products.mrp AS product_mrp, products.item_code AS product_item_code')
            ->join('products', 'products.id = invoice_items.product_id', 'left')
            ->where('invoice_items.invoice_id', $invoice_id)
            ->get('invoice_items')
            ->result();
    }

    public function get_by_customer($customer_id) {
        return $this->db
            ->where('customer_id', $customer_id)
            ->order_by('id', 'DESC')
            ->get('invoices')
            ->result();
    }

    public function create($data, $items) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('invoices', $data);
        $invoice_id = $this->db->insert_id();

        foreach ($items as $item) {
            $item['invoice_id'] = $invoice_id;
            $this->db->insert('invoice_items', $item);
        }
        return $invoice_id;
    }

    public function update_status($id, $status) {
        return $this->db->where('id', $id)->update('invoices', [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // Location Code + Financial Year numbering (e.g. WH/25-26/000001) — atomic per
    // location+FY, via application/libraries/DocumentNumbering.php. Existing historical
    // invoice numbers (the old INV-YYYY-NNNN format) are untouched; this only applies
    // to invoices created from here on. Returns ['number' => ..., 'financial_year' => ...]
    // so the caller can store both the number and the FY on the invoice row.
    public function generate_invoice_no($location_id, $date = null) {
        $this->load->library('DocumentNumbering');
        return $this->documentnumbering->generate('invoice', $location_id, $date);
    }

    // Get all company settings as key=>value array
    public function get_company_settings() {
        $rows = $this->db->get('company_settings')->result();
        $out  = [];
        foreach ($rows as $r) {
            if (!empty($r->key_name)) {
                $out[$r->key_name] = $r->key_value;
            }
        }
        
        // Merge column-based settings from the first row
        $first = $this->db->limit(1)->get('company_settings')->row();
        if ($first) {
            $cols = ['enable_estimate_stock_check', 'block_estimate_if_no_stock', 'enable_bill_modify',
                     'enable_excel_import', 'enable_gst', 'default_cash_sale_mode', 'low_stock_threshold'];
            foreach ($cols as $col) {
                if (isset($first->$col)) {
                    $out[$col] = $first->$col;
                }
            }
        }
        return $out;
    }

    private function _get_setting($key) {
        $row = $this->db->where('key_name', $key)->get('company_settings')->row();
        return $row ? $row->key_value : null;
    }

    public function get_unpaid_for_select() {
        $rows = $this->db
            ->select('invoices.id, invoices.invoice_no, invoices.due_amount, customers.name as customer_name')
            ->join('customers','customers.id = invoices.customer_id')
            ->where_in('invoices.status', ['unpaid','partial'])
            ->order_by('invoices.id','DESC')
            ->get('invoices')->result();
        $out = [];
        foreach ($rows as $r) {
            $out[$r->id] = $r->invoice_no . ' — ' . $r->customer_name . ' (Due: ₹' . number_format($r->due_amount,2) . ')';
        }
        return $out;
    }

    public function get_unpaid_by_customer($customer_id) {
        return $this->db
            ->select('id, invoice_no, total_amount, due_amount, invoice_date')
            ->where('customer_id', $customer_id)
            ->where_in('status', ['unpaid','partial'])
            ->order_by('id','DESC')
            ->get('invoices')->result();
    }

    // Oldest-first — used for auto knock-off so a lump payment settles the longest-standing bills first
    public function get_unpaid_by_customer_oldest_first($customer_id) {
        return $this->db
            ->select('id, invoice_no, total_amount, paid_amount, due_amount, invoice_date')
            ->where('customer_id', $customer_id)
            ->where_in('status', ['unpaid','partial'])
            ->order_by('invoice_date', 'ASC')
            ->order_by('id', 'ASC')
            ->get('invoices')->result();
    }
}
