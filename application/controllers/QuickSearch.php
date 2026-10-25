<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class QuickSearch extends Auth_Controller {

    public function search_ajax() {
        header('Content-Type: application/json');
        $q = trim($this->input->get('q', TRUE));
        if (strlen($q) < 2) { echo json_encode(['products'=>[],'customers'=>[]]); return; }

        $words = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) > 1) {
            $wc = [];
            foreach ($words as $w) {
                $wl = $this->db->escape('%' . $this->db->escape_like_str($w) . '%');
                $wc[] = "(p.name LIKE $wl OR p.item_code LIKE $wl)";
            }
            $prod_where = implode(' AND ', $wc);
        } else {
            $like = '%' . $this->db->escape_like_str($q) . '%';
            $q_e  = $this->db->escape($q);
            $like_e = $this->db->escape($like);
            $prod_where = "(p.name LIKE $like_e OR p.item_code LIKE $like_e OR p.item_code = $q_e)";
        }

        // Products
        $products = $this->db->query(
            "SELECT p.id, p.name, p.item_code,
                    COALESCE(c.name,'') AS category,
                    COALESCE(p.selling_price,0) AS price,
                    GREATEST(COALESCE(p.stock_qty,0), 0) AS stock
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = 1
               AND $prod_where
             ORDER BY p.name LIMIT 8"
        )->result();

        // Customers
        if (count($words) > 1) {
            $cwc = [];
            foreach ($words as $w) {
                $wl = $this->db->escape('%' . $this->db->escape_like_str($w) . '%');
                $cwc[] = "name LIKE $wl";
            }
            $cust_where = implode(' AND ', $cwc);
        } else {
            $cust_where = "(name LIKE $like_e OR phone LIKE $like_e)";
        }
        $customers = $this->db->query(
            "SELECT id, name, phone, city
             FROM customers
             WHERE status = 1
               AND $cust_where
             ORDER BY name LIMIT 5"
        )->result();

        echo json_encode([
            'products'  => $products,
            'customers' => $customers,
        ]);
    }
}
