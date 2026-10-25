<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class StockTransfers extends Auth_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Location_model');
        $this->load->model('Stock_transfer_model');
        $this->load->model('Product_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $filters = [
            'status'      => $this->input->get('status'),
            'date_from'   => $this->input->get('date_from'),
            'date_to'     => $this->input->get('date_to'),
            'q'           => trim((string)$this->input->get('q', TRUE)),
        ];
        // Non-owners only see their location's transfers
        if (!$this->is_owner()) {
            $filters['location_id'] = $this->active_location_id();
        } else {
            $filters['from_location_id'] = $this->input->get('from_location_id') ?: null;
            $filters['to_location_id']   = $this->input->get('to_location_id') ?: null;
        }
        $data['transfers']   = $this->Stock_transfer_model->get_all($filters);
        $data['in_transit_count'] = $this->Stock_transfer_model->get_in_transit_count($this->is_owner() ? null : $this->active_location_id());
        $data['locations']   = $this->Location_model->get_all(true);
        $data['filters']     = $filters;
        $data['page_title']  = 'Stock Transfers';
        $this->render('stock_transfers/index', $data);
    }

    public function create()
    {
        $locations = $this->Location_model->get_all(true);

        // Transfers always need a real source/destination — "All Locations" isn't a
        // physical location, so there's nothing concrete to send from or receive into.
        if (!$this->active_location_id()) {
            $this->session->set_flashdata('error', 'Switch to a specific location (top-right) before creating a stock transfer — "All Locations" has no stock of its own to send or receive.');
            redirect('stock_transfers');
        }

        if ($this->input->post()) {
            $from_id = (int)$this->input->post('from_location_id');
            $to_id   = (int)$this->input->post('to_location_id');
            $items_raw = $this->input->post('items');

            if ($from_id === $to_id) {
                $this->session->set_flashdata('error', 'From and To locations cannot be the same.');
                redirect('stock_transfers/create');
            }
            if (!$this->_loc_allowed($from_id, $to_id)) {
                $this->session->set_flashdata('error', 'You can only raise transfers into or out of your own location.');
                redirect('stock_transfers/create');
            }

            // Determine transfer type
            $from_loc = $this->Location_model->get_by_id($from_id);
            $to_loc   = $this->Location_model->get_by_id($to_id);
            $type = 'warehouse_to_outlet';
            if ($from_loc->type === 'outlet' && $to_loc->type === 'outlet') {
                $type = 'outlet_to_outlet';
            } elseif ($from_loc->type === 'outlet' && $to_loc->type === 'warehouse') {
                $type = 'outlet_to_warehouse';
            }

            // Determine status from user's button choice
            $save_action = $this->input->post('save_action') ?: 'draft';
            if ($save_action === 'pending') {
                $status = 'pending'; // submitted for authorization
            } else {
                $status = 'draft';   // saved as draft
            }

            $items = [];
            $serial_pick = [];   // parallel to $items; JSON of picked serials per line
            if (!empty($items_raw) && is_array($items_raw)) {
                foreach ($items_raw as $item) {
                    if (empty($item['product_id']) || empty($item['requested_qty'])) continue;
                    $p_name = $prod ? $prod->name : '';
                    if (!empty($item['size_name']) || !empty($item['colour_name'])) {
                        $p_name .= ' [' . ($item['colour_name'] ?? '') . ' / ' . ($item['size_name'] ?? '') . ']';
                    }
                    $row_data = [
                        'product_id'    => (int)$item['product_id'],
                        'variant_id'    => !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                        'variant_sku'   => !empty($item['variant_sku']) ? trim($item['variant_sku']) : null,
                        'size_name'     => !empty($item['size_name']) ? trim($item['size_name']) : null,
                        'colour_name'   => !empty($item['colour_name']) ? trim($item['colour_name']) : null,
                        'product_name'  => $p_name,
                        'hsn_code'      => $prod ? $prod->hsn_code : '',
                        'unit'          => $prod ? $prod->unit : 'PCS',
                        'requested_qty' => (float)$item['requested_qty'],
                        'rate'          => $prod ? (float)$prod->cost_price : 0,
                    ];
                    if ($this->db->field_exists('remarks', 'stock_transfer_items')) {
                        $row_data['remarks'] = trim($item['remarks'] ?? '');
                    }
                    if ($this->db->field_exists('prev_day_sales_qty', 'stock_transfer_items')) {
                        $row_data['prev_day_sales_qty'] = ($item['prev_day_sales_qty'] ?? '') !== '' ? (float)$item['prev_day_sales_qty'] : null;
                        $row_data['suggested_qty']      = ($item['suggested_qty'] ?? '') !== ''      ? (float)$item['suggested_qty']      : null;
                        $row_data['suggestion_source']  = !empty($item['suggestion_source'])          ? $item['suggestion_source']          : null;
                    }
                    $items[] = $row_data;
                    $serial_pick[] = $item['serial_json'] ?? '';
                }
            }

            if (empty($items)) {
                $this->session->set_flashdata('error', 'Please add at least one item.');
                redirect('stock_transfers/create');
            }

            // ── Serial selection for the transfer ───────────────────────────
            // Which physical units are moving is decided when the transfer is
            // raised; complete() then moves exactly those. Only units in stock
            // at the source location may be picked — sold, under-repair,
            // returned and scrapped units are rejected by validate_outbound().
            $this->load->model('Serial_model');
            $tx_serial_plan = [];
            $tx_errors      = [];
            if ($this->Serial_model->schema_ready()) {
                $smap = $this->Serial_model->serialized_map(array_column($items, 'product_id'));
                $seen = [];
                foreach ($items as $ix => $it) {
                    $prod = $smap[(int)$it['product_id']] ?? null;
                    if (!$prod || (int)$prod->maintain_serial !== 1) continue;

                    $list = json_decode((string)($serial_pick[$ix] ?? ''), true);
                    if (!is_array($list)) $list = [];
                    $need = (int)$it['requested_qty'];

                    if (count($list) !== $need) {
                        $tx_errors[] = 'Select exactly ' . $need . ' serial number(s) to transfer for "'
                                     . $prod->name . '" (' . count($list) . ' selected).';
                        continue;
                    }
                    foreach ($list as $sn) {
                        $key = $this->Serial_model->normalize($sn);
                        if (isset($seen[$key])) { $tx_errors[] = 'Serial ' . $key . ' selected twice.'; continue; }
                        $seen[$key] = true;
                        // Location is checked against the SOURCE, not the user's
                        // active location — a manager may raise a transfer between
                        // two branches they are not standing in.
                        $chk = $this->Serial_model->validate_outbound($key, $it['product_id'], $from_id);
                        if (!$chk['ok']) { $tx_errors[] = $chk['error']; continue; }
                        $tx_serial_plan[] = $chk['row'];
                    }
                }
            }
            if ($tx_errors) {
                $this->session->set_flashdata('error',
                    'Serial number problems:<br>• ' . implode('<br>• ', array_slice($tx_errors, 0, 12)));
                redirect('stock_transfers/create');
            }

            $transfer_data = [
                'from_location_id'  => $from_id,
                'to_location_id'    => $to_id,
                'transfer_type'     => $type,
                'status'            => $status,
                'reason'            => $this->input->post('reason', TRUE),
                'notes'             => $this->input->post('notes', TRUE),
                'requested_by'      => $this->session->userdata('user_id'),
                'requested_by_name' => $this->session->userdata('name'),
            ];

            $tid = $this->Stock_transfer_model->create($transfer_data, $items);
            if ($tid) {
                // Reserve the picked units against this transfer. Status stays
                // IN_STOCK until the transfer completes, so an unapproved
                // transfer never removes anything from sellable stock.
                foreach ($tx_serial_plan as $srow) {
                    $this->Serial_model->link_doc($srow->id, 'stock_transfer', $tid);
                }
                if ($tx_serial_plan) {
                    $this->log_activity('stock_transfers', 'serials_picked',
                        count($tx_serial_plan) . ' serial number(s) picked for this transfer', $tid);
                }
                $this->log_activity('stock_transfers', 'create', "Transfer created: {$status}", $tid);
                $tno = $this->db->select('transfer_no')->where('id', $tid)->get('stock_transfers')->row();
                $this->session->set_flashdata('success', 'Transfer Out ' . ($tno ? $tno->transfer_no : '') . ' created.');
                redirect('stock_transfers/view/' . $tid);
            } else {
                $this->session->set_flashdata('error', 'Failed to create transfer (is the current financial year closed?).');
            }
        }

        $data['locations']       = $locations;
        $data['products']        = $this->Product_model->get_all_active(2000);
        $data['page_title']      = 'Create Stock Transfer';
        $data['active_loc_id']   = $this->active_location_id();
        $data['active_loc_name'] = $this->active_location_name();
        $data['auto_load_sales'] = ($this->company['auto_load_prev_day_sales'] ?? '0') === '1';
        $this->render('stock_transfers/create', $data);
    }

    // AJAX: barcode/item-code scan lookup for the product search box — transfers move
    // whole products (not price points), so unlike Billing/GRN this resolves straight
    // to a single product with no PLU picker involved.
    public function get_by_barcode_ajax()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $barcode = $this->input->post('barcode', TRUE);
        header('Content-Type: application/json');

        $product = $this->Product_model->get_by_barcode($barcode);
        if ($product) {
            echo json_encode(['success' => true, 'product' => [
                'id'        => $product->id,
                'item_code' => $product->item_code,
                'name'      => $product->name,
                'unit'      => $product->unit,
                'stock_qty' => (float)$product->stock_qty,
                // Drives the serial picker on the transfer row; ?? 0 keeps this
                // working on a database without sql/43_serial_warranty.sql.
                'maintain_serial' => (int)($product->maintain_serial ?? 0),
            ]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Barcode not found']);
        }
    }

    public function get_prev_day_sales_ajax()
    {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');

        $location_id = (int)$this->input->get('location_id');
        $req_date    = $this->input->get('requisition_date');

        if (!$location_id || !$req_date) {
            echo json_encode(['success' => false, 'message' => 'Missing location or date.']);
            return;
        }
        if (!$this->_sales_loc_allowed($location_id)) {
            echo json_encode(['success' => false, 'message' => 'Access denied for this location.']);
            return;
        }

        $res   = $this->_prev_day_sales($location_id, $req_date);
        $items = $res['items'];
        usort($items, function ($a, $b) { return strcmp($a['name'], $b['name']); });

        echo json_encode([
            'success'     => true,
            'sales_date'  => $res['sales_date'],
            'items'       => $items,
            'total_items' => count($items),
            'total_units' => $res['total_units'],
        ]);
    }

    // Permission: user must have access to the sales location
    private function _sales_loc_allowed($location_id)
    {
        if ($this->is_owner()) return true;
        $user_loc = $this->active_location_id();
        return !($user_loc && (int)$user_loc !== (int)$location_id);
    }

    /**
     * Previous-day net sales (sold - returned) for a location, grouped by product,
     * with rack and stock. Shared by the "Load Last Day Sales" loader and the
     * printable / Excel report. $from_location_id adds stock at the source.
     */
    private function _prev_day_sales($location_id, $req_date, $from_location_id = 0)
    {
        $sales_date = date('Y-m-d', strtotime($req_date . ' -1 day'));
        $out = ['sales_date' => $sales_date, 'items' => [], 'total_units' => 0];

        // 1. Aggregate sales qty per product for the location on sales_date
        //    Exclude cancelled invoices.
        $sales = $this->db
            ->select('ii.product_id, SUM(ii.qty) AS sold_qty')
            ->from('invoice_items ii')
            ->join('invoices i', 'i.id = ii.invoice_id')
            ->where('i.location_id', $location_id)
            ->where('i.invoice_date', $sales_date)
            ->where('i.status !=', 'cancelled')
            ->group_by('ii.product_id')
            ->get()->result();
        if (empty($sales)) return $out;

        $product_ids = array_column($sales, 'product_id');
        $sales_map   = [];
        foreach ($sales as $s) {
            $sales_map[(int)$s->product_id] = (float)$s->sold_qty;
        }

        // 2. Subtract returns on the same date for the same location
        //    Returns link to invoices via sales_returns.invoice_id; use the
        //    invoice's location_id to scope by store.
        $returns = $this->db
            ->select('sri.product_id, SUM(sri.qty) AS returned_qty')
            ->from('sales_return_items sri')
            ->join('sales_returns sr', 'sr.id = sri.sales_return_id')
            ->join('invoices i', 'i.id = sr.invoice_id', 'left')
            ->where('sr.return_date', $sales_date)
            ->where('sr.status !=', 'cancelled')
            ->group_start()
                ->where('i.location_id', $location_id)
                ->or_where('sr.invoice_id IS NULL', NULL, FALSE)
            ->group_end()
            ->where_in('sri.product_id', $product_ids)
            ->group_by('sri.product_id')
            ->get()->result();

        $return_map = [];
        foreach ($returns as $r) {
            $return_map[(int)$r->product_id] = (float)$r->returned_qty;
        }

        // 3. Build the result — product details + location stock + rack
        $has_rack = $this->db->table_exists('racks') && $this->db->field_exists('rack_id', 'products');
        $this->db
            ->select('p.id, p.item_code, p.name, p.unit, p.mrp, p.status AS product_status,
                      COALESCE(pb.barcode, "") AS ean_code,
                      GREATEST(COALESCE(pls.stock_qty, 0), 0) AS current_stock')
            ->from('products p')
            ->join('product_barcodes pb', 'pb.product_id = p.id AND pb.unit_type = "PCS"', 'left')
            ->join('product_location_stock pls', 'pls.product_id = p.id AND pls.location_id = ' . (int)$location_id, 'left')
            ->where_in('p.id', $product_ids);
        if ($has_rack) {
            $this->db->select('rk.rack_code, rk.rack_name, rk.sort_order AS rack_sort')
                     ->join('racks rk', 'rk.id = p.rack_id', 'left');
        }
        if ($from_location_id) {
            $this->db->select('GREATEST(COALESCE(fls.stock_qty, 0), 0) AS source_stock', FALSE)
                     ->join('product_location_stock fls', 'fls.product_id = p.id AND fls.location_id = ' . (int)$from_location_id, 'left');
        }

        $products = $this->db->get()->result();
        $prod_map = [];
        foreach ($products as $p) {
            $prod_map[(int)$p->id] = $p;
        }

        foreach ($sales_map as $pid => $sold) {
            $returned = $return_map[$pid] ?? 0;
            $net      = $sold - $returned;
            if ($net <= 0) continue;

            $p = $prod_map[$pid] ?? null;
            if (!$p) continue;
            // Skip inactive products
            if (isset($p->product_status) && (int)$p->product_status === 0) continue;

            $out['total_units'] += $net;
            $out['items'][] = [
                'product_id'           => (int)$pid,
                'item_code'            => $p->item_code ?? '',
                'ean_code'             => $p->ean_code ?? '',
                'name'                 => $p->name,
                'unit'                 => $p->unit ?? 'PCS',
                'mrp'                  => (float)($p->mrp ?? 0),
                'current_stock'        => (float)$p->current_stock,
                'source_stock'         => isset($p->source_stock) ? (float)$p->source_stock : null,
                'rack_code'            => (string)($p->rack_code ?? ''),
                'rack_name'            => (string)($p->rack_name ?? ''),
                'rack_sort'            => (int)($p->rack_sort ?? 0),
                'prev_day_sales_qty'   => $net,
                'suggested_qty'        => $net,
            ];
        }
        return $out;
    }

    /**
     * Printable / Excel list of the previous-day sales that "Load Last Day Sales"
     * pulls in — sorted by rack (rack order, then rack code, then item name);
     * items without a rack follow, sorted by item name.
     * GET: location_id (sales/destination), requisition_date, from_location_id, format=print|excel
     */
    public function prev_day_sales_report()
    {
        $location_id = (int)$this->input->get('location_id');
        $from_id     = (int)$this->input->get('from_location_id');
        $req_date    = (string)$this->input->get('requisition_date', TRUE);
        $format      = $this->input->get('format') === 'excel' ? 'excel' : 'print';

        if (!$location_id || !$req_date || !strtotime($req_date)) show_error('Select a destination location and requisition date first.', 400);
        if (!$this->_sales_loc_allowed($location_id) && !($from_id && $this->_sales_loc_allowed($from_id))) show_error('Access denied for this location.', 403);

        $res   = $this->_prev_day_sales($location_id, $req_date, $from_id);
        $items = $res['items'];
        usort($items, function ($a, $b) {
            $ra = $a['rack_code'] !== ''; $rb = $b['rack_code'] !== '';
            if ($ra !== $rb) return $ra ? -1 : 1;            // racked items first
            if ($ra) {
                if ($a['rack_sort'] !== $b['rack_sort']) return $a['rack_sort'] - $b['rack_sort'];
                $c = strnatcasecmp($a['rack_code'], $b['rack_code']);
                if ($c !== 0) return $c;
            }
            return strnatcasecmp($a['name'], $b['name']);
        });

        $to_loc   = $this->Location_model->get_by_id($location_id);
        $from_loc = ($from_id && $from_id !== $location_id) ? $this->Location_model->get_by_id($from_id) : null;
        $data = [
            'items'       => $items,
            'sales_date'  => $res['sales_date'],
            'req_date'    => date('Y-m-d', strtotime($req_date)),
            'total_units' => $res['total_units'],
            'to_name'     => $to_loc ? $to_loc->name : '',
            'from_name'   => $from_loc ? $from_loc->name : '',
            'has_source'  => (bool)$from_loc,
        ];

        if ($format === 'excel') {
            $this->_prev_day_sales_xlsx($data);
            return;
        }
        $this->_print_company($data);
        $this->load->view('stock_transfers/prev_day_sales_print', $data);
    }

    private function _rack_label($it)
    {
        if ($it['rack_code'] === '') return '';
        return $it['rack_code'] . ($it['rack_name'] !== '' && $it['rack_name'] !== $it['rack_code'] ? ' - ' . $it['rack_name'] : '');
    }

    private function _prev_day_sales_xlsx($d)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Last Day Sales');
        $sheet->setCellValue('A1', 'Previous Day Sales - ' . $d['to_name'] . ' - Sales Date ' . date('d-m-Y', strtotime($d['sales_date']))
            . ($d['has_source'] ? '  (Pick from: ' . $d['from_name'] . ')' : ''));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $head = ['#', 'Rack', 'Item Code', 'Barcode', 'Item Name', 'Unit', 'MRP', 'Sold Qty', 'Stock at ' . $d['to_name']];
        if ($d['has_source']) $head[] = 'Stock at ' . $d['from_name'];
        $head[] = 'Qty Sent';
        $sheet->fromArray($head, NULL, 'A3');
        $last = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($head));
        $sheet->getStyle('A3:' . $last . '3')->getFont()->setBold(true);
        $sheet->getStyle('A3:' . $last . '3')->getFill()->setFillType('solid')->getStartColor()->setRGB('E9ECEF');

        $r = 4;
        foreach ($d['items'] as $i => $it) {
            $row = [$i + 1, $this->_rack_label($it), '', '', $it['name'], $it['unit'], $it['mrp'],
                    $it['prev_day_sales_qty'], $it['current_stock']];
            if ($d['has_source']) $row[] = $it['source_stock'];
            $row[] = '';
            $sheet->fromArray($row, NULL, 'A' . $r, true);   // strict: keep 0 stock visible
            // item code / barcode as text so long codes don't turn into 8.9E+12
            $sheet->setCellValueExplicit('C' . $r, (string)$it['item_code'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $r, (string)$it['ean_code'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }
        $sheet->setCellValue('E' . $r, 'Total');
        $sheet->setCellValue('H' . $r, $d['total_units']);
        $sheet->getStyle('A' . $r . ':' . $last . $r)->getFont()->setBold(true);
        for ($c = 1; $c <= count($head); $c++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        $fname = 'Last_Day_Sales_' . preg_replace('/[^A-Za-z0-9]+/', '_', $d['to_name']) . '_' . date('Ymd', strtotime($d['sales_date'])) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fname . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function view($id)
    {
        $transfer = $this->Stock_transfer_model->get_by_id($id);
        if (!$transfer) {
            $this->session->set_flashdata('error', 'Transfer not found.');
            redirect('stock_transfers');
        }
        $data['transfer']   = $transfer;
        $data['items']      = $this->Stock_transfer_model->get_items($id);
        $data['page_title'] = 'Transfer Voucher — ' . $transfer->transfer_no;
        $data['can_edit']    = $this->_can_edit($transfer);
        $this->render('stock_transfers/view', $data);
    }

    private function _can_edit($transfer)
    {
        if (!in_array($transfer->status, ['draft', 'pending'])) return false;
        if ($this->is_owner() || $this->session->userdata('role') === 'admin') return true;
        if (!$this->can_form('frm_stock_transfers_edit')) return false;
        return (int)$transfer->requested_by === (int)$this->session->userdata('user_id');
    }

    public function edit($id)
    {
        $transfer = $this->Stock_transfer_model->get_by_id($id);
        if (!$transfer || !$this->_can_edit($transfer)) {
            $this->session->set_flashdata('error', 'Transfer cannot be edited.');
            redirect('stock_transfers');
        }

        $locations = $this->Location_model->get_all(true);

        if ($this->input->post()) {
            $from_id = (int)$this->input->post('from_location_id');
            $to_id   = (int)$this->input->post('to_location_id');
            $items_raw = $this->input->post('items');

            if ($from_id === $to_id) {
                $this->session->set_flashdata('error', 'From and To locations cannot be the same.');
                redirect('stock_transfers/edit/' . $id);
            }
            // A TRO number belongs to its source location's series — the source
            // can't change once numbered, or TRO-WH-0005 would leave from ANA.
            if (strpos($transfer->transfer_no, 'TRO-') === 0 && $from_id !== (int)$transfer->from_location_id) {
                $this->session->set_flashdata('error', 'From location cannot be changed on ' . $transfer->transfer_no . ' — its number belongs to that location. Create a new transfer instead.');
                redirect('stock_transfers/edit/' . $id);
            }
            if (!$this->_loc_allowed($from_id, $to_id)) {
                $this->session->set_flashdata('error', 'You can only raise transfers into or out of your own location.');
                redirect('stock_transfers/edit/' . $id);
            }

            $from_loc = $this->Location_model->get_by_id($from_id);
            $to_loc   = $this->Location_model->get_by_id($to_id);
            $type = 'warehouse_to_outlet';
            if ($from_loc->type === 'outlet' && $to_loc->type === 'outlet') {
                $type = 'outlet_to_outlet';
            } elseif ($from_loc->type === 'outlet' && $to_loc->type === 'warehouse') {
                $type = 'outlet_to_warehouse';
            }

            $save_action = $this->input->post('save_action') ?: 'draft';
            $status = ($save_action === 'pending') ? 'pending' : 'draft';

            $items = [];
            if (!empty($items_raw) && is_array($items_raw)) {
                foreach ($items_raw as $item) {
                    if (empty($item['product_id']) || empty($item['requested_qty'])) continue;
                    $prod = $this->Product_model->get_by_id($item['product_id']);
                    $row_data = [
                        'product_id'    => (int)$item['product_id'],
                        'product_name'  => $prod ? $prod->name : '',
                        'hsn_code'      => $prod ? $prod->hsn_code : '',
                        'unit'          => $prod ? $prod->unit : 'PCS',
                        'requested_qty' => (float)$item['requested_qty'],
                        'rate'          => $prod ? (float)$prod->cost_price : 0,
                    ];
                    if ($this->db->field_exists('remarks', 'stock_transfer_items')) {
                        $row_data['remarks'] = trim($item['remarks'] ?? '');
                    }
                    if ($this->db->field_exists('prev_day_sales_qty', 'stock_transfer_items')) {
                        $row_data['prev_day_sales_qty'] = ($item['prev_day_sales_qty'] ?? '') !== '' ? (float)$item['prev_day_sales_qty'] : null;
                        $row_data['suggested_qty']      = ($item['suggested_qty'] ?? '') !== ''      ? (float)$item['suggested_qty']      : null;
                        $row_data['suggestion_source']  = !empty($item['suggestion_source'])          ? $item['suggestion_source']          : null;
                    }
                    $items[] = $row_data;
                }
            }

            if (empty($items)) {
                $this->session->set_flashdata('error', 'Please add at least one item.');
                redirect('stock_transfers/edit/' . $id);
            }

            $this->db->where('id', $id)->update('stock_transfers', [
                'from_location_id'  => $from_id,
                'to_location_id'    => $to_id,
                'transfer_type'     => $type,
                'status'            => $status,
                'reason'            => $this->input->post('reason', TRUE),
                'notes'             => $this->input->post('notes', TRUE),
                'requested_by'      => $this->session->userdata('user_id'),
                'requested_by_name' => $this->session->userdata('name'),
                'requested_at'      => date('Y-m-d H:i:s'),
            ]);
            $this->Stock_transfer_model->update_items($id, $items);

            $this->log_activity('stock_transfers', 'edit', "Transfer edited: {$transfer->transfer_no}", $id);
            $this->session->set_flashdata('success', 'Transfer updated successfully.');
            redirect('stock_transfers/view/' . $id);
        }

        $data['transfer']        = $transfer;
        $data['transfer_items']  = $this->Stock_transfer_model->get_items($id);
        $data['locations']       = $locations;
        $data['products']        = $this->Product_model->get_all_active(2000);
        $data['page_title']      = 'Edit Stock Transfer — ' . $transfer->transfer_no;
        $data['active_loc_id']   = $this->active_location_id();
        $data['active_loc_name'] = $this->active_location_name();
        $data['auto_load_sales'] = !empty($this->company['auto_load_prev_day_sales']);
        $this->render('stock_transfers/edit', $data);
    }

    public function print_transfer($id)
    {
        $transfer = $this->Stock_transfer_model->get_by_id($id);
        if (!$transfer) {
            show_404();
        }
        $data['transfer'] = $transfer;
        $data['items']    = $this->Stock_transfer_model->get_items($id);
        $data['doc_mode'] = 'out';
        $this->_print_company($data);
        $this->load->view('stock_transfers/print', $data);
    }

    private function _print_company(&$data)
    {
        $this->load->model('Invoice_model');
        $company = $this->Invoice_model->get_company_settings();
        $data['store_name']    = $company['company_name'] ?? 'My Store';
        $data['store_address'] = $company['address'] ?? '';
        $data['store_phone']   = $company['phone'] ?? '';
        $data['store_gstin']   = $company['gstin'] ?? '';
        $data['store_logo']    = $company['logo'] ?? '';
    }

    // Non-owners may only raise/edit transfers where their own location is one side.
    private function _loc_allowed($from_id, $to_id)
    {
        if ($this->is_owner() || in_array($this->session->userdata('role'), ['admin', 'superadmin'], true)) return true;
        $loc = (int)$this->active_location_id();
        return $loc && ($loc === (int)$from_id || $loc === (int)$to_id);
    }

    // Approve = confirm the Transfer Out: stock leaves the source and the linked
    // Transfer In is created for the destination, atomically (see model dispatch()).
    public function approve($id)
    {
        $this->require_admin();
        $transfer = $this->Stock_transfer_model->get_by_id($id);
        if (!$transfer || $transfer->status !== 'pending') {
            $this->session->set_flashdata('error', 'Transfer cannot be approved.');
            redirect('stock_transfers');
        }
        // Parse per-item approved_qty from POST
        $approved_items = [];
        $raw = $this->input->post('approved_qty');
        if (is_array($raw)) {
            foreach ($raw as $item_id => $qty) {
                $approved_items[(int)$item_id] = (float)$qty;
            }
        }
        $user_id   = $this->session->userdata('user_id');
        $user_name = $this->session->userdata('name');

        $res = $this->Stock_transfer_model->dispatch($id, $user_id, $user_name, $approved_items, $this->Location_model);
        if ($res['ok']) {
            $this->log_activity('stock_transfers', 'dispatch',
                "Transfer Out {$transfer->transfer_no} approved & dispatched — Transfer In {$res['tri_no']} created", $id, $transfer->transfer_no);
            $this->session->set_flashdata('success',
                "Transfer Out {$transfer->transfer_no} approved — stock sent from {$transfer->from_location_name}. "
                . "Transfer In <strong>{$res['tri_no']}</strong> created for {$transfer->to_location_name} (In Transit until received).");
        } else {
            $this->session->set_flashdata('error', 'Approval failed: ' . $res['error']);
        }
        redirect('stock_transfers/view/' . $id);
    }

    // ─── Transfer In (TRI) ─────────────────────────────────────

    public function transfer_in()
    {
        $filters = [
            'status'    => $this->input->get('status'),
            'date_from' => $this->input->get('date_from'),
            'date_to'   => $this->input->get('date_to'),
            'q'         => trim((string)$this->input->get('q', TRUE)),
        ];
        // Destination users only see Transfer Ins for their own location
        if (!$this->is_owner()) {
            $filters['to_location_id'] = $this->active_location_id() ?: -1;
        } else {
            $filters['from_location_id'] = $this->input->get('from_location_id') ?: null;
            $filters['to_location_id']   = $this->input->get('to_location_id') ?: null;
        }
        $data['transfer_ins'] = $this->Stock_transfer_model->get_all_transfer_ins($filters);
        $data['locations']    = $this->Location_model->get_all(true);
        $data['filters']      = $filters;
        $data['page_title']   = 'Transfer In';
        $this->render('stock_transfers/transfer_in_index', $data);
    }

    private function _can_see_tri($tri)
    {
        if ($this->is_owner() || in_array($this->session->userdata('role'), ['admin', 'superadmin'], true)) return true;
        $loc = (int)$this->active_location_id();
        return $loc && ($loc === (int)$tri->to_location_id || $loc === (int)$tri->from_location_id);
    }

    private function _can_receive($tri)
    {
        if ($tri->status !== 'in_transit') return false;
        if ($this->is_owner() || in_array($this->session->userdata('role'), ['admin', 'superadmin'], true)) return true;
        return (int)$this->active_location_id() === (int)$tri->to_location_id;
    }

    public function transfer_in_view($tri_id)
    {
        $tri = $this->Stock_transfer_model->get_transfer_in($tri_id);
        if (!$tri || !$this->_can_see_tri($tri)) {
            $this->session->set_flashdata('error', 'Transfer In not found.');
            redirect('stock_transfers/transfer_in');
        }
        $data['tri']         = $tri;
        $data['items']       = $this->Stock_transfer_model->get_items($tri->transfer_id);
        $data['can_receive'] = $this->_can_receive($tri);
        $data['page_title']  = 'Transfer In — ' . $tri->tri_no;
        $this->render('stock_transfers/transfer_in_view', $data);
    }

    public function receive($tri_id)
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('stock_transfers/transfer_in_view/' . (int)$tri_id);
        $tri = $this->Stock_transfer_model->get_transfer_in($tri_id);
        if (!$tri || !$this->_can_receive($tri)) {
            $this->session->set_flashdata('error', 'You cannot receive this Transfer In (already received, or not your location).');
            redirect('stock_transfers/transfer_in_view/' . (int)$tri_id);
        }
        $res = $this->Stock_transfer_model->receive($tri_id,
            $this->session->userdata('user_id'), $this->session->userdata('name'), $this->Location_model);
        if ($res['ok']) {
            $this->log_activity('stock_transfers', 'receive',
                "Transfer In {$tri->tri_no} received at {$tri->to_location_name} (Transfer Out {$tri->transfer_no})", $tri->transfer_id, $tri->tri_no);
            $this->session->set_flashdata('success', "Transfer In {$tri->tri_no} received — stock added to {$tri->to_location_name}.");
        } else {
            $this->session->set_flashdata('error', 'Receive failed: ' . $res['error']);
        }
        redirect('stock_transfers/transfer_in_view/' . (int)$tri_id);
    }

    public function print_transfer_in($tri_id)
    {
        $tri = $this->Stock_transfer_model->get_transfer_in($tri_id);
        if (!$tri || !$this->_can_see_tri($tri)) show_404();
        $transfer = $this->Stock_transfer_model->get_by_id($tri->transfer_id);
        if (!$transfer) show_404();
        $data['transfer'] = $transfer;
        $data['items']    = $this->Stock_transfer_model->get_items($tri->transfer_id);
        $data['doc_mode'] = 'in';
        $this->_print_company($data);
        $this->load->view('stock_transfers/print', $data);
    }

    public function reject($id)
    {
        $this->require_admin();
        $transfer = $this->Stock_transfer_model->get_by_id($id);
        if (!$transfer || $transfer->status !== 'pending') {
            $this->session->set_flashdata('error', 'Transfer cannot be rejected.');
            redirect('stock_transfers');
        }
        $reason = $this->input->post('rejection_reason', TRUE) ?: 'No reason given';
        $ok = $this->Stock_transfer_model->reject(
            $id,
            $this->session->userdata('user_id'),
            $this->session->userdata('name'),
            $reason
        );
        if ($ok) {
            $this->log_activity('stock_transfers', 'reject', "Transfer rejected: {$transfer->transfer_no}", $id);
            $this->session->set_flashdata('success', 'Transfer rejected.');
        }
        redirect('stock_transfers/view/' . $id);
    }

    public function complete($id)
    {
        $this->require_admin();
        $ok = $this->Stock_transfer_model->complete(
            $id,
            $this->session->userdata('user_id'),
            $this->session->userdata('name'),
            $this->Location_model
        );
        if ($ok) {
            $this->log_activity('stock_transfers', 'complete', "Transfer completed: #{$id}", $id);
            $this->session->set_flashdata('success', 'Transfer completed and stock updated.');
        } else {
            $this->session->set_flashdata('error', 'Could not complete transfer. Check status.');
        }
        redirect('stock_transfers/view/' . $id);
    }

    public function delete($id)
    {
        $this->require_admin();
        $this->Stock_transfer_model->delete_draft($id);
        $this->session->set_flashdata('success', 'Draft transfer deleted.');
        redirect('stock_transfers');
    }

    // AJAX: get product stock for a specific location
    public function get_product_stock_ajax()
    {
        $product_id  = (int)$this->input->get('product_id');
        $location_id = (int)$this->input->get('location_id');
        $qty = $this->Location_model->get_stock($product_id, $location_id);
        // display stock (never below 0); actual kept for admin/audit use
        echo json_encode(['stock_qty' => max(0, $qty), 'display_stock' => max(0, $qty), 'actual_stock' => $qty]);
    }

    // AJAX: search accepted GRNs received at a location — feeds "Load from GRN" on Transfer OUT
    public function search_grn_ajax()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('Grn_model');
        $q           = $this->input->get('q', TRUE);
        $location_id = (int)$this->input->get('location_id');
        $rows = $this->Grn_model->search_accepted_by_location($location_id, $q);
        echo json_encode($rows);
    }

    // AJAX: fetch a GRN's items so they can be loaded straight into a transfer
    public function get_grn_items_ajax($grn_id = null)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('Grn_model');
        header('Content-Type: application/json');

        $grn = $this->Grn_model->get_by_id($grn_id);
        if (!$grn || $grn->status !== 'accepted') {
            echo json_encode(['success' => false, 'message' => 'GRN not found or not accepted.']);
            return;
        }

        $items = $this->Grn_model->get_items($grn_id);
        $out = [];
        foreach ($items as $it) {
            $qty = $it->accepted_qty > 0 ? $it->accepted_qty : $it->received_qty;
            if ($qty <= 0) continue;
            $out[] = [
                'product_id' => (int)$it->product_id,
                'name'       => $it->product_name,
                'item_code'  => $it->item_code,
                'unit'       => $it->unit ?: 'PCS',
                'qty'        => (float)$qty,
            ];
        }

        echo json_encode([
            'success' => true,
            'grn'     => [
                'grn_number'    => $grn->grn_number,
                'supplier_name' => $grn->supplier_name,
                'received_date' => $grn->received_date,
            ],
            'items'   => $out,
        ]);
    }

    public function migrate_item_remarks() {
        if (!$this->db->field_exists('remarks', 'stock_transfer_items')) {
            $this->db->query("ALTER TABLE stock_transfer_items ADD COLUMN remarks VARCHAR(500) DEFAULT '' AFTER rate");
            echo 'Column remarks added to stock_transfer_items.';
        } else {
            echo 'Column already exists.';
        }
    }
}
