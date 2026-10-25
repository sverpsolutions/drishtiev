<?php
// ============================================================
// FILE: application/models/Customer_model.php
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_model extends CI_Model {

    private $table = 'customers';

    public function get_all() {
        return $this->db->where('status', 1)->order_by('name', 'ASC')->get($this->table)->result();
    }

    public function get_all_for_select($type = null) {
        $this->db->select('id, name, type, credit_limit, balance, gst_number, state, wallet_balance');
        $this->db->where('status', 1);
        if ($type) $this->db->where('type', $type);
        $rows = $this->db->order_by('name')->get($this->table)->result();
        $out  = [];
        foreach ($rows as $r) {
            $wallet_str = ($r->wallet_balance > 0) ? ' — Wallet: ₹' . number_format($r->wallet_balance, 0) : '';
            $out[$r->id] = $r->name . ' (' . ucfirst($r->type) . ')' . $wallet_str;
        }
        return $out;
    }

    // Returns full rows with balance for billing dropdown (includes outstanding and wallet)
    public function get_all_with_balance($type = null) {
        $cols = 'id, name, phone, type, credit_limit, balance, gst_number, state, show_outstanding_in_print, wallet_balance';
        if ($this->db->field_exists('discount_percent', $this->table)) $cols .= ', discount_percent';
        $this->db->select($cols);
        $this->db->where('status', 1);
        if ($type) $this->db->where('type', $type);
        return $this->db->order_by('name')->get($this->table)->result();
    }

    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row();
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    // Add to outstanding balance
    public function increase_balance($id, $amount) {
        return $this->db->set('balance', 'balance + ' . (float)$amount, FALSE)
            ->where('id', $id)->update($this->table);
    }

    // Reduce outstanding balance (on payment)
    public function decrease_balance($id, $amount) {
        return $this->db->set('balance', 'balance - ' . (float)$amount, FALSE)
            ->where('id', $id)->update($this->table);
    }

    public function delete($id) {
        return $this->db->where('id', $id)->update($this->table, ['status' => 0]);
    }

    // Adjust wallet balance and log transaction (atomic database transaction)
    public function adjust_wallet($customer_id, $amount, $type, $reference_type, $reference_id = null, $reference_no = null, $remarks = '', $created_by = null) {
        $this->db->trans_start();
        
        // Lock customer row
        $customer = $this->db->select('wallet_balance')->where('id', $customer_id)->get($this->table)->row();
        if (!$customer) {
            $this->db->trans_complete();
            return false;
        }
        
        $opening = (float)$customer->wallet_balance;
        $amount = (float)$amount;
        
        if ($type === 'credit') {
            $closing = $opening + $amount;
        } else {
            $closing = $opening - $amount;
            if ($closing < 0) {
                // Wallet balance should never become negative
                $this->db->trans_complete();
                return false;
            }
        }
        
        // Update customer wallet_balance
        $this->db->where('id', $customer_id)
                 ->update($this->table, ['wallet_balance' => $closing]);
                 
        // Log transaction
        $this->db->insert('customer_wallet_transactions', [
            'customer_id'      => $customer_id,
            'transaction_type' => $type,
            'amount'           => $amount,
            'opening_balance'  => $opening,
            'closing_balance'  => $closing,
            'reference_type'   => $reference_type,
            'reference_id'     => $reference_id,
            'reference_no'     => $reference_no,
            'remarks'          => $remarks,
            'branch_id'        => 0,
            'created_by'       => $created_by ?? 0,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
