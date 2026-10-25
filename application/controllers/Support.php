<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Help & Support — raise / track tickets with SV ERP (CRM helpdesk), license & AMC status.
 * URL: support            page
 *      support/api?action= license | notifications | list | view | raise | reply | save_config
 * Same CRM API + flow as ZeroHour (includes/sverp_support.php, api/support/index.php).
 */
class Support extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        if (!in_array($this->session->userdata('role'), ['owner', 'superadmin', 'admin', 'manager'], true)) {
            if ($this->input->is_ajax_request()) { $this->_out(['success' => false, 'error' => 'Access denied'], 403); }
            $this->session->set_flashdata('error', 'Access denied: Help & Support is for owner / admin / manager.');
            redirect('dashboard');
        }
        $this->load->library('SverpSupport', null, 'sup');
    }

    private function _is_owner() { return in_array($this->session->userdata('role'), ['owner', 'superadmin'], true); }

    private function _out($a, $code = 200) {
        $this->output->set_status_header($code)->set_content_type('application/json')->set_output(json_encode($a, JSON_UNESCAPED_UNICODE))->_display();
        exit;
    }

    private function _relay($r, $extra = []) {
        $ok = !empty($r['success']);
        $this->_out(['success' => $ok, 'error' => $ok ? null : ($r['message'] ?? 'Request failed'), 'message' => $r['message'] ?? null,
                     'data' => $r['data'] ?? [], 'not_configured' => !empty($r['not_configured'])] + $extra);
    }

    private function _check_image($f) {
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if ($f['error'] !== UPLOAD_ERR_OK) return 'Upload failed. Please try a smaller image.';
        if ($f['size'] > 5 * 1024 * 1024) return 'Screenshot must be 5 MB or smaller.';
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true) || !@getimagesize($f['tmp_name'])) return 'Only JPG, PNG, WEBP or GIF images are allowed.';
        return null;
    }

    private function _company() {
        $r = $this->db->where('key_name', 'company_name')->get('company_settings')->row();
        return $r ? $r->key_value : 'Store';
    }

    public function index() {
        $this->render('support/index', [
            'page_title' => 'Help & Support',
            'is_owner'   => $this->_is_owner(),
            'cfg'        => ['base' => $this->sup->cfg['base'], 'license' => $this->sup->cfg['license'], 'has_key' => $this->sup->cfg['key'] !== ''],
        ]);
    }

    public function api() {
        $action = $this->input->get('action');
        $post = $this->input->server('REQUEST_METHOD') === 'POST';
        try {
            switch ($action) {
            case 'license':
                $this->_relay($this->sup->call('GET', 'validate_license.php', ['license_key' => $this->sup->cfg['license']]));
            case 'notifications':
                $this->_out(['success' => true, 'updates' => $this->sup->updates()]);
            case 'list':
                $st = in_array($this->input->get('status'), ['open', 'in_progress', 'resolved'], true) ? $this->input->get('status') : 'all';
                $r = $this->sup->call('GET', 'get_tickets.php', ['license_key' => $this->sup->cfg['license'], 'status' => $st, 'page' => max(1, (int)$this->input->get('page')), 'per_page' => 20]);
                foreach ($r['data']['tickets'] ?? [] as $i => $t) $r['data']['tickets'][$i]['image_url'] = $this->sup->asset_url($t['image_path'] ?? null);
                $this->_relay($r);
            case 'view':
                $r = $this->sup->call('GET', 'get_tickets.php', ['license_key' => $this->sup->cfg['license'], 'ticket_id' => (int)$this->input->get('ticket_id')]);
                if (!empty($r['data']['id'])) {
                    $r['data']['image_url'] = $this->sup->asset_url($r['data']['image_path'] ?? null);
                    foreach ($r['data']['replies'] ?? [] as $i => $rep) $r['data']['replies'][$i]['image_url'] = $this->sup->asset_url($rep['image_path'] ?? null);
                    $this->sup->mark_seen($r['data']['id'], (string)$r['data']['status'], count($r['data']['replies'] ?? []));
                }
                $this->_relay($r);
            case 'raise':
                if (!$post) $this->_out(['success' => false, 'error' => 'POST required'], 405);
                $subject = trim((string)$this->input->post('subject')); $message = trim((string)$this->input->post('message'));
                $category = trim((string)$this->input->post('category'));
                $priority = in_array($this->input->post('priority'), ['low', 'medium', 'high'], true) ? $this->input->post('priority') : 'medium';
                if (mb_strlen($subject) < 5 || mb_strlen($subject) > 150) $this->_out(['success' => false, 'error' => 'Subject must be 5–150 characters.'], 400);
                if (mb_strlen($message) < 15) $this->_out(['success' => false, 'error' => 'Please describe the issue in a little more detail (at least 15 characters).'], 400);
                if ($err = $this->_check_image($_FILES['image'] ?? null)) $this->_out(['success' => false, 'error' => $err], 400);
                $who = ($this->session->userdata('name') ?: 'Staff') . ' (' . $this->session->userdata('role') . ')';
                $body = ($category ? "Category: $category\n" : '') . "Raised by: $who\nStore: " . $this->_company() . ' (' . ($_SERVER['HTTP_HOST'] ?? '') . ")\n\n" . $message;
                $r = $this->sup->call('POST', 'raise_ticket.php', [], ['license_key' => $this->sup->cfg['license'], 'subject' => ($category ? "[$category] " : '') . $subject,
                        'message' => $body, 'priority' => $priority], ['image' => $_FILES['image'] ?? []]);
                if (!empty($r['success'])) $this->log_activity('support', 'ticket_raise', 'Raised support ticket ' . ($r['data']['ticket_no'] ?? '') . ': ' . $subject);
                $this->_relay($r, ['ticket_no' => $r['data']['ticket_no'] ?? null]);
            case 'reply':
                if (!$post) $this->_out(['success' => false, 'error' => 'POST required'], 405);
                $tid = (int)$this->input->post('ticket_id'); $message = trim((string)$this->input->post('message'));
                if (!$tid || $message === '') $this->_out(['success' => false, 'error' => 'Write a reply first.'], 400);
                if ($err = $this->_check_image($_FILES['image'] ?? null)) $this->_out(['success' => false, 'error' => $err], 400);
                // reply_ticket.php trusts the ticket id, so make sure it belongs to this license first
                $own = $this->sup->call('GET', 'get_tickets.php', ['license_key' => $this->sup->cfg['license'], 'ticket_id' => $tid]);
                if (empty($own['success'])) $this->_out(['success' => false, 'error' => 'Ticket not found for this store.'], 404);
                $r = $this->sup->call('POST', 'reply_ticket.php', [], ['ticket_id' => $tid, 'license_key' => $this->sup->cfg['license'], 'message' => $message, 'sender_type' => 'customer',
                        'sender_name' => ($this->session->userdata('name') ?: 'Store Staff') . ' — ' . $this->_company()], ['image' => $_FILES['image'] ?? []]);
                $this->_relay($r);
            case 'save_config':
                if (!$post) $this->_out(['success' => false, 'error' => 'POST required'], 405);
                if (!$this->_is_owner()) $this->_out(['success' => false, 'error' => 'Owner only'], 403);
                $base = rtrim(trim((string)$this->input->post('crm_api_base_url')), '/');
                if ($base !== '' && !preg_match('#^https?://#i', $base)) $this->_out(['success' => false, 'error' => 'API URL must start with http:// or https://'], 400);
                $this->sup->set('crm_api_base_url', $base);
                $this->sup->set('sverp_license_key', strtoupper(trim((string)$this->input->post('sverp_license_key'))));
                if (trim((string)$this->input->post('crm_api_key')) !== '') $this->sup->set('crm_api_key', trim($this->input->post('crm_api_key')));   // blank = keep
                $this->sup->clear_cache();
                $this->log_activity('support', 'config_update', 'SV ERP support settings updated');
                $this->_out(['success' => true]);
            default:
                $this->_out(['success' => false, 'error' => 'Unknown action'], 400);
            }
        } catch (Throwable $e) {
            $this->_out(['success' => false, 'error' => 'Support request failed: ' . $e->getMessage()], 500);
        }
    }
}
