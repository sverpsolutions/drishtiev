<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stocksnapshots extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Report_model');
        // Only allow administrators to access this controller
        if (!in_array($this->session->userdata('role'), ['admin', 'superadmin'])) {
            $this->session->set_flashdata('error', 'Only Administrators can generate Stock Snapshots.');
            redirect('reports');
        }
    }

    public function index() {
        $this->generate();
    }

    public function generate() {
        $data = [
            'page_title' => 'Generate Stock Snapshot',
            'locations'  => $this->db->get('locations')->result(),
            'message'    => $this->session->flashdata('message'),
            'error'      => $this->session->flashdata('error')
        ];
        
        $this->render('stock_snapshots/generate', $data);
    }

    public function process_generation() {
        // Enforce Admin only
        if (!in_array($this->session->userdata('role'), ['admin', 'superadmin'])) {
            $this->session->set_flashdata('error', 'Only Administrators can generate Stock Snapshots.');
            redirect('stock_snapshots/generate');
        }

        $snapshot_date = $this->input->post('snapshot_date', TRUE);
        $location_id = $this->input->post('location_id', TRUE);
        
        if (!$snapshot_date || !$location_id) {
            $this->session->set_flashdata('error', 'Please select Date and Location.');
            redirect('stock_snapshots/generate');
        }

        // To generate snapshot, we run the closing stock report up to the snapshot_date
        $filters = [
            'from_date' => '2000-01-01', // Get all history to that date
            'to_date' => $snapshot_date,
            'location_id' => $location_id,
            'show_zero_stock' => '0' // don't store 0 stock items to save space
        ];

        // This will automatically use any prior snapshots to compute fast!
        $report_data = $this->Report_model->get_closing_stock_report($filters);

        $this->db->trans_start();

        // Remove existing snapshot for this date & location
        $this->db->where('snapshot_date', $snapshot_date);
        $this->db->where('location_id', $location_id);
        $this->db->delete('stock_snapshots');

        $inserts = [];
        $user_id = $this->session->userdata('user_id');
        $now = date('Y-m-d H:i:s');

        foreach ($report_data as $row) {
            if ($row->closing_qty != 0) {
                $inserts[] = [
                    'snapshot_date' => $snapshot_date,
                    'location_id'   => $location_id,
                    'product_id'    => $row->id,
                    'qty'           => $row->closing_qty,
                    'basic_cost'    => $row->basic_cost,
                    'cp_rate'       => $row->cp_rate,
                    'mrp'           => $row->mrp,
                    'created_by'    => $user_id,
                    'created_at'    => $now
                ];
            }
        }

        if (count($inserts) > 0) {
            // Bulk insert
            $chunks = array_chunk($inserts, 500);
            foreach ($chunks as $chunk) {
                $this->db->insert_batch('stock_snapshots', $chunk);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database Error: Failed to generate snapshot.');
        } else {
            $this->session->set_flashdata('message', 'Stock Snapshot generated successfully for ' . $snapshot_date . ' with ' . count($inserts) . ' items.');
        }

        redirect('stock_snapshots/generate');
    }
}
