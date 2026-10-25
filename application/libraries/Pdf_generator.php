<?php
// ============================================================
// FILE: application/libraries/Pdf_generator.php
// Wrapper around mPDF for CI3
// Install mPDF via Composer: composer require mpdf/mpdf
// Or place mpdf folder in application/third_party/mpdf
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Pdf_generator {

    private $mpdf;

    public function __construct() {
        // Try Composer autoload first, then manual third_party path
        if (file_exists(APPPATH . '../vendor/autoload.php')) {
            require_once APPPATH . '../vendor/autoload.php';
        } elseif (file_exists(APPPATH . 'third_party/mpdf/vendor/autoload.php')) {
            require_once APPPATH . 'third_party/mpdf/vendor/autoload.php';
        } else {
            show_error('mPDF not found. Run: composer require mpdf/mpdf in project root, or see README.');
        }
    }

    /**
     * Generate PDF from HTML string
     *
     * @param string $html      Full HTML content
     * @param string $filename  Output filename (without .pdf)
     * @param string $mode      'download' | 'inline' | 'string'
     * @param string|null $force_printer_type  Pass 'A4' to override the store's
     *        POS receipt printer setting — reports/exports should never be
     *        rendered at receipt (3in/2in) width, only actual bill printing should.
     * @return string|void      Returns string if mode=string
     */
    public function generate($html, $filename = 'document', $mode = 'download', $force_printer_type = null) {
        $CI =& get_instance();
        if (!isset($CI->Invoice_model)) {
            $CI->load->model('Invoice_model');
        }
        $company = $CI->Invoice_model->get_company_settings();
        $printer_type = $force_printer_type ?? ($company['printer_type'] ?? 'A4');

        $format = 'A4';
        $margin_top = 10;
        $margin_bottom = 10;
        $margin_left = 12;
        $margin_right = 12;

        if ($printer_type === '3in') {
            $format = [80, 240];
            $margin_top = 4;
            $margin_bottom = 4;
            $margin_left = 5;
            $margin_right = 5;
        } elseif ($printer_type === '2in') {
            $format = [58, 220];
            $margin_top = 3;
            $margin_bottom = 3;
            $margin_left = 4;
            $margin_right = 4;
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => $format,
            'margin_top'    => $margin_top,
            'margin_bottom' => $margin_bottom,
            'margin_left'   => $margin_left,
            'margin_right'  => $margin_right,
            'tempDir'       => sys_get_temp_dir(),
            'default_font'  => 'dejavusans',
        ]);

        $mpdf->SetTitle($filename);
        $mpdf->SetAuthor($company['company_name'] ?? 'ERP');
        $mpdf->WriteHTML($html);

        switch ($mode) {
            case 'inline':
                $mpdf->Output($filename . '.pdf', 'I');
                break;
            case 'string':
                return $mpdf->Output($filename . '.pdf', 'S');
            case 'save':
                $path = APPPATH . '../assets/pdf/' . $filename . '.pdf';
                $mpdf->Output($path, 'F');
                return $path;
            default:
                $mpdf->Output($filename . '.pdf', 'D');
        }
    }

    /**
     * Generate and email PDF as attachment
     * Returns the PDF as a string for attaching to email
     */
    public function as_string($html, $filename = 'invoice') {
        return $this->generate($html, $filename, 'string');
    }
}
