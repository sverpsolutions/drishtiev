<?php
// ============================================================
// FILE: application/models/Product_model.php  (UPDATED)
// Changes:
//   - get_all()         joins hsn_master for brand/category + hsn rates
//   - get_for_billing() returns igst/cgst/sgst from hsn_master
//   - low_stock uses per-product threshold
//   - count_low_stock() uses product's own low_stock_threshold
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends CI_Model {

    private $table = 'products';

    // $limit / $search are optional so every existing caller (billing, GRN, purchases,
    // estimates, etc.) keeps loading the full catalog unless it explicitly opts in —
    // only call sites that hit a large catalog (e.g. guptabros' 56k+ products, which
    // OOM'd the unbounded query) should pass these.
    public function get_all($location_id = null, $limit = null, $search = null) {
        // If a specific location is given, show that location's stock instead of the global total
        if ($location_id) {
            $loc_id = (int)$location_id;
            $this->db->select('products.*,
                brands.name     AS brand_name,
                categories.name AS category_name,
                hsn_master.hsn_code   AS hsn_code_from_master,
                hsn_master.igst_percent,
                hsn_master.cgst_percent,
                hsn_master.sgst_percent,
                GREATEST(COALESCE(pls.stock_qty, 0), 0) AS stock_qty,
                pcs_barcode.barcode AS pcs_barcode');
            $this->db->join('product_location_stock pls',
                "pls.product_id = products.id AND pls.location_id = {$loc_id}", 'left');
        } else {
            // products.* then a capped stock_qty — the later column wins: display stock never below 0
            $this->db->select('products.*, GREATEST(products.stock_qty, 0) AS stock_qty,
                brands.name    AS brand_name,
                categories.name AS category_name,
                hsn_master.hsn_code   AS hsn_code_from_master,
                hsn_master.igst_percent,
                hsn_master.cgst_percent,
                hsn_master.sgst_percent,
                pcs_barcode.barcode AS pcs_barcode');
        }
        // pcs_barcode joined (not a per-row correlated subquery) — GROUP BY products.id
        // collapses the rare case of duplicate PCS barcode rows for the same product.
        $this->db
            ->from($this->table)
            ->join('brands',     'brands.id     = products.brand_id',    'left')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->join('hsn_master', 'hsn_master.id = products.hsn_id',      'left')
            ->join('product_barcodes pcs_barcode', "pcs_barcode.product_id = products.id AND pcs_barcode.unit_type = 'PCS'", 'left');

        $this->load->helper('product_search');
        $ws = product_words_sql($search, ['products.name', 'products.item_code', 'products.model_name', 'products.model_number', 'products.colour', 'brands.name', 'categories.name', 'pcs_barcode.barcode']);
        if ($ws) {
            $this->db->where($ws, NULL, FALSE);
        } elseif ($search !== null && $search !== '') {
            $like = $this->db->escape_like_str($search);
            $this->db->group_start()
                ->like('products.name', $like, 'both')
                ->or_like('products.item_code', $like, 'both')
                ->or_like('products.model_name', $like, 'both')
                ->or_like('products.model_number', $like, 'both')
                ->or_like('products.colour', $like, 'both')
                ->or_like('brands.name', $like, 'both')
                ->or_like('categories.name', $like, 'both')
                ->or_like('pcs_barcode.barcode', $like, 'both')
                ->group_end();
        }

        $this->db->group_by('products.id')->order_by('products.name', 'ASC');
        if ($limit) { $this->db->limit((int)$limit); }

        return $this->db->get()->result();
    }

    // Alias used by Schemes controller (create/simulate pages)
    public function get_all_for_billing() {
        return $this->get_all();
    }

    // Fetch specific products by id, same shape as get_all() rows — used to backfill
    // products that fall outside a capped get_all($limit) result (e.g. an existing
    // invoice's line items when editing, so their dropdown isn't left blank).
    public function get_by_ids(array $ids) {
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) return [];
        return $this->db->select('products.*,
                brands.name     AS brand_name,
                categories.name AS category_name,
                hsn_master.hsn_code   AS hsn_code_from_master,
                hsn_master.igst_percent,
                hsn_master.cgst_percent,
                hsn_master.sgst_percent,
                pcs_barcode.barcode AS pcs_barcode')
            ->from($this->table)
            ->join('brands',     'brands.id     = products.brand_id',    'left')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->join('hsn_master', 'hsn_master.id = products.hsn_id',      'left')
            ->join('product_barcodes pcs_barcode', "pcs_barcode.product_id = products.id AND pcs_barcode.unit_type = 'PCS'", 'left')
            ->where_in('products.id', $ids)
            ->group_by('products.id')
            ->get()->result();
    }

    public function get_by_id($id) {
        return $this->db
            ->select('products.*,
                brands.name     AS brand_name,
                categories.name AS category_name,
                hsn_master.hsn_code    AS hsn_code_display,
                hsn_master.igst_percent,
                hsn_master.cgst_percent,
                hsn_master.sgst_percent')
            ->from($this->table)
            ->join('brands',     'brands.id     = products.brand_id',    'left')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->join('hsn_master', 'hsn_master.id = products.hsn_id',      'left')
            ->where('products.id', $id)
            ->get()->row();
    }

    // Returns product data for the billing AJAX call.
    // When location_id is given, stock_qty reflects that location's stock only.
    public function get_for_billing($id, $location_id = null) {
        $common_fields = 'products.id, products.name, products.selling_price, products.mrp,
                      products.gst_percent, products.hsn_id, products.product_type,
                      products.model_name, products.model_number, products.colour,
                      products.battery_type, products.battery_capacity, products.charger_details, products.motor_details,
                      products.warranty_applicable, products.warranty_days, products.warranty_policy,
                      products.maintain_serial, products.serial_entry_method,
                      products.unit, products.item_code, products.carton_size, products.description, products.image1, products.no_discount,
                      COALESCE(hsn_master.hsn_code, products.hsn_code) AS hsn_code,
                      COALESCE(hsn_master.igst_percent, products.gst_percent) AS igst_percent,
                      COALESCE(hsn_master.cgst_percent, products.gst_percent/2) AS cgst_percent,
                      COALESCE(hsn_master.sgst_percent, products.gst_percent/2) AS sgst_percent';

        if ($location_id) {
            $loc_id = (int)$location_id;
            $this->db->select($common_fields . ', GREATEST(COALESCE(pls.stock_qty, 0), 0) AS stock_qty');
            $this->db->join('product_location_stock pls',
                "pls.product_id = products.id AND pls.location_id = {$loc_id}", 'left');
        } else {
            $this->db->select($common_fields . ', GREATEST(products.stock_qty, 0) AS stock_qty');
        }
        return $this->db
            ->from($this->table)
            ->join('hsn_master', 'hsn_master.id = products.hsn_id', 'left')
            ->where('products.id', $id)
            ->get()->row();
    }

    // Products that are at or below their own low_stock_threshold
    public function get_low_stock() {
        return $this->db
            ->where('stock_qty >', 0)
            ->where('stock_qty <= low_stock_threshold', null, false)
            ->order_by('stock_qty', 'ASC')
            ->get($this->table)
            ->result();
    }

    public function count_low_stock() {
        return $this->db
            ->where('stock_qty > 0', null, false)
            ->where('stock_qty <= low_stock_threshold', null, false)
            ->count_all_results($this->table);
    }

    public function create($data) {
        $data = $this->_sync_gst_from_hsn($data);
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $data = $this->_sync_gst_from_hsn($data);
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    // Increase stock (on purchase)
    public function increase_stock($id, $qty) {
        return $this->db->set('stock_qty', 'stock_qty + ' . (float)$qty, FALSE)
            ->where('id', $id)
            ->update($this->table);
    }

    // Decrease stock (on sale) — allows negative stock
    public function decrease_stock($id, $qty) {
        $product = $this->get_by_id($id);
        if (!$product) {
            return FALSE;
        }
        return $this->db->set('stock_qty', 'stock_qty - ' . (float)$qty, FALSE)
            ->where('id', $id)
            ->update($this->table);
    }

    public function delete($id) {
        return $this->db->where('id', $id)->delete($this->table);
    }

    // Search for autocomplete in billing
    public function search($keyword) {
        $words = preg_split('/\s+/', $keyword, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) > 1) {
            foreach ($words as $w) {
                $this->db->like('name', $w);
            }
        } else {
            $this->db->group_start()->like('name', $keyword)->or_like('item_code', $keyword)->group_end();
        }
        return $this->db->where('stock_qty >', 0)->limit(10)->get($this->table)->result();
    }

    // Advanced wholesale scan logic — barcode or item_code
    public function get_by_barcode($barcode) {
        $barcode = trim((string)$barcode);
        if ($barcode === '') return null;

        // 1. Check garment variants first
        $gv = $this->db->where('barcode', $barcode)->where('status', 1)->get('garment_variants')->row();
        if ($gv) {
            $product = $this->get_for_billing($gv->product_id);
            if ($product) {
                $product->variant_id      = (int)$gv->id;
                $product->variant_sku     = $gv->variant_sku;
                $product->size_name       = $gv->size_name;
                $product->colour_name     = $gv->colour_name;
                $product->name            = $product->name . ' [' . $gv->colour_name . ' / ' . $gv->size_name . ']';
                $product->mrp             = (float)$gv->mrp;
                $product->selling_price   = (float)$gv->selling_price;
                $product->stock_qty       = (float)$gv->stock_qty;
                $product->scan_unit       = 'PCS';
                $product->scan_multiplier = 1;
                $product->is_garment      = 1;
                return $product;
            }
        }

        $product = $this->_get_for_billing_by_field('item_code', $barcode);
        if ($product) {
            $product->scan_unit = 'PCS';
            $product->scan_multiplier = 1;
            return $product;
        }

        $bc = $this->db->where('barcode', $barcode)->get('product_barcodes')->row();
        if ($bc) {
            $product = $this->get_for_billing($bc->product_id);
            if ($product) {
                if (!empty($bc->variant_id)) {
                    $gv = $this->db->where('id', $bc->variant_id)->get('garment_variants')->row();
                    if ($gv) {
                        $product->variant_id    = (int)$gv->id;
                        $product->variant_sku   = $gv->variant_sku;
                        $product->size_name     = $gv->size_name;
                        $product->colour_name   = $gv->colour_name;
                        $product->name          = $product->name . ' [' . $gv->colour_name . ' / ' . $gv->size_name . ']';
                        $product->mrp           = (float)$gv->mrp;
                        $product->selling_price = (float)$gv->selling_price;
                        $product->stock_qty     = (float)$gv->stock_qty;
                        $product->is_garment    = 1;
                    }
                }
                $product->scan_unit       = $bc->unit_type;
                $product->scan_multiplier = $bc->qty_multiplier;
                return $product;
            }
        }
        return null;
    }

    private function _get_for_billing_by_field($field, $val) {
        return $this->db
            ->select('products.id, products.name, products.selling_price, products.mrp,
                      products.gst_percent, products.hsn_id, GREATEST(products.stock_qty, 0) AS stock_qty,
                      products.unit, products.item_code, products.carton_size, products.description, products.image1, products.no_discount,
                      COALESCE(hsn_master.hsn_code, products.hsn_code) AS hsn_code,
                      COALESCE(hsn_master.igst_percent, products.gst_percent) AS igst_percent,
                      COALESCE(hsn_master.cgst_percent, products.gst_percent/2) AS cgst_percent,
                      COALESCE(hsn_master.sgst_percent, products.gst_percent/2) AS sgst_percent')
            ->from($this->table)
            ->join('hsn_master', 'hsn_master.id = products.hsn_id', 'left')
            ->where('products.' . $field, $val)
            ->get()->row();
    }

    public function get_next_item_code() {
        $query = $this->db->query(
            "SELECT MAX(CAST(item_code AS UNSIGNED)) as max_code
             FROM {$this->table}
             WHERE item_code REGEXP '^[0-9]+$'"
        );
        $row = $query->row();
        $max = $row ? (int)$row->max_code : 0;
        return $max >= 100001 ? (string)($max + 1) : '100001';
    }

    // When hsn_id is set, sync gst_percent from HSN master's IGST rate
    private function _sync_gst_from_hsn($data) {
        if (!empty($data['hsn_id'])) {
            $this->load->model('Hsn_model');
            $rates = $this->Hsn_model->get_rates($data['hsn_id']);
            if ($rates) {
                $data['gst_percent'] = $rates->igst_percent;
                // Also store the hsn_code string for legacy/display
                $data['hsn_code'] = $rates->hsn_code;
            }
        }
        return $data;
    }

    public function get_all_active($limit = null)
    {
        $this->db
            ->select('products.id, products.name, products.item_code, products.unit, products.cost_price, products.selling_price, GREATEST(products.stock_qty, 0) AS stock_qty, products.hsn_code, products.low_stock_threshold AS min_stock, pcs_barcode.barcode AS pcs_barcode')
            ->from($this->table)
            ->join('product_barcodes pcs_barcode', "pcs_barcode.product_id = products.id AND pcs_barcode.unit_type = 'PCS'", 'left')
            ->where('products.status', 1)
            ->group_by('products.id')
            ->order_by('products.name', 'ASC');
        if ($limit) $this->db->limit((int)$limit);
        return $this->db->get()->result();
    }
}
