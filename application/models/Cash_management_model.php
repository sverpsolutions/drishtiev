<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cash_management_model extends CI_Model {

    // ── Categories ──────────────────────────────────────────

    public function get_categories($type = null, $active_only = true) {
        if ($type) $this->db->where('type', $type);
        if ($active_only) $this->db->where('status', 1);
        return $this->db->order_by('sort_order')->order_by('name')->get('cash_categories')->result();
    }

    public function get_category($id) {
        return $this->db->where('id', (int)$id)->get('cash_categories')->row();
    }

    public function save_category($data, $id = null) {
        if ($id) {
            $this->db->where('id', (int)$id)->update('cash_categories', $data);
        } else {
            $this->db->insert('cash_categories', $data);
            $id = $this->db->insert_id();
        }
        return $id;
    }

    // ── Transactions (Pay-In / Pay-Out) ─────────────────────

    public function get_next_txn_no($type) {
        $prefix = ($type === 'pay_in') ? 'PI' : 'PO';
        $max = $this->db->select_max('id')->where('type', $type)->get('cash_transactions')->row()->id;
        return $prefix . '-' . str_pad(($max ?: 0) + 1, 4, '0', STR_PAD_LEFT);
    }

    public function save_transaction($data) {
        $this->db->insert('cash_transactions', $data);
        return $this->db->insert_id();
    }

    public function get_transaction($id) {
        return $this->db->where('id', (int)$id)->get('cash_transactions')->row();
    }

    public function void_transaction($id, $user_id, $reason) {
        $this->db->where('id', (int)$id)->update('cash_transactions', [
            'voided'      => 1,
            'voided_by'   => $user_id,
            'voided_at'   => date('Y-m-d H:i:s'),
            'void_reason' => $reason,
        ]);
    }

    public function get_transactions($location_id, $business_date, $shift_id = null, $type = null, $shift_times = null) {
        $this->db->select('cash_transactions.*, cash_categories.name AS category_name, u.name AS user_name')
            ->from('cash_transactions')
            ->join('cash_categories', 'cash_categories.id = cash_transactions.category_id', 'left')
            ->join('users u', 'u.id = cash_transactions.created_by', 'left')
            ->where('cash_transactions.voided', 0)
            ->where('cash_transactions.location_id', $location_id);
        if ($shift_id) {
            $this->_shift_filter('cash_transactions', $shift_id, $shift_times);
        } else {
            $this->db->where('cash_transactions.business_date', $business_date);
        }
        if ($type) $this->db->where('cash_transactions.type', $type);
        return $this->db->order_by('cash_transactions.created_at', 'ASC')->get()->result();
    }

    public function sum_transactions($location_id, $business_date, $type, $shift_id = null, $shift_times = null) {
        $this->db->select_sum('amount')
            ->where('voided', 0)
            ->where('location_id', $location_id)
            ->where('type', $type);
        if ($shift_id) {
            $this->_shift_filter('cash_transactions', $shift_id, $shift_times);
        } else {
            $this->db->where('business_date', $business_date);
        }
        return (float)($this->db->get('cash_transactions')->row()->amount ?? 0);
    }

    // ── Cash Sales (from invoices) ──────────────────────────

    public function get_cash_sales($location_id, $business_date, $shift_id = null) {
        $where_loc = $location_id ? "AND i.location_id = " . (int)$location_id : "";
        $where_shift = $shift_id ? "AND i.shift_id = " . (int)$shift_id : "";
        $where_date = $shift_id ? "" : "AND i.invoice_date = ?";
        $binds = $shift_id ? [] : [$business_date, $business_date];

        // Due collected later via Payments is added to invoices.paid_amount and is
        // already counted by get_cash_received() — take only what was paid at billing.
        $later_paid = $this->db->table_exists('payments')
            ? "(SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.type = 'received' AND p.ref_type = 'invoice' AND p.ref_id = i.id)"
            : "0";

        $sql = "
            SELECT COALESCE(SUM(cash_amount), 0) AS total FROM (
                SELECT GREATEST(i.paid_amount - $later_paid, 0) AS cash_amount
                FROM invoices i
                WHERE i.payment_mode = 'cash'
                  $where_date
                  AND i.status != 'cancelled'
                  $where_loc $where_shift

                UNION ALL

                SELECT ips.amount AS cash_amount
                FROM invoice_payment_splits ips
                JOIN invoices i ON i.id = ips.invoice_id
                WHERE ips.payment_mode = 'cash'
                  AND i.payment_mode = 'split'
                  $where_date
                  AND i.status != 'cancelled'
                  $where_loc $where_shift
            ) t
        ";
        return (float)$this->db->query($sql, $binds)->row()->total;
    }

    public function get_cash_refunds($location_id, $business_date, $shift_id = null, $shift_times = null) {
        if (!$this->db->table_exists('refunds')) return 0;
        $this->db->select_sum('r.refund_amount', 'refund_amount')
            ->from('refunds r')
            ->join('invoices i', 'i.id = r.invoice_id', 'left')
            ->where('r.refund_mode', 'cash')
            ->where('r.status', 'processed')
            ->where("(i.status IS NULL OR i.status != 'cancelled')", NULL, FALSE);
        if ($location_id) {
            $this->db->where('i.location_id', $location_id);
        }
        if ($shift_times && count($shift_times) >= 2) {
            $this->db->where('r.created_at >=', $shift_times[0]);
            $this->db->where('r.created_at <=', $shift_times[1]);
        } else {
            $this->db->where('r.created_at >=', $business_date . ' 00:00:00');
            $this->db->where('r.created_at <=', $business_date . ' 23:59:59');
        }
        $row = $this->db->get()->row();
        return (float)($row->refund_amount ?? 0);
    }

    public function get_cash_received($location_id, $business_date, $shift_id = null) {
        if (!$this->db->table_exists('payments')) return 0;
        $this->db->select_sum('amount')
            ->where('payment_mode', 'cash')
            ->where('type', 'received');
        if ($shift_id && $this->db->field_exists('shift_id', 'payments')) {
            $this->db->where('shift_id', $shift_id);
        } else {
            $this->db->where('payment_date', $business_date);
        }
        $row = $this->db->get('payments')->row();
        return (float)($row->amount ?? 0);
    }

    // ── Opening Cash ────────────────────────────────────────

    public function get_opening_cash($location_id, $business_date, $shift_id = null) {
        if (!$this->db->table_exists('cashier_shifts')) return 0;
        if ($shift_id) {
            $row = $this->db->select('opening_cash')
                ->where('id', $shift_id)
                ->get('cashier_shifts')->row();
            return (float)($row->opening_cash ?? 0);
        }
        $this->db->select_sum('cs.opening_cash', 'opening_cash')
            ->from('cashier_shifts cs')
            ->where('cs.business_date', $business_date);
        if ($location_id) {
            $this->db->join('users u', 'u.id = cs.cashier_id', 'left')
                ->group_start()
                    ->where('u.location_id', $location_id)
                    ->or_where('u.location_id IS NULL', null, false)
                ->group_end();
        }
        $row = $this->db->get()->row();
        return (float)($row->opening_cash ?? 0);
    }

    // ── Handovers ───────────────────────────────────────────

    public function get_next_handover_no() {
        $max = $this->db->select_max('id')->get('cash_handovers')->row()->id;
        return 'HO-' . str_pad(($max ?: 0) + 1, 4, '0', STR_PAD_LEFT);
    }

    public function save_handover($data) {
        $this->db->insert('cash_handovers', $data);
        return $this->db->insert_id();
    }

    public function get_handovers($location_id, $business_date, $shift_id = null, $shift_times = null) {
        $this->db->select('cash_handovers.*, fu.name AS from_user_name, tu.name AS to_user_name')
            ->from('cash_handovers')
            ->join('users fu', 'fu.id = cash_handovers.from_user_id', 'left')
            ->join('users tu', 'tu.id = cash_handovers.to_user_id', 'left')
            ->where('cash_handovers.location_id', $location_id)
            ->where('cash_handovers.status !=', 'cancelled');
        if ($shift_id) {
            $this->_shift_filter('cash_handovers', $shift_id, $shift_times);
        } else {
            $this->db->where('cash_handovers.business_date', $business_date);
        }
        return $this->db->order_by('cash_handovers.created_at', 'ASC')->get()->result();
    }

    public function sum_handovers($location_id, $business_date, $shift_id = null, $shift_times = null) {
        $this->db->select_sum('handover_amount')
            ->where('location_id', $location_id)
            ->where('status !=', 'cancelled');
        if ($shift_id) {
            $this->_shift_filter('cash_handovers', $shift_id, $shift_times);
        } else {
            $this->db->where('business_date', $business_date);
        }
        return (float)($this->db->get('cash_handovers')->row()->handover_amount ?? 0);
    }

    // ── Adjustments ─────────────────────────────────────────

    public function save_adjustment($data) {
        $this->db->insert('cash_adjustments', $data);
        return $this->db->insert_id();
    }

    public function get_adjustments($location_id, $business_date, $shift_id = null, $shift_times = null) {
        $this->db->select('cash_adjustments.*, u.name AS user_name, au.name AS approved_by_name')
            ->from('cash_adjustments')
            ->join('users u', 'u.id = cash_adjustments.created_by', 'left')
            ->join('users au', 'au.id = cash_adjustments.approved_by', 'left')
            ->where('cash_adjustments.location_id', $location_id);
        if ($shift_id) {
            $this->_shift_filter('cash_adjustments', $shift_id, $shift_times);
        } else {
            $this->db->where('cash_adjustments.business_date', $business_date);
        }
        return $this->db->order_by('cash_adjustments.created_at', 'ASC')->get()->result();
    }

    public function approve_adjustment($id, $user_id, $status = 'approved') {
        $this->db->where('id', (int)$id)->update('cash_adjustments', [
            'approval_status' => $status,
            'approved_by'     => $user_id,
            'approved_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    // ── Cash Summary (the core formula) ─────────────────────

    public function get_cash_summary($location_id, $business_date, $shift_id = null, $shift_times = null) {
        $opening     = $this->get_opening_cash($location_id, $business_date, $shift_id);
        $cash_sales  = $this->get_cash_sales($location_id, $business_date, $shift_id);
        $cash_refunds= $this->get_cash_refunds($location_id, $business_date, $shift_id, $shift_times);
        $cash_recv   = $this->get_cash_received($location_id, $business_date, $shift_id);
        $pay_in      = $this->sum_transactions($location_id, $business_date, 'pay_in', $shift_id, $shift_times);
        $pay_out     = $this->sum_transactions($location_id, $business_date, 'pay_out', $shift_id, $shift_times);
        $handovers   = $this->sum_handovers($location_id, $business_date, $shift_id, $shift_times);

        $approved_adj = 0;
        foreach ($this->get_adjustments($location_id, $business_date, $shift_id, $shift_times) as $adj) {
            if ($adj->approval_status !== 'approved') continue;
            $approved_adj += ($adj->type === 'excess') ? $adj->amount : -$adj->amount;
        }

        $expected = $opening + $cash_sales + $cash_recv + $pay_in - $pay_out - $cash_refunds;

        return [
            'opening_cash'  => $opening,
            'cash_sales'    => $cash_sales,
            'cash_received' => $cash_recv,
            'cash_refunds'  => $cash_refunds,
            'pay_in'        => $pay_in,
            'pay_out'       => $pay_out,
            'expected_cash' => $expected,
            'handovers'     => $handovers,
            'adjustments'   => $approved_adj,
            'carry_forward' => $expected - $handovers + $approved_adj,
        ];
    }

    // ── Sales breakdown (all payment modes) ─────────────────

    public function get_sales_breakdown($location_id, $business_date, $shift_id = null) {
        $where_loc = $location_id ? "AND i.location_id = " . (int)$location_id : "";
        $where_shift = $shift_id ? "AND i.shift_id = " . (int)$shift_id : "";
        $where_date = $shift_id ? "" : "AND i.invoice_date = ?";
        $binds = $shift_id ? [] : [$business_date];

        $sql = "
            SELECT
                COUNT(*) AS total_bills,
                COALESCE(SUM(i.total_amount), 0) AS total_sales,
                COALESCE(SUM(CASE WHEN i.payment_mode='cash' THEN i.paid_amount ELSE 0 END), 0) AS cash_sales,
                COALESCE(SUM(CASE WHEN i.payment_mode='upi' THEN i.paid_amount ELSE 0 END), 0) AS upi_sales,
                COALESCE(SUM(CASE WHEN i.payment_mode='card' THEN i.paid_amount ELSE 0 END), 0) AS card_sales,
                COALESCE(SUM(CASE WHEN i.payment_mode IN('neft','cheque') THEN i.paid_amount ELSE 0 END), 0) AS bank_sales,
                COALESCE(SUM(CASE WHEN i.payment_mode='credit' THEN i.total_amount ELSE 0 END), 0) AS credit_sales,
                COALESCE(SUM(CASE WHEN i.payment_mode='split' THEN i.paid_amount ELSE 0 END), 0) AS split_total
            FROM invoices i
            WHERE i.status != 'cancelled'
              $where_date
              $where_loc $where_shift
        ";
        $row = $this->db->query($sql, $binds)->row_array();

        if ((float)$row['split_total'] > 0) {
            $sql2 = "
                SELECT
                    COALESCE(SUM(CASE WHEN ips.payment_mode='cash' THEN ips.amount ELSE 0 END), 0) AS split_cash,
                    COALESCE(SUM(CASE WHEN ips.payment_mode='upi' THEN ips.amount ELSE 0 END), 0) AS split_upi,
                    COALESCE(SUM(CASE WHEN ips.payment_mode='card' THEN ips.amount ELSE 0 END), 0) AS split_card,
                    COALESCE(SUM(CASE WHEN ips.payment_mode IN('neft','cheque') THEN ips.amount ELSE 0 END), 0) AS split_bank,
                    COALESCE(SUM(CASE WHEN ips.payment_mode='credit' THEN ips.amount ELSE 0 END), 0) AS split_credit
                FROM invoice_payment_splits ips
                JOIN invoices i ON i.id = ips.invoice_id
                WHERE i.payment_mode = 'split'
                  $where_date
                  AND i.status != 'cancelled'
                  $where_loc $where_shift
            ";
            $splits = $this->db->query($sql2, $binds)->row_array();
            $row['cash_sales'] = (float)$row['cash_sales'] + (float)$splits['split_cash'];
            $row['upi_sales']  = (float)$row['upi_sales']  + (float)$splits['split_upi'];
            $row['card_sales'] = (float)$row['card_sales']  + (float)$splits['split_card'];
            $row['bank_sales'] = (float)$row['bank_sales']  + (float)$splits['split_bank'];
            $row['credit_sales'] = (float)$row['credit_sales'] + (float)$splits['split_credit'];
        }

        return $row;
    }

    // ── Full Ledger (chronological entries) ──────────────────

    public function get_ledger_entries($location_id, $business_date, $shift_id = null, $shift_times = null) {
        $entries = [];
        $where_loc = $location_id ? "AND location_id = " . (int)$location_id : "";

        $opening = $this->get_opening_cash($location_id, $business_date, $shift_id);
        $entries[] = (object)[
            'time' => $business_date . ' 00:00:00',
            'type' => 'opening',
            'label' => 'Opening Cash',
            'description' => 'Shift opening float',
            'in_amount' => $opening,
            'out_amount' => 0,
        ];

        foreach ($this->get_transactions($location_id, $business_date, $shift_id, null, $shift_times) as $t) {
            $entries[] = (object)[
                'time' => $t->created_at,
                'type' => $t->type,
                'label' => ($t->type === 'pay_in' ? 'Pay-In' : 'Pay-Out') . ' #' . $t->txn_no,
                'description' => trim(($t->category_name ?? '') . ' — ' . ($t->description ?? ''), ' —'),
                'in_amount' => $t->type === 'pay_in' ? (float)$t->amount : 0,
                'out_amount' => $t->type === 'pay_out' ? (float)$t->amount : 0,
                'id' => $t->id,
                'ref' => $t->txn_no,
            ];
        }

        foreach ($this->get_handovers($location_id, $business_date, $shift_id, $shift_times) as $h) {
            $entries[] = (object)[
                'time' => $h->created_at,
                'type' => 'handover',
                'label' => 'Cash Handover #' . $h->handover_no,
                'description' => ($h->from_user_name ?? '?') . ' → ' . ($h->to_user_name ?? '?'),
                'in_amount' => 0,
                'out_amount' => (float)$h->handover_amount,
                'id' => $h->id,
                'ref' => $h->handover_no,
            ];
        }

        usort($entries, function($a, $b) { return strcmp($a->time, $b->time); });
        return $entries;
    }

    // ── Ledger report with date range ───────────────────────

    public function get_transactions_range($location_id, $from_date, $to_date, $type = null) {
        $this->db->select('cash_transactions.*, cash_categories.name AS category_name, u.name AS user_name, l.name AS location_name')
            ->from('cash_transactions')
            ->join('cash_categories', 'cash_categories.id = cash_transactions.category_id', 'left')
            ->join('users u', 'u.id = cash_transactions.created_by', 'left')
            ->join('locations l', 'l.id = cash_transactions.location_id', 'left')
            ->where('cash_transactions.voided', 0)
            ->where('cash_transactions.business_date >=', $from_date)
            ->where('cash_transactions.business_date <=', $to_date);
        if ($location_id) $this->db->where('cash_transactions.location_id', $location_id);
        if ($type) $this->db->where('cash_transactions.type', $type);
        return $this->db->order_by('cash_transactions.business_date', 'ASC')
            ->order_by('cash_transactions.created_at', 'ASC')
            ->get()->result();
    }

    private function _shift_filter($table, $shift_id, $shift_times = null) {
        if (!$shift_times || count($shift_times) < 2) {
            $this->db->where("$table.shift_id", $shift_id);
            return;
        }
        $this->db->group_start()
            ->where("$table.shift_id", $shift_id)
            ->or_group_start()
                ->where("$table.shift_id IS NULL", null, false)
                ->where("$table.created_at >=", $shift_times[0])
                ->where("$table.created_at <=", $shift_times[1])
            ->group_end()
        ->group_end();
    }

    public function get_handovers_range($location_id, $from_date, $to_date) {
        $this->db->select('cash_handovers.*, fu.name AS from_user_name, tu.name AS to_user_name, l.name AS location_name')
            ->from('cash_handovers')
            ->join('users fu', 'fu.id = cash_handovers.from_user_id', 'left')
            ->join('users tu', 'tu.id = cash_handovers.to_user_id', 'left')
            ->join('locations l', 'l.id = cash_handovers.location_id', 'left')
            ->where('cash_handovers.business_date >=', $from_date)
            ->where('cash_handovers.business_date <=', $to_date)
            ->where('cash_handovers.status !=', 'cancelled');
        if ($location_id) $this->db->where('cash_handovers.location_id', $location_id);
        return $this->db->order_by('cash_handovers.created_at', 'ASC')->get()->result();
    }
}
