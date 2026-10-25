<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Minimal streaming .xlsx writer for very large reports (50k+ rows) where
 * PhpSpreadsheet would run out of memory. Rows are written straight to a temp
 * sheet XML file; finish() zips the parts into a real Excel workbook.
 *
 * Supports: a logo image, merged heading rows, bold header row with fill,
 * numeric cells, column widths, frozen header row. Styles (s=):
 *   0 normal | 1 company name (bold 14) | 2 small grey | 3 header (bold, fill, border)
 *   4 number 0.00 | 5 title (bold 12) | 6 bold | 7 bold number 0.00 | 8 number (general)
 */
class XlsxStream {

    private $tmp_dir;
    private $sheet_fh;
    private $row = 0;
    private $ncols = 1;
    private $merges = [];
    private $logo = null;        // ['path'=>..., 'ext'=>..., 'w'=>px, 'h'=>px]
    private $freeze_row = 0;
    private $widths = [];

    public function __construct($params = []) {}

    public static function col($n) {               // 1 -> A
        $s = '';
        while ($n > 0) { $m = ($n - 1) % 26; $s = chr(65 + $m) . $s; $n = intdiv($n - 1, 26); }
        return $s;
    }

    private static function x($v) {
        $v = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string)$v);
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** $widths: column widths (chars) — also sets the column count */
    public function start(array $widths) {
        $this->widths = $widths;
        $this->ncols  = max(1, count($widths));
        $this->tmp_dir = sys_get_temp_dir() . '/xlsx_' . uniqid('', true);
        mkdir($this->tmp_dir, 0700, true);
        $this->sheet_fh = fopen($this->tmp_dir . '/rows.xml', 'w');
    }

    /** Logo in the top-left corner; call before the first row. Height in px. */
    public function logo($path, $height = 45) {
        if (!$path || !is_file($path)) return false;
        $info = @getimagesize($path);
        if (!$info) return false;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'jpg' ? 'jpeg' : strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpeg', 'gif'], true)) return false;
        $h = $height; $w = (int)round($info[0] * $height / max(1, $info[1]));
        $this->logo = ['path' => $path, 'ext' => $ext, 'w' => $w, 'h' => $h];
        // a tall first row reserved for the logo
        $this->row++;
        fwrite($this->sheet_fh, '<row r="' . $this->row . '" ht="' . round($h * 0.78) . '" customHeight="1"/>');
        return true;
    }

    /** One text line spanning all columns (company name, address, title...) */
    public function banner($text, $style = 0) {
        $this->row++;
        $r = $this->row;
        fwrite($this->sheet_fh, '<row r="' . $r . '"><c r="A' . $r . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . self::x($text) . '</t></is></c></row>');
        if ($this->ncols > 1) $this->merges[] = 'A' . $r . ':' . self::col($this->ncols) . $r;
    }

    public function blank() { $this->row++; fwrite($this->sheet_fh, '<row r="' . $this->row . '"/>'); }

    /** Header row; frozen so it stays visible while scrolling */
    public function header(array $cells) {
        $this->row(array_values($cells), 3);
        $this->freeze_row = $this->row;
    }

    /**
     * Data row. $num_style applies to numeric cells (4 = 0.00, 8 = general).
     * Strings stay text (item codes / barcodes keep their leading zeros).
     */
    public function row(array $cells, $style = 0, $num_style = null, array $text_cols = []) {
        $this->row++;
        $r = $this->row;
        $xml = '<row r="' . $r . '">';
        $i = 0;
        foreach ($cells as $v) {
            $i++;
            if ($v === null || $v === '') continue;
            $ref = self::col($i) . $r;
            if (!in_array($i, $text_cols, true) && (is_int($v) || is_float($v))) {
                $s = $num_style !== null ? $num_style : ($style ?: 0);
                $xml .= '<c r="' . $ref . '" s="' . $s . '"><v>' . (is_float($v) ? rtrim(rtrim(sprintf('%.6F', $v), '0'), '.') : $v) . '</v></c>';
            } else {
                $xml .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . self::x($v) . '</t></is></c>';
            }
        }
        fwrite($this->sheet_fh, $xml . '</row>');
    }

    /** Builds the .xlsx and returns its temp path (caller streams + deletes it) */
    public function finish($sheet_name = 'Report') {
        fclose($this->sheet_fh);
        $d = $this->tmp_dir;
        $sheet_name = substr(preg_replace('/[\[\]\*\?\/\\\\:]/', '', $sheet_name), 0, 31) ?: 'Report';

        $cols = '<cols>';
        foreach ($this->widths as $i => $w) $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float)$w . '" customWidth="1"/>';
        $cols .= '</cols>';
        $views = '<sheetViews><sheetView workbookViewId="0"' . ($this->freeze_row ? '' : ' tabSelected="1"') . '>'
               . ($this->freeze_row ? '<pane ySplit="' . $this->freeze_row . '" topLeftCell="A' . ($this->freeze_row + 1) . '" activePane="bottomLeft" state="frozen"/>' : '')
               . '</sheetView></sheetViews>';

        $sheet = fopen($d . '/sheet1.xml', 'w');
        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . $views . $cols . '<sheetData>');
        $rows = fopen($d . '/rows.xml', 'r');
        stream_copy_to_stream($rows, $sheet);
        fclose($rows);
        fwrite($sheet, '</sheetData>');
        if ($this->merges) {
            fwrite($sheet, '<mergeCells count="' . count($this->merges) . '">');
            foreach ($this->merges as $m) fwrite($sheet, '<mergeCell ref="' . $m . '"/>');
            fwrite($sheet, '</mergeCells>');
        }
        fwrite($sheet, '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>');
        if ($this->logo) fwrite($sheet, '<drawing r:id="rId1"/>');
        fwrite($sheet, '</worksheet>');
        fclose($sheet);

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . ($this->logo ? '<Default Extension="' . $this->logo['ext'] . '" ContentType="image/' . $this->logo['ext'] . '"/>' : '')
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . ($this->logo ? '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>' : '')
            . '</Types>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
              . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::x($sheet_name) . '" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $wbrels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
            . '<fonts count="5"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="14"/><name val="Calibri"/></font>'
            . '<font><sz val="9"/><color rgb="FF666666"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="12"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF3E2723"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFBBBBBB"/></left><right style="thin"><color rgb="FFBBBBBB"/></right><top style="thin"><color rgb="FFBBBBBB"/></top><bottom style="thin"><color rgb="FFBBBBBB"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="9">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="164" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

        $out = $d . '/out.xlsx';
        $zip = new ZipArchive();
        if ($zip->open($out, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new Exception('Cannot create xlsx');
        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $wb);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbrels);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->addFile($d . '/sheet1.xml', 'xl/worksheets/sheet1.xml');
        if ($this->logo) {
            $emu_w = $this->logo['w'] * 9525; $emu_h = $this->logo['h'] * 9525;
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>');
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image1.' . $this->logo['ext'] . '"/></Relationships>');
            $zip->addFromString('xl/drawings/drawing1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<xdr:oneCellAnchor><xdr:from><xdr:col>0</xdr:col><xdr:colOff>19050</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>19050</xdr:rowOff></xdr:from>'
                . '<xdr:ext cx="' . $emu_w . '" cy="' . $emu_h . '"/>'
                . '<xdr:pic><xdr:nvPicPr><xdr:cNvPr id="2" name="Logo"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr>'
                . '<xdr:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
                . '<xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $emu_w . '" cy="' . $emu_h . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic>'
                . '<xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>');
            $zip->addFile($this->logo['path'], 'xl/media/image1.' . $this->logo['ext']);
        }
        $zip->close();
        return $out;
    }

    public function cleanup() {
        if (!$this->tmp_dir || !is_dir($this->tmp_dir)) return;
        foreach (glob($this->tmp_dir . '/*') as $f) @unlink($f);
        @rmdir($this->tmp_dir);
    }
}
