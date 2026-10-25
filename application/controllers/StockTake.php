<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class StockTake extends Auth_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Stock_take_model', 'Location_model', 'Product_model', 'Invoice_model']);
    }

    // ─── Index: list all stock takes ────────────────────────────────
    public function index()
    {
        $loc_id = $this->active_location_id();

        $filters = [];
        if ($loc_id) {
            $filters['location_id'] = $loc_id;
        }

        $takes = $this->Stock_take_model->get_all($filters);

        // Check for any in_progress for current location
        $pending = null;
        if ($loc_id) {
            $pending = $this->Stock_take_model->get_pending_for_location($loc_id);
        }

        $this->render('stock_take/index', [
            'page_title'  => 'Stock Takes',
            'takes'       => $takes,
            'pending'     => $pending,
            'loc_id'      => $loc_id,
        ]);
    }

    // ─── Create: start a new stock take ────────────────────────────
    public function create()
    {
        if ($this->input->post()) {
            $location_id = (int)$this->input->post('location_id');
            $notes       = trim($this->input->post('notes'));

            if (!$location_id) {
                $this->session->set_flashdata('error', 'Please select a location to start the stock take.');
                redirect('stock_take/create');
            }

            $loc = $this->Location_model->get_by_id($location_id);
            if (!$loc) {
                $this->session->set_flashdata('error', 'Location #' . $location_id . ' not found. Please try again.');
                redirect('stock_take/create');
            }

            // Outlet users can only create for their own location
            $user_loc = $this->session->userdata('location_id');
            if ($user_loc && (int)$user_loc !== $location_id) {
                $this->session->set_flashdata('error', 'You can only create stock takes for your assigned location.');
                redirect('stock_take/create');
            }

            $user_id   = (int)($this->session->userdata('user_id') ?: 0);
            $user_name = $this->session->userdata('name') ?: 'Unknown';

            $id = $this->Stock_take_model->create($location_id, $loc->name, $user_id, $user_name, $notes);

            $this->log_activity('Stock Take', 'create', 'Started stock take for ' . $loc->name, $id);

            $this->session->set_flashdata('success', 'Stock take started. Please count all items.');
            redirect('stock_take/count/' . $id);
        }

        $locations = $this->Location_model->get_all(true);

        // Outlet user: pre-select their location
        $user_loc_id = $this->session->userdata('location_id') ?: null;

        $this->render('stock_take/create', [
            'page_title'  => 'Start New Stock Take',
            'locations'   => $locations,
            'user_loc_id' => $user_loc_id,
        ]);
    }

    // ─── Count: main counting page ─────────────────────────────────
    public function count($id)
    {
        $take = $this->Stock_take_model->get_by_id($id);
        if (!$take) {
            $this->session->set_flashdata('error', 'Stock take not found.');
            redirect('stock_take');
        }

        if ($take->status !== 'in_progress') {
            $this->session->set_flashdata('error', 'This stock take is ' . $take->status . '. You cannot count items.');
            redirect('stock_take/view/' . $id);
        }

        // Access control: outlet users can only see their own location
        $user_loc = $this->session->userdata('location_id');
        if ($user_loc && (int)$user_loc !== (int)$take->location_id) {
            $this->session->set_flashdata('error', 'Access denied.');
            redirect('stock_take');
        }

        $this->render('stock_take/count', [
            'page_title' => 'Stock Count — ' . $take->take_no . ' | ' . $take->location_name,
            'take'       => $take,
            'csrf_name'  => $this->security->get_csrf_token_name(),
            'csrf_hash'  => $this->security->get_csrf_hash(),
        ]);
    }

    // ─── AJAX: auto-save a single item count ───────────────────────
    public function save_ajax()
    {
        if (!$this->input->is_ajax_request() && !$this->input->post()) {
            show_404();
        }

        $stock_take_id = (int)$this->input->post('stock_take_id');
        $product_id    = (int)$this->input->post('product_id');
        $counted_qty   = $this->input->post('counted_qty');

        if ($counted_qty === '' || $counted_qty === null) {
            echo json_encode(['success' => false, 'message' => 'No value provided']);
            return;
        }

        $counted_qty = (float)$counted_qty;

        // Verify this stock take belongs to a location this user can access
        $take = $this->Stock_take_model->get_by_id($stock_take_id);
        if (!$take || $take->status !== 'in_progress') {
            echo json_encode(['success' => false, 'message' => 'Stock take not found or not active']);
            return;
        }

        $user_loc = $this->session->userdata('location_id');
        if ($user_loc && (int)$user_loc !== (int)$take->location_id) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $result = $this->Stock_take_model->save_item_count($stock_take_id, $product_id, $counted_qty);

        echo json_encode($result);
    }

    // ─── AJAX: lookup product for scan mode ────────────────────────
    public function lookup_ajax()
    {
        header('Content-Type: application/json');
        ob_start(); // capture any stray PHP notices/warnings

        $stock_take_id = (int)$this->input->post('stock_take_id');
        $q             = trim($this->input->post('q'));

        if (!$q || !$stock_take_id) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Missing query', 'q' => $q, 'id' => $stock_take_id]);
            return;
        }

        $take = $this->Stock_take_model->get_by_id($stock_take_id);
        if (!$take || $take->status !== 'in_progress') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Stock take not active, status=' . ($take ? $take->status : 'null')]);
            return;
        }

        try {
            $results = $this->Stock_take_model->lookup_product($q, $stock_take_id);
            $stray = ob_get_clean();
            echo json_encode(['success' => true, 'products' => $results, 'debug' => $stray ?: null]);
        } catch (Exception $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        }
    }

    // ─── AJAX: scan + save qty (cumulative) ────────────────────────
    public function scan_save_ajax()
    {
        $stock_take_id = (int)$this->input->post('stock_take_id');
        $product_id    = (int)$this->input->post('product_id');
        $qty           = (float)$this->input->post('qty');

        if (!$stock_take_id || !$product_id) {
            echo json_encode(['success' => false, 'message' => 'Missing data']); return;
        }

        $take = $this->Stock_take_model->get_by_id($stock_take_id);
        if (!$take || $take->status !== 'in_progress') {
            echo json_encode(['success' => false, 'message' => 'Stock take not active']); return;
        }

        $result = $this->Stock_take_model->scan_add_qty($stock_take_id, $product_id, $qty);
        echo json_encode($result);
    }

    // ─── AJAX: zero all uncounted items ────────────────────────────
    public function zero_uncounted_ajax()
    {
        $stock_take_id = (int)$this->input->post('stock_take_id');
        if (!$stock_take_id) { echo json_encode(['success' => false]); return; }

        $take = $this->Stock_take_model->get_by_id($stock_take_id);
        if (!$take || $take->status !== 'in_progress') {
            echo json_encode(['success' => false, 'message' => 'Not active']); return;
        }

        $this->Stock_take_model->zero_uncounted($stock_take_id);
        echo json_encode(['success' => true]);
    }

    // ─── View: read-only summary ────────────────────────────────────
    public function view($id)
    {
        $take = $this->Stock_take_model->get_by_id($id);
        if (!$take) {
            $this->session->set_flashdata('error', 'Stock take not found.');
            redirect('stock_take');
        }

        $user_loc = $this->session->userdata('location_id');
        if ($user_loc && (int)$user_loc !== (int)$take->location_id) {
            $this->session->set_flashdata('error', 'Access denied.');
            redirect('stock_take');
        }

        $items = $this->Stock_take_model->get_items($id);

        // Summary stats
        $total_items       = count($items);
        $variance_items    = 0;
        $surplus_items     = 0;
        $shortage_items    = 0;
        $total_variance_val = 0;

        foreach ($items as $item) {
            if ($item->counted_qty !== null) {
                $var = (float)$item->variance;
                if ($var != 0) {
                    $variance_items++;
                    if ($var > 0) {
                        $surplus_items++;
                    } else {
                        $shortage_items++;
                    }
                    $total_variance_val += $var * (float)($item->cost_price ?? 0);
                }
            }
        }

        $this->render('stock_take/view', [
            'page_title'         => 'Stock Take — ' . $take->take_no,
            'take'               => $take,
            'items'              => $items,
            'total_items'        => $total_items,
            'variance_items'     => $variance_items,
            'surplus_items'      => $surplus_items,
            'shortage_items'     => $shortage_items,
            'total_variance_val' => $total_variance_val,
            'csrf_name'          => $this->security->get_csrf_token_name(),
            'csrf_hash'          => $this->security->get_csrf_hash(),
        ]);
    }

    // ─── Finalize ───────────────────────────────────────────────────
    public function finalize($id)
    {
        if (!$this->input->post()) {
            redirect('stock_take/view/' . $id);
        }

        $take = $this->Stock_take_model->get_by_id($id);
        if (!$take || $take->status !== 'in_progress') {
            $this->session->set_flashdata('error', 'Cannot finalize: stock take is not in progress.');
            redirect('stock_take');
        }

        $user_loc = $this->session->userdata('location_id');
        if ($user_loc && (int)$user_loc !== (int)$take->location_id) {
            $this->session->set_flashdata('error', 'Access denied.');
            redirect('stock_take');
        }

        $adjust_stock = (bool)$this->input->post('adjust_stock');
        $user_id      = (int)($this->session->userdata('user_id') ?: 0);
        $user_name    = $this->session->userdata('name') ?: 'Unknown';

        $ok = $this->Stock_take_model->finalize($id, $user_id, $user_name, $adjust_stock, $this->Location_model);

        if ($ok) {
            $adj_note = $adjust_stock ? ' Stock has been adjusted.' : ' No stock adjustment applied.';
            $this->log_activity('Stock Take', 'finalize', 'Finalized ' . $take->take_no . $adj_note, $id, $take->take_no);
            $this->session->set_flashdata('success', 'Stock take finalized successfully.' . $adj_note);
        } else {
            $this->session->set_flashdata('error', 'Failed to finalize stock take.');
        }

        redirect('stock_take/view/' . $id);
    }

    // ─── Variance Report (printable, only items with variance) ─────
    public function variance_report($id)
    {
        $take = $this->Stock_take_model->get_by_id($id);
        if (!$take) { show_404(); }

        $user_loc = $this->session->userdata('location_id');
        if ($user_loc && (int)$user_loc !== (int)$take->location_id) {
            show_error('Access denied.', 403);
        }

        $all_items = $this->Stock_take_model->get_items($id);

        // Compute variance in PHP (no DB column) and filter to only non-zero variance items
        $variance_items = [];
        foreach ($all_items as $item) {
            if ($item->counted_qty === null) continue;
            $v = (float)$item->counted_qty - (float)$item->system_qty;
            if (round($v, 4) == 0) continue;
            $item->_variance = $v;
            $variance_items[] = $item;
        }

        // Sort: shortages first, then surpluses
        usort($variance_items, function($a, $b) {
            if ($a->_variance < $b->_variance) return -1;
            if ($a->_variance > $b->_variance) return 1;
            return 0;
        });

        $total_surplus  = 0; $total_shortage = 0; $total_val = 0;
        foreach ($variance_items as $item) {
            if ($item->_variance > 0) $total_surplus++;
            else $total_shortage++;
            $total_val += $item->_variance * (float)($item->cost_price ?? 0);
        }

        // Render as standalone HTML (no layout)
        $company = $this->Invoice_model->get_company_settings();
        $data = [
            'take'           => $take,
            'variance_items' => array_values($variance_items),
            'total_surplus'  => $total_surplus,
            'total_shortage' => $total_shortage,
            'total_val'      => $total_val,
            'company'        => $company,
        ];
        $this->load->view('stock_take/variance_report', $data);
    }

    // ─── Download blank Excel template ─────────────────────────────
    public function download_template()
    {
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            require_once APPPATH . '../vendor/autoload.php';
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('StockTake');

        // Header row
        $sheet->setCellValue('A1', 'item_code');
        $sheet->setCellValue('B1', 'mrp');
        $sheet->setCellValue('C1', 'qty');

        // Bold headers
        $bold = ['font' => ['bold' => true]];
        $sheet->getStyle('A1:C1')->applyFromArray($bold);
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(14);

        // Example row
        $sheet->setCellValue('A2', 'ITEM001');
        $sheet->setCellValue('B2', 50.00);
        $sheet->setCellValue('C2', 10);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="stock_take_template.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    // ─── Import stock counts from Excel ────────────────────────────
    public function import_excel($id)
    {
        header('Content-Type: application/json');

        $take = $this->Stock_take_model->get_by_id($id);
        if (!$take || $take->status !== 'in_progress') {
            echo json_encode(['success' => false, 'message' => 'Stock take not found or not in progress.']);
            return;
        }

        $user_loc = $this->session->userdata('location_id');
        if ($user_loc && (int)$user_loc !== (int)$take->location_id) {
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            return;
        }

        if (empty($_FILES['import_file']['tmp_name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $php_err = $_FILES['import_file']['error'] ?? -1;
            echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $php_err]);
            return;
        }

        $orig_name = $_FILES['import_file']['name'];
        $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            echo json_encode(['success' => false, 'message' => 'Only .xlsx, .xls or .csv files are allowed.']);
            return;
        }

        // Use tmp file directly — avoids move_uploaded_file permission issues
        $file_path = $_FILES['import_file']['tmp_name'];
        $ext = '.' . $ext;

        // Parse file
        $rows   = [];
        $errors = [];

        if ($ext === '.csv') {
            $fh = fopen($file_path, 'r');
            $header = fgetcsv($fh);
            // normalise header names
            $header = array_map(fn($h) => strtolower(trim($h)), $header);
            $col_code = array_search('item_code', $header);
            $col_mrp  = array_search('mrp', $header);
            $col_qty  = array_search('qty', $header);

            if ($col_code === false || $col_qty === false) {
                $errors[] = 'CSV must have columns: item_code, mrp, qty';
            } else {
                $line_no = 1;   // header consumed above
                while (($line = fgetcsv($fh)) !== false) {
                    $line_no++;
                    $code = trim($line[$col_code] ?? '');
                    $qty  = $line[$col_qty] ?? '';
                    $mrp  = ($col_mrp !== false) ? $line[$col_mrp] ?? null : null;
                    if ($code === '') continue;
                    $rows[] = ['item_code' => $code, 'mrp' => $mrp, 'qty' => $qty, '_row' => $line_no];
                }
            }
            fclose($fh);
        } else {
            // Excel
            if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                require_once APPPATH . '../vendor/autoload.php';
            }
            try {
                $sheet  = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path)->getActiveSheet();
                $header = [];
                $col_code = $col_mrp = $col_qty = null;
                foreach ($sheet->getRowIterator() as $row_idx => $row) {
                    $cells = [];
                    foreach ($row->getCellIterator() as $ci => $cell) {
                        $cells[$ci] = $cell->getValue();
                    }
                    if ($row_idx === 1) {
                        // detect columns by header name
                        foreach ($cells as $ci => $val) {
                            $v = strtolower(trim((string)$val));
                            if ($v === 'item_code') $col_code = $ci;
                            if ($v === 'mrp')       $col_mrp  = $ci;
                            if ($v === 'qty')       $col_qty  = $ci;
                        }
                        if (!$col_code || !$col_qty) {
                            $errors[] = 'Excel must have header columns: item_code, mrp, qty';
                            break;
                        }
                        continue;
                    }
                    $code = trim((string)($cells[$col_code] ?? ''));
                    $qty  = $cells[$col_qty] ?? '';
                    $mrp  = $col_mrp ? ($cells[$col_mrp] ?? null) : null;
                    if ($code === '') continue;
                    $rows[] = ['item_code' => $code, 'mrp' => $mrp, 'qty' => $qty, '_row' => $row_idx];
                }
            } catch (Exception $e) {
                $errors[] = 'Could not read file: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(' | ', $errors)]);
            return;
        }

        if (empty($rows)) {
            echo json_encode(['success' => false, 'message' => 'No data rows found in the file.']);
            return;
        }

        // Import rows into stock take
        $result = $this->Stock_take_model->import_excel_rows($id, $rows);

        $this->log_activity('Stock Take', 'import_excel', "Imported {$result['matched']} items via Excel for {$take->take_no}", $id);

        // How the matches were made, so a file that only lands via the fallback
        // passes is visibly different from a clean one.
        $bm = $result['by_method'];
        $method_bits = [];
        if ($bm['item_code'])   $method_bits[] = "{$bm['item_code']} by item code";
        if ($bm['barcode'])     $method_bits[] = "{$bm['barcode']} by barcode/EAN";
        if ($bm['zero_padded']) $method_bits[] = "{$bm['zero_padded']} by item code ignoring leading zeros";

        $msg = "Import complete: {$result['matched']} item(s) updated";
        if ($method_bits) $msg .= ' (' . implode(', ', $method_bits) . ')';
        $msg .= '.';

        // Keep the problem rows around so they can be pulled down as a file —
        // the on-screen list is fine for 3 codes, useless for 300.
        $issues = $result['issue_rows'];
        if (count($issues) > 2000) $issues = array_slice($issues, 0, 2000); // keep the session sane
        if ($issues) {
            $this->session->set_userdata('stock_take_import_issues_' . (int)$id, [
                'file'    => $orig_name,
                'at'      => date('Y-m-d H:i:s'),
                'rows'    => $issues,
                'matched' => $result['matched'],
                'total'   => count($rows),
                'missing' => $result['missing_from_file'],
            ]);
        } else {
            $this->session->unset_userdata('stock_take_import_issues_' . (int)$id);
        }

        echo json_encode([
            'success'         => true,
            'matched'         => $result['matched'],
            'not_found'       => $result['not_found'],
            'not_found_codes' => $result['not_found_codes'],
            'invalid_qty'     => $result['invalid_qty'],
            'invalid_codes'   => $result['invalid_codes'],
            'duplicates'      => $result['duplicates'],
            'duplicate_codes' => $result['duplicate_codes'],
            'issue_count'     => count($issues),
            'issue_url'       => $issues ? base_url('stock_take/import_issues/' . (int)$id) : null,
            'message'         => $msg,
        ]);
    }

    // ─── Download the "rows that didn't import" report ──────────────
    public function import_issues($id)
    {
        $take = $this->Stock_take_model->get_by_id($id);
        if (!$take) { show_404(); }

        $user_loc = $this->session->userdata('location_id');
        if ($user_loc && (int)$user_loc !== (int)$take->location_id) {
            show_error('Access denied.', 403);
        }

        $data = $this->session->userdata('stock_take_import_issues_' . (int)$id);
        if (!$data || empty($data['rows'])) {
            $this->session->set_flashdata('error',
                'No import issues are on file for this stock take. Run the Excel import again, then download the report from the result panel.');
            redirect('stock_take/count/' . $id);
        }

        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            require_once APPPATH . '../vendor/autoload.php';
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Issues');

        $sheet->setCellValue('A1', 'Stock Take');
        $sheet->setCellValue('B1', $take->take_no . '  —  ' . $take->location_name);
        $sheet->setCellValue('A2', 'Source File');
        $sheet->setCellValue('B2', $data['file']);
        $sheet->setCellValue('A3', 'Imported At');
        $sheet->setCellValue('B3', $data['at']);
        $sheet->setCellValue('A4', 'Rows In File');
        $sheet->setCellValue('B4', (int)$data['total'] . ' — ' . (int)$data['matched'] . ' updated, '
                                 . count($data['rows']) . ' need attention');
        $sheet->setCellValue('A5', 'Not In File');
        $sheet->setCellValue('B5', (int)($data['missing'] ?? 0)
            . ' item(s) in this stock take have no row in the file — they stay uncounted '
            . '(and become zero if "zero uncounted" is used before finalize).');
        $sheet->getStyle('A1:A5')->applyFromArray(['font' => ['bold' => true]]);

        $head = ['Excel Row', 'Item Code (as in file)', 'MRP', 'Qty', 'Issue',
                 'Matched Product', 'Product ID', 'What To Check'];
        $col = 'A';
        foreach ($head as $h) {
            $sheet->setCellValue($col . '7', $h);
            $col++;
        }
        $sheet->getStyle('A7:H7')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '1E40AF']],
        ]);

        $r = 8;
        foreach ($data['rows'] as $row) {
            // Item code written as text so Excel doesn't strip leading zeros
            // again in the very report meant to diagnose that problem.
            $sheet->setCellValueExplicit('A' . $r, $row['row'] ?? '',
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $r, (string)$row['item_code'],
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $r, $row['mrp']);
            $sheet->setCellValue('D' . $r, $row['qty']);
            $sheet->setCellValue('E' . $r, $row['issue']);
            $sheet->setCellValue('F' . $r, $row['product_name'] ?? '');
            $sheet->setCellValue('G' . $r, $row['product_id'] ?? '');
            $sheet->setCellValue('H' . $r, $row['detail'] ?? '');
            $r++;
        }

        foreach (['A' => 11, 'B' => 24, 'C' => 10, 'D' => 10, 'E' => 24,
                  'F' => 38, 'G' => 11, 'H' => 80] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }
        $sheet->getStyle('H8:H' . ($r - 1))->getAlignment()->setWrapText(true);
        $sheet->freezePane('A8');

        $fname = 'import_issues_' . preg_replace('/[^A-Za-z0-9\-_]/', '', $take->take_no)
               . '_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    // ─── Cancel ─────────────────────────────────────────────────────
    public function cancel($id)
    {
        if (!$this->is_admin()) {
            $this->session->set_flashdata('error', 'Admin access required to cancel a stock take.');
            redirect('stock_take');
        }

        $take = $this->Stock_take_model->get_by_id($id);
        if (!$take) {
            $this->session->set_flashdata('error', 'Stock take not found.');
            redirect('stock_take');
        }

        $this->Stock_take_model->cancel($id);
        $this->log_activity('Stock Take', 'cancel', 'Cancelled ' . $take->take_no, $id, $take->take_no);
        $this->session->set_flashdata('success', 'Stock take cancelled.');
        redirect('stock_take');
    }
}
