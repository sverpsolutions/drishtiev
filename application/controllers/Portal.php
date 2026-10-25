<?php
// FILE: application/controllers/Portal.php
// Customer self-service portal: login, invoices, products (MRP only), order requests
defined('BASEPATH') OR exit('No direct script access allowed');

class Portal extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Customer_model', 'Invoice_model', 'Product_model']);
        
        // Auto-patch database for selling_price if missing
        if (!$this->db->field_exists('selling_price', 'order_request_items')) {
            $this->db->query("ALTER TABLE order_request_items ADD COLUMN selling_price decimal(10,2) NOT NULL DEFAULT 0.00 AFTER mrp");
        }
    }

    // ── Auth check helper ────────────────────────────────────────────────────
    private function _require_portal() {
        if (!$this->session->userdata('portal_customer_id')) {
            redirect('portal/login?redirect=' . urlencode(uri_string()));
        }
    }

    // Generate & save remember-me token, set as flashdata for JS to pick up
    private function _generate_remember_token($customer_id) {
        $token = bin2hex(random_bytes(32));
        $this->db->where('id', $customer_id)->update('customers', ['portal_token' => $token]);
        $this->session->set_flashdata('portal_new_token', $token);
        return $token;
    }

    // ── Auto-login via localStorage token (AJAX) ─────────────────────────────
    public function auto_login_ajax() {
        header('Content-Type: application/json');
        $token = $this->input->post('token', TRUE);
        if (!$token || strlen($token) < 32) {
            echo json_encode(['success' => false]);
            return;
        }
        $customer = $this->db->where('portal_token', $token)->where('status', 1)->where('portal_active', 1)->get('customers')->row();
        if (!$customer) {
            echo json_encode(['success' => false]);
            return;
        }
        $this->session->set_userdata([
            'portal_customer_id' => $customer->id,
            'portal_customer'    => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'phone' => $customer->phone,
                'type'  => $customer->type,
            ],
        ]);
        echo json_encode(['success' => true]);
    }

    // ── Login via AJAX (for guest-checkout modal) ────────────────────────────
    public function login_ajax() {
        header('Content-Type: application/json');
        $phone    = trim($this->input->post('phone', TRUE));
        $password = trim($this->input->post('password'));
        $digits10 = substr(preg_replace('/\D/', '', $phone), -10);

        $all = $this->db->where('status', 1)->get('customers')->result();
        $customer = null;
        foreach ($all as $c) {
            if (substr(preg_replace('/\D/', '', $c->phone), -10) === $digits10) {
                $customer = $c; break;
            }
        }

        if (!$customer) { echo json_encode(['success' => false, 'error' => 'Mobile number not registered.']); return; }
        if (!$customer->portal_active) { echo json_encode(['success' => false, 'error' => 'Portal access not enabled. Contact us.']); return; }

        $expected_pwd = substr($digits10, -4);
        if ($password !== $expected_pwd) { echo json_encode(['success' => false, 'error' => 'Incorrect password. Use last 4 digits of your mobile.']); return; }

        $this->session->set_userdata([
            'portal_customer_id' => $customer->id,
            'portal_customer'    => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'phone' => $customer->phone,
                'type'  => $customer->type,
            ],
        ]);
        $token = $this->_generate_remember_token($customer->id);
        echo json_encode(['success' => true, 'token' => $token, 'name' => $customer->name]);
    }

    private function _portal_render($view, $data = []) {
        $data['portal_customer'] = $this->session->userdata('portal_customer');
        $this->load->view('portal/layout_header', $data);
        $this->load->view($view, $data);
        $this->load->view('portal/layout_footer');
    }

    // ── Login ────────────────────────────────────────────────────────────────
    public function login() {
        if ($this->session->userdata('portal_customer_id')) {
            redirect('portal/dashboard');
        }
        $data['page_title'] = 'Customer Login';
        $data['prefill_phone'] = $this->input->get('phone', TRUE) ?? '';
        $data['company'] = $this->Invoice_model->get_company_settings();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->load->view('portal/login', $data);
            return;
        }

        $phone    = trim($this->input->post('phone', TRUE));
        $password = trim($this->input->post('password'));

        // Normalize: strip non-digits, get last 10 digits
        $digits10 = substr(preg_replace('/\D/', '', $phone), -10);

        // Match against stored phone — compare last 10 digits of stored phone
        $all = $this->db->where('status', 1)->get('customers')->result();
        $customer = null;
        foreach ($all as $c) {
            if (substr(preg_replace('/\D/', '', $c->phone), -10) === $digits10) {
                $customer = $c;
                break;
            }
        }

        if (!$customer) {
            $data['error'] = 'Mobile number not registered.';
            $this->load->view('portal/login', $data);
            return;
        }

        if (!$customer->portal_active) {
            $data['error'] = 'Portal access not enabled for your account. Contact us.';
            $this->load->view('portal/login', $data);
            return;
        }

        // Password = last 4 digits of mobile
        $expected_pwd = substr($digits10, -4);
        if ($password !== $expected_pwd) {
            $data['error'] = 'Incorrect password. Use last 4 digits of your mobile number.';
            $this->load->view('portal/login', $data);
            return;
        }

        $this->session->set_userdata([
            'portal_customer_id' => $customer->id,
            'portal_customer'    => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'phone' => $customer->phone,
                'type'  => $customer->type,
            ],
        ]);
        $this->_generate_remember_token($customer->id);
        $redirect_to = $this->input->get('redirect', TRUE) ?? '';
        // Only allow internal redirects
        if ($redirect_to && strpos($redirect_to, 'portal/') === 0) {
            redirect($redirect_to);
        }
        redirect('portal/products');
    }

    public function logout() {
        $this->session->unset_userdata(['portal_customer_id','portal_customer']);
        redirect('portal/products');
    }

    // ── Dashboard ────────────────────────────────────────────────────────────
    public function dashboard() {
        $this->_require_portal();
        $cid = $this->session->userdata('portal_customer_id');
        $customer = $this->db->where('id', $cid)->get('customers')->row();

        $recent_invoices = $this->db
            ->select('i.id, i.invoice_no, i.invoice_date, i.total_amount, i.due_amount, i.status')
            ->from('invoices i')
            ->where('i.customer_id', $cid)
            ->where('i.status !=', 'cancelled')
            ->order_by('i.created_at', 'DESC')
            ->limit(5)
            ->get()->result();

        $pending_orders = $this->db->where('customer_id', $cid)->where('status','pending')->count_all_results('order_requests');
        $total_due      = $customer->balance ?? 0;

        $data['page_title']      = 'My Dashboard';
        $data['customer']        = $customer;
        $data['recent_invoices'] = $recent_invoices;
        $data['pending_orders']  = $pending_orders;
        $data['total_due']       = $total_due;
        $this->_portal_render('portal/dashboard', $data);
    }

    // ── My Invoices ──────────────────────────────────────────────────────────
    public function invoices() {
        $this->_require_portal();
        $cid = $this->session->userdata('portal_customer_id');
        $data['page_title'] = 'My Invoices';
        $data['invoices'] = $this->db
            ->select('i.id, i.invoice_no, i.invoice_date, i.subtotal, i.discount, i.total_gst, i.cd_percent, i.cd_amount, i.total_amount, i.paid_amount, i.due_amount, i.status, i.payment_mode')
            ->from('invoices i')
            ->where('i.customer_id', $cid)
            ->where('i.status !=', 'cancelled')
            ->order_by('i.invoice_date', 'DESC')
            ->get()->result();
        $this->_portal_render('portal/invoices', $data);
    }

    // ── View Single Invoice ──────────────────────────────────────────────────
    public function invoice($id = null) {
        $this->_require_portal();
        if (!$id) redirect('portal/invoices');
        $cid = $this->session->userdata('portal_customer_id');
        $invoice = $this->db->where('id', $id)->where('customer_id', $cid)->get('invoices')->row();
        if (!$invoice) redirect('portal/invoices');
        $data['page_title'] = 'Invoice ' . $invoice->invoice_no;
        $data['invoice']    = $invoice;
        $data['items']      = $this->db->where('invoice_id', $id)->get('invoice_items')->result();
        $data['company']    = $this->Invoice_model->get_company_settings();
        $this->_portal_render('portal/invoice_detail', $data);
    }

    // ── Products (MRP only, no cost) ─────────────────────────────────────────
    public function products() {
        $cid = $this->session->userdata('portal_customer_id');
        $search = $this->input->get('q', TRUE) ?? '';
        $selected_category = $this->input->get('category', TRUE) ?? '';
        $selected_brand = $this->input->get('brand', TRUE) ?? '';
        
        // 1. Fetch products first so Active Record builder state is cleared
        // Fast query — load top 20 on initial load, search via AJAX
        $this->db->select('id, name, item_code, mrp, selling_price, unit, stock_qty, category, brand, image1')
                 ->where('status', 1);
        $this->load->helper('product_search');
        if ($ws = product_words_sql($search, ['name', 'item_code', 'brand', 'category'])) {
            $this->db->where($ws, NULL, FALSE);
        } elseif ($search) {
            $this->db->group_start()
                     ->like('name', $search)
                     ->or_like('item_code', $search)
                     ->group_end();
        }
        if ($selected_category) {
            $this->db->where('category', $selected_category);
        }
        if ($selected_brand) {
            $this->db->where('brand', $selected_brand);
        }
        $limit = $search ? 60 : 20;
        $products = $this->db->order_by('name','ASC')->limit($limit)->get('products')->result();
        
        // 2. Fetch all categories for filtering (clean builder)
        $categories = $this->db->where('status', 1)
                               ->order_by('display_order', 'ASC')
                               ->order_by('name', 'ASC')
                               ->get('categories')
                               ->result();
                               
        // 3. Fetch unique products from customer's past invoices for Buy Again / Last Orders (clean builder)
        $past_products = [];
        if ($cid) {
            $past_products = $this->db->select('products.id, products.name, products.item_code, products.mrp, products.selling_price, products.unit, GREATEST(products.stock_qty, 0) AS stock_qty, products.category, products.brand, products.image1')
                ->from('invoice_items ii')
                ->join('invoices i', 'i.id = ii.invoice_id')
                ->join('products', 'products.id = ii.product_id')
                ->where('i.customer_id', $cid)
                ->where('i.status !=', 'cancelled')
                ->where('products.status', 1)
                ->group_by('products.id')
                ->limit(8)
                ->get()->result();
        }
        
        $data['page_title'] = 'Product Catalogue';
        $data['products']   = $products;
        $data['search']     = $search;
        $data['categories'] = $categories;
        $data['selected_category'] = $selected_category;
        $data['selected_brand'] = $selected_brand;
        $data['past_products'] = $past_products;
        $data['cart']       = $this->session->userdata('portal_cart') ?? [];
        $data['is_home']    = empty($search) && empty($selected_category) && empty($selected_brand);
        
        $this->_portal_render('portal/products', $data);
    }

    // ── Product Details & Reviews ───────────────────────────────────────────
    public function product_detail($id = null) {
        if (!$id) redirect('portal/products');

        $cid = $this->session->userdata('portal_customer_id');
        $product = $this->db->select('id, name, item_code, mrp, selling_price, unit, stock_qty, category, brand, gst_percent, image1, image2, image3, description')
                            ->where('id', $id)
                            ->where('status', 1)
                            ->get('products')->row();
        
        if (!$product) {
            $this->session->set_flashdata('error', 'Product not found.');
            redirect('portal/products');
        }

        // Check if customer has purchased this product (only for logged-in users)
        $purchase_check = null;
        if ($cid) {
            $purchase_check = $this->db->select('ii.id')
                                       ->from('invoice_items ii')
                                       ->join('invoices i', 'i.id = ii.invoice_id')
                                       ->where('i.customer_id', $cid)
                                       ->where('ii.product_id', $id)
                                       ->where('i.status !=', 'cancelled')
                                       ->limit(1)
                                       ->get()->row();
        }
        $data['has_purchased'] = !empty($purchase_check);

        // Fetch all approved reviews
        $reviews = $this->db->where('product_id', $id)
                            ->where('status', 'approved')
                            ->order_by('created_at', 'DESC')
                            ->get('product_reviews')->result();

        // Calculate aggregates
        $total_reviews = count($reviews);
        $avg_rating = 0.0;
        $rating_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        
        if ($total_reviews > 0) {
            $sum_rating = 0;
            foreach ($reviews as $rev) {
                $sum_rating += $rev->rating;
                if (isset($rating_counts[$rev->rating])) {
                    $rating_counts[$rev->rating]++;
                }
            }
            $avg_rating = round($sum_rating / $total_reviews, 1);
        }

        $data['page_title'] = $product->name;
        $data['product'] = $product;
        $data['reviews'] = $reviews;
        $data['total_reviews'] = $total_reviews;
        $data['avg_rating'] = $avg_rating;
        $data['rating_counts'] = $rating_counts;

        $this->_portal_render('portal/product_detail', $data);
    }

    // ── Submit Product Review ───────────────────────────────────────────────
    public function submit_review() {
        $this->_require_portal();
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('portal/products');
        }

        $cid = $this->session->userdata('portal_customer_id');
        $customer = $this->session->userdata('portal_customer');
        $product_id = (int)$this->input->post('product_id', TRUE);
        $rating = (int)$this->input->post('rating', TRUE);
        $title = substr(trim($this->input->post('title', TRUE) ?? ''), 0, 255);
        $text = trim($this->input->post('review_text', TRUE) ?? '');

        // Verify product exists and is active
        $product = $this->db->where('id', $product_id)->where('status', 1)->get('products')->row();
        if (!$product) {
            $this->session->set_flashdata('error', 'Product not found.');
            redirect('portal/products');
        }

        // Validate rating
        if ($rating < 1 || $rating > 5) {
            $this->session->set_flashdata('error', 'Please select a rating between 1 and 5 stars.');
            redirect('portal/product_detail/' . $product_id);
        }

        // Check if customer has purchased this product to flag as verified purchase
        $purchase_check = $this->db->select('ii.id')
                                   ->from('invoice_items ii')
                                   ->join('invoices i', 'i.id = ii.invoice_id')
                                   ->where('i.customer_id', $cid)
                                   ->where('ii.product_id', $product_id)
                                   ->where('i.status !=', 'cancelled')
                                   ->limit(1)
                                   ->get()->row();
        $is_verified = !empty($purchase_check) ? 1 : 0;

        // Save review
        $review_data = [
            'product_id' => $product_id,
            'customer_id' => $cid,
            'customer_name' => $customer['name'] ?? 'Anonymous',
            'rating' => $rating,
            'review_title' => $title,
            'review_text' => $text,
            'status' => 'approved', // Auto-approved by default
            'is_verified_purchase' => $is_verified,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($this->db->insert('product_reviews', $review_data)) {
            $this->session->set_flashdata('success', 'Your review has been submitted successfully! Thank you.');
        } else {
            $this->session->set_flashdata('error', 'Failed to submit review. Please try again.');
        }

        redirect('portal/product_detail/' . $product_id);
    }


    // ── New Order Request ────────────────────────────────────────────────────
    public function order() {
        // POST requires login; GET is allowed for guests (they see cart + login prompt)
        $cid = $this->session->userdata('portal_customer_id');
        if ($this->input->server('REQUEST_METHOD') === 'POST' && !$cid) {
            echo json_encode(['status' => 'error', 'error' => 'login_required']);
            return;
        }

        // Get available products for the order form
        $all_products = $this->db->select('id, name, item_code, mrp, selling_price, unit, stock_qty, gst_percent, image1, image2, image3, category, brand')
                                  ->where('status', 1)
                                  ->where('stock_qty >', 0)
                                  ->order_by('name','ASC')
                                  ->get('products')->result();

        // Get categories sorted by display order
        $categories = $this->db->where('status', 1)
                               ->order_by('display_order', 'ASC')
                               ->order_by('name', 'ASC')
                               ->get('categories')
                               ->result();

        // Get unique products from customer's past invoices for Buy Again / Last Orders
        $past_products = [];
        if ($cid) {
            $past_products = $this->db->select('products.id, products.name, products.item_code, products.mrp, products.unit, GREATEST(products.stock_qty, 0) AS stock_qty, products.category, products.brand, products.image1, products.image2, products.image3')
                ->from('invoice_items ii')
                ->join('invoices i', 'i.id = ii.invoice_id')
                ->join('products', 'products.id = ii.product_id')
                ->where('i.customer_id', $cid)
                ->where('i.status !=', 'cancelled')
                ->where('products.status', 1)
                ->where('products.stock_qty >', 0)
                ->group_by('products.id')
                ->limit(8)
                ->get()->result();
        }

        $data['page_title'] = 'Place Order';
        $data['products']   = $all_products;
        $data['categories'] = $categories;
        $data['past_products'] = $past_products;
        $data['preselect_product_id'] = (int)$this->input->get('product_id', TRUE) ?: 0;
        $data['company'] = $this->Invoice_model->get_company_settings();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->_portal_render('portal/order', $data);
            return;
        }

        // Process submitted order
        $product_ids = $this->input->post('product_id') ?? [];
        $qtys        = $this->input->post('qty') ?? [];
        $notes_list  = $this->input->post('item_notes') ?? [];

        $items = [];
        foreach ($product_ids as $k => $pid) {
            $pid = (int)$pid;
            $qty = max(1, (int)($qtys[$k] ?? 1));
            if (!$pid) continue;
            $prod = $this->db->where('id', $pid)->get('products')->row();
            if (!$prod) continue;
            $sp = (float)($prod->selling_price > 0 ? $prod->selling_price : $prod->mrp);
            $items[] = [
                'product_id'   => $pid,
                'product_name' => $prod->name,
                'mrp'          => $prod->mrp,
                'selling_price'=> $sp,
                'qty'          => $qty,
                'unit'         => $prod->unit,
                'notes'        => substr($this->input->post('item_notes')[$k] ?? '', 0, 255),
            ];
        }

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Add at least one item.');
            $this->_portal_render('portal/order', $data);
            return;
        }

        // Generate request number
        $last = $this->db->select('request_no')->order_by('id','DESC')->limit(1)->get('order_requests')->row();
        $next_num = $last ? ((int)substr($last->request_no, -4) + 1) : 1;
        $request_no = 'ORD-' . date('Y') . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);

        $this->db->insert('order_requests', [
            'request_no'        => $request_no,
            'customer_id'       => $cid,
            'status'            => 'pending',
            'notes'             => $this->input->post('notes', TRUE) ?? '',
            'delivery_charge'   => (float)$this->input->post('delivery_charge', TRUE),
            'delivery_slot'     => $this->input->post('delivery_slot', TRUE),
            'delivery_distance' => (float)$this->input->post('delivery_distance', TRUE),
            'total_amount'      => (float)$this->input->post('total_amount', TRUE),
        ]);
        $order_id = $this->db->insert_id();

        foreach ($items as &$item) { $item['order_id'] = $order_id; }
        $this->db->insert_batch('order_request_items', $items);

        $this->session->unset_userdata('portal_cart');
        $this->session->set_flashdata('success', 'Order ' . $request_no . ' placed! We will confirm it shortly.');

        // Selected payment app (gpay/phonepe/paytm/upi/cod) — confirmation page launches it
        $pay_app = preg_replace('/[^a-z]/', '', strtolower($this->input->post('pay_app', TRUE) ?? ''));
        $suffix = ($pay_app && $pay_app !== 'cod') ? '?pay=' . $pay_app : '';
        redirect('portal/order_confirmation/' . $order_id . $suffix);
    }

    // ── Order Confirmation ───────────────────────────────────────────────────
    public function order_confirmation($id = null) {
        $this->_require_portal();
        if (!$id) redirect('portal/my_orders');
        $cid = $this->session->userdata('portal_customer_id');

        $order = $this->db->where('id', $id)->where('customer_id', $cid)->get('order_requests')->row();
        if (!$order) {
            $this->session->set_flashdata('error', 'Order not found.');
            redirect('portal/my_orders');
        }

        $data['page_title'] = 'Order Confirmation - ' . $order->request_no;
        $data['order']      = $order;
        $data['items']      = $this->db->where('order_id', $id)->get('order_request_items')->result();
        
        // Calculate order items subtotal
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $sp = (float)(isset($item->selling_price) && $item->selling_price > 0 ? $item->selling_price : $item->mrp);
            $subtotal += $sp * $item->qty;
        }
        $data['subtotal']        = $subtotal;
        $data['delivery_charge'] = (float)($order->delivery_charge ?? 0.00);
        $data['total']           = (float)($order->total_amount ?? ($subtotal + $data['delivery_charge']));

        // Fetch company settings for UPI QR Code
        $data['company'] = $this->Invoice_model->get_company_settings();

        // Payment app to auto-launch (from checkout pay button)
        $data['pay_app'] = preg_replace('/[^a-z]/', '', strtolower($this->input->get('pay', TRUE) ?? ''));

        $this->_portal_render('portal/order_confirmation', $data);
    }

    // ── My Orders ────────────────────────────────────────────────────────────
    public function my_orders() {
        $this->_require_portal();
        $cid = $this->session->userdata('portal_customer_id');
        $data['page_title'] = 'My Orders';
        $data['orders'] = $this->db
            ->select('or.*, c.name as customer_name')
            ->from('order_requests or')
            ->join('customers c', 'c.id = or.customer_id')
            ->where('or.customer_id', $cid)
            ->order_by('or.created_at', 'DESC')
            ->get()->result();
        $this->_portal_render('portal/my_orders', $data);
    }

    // ── Order detail ─────────────────────────────────────────────────────────
    public function order_detail($id = null) {
        $this->_require_portal();
        if (!$id) redirect('portal/my_orders');
        $cid = $this->session->userdata('portal_customer_id');
        $order = $this->db->where('id', $id)->where('customer_id', $cid)->get('order_requests')->row();
        if (!$order) redirect('portal/my_orders');
        $data['page_title'] = 'Order ' . $order->request_no;
        $data['order']      = $order;
        $data['items']      = $this->db->where('order_id', $id)->get('order_request_items')->result();
        $this->_portal_render('portal/order_detail', $data);
    }

    // ── Product Search AJAX ──────────────────────────────────────────────────
    public function search_ajax() {
        header('Content-Type: application/json');
        $q    = trim($this->input->get('q', TRUE) ?? '');
        $cat  = trim($this->input->get('category', TRUE) ?? '');
        $brand = trim($this->input->get('brand', TRUE) ?? '');
        $cart = $this->session->userdata('portal_cart') ?? [];

        $this->db->select('id, name, item_code, mrp, selling_price, unit, stock_qty, image1')
                 ->where('status', 1);
        $this->load->helper('product_search');
        if ($ws = product_words_sql($q, ['name', 'item_code', 'brand', 'category'])) {
            $this->db->where($ws, NULL, FALSE);
        } elseif ($q) {
            $this->db->group_start()
                     ->like('name', $q)
                     ->or_like('item_code', $q)
                     ->group_end();
        }
        if ($cat) $this->db->where('category', $cat);
        if ($brand) $this->db->where('brand', $brand);
        $products = $this->db->order_by('name','ASC')->limit(60)->get('products')->result();

        $out = [];
        foreach ($products as $p) {
            $sp  = (float)($p->selling_price > 0 ? $p->selling_price : $p->mrp);
            $mrp = (float)$p->mrp;
            $out[] = [
                'id'       => (int)$p->id,
                'name'     => $p->name,
                'code'     => $p->item_code,
                'mrp'      => $mrp,
                'sp'       => $sp,
                'unit'     => $p->unit,
                'stock'    => max(0, (int)$p->stock_qty),
                'image'    => $p->image1,
                'in_cart'  => isset($cart[$p->id]) ? (int)$cart[$p->id]['qty'] : 0,
                'has_disc' => $sp < $mrp && $sp > 0,
                'save'     => $sp < $mrp ? round($mrp - $sp, 2) : 0,
            ];
        }
        echo json_encode(['status'=>'ok','count'=>count($out),'products'=>$out]);
    }

    // ── Cart AJAX APIs ───────────────────────────────────────────────────────

    public function add_to_cart_ajax() {
        $product_id = (int)$this->input->post('product_id', TRUE);
        if (!$product_id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid product.']);
            return;
        }

        $product = $this->db->where('id', $product_id)->where('status', 1)->get('products')->row();
        if (!$product) {
            echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
            return;
        }

        $cart = $this->session->userdata('portal_cart') ?? [];
        if (isset($cart[$product_id])) {
            $cart[$product_id]['qty']++;
        } else {
            $cart[$product_id] = [
                'qty' => 1,
                'notes' => ''
            ];
        }

        $this->session->set_userdata('portal_cart', $cart);
        echo json_encode([
            'status' => 'success',
            'qty' => $cart[$product_id]['qty'],
            'cart_count' => $this->_get_cart_count($cart)
        ]);
    }

    public function update_cart_ajax() {
        $product_id = (int)$this->input->post('product_id', TRUE);
        $qty = (int)$this->input->post('qty', TRUE);
        $notes = $this->input->post('notes', TRUE) ?? '';

        if (!$product_id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid product.']);
            return;
        }

        $cart = $this->session->userdata('portal_cart') ?? [];

        if ($qty <= 0) {
            if (isset($cart[$product_id])) {
                unset($cart[$product_id]);
            }
        } else {
            if (isset($cart[$product_id])) {
                $cart[$product_id]['qty'] = $qty;
                $cart[$product_id]['notes'] = substr($notes, 0, 255);
            } else {
                $product = $this->db->where('id', $product_id)->where('status', 1)->get('products')->row();
                if (!$product) {
                    echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
                    return;
                }
                $cart[$product_id] = [
                    'qty' => $qty,
                    'notes' => substr($notes, 0, 255)
                ];
            }
        }

        $this->session->set_userdata('portal_cart', $cart);
        echo json_encode([
            'status' => 'success',
            'qty' => isset($cart[$product_id]) ? $cart[$product_id]['qty'] : 0,
            'cart_count' => $this->_get_cart_count($cart)
        ]);
    }

    public function remove_from_cart_ajax() {
        $product_id = (int)$this->input->post('product_id', TRUE);
        if (!$product_id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid product.']);
            return;
        }

        $cart = $this->session->userdata('portal_cart') ?? [];
        if (isset($cart[$product_id])) {
            unset($cart[$product_id]);
        }

        $this->session->set_userdata('portal_cart', $cart);
        echo json_encode([
            'status' => 'success',
            'cart_count' => $this->_get_cart_count($cart)
        ]);
    }

    public function get_cart_ajax() {
        $cart = $this->session->userdata('portal_cart') ?? [];
        $items = [];
        $total_sum = 0;

        if (!empty($cart)) {
            $product_ids = array_keys($cart);
            $this->db->where_in('id', $product_ids);
            $products = $this->db->where('status', 1)->get('products')->result();

            foreach ($products as $p) {
                $qty = $cart[$p->id]['qty'];
                $notes = $cart[$p->id]['notes'] ?? '';
                $sp = (float)($p->selling_price > 0 ? $p->selling_price : $p->mrp);
                $item_total = $sp * $qty;
                $total_sum += $item_total;

                $items[] = [
                    'id'    => (int)$p->id,
                    'name'  => $p->name,
                    'mrp'   => (float)$p->mrp,
                    'price' => $sp,
                    'unit'  => $p->unit,
                    'qty'   => $qty,
                    'notes' => $notes,
                    'image' => $p->image1,
                    'total' => $item_total
                ];
            }
        }

        echo json_encode([
            'status' => 'success',
            'items' => $items,
            'total' => $total_sum,
            'cart_count' => $this->_get_cart_count($cart)
        ]);
    }

    private function _get_cart_count($cart = null) {
        if ($cart === null) {
            $cart = $this->session->userdata('portal_cart') ?? [];
        }
        $count = 0;
        foreach ($cart as $item) {
            $count += $item['qty'];
        }
        return $count;
    }

    // ── OTP Registration & Login ─────────────────────────────────────────────
    
    public function register() {
        if ($this->session->userdata('portal_customer_id')) {
            redirect('portal/dashboard');
        }
        $data['page_title'] = 'Customer Registration';

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->load->view('portal/register', $data);
            return;
        }

        $name    = trim($this->input->post('name', TRUE));
        $phone   = trim($this->input->post('phone', TRUE));
        $email   = trim($this->input->post('email', TRUE));
        $address = trim($this->input->post('address', TRUE));

        if (empty($name) || empty($phone) || empty($email) || empty($address)) {
            $data['error'] = 'All fields (Name, Mobile, Email, and Address) are compulsory.';
            $this->load->view('portal/register', $data);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $data['error'] = 'Invalid email address.';
            $this->load->view('portal/register', $data);
            return;
        }

        // Normalize phone number (last 10 digits)
        $digits10 = substr(preg_replace('/\D/', '', $phone), -10);
        if (strlen($digits10) < 10) {
            $data['error'] = 'Invalid mobile number. Must be at least 10 digits.';
            $this->load->view('portal/register', $data);
            return;
        }

        // Check if phone/email already registered in the DB
        $all = $this->db->get('customers')->result();
        foreach ($all as $c) {
            $c_phone = substr(preg_replace('/\D/', '', $c->phone), -10);
            if ($c_phone === $digits10) {
                $data['error'] = 'Mobile number already registered. Please login.';
                $this->load->view('portal/register', $data);
                return;
            }
            if (!empty($c->email) && strtolower($c->email) === strtolower($email)) {
                $data['error'] = 'Email address already registered. Please login.';
                $this->load->view('portal/register', $data);
                return;
            }
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $expiry = time() + 600; // 10 minutes

        // Save in session
        $this->session->set_userdata('portal_otp_action', [
            'type' => 'register',
            'otp' => $otp,
            'expires' => $expiry,
            'data' => [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address
            ]
        ]);

        // Send Email
        if ($this->_send_otp_email($email, $otp)) {
            $this->session->set_flashdata('success', 'Verification OTP sent to ' . $email);
            redirect('portal/verify_otp');
        } else {
            $data['error'] = 'Failed to send OTP email. Please check SMTP settings or contact support.';
            $this->load->view('portal/register', $data);
        }
    }

    public function send_login_otp() {
        if ($this->session->userdata('portal_customer_id')) {
            redirect('portal/dashboard');
        }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('portal/login');
        }

        $email = trim($this->input->post('email', TRUE));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Invalid email address.');
            redirect('portal/login?tab=email');
        }

        // Find customer by email
        $customer = $this->db->where('email', $email)->where('status', 1)->get('customers')->row();
        if (!$customer) {
            $this->session->set_flashdata('error', 'Email address not registered in system.');
            redirect('portal/login?tab=email');
        }

        if (!$customer->portal_active) {
            $this->session->set_flashdata('error', 'Portal access is disabled for your account. Please contact support.');
            redirect('portal/login?tab=email');
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $expiry = time() + 600; // 10 minutes

        $this->session->set_userdata('portal_otp_action', [
            'type' => 'login',
            'otp' => $otp,
            'expires' => $expiry,
            'customer_id' => $customer->id,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'type' => $customer->type
            ]
        ]);

        if ($this->_send_otp_email($email, $otp)) {
            $this->session->set_flashdata('success', 'Verification OTP sent to ' . $email);
            redirect('portal/verify_otp');
        } else {
            $this->session->set_flashdata('error', 'Failed to send OTP email. Contact support.');
            redirect('portal/login?tab=email');
        }
    }

    public function verify_otp() {
        if ($this->session->userdata('portal_customer_id')) {
            redirect('portal/products');
        }

        $otp_action = $this->session->userdata('portal_otp_action');
        if (!$otp_action) {
            $this->session->set_flashdata('error', 'Session expired. Please request OTP again.');
            redirect('portal/login');
        }

        $data['page_title'] = 'Verify OTP';
        $data['email'] = $otp_action['type'] === 'register' ? $otp_action['data']['email'] : 'your email';

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->load->view('portal/verify_otp', $data);
            return;
        }

        $otp_input = trim($this->input->post('otp', TRUE));
        
        if (time() > $otp_action['expires']) {
            $this->session->unset_userdata('portal_otp_action');
            $data['error'] = 'OTP has expired. Please try again.';
            $this->load->view('portal/verify_otp', $data);
            return;
        }

        if ($otp_input != $otp_action['otp']) {
            $data['error'] = 'Incorrect OTP. Please check your email and try again.';
            $this->load->view('portal/verify_otp', $data);
            return;
        }

        // OTP is correct!
        if ($otp_action['type'] === 'register') {
            $reg_data = $otp_action['data'];
            $this->db->insert('customers', [
                'name' => $reg_data['name'],
                'phone' => $reg_data['phone'],
                'email' => $reg_data['email'],
                'address' => $reg_data['address'],
                'type' => 'retail',
                'status' => 1,
                'portal_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $customer_id = $this->db->insert_id();

            $this->session->set_userdata([
                'portal_customer_id' => $customer_id,
                'portal_customer' => [
                    'id' => $customer_id,
                    'name' => $reg_data['name'],
                    'phone' => $reg_data['phone'],
                    'type' => 'retail'
                ]
            ]);
            $this->session->unset_userdata('portal_otp_action');
            $company_name = $this->db->where('key_name', 'company_name')->get('company_settings')->row()->key_value ?? 'Sandal House';
            $this->session->set_flashdata('success', 'Registration successful! Welcome to ' . $company_name . '.');
            redirect('portal/products');
        } else if ($otp_action['type'] === 'login') {
            $this->session->set_userdata([
                'portal_customer_id' => $otp_action['customer_id'],
                'portal_customer' => $otp_action['customer']
            ]);
            $this->session->unset_userdata('portal_otp_action');
            $this->session->set_flashdata('success', 'Welcome back! You are now signed in.');
            redirect('portal/products');
        }
    }

    // ── Profile Page ─────────────────────────────────────────────────────────
    public function profile() {
        $this->_require_portal();
        $cid      = $this->session->userdata('portal_customer_id');
        $customer = $this->db->where('id', $cid)->get('customers')->row();

        $total_orders   = $this->db->where('customer_id', $cid)->count_all_results('order_requests');
        $total_invoices = $this->db->where('customer_id', $cid)->where('status !=', 'cancelled')->count_all_results('invoices');

        $data['page_title']     = 'My Profile';
        $data['customer']       = $customer;
        $data['total_orders']   = $total_orders;
        $data['total_invoices'] = $total_invoices;
        $this->_portal_render('portal/profile', $data);
    }

    // ── QR Code Scan Landing Page ─────────────────────────────────────────────
    public function scan() {
        if ($this->session->userdata('portal_customer_id')) {
            redirect('portal/dashboard');
        }
        redirect('portal');
    }

    private function _send_otp_email($to, $otp) {
        $company = $this->Invoice_model->get_company_settings();

        $this->load->library('email');
        $this->email->initialize([
            'protocol'    => 'smtp',
            'smtp_host'   => $this->db->where('key_name','smtp_host')->get('company_settings')->row()->key_value ?? 'smtp.gmail.com',
            'smtp_port'   => 587,
            'smtp_user'   => $this->db->where('key_name','smtp_user')->get('company_settings')->row()->key_value ?? '',
            'smtp_pass'   => $this->db->where('key_name','smtp_pass')->get('company_settings')->row()->key_value ?? '',
            'smtp_crypto' => 'tls',
            'mailtype'    => 'html',
            'charset'     => 'utf-8',
            'crlf'        => "\r\n",
            'newline'     => "\r\n"
        ]);

        $company_name = $company['company_name'] ?? 'Sandal House';
        $this->email->from($company['company_email'] ?? 'noreply@sandalhouse.in', $company_name);
        $this->email->to($to);
        $this->email->subject('Your Verification OTP - ' . $company_name);

        $message = "
        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);'>
            <h2 style='color: #2d3748; margin-top: 0;'>Verification Code</h2>
            <p style='color: #4a5568; font-size: 16px;'>Welcome to <strong>{$company_name}</strong> Customer Portal.</p>
            <p style='color: #4a5568; font-size: 16px;'>Please use the following 6-digit One-Time Password (OTP) to complete your verification. This code is valid for 10 minutes.</p>
            <div style='background: #f7fafc; border: 1px solid #edf2f7; border-radius: 8px; padding: 16px; text-align: center; margin: 24px 0;'>
                <span style='font-size: 32px; font-weight: bold; letter-spacing: 4px; color: #e53e3e;'>{$otp}</span>
            </div>
            <p style='color: #718096; font-size: 12px; border-top: 1px solid #edf2f7; padding-top: 16px; margin-bottom: 0;'>If you did not request this verification code, please ignore this email.</p>
        </div>";

        $this->email->message($message);
        return $this->email->send();
    }

    // ── Public Invoice Sharing routes (no login required) ──────────────────
    public function public_invoice($id = null) {
        if (!$id) show_404();
        
        $this->load->model(['Invoice_model', 'Customer_model']);
        $invoice = $this->Invoice_model->get_by_id($id);
        if (!$invoice) show_404();
        
        $data['invoice']  = $invoice;
        $data['items']    = $this->Invoice_model->get_items($id);
        $data['company']  = $this->Invoice_model->get_company_settings();
        $data['customer'] = $this->Customer_model->get_by_id($invoice->customer_id);
        
        // Pass flag indicating this is a public access link
        $data['is_public'] = true;
        
        $this->load->view('billing/print', $data);
    }

    public function public_pdf($id = null) {
        if (!$id) show_404();
        $this->load->model(['Invoice_model', 'Customer_model']);
        $invoice = $this->Invoice_model->get_by_id($id);
        if (!$invoice) show_404();
        
        $data['invoice']  = $invoice;
        $data['items']    = $this->Invoice_model->get_items($id);
        $data['company']  = $this->Invoice_model->get_company_settings();
        $data['customer'] = $this->Customer_model->get_by_id($invoice->customer_id);
        
        if (!class_exists('Mpdf\\Mpdf')) {
            redirect(base_url('portal/public_invoice/' . $id));
        }
        $this->load->library('pdf_generator');
        $html     = $this->load->view('billing/pdf_template', $data, TRUE);
        $filename = 'Invoice_' . $invoice->invoice_no . '_' . date('Ymd');
        $this->pdf_generator->generate($html, $filename, 'inline');
    }
}
