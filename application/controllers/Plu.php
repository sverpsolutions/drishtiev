<?php
// ============================================================
// FILE: application/controllers/Plu.php
// PLU (Price Look-Up) Management Controller
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Plu extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Plu_model', 'Product_model', 'Supplier_model', 'Location_model']);
        $this->load->library('form_validation');
    }

    // ── List PLUs ─────────────────────────────────────────────
    public function index() {
        $filters = [
            'search'      => $this->input->get('search'),
            'product_id'  => $this->input->get('product_id'),
            'status'      => $this->input->get('status'),
            'location_id' => $this->active_location_id(),
        ];
        $data['page_title'] = 'PLU (Price Look-Up) Master';
        $data['plus']       = $this->Plu_model->get_all($filters);
        $data['products']   = $this->Product_model->get_all();
        $data['filters']    = $filters;
        $this->render('plu/index', $data);
    }

    // ── Create PLU ────────────────────────────────────────────
    public function create() {
        $data['page_title'] = 'Create PLU';
        $data['products']   = $this->Product_model->get_all();
        $data['suppliers']  = $this->Supplier_model->get_all_for_select();
        $data['locations']  = $this->Location_model->get_all();
        $data['selected_product'] = null;

        if ($this->input->get('product_id')) {
            $data['selected_product'] = $this->Product_model->get_by_id($this->input->get('product_id'));
            $data['existing_plus']    = $this->Plu_model->get_by_product($this->input->get('product_id'));
        }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('plu/create', $data);
            return;
        }

        $this->form_validation->set_rules('product_id',   'Product',       'required|integer');
        $this->form_validation->set_rules('mrp',          'MRP',           'required|decimal');
        $this->form_validation->set_rules('selling_rate', 'Selling Rate',  'required|decimal');
        $this->form_validation->set_rules('purchase_rate','Purchase Rate', 'required|decimal');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors(' | '));
            $this->render('plu/create', $data);
            return;
        }

        $product_id = (int)$this->input->post('product_id');
        $mrp        = (float)$this->input->post('mrp');

        // Check if same MRP already exists
        $existing = $this->Plu_model->find_by_product_mrp($product_id, $mrp);
        if ($existing) {
            $this->session->set_flashdata('error', "A PLU with MRP ₹{$mrp} already exists for this product ({$existing->plu_code}). Edit that PLU instead.");
            $this->render('plu/create', $data);
            return;
        }

        $insert = [
            'product_id'    => $product_id,
            'barcode'       => $this->input->post('barcode', true),
            'batch_no'      => $this->input->post('batch_no', true),
            'mrp'           => $mrp,
            'purchase_rate' => (float)$this->input->post('purchase_rate'),
            'selling_rate'  => (float)$this->input->post('selling_rate'),
            'landing_cost'  => (float)$this->input->post('landing_cost'),
            'gst_percent'   => (float)$this->input->post('gst_percent'),
            'mfg_date'      => $this->input->post('mfg_date') ?: null,
            'expiry_date'   => $this->input->post('expiry_date') ?: null,
            'supplier_id'   => $this->input->post('supplier_id') ?: null,
            'location_id'   => $this->input->post('location_id') ?: null,
            'stock_qty'     => (float)$this->input->post('stock_qty'),
            'status'        => 1,
        ];

        $id = $this->Plu_model->create($insert);
        if ($id) {
            $this->session->set_flashdata('success', 'PLU created successfully.');
        } else {
            $this->session->set_flashdata('error', 'Failed to create PLU.');
        }
        redirect('plu?product_id=' . $product_id);
    }

    // ── Edit PLU ──────────────────────────────────────────────
    public function edit($id = null) {
        if (!$id) redirect('plu');
        $plu = $this->Plu_model->get_by_id($id);
        if (!$plu) { $this->session->set_flashdata('error', 'PLU not found.'); redirect('plu'); }

        $data['page_title'] = 'Edit PLU: ' . $plu->plu_code;
        $data['plu']        = $plu;
        $data['products']   = $this->Product_model->get_all();
        $data['suppliers']  = $this->Supplier_model->get_all_for_select();
        $data['locations']  = $this->Location_model->get_all();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('plu/edit', $data);
            return;
        }

        $update = [
            'barcode'       => $this->input->post('barcode', true),
            'batch_no'      => $this->input->post('batch_no', true),
            'mrp'           => (float)$this->input->post('mrp'),
            'purchase_rate' => (float)$this->input->post('purchase_rate'),
            'selling_rate'  => (float)$this->input->post('selling_rate'),
            'landing_cost'  => (float)$this->input->post('landing_cost'),
            'gst_percent'   => (float)$this->input->post('gst_percent'),
            'mfg_date'      => $this->input->post('mfg_date') ?: null,
            'expiry_date'   => $this->input->post('expiry_date') ?: null,
            'supplier_id'   => $this->input->post('supplier_id') ?: null,
            'location_id'   => $this->input->post('location_id') ?: null,
            'status'        => (int)$this->input->post('status'),
        ];

        $this->Plu_model->update($id, $update);
        $this->session->set_flashdata('success', 'PLU updated.');
        redirect('plu?product_id=' . $plu->product_id);
    }

    // ── Toggle Status ─────────────────────────────────────────
    public function toggle_status($id = null) {
        if (!$id) redirect('plu');
        $plu = $this->Plu_model->get_by_id($id);
        if ($plu) {
            $this->Plu_model->update($id, ['status' => $plu->status ? 0 : 1]);
        }
        redirect('plu?product_id=' . ($plu->product_id ?? ''));
    }

    // ── AJAX: Barcode lookup (returns JSON for billing) ───────
    public function get_by_barcode_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $barcode     = $this->input->post('barcode', true);
        $location_id = $this->active_location_id();
        $plus        = $this->Plu_model->get_by_barcode($barcode, $location_id);

        header('Content-Type: application/json');

        if (empty($plus)) {
            // Fall back to legacy product barcode lookup
            $product = $this->Product_model->get_by_barcode($barcode);
            if ($product) {
                echo json_encode([
                    'success' => true,
                    'mode'    => 'legacy',
                    'product' => $product,
                    'plus'    => [],
                    'count'   => 0,
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Barcode not found']);
            }
            return;
        }

        if (count($plus) === 1) {
            // Single PLU → auto-add
            $plu_data = $this->Plu_model->get_for_billing($plus[0]->id, $location_id);
            echo json_encode([
                'success' => true,
                'mode'    => 'single',
                'plu'     => $plu_data,
                'count'   => 1,
            ]);
        } else {
            // Multiple PLUs → show popup
            $plu_list = [];
            foreach ($plus as $p) {
                $plu_list[] = [
                    'id'           => $p->id,
                    'plu_code'     => $p->plu_code,
                    'item_name'    => $p->item_name,
                    'item_code'    => $p->item_code,
                    'mrp'          => (float)$p->mrp,
                    'selling_rate' => (float)$p->selling_rate,
                    'stock_qty'    => (float)$p->stock_qty,
                    'batch_no'     => $p->batch_no,
                    'expiry_date'  => $p->expiry_date,
                    'gst_percent'  => (float)$p->gst_percent,
                    'unit'         => $p->unit,
                ];
            }
            echo json_encode([
                'success'   => true,
                'mode'      => 'multiple',
                'plus'      => $plu_list,
                'item_name' => $plus[0]->item_name,
                'barcode'   => $barcode,
                'count'     => count($plus),
            ]);
        }
    }

    // ── AJAX: Get PLU details for billing ─────────────────────
    public function get_plu_ajax($id = null) {
        if (!$id || !$this->input->is_ajax_request()) show_404();
        $plu = $this->Plu_model->get_for_billing($id, $this->active_location_id());
        header('Content-Type: application/json');
        echo json_encode($plu ?: ['error' => 'Not found']);
    }

    // ── AJAX: Check MRP during purchase entry ─────────────────
    public function check_mrp_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $product_id = (int)$this->input->post('product_id');
        $mrp        = (float)$this->input->post('mrp');
        $existing   = $this->Plu_model->find_by_product_mrp($product_id, $mrp);
        $all_plus   = $this->Plu_model->get_by_product($product_id);

        header('Content-Type: application/json');
        echo json_encode([
            'exists'      => $existing ? true : false,
            'plu_code'    => $existing ? $existing->plu_code : null,
            'plu_count'   => count($all_plus),
            'existing_mrps' => array_column($all_plus, 'mrp'),
        ]);
    }

    // ==========================================================
    // EXCEL / CSV IMPORT
    // ----------------------------------------------------------
    // Two-step (preview → confirm) like the product importer, because a PLU
    // carries MRP and selling rate: a bad file silently repriced at the till
    // is far more damaging than a bad product name.
    // ==========================================================

    // Column aliases, so the user's own sheet layout usually just works.
    private function _plu_col_map() {
        return [
            'plu_code'      => ['plu code','plu_code','plu'],
            'item_code'     => ['item code','item_code','sku','code','product code'],
            'name'          => ['name','item name','product name','description','item description'],
            'barcode'       => ['barcode','pcs barcode','ean','bar code'],
            'batch_no'      => ['batch','batch no','batch_no','lot','lot no'],
            'mrp'           => ['mrp','max retail price','maximum retail price'],
            'selling_rate'  => ['selling rate','selling_rate','sale rate','sale price','selling price','sp','rate'],
            'purchase_rate' => ['purchase rate','purchase_rate','cost price','cost','purchase price','cp'],
            'landing_cost'  => ['landing cost','landing_cost','landing'],
            'gst_percent'   => ['gst %','gst%','gst percent','gst_percent','tax%','gst'],
            'mfg_date'      => ['mfg date','mfg_date','manufacture date','mfg'],
            'expiry_date'   => ['expiry date','expiry_date','expiry','exp date','best before'],
            'supplier'      => ['supplier','supplier name','vendor'],
            'location'      => ['location','store','outlet','branch'],
            'stock_qty'     => ['stock','stock qty','stock_qty','opening stock','qty','quantity'],
            'status'        => ['status','active'],
        ];
    }

    public function import() {
        $data['page_title'] = 'Import PLU Master from Excel / CSV';
        $data['col_map']    = $this->_plu_col_map();
        $this->render('plu/import', $data);
    }

    // ── Step 1: parse, match against the item master, classify ─
    public function import_preview() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('plu/import');

        if (empty($_FILES['import_file']['tmp_name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', 'No file uploaded, or the upload failed.');
            redirect('plu/import');
        }

        $ext = strtolower('.' . pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['.xlsx', '.xls', '.csv'])) {
            $this->session->set_flashdata('error', 'Only .xlsx, .xls or .csv files are allowed.');
            redirect('plu/import');
        }
        if ($_FILES['import_file']['size'] > 8 * 1024 * 1024) {
            $this->session->set_flashdata('error', 'File too large. Maximum 8 MB.');
            redirect('plu/import');
        }

        $tmp_dir = APPPATH . '../assets/tmp/';
        if (!is_dir($tmp_dir)) mkdir($tmp_dir, 0755, TRUE);
        $file_path = $tmp_dir . 'plu_import_' . time() . $ext;
        if (!move_uploaded_file($_FILES['import_file']['tmp_name'], $file_path)) {
            $this->session->set_flashdata('error', 'Could not save the uploaded file.');
            redirect('plu/import');
        }

        $errors = [];
        $raw = ($ext === '.csv') ? $this->_plu_parse_csv($file_path)
                                 : $this->_plu_parse_excel($file_path, $errors);
        @unlink($file_path);

        if (count($raw) < 2) {
            $this->session->set_flashdata('error', 'No data rows found. ' . implode(' ', $errors));
            redirect('plu/import');
        }

        $headers = array_map(function ($h) { return strtolower(trim((string)$h)); }, array_shift($raw));
        $col = [];
        foreach ($this->_plu_col_map() as $field => $aliases) {
            foreach ($aliases as $alias) {
                $pos = array_search($alias, $headers, TRUE);
                if ($pos !== FALSE) { $col[$field] = $pos; break; }
            }
        }

        if (!isset($col['mrp'])) {
            $this->session->set_flashdata('error', 'No "MRP" column found. MRP is what separates one PLU from another — download the template and use its headings.');
            redirect('plu/import');
        }
        if (!isset($col['plu_code']) && !isset($col['item_code']) && !isset($col['barcode']) && !isset($col['name'])) {
            $this->session->set_flashdata('error', 'The file has no PLU Code, Item Code, Barcode or Name column, so rows cannot be matched to a product.');
            redirect('plu/import');
        }

        // Classification lives in the model so it can be exercised directly
        // by tests — it decides what gets repriced at the till.
        $res     = $this->Plu_model->build_import_preview($raw, $col);
        $preview = $res['preview'];
        $counts  = $res['counts'];

        if (empty($preview)) {
            $this->session->set_flashdata('error', 'Every row in the file was blank.');
            redirect('plu/import');
        }

        $this->session->set_userdata('plu_import_preview', $preview);

        $data['page_title'] = 'PLU Import — Preview';
        $data['preview']    = $preview;
        $data['counts']     = $counts;
        $data['filename']   = $_FILES['import_file']['name'];
        $this->render('plu/import_preview', $data);
    }

    // ── Step 2: write the good rows ───────────────────────────
    public function import_confirm() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('plu/import');

        $preview = $this->session->userdata('plu_import_preview');
        if (empty($preview)) {
            $this->session->set_flashdata('error', 'The preview expired. Please upload the file again.');
            redirect('plu/import');
        }

        $update_existing = (int)$this->input->post('update_existing') === 1;
        $import_stock    = (int)$this->input->post('import_stock') === 1;

        $inserted = $updated = $skipped = 0;
        $issues   = [];

        $this->db->trans_begin();
        foreach ($preview as $r) {
            if ($r['action'] === 'error') {
                $issues[] = [$r['line'], $r['plu_code'], $r['item_code'], $r['product_name'],
                             $r['mrp'], 'Rejected', implode('; ', $r['errors'])];
                $skipped++;
                continue;
            }
            if ($r['action'] === 'update' && !$update_existing) {
                $issues[] = [$r['line'], $r['plu_code'], $r['item_code'], $r['product_name'],
                             $r['mrp'], 'Skipped', 'Already exists and "update existing" was not ticked'];
                $skipped++;
                continue;
            }

            if ($r['action'] === 'new') {
                $row = [
                    'product_id'    => $r['product_id'],
                    'barcode'       => $r['barcode'] ?: null,
                    'batch_no'      => $r['batch_no'] ?: null,
                    'mrp'           => $r['mrp'],
                    'purchase_rate' => $r['purchase_rate'],
                    'selling_rate'  => $r['selling_rate'],
                    'landing_cost'  => $r['landing_cost'] ?: $r['purchase_rate'],
                    'gst_percent'   => $r['gst_percent'],
                    'mfg_date'      => $r['mfg_date'],
                    'expiry_date'   => $r['expiry_date'],
                    'supplier_id'   => $r['supplier_id'],
                    'location_id'   => $r['location_id'],
                    'stock_qty'     => $import_stock ? $r['stock_qty'] : 0,
                    'status'        => $r['status'],
                ];
                if ($this->Plu_model->create($row)) $inserted++;
                continue;
            }

            // UPDATE — a blank cell means "leave the current value", so only
            // fields actually present in the sheet are written.
            $row = ['mrp' => $r['mrp'], 'status' => $r['status']];
            foreach (['barcode', 'batch_no'] as $f) {
                if ($r[$f] !== '') $row[$f] = $r[$f];
            }
            foreach (['selling_rate', 'purchase_rate', 'landing_cost', 'gst_percent'] as $f) {
                if ($r[$f] > 0) $row[$f] = $r[$f];
            }
            foreach (['mfg_date', 'expiry_date'] as $f) {
                if ($r[$f]) $row[$f] = $r[$f];
            }
            if ($r['supplier_id']) $row['supplier_id'] = $r['supplier_id'];
            if ($r['location_id']) $row['location_id'] = $r['location_id'];
            // Stock is deliberately opt-in: overwriting live stock from a price
            // sheet would wipe out counted quantities.
            if ($import_stock && $r['has_stock_col']) $row['stock_qty'] = $r['stock_qty'];

            $this->Plu_model->update($r['plu_id'], $row);
            $updated++;
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Import failed — nothing was saved.');
            redirect('plu/import');
        }
        $this->db->trans_commit();

        $this->session->unset_userdata('plu_import_preview');
        $this->session->set_userdata('plu_import_issues', $issues);
        $this->log_activity('PLU', 'import', "Imported PLUs: {$inserted} new, {$updated} updated, {$skipped} skipped");

        $msg = "Import complete: {$inserted} PLU(s) created, {$updated} updated.";
        if ($skipped) $msg .= " {$skipped} row(s) not imported — download the issues file to see why.";
        $this->session->set_flashdata($skipped ? 'warning' : 'success', $msg);
        redirect('plu');
    }

    // ── Rejected rows as a branded .xlsx ──────────────────────
    public function import_issues() {
        $issues = $this->session->userdata('plu_import_issues');
        if (empty($issues)) {
            $this->session->set_flashdata('error', 'No import issues to download.');
            redirect('plu');
        }
        $this->load->library('Excel_report');
        $this->excel_report->download(
            'PLU Import — Rows Not Imported',
            ['Excel Row', 'PLU Code', 'Item Code', 'Product / Lookup Value', 'MRP', 'Outcome', 'Reason'],
            $issues,
            [
                'subtitle' => 'Fix these rows in your sheet and import again',
                'filename' => 'plu_import_issues_' . date('Ymd_His'),
                'summary'  => ['Rows not imported' => count($issues)],
            ]
        );
    }

    // ── Ready-to-fill template ────────────────────────────────
    public function import_template() {
        $headers = ['PLU Code', 'Item Code', 'Name', 'Barcode', 'Batch No', 'MRP',
                    'Selling Rate', 'Purchase Rate', 'Landing Cost', 'GST %',
                    'Mfg Date', 'Expiry Date', 'Supplier', 'Location', 'Stock Qty', 'Status'];
        $sample  = ['', 'ITM000123', 'Parle-G Biscuit 100g', '8901719110192', 'B-2026-01',
                    '10.00', '10.00', '7.50', '7.50', '5', '2026-01-15', '2027-01-14',
                    '', '', '0', '1'];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="plu_import_template.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // BOM, or Excel mangles the file
        fputcsv($out, $headers);
        fputcsv($out, $sample);
        fclose($out);
        exit;
    }

    // ── Parsing helpers ───────────────────────────────────────
    private function _plu_parse_csv($path) {
        $rows = [];
        if (($fh = fopen($path, 'r')) !== FALSE) {
            while (($row = fgetcsv($fh)) !== FALSE) $rows[] = $row;
            fclose($fh);
        }
        // Strip a UTF-8 BOM off the first heading, or the match fails.
        if (isset($rows[0][0])) $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0]);
        return $rows;
    }

    private function _plu_parse_excel($path, &$errors) {
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            $autoload = APPPATH . '../vendor/autoload.php';
            if (!file_exists($autoload)) {
                $errors[] = 'The Excel library is not installed on this server — please upload a .csv instead.';
                return [];
            }
            require_once $autoload;
        }
        try {
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
            return $sheet->toArray(null, TRUE, TRUE, FALSE);
        } catch (\Exception $e) {
            $errors[] = 'Could not read the file: ' . $e->getMessage();
            return [];
        }
    }

}
