<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loyalty dashboard, customer statement, reports, manual adjustment and the
 * expiry sweep.
 *
 * Manual credit/debit and rule changes are admin-only — a cashier must not be
 * able to mint points (§22, §36).
 */
class Loyalty extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Loyalty_model', 'Invoice_model']);
        if (!$this->Loyalty_model->schema_ready()) {
            $this->session->set_flashdata('error',
                'Loyalty tables are not installed on this database. Run sql/45_loyalty.sql first.');
            redirect('dashboard');
        }
        if (!$this->can_module('mod_loyalty')) {
            $this->session->set_flashdata('error', 'You do not have access to the Loyalty module.');
            redirect('dashboard');
        }
    }

    private function json($p) {
        $this->output->set_content_type('application/json')->set_output(json_encode($p));
    }

    // ── Dashboard (§29) ─────────────────────────────────────────────────────
    public function index() {
        $today = date('Y-m-d');
        $c = [];
        $c['members']       = (int)$this->db->where('loyalty_member', 1)->count_all_results('customers');
        $c['active']        = (int)$this->db->where('loyalty_member', 1)->where('loyalty_status', 'active')->count_all_results('customers');
        $c['outstanding']   = (float)($this->db->select_sum('loyalty_points', 't')->where('loyalty_member', 1)->get('customers')->row()->t ?? 0);
        $c['issued']        = (float)($this->db->select_sum('loyalty_lifetime_earned', 't')->get('customers')->row()->t ?? 0);
        $c['redeemed']      = (float)($this->db->select_sum('loyalty_lifetime_redeemed', 't')->get('customers')->row()->t ?? 0);
        $c['expired']       = (float)($this->db->select_sum('loyalty_lifetime_expired', 't')->get('customers')->row()->t ?? 0);
        $c['expiring_30']   = (float)($this->db->query(
            "SELECT COALESCE(SUM(points - reversed_points - expired_points),0) t
               FROM loyalty_transactions
              WHERE txn_type='EARN' AND expiry_date IS NOT NULL
                AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND points > (reversed_points + expired_points)")->row()->t ?? 0);
        $c['non_eligible']  = (int)$this->db->where('allow_loyalty_points', 0)->count_all_results('products');

        // What the outstanding points would cost if every member redeemed today.
        $scheme = $this->Loyalty_model->active_scheme();
        $c['liability'] = $c['outstanding'] * ($scheme ? (float)$scheme->redeem_value_per_point : 1);

        $this->render('loyalty/dashboard', [
            'page_title' => 'Loyalty Dashboard',
            'c'          => $c,
            'scheme'     => $scheme,
            'top_customers' => $this->Loyalty_model->report('top_customers', ['limit' => 10]),
            'top_items'     => $this->Loyalty_model->report('itemwise', ['limit' => 10]),
        ]);
    }

    // ── Rule setup (§3, §16) ────────────────────────────────────────────────
    /**
     * Edits the active loyalty rule. Admin-only: changing the earn rate or the
     * redemption cap has a direct money consequence (§36).
     *
     * Existing points are NOT recalculated — §33 requires history to stand, so
     * a new rate only applies to bills raised after the change.
     */
    public function settings() {
        if (!$this->is_admin()) {
            $this->session->set_flashdata('error', 'Manager or admin permission is required to change loyalty rules.');
            redirect('loyalty');
        }

        $scheme = $this->db->where('id', 1)->get('loyalty_schemes')->row();
        if (!$scheme) {   // first run on a database where the default row is missing
            $this->db->insert('loyalty_schemes', ['id' => 1, 'name' => 'Default Loyalty Rule']);
            $scheme = $this->db->where('id', 1)->get('loyalty_schemes')->row();
        }

        if ($this->input->method() !== 'post') {
            $this->render('loyalty/settings', [
                'page_title' => 'Loyalty Setup',
                'scheme'     => $scheme,
                'members'    => (int)$this->db->where('loyalty_member', 1)->count_all_results('customers'),
                'outstanding'=> (float)($this->db->select_sum('loyalty_points', 't')
                                    ->where('loyalty_member', 1)->get('customers')->row()->t ?? 0),
            ]);
            return;
        }

        $calc = $this->input->post('calc_type', TRUE);
        if (!in_array($calc, ['amount_per_point', 'points_per_amount', 'fixed_per_unit'], true)) {
            $calc = 'amount_per_point';
        }
        $round = $this->input->post('round_mode', TRUE);
        if (!in_array($round, ['floor', 'round', 'ceil'], true)) $round = 'floor';

        $new = [
            'name'                   => $this->input->post('name', TRUE) ?: 'Default Loyalty Rule',
            'calc_type'              => $calc,
            // A zero rate would divide by zero and award nothing — hold the floor at 1.
            'amount_per_point'       => max(1, (float)$this->input->post('amount_per_point', TRUE)),
            'points_per_amount'      => max(0, (float)$this->input->post('points_per_amount', TRUE)),
            'fixed_points'           => max(0, (float)$this->input->post('fixed_points', TRUE)),
            'redeem_value_per_point' => max(0.01, (float)$this->input->post('redeem_value_per_point', TRUE)),
            'min_bill_amount'        => max(0, (float)$this->input->post('min_bill_amount', TRUE)),
            'min_redeem_points'      => max(0, (float)$this->input->post('min_redeem_points', TRUE)),
            'max_redeem_points'      => max(0, (float)$this->input->post('max_redeem_points', TRUE)),
            'max_redeem_percent'     => min(100, max(0, (float)$this->input->post('max_redeem_percent', TRUE))),
            'expiry_days'            => max(0, (int)$this->input->post('expiry_days', TRUE)),
            'tier_multiplier'        => max(0, (float)$this->input->post('tier_multiplier', TRUE)) ?: 1,
            'round_mode'             => $round,
            'is_active'              => $this->input->post('is_active') ? 1 : 0,
        ];

        // Audit every field that actually moved (§37).
        $labels = [
            'calc_type' => 'Calculation', 'amount_per_point' => '₹ per point',
            'points_per_amount' => 'Points per ₹', 'fixed_points' => 'Fixed points',
            'redeem_value_per_point' => 'Redeem value', 'min_bill_amount' => 'Min bill',
            'min_redeem_points' => 'Min redeem', 'max_redeem_points' => 'Max redeem pts',
            'max_redeem_percent' => 'Max redeem %', 'expiry_days' => 'Expiry days',
            'tier_multiplier' => 'Tier multiplier', 'round_mode' => 'Rounding', 'is_active' => 'Active',
        ];
        $changes = [];
        foreach ($labels as $f => $label) {
            if ((string)$new[$f] !== (string)$scheme->$f) {
                $changes[] = $label . ': ' . $scheme->$f . ' → ' . $new[$f];
            }
        }

        $this->db->where('id', 1)->update('loyalty_schemes', $new);

        if ($changes) {
            $this->log_activity('loyalty', 'rule_change',
                'Loyalty rule changed — ' . implode(', ', $changes), 1, 'loyalty_scheme');
        }
        $this->session->set_flashdata('success',
            $changes ? 'Loyalty rule updated. Existing points are unchanged — the new rule applies to future bills only.'
                     : 'No changes made.');
        redirect('loyalty/settings');
    }

    // ── Customer statement (§27) ────────────────────────────────────────────
    public function statement($customer_id = null) {
        $q = trim((string)$this->input->get('q', TRUE));
        $cust = $customer_id ? $this->db->where('id', (int)$customer_id)->get('customers')->row()
                             : ($q !== '' ? $this->Loyalty_model->find_customer($q) : null);

        $this->render('loyalty/statement', [
            'page_title' => 'Customer Loyalty Statement',
            'q'          => $q,
            'customer'   => $cust,
            'rows'       => $cust ? $this->Loyalty_model->statement($cust->id,
                                $this->input->get('from', TRUE), $this->input->get('to', TRUE)) : [],
            'ledger_bal' => $cust ? $this->Loyalty_model->ledger_balance($cust->id) : 0,
        ]);
    }

    // ── Earned vs Redeemed, one report with two views ───────────────────────
    public function earn_redeem() {
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to', TRUE)   ?: date('Y-m-d');
        $mode = $this->input->get('mode', TRUE) === 'detail' ? 'detail' : 'summary';

        $data = $this->Loyalty_model->earn_redeem_report($from, $to);
        $scheme = $this->Loyalty_model->active_scheme();

        if ($this->input->get('export', TRUE)) {
            $t = $data['totals'];
            $headers = []; $body = [];

            // Summary exports the totals block only — no customer names, by design.
            if ($mode === 'detail') {
                $headers = ['Customer', 'Mobile', 'Tier', 'Points Earned', 'Points Redeemed',
                            'Manual +', 'Manual -', 'Expired', 'Reversed', 'Net', 'Balance Now'];
                foreach ($data['rows'] as $r) {
                    $body[] = [$r->customer_name, (string)$r->phone, $r->loyalty_tier,
                        (float)$r->earned, (float)$r->redeemed, (float)$r->manual_credit,
                        (float)$r->manual_debit, (float)$r->expired, (float)$r->reversed,
                        (float)$r->earned + $r->manual_credit - $r->redeemed - $r->manual_debit
                            - $r->expired - $r->reversed,
                        (float)$r->balance];
                }
            }

            $this->load->library('Excel_report');
            $this->excel_report->download('Loyalty Points - Earned vs Redeemed', $headers, $body, [
                'subtitle' => 'Period: ' . date('d M Y', strtotime($from)) . ' to ' . date('d M Y', strtotime($to))
                            . '   |   ' . ucfirst($mode) . ' view',
                'summary_title' => 'Period Totals',
                'summary' => [
                    'TOTAL POINTS EARNED'                => (float)$t->earned,
                    'TOTAL POINTS REDEEMED'              => (float)$t->redeemed,
                    'Manual Credit'                      => (float)$t->manual_credit,
                    'Manual Debit'                       => (float)$t->manual_debit,
                    'Expired'                            => (float)$t->expired,
                    'Reversed (returns / cancellations)'  => (float)$t->reversed,
                    'NET MOVEMENT'                       => (float)$t->net,
                    'Customers with activity'            => (int)$t->customers,
                    'Transactions'                       => (int)($t->txns ?? 0),
                ],
                'highlight' => ['TOTAL POINTS EARNED', 'TOTAL POINTS REDEEMED', 'NET MOVEMENT'],
                'filename'  => 'loyalty_earned_vs_redeemed_' . $mode . '_' . $from . '_to_' . $to,
            ]);
            return;
        }

        $this->render('loyalty/earn_redeem', [
            'page_title' => 'Points Earned vs Redeemed',
            'from' => $from, 'to' => $to, 'mode' => $mode,
            'rows' => $data['rows'], 'totals' => $data['totals'],
            'rate' => $scheme ? (float)$scheme->redeem_value_per_point : 1,
        ]);
    }

    // ── Reports (§27) ───────────────────────────────────────────────────────
    public function reports($type = null) {
        $meta = $type ? $this->Loyalty_model->report_meta($type) : null;
        $f = [
            'from'  => $this->input->get('from', TRUE),
            'to'    => $this->input->get('to', TRUE),
            'days'  => $this->input->get('days', TRUE),
            'q'     => $this->input->get('q', TRUE),
            'limit' => 1000,
        ];
        $this->render('loyalty/reports', [
            'page_title' => $meta ? $meta[0] : 'Loyalty Reports',
            'type'       => $type,
            'meta'       => $meta,
            'rows'       => $meta ? $this->Loyalty_model->report($type, $f) : [],
            'groups'     => $this->Loyalty_model->report_types(),
            'f'          => $f,
        ]);
    }

    public function report_export($type = null) {
        $meta = $type ? $this->Loyalty_model->report_meta($type) : null;
        if (!$meta) show_404();
        $rows = $this->Loyalty_model->report($type, [
            'from' => $this->input->get('from', TRUE), 'to' => $this->input->get('to', TRUE),
            'days' => $this->input->get('days', TRUE), 'q' => $this->input->get('q', TRUE),
            'limit' => 20000,
        ]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="loyalty_' . $type . '_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array_values($meta[1]));
        foreach ($rows as $r) {
            $line = [];
            foreach (array_keys($meta[1]) as $k) $line[] = $r->$k ?? '';
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }

    // ── Manual adjustment (§22) ─────────────────────────────────────────────
    public function adjust() {
        if (!$this->is_admin()) {
            $this->session->set_flashdata('error', 'Manager or admin permission is required to adjust points.');
            redirect('loyalty');
        }
        if ($this->input->method() !== 'post') {
            $this->render('loyalty/adjust', ['page_title' => 'Manual Point Adjustment']);
            return;
        }

        $cust   = $this->Loyalty_model->find_customer($this->input->post('customer', TRUE));
        $points = (float)$this->input->post('points', TRUE);
        $dir    = $this->input->post('direction', TRUE) === 'debit' ? 'debit' : 'credit';
        $reason = trim((string)$this->input->post('reason', TRUE));

        if (!$cust)              { $this->session->set_flashdata('error', 'Customer not found.'); redirect('loyalty/adjust'); }
        if ($points <= 0)        { $this->session->set_flashdata('error', 'Enter a point value greater than zero.'); redirect('loyalty/adjust'); }
        if ($reason === '')      { $this->session->set_flashdata('error', 'A reason is required for a manual adjustment.'); redirect('loyalty/adjust'); }

        $ok = $this->Loyalty_model->adjust_points($cust->id, $points, $dir,
            $dir === 'credit' ? 'MANUAL_CREDIT' : 'MANUAL_DEBIT',
            ['reference_type' => 'manual', 'remarks' => $reason]);

        if (!$ok) {
            $this->session->set_flashdata('error',
                'Adjustment refused — a debit cannot take the balance below zero (current: '
                . (int)$this->Loyalty_model->balance($cust->id) . ').');
            redirect('loyalty/adjust');
        }

        $this->log_activity('loyalty', 'manual_' . $dir,
            ($dir === 'credit' ? '+' : '-') . (int)$points . ' point(s) for "' . $cust->name
            . '" — ' . $reason, $cust->id, $cust->name);
        $this->session->set_flashdata('success',
            ($dir === 'credit' ? 'Credited ' : 'Debited ') . (int)$points . ' point(s). New balance: '
            . (int)$this->Loyalty_model->balance($cust->id));
        redirect('loyalty/statement/' . $cust->id);
    }

    // ── Expiry sweep (§20) ──────────────────────────────────────────────────
    // Idempotent, so it is safe to run from a cron or by hand.
    public function run_expiry() {
        if (!$this->is_admin()) {
            $this->session->set_flashdata('error', 'Admin permission is required to run the expiry sweep.');
            redirect('loyalty');
        }
        $res = $this->Loyalty_model->expire_due();
        $this->log_activity('loyalty', 'expiry',
            (int)$res['points'] . ' point(s) expired across ' . $res['customers'] . ' customer(s)');
        $this->session->set_flashdata('success',
            $res['points'] > 0
                ? (int)$res['points'] . ' point(s) expired across ' . $res['customers'] . ' customer(s).'
                : 'Nothing was due to expire.');
        redirect('loyalty');
    }

    // ── Billing support ─────────────────────────────────────────────────────
    /** Points + redemption cap for the customer picked at the till (§10). */
    public function customer_ajax() {
        $cust = $this->Loyalty_model->find_customer($this->input->post('q', TRUE));
        if (!$cust) { $this->json(['ok' => false, 'error' => 'Customer not found.']); return; }

        $bill   = (float)$this->input->post('bill_amount', TRUE);
        $scheme = $this->Loyalty_model->active_scheme($cust->loyalty_tier ?? null);
        $max    = $this->Loyalty_model->max_redeemable($cust, $bill, $scheme);

        $this->json([
            'ok'             => true,
            'customer_id'    => (int)$cust->id,
            'name'           => $cust->name,
            'is_member'      => (int)$cust->loyalty_member === 1,
            'tier'           => $cust->loyalty_tier,
            'points'         => (float)$cust->loyalty_points,
            'max_points'     => $max,
            'max_value'      => $this->Loyalty_model->points_to_money($max, $scheme),
            'rate'           => $scheme ? (float)$scheme->redeem_value_per_point : 1,
            'max_percent'    => $scheme ? (float)$scheme->max_redeem_percent : 0,
        ]);
    }

    /** Enrol a customer as a loyalty member from the till (§11). */
    public function enrol_ajax() {
        $id = (int)$this->input->post('customer_id', TRUE);
        $cust = $id ? $this->db->where('id', $id)->get('customers')->row() : null;
        if (!$cust) { $this->json(['ok' => false, 'error' => 'Customer not found.']); return; }
        if ((int)$cust->loyalty_member === 1) { $this->json(['ok' => true, 'already' => true]); return; }

        $this->db->where('id', $id)->update('customers', [
            'loyalty_member'      => 1,
            'loyalty_status'      => 'active',
            'loyalty_enrolled_at' => date('Y-m-d H:i:s'),
        ]);
        $this->log_activity('loyalty', 'enrol', 'Enrolled "' . $cust->name . '" as a loyalty member', $id, $cust->name);
        $this->json(['ok' => true, 'enrolled' => true]);
    }
}
