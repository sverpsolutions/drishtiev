<?php
// ============================================================
// FILE: application/models/Inventory_model.php  (UPDATED)
// Changes:
//   - get_stock_status() includes low_stock_threshold
//   - count_low_stock() uses per-product threshold
//   - get_adjustments() returns stock_adjustments log
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_model extends CI_Model {

    public function get_stock_status($limit = 0, $search = '', $stock_filter = '') {
        $this->db
            ->select('products.id, products.name, products.item_code, products.category, products.brand,
                      GREATEST(products.stock_qty, 0) AS stock_qty, products.unit, products.low_stock_threshold,
                      products.purchase_price, products.selling_price, products.expiry_date,
                      COALESCE(hsn_master.hsn_code, products.hsn_code) AS hsn_code,
                      (GREATEST(products.stock_qty, 0) * products.purchase_price) AS stock_value,
                      categories.name AS category_name,
                      COALESCE(pb.barcode,"") AS barcode')
            ->from('products')
            ->join('hsn_master',       'hsn_master.id  = products.hsn_id',      'left')
            ->join('categories',       'categories.id  = products.category_id', 'left')
            ->join('product_barcodes pb', 'pb.product_id = products.id AND pb.unit_type = "PCS"', 'left');

        $this->load->helper('product_search');
        $ws = product_words_sql($search, ['products.name', 'products.item_code', 'pb.barcode', 'categories.name']);
        if ($ws) {
            $this->db->where($ws, NULL, FALSE);
        } elseif ($search !== '') {
            $this->db->group_start()
                ->like('products.name', $search)
                ->or_like('products.item_code', $search)
                ->or_like('COALESCE(pb.barcode,"")', $search, 'both', false)
                ->or_like('categories.name', $search)
                ->or_like('COALESCE(hsn_master.hsn_code, products.hsn_code)', $search, 'both', false)
            ->group_end();
        }

        if ($stock_filter === 'out') {
            $this->db->where('products.stock_qty <=', 0);   // out of stock incl. oversold
        } elseif ($stock_filter === 'low') {
            $this->db->where('products.stock_qty >', 0);
            $this->db->where('products.stock_qty <= products.low_stock_threshold', null, false);
        } elseif ($stock_filter === 'ok') {
            $this->db->where('products.stock_qty >', 0);
        }

        $this->db->group_by('products.id')
            ->order_by('products.stock_qty', 'ASC');

        if ($limit > 0) {
            $this->db->limit($limit);
        }

        return $this->db->get()->result();
    }

    // Uses each product's own low_stock_threshold (not a fixed 10)
    public function count_low_stock() {
        return $this->db
            ->where('stock_qty > 0', null, false)
            ->where('stock_qty <= low_stock_threshold', null, false)
            ->count_all_results('products');
    }

    public function count_out_of_stock() {
        return $this->db->where('stock_qty', 0)->count_all_results('products');
    }

    public function count_expiring_soon($days = 30) {
        return $this->db
            ->where('expiry_date IS NOT NULL', null, false)
            ->where('expiry_date <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('expiry_date >=', date('Y-m-d'))
            ->where('stock_qty >', 0)
            ->count_all_results('products');
    }

    public function total_stock_value() {
        $row = $this->db
            ->select('SUM(stock_qty * purchase_price) AS total_value')
            ->get('products')
            ->row();
        return $row->total_value ?? 0;
    }

    // Purchase + sale + adjustment movements for a product
    public function get_movements($product_id, $location_id = null) {
        $pid = (int)$product_id;
        $loc_purchase = $location_id ? "AND p.location_id = " . (int)$location_id : "";
        $loc_invoice  = $location_id ? "AND i.location_id = " . (int)$location_id : "";
        $loc_log      = $location_id ? "AND location_id = " . (int)$location_id : "";

        $in = $this->db->query("
            SELECT
                'Purchase' AS type,
                p.invoice_date AS date,
                COALESCE(p.created_at, CONCAT(p.invoice_date,' 00:00:00')) AS datetime,
                p.purchase_no  AS reference,
                pi.qty         AS qty_in,
                0              AS qty_out,
                s.name         AS party
            FROM purchase_items pi
            JOIN purchases p  ON p.id  = pi.purchase_id
            JOIN suppliers s  ON s.id  = p.supplier_id
            WHERE pi.product_id = $pid
            $loc_purchase
        ")->result();

        $out = $this->db->query("
            SELECT
                'Sale'       AS type,
                i.invoice_date AS date,
                COALESCE(i.created_at, CONCAT(i.invoice_date,' 00:00:00')) AS datetime,
                i.invoice_no   AS reference,
                0              AS qty_in,
                ii.qty         AS qty_out,
                c.name         AS party
            FROM invoice_items ii
            JOIN invoices  i ON i.id  = ii.invoice_id
            JOIN customers c ON c.id  = i.customer_id
            WHERE ii.product_id = $pid
            AND i.status != 'cancelled'
            $loc_invoice
        ")->result();

        $adj = $this->db->query("
            SELECT
                CASE movement_type
                    WHEN 'adjustment_in'  THEN 'Stock Take (+)'
                    WHEN 'adjustment_out' THEN 'Stock Take (-)'
                    WHEN 'transfer_in'    THEN 'Transfer In'
                    WHEN 'transfer_out'   THEN 'Transfer Out'
                    WHEN 'refund_in'      THEN 'Refund In'
                    WHEN 'opening_stock'  THEN 'Opening Stock'
                    ELSE movement_type
                END AS type,
                DATE(created_at) AS date,
                created_at AS datetime,
                COALESCE(reference_no, notes) AS reference,
                IF(movement_type IN ('adjustment_in','transfer_in','refund_in','opening_stock'),  quantity, 0) AS qty_in,
                IF(movement_type IN ('adjustment_out','transfer_out'), quantity, 0) AS qty_out,
                COALESCE(location_name, user_name, '') AS party
            FROM stock_movement_log
            WHERE product_id = $pid
            AND movement_type NOT IN ('sale_out','purchase_in')
            $loc_log
        ")->result();

        $all = array_merge($in, $out, $adj);
        usort($all, function($a, $b) { return strcmp($a->datetime, $b->datetime); });
        return $all;
    }

    // Manual adjustment log for a product
    public function get_adjustments($product_id) {
        return $this->db
            ->select('stock_adjustments.*, users.name AS adjusted_by')
            ->from('stock_adjustments')
            ->join('users', 'users.id = stock_adjustments.created_by', 'left')
            ->where('stock_adjustments.product_id', $product_id)
            ->order_by('stock_adjustments.created_at', 'DESC')
            ->get()
            ->result();
    }

    // All active locations for the report header
    public function get_active_locations() {
        return $this->db
            ->where('status', 1)
            ->order_by('id', 'ASC')
            ->get('locations')
            ->result();
    }

    // Location-wise stock pivot for inventory report
    public function get_location_stock_report($limit = 0, $offset = 0) {
        $locations = $this->get_active_locations();

        $joins  = '';
        $selects = '';
        foreach ($locations as $loc) {
            $lid = (int)$loc->id;
            $alias = 'ls' . $lid;
            $joins   .= " LEFT JOIN product_location_stock AS $alias
                              ON $alias.product_id = p.id AND $alias.location_id = $lid";
            $selects .= ", COALESCE($alias.stock_qty, 0) AS loc_$lid";
        }

        $limit_clause = $limit > 0 ? " LIMIT " . (int)$limit . " OFFSET " . (int)$offset : "";
        $sql = "
            SELECT
                p.id,
                p.item_code,
                p.name,
                COALESCE(b.name,'')           AS brand_name,
                COALESCE(cat.name,'')         AS category_name,
                COALESCE(p.unit,'PCS')        AS unit,
                COALESCE(p.purchase_price,0)  AS purchase_price,
                COALESCE(p.mrp,0)             AS mrp,
                COALESCE(p.selling_price,0)   AS selling_price,
                GREATEST(COALESCE(p.stock_qty,0), 0) AS total_qty,
                COALESCE(pb.barcode,'')       AS barcode,
                COALESCE(hm.hsn_code, p.hsn_code, '') AS hsn_code
                $selects
            FROM products p
            LEFT JOIN categories cat         ON cat.id  = p.category_id
            LEFT JOIN brands b               ON b.id    = p.brand_id
            LEFT JOIN product_barcodes pb    ON pb.product_id = p.id AND pb.unit_type = 'PCS'
            LEFT JOIN hsn_master hm          ON hm.id   = p.hsn_id
            $joins
            WHERE p.status = 1
            ORDER BY cat.name, p.name
            $limit_clause
        ";
        return $this->db->query($sql)->result();
    }

    public function count_location_stock_report() {
        return (int)$this->db->where('status', 1)->count_all_results('products');
    }
}
