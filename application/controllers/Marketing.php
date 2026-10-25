<?php
// ============================================================
// FILE: application/controllers/Marketing.php
// WhatsApp Marketing & Campaign Management — Phase 1: dashboard shell only.
// Campaign creation/sending/analytics land in later phases (see the approved plan).
// Gated by mod_whatsapp_marketing, which defaults OFF for everyone except the
// system-owner break-glass login (see Auth.php::_complete_login()) — a client
// admin only sees this after the vendor grants it via User Management.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Marketing extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        // Auth_Controller's $ctrl_map gate (mod_whatsapp_marketing) already ran via
        // can_module(), but that helper bypasses for ANY role='superadmin' account —
        // deliberate everywhere else, wrong here (see header.php's $__wa_visible for
        // the same reasoning). Re-check explicitly so only the literal system-owner
        // account (user_id 9999) or a user explicitly granted the module gets in —
        // a client's own superadmin/owner login does not, until the vendor grants it.
        $is_system_owner = (int)$this->session->userdata('user_id') === 9999;
        $module_perms = $this->session->userdata('module_perms') ?? [];
        if (!$is_system_owner && empty($module_perms['mod_whatsapp_marketing'])) {
            $this->session->set_flashdata('error', 'WhatsApp Marketing is not enabled for your account.');
            redirect('dashboard');
        }
        $this->load->model('Marketing_model');
        $this->load->library('form_validation');
    }

    public function index() {
        $data['page_title'] = 'WhatsApp Marketing';
        $data['summary'] = $this->Marketing_model->get_dashboard_summary();
        $data['recent_campaigns'] = $this->Marketing_model->get_recent_campaigns();
        $data['is_configured'] = $this->Marketing_model->is_whatsapp_configured();
        $data['can_configure'] = (int)$this->session->userdata('user_id') === 9999;
        $this->render('marketing/dashboard', $data);
    }

    public function campaigns() {
        $data['page_title'] = 'Campaigns';
        $data['campaigns']  = $this->Marketing_model->get_all_campaigns();
        $this->render('marketing/campaigns', $data);
    }

    public function campaign_create() {
        $data['page_title'] = 'New Campaign';
        $data['cities']     = $this->Marketing_model->get_distinct_cities();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('marketing/campaign_create', $data);
            return;
        }

        $this->form_validation->set_rules('name', 'Campaign Name', 'required|trim');
        $this->form_validation->set_rules('campaign_type', 'Campaign Type', 'required');
        $this->form_validation->set_rules('start_date', 'Start Date', 'required');
        $this->form_validation->set_rules('end_date', 'End Date', 'required');
        $this->form_validation->set_rules('message_body', 'Message', 'required|trim');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors(' '));
            $this->render('marketing/campaign_create', $data);
            return;
        }

        $filters = $this->_collect_filters_from_post();

        $this->db->trans_start();

        $template_id = $this->Marketing_model->create_draft_template(
            trim($this->input->post('name', TRUE)) . ' — message',
            trim($this->input->post('message_body', TRUE))
        );

        $campaign_data = [
            'name'                => trim($this->input->post('name', TRUE)),
            'campaign_type'       => $this->input->post('campaign_type', TRUE),
            'description'         => trim($this->input->post('description', TRUE)) ?: null,
            'start_date'          => $this->input->post('start_date', TRUE),
            'end_date'            => $this->input->post('end_date', TRUE),
            'valid_till'          => $this->input->post('valid_till', TRUE) ?: null,
            'min_purchase'        => (float)$this->input->post('min_purchase'),
            'max_discount'        => (float)$this->input->post('max_discount'),
            'coupon_code'         => trim($this->input->post('coupon_code', TRUE)) ?: null,
            'target_filter'       => json_encode($filters),
            'budget'              => (float)$this->input->post('budget'),
            'priority'            => (int)$this->input->post('priority') ?: 5,
            'message_template_id' => $template_id,
            'status'              => 'draft',
            'created_by'          => $this->session->userdata('user_id'),
        ];
        $campaign_id = $this->Marketing_model->create_campaign($campaign_data);

        // Snapshot the matched audience now — Phase 3's sender works off this fixed
        // list, not a live re-query, so it can't shift under a running campaign.
        $matched = $this->Marketing_model->get_matching_customers($filters);
        $this->Marketing_model->add_recipients($campaign_id, $matched);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error while creating the campaign.');
            $this->render('marketing/campaign_create', $data);
            return;
        }

        $this->log_activity('marketing', 'campaign_create', 'Created campaign "' . $campaign_data['name'] . '" (' . count($matched) . ' matched customers)', $campaign_id, $campaign_data['name']);
        $this->session->set_flashdata('success', 'Campaign created as a draft with ' . count($matched) . ' matched customers.');
        redirect('marketing/campaign_view/' . $campaign_id);
    }

    public function campaign_view($id = null) {
        $campaign = $this->Marketing_model->get_campaign($id);
        if (!$campaign) { show_404(); return; }

        $data['page_title']       = 'Campaign: ' . $campaign->name;
        $data['campaign']         = $campaign;
        $data['template']         = $campaign->message_template_id ? $this->Marketing_model->get_template($campaign->message_template_id) : null;
        $data['recipients']       = $this->Marketing_model->get_campaign_recipients($id);
        $data['recipient_stats']  = $this->Marketing_model->get_recipient_stats($id);
        $data['target_filter']    = json_decode($campaign->target_filter ?? '{}', true) ?: [];
        $this->render('marketing/campaign_view', $data);
    }

    // Live "how many customers match" count as filter fields change — jQuery's
    // global ajaxSend hook (footer.php) attaches the CSRF token automatically.
    public function preview_count_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $filters = $this->_collect_filters_from_post();
        $count = count($this->Marketing_model->get_matching_customers($filters));
        echo json_encode(['count' => $count]);
    }

    private function _collect_filters_from_post() {
        $filters = [];
        foreach (['city', 'pincode', 'customer_type'] as $k) {
            $v = trim((string)$this->input->post($k, TRUE));
            if ($v !== '') $filters[$k] = $v;
        }
        foreach (['last_purchase_days', 'new_customer_days', 'min_total_spent', 'min_balance', 'min_wallet_balance'] as $k) {
            $v = $this->input->post($k, TRUE);
            if ($v !== '' && $v !== null) $filters[$k] = $v;
        }
        if ($this->input->post('never_purchased')) $filters['never_purchased'] = 1;
        return $filters;
    }

    // ─── One-Click Send (Module 14) ────────────────────────────────────────
    // Wires together Modules 2-6 (filter, template, poster) into a single
    // form instead of three separate screens. It does NOT bypass Meta's real
    // template-approval requirement — that's a platform rule enforced at
    // send time (see _send_blocking_reason()), not a UI inconvenience — so
    // this creates a fully-prepared draft campaign (recipients snapshotted,
    // message + poster generated) that's one "Start Sending" click away the
    // moment its template is approved, rather than pretending to skip that.

    private function _audience_presets() {
        return [
            'inactive_30'    => ['label' => "Haven't purchased in 30 days", 'filters' => ['last_purchase_days' => 30]],
            'inactive_90'    => ['label' => "Haven't purchased in 90 days", 'filters' => ['last_purchase_days' => 90]],
            'high_spenders'  => ['label' => 'High spenders (₹5,000+ lifetime)', 'filters' => ['min_total_spent' => 5000]],
            'new_customers'  => ['label' => 'New customers (last 30 days)', 'filters' => ['new_customer_days' => 30]],
            'never_purchased'=> ['label' => 'Never purchased yet', 'filters' => ['never_purchased' => 1]],
            'all'            => ['label' => 'All opted-in customers', 'filters' => []],
        ];
    }

    public function campaign_wizard() {
        $data['page_title'] = 'One-Click Send';
        $data['presets']    = $this->_audience_presets();
        $data['posters']    = $this->Marketing_model->get_poster_templates();
        $this->render('marketing/campaign_wizard', $data);
    }

    public function wizard_preview_count_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');
        $presets = $this->_audience_presets();
        $preset  = $this->input->post('preset', TRUE);
        $filters = $presets[$preset]['filters'] ?? [];
        $count = count($this->Marketing_model->get_matching_customers($filters));
        echo json_encode(['count' => $count]);
    }

    public function campaign_wizard_submit() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') { redirect('marketing/campaign_wizard'); return; }

        $name = trim($this->input->post('name', TRUE));
        $message_body = trim($this->input->post('message_body', TRUE));
        $preset = $this->input->post('preset', TRUE);
        $presets = $this->_audience_presets();

        if ($name === '' || $message_body === '' || !isset($presets[$preset])) {
            $this->session->set_flashdata('error', 'Campaign name, a chosen audience, and message text are all required.');
            redirect('marketing/campaign_wizard');
            return;
        }

        $filters = $presets[$preset]['filters'];

        $this->db->trans_start();

        $template_id = $this->Marketing_model->create_draft_template($name . ' — message', $message_body);

        $campaign_data = [
            'name'                => $name,
            'campaign_type'       => $this->input->post('campaign_type', TRUE) ?: 'custom',
            'start_date'          => date('Y-m-d'),
            'end_date'            => date('Y-m-d', strtotime('+7 days')),
            'valid_till'          => date('Y-m-d', strtotime('+7 days')),
            'target_filter'       => json_encode($filters),
            'message_template_id' => $template_id,
            'status'              => 'draft',
            'created_by'          => $this->session->userdata('user_id'),
        ];
        $campaign_id = $this->Marketing_model->create_campaign($campaign_data);

        $matched = $this->Marketing_model->get_matching_customers($filters);
        $this->Marketing_model->add_recipients($campaign_id, $matched);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error while creating the campaign.');
            redirect('marketing/campaign_wizard');
            return;
        }

        // Optional poster — best-effort, doesn't fail the whole wizard if it errors.
        $poster_id = (int)$this->input->post('poster_template_id');
        if ($poster_id) {
            $poster = $this->Marketing_model->get_poster_template($poster_id);
            if ($poster) {
                $company_row = $this->db->where('key_name', 'company_name')->get('company_settings')->row();
                $this->load->library('PosterCompositor');
                $result = $this->postercompositor->generate($poster->background_path, [
                    'headline'   => $name,
                    'offer'      => trim($this->input->post('offer_text', TRUE)),
                    'valid_till' => 'Valid till ' . date('d M Y', strtotime($campaign_data['valid_till'])),
                    'store_name' => $company_row->key_value ?? '',
                ], base_url());
                if ($result['ok']) {
                    $this->Marketing_model->set_campaign_poster($campaign_id, $result['path']);
                }
            }
        }

        $this->log_activity('marketing', 'campaign_wizard', 'One-click created campaign "' . $name . '" (' . count($matched) . ' recipients)', $campaign_id, $name);
        $this->session->set_flashdata('success', 'Campaign created with ' . count($matched) . ' recipients. Approve its message template, then Start Sending.');
        redirect('marketing/campaign_view/' . $campaign_id);
    }

    // ─── Responses inbox (Module 9) ────────────────────────────────────────

    public function responses() {
        $status = $this->input->get('status', TRUE) ?: null;
        $data['page_title'] = 'Customer Responses';
        $data['responses']  = $this->Marketing_model->get_responses($status);
        $data['stats']      = $this->Marketing_model->count_responses_by_status();
        $data['filter']     = $status;
        $this->render('marketing/responses', $data);
    }

    public function response_edit($id = null) {
        $response = $this->Marketing_model->get_response($id);
        if (!$response) { show_404(); return; }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $data['page_title'] = 'Response';
            $data['response']   = $response;
            $data['users']      = $this->Marketing_model->get_assignable_users();
            $this->render('marketing/response_edit', $data);
            return;
        }

        $this->Marketing_model->update_response($id, [
            'status'         => $this->input->post('status', TRUE),
            'assigned_to'    => (int)$this->input->post('assigned_to') ?: null,
            'follow_up_date' => $this->input->post('follow_up_date', TRUE) ?: null,
            'notes'          => trim($this->input->post('notes', TRUE)) ?: null,
        ]);
        $this->log_activity('marketing', 'response_update', 'Updated response from ' . $response->phone, $id);
        $this->session->set_flashdata('success', 'Response updated.');
        redirect('marketing/responses');
    }

    // ─── Analytics (Module 11) ─────────────────────────────────────────────

    public function analytics() {
        $data['page_title']  = 'Marketing Analytics';
        $data['comparison']  = $this->Marketing_model->get_campaign_comparison();
        $data['trend']       = $this->Marketing_model->get_daily_send_trend(30);
        $this->render('marketing/analytics', $data);
    }

    // ─── Message templates (Module 6) ──────────────────────────────────────

    public function templates() {
        $data['page_title'] = 'Message Templates';
        $data['templates']  = $this->Marketing_model->get_all_templates();
        $this->render('marketing/templates', $data);
    }

    public function template_edit($id = null) {
        $template = $this->Marketing_model->get_template($id);
        if (!$template) { show_404(); return; }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $data['page_title'] = 'Edit Template';
            $data['template']   = $template;
            $this->render('marketing/template_edit', $data);
            return;
        }

        $this->form_validation->set_rules('name', 'Template Name', 'required|trim');
        $this->form_validation->set_rules('status', 'Status', 'required|in_list[draft,pending_approval,approved,rejected]');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors(' '));
            redirect('marketing/template_edit/' . $id);
            return;
        }

        $status    = $this->input->post('status', TRUE);
        $meta_name = trim($this->input->post('meta_template_name', TRUE));
        if ($status === 'approved' && $meta_name === '') {
            $this->session->set_flashdata('error', 'A template cannot be marked Approved without the exact template name approved in Meta Business Manager.');
            redirect('marketing/template_edit/' . $id);
            return;
        }

        $this->Marketing_model->update_template($id, [
            'name'               => trim($this->input->post('name', TRUE)),
            'category'           => trim($this->input->post('category', TRUE)) ?: 'marketing',
            'meta_template_name' => $meta_name ?: null,
            'language'           => trim($this->input->post('language', TRUE)) ?: 'en',
            'header_type'        => $this->input->post('header_type', TRUE) ?: 'none',
            'header_content'     => trim($this->input->post('header_content', TRUE)) ?: null,
            'footer_text'        => trim($this->input->post('footer_text', TRUE)) ?: null,
            'status'             => $status,
        ]);

        $this->log_activity('marketing', 'template_update', 'Updated template "' . $template->name . '" (status: ' . $status . ')', $id, $template->name);
        $this->session->set_flashdata('success', 'Template updated.');
        redirect('marketing/templates');
    }

    // ─── Sending engine (Module 7) — no cron exists in this app, so this
    // follows the same admin-polling pattern as offline sync: the runner page's
    // JS calls process_batch_ajax every few seconds while the tab stays open. ─

    public function campaign_start($id) {
        $campaign = $this->Marketing_model->get_campaign($id);
        if (!$campaign) { show_404(); return; }

        if (!in_array($campaign->status, ['draft', 'scheduled'], true)) {
            $this->session->set_flashdata('error', 'Campaign is not in a startable state.');
            redirect('marketing/campaign_view/' . $id);
            return;
        }

        $reason = $this->_send_blocking_reason($campaign);
        if (!$reason && $this->Marketing_model->get_recipient_stats($id)['total'] === 0) {
            $reason = 'This campaign has no recipients to send to.';
        }
        if ($reason) {
            $this->session->set_flashdata('error', $reason);
            redirect('marketing/campaign_view/' . $id);
            return;
        }

        $this->Marketing_model->update_campaign_status($id, 'running');
        $this->log_activity('marketing', 'campaign_start', 'Started sending campaign "' . $campaign->name . '"', $id, $campaign->name);
        redirect('marketing/campaign_runner/' . $id);
    }

    public function campaign_runner($id) {
        $campaign = $this->Marketing_model->get_campaign($id);
        if (!$campaign || !in_array($campaign->status, ['running', 'paused', 'completed'], true)) {
            redirect('marketing/campaign_view/' . $id);
            return;
        }
        $data['page_title']      = 'Sending: ' . $campaign->name;
        $data['campaign']        = $campaign;
        $data['recipient_stats'] = $this->Marketing_model->get_recipient_stats($id);
        $this->render('marketing/campaign_runner', $data);
    }

    // Sends one small batch per call — deliberately not the whole campaign in
    // one request, so the browser tab stays responsive and pause/cancel can
    // take effect between batches instead of only at the very end.
    public function process_batch_ajax($id) {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');

        $campaign = $this->Marketing_model->get_campaign($id);
        if (!$campaign) { echo json_encode(['ok' => false, 'error' => 'Campaign not found.']); return; }

        if ($campaign->status !== 'running') {
            echo json_encode(['ok' => false, 'error' => 'Campaign is not running.', 'campaign_status' => $campaign->status]);
            return;
        }

        // Re-checked on every batch (not just at start) — if the kill switch
        // gets flipped off or credentials removed mid-run, auto-pause instead
        // of repeatedly failing sends to real customers.
        $reason = $this->_send_blocking_reason($campaign);
        if ($reason) {
            $this->Marketing_model->update_campaign_status($id, 'paused');
            echo json_encode(['ok' => false, 'error' => $reason, 'campaign_status' => 'paused']);
            return;
        }

        $template = $this->Marketing_model->get_template($campaign->message_template_id);
        $this->load->library('WhatsAppClient');

        $batch = $this->Marketing_model->get_pending_recipients($id, 5);
        if (!$batch) {
            $this->Marketing_model->update_campaign_status($id, 'completed');
            echo json_encode(['ok' => true, 'done' => true, 'results' => [], 'remaining' => 0, 'campaign_status' => 'completed', 'stats' => $this->Marketing_model->get_recipient_stats($id)]);
            return;
        }

        $this->Marketing_model->mark_recipients_sending(array_column($batch, 'id'));

        $results = [];
        foreach ($batch as $r) {
            $phone = $this->whatsappclient->normalize_phone($r->phone);
            if (!$phone) {
                $this->Marketing_model->mark_recipient_failed($r->id, 'Invalid phone number');
                $results[] = ['name' => $r->customer_name, 'phone' => $r->phone, 'status' => 'failed', 'error' => 'Invalid phone number'];
                continue;
            }

            $components = $this->_build_template_components($template, $r, $campaign);
            $send = $this->whatsappclient->send_template($phone, $template->meta_template_name, $template->language, $components);

            if ($send['ok']) {
                $this->Marketing_model->mark_recipient_sent($r->id, $send['wa_message_id']);
                $results[] = ['name' => $r->customer_name, 'phone' => $phone, 'status' => 'sent'];
            } else {
                $this->Marketing_model->mark_recipient_failed($r->id, $send['error']);
                $results[] = ['name' => $r->customer_name, 'phone' => $phone, 'status' => 'failed', 'error' => $send['error']];
            }
        }

        $remaining = $this->Marketing_model->count_recipients_by_status($id, 'pending');
        $campaign_status = 'running';
        if ($remaining === 0) {
            $this->Marketing_model->update_campaign_status($id, 'completed');
            $campaign_status = 'completed';
        }

        echo json_encode([
            'ok' => true, 'done' => $remaining === 0, 'results' => $results,
            'remaining' => $remaining, 'campaign_status' => $campaign_status,
            'stats' => $this->Marketing_model->get_recipient_stats($id),
        ]);
    }

    public function campaign_pause($id) {
        $campaign = $this->Marketing_model->get_campaign($id);
        if ($campaign && $campaign->status === 'running') {
            $this->Marketing_model->update_campaign_status($id, 'paused');
        }
        if ($this->input->is_ajax_request()) { header('Content-Type: application/json'); echo json_encode(['ok' => true]); return; }
        redirect('marketing/campaign_runner/' . $id);
    }

    public function campaign_resume($id) {
        $campaign = $this->Marketing_model->get_campaign($id);
        if (!$campaign) { show_404(); return; }

        $reason = $this->_send_blocking_reason($campaign);
        if ($reason) {
            $this->session->set_flashdata('error', $reason);
            redirect('marketing/campaign_view/' . $id);
            return;
        }
        if ($campaign->status === 'paused') {
            $this->Marketing_model->update_campaign_status($id, 'running');
        }
        redirect('marketing/campaign_runner/' . $id);
    }

    public function campaign_cancel($id) {
        $campaign = $this->Marketing_model->get_campaign($id);
        if ($campaign && in_array($campaign->status, ['running', 'paused', 'draft', 'scheduled'], true)) {
            $this->Marketing_model->update_campaign_status($id, 'cancelled');
            $this->log_activity('marketing', 'campaign_cancel', 'Cancelled campaign "' . $campaign->name . '"', $id, $campaign->name);
        }
        $this->session->set_flashdata('success', 'Campaign cancelled.');
        redirect('marketing/campaign_view/' . $id);
    }

    // ─── Manual Send (click-to-chat via wa.me) ─────────────────────────────
    // Doesn't touch Meta's API at all — generates a https://wa.me/<phone>?text=...
    // link per recipient that opens WhatsApp (Web or the desktop/mobile app,
    // whichever the browser resolves it to) with the message pre-filled; a human
    // presses Send themselves. No API credentials, no template approval, no ToS
    // risk (it's the same action a person takes manually) — the tradeoff is it's
    // one click per customer instead of a background bulk send. Recipients move
    // through the exact same wa_campaign_recipients rows/statuses as the
    // automated sender, so the two modes can be mixed on the same campaign.
    public function campaign_manual_send($id) {
        $campaign = $this->Marketing_model->get_campaign($id);
        if (!$campaign) { show_404(); return; }
        if (!in_array($campaign->status, ['draft', 'scheduled', 'running', 'paused'], true)) {
            $this->session->set_flashdata('error', 'This campaign is already completed or cancelled.');
            redirect('marketing/campaign_view/' . $id);
            return;
        }
        $template = $campaign->message_template_id ? $this->Marketing_model->get_template($campaign->message_template_id) : null;
        if (!$template) {
            $this->session->set_flashdata('error', 'This campaign has no message to send.');
            redirect('marketing/campaign_view/' . $id);
            return;
        }

        $this->load->library('WhatsAppClient');
        $pending = $this->Marketing_model->get_pending_recipients($id, 1);
        $recipient = $pending ? $pending[0] : null;

        $data['page_title']      = 'Send Manually: ' . $campaign->name;
        $data['campaign']        = $campaign;
        $data['recipient']       = $recipient;
        $data['recipient_stats'] = $this->Marketing_model->get_recipient_stats($id);

        if ($recipient) {
            $data['message_text'] = $this->_render_message_text($template->body_text, $recipient, $campaign);
            $normalized = $this->whatsappclient->normalize_phone($recipient->phone);
            $data['wa_link'] = $normalized ? 'https://wa.me/' . $normalized . '?text=' . rawurlencode($data['message_text']) : null;
            $data['phone_invalid'] = !$normalized;
        }

        $this->render('marketing/campaign_manual_send', $data);
    }

    // Advances one recipient (sent or skipped) and returns to the next one.
    // First action on a draft campaign flips it to 'running', same as the API path.
    public function campaign_manual_mark($id, $recipient_id, $outcome) {
        $campaign = $this->Marketing_model->get_campaign($id);
        if (!$campaign) { show_404(); return; }

        if ($outcome === 'sent') {
            $this->Marketing_model->mark_recipient_sent($recipient_id, null);
        } else {
            $this->Marketing_model->mark_recipient_failed($recipient_id, 'Skipped (manual send)');
        }

        if ($campaign->status === 'draft') {
            $this->Marketing_model->update_campaign_status($id, 'running');
        }
        $remaining = $this->Marketing_model->count_recipients_by_status($id, 'pending');
        if ($remaining === 0) {
            $this->Marketing_model->update_campaign_status($id, 'completed');
            $this->log_activity('marketing', 'campaign_manual_complete', 'Finished manually sending campaign "' . $campaign->name . '"', $id, $campaign->name);
        }
        redirect('marketing/campaign_manual_send/' . $id);
    }

    // Same {{VarName}} substitution as _build_template_components(), but produces
    // one plain-text string instead of Meta's positional component structure —
    // manual mode sends real free text via wa.me, not a Meta template call.
    private function _render_message_text($body_text, $recipient, $campaign) {
        $values = [
            'CustomerName' => $recipient->customer_name,
            'Date'         => date('d M Y', strtotime($campaign->valid_till ?: $campaign->end_date)),
            'CouponCode'   => $campaign->coupon_code ?: '',
            'MinPurchase'  => $campaign->min_purchase > 0 ? number_format($campaign->min_purchase, 0) : '',
            'MaxDiscount'  => $campaign->max_discount > 0 ? number_format($campaign->max_discount, 0) : '',
            'Reward'       => $campaign->max_discount > 0 ? number_format($campaign->max_discount, 0) : '',
        ];
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($m) use ($values) {
            return $values[$m[1]] ?? '';
        }, $body_text);
    }

    private function _send_blocking_reason($campaign) {
        if (!$this->Marketing_model->is_marketing_enabled()) {
            return 'WhatsApp campaign sending is currently disabled (kill switch is off in company settings).';
        }
        $this->load->library('WhatsAppClient');
        if (!$this->whatsappclient->is_configured()) {
            return 'WhatsApp API is not configured yet — add credentials under Settings → WhatsApp Marketing first.';
        }
        if (!$campaign->message_template_id) {
            return 'This campaign has no message template.';
        }
        $template = $this->Marketing_model->get_template($campaign->message_template_id);
        if (!$template || $template->status !== 'approved' || empty($template->meta_template_name)) {
            return 'The message template must be marked Approved with its exact Meta-approved template name before sending. Edit it under Marketing → Templates.';
        }
        return null;
    }

    // ─── Poster designer (Module 4) ────────────────────────────────────────

    public function posters() {
        $data['page_title'] = 'Poster Templates';
        $data['posters']    = $this->Marketing_model->get_poster_templates();
        $this->render('marketing/posters', $data);
    }

    public function poster_upload() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') { redirect('marketing/posters'); return; }

        $name     = trim($this->input->post('name', TRUE));
        $category = trim($this->input->post('category', TRUE)) ?: 'general';
        if ($name === '' || empty($_FILES['background']['name'])) {
            $this->session->set_flashdata('error', 'A name and a background image are both required.');
            redirect('marketing/posters');
            return;
        }

        $config['upload_path']   = './assets/uploads/wa_posters/backgrounds/';
        $config['allowed_types'] = 'jpg|jpeg|png';
        $config['max_size']      = 4096;
        $config['encrypt_name']  = TRUE;
        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('background')) {
            $this->session->set_flashdata('error', strip_tags($this->upload->display_errors('', '')));
            redirect('marketing/posters');
            return;
        }

        $file = $this->upload->data();
        $path = 'assets/uploads/wa_posters/backgrounds/' . $file['file_name'];
        $this->Marketing_model->create_poster_template($name, $category, $path);
        $this->log_activity('marketing', 'poster_upload', 'Uploaded poster template "' . $name . '"');
        $this->session->set_flashdata('success', 'Poster template uploaded.');
        redirect('marketing/posters');
    }

    public function poster_delete($id) {
        $this->Marketing_model->delete_poster_template($id);
        $this->session->set_flashdata('success', 'Poster template removed.');
        redirect('marketing/posters');
    }

    public function poster_generate($campaign_id = null) {
        $campaign = $this->Marketing_model->get_campaign($campaign_id);
        if (!$campaign) { show_404(); return; }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $data['page_title'] = 'Generate Poster';
            $data['campaign']   = $campaign;
            $data['posters']    = $this->Marketing_model->get_poster_templates();
            $this->render('marketing/poster_generate', $data);
            return;
        }

        $poster = $this->Marketing_model->get_poster_template((int)$this->input->post('poster_template_id'));
        if (!$poster) {
            $this->session->set_flashdata('error', 'Select a poster template first.');
            redirect('marketing/poster_generate/' . $campaign_id);
            return;
        }

        $company_row = $this->db->where('key_name', 'company_name')->get('company_settings')->row();
        $texts = [
            'headline'   => trim($this->input->post('headline', TRUE)) ?: $campaign->name,
            'offer'      => trim($this->input->post('offer_text', TRUE)),
            'valid_till' => $campaign->valid_till ? ('Valid till ' . date('d M Y', strtotime($campaign->valid_till))) : '',
            'store_name' => $company_row->key_value ?? '',
        ];
        $qr_data = trim($this->input->post('qr_url', TRUE)) ?: base_url();

        $this->load->library('PosterCompositor');
        $result = $this->postercompositor->generate($poster->background_path, $texts, $qr_data);

        if (!$result['ok']) {
            $this->session->set_flashdata('error', $result['error']);
            redirect('marketing/poster_generate/' . $campaign_id);
            return;
        }

        $this->Marketing_model->set_campaign_poster($campaign_id, $result['path']);
        $this->log_activity('marketing', 'poster_generate', 'Generated poster for campaign "' . $campaign->name . '"', $campaign_id, $campaign->name);
        $this->session->set_flashdata('success', 'Poster generated.');
        redirect('marketing/campaign_view/' . $campaign_id);
    }

    // ─── AI Copywriter (Module 5) ──────────────────────────────────────────

    public function ai_generate_copy_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');

        $type  = $this->input->post('campaign_type', TRUE) ?: 'custom';
        $brief = trim($this->input->post('brief', TRUE));
        if ($brief === '') {
            echo json_encode(['ok' => false, 'error' => 'Describe the offer first, e.g. "20% off groceries this weekend only".']);
            return;
        }

        $this->load->library('AiCopywriter');
        $result = $this->aicopywriter->generate_variants($type, $brief, 3);
        echo json_encode($result);
    }

    // Meta template variables are positional ({{1}}, {{2}}, ...) in the order
    // they first appear in body_text — variables[] already preserves that order
    // (see Marketing_model::create_draft_template()), so it maps directly.
    private function _build_template_components($template, $recipient, $campaign) {
        $vars = json_decode($template->variables ?? '[]', true) ?: [];
        if (!$vars) return [];

        $values = [
            'CustomerName' => $recipient->customer_name,
            'Date'         => date('d M Y', strtotime($campaign->valid_till ?: $campaign->end_date)),
            'CouponCode'   => $campaign->coupon_code ?: '',
            'MinPurchase'  => $campaign->min_purchase > 0 ? number_format($campaign->min_purchase, 0) : '',
            'MaxDiscount'  => $campaign->max_discount > 0 ? number_format($campaign->max_discount, 0) : '',
            'Reward'       => $campaign->max_discount > 0 ? number_format($campaign->max_discount, 0) : '',
        ];

        $params = [];
        foreach ($vars as $name) {
            $params[] = ['type' => 'text', 'text' => (string)($values[$name] ?? '-')];
        }
        return [['type' => 'body', 'parameters' => $params]];
    }
}
