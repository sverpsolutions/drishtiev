<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Refund_model extends CI_Model {

    public function get_all() {
        return $this->db
            ->select('refunds.*, customers.name as customer_name, customers.phone as customer_phone')
            ->join('customers', 'customers.id = refunds.customer_id', 'left')
            ->order_by('refunds.id', 'DESC')
            ->get('refunds')
            ->result();
    }

    public function search($filters = []) {
        $this->db->select('refunds.*, customers.name as customer_name, customers.phone as customer_phone');
        $this->db->join('customers', 'customers.id = refunds.customer_id', 'left');
        if (!empty($filters['invoice_no'])) {
            $this->db->like('refunds.invoice_no', $filters['invoice_no']);
        }
        if (!empty($filters['customer_phone'])) {
            $this->db->like('customers.phone', $filters['customer_phone']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('DATE(refunds.created_at) >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('DATE(refunds.created_at) <=', $filters['date_to']);
        }
        return $this->db->order_by('refunds.id', 'DESC')->get('refunds')->result();
    }

    public function get_by_id($id) {
        return $this->db
            ->select('refunds.*, customers.name as customer_name, customers.phone as customer_phone,
                      customers.address as customer_address,
                      users.name as created_by_user')
            ->join('customers', 'customers.id = refunds.customer_id', 'left')
            ->join('users', 'users.id = refunds.created_by', 'left')
            ->where('refunds.id', $id)
            ->get('refunds')
            ->row();
    }

    public function get_items($refund_id) {
        return $this->db->where('refund_id', $refund_id)->get('refund_items')->result();
    }

    public function get_by_invoice($invoice_id) {
        return $this->db->where('invoice_id', $invoice_id)->order_by('id', 'DESC')->get('refunds')->result();
    }

    public function create($data, $items) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('refunds', $data);
        $refund_id = $this->db->insert_id();
        foreach ($items as $item) {
            $item['refund_id'] = $refund_id;
            $this->db->insert('refund_items', $item);
        }
        return $refund_id;
    }

    public function generate_refund_no() {
        $prefix = 'REF';
        $year   = date('Y');
        $last   = $this->db
            ->like('refund_no', $prefix . '-' . $year, 'after')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('refunds')
            ->row();
        $seq = $last ? ((int)substr($last->refund_no, -4) + 1) : 1;
        return $prefix . '-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function get_total_refunded_for_invoice($invoice_id) {
        $row = $this->db
            ->select_sum('refund_amount')
            ->where('invoice_id', $invoice_id)
            ->where('status', 'processed')
            ->get('refunds')
            ->row();
        return $row ? (float)$row->refund_amount : 0.0;
    }

    public function get_pending_report() {
        return $this->db
            ->select('refunds.*, customers.name as customer_name')
            ->join('customers', 'customers.id = refunds.customer_id', 'left')
            ->where('refunds.status', 'pending')
            ->order_by('refunds.id', 'DESC')
            ->get('refunds')
            ->result();
    }
}
