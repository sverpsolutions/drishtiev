<?php
// FILE: application/models/Store_model.php
// Read-only-ish catalog/order helpers for the new Store storefront. Queries the
// SAME tables the old portal uses (products, categories, order_requests, etc.)
// but is its own model — Portal.php inlines its own queries per-endpoint rather
// than sharing a model, so this follows that same convention instead of trying
// to retrofit a shared "portal" model that doesn't exist.
defined('BASEPATH') OR exit('No direct script access allowed');

class Store_model extends CI_Model {

    private $product_cols = 'id, name, item_code, mrp, selling_price, unit, stock_qty,
        category, category_id, brand, brand_id, gst_percent, image1, is_veg, no_discount';

    public function get_categories() {
        return $this->db->where('status', 1)
            ->order_by('display_order', 'ASC')->order_by('name', 'ASC')
            ->get('categories')->result();
    }

    public function get_category($id) {
        return $this->db->where('id', $id)->where('status', 1)->get('categories')->row();
    }

    public function get_home_products($limit = 24) {
        return $this->db->select($this->product_cols)
            ->where('status', 1)->where('stock_qty >', 0)
            ->order_by('name', 'ASC')->limit($limit)
            ->get('products')->result();
    }

    public function search_products($q = '', $category_id = 0, $limit = 60) {
        $this->db->select($this->product_cols)->where('status', 1);
        $this->load->helper('product_search');
        if ($ws = product_words_sql($q, ['name', 'item_code', 'brand'])) {
            $this->db->where($ws, NULL, FALSE);
        } elseif ($q !== '') {
            $this->db->group_start()->like('name', $q)->or_like('item_code', $q)->or_like('brand', $q)->group_end();
        }
        if ($category_id) $this->db->where('category_id', $category_id);
        return $this->db->order_by('name', 'ASC')->limit($limit)->get('products')->result();
    }

    public function get_product_detail($id) {
        return $this->db
            ->select($this->product_cols . ', image2, image3, description, ingredients, expiry_days, hsn_code')
            ->where('id', $id)->where('status', 1)
            ->get('products')->row();
    }

    public function get_product_min($id) {
        return $this->db->select($this->product_cols)->where('id', $id)->where('status', 1)->get('products')->row();
    }

    public function get_products_by_ids(array $ids) {
        if (empty($ids)) return [];
        return $this->db->select($this->product_cols)->where_in('id', $ids)->where('status', 1)->get('products')->result();
    }

    public function get_related_products($category_id, $exclude_id, $limit = 8) {
        if (!$category_id) return [];
        return $this->db->select($this->product_cols)
            ->where('category_id', $category_id)->where('id !=', $exclude_id)
            ->where('status', 1)->where('stock_qty >', 0)
            ->limit($limit)->get('products')->result();
    }

    public function get_past_products($customer_id, $limit = 8) {
        return $this->db->select('products.id, products.name, products.item_code, products.mrp,
            products.selling_price, products.unit, GREATEST(products.stock_qty, 0) AS stock_qty, products.category,
            products.category_id, products.brand, products.brand_id, products.gst_percent,
            products.image1, products.is_veg, products.no_discount')
            ->from('invoice_items ii')
            ->join('invoices i', 'i.id = ii.invoice_id')
            ->join('products', 'products.id = ii.product_id')
            ->where('i.customer_id', $customer_id)
            ->where('i.status !=', 'cancelled')
            ->where('products.status', 1)
            ->group_by('products.id')
            ->limit($limit)->get()->result();
    }

    public function has_purchased($customer_id, $product_id) {
        $row = $this->db->select('ii.id')
            ->from('invoice_items ii')
            ->join('invoices i', 'i.id = ii.invoice_id')
            ->where('i.customer_id', $customer_id)
            ->where('ii.product_id', $product_id)
            ->where('i.status !=', 'cancelled')
            ->limit(1)->get()->row();
        return !empty($row);
    }

    public function get_reviews($product_id) {
        return $this->db->where('product_id', $product_id)->where('status', 'approved')
            ->order_by('created_at', 'DESC')->get('product_reviews')->result();
    }
}
