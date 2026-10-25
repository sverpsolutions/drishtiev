<?php
// FILE: application/controllers/Store.php
// New premium "Blinkit-style" customer storefront. Lives entirely alongside the
// existing Portal.php (customer portal) — different route prefix (store/...),
// different session keys (store_*), different views (views/store/*). Reuses the
// SAME underlying tables (products, categories, brands, customers, order_requests,
// order_request_items, product_reviews) so both sites' orders land in the same
// staff-side "Order Requests" admin queue. See CLAUDE.md / project plan for the
// full non-collision naming registry — do not reuse portal_* session keys here.
defined('BASEPATH') OR exit('No direct script access allowed');

class Store extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Invoice_model', 'Product_model', 'Store_model']);
    }

    // ── Auth check helper ────────────────────────────────────────────────────
    private function _require_store() {
        if (!$this->session->userdata('store_customer_id')) {
            redirect('store/login?redirect=' . urlencode(uri_string()));
        }
    }

    private function _store_render($view, $data = []) {
        $data['store_customer'] = $this->session->userdata('store_customer');
        $data['cart_count']     = $this->_get_cart_count();
        $data['company']        = $this->Invoice_model->get_company_settings();
        $this->load->view('store/layout_header', $data);
        $this->load->view($view, $data);
        $this->load->view('store/layout_footer', $data);
    }

    private function _get_cart_count($cart = null) {
        if ($cart === null) $cart = $this->session->userdata('store_cart') ?? [];
        $count = 0;
        foreach ($cart as $item) $count += $item['qty'];
        return $count;
    }

    // Generate & save remember-me token (shares the customers.portal_token column
    // with the old portal — it's just an auth artifact on a shared table, not
    // portal-specific state, so reusing it is safe and avoids a schema change).
    private function _generate_remember_token($customer_id) {
        $token = bin2hex(random_bytes(32));
        $this->db->where('id', $customer_id)->update('customers', ['portal_token' => $token]);
        return $token;
    }

    // ============================================================
    // Stage B — Catalog
    // ============================================================

    public function home() {
        $cid = $this->session->userdata('store_customer_id');
        $data['page_title']   = 'Home';
        $data['categories']   = $this->Store_model->get_categories();
        $data['products']     = $this->Store_model->get_home_products(24);
        $data['past_products'] = $cid ? $this->Store_model->get_past_products($cid, 8) : [];
        $data['cart']         = $this->session->userdata('store_cart') ?? [];
        $this->_store_render('store/home', $data);
    }

    public function products() {
        $search   = trim($this->input->get('q', TRUE) ?? '');
        $category = (int)($this->input->get('category', TRUE) ?? 0);
        $data['page_title']       = $search ? 'Search: ' . $search : 'All Products';
        $data['products']         = $this->Store_model->search_products($search, $category, 60);
        $data['categories']       = $this->Store_model->get_categories();
        $data['search']           = $search;
        $data['selected_category']= $category;
        $data['cart']             = $this->session->userdata('store_cart') ?? [];
        $this->_store_render('store/category', $data);
    }

    public function category($id = null) {
        $id = (int)$id;
        $cat = $this->Store_model->get_category($id);
        if (!$cat) { redirect('store'); }
        $data['page_title']        = $cat->name;
        $data['category']          = $cat;
        $data['products']          = $this->Store_model->search_products('', $id, 60);
        $data['categories']        = $this->Store_model->get_categories();
        $data['search']            = '';
        $data['selected_category'] = $id;
        $data['cart']              = $this->session->userdata('store_cart') ?? [];
        $this->_store_render('store/category', $data);
    }

    public function product_detail($id = null) {
        if (!$id) redirect('store');
        $product = $this->Store_model->get_product_detail($id);
        if (!$product) {
            $this->session->set_flashdata('error', 'Product not found.');
            redirect('store');
        }

        $cid = $this->session->userdata('store_customer_id');
        $has_purchased = $cid ? $this->Store_model->has_purchased($cid, $id) : false;
        $reviews = $this->Store_model->get_reviews($id);

        $total_reviews = count($reviews);
        $avg_rating = 0.0;
        $rating_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        if ($total_reviews > 0) {
            $sum = 0;
            foreach ($reviews as $r) { $sum += $r->rating; if (isset($rating_counts[$r->rating])) $rating_counts[$r->rating]++; }
            $avg_rating = round($sum / $total_reviews, 1);
        }

        $data['page_title']     = $product->name;
        $data['product']        = $product;
        $data['has_purchased']  = $has_purchased;
        $data['reviews']        = $reviews;
        $data['total_reviews']  = $total_reviews;
        $data['avg_rating']     = $avg_rating;
        $data['rating_counts']  = $rating_counts;
        $data['related']        = $this->Store_model->get_related_products($product->category_id, $id, 8);
        $data['cart']           = $this->session->userdata('store_cart') ?? [];
        $this->_store_render('store/product_detail', $data);
    }

    public function search_ajax() {
        header('Content-Type: application/json');
        $q   = trim($this->input->get('q', TRUE) ?? '');
        $cat = (int)($this->input->get('category', TRUE) ?? 0);
        $cart = $this->session->userdata('store_cart') ?? [];

        $products = $this->Store_model->search_products($q, $cat, 60);
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
                'stock'    => (int)$p->stock_qty,
                'image'    => $p->image1,
                'is_veg'   => (int)($p->is_veg ?? 1),
                'in_cart'  => isset($cart[$p->id]) ? (int)$cart[$p->id]['qty'] : 0,
                'has_disc' => $sp < $mrp && $sp > 0,
                'save_pct' => ($sp > 0 && $sp < $mrp) ? round((($mrp - $sp) / $mrp) * 100) : 0,
            ];
        }
        echo json_encode(['status' => 'ok', 'count' => count($out), 'products' => $out]);
    }

    // ============================================================
    // Stage C — Cart, checkout, order pipeline
    // ============================================================

    public function add_to_cart_ajax() {
        header('Content-Type: application/json');
        $product_id = (int)$this->input->post('product_id', TRUE);
        if (!$product_id) { echo json_encode(['status' => 'error', 'message' => 'Invalid product.']); return; }

        $product = $this->Store_model->get_product_min($product_id);
        if (!$product) { echo json_encode(['status' => 'error', 'message' => 'Product not found.']); return; }

        $cart = $this->session->userdata('store_cart') ?? [];
        if (isset($cart[$product_id])) {
            $cart[$product_id]['qty']++;
        } else {
            $cart[$product_id] = ['qty' => 1, 'notes' => ''];
        }
        $this->session->set_userdata('store_cart', $cart);
        echo json_encode(['status' => 'success', 'qty' => $cart[$product_id]['qty'], 'cart_count' => $this->_get_cart_count($cart)]);
    }

    public function update_cart_ajax() {
        header('Content-Type: application/json');
        $product_id = (int)$this->input->post('product_id', TRUE);
        $qty   = (int)$this->input->post('qty', TRUE);
        $notes = $this->input->post('notes', TRUE) ?? '';
        if (!$product_id) { echo json_encode(['status' => 'error', 'message' => 'Invalid product.']); return; }

        $cart = $this->session->userdata('store_cart') ?? [];
        if ($qty <= 0) {
            unset($cart[$product_id]);
        } else {
            if (!isset($cart[$product_id]) && !$this->Store_model->get_product_min($product_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Product not found.']); return;
            }
            $cart[$product_id] = ['qty' => $qty, 'notes' => substr($notes, 0, 255)];
        }
        $this->session->set_userdata('store_cart', $cart);
        echo json_encode(['status' => 'success', 'qty' => $cart[$product_id]['qty'] ?? 0, 'cart_count' => $this->_get_cart_count($cart)]);
    }

    public function remove_from_cart_ajax() {
        header('Content-Type: application/json');
        $product_id = (int)$this->input->post('product_id', TRUE);
        $cart = $this->session->userdata('store_cart') ?? [];
        unset($cart[$product_id]);
        $this->session->set_userdata('store_cart', $cart);
        echo json_encode(['status' => 'success', 'cart_count' => $this->_get_cart_count($cart)]);
    }

    public function get_cart_ajax() {
        header('Content-Type: application/json');
        $cart = $this->session->userdata('store_cart') ?? [];
        $items = [];
        $total = 0;
        if (!empty($cart)) {
            $products = $this->Store_model->get_products_by_ids(array_keys($cart));
            foreach ($products as $p) {
                $qty   = $cart[$p->id]['qty'];
                $notes = $cart[$p->id]['notes'] ?? '';
                $sp    = (float)($p->selling_price > 0 ? $p->selling_price : $p->mrp);
                $line  = $sp * $qty;
                $total += $line;
                $items[] = ['id' => (int)$p->id, 'name' => $p->name, 'mrp' => (float)$p->mrp, 'price' => $sp,
                    'unit' => $p->unit, 'qty' => $qty, 'notes' => $notes, 'image' => $p->image1, 'total' => $line];
            }
        }
        echo json_encode(['status' => 'success', 'items' => $items, 'total' => $total, 'cart_count' => $this->_get_cart_count($cart)]);
    }

    public function cart() {
        $cart = $this->session->userdata('store_cart') ?? [];
        $items = [];
        $subtotal = 0;
        if (!empty($cart)) {
            $products = $this->Store_model->get_products_by_ids(array_keys($cart));
            foreach ($products as $p) {
                $qty  = $cart[$p->id]['qty'];
                $sp   = (float)($p->selling_price > 0 ? $p->selling_price : $p->mrp);
                $line = $sp * $qty;
                $subtotal += $line;
                $items[] = (object)['product' => $p, 'qty' => $qty, 'notes' => $cart[$p->id]['notes'] ?? '', 'price' => $sp, 'total' => $line];
            }
        }
        $data['page_title'] = 'My Cart';
        $data['items']      = $items;
        $data['subtotal']   = $subtotal;
        $this->_store_render('store/cart', $data);
    }

    public function checkout() {
        $cid = $this->session->userdata('store_customer_id');
        if ($this->input->server('REQUEST_METHOD') === 'POST' && !$cid) {
            echo json_encode(['status' => 'error', 'error' => 'login_required']);
            return;
        }

        $cart = $this->session->userdata('store_cart') ?? [];
        if (empty($cart)) redirect('store/cart');

        $products = $this->Store_model->get_products_by_ids(array_keys($cart));
        $items = [];
        $subtotal = 0;
        foreach ($products as $p) {
            $qty  = $cart[$p->id]['qty'];
            $sp   = (float)($p->selling_price > 0 ? $p->selling_price : $p->mrp);
            $line = $sp * $qty;
            $subtotal += $line;
            $items[] = (object)['product' => $p, 'qty' => $qty, 'notes' => $cart[$p->id]['notes'] ?? '', 'price' => $sp, 'total' => $line];
        }

        $data['page_title'] = 'Checkout';
        $data['items']      = $items;
        $data['subtotal']   = $subtotal;

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->_store_render('store/checkout', $data);
            return;
        }

        // ── Place order: writes into the SAME order_requests/order_request_items
        // tables the old portal uses, so it shows up in the existing staff-side
        // "Order Requests" admin queue via the unmodified OrderRequests::convert() flow.
        $delivery_charge = (float)$this->input->post('delivery_charge', TRUE);
        $delivery_slot   = $this->input->post('delivery_slot', TRUE);
        $total_amount    = $subtotal + $delivery_charge;

        // request_no generator must match Portal.php's exact scheme since both
        // controllers write into the same shared table (same pre-existing race
        // condition as today — not introduced by this feature).
        $last = $this->db->select('request_no')->order_by('id', 'DESC')->limit(1)->get('order_requests')->row();
        $next_num = $last ? ((int)substr($last->request_no, -4) + 1) : 1;
        $request_no = 'ORD-' . date('Y') . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);

        $this->db->insert('order_requests', [
            'request_no'        => $request_no,
            'customer_id'       => $cid,
            'status'            => 'pending',
            'notes'             => $this->input->post('notes', TRUE) ?? '',
            'delivery_charge'   => $delivery_charge,
            'delivery_slot'     => $delivery_slot,
            'delivery_distance' => (float)$this->input->post('delivery_distance', TRUE),
            'total_amount'      => $total_amount,
        ]);
        $order_id = $this->db->insert_id();

        $insert_items = [];
        foreach ($items as $it) {
            $insert_items[] = [
                'order_id'      => $order_id,
                'product_id'    => $it->product->id,
                'product_name'  => $it->product->name,
                'mrp'           => $it->product->mrp,
                'selling_price' => $it->price,
                'qty'           => $it->qty,
                'unit'          => $it->product->unit,
                'notes'         => substr($it->notes, 0, 255),
            ];
        }
        $this->db->insert_batch('order_request_items', $insert_items);

        $this->session->unset_userdata('store_cart');
        $this->session->set_flashdata('success', 'Order ' . $request_no . ' placed! We will confirm it shortly.');

        $pay_app = preg_replace('/[^a-z]/', '', strtolower($this->input->post('pay_app', TRUE) ?? ''));
        $suffix  = ($pay_app && $pay_app !== 'cod') ? '?pay=' . $pay_app : '';
        redirect('store/order_confirmation/' . $order_id . $suffix);
    }

    public function order_confirmation($id = null) {
        $this->_require_store();
        if (!$id) redirect('store/my_orders');
        $cid = $this->session->userdata('store_customer_id');

        $order = $this->db->where('id', $id)->where('customer_id', $cid)->get('order_requests')->row();
        if (!$order) { $this->session->set_flashdata('error', 'Order not found.'); redirect('store/my_orders'); }

        $data['page_title'] = 'Order Confirmed — ' . $order->request_no;
        $data['order']      = $order;
        $data['items']      = $this->db->where('order_id', $id)->get('order_request_items')->result();

        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $sp = (float)((isset($item->selling_price) && $item->selling_price > 0) ? $item->selling_price : $item->mrp);
            $subtotal += $sp * $item->qty;
        }
        $data['subtotal']        = $subtotal;
        $data['delivery_charge'] = (float)($order->delivery_charge ?? 0);
        $data['total']           = (float)($order->total_amount ?? ($subtotal + $data['delivery_charge']));
        $data['pay_app']         = preg_replace('/[^a-z]/', '', strtolower($this->input->get('pay', TRUE) ?? ''));

        $this->_store_render('store/order_confirmation', $data);
    }

    // ============================================================
    // Stage D — Auth, order history, profile
    // ============================================================

    public function login() {
        if ($this->session->userdata('store_customer_id')) redirect('store/home');
        $data['page_title']    = 'Login';
        $data['prefill_phone'] = $this->input->get('phone', TRUE) ?? '';

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->load->view('store/login', $data);
            return;
        }

        $phone    = trim($this->input->post('phone', TRUE));
        $password = trim($this->input->post('password'));
        $digits10 = substr(preg_replace('/\D/', '', $phone), -10);

        $all = $this->db->where('status', 1)->get('customers')->result();
        $customer = null;
        foreach ($all as $c) {
            if (substr(preg_replace('/\D/', '', $c->phone), -10) === $digits10) { $customer = $c; break; }
        }

        if (!$customer) { $data['error'] = 'Mobile number not registered.'; $this->load->view('store/login', $data); return; }
        if (!$customer->portal_active) { $data['error'] = 'Store access not enabled for your account. Contact us.'; $this->load->view('store/login', $data); return; }

        $expected_pwd = substr($digits10, -4);
        if ($password !== $expected_pwd) { $data['error'] = 'Incorrect password. Use last 4 digits of your mobile number.'; $this->load->view('store/login', $data); return; }

        $this->session->set_userdata([
            'store_customer_id' => $customer->id,
            'store_customer'    => ['id' => $customer->id, 'name' => $customer->name, 'phone' => $customer->phone, 'type' => $customer->type],
        ]);
        $this->_generate_remember_token($customer->id);

        $redirect_to = $this->input->get('redirect', TRUE) ?? '';
        if ($redirect_to && strpos($redirect_to, 'store/') === 0) redirect($redirect_to);
        redirect('store/home');
    }

    public function logout() {
        $this->session->unset_userdata(['store_customer_id', 'store_customer']);
        redirect('store/home');
    }

    public function register() {
        if ($this->session->userdata('store_customer_id')) redirect('store/home');
        $data['page_title'] = 'Create Account';

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->load->view('store/register', $data);
            return;
        }

        $name    = trim($this->input->post('name', TRUE));
        $phone   = trim($this->input->post('phone', TRUE));
        $email   = trim($this->input->post('email', TRUE));
        $address = trim($this->input->post('address', TRUE));

        if (empty($name) || empty($phone) || empty($email) || empty($address)) {
            $data['error'] = 'All fields (Name, Mobile, Email, and Address) are compulsory.';
            $this->load->view('store/register', $data); return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $data['error'] = 'Invalid email address.'; $this->load->view('store/register', $data); return;
        }
        $digits10 = substr(preg_replace('/\D/', '', $phone), -10);
        if (strlen($digits10) < 10) {
            $data['error'] = 'Invalid mobile number. Must be at least 10 digits.'; $this->load->view('store/register', $data); return;
        }

        $all = $this->db->get('customers')->result();
        foreach ($all as $c) {
            $c_phone = substr(preg_replace('/\D/', '', $c->phone), -10);
            if ($c_phone === $digits10) { $data['error'] = 'Mobile number already registered. Please login.'; $this->load->view('store/register', $data); return; }
            if (!empty($c->email) && strtolower($c->email) === strtolower($email)) { $data['error'] = 'Email address already registered. Please login.'; $this->load->view('store/register', $data); return; }
        }

        $otp = rand(100000, 999999);
        $this->session->set_userdata('store_otp_action', [
            'type' => 'register', 'otp' => $otp, 'expires' => time() + 600,
            'data' => ['name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address],
        ]);

        if ($this->_send_otp_email($email, $otp)) {
            $this->session->set_flashdata('success', 'Verification OTP sent to ' . $email);
            redirect('store/verify_otp');
        } else {
            $data['error'] = 'Failed to send OTP email. Please check SMTP settings or contact support.';
            $this->load->view('store/register', $data);
        }
    }

    public function send_login_otp() {
        if ($this->session->userdata('store_customer_id')) redirect('store/home');
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('store/login');

        $email = trim($this->input->post('email', TRUE));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Invalid email address.'); redirect('store/login?tab=email');
        }

        $customer = $this->db->where('email', $email)->where('status', 1)->get('customers')->row();
        if (!$customer) { $this->session->set_flashdata('error', 'Email address not registered in system.'); redirect('store/login?tab=email'); }
        if (!$customer->portal_active) { $this->session->set_flashdata('error', 'Store access is disabled for your account. Please contact support.'); redirect('store/login?tab=email'); }

        $otp = rand(100000, 999999);
        $this->session->set_userdata('store_otp_action', [
            'type' => 'login', 'otp' => $otp, 'expires' => time() + 600,
            'customer_id' => $customer->id,
            'customer' => ['id' => $customer->id, 'name' => $customer->name, 'phone' => $customer->phone, 'type' => $customer->type],
        ]);

        if ($this->_send_otp_email($email, $otp)) {
            $this->session->set_flashdata('success', 'Verification OTP sent to ' . $email);
            redirect('store/verify_otp');
        } else {
            $this->session->set_flashdata('error', 'Failed to send OTP email. Contact support.');
            redirect('store/login?tab=email');
        }
    }

    public function verify_otp() {
        if ($this->session->userdata('store_customer_id')) redirect('store/home');

        $otp_action = $this->session->userdata('store_otp_action');
        if (!$otp_action) { $this->session->set_flashdata('error', 'Session expired. Please request OTP again.'); redirect('store/login'); }

        $data['page_title'] = 'Verify OTP';
        $data['email'] = $otp_action['type'] === 'register' ? $otp_action['data']['email'] : 'your email';

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->load->view('store/verify_otp', $data);
            return;
        }

        $otp_input = trim($this->input->post('otp', TRUE));
        if (time() > $otp_action['expires']) {
            $this->session->unset_userdata('store_otp_action');
            $data['error'] = 'OTP has expired. Please try again.';
            $this->load->view('store/verify_otp', $data); return;
        }
        if ($otp_input != $otp_action['otp']) {
            $data['error'] = 'Incorrect OTP. Please check your email and try again.';
            $this->load->view('store/verify_otp', $data); return;
        }

        if ($otp_action['type'] === 'register') {
            $reg = $otp_action['data'];
            $this->db->insert('customers', [
                'name' => $reg['name'], 'phone' => $reg['phone'], 'email' => $reg['email'], 'address' => $reg['address'],
                'type' => 'retail', 'status' => 1, 'portal_active' => 1,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $customer_id = $this->db->insert_id();
            $this->session->set_userdata([
                'store_customer_id' => $customer_id,
                'store_customer'    => ['id' => $customer_id, 'name' => $reg['name'], 'phone' => $reg['phone'], 'type' => 'retail'],
            ]);
            $this->session->unset_userdata('store_otp_action');
            $company_name = $this->db->where('key_name', 'company_name')->get('company_settings')->row()->key_value ?? 'our store';
            $this->session->set_flashdata('success', 'Registration successful! Welcome to ' . $company_name . '.');
            redirect('store/home');
        } elseif ($otp_action['type'] === 'login') {
            $this->session->set_userdata([
                'store_customer_id' => $otp_action['customer_id'],
                'store_customer'    => $otp_action['customer'],
            ]);
            $this->session->unset_userdata('store_otp_action');
            $this->session->set_flashdata('success', 'Welcome back! You are now signed in.');
            redirect('store/home');
        }
    }

    public function my_orders() {
        $this->_require_store();
        $cid = $this->session->userdata('store_customer_id');
        $data['page_title'] = 'My Orders';
        $data['orders'] = $this->db->where('customer_id', $cid)->order_by('created_at', 'DESC')->get('order_requests')->result();
        $this->_store_render('store/my_orders', $data);
    }

    public function order_detail($id = null) {
        $this->_require_store();
        if (!$id) redirect('store/my_orders');
        $cid = $this->session->userdata('store_customer_id');
        $order = $this->db->where('id', $id)->where('customer_id', $cid)->get('order_requests')->row();
        if (!$order) redirect('store/my_orders');
        $data['page_title'] = 'Order ' . $order->request_no;
        $data['order']      = $order;
        $data['items']      = $this->db->where('order_id', $id)->get('order_request_items')->result();
        $this->_store_render('store/order_detail', $data);
    }

    public function profile() {
        $this->_require_store();
        $cid = $this->session->userdata('store_customer_id');
        $customer = $this->db->where('id', $cid)->get('customers')->row();
        $data['page_title']   = 'My Profile';
        $data['customer']     = $customer;
        $data['total_orders'] = $this->db->where('customer_id', $cid)->count_all_results('order_requests');
        $this->_store_render('store/profile', $data);
    }

    private function _send_otp_email($to, $otp) {
        $company = $this->Invoice_model->get_company_settings();
        $this->load->library('email');
        $this->email->initialize([
            'protocol'    => 'smtp',
            'smtp_host'   => $this->db->where('key_name', 'smtp_host')->get('company_settings')->row()->key_value ?? 'smtp.gmail.com',
            'smtp_port'   => 587,
            'smtp_user'   => $this->db->where('key_name', 'smtp_user')->get('company_settings')->row()->key_value ?? '',
            'smtp_pass'   => $this->db->where('key_name', 'smtp_pass')->get('company_settings')->row()->key_value ?? '',
            'smtp_crypto' => 'tls', 'mailtype' => 'html', 'charset' => 'utf-8', 'crlf' => "\r\n", 'newline' => "\r\n",
        ]);
        $company_name = $company['company_name'] ?? 'our store';
        $this->email->from($company['company_email'] ?? 'noreply@example.com', $company_name);
        $this->email->to($to);
        $this->email->subject('Your Verification OTP - ' . $company_name);
        $message = "
        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;'>
            <h2 style='color: #2d3748; margin-top: 0;'>Verification Code</h2>
            <p style='color: #4a5568; font-size: 16px;'>Welcome to <strong>{$company_name}</strong>.</p>
            <p style='color: #4a5568; font-size: 16px;'>Use this 6-digit code to verify — valid for 10 minutes.</p>
            <div style='background: #f7fafc; border: 1px solid #edf2f7; border-radius: 8px; padding: 16px; text-align: center; margin: 24px 0;'>
                <span style='font-size: 32px; font-weight: bold; letter-spacing: 4px; color: #0DB14B;'>{$otp}</span>
            </div>
            <p style='color: #718096; font-size: 12px; border-top: 1px solid #edf2f7; padding-top: 16px; margin-bottom: 0;'>If you did not request this code, please ignore this email.</p>
        </div>";
        $this->email->message($message);
        return $this->email->send();
    }

    // ============================================================
    // Stage E — Product reviews
    // ============================================================

    public function submit_review() {
        $this->_require_store();
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('store');

        $cid = $this->session->userdata('store_customer_id');
        $customer = $this->session->userdata('store_customer');
        $product_id = (int)$this->input->post('product_id', TRUE);
        $rating = (int)$this->input->post('rating', TRUE);
        $title  = substr(trim($this->input->post('title', TRUE) ?? ''), 0, 255);
        $text   = trim($this->input->post('review_text', TRUE) ?? '');

        $product = $this->Store_model->get_product_min($product_id);
        if (!$product) { $this->session->set_flashdata('error', 'Product not found.'); redirect('store'); }
        if ($rating < 1 || $rating > 5) { $this->session->set_flashdata('error', 'Please select a rating between 1 and 5 stars.'); redirect('store/product/' . $product_id); }

        $is_verified = $this->Store_model->has_purchased($cid, $product_id) ? 1 : 0;

        $this->db->insert('product_reviews', [
            'product_id' => $product_id, 'customer_id' => $cid, 'customer_name' => $customer['name'] ?? 'Anonymous',
            'rating' => $rating, 'review_title' => $title, 'review_text' => $text,
            'status' => 'approved', 'is_verified_purchase' => $is_verified, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->session->set_flashdata('success', 'Your review has been submitted. Thank you!');
        redirect('store/product/' . $product_id);
    }
}
