<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shop — public Suvasan Clothes storefront (site root).
 * Session keys: web_cart, web_customer, web_coupon, web_checkout_key, web_my_orders.
 * All money/stock is computed server-side in Webstore_model; nothing from the
 * browser except product/variant ids and quantities is trusted.
 */
class Shop extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Webstore_model', 'ws');
        $this->load->helper('shop');
        $m = $this->router->method;
        if ($this->ws->s('web_maintenance', '0') === '1' && !$this->session->userdata('user_id') && !in_array($m, ['razorpay_webhook', 'robots'], true)) {
            $this->output->set_status_header(503);
            echo $this->load->view('shop/maintenance', ['company' => $this->ws->settings()], true);
            exit;
        }
    }

    // ── Rendering ────────────────────────────────────────────────────────────

    private function _render($view, $data = []) {
        $data['company']   = $this->ws->settings();
        $data['customer']  = $this->session->userdata('web_customer');
        $data['cart_count'] = array_sum(array_column($this->_cart(), 'q'));
        $data['nav']       = $this->_nav();
        $data += ['meta_title' => '', 'meta_desc' => '', 'canonical' => current_url(), 'og_image' => base_url('assets/web/og-image.jpg'), 'noindex' => false];
        $this->load->view('shop/_header', $data);
        $this->load->view('shop/' . $view, $data);
        $this->load->view('shop/_footer', $data);
    }

    private function _nav() {
        static $nav;
        if ($nav === null) $nav = $this->ws->facets();
        return $nav;
    }

    private function _json($arr, $code = 200) {
        $this->output->set_status_header($code)->set_content_type('application/json')->set_output(json_encode($arr));
    }

    public function not_found() {
        $this->output->set_status_header(404);
        $this->_render('404', ['meta_title' => 'Page not found', 'noindex' => true]);
    }

    // ── Catalog ──────────────────────────────────────────────────────────────

    public function index() {
        $data['banners']   = $this->ws->active_banners();
        $data['new']       = $this->ws->catalog(['sort' => 'newest', 'per_page' => 8])['rows'];
        $data['best']      = $this->ws->catalog(['sort' => 'popular', 'per_page' => 8])['rows'];
        $data['featured']  = $this->ws->catalog(['featured' => 1, 'per_page' => 8])['rows'];
        $data['sale']      = $this->ws->catalog(['sale' => 1, 'in_stock' => 1, 'sort' => 'discount', 'per_page' => 8])['rows'];
        $data['about']     = $this->ws->page('about');
        $data['gender_counts'] = [];
        foreach (['Women', 'Men', 'Kids'] as $g) $data['gender_counts'][$g] = $this->ws->catalog(['gender' => $g, 'per_page' => 1])['total'];
        $data['meta_title'] = $this->ws->s('web_meta_title', $this->ws->s('company_name'));
        $data['meta_desc']  = $this->ws->s('web_meta_description');
        $data['canonical']  = base_url();
        $this->_render('home', $data);
    }

    public function listing($category_id = null) {
        $g = $this->input->get(null, true) ?: [];
        $f = [
            'q' => trim($g['q'] ?? ''), 'category' => (int)($category_id ?: ($g['category'] ?? 0)),
            'sub_category' => (int)($g['sub'] ?? 0), 'brand' => array_filter((array)($g['brand'] ?? [])),
            'gender' => in_array($g['gender'] ?? '', ['Men', 'Women', 'Kids', 'Boys', 'Girls', 'Unisex'], true) ? $g['gender'] : '',
            'size' => array_filter((array)($g['size'] ?? [])), 'colour' => array_filter((array)($g['colour'] ?? [])),
            'min' => is_numeric($g['min'] ?? '') ? $g['min'] : '', 'max' => is_numeric($g['max'] ?? '') ? $g['max'] : '',
            'in_stock' => !empty($g['in_stock']), 'sale' => !empty($g['sale']), 'featured' => !empty($g['featured']),
            'sort' => $g['sort'] ?? '', 'page' => max(1, (int)($g['page'] ?? 1)), 'per_page' => 24,
        ];
        $res = $this->ws->catalog($f);
        $cat = $f['category'] ? $this->db->get_where('categories', ['id' => $f['category'], 'status' => 1])->row() : null;
        if ($category_id && !$cat) return $this->not_found();

        $title = $cat ? $cat->name : ($f['q'] !== '' ? 'Search results for “' . $f['q'] . '”' : ($f['gender'] ? $f['gender'] . "'s Collection" : ($f['sale'] ? 'Special Offers' : 'All Products')));
        $data = ['f' => $f, 'rows' => $res['rows'], 'total' => $res['total'], 'cat' => $cat, 'heading' => $title,
                 'pages' => (int)ceil($res['total'] / $f['per_page'])];
        $data['meta_title'] = $title . ' | ' . ucwords(strtolower($this->ws->s('company_name')));
        $data['meta_desc']  = $cat ? "Shop {$cat->name} at " . ucwords(strtolower($this->ws->s('company_name'))) . ', Mathura.' : $this->ws->s('web_meta_description');
        $data['canonical']  = $cat ? category_url($cat) . ($f['page'] > 1 ? '?page=' . $f['page'] : '') : site_url('shop');
        $data['noindex']    = $f['q'] !== '' || count($g) > ($f['page'] > 1 ? 1 : 0) && !$cat;
        $this->_render('listing', $data);
    }

    public function product($slug_id = '') {
        if (!preg_match('/-(\d+)$/', (string)$slug_id, $m) && !ctype_digit((string)$slug_id)) return $this->not_found();
        $id = isset($m[1]) ? (int)$m[1] : (int)$slug_id;
        $p = $this->ws->get_product($id);
        if (!$p) return $this->not_found();
        if ($slug_id !== shop_slug($p->name) . '-' . $p->id) redirect(product_url($p), 'location', 301);

        $data['p'] = $p;
        $data['related'] = $this->ws->related($p, 8);
        $data['reviews'] = $this->db->where('product_id', $p->id)->where('status', 'approved')->order_by('id', 'DESC')->limit(20)->get('product_reviews')->result();
        $data['size_guide'] = $this->ws->page('size-guide');
        $data['meta_title'] = $p->name . ' | ' . ucwords(strtolower($this->ws->s('company_name')));
        $data['meta_desc']  = mb_substr(trim(strip_tags($p->description ?: ($p->name . ' — ' . trim($p->fabric_type . ' ' . $p->garment_category) . ' by ' . $p->brand))), 0, 155);
        $data['canonical']  = product_url($p);
        if (!empty($p->images)) $data['og_image'] = web_img($p->images[0]['path']);
        $this->_render('product', $data);
    }

    public function page($slug = '') {
        $pg = $this->ws->page($slug);
        if (!$pg) return $this->not_found();
        $this->_render('page', ['pg' => $pg, 'meta_title' => $pg->title . ' | ' . ucwords(strtolower($this->ws->s('company_name'))),
            'meta_desc' => $pg->meta_description ?: mb_substr(trim(strip_tags($pg->content)), 0, 155), 'canonical' => site_url('page/' . $pg->slug)]);
    }

    public function wishlist() {
        $this->_render('wishlist', ['meta_title' => 'Wishlist', 'noindex' => true]);
    }

    /** GET ids=1,2,3 → product cards HTML for the localStorage wishlist. */
    public function products_html() {
        $ids = array_slice(array_filter(array_map('intval', explode(',', (string)$this->input->get('ids')))), 0, 60);
        $html = '';
        foreach ($this->ws->products_by_ids($ids) as $p) $html .= $this->load->view('shop/_card', ['p' => $p], true);
        $this->_json(['html' => $html]);
    }

    public function suggest() {
        $q = trim((string)$this->input->get('q', true));
        if (mb_strlen($q) < 2) return $this->_json(['items' => []]);
        $rows = $this->ws->catalog(['q' => $q, 'per_page' => 6])['rows'];
        $this->_json(['items' => array_map(function ($p) {
            return ['name' => $p->name, 'url' => product_url($p), 'price' => inr($p->price), 'img' => web_img($p->web_image ?: $p->image1)];
        }, $rows)]);
    }

    // ── Cart ─────────────────────────────────────────────────────────────────

    private function _cart() { return $this->session->userdata('web_cart') ?: []; }
    private function _save_cart($c) { $this->session->set_userdata('web_cart', $c); }

    public function cart() {
        $pr = $this->ws->price_cart($this->_cart(), $this->session->userdata('web_coupon') ?: '');
        if ($pr['coupon_error']) $this->session->unset_userdata('web_coupon');
        $this->_render('cart', ['pr' => $pr, 'meta_title' => 'Shopping Cart', 'noindex' => true]);
    }

    public function cart_add() {
        $pid = (int)$this->input->post('product_id');
        $vid = (int)$this->input->post('variant_id');
        $qty = max(1, min(20, (int)$this->input->post('qty')));
        if (!$this->ws->throttle('cart:' . $this->input->ip_address(), 120, 60)) return $this->_json(['ok' => false, 'msg' => 'Too many requests. Please wait a minute.'], 429);

        $key = $pid . ':' . $vid;
        $cart = $this->_cart();
        $have = $cart[$key]['q'] ?? 0;
        $want = $have + $qty;
        $msg = 'Added to cart';
        $pr = $this->ws->price_cart([$key => ['p' => $pid, 'v' => $vid, 'q' => $want]]);
        if (!empty($pr['errors'][$key])) {
            $line = $pr['lines'][$key] ?? null;
            if (!$line || $line->price <= 0 || $line->avail <= $have) return $this->_json(['ok' => false, 'msg' => $have ? 'You already have all available stock in your cart.' : $pr['errors'][$key]]);
            $want = $line->avail; // cap at what's left
            $msg = "Added — only {$want} available";
        }
        $cart[$key] = ['p' => $pid, 'v' => $vid, 'q' => $want];
        $this->_save_cart($cart);
        $this->_json(['ok' => true, 'count' => array_sum(array_column($cart, 'q')), 'msg' => $msg]);
    }

    public function cart_update() {
        $key = (string)$this->input->post('key');
        $qty = (int)$this->input->post('qty');
        $cart = $this->_cart();
        if (isset($cart[$key])) {
            if ($qty <= 0) unset($cart[$key]); else $cart[$key]['q'] = min(20, $qty);
            $this->_save_cart($cart);
        }
        redirect('cart');
    }

    public function cart_coupon() {
        $code = strtoupper(trim((string)$this->input->post('code', true)));
        if ($this->input->post('remove')) { $this->session->unset_userdata('web_coupon'); redirect($this->input->post('back') === 'checkout' ? 'checkout' : 'cart'); }
        if (!$this->ws->throttle('coupon:' . $this->input->ip_address(), 20, 600)) { $this->session->set_flashdata('error', 'Too many attempts. Try again later.'); redirect('cart'); }
        $pr = $this->ws->price_cart($this->_cart(), $code);
        if ($pr['coupon_error']) $this->session->set_flashdata('error', $pr['coupon_error']);
        else { $this->session->set_userdata('web_coupon', $code); $this->session->set_flashdata('success', 'Coupon applied: you save ' . inr($pr['discount'], 2)); }
        redirect($this->input->post('back') === 'checkout' ? 'checkout' : 'cart');
    }

    // ── Checkout ─────────────────────────────────────────────────────────────

    public function checkout() {
        $this->ws->expire_pending_payments();
        $cart = $this->_cart();
        if (!$cart) redirect('cart');
        $cust = $this->session->userdata('web_customer');
        $this->load->library('GstAddress');

        $post = $this->input->method() === 'post';
        $f = [];
        foreach (['name', 'phone', 'email', 'address_line', 'landmark', 'city', 'state_code', 'pincode', 'notes', 'payment_method', 'payment_ref'] as $k) {
            $f[$k] = trim((string)($post ? $this->input->post($k, true) : ''));
        }
        if (!$post) {
            $last = $cust ? $this->db->where('customer_id', $cust['id'])->order_by('id', 'DESC')->limit(1)->get('web_orders')->row() : null;
            $f['name'] = $last->customer_name ?? ($cust['name'] ?? '');
            $f['phone'] = $last->phone ?? ($cust['phone'] ?? '');
            if ($last) foreach (['email', 'address_line', 'landmark', 'city', 'state_code', 'pincode'] as $k) $f[$k] = (string)$last->$k;
            if ($f['state_code'] === '') $f['state_code'] = str_pad($this->ws->s('company_state_code', '09'), 2, '0', STR_PAD_LEFT);
        }
        $f['phone'] = substr(preg_replace('/\D/', '', $f['phone']), -10);
        $f['coupon'] = $this->session->userdata('web_coupon') ?: '';

        $errors = [];
        if ($post) {
            if (!$this->ws->throttle('checkout:' . $this->input->ip_address(), 10, 600)) $errors[] = 'Too many order attempts. Please wait a few minutes.';
            if (mb_strlen($f['name']) < 2 || mb_strlen($f['name']) > 100) $errors[] = 'Please enter your full name.';
            if (!preg_match('/^[6-9]\d{9}$/', $f['phone'])) $errors[] = 'Please enter a valid 10-digit mobile number.';
            if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
            if (mb_strlen($f['address_line']) < 8) $errors[] = 'Please enter your complete delivery address.';
            if (mb_strlen($f['city']) < 2) $errors[] = 'Please enter your city.';
            if (!GstAddress::state_name($f['state_code'])) $errors[] = 'Please select your state.';
            elseif ($e = GstAddress::pin_error($f['pincode'], $f['state_code'], 'PIN code')) $errors[] = str_replace(' — it is compulsory for E-Invoice / E-Way Bill', '', $e);
            if ($f['payment_method'] === 'upi' && $f['payment_ref'] !== '' && !preg_match('/^[A-Za-z0-9]{6,30}$/', $f['payment_ref'])) $errors[] = 'UPI reference should be the 12-digit UTR / transaction ID.';
            foreach (['address_line' => 255, 'landmark' => 120, 'city' => 80, 'notes' => 500] as $k => $max) $f[$k] = mb_substr($f[$k], 0, $max);

            if (!$errors && hash_equals((string)$this->session->userdata('web_checkout_key'), (string)$this->input->post('checkout_key'))) {
                $res = $this->ws->place_order($cart, $f, $cust['id'] ?? null, (string)$this->input->post('checkout_key'));
                if ($res['ok']) {
                    $o = $res['order'];
                    $mine = $this->session->userdata('web_my_orders') ?: [];
                    $mine[] = (int)$o->id;
                    $this->session->set_userdata('web_my_orders', array_slice(array_unique($mine), -20));
                    $this->session->unset_userdata(['web_cart', 'web_coupon', 'web_checkout_key']);
                    if (empty($res['duplicate'])) {
                        if ($o->payment_method === 'razorpay') redirect('checkout/pay/' . $o->order_no);
                        $this->ws->notify_new_order($o);
                    }
                    redirect('order/success/' . $o->order_no);
                }
                $errors[] = $res['error'];
            } elseif (!$errors) {
                $errors[] = 'Your session changed. Please review and place the order again.';
            }
        }

        $key = bin2hex(random_bytes(16));
        $this->session->set_userdata('web_checkout_key', $key);
        $pr = $this->ws->price_cart($cart, $f['coupon'], $f['state_code'], $f['phone']);
        $methods = $this->ws->payment_methods();
        if (!$f['payment_method'] || !isset($methods[$f['payment_method']])) $f['payment_method'] = (string)key($methods);
        $this->_render('checkout', ['f' => $f, 'pr' => $pr, 'errors' => $errors, 'checkout_key' => $key, 'methods' => $methods,
            'states' => GstAddress::STATES, 'meta_title' => 'Checkout', 'noindex' => true]);
    }

    /** Razorpay: create the gateway order and open checkout. */
    public function pay($order_no = '') {
        $o = $this->_my_order($order_no);
        if (!$o || $o->payment_method !== 'razorpay') return $this->not_found();
        if ($o->payment_status === 'paid' || $o->order_status !== 'pending_payment') redirect('order/success/' . $o->order_no);
        $err = '';
        if (!$o->gateway_order_id) {
            $r = $this->_razorpay('POST', 'orders', ['amount' => (int)round($o->grand_total * 100), 'currency' => 'INR', 'receipt' => $o->order_no]);
            if (!empty($r['id'])) {
                $this->db->where('id', $o->id)->update('web_orders', ['gateway_order_id' => $r['id']]);
                $o->gateway_order_id = $r['id'];
            } else {
                $err = 'Could not reach the payment gateway. Please try again in a moment.';
                log_message('error', 'razorpay order: ' . json_encode($r));
            }
        }
        $this->_render('pay', ['o' => $o, 'err' => $err, 'key_id' => $this->ws->s('web_razorpay_key_id'), 'meta_title' => 'Complete payment', 'noindex' => true]);
    }

    /** Browser callback from Razorpay Checkout — signature verified server-side. */
    public function razorpay_verify() {
        $oid = (string)$this->input->post('razorpay_order_id');
        $pid = (string)$this->input->post('razorpay_payment_id');
        $sig = (string)$this->input->post('razorpay_signature');
        $o = $oid !== '' ? $this->db->get_where('web_orders', ['gateway_order_id' => $oid])->row() : null;
        if (!$o) return $this->not_found();
        $expected = hash_hmac('sha256', $oid . '|' . $pid, $this->ws->s('web_razorpay_key_secret'));
        if ($pid === '' || !hash_equals($expected, $sig)) {
            $this->ws->add_history($o->id, 'payment', null, null, 'Payment signature verification FAILED (payment ' . mb_substr($pid, 0, 40) . ')', 0, 'Payment gateway');
            $this->session->set_flashdata('error', 'We could not verify your payment. If money was debited, it will be confirmed automatically or refunded.');
            redirect('checkout/pay/' . $o->order_no);
        }
        $this->ws->mark_paid($o->id, (float)$o->grand_total, $pid, $pid, 'Razorpay');
        $this->ws->notify_new_order($this->ws->get_order($o->id));
        redirect('order/success/' . $o->order_no);
    }

    /** Server-to-server webhook (configure in Razorpay dashboard). */
    public function razorpay_webhook() {
        $body = file_get_contents('php://input');
        $secret = $this->ws->s('web_razorpay_webhook_secret');
        $sig = (string)$this->input->get_request_header('X-Razorpay-Signature');
        if ($secret === '' || !hash_equals(hash_hmac('sha256', $body, $secret), $sig)) return $this->_json(['ok' => false], 400);
        $ev = json_decode($body, true);
        $pay = $ev['payload']['payment']['entity'] ?? null;
        if ($pay && in_array($ev['event'] ?? '', ['payment.captured', 'order.paid'], true)) {
            $o = $this->db->get_where('web_orders', ['gateway_order_id' => $pay['order_id'] ?? ''])->row();
            if ($o && (int)$pay['amount'] === (int)round($o->grand_total * 100)) {
                if ($this->ws->mark_paid($o->id, $pay['amount'] / 100, $pay['id'], $pay['id'], 'Razorpay webhook') === true) $this->ws->notify_new_order($this->ws->get_order($o->id));
            } elseif ($o) {
                $this->ws->add_history($o->id, 'payment', null, null, 'Webhook amount mismatch: ' . ($pay['amount'] / 100), 0, 'Payment gateway');
            }
        } elseif ($pay && ($ev['event'] ?? '') === 'payment.failed') {
            $o = $this->db->get_where('web_orders', ['gateway_order_id' => $pay['order_id'] ?? ''])->row();
            if ($o) $this->ws->add_history($o->id, 'payment', null, null, 'Payment attempt failed: ' . mb_substr($pay['error_description'] ?? '', 0, 200), 0, 'Payment gateway');
        }
        $this->_json(['ok' => true]);
    }

    private function _razorpay($method, $path, $payload = null) {
        $ch = curl_init('https://api.razorpay.com/v1/' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERPWD => $this->ws->s('web_razorpay_key_id') . ':' . $this->ws->s('web_razorpay_key_secret'),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload ? json_encode($payload) : null,
        ]);
        $out = curl_exec($ch);
        curl_close($ch);
        return json_decode((string)$out, true) ?: [];
    }

    // ── Orders (customer side) ───────────────────────────────────────────────

    /** Order visible to: the session that placed it, or the logged-in owner. */
    private function _my_order($order_no) {
        $o = $this->ws->get_order_by_no((string)$order_no);
        if (!$o) return null;
        $mine = $this->session->userdata('web_my_orders') ?: [];
        $cust = $this->session->userdata('web_customer');
        if (in_array((int)$o->id, $mine, true) || ($cust && (int)$cust['id'] === (int)$o->customer_id)) return $o;
        return null;
    }

    public function success($order_no = '') {
        $o = $this->_my_order($order_no);
        if (!$o) return $this->not_found();
        $this->_render('order', ['o' => $o, 'items' => $this->ws->order_items($o->id), 'just_placed' => true,
            'history' => [], 'meta_title' => 'Order ' . $o->order_no, 'noindex' => true]);
    }

    public function track() {
        $o = null; $err = '';
        $no = trim((string)($this->input->post('order_no', true) ?? $this->input->get('order_no', true)));
        if ($this->input->method() === 'post') {
            $ph = substr(preg_replace('/\D/', '', (string)$this->input->post('phone')), -10);
            if (!$this->ws->throttle('track:' . $this->input->ip_address(), 15, 600)) $err = 'Too many attempts. Please try again later.';
            else {
                $o = $this->ws->get_order_by_no($no);
                if (!$o || substr(preg_replace('/\D/', '', $o->phone), -10) !== $ph) { $o = null; $err = 'No order found with that order number and mobile number.'; }
            }
        }
        $this->_render($o ? 'order' : 'track', ['o' => $o, 'err' => $err, 'order_no' => $no, 'items' => $o ? $this->ws->order_items($o->id) : [],
            'history' => $o ? $this->_public_history($o->id) : [], 'just_placed' => false, 'meta_title' => 'Track your order', 'noindex' => true]);
    }

    private function _public_history($oid) {
        return array_values(array_filter($this->ws->order_history($oid), function ($h) { return in_array($h->action, ['created', 'status', 'return'], true); }));
    }

    // ── Account ──────────────────────────────────────────────────────────────

    private function _require_customer() {
        $c = $this->session->userdata('web_customer');
        if (!$c) redirect('account/login?next=' . urlencode(uri_string()));
        return $c;
    }

    public function login() {
        if ($this->session->userdata('web_customer')) redirect('account');
        $err = '';
        if ($this->input->method() === 'post') {
            $ph = substr(preg_replace('/\D/', '', (string)$this->input->post('phone')), -10);
            $pw = (string)$this->input->post('password');
            if (!$this->ws->throttle('login:' . $this->input->ip_address(), 10, 900) || !$this->ws->throttle('loginph:' . $ph, 8, 900)) {
                $err = 'Too many login attempts. Please wait 15 minutes.';
            } else {
                $c = $this->db->query('SELECT * FROM customers WHERE (phone = ? OR phone LIKE ?) AND web_password_hash IS NOT NULL ORDER BY id LIMIT 1', [$ph, '%' . $ph])->row();
                if ($c && password_verify($pw, $c->web_password_hash) && $c->web_active && $c->status) {
                    $this->_login_customer($c);
                    $next = (string)$this->input->get('next');
                    redirect(preg_match('#^(account|checkout|cart)(/|$)#', $next) ? $next : 'account');
                }
                $err = ($c && !$c->web_active) ? 'Your account is disabled. Please contact the store.' : 'Incorrect mobile number or password.';
            }
        }
        $this->_render('login', ['err' => $err, 'meta_title' => 'Login', 'noindex' => true]);
    }

    public function register() {
        if ($this->session->userdata('web_customer')) redirect('account');
        $err = ''; $f = ['name' => '', 'phone' => '', 'email' => ''];
        if ($this->input->method() === 'post') {
            foreach ($f as $k => $_) $f[$k] = trim((string)$this->input->post($k, true));
            $f['phone'] = substr(preg_replace('/\D/', '', $f['phone']), -10);
            $pw = (string)$this->input->post('password');
            if (!$this->ws->throttle('register:' . $this->input->ip_address(), 5, 3600)) $err = 'Too many attempts. Please try later.';
            elseif (mb_strlen($f['name']) < 2) $err = 'Please enter your name.';
            elseif (!preg_match('/^[6-9]\d{9}$/', $f['phone'])) $err = 'Please enter a valid 10-digit mobile number.';
            elseif ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $err = 'Please enter a valid email.';
            elseif (strlen($pw) < 8) $err = 'Password must be at least 8 characters.';
            elseif ($pw !== (string)$this->input->post('password2')) $err = 'Passwords do not match.';
            else {
                $c = $this->db->query('SELECT * FROM customers WHERE phone = ? OR phone LIKE ? ORDER BY id LIMIT 1', [$f['phone'], '%' . $f['phone']])->row();
                if ($c && $c->web_password_hash) $err = 'An account with this mobile number already exists. Please log in.';
                else {
                    $hash = password_hash($pw, PASSWORD_DEFAULT);
                    if ($c) {
                        // Existing store (POS) customer: attach a web password. Only web orders are shown online.
                        $this->db->where('id', $c->id)->update('customers', ['web_password_hash' => $hash] + ($f['email'] && !$c->email ? ['email' => $f['email']] : []));
                        $id = $c->id;
                    } else {
                        $this->db->insert('customers', ['name' => $f['name'], 'phone' => $f['phone'], 'email' => $f['email'] ?: null, 'type' => 'retail',
                            'state' => 'Uttar Pradesh', 'status' => 1, 'portal_active' => 1, 'web_active' => 1, 'web_password_hash' => $hash]);
                        $id = $this->db->insert_id();
                    }
                    $this->_login_customer($this->db->get_where('customers', ['id' => $id])->row());
                    $this->session->set_flashdata('success', 'Welcome! Your account is ready.');
                    redirect('account');
                }
            }
        }
        $this->_render('register', ['err' => $err, 'f' => $f, 'meta_title' => 'Create account', 'noindex' => true]);
    }

    private function _login_customer($c) {
        $this->session->sess_regenerate(true);
        $this->session->set_userdata('web_customer', ['id' => (int)$c->id, 'name' => $c->name, 'phone' => $c->phone]);
    }

    public function logout() {
        $this->session->unset_userdata(['web_customer', 'web_my_orders']);
        $this->session->sess_regenerate(true);
        redirect('');
    }

    public function account() {
        $c = $this->_require_customer();
        $err = '';
        if ($this->input->method() === 'post') {
            $row = $this->db->get_where('customers', ['id' => $c['id']])->row();
            $new = (string)$this->input->post('new_password');
            if (!password_verify((string)$this->input->post('current_password'), (string)$row->web_password_hash)) $err = 'Current password is incorrect.';
            elseif (strlen($new) < 8) $err = 'New password must be at least 8 characters.';
            else {
                $this->db->where('id', $c['id'])->update('customers', ['web_password_hash' => password_hash($new, PASSWORD_DEFAULT)]);
                $this->session->set_flashdata('success', 'Password updated.');
                redirect('account');
            }
        }
        $orders = $this->db->where('customer_id', $c['id'])->order_by('id', 'DESC')->limit(50)->get('web_orders')->result();
        $this->_render('account', ['orders' => $orders, 'err' => $err, 'meta_title' => 'My account', 'noindex' => true]);
    }

    public function account_order($order_no = '') {
        $c = $this->_require_customer();
        $o = $this->ws->get_order_by_no((string)$order_no);
        if (!$o || (int)$o->customer_id !== (int)$c['id']) return $this->not_found();
        $this->_render('order', ['o' => $o, 'items' => $this->ws->order_items($o->id), 'history' => $this->_public_history($o->id),
            'just_placed' => false, 'can_cancel' => in_array($o->order_status, ['new', 'pending_payment'], true), 'meta_title' => 'Order ' . $o->order_no, 'noindex' => true]);
    }

    public function account_cancel($order_no = '') {
        $c = $this->_require_customer();
        $o = $this->ws->get_order_by_no((string)$order_no);
        if (!$o || (int)$o->customer_id !== (int)$c['id'] || $this->input->method() !== 'post') return $this->not_found();
        if (!in_array($o->order_status, ['new', 'pending_payment'], true)) {
            $this->session->set_flashdata('error', 'This order can no longer be cancelled online. Please contact the store.');
        } else {
            $r = $this->ws->change_status($o->id, 'cancelled', 'Cancelled by customer: ' . mb_substr(trim((string)$this->input->post('reason', true)) ?: 'no reason given', 0, 200));
            $this->session->set_flashdata($r === true ? 'success' : 'error', $r === true ? 'Your order has been cancelled.' : $r);
        }
        redirect('account/order/' . $o->order_no);
    }

    // ── SEO ──────────────────────────────────────────────────────────────────

    public function sitemap() {
        $urls = [base_url(), site_url('shop')];
        foreach ($this->_nav()['categories'] as $c) $urls[] = category_url($c);
        foreach ($this->db->select('slug')->get('web_pages')->result() as $pg) $urls[] = site_url('page/' . $pg->slug);
        foreach ($this->db->select('id, name, updated_at')->where('status', 1)->where('web_visible', 1)->get('products')->result() as $p) $urls[] = [product_url($p), $p->updated_at];
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $loc = is_array($u) ? $u[0] : $u;
            $xml .= '<url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . (is_array($u) && $u[1] ? '<lastmod>' . date('Y-m-d', strtotime($u[1])) . '</lastmod>' : '') . '</url>';
        }
        $this->output->set_content_type('application/xml')->set_output($xml . '</urlset>');
    }

    public function robots() {
        $this->output->set_content_type('text/plain')->set_output(
            "User-agent: *\nDisallow: /webadmin\nDisallow: /login\nDisallow: /dashboard\nDisallow: /cart\nDisallow: /checkout\nDisallow: /account\nDisallow: /order/\nDisallow: /track\nDisallow: /portal\nDisallow: /store\nDisallow: /vendor-portal\nDisallow: /*?q=\n\nSitemap: " . site_url('sitemap.xml') . "\n");
    }
}
