<?php
// ============================================================
// FILE: application/models/Marketing_model.php
// Data layer for the WhatsApp Marketing module (Phase 1: dashboard only —
// campaign CRUD, sending, and analytics land in later phases).
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Marketing_model extends CI_Model {

    // Real counts against the actual tables — deliberately not padded with fake
    // numbers; a fresh install honestly shows zeros until campaigns are sent.
    public function get_dashboard_summary() {
        $total_customers = $this->db->where('status', 1)->count_all_results('customers');
        $wa_active = $this->db->where('status', 1)->where('whatsapp_optin', 1)->count_all_results('customers');
        $campaigns = $this->db->count_all_results('wa_campaigns');
        $today = date('Y-m-d');

        $sent_today = $this->db->where('DATE(sent_at) =', $today)
            ->where_in('status', ['sent', 'delivered', 'read'])
            ->count_all_results('wa_campaign_recipients');

        $delivered = $this->db->where_in('status', ['delivered', 'read'])->count_all_results('wa_campaign_recipients');
        $read = $this->db->where('status', 'read')->count_all_results('wa_campaign_recipients');
        $failed = $this->db->where('status', 'failed')->count_all_results('wa_campaign_recipients');
        $replied = $this->db->where('status', 'replied')->count_all_results('wa_campaign_recipients');

        $total_sent = $this->db->where_in('status', ['sent', 'delivered', 'read', 'replied', 'failed'])->count_all_results('wa_campaign_recipients');
        $converted = $this->db->where('converted_invoice_id IS NOT NULL', null, false)->count_all_results('wa_campaign_recipients');

        $revenue_row = $this->db->select('COALESCE(SUM(i.total_amount),0) AS revenue')
            ->from('wa_campaign_recipients r')
            ->join('invoices i', 'i.id = r.converted_invoice_id')
            ->get()->row();

        return [
            'total_customers'   => $total_customers,
            'whatsapp_active'   => $wa_active,
            'campaigns_created' => $campaigns,
            'messages_sent_today' => $sent_today,
            'delivered'         => $delivered,
            'read'              => $read,
            'failed'            => $failed,
            'responses'         => $replied,
            'click_rate'        => 0, // needs button-click tracking — not wired until Phase 3
            'sales_generated'   => (float)($revenue_row->revenue ?? 0),
            'conversion_rate'   => $total_sent > 0 ? round($converted / $total_sent * 100, 2) : 0,
        ];
    }

    public function get_recent_campaigns($limit = 10) {
        return $this->db->order_by('created_at', 'DESC')->limit($limit)->get('wa_campaigns')->result();
    }

    public function is_whatsapp_configured() {
        $rows = $this->db->where_in('key_name', ['whatsapp_phone_number_id', 'whatsapp_access_token'])
            ->get('company_settings')->result();
        $vals = [];
        foreach ($rows as $r) $vals[$r->key_name] = $r->key_value;
        return !empty($vals['whatsapp_phone_number_id']) && !empty($vals['whatsapp_access_token']);
    }

    // ─── Customer targeting (Module 3) ───────────────────────────────────

    // Recency/frequency/monetary per customer, joined against invoices, mirroring
    // the RFM query already proven in Reports.php's customer_segments report
    // (MAX(invoice_date) for recency, COUNT/SUM for frequency/monetary) — same idiom,
    // extended with the filter fields this app's schema actually has (city, pincode,
    // type, balance, wallet_balance, created_at). Filters requiring fields that don't
    // exist yet (birthday, gender, area, loyalty tier) are intentionally not offered
    // here rather than silently ignored.
    public function get_matching_customers(array $filters) {
        $this->db->select("c.id, c.name, c.phone, c.city, c.pincode, c.type, c.balance, c.wallet_balance, c.created_at,
                MAX(i.invoice_date) AS last_purchase_date, COUNT(i.id) AS visit_count, COALESCE(SUM(i.total_amount),0) AS total_spent")
            ->from('customers c')
            ->join('invoices i', "i.customer_id = c.id AND i.status != 'cancelled'", 'left')
            ->where('c.status', 1)
            ->where('c.whatsapp_optin', 1)
            ->group_by('c.id');

        if (!empty($filters['city']))          $this->db->where('c.city', $filters['city']);
        if (!empty($filters['pincode']))        $this->db->where('c.pincode', $filters['pincode']);
        if (!empty($filters['customer_type']))  $this->db->where('c.type', $filters['customer_type']);
        if ($this->_is_set($filters, 'min_balance'))         $this->db->where('c.balance >=', (float)$filters['min_balance']);
        if ($this->_is_set($filters, 'min_wallet_balance'))  $this->db->where('c.wallet_balance >=', (float)$filters['min_wallet_balance']);
        if (!empty($filters['new_customer_days'])) {
            $this->db->where('c.created_at >=', date('Y-m-d', strtotime('-' . (int)$filters['new_customer_days'] . ' days')));
        }

        // "Haven't purchased in N days" — matches customers whose last invoice is
        // older than the cutoff, OR who have never purchased at all (MAX() is NULL
        // for a customer with zero matching invoices, since the join is LEFT).
        if (!empty($filters['last_purchase_days'])) {
            $cutoff = $this->db->escape(date('Y-m-d', strtotime('-' . (int)$filters['last_purchase_days'] . ' days')));
            $this->db->having("(MAX(i.invoice_date) < {$cutoff} OR MAX(i.invoice_date) IS NULL)", null, false);
        }
        if ($this->_is_set($filters, 'min_total_spent')) {
            $this->db->having('total_spent >=', (float)$filters['min_total_spent']);
        }
        if (!empty($filters['never_purchased'])) {
            $this->db->having('visit_count = 0', null, false);
        }

        return $this->db->get()->result();
    }

    private function _is_set($filters, $key) {
        return isset($filters[$key]) && $filters[$key] !== '';
    }

    public function get_distinct_cities() {
        return $this->db->distinct()->select('city')->where('city IS NOT NULL', null, false)
            ->where('status', 1)->order_by('city', 'ASC')->get('customers')->result();
    }

    // ─── Campaigns (Module 2) ─────────────────────────────────────────────

    public function create_campaign(array $data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('wa_campaigns', $data);
        return $this->db->insert_id();
    }

    public function get_campaign($id) {
        return $this->db->get_where('wa_campaigns', ['id' => $id])->row();
    }

    public function get_all_campaigns() {
        return $this->db->order_by('created_at', 'DESC')->get('wa_campaigns')->result();
    }

    public function update_campaign_status($id, $status) {
        return $this->db->where('id', $id)->update('wa_campaigns', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    // Snapshots the matched customer list into wa_campaign_recipients at creation
    // time rather than re-querying live when sending — so a campaign's audience is
    // fixed once created and doesn't silently shift under a running send.
    public function add_recipients($campaign_id, array $customers) {
        if (!$customers) return 0;
        $rows = [];
        foreach ($customers as $c) {
            $rows[] = [
                'campaign_id'   => $campaign_id,
                'customer_id'   => $c->id,
                'phone'         => $c->phone,
                'customer_name' => $c->name,
                'status'        => 'pending',
                'created_at'    => date('Y-m-d H:i:s'),
            ];
        }
        $this->db->insert_batch('wa_campaign_recipients', $rows);
        return count($rows);
    }

    public function get_campaign_recipients($campaign_id, $limit = 200) {
        return $this->db->where('campaign_id', $campaign_id)->order_by('id', 'ASC')->limit($limit)->get('wa_campaign_recipients')->result();
    }

    public function get_recipient_stats($campaign_id) {
        $rows = $this->db->select('status, COUNT(*) AS c')->where('campaign_id', $campaign_id)->group_by('status')->get('wa_campaign_recipients')->result();
        $stats = ['pending' => 0, 'queued' => 0, 'sending' => 0, 'sent' => 0, 'delivered' => 0, 'read' => 0, 'failed' => 0, 'replied' => 0, 'opted_out' => 0, 'total' => 0];
        foreach ($rows as $r) { $stats[$r->status] = (int)$r->c; $stats['total'] += (int)$r->c; }
        return $stats;
    }

    // ─── Message templates (minimal — full Module 6 builder lands in Phase 3) ─

    // Campaign creation captures message text directly rather than requiring a
    // pre-existing template pick; this persists it as a draft template so it's
    // already in place for Phase 3's real Meta-template mapping instead of being
    // thrown away.
    public function create_draft_template($name, $body_text) {
        preg_match_all('/\{\{(\w+)\}\}/', $body_text, $m);
        $this->db->insert('wa_message_templates', [
            'name'       => $name,
            'body_text'  => $body_text,
            'variables'  => json_encode(array_values(array_unique($m[1] ?? []))),
            'status'     => 'draft',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function get_template($id) {
        return $this->db->get_where('wa_message_templates', ['id' => $id])->row();
    }

    public function get_all_templates() {
        return $this->db->order_by('created_at', 'DESC')->get('wa_message_templates')->result();
    }

    public function update_template($id, array $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('wa_message_templates', $data);
    }

    // ─── Sending engine (Module 7) ─────────────────────────────────────────

    public function is_marketing_enabled() {
        $row = $this->db->where('key_name', 'whatsapp_marketing_enabled')->get('company_settings')->row();
        return $row && $row->key_value === '1';
    }

    public function get_pending_recipients($campaign_id, $limit = 5) {
        return $this->db->where('campaign_id', $campaign_id)->where('status', 'pending')
            ->order_by('id', 'ASC')->limit($limit)->get('wa_campaign_recipients')->result();
    }

    public function count_recipients_by_status($campaign_id, $status) {
        return $this->db->where('campaign_id', $campaign_id)->where('status', $status)
            ->count_all_results('wa_campaign_recipients');
    }

    // Flip to 'sending' before dispatch so a second overlapping poll (e.g. a
    // duplicate tab) can't grab the same rows — process_batch_ajax only ever
    // selects status='pending'.
    public function mark_recipients_sending(array $ids) {
        if (!$ids) return;
        $this->db->where_in('id', $ids)->update('wa_campaign_recipients', [
            'status' => 'sending', 'queued_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function mark_recipient_sent($id, $wa_message_id) {
        $this->db->where('id', $id)->update('wa_campaign_recipients', [
            'status' => 'sent', 'wa_message_id' => $wa_message_id,
            'sent_at' => date('Y-m-d H:i:s'), 'error_message' => null,
        ]);
    }

    public function mark_recipient_failed($id, $error) {
        $this->db->where('id', $id)->update('wa_campaign_recipients', [
            'status' => 'failed', 'error_message' => substr($error, 0, 255),
        ]);
    }

    // ─── Poster templates (Module 4) ───────────────────────────────────────

    public function get_poster_templates() {
        return $this->db->where('is_active', 1)->order_by('created_at', 'DESC')->get('wa_poster_templates')->result();
    }

    public function get_poster_template($id) {
        return $this->db->get_where('wa_poster_templates', ['id' => $id])->row();
    }

    public function create_poster_template($name, $category, $background_path) {
        $this->db->insert('wa_poster_templates', [
            'name' => $name, 'category' => $category, 'background_path' => $background_path,
            'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    // Soft-delete only — a generated poster's poster_path may still point at
    // this background, so the file and row both stay in place.
    public function delete_poster_template($id) {
        return $this->db->where('id', $id)->update('wa_poster_templates', ['is_active' => 0]);
    }

    public function set_campaign_poster($campaign_id, $path) {
        return $this->db->where('id', $campaign_id)->update('wa_campaigns', ['poster_path' => $path]);
    }

    // ─── Responses inbox (Module 9) ────────────────────────────────────────

    public function get_responses($status = null, $limit = 200) {
        $this->db->select('r.*, u.name AS assigned_name')
            ->from('wa_responses r')
            ->join('users u', 'u.id = r.assigned_to', 'left')
            ->order_by('r.received_at', 'DESC')
            ->limit($limit);
        if ($status) $this->db->where('r.status', $status);
        return $this->db->get()->result();
    }

    public function get_response($id) {
        return $this->db->select('r.*, u.name AS assigned_name')
            ->from('wa_responses r')->join('users u', 'u.id = r.assigned_to', 'left')
            ->where('r.id', $id)->get()->row();
    }

    public function update_response($id, array $data) {
        return $this->db->where('id', $id)->update('wa_responses', $data);
    }

    public function count_responses_by_status() {
        $rows = $this->db->select('status, COUNT(*) AS c')->group_by('status')->get('wa_responses')->result();
        $stats = ['new' => 0, 'assigned' => 0, 'converted' => 0, 'closed' => 0, 'total' => 0];
        foreach ($rows as $r) { $stats[$r->status] = (int)$r->c; $stats['total'] += (int)$r->c; }
        return $stats;
    }

    public function get_assignable_users() {
        return $this->db->select('id, name')->where('status', 1)->order_by('name', 'ASC')->get('users')->result();
    }

    // ─── Conversion attribution (Module 10) ────────────────────────────────

    // Called right after a billing invoice is successfully created (see
    // Billing::create()). Single-touch, most-recent-send attribution: credits
    // the sale to the newest WhatsApp send this customer received in the last
    // 30 days that hasn't already been credited to an earlier invoice — not
    // multi-touch modeling, and never overwrites an existing attribution.
    public function attribute_conversion($customer_id, $invoice_id) {
        if (!$customer_id) return false;
        $cutoff = date('Y-m-d H:i:s', strtotime('-30 days'));
        $row = $this->db->where('customer_id', $customer_id)
            ->where_in('status', ['sent', 'delivered', 'read', 'replied'])
            ->where('converted_invoice_id IS NULL', null, false)
            ->where('sent_at >=', $cutoff)
            ->order_by('sent_at', 'DESC')
            ->limit(1)
            ->get('wa_campaign_recipients')->row();
        if (!$row) return false;
        $this->db->where('id', $row->id)->update('wa_campaign_recipients', ['converted_invoice_id' => $invoice_id]);
        return true;
    }

    // ─── Analytics (Module 11) ─────────────────────────────────────────────

    public function get_campaign_comparison() {
        $this->db->select("c.id, c.name, c.campaign_type, c.status,
                COUNT(r.id) AS total_recipients,
                SUM(CASE WHEN r.status IN ('sent','delivered','read','replied') THEN 1 ELSE 0 END) AS sent_count,
                SUM(CASE WHEN r.status IN ('delivered','read') THEN 1 ELSE 0 END) AS delivered_count,
                SUM(CASE WHEN r.status = 'read' THEN 1 ELSE 0 END) AS read_count,
                SUM(CASE WHEN r.status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
                SUM(CASE WHEN r.status = 'replied' THEN 1 ELSE 0 END) AS replied_count,
                SUM(CASE WHEN r.converted_invoice_id IS NOT NULL THEN 1 ELSE 0 END) AS converted_count,
                COALESCE(SUM(i.total_amount), 0) AS revenue")
            ->from('wa_campaigns c')
            ->join('wa_campaign_recipients r', 'r.campaign_id = c.id', 'left')
            ->join('invoices i', 'i.id = r.converted_invoice_id', 'left')
            ->group_by('c.id')
            ->order_by('c.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_daily_send_trend($days = 30) {
        $cutoff = date('Y-m-d', strtotime("-{$days} days"));
        return $this->db->select("DATE(sent_at) AS day,
                SUM(CASE WHEN status IN ('sent','delivered','read','replied') THEN 1 ELSE 0 END) AS sent,
                SUM(CASE WHEN status IN ('delivered','read') THEN 1 ELSE 0 END) AS delivered,
                SUM(CASE WHEN status='read' THEN 1 ELSE 0 END) AS read_count,
                SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) AS failed")
            ->from('wa_campaign_recipients')
            ->where('sent_at IS NOT NULL', null, false)
            ->where('sent_at >=', $cutoff)
            ->group_by('DATE(sent_at)')
            ->order_by('day', 'ASC')
            ->get()->result();
    }

    // ─── Customer timeline (Module 12) ─────────────────────────────────────

    public function get_customer_wa_activity($customer_id, $limit = 20) {
        return $this->db->select('r.*, c.name AS campaign_name, c.campaign_type')
            ->from('wa_campaign_recipients r')
            ->join('wa_campaigns c', 'c.id = r.campaign_id')
            ->where('r.customer_id', $customer_id)
            ->order_by('r.id', 'DESC')
            ->limit($limit)
            ->get()->result();
    }
}
