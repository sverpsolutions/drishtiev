<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Branded Excel export — logo, company block, dark heading band.
 *
 * Mirrors the house style already used by Reports::_export_excel() so every
 * export in the system looks the same. Extracted as a library so the newer
 * modules (loyalty, warranty) can share it without touching Reports.php.
 *
 * Falls back to CSV when PhpSpreadsheet is missing — always with a UTF-8 BOM,
 * because Excel reads a BOM-less CSV as Windows-1252 and turns "—" into "â€"".
 */
class Excel_report {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    private function company() {
        $out = [];
        foreach ($this->CI->db->get('company_settings')->result_array() as $row) {
            $out[$row['key_name']] = $row['key_value'];
        }
        return $out;
    }

    /**
     * @param string $title    Report heading
     * @param array  $headers  Column captions (may be empty for a summary-only sheet)
     * @param array  $rows     Array of flat arrays, one per line
     * @param array  $opts     'subtitle'     => string under the title (e.g. the period)
     *                         'summary'      => [label => value] block printed below the table
     *                         'summary_title'=> heading for that block
     *                         'filename'     => without extension
     *                         'highlight'    => summary labels to render bold/large
     */
    public function download($title, array $headers, array $rows, array $opts = []) {
        $c = $this->company();
        $name    = $c['company_name']    ?? 'ERP';
        $address = trim(str_replace("\n", ', ', $c['company_address'] ?? ''), ', ');
        $phone   = $c['company_phone']   ?? '';
        $gstin   = $c['company_gstin']   ?? '';
        $file    = $opts['filename'] ?? preg_replace('/[^A-Za-z0-9_-]/', '_', $title);

        $autoload = APPPATH . '../vendor/autoload.php';
        if (!file_exists($autoload)) {
            $this->csv_fallback($title, $headers, $rows, $opts, $name, $address, $phone, $gstin, $file);
            return;
        }
        require_once $autoload;

        $col_count = max(1, count($headers), 2);
        $last_col  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_count);

        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9 _-]/', '', $title), 0, 31) ?: 'Report');

        $r = 1;

        // Logo sits on its own row, tall enough not to overlap the name below.
        $logo = $c['company_logo'] ?? '';
        if (!empty($logo) && file_exists('./' . $logo)) {
            $d = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $d->setName('Logo');
            $d->setPath(realpath('./' . $logo));
            $d->setHeight(45);
            $d->setCoordinates("A{$r}");
            $d->setOffsetX(2); $d->setOffsetY(2);
            $d->setWorksheet($sheet);
            $sheet->getRowDimension($r)->setRowHeight(36);
            $r++;
        }

        $sheet->setCellValue("A{$r}", $name);
        $sheet->mergeCells("A{$r}:{$last_col}{$r}");
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(14);
        $r++;

        if ($address !== '') {
            $sheet->setCellValue("A{$r}", $address);
            $sheet->mergeCells("A{$r}:{$last_col}{$r}");
            $sheet->getStyle("A{$r}")->getFont()->setSize(9)->getColor()->setRGB('666666');
            $r++;
        }
        if ($phone !== '' || $gstin !== '') {
            $sheet->setCellValue("A{$r}", trim("Phone: {$phone}   |   GSTIN: {$gstin}"));
            $sheet->mergeCells("A{$r}:{$last_col}{$r}");
            $sheet->getStyle("A{$r}")->getFont()->setSize(9)->getColor()->setRGB('666666');
            $r++;
        }
        $r++;

        $sheet->setCellValue("A{$r}", strtoupper($title));
        $sheet->mergeCells("A{$r}:{$last_col}{$r}");
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(12);
        $r++;

        if (!empty($opts['subtitle'])) {
            $sheet->setCellValue("A{$r}", $opts['subtitle']);
            $sheet->mergeCells("A{$r}:{$last_col}{$r}");
            $sheet->getStyle("A{$r}")->getFont()->setSize(10)->setBold(true)->getColor()->setRGB('1E40AF');
            $r++;
        }

        $sheet->setCellValue("A{$r}", 'Printed on: ' . date('d M Y H:i'));
        $sheet->mergeCells("A{$r}:{$last_col}{$r}");
        $sheet->getStyle("A{$r}")->getFont()->setSize(9)->getColor()->setRGB('666666');
        $r += 2;

        if ($headers) {
            $hr = $r;
            foreach ($headers as $i => $h) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue("{$col}{$hr}", strtoupper($h));
            }
            $sheet->getStyle("A{$hr}:{$last_col}{$hr}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '343A40']],
                'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                                 'color' => ['rgb' => 'CCCCCC']]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
            $r++;

            foreach ($rows as $row) {
                foreach (array_values($row) as $i => $v) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                    // Phone numbers and codes must stay text or Excel eats leading
                    // zeros and turns long digits into scientific notation.
                    if (is_string($v) && $v !== '' && !is_numeric($v)) {
                        $sheet->setCellValue("{$col}{$r}", $v);
                    } elseif (is_string($v) && strlen($v) > 11 && ctype_digit($v)) {
                        $sheet->setCellValueExplicit("{$col}{$r}", $v,
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    } else {
                        $sheet->setCellValue("{$col}{$r}", $v);
                    }
                }
                $sheet->getStyle("A{$r}:{$last_col}{$r}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                                   'color' => ['rgb' => 'E5E7EB']]],
                ]);
                $r++;
            }
            $r++;
        }

        if (!empty($opts['summary'])) {
            $sheet->setCellValue("A{$r}", strtoupper($opts['summary_title'] ?? 'Summary'));
            $sheet->mergeCells("A{$r}:{$last_col}{$r}");
            $sheet->getStyle("A{$r}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                           'startColor' => ['rgb' => '1E40AF']],
            ]);
            $r++;

            $hi = $opts['highlight'] ?? [];
            foreach ($opts['summary'] as $label => $value) {
                $sheet->setCellValue("A{$r}", $label);
                $sheet->setCellValue("B{$r}", $value);
                $big = in_array($label, $hi, true);
                $sheet->getStyle("A{$r}:B{$r}")->applyFromArray([
                    'font'    => ['bold' => $big, 'size' => $big ? 12 : 11],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                                   'color' => ['rgb' => 'E5E7EB']]],
                ]);
                if ($big) {
                    $sheet->getStyle("A{$r}:B{$r}")->getFill()
                          ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                          ->getStartColor()->setRGB('EFF6FF');
                }
                $sheet->getStyle("B{$r}")->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $r++;
            }
        }

        for ($i = 0; $i < $col_count; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $cap = $headers[$i] ?? '';
            $sheet->getColumnDimension($col)->setWidth(
                $i === 0 ? max(26, strlen((string)$cap) + 6) : min(22, max(13, strlen((string)$cap) + 4))
            );
        }
        if ($headers) $sheet->freezePane('A' . ($hr + 1));

        // An xlsx is a zip — a single stray byte before it corrupts the file
        // even though the download still "looks fine".
        while (ob_get_level() > 0) { @ob_end_clean(); }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $file . '_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit;
    }

    private function csv_fallback($title, $headers, $rows, $opts, $name, $address, $phone, $gstin, $file) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $file . '_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // BOM — without it Excel mangles ₹ and —
        fputcsv($out, [$name]);
        if ($address !== '') fputcsv($out, [$address]);
        if ($phone !== '' || $gstin !== '') fputcsv($out, ["Phone: {$phone} | GSTIN: {$gstin}"]);
        fputcsv($out, []);
        fputcsv($out, [$title]);
        if (!empty($opts['subtitle'])) fputcsv($out, [$opts['subtitle']]);
        fputcsv($out, ['Printed on: ' . date('d M Y H:i')]);
        fputcsv($out, []);
        if ($headers) {
            fputcsv($out, $headers);
            foreach ($rows as $row) fputcsv($out, array_values($row));
            fputcsv($out, []);
        }
        if (!empty($opts['summary'])) {
            fputcsv($out, [strtoupper($opts['summary_title'] ?? 'Summary')]);
            foreach ($opts['summary'] as $k => $v) fputcsv($out, [$k, $v]);
        }
        fclose($out);
        exit;
    }
}
