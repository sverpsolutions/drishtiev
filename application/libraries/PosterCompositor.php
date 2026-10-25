<?php
// ============================================================
// FILE: application/libraries/PosterCompositor.php
// GD-based image compositor for WhatsApp promotional posters: takes an
// uploaded background image + campaign text + an optional QR code and
// produces a finished PNG/JPEG. GD can composite onto a template, it can't
// originate artwork — background images must come from the user/a stock
// source (see wa_poster_templates).
//
// Uses the DejaVu Sans fonts already bundled with mPDF (vendor/mpdf/mpdf/
// ttfonts/) rather than depending on OS fonts (C:/Windows/Fonts locally vs
// whatever's on Hostinger's Linux box) — keeps this portable across both.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class PosterCompositor {

    private $font_bold;
    private $font_regular;

    public function __construct() {
        $this->font_bold    = FCPATH . 'vendor/mpdf/mpdf/ttfonts/DejaVuSans-Bold.ttf';
        $this->font_regular = FCPATH . 'vendor/mpdf/mpdf/ttfonts/DejaVuSans.ttf';
    }

    // $background_path is FCPATH-relative (e.g. assets/uploads/wa_posters/backgrounds/x.jpg).
    // $texts: ['headline'=>, 'offer'=>, 'valid_till'=>, 'store_name'=>]. $qr_data is the
    // string to encode (a URL, coupon code, etc.) — QR is skipped if null/empty.
    public function generate($background_path, array $texts, $qr_data = null) {
        $full_bg = FCPATH . ltrim(str_replace('\\', '/', $background_path), '/');
        if (!is_file($full_bg)) {
            return ['ok' => false, 'error' => 'Background image not found on disk.'];
        }
        if (!extension_loaded('gd')) {
            return ['ok' => false, 'error' => 'The GD PHP extension is not available on this server.'];
        }

        $info = @getimagesize($full_bg);
        if (!$info) {
            return ['ok' => false, 'error' => 'Could not read background image dimensions.'];
        }

        $img = $this->_load_image($full_bg, $info[2]);
        if (!$img) {
            return ['ok' => false, 'error' => 'Unsupported background image format (use JPG or PNG).'];
        }

        $w = imagesx($img);
        $h = imagesy($img);

        // Semi-transparent dark band across the bottom so light-on-dark text
        // stays legible regardless of what the background artwork looks like.
        $band_h = (int)round($h * 0.30);
        $band   = imagecolorallocatealpha($img, 0, 0, 0, 55);
        imagefilledrectangle($img, 0, $h - $band_h, $w, $h, $band);

        $white = imagecolorallocate($img, 255, 255, 255);
        $gold  = imagecolorallocate($img, 246, 205, 60);

        $qr_size = $qr_data ? (int)round($h * 0.20) : 0;
        $pad     = (int)round($w * 0.05);
        $text_max_w = $w - (2 * $pad) - ($qr_size ? $qr_size + $pad : 0);
        $y = $h - $band_h + (int)round($band_h * 0.30);

        if (!empty($texts['headline'])) {
            $size = $this->_fit_font_size($texts['headline'], $text_max_w, 40, 18, $this->font_bold);
            imagettftext($img, $size, 0, $pad, $y, $gold, $this->font_bold, $texts['headline']);
            $y += $size + 16;
        }
        if (!empty($texts['offer'])) {
            $size = $this->_fit_font_size($texts['offer'], $text_max_w, 24, 13, $this->font_bold);
            imagettftext($img, $size, 0, $pad, $y, $white, $this->font_bold, $texts['offer']);
            $y += $size + 12;
        }
        if (!empty($texts['valid_till'])) {
            imagettftext($img, 13, 0, $pad, $y, $white, $this->font_regular, $texts['valid_till']);
        }
        if (!empty($texts['store_name'])) {
            imagettftext($img, 12, 0, $pad, $h - 14, $white, $this->font_regular, $texts['store_name']);
        }

        if ($qr_size) {
            $qr_img = $this->_fetch_qr($qr_data, 300);
            if ($qr_img) {
                $qr_resized = imagescale($qr_img, $qr_size, $qr_size);
                $qx = $w - $qr_size - $pad;
                $qy = $h - $qr_size - (int)round($band_h * 0.15);
                imagefilledrectangle($img, $qx - 8, $qy - 8, $qx + $qr_size + 8, $qy + $qr_size + 8, $white);
                imagecopy($img, $qr_resized, $qx, $qy, 0, 0, $qr_size, $qr_size);
                imagedestroy($qr_resized);
                imagedestroy($qr_img);
            }
        }

        $filename = 'poster_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.jpg';
        $out_rel  = 'assets/uploads/wa_posters/generated/' . $filename;
        imagejpeg($img, FCPATH . $out_rel, 88);
        imagedestroy($img);

        return ['ok' => true, 'path' => $out_rel];
    }

    private function _fit_font_size($text, $max_width, $start_size, $min_size, $font) {
        $size = $start_size;
        while ($size > $min_size) {
            $box    = imagettfbbox($size, 0, $font, $text);
            $text_w = abs($box[2] - $box[0]);
            if ($text_w <= $max_width) break;
            $size -= 2;
        }
        return $size;
    }

    private function _load_image($path, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG: return @imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:  return @imagecreatefrompng($path);
            case IMAGETYPE_GIF:  return @imagecreatefromgif($path);
            default: return false;
        }
    }

    // Reuses the same external QR service (api.qrserver.com) already used for
    // the invoice PDF's payment/portal QR codes (see billing/print.php) —
    // fetched as raw bytes here instead of embedded as an <img> since this is
    // a GD composite, not HTML.
    private function _fetch_qr($data, $size = 300) {
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&qzone=2&ecc=M&data=' . rawurlencode($data);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $bytes = curl_exec($ch);
        curl_close($ch);
        if (!$bytes) return false;
        return @imagecreatefromstring($bytes);
    }
}
