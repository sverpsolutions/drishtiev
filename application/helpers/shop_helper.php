<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// View helpers shared by the storefront (Shop) and its admin (WebAdmin).

function shop_slug($text) {
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', (string)$text), '-'));
    return $s !== '' ? $s : 'item';
}

function product_url($p) {
    return site_url('product/' . shop_slug($p->name) . '-' . (int)$p->id);
}

function category_url($c) {
    return site_url('category/' . (int)$c->id . '/' . shop_slug($c->name));
}

function inr($n, $dec = 0) {
    $n = (float)$n;
    $s = number_format(abs($n), $dec, '.', '');
    // Indian digit grouping: 12,34,567
    $parts = explode('.', $s);
    $int = $parts[0];
    if (strlen($int) > 3) {
        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        $int = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',' . $last3;
    }
    return ($n < 0 ? '-' : '') . '₹' . $int . (isset($parts[1]) ? '.' . $parts[1] : '');
}

function web_img($path) {
    if (!$path) return '';
    return preg_match('#^https?://#', $path) ? $path : base_url($path);
}

function discount_pct($mrp, $price) {
    return ($mrp > 0 && $price < $mrp) ? (int)round(($mrp - $price) * 100 / $mrp) : 0;
}

function order_badge($status) {
    $cls = [
        'pending_payment' => 'b-amber', 'new' => 'b-blue', 'confirmed' => 'b-teal', 'processing' => 'b-teal',
        'packed' => 'b-purple', 'shipped' => 'b-indigo', 'delivered' => 'b-green', 'cancelled' => 'b-grey',
        'rejected' => 'b-red', 'returned' => 'b-red', 'pending' => 'b-amber', 'awaiting_verification' => 'b-amber',
        'paid' => 'b-green', 'failed' => 'b-red', 'refunded' => 'b-grey', 'partially_refunded' => 'b-amber',
    ][$status] ?? 'b-grey';
    return '<span class="badge ' . $cls . '">' . html_escape(Webstore_model::status_label($status)) . '</span>';
}

/** Strips scripts, event handlers and javascript: URLs from admin-authored page HTML. */
function safe_html($html) {
    $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1>#is', '', (string)$html);
    $html = preg_replace('#<(script|iframe|object|embed|form|meta|link)\b[^>]*/?>#is', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    return preg_replace('/(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*/i', '$1=$2#', $html);
}
