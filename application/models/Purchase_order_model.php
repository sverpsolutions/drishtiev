<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchase_order_model extends CI_Model {

    private $table = 'purchase_orders';

    public function get_all($filters = []) {
        $this->db->select('purchase_orders.*, suppliers.name as supplier_name, suppliers.supplier_code,
                           users.name as buyer_name, locations.name as location_name')
            ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id', 'left')
            ->join('users',     'users.id = purchase_orders.buyer_id',        'left')
            ->join('locations', 'locations.id = purchase_orders.location_id', 'left')
            ->order_by('purchase_orders.id', 'DESC');
        if (!empty($filters['status']))      $this->db->where('purchase_orders.status', $filters['status']);
        if (!empty($filters['supplier_id'])) $this->db->where('purchase_orders.supplier_id', $filters['supplier_id']);
        if (!empty($filters['location_id'])) $this->db->where('purchase_orders.location_id', $filters['location_id']);
        if (!empty($filters['from_date']))   $this->db->where('DATE(purchase_orders.created_at) >=', $filters['from_date']);
        if (!empty($filters['to_date']))     $this->db->where('DATE(purchase_orders.created_at) <=', $filters['to_date']);
        return $this->db->get($this->table)->result();
    }

    public function get_by_id($id) {
        return $this->db
            ->select('purchase_orders.*, suppliers.name as supplier_name, suppliers.supplier_code,
                      suppliers.gst_number as supplier_gstin, suppliers.address as supplier_address,
                      suppliers.city as supplier_city, suppliers.state as supplier_state,
                      suppliers.phone as supplier_mobile, suppliers.email as supplier_email,
                      suppliers.credit_days, suppliers.lead_time_days,
                      users.name as buyer_name, locations.name as location_name')
            ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id', 'left')
            ->join('users',     'users.id = purchase_orders.buyer_id',        'left')
            ->join('locations', 'locations.id = purchase_orders.location_id', 'left')
            ->where('purchase_orders.id', $id)
            ->get($this->table)->row();
    }

    public function get_items($po_id) {
        return $this->db
            ->select('purchase_order_items.*, products.name as product_name, products.item_code,
                      products.hsn_code, products.unit, products.mrp, GREATEST(products.stock_qty, 0) as current_stock,
                      products.gst_percent')
            ->join('products', 'products.id = purchase_order_items.product_id', 'left')
            ->where('purchase_order_items.po_id', $po_id)
            ->get('purchase_order_items')->result();
    }

    public function get_approval_log($po_id) {
        return $this->db
            ->select('purchase_approvals.*, users.name as approver_name')
            ->join('users', 'users.id = purchase_approvals.approver_id', 'left')
            ->where('purchase_approvals.po_id', $po_id)
            ->order_by('purchase_approvals.level')
            ->get('purchase_approvals')->result();
    }

    public function create($data, $items) {
        $data['po_number']  = $this->_next_po_number();
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        $po_id = $this->db->insert_id();
        $this->_save_items($po_id, $items);
        return $po_id;
    }

    public function update($id, $data, $items = null) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update($this->table, $data);
        if ($items !== null) {
            $this->db->where('po_id', $id)->delete('purchase_order_items');
            $this->_save_items($id, $items);
        }
    }

    public function update_status($id, $status, $extra = []) {
        $d = array_merge(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], $extra);
        $this->db->where('id', $id)->update($this->table, $d);
    }

    public function submit_for_approval($po_id) {
        $po = $this->get_by_id($po_id);
        if (!$po) return false;
        $level = $this->_required_approval_level($po->net_total);
        $this->update_status($po_id, 'pending_approval', ['approval_level' => $level]);
        $this->db->insert('purchase_approvals', [
            'po_id'      => $po_id,
            'level'      => $level,
            'action'     => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $level;
    }

    public function approve($po_id, $approver_id, $remarks = '') {
        $this->db->where('po_id', $po_id)->where('action', 'pending')->update('purchase_approvals', [
            'approver_id' => $approver_id,
            'action'      => 'approved',
            'remarks'     => $remarks,
            'acted_at'    => date('Y-m-d H:i:s'),
        ]);
        $this->update_status($po_id, 'approved', [
            'approved_by' => $approver_id,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function reject($po_id, $approver_id, $remarks = '') {
        $this->db->where('po_id', $po_id)->where('action', 'pending')->update('purchase_approvals', [
            'approver_id' => $approver_id,
            'action'      => 'rejected',
            'remarks'     => $remarks,
            'acted_at'    => date('Y-m-d H:i:s'),
        ]);
        $this->update_status($po_id, 'rejected');
        return true;
    }

    public function get_pending_for_approver($role) {
        $level = $this->_level_for_role($role);
        return $this->db
            ->select('purchase_orders.*, suppliers.name as supplier_name, users.name as buyer_name')
            ->join('purchase_approvals pa', 'pa.po_id = purchase_orders.id AND pa.action = "pending"', 'inner')
            ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id', 'left')
            ->join('users',     'users.id = purchase_orders.buyer_id',        'left')
            ->where('purchase_orders.status', 'pending_approval')
            ->where('purchase_orders.approval_level', $level)
            ->order_by('purchase_orders.created_at')
            ->get($this->table)->result();
    }

    public function get_by_supplier_for_vendor($supplier_id) {
        return $this->db
            ->where('supplier_id', $supplier_id)
            ->where_in('status', ['approved','vendor_confirmed','dispatched','in_transit','received'])
            ->order_by('id', 'DESC')
            ->get($this->table)->result();
    }

    public function vendor_confirm($po_id, $delivery_date = null) {
        $d = ['status' => 'vendor_confirmed', 'vendor_confirmed_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        if ($delivery_date) $d['expected_delivery'] = $delivery_date;
        $this->db->where('id', $po_id)->update($this->table, $d);
    }

    public function get_dashboard_stats($location_id = null) {
        $base = $this->db;
        if ($location_id) $base->where('location_id', $location_id);
        $stats = [];
        foreach (['draft','pending_approval','approved','vendor_confirmed','dispatched','received'] as $s) {
            $r = $this->db->where('status', $s);
            if ($location_id) $r->where('location_id', $location_id);
            $stats[$s] = $r->get($this->table)->num_rows();
        }
        return $stats;
    }

    // ─── Totals helpers ───────────────────────────────────────────────────────
    public function calc_totals($items) {
        $gross = $discount = $tax = 0;
        foreach ($items as &$item) {
            $line      = $item['rate'] * $item['ordered_qty'];
            $disc_amt  = $line * ($item['discount_pct'] / 100);
            $taxable   = $line - $disc_amt;
            $tax_amt   = $taxable * ($item['gst_pct'] / 100);
            $item['taxable_amt'] = round($taxable, 2);
            $item['tax_amt']     = round($tax_amt, 2);
            $item['discount_amt']= round($disc_amt, 2);
            $item['amount']      = round($taxable + $tax_amt, 2);
            $gross    += $line;
            $discount += $disc_amt;
            $tax      += $tax_amt;
        }
        return compact('gross','discount','tax','items');
    }

    private function _save_items($po_id, $items) {
        foreach ($items as $item) {
            if (empty($item['product_id']) || empty($item['ordered_qty'])) continue;
            $item['po_id'] = $po_id;
            $this->db->insert('purchase_order_items', $item);
        }
    }

    private function _next_po_number() {
        $row = $this->db->query("SELECT MAX(CAST(SUBSTRING(po_number,4) AS UNSIGNED)) as mx FROM purchase_orders WHERE po_number LIKE 'PO-%'")->row();
        $n   = ($row->mx ?? 0) + 1;
        return 'PO-' . str_pad($n, 5, '0', STR_PAD_LEFT);
    }

    private function _required_approval_level($amount) {
        if ($amount <= 25000)  return 1;
        if ($amount <= 100000) return 2;
        if ($amount <= 500000) return 3;
        return 4;
    }

    private function _level_for_role($role) {
        if ($role === 'manager')    return 1;
        if ($role === 'admin')      return 2;
        if ($role === 'superadmin' || $role === 'owner') return 4;
        return 1;
    }
}
