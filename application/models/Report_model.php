<?php
// ============================================================
// FILE: application/models/Report_model.php
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Report_model extends CI_Model {

    public function get_company_settings() {
        $rows = $this->db->get('company_settings')->result();
        $out  = [];
        foreach ($rows as $r) { $out[$r->key_name] = $r->key_value; }
        return $out;
    }

    // -------------------------------------------------------
    // SALES
    // -------------------------------------------------------
    public function get_sales($from, $to, $location_id = null, $payment_mode = null) {
        $this->db->select("
                invoices.*, customers.name AS customer_name, customers.type AS customer_type,
                locations.name AS location_name,
                (SELECT COUNT(*) FROM invoice_items WHERE invoice_items.invoice_id = invoices.id) AS item_count
            ", false)
            ->join('customers', 'customers.id = invoices.customer_id')
            ->join('locations', 'locations.id = invoices.location_id', 'left')
            ->where('invoices.invoice_date >=', $from)
            ->where('invoices.invoice_date <=', $to)
            ->where('invoices.status !=', 'cancelled');
        if ($location_id) $this->db->where('invoices.location_id', $location_id);
        if ($payment_mode) {
            if ($payment_mode === 'split') {
                $this->db->where('invoices.payment_mode', 'split');
            } else {
                $this->db->group_start()
                    ->where('invoices.payment_mode', $payment_mode)
                    ->or_group_start()
                        ->where('invoices.payment_mode', 'split')
                        ->where("invoices.id IN (SELECT invoice_id FROM invoice_payment_splits WHERE payment_mode = " . $this->db->escape($payment_mode) . ")", null, false)
                    ->group_end()
                ->group_end();
            }
        }
        return $this->db->order_by('invoices.invoice_date', 'ASC')->get('invoices')->result();
    }

    // Refunds processed in the period, with per-refund taxable/GST totals rolled up
    // from refund_items — used to show refund rows alongside invoices in the sales
    // report and to reconcile the "Refunds" summary card.
    public function get_refunds($from, $to, $location_id = null, $payment_mode = null) {
        $this->db->select("
                refunds.id, refunds.refund_no, refunds.invoice_id, refunds.invoice_no,
                refunds.refund_amount, refunds.refund_mode, refunds.refund_type, refunds.created_at,
                customers.name AS customer_name,
                invoices.is_interstate,
                locations.name AS location_name,
                (SELECT COUNT(*) FROM refund_items WHERE refund_items.refund_id = refunds.id) AS item_count,
                (SELECT COALESCE(SUM(taxable_amt),0) FROM refund_items WHERE refund_items.refund_id = refunds.id) AS taxable_amount,
                (SELECT COALESCE(SUM(gst_amount),0) FROM refund_items WHERE refund_items.refund_id = refunds.id) AS gst_amount
            ", false)
            ->join('customers', 'customers.id = refunds.customer_id', 'left')
            ->join('invoices', 'invoices.id = refunds.invoice_id', 'left')
            ->join('locations', 'locations.id = invoices.location_id', 'left')
            ->where('DATE(refunds.created_at) >=', $from)
            ->where('DATE(refunds.created_at) <=', $to)
            ->where('refunds.status', 'processed');
        if ($location_id) $this->db->where('invoices.location_id', $location_id);
        if ($payment_mode) $this->db->where('refunds.refund_mode', $payment_mode);
        return $this->db->order_by('refunds.created_at', 'ASC')->get('refunds')->result();
    }

    // Date x GST-rate rows for the Tax Breakup Report — one row per (date, rate)
    // found on any invoice line, pivoted into slab columns by the controller.
    // Sourced from invoice_items so it foots exactly against the per-line GST
    // already stored on each bill, and cancelled invoices are excluded.
    public function get_tax_breakup($from, $to, $location_id = null) {
        $this->db->select("
                invoices.invoice_date AS dt,
                invoice_items.gst_percent,
                SUM(invoice_items.taxable_amt) AS taxable,
                SUM(invoice_items.gst_amount) AS tax
            ", false)
            ->from('invoice_items')
            ->join('invoices', 'invoices.id = invoice_items.invoice_id')
            ->where('invoices.invoice_date >=', $from)
            ->where('invoices.invoice_date <=', $to)
            ->where('invoices.status !=', 'cancelled');
        if ($location_id) $this->db->where('invoices.location_id', $location_id);
        return $this->db->group_by(['invoices.invoice_date', 'invoice_items.gst_percent'])
            ->order_by('invoices.invoice_date', 'ASC')
            ->order_by('invoice_items.gst_percent', 'ASC')
            ->get()->result();
    }

    // Refund side of the same breakdown, grouped by the date the refund was
    // processed (not the original invoice date) — netted against get_tax_breakup()
    // by the controller so Total Sales/Basic Cost match actual net sales.
    // Only refunds whose original invoice is still non-cancelled are counted:
    // a fully-refunded invoice flips to status='cancelled' and its taxable/tax
    // already dropped out of get_tax_breakup() entirely, so netting its refund
    // here too would double-subtract it (same guard as get_sales_summary()'s
    // net_deduct query).
    public function get_tax_breakup_refunds($from, $to, $location_id = null) {
        $this->db->select("
                DATE(refunds.created_at) AS dt,
                refund_items.gst_percent,
                SUM(refund_items.taxable_amt) AS taxable,
                SUM(refund_items.gst_amount) AS tax
            ", false)
            ->from('refund_items')
            ->join('refunds', 'refunds.id = refund_items.refund_id')
            ->join('invoices', 'invoices.id = refunds.invoice_id')
            ->where('DATE(refunds.created_at) >=', $from)
            ->where('DATE(refunds.created_at) <=', $to)
            ->where('refunds.status', 'processed')
            ->where('invoices.status !=', 'cancelled');
        if ($location_id) $this->db->where('invoices.location_id', $location_id);
        return $this->db->group_by('DATE(refunds.created_at), refund_items.gst_percent', false)
            ->order_by('dt', 'ASC')
            ->order_by('refund_items.gst_percent', 'ASC')
            ->get()->result();
    }

    // Bill-level per-date sales/refund totals — the exact same basis as
    // get_sales_summary()'s "Net Sales" card (invoices.total_amount, which
    // includes round-off and bill-level discounts that don't belong to any
    // single GST slab). Used for the Tax Breakup Report's "Total Sales"
    // column so it reconciles with the Sales Report instead of the slightly
    // different item-level taxable+GST sum.
    public function get_tax_breakup_invoice_totals($from, $to, $location_id = null) {
        $this->db->select('invoice_date AS dt, SUM(total_amount) AS total', false)
            ->from('invoices')
            ->where('invoice_date >=', $from)
            ->where('invoice_date <=', $to)
            ->where('status !=', 'cancelled');
        if ($location_id) $this->db->where('location_id', $location_id);
        return $this->db->group_by('invoice_date')->get()->result();
    }

    public function get_tax_breakup_refund_totals($from, $to, $location_id = null) {
        $this->db->select('DATE(refunds.created_at) AS dt, SUM(refunds.refund_amount) AS total', false)
            ->from('refunds')
            ->join('invoices', 'invoices.id = refunds.invoice_id')
            ->where('DATE(refunds.created_at) >=', $from)
            ->where('DATE(refunds.created_at) <=', $to)
            ->where('refunds.status', 'processed')
            ->where('invoices.status !=', 'cancelled');
        if ($location_id) $this->db->where('invoices.location_id', $location_id);
        return $this->db->group_by('DATE(refunds.created_at)', false)->get()->result();
    }

    // Item-level breakdown of sales — one row per invoice line, with product
    // master data (category/brand/supplier/mrp) joined in for filtering.
    public function get_sales_item_details($from, $to, $location_id = null, $filters = []) {
        $this->db->select("
                i.invoice_no, i.invoice_date, i.id AS invoice_id,
                ii.product_id, ii.product_name, ii.hsn_code, ii.qty, ii.unit, ii.rate, ii.mrp,
                ii.discount, ii.taxable_amt, ii.gst_percent, ii.gst_amount, ii.total,
                p.item_code,
                COALESCE(c.name, p.category)  AS category_name,
                COALESCE(b.name, p.brand)     AS brand_name,
                COALESCE(l.name, '')          AS location_name,
                (SELECT s.name FROM supplier_products sp
                   JOIN suppliers s ON s.id = sp.supplier_id
                  WHERE sp.product_id = p.id
                  ORDER BY sp.is_primary DESC, sp.id ASC LIMIT 1) AS supplier_name
            ", false)
            ->from('invoice_items ii')
            ->join('invoices i', 'i.id = ii.invoice_id')
            ->join('products p', 'p.id = ii.product_id', 'left')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left')
            ->join('locations l', 'l.id = i.location_id', 'left')
            ->where('i.invoice_date >=', $from)
            ->where('i.invoice_date <=', $to)
            ->where('i.status !=', 'cancelled');

        if ($location_id) $this->db->where('i.location_id', $location_id);
        if (!empty($filters['item_name'])) $this->_apply_name_like('ii.product_name', $filters['item_name']);
        if (!empty($filters['item_code'])) $this->db->like('p.item_code', $filters['item_code']);
        if (!empty($filters['category_id'])) $this->db->where('p.category_id', (int)$filters['category_id']);
        if (!empty($filters['brand_id'])) $this->db->where('p.brand_id', (int)$filters['brand_id']);
        if (!empty($filters['mrp']) || $filters['mrp'] === '0') $this->db->where('ii.mrp', $filters['mrp']);
        if (!empty($filters['supplier_id'])) {
            $sid = (int)$filters['supplier_id'];
            $this->db->where("p.id IN (SELECT product_id FROM supplier_products WHERE supplier_id = {$sid})", null, false);
        }
        if (!empty($filters['payment_mode'])) {
            $pm = $filters['payment_mode'];
            if ($pm === 'split') {
                $this->db->where('i.payment_mode', 'split');
            } else {
                $this->db->group_start()
                    ->where('i.payment_mode', $pm)
                    ->or_group_start()
                        ->where('i.payment_mode', 'split')
                        ->where("i.id IN (SELECT invoice_id FROM invoice_payment_splits WHERE payment_mode = " . $this->db->escape($pm) . ")", null, false)
                    ->group_end()
                ->group_end();
            }
        }

        return $this->db->order_by('i.invoice_date', 'DESC')->order_by('i.id', 'DESC')->get()->result();
    }

    public function get_refund_item_details($from, $to, $location_id = null, $filters = []) {
        $this->db->select("
                r.id AS refund_id, r.refund_no, r.refund_type, r.refund_mode, r.reason,
                r.created_by_name, DATE(r.created_at) AS refund_date,
                r.invoice_no, r.invoice_id,
                ri.product_id, ri.product_name, ri.hsn_code, ri.qty, ri.unit, ri.rate,
                ri.taxable_amt, ri.gst_percent, ri.gst_amount, ri.total,
                p.item_code, p.mrp,
                COALESCE(c.name, p.category)  AS category_name,
                COALESCE(b.name, p.brand)     AS brand_name,
                COALESCE(l.name, '')          AS location_name,
                COALESCE(cu.name, '')         AS customer_name,
                (SELECT s.name FROM supplier_products sp
                   JOIN suppliers s ON s.id = sp.supplier_id
                  WHERE sp.product_id = p.id
                  ORDER BY sp.is_primary DESC, sp.id ASC LIMIT 1) AS supplier_name
            ", false)
            ->from('refund_items ri')
            ->join('refunds r', 'r.id = ri.refund_id')
            ->join('invoices i', 'i.id = r.invoice_id', 'left')
            ->join('products p', 'p.id = ri.product_id', 'left')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left')
            ->join('locations l', 'l.id = i.location_id', 'left')
            ->join('customers cu', 'cu.id = r.customer_id', 'left')
            ->where('r.status', 'processed')
            ->where('DATE(r.created_at) >=', $from)
            ->where('DATE(r.created_at) <=', $to);

        if ($location_id) $this->db->where('i.location_id', $location_id);
        if (!empty($filters['item_name'])) $this->_apply_name_like('ri.product_name', $filters['item_name']);
        if (!empty($filters['item_code'])) $this->db->like('p.item_code', $filters['item_code']);
        if (!empty($filters['category_id'])) $this->db->where('p.category_id', (int)$filters['category_id']);
        if (!empty($filters['brand_id'])) $this->db->where('p.brand_id', (int)$filters['brand_id']);
        if (!empty($filters['supplier_id'])) {
            $sid = (int)$filters['supplier_id'];
            $this->db->where("p.id IN (SELECT product_id FROM supplier_products WHERE supplier_id = {$sid})", null, false);
        }
        if (!empty($filters['refund_mode'])) $this->db->where('r.refund_mode', $filters['refund_mode']);

        return $this->db->order_by('r.created_at', 'DESC')->order_by('r.id', 'DESC')->get()->result();
    }

    public function sales_summary($from, $to, $location_id = null, $payment_mode = null) {
        $loc_sql = $location_id ? " AND location_id = " . (int)$location_id : "";
        $pay_sql = "";
        if ($payment_mode) {
            $esc = $this->db->escape($payment_mode);
            if ($payment_mode === 'split') {
                $pay_sql = " AND payment_mode = 'split'";
            } else {
                $pay_sql = " AND (payment_mode = {$esc} OR (payment_mode = 'split' AND id IN (SELECT invoice_id FROM invoice_payment_splits WHERE payment_mode = {$esc})))";
            }
        }
        $row = $this->db->query("
            SELECT
                COUNT(*)                        AS total_invoices,
                SUM(subtotal)                   AS gross_sales,
                SUM(discount)                   AS total_discount,
                SUM(taxable_amount)             AS taxable_amount,
                SUM(cgst_amount)                AS total_cgst,
                SUM(sgst_amount)                AS total_sgst,
                SUM(igst_amount)                AS total_igst,
                SUM(total_gst)                  AS total_gst,
                SUM(total_amount)               AS net_sales,
                SUM(paid_amount)                AS total_collected,
                SUM(due_amount)                 AS total_outstanding
            FROM invoices
            WHERE invoice_date BETWEEN ? AND ?
            AND status != 'cancelled'
            {$loc_sql}
            {$pay_sql}
        ", [$from, $to])->row();

        // Net out processed refunds — they aren't reflected on the invoice row itself.
        // Shown as the "Refunds" card total (all refunds processed in the period).
        $this->db->select_sum('refunds.refund_amount', 'total_refunds')
            ->join('invoices', 'invoices.id = refunds.invoice_id', 'left')
            ->where('DATE(refunds.created_at) >=', $from)
            ->where('DATE(refunds.created_at) <=', $to)
            ->where('refunds.status', 'processed');
        if ($location_id) $this->db->where('invoices.location_id', $location_id);
        if ($payment_mode) $this->db->where('refunds.refund_mode', $payment_mode);
        $refund_row = $this->db->get('refunds')->row();
        $row->total_refunds = $refund_row->total_refunds ?? 0;

        // Only deduct from Net Sales the refunds whose original invoice is still
        // counted in gross_sales (status != cancelled). A fully-refunded invoice
        // is flipped to 'cancelled' and its total already drops out of the
        // gross_sales/net_sales SUM above — subtracting its refund again here
        // would double-count that amount.
        $this->db->select_sum('refunds.refund_amount', 'net_deduct')
            ->join('invoices', 'invoices.id = refunds.invoice_id')
            ->where('DATE(refunds.created_at) >=', $from)
            ->where('DATE(refunds.created_at) <=', $to)
            ->where('refunds.status', 'processed')
            ->where('invoices.status !=', 'cancelled');
        if ($location_id) $this->db->where('invoices.location_id', $location_id);
        if ($payment_mode) $this->db->where('refunds.refund_mode', $payment_mode);
        $net_deduct_row = $this->db->get('refunds')->row();
        $net_deduct  = $net_deduct_row->net_deduct ?? 0;
        $row->net_sales = ($row->net_sales ?? 0) - $net_deduct;

        return $row;
    }

    // -------------------------------------------------------
    // PURCHASES
    // -------------------------------------------------------
    public function get_purchases($from, $to, $location_id = null) {
        $this->db->select("
                purchases.*, suppliers.name AS supplier_name,
                (SELECT GROUP_CONCAT(DISTINCT COALESCE(b.name, p.brand) ORDER BY COALESCE(b.name, p.brand) SEPARATOR ', ')
                   FROM purchase_items pi
                   JOIN products p ON p.id = pi.product_id
                   LEFT JOIN brands b ON b.id = p.brand_id
                  WHERE pi.purchase_id = purchases.id) AS brands
            ", false)
            ->join('suppliers', 'suppliers.id = purchases.supplier_id')
            ->where('purchases.invoice_date >=', $from)
            ->where('purchases.invoice_date <=', $to);
        if ($location_id) $this->db->where('purchases.location_id', $location_id);
        return $this->db->order_by('purchases.invoice_date', 'ASC')->get('purchases')->result();
    }

    public function get_purchase_item_wise($from, $to, $product_id = null, $location_id = null) {
        $this->db->select("
            purchases.invoice_date, purchases.purchase_no, purchases.invoice_no,
            suppliers.name AS supplier_name,
            products.item_code, products.name AS product_name,
            purchase_items.qty, purchase_items.price AS basic_cost, purchase_items.mrp, 
            purchase_items.selling_price AS sp, purchase_items.gst_amount AS tax, 
            purchase_items.total AS total_amount
        ", false)
        ->from('purchase_items')
        ->join('purchases', 'purchases.id = purchase_items.purchase_id')
        ->join('products', 'products.id = purchase_items.product_id')
        ->join('suppliers', 'suppliers.id = purchases.supplier_id')
        ->where('purchases.invoice_date >=', $from)
        ->where('purchases.invoice_date <=', $to);

        if ($product_id) {
            $this->db->where('purchase_items.product_id', $product_id);
        }
        if ($location_id) {
            $this->db->where('purchases.location_id', $location_id);
        }

        return $this->db->order_by('purchases.invoice_date', 'ASC')->get()->result();
    }

    public function purchase_summary($from, $to, $location_id = null) {
        $loc_sql = $location_id ? " AND location_id = " . (int)$location_id : "";
        return $this->db->query("
            SELECT
                COUNT(*)            AS total_orders,
                SUM(subtotal)       AS gross_purchases,
                SUM(total_gst)      AS total_gst,
                SUM(total_amount)   AS net_purchases,
                SUM(paid_amount)    AS total_paid,
                SUM(due_amount)     AS total_outstanding
            FROM purchases
            WHERE invoice_date BETWEEN ? AND ?
            {$loc_sql}
        ", [$from, $to])->row();
    }

    // -------------------------------------------------------
    // STOCK
    // -------------------------------------------------------
    private function _apply_name_like($column, $value) {
        $words = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) > 1) {
            foreach ($words as $w) {
                $this->db->like($column, $w);
            }
        } else {
            $this->db->like($column, $value);
        }
    }

    private function _apply_stock_filters($filters) {
        $location_id = !empty($filters['location_id']) ? (int)$filters['location_id'] : null;
        if ($location_id) {
            $this->db->join('product_location_stock pls', "pls.product_id = p.id AND pls.location_id = {$location_id}", 'left');
        }
        if (!empty($filters['item_name'])) $this->_apply_name_like('p.name', $filters['item_name']);
        if (!empty($filters['item_code'])) $this->db->like('p.item_code', $filters['item_code']);
        if (!empty($filters['ean_code'])) {
            $ean = $this->db->escape_like_str($filters['ean_code']);
            $this->db->where("EXISTS (SELECT 1 FROM product_barcodes pbf
                                       WHERE pbf.product_id = p.id AND pbf.barcode LIKE '%{$ean}%')", null, false);
        }
        if (!empty($filters['category_id'])) $this->db->where('p.category_id', (int)$filters['category_id']);
        if (!empty($filters['brand_id'])) $this->db->where('p.brand_id', (int)$filters['brand_id']);
        if (!empty($filters['supplier_id'])) {
            $sid = (int)$filters['supplier_id'];
            $this->db->where("p.id IN (SELECT product_id FROM supplier_products WHERE supplier_id = {$sid})", null, false);
        }
        if (!empty($filters['qoh_op']) && $filters['qoh_val'] !== '' && $filters['qoh_val'] !== null) {
            $location_id = !empty($filters['location_id']) ? (int)$filters['location_id'] : null;
            $qty_col = $location_id ? 'GREATEST(COALESCE(pls.stock_qty, 0), 0)' : 'GREATEST(p.stock_qty, 0)';
            $qval = (float)$filters['qoh_val'];
            $ops = ['eq'=>'=','lt'=>'<','lte'=>'<=','gt'=>'>','gte'=>'>='];
            $op = $ops[$filters['qoh_op']] ?? null;
            if ($op) {
                $this->db->where("{$qty_col} {$op} {$qval}", null, false);
            }
        }
    }

    public function count_stock_report($filters = []) {
        $this->db->from('products p');
        $this->_apply_stock_filters($filters);
        return $this->db->count_all_results();
    }

    public function get_stock_report($filters = [], $limit = 0, $offset = 0) {
        $location_id = !empty($filters['location_id']) ? (int)$filters['location_id'] : null;
        // Operational stock never shows below zero (negative-stock billing keeps the
        // real balance in product_location_stock; see the Negative Stock audit report)
        $qty_expr = $location_id ? 'GREATEST(COALESCE(pls.stock_qty, 0), 0)' : 'GREATEST(p.stock_qty, 0)';
        $unbounded = ($limit === 0);

        $select = "
                p.id, p.name, p.item_code, p.category, p.category_id, p.brand, p.brand_id,
                p.hsn_code, {$qty_expr} AS stock_qty, p.unit,
                p.basic_cost, p.purchase_price, p.selling_price, p.wsp, p.mrp, p.gst_percent, p.expiry_date,
                ({$qty_expr} * p.basic_cost)     AS net_cost_value,
                ({$qty_expr} * p.purchase_price) AS gross_cost_value,
                ({$qty_expr} * p.selling_price)  AS mrp_value,
                COALESCE(c.name, p.category) AS category_name,
                COALESCE(b.name, p.brand)    AS brand_name";

        if (!$unbounded) {
            $select .= ",
                (SELECT pb.barcode FROM product_barcodes pb
                  WHERE pb.product_id = p.id AND pb.unit_type = 'PCS'
                  ORDER BY pb.id ASC LIMIT 1) AS ean_code,
                (SELECT sp.supplier_id FROM supplier_products sp
                   WHERE sp.product_id = p.id ORDER BY sp.is_primary DESC, sp.id ASC LIMIT 1) AS supplier_id,
                (SELECT s.name FROM supplier_products sp
                   JOIN suppliers s ON s.id = sp.supplier_id
                  WHERE sp.product_id = p.id ORDER BY sp.is_primary DESC, sp.id ASC LIMIT 1) AS supplier_name";
        }

        $this->db->select($select, false)
            ->from('products p')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left');

        $this->_apply_stock_filters($filters);

        $this->db->order_by('p.category', 'ASC')->order_by('p.name', 'ASC');
        if ($limit > 0) {
            $this->db->limit($limit, $offset);
        }
        $products = $this->db->get()->result();

        if (empty($products)) return $products;

        $pids = array_map(function($p) { return (int)$p->id; }, $products);

        if ($unbounded) {
            $ean_map = [];
            foreach (array_chunk($pids, 1000) as $chunk) {
                $rows = $this->db->select('product_id, barcode')
                    ->where('unit_type', 'PCS')
                    ->where_in('product_id', $chunk)
                    ->order_by('id', 'ASC')
                    ->get('product_barcodes')->result();
                foreach ($rows as $r) {
                    if (!isset($ean_map[$r->product_id])) $ean_map[$r->product_id] = $r->barcode;
                }
            }
            $sup_map = [];
            foreach (array_chunk($pids, 1000) as $chunk) {
                $rows = $this->db->select('sp.product_id, sp.supplier_id, s.name as supplier_name')
                    ->from('supplier_products sp')
                    ->join('suppliers s', 's.id = sp.supplier_id')
                    ->where_in('sp.product_id', $chunk)
                    ->order_by('sp.is_primary', 'DESC')->order_by('sp.id', 'ASC')
                    ->get()->result();
                foreach ($rows as $r) {
                    if (!isset($sup_map[$r->product_id])) {
                        $sup_map[$r->product_id] = $r;
                    }
                }
            }
            foreach ($products as $p) {
                $p->ean_code      = $ean_map[$p->id] ?? '';
                $p->supplier_id   = isset($sup_map[$p->id]) ? $sup_map[$p->id]->supplier_id : null;
                $p->supplier_name = isset($sup_map[$p->id]) ? $sup_map[$p->id]->supplier_name : '';
            }
        }

        if (!$location_id) {
            $per_loc = [];
            foreach (array_chunk($pids, 1000) as $chunk) {
                $loc_rows = $this->db->select('product_id, location_id, stock_qty')
                    ->where_in('product_id', $chunk)
                    ->get('product_location_stock')->result();
                foreach ($loc_rows as $r) {
                    $per_loc[$r->product_id][$r->location_id] = max(0, (float)$r->stock_qty);
                }
            }
            foreach ($products as $p) {
                $p->loc_qty = $per_loc[$p->id] ?? [];
            }
        }

        return $products;
    }

    public function total_stock_value($location_id = null) {
        if ($location_id) {
            $row = $this->db->query("
                SELECT
                    SUM(GREATEST(COALESCE(pls.stock_qty, 0), 0) * p.basic_cost)     AS net_value,
                    SUM(GREATEST(COALESCE(pls.stock_qty, 0), 0) * p.purchase_price) AS gross_value,
                    SUM(GREATEST(COALESCE(pls.stock_qty, 0), 0) * p.selling_price)  AS mrp_value
                FROM products p
                LEFT JOIN product_location_stock pls ON pls.product_id = p.id AND pls.location_id = ?
            ", [(int)$location_id])->row();
        } else {
            $row = $this->db->query("
                SELECT
                    SUM(GREATEST(stock_qty, 0) * basic_cost)     AS net_value,
                    SUM(GREATEST(stock_qty, 0) * purchase_price) AS gross_value,
                    SUM(GREATEST(stock_qty, 0) * selling_price)  AS mrp_value
                FROM products
            ")->row();
        }
        return [
            'net'   => $row->net_value ?? 0,
            'gross' => $row->gross_value ?? 0,
            'mrp'   => $row->mrp_value ?? 0,
        ];
    }

    public function get_stock_summary_grouped($filters = []) {
        $location_id = !empty($filters['location_id']) ? (int)$filters['location_id'] : null;
        $qty_expr = $location_id ? 'GREATEST(COALESCE(pls.stock_qty, 0), 0)' : 'GREATEST(p.stock_qty, 0)';
        $join_pls = $location_id
            ? "LEFT JOIN product_location_stock pls ON pls.product_id = p.id AND pls.location_id = {$location_id}"
            : '';

        $where = [];
        if (!empty($filters['item_name'])) {
            $words = preg_split('/\s+/', $filters['item_name'], -1, PREG_SPLIT_NO_EMPTY);
            foreach ($words as $w) {
                $val = $this->db->escape_like_str($w);
                $where[] = "p.name LIKE '%{$val}%'";
            }
        }
        if (!empty($filters['item_code'])) {
            $val = $this->db->escape_like_str($filters['item_code']);
            $where[] = "p.item_code LIKE '%{$val}%'";
        }
        if (!empty($filters['ean_code'])) {
            $val = $this->db->escape_like_str($filters['ean_code']);
            $where[] = "EXISTS (SELECT 1 FROM product_barcodes pbf WHERE pbf.product_id = p.id AND pbf.barcode LIKE '%{$val}%')";
        }
        if (!empty($filters['category_id'])) $where[] = 'p.category_id = ' . (int)$filters['category_id'];
        if (!empty($filters['brand_id']))    $where[] = 'p.brand_id = ' . (int)$filters['brand_id'];
        if (!empty($filters['supplier_id'])) {
            $sid = (int)$filters['supplier_id'];
            $where[] = "p.id IN (SELECT product_id FROM supplier_products WHERE supplier_id = {$sid})";
        }
        if (!empty($filters['qoh_op']) && $filters['qoh_val'] !== '' && $filters['qoh_val'] !== null) {
            $qval = (float)$filters['qoh_val'];
            $ops  = ['eq'=>'=','lt'=>'<','lte'=>'<=','gt'=>'>','gte'=>'>='];
            $op   = $ops[$filters['qoh_op']] ?? null;
            if ($op) {
                $where[] = "{$qty_expr} {$op} {$qval}";
            }
        }
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $groups = ['category' => [], 'brand' => [], 'supplier' => []];

        // Category summary
        $sql = "SELECT COALESCE(c.id, 0) AS id, COALESCE(c.name, p.category, 'Uncategorized') AS name,
                       COUNT(*) AS cnt, SUM({$qty_expr}) AS qty,
                       SUM({$qty_expr} * p.basic_cost) AS net,
                       SUM({$qty_expr} * p.purchase_price) AS gross,
                       SUM({$qty_expr} * p.selling_price) AS mrp
                FROM products p LEFT JOIN categories c ON c.id = p.category_id {$join_pls}
                {$where_clause} GROUP BY COALESCE(c.id, 0) ORDER BY name";
        foreach ($this->db->query($sql)->result() as $r) {
            $groups['category'][$r->name] = (object)['id'=>$r->id,'name'=>$r->name,'count'=>(int)$r->cnt,'qty'=>(float)$r->qty,'net'=>(float)$r->net,'gross'=>(float)$r->gross,'mrp'=>(float)$r->mrp];
        }

        // Brand summary
        $sql = "SELECT COALESCE(b.id, 0) AS id, COALESCE(b.name, p.brand, 'No Brand') AS name,
                       COUNT(*) AS cnt, SUM({$qty_expr}) AS qty,
                       SUM({$qty_expr} * p.basic_cost) AS net,
                       SUM({$qty_expr} * p.purchase_price) AS gross,
                       SUM({$qty_expr} * p.selling_price) AS mrp
                FROM products p LEFT JOIN brands b ON b.id = p.brand_id {$join_pls}
                {$where_clause} GROUP BY COALESCE(b.id, 0) ORDER BY name";
        foreach ($this->db->query($sql)->result() as $r) {
            $groups['brand'][$r->name] = (object)['id'=>$r->id,'name'=>$r->name,'count'=>(int)$r->cnt,'qty'=>(float)$r->qty,'net'=>(float)$r->net,'gross'=>(float)$r->gross,'mrp'=>(float)$r->mrp];
        }

        // Supplier summary
        $sql = "SELECT COALESCE(s.id, 0) AS id, COALESCE(s.name, 'Unassigned') AS name,
                       COUNT(*) AS cnt, SUM({$qty_expr}) AS qty,
                       SUM({$qty_expr} * p.basic_cost) AS net,
                       SUM({$qty_expr} * p.purchase_price) AS gross,
                       SUM({$qty_expr} * p.selling_price) AS mrp
                FROM products p
                LEFT JOIN supplier_products sp ON sp.product_id = p.id AND sp.is_primary = 1
                LEFT JOIN suppliers s ON s.id = sp.supplier_id
                {$join_pls}
                {$where_clause} GROUP BY COALESCE(s.id, 0) ORDER BY name";
        foreach ($this->db->query($sql)->result() as $r) {
            $groups['supplier'][$r->name] = (object)['id'=>$r->id,'name'=>$r->name,'count'=>(int)$r->cnt,'qty'=>(float)$r->qty,'net'=>(float)$r->net,'gross'=>(float)$r->gross,'mrp'=>(float)$r->mrp];
        }

        return $groups;
    }

    public function get_stock_location_grid_products($filters = [], $limit = 0, $offset = 0) {
        $this->db->select('p.id, p.name, p.item_code, p.category_id, p.brand_id, p.unit,
                            p.purchase_price, p.mrp, p.selling_price')
            ->from('products p')
            ->where('p.status', 1);

        if (!empty($filters['item_name'])) $this->_apply_name_like('p.name', $filters['item_name']);
        if (!empty($filters['item_code'])) $this->db->like('p.item_code', $filters['item_code']);
        if (!empty($filters['category_id'])) $this->db->where('p.category_id', (int)$filters['category_id']);
        if (!empty($filters['brand_id'])) $this->db->where('p.brand_id', (int)$filters['brand_id']);

        $this->db->order_by('p.name', 'ASC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_stock_location_grid_products($filters = []) {
        $this->db->from('products p')->where('p.status', 1);
        if (!empty($filters['item_name'])) $this->_apply_name_like('p.name', $filters['item_name']);
        if (!empty($filters['item_code'])) $this->db->like('p.item_code', $filters['item_code']);
        if (!empty($filters['category_id'])) $this->db->where('p.category_id', (int)$filters['category_id']);
        if (!empty($filters['brand_id'])) $this->db->where('p.brand_id', (int)$filters['brand_id']);
        return $this->db->count_all_results();
    }

    // Map of [product_id][location_id] => stock_qty, for the products given
    public function get_location_stock_map($product_ids = []) {
        if (empty($product_ids)) return [];
        $rows = $this->db->select('product_id, location_id, stock_qty')
            ->where_in('product_id', $product_ids)
            ->get('product_location_stock')->result();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->product_id][$r->location_id] = max(0, (float)$r->stock_qty);
        }
        return $map;
    }

    // -------------------------------------------------------
    // PROFIT & LOSS
    // -------------------------------------------------------
    // Revenue/COGS/Gross Profit here are built from the exact same item-level data as
    // get_profit_loss_items() (net of sales returns, COGS from actual basic_cost) —
    // this used to run its own, different math (a cost/sale-price ratio proxy for COGS,
    // and netting GST-inclusive return totals against GST-exclusive revenue), which
    // silently produced a different Gross Profit than the Item/Category/Brand/Supplier
    // tabs for the same period. Deriving both from one source guarantees they agree.
    public function get_profit_loss($from, $to, $location_id = null) {
        $items = $this->get_profit_loss_items($from, $to, ['location_id' => $location_id]);
        // Gross Sales = before any returns/refunds are netted out — the "how much did
        // we actually sell" figure. Revenue below nets it down step by step from there.
        $gross_sales        = array_sum(array_column($items, 'gross_revenue'));
        $sales_return_value = array_sum(array_column($items, 'return_amount'));
        $revenue = array_sum(array_column($items, 'revenue')); // = gross_sales - sales_return_value
        $cogs    = array_sum(array_column($items, 'cogs'));

        // Direct cash refunds (refunds table) aren't tied to any specific sold line item,
        // so they don't appear in the item-wise breakdown at all — but they're real money
        // returned to customers and must still reduce revenue here.
        $loc_inv = $location_id ? " AND location_id = " . (int)$location_id : "";
        $direct_refunds = $this->db->query("
            SELECT COALESCE(SUM(refunds.refund_amount),0) AS total
            FROM refunds
            LEFT JOIN invoices ON invoices.id = refunds.invoice_id
            WHERE DATE(refunds.created_at) BETWEEN ? AND ?
              AND refunds.status = 'processed'
              AND (invoices.status IS NULL OR invoices.status != 'cancelled')
        ", [$from, $to])->row()->total;
        $revenue -= $direct_refunds;

        // Purchases total (cost incurred), excl. GST — same basis as revenue
        $purchases = $this->db->query("
            SELECT COALESCE(SUM(taxable_amount),0) AS purchases
            FROM purchases
            WHERE invoice_date BETWEEN ? AND ?
        ", [$from, $to])->row()->purchases;

        // Total Sales Value — the GST-inclusive figure customers actually paid, i.e.
        // what Dashboard/Sales Summary call "Sales". Shown alongside excl-GST revenue
        // for reference so this report doesn't look like it disagrees with those.
        $total_sales_value = $this->db->query("
            SELECT COALESCE(SUM(total_amount),0) AS total
            FROM invoices
            WHERE invoice_date BETWEEN ? AND ?
            AND status != 'cancelled'
            {$loc_inv}
        ", [$from, $to])->row()->total;

        // Net Sales — Total Sales Value with returns/refunds netted out, still incl.
        // GST (unlike Net Revenue above, which is excl. GST). sales_returns.total_amount
        // is the GST-inclusive return total, vs. sales_return_value above which is the
        // excl-GST taxable portion used for the Net Revenue/COGS math.
        $sales_return_value_incl_gst = $this->db->query("
            SELECT COALESCE(SUM(sr.total_amount),0) AS total
            FROM sales_returns sr
            LEFT JOIN invoices inv ON inv.id = sr.invoice_id
            WHERE sr.return_date BETWEEN ? AND ?
              AND sr.status = 'confirmed'
              " . ($location_id ? " AND inv.location_id = " . (int)$location_id : "") . "
        ", [$from, $to])->row()->total;
        $net_sales_value = $total_sales_value - $sales_return_value_incl_gst - $direct_refunds;

        // GST liability
        $gst_collected = $this->db->query("
            SELECT COALESCE(SUM(total_gst),0) AS gst
            FROM invoices
            WHERE invoice_date BETWEEN ? AND ?
            AND status != 'cancelled'
            {$loc_inv}
        ", [$from, $to])->row()->gst;

        $gst_paid = $this->db->query("
            SELECT COALESCE(SUM(total_gst),0) AS gst
            FROM purchases
            WHERE invoice_date BETWEEN ? AND ?
        ", [$from, $to])->row()->gst;

        $gross_profit = $revenue - $cogs;
        $gst_payable  = $gst_collected - $gst_paid;

        return [
            'gross_sales'        => $gross_sales,
            'sales_return_value' => $sales_return_value,
            'direct_refunds'     => $direct_refunds,
            'total_sales_value'  => $total_sales_value,
            'sales_return_value_incl_gst' => $sales_return_value_incl_gst,
            'net_sales_value'    => $net_sales_value,
            'revenue'        => $revenue,
            'cogs'           => $cogs,
            'gross_profit'   => $gross_profit,
            'gross_margin'   => $revenue > 0 ? round(($gross_profit / $revenue) * 100, 2) : 0,
            'purchases'      => $purchases,
            'gst_collected'  => $gst_collected,
            'gst_paid'       => $gst_paid,
            'gst_payable'    => $gst_payable,
        ];
    }

    // Item-wise P&L for a period — Category/Brand/Supplier views are just this same
    // data bucketed in PHP (mirrors how the Stock Report's Summary tab works).
    // Revenue/COGS use taxable (excl. GST) amounts; qty and value are netted against
    // confirmed sales returns in the same period. COGS uses the product's *current*
    // basic_cost (this system doesn't track historical per-batch cost), same
    // approximation the Stock Report and the existing P&L summary already make.
    public function get_profit_loss_items($from, $to, $filters = []) {
        $location_id = !empty($filters['location_id']) ? (int)$filters['location_id'] : null;

        $sales_loc = $location_id ? ' AND inv.location_id = ' . $location_id : '';
        $ret_loc   = $location_id ? ' AND inv2.location_id = ' . $location_id : '';

        $this->db->select("
                p.id, p.name, p.item_code, p.category, p.category_id, p.brand, p.brand_id, p.unit, p.basic_cost,
                COALESCE(sales.qty, 0)     AS qty_sold,
                COALESCE(sales.revenue, 0) AS gross_revenue,
                COALESCE(ret.qty, 0)       AS qty_returned,
                COALESCE(ret.amount, 0)    AS return_amount,
                COALESCE(c.name, p.category) AS category_name,
                COALESCE(b.name, p.brand)    AS brand_name,
                (SELECT sp.supplier_id FROM supplier_products sp
                   WHERE sp.product_id = p.id ORDER BY sp.is_primary DESC, sp.id ASC LIMIT 1) AS supplier_id,
                (SELECT s.name FROM supplier_products sp
                   JOIN suppliers s ON s.id = sp.supplier_id
                  WHERE sp.product_id = p.id ORDER BY sp.is_primary DESC, sp.id ASC LIMIT 1) AS supplier_name
            ", false)
            ->from('products p')
            ->join("(
                SELECT ii.product_id, SUM(ii.qty) AS qty, SUM(ii.taxable_amt) AS revenue
                FROM invoice_items ii
                JOIN invoices inv ON inv.id = ii.invoice_id
                WHERE inv.invoice_date BETWEEN " . $this->db->escape($from) . " AND " . $this->db->escape($to) . "
                  AND inv.status != 'cancelled' {$sales_loc}
                GROUP BY ii.product_id
            ) sales", 'sales.product_id = p.id', 'inner')
            ->join("(
                SELECT sri.product_id, SUM(sri.qty) AS qty, SUM(sri.taxable_amt) AS amount
                FROM sales_return_items sri
                JOIN sales_returns sr ON sr.id = sri.sales_return_id
                LEFT JOIN invoices inv2 ON inv2.id = sr.invoice_id
                WHERE sr.return_date BETWEEN " . $this->db->escape($from) . " AND " . $this->db->escape($to) . "
                  AND sr.status = 'confirmed' {$ret_loc}
                GROUP BY sri.product_id
            ) ret", 'ret.product_id = p.id', 'left')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left');

        if (!empty($filters['item_name'])) $this->_apply_name_like('p.name', $filters['item_name']);
        if (!empty($filters['item_code'])) $this->db->like('p.item_code', $filters['item_code']);
        if (!empty($filters['category_id'])) $this->db->where('p.category_id', (int)$filters['category_id']);
        if (!empty($filters['brand_id'])) $this->db->where('p.brand_id', (int)$filters['brand_id']);
        if (!empty($filters['supplier_id'])) {
            $sid = (int)$filters['supplier_id'];
            $this->db->where("p.id IN (SELECT product_id FROM supplier_products WHERE supplier_id = {$sid})", null, false);
        }

        $rows = $this->db->order_by('p.name', 'ASC')->get()->result();

        foreach ($rows as $r) {
            $net_qty          = (float)$r->qty_sold - (float)$r->qty_returned;
            $r->net_qty        = $net_qty;
            $r->revenue        = (float)$r->gross_revenue - (float)$r->return_amount;
            $r->cogs           = $net_qty * (float)$r->basic_cost;
            $r->gross_profit   = $r->revenue - $r->cogs;
            $r->margin_pct     = $r->revenue > 0 ? round(($r->gross_profit / $r->revenue) * 100, 2) : 0;
        }
        return $rows;
    }

    // -------------------------------------------------------
    // EXECUTIVE SUMMARY DASHBOARD
    // -------------------------------------------------------

    // Full KPI bundle for one period — builds on get_profit_loss() (revenue/cogs/
    // gross profit/margin/GST) and adds bills, qty, discounts, cash vs credit,
    // customers and returns so the whole executive summary comes from one call.
    public function get_executive_summary($from, $to, $location_id = null) {
        $pl = $this->get_profit_loss($from, $to, $location_id);

        $loc_inv = $location_id ? ' AND inv.location_id = ' . (int)$location_id : '';

        $sales_row = $this->db->query("
            SELECT
                COUNT(*) AS bill_count,
                COALESCE(SUM(inv.discount + inv.bill_discount_amount),0) AS discount_given,
                COALESCE(SUM(CASE WHEN inv.payment_mode='cash'   THEN inv.total_amount ELSE 0 END),0) AS cash_sales,
                COALESCE(SUM(CASE WHEN inv.payment_mode='credit' THEN inv.total_amount ELSE 0 END),0) AS credit_sales,
                COUNT(DISTINCT inv.customer_id) AS total_customers
            FROM invoices inv
            WHERE inv.invoice_date BETWEEN ? AND ? AND inv.status != 'cancelled' {$loc_inv}
        ", [$from, $to])->row();

        $qty_row = $this->db->query("
            SELECT COALESCE(SUM(ii.qty),0) AS qty
            FROM invoice_items ii JOIN invoices inv ON inv.id = ii.invoice_id
            WHERE inv.invoice_date BETWEEN ? AND ? AND inv.status != 'cancelled' {$loc_inv}
        ", [$from, $to])->row();

        // "New" = this is the customer's first-ever invoice, and it falls in this period
        $new_cust_row = $this->db->query("
            SELECT COUNT(*) AS cnt FROM (
                SELECT customer_id, MIN(invoice_date) AS first_date
                FROM invoices WHERE status != 'cancelled'
                GROUP BY customer_id
            ) t WHERE t.first_date BETWEEN ? AND ?
        ", [$from, $to])->row();

        $sret_loc = $location_id ? ' AND inv.location_id = ' . (int)$location_id : '';
        $sret_row = $this->db->query("
            SELECT COALESCE(SUM(sr.taxable_amount),0) AS amount
            FROM sales_returns sr LEFT JOIN invoices inv ON inv.id = sr.invoice_id
            WHERE sr.return_date BETWEEN ? AND ? AND sr.status = 'confirmed' {$sret_loc}
        ", [$from, $to])->row();

        $pret_loc = $location_id ? ' AND p.location_id = ' . (int)$location_id : '';
        $pret_row = $this->db->query("
            SELECT COALESCE(SUM(pr.total_amount),0) AS amount
            FROM purchase_returns pr JOIN purchases p ON p.id = pr.purchase_id
            WHERE pr.return_date BETWEEN ? AND ? {$pret_loc}
        ", [$from, $to])->row();

        $bill_count = (int)$sales_row->bill_count;

        return array_merge($pl, [
            'bill_count'      => $bill_count,
            'avg_bill_value'  => $bill_count > 0 ? round($pl['revenue'] / $bill_count, 2) : 0,
            'qty_sold'        => (float)$qty_row->qty,
            'discount_given'  => (float)$sales_row->discount_given,
            'cash_sales'      => (float)$sales_row->cash_sales,
            'credit_sales'    => (float)$sales_row->credit_sales,
            'total_customers' => (int)$sales_row->total_customers,
            'new_customers'   => (int)$new_cust_row->cnt,
            'sales_return'    => (float)$sret_row->amount,
            'purchase_return' => (float)$pret_row->amount,
        ]);
    }

    // Stock value as of a given date, reconstructed from the same transaction-ledger
    // logic get_closing_stock_report() already uses for snapshots — so "Opening Stock"
    // (as of the day before the period starts) and "Closing Stock" (as of the period
    // end) are both real reconstructed values, not just today's totals.
    public function get_stock_value_asof($date, $location_id = null) {
        $filters = [
            'from_date'       => '2000-01-01',
            'to_date'         => $date,
            'location_id'     => $location_id,
            'show_zero_stock' => '0',
        ];
        $items = $this->get_closing_stock_report($filters);
        $value = 0; $qty = 0;
        foreach ($items as $r) {
            $value += (float)$r->closing_qty * (float)$r->basic_cost;
            $qty   += (float)$r->closing_qty;
        }
        return ['value' => $value, 'qty' => $qty];
    }

    // Sales grouped by day within a period — feeds the daily sales trend line chart
    public function get_daily_sales($from, $to, $location_id = null) {
        $loc = $location_id ? ' AND location_id = ' . (int)$location_id : '';
        return $this->db->query("
            SELECT invoice_date AS d, COALESCE(SUM(taxable_amount),0) AS revenue, COUNT(*) AS bills
            FROM invoices
            WHERE invoice_date BETWEEN ? AND ? AND status != 'cancelled' {$loc}
            GROUP BY invoice_date ORDER BY invoice_date ASC
        ", [$from, $to])->result();
    }

    // Sales / Purchases / Gross Profit for each of the last N months — feeds the
    // Sales-vs-Purchase bar chart and the Gross Profit trend line chart
    public function get_monthly_trend($months = 6, $location_id = null) {
        $loc_inv = $location_id ? ' AND inv.location_id = ' . (int)$location_id : '';
        $loc_p   = $location_id ? ' AND location_id = ' . (int)$location_id : '';
        $start   = date('Y-m-01', strtotime('-' . ($months - 1) . ' months'));

        $sales = $this->db->query("
            SELECT DATE_FORMAT(inv.invoice_date,'%Y-%m') AS ym, COALESCE(SUM(inv.taxable_amount),0) AS revenue
            FROM invoices inv
            WHERE inv.invoice_date >= ? AND inv.status != 'cancelled' {$loc_inv}
            GROUP BY ym
        ", [$start])->result();

        $purchases = $this->db->query("
            SELECT DATE_FORMAT(invoice_date,'%Y-%m') AS ym, COALESCE(SUM(taxable_amount),0) AS purchases
            FROM purchases
            WHERE invoice_date >= ? AND status != 'returned' {$loc_p}
            GROUP BY ym
        ", [$start])->result();

        $cogs = $this->db->query("
            SELECT DATE_FORMAT(inv.invoice_date,'%Y-%m') AS ym, COALESCE(SUM(ii.qty * p.basic_cost),0) AS cogs
            FROM invoice_items ii
            JOIN invoices inv ON inv.id = ii.invoice_id
            JOIN products p   ON p.id  = ii.product_id
            WHERE inv.invoice_date >= ? AND inv.status != 'cancelled' {$loc_inv}
            GROUP BY ym
        ", [$start])->result();

        $map = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $map[$ym] = ['ym' => $ym, 'label' => date('M Y', strtotime($ym.'-01')), 'revenue' => 0, 'purchases' => 0, 'cogs' => 0];
        }
        foreach ($sales as $r)     if (isset($map[$r->ym])) $map[$r->ym]['revenue']   = (float)$r->revenue;
        foreach ($purchases as $r) if (isset($map[$r->ym])) $map[$r->ym]['purchases'] = (float)$r->purchases;
        foreach ($cogs as $r)      if (isset($map[$r->ym])) $map[$r->ym]['cogs']      = (float)$r->cogs;
        foreach ($map as &$m) { $m['gross_profit'] = $m['revenue'] - $m['cogs']; }
        return array_values($map);
    }

    // Category-wise sales for a period — feeds the category pie/bar charts
    public function get_category_sales($from, $to, $location_id = null, $limit = null) {
        $loc_inv = $location_id ? ' AND inv.location_id = ' . (int)$location_id : '';
        $sql = "
            SELECT COALESCE(c.name, p.category, 'Uncategorized') AS category_name,
                   SUM(ii.taxable_amt) AS revenue, SUM(ii.qty) AS qty
            FROM invoice_items ii
            JOIN invoices inv ON inv.id = ii.invoice_id
            JOIN products p   ON p.id  = ii.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE inv.invoice_date BETWEEN ? AND ? AND inv.status != 'cancelled' {$loc_inv}
            GROUP BY category_name
            ORDER BY revenue DESC
        ";
        if ($limit) $sql .= ' LIMIT ' . (int)$limit;
        return $this->db->query($sql, [$from, $to])->result();
    }

    // Top-selling products by revenue for a period
    public function get_top_products($from, $to, $location_id = null, $limit = 10) {
        $loc_inv = $location_id ? ' AND inv.location_id = ' . (int)$location_id : '';
        return $this->db->query("
            SELECT p.id, p.name, p.item_code,
                   SUM(ii.qty) AS qty_sold,
                   SUM(ii.taxable_amt) AS revenue,
                   SUM(ii.qty * p.basic_cost) AS cogs,
                   (SUM(ii.taxable_amt) - SUM(ii.qty * p.basic_cost)) AS gross_profit
            FROM invoice_items ii
            JOIN invoices inv ON inv.id = ii.invoice_id
            JOIN products p   ON p.id  = ii.product_id
            WHERE inv.invoice_date BETWEEN ? AND ? AND inv.status != 'cancelled' {$loc_inv}
            GROUP BY ii.product_id
            ORDER BY revenue DESC
            LIMIT " . (int)$limit . "
        ", [$from, $to])->result();
    }

    // Payment mode split for a period — feeds the payment mode pie chart
    public function get_payment_mode_breakdown($from, $to, $location_id = null) {
        $loc = $location_id ? ' AND location_id = ' . (int)$location_id : '';
        return $this->db->query("
            SELECT payment_mode, COUNT(*) AS bills, COALESCE(SUM(total_amount),0) AS amount
            FROM invoices
            WHERE invoice_date BETWEEN ? AND ? AND status != 'cancelled' {$loc}
            GROUP BY payment_mode
            ORDER BY amount DESC
        ", [$from, $to])->result();
    }

    // Per-location performance for the branch comparison table
    public function get_branch_performance($from, $to) {
        $locations = $this->db->where('status', 1)->order_by('sort_order, id')->get('locations')->result();
        $out = [];
        foreach ($locations as $loc) {
            $pl = $this->get_profit_loss($from, $to, $loc->id);
            $bills_row = $this->db->query("
                SELECT COUNT(*) AS bills, COUNT(DISTINCT customer_id) AS customers
                FROM invoices WHERE invoice_date BETWEEN ? AND ? AND status != 'cancelled' AND location_id = ?
            ", [$from, $to, $loc->id])->row();
            $stock = $this->get_stock_value_asof($to, $loc->id);
            $out[] = (object)[
                'id'           => $loc->id,
                'name'         => $loc->name,
                'type'         => $loc->type,
                'sales'        => $pl['revenue'],
                'purchases'    => $pl['purchases'],
                'gross_profit' => $pl['gross_profit'],
                'margin'       => $pl['gross_margin'],
                'stock_value'  => $stock['value'],
                'bills'        => (int)$bills_row->bills,
                'customers'    => (int)$bills_row->customers,
            ];
        }
        return $out;
    }

    // Products in stock that haven't sold in 30+ days — feeds Slow-Moving / Dead Stock
    public function get_dead_slow_stock($location_id = null, $limit = 100) {
        if ($location_id) {
            $stock_join = 'JOIN product_location_stock pls ON pls.product_id = p.id AND pls.location_id = ' . (int)$location_id;
            $qty_expr = 'GREATEST(pls.stock_qty, 0)';
        } else {
            $stock_join = 'LEFT JOIN (SELECT product_id, SUM(GREATEST(stock_qty, 0)) AS stock_qty FROM product_location_stock GROUP BY product_id) pls ON pls.product_id = p.id';
            $qty_expr = 'GREATEST(COALESCE(pls.stock_qty, p.stock_qty), 0)';
        }

        return $this->db->query("
            SELECT p.id, p.name, p.item_code, {$qty_expr} AS stock_qty, p.basic_cost,
                   ({$qty_expr} * p.basic_cost) AS stock_value,
                   ls.last_sold
            FROM products p
            {$stock_join}
            LEFT JOIN (
                SELECT ii.product_id, MAX(inv.invoice_date) AS last_sold
                FROM invoice_items ii JOIN invoices inv ON inv.id = ii.invoice_id
                WHERE inv.status != 'cancelled'
                GROUP BY ii.product_id
            ) ls ON ls.product_id = p.id
            WHERE p.status = 1
              AND {$qty_expr} > 0
              AND DATEDIFF(CURDATE(), COALESCE(ls.last_sold, p.created_at)) >= 30
            ORDER BY COALESCE(ls.last_sold, p.created_at) ASC
            LIMIT " . (int)$limit . "
        ")->result();
    }

    // -------------------------------------------------------
    // GST (GSTR-1 summary)
    // -------------------------------------------------------
    public function get_gst_summary($month, $location_id = null) {
        $from = $month . '-01';
        $to   = date('Y-m-t', strtotime($from));
        $loc_sql = $location_id ? " AND inv.location_id = " . (int)$location_id : "";
        return $this->db->query("
            SELECT
                gst_percent,
                COUNT(DISTINCT inv.id)  AS invoice_count,
                SUM(ii.taxable_amt)     AS taxable_value,
                SUM(CASE WHEN inv.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END) AS cgst,
                SUM(CASE WHEN inv.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END) AS sgst,
                SUM(CASE WHEN inv.is_interstate=1 THEN ii.gst_amount   ELSE 0 END) AS igst,
                SUM(ii.gst_amount)      AS total_tax
            FROM invoice_items ii
            JOIN invoices inv ON inv.id = ii.invoice_id
            WHERE inv.invoice_date BETWEEN ? AND ?
            AND inv.status != 'cancelled'
            {$loc_sql}
            GROUP BY ii.gst_percent
            ORDER BY ii.gst_percent
        ", [$from, $to])->result();
    }

    public function get_b2b_invoices($month, $location_id = null) {
        $from = $month . '-01';
        $to   = date('Y-m-t', strtotime($from));
        $this->db->select('invoices.invoice_no, invoices.invoice_date, invoices.taxable_amount,
                      invoices.cgst_amount, invoices.sgst_amount, invoices.igst_amount,
                      invoices.total_amount, invoices.is_interstate,
                      customers.name AS customer_name, customers.gst_number AS customer_gstin')
            ->join('customers', 'customers.id = invoices.customer_id')
            ->where('invoices.invoice_date >=', $from)
            ->where('invoices.invoice_date <=', $to)
            ->where('invoices.status !=', 'cancelled')
            ->where('customers.gst_number IS NOT NULL', null, false)
            ->where('customers.gst_number !=', '');
        if ($location_id) $this->db->where('invoices.location_id', $location_id);
        return $this->db->order_by('invoices.invoice_date')->get('invoices')->result();
    }

    public function get_b2c_invoices($month, $location_id = null) {
        $from = $month . '-01';
        $to   = date('Y-m-t', strtotime($from));
        $this->db->select('invoices.invoice_no, invoices.invoice_date, invoices.taxable_amount,
                      invoices.total_gst, invoices.total_amount,
                      customers.name AS customer_name')
            ->join('customers', 'customers.id = invoices.customer_id')
            ->where('invoices.invoice_date >=', $from)
            ->where('invoices.invoice_date <=', $to)
            ->where('invoices.status !=', 'cancelled')
            ->group_start()
                ->where('customers.gst_number IS NULL', null, false)
                ->or_where('customers.gst_number', '')
            ->group_end();
        if ($location_id) $this->db->where('invoices.location_id', $location_id);
        return $this->db->order_by('invoices.invoice_date')->get('invoices')->result();
    }

    // -------------------------------------------------------
    // HSN Summary (for GSTR-1)
    // -------------------------------------------------------
    public function get_hsn_summary($from, $to, $location_id = null) {
        $loc_sql = $location_id ? " AND inv.location_id = " . (int)$location_id : "";
        $result = $this->db->query("
            SELECT
                COALESCE(ii.hsn_code, 'N/A') AS hsn_code,
                h.description AS hsn_desc,
                SUM(ii.qty) AS total_qty,
                SUM(ii.taxable_amt) AS taxable_value,
                ii.gst_percent,
                SUM(CASE WHEN inv.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END) AS cgst,
                SUM(CASE WHEN inv.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END) AS sgst,
                SUM(CASE WHEN inv.is_interstate=1 THEN ii.gst_amount   ELSE 0 END) AS igst,
                SUM(ii.gst_amount) AS total_tax,
                SUM(ii.total) AS total_value
            FROM invoice_items ii
            JOIN invoices inv ON inv.id = ii.invoice_id
            LEFT JOIN hsn_master h ON h.hsn_code = ii.hsn_code
            WHERE inv.invoice_date BETWEEN ? AND ?
            AND inv.status != 'cancelled'
            {$loc_sql}
            GROUP BY ii.hsn_code, ii.gst_percent
            ORDER BY ii.hsn_code
        ", [$from, $to]);
        return $result ? $result->result() : [];
    }

    // -------------------------------------------------------
    // GSTR-2: Purchase Input Tax
    // -------------------------------------------------------
    public function get_gstr2_purchases($from, $to) {
        return $this->db->query("
            SELECT
                p.purchase_no, p.invoice_date, p.total_amount, p.total_gst,
                s.name AS supplier_name, s.gst_number, s.state AS supplier_state
            FROM purchases p
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            WHERE p.invoice_date BETWEEN ? AND ?
            AND p.status != 'returned'
            ORDER BY p.invoice_date
        ", [$from, $to])->result();
    }

    public function get_gstr2_summary($from, $to) {
        return $this->db->query("
            SELECT
                SUM(p.total_amount - p.total_gst) AS total_taxable,
                SUM(p.total_gst) AS total_gst,
                COUNT(*) AS total_invoices
            FROM purchases p
            WHERE p.invoice_date BETWEEN ? AND ?
            AND p.status != 'returned'
        ", [$from, $to])->row();
    }

    // -------------------------------------------------------
    // GSTR-3B: Output + Input Tax
    // -------------------------------------------------------
    // -------------------------------------------------------
    // GSTR-3B: Output + Input Tax
    // -------------------------------------------------------
    public function get_gstr3b_output($from, $to) {
        $row = $this->db->query("
            SELECT
                SUM(COALESCE(cgst_amount, CASE WHEN is_interstate=0 THEN total_gst/2 ELSE 0 END)) AS cgst,
                SUM(COALESCE(sgst_amount, CASE WHEN is_interstate=0 THEN total_gst/2 ELSE 0 END)) AS sgst,
                SUM(COALESCE(igst_amount, CASE WHEN is_interstate=1 THEN total_gst   ELSE 0 END)) AS igst,
                SUM(total_gst) AS total,
                SUM(taxable_amount) AS taxable_value,
                COUNT(*) AS invoice_count
            FROM invoices
            WHERE invoice_date BETWEEN ? AND ?
            AND status != 'cancelled'
        ", [$from, $to])->row();
        return $row ? (array)$row : ['cgst'=>0,'sgst'=>0,'igst'=>0,'total'=>0,'taxable_value'=>0,'invoice_count'=>0];
    }

    public function get_gstr3b_input($from, $to) {
        $row = $this->db->query("
            SELECT
                SUM(COALESCE(cgst_amount, CASE WHEN is_interstate=0 THEN total_gst/2 ELSE 0 END)) AS cgst,
                SUM(COALESCE(sgst_amount, CASE WHEN is_interstate=0 THEN total_gst/2 ELSE 0 END)) AS sgst,
                SUM(COALESCE(igst_amount, CASE WHEN is_interstate=1 THEN total_gst   ELSE 0 END)) AS igst,
                SUM(total_gst) AS total,
                SUM(CASE WHEN total_itc_eligible > 0 OR total_itc_ineligible > 0 THEN total_itc_eligible ELSE total_gst END) AS eligible_itc,
                SUM(total_itc_ineligible) AS ineligible_itc,
                SUM(COALESCE(taxable_amount, total_amount - total_gst)) AS taxable_value,
                COUNT(*) AS purchase_count
            FROM purchases
            WHERE invoice_date BETWEEN ? AND ?
            AND status != 'returned'
        ", [$from, $to])->row();
        return $row ? (array)$row : ['cgst'=>0,'sgst'=>0,'igst'=>0,'total'=>0,'eligible_itc'=>0,'ineligible_itc'=>0,'taxable_value'=>0,'purchase_count'=>0];
    }

    // -------------------------------------------------------
    // Dashboard: Top selling products from invoices
    // -------------------------------------------------------
    public function get_top_selling_products($from, $to, $limit = 10) {
        return $this->db->query("
            SELECT p.name AS product_name, SUM(ii.qty) AS total_qty, SUM(ii.total) AS total_val
            FROM invoice_items ii
            JOIN invoices inv ON inv.id = ii.invoice_id
            JOIN products p   ON p.id  = ii.product_id
            WHERE inv.invoice_date BETWEEN ? AND ?
            AND inv.status != 'cancelled'
            GROUP BY ii.product_id
            ORDER BY total_val DESC
            LIMIT ?
        ", [$from, $to, $limit])->result();
    }

    // Low stock products
    public function get_low_stock($threshold = 5) {
        return $this->db->query("
            SELECT id, name AS product_name, item_code, GREATEST(stock_qty, 0) AS stock_qty,
                   COALESCE(low_stock_threshold, ?) AS threshold
            FROM products
            WHERE status = 1
            AND stock_qty <= ?
            ORDER BY stock_qty ASC
        ", [$threshold, $threshold])->result();
    }

    // -------------------------------------------------------
    // OUTSTANDING
    // -------------------------------------------------------
    public function get_customer_dues() {
        return $this->db
            ->select('customers.id, customers.name, customers.phone, customers.type,
                      customers.credit_limit, customers.balance,
                      COUNT(invoices.id) AS open_invoices')
            ->join('invoices', 'invoices.customer_id = customers.id AND invoices.status IN ("unpaid","partial")', 'left')
            ->where('customers.balance >', 0)
            ->where('customers.status', 1)
            ->group_by('customers.id')
            ->order_by('customers.balance', 'DESC')
            ->get('customers')
            ->result();
    }

    /**
     * Customer outstanding split by outlet. Same customers as get_customer_dues()
     * (balance > 0); each customer's balance is split by the outlet of their open
     * invoices. Any remainder not tied to an open invoice (opening balance,
     * on-account advance) is one "Unallocated" row, so with all outlets the total
     * still equals SUM(customers.balance). With an outlet filter, only that
     * outlet's open-invoice dues are shown.
     */
    public function get_customer_dues_by_location($location_id = 0) {
        $has_loc = $this->db->field_exists('location_id', 'invoices');
        $sql = "
            SELECT c.id, c.name, c.phone, c.type, c.credit_limit, c.balance,
                   " . ($has_loc ? "i.location_id, l.name AS location_name, l.code AS location_code," : "NULL AS location_id, NULL AS location_name, NULL AS location_code,") . "
                   SUM(i.total_amount) AS total_billed, SUM(i.paid_amount) AS paid,
                   SUM(i.due_amount) AS outstanding, COUNT(i.id) AS open_invoices,
                   MIN(i.invoice_date) AS oldest_date
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            " . ($has_loc ? "LEFT JOIN locations l ON l.id = i.location_id" : "") . "
            WHERE i.status IN ('unpaid','partial') AND i.due_amount > 0
              AND c.status = 1 AND c.balance > 0
              " . ($has_loc && $location_id ? "AND i.location_id = " . (int)$location_id : "") . "
            GROUP BY c.id" . ($has_loc ? ", i.location_id" : "");
        $rows = $this->db->query($sql)->result();

        if (!$location_id) {
            $alloc = [];
            foreach ($rows as $r) $alloc[$r->id] = ($alloc[$r->id] ?? 0) + (float)$r->outstanding;
            foreach ($this->get_customer_dues() as $c) {
                $rest = round((float)$c->balance - ($alloc[$c->id] ?? 0), 2);
                if (abs($rest) < 0.01) continue;
                $rows[] = (object)[
                    'id' => $c->id, 'name' => $c->name, 'phone' => $c->phone, 'type' => $c->type,
                    'credit_limit' => $c->credit_limit, 'balance' => $c->balance,
                    'location_id' => 0, 'location_name' => $rest > 0 ? 'Unallocated (opening / on account)' : 'Advance / unadjusted credit',
                    'location_code' => '', 'total_billed' => null, 'paid' => null,
                    'outstanding' => $rest, 'open_invoices' => 0, 'oldest_date' => null,
                ];
            }
        }
        usort($rows, function ($a, $b) {
            if ((float)$a->outstanding != (float)$b->outstanding) return (float)$b->outstanding <=> (float)$a->outstanding;
            return strcmp((string)$a->name, (string)$b->name);
        });
        return $rows;
    }

    // Supplier payables split by the outlet the purchase was booked at.
    public function get_supplier_dues_by_location($location_id = 0) {
        $has_loc = $this->db->field_exists('location_id', 'purchases');
        $this->db->select('suppliers.id, suppliers.name, suppliers.phone,
                           SUM(purchases.due_amount) AS total_due,
                           COUNT(purchases.id) AS open_orders')
                 ->from('purchases')
                 ->join('suppliers', 'suppliers.id = purchases.supplier_id')
                 ->where('purchases.due_amount >', 0)
                 ->where('suppliers.status', 1);
        if ($has_loc) {
            $this->db->select('purchases.location_id, l.name AS location_name, l.code AS location_code')
                     ->join('locations l', 'l.id = purchases.location_id', 'left')
                     ->group_by(['suppliers.id', 'purchases.location_id']);
            if ($location_id) $this->db->where('purchases.location_id', (int)$location_id);
        } else {
            $this->db->select('NULL AS location_id, NULL AS location_name, NULL AS location_code', FALSE)
                     ->group_by('suppliers.id');
        }
        return $this->db->order_by('total_due', 'DESC')->get()->result();
    }

    // -------------------------------------------------------
    // PARTY LEDGER
    // -------------------------------------------------------

    public function get_party_ledger_customer($customer_id, $from, $to) {
        // Invoices = Debit (customer owes us)
        $invoices = $this->db->query("
            SELECT invoice_no AS ref_no, invoice_date AS txn_date,
                   'Sales Invoice' AS txn_type, 'debit' AS entry_type,
                   total_amount AS debit, 0 AS credit, status, id AS ref_id, 'invoice' AS ref_module
            FROM invoices
            WHERE customer_id = ? AND invoice_date BETWEEN ? AND ?
            AND status != 'cancelled'
            ORDER BY invoice_date
        ", [$customer_id, $from, $to])->result_array();

        // Payments received = Credit (customer paid us)
        $payments = $this->db->query("
            SELECT COALESCE(reference_no, payment_no) AS ref_no, payment_date AS txn_date,
                   'Payment Received' AS txn_type, 'credit' AS entry_type,
                   0 AS debit, amount AS credit, 'received' AS status, id AS ref_id, 'payment' AS ref_module
            FROM payments
            WHERE party_id = ? AND party_type = 'customer' AND payment_date BETWEEN ? AND ?
            ORDER BY payment_date
        ", [$customer_id, $from, $to])->result_array();

        $all = array_merge($invoices, $payments);
        usort($all, fn($a,$b) => strcmp($a['txn_date'],$b['txn_date']) ?: strcmp($a['ref_module'],$b['ref_module']));
        return $all;
    }

    public function get_party_ledger_supplier($supplier_id, $from, $to) {
        // Purchases = Credit (we owe supplier)
        $purchases = $this->db->query("
            SELECT purchase_no AS ref_no, invoice_date AS txn_date,
                   'Purchase Bill' AS txn_type, 'credit' AS entry_type,
                   0 AS debit, total_amount AS credit, status, id AS ref_id, 'purchase' AS ref_module
            FROM purchases
            WHERE supplier_id = ? AND invoice_date BETWEEN ? AND ?
            AND status != 'returned'
            ORDER BY invoice_date
        ", [$supplier_id, $from, $to])->result_array();

        // Payments made to supplier = Debit (reduces our liability)
        $payments = $this->db->query("
            SELECT COALESCE(reference_no, payment_no) AS ref_no, payment_date AS txn_date,
                   'Payment Made' AS txn_type, 'debit' AS entry_type,
                   amount AS debit, 0 AS credit, 'paid' AS status, id AS ref_id, 'payment' AS ref_module
            FROM payments
            WHERE party_id = ? AND party_type = 'supplier' AND payment_date BETWEEN ? AND ?
            ORDER BY payment_date
        ", [$supplier_id, $from, $to])->result_array();

        $all = array_merge($purchases, $payments);
        usort($all, fn($a,$b) => strcmp($a['txn_date'],$b['txn_date']) ?: strcmp($a['ref_module'],$b['ref_module']));
        return $all;
    }

    public function get_customer_opening_balance($customer_id, $before_date) {
        // Total invoiced before period - total paid before period
        $inv = $this->db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM invoices
            WHERE customer_id=? AND invoice_date < ? AND status != 'cancelled'", [$customer_id, $before_date])->row();
        $paid = $this->db->query("SELECT COALESCE(SUM(amount),0) AS total FROM payments
            WHERE party_id=? AND party_type='customer' AND payment_date < ?", [$customer_id, $before_date])->row();
        return (float)$inv->total - (float)$paid->total;
    }

    public function get_supplier_opening_balance($supplier_id, $before_date) {
        $purchased = $this->db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM purchases
            WHERE supplier_id=? AND invoice_date < ? AND status != 'returned'", [$supplier_id, $before_date])->row();
        $paid = $this->db->query("SELECT COALESCE(SUM(amount),0) AS total FROM payments
            WHERE party_id=? AND party_type='supplier' AND payment_date < ?", [$supplier_id, $before_date])->row();
        return (float)$purchased->total - (float)$paid->total;
    }

    // -------------------------------------------------------
    // WALLET LEDGER
    // -------------------------------------------------------
    public function get_wallet_ledger($customer_id, $from, $to) {
        return $this->db->select('cwt.*')
            ->from('customer_wallet_transactions cwt')
            ->where('cwt.customer_id', $customer_id)
            ->where('DATE(cwt.created_at) >=', $from)
            ->where('DATE(cwt.created_at) <=', $to)
            ->order_by('cwt.created_at', 'ASC')
            ->get()->result_array();
    }

    // -------------------------------------------------------
    // PORTAL ORDER FUNNEL
    // -------------------------------------------------------
    public function get_portal_order_status_summary($from, $to) {
        return $this->db->select('status, COUNT(*) as cnt, SUM(total_amount) as total_value')
            ->from('order_requests')
            ->where('DATE(created_at) >=', $from)
            ->where('DATE(created_at) <=', $to)
            ->group_by('status')
            ->get()->result_array();
    }

    public function get_portal_order_detail($from, $to) {
        return $this->db->select('order_requests.request_no, order_requests.status, order_requests.total_amount, order_requests.created_at, order_requests.updated_at, customers.name as customer_name, customers.phone as customer_phone')
            ->from('order_requests')
            ->join('customers', 'customers.id = order_requests.customer_id', 'left')
            ->where('DATE(order_requests.created_at) >=', $from)
            ->where('DATE(order_requests.created_at) <=', $to)
            ->order_by('order_requests.created_at', 'DESC')
            ->get()->result_array();
    }

    public function get_portal_top_pending_customers($limit = 10) {
        return $this->db->select('order_requests.customer_id, customers.name as customer_name, customers.phone as customer_phone, COUNT(*) as pending_orders, SUM(order_requests.total_amount) as pending_value')
            ->from('order_requests')
            ->join('customers', 'customers.id = order_requests.customer_id', 'left')
            ->where('order_requests.status', 'pending')
            ->group_by('order_requests.customer_id')
            ->order_by('pending_value', 'DESC')
            ->limit($limit)
            ->get()->result_array();
    }

    // -------------------------------------------------------
    // PO vs GRN VARIANCE
    // -------------------------------------------------------
    public function get_po_grn_variance($from, $to, $pending_only = true) {
        $sql = "
            SELECT po.po_number, po.status as po_status, po.expected_delivery, s.name as supplier_name,
                   p.name as product_name, p.item_code,
                   poi.ordered_qty,
                   COALESCE(gi.accepted_qty, 0) as accepted_qty,
                   COALESCE(gi.rejected_qty, 0) as rejected_qty,
                   (poi.ordered_qty - COALESCE(gi.accepted_qty, 0)) as pending_qty
            FROM purchase_order_items poi
            JOIN purchase_orders po ON po.id = poi.po_id
            JOIN suppliers s ON s.id = po.supplier_id
            LEFT JOIN products p ON p.id = poi.product_id
            LEFT JOIN (
                SELECT gi.po_item_id, SUM(gi.accepted_qty) as accepted_qty, SUM(gi.rejected_qty) as rejected_qty
                FROM grn_items gi
                JOIN grn g ON g.id = gi.grn_id
                WHERE g.status = 'accepted'
                GROUP BY gi.po_item_id
            ) gi ON gi.po_item_id = poi.id
            WHERE po.created_at >= ? AND po.created_at <= ?
              AND po.status NOT IN ('draft', 'cancelled', 'rejected')
        ";
        if ($pending_only) {
            $sql .= " HAVING pending_qty > 0";
        }
        $sql .= " ORDER BY po.po_number ASC";
        return $this->db->query($sql, [$from . ' 00:00:00', $to . ' 23:59:59'])->result_array();
    }

    // -------------------------------------------------------
    // STOCK-TAKE VARIANCE (cross date-range)
    // -------------------------------------------------------
    public function get_stock_take_variance_summary($from, $to, $location_id = null) {
        $sql = "
            SELECT st.id, st.take_no, st.location_name, st.finalized_at,
                   SUM(CASE WHEN sti.variance != 0 THEN 1 ELSE 0 END) as variance_lines,
                   SUM(CASE WHEN sti.variance > 0 THEN sti.variance * COALESCE(p.cost_price, 0) ELSE 0 END) as surplus_value,
                   SUM(CASE WHEN sti.variance < 0 THEN ABS(sti.variance) * COALESCE(p.cost_price, 0) ELSE 0 END) as shortage_value
            FROM stock_takes st
            JOIN stock_take_items sti ON sti.stock_take_id = st.id
            LEFT JOIN products p ON p.id = sti.product_id
            WHERE st.status = 'completed'
              AND DATE(st.finalized_at) >= ? AND DATE(st.finalized_at) <= ?
        ";
        $params = [$from, $to];
        if ($location_id) { $sql .= " AND st.location_id = ?"; $params[] = $location_id; }
        $sql .= " GROUP BY st.id ORDER BY st.finalized_at DESC";
        return $this->db->query($sql, $params)->result_array();
    }

    public function get_supplier_dues() {
        return $this->db
            ->select('suppliers.id, suppliers.name, suppliers.phone,
                      SUM(purchases.due_amount) AS total_due,
                      COUNT(purchases.id) AS open_orders')
            ->join('purchases', 'purchases.supplier_id = suppliers.id AND purchases.due_amount > 0', 'left')
            ->where('suppliers.status', 1)
            ->group_by('suppliers.id')
            ->having('total_due >', 0)
            ->order_by('total_due', 'DESC')
            ->get('suppliers')
            ->result();
    }

    // ── CATEGORY REPORT ──────────────────────────────────────
    public function get_category_report($from, $to, $location_id = null) {
        $loc = $location_id ? " AND inv.location_id = " . (int)$location_id : "";
        return $this->db->query("
            SELECT
                COALESCE(cat.name, 'Uncategorized') AS category_name,
                cat.id AS category_id,
                COUNT(DISTINCT inv.id)              AS invoice_count,
                SUM(ii.qty)                         AS total_qty,
                SUM(ii.qty * ii.rate)               AS total_sales,
                SUM(ii.qty * ii.rate * (1 + COALESCE(ii.gst_percent,0)/100)) AS total_with_gst,
                SUM(ii.qty * COALESCE(p.purchase_price, 0)) AS total_cost,
                SUM(ii.qty * ii.rate) - SUM(ii.qty * COALESCE(p.purchase_price,0)) AS gross_profit
            FROM invoice_items ii
            JOIN invoices inv ON inv.id = ii.invoice_id AND inv.status != 'cancelled'
                AND inv.invoice_date BETWEEN ? AND ? {$loc}
            JOIN products p ON p.id = ii.product_id
            LEFT JOIN categories cat ON cat.id = p.category_id
            GROUP BY cat.id, cat.name
            ORDER BY total_sales DESC
        ", [$from, $to])->result();
    }

    // ── BRAND REPORT ─────────────────────────────────────────
    public function get_brand_report($from, $to, $location_id = null) {
        $loc = $location_id ? " AND inv.location_id = " . (int)$location_id : "";
        return $this->db->query("
            SELECT
                COALESCE(br.name, 'Unbranded')      AS brand_name,
                br.id AS brand_id,
                COUNT(DISTINCT p.id)                AS product_count,
                COUNT(DISTINCT inv.id)              AS invoice_count,
                SUM(ii.qty)                         AS total_qty,
                SUM(ii.qty * ii.rate)               AS total_sales,
                SUM(ii.qty * COALESCE(p.purchase_price, 0)) AS total_cost,
                SUM(ii.qty * ii.rate) - SUM(ii.qty * COALESCE(p.purchase_price,0)) AS gross_profit,
                SUM(ii.gst_amount)                  AS total_gst
            FROM invoice_items ii
            JOIN invoices inv ON inv.id = ii.invoice_id AND inv.status != 'cancelled'
                AND inv.invoice_date BETWEEN ? AND ? {$loc}
            JOIN products p ON p.id = ii.product_id
            LEFT JOIN brands br ON br.id = p.brand_id
            GROUP BY br.id, br.name
            ORDER BY total_sales DESC
        ", [$from, $to])->result();
    }

    // ── SUPPLIER SALES REPORT ─────────────────────────────────
    public function get_supplier_report($from, $to) {
        // Built as three pre-aggregated subqueries (purchase headers, purchase line
        // items, payments) joined on supplier — flattening these into one query with
        // direct JOINs fans out (purchase_items x payments) and wildly inflates SUMs.
        return $this->db->query("
            SELECT
                s.id AS supplier_id,
                COALESCE(s.name, 'Unknown') AS supplier_name,
                s.gst_number, s.phone,
                COALESCE(ph.purchase_count, 0)       AS purchase_count,
                COALESCE(it.total_qty_purchased, 0)  AS total_qty_purchased,
                COALESCE(it.total_purchase_value, 0) AS total_purchase_value,
                COALESCE(ph.total_bills, 0)          AS total_bills,
                COALESCE(pay.total_paid, 0)          AS total_paid,
                COALESCE(ph.total_bills, 0) - COALESCE(pay.total_paid, 0) AS outstanding
            FROM suppliers s
            INNER JOIN (
                SELECT supplier_id, COUNT(*) AS purchase_count, SUM(total_amount) AS total_bills
                FROM purchases
                WHERE invoice_date BETWEEN ? AND ?
                GROUP BY supplier_id
            ) ph ON ph.supplier_id = s.id
            LEFT JOIN (
                SELECT pu.supplier_id, SUM(pi.qty) AS total_qty_purchased, SUM(pi.qty * pi.price) AS total_purchase_value
                FROM purchases pu
                JOIN purchase_items pi ON pi.purchase_id = pu.id
                WHERE pu.invoice_date BETWEEN ? AND ?
                GROUP BY pu.supplier_id
            ) it ON it.supplier_id = s.id
            LEFT JOIN (
                SELECT party_id, SUM(amount) AS total_paid
                FROM payments
                WHERE party_type = 'supplier' AND payment_date BETWEEN ? AND ?
                GROUP BY party_id
            ) pay ON pay.party_id = s.id
            ORDER BY ph.total_bills DESC
        ", [$from, $to, $from, $to, $from, $to])->result();
    }

    // ── INVENTORY ANALYTICS ──────────────────────────────────
    public function get_fast_moving($from, $to, $limit = 20, $location_id = null) {
        $loc = $location_id ? " AND inv.location_id = " . (int)$location_id : "";
        return $this->db->query("
            SELECT p.id, p.name AS product_name, p.item_code, p.unit,
                COALESCE(cat.name,'') AS category_name,
                COALESCE(br.name,'') AS brand_name,
                SUM(ii.qty) AS total_sold,
                SUM(ii.qty * ii.rate) AS total_revenue,
                COUNT(DISTINCT inv.id) AS txn_count,
                GREATEST(COALESCE(p.stock_qty, 0), 0) AS current_stock
            FROM invoice_items ii
            JOIN invoices inv ON inv.id = ii.invoice_id AND inv.status != 'cancelled'
                AND inv.invoice_date BETWEEN ? AND ? {$loc}
            JOIN products p ON p.id = ii.product_id
            LEFT JOIN categories cat ON cat.id = p.category_id
            LEFT JOIN brands br ON br.id = p.brand_id
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT ?
        ", [$from, $to, $limit])->result();
    }

    public function get_slow_moving($from, $to, $limit = 20, $location_id = null) {
        $loc = $location_id ? " AND inv.location_id = " . (int)$location_id : "";
        return $this->db->query("
            SELECT p.id, p.name AS product_name, p.item_code, p.unit,
                COALESCE(cat.name,'') AS category_name,
                COALESCE(br.name,'') AS brand_name,
                COALESCE(SUM(ii.qty), 0) AS total_sold,
                GREATEST(COALESCE(p.stock_qty, 0), 0) AS current_stock,
                GREATEST(p.stock_qty, 0) * COALESCE(p.purchase_price, 0) AS stock_value
            FROM products p
            LEFT JOIN categories cat ON cat.id = p.category_id
            LEFT JOIN brands br ON br.id = p.brand_id
            LEFT JOIN invoice_items ii ON ii.product_id = p.id
            LEFT JOIN invoices inv ON inv.id = ii.invoice_id AND inv.status != 'cancelled'
                AND inv.invoice_date BETWEEN ? AND ? {$loc}
            WHERE p.status = 1 AND p.stock_qty > 0
            GROUP BY p.id
            HAVING total_sold < 5
            ORDER BY total_sold ASC, current_stock DESC
            LIMIT ?
        ", [$from, $to, $limit])->result();
    }

    // ── Fast/Slow Movement Report (full, with filters) ───────────────────────

    public function get_movement_report($from, $to, $filters = []) {
        $location_id  = !empty($filters['location_id']) ? (int)$filters['location_id'] : 0;
        $category_id  = !empty($filters['category_id']) ? (int)$filters['category_id'] : 0;
        $brand_id     = !empty($filters['brand_id'])    ? (int)$filters['brand_id']    : 0;
        $slow_thresh  = isset($filters['slow_thresh'])  ? (int)$filters['slow_thresh'] : 10;
        $fast_thresh  = isset($filters['fast_thresh'])  ? (int)$filters['fast_thresh'] : 50;

        $loc_where  = $location_id ? "AND inv.location_id = $location_id" : '';
        $cat_where  = $category_id ? "AND p.category_id = $category_id"  : '';
        $brand_where= $brand_id    ? "AND p.brand_id = $brand_id"        : '';

        $rows = $this->db->query("
            SELECT
                p.id,
                p.name             AS product_name,
                p.item_code,
                p.unit,
                COALESCE(cat.name, '')    AS category_name,
                COALESCE(br.name, '')     AS brand_name,
                COALESCE(SUM(ii.qty), 0)          AS total_sold,
                COALESCE(SUM(ii.qty * ii.rate), 0) AS total_revenue,
                COALESCE(SUM(ii.discount), 0)      AS total_discount,
                COUNT(DISTINCT inv.id)             AS txn_count,
                GREATEST(COALESCE(p.stock_qty, 0), 0) AS current_stock,
                COALESCE(p.purchase_price, p.cost_price, 0) AS cost_price,
                GREATEST(p.stock_qty, 0) * COALESCE(p.purchase_price, p.cost_price, 0) AS stock_value,
                MAX(inv.invoice_date)              AS last_sale_date,
                DATEDIFF(CURDATE(), MAX(inv.invoice_date)) AS days_since_last_sale
            FROM products p
            LEFT JOIN categories cat ON cat.id = p.category_id
            LEFT JOIN brands br      ON br.id  = p.brand_id
            LEFT JOIN invoice_items ii ON ii.product_id = p.id
            LEFT JOIN invoices inv ON inv.id = ii.invoice_id
                AND inv.status != 'cancelled'
                AND inv.invoice_date BETWEEN ? AND ?
                $loc_where
            WHERE p.status = 1
                $cat_where
                $brand_where
            GROUP BY p.id
            ORDER BY total_sold DESC
        ", [$from, $to])->result();

        $fast = $slow = $no_movement = [];
        foreach ($rows as $r) {
            $qty = (int)$r->total_sold;
            if ($qty >= $fast_thresh) {
                $r->movement_class = 'fast';
                $fast[] = $r;
            } elseif ($qty > 0 && $qty < $slow_thresh) {
                $r->movement_class = 'slow';
                $slow[] = $r;
            } elseif ($qty === 0) {
                $r->movement_class = 'none';
                $no_movement[] = $r;
            } else {
                $r->movement_class = 'medium';
            }
        }

        return [
            'all'         => $rows,
            'fast'        => $fast,
            'slow'        => $slow,
            'no_movement' => $no_movement,
            'fast_thresh' => $fast_thresh,
            'slow_thresh' => $slow_thresh,
        ];
    }

    public function get_dead_stock($days = 90, $location_id = null) {
        $since = date('Y-m-d', strtotime("-{$days} days"));
        $loc   = $location_id ? " AND pls.location_id = " . (int)$location_id : "";
        return $this->db->query("
            SELECT p.id, p.name AS product_name, p.item_code, p.unit,
                COALESCE(cat.name,'') AS category_name,
                COALESCE(br.name,'') AS brand_name,
                GREATEST(COALESCE(p.stock_qty, 0), 0) AS current_stock,
                GREATEST(p.stock_qty, 0) * COALESCE(p.purchase_price, 0) AS dead_stock_value,
                MAX(inv.invoice_date) AS last_sale_date,
                DATEDIFF(CURDATE(), COALESCE(MAX(inv.invoice_date), p.created_at)) AS days_since_sale
            FROM products p
            LEFT JOIN categories cat ON cat.id = p.category_id
            LEFT JOIN brands br ON br.id = p.brand_id
            LEFT JOIN invoice_items ii ON ii.product_id = p.id
            LEFT JOIN invoices inv ON inv.id = ii.invoice_id AND inv.status != 'cancelled'
            WHERE p.status = 1 AND p.stock_qty > 0
            GROUP BY p.id
            HAVING (last_sale_date IS NULL OR last_sale_date < ?)
            ORDER BY dead_stock_value DESC
        ", [$since])->result();
    }

    public function get_abc_analysis($from, $to) {
        $rows = $this->db->query("
            SELECT p.id, p.name AS product_name, p.item_code,
                SUM(ii.qty * ii.rate) AS revenue,
                SUM(ii.qty)           AS qty_sold
            FROM invoice_items ii
            JOIN invoices inv ON inv.id = ii.invoice_id AND inv.status != 'cancelled'
                AND inv.invoice_date BETWEEN ? AND ?
            JOIN products p ON p.id = ii.product_id
            GROUP BY p.id
            ORDER BY revenue DESC
        ", [$from, $to])->result();

        $total = array_sum(array_column((array)$rows, 'revenue'));
        $cumulative = 0;
        $result = [];
        foreach ($rows as $r) {
            $cumulative += $r->revenue;
            $pct = $total > 0 ? ($cumulative / $total) * 100 : 0;
            $r->abc_class = $pct <= 80 ? 'A' : ($pct <= 95 ? 'B' : 'C');
            $r->cumulative_pct = round($pct, 1);
            $result[] = $r;
        }
        return $result;
    }

    public function get_inventory_turnover($from, $to) {
        return $this->db->query("
            SELECT p.id, p.name AS product_name, p.item_code,
                GREATEST(COALESCE(p.stock_qty, 0), 0) AS closing_stock,
                COALESCE(p.purchase_price, 0)        AS unit_cost,
                SUM(COALESCE(ii.qty, 0))             AS qty_sold,
                SUM(COALESCE(ii.qty, 0) * COALESCE(p.purchase_price, 0)) AS cogs,
                CASE
                    WHEN p.stock_qty > 0 AND p.purchase_price > 0
                    THEN ROUND(SUM(COALESCE(ii.qty, 0)) / p.stock_qty, 2)
                    ELSE 0
                END AS turnover_ratio
            FROM products p
            LEFT JOIN invoice_items ii ON ii.product_id = p.id
            LEFT JOIN invoices inv ON inv.id = ii.invoice_id AND inv.status != 'cancelled'
                AND inv.invoice_date BETWEEN ? AND ?
            WHERE p.status = 1
            GROUP BY p.id
            ORDER BY turnover_ratio DESC
        ", [$from, $to])->result();
    }

    // -------------------------------------------------------
    // CLOSING STOCK SUMMARY
    // -------------------------------------------------------
    public function get_closing_stock_report($filters) {
        $from = isset($filters['from_date']) ? $filters['from_date'] : date('Y-m-d');
        $to   = isset($filters['to_date']) ? $filters['to_date'] : date('Y-m-d');
        $loc_id = isset($filters['location_id']) && $filters['location_id'] != '' ? (int)$filters['location_id'] : 0;
        
        // Build location conditions
        $loc_cond_p = $loc_id ? " AND p.location_id = $loc_id" : "";
        $loc_cond_i = $loc_id ? " AND i.location_id = $loc_id" : "";
        $loc_cond_sml = $loc_id ? " AND location_id = $loc_id" : "";
        $loc_cond_snap = $loc_id ? " AND location_id = $loc_id" : "";
        
        // Snapshot optimization
        $snap_row = $this->db->query("SELECT MAX(snapshot_date) as snap_date FROM stock_snapshots WHERE snapshot_date < ? {$loc_cond_snap}", [$from])->row();
        $snap_date = $snap_row && $snap_row->snap_date ? $snap_row->snap_date : null;
        $history_start = $snap_date ? date('Y-m-d', strtotime($snap_date . ' +1 day')) : '1970-01-01';
        $snap_join_cond = $snap_date ? " AND ss.snapshot_date = '".$this->db->escape_str($snap_date)."'" : " AND 1=0";
        
        $sql = "
            SELECT 
                p.id, p.item_code, p.name AS item_name, p.unit AS uom,
                COALESCE(pb.barcode, '') AS barcode,
                COALESCE(p.purchase_price, 0) AS basic_cost,
                COALESCE(p.cost_price, p.purchase_price, 0) AS cp_rate,
                COALESCE(p.mrp, 0) AS mrp,
                COALESCE(hm.igst_percent, p.gst_percent, 0) AS tax_percent,
                COALESCE(ss.qty, 0) + COALESCE(m.opening_qty, 0) AS opening_qty,
                COALESCE(m.purchase_qty, 0) AS purchase_qty,
                COALESCE(m.purchase_return_qty, 0) AS purchase_return_qty,
                COALESCE(m.transfer_in_qty, 0) AS transfer_in_qty,
                COALESCE(m.transfer_out_qty, 0) AS transfer_out_qty,
                COALESCE(m.sale_qty, 0) AS sale_qty,
                COALESCE(m.sale_return_qty, 0) AS sale_return_qty,
                COALESCE(m.stock_take_qty, 0) AS stock_take_qty,
                COALESCE(m.adjustment_qty, 0) AS adjustment_qty,
                COALESCE(m.opening_qty, 0) + COALESCE(m.purchase_qty, 0) + COALESCE(m.sale_return_qty, 0) + COALESCE(m.transfer_in_qty, 0) + COALESCE(m.adjustment_qty, 0) + COALESCE(m.stock_take_qty, 0) - COALESCE(m.sale_qty, 0) - COALESCE(m.purchase_return_qty, 0) - COALESCE(m.transfer_out_qty, 0) AS closing_qty
            FROM products p
            LEFT JOIN hsn_master hm ON hm.id = p.hsn_id
            LEFT JOIN product_barcodes pb ON pb.product_id = p.id AND pb.unit_type = 'PCS'
            LEFT JOIN stock_snapshots ss ON ss.product_id = p.id {$snap_join_cond} {$loc_cond_snap}
            LEFT JOIN (
                SELECT 
                    txn.product_id,
                    SUM(CASE WHEN txn.txn_date < ? OR (txn.txn_date >= ? AND txn.txn_date <= ? AND txn.txn_type = 'opening_stock') THEN txn.qty_in - txn.qty_out ELSE 0 END) AS opening_qty,
                    SUM(CASE WHEN txn.txn_date >= ? AND txn.txn_date <= ? AND txn.txn_type = 'purchase' THEN txn.qty_in ELSE 0 END) AS purchase_qty,
                    SUM(CASE WHEN txn.txn_date >= ? AND txn.txn_date <= ? AND txn.txn_type = 'purchase_return' THEN txn.qty_out ELSE 0 END) AS purchase_return_qty,
                    SUM(CASE WHEN txn.txn_date >= ? AND txn.txn_date <= ? AND txn.txn_type = 'transfer_in' THEN txn.qty_in ELSE 0 END) AS transfer_in_qty,
                    SUM(CASE WHEN txn.txn_date >= ? AND txn.txn_date <= ? AND txn.txn_type = 'transfer_out' THEN txn.qty_out ELSE 0 END) AS transfer_out_qty,
                    SUM(CASE WHEN txn.txn_date >= ? AND txn.txn_date <= ? AND txn.txn_type = 'sale' THEN txn.qty_out ELSE 0 END) AS sale_qty,
                    SUM(CASE WHEN txn.txn_date >= ? AND txn.txn_date <= ? AND txn.txn_type = 'sale_return' THEN txn.qty_in ELSE 0 END) AS sale_return_qty,
                    SUM(CASE WHEN txn.txn_date >= ? AND txn.txn_date <= ? AND txn.txn_type IN ('adjustment_in', 'adjustment_out') AND txn.reference_type = 'stock_take' THEN txn.qty_in - txn.qty_out ELSE 0 END) AS stock_take_qty,
                    SUM(CASE WHEN txn.txn_date >= ? AND txn.txn_date <= ? AND txn.txn_type IN ('adjustment_in', 'adjustment_out') AND txn.reference_type != 'stock_take' THEN txn.qty_in - txn.qty_out ELSE 0 END) AS adjustment_qty
                FROM (
                    SELECT pi.product_id, DATE(p.invoice_date) AS txn_date, 'purchase' AS txn_type, pi.qty AS qty_in, 0 AS qty_out, '' AS reference_type
                    FROM purchase_items pi JOIN purchases p ON p.id = pi.purchase_id WHERE DATE(p.invoice_date) >= '{$history_start}' $loc_cond_p
                    UNION ALL
                    SELECT ii.product_id, DATE(i.invoice_date) AS txn_date, 'sale' AS txn_type, 0 AS qty_in, ii.qty AS qty_out, '' AS reference_type
                    FROM invoice_items ii JOIN invoices i ON i.id = ii.invoice_id WHERE i.status != 'cancelled' AND DATE(i.invoice_date) >= '{$history_start}' $loc_cond_i
                    UNION ALL
                    SELECT pri.product_id, DATE(pr.return_date) AS txn_date, 'purchase_return' AS txn_type, 0 AS qty_in, pri.qty AS qty_out, '' AS reference_type
                    FROM purchase_return_items pri JOIN purchase_returns pr ON pr.id = pri.prn_id JOIN purchases p ON p.id = pr.purchase_id WHERE DATE(pr.return_date) >= '{$history_start}' $loc_cond_p
                    UNION ALL
                    SELECT sri.product_id, DATE(sr.return_date) AS txn_date, 'sale_return' AS txn_type, sri.qty AS qty_in, 0 AS qty_out, '' AS reference_type
                    FROM sales_return_items sri JOIN sales_returns sr ON sr.id = sri.sales_return_id JOIN invoices i ON i.id = sr.invoice_id WHERE DATE(sr.return_date) >= '{$history_start}' $loc_cond_i
                    UNION ALL
                    SELECT product_id, DATE(created_at) AS txn_date, movement_type AS txn_type, 
                           IF(movement_type IN ('adjustment_in', 'transfer_in', 'opening_stock'), quantity, 0) AS qty_in,
                           IF(movement_type IN ('adjustment_out', 'transfer_out'), quantity, 0) AS qty_out,
                           COALESCE(reference_type, '') AS reference_type
                    FROM stock_movement_log
                    WHERE movement_type IN ('adjustment_in', 'adjustment_out', 'transfer_in', 'transfer_out', 'opening_stock') AND DATE(created_at) >= '{$history_start}' $loc_cond_sml
                ) txn
                GROUP BY txn.product_id
            ) m ON m.product_id = p.id
            WHERE 1=1 
        ";
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $sql .= " AND p.status = " . (int)$filters['status'];
        } else {
            $sql .= " AND p.status = 1"; // default active
        }
        
        if (!empty($filters['department_id'])) $sql .= " AND p.department_id = " . (int)$filters['department_id'];
        if (!empty($filters['category_id'])) $sql .= " AND p.category_id = " . (int)$filters['category_id'];
        if (!empty($filters['brand_id'])) $sql .= " AND p.brand_id = " . (int)$filters['brand_id'];
        if (!empty($filters['item_code'])) $sql .= " AND p.item_code = '" . $this->db->escape_str($filters['item_code']) . "'";
        if (!empty($filters['supplier_id'])) {
            $sid = (int)$filters['supplier_id'];
            $sql .= " AND p.id IN (SELECT product_id FROM supplier_products WHERE supplier_id = {$sid}) ";
        }
        
        if (isset($filters['show_zero_stock']) && $filters['show_zero_stock'] === '0') {
            $sql .= " HAVING closing_qty != 0 ";
        }
        
        $sql .= " ORDER BY p.name ASC ";
        
        $binds = [$from, $from, $to, $from, $to, $from, $to, $from, $to, $from, $to, $from, $to, $from, $to, $from, $to, $from, $to];
        return $this->db->query($sql, $binds)->result();
    }

    // -------------------------------------------------------
    // ITEM MARGIN REPORT
    // -------------------------------------------------------
    public function get_margin_report($filters) {
        $this->db->select("
                products.id, products.item_code, products.name AS item_name,
                COALESCE(products.basic_cost, 0)   AS basic_cost,
                COALESCE(products.cost_price, products.purchase_price, 0) AS cp_rate,
                COALESCE(products.mrp, 0)          AS mrp,
                COALESCE(products.selling_price, 0) AS selling_price,
                COALESCE(products.gst_percent, 0)   AS gst_percent,
                brands.name     AS brand_name,
                categories.name AS category_name
            ", false)
            ->from('products')
            ->join('brands',     'brands.id     = products.brand_id',    'left')
            ->join('categories', 'categories.id = products.category_id', 'left');

        if (!empty($filters['item_name'])) {
            $words = preg_split('/\s+/', $filters['item_name'], -1, PREG_SPLIT_NO_EMPTY);
            if (count($words) > 1) {
                foreach ($words as $w) {
                    $this->db->group_start()
                        ->like('products.name', $w)
                        ->or_like('products.item_code', $w)
                        ->group_end();
                }
            } else {
                $this->db->group_start()
                    ->like('products.name', $filters['item_name'])
                    ->or_like('products.item_code', $filters['item_name'])
                    ->group_end();
            }
        }
        if (!empty($filters['brand_id']))    $this->db->where('products.brand_id', $filters['brand_id']);
        if (!empty($filters['category_id'])) $this->db->where('products.category_id', $filters['category_id']);

        if (isset($filters['status']) && $filters['status'] !== '') {
            $this->db->where('products.status', $filters['status']);
        } else {
            $this->db->where('products.status', 1);
        }

        return $this->db->order_by('products.name', 'ASC')->get()->result();
    }

    // -------------------------------------------------------
    // SCHEME SALES
    // -------------------------------------------------------

    // "Scheme sales" is defined at invoice level: an invoice counts as scheme-touched
    // if it has at least one scheme_application_log row in the period. This is the
    // most honest metric the current schema supports — scheme discounts are computed
    // on MRP and applied to the line's rate at billing time rather than stored as a
    // separate subtractable amount (see SchemeEngine::evaluate()), and
    // scheme_application_log links to invoice_id only, not invoice_item_id/product_id,
    // so per-line revenue attribution isn't possible without a schema change. "Scheme
    // sales %" therefore means "% of period revenue that came from baskets containing
    // at least one scheme-discounted item," not "% of revenue that was scheme-discounted."
    public function get_scheme_sales_summary($from, $to) {
        $total = $this->db->select('COUNT(*) AS invoices, COALESCE(SUM(total_amount),0) AS sales')
            ->where('invoice_date >=', $from)->where('invoice_date <=', $to)
            ->where('status !=', 'cancelled')
            ->get('invoices')->row();

        $scheme_ids = $this->db->distinct()->select('invoice_id')
            ->where('DATE(applied_at) >=', $from)->where('DATE(applied_at) <=', $to)
            ->get('scheme_application_log')->result_array();
        $ids = array_column($scheme_ids, 'invoice_id');

        $scheme_invoices = 0;
        $scheme_sales = 0.0;
        if ($ids) {
            $row = $this->db->select('COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS s')
                ->where_in('id', $ids)->where('status !=', 'cancelled')
                ->get('invoices')->row();
            $scheme_invoices = (int)$row->c;
            $scheme_sales = (float)$row->s;
        }

        $benefits = $this->db->select('COUNT(*) AS applications, COALESCE(SUM(discount_given),0) AS discount, COALESCE(SUM(free_qty),0) AS free_units')
            ->where('DATE(applied_at) >=', $from)->where('DATE(applied_at) <=', $to)
            ->get('scheme_application_log')->row();

        $total_sales = (float)$total->sales;
        return [
            'from'             => $from,
            'to'               => $to,
            'total_invoices'   => (int)$total->invoices,
            'total_sales'      => $total_sales,
            'scheme_invoices'  => $scheme_invoices,
            'scheme_sales'     => $scheme_sales,
            'non_scheme_sales' => round($total_sales - $scheme_sales, 2),
            'scheme_sales_pct' => $total_sales > 0 ? round($scheme_sales / $total_sales * 100, 2) : 0,
            'applications'     => (int)$benefits->applications,
            'total_discount'   => (float)$benefits->discount,
            'total_free_units' => (float)$benefits->free_units,
        ];
    }

    // Per-scheme breakdown for the period — uses the denormalized scheme_name/
    // scheme_type columns already stored on scheme_application_log, so a scheme that's
    // since been renamed or deleted still shows correctly for historical dates.
    public function get_scheme_wise_breakdown($from, $to) {
        return $this->db->select('scheme_id, scheme_name, scheme_type,
                COUNT(DISTINCT invoice_id) AS invoices, COUNT(DISTINCT customer_id) AS customers,
                COALESCE(SUM(discount_given),0) AS discount_given, COALESCE(SUM(free_qty),0) AS free_units')
            ->where('DATE(applied_at) >=', $from)->where('DATE(applied_at) <=', $to)
            ->group_by(['scheme_id', 'scheme_name', 'scheme_type'])
            ->order_by('discount_given', 'DESC')
            ->get('scheme_application_log')->result();
    }

    public function get_all_schemes_for_select() {
        return $this->db->select('id, name, start_date, end_date, is_active')
            ->order_by('start_date', 'DESC')
            ->get('scheme_master')->result();
    }
}