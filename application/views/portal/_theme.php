<?php
// FILE: application/views/portal/_theme.php
// Shared customer-portal colour theme. Derives all CSS shades from TWO
// admin settings — portal_primary_color & portal_accent_color — so the
// portal palette can be matched to the company logo from Settings.
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('portal_hex_adjust')) {
    /**
     * Lighten ($factor > 0, toward white) or darken ($factor < 0, toward black) a hex colour.
     * $factor is 0..1. Returns #RRGGBB. Invalid input is returned untouched.
     */
    function portal_hex_adjust($hex, $factor) {
        $hex = ltrim(trim((string)$hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return '#'.$hex;
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $adj = function ($c) use ($factor) {
            $c = ($factor >= 0) ? $c + (255 - $c) * $factor : $c * (1 + $factor);
            return max(0, min(255, (int)round($c)));
        };
        return sprintf('#%02X%02X%02X', $adj($r), $adj($g), $adj($b));
    }
}

if (!function_exists('portal_theme_vars')) {
    /**
     * Emit the brand colour custom-properties for a portal :root block.
     * Pass the page's $company array; if absent, settings are loaded from DB.
     * Cached per-request so re-calls on the same page cost nothing.
     */
    function portal_theme_vars($company = null) {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        if (empty($company) || !is_array($company) || !isset($company['portal_primary_color'])) {
            $CI =& get_instance();
            if (isset($CI->Invoice_model)) {
                $company = $CI->Invoice_model->get_company_settings();
            } elseif (!is_array($company)) {
                $company = [];
            }
        }

        // Defaults = existing Sandal House brown + gold.
        $primary = trim($company['portal_primary_color'] ?? '') ?: '#3B2314';
        $accent  = trim($company['portal_accent_color']  ?? '') ?: '#F6CD3C';

        $vars = [
            '--primary'        => $primary,
            '--primary-dark'   => portal_hex_adjust($primary, -0.35),
            '--primary-light'  => portal_hex_adjust($primary,  0.18),
            '--primary-xlight' => portal_hex_adjust($primary,  0.92),
            '--gold'           => $accent,
            '--gold-dark'      => portal_hex_adjust($accent, -0.22),
            '--gold-light'     => portal_hex_adjust($accent,  0.82),
            '--accent'         => portal_hex_adjust($accent, -0.10),
            '--bg'             => portal_hex_adjust($accent,  0.93),
            '--bg-page'        => portal_hex_adjust($accent,  0.93),
        ];

        $out = '';
        foreach ($vars as $k => $v) {
            $out .= "  $k: $v;\n";
        }
        return $cache = $out;
    }
}
