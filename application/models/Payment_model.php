<?php
// ============================================================
// FILE: application/models/Payment_model.php
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_model extends CI_Model {

    private $table = 'payments';

    public function get_all() {
        return $this->db
            ->select('payments.*, users.name as created_by_name')
            ->join('users', 'users.id = payments.created_by')
            ->order_by('payments.id', 'DESC')
            ->get($this->table)
            ->result();
    }

    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row();
    }

    public function get_by_party($party_type, $party_id) {
        return $this->db
            ->where('party_type', $party_type)
            ->where('party_id',   $party_id)
            ->order_by('payment_date', 'DESC')
            ->get($this->table)
            ->result();
    }

    public function total_by_type($type) {
        $row = $this->db->select_sum('amount')->where('type', $type)->get($this->table)->row();
        return $row->amount ?? 0;
    }

    public function monthly_summary($year = null) {
        $year = $year ?? date('Y');
        return $this->db->query("
            SELECT
                MONTH(payment_date) AS month,
                SUM(CASE WHEN type='received' THEN amount ELSE 0 END) AS received,
                SUM(CASE WHEN type='paid'     THEN amount ELSE 0 END) AS paid
            FROM payments
            WHERE YEAR(payment_date) = ?
            GROUP BY MONTH(payment_date)
            ORDER BY month ASC
        ", [$year])->result();
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function generate_payment_no() {
        $prefix = 'PAY';
        $year   = date('Y');
        $last   = $this->db
            ->like('payment_no', $prefix . '-' . $year, 'after')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();
        $seq = $last ? ((int)substr($last->payment_no, -4) + 1) : 1;
        return $prefix . '-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------------
    // Cheque printing
    // -------------------------------------------------------
    public function get_cheque_print_data($id) {
        $p = $this->db->where('id', $id)->get($this->table)->row();
        if (!$p) return null;

        $p->party_name = '';
        if ($p->party_type === 'supplier') {
            $s = $this->db->select('name')->where('id', $p->party_id)->get('suppliers')->row();
            $p->party_name = $s->name ?? '';
        } elseif ($p->party_type === 'customer') {
            $c = $this->db->select('name')->where('id', $p->party_id)->get('customers')->row();
            $p->party_name = $c->name ?? '';
        }

        $p->bank = !empty($p->bank_account_id)
            ? $this->db->where('id', $p->bank_account_id)->get('bank_accounts')->row()
            : null;

        return $p;
    }

    public function get_cheque_layout($bank_account_id) {
        return $this->db->where('bank_account_id', $bank_account_id)->get('cheque_print_layouts')->row();
    }

    public function save_cheque_layout($bank_account_id, array $fields) {
        $existing = $this->get_cheque_layout($bank_account_id);
        if ($existing) {
            $this->db->where('bank_account_id', $bank_account_id)->update('cheque_print_layouts', $fields);
        } else {
            $fields['bank_account_id'] = $bank_account_id;
            $this->db->insert('cheque_print_layouts', $fields);
        }
    }
}

// ============================================================
// ADD THESE METHODS to Invoice_model.php
// ============================================================
/*
    public function get_unpaid_for_select() {
        $rows = $this->db
            ->select('invoices.id, invoices.invoice_no, invoices.due_amount, customers.name as customer_name')
            ->join('customers','customers.id = invoices.customer_id')
            ->where_in('status', ['unpaid','partial'])
            ->order_by('invoices.id','DESC')
            ->get('invoices')->result();
        $out = [];
        foreach ($rows as $r) {
            $out[$r->id] = $r->invoice_no . ' — ' . $r->customer_name . ' (Due: ₹' . number_format($r->due_amount,2) . ')';
        }
        return $out;
    }

    public function get_unpaid_by_customer($customer_id) {
        return $this->db
            ->select('id, invoice_no, total_amount, due_amount, invoice_date')
            ->where('customer_id', $customer_id)
            ->where_in('status', ['unpaid','partial'])
            ->order_by('id','DESC')
            ->get('invoices')->result();
    }
*/

// ============================================================
// ADD THESE METHODS to Purchase_model.php
// ============================================================
/*
    public function get_unpaid_for_select() {
        $rows = $this->db
            ->select('purchases.id, purchases.purchase_no, purchases.due_amount, suppliers.name as supplier_name')
            ->join('suppliers','suppliers.id = purchases.supplier_id')
            ->where_in('status', ['partial','pending'])
            ->order_by('purchases.id','DESC')
            ->get('purchases')->result();
        $out = [];
        foreach ($rows as $r) {
            $out[$r->id] = $r->purchase_no . ' — ' . $r->supplier_name . ' (Due: ₹' . number_format($r->due_amount,2) . ')';
        }
        return $out;
    }

    public function get_unpaid_by_supplier($supplier_id) {
        return $this->db
            ->select('id, purchase_no, total_amount, due_amount, invoice_date')
            ->where('supplier_id', $supplier_id)
            ->where_in('status', ['partial','pending'])
            ->order_by('id','DESC')
            ->get('purchases')->result();
    }
*/
