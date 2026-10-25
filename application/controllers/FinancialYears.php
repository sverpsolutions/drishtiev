<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Financial Year master. Company-wide (not location-scoped) — closing a year blocks
// document numbering for every location, so this is superadmin-only, unlike Locations
// (admin-accessible) where the blast radius of a mistake is much smaller.
class FinancialYears extends Auth_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Financial_year_model');
        $this->load->library(['form_validation', 'DocumentNumbering']);
        $this->require_superadmin();
    }

    public function index()
    {
        $data['financial_years'] = $this->Financial_year_model->get_all();
        $data['current_label']   = $this->documentnumbering->resolve_financial_year(date('Y-m-d'));
        $data['page_title']      = 'Financial Years';
        $this->render('financial_years/index', $data);
    }

    public function add()
    {
        if ($this->input->post()) {
            $this->form_validation->set_rules('label', 'Label', 'required|trim|max_length[8]|regex_match[/^\d{2}-\d{2}$/]');
            $this->form_validation->set_rules('start_date', 'Start Date', 'required|trim');
            $this->form_validation->set_rules('end_date', 'End Date', 'required|trim');

            if ($this->form_validation->run()) {
                $label = $this->input->post('label', TRUE);
                if ($this->Financial_year_model->get_by_label($label)) {
                    $this->session->set_flashdata('error', 'Financial Year "' . $label . '" already exists.');
                } else {
                    $this->Financial_year_model->create([
                        'label'      => $label,
                        'start_date' => $this->input->post('start_date', TRUE),
                        'end_date'   => $this->input->post('end_date', TRUE),
                        'status'     => 'open',
                    ]);
                    $this->log_activity('financial_years', 'create', 'Created financial year ' . $label);
                    $this->session->set_flashdata('success', 'Financial Year created.');
                    redirect('financial_years');
                    return;
                }
            }
        }
        $data['page_title'] = 'Add Financial Year';
        $this->render('financial_years/form', $data);
    }

    public function close($id)
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') show_404();
        $fy = $this->Financial_year_model->get_by_id($id);
        if (!$fy) {
            $this->session->set_flashdata('error', 'Financial Year not found.');
            redirect('financial_years');
            return;
        }
        // Guard against locking yourself out of billing today by mistake.
        $current_label = $this->documentnumbering->resolve_financial_year(date('Y-m-d'));
        if ($fy->label === $current_label) {
            $this->session->set_flashdata('error', 'Cannot close ' . $fy->label . ' — it is the financial year for today\'s date.');
            redirect('financial_years');
            return;
        }
        $this->Financial_year_model->set_status($id, 'closed');
        $this->log_activity('financial_years', 'close', 'Closed financial year ' . $fy->label, $id);
        $this->session->set_flashdata('success', 'Financial Year ' . $fy->label . ' closed. No new documents can be posted into it.');
        redirect('financial_years');
    }

    public function reopen($id)
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') show_404();
        $fy = $this->Financial_year_model->get_by_id($id);
        if (!$fy) {
            $this->session->set_flashdata('error', 'Financial Year not found.');
            redirect('financial_years');
            return;
        }
        $this->Financial_year_model->set_status($id, 'open');
        $this->log_activity('financial_years', 'reopen', 'Reopened financial year ' . $fy->label, $id);
        $this->session->set_flashdata('success', 'Financial Year ' . $fy->label . ' reopened.');
        redirect('financial_years');
    }
}
