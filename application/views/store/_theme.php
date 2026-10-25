<?php
// FILE: application/views/store/_theme.php
// Emits --st-* CSS custom properties for the Store storefront, driven by its own
// company_settings keys (store_primary_color / store_accent_color) — deliberately
// separate from portal_primary_color/portal_accent_color so the two storefronts
// can be themed independently. Mirrors portal/_theme.php's structure exactly.
if (!function_exists('store_hex_adjust')) {
    function store_hex_adjust($hex, $amount) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return '#' . $hex;
        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        $adjust = function ($c) use ($amount) {
            $c = $amount >= 0 ? $c + (255 - $c) * $amount : $c + $c * $amount;
            return max(0, min(255, (int)round($c)));
        };
        return sprintf('#%02x%02x%02x', $adjust($r), $adjust($g), $adjust($b));
    }
}

if (!function_exists('store_theme_vars')) {
    function store_theme_vars($company = null) {
        static $cache = null;
        if ($cache !== null) return $cache;
        if (empty($company) || !is_array($company) || !isset($company['store_primary_color'])) {
            $CI =& get_instance();
            if (isset($CI->Invoice_model)) { $company = $CI->Invoice_model->get_company_settings(); }
            elseif (!is_array($company)) { $company = []; }
        }
        // Vibrant "quick-commerce" default palette — deliberately distinct from
        // the old portal's brown/gold theme.
        $primary = trim($company['store_primary_color'] ?? '') ?: '#0DB14B';
        $accent  = trim($company['store_accent_color']  ?? '') ?: '#FFD400';

        $vars = [
            '--st-primary'       => $primary,
            '--st-primary-dark'  => store_hex_adjust($primary, -0.30),
            '--st-primary-light' => store_hex_adjust($primary, 0.30),
            '--st-primary-xlight'=> store_hex_adjust($primary, 0.93),
            '--st-accent'        => $accent,
            '--st-accent-dark'   => store_hex_adjust($accent, -0.25),
            '--st-accent-light'  => store_hex_adjust($accent, 0.75),
            '--st-bg'            => '#F8F8F8',
            '--st-ink'           => '#1D1D1D',
        ];
        $out = '';
        foreach ($vars as $k => $v) $out .= "  $k: $v;\n";
        return $cache = $out;
    }
}
