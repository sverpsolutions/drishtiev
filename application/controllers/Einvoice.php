<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// e-Invoice (IRN) support — Option 3 from the ERP owner's choice: no paid GSP/ASP
// API subscription. This controller builds the government's e-Invoice JSON
// (NIC schema v1.1) for B2B invoices so it can be uploaded manually on
// einvoice1.gst.gov.in (single entry or the portal's own bulk-upload tool).
// The IRN/Ack/QR the portal returns is then recorded back here by hand (or via
// a small JSON paste) so it prints on the invoice as GST rules require.
class Einvoice extends Auth_Controller {

    // Government "Unit Quantity Code" list is fixed — this app's free-text unit
    // field won't match it, so map the common ones and fall back to OTH (Others)
    // rather than send an invalid code the portal would reject outright.
    private $uqc_map = [
        'PCS' => 'PCS', 'PC' => 'PCS', 'NOS' => 'NOS', 'UNIT' => 'NOS',
        'KG' => 'KGS', 'KGS' => 'KGS', 'GM' => 'GMS', 'GMS' => 'GMS', 'GRAM' => 'GMS',
        'LTR' => 'LTR', 'LITRE' => 'LTR', 'ML' => 'MLT',
        'BOX' => 'BOX', 'CTN' => 'CTN', 'CARTON' => 'CTN',
        'PKT' => 'PAC', 'PACK' => 'PAC', 'PACKET' => 'PAC',
        'BAG' => 'BAG', 'BOTTLE' => 'BTL', 'BTL' => 'BTL',
        'DOZEN' => 'DOZ', 'DZN' => 'DOZ', 'DOZ' => 'DOZ',
        'SET' => 'SET', 'PAIR' => 'PRS', 'PRS' => 'PRS',
        'MTR' => 'MTR', 'METER' => 'MTR', 'ROLL' => 'ROL', 'ROL' => 'ROL',
        'CAN' => 'CAN', 'JAR' => 'JAR', 'TIN' => 'TIN', 'TUBE' => 'TUB',
        'BUNDLE' => 'BDL', 'BDL' => 'BDL',
    ];

    private $state_codes = [
        'jammu'        => '01','himachal'     => '02','punjab'       => '03','chandigarh'   => '04',
        'uttarakhand'  => '05','haryana'      => '06','delhi'        => '07','rajasthan'    => '08',
        'uttar pradesh'=> '09','bihar'        => '10','sikkim'       => '11','arunachal'    => '12',
        'nagaland'     => '13','manipur'      => '14','mizoram'      => '15','tripura'      => '16',
        'meghalaya'    => '17','assam'        => '18','west bengal'  => '19','jharkhand'    => '20',
        'odisha'       => '21','chhattisgarh' => '22','madhya pradesh'=> '23','gujarat'    => '24',
        'dadra'        => '26','maharashtra'  => '27','karnataka'   => '29','goa'          => '30',
        'lakshadweep'  => '31','kerala'       => '32','tamil nadu'   => '33','puducherry'   => '34',
        'andaman'      => '35','telangana'    => '36','andhra pradesh'=> '37','ladakh'      => '38',
    ];

    public function __construct() {
        parent::__construct();
        $this->require_admin();
        $this->load->model(['Invoice_model', 'Customer_model']);
    }

    // Dashboard: B2B invoices in a date range with e-Invoice status + bulk export
    public function index() {
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to', TRUE)   ?: date('Y-m-d');
        $status_filter    = $this->input->get('status', TRUE) ?: '';
        $required_only    = $this->input->get('required_only', TRUE) ? 1 : 0;

        $company = $this->Invoice_model->get_company_settings();

        $data['page_title']       = 'e-Invoice (IRN) Export';
        $data['from']             = $from;
        $data['to']               = $to;
        $data['status_filter']    = $status_filter;
        $data['required_only']    = $required_only;
        $data['invoices']         = $this->_eligible_invoices($from, $to, $status_filter, $required_only);
        $data['company']          = $company;
        $data['turnover']         = (float)($company['company_annual_turnover'] ?? 0);
        $data['einvoice_mandatory'] = $data['turnover'] >= 50000000; // CBIC: > Rs 5 Cr aggregate turnover
        $data['hsn_min_digits']   = $data['einvoice_mandatory'] ? 6 : 4;
        $this->render('einvoice/index', $data);
    }

    private function _eligible_invoices($from, $to, $status_filter = '', $required_only = 0) {
        $this->db->select('invoices.*, customers.name AS customer_name, customers.gst_number, customers.einvoice_required')
                  ->from('invoices')
                  ->join('customers', 'customers.id = invoices.customer_id')
                  ->where('invoices.invoice_date >=', $from)
                  ->where('invoices.invoice_date <=', $to)
                  ->where('invoices.status !=', 'cancelled')
                  ->where('customers.gst_number IS NOT NULL')
                  ->where('customers.gst_number !=', '')
                  ->order_by('invoices.invoice_date', 'DESC')
                  ->order_by('invoices.id', 'DESC');
        if ($status_filter !== '') {
            $this->db->where('invoices.einvoice_status', $status_filter);
        }
        if ($required_only) {
            $this->db->where('customers.einvoice_required', 1);
        }
        return $this->db->get()->result();
    }

    // Export selected invoices as one e-Invoice JSON file (NIC schema v1.1,
    // array-wrapped — works for both single-invoice and bulk portal upload).
    public function export() {
        $ids = $this->input->post('invoice_ids');
        if (empty($ids) || !is_array($ids)) {
            $single = $this->input->get('id', TRUE);
            $ids = $single ? [$single] : [];
        }
        if (empty($ids)) {
            $this->session->set_flashdata('error', 'No invoices selected for e-Invoice export.');
            redirect('einvoice');
        }

        $company = $this->Invoice_model->get_company_settings();
        $hsn_min_digits = $this->_hsn_min_digits($company);
        $payload = [];
        $exported_ids = [];
        $skipped = [];

        foreach ($ids as $id) {
            $inv = $this->Invoice_model->get_by_id($id);
            if (!$inv || $inv->status === 'cancelled') continue;
            $customer = $this->Customer_model->get_by_id($inv->customer_id);
            $items = $this->Invoice_model->get_items($id);

            $issues = $this->_preflight($inv, $items, $customer, $company, $hsn_min_digits);
            if (!empty($issues)) {
                $skipped[] = $inv->invoice_no . ' (' . implode('; ', $issues) . ')';
                continue;
            }

            $payload[] = $this->_build_json($inv, $items, $customer, $company);
            $exported_ids[] = $id;
        }

        if (empty($payload)) {
            $this->session->set_flashdata('error', 'No invoices could be exported. ' . implode(' | ', $skipped));
            redirect('einvoice');
        }

        $this->db->where_in('id', $exported_ids)
                  ->where('einvoice_status !=', 'generated')
                  ->update('invoices', ['einvoice_status' => 'pending', 'einvoice_exported_at' => date('Y-m-d H:i:s')]);

        if (!empty($skipped)) {
            $this->session->set_flashdata('error', 'Skipped ' . count($skipped) . ' invoice(s): ' . implode(' | ', $skipped));
        }

        $this->log_activity('einvoice', 'export', 'Exported e-Invoice JSON for ' . count($payload) . ' invoice(s)', 0, '');

        $filename = 'einvoice_export_' . date('Ymd_His') . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // CBIC rule: aggregate annual turnover > Rs 5 Cr => e-Invoice mandatory AND
    // 6-digit HSN mandatory on every invoice (B2B and B2C). Below that, 4-digit
    // HSN is the accepted minimum for B2B tax invoices.
    private function _hsn_min_digits($company) {
        $turnover = (float)($company['company_annual_turnover'] ?? 0);
        return $turnover >= 50000000 ? 6 : 4;
    }

    // Checks that would make the government portal reject the JSON outright —
    // caught here with a plain-English reason instead of a cryptic portal error.
    private function _preflight($inv, $items, $customer, $company, $hsn_min_digits = 4) {
        $issues = [];
        if (!$customer || empty($customer->gst_number) || strlen($customer->gst_number) !== 15) {
            $issues[] = 'customer GSTIN missing/invalid';
        }
        if (empty($company['company_gstin']) || strlen($company['company_gstin']) !== 15) {
            $issues[] = 'company GSTIN not set (Settings > Company Info)';
        }
        if (empty($company['company_pincode'])) {
            $issues[] = 'company pincode not set (Settings > Company Info)';
        }
        if (empty($company['company_state_code'])) {
            $issues[] = 'company GST state code not set (Settings > Company Info)';
        }
        if (empty($customer->pincode)) {
            $issues[] = 'customer pincode missing';
        }
        if (empty($items)) {
            $issues[] = 'no line items';
        }
        foreach ($items as $it) {
            $hsn = trim($it->hsn_code ?? '');
            if (empty($hsn)) {
                $issues[] = 'item "' . $it->product_name . '" has no HSN code';
                break;
            }
            if (!ctype_digit($hsn) || strlen($hsn) < $hsn_min_digits) {
                $issues[] = 'item "' . $it->product_name . '" HSN "' . $hsn . '" needs at least ' . $hsn_min_digits . ' digits (turnover-based rule)';
                break;
            }
        }
        return $issues;
    }

    private function _uqc($unit) {
        $key = strtoupper(trim($unit ?: ''));
        return $this->uqc_map[$key] ?? 'OTH';
    }

    private function _state_code_from_name($state_name, $fallback = '07') {
        $lower = strtolower(trim($state_name ?: ''));
        foreach ($this->state_codes as $key => $code) {
            if (strpos($lower, $key) !== false) return $code;
        }
        return $fallback;
    }

    private function _build_json($inv, $items, $customer, $company) {
        $seller_gstin = strtoupper($company['company_gstin'] ?? '');
        $seller_state_code = $company['company_state_code'] ?? substr($seller_gstin, 0, 2);

        $buyer_gstin = strtoupper($customer->gst_number);
        $buyer_state_code = strlen($buyer_gstin) >= 2 ? substr($buyer_gstin, 0, 2) : $this->_state_code_from_name($customer->state);

        $item_list = [];
        $sl = 1;
        foreach ($items as $it) {
            $gst_rt = (float)$it->gst_percent;
            $item_list[] = [
                'SlNo'      => (string)$sl,
                'PrdDesc'   => $it->product_name,
                'IsServc'   => 'N',
                'HsnCd'     => $it->hsn_code,
                'Qty'       => (float)$it->qty,
                'Unit'      => $this->_uqc($it->unit),
                'UnitPrice' => round((float)$it->rate, 2),
                'TotAmt'    => round((float)$it->rate * (float)$it->qty, 2),
                'Discount'  => round((float)$it->discount, 2),
                'AssAmt'    => round((float)$it->taxable_amt, 2),
                'GstRt'     => $gst_rt,
                'IgstAmt'   => round((float)$it->igst_amount, 2),
                'CgstAmt'   => round((float)$it->cgst_amount, 2),
                'SgstAmt'   => round((float)$it->sgst_amount, 2),
                'CesAmt'    => 0,
                'TotItemVal'=> round((float)$it->total, 2),
            ];
            $sl++;
        }

        $total_discount = round((float)$inv->discount + (float)$inv->bill_discount_amount, 2);

        return [
            'Version'  => '1.1',
            'TranDtls' => [
                'TaxSch'     => 'GST',
                'SupTyp'     => 'B2B',
                'RegRev'     => 'N',
                'IgstOnIntra'=> 'N',
            ],
            'DocDtls' => [
                'Typ' => 'INV',
                'No'  => $inv->invoice_no,
                'Dt'  => date('d/m/Y', strtotime($inv->invoice_date)),
            ],
            'SellerDtls' => [
                'Gstin' => $seller_gstin,
                'LglNm' => $company['company_name'] ?? '',
                'Addr1' => $company['company_address'] ?? '',
                'Loc'   => $company['company_city'] ?? '',
                'Pin'   => (int)($company['company_pincode'] ?? 0),
                'Stcd'  => $seller_state_code,
                'Ph'    => preg_replace('/\D/', '', $company['company_phone'] ?? ''),
                'Em'    => $company['company_email'] ?? '',
            ],
            'BuyerDtls' => [
                'Gstin' => $buyer_gstin,
                'LglNm' => $customer->name,
                'Pos'   => $buyer_state_code,
                'Addr1' => $customer->address ?: $customer->name,
                'Loc'   => $customer->city ?: '',
                'Pin'   => (int)($customer->pincode ?: 0),
                'Stcd'  => $buyer_state_code,
                'Ph'    => preg_replace('/\D/', '', $customer->phone ?? ''),
                'Em'    => $customer->email ?: '',
            ],
            'ItemList' => $item_list,
            'ValDtls' => [
                'AssVal'    => round((float)$inv->taxable_amount, 2),
                'CgstVal'   => round((float)$inv->cgst_amount, 2),
                'SgstVal'   => round((float)$inv->sgst_amount, 2),
                'IgstVal'   => round((float)$inv->igst_amount, 2),
                'CesVal'    => 0,
                'Discount'  => $total_discount,
                'RndOffAmt' => round((float)$inv->round_off, 2),
                'TotInvVal' => round((float)$inv->total_amount, 2),
            ],
        ];
    }

    // Manual entry of what the portal handed back after upload — the safest
    // path since the exact response shape varies between the single-invoice
    // web form and the bulk-upload tool, and this makes no assumption about it.
    public function save_response($id = null) {
        if (!$id) redirect('einvoice');
        $inv = $this->Invoice_model->get_by_id($id);
        if (!$inv) {
            $this->session->set_flashdata('error', 'Invoice not found.');
            redirect('einvoice');
        }

        $irn      = trim($this->input->post('irn', TRUE));
        $ack_no   = trim($this->input->post('ack_no', TRUE));
        $ack_date = trim($this->input->post('ack_date', TRUE));
        $qr_data  = trim($this->input->post('qr_data', TRUE));

        if (empty($irn)) {
            $this->session->set_flashdata('error', 'IRN is required to mark this invoice as generated.');
            redirect('einvoice');
        }

        $this->db->where('id', $id)->update('invoices', [
            'einvoice_status'   => 'generated',
            'einvoice_irn'      => $irn,
            'einvoice_ack_no'   => $ack_no ?: null,
            'einvoice_ack_date' => $ack_date ? date('Y-m-d H:i:s', strtotime($ack_date)) : date('Y-m-d H:i:s'),
            'einvoice_qr_data'  => $qr_data ?: null,
        ]);

        $this->log_activity('einvoice', 'save_response', 'Recorded IRN for invoice #' . $inv->invoice_no, $id, $inv->invoice_no);
        $this->session->set_flashdata('success', 'IRN recorded for invoice #' . $inv->invoice_no . '.');
        redirect('einvoice');
    }

    // Bulk-import a JSON array response (from the portal's bulk tool) — matches
    // rows back to invoices by echoed document number where present, tolerant
    // of a few common key spellings. Anything it can't match is reported back
    // so it can be entered manually via save_response() instead.
    public function import_response() {
        if (!isset($_FILES['response_file']) || $_FILES['response_file']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', 'No response file uploaded.');
            redirect('einvoice');
        }
        $raw = file_get_contents($_FILES['response_file']['tmp_name']);
        $decoded = json_decode($raw, true);
        if ($decoded === null) {
            $this->session->set_flashdata('error', 'Uploaded file is not valid JSON.');
            redirect('einvoice');
        }
        $rows = (isset($decoded[0]) && is_array($decoded[0])) ? $decoded : [$decoded];

        $matched = 0;
        $unmatched = [];
        foreach ($rows as $row) {
            $doc_no = $row['DocDtls']['No'] ?? $row['DocNo'] ?? $row['doc_no'] ?? $row['InvoiceNo'] ?? null;
            $irn    = $row['Irn'] ?? $row['IRN'] ?? $row['irn'] ?? null;
            $ack_no = $row['AckNo'] ?? $row['ackNo'] ?? $row['ack_no'] ?? null;
            $ack_dt = $row['AckDt'] ?? $row['AckDate'] ?? $row['ack_date'] ?? null;
            $qr     = $row['SignedQRCode'] ?? $row['QrCode'] ?? $row['qr_data'] ?? null;

            if (!$doc_no || !$irn) { $unmatched[] = json_encode($row); continue; }

            $inv = $this->db->where('invoice_no', $doc_no)->get('invoices')->row();
            if (!$inv) { $unmatched[] = 'Invoice ' . $doc_no . ' not found'; continue; }

            $this->db->where('id', $inv->id)->update('invoices', [
                'einvoice_status'   => 'generated',
                'einvoice_irn'      => $irn,
                'einvoice_ack_no'   => $ack_no ?: null,
                'einvoice_ack_date' => $ack_dt ? date('Y-m-d H:i:s', strtotime($ack_dt)) : date('Y-m-d H:i:s'),
                'einvoice_qr_data'  => $qr ?: null,
            ]);
            $matched++;
        }

        $msg = 'Matched and recorded IRN for ' . $matched . ' invoice(s).';
        if (!empty($unmatched)) {
            $msg .= ' ' . count($unmatched) . ' row(s) could not be matched — use "Record IRN" manually for those.';
        }
        $this->log_activity('einvoice', 'import_response', $msg, 0, '');
        $this->session->set_flashdata('success', $msg);
        redirect('einvoice');
    }
}
