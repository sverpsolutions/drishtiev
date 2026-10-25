<?php
// ============================================================
// FILE: application/controllers/Products.php  (UPDATED)
// New features:
//   - HSN Master dropdown linked to gst_percent sync
//   - Expiry date field
//   - Low-stock threshold per product
//   - Excel / CSV bulk import  (PhpSpreadsheet or CSV fallback)
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Product_model', 'Master_model', 'Hsn_model', 'Garment_model']);
        $this->load->library('form_validation');
    }

    // Loading the whole catalog unbounded into one page crashes past a few thousand
    // products (56k+ on guptabros OOM'd the 256M web memory_limit) — cap the initial
    // render and let the search box hit list_ajax() for anything beyond it.
    const PRODUCTS_PAGE_LIMIT = 500;

    public function index() {
        $data['page_title']  = 'Products / Item Master';
        $loc_id              = $this->active_location_id(); // null = owner sees global stock
        $data['products']    = $this->Product_model->get_all($loc_id, self::PRODUCTS_PAGE_LIMIT);
        $data['view_loc_id'] = $loc_id;
        $data['products_page_limit'] = self::PRODUCTS_PAGE_LIMIT;
        $this->render('products/index', $data);
    }

    // AJAX search used by the Products list page once the catalog is bigger than
    // PRODUCTS_PAGE_LIMIT — returns rendered <tr> HTML (not JSON) so it can reuse the
    // exact same row markup as the initial page load via the _rows partial.
    public function list_ajax() {
        $q       = trim($this->input->get('q') ?: '');
        $loc_id  = $this->active_location_id();
        $products = $this->Product_model->get_all($loc_id, self::PRODUCTS_PAGE_LIMIT, $q !== '' ? $q : null);
        echo $this->load->view('products/_rows', ['products' => $products, 'view_loc_id' => $loc_id], true);
    }

    public function add() {
        $data['page_title']  = 'Add Product';
        $data['brands']      = $this->Master_model->get_all_brands();
        $data['categories']  = $this->Master_model->get_all_categories();
        $data['hsn_options'] = $this->Hsn_model->get_for_select();
        $data['racks']       = $this->db->table_exists('racks') ? $this->db->where('status', 1)->order_by('rack_code')->get('racks')->result() : [];
        $data['garment_categories'] = $this->Garment_model->get_garment_categories();
        $data['fabric_types']       = $this->Garment_model->get_fabric_types();
        $data['fit_types']          = $this->Garment_model->get_fit_types();
        $data['sleeve_types']       = $this->Garment_model->get_sleeve_types();
        $data['neck_types']         = $this->Garment_model->get_neck_types();
        $data['size_sets']          = $this->Garment_model->get_size_sets();
        $data['all_sizes']          = $this->Garment_model->get_all_sizes();
        $data['all_colours']        = $this->Garment_model->get_colours();

        $this->_set_validation_rules();

        if ($this->form_validation->run() === FALSE) {
            $this->render('products/add', $data);
            return;
        }

        $product_data = $this->_collect_post();

        // Image uploads
        $product_data['image1'] = $this->_handle_image_upload('image1');
        $product_data['image2'] = $this->_handle_image_upload('image2');
        $product_data['image3'] = $this->_handle_image_upload('image3');

        $this->db->trans_start();
        $id = $this->Product_model->create($product_data);
        if ($id) {
            $this->_save_barcodes($id, $product_data['carton_size']);
            $this->_save_garment_variants($id, $product_data);
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() !== FALSE) {
            $code = $product_data['item_code'] ?? '';
            $this->log_activity('products', 'create', 'Added product "'.($product_data['name'] ?? '').'" | Code: '.$code.' | MRP: '.($product_data['mrp'] ?? 0).' | SP: '.($product_data['selling_price'] ?? 0).' | CP: '.($product_data['purchase_price'] ?? 0), $id, $code);
            $this->session->set_flashdata('success', 'Product created successfully! Item Code: <strong>' . htmlspecialchars($code) . '</strong>');
        } else {
            $this->session->set_flashdata('error', 'Failed to save product.');
        }
        redirect('products/add');
    }

    public function edit($id = null) {
        if (!$id) redirect('products');
        $data['page_title']  = 'Edit Product';
        $data['brands']      = $this->Master_model->get_all_brands();
        $data['categories']  = $this->Master_model->get_all_categories();
        $data['hsn_options'] = $this->Hsn_model->get_for_select();
        $data['racks']       = $this->db->table_exists('racks') ? $this->db->where('status', 1)->order_by('rack_code')->get('racks')->result() : [];
        $data['garment_categories'] = $this->Garment_model->get_garment_categories();
        $data['fabric_types']       = $this->Garment_model->get_fabric_types();
        $data['fit_types']          = $this->Garment_model->get_fit_types();
        $data['sleeve_types']       = $this->Garment_model->get_sleeve_types();
        $data['neck_types']         = $this->Garment_model->get_neck_types();
        $data['size_sets']          = $this->Garment_model->get_size_sets();
        $data['all_sizes']          = $this->Garment_model->get_all_sizes();
        $data['all_colours']        = $this->Garment_model->get_colours();
        $data['variants']           = $this->Garment_model->get_variants_by_product($id, $this->active_location_id());
        $data['product']     = $this->Product_model->get_by_id($id);

        if (!$data['product']) {
            $this->session->set_flashdata('error', 'Product not found.');
            redirect('products');
        }

        // Fetch barcodes
        $bcs = $this->db->where('product_id', $id)->get('product_barcodes')->result();
        $data['pcs_barcode'] = '';
        $data['crt_barcode'] = '';
        foreach ($bcs as $bc) {
            if ($bc->unit_type === 'PCS') $data['pcs_barcode'] = $bc->barcode;
            if ($bc->unit_type === 'CRT') $data['crt_barcode'] = $bc->barcode;
        }

        $this->_set_validation_rules();

        if ($this->form_validation->run() === FALSE) {
            $this->render('products/edit', $data);
            return;
        }

        $product_data = $this->_collect_post();

        // Image uploads (replace or delete)
        for ($i = 1; $i <= 3; $i++) {
            $field = 'image' . $i;
            $del_field = 'delete_image' . $i;
            if ($this->input->post($del_field) == '1') {
                if ($data['product']->$field && file_exists('./assets/uploads/products/' . $data['product']->$field)) {
                    @unlink('./assets/uploads/products/' . $data['product']->$field);
                }
                $product_data[$field] = null;
            } else {
                $product_data[$field] = $this->_handle_image_upload($field, $data['product']->$field);
            }
        }

        $this->db->trans_start();
        $this->Product_model->update($id, $product_data);
        $this->_save_barcodes($id, $product_data['carton_size']);
        $this->_save_garment_variants($id, $product_data);
        $this->db->trans_complete();

        if ($this->db->trans_status() !== FALSE) {
            $old = $data['product'];
            $changes = [];
            if ((float)($product_data['selling_price'] ?? 0) != (float)$old->selling_price) $changes[] = 'SP: '.$old->selling_price.' → '.($product_data['selling_price'] ?? 0);
            if ((float)($product_data['mrp']           ?? 0) != (float)$old->mrp)           $changes[] = 'MRP: '.$old->mrp.' → '.($product_data['mrp'] ?? 0);
            if ((float)($product_data['purchase_price'] ?? 0) != (float)$old->purchase_price) $changes[] = 'CP: '.$old->purchase_price.' → '.($product_data['purchase_price'] ?? 0);

            // Warranty/serial switches are audited separately from price — turning
            // serial tracking off on an item that already has serials in stock is
            // the kind of change someone will need to trace later.
            $sw = [];
            foreach ([
                'warranty_applicable' => 'Warranty',
                'warranty_days'       => 'Warranty Days',
                'maintain_serial'     => 'Serial Tracking',
                'serial_entry_method' => 'Serial Entry',
                'allow_loyalty_points'=> 'Loyalty Points',
                'loyalty_points_per_unit'  => 'Loyalty Pts/Unit',
                'loyalty_amount_per_point' => 'Loyalty ₹/Point',
            ] as $f => $label) {
                if (!property_exists($old, $f)) continue;   // pre-migration install
                if ((string)($product_data[$f] ?? '') !== (string)$old->$f) {
                    $sw[] = $label.': '.$old->$f.' → '.($product_data[$f] ?? '');
                }
            }

            $desc = 'Edited product "'.$old->name.'" (ID #'.$id.')';
            if ($changes) $desc .= ' | Price changes: '.implode(', ', $changes);
            if ($sw)      $desc .= ' | Warranty/Serial: '.implode(', ', $sw);
            $this->log_activity('products', $changes ? 'price_change' : 'edit', $desc, $id, $old->item_code ?? '');
            $this->session->set_flashdata('success', 'Product updated.');
        } else {
            $this->session->set_flashdata('error', 'Failed to update product.');
        }
        redirect('products');
    }

    public function delete($id = null) {
        if (!$id || $this->session->userdata('role') !== 'admin') {
            $this->session->set_flashdata('error', 'Unauthorized.');
            redirect('products');
        }
        $this->Product_model->delete($id);
        $this->session->set_flashdata('success', 'Product deleted.');
        redirect('products');
    }

    // -------------------------------------------------------
    // Excel / CSV Import
    // -------------------------------------------------------
    public function import() {
        $data['page_title']  = 'Import Products from Excel / CSV';
        $data['hsn_options'] = $this->Hsn_model->get_for_select();
        // Check settings
        $settings = $this->db->get('company_settings')->row_array();
        if (isset($settings['enable_excel_import']) && !$settings['enable_excel_import']) {
            $this->session->set_flashdata('error', 'Excel Import is disabled in Settings.');
            redirect('products');
        }
        $this->render('products/import', $data);
    }

    // Preview step: parse file, store in session, show preview table
    public function import_preview() {
        // A large sheet (tens of thousands of rows) can need several hundred MB to
        // parse with PhpSpreadsheet — well above the site-wide 256M default. Raise
        // the ceiling for just this request rather than globally.
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('products/import');
        }

        // Use native PHP upload to avoid CI's strict MIME type rejection of xlsx
        if (empty($_FILES['import_file']['tmp_name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', 'No file uploaded or upload error.');
            redirect('products/import');
        }

        $orig_name = $_FILES['import_file']['name'];
        $ext       = strtolower('.' . pathinfo($orig_name, PATHINFO_EXTENSION));

        if (!in_array($ext, ['.xlsx', '.xls', '.csv'])) {
            $this->session->set_flashdata('error', 'Only .xlsx, .xls, or .csv files are allowed.');
            redirect('products/import');
        }

        if ($_FILES['import_file']['size'] > 8 * 1024 * 1024) {
            $this->session->set_flashdata('error', 'File too large. Maximum 8MB allowed.');
            redirect('products/import');
        }

        $tmp_dir = APPPATH . '../assets/tmp/';
        if (!is_dir($tmp_dir)) { mkdir($tmp_dir, 0755, TRUE); }

        $file_path = $tmp_dir . 'import_preview_' . time() . $ext;
        if (!move_uploaded_file($_FILES['import_file']['tmp_name'], $file_path)) {
            $this->session->set_flashdata('error', 'Failed to save uploaded file.');
            redirect('products/import');
        }

        $errors = [];
        $raw    = [];
        if ($ext === '.csv') {
            $raw = $this->_parse_csv($file_path);
        } else {
            $raw = $this->_parse_excel($file_path, $errors);
        }

        if (empty($raw)) {
            @unlink($file_path);
            $this->session->set_flashdata('error', 'No data found. ' . implode(' ', $errors));
            redirect('products/import');
        }

        // Parsing a large file can take well over a minute of pure PHP/CPU work with
        // no DB activity. The connection opened at request start (MY_Controller loads
        // company_settings for every request) can go idle long enough that the DB
        // server drops it — the next query then fails with "MySQL server has gone
        // away" instead of a fresh connection. Reconnect proactively before the
        // bulk-lookup queries that follow.
        $this->_reconnect_db();

        // Detect headers
        $headers = array_map(fn($h) => strtolower(trim($h)), array_shift($raw));

        $col_map = [
            'name'           => ['name','product name','item name','description','item description'],
            'item_code'      => ['item code','item_code','sku','code'],
            'brand'          => ['brand','brand name','manufacturer'],
            'category'       => ['category','category name','cat'],
            'unit'           => ['unit','base unit','uom'],
            'basic_cost'     => ['basic cost','basic_cost','basic','base cost','cost excl gst','purchase excl gst'],
            'rate'           => ['sale rate (sp)','selling price','sale price','sale rate','selling_price','sp','rate','price'],
            'mrp'            => ['mrp','max retail price','maximum retail price'],
            'wsp'            => ['wsp','wholesale price','whole carton price','carton price','wsp price'],
            'cost_price'     => ['cost price (cp)','cost price','cost_price','purchase price','purchase_price','cp'],
            'gst_percent'    => ['gst %','gst%','gst percent','gst_percent','tax%','gst'],
            'stock_qty'      => ['opening stock','stock','stock qty','stock_qty','opening'],
            'hsn_code'       => ['hsn code','hsn','hsn_code'],
            'pcs_barcode'    => ['pcs barcode','pcs_barcode','barcode pcs','barcode'],
            'crt_barcode'    => ['crt barcode','crt_barcode','barcode crt','carton barcode'],
        ];

        $col_idx = [];
        foreach ($col_map as $field => $aliases) {
            foreach ($aliases as $alias) {
                $pos = array_search($alias, $headers);
                if ($pos !== FALSE) { $col_idx[$field] = $pos; break; }
            }
        }

        // Bulk pre-fetch: scan the whole file once for distinct item_codes / names / hsn_codes
        // so the row loop below does zero DB queries per row (was 2-3 queries per row —
        // 100k+ round trips on a 50k-row file, which is what was timing the import out).
        $scan_codes = [];
        $scan_names = [];
        $scan_hsn   = [];
        foreach ($raw as $row) {
            if (isset($col_idx['item_code'])) $scan_codes[] = trim($row[$col_idx['item_code']] ?? '');
            if (isset($col_idx['name']))      $scan_names[] = trim($row[$col_idx['name']] ?? '');
            if (isset($col_idx['hsn_code']))  $scan_hsn[]   = $this->_clean_hsn_code(trim((string)($row[$col_idx['hsn_code']] ?? '')));
        }
        $existing_codes = $this->_existing_set('products', 'item_code', $scan_codes);
        $existing_names = $this->_existing_set('products', 'name', $scan_names);
        $hsn_map        = $this->_hsn_lookup_map($scan_hsn);

        // Build preview rows
        $preview = [];

        foreach ($raw as $rn => $row) {
            $name = isset($col_idx['name']) ? trim($row[$col_idx['name']] ?? '') : '';
            if (empty($name)) continue;

            $item_code   = isset($col_idx['item_code'])   ? trim($row[$col_idx['item_code']] ?? '')    : '';
            $brand       = isset($col_idx['brand'])       ? trim($row[$col_idx['brand']] ?? '')         : '';
            $basic_cost  = isset($col_idx['basic_cost'])  ? (float)($row[$col_idx['basic_cost']] ?? 0)  : 0;
            $rate        = isset($col_idx['rate'])        ? (float)($row[$col_idx['rate']] ?? 0)        : 0;
            $mrp         = isset($col_idx['mrp'])         ? (float)($row[$col_idx['mrp']] ?? 0)         : 0;
            $wsp         = isset($col_idx['wsp'])         ? (float)($row[$col_idx['wsp']] ?? 0)         : 0;
            $gst         = isset($col_idx['gst_percent']) ? (float)($row[$col_idx['gst_percent']] ?? 0) : 0;
            $hsn_code    = isset($col_idx['hsn_code'])    ? $this->_clean_hsn_code(trim((string)($row[$col_idx['hsn_code']] ?? ''))) : '';

            // Auto-lookup GST% from HSN master if not provided in Excel
            $hsn_id = null;
            if ($hsn_code !== '' && isset($hsn_map[$hsn_code])) {
                $hsn_id = $hsn_map[$hsn_code]['id'];
                if ($gst == 0) {
                    $gst = (float)$hsn_map[$hsn_code]['igst_percent'];
                }
            }

            $cost        = isset($col_idx['cost_price'])  ? (float)($row[$col_idx['cost_price']] ?? 0)  : 0;
            if ($cost <= 0 && $basic_cost > 0) {
                $cost = round($basic_cost * (1 + $gst / 100), 2);
            }
            $stock       = isset($col_idx['stock_qty'])   ? (int)($row[$col_idx['stock_qty']] ?? 0)     : 0;
            $unit        = isset($col_idx['unit'])        ? strtoupper(trim($row[$col_idx['unit']] ?? 'PCS')) : 'PCS';
            $cat         = isset($col_idx['category'])    ? trim($row[$col_idx['category']] ?? '')      : '';
            $pcs_barcode = isset($col_idx['pcs_barcode']) ? trim($row[$col_idx['pcs_barcode']] ?? '')   : '';
            $crt_barcode = isset($col_idx['crt_barcode']) ? trim($row[$col_idx['crt_barcode']] ?? '')   : '';

            // Duplicate check (against the bulk-loaded sets above — no per-row query)
            $exists = (!empty($item_code) && isset($existing_codes[$item_code])) || isset($existing_names[$name]);

            $error = '';
            if ($basic_cost <= 0 && $cost <= 0) $error .= 'Basic Cost or Cost Price required. ';
            if (empty($name)) $error .= 'Name required. ';

            $preview[] = [
                'row'         => $rn + 2,
                'name'        => $name,
                'item_code'   => $item_code,
                'brand'       => $brand,
                'category'    => $cat,
                'unit'        => $unit,
                'basic_cost'  => $basic_cost,
                'cost_price'  => $cost,
                'rate'        => $rate,
                'mrp'         => $mrp,
                'wsp'         => $wsp,
                'gst'         => $gst,
                'stock'       => $stock,
                'hsn_code'    => $hsn_code,
                'hsn_id'      => $hsn_id,
                'pcs_barcode' => $pcs_barcode,
                'crt_barcode' => $crt_barcode,
                'exists'      => $exists,
                'error'       => $error,
            ];
        }

        // Store preview data in a temp file, not the session — a 50k-row array is
        // several MB serialized, too slow/heavy to round-trip through the DB-backed
        // session table (sess_driver = database) on every request. Session just keeps the pointer.
        $preview_file = $tmp_dir . 'import_preview_data_' . time() . '.json';
        file_put_contents($preview_file, json_encode($preview));

        $this->session->set_userdata('import_preview_file', $preview_file);
        $this->session->set_userdata('import_file_path', $file_path);
        $this->session->set_userdata('import_col_idx', $col_idx);

        $data['page_title']      = 'Import Preview';
        $data['preview']         = $preview; // full data, used for the stat counts
        $data['preview_display'] = array_slice($preview, 0, 1000); // cap the rendered table
        $data['preview_total']   = count($preview);
        $data['col_idx']         = $col_idx;
        $this->render('products/import_preview', $data);
    }

    // Confirm import (after preview)
    public function import_confirm() {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);
        $this->_reconnect_db(); // guard against a connection gone stale while the user sat on the preview page

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('products/import');
        }

        $preview_file = $this->session->userdata('import_preview_file');
        $file_path    = $this->session->userdata('import_file_path');
        $preview      = ($preview_file && file_exists($preview_file)) ? json_decode(file_get_contents($preview_file), true) : null;

        if (empty($preview)) {
            $this->session->set_flashdata('error', 'Session expired. Please re-upload.');
            redirect('products/import');
        }

        $mode     = $this->input->post('mode') === 'update' ? 'update' : 'insert';
        $inserted = 0; $updated = 0; $skipped = 0;

        // Split into brand-new inserts vs existing rows to update vs skipped —
        // inserts get batched below, updates stay row-by-row (that set is normally
        // far smaller than the insert set on a first bulk catalog import).
        $to_insert = [];
        $to_update = [];
        foreach ($preview as $row) {
            if (!empty($row['error'])) { $skipped++; continue; }
            if ($row['exists']) {
                if ($mode === 'update') { $to_update[] = $row; }
                else                    { $skipped++; }
                continue;
            }
            $to_insert[] = $row;
        }

        // Bulk-resolve brand/category names to ids once — the distinct set is small
        // even for a 50k-row file — instead of a lookup-or-create query pair per row.
        $all_rows    = array_merge($to_insert, $to_update);
        $brand_names = array_values(array_unique(array_filter(array_map(fn($r) => trim($r['brand'] ?? ''), $all_rows))));
        $cat_names   = array_values(array_unique(array_filter(array_map(fn($r) => trim($r['category'] ?? ''), $all_rows))));
        $brand_id_map = $this->_resolve_or_create_ids('brands', $brand_names, ['status' => 1]);
        $cat_id_map   = $this->_resolve_or_create_ids('categories', $cat_names, ['parent_id' => 0, 'status' => 1]);

        // Bulk-insert brand-new products in chunks. MySQL guarantees insert_id() after
        // a multi-row INSERT returns the FIRST id of that statement, so with an explicit
        // batch_size matching the chunk we can compute every row's new id as first_id + offset
        // without a query per row.
        $barcode_rows = [];
        foreach (array_chunk($to_insert, 500) as $chunk) {
            $batch = [];
            foreach ($chunk as $row) {
                $batch[] = [
                    'name'          => $row['name'],
                    'item_code'     => $row['item_code'] ?: NULL,
                    'basic_cost'    => $row['basic_cost'] ?? 0,
                    'purchase_price'=> $row['cost_price'],
                    'cost_price'    => $row['cost_price'],
                    'selling_price' => $row['rate'],
                    'mrp'           => $row['mrp'] ?? 0,
                    'wsp'           => $row['wsp'] ?? 0,
                    'stock_qty'     => $row['stock'],
                    'unit'          => $row['unit'],
                    'gst_percent'   => $row['gst'],
                    'hsn_id'        => $row['hsn_id'] ?? null,
                    'hsn_code'      => $row['hsn_code'] ?? '',
                    'category'      => trim($row['category'] ?? ''),
                    'category_id'   => $cat_id_map[trim($row['category'] ?? '')] ?? null,
                    'brand'         => trim($row['brand'] ?? ''),
                    'brand_id'      => $brand_id_map[trim($row['brand'] ?? '')] ?? null,
                    'status'        => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                ];
            }
            $this->db->insert_batch('products', $batch, TRUE, count($batch));
            $first_id = $this->db->insert_id();
            foreach ($chunk as $i => $row) {
                $product_id = $first_id + $i;
                $inserted++;
                if (!empty($row['pcs_barcode'])) $barcode_rows[] = ['product_id'=>$product_id,'barcode'=>$row['pcs_barcode'],'unit_type'=>'PCS','qty_multiplier'=>1];
                if (!empty($row['crt_barcode'])) $barcode_rows[] = ['product_id'=>$product_id,'barcode'=>$row['crt_barcode'],'unit_type'=>'CRT','qty_multiplier'=>1];
            }
        }
        foreach (array_chunk($barcode_rows, 500) as $chunk) {
            $this->db->insert_batch('product_barcodes', $chunk);
        }

        // Existing rows to update — row-by-row (unchanged logic, just batched brand/category above).
        foreach ($to_update as $row) {
            $product_data = [
                'name'          => $row['name'],
                'item_code'     => $row['item_code'] ?: NULL,
                'basic_cost'    => $row['basic_cost'] ?? 0,
                'purchase_price'=> $row['cost_price'],
                'cost_price'    => $row['cost_price'],
                'selling_price' => $row['rate'],
                'mrp'           => $row['mrp'] ?? 0,
                'wsp'           => $row['wsp'] ?? 0,
                'stock_qty'     => $row['stock'],
                'unit'          => $row['unit'],
                'gst_percent'   => $row['gst'],
                'hsn_id'        => $row['hsn_id'] ?? null,
                'hsn_code'      => $row['hsn_code'] ?? '',
                'category'      => trim($row['category'] ?? ''),
                'category_id'   => $cat_id_map[trim($row['category'] ?? '')] ?? null,
                'brand'         => trim($row['brand'] ?? ''),
                'brand_id'      => $brand_id_map[trim($row['brand'] ?? '')] ?? null,
                'status'        => 1,
            ];

            $find = $this->db;
            if (!empty($row['item_code'])) { $find->where('item_code', $row['item_code']); }
            else                            { $find->where('name', $row['name']); }
            $existing = $find->select('id')->get('products')->row();
            $product_id = $existing ? $existing->id : null;

            $q = $this->db;
            if (!empty($row['item_code'])) { $q->where('item_code', $row['item_code']); }
            else                            { $q->where('name', $row['name']); }
            $q->update('products', $product_data);
            $updated++;

            if ($product_id) {
                $pcs_bc = $row['pcs_barcode'] ?? '';
                $crt_bc = $row['crt_barcode'] ?? '';
                if ($pcs_bc || $crt_bc) {
                    $this->db->where('product_id', $product_id)->delete('product_barcodes');
                    $bc_inserts = [];
                    if ($pcs_bc) $bc_inserts[] = ['product_id'=>$product_id,'barcode'=>$pcs_bc,'unit_type'=>'PCS','qty_multiplier'=>1];
                    if ($crt_bc) $bc_inserts[] = ['product_id'=>$product_id,'barcode'=>$crt_bc,'unit_type'=>'CRT','qty_multiplier'=>1];
                    if ($bc_inserts) $this->db->insert_batch('product_barcodes', $bc_inserts);
                }
            }
        }

        @unlink($file_path ?? '');
        @unlink($preview_file ?? '');
        $this->session->unset_userdata(['import_preview_file','import_file_path','import_col_idx']);

        $msg = "Import done. {$inserted} inserted, {$updated} updated, {$skipped} skipped.";
        $this->session->set_flashdata('success', $msg);
        redirect('products');
    }

    // Download Excel template (.xlsx) using PhpSpreadsheet
    public function download_excel_template() {
        $this->_require_spreadsheet();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products Import');

        $headers = [
            'ITEM CODE','DESCRIPTION','BRAND','CATEGORY','UNIT',
            'BASIC COST','COST PRICE (CP)','MRP','SALE RATE (SP)','WSP',
            'GST %','OPENING STOCK','HSN CODE',
            'PCS BARCODE','CRT BARCODE'
        ];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                  ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FF4472C4');
            $sheet->getStyle($col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Highlight BASIC COST column (col E) in light green
        $sheet->getStyle('E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FF70AD47');

        // Format HSN CODE (M), ITEM CODE (A), and barcode columns (N, O) as Text
        // so Excel preserves leading zeros (e.g. HSN "01012100")
        foreach (['A', 'M', 'N', 'O'] as $textCol) {
            $sheet->getStyle($textCol . '2:' . $textCol . '5000')
                  ->getNumberFormat()
                  ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }

        // Add instruction row
        $sheet->setCellValue('A3', '* BASIC COST = Purchase price EXCL GST. CP = Basic Cost + GST (auto-calc). WSP = Whole Carton/Wholesale Price. HSN CODE column is Text format — leading zeros are preserved.');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A3')->getFont()->getColor()->setARGB('FF808080');
        $sheet->mergeCells('A3:O3');

        // Sample row — use setCellValueExplicit for Text columns to preserve leading zeros
        $sample = ['ITEM001','Parle-G Biscuit 100g','Parle','Biscuits','PCS','6.50','6.83','10.00','9.00','200.00','5','100','1905','8901719110192','8901719110192CRT'];
        foreach ($sample as $i => $val) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            // Use explicit string type for text columns (A=item code, M=HSN, N=pcs barcode, O=crt barcode)
            if (in_array($col, ['A', 'M', 'N', 'O'])) {
                $sheet->setCellValueExplicit($col . '2', $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue($col . '2', $val);
            }
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="product_import_template.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function _require_spreadsheet() {
        $autoload = APPPATH . '../vendor/autoload.php';
        if (file_exists($autoload)) { require_once $autoload; }
    }

    public function import_process() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('products/import');
        }

        // Native PHP upload — avoids CI MIME type rejection for xlsx
        if (empty($_FILES['import_file']['tmp_name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', 'No file uploaded or upload error.');
            redirect('products/import');
        }

        $orig_name = $_FILES['import_file']['name'];
        $ext       = strtolower('.' . pathinfo($orig_name, PATHINFO_EXTENSION));

        if (!in_array($ext, ['.xlsx', '.xls', '.csv'])) {
            $this->session->set_flashdata('error', 'Only .xlsx, .xls, or .csv files are allowed.');
            redirect('products/import');
        }

        $tmp_dir = APPPATH . '../assets/tmp/';
        if (!is_dir($tmp_dir)) { mkdir($tmp_dir, 0755, TRUE); }

        $file_path = $tmp_dir . 'import_products_' . time() . $ext;
        if (!move_uploaded_file($_FILES['import_file']['tmp_name'], $file_path)) {
            $this->session->set_flashdata('error', 'Failed to save uploaded file.');
            redirect('products/import');
        }

        // Parse the file
        $rows = [];
        $errors = [];

        if ($ext === '.csv') {
            $rows = $this->_parse_csv($file_path);
        } elseif (in_array($ext, ['.xlsx', '.xls'])) {
            $rows = $this->_parse_excel($file_path, $errors);
        }

        if (empty($rows)) {
            @unlink($file_path);
            $this->session->set_flashdata('error', 'No data rows found in file. ' . implode(' ', $errors));
            redirect('products/import');
        }

        // Column map (header row names → internal keys)
        $col_map = [
            'name'                => ['name', 'product name', 'item name'],
            'item_code'           => ['item code', 'item_code', 'sku', 'code'],
            'hsn_code'            => ['hsn code', 'hsn_code', 'hsn'],
            'brand'               => ['brand', 'brand name'],
            'category'            => ['category', 'category name'],
            'mrp'                 => ['mrp', 'max retail price'],
            'purchase_price'      => ['purchase price', 'purchase_price', 'cost', 'buy price'],
            'selling_price'       => ['selling price', 'selling_price', 'sale price', 'sale rate'],
            'gst_percent'         => ['gst%', 'gst percent', 'gst_percent', 'tax%'],
            'stock_qty'           => ['stock', 'stock qty', 'opening stock', 'stock_qty'],
            'unit'                => ['unit', 'base unit'],
            'carton_size'         => ['carton size', 'carton_size', 'crt size', 'box size'],
            'low_stock_threshold' => ['low stock', 'low_stock_threshold', 'alert qty', 'min stock'],
            'expiry_date'         => ['expiry', 'expiry date', 'expiry_date', 'exp date'],
            'pcs_barcode'         => ['pcs barcode', 'pcs_barcode', 'barcode pcs'],
            'crt_barcode'         => ['crt barcode', 'crt_barcode', 'barcode crt'],
        ];

        // Detect header row columns
        $headers = array_map(fn($h) => strtolower(trim($h)), array_shift($rows));
        $col_idx = [];
        foreach ($col_map as $field => $aliases) {
            foreach ($aliases as $alias) {
                $pos = array_search($alias, $headers);
                if ($pos !== FALSE) { $col_idx[$field] = $pos; break; }
            }
        }

        if (empty($col_idx['name'])) {
            @unlink($file_path);
            $this->session->set_flashdata('error', 'Could not find a "Name" column in the file. Check the template.');
            redirect('products/import');
        }

        // HSN lookup cache
        $hsn_cache = [];

        $inserted = 0;
        $skipped  = 0;
        $row_errors = [];

        foreach ($rows as $rn => $row) {
            $name = trim($row[$col_idx['name']] ?? '');
            if (empty($name)) { $skipped++; continue; }

            $hsn_code = isset($col_idx['hsn_code']) ? trim((string)($row[$col_idx['hsn_code']] ?? '')) : '';
            // Remove trailing ".0" Excel adds but preserve leading zeros
            if ($hsn_code !== '' && preg_match('/^\d+\.0+$/', $hsn_code)) {
                $hsn_code = preg_replace('/\.0+$/', '', $hsn_code);
            }
            $hsn_id   = null;
            $gst_pct  = isset($col_idx['gst_percent']) ? (float)($row[$col_idx['gst_percent']] ?? 0) : 0;

            if (!empty($hsn_code)) {
                if (!isset($hsn_cache[$hsn_code])) {
                    $h = $this->Hsn_model->get_by_code($hsn_code);
                    $hsn_cache[$hsn_code] = $h ? $h : null;
                }
                if ($hsn_cache[$hsn_code]) {
                    $hsn_id  = $hsn_cache[$hsn_code]->id;
                    $gst_pct = $hsn_cache[$hsn_code]->igst_percent;
                }
            }

            $csize = (int)($row[$col_idx['carton_size']] ?? 1);
            if ($csize < 1) $csize = 1;

            $item_code = isset($col_idx['item_code']) ? trim($row[$col_idx['item_code']] ?? '') : '';
            if (!empty($item_code) && strlen($item_code) > 6) {
                $item_code = '';
            }
            if (empty($item_code)) {
                $item_code = $this->Product_model->get_next_item_code();
            }

            // Parse expiry date
            $expiry = null;
            if (isset($col_idx['expiry_date'])) {
                $raw_exp = trim($row[$col_idx['expiry_date']] ?? '');
                if (!empty($raw_exp)) {
                    $ts = strtotime($raw_exp);
                    if ($ts) $expiry = date('Y-m-d', $ts);
                }
            }

            // Resolve or insert brand
            $brand_name = isset($col_idx['brand']) ? trim($row[$col_idx['brand']] ?? '') : '';
            $brand_id = null;
            if ($brand_name !== '') {
                $br = $this->db->where('name', $brand_name)->get('brands')->row();
                if ($br) {
                    $brand_id = $br->id;
                } else {
                    $this->db->insert('brands', ['name' => $brand_name, 'status' => 1]);
                    $brand_id = $this->db->insert_id();
                }
            }

            // Resolve or insert category
            $category_name = isset($col_idx['category']) ? trim($row[$col_idx['category']] ?? '') : '';
            $category_id = null;
            if ($category_name !== '') {
                $cat = $this->db->where('name', $category_name)->get('categories')->row();
                if ($cat) {
                    $category_id = $cat->id;
                } else {
                    $this->db->insert('categories', ['name' => $category_name, 'parent_id' => 0, 'status' => 1]);
                    $category_id = $this->db->insert_id();
                }
            }

            $product_data = [
                'name'                => $name,
                'item_code'           => $item_code,
                'hsn_id'              => $hsn_id,
                'hsn_code'            => $hsn_code,
                'gst_percent'         => $gst_pct,
                'mrp'                 => (float)($row[$col_idx['mrp']] ?? 0),
                'purchase_price'      => (float)($row[$col_idx['purchase_price']] ?? 0),
                'selling_price'       => (float)($row[$col_idx['selling_price']] ?? 0),
                'stock_qty'           => (int)($row[$col_idx['stock_qty']] ?? 0),
                'unit'                => strtoupper(trim($row[$col_idx['unit']] ?? 'PCS')),
                'carton_size'         => $csize,
                'low_stock_threshold' => (int)($row[$col_idx['low_stock_threshold']] ?? 10),
                'expiry_date'         => $expiry,
                'description'         => $csize > 1 ? "{$csize}pc X 1crt" : '',
                'category'            => $category_name,
                'category_id'         => $category_id,
                'brand'               => $brand_name,
                'brand_id'            => $brand_id,
            ];

            $this->db->trans_start();
            $id = $this->Product_model->create($product_data);

            $pcs_bc = isset($col_idx['pcs_barcode']) ? trim($row[$col_idx['pcs_barcode']] ?? '') : '';
            $crt_bc = isset($col_idx['crt_barcode']) ? trim($row[$col_idx['crt_barcode']] ?? '') : '';
            if ($id && ($pcs_bc || $crt_bc)) {
                $inserts = [];
                if ($pcs_bc) $inserts[] = ['product_id'=>$id,'barcode'=>$pcs_bc,'unit_type'=>'PCS','qty_multiplier'=>1];
                if ($crt_bc) $inserts[] = ['product_id'=>$id,'barcode'=>$crt_bc,'unit_type'=>'CRT','qty_multiplier'=>$csize];
                if ($inserts) $this->db->insert_batch('product_barcodes', $inserts);
            }
            $this->db->trans_complete();

            if ($this->db->trans_status() !== FALSE) {
                $inserted++;
            } else {
                $row_errors[] = 'Row ' . ($rn + 2) . ': DB error for "' . $name . '"';
            }
        }

        @unlink($file_path);

        $msg = "Import complete. {$inserted} products imported, {$skipped} blank rows skipped.";
        if (!empty($row_errors)) $msg .= ' Errors: ' . implode('; ', $row_errors);
        $this->session->set_flashdata('success', $msg);
        redirect('products');
    }

    // Download blank import template as CSV
    public function download_template() {
        $headers = [
            'name', 'item_code', 'hsn_code', 'brand', 'category',
            'mrp', 'purchase_price', 'selling_price', 'gst_percent',
            'stock_qty', 'unit', 'carton_size', 'low_stock_threshold',
            'expiry_date', 'pcs_barcode', 'crt_barcode',
        ];
        $sample = [
            'Parle-G Biscuit', '100001', '1905', 'Parle', 'Biscuits',
            '10.00', '6.50', '9.00', '18',
            '100', 'PCS', '24', '20',
            '', '8901719110192', '8901719110192CRT',
        ];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="product_import_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        fputcsv($out, $sample);
        fclose($out);
        exit;
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------

    // reconnect() can itself fail to get a new connection (e.g. the shared-hosting
    // account is transiently at its connection limit) — that leaves conn_id === FALSE,
    // which fatals as "call to a member function on false" on the next query. Give it
    // a few short-spaced attempts rather than failing on the first refusal.
    private function _reconnect_db($attempts = 3) {
        for ($i = 0; $i < $attempts; $i++) {
            $this->db->reconnect();
            if ($this->db->conn_id) return TRUE;
            usleep(500000);
        }
        return (bool)$this->db->conn_id;
    }

    // Run a query builder callback, transparently reconnecting and retrying once if
    // the connection had gone stale (get() returns FALSE rather than throwing when
    // db_debug is off, which would otherwise fatal on ->result_array()).
    private function _query_retry(callable $build) {
        $result = $build($this->db);
        if ($result === FALSE) {
            $this->_reconnect_db();
            $result = $build($this->db);
        }
        return $result ? $result->result_array() : [];
    }

    // Bulk-fetch which of the given values already exist in $table.$column,
    // replacing N per-row exists-queries with O(distinct values / 1000) queries.
    private function _existing_set($table, $column, array $values) {
        $values = array_values(array_unique(array_filter($values, fn($v) => $v !== '')));
        $existing = [];
        foreach (array_chunk($values, 1000) as $chunk) {
            $rows = $this->_query_retry(fn($db) => $db->select($column)->from($table)->where_in($column, $chunk)->get());
            foreach ($rows as $r) { $existing[$r[$column]] = true; }
        }
        return $existing;
    }

    // Bulk-fetch hsn_master rows for the given hsn_codes, keyed by hsn_code.
    private function _hsn_lookup_map(array $hsn_codes) {
        $hsn_codes = array_values(array_unique(array_filter($hsn_codes, fn($v) => $v !== '')));
        $map = [];
        foreach (array_chunk($hsn_codes, 1000) as $chunk) {
            $rows = $this->_query_retry(fn($db) => $db->select('id, hsn_code, igst_percent')->from('hsn_master')->where_in('hsn_code', $chunk)->get());
            foreach ($rows as $r) { $map[$r['hsn_code']] = $r; }
        }
        return $map;
    }

    // Strip the trailing ".0" Excel adds to numeric HSN codes (e.g. "1905.0" -> "1905")
    // but preserve leading zeros (e.g. "01012100" stays "01012100").
    private function _clean_hsn_code($code) {
        if ($code !== '' && preg_match('/^\d+\.0+$/', $code)) {
            return preg_replace('/\.0+$/', '', $code);
        }
        return $code;
    }

    // Bulk-resolve a list of names to ids in $table, inserting any that don't exist yet.
    // Distinct brand/category names in a large import are typically a few hundred at most.
    private function _resolve_or_create_ids($table, array $names, array $insert_extra = []) {
        if (empty($names)) return [];
        $map = [];
        foreach (array_chunk($names, 1000) as $chunk) {
            $rows = $this->_query_retry(fn($db) => $db->select('id, name')->from($table)->where_in('name', $chunk)->get());
            foreach ($rows as $r) { $map[$r['name']] = $r['id']; }
        }
        foreach ($names as $name) {
            if (!isset($map[$name])) {
                $this->db->insert($table, array_merge(['name' => $name], $insert_extra));
                $map[$name] = $this->db->insert_id();
            }
        }
        return $map;
    }

    private function _parse_csv($path) {
        $rows = [];
        if (($fh = fopen($path, 'r')) !== FALSE) {
            while (($row = fgetcsv($fh)) !== FALSE) {
                $rows[] = $row;
            }
            fclose($fh);
        }
        return $rows;
    }

    private function _parse_excel($path, &$errors) {
        // Requires: composer require phpoffice/phpspreadsheet
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            // Attempt autoload
            $autoload = APPPATH . '../vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            } else {
                $errors[] = 'PhpSpreadsheet not installed. Run: composer require phpoffice/phpspreadsheet. Or upload a .csv file instead.';
                return [];
            }
        }

        $rows = [];
        try {
            // setReadDataOnly skips loading style/formatting objects we don't use —
            // for a large sheet (tens of thousands of rows) that object graph is the
            // dominant memory cost, so this matters even though we still hold every
            // cell's value in $rows either way.
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $sheet       = $spreadsheet->getActiveSheet();
            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $val = $cell->getValue();
                    // If cell contains a formula, evaluate it to get the actual number.
                    // Malformed "formulas" (e.g. a product name accidentally typed starting
                    // with "=") can throw a TypeError deep in PhpSpreadsheet's calculation
                    // engine, not just Exception — catch \Throwable or the whole import dies
                    // on one bad cell. Fall back to the raw text with the "=" stripped, not 0,
                    // so a mis-typed name/text cell doesn't silently become "0".
                    if (is_string($val) && strlen($val) > 1 && $val[0] === '=') {
                        try { $val = $cell->getCalculatedValue(); } catch (\Throwable $fe) { $val = ltrim($val, '='); }
                    }
                    // Force all values to string to preserve leading zeros (e.g. HSN "01012100")
                    $cells[] = (string)($val ?? '');
                }
                $rows[] = $cells;
            }
            // Free PhpSpreadsheet's internal cell/worksheet object graph now that we've
            // copied everything we need into $rows — on a large file this roughly halves
            // peak memory for the rest of the import (parse + preview array otherwise
            // both live in memory at once).
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);
        } catch (\Throwable $e) {
            $errors[] = 'Excel read error: ' . $e->getMessage();
        }
        return $rows;
    }

    private function _set_validation_rules() {
        $this->form_validation->set_rules('name',           'Product Name',   'required|trim|max_length[150]');
        $this->form_validation->set_rules('basic_cost',     'Basic Cost',     'required|decimal');
        $this->form_validation->set_rules('selling_price',  'Selling Price',  'required|decimal');
        $this->form_validation->set_rules('mrp',            'MRP',            'required|decimal');
        $this->form_validation->set_rules('carton_size',    'Carton Size',    'integer');
        $this->form_validation->set_rules('low_stock_threshold', 'Low Stock Alert', 'integer|greater_than[0]');
    }

    private function _collect_post() {
        $csize      = (int)$this->input->post('carton_size');
        $item_code  = $this->input->post('item_code', TRUE);
        if (!empty($item_code) && strlen($item_code) > 6) {
            $item_code = '';
        }
        if (empty($item_code)) {
            $item_code = $this->Product_model->get_next_item_code();
        }

        $expiry     = $this->input->post('expiry_date', TRUE) ?: null;
        $basic_cost = (float)$this->input->post('basic_cost', TRUE);
        $gst_pct    = (float)($this->input->post('gst_percent', TRUE) ?: 0);
        // Cost price = basic_cost + GST; use form-submitted value as fallback
        $purchase_price = (float)$this->input->post('purchase_price', TRUE);
        if ($purchase_price <= 0 && $basic_cost > 0) {
            $purchase_price = round($basic_cost * (1 + $gst_pct / 100), 2);
        }

        $category_id = $this->input->post('category_id', TRUE);
        $category_name = '';
        if ($category_id) {
            $cat = $this->db->where('id', $category_id)->get('categories')->row();
            if ($cat) $category_name = $cat->name;
        }

        $brand_id = $this->input->post('brand_id', TRUE);
        $brand_name = '';
        if ($brand_id) {
            $br = $this->db->where('id', $brand_id)->get('brands')->row();
            if ($br) $brand_name = $br->name;
        }

        return [
            'name'                => $this->input->post('name',            TRUE),
            'item_code'           => $item_code,
            'hsn_id'              => $this->input->post('hsn_id',          TRUE) ?: null,
            'description'         => $csize > 1 ? "{$csize}pc X 1crt" : '',
            'category_id'         => $category_id ?: null,
            'brand_id'            => $brand_id ?: null,
            'mrp'                 => $this->input->post('mrp',            TRUE),
            'basic_cost'          => $basic_cost,
            'purchase_price'      => $purchase_price,
            'cost_price'          => $purchase_price,
            'selling_price'       => $this->input->post('selling_price',  TRUE),
            'wsp'                 => (float)$this->input->post('wsp',     TRUE) ?: 0,
            'gst_percent'         => $gst_pct,
            // stock_qty is NOT taken from the form: QOH changes only through
            // Purchase/GRN, transfers, sales/returns, stock take or stock adjustment
            // (all of which keep location stock + the stock ledger in step).
            'low_stock_threshold' => $this->input->post('low_stock_threshold', TRUE) ?: 10,
            'unit'                => $this->input->post('unit',           TRUE),
            'carton_size'         => $csize ?: 1,
            'expiry_date'         => $expiry,
            'category'            => $category_name,
            'brand'               => $brand_name,
            'ingredients'         => $this->input->post('ingredients',    TRUE),
            'expiry_days'         => (int)$this->input->post('expiry_days', TRUE) ?: 0,
            'is_veg'              => $this->input->post('is_veg', TRUE) !== null ? (int)$this->input->post('is_veg', TRUE) : 1,
            'no_discount'         => $this->input->post('no_discount') ? 1 : 0,
            'rack_id'             => $this->input->post('rack_id', TRUE) ?: null,
        ] + $this->_collect_name_edit_post()
          + $this->_collect_serial_warranty_post()
          + $this->_collect_loyalty_post()
          + $this->_collect_garment_post()
          + $this->_collect_ev_post();
    }

    private function _collect_ev_post() {
        if (!$this->db->field_exists('model_name', 'products')) return [];
        return [
            'model_name'       => $this->input->post('model_name', TRUE) ?: null,
            'model_number'     => $this->input->post('model_number', TRUE) ?: null,
            'colour'           => $this->input->post('colour', TRUE) ?: null,
            'battery_type'     => $this->input->post('battery_type', TRUE) ?: null,
            'battery_capacity' => $this->input->post('battery_capacity', TRUE) ?: null,
            'charger_details'  => $this->input->post('charger_details', TRUE) ?: null,
            'motor_details'    => $this->input->post('motor_details', TRUE) ?: null,
            'warranty_policy'  => $this->input->post('warranty_policy', TRUE) ?: null,
        ];
    }

    private function _collect_garment_post() {
        $ptype = $this->input->post('product_type', TRUE) ?: 'electric_scooter';
        $is_garment = ($ptype === 'garment' || $this->input->post('is_garment')) ? 1 : 0;
        return [
            'product_type'      => $ptype,
            'is_garment'        => $is_garment,
            'garment_category'  => $this->input->post('garment_category', TRUE) ?: null,
            'gender'            => $this->input->post('gender', TRUE) ?: null,
            'collection_season' => $this->input->post('collection_season', TRUE) ?: null,
            'fabric_type'       => $this->input->post('fabric_type', TRUE) ?: null,
            'pattern_print'     => $this->input->post('pattern_print', TRUE) ?: null,
            'fit_type'          => $this->input->post('fit_type', TRUE) ?: null,
            'sleeve_type'       => $this->input->post('sleeve_type', TRUE) ?: null,
            'neck_type'         => $this->input->post('neck_type', TRUE) ?: null,
            'garment_length'    => $this->input->post('garment_length', TRUE) ?: null,
            'size_system'       => $this->input->post('size_system', TRUE) ?: 'Alphabetical',
            'design_no'         => $this->input->post('design_no', TRUE) ?: null,
            'style_code'        => $this->input->post('style_code', TRUE) ?: null,
            'mfr_article_no'    => $this->input->post('mfr_article_no', TRUE) ?: null,
            'country_of_origin' => $this->input->post('country_of_origin', TRUE) ?: 'India',
            'care_instructions' => $this->input->post('care_instructions', TRUE) ?: null,
        ];
    }

    private function _collect_name_edit_post() {
        if (!$this->db->field_exists('allow_name_edit', 'products')) return [];
        return ['allow_name_edit' => $this->input->post('allow_name_edit') ? 1 : 0];
    }

    private function _collect_loyalty_post() {
        if (!$this->db->field_exists('allow_loyalty_points', 'products')) return [];

        $allow  = $this->input->post('allow_loyalty_points') ? 1 : 0;
        $ppu    = $this->input->post('loyalty_points_per_unit', TRUE);
        $app    = $this->input->post('loyalty_amount_per_point', TRUE);
        $reason = trim((string)$this->input->post('loyalty_block_reason', TRUE));

        return [
            'allow_loyalty_points'     => $allow,
            // Blank means "use the global rule" — store NULL, not 0, or the item
            // would silently award zero points.
            'loyalty_points_per_unit'  => ($allow && $ppu !== '' && $ppu !== null) ? (float)$ppu : null,
            'loyalty_amount_per_point' => ($allow && $app !== '' && $app !== null && (float)$app > 0) ? (float)$app : null,
            'loyalty_block_reason'     => (!$allow && $reason !== '') ? $reason : null,
        ];
    }

    // Warranty / serial-number settings. Split out so the four supported
    // combinations stay readable:
    //   both off  -> plain quantity item, unchanged behaviour
    //   warranty only -> expiry calculated per sale, no per-unit tracking
    //   serial only   -> per-unit tracking, no warranty
    //   both on       -> full per-unit warranty tracking (motor parts)
    private function _collect_serial_warranty_post() {
        // Guard: this controller is shared by every instance, but
        // sql/43_serial_warranty.sql is per-database. Without this check a
        // deploy to an un-migrated install would make every product save fail
        // on an unknown column — the same trap that bit the financial-year
        // migration. Returning [] keeps that install on its old behaviour.
        if (!$this->db->field_exists('maintain_serial', 'products')) return [];

        $warranty = $this->input->post('warranty_applicable') ? 1 : 0;
        $serial   = $this->input->post('maintain_serial') ? 1 : 0;
        $days     = (int)$this->input->post('warranty_days', TRUE);

        // Warranty off means the day count is meaningless — store 0 rather than
        // leaving a stale number that would come back if it is re-enabled.
        if (!$warranty) $days = 0;
        if ($days < 0)  $days = 0;

        $method = $this->input->post('serial_entry_method', TRUE);
        if (!in_array($method, ['scanner', 'manual', 'excel'], true)) $method = 'scanner';

        return [
            'warranty_applicable' => $warranty,
            'warranty_days'       => $days,
            'warranty_start_from' => 'sale_invoice_date',
            'maintain_serial'     => $serial,
            'serial_entry_method' => $method,
        ];
    }

    private function _save_barcodes($product_id, $carton_size) {
        $this->db->where('product_id', $product_id)->where('variant_id IS NULL', null, false)->delete('product_barcodes');

        $pcs     = $this->input->post('pcs_barcode', TRUE);
        $crt     = $this->input->post('crt_barcode', TRUE);
        $inserts = [];

        if (!empty($pcs)) {
            $inserts[] = ['product_id'=>$product_id,'barcode'=>$pcs,'unit_type'=>'PCS','qty_multiplier'=>1];
        }
        if (!empty($crt)) {
            $inserts[] = ['product_id'=>$product_id,'barcode'=>$crt,'unit_type'=>'CRT','qty_multiplier'=>$carton_size > 0 ? $carton_size : 1];
        }
        if (!empty($inserts)) {
            $this->db->insert_batch('product_barcodes', $inserts);
        }
    }

    private function _handle_image_upload($field_name, $existing_image = null) {
        if (empty($_FILES[$field_name]['name'])) {
            return $existing_image;
        }

        $config['upload_path']   = './assets/uploads/products/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
        $config['max_size']      = 10240; // 10MB (allows larger high-res images)
        $config['encrypt_name']  = TRUE;

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, TRUE);
        }

        $this->load->library('upload');
        $this->upload->initialize($config, TRUE); // TRUE resets upload class preferences to default

        if (!$this->upload->do_upload($field_name)) {
            $this->session->set_flashdata('error', 'Image upload error: ' . $this->upload->display_errors('', ''));
            return $existing_image;
        }

        // Delete old image if exists
        if ($existing_image && file_exists($config['upload_path'] . $existing_image)) {
            @unlink($config['upload_path'] . $existing_image);
        }

        $upload_data = $this->upload->data();
        return $upload_data['file_name'];
    }

    // ── Print Product Barcode Labels ─────────────────────────────────────────
    public function print_barcodes() {
        $data['page_title'] = 'Print Barcode Labels';
        $data['products']   = $this->Product_model->get_all();
        
        // Scan prn_templates folder for template files
        $templates = [];
        $dir = FCPATH . 'prn_templates/';
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if (is_file($dir . $file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['prn', 'txt'])) {
                    $templates[] = $file;
                }
            }
        }
        $data['prn_templates'] = $templates;

        // Fetch TSC printer settings
        $settings_row = $this->db->where('key_name', 'tsc_printer_name')->get('company_settings')->row();
        $data['tsc_printer_name'] = $settings_row ? $settings_row->key_value : 'TSC';

        $this->render('products/print_barcodes', $data);
    }

    // ── Direct TSC Barcode Printing API (AJAX) ───────────────────────────────
    public function print_tsc_direct() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Method not allowed', 405);
        }

        $product_id   = (int)$this->input->post('product_id', TRUE);
        $template     = trim($this->input->post('template', TRUE));
        $qty          = (int)$this->input->post('qty', TRUE);
        $mfg_date_raw = trim($this->input->post('mfg_date', TRUE));
        $expiry_days  = (int)$this->input->post('expiry_days', TRUE);

        // Custom variables from form fields (user-edited values)
        $post_name        = trim($this->input->post('product_name', TRUE));
        $post_barcode     = trim($this->input->post('barcode', TRUE));
        $post_mrp         = trim($this->input->post('mrp', TRUE));
        $post_sp          = trim($this->input->post('sp', TRUE));
        $post_ingredients = trim($this->input->post('ingredients', TRUE));

        if (!$product_id || !$template || $qty < 1) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters provided.']);
            return;
        }

        // Fetch product
        $product = $this->Product_model->get_by_id($product_id);
        if (!$product) {
            echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
            return;
        }

        // Resolve printing values: use user-edited values if provided, otherwise fallback to database
        $product_name = ($post_name !== '') ? $post_name : $product->name;
        
        if ($post_barcode !== '') {
            $barcode = $post_barcode;
        } else {
            $barcode_row = $this->db->where('product_id', $product_id)->where('unit_type', 'PCS')->get('product_barcodes')->row();
            $barcode = $barcode_row ? $barcode_row->barcode : (!empty($product->pcs_barcode) ? $product->pcs_barcode : (!empty($product->item_code) ? $product->item_code : ''));
        }

        $mrp_val = ($post_mrp !== '') ? $post_mrp : number_format($product->mrp, 2);
        $sp_val  = ($post_sp !== '') ? $post_sp : number_format($product->selling_price, 2);
        $ingredients_val = ($post_ingredients !== '') ? $post_ingredients : ($product->ingredients ?? '');

        // Load template file
        $template_path = FCPATH . 'prn_templates/' . basename($template);
        if (!file_exists($template_path)) {
            echo json_encode(['status' => 'error', 'message' => 'Template file not found.']);
            return;
        }
        $raw_content = file_get_contents($template_path);

        // Format dates
        $mfg_timestamp = strtotime($mfg_date_raw) ?: time();
        $mfg_date_formatted = date('d.m.Y', $mfg_timestamp);

        // Expiry Date (PRINTMONTH)
        $exp_timestamp = strtotime("+{$expiry_days} days", $mfg_timestamp);
        $exp_month_abbrev = date('d M Y', $exp_timestamp);

        // Replace placeholders in the template
        $replacements = [
            '@ITEM_NAME'   => $product_name,
            '@PLU_CODE1'   => $barcode,
            '@MRP1'        => $mrp_val,
            '@SP1'         => $sp_val,
            '@SP'          => $sp_val,
            '@MFGDATE'     => $mfg_date_formatted,
            '@PRINTMONTH'  => $exp_month_abbrev,
            '@INGREDIENTS' => $ingredients_val,
        ];

        $parsed_content = $raw_content;
        foreach ($replacements as $key => $val) {
            $parsed_content = str_replace($key, $val, $parsed_content);
        }

        // Adjust TSPL print quantity command (replace P1 or similar print command at the end)
        if (preg_match('/(^|\r?\n)P\s*\d+(\r?\n|$)/', $parsed_content)) {
            $parsed_content = preg_replace('/(^|\r?\n)P\s*\d+(\r?\n|$)/', "\r\nP{$qty}\r\n", $parsed_content);
        } else {
            // Append print quantity if not explicitly matched
            $parsed_content .= "\r\nP{$qty}\r\n";
        }

        // Fetch TSC printer connection
        $settings_row = $this->db->where('key_name', 'tsc_printer_name')->get('company_settings')->row();
        $printer_name = $settings_row ? $settings_row->key_value : 'TSC';

        $print_success = false;
        $print_error_msg = '';

        // Write to printer via batch file
        try {
            $temp_dir = FCPATH . 'assets/tmp/';
            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0755, true);
            }
            
            $unique_id = time() . '_' . uniqid();
            $prn_file = $temp_dir . 'print_job_' . $unique_id . '.prn';
            $bat_file = $temp_dir . 'print_job_' . $unique_id . '.bat';
            
            if (file_put_contents($prn_file, $parsed_content) !== false) {
                // Create batch file using absolute path for the prn file
                $bat_content = "copy /B " . escapeshellarg(realpath($prn_file)) . " " . escapeshellarg($printer_name) . "\r\n";
                file_put_contents($bat_file, $bat_content);
                
                // Execute batch file using absolute path
                $cmd = escapeshellarg(realpath($bat_file));
                $cmd_output = [];
                exec($cmd, $cmd_output, $retval);
                
                // Clean up temp files
                @unlink($prn_file);
                @unlink($bat_file);
                
                if ($retval === 0) {
                    $print_success = true;
                } else {
                    $print_error_msg = "Batch file execution failed. Code: {$retval}. Output: " . implode("\n", $cmd_output);
                }
            } else {
                $print_error_msg = "Failed to write temp PRN file in assets/tmp directory.";
            }
        } catch (Exception $e) {
            $print_error_msg = $e->getMessage();
        }

        // Log printing activity
        $this->log_activity('mod_item_master', 'tsc_print', "Printed {$qty} barcode labels of product '{$product->name}' (ID #{$product_id}) using template '{$template}' to printer '{$printer_name}'", $product_id);

        if ($print_success) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Barcode commands sent to printer successfully!',
                'printer' => $printer_name,
                'commands' => $parsed_content
            ]);
        } else {
            echo json_encode([
                'status' => 'warning',
                'message' => 'Print commands generated, but direct connection to the printer failed. The printer might not be shared or online.',
                'error_detail' => $print_error_msg,
                'printer' => $printer_name,
                'commands' => $parsed_content
            ]);
        }
    }

    // ── Customer Reviews List ────────────────────────────────────────────────
    public function reviews() {
        $data['page_title'] = 'Customer Reviews';
        $data['reviews'] = $this->db->select('r.*, p.name as product_name, p.item_code, c.name as customer_name')
                                    ->from('product_reviews r')
                                    ->join('products p', 'p.id = r.product_id')
                                    ->join('customers c', 'c.id = r.customer_id')
                                    ->order_by('r.created_at', 'DESC')
                                    ->get()->result();
        $this->render('products/reviews', $data);
    }

    // ── Update Review Status ─────────────────────────────────────────────────
    public function update_review_status() {
        $id = (int)$this->input->post('id', TRUE);
        $action = trim($this->input->post('action', TRUE));

        $review = $this->db->where('id', $id)->get('product_reviews')->row();
        if (!$review) {
            $this->session->set_flashdata('error', 'Review not found.');
            redirect('products/reviews');
        }

        if ($action === 'delete') {
            if ($this->db->where('id', $id)->delete('product_reviews')) {
                $this->log_activity('mod_item_master', 'delete_review', "Deleted review ID #{$id} from customer ID #{$review->customer_id} on product ID #{$review->product_id}", $id);
                $this->session->set_flashdata('success', 'Review deleted successfully.');
            } else {
                $this->session->set_flashdata('error', 'Failed to delete review.');
            }
        } elseif (in_array($action, ['approved', 'rejected'])) {
            $this->db->where('id', $id)->update('product_reviews', ['status' => $action]);
            $this->log_activity('mod_item_master', 'update_review_status', "Updated review ID #{$id} status to {$action}", $id);
            $this->session->set_flashdata('success', "Review status updated to {$action} successfully.");
        } else {
            $this->session->set_flashdata('error', 'Invalid action.');
        }

        redirect('products/reviews');
    }

    // AJAX quick-add from billing page
    public function quick_add_ajax() {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(403)->set_output('Forbidden');
        }
        $name = trim($this->input->post('name', TRUE));
        $sp   = (float)$this->input->post('selling_price', TRUE);
        if (empty($name) || $sp <= 0) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'Name and selling price are required.']));
        }
        $item_code = trim($this->input->post('item_code', TRUE));
        if (empty($item_code)) {
            $item_code = $this->Product_model->get_next_item_code();
        }
        $gst_pct    = (float)$this->input->post('gst_percent', TRUE);
        $basic_cost = (float)$this->input->post('basic_cost', TRUE);
        $mrp        = (float)$this->input->post('mrp', TRUE) ?: $sp;
        $stock_qty  = (float)$this->input->post('stock_qty', TRUE);
        $category   = trim($this->input->post('category', TRUE));
        $brand      = trim($this->input->post('brand', TRUE));

        // Resolve or auto-create category/brand by name
        $category_id = null;
        if ($category) {
            $cat = $this->db->where('name', $category)->get('categories')->row();
            if ($cat) {
                $category_id = $cat->id;
            } else {
                $this->db->insert('categories', ['name' => $category, 'status' => 1]);
                $category_id = $this->db->insert_id();
            }
        }
        $brand_id = null;
        if ($brand) {
            $br = $this->db->where('name', $brand)->get('brands')->row();
            if ($br) {
                $brand_id = $br->id;
            } else {
                $this->db->insert('brands', ['name' => $brand, 'status' => 1]);
                $brand_id = $this->db->insert_id();
            }
        }

        $data = [
            'name'           => $name,
            'item_code'      => $item_code,
            'unit'           => $this->input->post('unit', TRUE) ?: 'PCS',
            'basic_cost'     => $basic_cost,
            'purchase_price' => $basic_cost > 0 ? round($basic_cost * (1 + $gst_pct / 100), 2) : 0,
            'selling_price'  => $sp,
            'mrp'            => $mrp,
            'gst_percent'    => $gst_pct,
            'stock_qty'      => $stock_qty,
            'category_id'    => $category_id,
            'brand_id'       => $brand_id,
            'status'         => 1,
        ];

        $id = $this->Product_model->create($data);
        if (!$id) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'Database insert failed.']));
        }
        // Return enough fields to add to billing PRODUCTS array
        $product = [
            'id'            => (int)$id,
            'name'          => $name,
            'item_code'     => $item_code,
            'unit'          => $data['unit'],
            'selling_price' => $sp,
            'mrp'           => $mrp,
            'gst_percent'   => $gst_pct,
            'stock_qty'     => $stock_qty,
            'image1'        => null,
            'plu_list'      => [],
        ];
        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'product' => $product]));
    }

    // ─── Return next available item code ─────────────────────────────
    public function next_item_code_ajax() {
        header('Content-Type: application/json');
        echo json_encode(['code' => $this->Product_model->get_next_item_code()]);
    }

    // ─── Universal product search AJAX (used by all modules) ────────
    public function search_ajax()
    {
        header('Content-Type: application/json');
        $q          = trim($this->input->post('q') ?: $this->input->get('q'));
        $category   = (int)($this->input->post('category_id') ?: $this->input->get('category_id'));
        $brand      = (int)($this->input->post('brand_id') ?: $this->input->get('brand_id'));
        $limit      = min((int)($this->input->post('limit') ?: 50), 200);

        $sql = "SELECT
                    p.id, p.item_code, p.name,
                    COALESCE(b.name,'')  AS brand_name,
                    COALESCE(c.name,'')  AS category_name,
                    COALESCE(p.unit,'PCS') AS unit,
                    COALESCE(p.mrp,0)           AS mrp,
                    COALESCE(p.selling_price,0)  AS selling_price,
                    COALESCE(p.basic_cost,0)     AS purchase_price,
                    GREATEST(COALESCE(p.stock_qty,0), 0) AS stock_qty,   /* display stock: never below 0 */
                    COALESCE(p.low_stock_threshold,10) AS min_stock,
                    COALESCE(p.gst_percent,0)    AS gst_percent,
                    p.hsn_id,
                    COALESCE(h.hsn_code,p.hsn_code,'') AS hsn_code,
                    COALESCE(pb.barcode,'')      AS barcode,
                    COALESCE(p.no_discount,0)    AS no_discount,
                    COALESCE(p.brand_id,0)       AS brand_id,
                    COALESCE(p.category_id,0)    AS category_id,
                    COALESCE(p.carton_size,1)    AS carton_size,
                    COALESCE(p.image1,'')        AS image1,
                    COALESCE(p.is_garment,0)     AS is_garment,
                    COALESCE(p.style_code,'')    AS style_code,
                    COALESCE(p.design_no,'')     AS design_no,
                    COALESCE(p.garment_category,'') AS garment_category,
                    COALESCE(p.gender,'')        AS gender,
                    (SELECT COUNT(*) FROM garment_variants gv WHERE gv.product_id = p.id AND gv.status = 1) AS variant_count,
                    " . ($this->db->field_exists('allow_name_edit', 'products')
                          ? "COALESCE(p.allow_name_edit,0) AS allow_name_edit,"
                          : "0 AS allow_name_edit,") . "
                    " . ($this->db->field_exists('maintain_serial', 'products')
                          ? "COALESCE(p.maintain_serial,0) AS maintain_serial,
                             COALESCE(p.warranty_applicable,0) AS warranty_applicable,
                             COALESCE(p.warranty_days,0) AS warranty_days"
                          : "0 AS maintain_serial, 0 AS warranty_applicable, 0 AS warranty_days") . "
                FROM products p
                LEFT JOIN brands     b  ON b.id  = p.brand_id
                LEFT JOIN categories c  ON c.id  = p.category_id
                LEFT JOIN hsn_master h  ON h.id  = p.hsn_id
                LEFT JOIN product_barcodes pb ON pb.product_id = p.id AND pb.unit_type = 'PCS'
                WHERE p.status = 1";

        $params = [];

        if ($q !== '') {
            $words = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
            if (count($words) > 1) {
                $word_conds = [];
                foreach ($words as $w) {
                    $wl = $this->db->escape('%' . $this->db->escape_like_str($w) . '%');
                    $word_conds[] = "(p.name LIKE $wl OR b.name LIKE $wl OR c.name LIKE $wl OR p.style_code LIKE $wl)";
                }
                $sql .= " AND (" . implode(' AND ', $word_conds) . ")";
            } else {
                $like = '%' . $this->db->escape_like_str($q) . '%';
                $q_e  = $this->db->escape($q);
                $like_e = $this->db->escape($like);
                $sql .= " AND (p.item_code = $q_e OR pb.barcode = $q_e OR p.name LIKE $like_e
                              OR b.name LIKE $like_e OR c.name LIKE $like_e
                              OR h.hsn_code LIKE $like_e OR p.hsn_code LIKE $like_e
                              OR p.style_code = $q_e OR p.style_code LIKE $like_e
                              OR p.design_no = $q_e OR p.design_no LIKE $like_e
                              OR EXISTS (SELECT 1 FROM garment_variants gv WHERE gv.product_id = p.id AND (gv.barcode = $q_e OR gv.variant_sku = $q_e OR gv.barcode LIKE $like_e OR gv.variant_sku LIKE $like_e)))";
            }
        }
        if ($category) {
            $sql .= " AND p.category_id = " . (int)$category;
        }
        if ($brand) {
            $sql .= " AND p.brand_id = " . (int)$brand;
        }

        $sql .= " GROUP BY p.id ORDER BY p.name ASC LIMIT " . (int)$limit;

        $rows = $this->db->query($sql)->result();

        // Stock status flag
        foreach ($rows as &$row) {
            $s = (float)$row->stock_qty;
            if ($s <= 0)                          $row->stock_status = 'out';
            elseif ($s <= (float)$row->min_stock) $row->stock_status = 'low';
            else                                   $row->stock_status = 'ok';
        }

        // Also send filter options (categories + brands) on empty query for initial modal
        $extra = [];
        if ($q === '' && !$category && !$brand) {
            $extra['categories'] = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('categories')->result();
            $extra['brands']     = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('brands')->result();
        }

        echo json_encode(['success' => true, 'products' => $rows, 'count' => count($rows)] + $extra);
    }

    // ─── Bulk Price Update Template Download ────────────────────────────────
    public function bulk_price_template()
    {
        $products = $this->db->select('item_code, name, mrp, selling_price')
                             ->where('status', 1)->order_by('item_code')->get('products')->result();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="bulk_price_template_' . date('Ymd') . '.csv"');
        $fh = fopen('php://output', 'w');
        fputcsv($fh, ['item_code', 'item_name', 'mrp', 'sp']);
        foreach ($products as $p) {
            fputcsv($fh, [$p->item_code, $p->name, $p->mrp, $p->selling_price]);
        }
        fclose($fh);
    }

    // ─── Bulk Price Update ────────────────────────────────────────────────────
    public function bulk_price_update()
    {
        $data['page_title'] = 'Bulk Price Update';
        $this->render('products/bulk_price_update', $data);
    }

    public function bulk_price_update_ajax()
    {
        header('Content-Type: application/json');

        if (empty($_FILES['price_file']['tmp_name']) || $_FILES['price_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Upload error: ' . ($_FILES['price_file']['error'] ?? 'no file')]);
            return;
        }

        $ext = strtolower(pathinfo($_FILES['price_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            echo json_encode(['success' => false, 'message' => 'Only .xlsx, .xls or .csv files allowed.']);
            return;
        }

        $rows   = [];
        $errors = [];

        if ($ext === 'csv') {
            $fh     = fopen($_FILES['price_file']['tmp_name'], 'r');
            $header = array_map(fn($h) => strtolower(trim($h)), fgetcsv($fh));
            $ci     = array_flip($header);
            if (!isset($ci['item_code'])) { echo json_encode(['success'=>false,'message'=>'CSV missing item_code column.']); return; }
            while (($line = fgetcsv($fh)) !== false) {
                $code = trim($line[$ci['item_code']] ?? '');
                if ($code === '') continue;
                $rows[] = [
                    'item_code' => $code,
                    'item_name' => trim($line[$ci['item_name'] ?? -1] ?? ''),
                    'mrp'       => isset($ci['mrp'])  ? $line[$ci['mrp']]  : null,
                    'sp'        => isset($ci['sp'])   ? $line[$ci['sp']]   : null,
                ];
            }
            fclose($fh);
        } else {
            if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                require_once APPPATH . '../vendor/autoload.php';
            }
            try {
                $sheet    = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['price_file']['tmp_name'])->getActiveSheet();
                $col_code = $col_name = $col_mrp = $col_sp = null;
                foreach ($sheet->getRowIterator() as $row_idx => $row) {
                    $cells = [];
                    foreach ($row->getCellIterator() as $ci => $cell) {
                        $cells[$ci] = $cell->getValue();
                    }
                    if ($row_idx === 1) {
                        foreach ($cells as $ci => $val) {
                            $v = strtolower(trim((string)$val));
                            if ($v === 'item_code') $col_code = $ci;
                            if ($v === 'item_name') $col_name = $ci;
                            if ($v === 'mrp')       $col_mrp  = $ci;
                            if ($v === 'sp')        $col_sp   = $ci;
                        }
                        if (!$col_code) { $errors[] = 'Excel missing item_code header.'; break; }
                        continue;
                    }
                    $code = trim((string)($cells[$col_code] ?? ''));
                    if ($code === '') continue;
                    $rows[] = [
                        'item_code' => $code,
                        'item_name' => $col_name ? trim((string)($cells[$col_name] ?? '')) : '',
                        'mrp'       => $col_mrp  ? $cells[$col_mrp]  : null,
                        'sp'        => $col_sp   ? $cells[$col_sp]   : null,
                    ];
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Could not read file: ' . $e->getMessage()]);
                return;
            }
        }

        if (!empty($errors)) { echo json_encode(['success'=>false,'message'=>implode(' | ',$errors)]); return; }
        if (empty($rows))    { echo json_encode(['success'=>false,'message'=>'No data rows found.']); return; }

        $updated = $not_found = $skipped = 0;
        $preview = [];

        foreach ($rows as $row) {
            $product = $this->db->where('item_code', $row['item_code'])->get('products')->row();
            if (!$product) { $not_found++; continue; }

            $update = [];
            if ($row['mrp'] !== null && $row['mrp'] !== '') $update['mrp']           = (float)$row['mrp'];
            if ($row['sp']  !== null && $row['sp']  !== '') $update['selling_price']  = (float)$row['sp'];
            if ($row['item_name'] !== '')                   $update['name']           = $row['item_name'];

            if (empty($update)) { $skipped++; continue; }

            $this->db->where('item_code', $row['item_code'])->update('products', $update);
            $updated++;

            if (count($preview) < 5) {
                $preview[] = $row['item_code'] . ' → MRP:' . ($update['mrp'] ?? '-') . ' SP:' . ($update['selling_price'] ?? '-');
            }
        }

        $this->log_activity('Products', 'bulk_price_update', "Bulk price update: {$updated} updated, {$not_found} not found");

        echo json_encode([
            'success'   => true,
            'updated'   => $updated,
            'not_found' => $not_found,
            'skipped'   => $skipped,
            'preview'   => $preview,
            'message'   => "{$updated} products updated." . ($not_found ? " {$not_found} item codes not found." : '') . ($skipped ? " {$skipped} skipped (no price data)." : ''),
        ]);
    }

    public function fix_barcode_item_codes() {
        if ($this->session->userdata('role') !== 'superadmin') {
            echo 'Superadmin only.'; return;
        }
        $max_normal = $this->db->query(
            "SELECT MAX(CAST(item_code AS UNSIGNED)) as m FROM products WHERE item_code REGEXP '^[0-9]+$' AND LENGTH(item_code) <= 6"
        )->row()->m;
        $next = max((int)$max_normal + 1, 100001);

        $bad = $this->db->query(
            "SELECT id, item_code, name FROM products WHERE item_code REGEXP '^[0-9]+$' AND LENGTH(item_code) > 6 ORDER BY id ASC"
        )->result();

        if (empty($bad)) { echo 'No barcode-length item codes found. All good!'; return; }

        $fixed = [];
        foreach ($bad as $p) {
            $old = $p->item_code;
            $this->db->where('id', $p->id)->update('products', ['item_code' => (string)$next]);
            $fixed[] = "#{$p->id} {$p->name}: {$old} → {$next}";
            $next++;
        }
        echo '<h3>Fixed ' . count($fixed) . ' products:</h3><ul>';
        foreach ($fixed as $f) echo '<li>' . htmlspecialchars($f) . '</li>';
        echo '</ul><p>Next new item code will be: ' . $next . '</p>';
    }

    private function _save_garment_variants($product_id, $product_data) {
        $sizes = $this->input->post('var_size');
        if (empty($sizes) || !is_array($sizes)) {
            return;
        }

        $variant_ids     = $this->input->post('var_id');
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
        $style_code = !empty($product_data['style_code']) ? $product_data['style_code'] : ($product_data['item_code'] ?? 'GARMENT');

        foreach ($sizes as $idx => $size_name) {
            $vid       = !empty($variant_ids[$idx]) ? (int)$variant_ids[$idx] : 0;
            $col_name  = trim($colours[$idx] ?? 'Standard');
            $size_name = trim($size_name);
            if ($size_name === '') continue;

            $barcode = trim($barcodes[$idx] ?? '');
            if (empty($barcode)) {
                $barcode = $this->Garment_model->generate_barcode($style_code, $col_name, $size_name);
            }

            $sku = trim($skus[$idx] ?? '');
            if (empty($sku)) {
                $sku = $this->Garment_model->generate_sku($style_code, $col_name, $size_name);
            }

            $mrp_val  = isset($mrps[$idx]) && $mrps[$idx] !== '' ? (float)$mrps[$idx] : (float)($product_data['mrp'] ?? 0);
            $sp_val   = isset($sps[$idx]) && $sps[$idx] !== '' ? (float)$sps[$idx] : (float)($product_data['selling_price'] ?? 0);
            $cost_val = isset($costs[$idx]) && $costs[$idx] !== '' ? (float)$costs[$idx] : (float)($product_data['cost_price'] ?? 0);
            $min_val  = isset($min_stocks[$idx]) && $min_stocks[$idx] !== '' ? (float)$min_stocks[$idx] : 2;
            $reorder  = isset($reorders[$idx]) && $reorders[$idx] !== '' ? (float)$reorders[$idx] : 5;
            $stat_val = isset($statuses[$idx]) ? (int)$statuses[$idx] : 1;

            $var_row = [
                'product_id'     => $product_id,
                'variant_sku'    => $sku,
                'size_id'        => !empty($size_ids[$idx]) ? (int)$size_ids[$idx] : null,
                'size_name'      => $size_name,
                'colour_id'      => !empty($colour_ids[$idx]) ? (int)$colour_ids[$idx] : null,
                'colour_name'    => $col_name,
                'barcode'        => $barcode,
                'mfr_article_no' => $product_data['mfr_article_no'] ?? null,
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
}
