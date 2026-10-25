<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class VendorPortal extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Supplier_model','Purchase_order_model','Rfq_model']);
        $this->load->library('session');
        $this->load->helper('url');
        // Load company for branding
        if ($this->db->table_exists('company_settings')) {
            $rows = $this->db->get('company_settings')->result();
            $company = [];
            foreach ($rows as $r) $company[$r->key_name] = $r->key_value;
            $this->load->vars(['company' => $company]);
        }
    }

    private function _check_vendor_auth() {
        if (!$this->session->userdata('vendor_supplier_id')) {
            redirect('vendor-portal/login');
        }
    }

    private function _vendor_render($view, $data = []) {
        $data['vendor_supplier'] = $this->Supplier_model->get_by_id($this->session->userdata('vendor_supplier_id'));
        $this->load->view('vendor_portal/header', $data);
        $this->load->view($view, $data);
        $this->load->view('vendor_portal/footer');
    }

    public function login() {
        if ($this->session->userdata('vendor_supplier_id')) redirect('vendor-portal/dashboard');
        $data['page_title'] = 'Vendor Portal Login';
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $username = $this->input->post('username', true);
            $password = $this->input->post('password', true);
            $supplier = $this->Supplier_model->get_by_portal_username($username);
            if ($supplier && password_verify($password, $supplier->portal_password)) {
                $this->session->set_userdata([
                    'vendor_supplier_id'   => $supplier->id,
                    'vendor_supplier_name' => $supplier->name,
                ]);
                redirect('vendor-portal/dashboard');
            } else {
                $data['error'] = 'Invalid credentials.';
            }
        }
        $this->load->view('vendor_portal/login', $data);
    }

    public function logout() {
        $this->session->unset_userdata(['vendor_supplier_id','vendor_supplier_name']);
        redirect('vendor-portal/login');
    }

    public function dashboard() {
        $this->_check_vendor_auth();
        $sid = $this->session->userdata('vendor_supplier_id');
        $data['page_title'] = 'Vendor Dashboard';
        $data['pending_pos']   = $this->Purchase_order_model->get_all(['supplier_id' => $sid, 'status' => 'approved']);
        $data['confirmed_pos'] = $this->Purchase_order_model->get_all(['supplier_id' => $sid, 'status' => 'vendor_confirmed']);
        $data['all_pos']       = $this->Purchase_order_model->get_by_supplier_for_vendor($sid);
        $data['rfqs']          = $this->Rfq_model->get_for_supplier($sid);
        $data['performance']   = $this->db->where('supplier_id', $sid)->get('vendor_performance')->row();
        $data['unread_msgs']   = $this->db->where('supplier_id', $sid)->where('sender_type', 'buyer')->where('is_read', 0)->get('vendor_messages')->num_rows();
        $this->_vendor_render('vendor_portal/dashboard', $data);
    }

    public function orders() {
        $this->_check_vendor_auth();
        $sid = $this->session->userdata('vendor_supplier_id');
        $data['page_title'] = 'My Orders';
        $data['pos'] = $this->Purchase_order_model->get_by_supplier_for_vendor($sid);
        $this->_vendor_render('vendor_portal/orders', $data);
    }

    public function view_po($id = null) {
        $this->_check_vendor_auth();
        $sid = $this->session->userdata('vendor_supplier_id');
        $po  = $this->Purchase_order_model->get_by_id($id);
        if (!$po || $po->supplier_id != $sid) { redirect('vendor-portal/orders'); }
        $data['page_title'] = 'Order Details — ' . $po->po_number;
        $data['po']    = $po;
        $data['items'] = $this->Purchase_order_model->get_items($id);
        $data['messages'] = $this->db->where('po_id', $id)->where('supplier_id', $sid)->order_by('created_at')->get('vendor_messages')->result();
        // Mark buyer messages as read
        $this->db->where('po_id', $id)->where('sender_type', 'buyer')->update('vendor_messages', ['is_read' => 1]);
        $this->_vendor_render('vendor_portal/view_po', $data);
    }

    public function accept_po($id = null) {
        $this->_check_vendor_auth();
        $sid = $this->session->userdata('vendor_supplier_id');
        $po  = $this->Purchase_order_model->get_by_id($id);
        if (!$po || $po->supplier_id != $sid) { redirect('vendor-portal/orders'); }
        $delivery_date = $this->input->post('delivery_date', true);
        $this->Purchase_order_model->vendor_confirm($id, $delivery_date);
        // System message
        $this->db->insert('vendor_messages', [
            'supplier_id' => $sid, 'po_id' => $id, 'sender_type' => 'supplier',
            'sender_id' => $sid, 'message' => 'PO Accepted. Delivery confirmed by ' . date('d M Y', strtotime($delivery_date ?: 'now')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->session->set_flashdata('success', 'PO accepted successfully.');
        redirect("vendor-portal/view-po/$id");
    }

    public function reject_po($id = null) {
        $this->_check_vendor_auth();
        $sid    = $this->session->userdata('vendor_supplier_id');
        $po     = $this->Purchase_order_model->get_by_id($id);
        if (!$po || $po->supplier_id != $sid) { redirect('vendor-portal/orders'); }
        $reason = $this->input->post('reason', true);
        $this->Purchase_order_model->update_status($id, 'rejected');
        $this->db->insert('vendor_messages', [
            'supplier_id' => $sid, 'po_id' => $id, 'sender_type' => 'supplier',
            'sender_id' => $sid, 'message' => 'PO Rejected. Reason: ' . $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->session->set_flashdata('success', 'PO rejected.');
        redirect('vendor-portal/orders');
    }

    public function send_message() {
        $this->_check_vendor_auth();
        $sid = $this->session->userdata('vendor_supplier_id');
        $po_id = (int)$this->input->post('po_id');
        $msg   = $this->input->post('message', true);
        $this->db->insert('vendor_messages', [
            'supplier_id' => $sid, 'po_id' => $po_id ?: null, 'sender_type' => 'supplier',
            'sender_id' => $sid, 'message' => $msg, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        redirect($po_id ? "vendor-portal/view-po/$po_id" : 'vendor-portal/messages');
    }

    public function messages() {
        $this->_check_vendor_auth();
        $sid = $this->session->userdata('vendor_supplier_id');
        $data['page_title'] = 'Messages';
        $data['messages'] = $this->db->where('supplier_id', $sid)->order_by('created_at', 'DESC')->get('vendor_messages')->result();
        $this->db->where('supplier_id', $sid)->where('sender_type', 'buyer')->update('vendor_messages', ['is_read' => 1]);
        $this->_vendor_render('vendor_portal/messages', $data);
    }

    public function rfqs() {
        $this->_check_vendor_auth();
        $sid = $this->session->userdata('vendor_supplier_id');
        $data['page_title'] = 'RFQ / Quotations';
        $data['rfqs'] = $this->Rfq_model->get_for_supplier($sid);
        $this->_vendor_render('vendor_portal/rfqs', $data);
    }

    public function submit_quotation($rfq_id = null) {
        $this->_check_vendor_auth();
        $sid = $this->session->userdata('vendor_supplier_id');
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $item_ids = $this->input->post('rfq_item_id');
            $prices   = $this->input->post('quoted_price');
            $discs    = $this->input->post('discount_pct');
            $gsts     = $this->input->post('gst_pct');
            $del_days = $this->input->post('delivery_days');
            $quotes   = [];
            foreach ((array)$item_ids as $i => $iid) {
                $quotes[] = [
                    'rfq_item_id'  => (int)$iid,
                    'quoted_price' => (float)($prices[$i] ?? 0),
                    'discount_pct' => (float)($discs[$i]  ?? 0),
                    'gst_pct'      => (float)($gsts[$i]   ?? 0),
                    'delivery_days'=> (int)($del_days[$i] ?? 0),
                ];
            }
            $this->Rfq_model->submit_quotation($rfq_id, $sid, $quotes);
            $this->session->set_flashdata('success', 'Quotation submitted successfully.');
            redirect('vendor-portal/rfqs');
        }
        $data['page_title'] = 'Submit Quotation';
        $data['rfq']   = $this->Rfq_model->get_by_id($rfq_id);
        $data['items'] = $this->Rfq_model->get_items($rfq_id);
        $this->_vendor_render('vendor_portal/submit_quotation', $data);
    }

    public function performance() {
        $this->_check_vendor_auth();
        $sid = $this->session->userdata('vendor_supplier_id');
        $data['page_title']  = 'My Performance';
        $data['performance'] = $this->db->where('supplier_id', $sid)->get('vendor_performance')->row();
        $data['pos']         = $this->Purchase_order_model->get_by_supplier_for_vendor($sid);
        $this->_vendor_render('vendor_portal/performance', $data);
    }
}
