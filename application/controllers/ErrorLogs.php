<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ErrorLogs extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_superadmin();
        $this->load->model('ErrorLog_model');
    }

    public function index() {
        $filters = [
            'severity'    => $this->input->get('severity', TRUE) ?: '',
            'is_resolved' => $this->input->get('is_resolved', TRUE),
            'from'        => $this->input->get('from', TRUE) ?: '',
            'to'          => $this->input->get('to', TRUE) ?: '',
            'search'      => $this->input->get('search', TRUE) ?: '',
        ];
        if ($filters['is_resolved'] === NULL) $filters['is_resolved'] = '';

        $page     = max(1, (int)$this->input->get('page'));
        $per_page = 100;
        $offset   = ($page - 1) * $per_page;

        $data['page_title']  = 'Error Logs';
        $data['filters']     = $filters;
        $data['severities']  = $this->ErrorLog_model->get_severities();
        $data['logs']        = $this->ErrorLog_model->get_all($filters, $per_page, $offset);
        $data['total']       = $this->ErrorLog_model->count_all($filters);
        $data['page']        = $page;
        $data['per_page']    = $per_page;
        $data['total_pages'] = max(1, (int)ceil($data['total'] / $per_page));
        $data['unresolved_count'] = $this->ErrorLog_model->count_all(['is_resolved' => 0]);

        $this->render('error_logs/index', $data);
    }

    public function resolve($id) {
        $this->ErrorLog_model->mark_resolved($id, 1);
        $this->session->set_flashdata('success', 'Marked as resolved.');
        redirect($this->_back_url());
    }

    public function unresolve($id) {
        $this->ErrorLog_model->mark_resolved($id, 0);
        $this->session->set_flashdata('success', 'Marked as unresolved.');
        redirect($this->_back_url());
    }

    public function delete($id) {
        $this->ErrorLog_model->delete($id);
        $this->session->set_flashdata('success', 'Log entry deleted.');
        redirect($this->_back_url());
    }

    public function clear_resolved() {
        $this->ErrorLog_model->delete_resolved();
        $this->session->set_flashdata('success', 'Resolved entries cleared.');
        redirect('error_logs');
    }

    // Action links (resolve/unresolve/delete) carry the list page's current
    // filters via a `return` param so the cashier/admin lands back where they were.
    private function _back_url() {
        $return = $this->input->get('return', TRUE);
        return $return ? 'error_logs?' . $return : 'error_logs';
    }
}
