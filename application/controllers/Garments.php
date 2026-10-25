<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Garments Controller
 * Comprehensive Clothing & Garment Management: Styles, Size/Colour Variants, Barcodes & Thermal Printing
 */
class Garments extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Product_model', 'Garment_model', 'Master_model', 'Hsn_model', 'Location_model']);
        $this->load->library('form_validation');
    }

    // ─── 1. Style Master / Garment Catalog ────────────────────────────────────
    public function index() {
        $data['page_title'] = 'Garments / Style Master';
        $loc_id = $this->active_location_id();

        $filters = [
            'q'                => trim($this->input->get('q') ?: ''),
            'garment_category' => trim($this->input->get('garment_category') ?: ''),
            'gender'           => trim($this->input->get('gender') ?: ''),
            'brand'            => trim($this->input->get('brand') ?: ''),
            'fabric_type'      => trim($this->input->get('fabric_type') ?: ''),
        ];

        $this->db->select('p.*, 
                           COUNT(DISTINCT gv.id) as variant_count,
                           SUM(GREATEST(COALESCE(pls.stock_qty, gv.stock_qty, 0), 0)) as total_garment_stock,
                           MIN(gv.selling_price) as min_sp,
                           MAX(gv.selling_price) as max_sp,
                           MIN(gv.mrp) as min_mrp,
                           MAX(gv.mrp) as max_mrp')
                 ->from('products p')
                 ->join('garment_variants gv', 'gv.product_id = p.id AND gv.status = 1', 'left');

        if ($loc_id) {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id AND pls.location_id = ' . (int)$loc_id, 'left');
        } else {
            $this->db->join('garment_variant_location_stock pls', 
                            'pls.variant_id = gv.id', 'left');
        }

        $this->db->where('p.is_garment', 1);

        if (!empty($filters['q'])) {
            $q = $this->db->escape_like_str($filters['q']);
            $this->db->where("(p.name LIKE '%{$q}%' OR p.item_code LIKE '%{$q}%' OR p.style_code LIKE '%{$q}%' OR p.design_no LIKE '%{$q}%' OR gv.barcode LIKE '%{$q}%' OR gv.variant_sku LIKE '%{$q}%')", null, false);
        }
        if (!empty($filters['garment_category'])) $this->db->where('p.garment_category', $filters['garment_category']);
        if (!empty($filters['gender']))           $this->db->where('p.gender', $filters['gender']);
        if (!empty($filters['brand']))            $this->db->where('p.brand', $filters['brand']);
        if (!empty($filters['fabric_type']))      $this->db->where('p.fabric_type', $filters['fabric_type']);

        $this->db->group_by('p.id')->order_by('p.id', 'DESC')->limit(300);
        $data['styles'] = $this->db->get()->result();

        $data['filters'] = $filters;
        $data['categories'] = $this->Garment_model->get_garment_categories();
        $data['fabrics']    = $this->Garment_model->get_fabric_types();
        $data['brands']     = $this->Master_model->get_all_brands();

        $this->render('garments/index', $data);
    }

    // ─── 2. Create Style & Generate Variants ──────────────────────────────────
    public function create() {
        $data['page_title']    = 'Add New Garment Style';
        $data['style']         = null;
        $data['variants']      = [];
        $data['size_sets']     = $this->Garment_model->get_size_sets();
        $data['all_sizes']     = $this->Garment_model->get_all_sizes();
        $data['all_colours']   = $this->Garment_model->get_colours();
        $data['categories']    = $this->Garment_model->get_garment_categories();
        $data['fabrics']       = $this->Garment_model->get_fabric_types();
        $data['fits']          = $this->Garment_model->get_fit_types();
        $data['sleeves']       = $this->Garment_model->get_sleeve_types();
        $data['necks']         = $this->Garment_model->get_neck_types();
        $data['brands']        = $this->Master_model->get_all_brands();
        $data['product_cats']  = $this->Master_model->get_all_categories();
        $data['hsn_options']   = $this->Hsn_model->get_for_select();

        $this->render('garments/form', $data);
    }

    // ─── 3. Edit Style & Variants ─────────────────────────────────────────────
    public function edit($id) {
        $style = $this->db->where('id', (int)$id)->where('is_garment', 1)->get('products')->row();
        if (!$style) {
            $this->session->set_flashdata('error', 'Garment Style not found.');
            redirect('garments');
        }

        $data['page_title']    = 'Edit Style: ' . $style->name;
        $data['style']         = $style;
        $data['variants']      = $this->Garment_model->get_variants_by_product($id, $this->active_location_id());
        $data['size_sets']     = $this->Garment_model->get_size_sets();
        $data['all_sizes']     = $this->Garment_model->get_all_sizes();
        $data['all_colours']   = $this->Garment_model->get_colours();
        $data['categories']    = $this->Garment_model->get_garment_categories();
        $data['fabrics']       = $this->Garment_model->get_fabric_types();
        $data['fits']          = $this->Garment_model->get_fit_types();
        $data['sleeves']       = $this->Garment_model->get_sleeve_types();
        $data['necks']         = $this->Garment_model->get_neck_types();
        $data['brands']        = $this->Master_model->get_all_brands();
        $data['product_cats']  = $this->Master_model->get_all_categories();
        $data['hsn_options']   = $this->Hsn_model->get_for_select();

        $this->render('garments/form', $data);
    }

    // ─── 4. Save Style & Variants (POST) ──────────────────────────────────────
    public function save() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('garments');
        }

        $id = (int)$this->input->post('id');
        $name = trim($this->input->post('name', true));
        $item_code = trim($this->input->post('item_code', true));
        $style_code = trim($this->input->post('style_code', true)) ?: $item_code;
        $design_no = trim($this->input->post('design_no', true));

        if (empty($name)) {
            $this->session->set_flashdata('error', 'Product / Style Name is required.');
            redirect($id ? "garments/edit/{$id}" : 'garments/create');
            return;
        }

        // Auto-generate item code if blank
        if (empty($item_code)) {
            $item_code = 'GAR-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 4)) . '-' . mt_rand(1000, 9999);
        }

        // Check unique item code
        $this->db->where('item_code', $item_code);
        if ($id) $this->db->where('id !=', $id);
        if ($this->db->count_all_results('products') > 0) {
            $this->session->set_flashdata('error', "Item Code '{$item_code}' is already in use.");
            redirect($id ? "garments/edit/{$id}" : 'garments/create');
            return;
        }

        $product_data = [
            'name'                 => $name,
            'item_code'            => $item_code,
            'product_type'         => 'garment',
            'is_garment'           => 1,
            'garment_category'     => $this->input->post('garment_category', true),
            'gender'               => $this->input->post('gender', true) ?: 'Unisex',
            'collection_season'    => $this->input->post('collection_season', true),
            'fabric_type'          => $this->input->post('fabric_type', true),
            'pattern_print'        => $this->input->post('pattern_print', true),
            'fit_type'             => $this->input->post('fit_type', true),
            'sleeve_type'          => $this->input->post('sleeve_type', true),
            'neck_type'            => $this->input->post('neck_type', true),
            'garment_length'       => $this->input->post('garment_length', true),
            'size_system'          => $this->input->post('size_system', true) ?: 'Alphabetical',
            'design_no'            => $design_no,
            'style_code'           => $style_code,
            'mfr_article_no'       => $this->input->post('mfr_article_no', true),
            'country_of_origin'    => $this->input->post('country_of_origin', true) ?: 'India',
            'care_instructions'    => $this->input->post('care_instructions', true),
            'description'          => $this->input->post('description', true),
            'brand'                => $this->input->post('brand', true),
            'category'             => $this->input->post('category', true) ?: 'Apparel & Clothing',
            'hsn_id'               => (int)$this->input->post('hsn_id') ?: null,
            'hsn_code'             => $this->input->post('hsn_code', true) ?: '6204',
            'tax_category'         => 'garment_slab',
            'is_garment'           => 1,
            'tax_inclusive_sales'  => 1,
            'tax_inclusive_purchase' => 0,
            'gst_percent'          => (float)$this->input->post('gst_percent') ?: 5.0,
            'mrp'                  => (float)$this->input->post('base_mrp') ?: 0.0,
            'selling_price'        => (float)$this->input->post('base_sp') ?: 0.0,
            'purchase_price'       => (float)$this->input->post('base_cost') ?: 0.0,
            'cost_price'           => (float)$this->input->post('base_cost') ?: 0.0,
            'unit'                 => 'PCS',
            'status'               => 1,
            'stock_manage'         => 1,
            'updated_at'           => date('Y-m-d H:i:s'),
        ];

        // Handle image upload if provided
        if (!empty($_FILES['image1']['name'])) {
            $config['upload_path']   = './uploads/products/';
            $config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
            $config['max_size']      = 5120;
            $config['file_name']     = 'garment_' . time() . '_' . mt_rand(100, 999);
            if (!is_dir($config['upload_path'])) mkdir($config['upload_path'], 0777, true);
            $this->load->library('upload', $config);
            if ($this->upload->do_upload('image1')) {
                $upload_res = $this->upload->data();
                $product_data['image1'] = $upload_res['file_name'];
            }
        }

        $this->db->trans_start();

        if ($id) {
            $this->db->where('id', $id)->update('products', $product_data);
            $product_id = $id;
        } else {
            $product_data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('products', $product_data);
            $product_id = $this->db->insert_id();
        }

        // Process Variants submitted from matrix
        $variant_ids     = $this->input->post('var_id');
        $sizes           = $this->input->post('var_size');
        $size_ids        = $this->input->post('var_size_id');
        $colours         = $this->input->post('var_colour');
        $colour_ids      = $this->input->post('var_colour_id');
        $skus            = $this->input->post('var_sku');
        $barcodes        = $this->input->post('var_barcode');
        $mrps            = $this->input->post('var_mrp');
        $sps             = $this->input->post('var_sp');
        $costs           = $this->input->post('var_cost');
        $opening_stocks  = $this->input->post('var_opening_stock');
        $min_stocks      = $this->input->post('var_min_stock');
        $reorders        = $this->input->post('var_reorder');
        $statuses        = $this->input->post('var_status');

        $active_loc = $this->active_location_id() ?: 1;

        if (!empty($sizes) && is_array($sizes)) {
            foreach ($sizes as $idx => $size_name) {
                $vid         = !empty($variant_ids[$idx]) ? (int)$variant_ids[$idx] : 0;
                $col_name    = trim($colours[$idx] ?? 'Standard');
                $size_name   = trim($size_name);
                if ($size_name === '') continue;

                $barcode = trim($barcodes[$idx] ?? '');
                if (empty($barcode)) {
                    $barcode = $this->Garment_model->generate_barcode($style_code, $col_name, $size_name);
                }

                $sku = trim($skus[$idx] ?? '');
                if (empty($sku)) {
                    $sku = $this->Garment_model->generate_sku($style_code, $col_name, $size_name);
                }

                $mrp_val  = isset($mrps[$idx]) ? (float)$mrps[$idx] : (float)$product_data['mrp'];
                $sp_val   = isset($sps[$idx]) ? (float)$sps[$idx] : (float)$product_data['selling_price'];
                $cost_val = isset($costs[$idx]) ? (float)$costs[$idx] : (float)$product_data['cost_price'];
                $min_val  = isset($min_stocks[$idx]) ? (float)$min_stocks[$idx] : 2;
                $reorder  = isset($reorders[$idx]) ? (float)$reorders[$idx] : 5;
                $stat_val = isset($statuses[$idx]) ? (int)$statuses[$idx] : 1;

                $var_row = [
                    'product_id'     => $product_id,
                    'variant_sku'    => $sku,
                    'size_id'        => !empty($size_ids[$idx]) ? (int)$size_ids[$idx] : null,
                    'size_name'      => $size_name,
                    'colour_id'      => !empty($colour_ids[$idx]) ? (int)$colour_ids[$idx] : null,
                    'colour_name'    => $col_name,
                    'barcode'        => $barcode,
                    'mfr_article_no' => $product_data['mfr_article_no'] ?: null,
                    'purchase_price' => $cost_val,
                    'cost_price'     => $cost_val,
                    'mrp'            => $mrp_val,
                    'selling_price'  => $sp_val,
                    'min_stock'      => $min_val,
                    'reorder_level'  => $reorder,
                    'status'         => $stat_val,
                    'updated_at'     => date('Y-m-d H:i:s'),
                ];

                if ($vid > 0) {
                    $this->db->where('id', $vid)->update('garment_variants', $var_row);
                    $saved_var_id = $vid;
                } else {
                    $var_row['created_at'] = date('Y-m-d H:i:s');
                    $this->db->insert('garment_variants', $var_row);
                    $saved_var_id = $this->db->insert_id();
                }

                // Register/update in product_barcodes
                $this->db->where('product_id', $product_id)->where('variant_id', $saved_var_id)->delete('product_barcodes');
                $this->db->insert('product_barcodes', [
                    'product_id'     => $product_id,
                    'variant_id'     => $saved_var_id,
                    'barcode'        => $barcode,
                    'unit_type'      => 'PCS',
                    'qty_multiplier' => 1
                ]);

                // Handle Opening Stock if entered for a new variant or explicitly updated
                if (isset($opening_stocks[$idx]) && $opening_stocks[$idx] !== '') {
                    $op_qty = (float)$opening_stocks[$idx];
                    if ($op_qty >= 0) {
                        $this->Garment_model->set_variant_stock($saved_var_id, $product_id, $active_loc, $op_qty);
                    }
                }
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Error saving garment style and variants.');
            redirect($id ? "garments/edit/{$id}" : 'garments/create');
        } else {
            $this->log_activity('garments', $id ? 'update' : 'create', "Garment Style '{$name}' saved with variants", $product_id);
            $this->session->set_flashdata('success', "Garment Style '{$name}' saved successfully!");
            redirect("garments/view/{$product_id}");
        }
    }

    // ─── 5. View Style, Variants & Stock ──────────────────────────────────────
    public function view($id) {
        $style = $this->db->where('id', (int)$id)->where('is_garment', 1)->get('products')->row();
        if (!$style) {
            $this->session->set_flashdata('error', 'Garment Style not found.');
            redirect('garments');
        }

        $loc_id = $this->active_location_id();
        $data['page_title'] = $style->name . ' (' . ($style->style_code ?: $style->item_code) . ')';
        $data['style']      = $style;
        $data['variants']   = $this->Garment_model->get_variants_by_product($id, $loc_id);
        $data['locations']  = $this->Location_model->get_all();

        // Location breakdown per variant
        $loc_stock_matrix = [];
        $loc_rows = $this->db->where('product_id', (int)$id)->get('garment_variant_location_stock')->result();
        foreach ($loc_rows as $lr) {
            $loc_stock_matrix[$lr->variant_id][$lr->location_id] = (float)$lr->stock_qty;
        }
        $data['loc_stock_matrix'] = $loc_stock_matrix;

        $this->render('garments/view', $data);
    }

    // ─── 6. Size Sets & Sizes Master ──────────────────────────────────────────
    public function sizes() {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $action = $this->input->post('action');
            if ($action === 'add_set') {
                $set_name = trim($this->input->post('set_name', true));
                $code     = strtoupper(trim($this->input->post('code', true) ?: preg_replace('/[^A-Za-z0-9]/', '', $set_name)));
                if (!empty($set_name)) {
                    $this->Garment_model->save_size_set([
                        'name' => $set_name,
                        'code' => $code,
                        'sort_order' => (int)$this->input->post('sort_order') ?: 10,
                        'status' => 1
                    ]);
                    $this->session->set_flashdata('success', 'Size Set created.');
                }
            } elseif ($action === 'add_size') {
                $set_id = (int)$this->input->post('size_set_id');
                $name   = trim($this->input->post('size_name', true));
                $meas   = trim($this->input->post('measurement', true));
                if (!empty($name) && $set_id) {
                    $this->Garment_model->save_size([
                        'size_set_id' => $set_id,
                        'name'        => $name,
                        'measurement' => $meas,
                        'sort_order'  => (int)$this->input->post('sort_order') ?: 0,
                        'status'      => 1
                    ]);
                    $this->session->set_flashdata('success', 'Size created.');
                }
            }
            redirect('garments/sizes');
            return;
        }

        $data['page_title'] = 'Size Sets & Sizes Master';
        $data['size_sets']  = $this->Garment_model->get_size_sets(false);
        $data['sizes']      = $this->Garment_model->get_all_sizes(false);

        $this->render('garments/sizes', $data);
    }

    // ─── 7. Colours Master ───────────────────────────────────────────────────
    public function colours() {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $name     = trim($this->input->post('name', true));
            $code     = trim($this->input->post('code', true) ?: strtoupper(substr($name, 0, 3)));
            $hex_code = trim($this->input->post('hex_code', true) ?: '#CCCCCC');
            $cid      = (int)$this->input->post('id');

            if (!empty($name)) {
                $this->Garment_model->save_colour([
                    'id'         => $cid ?: null,
                    'name'       => $name,
                    'code'       => $code,
                    'hex_code'   => $hex_code,
                    'sort_order' => (int)$this->input->post('sort_order') ?: 0,
                    'status'     => 1
                ]);
                $this->session->set_flashdata('success', 'Colour saved.');
            }
            redirect('garments/colours');
            return;
        }

        $data['page_title'] = 'Vehicle & Product Colours Master';
        $data['colours']    = $this->Garment_model->get_colours(false);

        $this->render('garments/colours', $data);
    }

    public function delete_colour($id = null) {
        if ($id) {
            $this->db->where('id', (int)$id)->delete('garment_colours');
            $this->session->set_flashdata('success', 'Colour deleted successfully.');
        }
        redirect('garments/colours');
    }

    // ─── 8. Thermal Garment Barcode Label Printing ────────────────────────────
    public function print_tags() {
        $product_id = (int)$this->input->get('product_id');
        $variant_id = (int)$this->input->get('variant_id');

        $this->db->select('gv.*, p.name as product_name, p.style_code, p.design_no, p.mfr_article_no,
                           p.garment_category, p.brand, p.hsn_code, p.country_of_origin')
                 ->from('garment_variants gv')
                 ->join('products p', 'p.id = gv.product_id');

        if ($variant_id) {
            $this->db->where('gv.id', $variant_id);
        } elseif ($product_id) {
            $this->db->where('gv.product_id', $product_id);
        } else {
            $this->db->where('gv.status', 1)->limit(100);
        }

        $data['tags'] = $this->db->get()->result();
        $data['page_title'] = 'Print Garment Barcode Tags';
        $data['label_format'] = $this->input->get('format') ?: '50x38'; // 50x25, 50x38, 75x50
        $data['copies'] = max(1, (int)($this->input->get('copies') ?: 1));

        $this->load->view('garments/print_tags', $data);
    }

    // ─── 9. AJAX Variant Matrix Generator ─────────────────────────────────────
    public function generate_matrix_ajax() {
        $size_ids    = $this->input->post('size_ids');
        $colour_ids  = $this->input->post('colour_ids');
        $style_code  = trim($this->input->post('style_code') ?: 'GARMENT');
        $base_mrp    = (float)$this->input->post('base_mrp');
        $base_sp     = (float)$this->input->post('base_sp');
        $base_cost   = (float)$this->input->post('base_cost');

        if (empty($size_ids) || empty($colour_ids)) {
            echo json_encode(['status' => 'error', 'message' => 'Please select at least one Size and one Colour.']);
            return;
        }

        $sizes = $this->db->where_in('id', (array)$size_ids)->order_by('sort_order', 'ASC')->get('garment_sizes')->result();
        $colours = $this->db->where_in('id', (array)$colour_ids)->order_by('name', 'ASC')->get('garment_colours')->result();

        $rows = [];
        foreach ($colours as $col) {
            foreach ($sizes as $sz) {
                $sku = $this->Garment_model->generate_sku($style_code, $col->name, $sz->name);
                $barcode = $this->Garment_model->generate_barcode($style_code, $col->name, $sz->name);

                $rows[] = [
                    'size_id'       => $sz->id,
                    'size_name'     => $sz->name,
                    'colour_id'     => $col->id,
                    'colour_name'   => $col->name,
                    'colour_hex'    => $col->hex_code,
                    'sku'           => $sku,
                    'barcode'       => $barcode,
                    'mrp'           => $base_mrp,
                    'sp'            => $base_sp,
                    'cost'          => $base_cost,
                    'opening_stock' => 0,
                    'min_stock'     => 2,
                    'reorder'       => 5
                ];
            }
        }

        echo json_encode(['status' => 'success', 'matrix' => $rows]);
    }

    // ─── 10. Check Barcode AJAX ───────────────────────────────────────────────
    public function check_barcode_ajax() {
        $barcode = trim($this->input->get('barcode') ?: '');
        $ignore_id = (int)$this->input->get('ignore_variant_id');

        if ($barcode === '') {
            echo json_encode(['exists' => false]);
            return;
        }

        $this->db->select('gv.id, gv.variant_sku, gv.size_name, gv.colour_name, p.name as product_name')
                 ->from('garment_variants gv')
                 ->join('products p', 'p.id = gv.product_id')
                 ->where('gv.barcode', $barcode);

        if ($ignore_id) $this->db->where('gv.id !=', $ignore_id);
        $found = $this->db->get()->row();

        if ($found) {
            echo json_encode([
                'exists'  => true,
                'message' => "Barcode is already in use by: {$found->product_name} ({$found->colour_name} / {$found->size_name})"
            ]);
        } else {
            echo json_encode(['exists' => false]);
        }
    }

    // ─── 11. Import Styles & Variants (CSV) ────────────────────────────────────
    public function import() {
        $data['page_title'] = 'Import Garment Styles & Variants (CSV)';

        if ($this->input->server('REQUEST_METHOD') === 'POST' && !empty($_FILES['csv_file']['tmp_name'])) {
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if ($file) {
                $header = fgetcsv($file);
                $imported = 0;
                $active_loc = $this->active_location_id() ?: 1;

                while (($row = fgetcsv($file)) !== FALSE) {
                    if (empty($row[0])) continue;
                    // Format: Name, StyleCode, GarmentCategory, Gender, Brand, Size, Colour, Barcode, MRP, SellingPrice, CostPrice, OpeningStock
                    $name      = trim($row[0]);
                    $style_code= trim($row[1] ?? '');
                    $category  = trim($row[2] ?? 'Garment');
                    $gender    = trim($row[3] ?? 'Unisex');
                    $brand     = trim($row[4] ?? '');
                    $size      = trim($row[5] ?? 'Free Size');
                    $colour    = trim($row[6] ?? 'Standard');
                    $barcode   = trim($row[7] ?? '');
                    $mrp       = (float)($row[8] ?? 0);
                    $sp        = (float)($row[9] ?? $mrp);
                    $cost      = (float)($row[10] ?? 0);
                    $op_stock  = (float)($row[11] ?? 0);

                    // Find or create parent product
                    $prod = $this->db->where('name', $name)->where('is_garment', 1)->get('products')->row();
                    if (!$prod) {
                        $p_data = [
                            'name'             => $name,
                            'item_code'        => 'GAR-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 4)) . '-' . mt_rand(1000, 9999),
                            'style_code'       => $style_code ?: $name,
                            'product_type'     => 'garment',
                            'is_garment'       => 1,
                            'garment_category' => $category,
                            'gender'           => $gender,
                            'brand'            => $brand,
                            'mrp'              => $mrp,
                            'selling_price'    => $sp,
                            'cost_price'       => $cost,
                            'unit'             => 'PCS',
                            'status'           => 1,
                            'created_at'       => date('Y-m-d H:i:s')
                        ];
                        $this->db->insert('products', $p_data);
                        $prod_id = $this->db->insert_id();
                    } else {
                        $prod_id = $prod->id;
                    }

                    // Check or generate barcode & sku
                    if (empty($barcode)) {
                        $barcode = $this->Garment_model->generate_barcode($style_code ?: $name, $colour, $size);
                    }
                    $sku = $this->Garment_model->generate_sku($style_code ?: $name, $colour, $size);

                    $v_row = [
                        'product_id'     => $prod_id,
                        'variant_sku'    => $sku,
                        'size_name'      => $size,
                        'colour_name'    => $colour,
                        'barcode'        => $barcode,
                        'cost_price'     => $cost,
                        'mrp'            => $mrp,
                        'selling_price'  => $sp,
                        'status'         => 1,
                        'created_at'     => date('Y-m-d H:i:s'),
                    ];
                    $this->db->insert('garment_variants', $v_row);
                    $var_id = $this->db->insert_id();

                    $this->db->insert('product_barcodes', [
                        'product_id' => $prod_id,
                        'variant_id' => $var_id,
                        'barcode'    => $barcode,
                        'unit_type'  => 'PCS'
                    ]);

                    if ($op_stock > 0) {
                        $this->Garment_model->set_variant_stock($var_id, $prod_id, $active_loc, $op_stock);
                    }
                    $imported++;
                }
                fclose($file);
                $this->session->set_flashdata('success', "Imported {$imported} garment variants successfully!");
                redirect('garments');
                return;
            }
        }

        $this->render('garments/import', $data);
    }

    // ─── 12. Template Download ────────────────────────────────────────────────
    public function download_template() {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="garment_import_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Product Name', 'Style Code', 'Garment Category', 'Gender', 'Brand', 'Size', 'Colour', 'Barcode', 'MRP', 'Selling Price', 'Cost Price', 'Opening Stock']);
        fputcsv($out, ['PRINTED KALI PALAZZO', 'LEFB-4191', 'Palazzo', 'Women', 'Suvasan', 'M', 'Grey', '8905011502814', '799.00', '799.00', '350.00', '10']);
        fputcsv($out, ['PRINTED KALI PALAZZO', 'LEFB-4191', 'Palazzo', 'Women', 'Suvasan', 'L', 'Grey', '8905011502815', '799.00', '799.00', '350.00', '8']);
        fputcsv($out, ['SLIM FIT COTTON SHIRT', 'SHT-102', 'Shirt', 'Men', 'Arrow', '40', 'Sky Blue', '8905011502820', '1299.00', '1199.00', '550.00', '15']);
        fclose($out);
        exit;
    }

    // ─── 13. Clothing Attributes Master ───────────────────────────────────────
    public function attributes() {
        $data['page_title'] = 'Clothing Attributes Master';
        $active_tab = $this->input->get('tab') ?: 'category';
        $data['active_tab'] = $active_tab;

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $type = trim($this->input->post('attribute_type', TRUE));
            $value = trim($this->input->post('attribute_value', TRUE));
            if (!empty($type) && !empty($value)) {
                $this->Garment_model->add_attribute($type, $value);
                $this->session->set_flashdata('success', "Added new {$type}: <strong>" . htmlspecialchars($value) . "</strong>");
            }
            redirect('garments/attributes?tab=' . urlencode($type));
            return;
        }

        $data['categories'] = $this->Garment_model->get_attributes_by_type('category');
        $data['fabrics']    = $this->Garment_model->get_attributes_by_type('fabric');
        $data['fits']       = $this->Garment_model->get_attributes_by_type('fit');
        $data['sleeves']    = $this->Garment_model->get_attributes_by_type('sleeve');
        $data['necks']      = $this->Garment_model->get_attributes_by_type('neck');

        $this->render('garments/attributes', $data);
    }

    public function delete_attribute($id = null) {
        $id = (int)$id;
        $tab = $this->input->get('tab') ?: 'category';
        if ($id) {
            $this->Garment_model->delete_attribute($id);
            $this->session->set_flashdata('success', 'Attribute deleted successfully.');
        }
        redirect('garments/attributes?tab=' . urlencode($tab));
    }

    // ─── 14. Quick Add Attribute AJAX ────────────────────────────────────────
    public function add_attribute_ajax() {
        $type = trim($this->input->post('type', TRUE));
        $value = trim($this->input->post('value', TRUE));

        if (empty($type) || empty($value)) {
            echo json_encode(['success' => false, 'message' => 'Type and value are required.']);
            return;
        }

        $id = $this->Garment_model->add_attribute($type, $value);
        if ($id) {
            echo json_encode(['success' => true, 'id' => $id, 'value' => $value, 'type' => $type]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save attribute.']);
        }
    }
}
