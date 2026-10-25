<?php
// ============================================================
// FILE: application/helpers/barcode_helper.php
// Helpers for barcode scanning in the billing interface
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Generate a barcode HTML string using barcode128 pure PHP
 * No external library needed for display — uses SVG output
 * For printing labels, use picqer/php-barcode-generator via Composer
 *   composer require picqer/php-barcode-generator
 */
function generate_barcode_svg($code, $width = 2, $height = 50) {
    // Uses picqer library if available, else returns a span
    if (!class_exists('Picqer\Barcode\BarcodeGeneratorSVG')) {
        return '<span class="badge badge-secondary">' . htmlspecialchars($code) . '</span>';
    }
    $generator = new Picqer\Barcode\BarcodeGeneratorSVG();
    return $generator->getBarcode($code, $generator::TYPE_CODE_128, $width, $height);
}

/**
 * Generate barcode as PNG base64 (for embedding in PDF/email)
 */
function generate_barcode_png_base64($code) {
    if (!class_exists('Picqer\Barcode\BarcodeGeneratorPNG')) {
        return '';
    }
    $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
    $png = $generator->getBarcode($code, $generator::TYPE_CODE_128, 2, 40);
    return 'data:image/png;base64,' . base64_encode($png);
}
