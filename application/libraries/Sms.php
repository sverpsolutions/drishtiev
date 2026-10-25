<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SMS gateway + OTP engine.
 *
 * Each purpose carries its own URL, sender id and DLT template, because Indian
 * TRAI/DLT registration is per message type — a single global gateway URL
 * cannot serve OTP and promotional traffic.
 *
 * Two rules hold throughout:
 *   1. Every attempt is logged, success or failure, with credentials masked.
 *   2. OTP codes are stored HASHED. A leaked row must not let anyone approve a
 *      refund; verification compares hashes and never reads a code back.
 */
class Sms {

    private $CI;
    const TIMEOUT = 15;

    public function __construct() {
        $this->CI =& get_instance();
    }

    public function ready() {
        return $this->CI->db->table_exists('sms_templates');
    }

    public function template($purpose) {
        if (!$this->ready()) return null;
        return $this->CI->db->where('purpose', $purpose)->get('sms_templates')->row();
    }

    public function templates() {
        if (!$this->ready()) return [];
        return $this->CI->db->order_by('category', 'ASC')->order_by('id', 'ASC')
                            ->get('sms_templates')->result();
    }

    /** Is this purpose switched on AND configured enough to actually send? */
    public function enabled($purpose) {
        $t = $this->template($purpose);
        return $t && (int)$t->is_active === 1 && trim((string)$t->api_url) !== '';
    }

    /** Does this purpose block its action until an OTP is verified? */
    public function is_required($purpose) {
        $t = $this->template($purpose);
        return $t && (int)$t->is_required === 1 && (int)$t->is_active === 1;
    }

    // ── Sending ─────────────────────────────────────────────────────────────

    private function fill($text, array $vars) {
        if ($text === null || $text === '') return '';
        foreach ($vars as $k => $v) {
            $text = str_replace('{' . $k . '}', (string)$v, $text);
        }
        return $text;
    }

    /** Hides api keys / passwords before a URL is written to the log. */
    private function mask($url) {
        return preg_replace(
            '/((?:api_?key|apikey|authkey|auth_?token|password|pwd|pass|token|secret)=)[^&\s]+/i',
            '$1***', (string)$url);
    }

    public function normalize_mobile($mobile) {
        $m = preg_replace('/\D/', '', (string)$mobile);
        if (strlen($m) > 10 && substr($m, 0, 2) === '91') $m = substr($m, 2);
        return $m;
    }

    /**
     * Sends one message. Returns
     *   ['ok' => bool, 'message' => string, 'error' => ?string, 'log_id' => int]
     *
     * Never throws — a failed SMS must not take a bill down with it.
     */
    public function send($purpose, $mobile, array $vars = [], array $ref = [], $is_test = false) {
        $t = $this->template($purpose);
        $mobile = $this->normalize_mobile($mobile);

        if (!$t) return $this->log_only($purpose, $mobile, '', 'skipped', 'No template configured for this purpose.', $ref);
        if (!$is_test && (int)$t->is_active !== 1) {
            return $this->log_only($purpose, $mobile, '', 'skipped', 'This SMS purpose is switched off.', $ref);
        }
        if (strlen($mobile) < 10) {
            return $this->log_only($purpose, $mobile, '', 'failed', 'Invalid mobile number.', $ref);
        }
        if (trim((string)$t->api_url) === '') {
            return $this->log_only($purpose, $mobile, '', 'failed', 'No API URL configured.', $ref);
        }

        $c = $this->CI->db->get('company_settings')->result_array();
        $company = [];
        foreach ($c as $row) $company[$row['key_name']] = $row['key_value'];

        $vars = array_merge([
            'company'  => $company['company_name'] ?? '',
            'mobile'   => $mobile,
            'sender'   => $t->sender_id,
            'template' => $t->dlt_template_id,
            'validity' => $t->otp_validity_min,
            'date'     => date('d-M-Y'),
            'time'     => date('h:i A'),
        ], $vars);

        $message = $this->fill($t->message, $vars);
        $vars['message']     = $message;
        $vars['message_url'] = rawurlencode($message);

        $url  = $this->fill($t->api_url, $vars);
        $body = $this->fill($t->post_body, $vars);

        $headers = [];
        foreach (preg_split('/\r\n|\r|\n/', (string)$t->headers) as $h) {
            $h = trim($h);
            if ($h !== '' && strpos($h, ':') !== false) $headers[] = $h;
        }
        if ($t->http_method === 'POST' && $t->content_type) {
            $headers[] = 'Content-Type: ' . $t->content_type;
        }

        $started = microtime(true);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,   // many Indian SMS gateways ship broken chains
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($t->http_method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body !== '' ? $body : '');
        }
        $response = curl_exec($ch);
        $code     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);
        $ms = (int)round((microtime(true) - $started) * 1000);

        $ok = ($curl_err === '' && $code >= 200 && $code < 300);
        // A gateway can answer 200 and still refuse the message, so honour the
        // operator's success marker when one is configured.
        if ($ok && trim((string)$t->success_match) !== '') {
            $ok = stripos((string)$response, trim($t->success_match)) !== false;
        }

        $error = $curl_err !== '' ? $curl_err
               : (!$ok ? ('Gateway responded ' . $code . ($t->success_match ? ' without "' . $t->success_match . '"' : '')) : null);

        $log_id = $this->write_log([
            'purpose'      => $purpose,
            'mobile'       => $mobile,
            'message'      => $message,
            'request_url'  => $this->mask($url),
            'http_code'    => $code,
            'response'     => mb_substr((string)$response, 0, 2000),
            'status'       => $is_test ? 'test' : ($ok ? 'sent' : 'failed'),
            'error'        => $error,
            'duration_ms'  => $ms,
        ], $ref);

        return ['ok' => $ok, 'message' => $message, 'error' => $error,
                'http_code' => $code, 'response' => $response, 'log_id' => $log_id];
    }

    private function log_only($purpose, $mobile, $message, $status, $error, $ref) {
        $id = $this->write_log([
            'purpose' => $purpose, 'mobile' => $mobile, 'message' => $message,
            'status'  => $status,  'error'  => $error,
        ], $ref);
        return ['ok' => false, 'message' => $message, 'error' => $error, 'log_id' => $id];
    }

    private function write_log(array $d, array $ref = []) {
        if (!$this->CI->db->table_exists('sms_logs')) return 0;
        $this->CI->db->insert('sms_logs', array_merge([
            'reference_type' => $ref['type'] ?? null,
            'reference_id'   => $ref['id']   ?? null,
            'reference_no'   => $ref['no']   ?? null,
            'location_id'    => $this->CI->session->userdata('location_id'),
            'user_id'        => $this->CI->session->userdata('user_id'),
            'user_name'      => $this->CI->session->userdata('name'),
            'created_at'     => date('Y-m-d H:i:s'),
        ], $d));
        return (int)$this->CI->db->insert_id();
    }

    // ── OTP ─────────────────────────────────────────────────────────────────

    /**
     * Issues an OTP and texts it. Returns the opaque token the browser holds —
     * never the code itself, so the code only ever exists on the phone.
     */
    public function send_otp($purpose, $mobile, array $vars = [], array $ref = []) {
        $t = $this->template($purpose);
        if (!$t) return ['ok' => false, 'error' => 'This OTP purpose is not configured.'];

        $len  = max(4, min(8, (int)$t->otp_length ?: 6));
        $otp  = str_pad((string)random_int(0, (int)str_repeat('9', $len)), $len, '0', STR_PAD_LEFT);
        $token = bin2hex(random_bytes(24));
        $mob  = $this->normalize_mobile($mobile);

        // One live challenge per purpose+mobile — re-requesting supersedes the old.
        $this->CI->db->where('purpose', $purpose)->where('mobile', $mob)
                     ->where('is_verified', 0)->delete('sms_otps');

        $this->CI->db->insert('sms_otps', [
            'purpose'        => $purpose,
            'mobile'         => $mob,
            'otp_hash'       => password_hash($otp, PASSWORD_DEFAULT),
            'token'          => $token,
            'reference_type' => $ref['type'] ?? null,
            'reference_id'   => $ref['id']   ?? null,
            'context'        => $ref['context'] ?? null,
            'max_attempts'   => max(1, (int)$t->max_attempts ?: 5),
            'expires_at'     => date('Y-m-d H:i:s', strtotime('+' . max(1, (int)$t->otp_validity_min) . ' minute')),
            'created_by'     => $this->CI->session->userdata('user_id'),
        ]);

        $res = $this->send($purpose, $mob, array_merge($vars, ['otp' => $otp]), $ref);

        return ['ok' => $res['ok'], 'token' => $token, 'error' => $res['error'],
                'mobile_masked' => $this->mask_mobile($mob),
                'validity' => (int)$t->otp_validity_min, 'log_id' => $res['log_id']];
    }

    public function mask_mobile($m) {
        $m = $this->normalize_mobile($m);
        return strlen($m) >= 10 ? substr($m, 0, 2) . str_repeat('X', 6) . substr($m, -2) : $m;
    }

    /** Checks a code against its token. Wrong codes burn an attempt. */
    public function verify_otp($token, $code) {
        $row = $this->CI->db->where('token', (string)$token)->get('sms_otps')->row();
        if (!$row)                       return ['ok' => false, 'error' => 'This verification has expired. Request a new OTP.'];
        if ((int)$row->is_verified === 1) return ['ok' => true,  'already' => true];
        if (strtotime($row->expires_at) < time()) return ['ok' => false, 'error' => 'OTP expired. Request a new one.'];
        if ((int)$row->attempts >= (int)$row->max_attempts) {
            return ['ok' => false, 'error' => 'Too many wrong attempts. Request a new OTP.'];
        }

        if (!password_verify(trim((string)$code), $row->otp_hash)) {
            $this->CI->db->where('id', $row->id)->set('attempts', 'attempts + 1', false)->update('sms_otps');
            $left = (int)$row->max_attempts - (int)$row->attempts - 1;
            return ['ok' => false, 'error' => 'Incorrect OTP.' . ($left > 0 ? ' ' . $left . ' attempt(s) left.' : ' No attempts left.')];
        }

        $this->CI->db->where('id', $row->id)->update('sms_otps', [
            'is_verified' => 1, 'verified_at' => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => true, 'token' => $token];
    }

    /**
     * Server-side gate. Call this from the action being protected — an OTP that
     * was verified in the browser means nothing until the action itself checks
     * it here, and consuming it stops the same code approving two refunds.
     */
    public function consume($purpose, $token) {
        if (!$this->is_required($purpose)) return true;   // gate not switched on
        $row = $this->CI->db->where('token', (string)$token)->where('purpose', $purpose)
                            ->where('is_verified', 1)->where('is_consumed', 0)
                            ->get('sms_otps')->row();
        if (!$row) return false;
        if (strtotime($row->expires_at) < strtotime('-30 minute')) return false;
        $this->CI->db->where('id', $row->id)->update('sms_otps', ['is_consumed' => 1]);
        return true;
    }

    /** Housekeeping — safe to call from anywhere. */
    public function purge_expired($days = 7) {
        if (!$this->ready()) return 0;
        $this->CI->db->where('expires_at <', date('Y-m-d H:i:s', strtotime("-{$days} days")))
                     ->delete('sms_otps');
        return $this->CI->db->affected_rows();
    }
}
