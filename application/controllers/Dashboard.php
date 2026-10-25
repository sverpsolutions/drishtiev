<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Product_model', 'Invoice_model', 'Purchase_model', 'Customer_model', 'Report_model', 'Estimate_model', 'Location_model', 'Stock_transfer_model', 'Plu_model']);
    }

    public function index() {
        $data['page_title'] = 'Dashboard';

        // If user doesn't have sales dashboard permission → show basic welcome dashboard
        if (!$this->can_form('frm_dashboard')) {
            $data['name']       = $this->session->userdata('name');
            $data['role']       = $this->session->userdata('role');
            $data['form_perms'] = $this->session->userdata('form_perms') ?: [];
            $this->render('dashboard/basic', $data);
            return;
        }

        $today      = date('Y-m-d');
        $month_from = date('Y-m-01');
        $month_to   = date('Y-m-d');

        // Active location filter for queries
        $active_loc_id = $this->active_location_id();

        // ---- Summary Cards ----
        $data['total_products']  = $this->db->where('status',1)->count_all_results('products');
        $data['total_customers'] = $this->db->where('status',1)->count_all_results('customers');
        $data['total_suppliers'] = $this->db->where('status',1)->count_all_results('suppliers');

        // Low stock count
        $settings = $this->db->get('company_settings')->row_array();
        $low_thresh = (int)($settings['low_stock_threshold'] ?? 5);
        if ($active_loc_id) {
            $low_count_row = $this->db->query(
                "SELECT COUNT(*) as cnt FROM product_location_stock pls
                 JOIN products p ON p.id = pls.product_id
                 WHERE p.status=1 AND pls.location_id=? AND pls.stock_qty <= p.low_stock_threshold",
                [$active_loc_id]
            )->row();
        } else {
            $low_count_row = $this->db->query(
                "SELECT COUNT(*) as cnt FROM products WHERE status=1 AND stock_qty <= ?",
                [$low_thresh]
            )->row();
        }
        $data['low_stock_count'] = $low_count_row ? $low_count_row->cnt : 0;

        // ---- Pending transfers badge ----
        $data['pending_transfers'] = $this->Stock_transfer_model->get_pending_count($active_loc_id);

        // ---- Location context for view ----
        $data['is_owner']        = $this->is_owner();
        $data['active_location'] = $active_loc_id ? $this->Location_model->get_by_id($active_loc_id) : null;
        $data['all_locations']   = $this->Location_model->get_all(true);

        // Build location-filtered invoice WHERE clause (explicit int cast = SQL injection safety)
        $loc_where = $active_loc_id ? ' AND location_id = ' . (int)$active_loc_id : '';

        // ---- Today's Sales (location-filtered) ----
        // Refunds/sales_returns have no location_id, so they're netted only
        // out of the system-wide (all-locations) figures below.
        $today_refunds = get_refund_summary_for_date($today);
        $month_refunds = get_refund_summary($month_from, $month_to);

        $data['today_sales'] = ($this->db->query(
            "SELECT SUM(total_amount) as s FROM invoices WHERE DATE(invoice_date)=? AND status != 'cancelled'{$loc_where}",
            [$today]
        )->row()->s ?? 0) - ($active_loc_id ? 0 : $today_refunds['total']);

        // ---- Today's Invoice Count ----
        $data['today_invoice_count'] = $this->db->query(
            "SELECT COUNT(*) as c FROM invoices WHERE DATE(invoice_date)=? AND status != 'cancelled'{$loc_where}",
            [$today]
        )->row()->c ?? 0;

        // ---- Month Sales ----
        $data['month_sales'] = ($this->db->query(
            "SELECT SUM(total_amount) as s FROM invoices WHERE invoice_date >= ? AND invoice_date <= ? AND status != 'cancelled'{$loc_where}",
            [$month_from, $month_to]
        )->row()->s ?? 0) - ($active_loc_id ? 0 : $month_refunds['total']);

        // ---- Month Purchases ----
        $purch_loc_where = $active_loc_id ? ' AND location_id = ' . (int)$active_loc_id : '';
        $data['month_purchases'] = $this->db->query(
            "SELECT SUM(total_amount) as s FROM purchases WHERE invoice_date >= ? AND invoice_date <= ?{$purch_loc_where}",
            [$month_from, $month_to]
        )->row()->s ?? 0;

        // ---- Per-outlet stats (owner only) ----
        $data['outlet_sales_today']  = [];
        $data['outlet_month_stats']  = [];
        if ($this->is_owner()) {
            // Today per outlet
            $rows = $this->db->query(
                "SELECT l.id, l.name AS location_name, l.code, l.type,
                        SUM(i.total_amount) AS total, COUNT(i.id) AS invoice_count
                 FROM locations l
                 LEFT JOIN invoices i ON i.location_id = l.id
                     AND DATE(i.invoice_date) = ? AND i.status != 'cancelled'
                 WHERE l.status = 1
                 GROUP BY l.id ORDER BY l.sort_order, l.id",
                [$today]
            )->result();
            foreach ($rows as $r) {
                $data['outlet_sales_today'][$r->location_name] = $r->total ?? 0;
            }
            // Month per outlet
            $data['outlet_month_stats'] = $this->db->query(
                "SELECT l.id, l.name AS location_name, l.code, l.type,
                        COALESCE(SUM(i.total_amount), 0) AS month_sales,
                        COUNT(i.id) AS invoice_count,
                        COALESCE(SUM(i.total_amount - i.subtotal + i.total_amount), 0) AS dummy
                 FROM locations l
                 LEFT JOIN invoices i ON i.location_id = l.id
                     AND i.invoice_date >= ? AND i.invoice_date <= ? AND i.status != 'cancelled'
                 WHERE l.status = 1
                 GROUP BY l.id ORDER BY l.sort_order, l.id",
                [$month_from, $month_to]
            )->result();

            // Top products per outlet (this month)
            $data['outlet_top_products'] = $this->db->query(
                "SELECT l.name AS location_name, p.name AS product_name,
                        SUM(ii.qty) AS total_qty, SUM(ii.total) AS total_val
                 FROM invoice_items ii
                 JOIN invoices i ON i.id = ii.invoice_id
                 JOIN products p ON p.id = ii.product_id
                 JOIN locations l ON l.id = i.location_id
                 WHERE i.invoice_date >= ? AND i.invoice_date <= ? AND i.status != 'cancelled'
                 ORDER BY l.id, total_val DESC",
                [$month_from, $month_to]
            )->result();
        }

        // ---- Estimate Stats ----
        $est_stats = $this->Estimate_model->get_dashboard_stats($month_from, $month_to);
        $data['estimate_total_value'] = $est_stats['total_value'] ?? 0;
        $data['estimate_cash_value']  = $est_stats['cash_value'] ?? 0;
        $data['estimate_pending']     = $this->db->where('status','pending')->count_all_results('estimates');
        $data['estimate_cash_count']  = $est_stats['cash_count'] ?? 0;

        // ---- Profit (sales - cost from estimate closures + invoices) ----
        $invoice_profit = $this->db->query("
            SELECT SUM(ii.total - (ii.qty * p.cost_price)) as profit
            FROM invoice_items ii
            JOIN invoices inv ON inv.id = ii.invoice_id
            JOIN products p ON p.id = ii.product_id
            WHERE inv.invoice_date BETWEEN ? AND ? AND inv.status != 'cancelled'
        ", [$month_from, $month_to])->row()->profit ?? 0;

        $estimate_profit = $this->db->query("
            SELECT SUM(ei.total - (ei.qty * ei.cost_price)) as profit
            FROM estimate_items ei
            JOIN estimates e ON e.id = ei.estimate_id
            WHERE e.estimate_date BETWEEN ? AND ? AND e.status = 'closed_cash'
        ", [$month_from, $month_to])->row()->profit ?? 0;

        $data['month_profit'] = $invoice_profit + $estimate_profit;

        // ---- Top Items ----
        $data['top_items'] = $this->Report_model->get_top_selling_products($month_from, $month_to, 8);

        // ---- Low Stock Items ----
        $data['low_stock_items'] = $this->Report_model->get_low_stock($low_thresh);
        $data['low_stock_items'] = array_slice((array)$data['low_stock_items'], 0, 10);

        // ---- PLU Analytics for Dashboard ----
        $data['plu_stock_value']    = $this->Plu_model->total_stock_value();
        $data['plu_low_stock']      = count($this->Plu_model->get_low_stock(5));
        $data['plu_out_of_stock']   = count($this->Plu_model->get_out_of_stock());
        $data['plu_expiring_soon']  = count($this->Plu_model->get_expiring_soon(30));

        // ---- Recent Invoices (location-filtered) ----
        $qb = $this->db
            ->select('inv.id, inv.invoice_no, c.name as customer_name, inv.total_amount, inv.invoice_date, inv.status, l.name as location_name')
            ->from('invoices inv')
            ->join('customers c', 'c.id = inv.customer_id', 'left')
            ->join('locations l', 'l.id = inv.location_id', 'left');
        if ($active_loc_id) {
            $qb->where('inv.location_id', $active_loc_id);
        }
        $data['recent_invoices'] = $qb->order_by('inv.id', 'DESC')->limit(8)->get()->result();

        // ---- Recent Estimates ----
        $data['recent_estimates'] = $this->db
            ->select('id, estimate_no, customer_name, total_amount, estimate_date, status')
            ->order_by('id','DESC')->limit(5)->get('estimates')->result();

        // ---- Daily Sales (last 7 days) ----
        $data['daily_sales'] = $this->db->query("
            SELECT DATE(invoice_date) as day, SUM(total_amount) as total
            FROM invoices
            WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            AND status != 'cancelled'{$loc_where}
            GROUP BY DATE(invoice_date)
            ORDER BY day
        ")->result();
        if (!$active_loc_id) {
            $daily_refunds = $this->db->query("
                SELECT DATE(created_at) as day, SUM(refund_amount) as total
                FROM refunds WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status='processed'
                GROUP BY DATE(created_at)
            ")->result_array();
            $daily_refund_map = array_column($daily_refunds, 'total', 'day');
            foreach ($data['daily_sales'] as $row) {
                $row->total -= (float)($daily_refund_map[$row->day] ?? 0);
            }
        }

        // ---- Monthly Sales (last 6 months) ----
        $data['monthly_sales'] = $this->db->query("
            SELECT DATE_FORMAT(invoice_date,'%Y-%m') as month_key,
                   DATE_FORMAT(invoice_date,'%b %Y') as month_label,
                   SUM(total_amount) as total
            FROM invoices
            WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            AND status != 'cancelled'{$loc_where}
            GROUP BY month_key
            ORDER BY month_key
        ")->result();
        if (!$active_loc_id) {
            $monthly_refunds = $this->db->query("
                SELECT DATE_FORMAT(created_at,'%Y-%m') as month_key, SUM(refund_amount) as total
                FROM refunds WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND status='processed'
                GROUP BY month_key
            ")->result_array();
            $monthly_refund_map = array_column($monthly_refunds, 'total', 'month_key');
            foreach ($data['monthly_sales'] as $row) {
                $row->total -= (float)($monthly_refund_map[$row->month_key] ?? 0);
            }
        }

        $this->render('dashboard/index', $data);
    }
}
