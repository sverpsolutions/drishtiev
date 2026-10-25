<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AdaequareGsp {

    protected $CI;
    protected $base_url = 'https://gsp.adaequare.com';

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    // ─── Authentication ────────────────────────────────────────────────────

    public function get_token($api_type) {
        $cached = $this->_get_cached_token($api_type);
        if ($cached) return $cached;
        return $this->_authenticate($api_type);
    }

    protected function _get_cached_token($api_type) {
        $row = $this->CI->db
            ->where('api_type', $api_type)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get('gsp_tokens')->row();
        return $row ? $row->access_token : null;
    }

    protected function _authenticate($api_type) {
        $settings = $this->_get_settings($api_type);
        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
            throw new Exception("GSP {$api_type} credentials not configured");
        }

        $url = $this->base_url . '/gsp/authenticate?grant_type=token';
        $headers = [
            'gspappid: ' . $settings['client_id'],
            'gspappsecret: ' . $settings['client_secret'],
        ];

        $response = $this->_curl('POST', $url, null, $headers, 15);

        if (!$response['success'] || empty($response['body']['access_token'])) {
            $err = $response['body']['error'] ?? $response['error'] ?? 'Authentication failed';
            throw new Exception("GSP auth failed for {$api_type}: {$err}");
        }

        $token = $response['body']['access_token'];
        $expires_in = (int)($response['body']['expires_in'] ?? 82800);
        $expires_at = date('Y-m-d H:i:s', time() + $expires_in - 300);

        $this->CI->db->replace('gsp_tokens', [
            'api_type'     => $api_type,
            'access_token' => $token,
            'expires_at'   => $expires_at,
        ]);

        return $token;
    }

    protected function _get_settings($api_type) {
        $prefix = 'gsp_' . $api_type . '_';
        $rows = $this->CI->db
            ->like('key_name', $prefix, 'after')
            ->get('company_settings')->result();
        $out = [];
        foreach ($rows as $r) {
            $key = str_replace($prefix, '', $r->key_name);
            $out[$key] = $r->key_value;
        }
        return $out;
    }

    public function is_sandbox($api_type) {
        $key = 'gsp_' . $api_type . '_sandbox';
        $row = $this->CI->db->where('key_name', $key)->get('company_settings')->row();
        return $row && $row->key_value == '1';
    }

    // ─── E-Way Bill APIs ───────────────────────────────────────────────────

    public function ewb_generate($invoice_data) {
        return $this->_ewb_call('POST', 'GENEWAYBILL', $invoice_data);
    }

    public function ewb_cancel($ewb_no, $reason_code, $remark) {
        return $this->_ewb_call('POST', 'CANEWB', [
            'ewbNo'        => (int)$ewb_no,
            'cancelRsnCode' => (int)$reason_code,
            'cancelRmrk'   => $remark,
        ]);
    }

    public function ewb_update_vehicle($ewb_no, $vehicle_no, $from_place, $from_state, $reason_code = '1', $reason_remark = 'Vehicle change', $trans_mode = '1') {
        return $this->_ewb_call('POST', 'VEHEWB', [
            'EwbNo'       => (int)$ewb_no,
            'VehicleNo'   => $vehicle_no,
            'FromPlace'   => $from_place,
            'FromState'   => (int)$from_state,
            'ReasonCode'  => $reason_code,
            'ReasonRem'   => $reason_remark,
            'TransMode'   => $trans_mode,
        ]);
    }

    public function ewb_get($ewb_no) {
        return $this->_ewb_call('GET', 'GetEwayBill', null, ['ewbNo' => $ewb_no]);
    }

    public function ewb_get_by_date($date) {
        return $this->_ewb_call('GET', 'GetEwayBillsByDate', null, ['date' => $date]);
    }

    protected function _ewb_call($method, $action, $body = null, $query_params = []) {
        $settings = $this->_get_settings('ewb');
        $token = $this->get_token('ewb');
        $sandbox = $this->is_sandbox('ewb');

        if ($method === 'GET') {
            $url = $this->base_url . ($sandbox ? '/test' : '') . '/enriched/ewb/ewayapi/' . $action;
            if (!empty($query_params)) {
                $url .= '?' . http_build_query($query_params);
            }
        } else {
            $url = $this->base_url . ($sandbox ? '/test' : '') . '/enriched/ewb/ewayapi?action=' . $action;
        }

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'gstin: ' . ($this->_company_gstin('ewb')),
            'username: ' . ($settings['username'] ?? ''),
            'password: ' . ($settings['password'] ?? ''),
            'requestid: ' . $this->_request_id(),
        ];

        $response = $this->_curl($method, $url, $body, $headers);

        $this->_log('ewb', $action, null, $body, $response);

        return $response;
    }

    // ─── E-Invoice (IRN) APIs ──────────────────────────────────────────────

    public function ei_generate_irn($invoice_json) {
        return $this->_ei_call('POST', 'GENERATE', $invoice_json);
    }

    public function ei_cancel_irn($irn, $reason_code, $remark) {
        return $this->_ei_call('POST', 'CANCEL', [
            'Irn'       => $irn,
            'CnlRsn'    => (string)$reason_code,
            'CnlRem'    => $remark,
        ]);
    }

    public function ei_get_irn_by_doctype($doc_type, $doc_no, $fin_year) {
        return $this->_ei_call('GET', 'GETIRN', null, [
            'doctype' => $doc_type,
            'docnum'  => $doc_no,
            'finyear' => $fin_year,
        ]);
    }

    public function ei_get_irn($irn) {
        return $this->_ei_call('GET', 'GETIRNBYIRN', null, ['irn' => $irn]);
    }

    public function ei_get_gstin_details($gstin) {
        return $this->_ei_call('GET', 'GETGSTINDETAILS', null, ['gstin' => $gstin]);
    }

    protected function _ei_call($method, $action, $body = null, $query_params = []) {
        $settings = $this->_get_settings('einvoice');
        $token = $this->get_token('einvoice');
        $sandbox = $this->is_sandbox('einvoice');
        $base = $this->base_url . ($sandbox ? '/test' : '') . '/enriched/ei/api';

        $action_paths = [
            'GENERATE'        => '/invoice',
            'CANCEL'          => '/invoice/cancel',
            'GETIRN'          => '/invoice/irn',
            'GETIRNBYIRN'     => '/invoice/irn',
            'GETGSTINDETAILS' => '/master/gstin',
        ];
        $path = $action_paths[$action] ?? '/invoice';
        $url = $base . $path;

        if (!empty($query_params)) {
            $url .= '?' . http_build_query($query_params);
        }

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'gstin: ' . ($this->_company_gstin('einvoice')),
            'user_name: ' . ($settings['username'] ?? ''),
            'password: ' . ($settings['password'] ?? ''),
            'requestid: ' . $this->_request_id(),
        ];

        $response = $this->_curl($method, $url, $body, $headers);

        $this->_log('einvoice', $action, null, $body, $response);

        return $response;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    protected function _company_gstin($api_type = null) {
        if ($api_type && $this->is_sandbox($api_type)) {
            // EWB sandbox: the username IS the GSTIN (e.g. 05AAACG2115R1ZN)
            if ($api_type === 'ewb') {
                $row = $this->CI->db->where('key_name', 'gsp_ewb_username')->get('company_settings')->row();
                if ($row && !empty($row->key_value) && strlen($row->key_value) === 15) {
                    return strtoupper($row->key_value);
                }
            }
            $row = $this->CI->db->where('key_name', 'gsp_sandbox_gstin')->get('company_settings')->row();
            if ($row && !empty($row->key_value)) return strtoupper($row->key_value);
        }
        $row = $this->CI->db->where('key_name', 'company_gstin')->get('company_settings')->row();
        return $row ? strtoupper($row->key_value) : '';
    }

    protected function _request_id() {
        return 'REQ' . time() . rand(1000, 9999);
    }

    protected function _curl($method, $url, $body = null, $headers = [], $timeout = 30) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $raw = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode($raw, true);

        return [
            'success'   => ($http_code >= 200 && $http_code < 300 && $error === ''),
            'http_code' => $http_code,
            'body'      => $decoded ?: [],
            'raw'       => $raw,
            'error'     => $error ?: null,
        ];
    }

    protected function _log($api_type, $action, $invoice_id, $request, $response) {
        $user_id = $this->CI->session->userdata('user_id');
        $this->CI->db->insert('gsp_api_logs', [
            'api_type'         => $api_type,
            'action'           => $action,
            'invoice_id'       => $invoice_id,
            'request_id'       => null,
            'request_payload'  => $request ? json_encode($request) : null,
            'response_payload' => $response['raw'] ?? null,
            'http_status'      => $response['http_code'] ?? null,
            'success'          => ($response['success'] ?? false) ? 1 : 0,
            'error_message'    => $response['error'] ?? null,
            'created_by'       => $user_id ?: null,
        ]);
    }

    // Set the invoice_id on the last log entry (called after _log when we know the ID)
    public function update_last_log_invoice($invoice_id) {
        $last = $this->CI->db->order_by('id', 'DESC')->limit(1)->get('gsp_api_logs')->row();
        if ($last && !$last->invoice_id) {
            $this->CI->db->where('id', $last->id)->update('gsp_api_logs', ['invoice_id' => $invoice_id]);
        }
    }

    // ─── E-Way Bill payload builder ────────────────────────────────────────

    public function build_ewb_payload($invoice, $items, $customer, $company) {
        $seller_gstin = $this->_company_gstin('ewb');
        if (empty($seller_gstin)) $seller_gstin = strtoupper($company['company_gstin'] ?? '');
        $seller_state = (int)substr($seller_gstin, 0, 2);

        $buyer_gstin = strtoupper($customer->gst_number ?? '');
        $buyer_state = strlen($buyer_gstin) >= 2 ? (int)substr($buyer_gstin, 0, 2) : $seller_state;

        $sandbox = $this->is_sandbox('ewb');

        $from_place  = $this->_clean_str($company['company_city'] ?? '', 100) ?: 'Seller City';
        $from_pin    = (int)($company['company_pincode'] ?? 0);
        $from_addr   = $this->_clean_str($company['company_address'] ?? '', 100) ?: 'Address';
        $to_place    = $this->_clean_str($customer->city ?? '', 100) ?: 'Buyer City';
        $to_pin      = (int)($customer->pincode ?: 0);
        $to_addr     = $this->_clean_str($customer->address ?: ($customer->name ?? ''), 100) ?: 'Address';

        if ($sandbox) {
            $from_pin = $this->_default_pin_for_state((string)$seller_state);
            if ($to_pin < 100000 || !$this->_pin_matches_state($to_pin, (string)$buyer_state)) {
                $to_pin = $this->_default_pin_for_state((string)$buyer_state);
            }
        }

        $item_list = [];
        foreach ($items as $it) {
            $is_igst = ($seller_state !== $buyer_state);
            $item_list[] = [
                'productName'   => $this->_clean_str($it->product_name, 100),
                'productDesc'   => $this->_clean_str($it->product_name, 100),
                'hsnCode'       => (int)$this->_normalize_hsn($it->hsn_code ?: ''),
                'quantity'      => (float)$it->qty,
                'qtyUnit'       => $this->_ewb_unit($it->unit ?? 'PCS'),
                'cgstRate'      => $is_igst ? 0 : (float)$it->cgst_percent,
                'sgstRate'      => $is_igst ? 0 : (float)$it->sgst_percent,
                'igstRate'      => $is_igst ? (float)$it->gst_percent : 0,
                'cessRate'      => 0,
                'cessAdvol'     => 0,
                'taxableAmount' => round((float)$it->taxable_amt, 2),
            ];
        }

        $transaction_type = 1;
        if (empty($buyer_gstin) || $buyer_gstin === 'URP') {
            $transaction_type = 4;
        }
        // Delivery address different from the buyer: goods go there (Bill To - Ship To)
        $act_to_state = $buyer_state;
        if (!empty($invoice->ship_to_different) && !empty($invoice->ship_pincode)) {
            $to_addr  = $this->_clean_str((string)$invoice->ship_address, 100) ?: $to_addr;
            $to_place = $this->_clean_str((string)$invoice->ship_place, 100) ?: $to_place;
            $act_to_state = (int)$invoice->ship_state_code ?: $buyer_state;
            $to_pin   = $sandbox && !$this->_pin_matches_state((int)$invoice->ship_pincode, (string)$act_to_state)
                      ? $this->_default_pin_for_state((string)$act_to_state) : (int)$invoice->ship_pincode;
            $transaction_type = 2;
        }

        return [
            'supplyType'       => 'O',
            'subSupplyType'    => '1',
            'docType'          => 'INV',
            'docNo'            => $invoice->invoice_no,
            'docDate'          => date('d/m/Y', strtotime($invoice->invoice_date)),
            'fromGstin'        => $seller_gstin,
            'fromTrdName'      => $this->_clean_str($company['company_name'] ?? '', 100) ?: 'Seller',
            'fromAddr1'        => $from_addr,
            'fromAddr2'        => '',
            'fromPlace'        => $from_place,
            'fromPincode'      => $from_pin,
            'actFromStateCode' => $seller_state,
            'fromStateCode'    => $seller_state,
            'toGstin'          => $buyer_gstin ?: 'URP',
            'toTrdName'        => $this->_clean_str($customer->name ?? '', 100) ?: 'Buyer',
            'toAddr1'          => $to_addr,
            'toAddr2'          => '',
            'toPlace'          => $to_place,
            'toPincode'        => $to_pin,
            'actToStateCode'   => $act_to_state,
            'toStateCode'      => $buyer_state,
            'totalValue'       => round((float)$invoice->taxable_amount, 2),
            'cgstValue'        => round((float)$invoice->cgst_amount, 2),
            'sgstValue'        => round((float)$invoice->sgst_amount, 2),
            'igstValue'        => round((float)$invoice->igst_amount, 2),
            'cessValue'        => 0,
            'totInvValue'      => round((float)$invoice->total_amount, 2),
            'transporterId'    => $invoice->transporter_id ?: '',
            'transporterName'  => $invoice->transporter_name ?: '',
            'transDocNo'       => $invoice->transport_doc_no ?: '',
            'transMode'        => (string)($invoice->transport_mode ?: '1'),
            'transDistance'     => max(1, (int)($invoice->transport_distance ?: 1)),
            'transDocDate'     => $invoice->transport_doc_date ? date('d/m/Y', strtotime($invoice->transport_doc_date)) : '',
            'vehicleNo'        => $invoice->vehicle_no ?: '',
            'vehicleType'      => $invoice->vehicle_type ?: 'R',
            'TransactionType'  => (string)$transaction_type,
            'itemList'         => $item_list,
        ];
    }

    // ─── E-Invoice (IRN) payload builder (NIC schema v1.1) ─────────────────

    public function build_einvoice_payload($invoice, $items, $customer, $company) {
        $seller_gstin = $this->_company_gstin('einvoice');
        if (empty($seller_gstin)) $seller_gstin = strtoupper($company['company_gstin'] ?? '');
        $seller_state = substr($seller_gstin, 0, 2);

        $buyer_gstin = strtoupper($customer->gst_number ?? '');
        $buyer_state = strlen($buyer_gstin) >= 2 ? substr($buyer_gstin, 0, 2) : $seller_state;

        $item_list = [];
        $sl = 1;
        foreach ($items as $it) {
            // NIC requires: AssAmt = TotAmt - Discount.  Our rate is
            // GST-inclusive, so derive GST-exclusive values from the
            // stored taxable_amt (which already has GST backed out).
            $ass_amt  = round((float)$it->taxable_amt, 2);
            $disc     = round((float)$it->discount, 2);
            $tot_amt  = round($ass_amt + $disc, 2);
            $qty      = (float)$it->qty;
            $unit_px  = $qty > 0 ? round($tot_amt / $qty, 2) : 0;

            $item_list[] = [
                'SlNo'       => (string)$sl,
                'PrdDesc'    => $this->_clean_str($it->product_name, 300),
                'IsServc'    => 'N',
                'HsnCd'      => $this->_normalize_hsn($it->hsn_code ?: ''),
                'Qty'        => $qty,
                'Unit'       => $this->_ei_uqc($it->unit ?? 'PCS'),
                'UnitPrice'  => $unit_px,
                'TotAmt'     => $tot_amt,
                'Discount'   => $disc,
                'AssAmt'     => $ass_amt,
                'GstRt'      => (float)$it->gst_percent,
                'IgstAmt'    => round((float)$it->igst_amount, 2),
                'CgstAmt'    => round((float)$it->cgst_amount, 2),
                'SgstAmt'    => round((float)$it->sgst_amount, 2),
                'CesAmt'     => 0,
                'TotItemVal' => round((float)$it->total, 2),
            ];
            $sl++;
        }

        $total_discount = round((float)($invoice->discount ?? 0) + (float)($invoice->bill_discount_amount ?? 0), 2);

        $seller_addr = $this->_clean_str($company['company_address'] ?? '', 100);
        $seller_loc  = $this->_clean_str($company['company_city'] ?? '', 100) ?: 'NA';
        $seller_pin  = (int)($company['company_pincode'] ?? 0);

        $buyer_addr = $this->_clean_str($customer->address ?: ($customer->name ?? ''), 100);
        $buyer_loc  = $this->_clean_str($customer->city ?? '', 100);
        if (empty($buyer_loc)) {
            $buyer_loc = $this->_clean_str($customer->state ?? '', 100) ?: 'NA';
        }
        $buyer_pin = (int)($customer->pincode ?: 0);

        if ($this->is_sandbox('einvoice')) {
            if (empty($seller_addr) || strlen($seller_addr) < 3) $seller_addr = 'Test Seller Address';
            $seller_pin = $this->_default_pin_for_state($seller_state);
            if ($buyer_pin < 100000 || !$this->_pin_matches_state($buyer_pin, $buyer_state)) {
                $buyer_pin = $this->_default_pin_for_state($buyer_state);
            }
        }

        return [
            'Version'  => '1.1',
            'TranDtls' => [
                'TaxSch'      => 'GST',
                'SupTyp'      => 'B2B',
                'RegRev'      => 'N',
                'IgstOnIntra' => 'N',
            ],
            'DocDtls' => [
                'Typ' => 'INV',
                'No'  => $invoice->invoice_no,
                'Dt'  => date('d/m/Y', strtotime($invoice->invoice_date)),
            ],
            'SellerDtls' => [
                'Gstin' => $seller_gstin,
                'LglNm' => $this->_clean_str($company['company_name'] ?? '', 100) ?: 'Seller',
                'Addr1' => $seller_addr ?: 'Address',
                'Loc'   => $seller_loc,
                'Pin'   => $seller_pin,
                'Stcd'  => $seller_state,
            ],
            'BuyerDtls' => [
                'Gstin' => $buyer_gstin,
                'LglNm' => $this->_clean_str($customer->name ?? '', 100) ?: 'Buyer',
                'Pos'   => $buyer_state,
                'Addr1' => $buyer_addr ?: 'Address',
                'Loc'   => $buyer_loc,
                'Pin'   => $buyer_pin,
                'Stcd'  => $buyer_state,
            ],
            'ItemList' => $item_list,
            'ShipDtls' => (!empty($invoice->ship_to_different) && !empty($invoice->ship_pincode)) ? array_filter([
                'Gstin' => strtoupper(trim((string)$invoice->ship_gstin)) ?: null,
                'LglNm' => $this->_clean_str((string)$invoice->ship_name, 100),
                'Addr1' => $this->_clean_str((string)$invoice->ship_address, 100),
                'Loc'   => $this->_clean_str((string)$invoice->ship_place, 100),
                'Pin'   => (int)$invoice->ship_pincode,
                'Stcd'  => str_pad((string)$invoice->ship_state_code, 2, '0', STR_PAD_LEFT),
            ], function ($v) { return $v !== null && $v !== ''; }) : null,
            'ValDtls' => [
                'AssVal'    => round((float)$invoice->taxable_amount, 2),
                'CgstVal'   => round((float)$invoice->cgst_amount, 2),
                'SgstVal'   => round((float)$invoice->sgst_amount, 2),
                'IgstVal'   => round((float)$invoice->igst_amount, 2),
                'CesVal'    => 0,
                'Discount'  => $total_discount,
                'RndOffAmt' => round((float)$invoice->round_off, 2),
                'TotInvVal' => round((float)$invoice->total_amount, 2),
            ],
            'EwbDtls' => $invoice->transport_distance && (int)$invoice->transport_distance > 0 ? [
                'TransId'   => $invoice->transporter_id ?: '',
                'TransName' => $invoice->transporter_name ?: '',
                'Distance'  => (int)$invoice->transport_distance,
                'TransDocNo'  => $invoice->transport_doc_no ?: '',
                'TransDocDt'  => $invoice->transport_doc_date ? date('d/m/Y', strtotime($invoice->transport_doc_date)) : '',
                'VehNo'     => $invoice->vehicle_no ?: '',
                'VehType'   => $invoice->vehicle_type ?: 'R',
                'TransMode' => (string)($invoice->transport_mode ?: '1'),
            ] : null,
        ];
    }

    protected $_uqc_map = [
        'PCS' => 'PCS', 'PC' => 'PCS', 'NOS' => 'NOS', 'UNIT' => 'NOS',
        'KG' => 'KGS', 'KGS' => 'KGS', 'GM' => 'GMS', 'GMS' => 'GMS',
        'LTR' => 'LTR', 'LITRE' => 'LTR', 'ML' => 'MLT',
        'BOX' => 'BOX', 'CTN' => 'CTN', 'CARTON' => 'CTN',
        'PKT' => 'PAC', 'PACK' => 'PAC', 'BAG' => 'BAG',
        'BOTTLE' => 'BTL', 'BTL' => 'BTL', 'SET' => 'SET',
        'DOZEN' => 'DOZ', 'DOZ' => 'DOZ', 'MTR' => 'MTR',
        'ROLL' => 'ROL', 'PAIR' => 'PRS',
    ];

    protected $_ewb_unit_map = [
        'PCS' => 'NOS', 'PC' => 'NOS', 'NOS' => 'NOS', 'UNIT' => 'NOS',
        'KG' => 'KGS', 'KGS' => 'KGS', 'GM' => 'GMS',
        'LTR' => 'LTR', 'ML' => 'MLT',
        'BOX' => 'BOX', 'CTN' => 'CTN', 'BAG' => 'BAG',
        'SET' => 'SET', 'DOZEN' => 'DOZ', 'DOZ' => 'DOZ',
        'MTR' => 'MTR', 'ROLL' => 'ROL',
    ];

    protected function _ei_uqc($unit) {
        $key = strtoupper(trim($unit));
        return $this->_uqc_map[$key] ?? 'OTH';
    }

    protected function _ewb_unit($unit) {
        $key = strtoupper(trim($unit));
        return $this->_ewb_unit_map[$key] ?? 'OTH';
    }

    protected function _clean_str($val, $max = 100) {
        $val = preg_replace('/[\r\n]+/', ', ', trim($val));
        $val = preg_replace('/,\s*,/', ',', $val);
        $val = trim($val, ', ');
        return mb_substr($val, 0, $max);
    }

    protected function _pin_matches_state($pin, $state_code) {
        $expected = $this->_default_pin_for_state($state_code);
        return substr((string)$pin, 0, 1) === substr((string)$expected, 0, 1);
    }

    protected function _default_pin_for_state($state_code) {
        $state_code = str_pad($state_code, 2, '0', STR_PAD_LEFT);
        $map = [
            '01'=>180001,'02'=>175001,'03'=>140001,'04'=>160001,'05'=>248001,
            '06'=>122001,'07'=>110001,'08'=>302001,'09'=>226001,'10'=>452001,
            '11'=>151001,'12'=>160001,'13'=>171001,'14'=>814001,'15'=>713301,
            '16'=>755001,'17'=>751001,'18'=>176001,'19'=>799001,'20'=>226001,
            '21'=>793001,'22'=>786001,'23'=>795001,'24'=>380001,'25'=>796001,
            '26'=>793001,'27'=>400001,'28'=>522001,'29'=>560001,'30'=>600001,
            '31'=>403001,'32'=>682001,'33'=>600001,'34'=>605001,'35'=>799001,
            '36'=>500001,'37'=>530001,'38'=>788001,
        ];
        return $map[$state_code] ?? 110001;
    }

    protected function _normalize_hsn($hsn) {
        return preg_replace('/[^0-9]/', '', (string)$hsn);
    }
}
