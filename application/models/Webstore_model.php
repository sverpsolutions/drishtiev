<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Webstore_model — catalog, pricing/GST, and web order lifecycle for the
 * Suvasan Clothes storefront (Shop.php) and its admin (WebAdmin.php).
 *
 * Stock lifecycle (single source of truth = garment_variant_location_stock /
 * product_location_stock at setting web_location_id, the same balances POS uses):
 *   place_order   → rows locked FOR UPDATE, availability checked, stock deducted,
 *                   stock_state='deducted', movement 'sale_out' (ref web_order)
 *   cancel/reject → stock_state 'deducted'→'restored' once (order row locked),
 *                   movement 'refund_in' for qty not already returned
 *   return        → returned_qty += n, optional restock 'refund_in'
 *   pending_payment older than web_pending_payment_minutes → auto-cancelled
 * Gateway callbacks are idempotent: mark_paid() no-ops on an already-paid order.
 */
class Webstore_model extends CI_Model {

    const OPEN_STATUSES = ['new', 'confirmed', 'processing', 'packed'];

    private $_settings;

    public function settings() {
        if ($this->_settings === null) {
            $this->_settings = [];
            foreach ($this->db->get('company_settings')->result() as $r) $this->_settings[$r->key_name] = $r->key_value;
        }
        return $this->_settings;
    }

    public function s($key, $default = '') {
        $s = $this->settings();
        return (isset($s[$key]) && $s[$key] !== '') ? $s[$key] : $default;
    }

    public function save_setting($key, $value) {
        $this->db->query('INSERT INTO company_settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)', [$key, $value]);
        $this->_settings = null;
    }

    public function location_id() { return (int)$this->s('web_location_id', 1); }

    public static function slug($text) {
        $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', (string)$text), '-'));
        return $s !== '' ? $s : 'item';
    }

    // =========================================================================
    // Catalog
    // =========================================================================

    /** Product rows with web price (cheapest active variant), MRP and sellable stock. */
    private function _base_sql() {
        $loc = $this->location_id();
        return "SELECT p.id, p.name, p.item_code, p.style_code, p.brand, p.brand_id, p.category_id, p.sub_category_id,
                    c.name AS category_name, p.gender, p.garment_category, p.fabric_type, p.image1, p.created_at,
                    p.is_featured, p.description,
                    COALESCE(v.min_price, IF(p.selling_price > 0, p.selling_price, p.mrp)) AS price,
                    COALESCE(v.mrp_at_min, p.mrp) AS mrp,
                    GREATEST(COALESCE(v.stock, pls.stock_qty, 0), 0) AS stock,
                    COALESCE(v.vcount, 0) AS vcount,
                    COALESCE(sold.qty, 0) AS sold_qty,
                    (SELECT wpi.path FROM web_product_images wpi WHERE wpi.product_id = p.id ORDER BY wpi.sort_order, wpi.id LIMIT 1) AS web_image
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN (
                    SELECT gv.product_id,
                           MIN(IF(gv.selling_price > 0, gv.selling_price, gv.mrp)) AS min_price,
                           SUBSTRING_INDEX(GROUP_CONCAT(gv.mrp ORDER BY IF(gv.selling_price > 0, gv.selling_price, gv.mrp)), ',', 1) + 0 AS mrp_at_min,
                           SUM(GREATEST(COALESCE(ls.stock_qty, 0), 0)) AS stock,
                           COUNT(*) AS vcount
                    FROM garment_variants gv
                    LEFT JOIN garment_variant_location_stock ls ON ls.variant_id = gv.id AND ls.location_id = {$loc}
                    WHERE gv.status = 1
                    GROUP BY gv.product_id
                ) v ON v.product_id = p.id
                LEFT JOIN product_location_stock pls ON pls.product_id = p.id AND pls.location_id = {$loc}
                LEFT JOIN (
                    SELECT product_id, SUM(qty) AS qty FROM (
                        SELECT product_id, qty FROM web_order_items woi JOIN web_orders wo ON wo.id = woi.order_id
                            WHERE wo.order_status NOT IN ('cancelled','rejected','pending_payment')
                        UNION ALL
                        SELECT ii.product_id, ii.qty FROM invoice_items ii JOIN invoices i ON i.id = ii.invoice_id
                            WHERE i.status != 'cancelled'
                    ) x GROUP BY product_id
                ) sold ON sold.product_id = p.id
                WHERE p.status = 1 AND p.web_visible = 1";
    }

    /**
     * $f: q, category, sub_category, brand[], gender, size[], colour[], min, max,
     *     in_stock, sale, featured, sort, page, per_page
     * Returns ['rows' => [...], 'total' => n]
     */
    public function catalog($f = []) {
        $inner = []; $bind = [];
        if (!empty($f['category']))     { $inner[] = 'p.category_id = ?'; $bind[] = (int)$f['category']; }
        if (!empty($f['sub_category'])) { $inner[] = 'p.sub_category_id = ?'; $bind[] = (int)$f['sub_category']; }
        if (!empty($f['gender'])) {
            $g = $f['gender'] === 'Kids' ? ['Boys', 'Girls'] : [$f['gender']];
            if ($f['gender'] !== 'Kids') $g[] = 'Unisex';
            $inner[] = 'p.gender IN (' . implode(',', array_fill(0, count($g), '?')) . ')'; $bind = array_merge($bind, $g);
        }
        if (!empty($f['brand'])) {
            $b = array_map('intval', (array)$f['brand']);
            $inner[] = 'p.brand_id IN (' . implode(',', $b) . ')';
        }
        foreach (['size' => 'size_name', 'colour' => 'colour_name'] as $k => $col) {
            if (!empty($f[$k])) {
                $vals = array_values((array)$f[$k]);
                $inner[] = "EXISTS (SELECT 1 FROM garment_variants gv2 WHERE gv2.product_id = p.id AND gv2.status = 1 AND gv2.{$col} IN (" . implode(',', array_fill(0, count($vals), '?')) . '))';
                $bind = array_merge($bind, $vals);
            }
        }
        $q = trim($f['q'] ?? '');
        if ($q !== '') {
            $like = '%' . $this->db->escape_like_str($q) . '%';
            $inner[] = "(p.name LIKE ? OR p.item_code = ? OR p.style_code = ? OR p.brand LIKE ? OR c.name LIKE ? OR p.garment_category LIKE ?
                         OR EXISTS (SELECT 1 FROM garment_variants gv3 WHERE gv3.product_id = p.id AND (gv3.barcode = ? OR gv3.variant_sku = ?)))";
            array_push($bind, $like, $q, $q, $like, $like, $like, $q, $q);
        }
        if (!empty($f['featured'])) $inner[] = 'p.is_featured = 1';

        $sql = $this->_base_sql() . ($inner ? ' AND ' . implode(' AND ', $inner) : '');

        $outer = [];
        if (isset($f['min']) && $f['min'] !== '') { $outer[] = 't.price >= ?'; $bind[] = (float)$f['min']; }
        if (isset($f['max']) && $f['max'] !== '') { $outer[] = 't.price <= ?'; $bind[] = (float)$f['max']; }
        if (!empty($f['in_stock'])) $outer[] = 't.stock > 0';
        if (!empty($f['sale']))     $outer[] = 't.price < t.mrp';
        $where = $outer ? ' WHERE ' . implode(' AND ', $outer) : '';

        $total = (int)$this->db->query("SELECT COUNT(*) AS n FROM ({$sql}) t{$where}", $bind)->row()->n;

        $sorts = [
            'newest'     => 't.created_at DESC, t.id DESC',
            'price_asc'  => 't.price ASC',
            'price_desc' => 't.price DESC',
            'popular'    => 't.sold_qty DESC, t.id DESC',
            'discount'   => '(t.mrp - t.price) / GREATEST(t.mrp, 1) DESC',
        ];
        $sort = $f['sort'] ?? '';
        if (isset($sorts[$sort])) {
            $order = $sorts[$sort];
        } elseif ($q !== '') {
            $order = '(t.name LIKE ' . $this->db->escape($this->db->escape_like_str($q) . '%') . ') DESC, t.sold_qty DESC';
        } else {
            $order = 't.is_featured DESC, t.created_at DESC';
        }
        $order = '(t.stock > 0) DESC, ' . $order; // sold-out items sink

        $per  = max(1, min(60, (int)($f['per_page'] ?? 24)));
        $page = max(1, (int)($f['page'] ?? 1));
        $off  = ($page - 1) * $per;
        $rows = $this->db->query("SELECT * FROM ({$sql}) t{$where} ORDER BY {$order} LIMIT {$off}, {$per}", $bind)->result();
        return ['rows' => $rows, 'total' => $total];
    }

    public function products_by_ids(array $ids) {
        $ids = array_filter(array_map('intval', $ids));
        if (!$ids) return [];
        return $this->db->query($this->_base_sql() . ' AND p.id IN (' . implode(',', $ids) . ')')->result();
    }

    public function facets() {
        $vis = 'SELECT id FROM products WHERE status = 1 AND web_visible = 1';
        return [
            'categories' => $this->db->query("SELECT c.id, c.name, COUNT(p.id) n FROM categories c JOIN products p ON p.category_id = c.id AND p.status = 1 AND p.web_visible = 1 WHERE c.status = 1 GROUP BY c.id ORDER BY c.display_order, c.name")->result(),
            'brands'     => $this->db->query("SELECT b.id, b.name, COUNT(p.id) n FROM brands b JOIN products p ON p.brand_id = b.id AND p.status = 1 AND p.web_visible = 1 WHERE b.status = 1 GROUP BY b.id ORDER BY b.name")->result(),
            'sizes'      => $this->db->query("SELECT gv.size_name AS name, MIN(COALESCE(gs.sort_order, 999)) so FROM garment_variants gv LEFT JOIN garment_sizes gs ON gs.id = gv.size_id WHERE gv.status = 1 AND gv.product_id IN ({$vis}) GROUP BY gv.size_name ORDER BY so, gv.size_name")->result(),
            'colours'    => $this->db->query("SELECT gv.colour_name AS name, MAX(gc.hex_code) hex FROM garment_variants gv LEFT JOIN garment_colours gc ON gc.id = gv.colour_id WHERE gv.status = 1 AND gv.product_id IN ({$vis}) GROUP BY gv.colour_name ORDER BY gv.colour_name")->result(),
            'genders'    => array_column($this->db->query("SELECT DISTINCT gender FROM products WHERE status = 1 AND web_visible = 1 AND gender IS NOT NULL")->result_array(), 'gender'),
            'sub_categories' => $this->db->query("SELECT sc.id, sc.name, sc.category_id FROM sub_categories sc JOIN products p ON p.sub_category_id = sc.id AND p.status = 1 AND p.web_visible = 1 WHERE sc.status = 1 GROUP BY sc.id ORDER BY sc.name")->result(),
        ];
    }

    public function get_product($id) {
        $p = $this->db->query($this->_base_sql() . ' AND p.id = ?', [(int)$id])->row();
        if (!$p) return null;
        $full = $this->db->get_where('products', ['id' => $p->id])->row();
        foreach (['pattern_print', 'fit_type', 'sleeve_type', 'neck_type', 'garment_length', 'care_instructions', 'country_of_origin', 'hsn_code', 'gst_percent', 'image2', 'image3', 'collection_season'] as $k) $p->$k = $full->$k ?? null;
        $loc = $this->location_id();
        $p->variants = $this->db->query(
            "SELECT gv.id, gv.size_name, gv.colour_name, gv.variant_sku, gv.barcode, gv.mrp,
                    IF(gv.selling_price > 0, gv.selling_price, gv.mrp) AS price,
                    GREATEST(COALESCE(ls.stock_qty, 0), 0) AS stock, gc.hex_code AS hex,
                    COALESCE(gs.sort_order, 999) AS so, gs.measurement
             FROM garment_variants gv
             LEFT JOIN garment_variant_location_stock ls ON ls.variant_id = gv.id AND ls.location_id = ?
             LEFT JOIN garment_colours gc ON gc.id = gv.colour_id
             LEFT JOIN garment_sizes gs ON gs.id = gv.size_id
             WHERE gv.product_id = ? AND gv.status = 1
             ORDER BY so, gv.size_name, gv.colour_name", [$loc, $p->id])->result();
        $p->images = $this->product_images($p);
        return $p;
    }

    /** Gallery: web_product_images first, then legacy image1..3. */
    public function product_images($p) {
        $out = [];
        foreach ($this->db->order_by('sort_order, id')->get_where('web_product_images', ['product_id' => $p->id])->result() as $im) {
            $out[] = ['path' => $im->path, 'colour' => $im->colour_name, 'id' => $im->id];
        }
        foreach (['image1', 'image2', 'image3'] as $k) {
            if (!empty($p->$k)) $out[] = ['path' => $p->$k, 'colour' => null, 'id' => null];
        }
        return $out;
    }

    public function related($p, $limit = 8) {
        $r = $this->catalog(['category' => $p->category_id, 'sort' => 'popular', 'per_page' => $limit + 1]);
        return array_values(array_filter($r['rows'], function ($x) use ($p) { return $x->id != $p->id; }));
    }

    public function active_banners() {
        $now = date('Y-m-d H:i:s');
        return $this->db->where('is_active', 1)
            ->group_start()->where('starts_at IS NULL', null, false)->or_where('starts_at <=', $now)->group_end()
            ->group_start()->where('ends_at IS NULL', null, false)->or_where('ends_at >=', $now)->group_end()
            ->order_by('sort_order, slot')->get('web_banners')->result();
    }

    public function page($slug) { return $this->db->get_where('web_pages', ['slug' => $slug])->row(); }

    // =========================================================================
    // Pricing, coupons and GST
    // =========================================================================

    /** Effective GST rate for one unit at a given GST-inclusive net price. */
    public function gst_rate($product, $unit_net) {
        $ci =& get_instance();
        $ci->load->library('Gst_tax_engine');
        $calc = $ci->gst_tax_engine->calculate_sales_line([
            'product'          => $product,
            'unit_price'       => (float)$unit_net,
            'qty'              => 1,
            'discount'         => 0,
            'is_tax_inclusive' => true,
            'is_interstate'    => false,
        ]);
        return (float)$calc['gst_percent'];
    }

    /**
     * Prices a cart entirely from the database. Never uses client prices.
     * $cart: [key => ['p' => product_id, 'v' => variant_id|0, 'q' => qty]]
     * $stock_override: [key => available] (used inside the locked transaction)
     */
    public function price_cart(array $cart, $coupon_code = '', $state_code = '', $phone = '', $stock_override = null) {
        $res = ['lines' => [], 'errors' => [], 'subtotal' => 0, 'mrp_total' => 0, 'discount' => 0, 'shipping' => 0,
                'taxable_total' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'grand_total' => 0, 'coupon' => null,
                'coupon_error' => '', 'interstate' => false, 'count' => 0, 'shipping_rate' => 0, 'shipping_taxable' => 0];
        if (!$cart) return $res;

        $pids = array_unique(array_map(function ($c) { return (int)$c['p']; }, $cart));
        $vids = array_filter(array_map(function ($c) { return (int)$c['v']; }, $cart));
        $prods = [];
        foreach ($this->db->where_in('id', $pids)->where('status', 1)->where('web_visible', 1)->get('products')->result() as $p) $prods[$p->id] = $p;
        $vars = [];
        if ($vids) {
            $loc = $this->location_id();
            $vars_q = $this->db->query("SELECT gv.*, COALESCE(ls.stock_qty, 0) AS loc_stock FROM garment_variants gv
                LEFT JOIN garment_variant_location_stock ls ON ls.variant_id = gv.id AND ls.location_id = ?
                WHERE gv.id IN (" . implode(',', array_map('intval', $vids)) . ")", [$loc])->result();
            foreach ($vars_q as $v) $vars[$v->id] = $v;
        }
        $pls = [];
        foreach ($this->db->where_in('product_id', $pids)->where('location_id', $this->location_id())->get('product_location_stock')->result() as $r) $pls[$r->product_id] = (float)$r->stock_qty;

        foreach ($cart as $key => $c) {
            $p = $prods[(int)$c['p']] ?? null;
            $v = $c['v'] ? ($vars[(int)$c['v']] ?? null) : null;
            if (!$p || ($c['v'] && (!$v || !$v->status || $v->product_id != $p->id))) {
                $res['errors'][$key] = 'This item is no longer available.';
                continue;
            }
            if (!$c['v'] && $this->db->where('product_id', $p->id)->where('status', 1)->count_all_results('garment_variants') > 0) {
                $res['errors'][$key] = 'Please choose a size / colour.';
                continue;
            }
            $price = $v ? (float)($v->selling_price > 0 ? $v->selling_price : $v->mrp) : (float)($p->selling_price > 0 ? $p->selling_price : $p->mrp);
            $mrp   = $v ? (float)$v->mrp : (float)$p->mrp;
            $avail = $stock_override !== null ? ($stock_override[$key] ?? 0) : ($v ? (float)$v->loc_stock : ($pls[$p->id] ?? 0));
            $avail = max(0, (int)floor($avail));
            $qty   = max(1, (int)$c['q']);
            if ($avail <= 0)        $res['errors'][$key] = 'Out of stock.';
            elseif ($qty > $avail)  $res['errors'][$key] = "Only {$avail} left in stock.";
            if ($price <= 0)        $res['errors'][$key] = 'Price not available for this item.';
            $res['lines'][$key] = (object)[
                'key' => $key, 'product' => $p, 'variant' => $v, 'qty' => $qty, 'avail' => $avail,
                'price' => $price, 'mrp' => max($mrp, $price), 'gross' => round($price * $qty, 2), 'discount' => 0,
                'name' => $p->name, 'size' => $v->size_name ?? null, 'colour' => $v->colour_name ?? null,
                'sku' => $v->variant_sku ?? $p->item_code, 'barcode' => $v->barcode ?? null,
            ];
            $res['subtotal']  += round($price * $qty, 2);
            $res['mrp_total'] += round(max($mrp, $price) * $qty, 2);
            $res['count']     += $qty;
        }

        // Coupon
        $code = strtoupper(trim((string)$coupon_code));
        if ($code !== '' && $res['lines']) {
            $chk = $this->check_coupon($code, $res['lines'], $phone);
            if (is_string($chk)) {
                $res['coupon_error'] = $chk;
            } else {
                $res['coupon'] = $chk['coupon'];
                $res['discount'] = $chk['discount'];
                // Allocate pro-rata across eligible lines, remainder to the last one
                $left = $chk['discount']; $keys = $chk['eligible']; $base = $chk['base'];
                foreach ($keys as $i => $k) {
                    $l = $res['lines'][$k];
                    $d = ($i === count($keys) - 1) ? $left : round($chk['discount'] * $l->gross / $base, 2);
                    $l->discount = min($d, $l->gross); $left = round($left - $l->discount, 2);
                }
            }
        }

        $company_sc = str_pad((string)$this->s('company_state_code', '09'), 2, '0', STR_PAD_LEFT);
        $res['interstate'] = ($state_code !== '' && $state_code !== $company_sc);

        $net_total = 0; $max_rate = 0;
        foreach ($res['lines'] as $l) {
            $net = round($l->gross - $l->discount, 2);
            $rate = $this->gst_rate($l->product, $l->qty ? $net / $l->qty : 0);
            $this->_tax_split($l, $net, $rate, $res['interstate']);
            $net_total += $net; $max_rate = max($max_rate, $rate);
            $res['taxable_total'] += $l->taxable; $res['cgst'] += $l->cgst; $res['sgst'] += $l->sgst; $res['igst'] += $l->igst;
        }

        // Shipping (GST-inclusive; composite supply → highest item rate)
        $free_above = (float)$this->s('web_free_shipping_above', 0);
        $ship = (float)$this->s('web_shipping_charge', 0);
        if ($res['lines'] && $ship > 0 && !($free_above > 0 && $net_total >= $free_above)) {
            $res['shipping'] = $ship;
            $sh = (object)[];
            $this->_tax_split($sh, $ship, $max_rate, $res['interstate']);
            $res['shipping_rate'] = $max_rate; $res['shipping_taxable'] = $sh->taxable;
            $res['taxable_total'] += $sh->taxable; $res['cgst'] += $sh->cgst; $res['sgst'] += $sh->sgst; $res['igst'] += $sh->igst;
        }
        foreach (['subtotal', 'mrp_total', 'discount', 'taxable_total', 'cgst', 'sgst', 'igst'] as $k) $res[$k] = round($res[$k], 2);
        $res['grand_total'] = round($net_total + $res['shipping'], 2);
        $res['free_shipping_gap'] = ($free_above > 0 && $ship > 0 && $net_total < $free_above) ? round($free_above - $net_total, 2) : 0;

        $min = (float)$this->s('web_min_order', 0);
        if ($res['lines'] && $min > 0 && $net_total < $min) $res['errors']['_min'] = 'Minimum order value is ₹' . number_format($min, 0) . '.';
        return $res;
    }

    private function _tax_split($obj, $net, $rate, $interstate) {
        $taxable = round($net / (1 + $rate / 100), 2);
        $tax = round($net - $taxable, 2);
        $obj->net = $net; $obj->rate = $rate; $obj->taxable = $taxable;
        $obj->igst = $interstate ? $tax : 0;
        $obj->cgst = $interstate ? 0 : round($tax / 2, 2);
        $obj->sgst = $interstate ? 0 : round($tax - $obj->cgst, 2);
    }

    /** Returns error string or ['coupon','discount','eligible'=>keys,'base']. */
    public function check_coupon($code, $lines, $phone = '') {
        $c = $this->db->get_where('web_coupons', ['code' => $code])->row();
        if (!$c || !$c->is_active) return 'Invalid coupon code.';
        $now = date('Y-m-d H:i:s');
        if ($c->starts_at && $c->starts_at > $now) return 'This coupon is not active yet.';
        if ($c->ends_at && $c->ends_at < $now) return 'This coupon has expired.';
        $used_q = "SELECT COUNT(*) n FROM web_orders WHERE coupon_code = ? AND order_status NOT IN ('cancelled','rejected')";
        if ($c->usage_limit !== null && (int)$this->db->query($used_q, [$code])->row()->n >= (int)$c->usage_limit) return 'This coupon has reached its usage limit.';
        if ($c->per_customer_limit !== null && $phone !== '' && (int)$this->db->query($used_q . ' AND phone = ?', [$code, $phone])->row()->n >= (int)$c->per_customer_limit) return 'You have already used this coupon.';

        $cats = array_filter(array_map('intval', explode(',', (string)$c->category_ids)));
        $pids = array_filter(array_map('intval', explode(',', (string)$c->product_ids)));
        $eligible = []; $base = 0; $sub = 0;
        foreach ($lines as $k => $l) {
            $sub += $l->gross;
            $ok = (!$cats && !$pids) || in_array((int)$l->product->category_id, $cats, true) || in_array((int)$l->product->id, $pids, true);
            if ($ok) { $eligible[] = $k; $base += $l->gross; }
        }
        if (!$eligible) return 'This coupon does not apply to items in your cart.';
        if ($c->min_order > 0 && $sub < $c->min_order) return 'Add items worth ₹' . number_format($c->min_order - $sub, 0) . ' more to use this coupon.';
        $disc = $c->discount_type === 'percent' ? $base * $c->discount_value / 100 : (float)$c->discount_value;
        if ($c->max_discount !== null && $c->max_discount > 0) $disc = min($disc, (float)$c->max_discount);
        $disc = round(min($disc, $base), 2);
        if ($disc <= 0) return 'This coupon gives no discount on your cart.';
        return ['coupon' => $c, 'discount' => $disc, 'eligible' => $eligible, 'base' => $base];
    }

    public function payment_methods() {
        $m = [];
        if ($this->s('web_cod_enabled', '0') === '1') $m['cod'] = 'Cash on Delivery';
        if ($this->s('web_upi_enabled', '0') === '1' && $this->s('web_upi_id') !== '') $m['upi'] = 'UPI (pay to store UPI ID)';
        if ($this->s('web_razorpay_enabled', '0') === '1' && $this->s('web_razorpay_key_id') !== '' && $this->s('web_razorpay_key_secret') !== '') $m['razorpay'] = 'Cards / UPI / Netbanking (Razorpay)';
        return $m;
    }

    // =========================================================================
    // Order placement
    // =========================================================================

    /**
     * $f: name, phone, email, address_line, landmark, city, state_code, pincode, notes, coupon, payment_method
     * Returns ['ok' => true, 'order' => row] or ['ok' => false, 'error' => msg]
     */
    public function place_order(array $cart, array $f, $customer_id, $idem_key) {
        $existing = $this->db->get_where('web_orders', ['idempotency_key' => $idem_key])->row();
        if ($existing) return ['ok' => true, 'order' => $existing, 'duplicate' => true];
        if (!$cart) return ['ok' => false, 'error' => 'Your cart is empty.'];

        $this->db->trans_begin();
        try {
            // 1. Lock every stock row we will touch (sorted to avoid deadlocks)
            $loc = $this->location_id();
            $avail = [];
            ksort($cart);
            foreach ($cart as $key => $c) {
                if ($c['v']) {
                    $r = $this->db->query('SELECT stock_qty FROM garment_variant_location_stock WHERE variant_id = ? AND location_id = ? FOR UPDATE', [(int)$c['v'], $loc])->row();
                } else {
                    $r = $this->db->query('SELECT stock_qty FROM product_location_stock WHERE product_id = ? AND location_id = ? FOR UPDATE', [(int)$c['p'], $loc])->row();
                }
                $avail[$key] = $r ? (float)$r->stock_qty : 0;
            }

            // 2. Re-price with locked stock
            $pr = $this->price_cart($cart, $f['coupon'] ?? '', $f['state_code'], $f['phone'], $avail);
            if ($pr['errors']) throw new RuntimeException(reset($pr['errors']));
            if (!$pr['lines']) throw new RuntimeException('Your cart is empty.');
            if (($f['coupon'] ?? '') !== '' && $pr['coupon_error']) throw new RuntimeException($pr['coupon_error']);
            $methods = $this->payment_methods();
            if (!isset($methods[$f['payment_method']])) throw new RuntimeException('Selected payment method is not available.');
            $cod_max = (float)$this->s('web_cod_max', 0);
            if ($f['payment_method'] === 'cod' && $cod_max > 0 && $pr['grand_total'] > $cod_max) throw new RuntimeException('Cash on Delivery is available for orders up to ₹' . number_format($cod_max, 0) . '.');

            // 3. Customer record (shared ERP customer master)
            if (!$customer_id) $customer_id = $this->find_or_create_customer($f);

            $this->load->library('GstAddress');
            $method = $f['payment_method'];
            $this->db->insert('web_orders', [
                'order_no'        => 'TMP-' . substr($idem_key, 0, 20),
                'idempotency_key' => $idem_key,
                'customer_id'     => $customer_id,
                'customer_name'   => $f['name'],
                'phone'           => $f['phone'],
                'email'           => $f['email'] ?: null,
                'address_line'    => $f['address_line'],
                'landmark'        => $f['landmark'] ?: null,
                'city'            => $f['city'],
                'state'           => GstAddress::state_name($f['state_code']),
                'state_code'      => $f['state_code'],
                'pincode'         => $f['pincode'],
                'is_interstate'   => $pr['interstate'] ? 1 : 0,
                'subtotal'        => $pr['subtotal'],
                'coupon_code'     => $pr['coupon'] ? $pr['coupon']->code : null,
                'discount'        => $pr['discount'],
                'shipping'        => $pr['shipping'],
                'taxable_total'   => $pr['taxable_total'],
                'cgst'            => $pr['cgst'], 'sgst' => $pr['sgst'], 'igst' => $pr['igst'],
                'grand_total'     => $pr['grand_total'],
                'payment_method'  => $method,
                'payment_status'  => $method === 'upi' ? 'awaiting_verification' : 'pending',
                'payment_ref'     => $f['payment_ref'] ?? null,
                'order_status'    => $method === 'razorpay' ? 'pending_payment' : 'new',
                'stock_state'     => 'deducted',
                'location_id'     => $loc,
                'customer_notes'  => $f['notes'] ?: null,
                'ip_address'      => $this->input->ip_address(),
            ]);
            $oid = $this->db->insert_id();
            $order_no = $this->s('web_order_prefix', 'SVC') . date('ymd') . '-' . $oid;
            $this->db->where('id', $oid)->update('web_orders', ['order_no' => $order_no]);

            // 4. Items + stock deduction + movement log
            $this->load->model(['Garment_model', 'Location_model']);
            $loc_name = $this->db->select('name')->get_where('locations', ['id' => $loc])->row()->name ?? 'Web';
            foreach ($pr['lines'] as $key => $l) {
                $this->db->insert('web_order_items', [
                    'order_id' => $oid, 'product_id' => $l->product->id, 'variant_id' => $l->variant->id ?? null,
                    'product_name' => $l->name, 'sku' => $l->sku, 'barcode' => $l->barcode,
                    'size_name' => $l->size, 'colour_name' => $l->colour, 'hsn_code' => $l->product->hsn_code,
                    'category_id' => $l->product->category_id, 'qty' => $l->qty, 'mrp' => $l->mrp,
                    'unit_price' => $l->price, 'discount' => $l->discount, 'taxable' => $l->taxable,
                    'gst_rate' => $l->rate, 'cgst' => $l->cgst, 'sgst' => $l->sgst, 'igst' => $l->igst,
                    'line_total' => $l->net,
                ]);
                $this->_move_stock($l->product->id, $l->variant->id ?? null, $l->qty * -1, $avail[$key], $loc, $loc_name, $oid, $order_no, 'sale_out',
                    ['sku' => $l->sku, 'size' => $l->size, 'colour' => $l->colour, 'name' => $l->name], 'Web order');
            }
            $this->add_history($oid, 'created', null, $method === 'razorpay' ? 'pending_payment' : 'new',
                'Order placed online (' . strtoupper($method) . ')' . ($pr['coupon'] ? ', coupon ' . $pr['coupon']->code : ''), null, 'Customer');

            if ($this->db->trans_status() === false) throw new RuntimeException('Could not save your order. Please try again.');
            $this->db->trans_commit();
            return ['ok' => true, 'order' => $this->get_order($oid)];
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            // A concurrent duplicate submit with the same key loses the unique race
            $dup = $this->db->get_where('web_orders', ['idempotency_key' => $idem_key])->row();
            if ($dup) return ['ok' => true, 'order' => $dup, 'duplicate' => true];
            log_message('error', 'web place_order: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e instanceof RuntimeException ? $e->getMessage() : 'Could not place your order. Please try again.'];
        }
    }

    /** Applies a signed stock delta at the web location (caller holds the row lock). */
    private function _move_stock($product_id, $variant_id, $delta, $before, $loc, $loc_name, $oid, $order_no, $type, $meta, $note) {
        $after = $before + $delta;
        if ($variant_id) {
            $this->Garment_model->set_variant_stock($variant_id, $product_id, $loc, $after);
        } else {
            $this->Location_model->set_stock($product_id, $loc, $after);
            $row = $this->db->select('SUM(GREATEST(stock_qty,0)) t', false)->get_where('product_location_stock', ['product_id' => $product_id])->row();
            $this->db->where('id', $product_id)->update('products', ['stock_qty' => $row ? (float)$row->t : 0]);
        }
        $this->Location_model->log_movement([
            'product_id' => $product_id, 'variant_id' => $variant_id, 'variant_sku' => $meta['sku'],
            'size_name' => $meta['size'], 'colour_name' => $meta['colour'], 'product_name' => $meta['name'],
            'location_id' => $loc, 'location_name' => $loc_name, 'movement_type' => $type,
            'quantity' => abs($delta), 'balance_before' => $before, 'balance_after' => $after,
            'reference_type' => 'web_order', 'reference_id' => $oid, 'reference_no' => $order_no,
            'user_id' => $this->session->userdata('user_id') ?: null,
            'user_name' => $this->session->userdata('name') ?: 'Website', 'notes' => $note,
        ]);
    }

    private function _locked_stock($product_id, $variant_id, $loc) {
        if ($variant_id) {
            $r = $this->db->query('SELECT stock_qty FROM garment_variant_location_stock WHERE variant_id = ? AND location_id = ? FOR UPDATE', [$variant_id, $loc])->row();
        } else {
            $r = $this->db->query('SELECT stock_qty FROM product_location_stock WHERE product_id = ? AND location_id = ? FOR UPDATE', [$product_id, $loc])->row();
        }
        return $r ? (float)$r->stock_qty : 0;
    }

    public function find_or_create_customer(array $f) {
        $d10 = substr(preg_replace('/\D/', '', $f['phone']), -10);
        $c = $this->db->query("SELECT id FROM customers WHERE phone = ? OR phone = ? OR phone LIKE ? ORDER BY id LIMIT 1", [$d10, '+91' . $d10, '%' . $d10])->row();
        if ($c) return (int)$c->id;
        $this->load->library('GstAddress');
        $this->db->insert('customers', [
            'name' => $f['name'], 'phone' => $d10, 'email' => $f['email'] ?: null,
            'address' => trim($f['address_line'] . ($f['landmark'] ? ', ' . $f['landmark'] : '')),
            'city' => $f['city'], 'state' => GstAddress::state_name($f['state_code']), 'pincode' => $f['pincode'],
            'type' => 'retail', 'status' => 1, 'portal_active' => 1, 'web_active' => 1,
        ]);
        return (int)$this->db->insert_id();
    }

    // =========================================================================
    // Order lifecycle
    // =========================================================================

    public function get_order($id) { return $this->db->get_where('web_orders', ['id' => (int)$id])->row(); }
    public function get_order_by_no($no) { return $this->db->get_where('web_orders', ['order_no' => $no])->row(); }
    public function order_items($id) { return $this->db->order_by('id')->get_where('web_order_items', ['order_id' => (int)$id])->result(); }
    public function order_history($id) { return $this->db->order_by('id', 'DESC')->get_where('web_order_history', ['order_id' => (int)$id])->result(); }
    public function order_refunds($id) { return $this->db->order_by('id')->get_where('web_refunds', ['order_id' => (int)$id])->result(); }

    public function add_history($oid, $action, $from, $to, $note = null, $uid = null, $uname = null) {
        $this->db->insert('web_order_history', [
            'order_id' => $oid, 'action' => $action, 'from_status' => $from, 'to_status' => $to,
            'note' => $note ? mb_substr($note, 0, 500) : null,
            'user_id' => $uid ?? ($this->session->userdata('user_id') ?: null),
            'user_name' => $uname ?? ($this->session->userdata('name') ?: 'System'),
        ]);
    }

    public static function allowed_transitions() {
        return [
            'pending_payment' => ['cancelled'],
            'new'        => ['confirmed', 'rejected', 'cancelled'],
            'confirmed'  => ['processing', 'packed', 'shipped', 'cancelled'],
            'processing' => ['packed', 'shipped', 'cancelled'],
            'packed'     => ['shipped', 'cancelled'],
            'shipped'    => ['delivered'],
            'delivered'  => [],
            'cancelled'  => [], 'rejected' => [], 'returned' => [],
        ];
    }

    /** Status change with stock restoration on cancel/reject. Returns true or error string. */
    public function change_status($oid, $to, $reason = '', $extra = []) {
        $this->db->trans_begin();
        try {
            $o = $this->db->query('SELECT * FROM web_orders WHERE id = ? FOR UPDATE', [(int)$oid])->row();
            if (!$o) throw new RuntimeException('Order not found.');
            $allowed = self::allowed_transitions()[$o->order_status] ?? [];
            if (!in_array($to, $allowed, true)) throw new RuntimeException("Cannot move order from {$o->order_status} to {$to}.");
            if (in_array($to, ['cancelled', 'rejected'], true) && trim($reason) === '') throw new RuntimeException('A reason is required.');

            $upd = ['order_status' => $to] + $extra;
            if (in_array($to, ['cancelled', 'rejected'], true)) {
                $upd['status_reason'] = mb_substr($reason, 0, 255);
                $this->_restore_stock($o, $to === 'rejected' ? 'Order rejected' : 'Order cancelled');
                $upd['stock_state'] = 'restored';
                if ($o->payment_status === 'pending' || $o->payment_status === 'awaiting_verification') $upd['payment_status'] = 'failed';
            }
            if ($to === 'confirmed' && !$o->invoice_no) $upd['invoice_no'] = $this->_next_invoice_no();
            if ($to === 'shipped')   $upd['shipped_at'] = date('Y-m-d H:i:s');
            if ($to === 'delivered') {
                $upd['delivered_at'] = date('Y-m-d H:i:s');
                if (!$o->invoice_no) $upd['invoice_no'] = $this->_next_invoice_no();
            }
            $this->db->where('id', $o->id)->update('web_orders', $upd);
            $this->add_history($o->id, 'status', $o->order_status, $to, $reason ?: null);
            if ($this->db->trans_status() === false) throw new RuntimeException('Database error.');
            $this->db->trans_commit();
            return true;
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            return $e instanceof RuntimeException ? $e->getMessage() : 'Could not update the order.';
        }
    }

    /** Caller holds the order row lock. Restores (qty - returned_qty) exactly once. */
    private function _restore_stock($o, $note) {
        if ($o->stock_state !== 'deducted') return;
        $this->load->model(['Garment_model', 'Location_model']);
        $loc_name = $this->db->select('name')->get_where('locations', ['id' => $o->location_id])->row()->name ?? 'Web';
        foreach ($this->order_items($o->id) as $it) {
            $qty = (int)$it->qty - (int)$it->returned_qty;
            if ($qty <= 0) continue;
            $before = $this->_locked_stock($it->product_id, $it->variant_id, $o->location_id);
            $this->_move_stock($it->product_id, $it->variant_id, $qty, $before, $o->location_id, $loc_name, $o->id, $o->order_no, 'refund_in',
                ['sku' => $it->sku, 'size' => $it->size_name, 'colour' => $it->colour_name, 'name' => $it->product_name], $note);
        }
    }

    /** Records returned quantities; optionally puts them back into stock. */
    public function return_items($oid, array $qtys, $restock, $reason) {
        $this->db->trans_begin();
        try {
            $o = $this->db->query('SELECT * FROM web_orders WHERE id = ? FOR UPDATE', [(int)$oid])->row();
            if (!$o || !in_array($o->order_status, ['shipped', 'delivered'], true)) throw new RuntimeException('Only shipped or delivered orders can be returned.');
            if (trim($reason) === '') throw new RuntimeException('A return reason is required.');
            $this->load->model(['Garment_model', 'Location_model']);
            $loc_name = $this->db->select('name')->get_where('locations', ['id' => $o->location_id])->row()->name ?? 'Web';
            $any = false; $all = true; $desc = [];
            foreach ($this->order_items($o->id) as $it) {
                $n = (int)($qtys[$it->id] ?? 0);
                $max = (int)$it->qty - (int)$it->returned_qty;
                if ($n < 0 || $n > $max) throw new RuntimeException("Invalid return quantity for {$it->product_name}.");
                if ($n > 0) {
                    $any = true;
                    $this->db->set('returned_qty', 'returned_qty + ' . $n, false)->where('id', $it->id)->update('web_order_items');
                    if ($restock && $o->stock_state === 'deducted') {
                        $before = $this->_locked_stock($it->product_id, $it->variant_id, $o->location_id);
                        $this->_move_stock($it->product_id, $it->variant_id, $n, $before, $o->location_id, $loc_name, $o->id, $o->order_no, 'refund_in',
                            ['sku' => $it->sku, 'size' => $it->size_name, 'colour' => $it->colour_name, 'name' => $it->product_name], 'Web order return');
                    }
                    $desc[] = "{$n} × {$it->product_name}" . ($it->size_name ? " ({$it->size_name}/{$it->colour_name})" : '');
                }
                if ($n < $max) $all = false;
            }
            if (!$any) throw new RuntimeException('Enter at least one quantity to return.');
            if ($all) {
                $this->db->where('id', $o->id)->update('web_orders', ['order_status' => 'returned', 'status_reason' => mb_substr($reason, 0, 255)]);
            }
            $this->add_history($o->id, 'return', $o->order_status, $all ? 'returned' : $o->order_status,
                'Returned ' . implode(', ', $desc) . ($restock ? ' (restocked)' : ' (not restocked)') . '. Reason: ' . $reason);
            if ($this->db->trans_status() === false) throw new RuntimeException('Database error.');
            $this->db->trans_commit();
            return true;
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            return $e instanceof RuntimeException ? $e->getMessage() : 'Could not record the return.';
        }
    }

    /** Idempotent: safe for repeated gateway callbacks/webhooks. */
    public function mark_paid($oid, $amount, $ref, $gateway_payment_id = null, $source = 'admin') {
        $this->db->trans_begin();
        $o = $this->db->query('SELECT * FROM web_orders WHERE id = ? FOR UPDATE', [(int)$oid])->row();
        if (!$o || $o->payment_status === 'paid') { $this->db->trans_commit(); return $o ? 'already' : false; }
        $upd = ['payment_status' => 'paid', 'paid_amount' => $amount, 'payment_ref' => $ref];
        if ($gateway_payment_id) $upd['gateway_payment_id'] = $gateway_payment_id;
        $note = "Payment received ₹" . number_format($amount, 2) . " ({$source})" . ($ref ? ", ref {$ref}" : '');
        if ($o->order_status === 'pending_payment') {
            $upd['order_status'] = 'new';
        } elseif (in_array($o->order_status, ['cancelled', 'rejected'], true)) {
            $note .= ' — ORDER WAS ALREADY CANCELLED: refund this payment or re-open manually.';
        }
        $this->db->where('id', $o->id)->update('web_orders', $upd);
        $by_gateway = $gateway_payment_id !== null;
        $this->add_history($o->id, 'payment', $o->order_status, $upd['order_status'] ?? $o->order_status, $note,
            $by_gateway ? 0 : null, $by_gateway ? 'Payment gateway' : null);
        $this->db->trans_commit();
        return true;
    }

    /** Cancels unpaid gateway orders after the timeout and frees their stock. */
    public function expire_pending_payments() {
        $mins = max(5, (int)$this->s('web_pending_payment_minutes', 30));
        $ids = $this->db->query("SELECT id FROM web_orders WHERE order_status = 'pending_payment' AND created_at < (NOW() - INTERVAL {$mins} MINUTE) LIMIT 50")->result();
        foreach ($ids as $r) $this->change_status($r->id, 'cancelled', 'Online payment not completed within ' . $mins . ' minutes');
    }

    public function add_refund($oid, $amount, $method, $reference, $status, $note) {
        $o = $this->get_order($oid);
        if (!$o) return 'Order not found.';
        if ($amount <= 0) return 'Refund amount must be positive.';
        $done = (float)$this->db->select_sum('amount')->where('order_id', $oid)->where('status !=', 'failed')->get('web_refunds')->row()->amount;
        $paid = (float)$o->paid_amount;
        if ($paid <= 0) return 'No payment has been recorded for this order.';
        if ($done + $amount > $paid + 0.001) return 'Refund exceeds the amount paid (₹' . number_format($paid - $done, 2) . ' refundable).';
        $this->db->insert('web_refunds', [
            'order_id' => $oid, 'amount' => $amount, 'method' => $method, 'reference' => $reference ?: null,
            'status' => $status, 'note' => $note ?: null,
            'user_id' => $this->session->userdata('user_id'), 'user_name' => $this->session->userdata('name'),
        ]);
        $this->_sync_refund_status($oid);
        $this->add_history($oid, 'refund', null, null, "Refund ₹" . number_format($amount, 2) . " via {$method} ({$status})" . ($reference ? ", ref {$reference}" : '') . ($note ? ". {$note}" : ''));
        return true;
    }

    public function update_refund($rid, $status, $reference) {
        $r = $this->db->get_where('web_refunds', ['id' => (int)$rid])->row();
        if (!$r) return 'Refund not found.';
        $this->db->where('id', $r->id)->update('web_refunds', ['status' => $status, 'reference' => $reference ?: $r->reference]);
        $this->_sync_refund_status($r->order_id);
        $this->add_history($r->order_id, 'refund', null, null, "Refund #{$r->id} marked {$status}");
        return true;
    }

    private function _sync_refund_status($oid) {
        $o = $this->get_order($oid);
        $done = (float)$this->db->select_sum('amount')->where('order_id', $oid)->where('status', 'processed')->get('web_refunds')->row()->amount;
        if ($done <= 0) return;
        $this->db->where('id', $oid)->update('web_orders', ['payment_status' => $done + 0.001 >= (float)$o->paid_amount ? 'refunded' : 'partially_refunded']);
    }

    /** Sequential per financial year, generated under a row lock. */
    private function _next_invoice_no() {
        $y = (int)date('Y'); $m = (int)date('n');
        $fy = $m >= 4 ? sprintf('%02d-%02d', $y % 100, ($y + 1) % 100) : sprintf('%02d-%02d', ($y - 1) % 100, $y % 100);
        $key = 'web_invoice_seq_' . $fy;
        $this->db->query("INSERT IGNORE INTO company_settings (key_name, key_value) VALUES (?, '0')", [$key]);
        $cur = (int)$this->db->query('SELECT key_value FROM company_settings WHERE key_name = ? FOR UPDATE', [$key])->row()->key_value;
        $this->db->query('UPDATE company_settings SET key_value = ? WHERE key_name = ?', [$cur + 1, $key]);
        return $this->s('web_invoice_prefix', 'SVW') . '/' . $fy . '/' . str_pad($cur + 1, 4, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    // Misc
    // =========================================================================

    /** Fixed-window limiter. Returns false when the caller is over the limit. */
    public function throttle($key, $max, $window_sec) {
        $k = substr($key, 0, 100); $now = time();
        $this->db->query('INSERT INTO web_throttle (k, hits, reset_at) VALUES (?, 1, ?)
            ON DUPLICATE KEY UPDATE hits = IF(reset_at < ?, 1, hits + 1), reset_at = IF(reset_at < ?, VALUES(reset_at), reset_at)',
            [$k, $now + $window_sec, $now, $now]);
        return (int)$this->db->get_where('web_throttle', ['k' => $k])->row()->hits <= $max;
    }

    /** Sends via the ERP's SMTP settings; silently skipped when SMTP is not configured. */
    public function send_mail($to, $subject, $html) {
        if (!$to || $this->s('smtp_user') === '' || $this->s('smtp_pass') === '') return false;
        try {
            $this->load->library('email');
            $this->email->clear();
            $this->email->initialize([
                'protocol' => 'smtp', 'smtp_host' => $this->s('smtp_host', 'smtp.gmail.com'),
                'smtp_port' => (int)$this->s('smtp_port', 587), 'smtp_user' => $this->s('smtp_user'),
                'smtp_pass' => $this->s('smtp_pass'), 'smtp_crypto' => $this->s('smtp_crypto', 'tls'),
                'mailtype' => 'html', 'charset' => 'utf-8', 'crlf' => "\r\n", 'newline' => "\r\n", 'smtp_timeout' => 8,
            ]);
            $this->email->from($this->s('smtp_user'), $this->s('company_name', 'Store'));
            $this->email->to($to);
            $this->email->subject($subject);
            $this->email->message($html);
            return $this->email->send(false);
        } catch (Throwable $e) {
            log_message('error', 'web mail: ' . $e->getMessage());
            return false;
        }
    }

    public function notify_new_order($o) {
        $items = $this->order_items($o->id);
        $rows = '';
        foreach ($items as $it) {
            $rows .= '<tr><td>' . html_escape($it->product_name) . ($it->size_name ? ' — ' . html_escape($it->size_name . ' / ' . $it->colour_name) : '') . '</td><td>' . (int)$it->qty . '</td><td>₹' . number_format($it->line_total, 2) . '</td></tr>';
        }
        $body = '<h2 style="color:#7a1420">Order ' . html_escape($o->order_no) . '</h2><table cellpadding="6" border="1" style="border-collapse:collapse">' . $rows
            . '</table><p><b>Total: ₹' . number_format($o->grand_total, 2) . '</b> (' . strtoupper($o->payment_method) . ')</p>';
        $this->send_mail($this->s('web_notify_email'), 'New web order ' . $o->order_no, $body . '<p>' . html_escape($o->customer_name . ', ' . $o->phone) . '</p>');
        if ($o->email) $this->send_mail($o->email, 'Your order ' . $o->order_no . ' — ' . $this->s('company_name'), '<p>Thank you for shopping with us! We have received your order.</p>' . $body);
    }

    public static function status_label($s) {
        $map = ['pending_payment' => 'Awaiting Payment', 'new' => 'New', 'confirmed' => 'Confirmed', 'processing' => 'Processing',
                'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled',
                'rejected' => 'Rejected', 'returned' => 'Returned', 'pending' => 'Pending', 'awaiting_verification' => 'Awaiting Verification',
                'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded', 'partially_refunded' => 'Partly Refunded'];
        return $map[$s] ?? ucfirst(str_replace('_', ' ', $s));
    }
}
