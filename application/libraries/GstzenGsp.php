<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'libraries/AdaequareGsp.php';

/**
 * GSTZen E-Invoice + E-Way Bill API (https://www.gstzen.in).
 *
 * Every call is a POST of the full NIC e-invoice JSON (schema 1.1) to one of
 * the einvoice-json/ endpoints, authenticated with a single `Token` header.
 * Responses share one shape: status (1/0), message, IrnStatus / EwbStatus
 * (GEN, ERR, CAN, NON), Irn, AckNo, AckDt, EwbNo, EwbDt, EwbValidTill, ...
 *
 * Extends AdaequareGsp only to reuse its NIC payload builder, unit maps and
 * logging — is_sandbox() and _company_gstin() are overridden so that builder
 * follows the GSTZen settings.
 *
 * Sandbox: GSTZen's public demo token + a demo seller GSTIN route to the
 * government test IRP. The demo seller is picked for the company's own state
 * so CGST/SGST vs IGST stays exactly as on the invoice; the buyer keeps the
 * customer's real GSTIN (the sandbox accepts any valid one).
 */
class GstzenGsp extends AdaequareGsp {

    const BASE_URL   = 'https://my.gstzen.in/~gstzen/a/post-einvoice-data/einvoice-json/';
    const DEMO_TOKEN = 'de3a3a01-273a-4a81-8b75-13fe37f14dc6';

    // GSTZen demo GSTINs, one per state (state code => GSTIN)
    const DEMO_GSTINS = [
        '01' => '01AADCG4992P1Z7', '03' => '03AADCG4992P1Z3', '04' => '04AADCG4992P1Z1', '05' => '05AADCG4992P1ZZ',
        '06' => '06AADCG4992P1ZX', '07' => '07AADCG4992P1ZV', '08' => '08AADCG4992P1ZT', '09' => '09AADCG4992P1ZR',
        '10' => '10AADCG4992P1Z8', '18' => '18AADCG4992P1ZS', '19' => '19AADCG4992P1ZQ', '20' => '20AADCG4992P1Z7',
        '21' => '21AADCG4992P1Z5', '22' => '22AADCG4992P1Z3', '23' => '23AADCG4992P1Z1', '24' => '24AADCG4992P1ZZ',
        '27' => '27AADCG4992P1ZT', '29' => '29AADCG4992P1ZP', '30' => '30AADCG4992P1Z6', '32' => '32AADCG4992P1Z2',
        '33' => '33AADCG4992P1Z0', '36' => '36AADCG4992P1ZU', '37' => '37AADCG4992P2ZR',
    ];

    private function _setting($key, $default = '') {
        $row = $this->CI->db->where('key_name', $key)->get('company_settings')->row();
        return ($row && $row->key_value !== null && $row->key_value !== '') ? $row->key_value : $default;
    }

    public function is_sandbox($api_type = null) {
        return $this->_setting('gstzen_sandbox', '1') == '1';
    }

    // Seller GSTIN sent to GSTZen: in sandbox, an explicit override, else the
    // demo GSTIN for the company's state; in production, the company GSTIN.
    protected function _company_gstin($api_type = null) {
        $real = strtoupper(trim($this->_setting('company_gstin')));
        if (!$this->is_sandbox()) return $real;
        $override = strtoupper(trim($this->_setting('gstzen_sandbox_gstin')));
        if (strlen($override) === 15) return $override;
        return self::DEMO_GSTINS[substr($real, 0, 2)] ?? self::DEMO_GSTINS['27'];
    }

    public function seller_gstin() {
        return $this->_company_gstin();
    }

    private function _token() {
        $tok = trim($this->_setting('gstzen_token'));
        if ($tok !== '') return $tok;
        if ($this->is_sandbox()) return self::DEMO_TOKEN;
        throw new Exception('GSTZen auth token is not set (Settings → E-Invoice & E-Way Bill API).');
    }

    // ─── Payload ───────────────────────────────────────────────────────────

    /**
     * NIC e-invoice JSON for an invoice. With $with_ewb the EwbDtls block is
     * added for e-way bill generation. NIC rejects empty strings for optional
     * transport fields (e.g. TransId must be exactly 15 chars), so blanks are
     * omitted rather than sent as "".
     */
    public function einvoice_payload($invoice, $items, $customer, $company, $with_ewb = false, $addr = null) {
        $p = $this->build_einvoice_payload($invoice, $items, $customer, $company);
        unset($p['EwbDtls']);
        if ($addr) {
            // Real, validated addresses (GstAddress::resolve) replace the builder's
            // fallbacks: seller address = dispatch location, buyer = customer
            // master, POS = ship-to state, plus ShipDtls for a delivery address.
            $d = $addr['dispatch'];
            $p['SellerDtls']['Addr1'] = $this->_clean_str($d['addr1'], 100);
            $p['SellerDtls']['Loc']   = $this->_clean_str($d['place'], 100);
            $p['SellerDtls']['Pin']   = (int)$d['pin'];
            $b = $addr['buyer'];
            $p['BuyerDtls']['Addr1'] = $this->_clean_str($b['addr1'], 100);
            $p['BuyerDtls']['Loc']   = $this->_clean_str($b['place'], 100);
            $p['BuyerDtls']['Pin']   = (int)$b['pin'];
            $p['BuyerDtls']['Stcd']  = $b['state'];
            $p['BuyerDtls']['Pos']   = $addr['pos'];
            if (!empty($addr['ship'])) {
                $s = $addr['ship'];
                $p['ShipDtls'] = array_filter([
                    'Gstin' => $s['gstin'] ?: null,
                    'LglNm' => $this->_clean_str($s['name'], 100),
                    'TrdNm' => $this->_clean_str($s['name'], 100),
                    'Addr1' => $this->_clean_str($s['addr1'], 100),
                    'Loc'   => $this->_clean_str($s['place'], 100),
                    'Pin'   => (int)$s['pin'],
                    'Stcd'  => $s['state'],
                ], function ($v) { return $v !== null && $v !== ''; });
            }
        }
        if ($with_ewb) {
            $ewb = [
                'TransId'    => strtoupper(trim((string)$invoice->transporter_id)),
                'TransName'  => $this->_clean_str((string)$invoice->transporter_name, 100),
                'TransMode'  => (string)($invoice->transport_mode ?: '1'),
                'Distance'   => max(0, (int)$invoice->transport_distance),   // 0 = portal auto-calculates from PINs
                'TransDocNo' => trim((string)$invoice->transport_doc_no),
                'TransDocDt' => $invoice->transport_doc_date ? date('d/m/Y', strtotime($invoice->transport_doc_date)) : '',
                'VehNo'      => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$invoice->vehicle_no)),
                'VehType'    => $invoice->vehicle_type ?: 'R',
            ];
            if (strlen($ewb['TransId']) !== 15) unset($ewb['TransId']);
            if (strlen($ewb['TransName']) < 3)  unset($ewb['TransName']);
            foreach (['TransDocNo', 'TransDocDt', 'VehNo'] as $k) {
                if ($ewb[$k] === '') unset($ewb[$k]);
            }
            // No vehicle yet = Part-A only e-way bill (transporter adds the vehicle
            // later). The IRP rejects a TransMode without a vehicle, so send
            // only the transporter + distance in that case.
            if (empty($ewb['VehNo'])) {
                unset($ewb['VehType']);
                if ($ewb['TransMode'] === '1') unset($ewb['TransMode']);
            }
            $p['EwbDtls'] = $ewb;
        }
        return $p;
    }

    // ─── API calls ─────────────────────────────────────────────────────────

    public function generate_irn($payload)  { return $this->_post('',           'GENERATE',   'einvoice', $payload); }
    public function generate_ewb($payload)  { return $this->_post('genewb/',    'GENEWB',     'ewb',      $payload); }

    // NIC cancel codes differ per document; 4 = "Others" is valid for both and
    // carries the user's reason in the remark.
    public function cancel_irn($payload, $remark) {
        return $this->_post('cancel/', 'CANCEL', 'einvoice',
            $payload + ['CnlRsn' => '4', 'CnlRem' => mb_substr($remark ?: 'Cancelled', 0, 100)]);
    }
    public function cancel_ewb($payload, $remark) {
        return $this->_post('cancelewb/', 'CANCELEWB', 'ewb',
            $payload + ['CnlRsn' => '4', 'CnlRem' => mb_substr($remark ?: 'Cancelled', 0, 100)]);
    }

    /**
     * Checks the token and that the seller GSTIN is in the GSTZen account,
     * without creating anything: a payload with only the seller GSTIN is
     * rejected for the missing date once auth and GSTIN pass.
     */
    public function test_connection() {
        $seller = $this->seller_gstin();
        $r = $this->_post('', 'TEST', 'einvoice', ['SellerDtls' => ['Gstin' => $seller]]);
        if ($r['http_code'] == 403) return ['ok' => false, 'message' => 'GSTZen token is invalid (' . $r['message'] . ')'];
        if ($r['http_code'] == 404) return ['ok' => false, 'message' => "GSTIN {$seller} is not in this GSTZen account"];
        if ($r['http_code'] == 200 && stripos($r['message'], 'missing') !== false) {
            return ['ok' => true, 'message' => 'GSTZen connection OK — token valid, seller GSTIN ' . $seller
                . ($this->is_sandbox() ? ' (SANDBOX)' : ' (PRODUCTION)')];
        }
        return ['ok' => false, 'message' => 'Unexpected GSTZen reply: ' . ($r['message'] ?: ('HTTP ' . $r['http_code']))];
    }

    /**
     * POST to GSTZen and normalise the reply:
     * ['ok' => status==1, 'message' => GSTZen's user-facing message, 'body' => decoded JSON, 'http_code']
     */
    protected function _post($path, $action, $api_type, $body) {
        $headers = [
            'Content-Type: application/json',
            'Token: ' . $this->_token(),
        ];
        $resp = $this->_curl('POST', self::BASE_URL . $path, $body, $headers, 60);
        $this->_log($api_type, 'GSTZEN_' . $action, null, $body, $resp);

        $b = is_array($resp['body']) ? $resp['body'] : [];
        $msg = trim((string)($b['message'] ?? ''));
        if ($msg === '') $msg = $resp['error'] ?: ('HTTP ' . $resp['http_code'] . ' from GSTZen');
        return [
            'ok'        => (string)($b['status'] ?? '0') === '1',
            'message'   => $msg,
            'body'      => $b,
            'http_code' => $resp['http_code'],
        ];
    }
}
