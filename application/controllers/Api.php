<?php
// ============================================================
// FILE: application/controllers/Api.php
// Read-only API for external local apps (e.g. the .NET barcode
// label-printing app). Auth: static API key, header X-API-KEY
// (or ?api_key= query param), checked against company_settings
// row 'barcode_api_key' (managed from Settings > Barcode Printer).
//
// The .NET app is responsible for the actual printing (talking
// to the Windows print spooler / TSC printer locally). This API
// only supplies the data: product fields + raw .prn templates
// with @PLACEHOLDER tokens for the app to substitute and send.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Product_model');
        $this->_require_api_key();
    }

    // ── API key check ────────────────────────────────────────────────────────
    private function _require_api_key() {
        $sent = $this->input->get_request_header('X-API-KEY', TRUE);
        if (!$sent) $sent = $this->input->get('api_key');
        if (!$sent) $sent = $this->input->post('api_key');

        $row = $this->db->where('key_name', 'barcode_api_key')->get('company_settings')->row();
        $stored = $row ? $row->key_value : '';

        if (!$stored || !$sent || !hash_equals($stored, $sent)) {
            $this->_json(['success' => false, 'message' => 'Invalid or missing API key.'], 401);
            exit;
        }
    }

    private function _json($payload, $status = 200) {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    // ── GET api/ping ─────────────────────────────────────────────────────────
    public function ping() {
        $this->_json(['success' => true, 'message' => 'pong', 'server_time' => date('Y-m-d H:i:s')]);
    }

    // ── GET api/barcode_settings ─────────────────────────────────────────────
    public function barcode_settings() {
        $company = $this->db->get('company_settings')->result();
        $kv = [];
        foreach ($company as $r) $kv[$r->key_name] = $r->key_value;

        $this->_json([
            'success' => true,
            'data' => [
                'company_name'   => $kv['company_name'] ?? '',
                'tsc_printer_name_legacy' => $kv['tsc_printer_name'] ?? '',
                'server_date'    => date('Y-m-d'),
                'server_time'    => date('H:i:s'),
            ],
        ]);
    }

    // ── GET api/barcode_search?q=&limit= ─────────────────────────────────────
    // Type-ahead search for the .NET app's product picker.
    // ── GET api/barcode_search?q=&size=&colour=&limit= ──────────────────────
    // Type-ahead search for the .NET app's product & variant picker.
    public function barcode_search() {
        $q      = trim($this->input->get('q') ?: '');
        $size   = trim($this->input->get('size') ?: '');
        $colour = trim($this->input->get('colour') ?: '');
        $limit  = min((int)($this->input->get('limit') ?: 50), 100);

        // Fetch garment variants matching filters
        $this->db->select("
                gv.id,
                gv.id AS variant_id,
                p.id  AS product_id,
                p.item_code,
                p.name AS product_name,
                p.name,
                COALESCE(gv.size_name, '')   AS size_name,
                COALESCE(gv.colour_name, '') AS colour_name,
                COALESCE(gv.barcode, '')     AS barcode,
                COALESCE(gv.barcode, '')     AS pcs_barcode,
                COALESCE(gv.variant_sku, '') AS variant_sku,
                COALESCE(p.unit, 'PCS')      AS unit,
                COALESCE(gv.mrp, p.mrp, 0)   AS mrp,
                COALESCE(gv.selling_price, p.selling_price, 0) AS selling_price,
                GREATEST(COALESCE(gv.stock_qty, 0), 0)         AS stock_qty,
                COALESCE(p.brand, '')        AS brand,
                COALESCE(p.hsn_code, '')     AS hsn_code,
                COALESCE(p.category, '')     AS category,
                1 AS is_variant
            ", false)
            ->from('garment_variants gv')
            ->join('products p', 'p.id = gv.product_id')
            ->where('p.status', 1)
            ->where('gv.status', 1);

        if ($size !== '' && $size !== 'ALL') {
            $this->db->where('gv.size_name', $size);
        }
        if ($colour !== '' && $colour !== 'ALL') {
            $this->db->where('gv.colour_name', $colour);
        }

        if ($q !== '') {
            $this->db->group_start()
                ->like('p.name', $q)
                ->or_like('p.item_code', $q)
                ->or_like('gv.barcode', $q)
                ->or_like('gv.variant_sku', $q)
                ->or_like('gv.size_name', $q)
                ->or_like('gv.colour_name', $q)
                ->group_end();
        }

        $variant_rows = $this->db->order_by('p.name ASC, gv.colour_name ASC, gv.size_name ASC')
            ->limit($limit)
            ->get()
            ->result();

        // Also fetch distinct sizes and colours for filter dropdowns/matrix
        $sizes_db = $this->db->select('DISTINCT size_name', false)
            ->from('garment_variants')
            ->where('status', 1)
            ->where("size_name != ''")
            ->order_by('size_name ASC')
            ->get()->result_array();
        $available_sizes = array_column($sizes_db, 'size_name');

        $colours_db = $this->db->select('DISTINCT colour_name', false)
            ->from('garment_variants')
            ->where('status', 1)
            ->where("colour_name != ''")
            ->order_by('colour_name ASC')
            ->get()->result_array();
        $available_colours = array_column($colours_db, 'colour_name');

        $this->_json([
            'success'          => true,
            'count'            => count($variant_rows),
            'data'             => $variant_rows,
            'available_sizes'   => $available_sizes,
            'available_colours' => $available_colours
        ]);
    }

    // ── GET api/barcode_product/{id} ─────────────────────────────────────────
    // Full data needed to print a label for one variant or product.
    public function barcode_product($id = null) {
        $id = (int)$id;
        if (!$id) {
            $this->_json(['success' => false, 'message' => 'Variant or Product id required.'], 400);
            return;
        }

        // First check if $id matches a garment_variants row
        $variant = $this->db->select('gv.*, p.name AS product_name, p.item_code AS base_item_code, p.unit, p.hsn_code, p.category, p.brand, p.ingredients, p.expiry_days')
            ->from('garment_variants gv')
            ->join('products p', 'p.id = gv.product_id')
            ->where('gv.id', $id)
            ->get()
            ->row();

        if ($variant) {
            $pcs_barcode = !empty($variant->barcode) ? $variant->barcode : (!empty($variant->variant_sku) ? $variant->variant_sku : (string)$variant->id);
            $this->_json([
                'success' => true,
                'data' => [
                    'id'             => (int)$variant->id,
                    'variant_id'     => (int)$variant->id,
                    'product_id'     => (int)$variant->product_id,
                    'item_code'      => $variant->variant_sku ?: $variant->base_item_code,
                    'name'           => $variant->product_name,
                    'size_name'      => $variant->size_name ?: '',
                    'colour_name'    => $variant->colour_name ?: '',
                    'variant_sku'    => $variant->variant_sku ?: '',
                    'unit'           => $variant->unit ?: 'PCS',
                    'carton_size'    => 1,
                    'mrp'            => number_format((float)$variant->mrp, 2, '.', ''),
                    'selling_price'  => number_format((float)$variant->selling_price, 2, '.', ''),
                    'purchase_price' => number_format((float)$variant->purchase_price, 2, '.', ''),
                    'gst_percent'    => (float)($variant->sales_gst_rate ?: 5.00),
                    'hsn_code'       => $variant->hsn_code ?: '',
                    'pcs_barcode'    => $pcs_barcode,
                    'crt_barcode'    => '',
                    'crt_qty_multiplier' => 1,
                    'ingredients'    => $variant->ingredients ?: '',
                    'expiry_days'    => (int)($variant->expiry_days ?: 0),
                    'category'       => $variant->category ?: '',
                    'brand'          => $variant->brand ?: 'SUVASAN',
                    'stock_qty'      => max(0, (float)$variant->stock_qty),
                ],
            ]);
            return;
        }

        // Fallback to checking by products table id
        $product = $this->Product_model->get_by_id($id);
        if (!$product) {
            $this->_json(['success' => false, 'message' => 'Product or variant not found.'], 404);
            return;
        }

        $barcodes = $this->db->where('product_id', $id)->get('product_barcodes')->result();
        $pcs_barcode = '';
        $crt_barcode = '';
        $crt_multiplier = 1;
        foreach ($barcodes as $bc) {
            if ($bc->unit_type === 'PCS') $pcs_barcode = $bc->barcode;
            if ($bc->unit_type === 'CRT') { $crt_barcode = $bc->barcode; $crt_multiplier = (int)$bc->qty_multiplier; }
        }
        if ($pcs_barcode === '') {
            $pcs_barcode = !empty($product->item_code) ? $product->item_code : (string)$product->id;
        }

        $this->_json([
            'success' => true,
            'data' => [
                'id'             => (int)$product->id,
                'variant_id'     => 0,
                'product_id'     => (int)$product->id,
                'item_code'      => $product->item_code,
                'name'           => $product->name,
                'size_name'      => '',
                'colour_name'    => '',
                'variant_sku'    => $product->item_code,
                'unit'           => $product->unit ?: 'PCS',
                'carton_size'    => (int)$product->carton_size,
                'mrp'            => number_format((float)$product->mrp, 2, '.', ''),
                'selling_price'  => number_format((float)$product->selling_price, 2, '.', ''),
                'purchase_price' => number_format((float)$product->purchase_price, 2, '.', ''),
                'gst_percent'    => (float)$product->gst_percent,
                'hsn_code'       => $product->hsn_code_display ?: $product->hsn_code,
                'pcs_barcode'    => $pcs_barcode,
                'crt_barcode'    => $crt_barcode,
                'crt_qty_multiplier' => $crt_multiplier,
                'ingredients'    => $product->ingredients ?: '',
                'expiry_days'    => (int)($product->expiry_days ?: 0),
                'category'       => $product->category_name ?: '',
                'brand'          => $product->brand_name ?: 'SUVASAN',
                'stock_qty'      => max(0, (float)$product->stock_qty),
            ],
        ]);
    }

    // ── GET api/barcode_templates ─────────────────────────────────────────────
    // Lists every .prn/.txt template with its raw content + placeholder tokens.
    public function barcode_templates() {
        $dir = FCPATH . 'prn_templates/';
        $templates = [];

        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                $path = $dir . $file;
                if (!is_file($path)) continue;
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($ext, ['prn', 'txt'])) continue;

                $content = file_get_contents($path);
                preg_match_all('/@[A-Z0-9_]+/', $content, $m);

                $templates[] = [
                    'name'         => $file,
                    'content'      => $content,
                    'placeholders' => array_values(array_unique($m[0])),
                ];
            }
        }

        $this->_json(['success' => true, 'count' => count($templates), 'data' => $templates]);
    }

    // ── GET api/barcode_template/{name} ──────────────────────────────────────
    public function barcode_template($name = null) {
        $name = basename((string)$name);
        $path = FCPATH . 'prn_templates/' . $name;

        if ($name === '' || !file_exists($path)) {
            $this->_json(['success' => false, 'message' => 'Template not found.'], 404);
            return;
        }

        $content = file_get_contents($path);
        preg_match_all('/@[A-Z0-9_]+/', $content, $m);

        $this->_json([
            'success' => true,
            'data' => [
                'name'         => $name,
                'content'      => $content,
                'placeholders' => array_values(array_unique($m[0])),
            ],
        ]);
    }

    // ── POST api/barcode_log ─────────────────────────────────────────────────
    // Fire-and-forget: lets the .NET app record what it printed, for the audit log.
    public function barcode_log() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->_json(['success' => false, 'message' => 'POST required.'], 405);
            return;
        }

        $product_id   = (int)$this->input->post('product_id', TRUE);
        $product_name = trim($this->input->post('product_name', TRUE));
        $template     = trim($this->input->post('template', TRUE));
        $qty          = (int)$this->input->post('qty', TRUE);
        $printer_name = trim($this->input->post('printer_name', TRUE));
        $status       = trim($this->input->post('status', TRUE)) ?: 'success';
        $error_msg    = trim($this->input->post('error_msg', TRUE));

        $desc = "Printed {$qty} barcode label(s) of '{$product_name}' (ID #{$product_id}) using template '{$template}' via local .NET app to printer '{$printer_name}'";
        if ($status !== 'success') $desc .= " — FAILED: {$error_msg}";

        $this->log_activity('mod_item_master', 'tsc_print_local', $desc, $product_id, '', 0, 'Barcode App');

        $this->_json(['success' => true]);
    }
}
