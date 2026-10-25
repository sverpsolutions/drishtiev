<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WebAdmin — online store back office. Uses ERP login (Auth_Controller) and the
 * 'mod_webstore' module permission (mapped in MY_Controller). Settings, refunds and
 * customer account changes additionally require an admin/manager/owner role.
 */
class WebAdmin extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Webstore_model', 'ws');
        $this->load->helper('shop');
        $this->ws->expire_pending_payments();
    }

    private function _view($view, $data = []) {
        $data['company'] = $this->ws->settings();
        $data['new_count'] = $this->db->where('order_status', 'new')->count_all_results('web_orders');
        $data['active'] = $data['active'] ?? $this->router->method;
        $this->load->view('webadmin/_header', $data);
        $this->load->view('webadmin/' . $view, $data);
        $this->load->view('webadmin/_footer', $data);
    }

    private function _admin_only() {
        if (!$this->is_admin()) {
            $this->session->set_flashdata('error', 'Only admins/managers can do this.');
            redirect('webadmin');
        }
    }

    private function _post_only() {
        if ($this->input->method() !== 'post') show_404();
    }

    private function _back($msg, $ok = true, $to = null) {
        $this->session->set_flashdata($ok ? 'success' : 'error', $msg);
        redirect($to ?: ($this->input->server('HTTP_REFERER') ?: 'webadmin'));
    }

    /** CSV download; cells that start with = + - @ are prefixed to stop formula injection. */
    private function _csv($name, array $head, array $rows) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $head);
        foreach ($rows as $r) {
            fputcsv($out, array_map(function ($v) { $v = (string)$v; return preg_match('/^[=+\-@\t\r]/', $v) && !is_numeric($v) ? "'" . $v : $v; }, array_values((array)$r)));
        }
        fclose($out);
        exit;
    }

    // =========================================================================
    // Dashboard
    // =========================================================================

    public function index() {
        $valid = "order_status NOT IN ('cancelled','rejected','pending_payment')";
        $d['today'] = $this->db->query("SELECT COUNT(*) n, COALESCE(SUM(grand_total),0) amt FROM web_orders WHERE DATE(created_at) = CURDATE() AND {$valid}")->row();
        $d['month'] = $this->db->query("SELECT COUNT(*) n, COALESCE(SUM(grand_total),0) amt FROM web_orders WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND {$valid}")->row();
        $d['by_status'] = array_column($this->db->query('SELECT order_status s, COUNT(*) n FROM web_orders GROUP BY order_status')->result_array(), 'n', 's');
        $d['awaiting_pay'] = $this->db->where('payment_status', 'awaiting_verification')->where_not_in('order_status', ['cancelled', 'rejected'])->count_all_results('web_orders');
        $d['trend'] = $this->db->query("SELECT DATE(created_at) d, COUNT(*) n, SUM(grand_total) amt FROM web_orders WHERE created_at >= CURDATE() - INTERVAL 13 DAY AND {$valid} GROUP BY DATE(created_at)")->result();
        $d['top'] = $this->db->query("SELECT i.product_id, i.product_name, SUM(i.qty) q, SUM(i.line_total) amt FROM web_order_items i JOIN web_orders o ON o.id = i.order_id
            WHERE o.created_at >= CURDATE() - INTERVAL 30 DAY AND o.{$valid} GROUP BY i.product_id, i.product_name ORDER BY q DESC LIMIT 8")->result();
        $d['by_cat'] = $this->db->query("SELECT COALESCE(c.name, 'Uncategorised') name, SUM(i.qty) q, SUM(i.line_total) amt FROM web_order_items i JOIN web_orders o ON o.id = i.order_id
            LEFT JOIN categories c ON c.id = i.category_id WHERE o.created_at >= CURDATE() - INTERVAL 30 DAY AND o.{$valid} GROUP BY c.id ORDER BY amt DESC LIMIT 8")->result();
        $d['low'] = $this->_low_stock(10);
        $d['recent'] = $this->db->order_by('id', 'DESC')->limit(10)->get('web_orders')->result();
        $d['title'] = 'Dashboard';
        $this->_view('dashboard', $d);
    }

    private function _low_stock($limit = 0) {
        $loc = $this->ws->location_id();
        $sql = "SELECT gv.id, gv.variant_sku, gv.barcode, gv.size_name, gv.colour_name, gv.min_stock, p.id product_id, p.name,
                       COALESCE(ls.stock_qty, 0) stock
                FROM garment_variants gv JOIN products p ON p.id = gv.product_id
                LEFT JOIN garment_variant_location_stock ls ON ls.variant_id = gv.id AND ls.location_id = {$loc}
                WHERE gv.status = 1 AND p.status = 1 AND COALESCE(ls.stock_qty, 0) <= gv.min_stock
                ORDER BY stock ASC, p.name" . ($limit ? " LIMIT {$limit}" : '');
        return $this->db->query($sql)->result();
    }

    // =========================================================================
    // Orders
    // =========================================================================

    private function _order_filters() {
        $g = $this->input->get(null, true) ?: [];
        $f = [
            'status' => $g['status'] ?? '', 'payment' => $g['payment'] ?? '', 'pstatus' => $g['pstatus'] ?? '',
            'from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $g['from'] ?? '') ? $g['from'] : '',
            'to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $g['to'] ?? '') ? $g['to'] : '',
            'q' => trim($g['q'] ?? ''), 'page' => max(1, (int)($g['page'] ?? 1)),
        ];
        $this->db->from('web_orders');
        if ($f['status'] === 'today') $this->db->where('DATE(created_at) = CURDATE()', null, false);
        elseif ($f['status'] === 'open') $this->db->where_in('order_status', Webstore_model::OPEN_STATUSES);
        elseif ($f['status'] !== '') $this->db->where('order_status', $f['status']);
        if ($f['payment'] !== '') $this->db->where('payment_method', $f['payment']);
        if ($f['pstatus'] !== '') $this->db->where('payment_status', $f['pstatus']);
        if ($f['from']) $this->db->where('created_at >=', $f['from'] . ' 00:00:00');
        if ($f['to'])   $this->db->where('created_at <=', $f['to'] . ' 23:59:59');
        if ($f['q'] !== '') {
            $digits = preg_replace('/\D/', '', $f['q']);
            $this->db->group_start()->like('order_no', $f['q'])->or_like('customer_name', $f['q'])->or_like('email', $f['q']);
            if (strlen($digits) >= 4) $this->db->or_like('phone', $digits);
            $this->db->group_end();
        }
        return $f;
    }

    public function orders() {
        $f = $this->_order_filters();
        if ($this->input->get('export')) {
            $rows = $this->db->order_by('id', 'DESC')->get()->result();
            $out = [];
            foreach ($rows as $o) {
                $items = $this->ws->order_items($o->id);
                $out[] = [$o->order_no, $o->created_at, $o->customer_name, $o->phone, $o->email, "{$o->address_line}, {$o->landmark}, {$o->city}, {$o->state} - {$o->pincode}",
                    implode(' | ', array_map(function ($i) { return "{$i->qty} x {$i->product_name}" . ($i->size_name ? " ({$i->size_name}/{$i->colour_name})" : '') . " [{$i->sku}]"; }, $items)),
                    $o->subtotal, $o->discount, $o->coupon_code, $o->shipping, $o->taxable_total, $o->cgst, $o->sgst, $o->igst, $o->grand_total,
                    strtoupper($o->payment_method), $o->payment_status, $o->order_status, $o->courier, $o->tracking_no, $o->invoice_no];
            }
            $this->_csv('web-orders', ['Order No', 'Date', 'Customer', 'Mobile', 'Email', 'Address', 'Items', 'Subtotal', 'Discount', 'Coupon', 'Shipping', 'Taxable', 'CGST', 'SGST', 'IGST', 'Total', 'Payment', 'Payment Status', 'Order Status', 'Courier', 'Tracking', 'Invoice No'], $out);
        }
        $per = 25;
        $total = $this->db->count_all_results('', false);
        $d['rows'] = $this->db->order_by('id', 'DESC')->limit($per, ($f['page'] - 1) * $per)->get()->result();
        $d['total'] = $total; $d['pages'] = (int)ceil($total / $per); $d['f'] = $f;
        $d['counts'] = array_column($this->db->query('SELECT order_status s, COUNT(*) n FROM web_orders GROUP BY order_status')->result_array(), 'n', 's');
        $d['counts']['today'] = $this->db->where('DATE(created_at) = CURDATE()', null, false)->count_all_results('web_orders');
        $d['counts']['open'] = $this->db->where_in('order_status', Webstore_model::OPEN_STATUSES)->count_all_results('web_orders');
        $d['title'] = 'Orders';
        $this->_view('orders', $d);
    }

    public function order($id = 0) {
        $o = $this->ws->get_order($id);
        if (!$o) show_404();
        $d = ['o' => $o, 'items' => $this->ws->order_items($id), 'history' => $this->ws->order_history($id),
              'refunds' => $this->ws->order_refunds($id), 'title' => 'Order ' . $o->order_no, 'active' => 'orders',
              'next' => Webstore_model::allowed_transitions()[$o->order_status] ?? []];
        $d['cust_orders'] = $o->customer_id ? $this->db->where('customer_id', $o->customer_id)->count_all_results('web_orders') : 0;
        $this->_view('order', $d);
    }

    public function order_status($id = 0) {
        $this->_post_only();
        $to = (string)$this->input->post('to');
        $reason = trim((string)$this->input->post('reason', true));
        $extra = [];
        if ($to === 'shipped') {
            $extra = array_filter(['courier' => mb_substr(trim((string)$this->input->post('courier', true)), 0, 80),
                'tracking_no' => mb_substr(trim((string)$this->input->post('tracking_no', true)), 0, 80),
                'tracking_url' => filter_var(trim((string)$this->input->post('tracking_url')), FILTER_VALIDATE_URL) ?: null]);
        }
        $r = $this->ws->change_status($id, $to, $reason, $extra);
        if ($r === true) {
            $o = $this->ws->get_order($id);
            $this->log_activity('webstore', 'order_status', "Order {$o->order_no} → {$to}" . ($reason ? ": {$reason}" : ''), $o->id, $o->order_no);
            if ($o->email && in_array($to, ['confirmed', 'shipped', 'delivered', 'cancelled', 'rejected'], true)) {
                $this->ws->send_mail($o->email, "Order {$o->order_no}: " . Webstore_model::status_label($to),
                    '<p>Your order <b>' . html_escape($o->order_no) . '</b> is now <b>' . Webstore_model::status_label($to) . '</b>.</p>'
                    . ($to === 'shipped' && $o->tracking_no ? '<p>Courier: ' . html_escape($o->courier) . ', tracking no. ' . html_escape($o->tracking_no) . '</p>' : '')
                    . '<p><a href="' . site_url('track?order_no=' . urlencode($o->order_no)) . '">Track your order</a></p>');
            }
        }
        $this->_back($r === true ? 'Order updated.' : $r, $r === true, 'webadmin/order/' . (int)$id);
    }

    public function order_tracking($id = 0) {
        $this->_post_only();
        $o = $this->ws->get_order($id) or show_404();
        $upd = ['courier' => mb_substr(trim((string)$this->input->post('courier', true)), 0, 80) ?: null,
                'tracking_no' => mb_substr(trim((string)$this->input->post('tracking_no', true)), 0, 80) ?: null,
                'tracking_url' => filter_var(trim((string)$this->input->post('tracking_url')), FILTER_VALIDATE_URL) ?: null];
        $this->db->where('id', $o->id)->update('web_orders', $upd);
        $this->ws->add_history($o->id, 'tracking', null, null, 'Courier: ' . ($upd['courier'] ?: '-') . ', tracking: ' . ($upd['tracking_no'] ?: '-'));
        $this->_back('Tracking details saved.', true, 'webadmin/order/' . $o->id);
    }

    public function order_payment($id = 0) {
        $this->_post_only();
        $o = $this->ws->get_order($id) or show_404();
        $amount = round((float)$this->input->post('amount'), 2);
        $ref = mb_substr(trim((string)$this->input->post('reference', true)), 0, 100);
        if (abs($amount - (float)$o->grand_total) > 0.009) $this->_back('Amount must equal the order total ' . inr($o->grand_total, 2) . '.', false, 'webadmin/order/' . $o->id);
        if ($o->payment_method === 'cod' && $o->order_status !== 'delivered') $this->_back('Record COD collection after the order is delivered.', false, 'webadmin/order/' . $o->id);
        $r = $this->ws->mark_paid($o->id, $amount, $ref ?: null, null, $o->payment_method === 'cod' ? 'COD collected' : 'verified by ' . $this->session->userdata('name'));
        $this->log_activity('webstore', 'payment', "Payment recorded for {$o->order_no}: {$amount}", $o->id, $o->order_no);
        $this->_back($r === 'already' ? 'Payment was already recorded.' : 'Payment recorded.', true, 'webadmin/order/' . $o->id);
    }

    public function order_note($id = 0) {
        $this->_post_only();
        $o = $this->ws->get_order($id) or show_404();
        $note = trim((string)$this->input->post('note', true));
        if ($note === '') $this->_back('Note is empty.', false);
        $this->db->where('id', $o->id)->update('web_orders', ['admin_notes' => trim(($o->admin_notes ? $o->admin_notes . "\n" : '') . '[' . date('d-m-Y H:i') . ' ' . $this->session->userdata('name') . '] ' . $note)]);
        $this->ws->add_history($o->id, 'note', null, null, $note);
        $this->_back('Note added.', true, 'webadmin/order/' . $o->id);
    }

    public function order_return($id = 0) {
        $this->_post_only();
        $r = $this->ws->return_items($id, (array)$this->input->post('qty'), (bool)$this->input->post('restock'), trim((string)$this->input->post('reason', true)));
        if ($r === true) $this->log_activity('webstore', 'return', 'Return recorded for web order #' . (int)$id, (int)$id);
        $this->_back($r === true ? 'Return recorded.' : $r, $r === true, 'webadmin/order/' . (int)$id);
    }

    public function order_refund($id = 0) {
        $this->_post_only(); $this->_admin_only();
        $status = in_array($this->input->post('status'), ['pending', 'processed', 'failed'], true) ? $this->input->post('status') : 'pending';
        $r = $this->ws->add_refund($id, round((float)$this->input->post('amount'), 2), mb_substr(trim((string)$this->input->post('method', true)), 0, 30) ?: 'Other',
            mb_substr(trim((string)$this->input->post('reference', true)), 0, 100), $status, mb_substr(trim((string)$this->input->post('note', true)), 0, 255));
        if ($r === true) $this->log_activity('webstore', 'refund', 'Refund recorded for web order #' . (int)$id, (int)$id);
        $this->_back($r === true ? 'Refund recorded.' : $r, $r === true, 'webadmin/order/' . (int)$id);
    }

    public function refund_update($rid = 0) {
        $this->_post_only(); $this->_admin_only();
        $status = in_array($this->input->post('status'), ['pending', 'processed', 'failed'], true) ? $this->input->post('status') : 'pending';
        $r = $this->ws->update_refund($rid, $status, mb_substr(trim((string)$this->input->post('reference', true)), 0, 100));
        $this->_back($r === true ? 'Refund updated.' : $r, $r === true);
    }

    public function invoice($id = 0) {
        $o = $this->ws->get_order($id) or show_404();
        $this->load->view('webadmin/invoice', ['o' => $o, 'items' => $this->ws->order_items($id), 'company' => $this->ws->settings(), 'slip' => false]);
    }

    public function packing_slip($id = 0) {
        $o = $this->ws->get_order($id) or show_404();
        $this->load->view('webadmin/invoice', ['o' => $o, 'items' => $this->ws->order_items($id), 'company' => $this->ws->settings(), 'slip' => true]);
    }

    // =========================================================================
    // Products (web catalog layer on top of the existing item master)
    // =========================================================================

    public function products() {
        $q = trim((string)$this->input->get('q', true));
        $loc = $this->ws->location_id();
        $this->db->select("p.id, p.name, p.item_code, p.style_code, p.brand, p.category, p.status, p.web_visible, p.is_featured, p.selling_price, p.mrp, p.image1,
            (SELECT COUNT(*) FROM garment_variants gv WHERE gv.product_id = p.id AND gv.status = 1) vcount,
            (SELECT COALESCE(SUM(GREATEST(ls.stock_qty,0)),0) FROM garment_variant_location_stock ls JOIN garment_variants gv ON gv.id = ls.variant_id AND gv.status = 1 WHERE ls.product_id = p.id AND ls.location_id = {$loc}) vstock,
            (SELECT COUNT(*) FROM web_product_images w WHERE w.product_id = p.id) imgs", false)->from('products p');
        if ($q !== '') $this->db->group_start()->like('p.name', $q)->or_like('p.item_code', $q)->or_like('p.style_code', $q)->group_end();
        $d['rows'] = $this->db->order_by('p.id', 'DESC')->limit(300)->get()->result();
        $d['q'] = $q; $d['title'] = 'Products';
        $this->_view('products', $d);
    }

    public function product_flags($id = 0) {
        $this->_post_only();
        $field = $this->input->post('field');
        if (!in_array($field, ['web_visible', 'is_featured'], true)) show_404();
        $this->db->where('id', (int)$id)->update('products', [$field => $this->input->post('value') ? 1 : 0]);
        $this->log_activity('webstore', 'product_flag', "{$field}=" . ($this->input->post('value') ? 1 : 0), (int)$id);
        $this->_back('Product updated.');
    }

    public function product_images($id = 0) {
        $p = $this->db->get_where('products', ['id' => (int)$id])->row() or show_404();
        if ($this->input->method() === 'post') {
            $colour = mb_substr(trim((string)$this->input->post('colour_name', true)), 0, 50) ?: null;
            $done = 0; $errs = [];
            $files = $_FILES['images'] ?? null;
            $n = $files && is_array($files['name']) ? count($files['name']) : 0;
            $next = (int)$this->db->select_max('sort_order')->get_where('web_product_images', ['product_id' => $p->id])->row()->sort_order;
            for ($i = 0; $i < min($n, 12); $i++) {
                $one = ['name' => $files['name'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
                $path = $this->_save_image($one, 'products/' . $p->id, 1600);
                if (is_string($path) && strpos($path, 'assets/') === 0) {
                    $this->db->insert('web_product_images', ['product_id' => $p->id, 'colour_name' => $colour, 'path' => $path, 'sort_order' => ++$next]);
                    $done++;
                } else $errs[] = $one['name'] . ': ' . $path;
            }
            if ($done) $this->log_activity('webstore', 'product_images', "{$done} image(s) uploaded", $p->id);
            $this->_back($done ? "{$done} image(s) uploaded." . ($errs ? ' Skipped: ' . implode('; ', $errs) : '') : ($errs ? implode('; ', $errs) : 'No files selected.'), (bool)$done, 'webadmin/product_images/' . $p->id);
        }
        $d['p'] = $p;
        $d['images'] = $this->db->order_by('sort_order, id')->get_where('web_product_images', ['product_id' => $p->id])->result();
        $d['colours'] = array_column($this->db->query('SELECT DISTINCT colour_name FROM garment_variants WHERE product_id = ?', [$p->id])->result_array(), 'colour_name');
        $d['title'] = 'Images — ' . $p->name; $d['active'] = 'products';
        $this->_view('product_images', $d);
    }

    public function image_action($img_id = 0) {
        $this->_post_only();
        $im = $this->db->get_where('web_product_images', ['id' => (int)$img_id])->row() or show_404();
        if ($this->input->post('delete')) {
            $this->db->where('id', $im->id)->delete('web_product_images');
            if (strpos($im->path, 'assets/web/uploads/') === 0 && is_file(FCPATH . $im->path)) @unlink(FCPATH . $im->path);
        } else {
            $this->db->where('id', $im->id)->update('web_product_images', ['sort_order' => (int)$this->input->post('sort_order'),
                'colour_name' => mb_substr(trim((string)$this->input->post('colour_name', true)), 0, 50) ?: null]);
        }
        $this->_back('Image updated.', true, 'webadmin/product_images/' . $im->product_id);
    }

    /**
     * Validates an uploaded image and re-encodes it with GD (drops any embedded
     * payload/metadata). Returns the saved relative path or an error string.
     */
    private function _save_image($f, $subdir, $max_w) {
        if (empty($f['tmp_name']) || $f['error'] !== UPLOAD_ERR_OK) return 'upload failed';
        if ($f['size'] > 5 * 1024 * 1024) return 'larger than 5 MB';
        if (!is_uploaded_file($f['tmp_name'])) return 'invalid upload';
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $info = @getimagesize($f['tmp_name']);
        if (!$info || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $info['mime'] !== $mime) return 'only JPG, PNG or WebP images are allowed';
        if ($info[0] < 200 || $info[1] < 200 || $info[0] * $info[1] > 40000000) return 'image dimensions not supported';
        $src = $mime === 'image/png' ? @imagecreatefrompng($f['tmp_name']) : ($mime === 'image/webp' ? @imagecreatefromwebp($f['tmp_name']) : @imagecreatefromjpeg($f['tmp_name']));
        if (!$src) return 'could not read image';
        $w = imagesx($src); $h = imagesy($src);
        $nw = min($w, $max_w); $nh = (int)round($h * $nw / $w);
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $dir = 'assets/web/uploads/' . trim($subdir, '/');
        if (!is_dir(FCPATH . $dir)) mkdir(FCPATH . $dir, 0755, true);
        $rel = $dir . '/' . bin2hex(random_bytes(8)) . '.jpg';
        $ok = imagejpeg($dst, FCPATH . $rel, 84);
        imagedestroy($src); imagedestroy($dst);
        return $ok ? $rel : 'could not save image';
    }

    // =========================================================================
    // Banners (exactly four slots)
    // =========================================================================

    public function banners() {
        $d['rows'] = $this->db->order_by('slot')->get('web_banners')->result();
        $d['title'] = 'Homepage Banners';
        $this->_view('banners', $d);
    }

    public function banner_save($slot = 0) {
        $this->_post_only();
        $slot = (int)$slot;
        if ($slot < 1 || $slot > 4) show_404();
        $b = $this->db->get_where('web_banners', ['slot' => $slot])->row();
        $p = function ($k, $max) { return mb_substr(trim((string)$this->input->post($k, true)), 0, $max); };
        $title = $p('title', 150);
        if ($title === '') $this->_back('Headline is required.', false, 'webadmin/banners');
        $link = $p('link', 255);
        if ($link !== '' && !preg_match('#^(https?://|[a-z0-9])#i', $link)) $this->_back('Link must be a site path like shop?gender=Men or a full https:// URL.', false, 'webadmin/banners');
        $dt = function ($k) { $v = trim((string)$this->input->post($k)); return $v !== '' && strtotime($v) ? date('Y-m-d H:i:s', strtotime($v)) : null; };
        $row = ['slot' => $slot, 'title' => $title, 'subtitle' => $p('subtitle', 255) ?: null, 'offer_text' => $p('offer_text', 100) ?: null,
                'button_text' => $p('button_text', 40) ?: 'Shop Now', 'link' => $link ?: null, 'sort_order' => (int)$this->input->post('sort_order'),
                'is_active' => $this->input->post('is_active') ? 1 : 0, 'starts_at' => $dt('starts_at'), 'ends_at' => $dt('ends_at')];
        foreach (['image_desktop' => 2400, 'image_mobile' => 1200] as $k => $mw) {
            if (!empty($_FILES[$k]['name'])) {
                $r = $this->_save_image($_FILES[$k], 'banners', $mw);
                if (strpos($r, 'assets/') !== 0) $this->_back(ucfirst(str_replace('_', ' ', $k)) . ': ' . $r, false, 'webadmin/banners');
                $row[$k] = $r;
            }
        }
        if ($b) $this->db->where('slot', $slot)->update('web_banners', $row); else $this->db->insert('web_banners', $row);
        $this->log_activity('webstore', 'banner', "Banner slot {$slot} saved");
        $this->_back("Banner {$slot} saved.", true, 'webadmin/banners');
    }

    // =========================================================================
    // Content pages
    // =========================================================================

    public function pages($slug = '') {
        if ($slug !== '') {
            $pg = $this->ws->page($slug) or show_404();
            if ($this->input->method() === 'post') {
                $this->db->where('id', $pg->id)->update('web_pages', [
                    'title' => mb_substr(trim((string)$this->input->post('title', true)), 0, 150) ?: $pg->title,
                    'content' => safe_html((string)$this->input->post('content')),
                    'meta_description' => mb_substr(trim((string)$this->input->post('meta_description', true)), 0, 255) ?: null,
                ]);
                $this->log_activity('webstore', 'page', "Page {$slug} updated");
                $this->_back('Page saved.', true, 'webadmin/pages/' . $slug);
            }
            $this->_view('page_edit', ['pg' => $pg, 'title' => 'Edit: ' . $pg->title, 'active' => 'pages']);
            return;
        }
        $this->_view('pages', ['rows' => $this->db->order_by('id')->get('web_pages')->result(), 'title' => 'Website Content']);
    }

    // =========================================================================
    // Coupons
    // =========================================================================

    public function coupons() {
        $d['rows'] = $this->db->query("SELECT c.*, (SELECT COUNT(*) FROM web_orders o WHERE o.coupon_code = c.code AND o.order_status NOT IN ('cancelled','rejected')) used
            FROM web_coupons c ORDER BY c.id DESC")->result();
        $d['title'] = 'Coupons';
        $this->_view('coupons', $d);
    }

    public function coupon($id = 0) {
        $c = $id ? $this->db->get_where('web_coupons', ['id' => (int)$id])->row() : null;
        if ($id && !$c) show_404();
        $err = '';
        if ($this->input->method() === 'post') {
            $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string)$this->input->post('code')));
            $type = $this->input->post('discount_type') === 'fixed' ? 'fixed' : 'percent';
            $val = round((float)$this->input->post('discount_value'), 2);
            $num = function ($k) { $v = trim((string)$this->input->post($k)); return $v === '' ? null : max(0, (float)$v); };
            $dt = function ($k) { $v = trim((string)$this->input->post($k)); return $v !== '' && strtotime($v) ? date('Y-m-d H:i:s', strtotime($v)) : null; };
            $ids = function ($k) { return implode(',', array_filter(array_map('intval', (array)$this->input->post($k)))) ?: null; };
            $row = ['code' => $code, 'description' => mb_substr(trim((string)$this->input->post('description', true)), 0, 255) ?: null,
                    'discount_type' => $type, 'discount_value' => $val, 'max_discount' => $num('max_discount'), 'min_order' => (float)$num('min_order'),
                    'starts_at' => $dt('starts_at'), 'ends_at' => $dt('ends_at'),
                    'usage_limit' => $num('usage_limit') === null ? null : (int)$num('usage_limit'),
                    'per_customer_limit' => $num('per_customer_limit') === null ? null : (int)$num('per_customer_limit'),
                    'category_ids' => $ids('category_ids'), 'product_ids' => $ids('product_ids'), 'is_active' => $this->input->post('is_active') ? 1 : 0];
            $dup = $this->db->where('code', $code)->where('id !=', (int)$id)->count_all_results('web_coupons');
            if (strlen($code) < 3) $err = 'Code must be at least 3 letters/numbers.';
            elseif ($dup) $err = 'This code already exists.';
            elseif ($val <= 0 || ($type === 'percent' && $val > 90)) $err = 'Enter a discount value (percent max 90).';
            elseif ($row['starts_at'] && $row['ends_at'] && $row['ends_at'] < $row['starts_at']) $err = 'End date is before start date.';
            else {
                if ($c) $this->db->where('id', $c->id)->update('web_coupons', $row); else $this->db->insert('web_coupons', $row);
                $this->log_activity('webstore', 'coupon', "Coupon {$code} saved");
                $this->_back('Coupon saved.', true, 'webadmin/coupons');
            }
            $c = (object)array_merge((array)$c, $row);
        }
        $this->_view('coupon_edit', ['c' => $c, 'err' => $err, 'title' => $c && $id ? 'Edit coupon' : 'New coupon', 'active' => 'coupons',
            'cats' => $this->db->where('status', 1)->order_by('name')->get('categories')->result(),
            'prods' => $this->db->select('id, name, item_code')->where('status', 1)->order_by('name')->get('products')->result()]);
    }

    // =========================================================================
    // Customers
    // =========================================================================

    public function customers() {
        $q = trim((string)$this->input->get('q', true));
        $this->db->select('c.id, c.name, c.phone, c.email, c.city, c.web_active, c.status, (c.web_password_hash IS NOT NULL) has_login,
            COUNT(o.id) orders, COALESCE(SUM(IF(o.order_status NOT IN (\'cancelled\',\'rejected\',\'pending_payment\'), o.grand_total, 0)),0) spent, MAX(o.created_at) last_order', false)
            ->from('customers c')->join('web_orders o', 'o.customer_id = c.id', 'left');
        if ($q !== '') $this->db->group_start()->like('c.name', $q)->or_like('c.phone', preg_replace('/\D/', '', $q) ?: $q)->or_like('c.email', $q)->group_end();
        else $this->db->group_start()->where('o.id IS NOT NULL', null, false)->or_where('c.web_password_hash IS NOT NULL', null, false)->group_end();
        $d['rows'] = $this->db->group_by('c.id')->order_by('last_order', 'DESC')->limit(200)->get()->result();
        $d['q'] = $q; $d['title'] = 'Customers';
        $this->_view('customers', $d);
    }

    public function customer($id = 0) {
        $c = $this->db->get_where('customers', ['id' => (int)$id])->row() or show_404();
        $this->_view('customer', ['c' => $c, 'orders' => $this->db->where('customer_id', $c->id)->order_by('id', 'DESC')->get('web_orders')->result(),
            'title' => $c->name, 'active' => 'customers']);
    }

    public function customer_action($id = 0) {
        $this->_post_only(); $this->_admin_only();
        $c = $this->db->get_where('customers', ['id' => (int)$id])->row() or show_404();
        $act = $this->input->post('action');
        if ($act === 'toggle') {
            $this->db->where('id', $c->id)->update('customers', ['web_active' => $c->web_active ? 0 : 1]);
            $msg = $c->web_active ? 'Website login disabled.' : 'Website login enabled.';
        } elseif ($act === 'reset') {
            $pw = (string)$this->input->post('password');
            if (strlen($pw) < 8) $this->_back('Temporary password must be at least 8 characters.', false);
            $this->db->where('id', $c->id)->update('customers', ['web_password_hash' => password_hash($pw, PASSWORD_DEFAULT)]);
            $msg = 'Password reset. Share the temporary password with the customer privately.';
        } else show_404();
        $this->log_activity('webstore', 'customer_' . $act, "Customer {$c->name} ({$c->phone})", $c->id);
        $this->_back($msg);
    }

    // =========================================================================
    // Reports
    // =========================================================================

    public function reports($type = 'sales') {
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$this->input->get('from')) ? $this->input->get('from') : date('Y-m-01');
        $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$this->input->get('to')) ? $this->input->get('to') : date('Y-m-d');
        $range = [$from . ' 00:00:00', $to . ' 23:59:59'];
        $valid = "o.order_status NOT IN ('cancelled','rejected','pending_payment')";
        $item_net = '(i.qty - i.returned_qty)';
        $reports = [
            'sales'    => ['Date-wise sales', ['Date', 'Orders', 'Items', 'Subtotal', 'Discount', 'Shipping', 'Net sales'],
                "SELECT DATE(o.created_at), COUNT(*), (SELECT SUM(qty) FROM web_order_items WHERE order_id IN (SELECT id FROM web_orders o2 WHERE DATE(o2.created_at) = DATE(o.created_at) AND o2.order_status NOT IN ('cancelled','rejected','pending_payment'))),
                 SUM(o.subtotal), SUM(o.discount), SUM(o.shipping), SUM(o.grand_total) FROM web_orders o WHERE {$valid} AND o.created_at BETWEEN ? AND ? GROUP BY DATE(o.created_at) ORDER BY 1"],
            'product'  => ['Product-wise sales', ['Product', 'SKU', 'Qty sold', 'Returned', 'Net amount'],
                "SELECT i.product_name, MAX(i.sku), SUM(i.qty), SUM(i.returned_qty), SUM(i.line_total) FROM web_order_items i JOIN web_orders o ON o.id = i.order_id WHERE {$valid} AND o.created_at BETWEEN ? AND ? GROUP BY i.product_id, i.product_name ORDER BY 3 DESC"],
            'category' => ['Category-wise sales', ['Category', 'Qty sold', 'Net amount'],
                "SELECT COALESCE(c.name,'Uncategorised'), SUM(i.qty), SUM(i.line_total) FROM web_order_items i JOIN web_orders o ON o.id = i.order_id LEFT JOIN categories c ON c.id = i.category_id WHERE {$valid} AND o.created_at BETWEEN ? AND ? GROUP BY c.id ORDER BY 3 DESC"],
            'size'     => ['Size-wise sales', ['Size', 'Qty sold', 'Net amount'],
                "SELECT COALESCE(i.size_name,'—'), SUM({$item_net}), SUM(i.line_total) FROM web_order_items i JOIN web_orders o ON o.id = i.order_id WHERE {$valid} AND o.created_at BETWEEN ? AND ? GROUP BY i.size_name ORDER BY 2 DESC"],
            'colour'   => ['Colour-wise sales', ['Colour', 'Qty sold', 'Net amount'],
                "SELECT COALESCE(i.colour_name,'—'), SUM({$item_net}), SUM(i.line_total) FROM web_order_items i JOIN web_orders o ON o.id = i.order_id WHERE {$valid} AND o.created_at BETWEEN ? AND ? GROUP BY i.colour_name ORDER BY 2 DESC"],
            'payment'  => ['Payment method summary', ['Method', 'Payment status', 'Orders', 'Amount'],
                "SELECT UPPER(o.payment_method), o.payment_status, COUNT(*), SUM(o.grand_total) FROM web_orders o WHERE o.created_at BETWEEN ? AND ? GROUP BY o.payment_method, o.payment_status ORDER BY 1, 2"],
            'gst'      => ['GST / tax summary (by rate)', ['GST rate %', 'Supply', 'Taxable value', 'CGST', 'SGST', 'IGST', 'Invoice value'],
                "SELECT i.gst_rate, IF(o.is_interstate,'Inter-state','Intra-state'), SUM(i.taxable), SUM(i.cgst), SUM(i.sgst), SUM(i.igst), SUM(i.line_total) FROM web_order_items i JOIN web_orders o ON o.id = i.order_id
                 WHERE o.order_status IN ('confirmed','processing','packed','shipped','delivered','returned') AND o.created_at BETWEEN ? AND ? GROUP BY i.gst_rate, o.is_interstate ORDER BY 1"],
            'cancelled' => ['Cancelled / rejected orders', ['Order', 'Date', 'Customer', 'Mobile', 'Status', 'Reason', 'Amount', 'Payment status'],
                "SELECT o.order_no, o.created_at, o.customer_name, o.phone, o.order_status, o.status_reason, o.grand_total, o.payment_status FROM web_orders o WHERE o.order_status IN ('cancelled','rejected') AND o.created_at BETWEEN ? AND ? ORDER BY o.id DESC"],
            'returns'  => ['Returns & refunds', ['Order', 'Date', 'Customer', 'Returned items', 'Refunded (processed)', 'Refund pending', 'Payment status'],
                "SELECT o.order_no, o.created_at, o.customer_name, (SELECT SUM(returned_qty) FROM web_order_items WHERE order_id = o.id),
                 (SELECT COALESCE(SUM(amount),0) FROM web_refunds WHERE order_id = o.id AND status = 'processed'), (SELECT COALESCE(SUM(amount),0) FROM web_refunds WHERE order_id = o.id AND status = 'pending'), o.payment_status
                 FROM web_orders o WHERE (o.id IN (SELECT order_id FROM web_order_items WHERE returned_qty > 0) OR o.id IN (SELECT order_id FROM web_refunds)) AND o.created_at BETWEEN ? AND ? ORDER BY o.id DESC"],
            'stock'    => ['Variant-wise stock (web location)', ['Product', 'SKU', 'Barcode', 'Size', 'Colour', 'Stock', 'Min stock', 'MRP', 'Selling price'], null],
            'lowstock' => ['Low-stock variants', ['Product', 'SKU', 'Barcode', 'Size', 'Colour', 'Stock', 'Min stock'], null],
        ];
        if (!isset($reports[$type])) show_404();
        [$label, $head, $sql] = $reports[$type];
        if ($type === 'stock') {
            $loc = $this->ws->location_id();
            $rows = $this->db->query("SELECT p.name, gv.variant_sku, gv.barcode, gv.size_name, gv.colour_name, COALESCE(ls.stock_qty,0), gv.min_stock, gv.mrp, gv.selling_price
                FROM garment_variants gv JOIN products p ON p.id = gv.product_id LEFT JOIN garment_variant_location_stock ls ON ls.variant_id = gv.id AND ls.location_id = {$loc}
                WHERE gv.status = 1 AND p.status = 1 ORDER BY p.name, gv.colour_name, gv.size_name")->result_array();
        } elseif ($type === 'lowstock') {
            $rows = array_map(function ($r) { return [$r->name, $r->variant_sku, $r->barcode, $r->size_name, $r->colour_name, $r->stock, $r->min_stock]; }, $this->_low_stock());
        } else {
            $rows = $this->db->query($sql, $range)->result_array();
        }
        $rows = array_map('array_values', $rows);
        if ($this->input->get('export')) $this->_csv('web-report-' . $type, $head, $rows);
        $this->_view('reports', ['type' => $type, 'reports' => $reports, 'label' => $label, 'head' => $head, 'rows' => $rows,
            'from' => $from, 'to' => $to, 'dated' => !in_array($type, ['stock', 'lowstock'], true), 'title' => 'Reports']);
    }

    // =========================================================================
    // Settings
    // =========================================================================

    public function settings() {
        $this->_admin_only();
        $text = ['web_announcement' => 255, 'web_order_prefix' => 10, 'web_invoice_prefix' => 10, 'web_upi_id' => 80, 'web_razorpay_key_id' => 80,
                 'web_notify_email' => 120, 'web_meta_title' => 120, 'web_meta_description' => 255, 'web_social_facebook' => 255,
                 'web_social_instagram' => 255, 'web_social_youtube' => 255, 'web_whatsapp' => 15, 'company_phone' => 30, 'company_email' => 120];
        $nums = ['web_shipping_charge', 'web_free_shipping_above', 'web_min_order', 'web_cod_max', 'web_return_days', 'web_location_id',
                 'web_gst_slab_threshold', 'web_gst_slab_low', 'web_gst_slab_high', 'web_pending_payment_minutes'];
        $flags = ['web_cod_enabled', 'web_upi_enabled', 'web_razorpay_enabled', 'web_maintenance', 'web_show_stock_count', 'web_gst_slab_enabled'];
        $secrets = ['web_razorpay_key_secret', 'web_razorpay_webhook_secret'];
        if ($this->input->method() === 'post') {
            $changed = [];
            foreach ($text as $k => $max) {
                $v = mb_substr(trim((string)$this->input->post($k, true)), 0, $max);
                if ($k === 'web_notify_email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) $this->_back('Notification email is not valid.', false);
                if (strpos($k, 'web_social_') === 0 && $v !== '' && !preg_match('#^https://#', $v)) $this->_back('Social links must start with https://', false);
                if (in_array($k, ['web_order_prefix', 'web_invoice_prefix'], true)) $v = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $v)) ?: ($k === 'web_order_prefix' ? 'SVC' : 'SVW');
                if ($v !== $this->ws->s($k)) $changed[] = $k;
                $this->ws->save_setting($k, $v);
            }
            foreach ($nums as $k) { $v = (string)max(0, (float)$this->input->post($k)); if ($v !== $this->ws->s($k, '0')) $changed[] = $k; $this->ws->save_setting($k, $v); }
            foreach ($flags as $k) { $v = $this->input->post($k) ? '1' : '0'; if ($v !== $this->ws->s($k, '0')) $changed[] = $k; $this->ws->save_setting($k, $v); }
            foreach ($secrets as $k) { $v = trim((string)$this->input->post($k)); if ($v !== '') { $this->ws->save_setting($k, $v); $changed[] = $k; } }
            if (!$this->db->get_where('locations', ['id' => (int)$this->ws->s('web_location_id', 1)])->row()) $this->ws->save_setting('web_location_id', '1');
            $this->log_activity('webstore', 'settings', 'Changed: ' . implode(', ', $changed));
            $this->_back('Settings saved.', true, 'webadmin/settings');
        }
        $this->_view('settings', ['s' => $this->ws->settings(), 'locations' => $this->db->where('status', 1)->get('locations')->result(), 'title' => 'Store Settings']);
    }
}
