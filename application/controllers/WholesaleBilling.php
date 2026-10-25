<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class WholesaleBilling extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Invoice_model', 'Customer_model', 'Product_model']);
        $this->load->library(['DocumentNumbering', 'AdaequareGsp', 'GstzenGsp', 'GstAddress']);
        $this->_ensure_ship_columns();
    }

    // Self-heal the per-invoice delivery (ship-to) address columns (sql/63)
    private function _ensure_ship_columns() {
        if ($this->db->field_exists('ship_to_different', 'invoices')) return;
        $this->db->query("ALTER TABLE invoices
            ADD COLUMN ship_to_different TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN ship_name VARCHAR(100) NULL,
            ADD COLUMN ship_gstin VARCHAR(15) NULL,
            ADD COLUMN ship_address VARCHAR(200) NULL,
            ADD COLUMN ship_place VARCHAR(100) NULL,
            ADD COLUMN ship_pincode VARCHAR(6) NULL,
            ADD COLUMN ship_state_code CHAR(2) NULL");
    }

    // Posted ship-to fields (create form / edit modal), normalised
    private function _posted_ship() {
        $on = (int)$this->input->post('ship_to_different') === 1;
        return [
            'ship_to_different' => $on ? 1 : 0,
            'ship_name'       => $on ? mb_substr(trim((string)$this->input->post('ship_name', TRUE)), 0, 100) : null,
            'ship_gstin'      => $on ? (strtoupper(trim((string)$this->input->post('ship_gstin', TRUE))) ?: null) : null,
            'ship_address'    => $on ? mb_substr(trim((string)$this->input->post('ship_address', TRUE)), 0, 200) : null,
            'ship_place'      => $on ? mb_substr(trim((string)$this->input->post('ship_place', TRUE)), 0, 100) : null,
            'ship_pincode'    => $on ? trim((string)$this->input->post('ship_pincode', TRUE)) : null,
            'ship_state_code' => $on ? str_pad(preg_replace('/[^0-9]/', '', (string)$this->input->post('ship_state_code')), 2, '0', STR_PAD_LEFT) : null,
        ];
    }

    // Address / PIN problems that block E-Invoice and E-Way Bill for a saved invoice
    private function _address_errors($invoice, $customer, $company) {
        $r = GstAddress::resolve($invoice, $customer, $company, $this->db);
        return $r['errors'];
    }

    // Wholesale numbers use the wholesale_invoice_prefix (default WS) so they can
    // never equal a retail number — both series share the {Loc}/{FY}/{Serial}
    // shape, and the retail one would otherwise collide on invoices.invoice_no.
    // Kept within NIC's DocDtls.No rule: max 16 chars, first char A-Z or 1-9.
    // e.g. WSWH/26-27/00002
    private function _wholesale_no($doc, $company) {
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $company['wholesale_invoice_prefix'] ?? 'WS')) ?: 'WS';
        if (!preg_match('/^[A-Z1-9]/', $prefix)) $prefix = 'WS' . $prefix;
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $doc['location_code'] ?? ''));
        $fy   = $doc['financial_year'];
        $serial = (string)$doc['serial'];
        $room = 16 - strlen($fy) - 2 - max(3, strlen($serial));      // chars left for prefix+code
        $head = substr($prefix . $code, 0, max(1, $room));
        $width = max(strlen($serial), 16 - strlen($head) - strlen($fy) - 2);
        return $head . '/' . $fy . '/' . str_pad($serial, $width, '0', STR_PAD_LEFT);
    }

    // Which e-invoice / e-way bill API is active: Settings -> einvoice_provider
    private function _provider() {
        $row = $this->db->where('key_name', 'einvoice_provider')->get('company_settings')->row();
        return ($row && $row->key_value === 'gstzen') ? 'gstzen' : 'adaequare';
    }

    // ─── Listing ───────────────────────────────────────────────────────────

    public function index() {
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to', TRUE)   ?: date('Y-m-d');

        $invoices = $this->db->select('invoices.*, customers.name AS customer_name, customers.gst_number')
            ->from('invoices')
            ->join('customers', 'customers.id = invoices.customer_id')
            ->where('invoices.invoice_type', 'wholesale')
            ->where('invoices.invoice_date >=', $from)
            ->where('invoices.invoice_date <=', $to)
            ->order_by('invoices.id', 'DESC')
            ->get()->result();

        $data['page_title'] = 'Wholesale Invoices';
        $data['invoices']   = $invoices;
        $data['from']       = $from;
        $data['to']         = $to;
        $data['company']    = $this->Invoice_model->get_company_settings();
        $this->render('wholesale_billing/index', $data);
    }

    // ─── Create ────────────────────────────────────────────────────────────

    public function create() {
        $data['page_title'] = 'Create Wholesale Invoice';
        $data['company']    = $this->Invoice_model->get_company_settings();
        $data['customers']  = $this->db->where('type', 'wholesale')->where('status', 1)->order_by('name')->get('customers')->result();
        $data['products']   = $this->Product_model->get_all($this->session->userdata('active_location_id'));
        $data['locations']  = $this->db->get('locations')->result();
        $data['states']     = GstAddress::STATES;
        $this->render('wholesale_billing/create', $data);
    }

    public function create_ajax() {
        $customer_id = (int)$this->input->post('customer_id');
        $customer = $this->Customer_model->get_by_id($customer_id);
        if (!$customer || empty($customer->gst_number)) {
            echo json_encode(['success' => false, 'message' => 'Wholesale invoice requires a customer with GSTIN']);
            return;
        }

        $company = $this->Invoice_model->get_company_settings();
        $invoice_date = $this->input->post('invoice_date', TRUE) ?: date('Y-m-d');
        $location_id = (int)($this->input->post('location_id', TRUE) ?: $this->session->userdata('active_location_id') ?: 1);

        // Addresses + PIN codes are compulsory for E-Invoice / E-Way Bill, so they
        // are checked before the invoice (and its number) is created.
        $ship = $this->_posted_ship();
        $addr = GstAddress::resolve(array_merge(['location_id' => $location_id], $ship), $customer, $company, $this->db);
        if (!empty($addr['errors'])) {
            echo json_encode(['success' => false, 'message' => "Please fix before saving:\n- " . implode("\n- ", $addr['errors'])]);
            return;
        }

        $doc = $this->documentnumbering->generate('wholesale_invoice', $location_id, $invoice_date);
        $doc['number'] = $this->_wholesale_no($doc, $company);

        // IGST vs CGST+SGST: seller GSTIN state vs place of supply (ship-to
        // state when goods go to a different address, else buyer GSTIN state)
        $is_interstate = ($addr['pos'] !== $addr['seller_state']) ? 1 : 0;

        $product_ids = $this->input->post('product_id');
        $qtys        = $this->input->post('qty');
        $rates       = $this->input->post('rate');
        $discounts   = $this->input->post('item_discount');
        $gst_pcts    = $this->input->post('gst_percent');
        $hsn_codes   = $this->input->post('hsn_code');

        if (empty($product_ids) || !is_array($product_ids)) {
            echo json_encode(['success' => false, 'message' => 'No items added']);
            return;
        }

        $items = [];
        $subtotal = $taxable = $cgst = $sgst = $igst = $total_gst = 0;

        for ($i = 0; $i < count($product_ids); $i++) {
            $pid = (int)$product_ids[$i];
            if (!$pid) continue;

            $product = $this->Product_model->get_by_id($pid);
            if (!$product) continue;

            $qty       = (float)($qtys[$i] ?? 1);
            $rate      = (float)($rates[$i] ?? $product->selling_price);
            $disc      = (float)($discounts[$i] ?? 0);
            $hsn       = $hsn_codes[$i] ?? $product->hsn_code ?? '';

            $this->load->library('Gst_tax_engine');
            $tax_calc = $this->gst_tax_engine->calculate_sales_line([
                'product'          => $product,
                'unit_price'       => $rate,
                'qty'              => $qty,
                'discount'         => $disc,
                'is_tax_inclusive' => false, // Wholesale billing is tax-exclusive
                'is_interstate'    => $is_interstate,
                'transaction_date' => $invoice_date,
            ]);

            $line_total   = round($qty * $rate, 2);
            $line_disc    = round($disc, 2);
            $line_taxable = $tax_calc['taxable_amount'];
            $line_gst_amt = $tax_calc['gst_amount'];
            $line_cgst    = $tax_calc['cgst_amount'];
            $line_sgst    = $tax_calc['sgst_amount'];
            $line_igst    = $tax_calc['igst_amount'];
            $gst_pct      = $tax_calc['gst_percent'];

            $items[] = [
                'product_id'       => $pid,
                'product_name'     => $product->name,
                'hsn_code'         => $hsn,
                'qty'              => $qty,
                'unit'             => $product->unit ?: 'PCS',
                'rate'             => $rate,
                'mrp'              => $product->mrp,
                'discount'         => $line_disc,
                'taxable_amt'      => $line_taxable,
                'gst_percent'      => $gst_pct,
                'gst_amount'       => $line_gst_amt,
                'cgst_percent'     => $tax_calc['cgst_percent'],
                'sgst_percent'     => $tax_calc['sgst_percent'],
                'igst_percent'     => $tax_calc['igst_percent'],
                'cgst_amount'      => $line_cgst,
                'sgst_amount'      => $line_sgst,
                'igst_amount'      => $line_igst,
                'total'            => $tax_calc['total_amount'],
                'invoice_no'       => $doc['number'],
                'tax_inclusive'    => 0,
                'tax_rule_applied' => $tax_calc['tax_rule_applied'],
            ];

            $subtotal  += $line_total;
            $taxable   += $line_taxable;
            $cgst      += $line_cgst;
            $sgst      += $line_sgst;
            $igst      += $line_igst;
            $total_gst += $line_gst_amt;
        }

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No valid items']);
            return;
        }

        $total_before_round = $taxable + $total_gst;
        $round_off = round($total_before_round) - $total_before_round;
        $total_amount = round($total_before_round + $round_off, 2);
        $payment_mode = $this->input->post('payment_mode', TRUE) ?: 'credit';
        $paid_amount  = ($payment_mode === 'credit') ? 0 : $total_amount;

        $invoice_data = [
            'invoice_no'      => $doc['number'],
            'financial_year'  => $doc['financial_year'],
            'customer_id'     => $customer_id,
            'invoice_type'    => 'wholesale',
            'invoice_date'    => $invoice_date,
            'subtotal'        => round($subtotal, 2),
            'discount'        => 0,
            'taxable_amount'  => round($taxable, 2),
            'cgst_amount'     => round($cgst, 2),
            'sgst_amount'     => round($sgst, 2),
            'igst_amount'     => round($igst, 2),
            'total_gst'       => round($total_gst, 2),
            'round_off'       => round($round_off, 2),
            'total_amount'    => $total_amount,
            'paid_amount'     => $paid_amount,
            'due_amount'      => round($total_amount - $paid_amount, 2),
            'payment_mode'    => $payment_mode,
            'is_interstate'   => $is_interstate,
            'notes'           => $this->input->post('notes', TRUE) ?: '',
            'status'          => ($paid_amount >= $total_amount) ? 'paid' : 'unpaid',
            'created_by'      => $this->session->userdata('user_id'),
            'location_id'     => $location_id,
            'vehicle_no'         => $this->input->post('vehicle_no', TRUE) ?: null,
            'vehicle_type'       => $this->input->post('vehicle_type', TRUE) ?: 'R',
            'transport_mode'     => $this->input->post('transport_mode', TRUE) ?: 1,
            'transporter_id'     => $this->input->post('transporter_id', TRUE) ?: null,
            'transporter_name'   => $this->input->post('transporter_name', TRUE) ?: null,
            'transport_doc_no'   => $this->input->post('transport_doc_no', TRUE) ?: null,
            'transport_doc_date' => $this->input->post('transport_doc_date', TRUE) ?: null,
            'transport_distance' => $this->input->post('transport_distance', TRUE) ?: null,
        ] + $ship;

        $invoice_id = $this->Invoice_model->create($invoice_data, $items);

        if ($invoice_id) {
            $this->_deduct_stock($items, $location_id);

            if ($payment_mode === 'credit' && $customer->type === 'wholesale') {
                $this->db->set('balance', 'balance + ' . $total_amount, FALSE)
                    ->where('id', $customer_id)->update('customers');
            }

            $this->log_activity('wholesale_billing', 'create', 'Created wholesale invoice ' . $doc['number'], $invoice_id, $doc['number']);

            echo json_encode([
                'success'    => true,
                'invoice_id' => $invoice_id,
                'invoice_no' => $doc['number'],
                'message'    => 'Wholesale invoice ' . $doc['number'] . ' created successfully',
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create invoice']);
        }
    }

    private function _deduct_stock($items, $location_id) {
        foreach ($items as $item) {
            $this->Product_model->decrease_stock($item['product_id'], $item['qty']);
            if ($this->db->table_exists('product_location_stock')) {
                $this->db->set('stock_qty', 'stock_qty - ' . (float)$item['qty'], FALSE)
                    ->where('product_id', $item['product_id'])
                    ->where('location_id', $location_id)
                    ->update('product_location_stock');
            }
        }
    }

    // ─── View ──────────────────────────────────────────────────────────────

    public function view($id = null) {
        if (!$id) redirect('wholesale_billing');
        $invoice = $this->Invoice_model->get_by_id($id);
        if (!$invoice || $invoice->invoice_type !== 'wholesale') {
            $this->session->set_flashdata('error', 'Invoice not found.');
            redirect('wholesale_billing');
        }

        $data['page_title'] = 'Wholesale Invoice #' . $invoice->invoice_no;
        $data['invoice']    = $invoice;
        $data['items']      = $this->Invoice_model->get_items($id);
        $data['customer']   = $this->Customer_model->get_by_id($invoice->customer_id);
        $data['company']    = $this->Invoice_model->get_company_settings();
        $data['addr']       = GstAddress::resolve($invoice, $data['customer'], $data['company'], $this->db);
        $data['states']     = GstAddress::STATES;
        $this->render('wholesale_billing/view', $data);
    }

    // Transport details posted from the E-Way Bill popup -> ['data' => [...], 'errors' => [...]]
    private function _posted_transport() {
        $mode   = (int)$this->input->post('transport_mode');
        $veh    = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$this->input->post('vehicle_no', TRUE)));
        $vtype  = $this->input->post('vehicle_type') === 'O' ? 'O' : 'R';
        $tid    = strtoupper(trim((string)$this->input->post('transporter_id', TRUE)));
        $tname  = trim((string)$this->input->post('transporter_name', TRUE));
        $dist   = trim((string)$this->input->post('transport_distance', TRUE));
        $docno  = trim((string)$this->input->post('transport_doc_no', TRUE));
        $docdt  = trim((string)$this->input->post('transport_doc_date', TRUE));
        $e = [];
        if (!in_array($mode, [1, 2, 3, 4], true)) $e[] = 'Select a transport mode.';
        if ($mode === 1 && $veh === '' && $tid === '') $e[] = 'Road transport needs a Vehicle No (or a Transporter ID if the transporter will add the vehicle later).';
        if ($veh !== '' && !preg_match('/^[A-Z]{2}[0-9A-Z]{5,13}$/', $veh)) $e[] = "Vehicle No '{$veh}' looks wrong (e.g. HR26AB1234).";
        if ($mode > 1 && ($docno === '' || $docdt === '')) $e[] = 'Rail / Air / Ship needs the Transport Doc No and Date (RR / Airway bill / Bill of lading).';
        if ($tid !== '' && !preg_match('/^[0-9A-Z]{15}$/', $tid)) $e[] = 'Transporter ID must be the 15-character transporter GSTIN / enrolment ID.';
        if ($tname !== '' && strlen($tname) < 3) $e[] = 'Transporter Name must be at least 3 characters.';
        if ($dist !== '' && (!ctype_digit($dist) || (int)$dist > 4000)) $e[] = 'Distance must be a whole number of KM from 0 to 4000 (0 = let the portal calculate it from the PIN codes).';
        if ($docdt !== '' && strtotime($docdt) === false) $e[] = 'Transport Doc Date is not a valid date.';
        return ['errors' => $e, 'data' => [
            'transport_mode'     => $mode ?: 1,
            'vehicle_no'         => $veh ?: null,
            'vehicle_type'       => $vtype,
            'transporter_id'     => $tid ?: null,
            'transporter_name'   => $tname ?: null,
            'transport_distance' => $dist === '' ? 0 : (int)$dist,
            'transport_doc_no'   => $docno ?: null,
            'transport_doc_date' => $docdt ? date('Y-m-d', strtotime($docdt)) : null,
        ]];
    }

    // Change the delivery (ship-to) address — only while no IRN / E-Way Bill
    // exists, since both are issued against the address they were created with.
    public function update_ship($id = null) {
        $invoice = $this->Invoice_model->get_by_id((int)$id);
        if (!$invoice || $invoice->invoice_type !== 'wholesale' || $this->input->method() !== 'post') redirect('wholesale_billing');
        if ((!empty($invoice->einvoice_irn) && ($invoice->einvoice_status ?? '') !== 'cancelled')
            || (!empty($invoice->ewb_no) && ($invoice->ewb_status ?? '') !== 'cancelled')) {
            $this->session->set_flashdata('error', 'Delivery address cannot be changed after the E-Invoice / E-Way Bill is generated.');
            redirect('wholesale_billing/view/' . $invoice->id);
        }
        $ship = $this->_posted_ship();
        $customer = $this->Customer_model->get_by_id($invoice->customer_id);
        $company  = $this->Invoice_model->get_company_settings();
        $addr = GstAddress::resolve(array_merge(['location_id' => $invoice->location_id], $ship), $customer, $company, $this->db);
        $ship_errs = array_values(array_filter($addr['errors'], function ($m) { return stripos($m, 'ship-to') !== false; }));
        if ($ship_errs) {
            $this->session->set_flashdata('error', 'Delivery address not saved:<br>- ' . implode('<br>- ', array_map('htmlspecialchars', $ship_errs)));
            redirect('wholesale_billing/view/' . $invoice->id);
        }
        // Place of supply decides IGST vs CGST+SGST — refuse a change that would flip it
        $new_inter = ($addr['pos'] !== $addr['seller_state']) ? 1 : 0;
        if ($new_inter !== (int)$invoice->is_interstate) {
            $this->session->set_flashdata('error', 'This delivery address changes the place of supply ('
                . ($new_inter ? 'CGST+SGST → IGST' : 'IGST → CGST+SGST') . '). The tax on the invoice would be wrong — create a new invoice instead.');
            redirect('wholesale_billing/view/' . $invoice->id);
        }
        $this->db->where('id', $invoice->id)->update('invoices', $ship);
        $this->log_activity('wholesale_billing', 'update_ship', 'Delivery address updated on ' . $invoice->invoice_no, $invoice->id, $invoice->invoice_no);
        $this->session->set_flashdata('success', $ship['ship_to_different'] ? 'Delivery address saved.' : 'Delivery address removed — goods ship to the billing address.');
        redirect('wholesale_billing/view/' . $invoice->id);
    }

    // ─── Print ─────────────────────────────────────────────────────────────

    public function print_invoice($id = null) {
        if (!$id) redirect('wholesale_billing');
        $invoice = $this->Invoice_model->get_by_id($id);
        if (!$invoice || $invoice->invoice_type !== 'wholesale') {
            $this->session->set_flashdata('error', 'Invoice not found.');
            redirect('wholesale_billing');
        }
        $data['invoice']  = $invoice;
        $data['items']    = $this->Invoice_model->get_items($id);
        $data['customer'] = $this->Customer_model->get_by_id($invoice->customer_id);
        $data['company']  = $this->Invoice_model->get_company_settings();
        $this->load->view('wholesale_billing/print', $data);
    }

    // ─── E-Way Bill Generation ─────────────────────────────────────────────

    public function generate_ewb_ajax() {
        $invoice_id = (int)$this->input->post('invoice_id');
        $invoice = $this->Invoice_model->get_by_id($invoice_id);
        if (!$invoice || $invoice->invoice_type !== 'wholesale') {
            echo json_encode(['success' => false, 'message' => 'Invalid invoice']);
            return;
        }

        $customer = $this->Customer_model->get_by_id($invoice->customer_id);
        $items = $this->Invoice_model->get_items($invoice_id);
        $company = $this->Invoice_model->get_company_settings();

        // Transport details come from the E-Way Bill popup; save them first
        if ($this->input->post('transport_mode') !== null) {
            $t = $this->_posted_transport();
            if ($t['errors']) {
                echo json_encode(['success' => false, 'message' => "Please fix:\n- " . implode("\n- ", $t['errors'])]);
                return;
            }
            $this->db->where('id', $invoice_id)->update('invoices', $t['data']);
            $invoice = $this->Invoice_model->get_by_id($invoice_id);
        } elseif (empty($invoice->vehicle_no) && empty($invoice->transporter_id)) {
            echo json_encode(['success' => false, 'message' => 'Vehicle number or transporter ID is required for E-Way Bill']);
            return;
        }
        if ($errs = $this->_address_errors($invoice, $customer, $company)) {
            echo json_encode(['success' => false, 'message' => "Address problems:\n- " . implode("\n- ", $errs)]);
            return;
        }
        if ($this->_provider() === 'gstzen') { $this->_gz_generate_ewb($invoice, $items, $customer, $company); return; }

        try {
            $payload = $this->adaequaregsp->build_ewb_payload($invoice, $items, $customer, $company);
            $result = $this->adaequaregsp->ewb_generate($payload);
            $this->adaequaregsp->update_last_log_invoice($invoice_id);

            $body = isset($result['body']['result']) && is_array($result['body']['result']) ? $result['body']['result'] : $result['body'];
            if (!empty($body['ewayBillNo'])) {
                $ewb_no = $body['ewayBillNo'];
                $ewb_date = $body['ewayBillDate'] ?? null;
                $valid_until = $body['validUpto'] ?? null;

                $update = [
                    'ewb_no'     => $ewb_no,
                    'ewb_status' => 'generated',
                ];
                if ($ewb_date) $update['ewb_date'] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $ewb_date)));
                if ($valid_until) $update['ewb_valid_until'] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $valid_until)));

                $this->db->where('id', $invoice_id)->update('invoices', $update);
                $this->log_activity('ewb', 'generate', "E-Way Bill {$ewb_no} generated for {$invoice->invoice_no}", $invoice_id, $invoice->invoice_no);

                echo json_encode(['success' => true, 'ewb_no' => $ewb_no, 'message' => "E-Way Bill #{$ewb_no} generated"]);
            } else {
                $err = $result['body']['errorMessage'] ?? $result['body']['error'] ?? json_encode($result['body']);
                echo json_encode(['success' => false, 'message' => 'EWB API error: ' . $err, 'api_response' => $result['body']]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ─── E-Invoice (IRN) Generation ────────────────────────────────────────

    public function generate_irn_ajax() {
        $invoice_id = (int)$this->input->post('invoice_id');
        $invoice = $this->Invoice_model->get_by_id($invoice_id);
        if (!$invoice || $invoice->invoice_type !== 'wholesale') {
            echo json_encode(['success' => false, 'message' => 'Invalid invoice']);
            return;
        }

        $customer = $this->Customer_model->get_by_id($invoice->customer_id);
        if (empty($customer->gst_number) || strlen($customer->gst_number) !== 15) {
            echo json_encode(['success' => false, 'message' => 'Customer must have a valid 15-digit GSTIN for e-Invoice']);
            return;
        }

        $items = $this->Invoice_model->get_items($invoice_id);
        $company = $this->Invoice_model->get_company_settings();
        if ($errs = $this->_address_errors($invoice, $customer, $company)) {
            echo json_encode(['success' => false, 'message' => "Address problems:\n- " . implode("\n- ", $errs)]);
            return;
        }
        if ($this->_provider() === 'gstzen') { $this->_gz_generate_irn($invoice, $items, $customer, $company); return; }

        try {
            $payload = $this->adaequaregsp->build_einvoice_payload($invoice, $items, $customer, $company);
            if (empty($payload['EwbDtls'])) unset($payload['EwbDtls']);
            if (empty($payload['ShipDtls'])) unset($payload['ShipDtls']);

            $result = $this->adaequaregsp->ei_generate_irn($payload);
            $this->adaequaregsp->update_last_log_invoice($invoice_id);

            $body = isset($result['body']['result']) && is_array($result['body']['result']) ? $result['body']['result'] : $result['body'];

            // DUPIRN: IRN already exists for this document — NIC returns
            // the existing one inside result[0].Desc instead of the normal
            // top-level keys. Extract it so the DB gets updated.
            if (empty($body['Irn']) && is_array($body) && isset($body[0]['InfCd']) && $body[0]['InfCd'] === 'DUPIRN') {
                $desc = $body[0]['Desc'] ?? [];
                $body = [
                    'Irn'    => $desc['Irn'] ?? null,
                    'AckNo'  => $desc['AckNo'] ?? null,
                    'AckDt'  => $desc['AckDt'] ?? null,
                ];
            }

            if (!empty($body['Irn'])) {
                $irn = $body['Irn'];
                $update = [
                    'einvoice_status' => 'generated',
                    'einvoice_irn'    => $irn,
                    'einvoice_ack_no' => $body['AckNo'] ?? null,
                    'einvoice_ack_date' => isset($body['AckDt']) ? date('Y-m-d H:i:s', strtotime($body['AckDt'])) : date('Y-m-d H:i:s'),
                    'einvoice_qr_data'  => $body['SignedQRCode'] ?? null,
                    'einvoice_signed_invoice' => isset($body['SignedInvoice']) ? $body['SignedInvoice'] : null,
                ];
                $this->db->where('id', $invoice_id)->update('invoices', $update);
                $this->log_activity('einvoice', 'generate_irn', "IRN generated for {$invoice->invoice_no}", $invoice_id, $invoice->invoice_no);

                $response = ['success' => true, 'irn' => $irn, 'message' => 'E-Invoice IRN generated successfully'];
                if (!empty($body['EwbNo'])) {
                    $this->db->where('id', $invoice_id)->update('invoices', [
                        'ewb_no' => $body['EwbNo'],
                        'ewb_status' => 'generated',
                        'ewb_date' => isset($body['EwbDt']) ? date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $body['EwbDt']))) : null,
                        'ewb_valid_until' => isset($body['EwbValidTill']) ? date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $body['EwbValidTill']))) : null,
                    ]);
                    $response['ewb_no'] = $body['EwbNo'];
                    $response['message'] .= ' + E-Way Bill #' . $body['EwbNo'];
                }

                echo json_encode($response);
            } else {
                $err = $result['body']['ErrorDetails'][0]['ErrorMessage'] ?? $result['body']['errorMessage'] ?? $result['body']['error'] ?? json_encode($result['body']);
                echo json_encode(['success' => false, 'message' => 'IRN API error: ' . $err, 'api_response' => $result['body']]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ─── Cancel E-Way Bill ─────────────────────────────────────────────────

    public function cancel_ewb_ajax() {
        $invoice_id = (int)$this->input->post('invoice_id');
        $reason = $this->input->post('reason', TRUE) ?: 'Order cancelled';

        $invoice = $this->Invoice_model->get_by_id($invoice_id);
        if (!$invoice || empty($invoice->ewb_no)) {
            echo json_encode(['success' => false, 'message' => 'No E-Way Bill found']);
            return;
        }
        if ($this->_provider() === 'gstzen') { $this->_gz_cancel($invoice, 'ewb', $reason); return; }

        try {
            $result = $this->adaequaregsp->ewb_cancel($invoice->ewb_no, 2, $reason);
            $this->adaequaregsp->update_last_log_invoice($invoice_id);

            $body = isset($result['body']['result']) && is_array($result['body']['result']) ? $result['body']['result'] : $result['body'];
            if ($result['success'] || !empty($body['ewayBillNo'])) {
                $this->db->where('id', $invoice_id)->update('invoices', ['ewb_status' => 'cancelled']);
                echo json_encode(['success' => true, 'message' => 'E-Way Bill cancelled']);
            } else {
                $err = $result['body']['errorMessage'] ?? json_encode($result['body']);
                echo json_encode(['success' => false, 'message' => 'Cancel failed: ' . $err]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ─── Cancel E-Invoice (IRN) ────────────────────────────────────────────

    public function cancel_irn_ajax() {
        $invoice_id = (int)$this->input->post('invoice_id');
        $reason = $this->input->post('reason', TRUE) ?: 'Order cancelled';

        $invoice = $this->Invoice_model->get_by_id($invoice_id);
        if (!$invoice || empty($invoice->einvoice_irn)) {
            echo json_encode(['success' => false, 'message' => 'No IRN found']);
            return;
        }
        if ($this->_provider() === 'gstzen') { $this->_gz_cancel($invoice, 'irn', $reason); return; }

        try {
            $result = $this->adaequaregsp->ei_cancel_irn($invoice->einvoice_irn, '1', $reason);
            $this->adaequaregsp->update_last_log_invoice($invoice_id);

            $body = isset($result['body']['result']) && is_array($result['body']['result']) ? $result['body']['result'] : $result['body'];
            if ($result['success'] || !empty($body['Irn'])) {
                $this->db->where('id', $invoice_id)->update('invoices', ['einvoice_status' => 'cancelled']);
                echo json_encode(['success' => true, 'message' => 'E-Invoice IRN cancelled']);
            } else {
                $err = $result['body']['ErrorDetails'][0]['ErrorMessage'] ?? $result['body']['errorMessage'] ?? json_encode($result['body']);
                echo json_encode(['success' => false, 'message' => 'Cancel failed: ' . $err]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ─── Verify E-Invoice IRN ─────────────────────────────────────────────

    public function verify_irn_ajax() {
        $invoice_id = (int)$this->input->post('invoice_id');
        $invoice = $this->Invoice_model->get_by_id($invoice_id);
        if (!$invoice || empty($invoice->einvoice_irn)) {
            echo json_encode(['success' => false, 'message' => 'No IRN found on this invoice']);
            return;
        }
        if ($this->_provider() === 'gstzen') { $this->_gz_verify($invoice); return; }

        try {
            $result = $this->adaequaregsp->ei_get_irn($invoice->einvoice_irn);
            $this->adaequaregsp->update_last_log_invoice($invoice_id);

            $body = isset($result['body']['result']) && is_array($result['body']['result']) ? $result['body']['result'] : $result['body'];
            if (!empty($body['Irn'])) {
                $details = [
                    'Irn'      => $body['Irn'] ?? '',
                    'Status'   => $body['Status'] ?? '',
                    'AckNo'    => $body['AckNo'] ?? '',
                    'AckDt'    => $body['AckDt'] ?? '',
                    'DocNo'    => $body['DocDtls']['No'] ?? '',
                    'DocDt'    => $body['DocDtls']['Dt'] ?? '',
                    'SellerGstin' => $body['SellerDtls']['Gstin'] ?? '',
                    'BuyerGstin'  => $body['BuyerDtls']['Gstin'] ?? '',
                    'TotInvVal'   => $body['ValDtls']['TotInvVal'] ?? '',
                ];
                echo json_encode(['success' => true, 'message' => 'IRN verified — Status: ' . ($details['Status'] ?: 'Active'), 'details' => $details]);
            } else {
                $err = $result['body']['ErrorDetails'][0]['ErrorMessage'] ?? $result['body']['errorMessage'] ?? json_encode($result['body']);
                echo json_encode(['success' => false, 'message' => 'Verify failed: ' . $err, 'api_response' => $result['body']]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ─── Test GSP Connection ───────────────────────────────────────────────

    public function test_connection_ajax() {
        $api_type = $this->input->post('api_type', TRUE) ?: 'ewb';
        if ($this->_provider() === 'gstzen') {
            try {
                $r = $this->gstzengsp->test_connection();
                echo json_encode(['success' => $r['ok'], 'message' => $r['message']]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            return;
        }
        try {
            $token = $this->adaequaregsp->get_token($api_type);
            echo json_encode([
                'success' => true,
                'message' => ucfirst($api_type) . ' GSP authentication successful',
                'token_preview' => substr($token, 0, 30) . '...',
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ─── GSTZen handlers ────────────────────────────────────────────────────
    // GSTZen replies in one shape for every call (status, message, IrnStatus,
    // EwbStatus, Irn, AckNo, AckDt, EwbNo, EwbDt, EwbValidTill), so the saving
    // logic is shared: whatever the reply says about IRN / EWB is stored.

    private function _gz_dt($v) {
        return $v ? date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $v))) : null;
    }

    // Persist any IRN / EWB details present in a GSTZen reply
    private function _gz_save($invoice_id, $b) {
        $upd = [];
        if (!empty($b['Irn'])) {
            $upd['einvoice_irn']    = $b['Irn'];
            $upd['einvoice_status'] = (($b['IrnStatus'] ?? '') === 'CAN') ? 'cancelled' : 'generated';
            if (!empty($b['AckNo']))         $upd['einvoice_ack_no']   = (string)$b['AckNo'];
            if (!empty($b['AckDt']))         $upd['einvoice_ack_date'] = $this->_gz_dt($b['AckDt']);
            if (!empty($b['SignedQRCode']))  $upd['einvoice_qr_data']  = $b['SignedQRCode'];
            if (!empty($b['SignedInvoice'])) $upd['einvoice_signed_invoice'] = $b['SignedInvoice'];
        }
        if (!empty($b['EwbNo'])) {
            $upd['ewb_no']     = $b['EwbNo'];
            $upd['ewb_status'] = (($b['EwbStatus'] ?? '') === 'CAN') ? 'cancelled' : 'generated';
            if (!empty($b['EwbDt']))        $upd['ewb_date']        = $this->_gz_dt($b['EwbDt']);
            if (!empty($b['EwbValidTill'])) $upd['ewb_valid_until'] = $this->_gz_dt($b['EwbValidTill']);
        }
        if ($upd) $this->db->where('id', $invoice_id)->update('invoices', $upd);
        $this->gstzengsp->update_last_log_invoice($invoice_id);
        return $upd;
    }

    private function _gz_generate_irn($invoice, $items, $customer, $company) {
        try {
            $payload = $this->gstzengsp->einvoice_payload($invoice, $items, $customer, $company, false,
                GstAddress::resolve($invoice, $customer, $company, $this->db));
            $r = $this->gstzengsp->generate_irn($payload);
            $upd = $this->_gz_save($invoice->id, $r['body']);
            if ($r['ok'] && !empty($upd['einvoice_irn'])) {
                $this->log_activity('einvoice', 'generate_irn', "IRN generated via GSTZen for {$invoice->invoice_no}", $invoice->id, $invoice->invoice_no);
                echo json_encode(['success' => true, 'irn' => $upd['einvoice_irn'],
                    'message' => 'GSTZen: ' . $r['message'] . ($this->gstzengsp->is_sandbox() ? ' (sandbox)' : '')]);
            } else {
                echo json_encode(['success' => false, 'message' => 'GSTZen: ' . $r['message'], 'api_response' => $r['body']]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // GSTZen generates the e-way bill against the IRN, so the IRN must exist first.
    private function _gz_generate_ewb($invoice, $items, $customer, $company) {
        if (empty($invoice->einvoice_irn) || ($invoice->einvoice_status ?? '') !== 'generated') {
            echo json_encode(['success' => false, 'message' => 'With GSTZen, generate the E-Invoice (IRN) first - the E-Way Bill is created from the IRN.']);
            return;
        }
        try {
            $payload = $this->gstzengsp->einvoice_payload($invoice, $items, $customer, $company, true,
                GstAddress::resolve($invoice, $customer, $company, $this->db));
            $r = $this->gstzengsp->generate_ewb($payload);
            $upd = $this->_gz_save($invoice->id, $r['body']);
            // An EwbNo in the reply means an e-way bill exists for this IRN — either
            // just generated, or already there (e.g. a retry after a timeout).
            if (!empty($upd['ewb_no'])) {
                $this->log_activity('ewb', 'generate', "E-Way Bill {$upd['ewb_no']} generated via GSTZen for {$invoice->invoice_no}", $invoice->id, $invoice->invoice_no);
                echo json_encode(['success' => true, 'ewb_no' => $upd['ewb_no'], 'message' => $r['ok']
                    ? "E-Way Bill #{$upd['ewb_no']} generated (GSTZen)"
                    : "E-Way Bill #{$upd['ewb_no']} already exists for this invoice — saved (GSTZen: {$r['message']})"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'GSTZen: ' . $r['message'], 'api_response' => $r['body']]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // $what = 'irn' | 'ewb'. GSTZen identifies the document from the full payload.
    private function _gz_cancel($invoice, $what, $reason) {
        try {
            $cust = $this->Customer_model->get_by_id($invoice->customer_id);
            $comp = $this->Invoice_model->get_company_settings();
            $payload = $this->gstzengsp->einvoice_payload($invoice, $this->Invoice_model->get_items($invoice->id), $cust, $comp,
                $what === 'ewb', GstAddress::resolve($invoice, $cust, $comp, $this->db));
            $r = $what === 'ewb' ? $this->gstzengsp->cancel_ewb($payload, $reason)
                                 : $this->gstzengsp->cancel_irn($payload, $reason);
            $this->gstzengsp->update_last_log_invoice($invoice->id);
            $b = $r['body'];
            $done = $what === 'ewb' ? (($b['EwbStatus'] ?? '') === 'CAN') : (($b['IrnStatus'] ?? '') === 'CAN');
            if ($r['ok'] || $done) {
                $col = $what === 'ewb' ? 'ewb_status' : 'einvoice_status';
                $this->db->where('id', $invoice->id)->update('invoices', [$col => 'cancelled']);
                $label = $what === 'ewb' ? 'E-Way Bill' : 'E-Invoice IRN';
                $this->log_activity($what === 'ewb' ? 'ewb' : 'einvoice', 'cancel', "{$label} cancelled via GSTZen for {$invoice->invoice_no}: {$reason}", $invoice->id, $invoice->invoice_no);
                echo json_encode(['success' => true, 'message' => "{$label} cancelled (GSTZen)"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cancel failed - GSTZen: ' . $r['message'], 'api_response' => $b]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // GSTZen has no get-IRN call. Re-posting the same invoice is idempotent:
    // GSTZen answers "IRN already generated" with the IRN it holds, which is
    // used to confirm the stored IRN. Cancelled invoices are not re-posted.
    private function _gz_verify($invoice) {
        if (($invoice->einvoice_status ?? '') === 'cancelled') {
            echo json_encode(['success' => true, 'message' => 'IRN is cancelled (as recorded) - not re-checked with GSTZen.',
                'details' => ['Irn' => $invoice->einvoice_irn, 'Status' => 'CNL', 'AckNo' => $invoice->einvoice_ack_no, 'AckDt' => $invoice->einvoice_ack_date]]);
            return;
        }
        try {
            $cust = $this->Customer_model->get_by_id($invoice->customer_id);
            $comp = $this->Invoice_model->get_company_settings();
            $payload = $this->gstzengsp->einvoice_payload($invoice, $this->Invoice_model->get_items($invoice->id), $cust, $comp,
                false, GstAddress::resolve($invoice, $cust, $comp, $this->db));
            $r = $this->gstzengsp->generate_irn($payload);
            $this->gstzengsp->update_last_log_invoice($invoice->id);
            $b = $r['body'];
            if (!empty($b['Irn'])) {
                $match = $b['Irn'] === $invoice->einvoice_irn;
                echo json_encode(['success' => $match,
                    'message' => $match ? 'IRN verified with GSTZen - Status: ' . ($b['IrnStatus'] ?? '')
                                        : 'GSTZen holds a DIFFERENT IRN for this invoice: ' . $b['Irn'],
                    'details' => ['Irn' => $b['Irn'], 'Status' => $b['IrnStatus'] ?? '', 'AckNo' => $b['AckNo'] ?? '', 'AckDt' => $b['AckDt'] ?? '',
                                  'DocNo' => $invoice->invoice_no, 'SellerGstin' => $this->gstzengsp->seller_gstin(), 'TotInvVal' => $invoice->total_amount]]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Verify failed - GSTZen: ' . $r['message'], 'api_response' => $b]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ─── API Logs ──────────────────────────────────────────────────────────

    public function api_logs() {
        $data['page_title'] = 'GSP API Logs';
        $data['company'] = $this->Invoice_model->get_company_settings();
        $data['logs'] = $this->db->order_by('id', 'DESC')->limit(100)->get('gsp_api_logs')->result();
        $this->render('wholesale_billing/api_logs', $data);
    }
}
