<?php
// FILE: application/controllers/OrderRequests.php
defined('BASEPATH') OR exit('No direct script access allowed');

class OrderRequests extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Customer_model', 'Invoice_model', 'Product_model']);
    }

    // ── List all order requests ──────────────────────────────────────────────
    public function index() {
        $status_filter = $this->input->get('status', TRUE) ?? '';
        $query = $this->db
            ->select('or.*, c.name as customer_name, c.phone as customer_phone')
            ->from('order_requests or')
            ->join('customers c', 'c.id = or.customer_id')
            ->order_by('or.created_at', 'DESC');

        if ($status_filter) {
            $query->where('or.status', $status_filter);
        }

        $data['page_title']     = 'Order Requests';
        $data['orders']         = $query->get()->result();
        $data['status_filter']  = $status_filter;
        $data['pending_count']  = $this->db->where('status','pending')->count_all_results('order_requests');
        $this->load->view('layouts/header', $data);
        $this->load->view('order_requests/index', $data);
        $this->load->view('layouts/footer');
    }

    // ── View single order ────────────────────────────────────────────────────
    public function view($id = null) {
        if (!$id) redirect('order_requests');
        $order = $this->db
            ->select('or.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, c.gst_number')
            ->from('order_requests or')
            ->join('customers c', 'c.id = or.customer_id')
            ->where('or.id', $id)
            ->get()->row();

        if (!$order) redirect('order_requests');

        $data['page_title'] = 'Order ' . $order->request_no;
        $data['order']      = $order;
        $data['items']      = $this->db
            ->select('ori.*, p.item_code, GREATEST(p.stock_qty, 0) AS stock_qty', FALSE)
            ->from('order_request_items ori')
            ->join('products p', 'p.id = ori.product_id', 'left')
            ->where('ori.order_id', $id)
            ->get()->result();

        $this->load->view('layouts/header', $data);
        $this->load->view('order_requests/view', $data);
        $this->load->view('layouts/footer');
    }

    // ── Update order status (confirm / cancel) ────────────────────────────────
    public function update_status($id = null) {
        if (!$id || $this->input->server('REQUEST_METHOD') !== 'POST') redirect('order_requests');
        $status = $this->input->post('status', TRUE);
        $allowed = ['pending', 'confirmed', 'cancelled'];
        if (!in_array($status, $allowed)) redirect('order_requests/view/' . $id);

        $this->db->where('id', $id)->update('order_requests', [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->session->set_flashdata('success', 'Order status updated to ' . ucfirst($status) . '.');
        redirect('order_requests/view/' . $id);
    }

    // ── Convert order to invoice (redirect to billing/create with pre-filled data) ──
    public function convert($id = null) {
        if (!$id) redirect('order_requests');
        $order = $this->db->where('id', $id)->get('order_requests')->row();
        if (!$order || $order->status === 'converted' || $order->status === 'cancelled') {
            $this->session->set_flashdata('error', 'Cannot convert this order.');
            redirect('order_requests/view/' . $id);
        }

        $items = $this->db
            ->select('ori.product_id, ori.product_name, ori.mrp, ori.qty, ori.unit, ori.notes, p.selling_price, p.gst_percent, p.hsn_code')
            ->from('order_request_items ori')
            ->join('products p', 'p.id = ori.product_id')
            ->where('ori.order_id', $id)
            ->get()->result();

        // Store conversion data in session and redirect to billing/create
        $this->session->set_userdata('convert_order', [
            'order_id'    => $id,
            'request_no'  => $order->request_no,
            'customer_id' => $order->customer_id,
            'notes'       => $order->notes,
            'items'       => json_decode(json_encode($items), true),
        ]);

        redirect('billing/create');
    }

    // ── AJAX: get pending count (for header badge) ───────────────────────────
    public function pending_count() {
        $count = $this->db->where('status','pending')->count_all_results('order_requests');
        echo json_encode(['count' => $count]);
    }
}
