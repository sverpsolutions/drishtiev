<?php
// ============================================================
// FILE: application/libraries/WhatsAppClient.php
// Thin cURL wrapper around the Meta WhatsApp Business Cloud API. No Guzzle/HTTP
// client exists anywhere else in this codebase (confirmed by research before
// building this), so this uses PHP's native curl_* functions to match the app's
// existing zero-extra-dependency style — consistent with how mPDF/PhpSpreadsheet
// are the only third-party libraries actually installed.
//
// Credentials come from company_settings (same plaintext key/value pattern already
// used for SMTP credentials) and are only ever configured via Settings.php, which is
// gated by require_system_owner() — this library itself does no permission checking,
// callers are responsible for that.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class WhatsAppClient {

    private $CI;
    private $api_version = 'v19.0';
    private $phone_number_id;
    private $access_token;

    public function __construct() {
        $this->CI =& get_instance();
        $rows = $this->CI->db->where_in('key_name', ['whatsapp_phone_number_id', 'whatsapp_access_token'])
            ->get('company_settings')->result();
        $settings = [];
        foreach ($rows as $r) $settings[$r->key_name] = $r->key_value;

        $this->phone_number_id = $settings['whatsapp_phone_number_id'] ?? '';
        $this->access_token    = $settings['whatsapp_access_token'] ?? '';
    }

    public function is_configured() {
        return $this->phone_number_id !== '' && $this->access_token !== '';
    }

    // Indian mobile numbers in this app's `customers.phone` are unvalidated free
    // text (confirmed: live data includes entries with embedded spaces) — strip
    // everything but digits, take the last 10, and prefix the country code Meta's
    // API requires (E.164 without the leading '+'). Returns null if what's left
    // isn't a plausible 10-digit mobile number, so callers can skip/flag it rather
    // than silently sending to a mangled number.
    public function normalize_phone($raw, $country_code = '91') {
        $digits = preg_replace('/\D/', '', (string)$raw);
        if (strlen($digits) < 10) return null;
        $last10 = substr($digits, -10);
        return $country_code . $last10;
    }

    // $components follows Meta's template-component structure, e.g.:
    // [['type'=>'body','parameters'=>[['type'=>'text','text'=>'Rahul'], ['type'=>'text','text'=>'500']]]]
    public function send_template($to_phone, $template_name, $language = 'en', array $components = []) {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to_phone,
            'type'              => 'template',
            'template'          => [
                'name'     => $template_name,
                'language' => ['code' => $language],
            ],
        ];
        if ($components) $payload['template']['components'] = $components;
        return $this->_post_message($payload);
    }

    // Only deliverable inside a 24-hour customer-initiated session window — Meta
    // rejects free text sent outside that window. Use send_template() for anything
    // business-initiated (i.e. every marketing campaign send).
    public function send_text($to_phone, $body) {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to_phone,
            'type'              => 'text',
            'text'              => ['body' => $body],
        ];
        return $this->_post_message($payload);
    }

    // Round-trips a lightweight Graph API call (fetching the phone number's own
    // display info) purely to confirm the stored token/phone_number_id are valid —
    // used by Settings::test_whatsapp_connection().
    public function verify_credentials() {
        if (!$this->is_configured()) {
            return ['ok' => false, 'error' => 'WhatsApp API credentials are not configured.'];
        }
        $url = "https://graph.facebook.com/{$this->api_version}/{$this->phone_number_id}";
        $resp = $this->_curl_get($url);
        if ($resp['http_code'] === 200 && isset($resp['body']['id'])) {
            return ['ok' => true, 'display_phone_number' => $resp['body']['display_phone_number'] ?? null, 'verified_name' => $resp['body']['verified_name'] ?? null];
        }
        return ['ok' => false, 'error' => $resp['body']['error']['message'] ?? ('HTTP ' . $resp['http_code'])];
    }

    private function _post_message(array $payload) {
        if (!$this->is_configured()) {
            return ['ok' => false, 'error' => 'WhatsApp API credentials are not configured.'];
        }
        $url = "https://graph.facebook.com/{$this->api_version}/{$this->phone_number_id}/messages";
        $resp = $this->_curl_post($url, $payload);

        if ($resp['http_code'] === 200 && isset($resp['body']['messages'][0]['id'])) {
            return ['ok' => true, 'wa_message_id' => $resp['body']['messages'][0]['id']];
        }
        return ['ok' => false, 'error' => $resp['body']['error']['message'] ?? ('HTTP ' . $resp['http_code']), 'raw' => $resp['body']];
    }

    private function _curl_post($url, array $payload) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->access_token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 20,
        ]);
        $raw = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['http_code' => 0, 'body' => ['error' => ['message' => 'cURL error: ' . $curl_err]]];
        }
        return ['http_code' => $http_code, 'body' => json_decode($raw, true) ?: []];
    }

    private function _curl_get($url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->access_token],
            CURLOPT_TIMEOUT        => 20,
        ]);
        $raw = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['http_code' => 0, 'body' => ['error' => ['message' => 'cURL error: ' . $curl_err]]];
        }
        return ['http_code' => $http_code, 'body' => json_decode($raw, true) ?: []];
    }
}
