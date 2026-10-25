<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OwnerApp — standalone mobile dashboard for store owner.
 * Has its own session key (owner_app_id) — does NOT use Auth_Controller.
 * Only user_role = 'owner' OR user ID in OWNER_IDS can log in.
 */
class OwnerApp extends CI_Controller {

    // Users allowed: either role='owner' in DB OR their ID is listed here
    private const OWNER_IDS = [1, 9999, 10000];

    public function __construct() {
        parent::__construct();
        $this->load->model(['Invoice_model', 'Customer_model']);
        $this->load->database();
        $this->load->library('session');
    }

    // ── helpers ──────────────────────────────────────────────────
    private function _logged_in()  { return (bool)$this->session->userdata('owner_app_id'); }
    private function _guard()      { if (!$this->_logged_in()) redirect('owner/login'); }
    private function _view($v, $d=[]){
        $d['_company'] = $this->_company();
        $this->load->view('owner_app/'.$v, $d);
    }
    private function _company() {
        $rows = $this->db->get('company_settings')->result();
        $out  = [];
        foreach ($rows as $r) $out[$r->key_name] = $r->key_value;
        return $out;
    }

    // ── LOGIN ─────────────────────────────────────────────────────
    public function login() {
        if ($this->_logged_in()) { redirect('owner/dashboard'); return; }

        $error = '';
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $phone    = trim($this->input->post('phone', TRUE));
            $password = $this->input->post('password', TRUE);

            $user = $this->db->where('username', $phone)->limit(1)->get('users')->row();
            if ($user && password_verify($password, $user->password) && $user->status == 1) {
                $is_owner = in_array((int)$user->id, self::OWNER_IDS) || ($user->role ?? '') === 'owner';
                if ($is_owner) {
                    $this->session->set_userdata([
                        'owner_app_id'   => $user->id,
                        'owner_app_name' => $user->name,
                    ]);
                    redirect('owner/dashboard');
                    return;
                }
                $error = 'Access denied. Owner account required.';
            } else {
                $error = 'Invalid phone / password.';
            }
        }
        $this->_view('login', ['error' => $error]);
    }

    public function logout() {
        $this->session->unset_userdata(['owner_app_id','owner_app_name']);
        redirect('owner/login');
    }

    // ── DASHBOARD ─────────────────────────────────────────────────
    public function dashboard() {
        $this->_guard();
        $today = date('Y-m-d');

        // Today sales by payment mode
        $modes = $this->db->query("
            SELECT payment_mode, COUNT(id) AS bills, SUM(paid_amount) AS amount
            FROM invoices
            WHERE DATE(invoice_date)=? AND status!='cancelled'
            GROUP BY payment_mode
        ", [$today])->result();

        $mode_map = [];
        foreach ($modes as $m) $mode_map[strtolower($m->payment_mode)] = $m;

        // Split payments breakdown for today
        $splits = $this->db->query("
            SELECT ip.payment_mode, COUNT(DISTINCT ip.invoice_id) AS bills, SUM(ip.amount) AS amount
            FROM invoice_payment_splits ip
            JOIN invoices i ON i.id=ip.invoice_id
            WHERE DATE(i.invoice_date)=? AND i.status!='cancelled' AND i.payment_mode='split'
            GROUP BY ip.payment_mode
        ", [$today])->result();
        foreach ($splits as $s) {
            $k = strtolower($s->payment_mode);
            if (!isset($mode_map[$k])) $mode_map[$k] = (object)['bills'=>0,'amount'=>0];
            $mode_map[$k]->amount = ($mode_map[$k]->amount ?? 0) + $s->amount;
        }

        // Refunds processed today reduce both the day's total and the
        // relevant payment-mode bucket (credit notes don't move real cash)
        $today_refunds = get_refund_summary_for_date($today);
        foreach (['cash','upi','card'] as $mode) {
            if (isset($mode_map[$mode]) && $today_refunds[$mode] > 0) {
                $mode_map[$mode]->amount -= $today_refunds[$mode];
            }
        }
        if (isset($mode_map['bank']) && $today_refunds['bank'] > 0) {
            $mode_map['bank']->amount -= $today_refunds['bank'];
        }

        // Today totals
        $today_row = $this->db->query("
            SELECT COUNT(id) AS bills, SUM(total_amount) AS sales, SUM(paid_amount) AS collected, SUM(due_amount) AS due
            FROM invoices WHERE DATE(invoice_date)=? AND status!='cancelled'
        ", [$today])->row();
        if ($today_row) $today_row->sales -= $today_refunds['total'];

        // This month
        $month_row = $this->db->query("
            SELECT COUNT(id) AS bills, SUM(total_amount) AS sales
            FROM invoices WHERE DATE_FORMAT(invoice_date,'%Y-%m')=? AND status!='cancelled'
        ", [date('Y-m')])->row();
        if ($month_row) $month_row->sales -= get_refund_summary(date('Y-m-01'), date('Y-m-d'))['total'];

        // Today purchases — this store uses the direct-entry "Purchases"
        // module, not the separate formal PO -> GRN workflow.
        $purchase_row = $this->db->query("
            SELECT COUNT(id) AS count, SUM(total_amount) AS amount
            FROM purchases WHERE invoice_date=? AND status!='draft'
        ", [$today])->row();

        // Hourly chart data for today
        $hourly = $this->db->query("
            SELECT HOUR(created_at) AS hr, SUM(total_amount) AS amt
            FROM invoices WHERE DATE(invoice_date)=? AND status!='cancelled'
            GROUP BY HOUR(created_at) ORDER BY hr
        ", [$today])->result();
        if ($today_refunds['total'] > 0) {
            $hourly_refunds = $this->db->query("
                SELECT HOUR(created_at) AS hr, SUM(refund_amount) AS amt
                FROM refunds WHERE DATE(created_at)=? AND status='processed' GROUP BY HOUR(created_at)
            ", [$today])->result_array();
            $hourly_refund_map = array_column($hourly_refunds, 'amt', 'hr');
            foreach ($hourly as $h) {
                $h->amt -= (float)($hourly_refund_map[$h->hr] ?? 0);
            }
        }

        // Recent bills (last 10)
        $recent = $this->db->query("
            SELECT i.invoice_no, i.total_amount, i.payment_mode, i.created_at, c.name AS customer
            FROM invoices i LEFT JOIN customers c ON c.id=i.customer_id
            WHERE DATE(i.invoice_date)=? AND i.status!='cancelled'
            ORDER BY i.id DESC LIMIT 10
        ", [$today])->result();

        // Today sales by outlet/location
        $by_location = $this->db->query("
            SELECT l.name AS location_name, l.code AS location_code,
                   COUNT(i.id) AS bills, SUM(i.total_amount) AS amount
            FROM invoices i
            LEFT JOIN locations l ON l.id = i.location_id
            WHERE DATE(i.invoice_date)=? AND i.status!='cancelled'
            GROUP BY i.location_id
            ORDER BY amount DESC
        ", [$today])->result();

        $this->_view('dashboard', compact('mode_map','today_row','month_row','purchase_row','hourly','recent','today','by_location'));
    }

    // ── SALES REPORT ──────────────────────────────────────────────
    public function sales() {
        $this->_guard();
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to   = $this->input->get('to')   ?: date('Y-m-d');

        $period_refunds = get_refund_summary($from, $to);

        $summary = $this->db->query("
            SELECT COUNT(id) AS bills, SUM(total_amount) AS gross, SUM(paid_amount) AS collected,
                   SUM(due_amount) AS due, SUM(total_gst) AS gst, SUM(discount) AS disc
            FROM invoices WHERE invoice_date BETWEEN ? AND ? AND status!='cancelled'
        ", [$from, $to])->row();
        if ($summary) $summary->gross -= $period_refunds['total'];

        $by_mode = $this->db->query("
            SELECT payment_mode, COUNT(id) AS bills, SUM(total_amount) AS amount
            FROM invoices WHERE invoice_date BETWEEN ? AND ? AND status!='cancelled'
            GROUP BY payment_mode ORDER BY amount DESC
        ", [$from, $to])->result();
        foreach ($by_mode as $bm) {
            $key = strtolower($bm->payment_mode);
            if (isset($period_refunds[$key])) $bm->amount -= $period_refunds[$key];
            elseif (in_array($key, ['neft','cheque'], true)) $bm->amount -= $period_refunds['bank'];
        }

        $daily = $this->db->query("
            SELECT invoice_date AS dt, COUNT(id) AS bills, SUM(total_amount) AS amount
            FROM invoices WHERE invoice_date BETWEEN ? AND ? AND status!='cancelled'
            GROUP BY invoice_date ORDER BY invoice_date DESC
        ", [$from, $to])->result();
        $daily_refunds = $this->db->query("
            SELECT DATE(created_at) AS dt, SUM(refund_amount) AS amt
            FROM refunds WHERE DATE(created_at) BETWEEN ? AND ? AND status='processed' GROUP BY DATE(created_at)
        ", [$from, $to])->result_array();
        $daily_refund_map = array_column($daily_refunds, 'amt', 'dt');
        foreach ($daily as $d) {
            $d->amount -= (float)($daily_refund_map[$d->dt] ?? 0);
        }

        $top_products = $this->db->query("
            SELECT p.name, SUM(ii.qty) AS qty, SUM(ii.qty*ii.rate) AS revenue
            FROM invoice_items ii
            JOIN products p ON p.id=ii.product_id
            JOIN invoices i ON i.id=ii.invoice_id
            WHERE i.invoice_date BETWEEN ? AND ? AND i.status!='cancelled'
            GROUP BY ii.product_id ORDER BY revenue DESC LIMIT 5
        ", [$from, $to])->result();

        $this->_view('sales', compact('from','to','summary','by_mode','daily','top_products'));
    }

    // ── PURCHASE REPORT ───────────────────────────────────────────
    public function purchases() {
        $this->_guard();
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to   = $this->input->get('to')   ?: date('Y-m-d');

        // Purchases here means the actual "Purchases" (direct-entry) module —
        // this store doesn't use the separate formal PO -> GRN workflow.
        $summary = $this->db->query("
            SELECT COUNT(id) AS orders, SUM(total_amount) AS gross, SUM(due_amount) AS due
            FROM purchases WHERE invoice_date BETWEEN ? AND ? AND status!='draft'
        ", [$from, $to])->row();

        $by_supplier = $this->db->query("
            SELECT s.name AS supplier, COUNT(pu.id) AS orders, SUM(pu.total_amount) AS amount
            FROM purchases pu JOIN suppliers s ON s.id=pu.supplier_id
            WHERE pu.invoice_date BETWEEN ? AND ? AND pu.status!='draft'
            GROUP BY pu.supplier_id ORDER BY amount DESC LIMIT 8
        ", [$from, $to])->result();

        $daily = $this->db->query("
            SELECT invoice_date AS dt, COUNT(id) AS orders, SUM(total_amount) AS amount
            FROM purchases WHERE invoice_date BETWEEN ? AND ? AND status!='draft'
            GROUP BY invoice_date ORDER BY dt DESC
        ", [$from, $to])->result();

        $this->_view('purchases', compact('from','to','summary','by_supplier','daily'));
    }

    // ── STOCK TRANSFER REPORT ───────────────────────────────────────
    public function transfers() {
        $this->_guard();
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to   = $this->input->get('to')   ?: date('Y-m-d');

        // Stock transfers have no value column of their own — a transfer's worth is
        // qty * rate on its line items, same as the Transfer Voucher screen computes it.
        $summary = $this->db->query("
            SELECT COUNT(DISTINCT st.id) AS transfers,
                   SUM(COALESCE(sti.approved_qty, sti.requested_qty) * sti.rate) AS value
            FROM stock_transfers st
            JOIN stock_transfer_items sti ON sti.transfer_id = st.id
            WHERE DATE(st.created_at) BETWEEN ? AND ? AND st.status != 'rejected'
        ", [$from, $to])->row();

        $pending = $this->db->query("SELECT COUNT(*) AS cnt FROM stock_transfers WHERE status = 'pending'")->row();

        // Transfer OUT by outlet — which outlet is sending stock out the most
        $by_outlet = $this->db->query("
            SELECT fl.name AS outlet, COUNT(DISTINCT st.id) AS transfers,
                   SUM(COALESCE(sti.approved_qty, sti.requested_qty) * sti.rate) AS value
            FROM stock_transfers st
            JOIN stock_transfer_items sti ON sti.transfer_id = st.id
            LEFT JOIN locations fl ON fl.id = st.from_location_id
            WHERE DATE(st.created_at) BETWEEN ? AND ? AND st.status != 'rejected'
            GROUP BY st.from_location_id ORDER BY value DESC
        ", [$from, $to])->result();

        $daily = $this->db->query("
            SELECT DATE(st.created_at) AS dt, COUNT(DISTINCT st.id) AS transfers,
                   SUM(COALESCE(sti.approved_qty, sti.requested_qty) * sti.rate) AS value
            FROM stock_transfers st
            JOIN stock_transfer_items sti ON sti.transfer_id = st.id
            WHERE DATE(st.created_at) BETWEEN ? AND ? AND st.status != 'rejected'
            GROUP BY DATE(st.created_at) ORDER BY dt DESC
        ", [$from, $to])->result();

        $this->_view('transfers', compact('from','to','summary','pending','by_outlet','daily'));
    }

    // ── LIVE STATS API (JSON) ─────────────────────────────────────
    public function live_stats() {
        $today = date('Y-m-d');
        $row = $this->db->query("
            SELECT COUNT(id) AS bills, SUM(total_amount) AS sales
            FROM invoices WHERE DATE(invoice_date)=? AND status!='cancelled'
        ", [$today])->row();
        $sales = (float)($row->sales ?? 0) - get_refund_summary_for_date($today)['total'];
        header('Content-Type: application/json');
        echo json_encode(['bills'=>(int)($row->bills??0), 'sales'=>$sales, 'time'=>date('h:i A')]);
    }
}
