<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rfq_model extends CI_Model {

    public function get_all() {
        return $this->db
            ->select('rfq.*, users.name as buyer_name')
            ->join('users', 'users.id = rfq.buyer_id', 'left')
            ->order_by('rfq.id', 'DESC')
            ->get('rfq')->result();
    }

    public function get_by_id($id) {
        return $this->db
            ->select('rfq.*, users.name as buyer_name')
            ->join('users', 'users.id = rfq.buyer_id', 'left')
            ->where('rfq.id', $id)->get('rfq')->row();
    }

    public function get_items($rfq_id) {
        return $this->db
            ->select('rfq_items.*, products.name as product_name, products.item_code, products.unit')
            ->join('products', 'products.id = rfq_items.product_id', 'left')
            ->where('rfq_id', $rfq_id)->get('rfq_items')->result();
    }

    public function get_suppliers($rfq_id) {
        return $this->db
            ->select('rfq_suppliers.*, suppliers.name as supplier_name, suppliers.email, suppliers.phone as mobile')
            ->join('suppliers', 'suppliers.id = rfq_suppliers.supplier_id', 'left')
            ->where('rfq_id', $rfq_id)->get('rfq_suppliers')->result();
    }

    public function get_quotations($rfq_id) {
        return $this->db
            ->select('rfq_quotations.*, rfq_items.qty as required_qty, products.name as product_name,
                      suppliers.name as supplier_name')
            ->join('rfq_items',  'rfq_items.id = rfq_quotations.rfq_item_id',   'left')
            ->join('products',   'products.id = rfq_items.product_id',           'left')
            ->join('suppliers',  'suppliers.id = rfq_quotations.supplier_id',    'left')
            ->where('rfq_quotations.rfq_id', $rfq_id)
            ->order_by('rfq_items.id, rfq_quotations.quoted_price')
            ->get('rfq_quotations')->result();
    }

    public function create($data, $items, $supplier_ids) {
        $data['rfq_number'] = $this->_next_number();
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('rfq', $data);
        $rfq_id = $this->db->insert_id();
        foreach ($items as $item) {
            $item['rfq_id'] = $rfq_id;
            $this->db->insert('rfq_items', $item);
        }
        foreach ($supplier_ids as $sid) {
            $this->db->insert('rfq_suppliers', ['rfq_id' => $rfq_id, 'supplier_id' => $sid, 'status' => 'sent', 'sent_at' => date('Y-m-d H:i:s')]);
        }
        $this->db->where('id', $rfq_id)->update('rfq', ['status' => 'sent']);
        return $rfq_id;
    }

    public function submit_quotation($rfq_id, $supplier_id, $quotes) {
        foreach ($quotes as $q) {
            $q['rfq_id'] = $rfq_id; $q['supplier_id'] = $supplier_id; $q['submitted_at'] = date('Y-m-d H:i:s');
            $this->db->insert('rfq_quotations', $q);
        }
        $this->db->where('rfq_id', $rfq_id)->where('supplier_id', $supplier_id)
            ->update('rfq_suppliers', ['status' => 'responded']);
        // Check if all responded
        $total   = $this->db->where('rfq_id', $rfq_id)->get('rfq_suppliers')->num_rows();
        $responded = $this->db->where('rfq_id', $rfq_id)->where('status', 'responded')->get('rfq_suppliers')->num_rows();
        if ($responded >= $total) {
            $this->db->where('id', $rfq_id)->update('rfq', ['status' => 'responded']);
        }
    }

    public function get_for_supplier($supplier_id) {
        return $this->db
            ->select('rfq.*, rfq_suppliers.status as my_status, rfq_suppliers.sent_at')
            ->join('rfq_suppliers', 'rfq_suppliers.rfq_id = rfq.id')
            ->where('rfq_suppliers.supplier_id', $supplier_id)
            ->order_by('rfq.id', 'DESC')
            ->get('rfq')->result();
    }

    private function _next_number() {
        $row = $this->db->select_max('id')->get('rfq')->row();
        $n   = ($row->id ?? 0) + 1;
        return 'RFQ-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }
}
