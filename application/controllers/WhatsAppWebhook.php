<?php
// ============================================================
// FILE: application/controllers/WhatsAppWebhook.php
// Public endpoint Meta calls directly — GET for webhook verification (echoes back
// hub.challenge if the verify token matches), POST for delivery/read-status and
// inbound-message events. Deliberately extends CI_Controller, not Auth_Controller —
// Meta's servers have no session/login, and this must stay reachable without one.
// Registered in csrf_exclude_uris since Meta's POST carries no CSRF token.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class WhatsAppWebhook extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function index() {
        if ($this->input->method() === 'get') {
            $this->_verify();
        } else {
            $this->_receive();
        }
    }

    private function _verify() {
        $mode      = $this->input->get('hub_mode', TRUE);
        $token     = $this->input->get('hub_verify_token', TRUE);
        $challenge = $this->input->get('hub_challenge', TRUE);

        $row = $this->db->where('key_name', 'whatsapp_webhook_verify_token')->get('company_settings')->row();
        $expected = $row ? $row->key_value : null;

        if ($mode === 'subscribe' && $expected && hash_equals($expected, (string)$token)) {
            echo $challenge;
            return;
        }
        http_response_code(403);
        echo 'Verification failed.';
    }

    private function _receive() {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        http_response_code(200); // ack immediately regardless — Meta retries on non-200

        if (empty($payload['entry']) || !is_array($payload['entry'])) return;

        foreach ($payload['entry'] as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                foreach (($value['statuses'] ?? []) as $status) {
                    $this->_apply_status($status);
                }
                foreach (($value['messages'] ?? []) as $message) {
                    $this->_apply_inbound($message);
                }
            }
        }
    }

    // Updates the matching wa_campaign_recipients row by wa_message_id. A status can
    // arrive more than once (Meta may resend) and out of order is possible, so this
    // only ever moves a row forward (sent -> delivered -> read), never backward.
    private function _apply_status($status) {
        $wa_message_id = $status['id'] ?? null;
        $new_status    = $status['status'] ?? null;
        if (!$wa_message_id || !$new_status) return;

        $rank = ['sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 1];
        if (!isset($rank[$new_status])) return;

        $recipient = $this->db->where('wa_message_id', $wa_message_id)->get('wa_campaign_recipients')->row();
        if (!$recipient) return;

        $current_rank = $rank[$recipient->status] ?? 0;
        if ($new_status !== 'failed' && $current_rank >= $rank[$new_status]) return;

        $update = ['status' => $new_status];
        $ts = isset($status['timestamp']) ? date('Y-m-d H:i:s', (int)$status['timestamp']) : date('Y-m-d H:i:s');
        if ($new_status === 'delivered') $update['delivered_at'] = $ts;
        if ($new_status === 'read')      $update['read_at'] = $ts;
        if ($new_status === 'failed')    $update['error_message'] = $status['errors'][0]['title'] ?? 'Delivery failed';

        $this->db->where('id', $recipient->id)->update('wa_campaign_recipients', $update);
    }

    // Logs an inbound customer reply into wa_responses for the response-management
    // inbox, correlating it to the most recent campaign recipient row for that phone
    // number if one exists (best-effort — a reply may arrive with no prior campaign).
    private function _apply_inbound($message) {
        $from = $message['from'] ?? null;
        if (!$from) return;

        $text = $message['text']['body'] ?? ('[' . ($message['type'] ?? 'unsupported') . ' message]');
        $ts = isset($message['timestamp']) ? date('Y-m-d H:i:s', (int)$message['timestamp']) : date('Y-m-d H:i:s');

        $last10 = substr(preg_replace('/\D/', '', $from), -10);
        $customer = $this->db->like('phone', $last10, 'after')->get('customers')->row();

        $recipient = null;
        if ($customer) {
            $recipient = $this->db->where('customer_id', $customer->id)
                ->order_by('id', 'DESC')->limit(1)->get('wa_campaign_recipients')->row();
            if ($recipient) {
                $this->db->where('id', $recipient->id)->update('wa_campaign_recipients', [
                    'status' => 'replied', 'replied_at' => $ts,
                ]);
            }
        }

        $this->db->insert('wa_responses', [
            'recipient_id' => $recipient->id ?? null,
            'customer_id'  => $customer->id ?? null,
            'phone'        => $from,
            'message_text' => $text,
            'received_at'  => $ts,
            'status'       => 'new',
        ]);
    }
}
