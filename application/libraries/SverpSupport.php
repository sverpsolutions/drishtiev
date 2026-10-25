<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Client for the SVERP CRM helpdesk API (same API ZeroHour uses):
 *   validate_license.php · get_tickets.php · raise_ticket.php · reply_ticket.php
 * Settings (company_settings): sverp_license_key, crm_api_key (secret), crm_api_base_url.
 * The API key / license key are only used server-side — the browser never sees the API key.
 */
class SverpSupport {

    private $CI;
    public $cfg;

    public function __construct() {
        $this->CI =& get_instance();
        $this->cfg = $this->config();
    }

    private function _get($k) {
        $r = $this->CI->db->select('key_value')->where('key_name', $k)->get('company_settings')->row();
        return $r ? (string)$r->key_value : '';
    }

    public function set($k, $v) {
        if ($this->CI->db->where('key_name', $k)->count_all_results('company_settings')) $this->CI->db->where('key_name', $k)->update('company_settings', ['key_value' => $v]);
        else $this->CI->db->insert('company_settings', ['key_name' => $k, 'key_value' => $v]);
    }

    public function config() {
        $host  = $_SERVER['HTTP_HOST'] ?? '';
        $local = in_array($host, ['localhost', '127.0.0.1'], true) || strpos($host, '192.168.') === 0;
        $base  = rtrim($this->_get('crm_api_base_url'), '/');
        return [
            'base'    => $base ?: ($local ? 'http://localhost/crmsverp/api' : 'https://crm.sverpsolutions.com/api'),
            'key'     => trim($this->_get('crm_api_key')),
            'license' => strtoupper(trim($this->_get('sverp_license_key'))),
        ];
    }

    public function configured() { return $this->cfg['key'] !== '' && $this->cfg['license'] !== ''; }

    /** Returns the CRM's decoded body ({success, message, data}) — also for 4xx (a suspended license is a 403 with data). */
    public function call($method, $endpoint, $query = [], $post = [], $files = []) {
        if (!$this->configured()) return ['success' => false, 'message' => 'Support is not set up yet. The owner must add the SV ERP license key and API key in Settings.', 'data' => [], 'not_configured' => true];
        $ch = curl_init($this->cfg['base'] . '/' . $endpoint . ($query ? '?' . http_build_query($query) : ''));
        $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 8,
                 CURLOPT_HTTPHEADER => ['X-API-KEY: ' . $this->cfg['key'], 'Accept: application/json']];
        if ($method === 'POST') {
            foreach ($files as $field => $f) {
                if (!empty($f['tmp_name']) && ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) $post[$field] = new CURLFile($f['tmp_name'], $f['type'] ?: 'application/octet-stream', $f['name']);
            }
            $opts[CURLOPT_POST] = true; $opts[CURLOPT_POSTFIELDS] = $post;
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($body === false) return ['success' => false, 'message' => 'Could not reach the SV ERP support server. Check the internet connection.', 'data' => []];
        $j = json_decode($body, true);
        if (!is_array($j)) return ['success' => false, 'message' => "Support server returned an unexpected response (HTTP $code).", 'data' => []];
        $j['http_code'] = $code;
        return $j;
    }

    /** CRM-relative upload path ("/uploads/tickets/x.png") -> full URL */
    public function asset_url($path) {
        if (!$path) return null;
        if (preg_match('#^https?://#i', $path)) return $path;
        return preg_replace('#/api$#', '', $this->cfg['base']) . '/' . ltrim($path, '/');
    }

    /** Small cache in company_settings; keeps the last good answer if the CRM is briefly down */
    private function _cache($key, $ttl, $fetch) {
        $c = json_decode($this->_get($key), true);
        if (is_array($c) && ($c['at'] ?? 0) > time() - $ttl) return $c['r'];
        $r = $fetch();
        if (empty($r['success']) && empty($r['data']) && is_array($c)) return $c['r'];
        $this->set($key, json_encode(['at' => time(), 'r' => $r], JSON_UNESCAPED_UNICODE));
        return $r;
    }

    public function clear_cache() {
        $this->CI->db->where_in('key_name', ['support_license_cache', 'support_tickets_cache'])->delete('company_settings');
    }

    /** License / AMC status, refreshed every 6 hours (never blocks anything if the CRM is down) */
    public function license() {
        if (!$this->configured()) return null;
        $r = $this->_cache('support_license_cache', 21600, function () { return $this->call('GET', 'validate_license.php', ['license_key' => $this->cfg['license']]); });
        return !empty($r['data']['status']) ? $r['data'] : null;
    }

    /** License number for public pages: first and last block only */
    public function masked_license() {
        $p = explode('-', $this->cfg['license']);
        return count($p) > 2 ? $p[0] . '-' . str_repeat('••••-', count($p) - 2) . end($p) : $this->cfg['license'];
    }

    /** Tickets with a new SVERP reply or newly resolved since staff last opened them (bell) */
    public function updates() {
        if (!$this->configured()) return [];
        $r = $this->_cache('support_tickets_cache', 90, function () { return $this->call('GET', 'get_tickets.php', ['license_key' => $this->cfg['license'], 'status' => 'all', 'per_page' => 50]); });
        $tickets = $r['data']['tickets'] ?? [];
        $seen = json_decode($this->_get('support_seen'), true);
        if (!is_array($seen)) {   // first run: treat everything as seen so old tickets don't flood the bell
            $seen = [];
            foreach ($tickets as $t) $seen[$t['id']] = $t['status'] . '|' . (int)$t['reply_count'];
            $this->set('support_seen', json_encode($seen));
            return [];
        }
        $out = [];
        foreach ($tickets as $t) {
            list($os, $or) = explode('|', $seen[$t['id']] ?? 'open|0') + ['open', '0'];
            $reply = (int)$t['reply_count'] > (int)$or && ($t['latest_reply_from'] ?? '') === 'admin';
            $resolved = $t['status'] === 'resolved' && $os !== 'resolved';
            if ($reply || $resolved) $out[] = ['id' => (int)$t['id'], 'ticket_no' => $t['ticket_no'], 'subject' => $t['subject'], 'type' => $resolved ? 'resolved' : 'reply', 'reply' => $t['latest_reply'] ?? ''];
        }
        return $out;
    }

    public function mark_seen($id, $status, $replies) {
        $seen = json_decode($this->_get('support_seen'), true) ?: [];
        $seen[(int)$id] = $status . '|' . (int)$replies;
        $this->set('support_seen', json_encode($seen));
    }
}
