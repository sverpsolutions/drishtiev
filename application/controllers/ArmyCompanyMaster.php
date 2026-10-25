<?php
// ============================================================
// FILE: application/controllers/ArmyCompanyMaster.php
// Army Quotation Management — Company Master CRUD.
// Fully independent module — does not touch any existing table.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class ArmyCompanyMaster extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Army_company_model');
        if (!$this->is_admin()) {
            $this->session->set_flashdata('error', 'Permission denied.');
            redirect('dashboard');
        }
    }

    public function index() {
        $data['page_title'] = 'Army Quotation — Company Master';
        $data['companies']  = $this->Army_company_model->get_all();
        $this->render('army/company_master/index', $data);
    }

    public function create() {
        $data['page_title']   = 'Add Company';
        $data['army_company'] = null;

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $result = $this->_save(null);
            if ($result !== true) {
                $this->session->set_flashdata('error', $result);
                $this->render('army/company_master/form', $data);
                return;
            }
            $this->session->set_flashdata('success', 'Company created successfully.');
            redirect('army_company_master');
        }

        $this->render('army/company_master/form', $data);
    }

    public function edit($id = null) {
        if (!$id) redirect('army_company_master');
        $company = $this->Army_company_model->get_by_id($id);
        if (!$company) {
            $this->session->set_flashdata('error', 'Company not found.');
            redirect('army_company_master');
        }
        $data['page_title']   = 'Edit Company';
        $data['army_company'] = $company;

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $result = $this->_save($id);
            if ($result !== true) {
                $this->session->set_flashdata('error', $result);
                $data['army_company'] = $this->Army_company_model->get_by_id($id);
                $this->render('army/company_master/form', $data);
                return;
            }
            $this->session->set_flashdata('success', 'Company updated successfully.');
            redirect('army_company_master');
        }

        $this->render('army/company_master/form', $data);
    }

    // Shared create/update handler. Returns true on success, or an error string.
    private function _save($id) {
        $company_name = $this->input->post('company_name', TRUE);
        if (empty($company_name)) {
            return 'Company Name is required.';
        }

        $data = [
            'company_name'   => $company_name,
            'address'        => $this->input->post('address', FALSE), // preserve raw line breaks
            'gst_number'     => $this->input->post('gst_number', TRUE),
            'pan_number'     => $this->input->post('pan_number', TRUE),
            'phone'          => $this->input->post('phone', TRUE),
            'mobile'         => $this->input->post('mobile', TRUE),
            'email'          => $this->input->post('email', TRUE),
            'website'        => $this->input->post('website', TRUE),
            'bank_name'      => $this->input->post('bank_name', TRUE),
            'account_number' => $this->input->post('account_number', TRUE),
            'ifsc_code'      => $this->input->post('ifsc_code', TRUE),
            'branch'         => $this->input->post('branch', TRUE),
            'signatory_name' => $this->input->post('signatory_name', TRUE),
            'is_active'      => $this->input->post('is_active') ? 1 : 0,
        ];

        // File uploads: logo, signature, stamp — each gets its own filename
        // prefix so uploading all three in one request never collides even
        // though they'd otherwise share the same time() second.
        $upload_fields = [
            'logo'      => ['column' => 'logo_path',      'prefix' => 'army_logo_'],
            'signature' => ['column' => 'signature_path',  'prefix' => 'army_sign_'],
            'stamp'     => ['column' => 'stamp_path',      'prefix' => 'army_stamp_'],
        ];
        foreach ($upload_fields as $field => $cfg) {
            if (!empty($_FILES[$field]['name'])) {
                $config['upload_path']   = './assets/uploads/';
                $config['allowed_types'] = 'gif|jpg|jpeg|png';
                $config['max_size']      = 2048;
                $config['file_name']     = $cfg['prefix'] . time();
                $this->load->library('upload', $config);
                $this->upload->initialize($config);
                if ($this->upload->do_upload($field)) {
                    $upload_data = $this->upload->data();
                    $data[$cfg['column']] = 'assets/uploads/' . $upload_data['file_name'];
                } else {
                    return ucfirst($field) . ' upload failed: ' . $this->upload->display_errors('', '');
                }
            }
        }

        if ($id) {
            $this->Army_company_model->update($id, $data);
        } else {
            $this->Army_company_model->create($data);
        }
        return true;
    }

    public function toggle_active($id = null) {
        if (!$id) show_404();
        $company = $this->Army_company_model->get_by_id($id);
        if (!$company) show_404();
        $this->Army_company_model->set_active($id, !$company->is_active);
        echo json_encode(['success' => true, 'is_active' => !$company->is_active]);
    }

    public function delete($id = null) {
        if (!$id) redirect('army_company_master');
        $count = $this->Army_company_model->count_quotations_for_company($id);
        if ($count > 0) {
            $this->session->set_flashdata('error', "Cannot delete — $count quotation(s) reference this company. Deactivate it instead.");
            redirect('army_company_master');
        }
        $this->Army_company_model->delete($id);
        $this->session->set_flashdata('success', 'Company deleted.');
        redirect('army_company_master');
    }
}
