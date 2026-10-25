<?php
// ============================================================
// FILE: application/models/Purchase_model.php
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchase_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        if (!$this->db->field_exists('mrp', 'purchase_items')) {
            $this->db->query("ALTER TABLE purchase_items ADD COLUMN mrp decimal(12,2) DEFAULT 0.00");
        }
        if (!$this->db->field_exists('selling_price', 'purchase_items')) {
            $this->db->query("ALTER TABLE purchase_items ADD COLUMN selling_price decimal(12,2) DEFAULT 0.00");
        }
        if (!$this->db->field_exists('converted_invoice_id', 'purchases')) {
            $this->db->query("ALTER TABLE purchases ADD COLUMN converted_invoice_id INT(11) DEFAULT NULL COMMENT 'Sales invoice this purchase was converted into, if any' AFTER notes");
        }
    }

    public function mark_converted($purchase_id, $invoice_id) {
        $this->db->where('id', $purchase_id)->update('purchases', ['converted_invoice_id' => $invoice_id]);
    }

    public function get_all_with_supplier($filters = []) {
        $this->db->select('purchases.*, suppliers.name as supplier_name, suppliers.supplier_code')
                 ->join('suppliers', 'suppliers.id = purchases.supplier_id');
        
        if (!empty($filters['supplier_id'])) {
            $this->db->where('purchases.supplier_id', $filters['supplier_id']);
        }
        if (!empty($filters['supplier_code'])) {
            $this->db->like('suppliers.supplier_code', $filters['supplier_code']);
        }
        if (!empty($filters['invoice_no'])) {
            $this->db->like('purchases.invoice_no', $filters['invoice_no']);
        }
        if (!empty($filters['invoice_date'])) {
            $this->db->where('purchases.invoice_date', $filters['invoice_date']);
        }
        if (!empty($filters['from_date'])) {
            $this->db->where('purchases.invoice_date >=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $this->db->where('purchases.invoice_date <=', $filters['to_date']);
        }
        
        return $this->db->order_by('purchases.id', 'DESC')->get('purchases')->result();
    }

    public function get_by_id($id) {
        return $this->db
            ->select('purchases.*, suppliers.name as supplier_name, suppliers.gst_number as supplier_gstin,
                      suppliers.address as supplier_address, suppliers.city as supplier_city,
                      users.name as created_by_name')
            ->join('suppliers', 'suppliers.id = purchases.supplier_id')
            ->join('users',     'users.id = purchases.created_by')
            ->where('purchases.id', $id)
            ->get('purchases')
            ->row();
    }

    public function get_items($purchase_id) {
        // maintain_serial drives the serial picker on the return form; the
        // literal 0 keeps this query valid on a database that has not run
        // sql/43_serial_warranty.sql yet.
        $serial_col = $this->db->field_exists('maintain_serial', 'products')
            ? 'COALESCE(products.maintain_serial,0) as maintain_serial'
            : '0 as maintain_serial';
        return $this->db
            ->select('purchase_items.*, products.name as product_name, products.item_code, products.hsn_code, products.is_garment, products.style_code, IF(purchase_items.mrp > 0, purchase_items.mrp, products.mrp) as mrp, IF(purchase_items.selling_price > 0, purchase_items.selling_price, products.selling_price) as selling_price, COALESCE(gv.barcode, pb.barcode) as barcode, COALESCE(gv.barcode, pb.barcode) as ean_code, gv.mfr_article_no, gc.hex_code as colour_hex, ' . $serial_col, FALSE)
            ->join('products', 'products.id = purchase_items.product_id')
            ->join('garment_variants gv', 'gv.id = purchase_items.variant_id', 'left')
            ->join('garment_colours gc', 'gc.id = gv.colour_id', 'left')
            ->join('product_barcodes pb', "pb.product_id = products.id AND pb.unit_type = 'PCS' AND (pb.variant_id IS NULL OR pb.variant_id = 0)", 'left')
            ->where('purchase_items.purchase_id', $purchase_id)
            ->group_by('purchase_items.id')
            ->order_by('purchase_items.id', 'ASC')
            ->get('purchase_items')
            ->result();
    }

    public function get_by_supplier($supplier_id) {
        return $this->db
            ->where('supplier_id', $supplier_id)
            ->order_by('id', 'DESC')
            ->get('purchases')
            ->result();
    }

    public function create($data, $items) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('purchases', $data);
        $purchase_id = $this->db->insert_id();

        foreach ($items as $item) {
            $item['purchase_id'] = $purchase_id;
            $this->db->insert('purchase_items', $item);
        }
        return $purchase_id;
    }

    public function update($id, $data, $items) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update('purchases', $data);

        // Delete old items and re-insert
        $this->db->where('purchase_id', $id)->delete('purchase_items');
        foreach ($items as $item) {
            $item['purchase_id'] = $id;
            $this->db->insert('purchase_items', $item);
        }
        return $id;
    }

    public function create_prn($data, $items) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('purchase_returns', $data);
        $prn_id = $this->db->insert_id();

        foreach ($items as $item) {
            $item['prn_id'] = $prn_id;
            $this->db->insert('purchase_return_items', $item);
        }
        return $prn_id;
    }

    public function generate_purchase_no() {
        $prefix = $this->_get_setting('purchase_prefix') ?? 'PUR';
        $year   = date('Y');
        $last   = $this->db
            ->like('purchase_no', $prefix . '-' . $year, 'after')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('purchases')
            ->row();
        $seq = $last ? ((int)substr($last->purchase_no, -4) + 1) : 1;
        return $prefix . '-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function generate_prn_no() {
        $prefix = $this->_get_setting('prn_prefix') ?? 'PRN';
        $year   = date('Y');
        $last   = $this->db
            ->like('prn_no', $prefix . '-' . $year, 'after')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('purchase_returns')
            ->row();
        $seq = $last ? ((int)substr($last->prn_no, -4) + 1) : 1;
        return $prefix . '-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function _get_setting($key) {
        $row = $this->db->where('key_name', $key)->get('company_settings')->row();
        return $row ? $row->key_value : null;
    }

    public function get_unpaid_for_select() {
        $rows = $this->db
            ->select('purchases.id, purchases.purchase_no, purchases.due_amount, suppliers.name as supplier_name')
            ->join('suppliers','suppliers.id = purchases.supplier_id')
            ->where_in('purchases.status', ['partial','pending'])
            ->order_by('purchases.id','DESC')
            ->get('purchases')->result();
        $out = [];
        foreach ($rows as $r) {
            $out[$r->id] = $r->purchase_no . ' — ' . $r->supplier_name . ' (Due: ₹' . number_format($r->due_amount,2) . ')';
        }
        return $out;
    }

    public function get_unpaid_by_supplier($supplier_id) {
        return $this->db
            ->select('id, purchase_no, total_amount, due_amount, invoice_date')
            ->where('supplier_id', $supplier_id)
            ->where_in('status', ['partial','pending'])
            ->order_by('id','DESC')
            ->get('purchases')->result();
    }

    // Oldest-first — used for auto knock-off so a lump payment settles the longest-standing bills first
    public function get_unpaid_by_supplier_oldest_first($supplier_id) {
        return $this->db
            ->select('id, purchase_no, total_amount, paid_amount, due_amount, invoice_date')
            ->where('supplier_id', $supplier_id)
            ->where_in('status', ['partial','pending'])
            ->order_by('invoice_date', 'ASC')
            ->order_by('id', 'ASC')
            ->get('purchases')->result();
    }
}
