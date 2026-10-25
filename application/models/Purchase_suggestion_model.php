<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchase_suggestion_model extends CI_Model {

    /**
     * Core suggestion engine.
     * Returns products with recommended purchase quantities.
     *
     * @param int    $supplier_id   0 = all suppliers
     * @param int    $days          sales analysis period
     * @param string $from          custom from date
     * @param string $to            custom to date
     * @param int    $location_id
     * @param int    $brand_id      0 = all brands
     */
    public function get_suggestions($supplier_id = 0, $days = 30, $from = null, $to = null, $location_id = null, $brand_id = 0) {
        $to_date   = $to   ? $to   : date('Y-m-d');
        $from_date = $from ? $from : date('Y-m-d', strtotime("-{$days} days"));
        $period_days = max(1, (int)((strtotime($to_date) - strtotime($from_date)) / 86400));

        // Last purchase price/date come from actual purchase history, not a
        // cached column on supplier_products (which doesn't track this) —
        // same live-lookup approach used by Purchases::get_product_history_ajax().
        $supplier_filter = $supplier_id ? " AND pu.supplier_id = {$supplier_id}" : '';
        $lp_price_sql = "(SELECT pi.price FROM purchase_items pi JOIN purchases pu ON pu.id = pi.purchase_id WHERE pi.product_id = p.id{$supplier_filter} ORDER BY pu.invoice_date DESC, pu.id DESC LIMIT 1)";
        $lp_date_sql  = "(SELECT pu.invoice_date FROM purchase_items pi JOIN purchases pu ON pu.id = pi.purchase_id WHERE pi.product_id = p.id{$supplier_filter} ORDER BY pu.invoice_date DESC, pu.id DESC LIMIT 1)";

        // Get products (optionally filtered by supplier / brand)
        $this->db->select("p.id, p.name, p.item_code, p.unit, p.stock_qty, p.gst_percent,
                           p.mrp, p.selling_price, p.basic_cost,
                           COALESCE({$lp_price_sql}, p.basic_cost) as last_purchase_price,
                           {$lp_date_sql} as last_purchase_date,
                           s.name as supplier_name,
                           COALESCE(br.name, p.brand) as brand_name,
                           s.lead_time_days, svs.interval_days, svs.schedule_type,
                           COALESCE(svs.interval_days, 7) as visit_cycle_days", false)
            ->from('products p');

        if ($supplier_id) {
            $this->db->join('supplier_products sp', "sp.product_id = p.id AND sp.supplier_id = {$supplier_id}", 'inner')
                ->join('suppliers s', "s.id = {$supplier_id}", 'inner')
                ->join('supplier_visit_schedules svs', "svs.supplier_id = {$supplier_id}", 'left');
        } else {
            $this->db->join('supplier_products sp', 'sp.product_id = p.id AND sp.is_primary = 1', 'left')
                ->join('suppliers s', 's.id = sp.supplier_id', 'left')
                ->join('supplier_visit_schedules svs', 'svs.supplier_id = s.id', 'left');
        }
        $this->db->join('brands br', 'br.id = p.brand_id', 'left');

        $this->db->where('p.status', 1);
        if ($location_id) $this->db->where('p.location_id', $location_id);
        if ($brand_id) $this->db->where('p.brand_id', $brand_id);

        $products = $this->db->group_by('p.id')->get()->result();

        $result = [];
        foreach ($products as $p) {
            // Total sales in period
            $sales = $this->_get_sales_qty($p->id, $from_date, $to_date, $location_id);
            $avg_daily = $period_days > 0 ? round($sales / $period_days, 3) : 0;

            $visit_cycle  = max(1, (int)($p->visit_cycle_days ?? 7));
            $lead_time    = max(0, (int)($p->lead_time_days  ?? 2));
            $safety_stock = max(5, round($avg_daily * 3)); // 3 days safety

            // Pending PO qty
            $pending_po = $this->_get_pending_po_qty($p->id);

            // Formula: (avg_daily × (visit_cycle + lead_time)) + safety − stock − pending
            $required = ($avg_daily * ($visit_cycle + $lead_time)) + $safety_stock;
            $suggested = max(0, round($required - $p->stock_qty - $pending_po));

            // Stock cover days
            $cover_days = $avg_daily > 0 ? round($p->stock_qty / $avg_daily) : 999;

            // Classification
            $tag = $this->_classify($avg_daily, $sales, $cover_days);

            $result[] = [
                'product_id'         => $p->id,
                'name'               => $p->name,
                'item_code'          => $p->item_code,
                'supplier_name'      => $p->supplier_name,
                'brand_name'         => $p->brand_name,
                'unit'               => $p->unit,
                'current_stock'      => (float)$p->stock_qty,
                'mrp'                => (float)$p->mrp,
                'selling_price'      => (float)$p->selling_price,
                'last_purchase_price'=> (float)$p->last_purchase_price,
                'last_purchase_date' => $p->last_purchase_date,
                'gst_percent'        => (float)$p->gst_percent,
                'total_sales_qty'    => $sales,
                'avg_daily_sales'    => $avg_daily,
                'visit_cycle_days'   => $visit_cycle,
                'lead_time_days'     => $lead_time,
                'safety_stock'       => $safety_stock,
                'pending_po_qty'     => $pending_po,
                'suggested_qty'      => $suggested,
                'cover_days'         => $cover_days,
                'tag'                => $tag,
                'period_days'        => $period_days,
                'from_date'          => $from_date,
                'to_date'            => $to_date,
            ];
        }

        // Sort: out of stock first, then low stock, then by suggested qty desc
        usort($result, function($a, $b) {
            if ($a['current_stock'] == 0 && $b['current_stock'] > 0) return -1;
            if ($b['current_stock'] == 0 && $a['current_stock'] > 0) return  1;
            return $b['suggested_qty'] - $a['suggested_qty'];
        });

        return $result;
    }

    private function _get_sales_qty($product_id, $from, $to, $location_id = null) {
        $q = $this->db
            ->select_sum('ii.qty', 'total')
            ->from('invoice_items ii')
            ->join('invoices i', 'i.id = ii.invoice_id')
            ->where('ii.product_id', $product_id)
            ->where('i.status !=', 'cancelled')
            ->where('DATE(i.invoice_date) >=', $from)
            ->where('DATE(i.invoice_date) <=', $to);
        if ($location_id) $q->where('i.location_id', $location_id);
        $r = $q->get()->row();
        return (float)($r->total ?? 0);
    }

    private function _get_pending_po_qty($product_id) {
        $r = $this->db
            ->select_sum('poi.ordered_qty', 'total')
            ->from('purchase_order_items poi')
            ->join('purchase_orders po', 'po.id = poi.po_id')
            ->where('poi.product_id', $product_id)
            ->where_in('po.status', ['approved','vendor_confirmed','dispatched','in_transit'])
            ->get()->row();
        return (float)($r->total ?? 0);
    }

    private function _classify($avg_daily, $total_sales, $cover_days) {
        if ($total_sales == 0) return 'dead';
        if ($avg_daily >= 5)   return 'fast';
        if ($avg_daily >= 1)   return 'medium';
        if ($cover_days > 90)  return 'slow';
        return 'slow';
    }
}
