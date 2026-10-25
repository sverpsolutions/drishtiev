<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Locations extends Auth_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Location_model');
        $this->load->library('form_validation');
        $this->require_admin();
    }

    public function index()
    {
        $data['locations']   = $this->Location_model->get_all();
        $data['page_title']  = 'Locations';
        $this->render('locations/index', $data);
    }

    public function add()
    {
        if ($this->input->post()) {
            $this->form_validation->set_rules('name', 'Location Name', 'required|trim|max_length[100]');
            $this->form_validation->set_rules('code', 'Code', 'required|trim|max_length[10]');
            $this->form_validation->set_rules('type', 'Type', 'required|in_list[warehouse,outlet]');

            if ($this->form_validation->run()) {
                $this->Location_model->create([
                    'name'       => $this->input->post('name', TRUE),
                    'code'       => strtoupper($this->input->post('code', TRUE)),
                    'type'       => $this->input->post('type', TRUE),
                    'address'    => $this->input->post('address', TRUE),
                    'phone'      => $this->input->post('phone', TRUE),
                    'email'      => $this->input->post('email', TRUE),
                    'city'       => $this->input->post('city', TRUE),
                    'state'      => $this->input->post('state', TRUE),
                    'pincode'    => $this->input->post('pincode', TRUE),
                    'gstin'      => strtoupper($this->input->post('gstin', TRUE)),
                    'pan'        => strtoupper($this->input->post('pan', TRUE)),
                    'sort_order' => (int)$this->input->post('sort_order', TRUE) ?: 0,
                    'status'     => 1,
                ]);
                $this->log_activity('locations', 'create', 'New location created: ' . $this->input->post('name', TRUE));
                $this->session->set_flashdata('success', 'Location created successfully.');
                redirect('locations');
            }
        }
        $data['page_title'] = 'Add Location';
        $this->render('locations/form', $data);
    }

    public function edit($id)
    {
        $location = $this->Location_model->get_by_id($id);
        if (!$location) {
            $this->session->set_flashdata('error', 'Location not found.');
            redirect('locations');
        }
        if ($this->input->post()) {
            $this->form_validation->set_rules('name', 'Location Name', 'required|trim|max_length[100]');
            $this->form_validation->set_rules('code', 'Code', 'required|trim|max_length[10]');

            if ($this->form_validation->run()) {
                $this->Location_model->update($id, [
                    'name'       => $this->input->post('name', TRUE),
                    'code'       => strtoupper($this->input->post('code', TRUE)),
                    'type'       => $this->input->post('type', TRUE),
                    'address'    => $this->input->post('address', TRUE),
                    'phone'      => $this->input->post('phone', TRUE),
                    'email'      => $this->input->post('email', TRUE),
                    'city'       => $this->input->post('city', TRUE),
                    'state'      => $this->input->post('state', TRUE),
                    'pincode'    => $this->input->post('pincode', TRUE),
                    'gstin'      => strtoupper($this->input->post('gstin', TRUE)),
                    'pan'        => strtoupper($this->input->post('pan', TRUE)),
                    'sort_order' => (int)$this->input->post('sort_order', TRUE) ?: 0,
                    'status'     => (int)$this->input->post('status', TRUE),
                ]);
                $this->log_activity('locations', 'update', 'Location updated: ' . $location->name, $id);
                $this->session->set_flashdata('success', 'Location updated.');
                redirect('locations');
            }
        }
        $data['location']   = $location;
        $data['page_title'] = 'Edit Location';
        $this->render('locations/form', $data);
    }

    public function stock($id = null)
    {
        $this->load->model('Invoice_model');
        $locations = $this->Location_model->get_all(true);
        if (!$id && !empty($locations)) {
            $id = $locations[0]->id;
        }
        $location = $this->Location_model->get_by_id($id);

        $per_page = 500;
        $page     = max(1, (int)$this->input->get('page'));
        $search   = trim($this->input->get('q', TRUE) ?? '');
        $stock_filter = $this->input->get('sf', TRUE) ?: '';
        $category = $this->input->get('cat', TRUE) ?: '';

        $data['locations']        = $locations;
        $data['current_location'] = $location;
        $data['stock']            = $location ? $this->Location_model->get_location_stock($id, $per_page, ($page - 1) * $per_page, $search, $stock_filter, $category) : [];
        $data['total_rows']       = $location ? $this->Location_model->count_location_stock($id, $search, $stock_filter, $category) : 0;
        $data['kpis']             = $location ? $this->Location_model->get_location_stock_kpis($id) : null;
        $data['categories']       = $location ? $this->Location_model->get_location_categories($id) : [];
        $data['current_page']     = $page;
        $data['per_page']         = $per_page;
        $data['search']           = $search;
        $data['stock_filter']     = $stock_filter;
        $data['category_filter']  = $category;
        $data['company']          = $this->Invoice_model->get_company_settings();
        $data['page_title']       = 'Location Stock — ' . ($location->name ?? '');
        $this->render('locations/stock', $data);
    }

    public function switch_location()
    {
        if (!$this->is_owner()) {
            $this->output->set_status_header(403);
            echo json_encode(['error' => 'Not allowed']);
            return;
        }
        $loc_id = (int)$this->input->post('location_id');
        if ($loc_id) {
            // Only switch to a location that actually exists and is active — previously
            // this set active_location_id from the raw POST value even if get_by_id()
            // found nothing, which could leave the session pointed at a bogus location.
            $loc = $this->db->where('id', $loc_id)->where('status', 1)->get('locations')->row();
            if (!$loc) {
                $this->session->set_flashdata('error', 'That location does not exist or is inactive.');
                redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
                return;
            }
            $this->session->set_userdata([
                'active_location_id'   => $loc_id,
                'active_location_name' => $loc->name,
            ]);
        } else {
            $this->session->set_userdata([
                'active_location_id'   => null,
                'active_location_name' => null,
            ]);
        }
        redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
    }
}
