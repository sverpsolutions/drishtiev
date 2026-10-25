<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Suppliers extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Supplier_model', 'Product_model']);
        $this->load->library('form_validation');
        $this->load->helper('url');
    }

    public function index() {
        $data['page_title'] = 'Supplier Master';
        $data['suppliers']  = $this->Supplier_model->get_all_with_performance();
        $data['visits_today'] = $this->Supplier_model->get_visits_today();
        $this->render('suppliers/index', $data);
    }

    public function add() {
        $data['page_title'] = 'Add Supplier';
        $this->_set_rules();
        if ($this->form_validation->run() === FALSE) {
            $this->render('suppliers/add', $data);
            return;
        }
        $id = $this->Supplier_model->create($this->_collect_post());
        // Save visit schedule if provided
        $this->_save_schedule($id);
        $this->log_activity('suppliers', 'create', 'Supplier created', $id);
        $this->session->set_flashdata('success', 'Supplier added successfully.');
        redirect('suppliers');
    }

    public function edit($id = null) {
        if (!$id) redirect('suppliers');
        $data['page_title'] = 'Edit Supplier';
        $data['supplier']   = $this->Supplier_model->get_with_schedule($id);
        if (!$data['supplier']) { $this->session->set_flashdata('error','Not found.'); redirect('suppliers'); }

        $this->_set_rules();
        if ($this->form_validation->run() === FALSE) {
            $this->render('suppliers/edit', $data);
            return;
        }
        $this->Supplier_model->update($id, $this->_collect_post());
        $this->_save_schedule($id);
        $this->log_activity('suppliers', 'update', 'Supplier updated', $id);
        $this->session->set_flashdata('success', 'Supplier updated.');
        redirect('suppliers');
    }

    public function view($id = null) {
        if (!$id) redirect('suppliers');
        $data['page_title'] = 'Supplier Details';
        $data['supplier']   = $this->Supplier_model->get_with_schedule($id);
        if (!$data['supplier']) { redirect('suppliers'); }
        $this->load->model(['Purchase_model','Purchase_order_model']);
        $data['purchases']    = $this->Purchase_model->get_by_supplier($id);
        $data['pos']          = $this->Purchase_order_model->get_all(['supplier_id' => $id]);
        $data['sp_products']  = $this->Supplier_model->get_supplier_products($id);
        $data['visit_logs']   = $this->db->where('supplier_id', $id)->order_by('visit_date','DESC')->limit(20)->get('supplier_visit_logs')->result();
        $this->render('suppliers/view', $data);
    }

    public function delete($id = null) {
        $this->require_admin();
        $this->Supplier_model->delete($id);
        $this->session->set_flashdata('success', 'Supplier deleted.');
        redirect('suppliers');
    }

    // ── Visit schedule page ──────────────────────────────────────────────────
    public function visit_schedule($id = null) {
        if (!$id) redirect('suppliers');
        $data['page_title'] = 'Visit Schedule';
        $data['supplier']   = $this->Supplier_model->get_with_schedule($id);
        $data['today_visits']    = $this->Supplier_model->get_visits_today();
        $data['upcoming_visits'] = $this->Supplier_model->get_visits_upcoming(14);
        $this->render('suppliers/visit_schedule', $data);
    }

    public function log_visit_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $supplier_id = (int)$this->input->post('supplier_id');
        $notes    = $this->input->post('notes', true);
        $po_value = (float)$this->input->post('po_value');
        $buyer_id = $this->session->userdata('user_id');
        $this->Supplier_model->log_visit($supplier_id, $buyer_id, $notes, $po_value);
        $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true]));
    }

    // ── Products linked to supplier ──────────────────────────────────────────
    public function products($id = null) {
        if (!$id) redirect('suppliers');
        $data['page_title']  = 'Supplier Products';
        $data['supplier']    = $this->Supplier_model->get_by_id($id);
        $data['sp_products'] = $this->Supplier_model->get_supplier_products($id);
        $data['all_products']= $this->Product_model->get_all();
        $this->render('suppliers/products', $data);
    }

    public function link_product_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $supplier_id = (int)$this->input->post('supplier_id');
        $product_id  = (int)$this->input->post('product_id');
        $action      = $this->input->post('action'); // link / unlink
        if ($action === 'unlink') {
            $this->Supplier_model->unlink_product($supplier_id, $product_id);
        } else {
            $this->Supplier_model->link_product($supplier_id, $product_id, ['is_primary' => (int)$this->input->post('is_primary')]);
        }
        $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true]));
    }

    // ── Documents ────────────────────────────────────────────────────────────
    public function documents($id = null) {
        if (!$id) redirect('suppliers');
        $data['page_title'] = 'Supplier Documents';
        $data['supplier']   = $this->Supplier_model->get_by_id($id);
        $data['documents']  = $this->db->where('supplier_id', $id)->order_by('doc_type')->get('supplier_documents')->result();
        $this->render('suppliers/documents', $data);
    }

    public function upload_document($id = null) {
        if (!$id) redirect('suppliers');
        $doc_type    = $this->input->post('doc_type', true);
        $expiry_date = $this->input->post('expiry_date', true) ?: null;
        $notes       = $this->input->post('notes', true);
        $file_path   = '';
        if (!empty($_FILES['doc_file']['name'])) {
            $cfg = ['upload_path' => FCPATH . 'assets/uploads/supplier_docs/', 'allowed_types' => 'pdf|jpg|jpeg|png|doc|docx', 'max_size' => 5120, 'encrypt_name' => true];
            $this->load->library('upload', $cfg);
            if ($this->upload->do_upload('doc_file')) {
                $file_path = 'assets/uploads/supplier_docs/' . $this->upload->data('file_name');
            }
        }
        $this->db->insert('supplier_documents', [
            'supplier_id' => $id, 'doc_type' => $doc_type, 'file_path' => $file_path,
            'doc_name' => $this->input->post('doc_name', true), 'expiry_date' => $expiry_date,
            'notes' => $notes, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->session->set_flashdata('success', 'Document uploaded.');
        redirect("suppliers/documents/$id");
    }

    // ── Vendor portal credentials ────────────────────────────────────────────
    public function set_portal_credentials($id = null) {
        if (!$id) redirect('suppliers');
        $this->require_admin();
        $username = $this->input->post('portal_username', true);
        $password = $this->input->post('portal_password', true);
        $active   = (int)$this->input->post('portal_active');
        $update   = ['portal_username' => $username, 'portal_active' => $active];
        if ($password) $update['portal_password'] = password_hash($password, PASSWORD_DEFAULT);
        $this->Supplier_model->update($id, $update);
        $this->session->set_flashdata('success', 'Portal credentials updated.');
        redirect("suppliers/view/$id");
    }

    // ── AJAX for dropdown/search ─────────────────────────────────────────────
    public function search_ajax() {
        $q = $this->input->get('q', true);
        $rows = $this->db->select('id, name, supplier_code, mobile, city')
            ->like('name', $q)->or_like('supplier_code', $q)
            ->where('status', 1)->limit(10)->get('suppliers')->result();
        $this->output->set_content_type('application/json')->set_output(json_encode($rows));
    }

    // ── Excel / CSV bulk import (name + gst_number + phone) ──────────────────
    public function import() {
        $data['page_title'] = 'Import Suppliers';
        if ($this->input->method() !== 'post') {
            $this->render('suppliers/import', $data);
            return;
        }

        if (empty($_FILES['import_file']['name'])) {
            $this->session->set_flashdata('error', 'Please choose a file to upload.');
            redirect('suppliers/import');
        }

        $tmp  = $_FILES['import_file']['tmp_name'];
        $fname = strtolower($_FILES['import_file']['name']);
        $errors = [];

        if (substr($fname, -4) === '.csv') {
            $rows = $this->_parse_csv_simple($tmp);
        } elseif (substr($fname, -5) === '.xlsx' || substr($fname, -4) === '.xls') {
            $rows = $this->_parse_xlsx_simple($tmp, $errors);
        } else {
            $this->session->set_flashdata('error', 'Unsupported file type. Please upload a .xlsx or .csv file.');
            redirect('suppliers/import');
        }

        if (!empty($errors)) {
            $this->session->set_flashdata('error', implode(' ', $errors));
            redirect('suppliers/import');
        }
        if (count($rows) < 2) {
            $this->session->set_flashdata('error', 'No data rows found in the file.');
            redirect('suppliers/import');
        }

        // Detect columns from the header row (flexible order)
        $header = array_map(function ($h) { return strtolower(trim((string)$h)); }, $rows[0]);
        $col = ['name' => -1, 'gst' => -1, 'phone' => -1];
        foreach ($header as $i => $h) {
            if ($col['name'] < 0 && strpos($h, 'name') !== false)            $col['name']  = $i;
            elseif ($col['gst'] < 0 && strpos($h, 'gst') !== false)          $col['gst']   = $i;
            elseif ($col['phone'] < 0 && (strpos($h, 'phone') !== false || strpos($h, 'mobile') !== false || strpos($h, 'contact') !== false)) $col['phone'] = $i;
        }
        if ($col['name'] < 0) {
            $this->session->set_flashdata('error', 'Could not find a "name" column in the header row. Please use the template.');
            redirect('suppliers/import');
        }

        $inserted = 0; $skipped = 0;
        for ($r = 1; $r < count($rows); $r++) {
            $row   = $rows[$r];
            $sname = trim((string)($row[$col['name']] ?? ''));
            if ($sname === '') continue; // ignore blank rows

            $gst   = $col['gst']   >= 0 ? strtoupper(trim((string)($row[$col['gst']]   ?? ''))) : '';
            $phone = $col['phone'] >= 0 ? trim((string)($row[$col['phone']] ?? '')) : '';

            // Skip duplicates (same name, already active)
            $dup = $this->db->query(
                "SELECT id FROM suppliers WHERE LOWER(name) = ? AND status = 1 LIMIT 1",
                [strtolower($sname)]
            )->row();
            if ($dup) { $skipped++; continue; }

            $this->Supplier_model->create([
                'name'       => $sname,
                'phone'      => $phone,
                'gst_number' => $gst,
                'country'    => 'India',
                'currency'   => 'INR',
                'status'     => 1,
            ]);
            $inserted++;
        }

        $msg = "Import complete: {$inserted} supplier(s) added.";
        if ($skipped) $msg .= " {$skipped} duplicate name(s) skipped.";
        $this->session->set_flashdata('success', $msg);
        redirect('suppliers');
    }

    // Download a ready-to-fill CSV template
    public function import_template() {
        $headers = ['name', 'gst_number', 'phone'];
        $sample  = ['ABC Traders', '06ABCDE1234F1Z5', '9876543210'];
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="supplier_import_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        fputcsv($out, $sample);
        fclose($out);
        exit;
    }

    private function _parse_csv_simple($path) {
        $rows = [];
        if (($fh = fopen($path, 'r')) !== FALSE) {
            while (($row = fgetcsv($fh)) !== FALSE) { $rows[] = $row; }
            fclose($fh);
        }
        return $rows;
    }

    private function _parse_xlsx_simple($path, &$errors) {
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            $autoload = APPPATH . '../vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            } else {
                $errors[] = 'Excel library not installed on server. Please upload a .csv file instead.';
                return [];
            }
        }
        $rows = [];
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $sheet       = $spreadsheet->getActiveSheet();
            // toArray keeps columns aligned even when some cells are blank
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Exception $e) {
            $errors[] = 'Excel read error: ' . $e->getMessage();
        }
        return $rows;
    }

    private function _set_rules() {
        $this->form_validation->set_rules('name',  'Supplier Name', 'required|trim|max_length[150]');
        $this->form_validation->set_rules('phone', 'Phone',         'required|trim|max_length[15]');
    }

    private function _collect_post() {
        $data = [
            'name'              => $this->input->post('name',             TRUE),
            'company_name'      => $this->input->post('company_name',     TRUE),
            'contact_name'      => $this->input->post('contact_name',     TRUE),
            'phone'             => $this->input->post('phone',            TRUE),
            'whatsapp'          => $this->input->post('whatsapp',         TRUE),
            'email'             => $this->input->post('email',            TRUE),
            'website'           => $this->input->post('website',          TRUE),
            'address'           => $this->input->post('address',          TRUE),
            'city'              => $this->input->post('city',             TRUE),
            'state'             => $this->input->post('state',            TRUE),
            'country'           => $this->input->post('country',          TRUE) ?: 'India',
            'pincode'           => $this->input->post('pincode',          TRUE),
            'gst_number'        => strtoupper($this->input->post('gst_number',  TRUE)),
            'pan_number'        => strtoupper($this->input->post('pan_number',  TRUE)),
            'credit_days'       => (int)$this->input->post('credit_days'),
            'credit_limit'      => (float)$this->input->post('credit_limit'),
            'opening_balance'   => (float)$this->input->post('opening_balance'),
            'payment_terms'     => $this->input->post('payment_terms',    TRUE),
            'currency'          => $this->input->post('currency',         TRUE) ?: 'INR',
            'is_preferred'      => (int)$this->input->post('is_preferred'),
            'default_tax_pct'   => (float)$this->input->post('default_tax_pct'),
            'default_discount'  => (float)$this->input->post('default_discount'),
            'freight_applicable'=> (int)$this->input->post('freight_applicable'),
            'moq'               => (int)$this->input->post('moq') ?: 1,
            'pack_size'         => (int)$this->input->post('pack_size') ?: 1,
            'lead_time_days'    => (int)$this->input->post('lead_time_days') ?: 2,
            'delivery_days'     => (int)$this->input->post('delivery_days') ?: 1,
            'order_cutoff_time' => $this->input->post('order_cutoff_time', TRUE) ?: null,
            'notes'             => $this->input->post('notes',            TRUE),
            'status'            => (int)($this->input->post('status') ?? 1),
            'portal_username'   => $this->input->post('portal_username',  TRUE) ?: null,
            'portal_active'     => (int)$this->input->post('portal_active'),
        ];
        $pwd = $this->input->post('portal_password', TRUE);
        if ($pwd) {
            $data['portal_password'] = password_hash($pwd, PASSWORD_DEFAULT);
        }
        return $data;
    }

    private function _save_schedule($supplier_id) {
        $type = $this->input->post('schedule_type', true);
        if (!$type) return;
        $sched = [
            'schedule_type'  => $type,
            'weekdays'       => $type === 'weekly'         ? json_encode($this->input->post('weekdays') ?: [1]) : null,
            'interval_days'  => $type === 'interval'       ? (int)$this->input->post('interval_days') : null,
            'month_dates'    => $type === 'monthly_dates'  ? json_encode($this->input->post('month_dates') ?: [1]) : null,
            'assigned_buyer' => (int)$this->input->post('assigned_buyer') ?: null,
            'notes'          => $this->input->post('schedule_notes', true),
            'last_visit_date'=> $this->input->post('last_visit_date', true) ?: null,
        ];
        $this->Supplier_model->save_visit_schedule($supplier_id, $sched);
    }
}
