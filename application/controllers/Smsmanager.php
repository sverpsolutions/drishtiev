<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SMS setup, test sending, OTP endpoints and the send log.
 *
 * Setup is admin-only: the API URL carries the gateway credentials, and turning
 * an OTP gate off would weaken a control.
 */
class Smsmanager extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('Sms');
        if (!$this->sms->ready()) {
            $this->session->set_flashdata('error',
                'SMS tables are not installed on this database. Run sql/48_sms.sql first.');
            redirect('dashboard');
        }
        if (!$this->can_module('mod_sms')) {
            $this->session->set_flashdata('error', 'You do not have access to the SMS module.');
            redirect('dashboard');
        }
    }

    private function json($p) {
        $this->output->set_content_type('application/json')->set_output(json_encode($p));
    }

    private function require_admin_or_bounce($to = 'smsmanager') {
        if (!$this->is_admin()) {
            $this->session->set_flashdata('error', 'Manager or admin permission is required for SMS setup.');
            redirect($to);
        }
    }

    // ── Setup list ──────────────────────────────────────────────────────────
    public function index() {
        $rows = $this->sms->templates();
        $stats = [];
        foreach ($rows as $r) {
            $s = $this->db->query(
                "SELECT COUNT(*) total,
                        SUM(status='sent') sent,
                        SUM(status='failed') failed,
                        MAX(created_at) last_at
                   FROM sms_logs WHERE purpose = ?", [$r->purpose])->row();
            $stats[$r->purpose] = $s;
        }
        $this->render('sms/index', [
            'page_title' => 'SMS Setup',
            'rows'       => $rows,
            'stats'      => $stats,
        ]);
    }

    // ── Edit one purpose ────────────────────────────────────────────────────
    public function edit($id = null) {
        $this->require_admin_or_bounce();
        $row = $id ? $this->db->where('id', (int)$id)->get('sms_templates')->row() : null;
        if (!$row) show_404();

        if ($this->input->method() !== 'post') {
            $this->render('sms/edit', ['page_title' => 'SMS — ' . $row->label, 'r' => $row]);
            return;
        }

        $new = [
            'api_url'          => trim((string)$this->input->post('api_url', TRUE)),
            'http_method'      => $this->input->post('http_method', TRUE) === 'POST' ? 'POST' : 'GET',
            'post_body'        => $this->input->post('post_body', TRUE),
            'content_type'     => trim((string)$this->input->post('content_type', TRUE)) ?: 'application/x-www-form-urlencoded',
            'headers'          => $this->input->post('headers', TRUE),
            'sender_id'        => trim((string)$this->input->post('sender_id', TRUE)),
            'dlt_template_id'  => trim((string)$this->input->post('dlt_template_id', TRUE)),
            'message'          => $this->input->post('message', TRUE),
            'success_match'    => trim((string)$this->input->post('success_match', TRUE)),
            'otp_length'       => max(4, min(8, (int)$this->input->post('otp_length', TRUE))),
            'otp_validity_min' => max(1, (int)$this->input->post('otp_validity_min', TRUE)),
            'max_attempts'     => max(1, min(10, (int)$this->input->post('max_attempts', TRUE))),
            'is_active'        => $this->input->post('is_active') ? 1 : 0,
            'is_required'      => $this->input->post('is_required') ? 1 : 0,
            'updated_by'       => $this->session->userdata('user_id'),
        ];

        $changes = [];
        foreach (['is_active' => 'Active', 'is_required' => 'OTP Required', 'api_url' => 'API URL',
                  'sender_id' => 'Sender ID', 'dlt_template_id' => 'DLT Template', 'message' => 'Message'] as $f => $lbl) {
            if ((string)$new[$f] !== (string)$row->$f) {
                // The URL holds credentials — record that it changed, not what to.
                $changes[] = $f === 'api_url' ? 'API URL updated' : $lbl . ': ' . $row->$f . ' → ' . $new[$f];
            }
        }

        $this->db->where('id', (int)$id)->update('sms_templates', $new);
        if ($changes) {
            $this->log_activity('sms', 'setup', $row->label . ' — ' . implode(', ', $changes), $id, $row->purpose);
        }
        $this->session->set_flashdata('success', 'SMS settings saved for "' . $row->label . '".');
        redirect('smsmanager/edit/' . $id);
    }

    // ── Test send ───────────────────────────────────────────────────────────
    public function test_send() {
        $this->require_admin_or_bounce();
        $purpose = $this->input->post('purpose', TRUE);
        $mobile  = $this->input->post('mobile', TRUE);

        if (!$purpose || !$mobile) { $this->json(['ok' => false, 'error' => 'Purpose and mobile number are required.']); return; }

        // Sample values so the operator sees a realistic message, and a visible
        // 000000 rather than a working code.
        $vars = [
            'name' => 'Test Customer', 'otp' => '000000', 'points' => '100',
            'amount' => '1,250.00', 'reference' => 'TEST-0001', 'balance' => '250',
            'location' => 'Main Store', 'status' => 'APPROVED',
        ];
        $res = $this->sms->send($purpose, $mobile, $vars, ['type' => 'test', 'no' => 'TEST'], true);

        $this->log_activity('sms', 'test', 'Test SMS (' . $purpose . ') to ' . $mobile
            . ' — ' . ($res['ok'] ? 'OK' : 'FAILED'), $res['log_id'] ?? null, $purpose);

        $this->json([
            'ok'        => $res['ok'],
            'error'     => $res['error'],
            'message'   => $res['message'],
            'http_code' => $res['http_code'] ?? null,
            'response'  => mb_substr((string)($res['response'] ?? ''), 0, 600),
            'log_id'    => $res['log_id'] ?? null,
        ]);
    }

    // ── OTP endpoints, used by any screen that needs a gate ─────────────────
    public function send_otp() {
        $purpose = $this->input->post('purpose', TRUE);
        $mobile  = $this->input->post('mobile', TRUE);
        if (!$purpose || !$mobile) { $this->json(['ok' => false, 'error' => 'Mobile number is required.']); return; }
        if (!$this->sms->enabled($purpose)) {
            $this->json(['ok' => false, 'error' => 'OTP is not switched on for this action.']); return;
        }

        $res = $this->sms->send_otp($purpose, $mobile, [
            'name'      => $this->input->post('name', TRUE) ?: 'Customer',
            'points'    => $this->input->post('points', TRUE),
            'amount'    => $this->input->post('amount', TRUE),
            'reference' => $this->input->post('reference', TRUE),
            'location'  => $this->active_location_name(),
        ], [
            'type'    => $this->input->post('ref_type', TRUE),
            'id'      => $this->input->post('ref_id', TRUE),
            'context' => $this->input->post('context', TRUE),
        ]);
        $this->json($res);
    }

    public function verify_otp() {
        $res = $this->sms->verify_otp($this->input->post('token', TRUE), $this->input->post('otp', TRUE));
        $this->json($res);
    }

    // ── Send log ────────────────────────────────────────────────────────────
    public function logs() {
        $f = [
            'from'    => $this->input->get('from', TRUE) ?: date('Y-m-d', strtotime('-7 day')),
            'to'      => $this->input->get('to', TRUE)   ?: date('Y-m-d'),
            'purpose' => $this->input->get('purpose', TRUE),
            'status'  => $this->input->get('status', TRUE),
            'q'       => $this->input->get('q', TRUE),
        ];

        $this->db->from('sms_logs')
                 ->where('created_at >=', $f['from'] . ' 00:00:00')
                 ->where('created_at <=', $f['to'] . ' 23:59:59');
        if ($f['purpose']) $this->db->where('purpose', $f['purpose']);
        if ($f['status'])  $this->db->where('status', $f['status']);
        if ($f['q'])       $this->db->group_start()->like('mobile', $f['q'])
                                    ->or_like('reference_no', $f['q'])->or_like('message', $f['q'])->group_end();
        $rows = $this->db->order_by('id', 'DESC')->limit(1000)->get()->result();

        $tot = $this->db->query(
            "SELECT COUNT(*) total, SUM(status='sent') sent, SUM(status='failed') failed,
                    SUM(status='test') tests, SUM(status='skipped') skipped
               FROM sms_logs WHERE created_at >= ? AND created_at <= ?",
            [$f['from'] . ' 00:00:00', $f['to'] . ' 23:59:59'])->row();

        if ($this->input->get('export', TRUE)) {
            $this->load->library('Excel_report');
            $body = [];
            foreach ($rows as $r) {
                $body[] = [date('d-M-Y H:i', strtotime($r->created_at)), $r->purpose, (string)$r->mobile,
                           $r->status, (string)$r->http_code, $r->reference_no, $r->message,
                           $r->error, $r->user_name];
            }
            $this->excel_report->download('SMS Send Log',
                ['Date/Time', 'Purpose', 'Mobile', 'Status', 'HTTP', 'Reference', 'Message', 'Error', 'Sent By'],
                $body, [
                    'subtitle' => 'Period: ' . date('d M Y', strtotime($f['from'])) . ' to ' . date('d M Y', strtotime($f['to'])),
                    'summary_title' => 'Totals',
                    'summary' => ['TOTAL MESSAGES' => (int)$tot->total, 'Sent' => (int)$tot->sent,
                                  'Failed' => (int)$tot->failed, 'Tests' => (int)$tot->tests,
                                  'Skipped (switched off)' => (int)$tot->skipped],
                    'highlight' => ['TOTAL MESSAGES'],
                    'filename'  => 'sms_send_log_' . $f['from'] . '_to_' . $f['to'],
                ]);
            return;
        }

        $this->render('sms/logs', [
            'page_title' => 'SMS Send Log',
            'rows' => $rows, 'f' => $f, 'tot' => $tot,
            'purposes' => $this->sms->templates(),
        ]);
    }
}
