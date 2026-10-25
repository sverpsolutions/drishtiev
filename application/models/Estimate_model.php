<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Estimate_model extends CI_Model {

    // -----------------------------------------------
    // Generate estimate number: EST-YYYYMMDD-XXXX
    // -----------------------------------------------
    public function generate_estimate_no() {
        $prefix = 'EST-' . date('Ymd') . '-';
        $last = $this->db->like('estimate_no', $prefix, 'after')
                         ->order_by('id', 'DESC')
                         ->limit(1)
                         ->get('estimates')->row();
        if ($last) {
            $parts = explode('-', $last->estimate_no);
            $seq   = (int)end($parts) + 1;
        } else {
            $seq = 1;
        }
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // -----------------------------------------------
    // Create estimate
    // -----------------------------------------------
    public function create($data, $items) {
        $this->db->trans_start();

        $this->db->insert('estimates', $data);
        $estimate_id = $this->db->insert_id();

        foreach ($items as &$item) {
            $item['estimate_id'] = $estimate_id;
        }
        $this->db->insert_batch('estimate_items', $items);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) return FALSE;
        return $estimate_id;
    }

    // -----------------------------------------------
    // Get all estimates with optional filters
    // -----------------------------------------------
    public function get_all($filters = []) {
        // COALESCE: if customer_id is set, use customers.name, else fall back to stored text
        $this->db->select('e.*, u.name as created_by_name,
                           COALESCE(IF(e.customer_id > 0, c.name, NULL), e.customer_name) as customer_name')
                 ->from('estimates e')
                 ->join('users u',     'u.id = e.created_by', 'left')
                 ->join('customers c', 'c.id = e.customer_id', 'left')
                 ->order_by('e.id', 'DESC');

        if (!empty($filters['status'])) {
            $this->db->where('e.status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('e.estimate_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('e.estimate_date <=', $filters['date_to']);
        }
        if (!empty($filters['customer_name'])) {
            $this->db->like('e.customer_name', $filters['customer_name']);
        }

        return $this->db->get()->result();
    }

    // -----------------------------------------------
    // Get single estimate with items
    // -----------------------------------------------
    public function get_by_id($id) {
        return $this->db->where('id', $id)->get('estimates')->row();
    }

    public function get_items($estimate_id) {
        return $this->db->select('ei.*, p.item_code, GREATEST(p.stock_qty, 0) AS stock_qty', FALSE)
                        ->from('estimate_items ei')
                        ->join('products p', 'p.id = ei.product_id', 'left')
                        ->where('ei.estimate_id', $estimate_id)
                        ->get()->result();
    }

    // -----------------------------------------------
    // Update estimate
    // -----------------------------------------------
    public function update($id, $data, $items) {
        $this->db->trans_start();

        $this->db->where('id', $id)->update('estimates', $data);
        $this->db->where('estimate_id', $id)->delete('estimate_items');

        foreach ($items as &$item) {
            $item['estimate_id'] = $id;
        }
        $this->db->insert_batch('estimate_items', $items);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // -----------------------------------------------
    // Close as Cash Sale (deduct stock)
    // -----------------------------------------------
    public function close_as_cash($estimate_id, $user_id) {
        $estimate = $this->get_by_id($estimate_id);
        if (!$estimate || $estimate->status !== 'pending') return FALSE;

        $items = $this->get_items($estimate_id);

        $this->db->trans_start();

        // Deduct stock for each item
        foreach ($items as $item) {
            // Check stock
            $product = $this->db->where('id', $item->product_id)->get('products')->row();
            if ($product && $product->stock_qty >= $item->qty) {
                $this->db->where('id', $item->product_id)
                         ->set('stock_qty', 'stock_qty - ' . (float)$item->qty, FALSE)
                         ->update('products');

                // Stock ledger entry
                $balance = $product->stock_qty - $item->qty;
                $this->db->insert('stock_ledger', [
                    'product_id'  => $item->product_id,
                    'txn_type'    => 'estimate_close',
                    'txn_id'      => $estimate_id,
                    'txn_no'      => $estimate->estimate_no,
                    'qty_in'      => 0,
                    'qty_out'     => $item->qty,
                    'balance_qty' => $balance,
                    'rate'        => $item->rate,
                    'notes'       => 'Cash Sale (No Bill): ' . $estimate->estimate_no,
                    'created_by'  => $user_id,
                ]);
            }
        }

        // Update estimate status
        $this->db->where('id', $estimate_id)->update('estimates', [
            'status'    => 'closed_cash',
            'closed_by' => $user_id,
            'closed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // -----------------------------------------------
    // Cancel estimate
    // -----------------------------------------------
    public function cancel($estimate_id) {
        $estimate = $this->get_by_id($estimate_id);
        if (!$estimate || $estimate->status !== 'pending') return FALSE;

        return $this->db->where('id', $estimate_id)
                        ->update('estimates', ['status' => 'cancelled']);
    }

    // -----------------------------------------------
    // Convert to invoice (mark status; actual invoice created by Billing)
    // -----------------------------------------------
    public function mark_converted($estimate_id, $invoice_id) {
        return $this->db->where('id', $estimate_id)->update('estimates', [
            'status'       => 'converted',
            'converted_to' => $invoice_id,
        ]);
    }

    // -----------------------------------------------
    // Stock check for items
    // -----------------------------------------------
    public function check_stock($product_id, $qty) {
        $product = $this->db->select('stock_qty, name')
                            ->where('id', $product_id)
                            ->get('products')->row();
        if (!$product) return ['ok' => FALSE, 'available' => 0, 'name' => ''];

        return [
            'ok'        => $product->stock_qty >= $qty,
            'available' => (float)$product->stock_qty,
            'name'      => $product->name,
        ];
    }

    // -----------------------------------------------
    // Dashboard metrics for estimates
    // -----------------------------------------------
    public function get_dashboard_stats($date_from = NULL, $date_to = NULL) {
        $date_from = $date_from ?: date('Y-m-01');
        $date_to   = $date_to   ?: date('Y-m-d');

        $this->db->where('estimate_date >=', $date_from)
                 ->where('estimate_date <=', $date_to);
        $total = $this->db->select('COUNT(*) as cnt, SUM(total_amount) as val')
                          ->get('estimates')->row();

        $this->db->where('estimate_date >=', $date_from)
                 ->where('estimate_date <=', $date_to)
                 ->where('status', 'closed_cash');
        $cash = $this->db->select('COUNT(*) as cnt, SUM(total_amount) as val')
                         ->get('estimates')->row();

        $this->db->where('estimate_date >=', $date_from)
                 ->where('estimate_date <=', $date_to)
                 ->where('status', 'converted');
        $converted = $this->db->select('COUNT(*) as cnt')->get('estimates')->row();

        return [
            'total_count'     => $total->cnt ?? 0,
            'total_value'     => $total->val ?? 0,
            'cash_count'      => $cash->cnt ?? 0,
            'cash_value'      => $cash->val ?? 0,
            'converted_count' => $converted->cnt ?? 0,
        ];
    }

    // -----------------------------------------------
    // Get top items from estimates + cash closures
    // -----------------------------------------------
    public function get_top_items($limit = 10) {
        return $this->db->select('p.name AS product_name, SUM(ei.qty) as total_qty, SUM(ei.total) as total_val')
                        ->from('estimate_items ei')
                        ->join('estimates e', 'e.id = ei.estimate_id')
                        ->join('products p', 'p.id = ei.product_id', 'left')
                        ->where_in('e.status', ['closed_cash', 'converted'])
                        ->group_by('ei.product_id')
                        ->order_by('total_val', 'DESC')
                        ->limit($limit)
                        ->get()->result();
    }
}
