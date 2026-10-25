<?php
// ============================================================
// FILE: application/controllers/Hsn.php
// HSN Master CRUD + AJAX endpoint for billing form.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Hsn extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Hsn_model');
        $this->load->library('form_validation');
    }

    // List all HSN codes
    public function index() {
        $data['page_title'] = 'HSN Master';
        $data['hsn_list']   = $this->Hsn_model->get_all();
        $this->render('hsn/index', $data);
    }

    // Add new HSN code
    public function add() {
        $data['page_title'] = 'Add HSN Code';
        $this->_set_rules();

        if ($this->form_validation->run() === FALSE) {
            $this->render('hsn/add', $data);
            return;
        }

        $code = strtoupper(trim($this->input->post('hsn_code', TRUE)));
        if ($this->Hsn_model->hsn_code_exists($code)) {
            $this->session->set_flashdata('error', 'HSN code ' . $code . ' already exists.');
            $this->render('hsn/add', $data);
            return;
        }

        $this->Hsn_model->create([
            'hsn_code'     => $code,
            'description'  => $this->input->post('description', TRUE),
            'igst_percent' => $this->input->post('igst_percent', TRUE),
        ]);

        $this->session->set_flashdata('success', 'HSN code ' . $code . ' added. CGST/SGST auto-calculated.');
        redirect('hsn');
    }

    // Edit existing HSN code
    public function edit($id = null) {
        if (!$id) redirect('hsn');
        $data['page_title'] = 'Edit HSN Code';
        $data['hsn']        = $this->Hsn_model->get_by_id($id);
        if (!$data['hsn']) { $this->session->set_flashdata('error', 'HSN not found.'); redirect('hsn'); }

        $this->_set_rules();
        if ($this->form_validation->run() === FALSE) {
            $this->render('hsn/add', $data);
            return;
        }

        $code = strtoupper(trim($this->input->post('hsn_code', TRUE)));
        if ($this->Hsn_model->hsn_code_exists($code, $id)) {
            $this->session->set_flashdata('error', 'HSN code ' . $code . ' already used by another entry.');
            $this->render('hsn/add', $data);
            return;
        }

        $this->Hsn_model->update($id, [
            'hsn_code'     => $code,
            'description'  => $this->input->post('description', TRUE),
            'igst_percent' => $this->input->post('igst_percent', TRUE),
        ]);

        $this->session->set_flashdata('success', 'HSN code updated.');
        redirect('hsn');
    }

    // Delete (admin only)
    public function delete($id = null) {
        $this->require_admin();
        $this->Hsn_model->delete($id);
        $this->session->set_flashdata('success', 'HSN code deleted. Linked products HSN cleared.');
        redirect('hsn');
    }

    // Show import page
    public function import() {
        $data['page_title'] = 'Import HSN Codes';
        $this->render('hsn/import', $data);
    }

    // Parse uploaded file and show preview
    public function import_preview() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('hsn/import');

        if (empty($_FILES['import_file']['tmp_name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', 'No file uploaded or upload error.');
            redirect('hsn/import');
        }

        $orig_name = $_FILES['import_file']['name'];
        $ext       = strtolower('.' . pathinfo($orig_name, PATHINFO_EXTENSION));

        if (!in_array($ext, ['.xlsx', '.xls', '.csv'])) {
            $this->session->set_flashdata('error', 'Only .xlsx, .xls, or .csv files are allowed.');
            redirect('hsn/import');
        }

        $tmp_dir = APPPATH . '../assets/tmp/';
        if (!is_dir($tmp_dir)) mkdir($tmp_dir, 0755, TRUE);

        $file_path = $tmp_dir . 'hsn_import_' . time() . $ext;
        if (!move_uploaded_file($_FILES['import_file']['tmp_name'], $file_path)) {
            $this->session->set_flashdata('error', 'Failed to save uploaded file.');
            redirect('hsn/import');
        }

        $parse_errors = [];
        $raw = ($ext === '.csv') ? $this->_parse_csv($file_path) : $this->_parse_excel($file_path, $parse_errors);

        if (empty($raw)) {
            @unlink($file_path);
            $this->session->set_flashdata('error', 'No data found. ' . implode(' ', $parse_errors));
            redirect('hsn/import');
        }

        $headers = array_map(fn($h) => strtolower(trim($h)), array_shift($raw));
        $col_map = [
            'hsn_code'    => ['hsn code','hsn_code','hsn','code'],
            'description' => ['description','desc','name','product description'],
            'igst_percent'=> ['igst%','igst %','igst percent','igst_percent','gst%','gst %','gst','tax rate','rate%'],
        ];

        $col_idx = [];
        foreach ($col_map as $field => $aliases) {
            foreach ($aliases as $alias) {
                $pos = array_search($alias, $headers);
                if ($pos !== FALSE) { $col_idx[$field] = $pos; break; }
            }
        }

        if (!isset($col_idx['hsn_code'])) {
            @unlink($file_path);
            $this->session->set_flashdata('error', 'Column "hsn_code" not found. Check the template.');
            redirect('hsn/import');
        }

        $preview = [];
        foreach ($raw as $row) {
            $code = strtoupper(trim($row[$col_idx['hsn_code']] ?? ''));
            if (empty($code)) continue;

            // Fix Excel numeric HSN (e.g. "1905.0" -> "1905")
            if (preg_match('/^\d+\.0+$/', $code)) $code = preg_replace('/\.0+$/', '', $code);

            $desc  = isset($col_idx['description'])  ? trim($row[$col_idx['description']] ?? '')    : '';
            $igst  = isset($col_idx['igst_percent'])  ? (float)($row[$col_idx['igst_percent']] ?? 0) : 0;

            $error  = '';
            if (!preg_match('/^[A-Z0-9]{1,20}$/', $code)) $error = 'Invalid HSN code format.';
            if ($igst < 0 || $igst > 100)                 $error = 'IGST % must be 0–100.';

            $exists = $this->Hsn_model->hsn_code_exists($code);

            $preview[] = [
                'hsn_code'    => $code,
                'description' => $desc,
                'igst_percent'=> $igst,
                'cgst_percent'=> round($igst / 2, 2),
                'sgst_percent'=> round($igst / 2, 2),
                'exists'      => $exists,
                'error'       => $error,
            ];
        }

        $this->session->set_userdata('hsn_import_preview', $preview);
        $this->session->set_userdata('hsn_import_file',    $file_path);

        $data['page_title'] = 'HSN Import Preview';
        $data['preview']    = $preview;
        $this->render('hsn/import_preview', $data);
    }

    // Confirm and execute import
    public function import_confirm() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('hsn/import');

        $preview   = $this->session->userdata('hsn_import_preview') ?: [];
        $file_path = $this->session->userdata('hsn_import_file');
        $mode      = $this->input->post('mode') === 'update' ? 'update' : 'insert';

        $inserted = $updated = $skipped = $errors = 0;

        foreach ($preview as $row) {
            if (!empty($row['error'])) { $errors++; continue; }

            if ($row['exists']) {
                if ($mode === 'update') {
                    $existing = $this->Hsn_model->get_by_code($row['hsn_code']);
                    if ($existing) {
                        $this->Hsn_model->update($existing->id, [
                            'hsn_code'     => $row['hsn_code'],
                            'description'  => $row['description'],
                            'igst_percent' => $row['igst_percent'],
                        ]);
                        $updated++;
                    }
                } else {
                    $skipped++;
                }
            } else {
                $this->Hsn_model->create([
                    'hsn_code'     => $row['hsn_code'],
                    'description'  => $row['description'],
                    'igst_percent' => $row['igst_percent'],
                ]);
                $inserted++;
            }
        }

        @unlink($file_path ?? '');
        $this->session->unset_userdata(['hsn_import_preview', 'hsn_import_file']);

        $this->session->set_flashdata('success',
            "Import done — Added: {$inserted}, Updated: {$updated}, Skipped: {$skipped}, Errors: {$errors}.");
        redirect('hsn');
    }

    // Download blank Excel template
    public function download_template() {
        $filename = 'hsn_import_template.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['hsn_code', 'description', 'igst_percent']);
        fputcsv($out, ['0401',  'Milk and cream, not concentrated', '5']);
        fputcsv($out, ['1905',  'Bread, pastry, cakes', '18']);
        fputcsv($out, ['2710',  'Petroleum oils', '5']);
        fclose($out);
        exit;
    }

    // AJAX: get rates by HSN id — used by Products form and Billing JS
    public function get_rates_ajax($id = null) {
        if (!$this->input->is_ajax_request()) show_404();
        $rates = $this->Hsn_model->get_rates($id);
        header('Content-Type: application/json');
        echo json_encode($rates ?: ['igst_percent' => 0, 'cgst_percent' => 0, 'sgst_percent' => 0]);
    }

    private function _set_rules() {
        $this->form_validation->set_rules('hsn_code',     'HSN Code',    'required|trim|max_length[20]');
        $this->form_validation->set_rules('description',  'Description', 'trim|max_length[255]');
        $this->form_validation->set_rules('igst_percent', 'IGST %',      'required|numeric|greater_than_equal_to[0]');
    }

    private function _parse_csv($path) {
        $rows = [];
        if (($fh = fopen($path, 'r')) === FALSE) return $rows;
        while (($line = fgetcsv($fh)) !== FALSE) {
            $rows[] = array_map(fn($v) => (string)$v, $line);
        }
        fclose($fh);
        return $rows;
    }

    private function _parse_excel($path, &$errors) {
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            $autoload = APPPATH . '../vendor/autoload.php';
            if (file_exists($autoload)) require_once $autoload;
            else { $errors[] = 'PhpSpreadsheet not installed. Upload a .csv instead.'; return []; }
        }
        $rows = [];
        try {
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $val = $cell->getValue();
                    if (is_string($val) && strlen($val) > 1 && $val[0] === '=') {
                        try { $val = $cell->getCalculatedValue(); } catch (\Exception $e) { $val = 0; }
                    }
                    $cells[] = (string)($val ?? '');
                }
                $rows[] = $cells;
            }
        } catch (\Exception $e) {
            $errors[] = 'Excel read error: ' . $e->getMessage();
        }
        return $rows;
    }
}
