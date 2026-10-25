<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Garment_model
 * Comprehensive Model for Clothing & Garment Inventory, Variants, Size Sets, Colours & Reports
 */
class Garment_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    // =========================================================================
    // 1. SIZE SETS & SIZES
    // =========================================================================

    public function get_size_sets($active_only = true) {
        if ($active_only) $this->db->where('status', 1);
        return $this->db->order_by('sort_order', 'ASC')->get('garment_size_sets')->result();
    }

    public function get_size_set_by_id($id) {
        return $this->db->where('id', (int)$id)->get('garment_size_sets')->row();
    }

    public function get_sizes_by_set($size_set_id) {
        return $this->db->where('size_set_id', (int)$size_set_id)
                        ->where('status', 1)
                        ->order_by('sort_order', 'ASC')
                        ->get('garment_sizes')->result();
    }

    public function get_all_sizes($active_only = true) {
        $this->db->select('garment_sizes.*, garment_size_sets.name as size_set_name')
                 ->from('garment_sizes')
                 ->join('garment_size_sets', 'garment_size_sets.id = garment_sizes.size_set_id', 'left');
        if ($active_only) $this->db->where('garment_sizes.status', 1);
        return $this->db->order_by('garment_size_sets.sort_order', 'ASC')
                        ->order_by('garment_sizes.sort_order', 'ASC')
                        ->get()->result();
    }

    public function get_sizes($active_only = true) {
        return $this->get_all_sizes($active_only);
    }

    public function get_distinct_sizes() {
        return $this->db->select('name, code, sort_order')
                        ->group_by('name')
                        ->order_by('sort_order', 'ASC')
                        ->order_by('name', 'ASC')
                        ->get('garment_sizes')->result();
    }

    public function save_size_set($data) {
        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            unset($data['id']);
            $this->db->where('id', $id)->update('garment_size_sets', $data);
            return $id;
        }
        $this->db->insert('garment_size_sets', $data);
        return $this->db->insert_id();
    }

    public function save_size($data) {
        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            unset($data['id']);
            $this->db->where('id', $id)->update('garment_sizes', $data);
            return $id;
        }
        $this->db->insert('garment_sizes', $data);
        return $this->db->insert_id();
    }

    // =========================================================================
    // 2. COLOURS MASTER
    // =========================================================================

    public function get_colours($active_only = true) {
        if ($active_only) $this->db->where('status', 1);
        return $this->db->order_by('sort_order', 'ASC')->order_by('name', 'ASC')->get('garment_colours')->result();
    }

    public function get_colour_by_id($id) {
        return $this->db->where('id', (int)$id)->get('garment_colours')->row();
    }

    public function save_colour($data) {
        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            unset($data['id']);
            $this->db->where('id', $id)->update('garment_colours', $data);
            return $id;
        }
        $this->db->insert('garment_colours', $data);
        return $this->db->insert_id();
    }

    // =========================================================================
    // 3. GARMENT ATTRIBUTES & CATEGORIES
    // =========================================================================

    public function get_garment_categories() {
        if ($this->db->table_exists('garment_attributes')) {
            $rows = $this->db->where('attribute_type', 'category')
                             ->where('status', 1)
                             ->order_by('sort_order', 'ASC')
                             ->order_by('attribute_value', 'ASC')
                             ->get('garment_attributes')->result();
            if (!empty($rows)) {
                return array_column($rows, 'attribute_value');
            }
        }
        return [
            'Saree', 'Kurti', 'Kurta Set', 'Palazzo', 'Salwar Suit', 'Lehenga', 'Dupatta',
            'Shirt', 'T-Shirt', 'Jeans', 'Trousers', 'Formal Pant', 'Dress', 'Gown',
            'Leggings', 'Jeggings', 'Shorts', 'Skirt', 'Top', 'Tunics', 'Blazer',
            'Jacket', 'Sweater', 'Cardigan', 'Track Pant', 'Nightwear', 'Innerwear',
            'Kids Wear', 'Ethnic Wear', 'Western Wear', 'Activewear', 'Accessory'
        ];
    }

    public function get_fabric_types() {
        if ($this->db->table_exists('garment_attributes')) {
            $rows = $this->db->where('attribute_type', 'fabric')
                             ->where('status', 1)
                             ->order_by('sort_order', 'ASC')
                             ->order_by('attribute_value', 'ASC')
                             ->get('garment_attributes')->result();
            if (!empty($rows)) {
                return array_column($rows, 'attribute_value');
            }
        }
        return [
            '100% Cotton', 'Cotton Blend', 'Rayon', 'Pure Silk', 'Art Silk', 'Chiffon',
            'Georgette', 'Denim', 'Linen', 'Polyester', 'Crepe', 'Viscose', 'Woolen',
            'Satin', 'Velvet', 'Organza', 'Khadi', 'Lycra / Spandex'
        ];
    }

    public function get_fit_types() {
        if ($this->db->table_exists('garment_attributes')) {
            $rows = $this->db->where('attribute_type', 'fit')
                             ->where('status', 1)
                             ->order_by('sort_order', 'ASC')
                             ->order_by('attribute_value', 'ASC')
                             ->get('garment_attributes')->result();
            if (!empty($rows)) {
                return array_column($rows, 'attribute_value');
            }
        }
        return ['Regular Fit', 'Slim Fit', 'Skinny Fit', 'Relaxed Fit', 'Loose Fit', 'Tailored Fit', 'Oversized'];
    }

    public function get_sleeve_types() {
        if ($this->db->table_exists('garment_attributes')) {
            $rows = $this->db->where('attribute_type', 'sleeve')
                             ->where('status', 1)
                             ->order_by('sort_order', 'ASC')
                             ->order_by('attribute_value', 'ASC')
                             ->get('garment_attributes')->result();
            if (!empty($rows)) {
                return array_column($rows, 'attribute_value');
            }
        }
        return ['Full Sleeve', 'Half Sleeve', 'Short Sleeve', 'Sleeveless', '3/4th Sleeve', 'Cap Sleeve', 'Bell Sleeve'];
    }

    public function get_neck_types() {
        if ($this->db->table_exists('garment_attributes')) {
            $rows = $this->db->where('attribute_type', 'neck')
                             ->where('status', 1)
                             ->order_by('sort_order', 'ASC')
                             ->order_by('attribute_value', 'ASC')
                             ->get('garment_attributes')->result();
            if (!empty($rows)) {
                return array_column($rows, 'attribute_value');
            }
        }
        return ['Round Neck', 'V-Neck', 'Collar Neck', 'Mandarin / Chinese Collar', 'Boat Neck', 'Square Neck', 'Hooded', 'Polo Collar', 'Sweetheart Neck'];
    }

    public function get_attributes_by_type($type) {
        if (!$this->db->table_exists('garment_attributes')) return [];
        return $this->db->where('attribute_type', $type)
                        ->where('status', 1)
                        ->order_by('sort_order', 'ASC')
                        ->order_by('attribute_value', 'ASC')
                        ->get('garment_attributes')->result();
    }

    public function add_attribute($type, $value) {
        $value = trim($value);
        if (empty($value) || empty($type)) return false;
        if (!$this->db->table_exists('garment_attributes')) return false;

        $existing = $this->db->where('attribute_type', $type)
                             ->where('attribute_value', $value)
                             ->get('garment_attributes')->row();
        if ($existing) {
            if ($existing->status == 0) {
                $this->db->where('id', $existing->id)->update('garment_attributes', ['status' => 1]);
            }
            return $existing->id;
        }

        $max_row = $this->db->select_max('sort_order')->where('attribute_type', $type)->get('garment_attributes')->row();
        $max_order = $max_row ? (int)$max_row->sort_order : 0;

        $this->db->insert('garment_attributes', [
            'attribute_type'  => $type,
            'attribute_value' => $value,
            'sort_order'      => $max_order + 1,
            'status'          => 1
        ]);
        return $this->db->insert_id();
    }

    public function delete_attribute($id) {
        if (!$this->db->table_exists('garment_attributes')) return false;
        return $this->db->where('id', (int)$id)->delete('garment_attributes');
    }

    // =========================================================================
    // 4. GARMENT VARIANTS MANAGEMENT
    // =========================================================================

    public function get_variants_by_product($product_id, $location_id = null) {
        $this->db->select('gv.*, 
                           COALESCE(pls.stock_qty, 0) as loc_stock_qty,
                           gc.hex_code as colour_hex,
                           gs.measurement as size_measurement,
                           COALESCE(gs.sort_order, 999) as size_sort_order')
                 ->from('garment_variants gv')
                 ->join('garment_colours gc', 'gc.id = gv.colour_id', 'left')
                 ->join('garment_sizes gs', 'gs.id = gv.size_id', 'left');

        if ($location_id) {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id AND pls.location_id = ' . (int)$location_id, 'left');
        } else {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id', 'left');
        }

        $this->db->where('gv.product_id', (int)$product_id)
                 ->group_by('gv.id')
                 ->order_by('size_sort_order', 'ASC')
                 ->order_by('gv.colour_name', 'ASC')
                 ->order_by('gv.id', 'ASC');

        return $this->db->get()->result();
    }

    public function get_variant_by_id($id, $location_id = null) {
        $this->db->select('gv.*, p.name as product_name, p.item_code as parent_item_code, 
                           p.hsn_code, p.gst_percent, p.unit, p.brand, p.category,
                           p.image1, p.design_no, p.style_code, p.product_type,
                           COALESCE(pls.stock_qty, 0) as loc_stock_qty')
                 ->from('garment_variants gv')
                 ->join('products p', 'p.id = gv.product_id')
                 ->join('garment_variant_location_stock pls', 
                        'pls.variant_id = gv.id AND pls.location_id = ' . (int)($location_id ?: 1), 'left')
                 ->where('gv.id', (int)$id);
        return $this->db->get()->row();
    }

    /**
     * Exact variant lookup by barcode (or, with $by_sku, by variant SKU).
     * Returns the variant row, null if none, or (object)['ambiguous'=>true] when more
     * than one active variant shares the value — never picks one arbitrarily.
     */
    public function get_variant_by_barcode($barcode, $location_id = null, $by_sku = false) {
        $barcode = trim((string)$barcode);
        if ($barcode === '') return null;

        $this->db->select('gv.*, p.name as product_name, p.item_code as parent_item_code, 
                           p.hsn_code, p.gst_percent, p.unit, p.brand, p.category,
                           p.image1, p.design_no, p.style_code, p.product_type,
                           COALESCE(hsn_master.igst_percent, p.gst_percent) as igst_percent,
                           COALESCE(hsn_master.cgst_percent, p.gst_percent/2) as cgst_percent,
                           COALESCE(hsn_master.sgst_percent, p.gst_percent/2) as sgst_percent,
                           COALESCE(pls.stock_qty, 0) as loc_stock_qty')
                 ->from('garment_variants gv')
                 ->join('products p', 'p.id = gv.product_id')
                 ->join('hsn_master', 'hsn_master.id = p.hsn_id', 'left')
                 ->join('garment_variant_location_stock pls', 
                        'pls.variant_id = gv.id AND pls.location_id = ' . (int)($location_id ?: 1), 'left')
                 ->where($by_sku ? 'gv.variant_sku' : 'gv.barcode', $barcode)
                 ->where('gv.status', 1)
                 ->limit(2);

        $rows = $this->db->get()->result();
        if (count($rows) > 1) return (object)['ambiguous' => true];
        return $rows ? $rows[0] : null;
    }

    public function generate_barcode($style_code, $colour_name, $size_name) {
        // Generates an EAN-13 compatible internal barcode or unique SKU
        // Prefix 890 (India GS1 country code) + 9 digits + 1 checksum digit
        $clean_code = preg_replace('/[^0-9]/', '', crc32($style_code . '_' . $colour_name . '_' . $size_name));
        $seed = str_pad(substr($clean_code, 0, 9), 9, '0', STR_PAD_LEFT);
        $base = '890' . $seed; // 12 digits
        
        // Calculate EAN-13 checksum digit
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$base[$i];
            $sum += ($i % 2 === 0) ? $digit : ($digit * 3);
        }
        $check = (10 - ($sum % 10)) % 10;
        $ean = $base . $check;

        // Ensure uniqueness in database
        $exists = $this->db->where('barcode', $ean)->get('garment_variants')->row();
        if ($exists) {
            // Append random salt if collision
            $rnd = mt_rand(1000, 9999);
            return 'GAR-' . strtoupper(substr($style_code, 0, 6)) . '-' . $rnd;
        }
        return $ean;
    }

    public function generate_sku($style_code, $colour_name, $size_name) {
        $c_abbr = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $colour_name), 0, 3));
        $s_clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $size_name));
        $st_clean = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $style_code ?: 'STYLE'));
        return $st_clean . '-' . $c_abbr . '-' . $s_clean;
    }

    /**
     * Generate or update full variant matrix for a garment style
     */
    public function generate_variant_matrix($product_id, array $sizes, array $colours, array $defaults = []) {
        $product = $this->db->where('id', (int)$product_id)->get('products')->row();
        if (!$product) return false;

        $style_code = !empty($product->style_code) ? $product->style_code : (!empty($product->design_no) ? $product->design_no : $product->item_code);
        $mrp        = isset($defaults['mrp']) ? (float)$defaults['mrp'] : (float)$product->mrp;
        $sp         = isset($defaults['selling_price']) ? (float)$defaults['selling_price'] : (float)$product->selling_price;
        $pp         = isset($defaults['purchase_price']) ? (float)$defaults['purchase_price'] : (float)$product->purchase_price;
        $cp         = isset($defaults['cost_price']) ? (float)$defaults['cost_price'] : (float)$product->cost_price;

        $created = 0;
        foreach ($colours as $col) {
            $col_name = is_array($col) ? $col['name'] : (is_object($col) ? $col->name : trim($col));
            $col_id   = is_array($col) ? ($col['id'] ?? null) : (is_object($col) ? ($col->id ?? null) : null);
            if (!$col_id) {
                $c_row = $this->db->where('name', $col_name)->get('garment_colours')->row();
                $col_id = $c_row ? $c_row->id : null;
            }

            foreach ($sizes as $sz) {
                $sz_name = is_array($sz) ? $sz['name'] : (is_object($sz) ? $sz->name : trim($sz));
                $sz_id   = is_array($sz) ? ($sz['id'] ?? null) : (is_object($sz) ? ($sz->id ?? null) : null);
                if (!$sz_id) {
                    $s_row = $this->db->where('name', $sz_name)->get('garment_sizes')->row();
                    $sz_id = $s_row ? $s_row->id : null;
                }

                // Check if already exists
                $existing = $this->db->where('product_id', $product->id)
                                     ->where('size_name', $sz_name)
                                     ->where('colour_name', $col_name)
                                     ->get('garment_variants')->row();

                if (!$existing) {
                    $sku     = $this->generate_sku($style_code, $col_name, $sz_name);
                    $barcode = $this->generate_barcode($style_code, $col_name, $sz_name);

                    $var_data = [
                        'product_id'     => $product->id,
                        'variant_sku'    => $sku,
                        'size_id'        => $sz_id,
                        'size_name'      => $sz_name,
                        'colour_id'      => $col_id,
                        'colour_name'    => $col_name,
                        'barcode'        => $barcode,
                        'mfr_article_no' => $product->mfr_article_no ?: null,
                        'purchase_price' => $pp,
                        'cost_price'     => $cp,
                        'mrp'            => $mrp,
                        'selling_price'  => $sp,
                        'stock_qty'      => 0,
                        'status'         => 1,
                        'created_at'     => date('Y-m-d H:i:s'),
                    ];

                    $this->db->insert('garment_variants', $var_data);
                    $var_id = $this->db->insert_id();

                    // Register in product_barcodes table as well
                    $this->db->insert('product_barcodes', [
                        'product_id'     => $product->id,
                        'variant_id'     => $var_id,
                        'barcode'        => $barcode,
                        'unit_type'      => 'PCS',
                        'qty_multiplier' => 1
                    ]);

                    $created++;
                }
            }
        }
        return $created;
    }

    // =========================================================================
    // 5. VARIANT STOCK & INVENTORY OPERATIONS
    // =========================================================================

    public function get_variant_stock($variant_id, $location_id) {
        $row = $this->db->where('variant_id', (int)$variant_id)
                        ->where('location_id', (int)$location_id)
                        ->get('garment_variant_location_stock')->row();
        return $row ? (float)$row->stock_qty : 0.0;
    }

    public function set_variant_stock($variant_id, $product_id, $location_id, $qty) {
        $existing = $this->db->where('variant_id', (int)$variant_id)
                             ->where('location_id', (int)$location_id)
                             ->get('garment_variant_location_stock')->row();
        if ($existing) {
            $this->db->where('variant_id', (int)$variant_id)
                     ->where('location_id', (int)$location_id)
                     ->update('garment_variant_location_stock', ['stock_qty' => $qty]);
        } else {
            $this->db->insert('garment_variant_location_stock', [
                'variant_id'  => (int)$variant_id,
                'product_id'  => (int)$product_id,
                'location_id' => (int)$location_id,
                'stock_qty'   => $qty,
            ]);
        }
        $this->_sync_variant_global_stock($variant_id, $product_id);
    }

    public function increase_variant_stock($variant_id, $product_id, $location_id, $qty) {
        $cur = $this->get_variant_stock($variant_id, $location_id);
        $new = $cur + (float)$qty;
        $this->set_variant_stock($variant_id, $product_id, $location_id, $new);
        return $new;
    }

    public function decrease_variant_stock($variant_id, $product_id, $location_id, $qty) {
        $cur = $this->get_variant_stock($variant_id, $location_id);
        $new = $cur - (float)$qty;
        $this->set_variant_stock($variant_id, $product_id, $location_id, $new);
        return $new;
    }

    private function _sync_variant_global_stock($variant_id, $product_id) {
        // 1. Update garment_variants.stock_qty (sum across outlets)
        $v_row = $this->db->select('SUM(GREATEST(stock_qty, 0)) as total')
                          ->where('variant_id', (int)$variant_id)
                          ->get('garment_variant_location_stock')->row();
        $v_total = $v_row ? (float)$v_row->total : 0.0;
        $this->db->where('id', (int)$variant_id)->update('garment_variants', ['stock_qty' => $v_total]);

        // 2. Sync parent product location stock
        $loc_rows = $this->db->select('location_id, SUM(stock_qty) as loc_sum')
                             ->where('product_id', (int)$product_id)
                             ->group_by('location_id')
                             ->get('garment_variant_location_stock')->result();

        $this->load->model('Location_model');
        foreach ($loc_rows as $lr) {
            $this->Location_model->set_stock($product_id, $lr->location_id, $lr->loc_sum);
        }

        // 3. Update parent products.stock_qty
        $p_row = $this->db->select('SUM(GREATEST(stock_qty, 0)) as parent_total')
                          ->where('product_id', (int)$product_id)
                          ->get('garment_variants')->row();
        $p_total = $p_row ? (float)$p_row->parent_total : 0.0;
        $this->db->where('id', (int)$product_id)->update('products', ['stock_qty' => $p_total]);
    }

    // =========================================================================
    // 6. CLOTHING & GARMENT REPORTS ENGINE
    // =========================================================================

    /**
     * Size-wise Sales & Stock Matrix (Parent Style x Sizes)
     */
    public function get_size_sales_matrix($filters = []) {
        $from        = $filters['from'] ?? date('Y-m-01');
        $to          = $filters['to']   ?? date('Y-m-d');
        $location_id = !empty($filters['location_id']) ? (int)$filters['location_id'] : null;
        $brand       = !empty($filters['brand']) ? $filters['brand'] : null;
        $category    = !empty($filters['category']) ? $filters['category'] : null;

        // Fetch all distinct sizes present in garments
        $sizes_res = $this->db->select('DISTINCT size_name')
                              ->from('garment_variants')
                              ->order_by('size_name', 'ASC')
                              ->get()->result_array();
        $size_cols = array_column($sizes_res, 'size_name');

        // Fetch garment styles
        $this->db->select('p.id as product_id, p.name as product_name, p.item_code, p.design_no, 
                           p.style_code, p.category, p.brand, p.mrp, p.selling_price')
                 ->from('products p')
                 ->where('p.is_garment', 1)
                 ->where('p.status', 1);

        if ($brand) $this->db->where('p.brand', $brand);
        if ($category) $this->db->where('p.category', $category);

        $styles = $this->db->order_by('p.name', 'ASC')->get()->result();

        // Build sales aggregated by product_id and size_name
        $this->db->select('ii.product_id, ii.size_name, SUM(ii.qty) as sold_qty, SUM(ii.total) as sold_amt')
                 ->from('invoice_items ii')
                 ->join('invoices i', 'i.id = ii.invoice_id')
                 ->where('i.status !=', 'cancelled')
                 ->where('i.invoice_date >=', $from)
                 ->where('i.invoice_date <=', $to)
                 ->where('ii.variant_id IS NOT NULL', null, false);

        if ($location_id) $this->db->where('i.location_id', $location_id);
        $sales_rows = $this->db->group_by('ii.product_id, ii.size_name')->get()->result();

        $sales_map = [];
        foreach ($sales_rows as $sr) {
            $sales_map[$sr->product_id][$sr->size_name] = [
                'qty' => (float)$sr->sold_qty,
                'amt' => (float)$sr->sold_amt
            ];
        }

        // Build current stock aggregated by product_id and size_name
        $this->db->select('gv.product_id, gv.size_name, SUM(COALESCE(pls.stock_qty, gv.stock_qty)) as stock_qty')
                 ->from('garment_variants gv');
        if ($location_id) {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id AND pls.location_id = ' . $location_id, 'left');
        } else {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id', 'left');
        }
        $stock_rows = $this->db->group_by('gv.product_id, gv.size_name')->get()->result();

        $stock_map = [];
        foreach ($stock_rows as $stk) {
            $stock_map[$stk->product_id][$stk->size_name] = (float)$stk->stock_qty;
        }

        return [
            'size_columns' => $size_cols,
            'styles'       => $styles,
            'sales_map'    => $sales_map,
            'stock_map'    => $stock_map
        ];
    }

    /**
     * Colour-wise Sales & Stock Matrix
     */
    public function get_colour_sales_matrix($filters = []) {
        $from        = $filters['from'] ?? date('Y-m-01');
        $to          = $filters['to']   ?? date('Y-m-d');
        $location_id = !empty($filters['location_id']) ? (int)$filters['location_id'] : null;

        $this->db->select('gv.colour_name, 
                           COUNT(DISTINCT gv.id) as total_variants,
                           SUM(COALESCE(pls.stock_qty, gv.stock_qty)) as current_stock,
                           COALESCE(sales.sold_qty, 0) as sold_qty,
                           COALESCE(sales.sold_val, 0) as sold_val')
                 ->from('garment_variants gv')
                 ->join('products p', 'p.id = gv.product_id');

        if ($location_id) {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id AND pls.location_id = ' . $location_id, 'left');
        } else {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id', 'left');
        }

        // Subquery for sales
        $sales_sql = "(SELECT ii.colour_name, SUM(ii.qty) as sold_qty, SUM(ii.total) as sold_val 
                       FROM invoice_items ii 
                       JOIN invoices i ON i.id = ii.invoice_id 
                       WHERE i.status != 'cancelled' AND i.invoice_date BETWEEN '{$from}' AND '{$to}'"
                     . ($location_id ? " AND i.location_id = {$location_id}" : "") 
                     . " GROUP BY ii.colour_name) sales";

        $this->db->join($sales_sql, 'sales.colour_name = gv.colour_name', 'left');
        $this->db->where('p.is_garment', 1);
        $this->db->group_by('gv.colour_name');
        $this->db->order_by('sold_qty', 'DESC');

        return $this->db->get()->result();
    }

    /**
     * Garment Comprehensive Stock Report
     */
    public function get_stock_report($filters = []) {
        $location_id = !empty($filters['location_id']) ? (int)$filters['location_id'] : null;
        $category    = !empty($filters['category']) ? $filters['category'] : null;
        $brand       = !empty($filters['brand']) ? $filters['brand'] : null;
        $gender      = !empty($filters['gender']) ? $filters['gender'] : null;
        $size        = !empty($filters['size']) ? $filters['size'] : null;
        $colour      = !empty($filters['colour']) ? $filters['colour'] : null;
        $search      = !empty($filters['search']) ? trim($filters['search']) : null;
        $stock_filter= $filters['stock_filter'] ?? '';

        $this->db->select('gv.id as variant_id, gv.variant_sku, gv.barcode, gv.size_name, gv.colour_name, gv.mfr_article_no,
                           gv.mrp, gv.selling_price, gv.purchase_price, gv.cost_price, gv.min_stock,
                           gc.hex_code as colour_hex, COALESCE(gs.sort_order, 999) as size_sort_order,
                           p.id as product_id, p.name as product_name, p.item_code, p.style_code, p.design_no,
                           p.category, p.brand, p.gender, p.garment_category, p.fabric_type, p.fit_type,
                           GREATEST(COALESCE(pls.stock_qty, gv.stock_qty), 0) as stock_qty,
                           (GREATEST(COALESCE(pls.stock_qty, gv.stock_qty), 0) * gv.cost_price) as stock_val_cost,
                           (GREATEST(COALESCE(pls.stock_qty, gv.stock_qty), 0) * gv.mrp) as stock_val_mrp')
                 ->from('garment_variants gv')
                 ->join('products p', 'p.id = gv.product_id')
                 ->join('garment_colours gc', 'gc.id = gv.colour_id', 'left')
                 ->join('garment_sizes gs', 'gs.id = gv.size_id', 'left');

        if ($location_id) {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id AND pls.location_id = ' . $location_id, 'left');
        } else {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id', 'left');
        }

        $this->db->where('p.is_garment', 1);

        if ($category) {
            $this->db->group_start()
                     ->where('p.garment_category', $category)
                     ->or_where('p.category', $category)
                     ->group_end();
        }
        if ($brand) $this->db->where('p.brand', $brand);
        if ($gender) $this->db->where('p.gender', $gender);
        if ($size) $this->db->where('gv.size_name', $size);
        if ($colour) $this->db->where('gv.colour_name', $colour);

        if ($search) {
            $this->db->group_start()
                     ->like('gv.barcode', $search)
                     ->or_like('gv.variant_sku', $search)
                     ->or_like('gv.mfr_article_no', $search)
                     ->or_like('p.style_code', $search)
                     ->or_like('p.design_no', $search)
                     ->or_like('p.item_code', $search)
                     ->or_like('p.name', $search)
                     ->group_end();
        }

        if ($stock_filter === 'zero') {
            $this->db->having('stock_qty <= 0');
        } elseif ($stock_filter === 'low') {
            $this->db->having('stock_qty > 0 AND stock_qty <= min_stock');
        } elseif ($stock_filter === 'available') {
            $this->db->having('stock_qty > 0');
        }

        $this->db->order_by('p.name', 'ASC')
                 ->order_by('size_sort_order', 'ASC')
                 ->order_by('gv.colour_name', 'ASC');

        return $this->db->get()->result();
    }

    /**
     * Size-wise Stock Breakdown (Calculates total pieces per size for fast insights)
     */
    public function get_size_wise_stock_breakdown($filters = []) {
        $location_id = !empty($filters['location_id']) ? (int)$filters['location_id'] : null;

        $this->db->select('gv.size_name, 
                           COALESCE(gs.sort_order, 999) as size_sort_order,
                           COUNT(DISTINCT gv.id) as variants_count,
                           COUNT(DISTINCT p.id) as styles_count,
                           SUM(GREATEST(COALESCE(pls.stock_qty, gv.stock_qty), 0)) as total_stock_qty,
                           SUM(GREATEST(COALESCE(pls.stock_qty, gv.stock_qty), 0) * gv.cost_price) as total_val_cost,
                           SUM(GREATEST(COALESCE(pls.stock_qty, gv.stock_qty), 0) * gv.mrp) as total_val_mrp')
                 ->from('garment_variants gv')
                 ->join('products p', 'p.id = gv.product_id')
                 ->join('garment_sizes gs', 'gs.id = gv.size_id', 'left');

        if ($location_id) {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id AND pls.location_id = ' . $location_id, 'left');
        } else {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id', 'left');
        }

        $this->db->where('p.is_garment', 1);

        if (!empty($filters['category'])) {
            $cat = $filters['category'];
            $this->db->group_start()->where('p.garment_category', $cat)->or_where('p.category', $cat)->group_end();
        }
        if (!empty($filters['brand'])) {
            $this->db->where('p.brand', $filters['brand']);
        }
        if (!empty($filters['gender'])) {
            $this->db->where('p.gender', $filters['gender']);
        }
        if (!empty($filters['colour'])) {
            $this->db->where('gv.colour_name', $filters['colour']);
        }
        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $this->db->group_start()
                     ->like('gv.barcode', $s)
                     ->or_like('gv.variant_sku', $s)
                     ->or_like('gv.mfr_article_no', $s)
                     ->or_like('p.style_code', $s)
                     ->or_like('p.design_no', $s)
                     ->or_like('p.item_code', $s)
                     ->or_like('p.name', $s)
                     ->group_end();
        }

        $this->db->group_by('gv.size_name')
                 ->order_by('size_sort_order', 'ASC')
                 ->order_by('gv.size_name', 'ASC');

        return $this->db->get()->result();
    }
}
