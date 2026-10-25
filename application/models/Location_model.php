<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Location_model extends CI_Model {

    public function get_all($active_only = false)
    {
        if ($active_only) {
            $this->db->where('status', 1);
        }
        return $this->db->order_by('sort_order', 'ASC')->get('locations')->result();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('locations', ['id' => $id])->row();
    }

    public function get_by_type($type)
    {
        return $this->db->where('type', $type)->where('status', 1)->order_by('sort_order')->get('locations')->result();
    }

    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('locations', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id)->update('locations', $data);
        return $this->db->affected_rows();
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->update('locations', ['status' => 0]);
    }

    // ─── Per-location stock ───────────────────────────────────────

    public function get_stock($product_id, $location_id)
    {
        $row = $this->db->get_where('product_location_stock', [
            'product_id'  => $product_id,
            'location_id' => $location_id,
        ])->row();
        return $row ? (float)$row->stock_qty : 0.0;
    }

    public function set_stock($product_id, $location_id, $qty)
    {
        $exists = $this->db->get_where('product_location_stock', [
            'product_id'  => $product_id,
            'location_id' => $location_id,
        ])->row();
        if ($exists) {
            $this->db->where('product_id', $product_id)
                     ->where('location_id', $location_id)
                     ->update('product_location_stock', ['stock_qty' => $qty]);
        } else {
            $this->db->insert('product_location_stock', [
                'product_id'  => $product_id,
                'location_id' => $location_id,
                'stock_qty'   => $qty,
            ]);
        }
    }

    public function increase_stock($product_id, $location_id, $qty)
    {
        $current = $this->get_stock($product_id, $location_id);
        $new_qty  = $current + $qty;
        $this->set_stock($product_id, $location_id, $new_qty);
        // keep products.stock_qty in sync (sum across locations)
        $this->_sync_global_stock($product_id);
        return $new_qty;
    }

    // Sales may take a location below zero (negative-stock billing). The real
    // balance is kept — e.g. 50 - 51 = -1 — so the next stock-in recovers it
    // (-1 + 10 = 9). Screens show MAX(stock, 0); see display_stock().
    public function decrease_stock($product_id, $location_id, $qty)
    {
        $current = $this->get_stock($product_id, $location_id);
        $new_qty  = $current - $qty;
        $this->set_stock($product_id, $location_id, $new_qty);
        $this->_sync_global_stock($product_id);
        return $new_qty;
    }

    // Operational (display) stock: never below zero. Use for anything shown to
    // normal users; use get_stock() for calculations and audit.
    public static function display_stock($qty)
    {
        return max(0, (float)$qty);
    }

    private function _sync_global_stock($product_id)
    {
        // products.stock_qty is the DISPLAY total: each location's balance capped
        // at 0, then summed — a location that sold past zero shows 0, and its
        // shortfall never hides another location's real stock. The true (possibly
        // negative) balance stays in product_location_stock + stock_movement_log.
        $row = $this->db->select('SUM(GREATEST(stock_qty, 0)) as total', FALSE)
                        ->where('product_id', $product_id)
                        ->get('product_location_stock')->row();
        $total = $row ? (float)$row->total : 0;
        $this->db->where('id', $product_id)->update('products', ['stock_qty' => $total]);
    }

    // ─── Stock overview for a location ───────────────────────────

    public function get_location_stock($location_id, $limit = 0, $offset = 0, $search = '', $stock_filter = '', $category = '')
    {
        $lid = (int)$location_id;
        $where = "WHERE p.status = 1";
        $binds = [];
        $this->load->helper('product_search');
        $ws = product_words_sql($search, ['p.name', 'p.item_code', 'cat.name']);
        if ($ws) {
            $where .= " AND " . $ws;
        } elseif ($search !== '') {
            $where .= " AND (p.name LIKE ? OR p.item_code LIKE ? OR EXISTS (SELECT 1 FROM product_barcodes pb2 WHERE pb2.product_id = p.id AND pb2.barcode LIKE ?))";
            $binds[] = "%{$search}%";
            $binds[] = "%{$search}%";
            $binds[] = "%{$search}%";
        }
        if ($category !== '') {
            $where .= " AND cat.name = ?";
            $binds[] = $category;
        }
        $having = '';
        if ($stock_filter === 'out') $having = "HAVING stock_qty <= 0";
        elseif ($stock_filter === 'low') $having = "HAVING stock_qty > 0 AND stock_qty <= min_stock";
        elseif ($stock_filter === 'ok') $having = "HAVING stock_qty > min_stock";

        $limit_sql = $limit > 0 ? "LIMIT {$limit} OFFSET {$offset}" : "";

        return $this->db->query("
            SELECT
                p.id, p.name, p.item_code, p.unit,
                p.low_stock_threshold  AS min_stock,
                p.purchase_price       AS purchase_price,
                p.mrp                  AS mrp,
                p.selling_price        AS selling_price,
                COALESCE(cat.name,'')  AS category_name,
                COALESCE(pb.barcode,'') AS barcode,
                GREATEST(COALESCE(pls.stock_qty, 0), 0) AS stock_qty,
                COALESCE(pls.stock_qty, 0) AS actual_qty
            FROM products p
            LEFT JOIN product_location_stock pls ON pls.product_id = p.id AND pls.location_id = $lid
            LEFT JOIN categories cat ON cat.id = p.category_id
            LEFT JOIN product_barcodes pb ON pb.product_id = p.id AND pb.unit_type = 'PCS'
            $where
            GROUP BY p.id
            $having
            ORDER BY cat.name, p.name
            $limit_sql
        ", $binds)->result();
    }

    public function count_location_stock($location_id, $search = '', $stock_filter = '', $category = '')
    {
        $lid = (int)$location_id;
        $where = "WHERE p.status = 1";
        $binds = [];
        $this->load->helper('product_search');
        $ws = product_words_sql($search, ['p.name', 'p.item_code', 'cat.name']);
        if ($ws) {
            $where .= " AND " . $ws;
        } elseif ($search !== '') {
            $where .= " AND (p.name LIKE ? OR p.item_code LIKE ? OR EXISTS (SELECT 1 FROM product_barcodes pb2 WHERE pb2.product_id = p.id AND pb2.barcode LIKE ?))";
            $binds[] = "%{$search}%";
            $binds[] = "%{$search}%";
            $binds[] = "%{$search}%";
        }
        if ($category !== '') {
            $where .= " AND cat.name = ?";
            $binds[] = $category;
        }
        $having = '';
        if ($stock_filter === 'out') $having = "HAVING stock_qty <= 0";
        elseif ($stock_filter === 'low') $having = "HAVING stock_qty > 0 AND stock_qty <= min_stock";
        elseif ($stock_filter === 'ok') $having = "HAVING stock_qty > min_stock";

        $row = $this->db->query("
            SELECT COUNT(*) AS cnt FROM (
                SELECT p.id,
                    GREATEST(COALESCE(pls.stock_qty, 0), 0) AS stock_qty,
                    p.low_stock_threshold AS min_stock
                FROM products p
                LEFT JOIN product_location_stock pls ON pls.product_id = p.id AND pls.location_id = $lid
                LEFT JOIN categories cat ON cat.id = p.category_id
                $where
                GROUP BY p.id
                $having
            ) t
        ", $binds)->row();
        return (int)($row->cnt ?? 0);
    }

    public function get_location_stock_kpis($location_id)
    {
        $lid = (int)$location_id;
        return $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN COALESCE(pls.stock_qty,0) <= 0 THEN 1 ELSE 0 END) AS out_count,
                SUM(CASE WHEN COALESCE(pls.stock_qty,0) > 0 AND COALESCE(pls.stock_qty,0) <= p.low_stock_threshold THEN 1 ELSE 0 END) AS low_count,
                SUM(CASE WHEN COALESCE(pls.stock_qty,0) > p.low_stock_threshold THEN 1 ELSE 0 END) AS ok_count,
                COALESCE(SUM(GREATEST(COALESCE(pls.stock_qty,0), 0)), 0) AS total_qty,
                COALESCE(SUM(GREATEST(COALESCE(pls.stock_qty,0), 0) * p.purchase_price), 0) AS total_val
            FROM products p
            LEFT JOIN product_location_stock pls ON pls.product_id = p.id AND pls.location_id = $lid
            WHERE p.status = 1
        ")->row();
    }

    public function get_location_categories($location_id)
    {
        $lid = (int)$location_id;
        return $this->db->query("
            SELECT DISTINCT COALESCE(cat.name, '') AS category_name
            FROM products p
            LEFT JOIN categories cat ON cat.id = p.category_id
            WHERE p.status = 1
            ORDER BY category_name
        ")->result();
    }

    public function get_low_stock($location_id)
    {
        return $this->db
            ->select('p.id, p.name, p.item_code, p.unit, p.low_stock_threshold AS min_stock,
                      GREATEST(COALESCE(pls.stock_qty, 0), 0) AS stock_qty', FALSE)
            ->from('products p')
            ->join('product_location_stock pls', "pls.product_id = p.id AND pls.location_id = {$location_id}", 'left')
            ->where('p.status', 1)
            ->where('COALESCE(pls.stock_qty, 0) <= p.low_stock_threshold', NULL, FALSE)
            ->order_by('stock_qty', 'ASC')
            ->get()->result();
    }

    public function get_stock_value($location_id)
    {
        $row = $this->db
            ->select('SUM(p.cost_price * GREATEST(COALESCE(pls.stock_qty, 0), 0)) AS value', FALSE)
            ->from('products p')
            ->join('product_location_stock pls', "pls.product_id = p.id AND pls.location_id = {$location_id}", 'left')
            ->where('p.status', 1)
            ->get()->row();
        return $row ? (float)$row->value : 0;
    }

    // ─── Movement log ─────────────────────────────────────────────

    public function log_movement($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('stock_movement_log', $data);
        return $this->db->insert_id();
    }

    public function get_movements($filters = [])
    {
        $this->db->select('sml.*, p.name as product_name, p.item_code, p.mrp')
                 ->from('stock_movement_log sml')
                 ->join('products p', 'p.id = sml.product_id', 'left');
        if (!empty($filters['location_id'])) {
            $this->db->where('sml.location_id', $filters['location_id']);
        }
        if (!empty($filters['product_id'])) {
            $this->db->where('sml.product_id', $filters['product_id']);
        }
        $this->load->helper('product_search');
        $ws = !empty($filters['product_search']) ? product_words_sql($filters['product_search'], ['p.name', 'p.item_code']) : NULL;
        if ($ws) {
            $this->db->where($ws, NULL, FALSE);
        } elseif (!empty($filters['product_search'])) {
            $this->db->group_start()
                     ->like('p.name', $filters['product_search'])
                     ->or_like('p.item_code', $filters['product_search'])
                     ->group_end();
        }
        if (!empty($filters['item_code'])) {
            $this->db->like('p.item_code', $filters['item_code']);
        }
        if (!empty($filters['ean_code'])) {
            $ean = $this->db->escape_like_str($filters['ean_code']);
            $this->db->where("EXISTS (SELECT 1 FROM product_barcodes pb WHERE pb.product_id = sml.product_id AND pb.barcode LIKE '%{$ean}%')", null, false);
        }
        if (!empty($filters['movement_type'])) {
            $this->db->where('sml.movement_type', $filters['movement_type']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('DATE(sml.created_at) >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('DATE(sml.created_at) <=', $filters['date_to']);
        }
        return $this->db->order_by('sml.created_at', 'DESC')->limit(500)->get()->result();
    }

    public function get_movement_summary($filters = [])
    {
        $this->db->select('sml.product_id, sml.movement_type, sml.quantity, sml.balance_before, sml.balance_after, p.name AS product_name, p.item_code, p.mrp, p.unit')
                 ->from('stock_movement_log sml')
                 ->join('products p', 'p.id = sml.product_id', 'left');

        if (!empty($filters['location_id']))   $this->db->where('sml.location_id', $filters['location_id']);
        if (!empty($filters['product_id']))    $this->db->where('sml.product_id', $filters['product_id']);
        if (!empty($filters['item_code']))     $this->db->like('p.item_code', $filters['item_code']);
        if (!empty($filters['ean_code'])) {
            $ean = $this->db->escape_like_str($filters['ean_code']);
            $this->db->where("EXISTS (SELECT 1 FROM product_barcodes pb WHERE pb.product_id = sml.product_id AND pb.barcode LIKE '%{$ean}%')", null, false);
        }
        if (!empty($filters['movement_type'])) $this->db->where('sml.movement_type', $filters['movement_type']);
        if (!empty($filters['date_from']))     $this->db->where('DATE(sml.created_at) >=', $filters['date_from']);
        if (!empty($filters['date_to']))       $this->db->where('DATE(sml.created_at) <=', $filters['date_to']);

        $rows = $this->db->order_by('sml.created_at', 'ASC')->order_by('sml.id', 'ASC')->get()->result();

        $in_types  = ['purchase_in','transfer_in','adjustment_in','refund_in','opening_stock'];
        $out_types = ['sale_out','transfer_out','adjustment_out'];
        $summary   = [];

        foreach ($rows as $r) {
            $pid = $r->product_id;
            if (!isset($summary[$pid])) {
                $summary[$pid] = (object)[
                    'product_id'    => $pid,
                    'product_name'  => $r->product_name,
                    'item_code'     => $r->item_code,
                    'mrp'           => (float)$r->mrp,
                    'unit'          => $r->unit,
                    'opening_stock' => (float)$r->balance_before,
                    'stock_in'      => 0,
                    'stock_out'     => 0,
                    'closing_stock' => (float)$r->balance_after,
                ];
            }
            if (in_array($r->movement_type, $in_types))  $summary[$pid]->stock_in  += (float)$r->quantity;
            if (in_array($r->movement_type, $out_types)) $summary[$pid]->stock_out += (float)$r->quantity;
            $summary[$pid]->closing_stock = (float)$r->balance_after;
        }

        usort($summary, function($a, $b) { return strcmp($a->product_name, $b->product_name); });
        return array_values($summary);
    }
}
