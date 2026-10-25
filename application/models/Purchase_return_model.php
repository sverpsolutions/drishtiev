<?php
// ============================================================
// FILE: application/models/Purchase_return_model.php
// Handles Purchase Returns / Debit Notes
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchase_return_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    // ─── Generate unique Debit Note number ───────────────────
    public function generate_number() {
        $prefix = $this->_setting('purchase_return_prefix') ?: 'DN';
        $seq    = (int)($this->_setting('purchase_return_seq') ?? 0) + 1;
        $year   = date('Y');
        $num    = $prefix . '-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // Update sequence
        $this->db->where('key_name', 'purchase_return_seq')
                 ->update('company_settings', ['key_value' => $seq]);
        return $num;
    }

    // ─── Get all returns (for list view) ─────────────────────
    public function get_all($from = null, $to = null, $supplier_id = null) {
        $this->db->select('pr.*, s.name as supplier_name')
                 ->from('purchase_returns pr')
                 ->join('suppliers s', 's.id = pr.supplier_id', 'left')
                 ->order_by('pr.return_date', 'DESC');

        if ($from) $this->db->where('pr.return_date >=', $from);
        if ($to)   $this->db->where('pr.return_date <=', $to);
        if ($supplier_id) $this->db->where('pr.supplier_id', $supplier_id);

        return $this->db->get()->result();
    }

    // ─── Get single return with items ────────────────────────
    public function get_by_id($id) {
        $ret = $this->db->select('pr.*, s.name as supplier_name, s.gst_number as supplier_gst,
                                  s.address as supplier_address, s.state as supplier_state,
                                  p.purchase_no as original_purchase_no')
                        ->from('purchase_returns pr')
                        ->join('suppliers s', 's.id = pr.supplier_id', 'left')
                        ->join('purchases p', 'p.id = pr.purchase_id', 'left')
                        ->where('pr.id', $id)
                        ->get()->row();

        if ($ret) {
            $ret->items = $this->db->where('purchase_return_id', $id)
                                   ->get('purchase_return_items')->result();
        }
        return $ret;
    }

    // ─── Create purchase return ───────────────────────────────
    public function create($data, $items) {
        if (empty($data['prn_no'])) $data['prn_no'] = $data['return_number'];

        $this->db->trans_start();

        // Whitelist header columns
        $header_cols = ['prn_no', 'purchase_id', 'supplier_id', 'return_date', 'total_amount', 'taxable_amount', 'total_gst', 'cgst_amount', 'sgst_amount', 'igst_amount', 'is_interstate', 'reason', 'created_by'];
        $insert_data = [];
        foreach ($header_cols as $c) {
            if (isset($data[$c])) $insert_data[$c] = $data[$c];
        }

        // Insert header
        $this->db->insert('purchase_returns', $insert_data);
        $return_id = $this->db->insert_id();

        if (!$return_id) {
            $this->db->trans_complete();
            return false;
        }

        $item_cols = ['prn_id', 'product_id', 'qty', 'price', 'total', 'taxable_amount', 'gst_percent', 'gst_amount', 'cgst_amount', 'sgst_amount', 'igst_amount', 'itc_reversed'];

        foreach ($items as $item) {
            $row_data = [
                'prn_id'         => $return_id,
                'product_id'     => $item['product_id'] ?? null,
                'qty'            => $item['qty'] ?? 1,
                'price'          => $item['price'] ?? ($item['rate'] ?? 0),
                'total'          => $item['total'] ?? 0,
                'taxable_amount' => $item['taxable_amount'] ?? ($item['taxable_amt'] ?? 0),
                'gst_percent'    => $item['gst_percent'] ?? 0,
                'gst_amount'     => $item['gst_amount'] ?? 0,
                'cgst_amount'    => $item['cgst_amount'] ?? 0,
                'sgst_amount'    => $item['sgst_amount'] ?? 0,
                'igst_amount'    => $item['igst_amount'] ?? 0,
                'itc_reversed'   => $item['itc_reversed'] ?? ($item['gst_amount'] ?? 0)
            ];
            $this->db->insert('purchase_return_items', $row_data);

            // Restore stock (items going back to supplier reduce our stock: purchase return = goods out)
            if (!empty($item['product_id'])) {
                $this->db->where('id', $item['product_id'])
                         ->set('stock_qty', 'stock_qty - ' . (float)$item['qty'], FALSE)
                         ->update('products');

                // Log to stock ledger
                $_prow = $this->db->select('stock_qty')->where('id',$item['product_id'])->get('products')->row();
                $cur_stock = (float)($_prow->stock_qty ?? 0);
                $this->db->insert('stock_ledger', [
                    'product_id'  => $item['product_id'],
                    'txn_type'    => 'purchase_return',
                    'txn_id'      => $return_id,
                    'txn_no'      => $data['return_number'] ?? ($data['prn_no'] ?? ''),
                    'qty_in'      => 0,
                    'qty_out'     => (float)$item['qty'],
                    'balance_qty' => max(0, $cur_stock - (float)$item['qty']),
                    'rate'        => (float)($item['price'] ?? ($item['rate'] ?? 0)),
                    'notes'       => 'Purchase Return: ' . ($data['return_number'] ?? ($data['prn_no'] ?? '')),
                    'created_by'  => $data['created_by'] ?? null,
                ]);
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $return_id : false;
    }

    // ─── Cancel / void a return ───────────────────────────────
    public function cancel($id) {
        $ret = $this->get_by_id($id);
        if (!$ret || $ret->status === 'cancelled') return false;

        $this->db->trans_start();
        $this->db->where('id', $id)->update('purchase_returns', ['status' => 'cancelled']);

        // Restore stock back (reverse the return)
        foreach ($ret->items as $item) {
            if (!empty($item->product_id)) {
                $this->db->where('id', $item->product_id)
                         ->set('stock_qty', 'stock_qty + ' . (float)$item->qty, FALSE)
                         ->update('products');
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // ─── Totals for dashboard/reports ────────────────────────
    public function get_totals($from = null, $to = null) {
        $this->db->select_sum('total_amount')->from('purchase_returns')
                 ->where('status', 'confirmed');
        if ($from) $this->db->where('return_date >=', $from);
        if ($to)   $this->db->where('return_date <=', $to);
        return $this->db->get()->row()->total_amount ?? 0;
    }

    // ─── Private helper ───────────────────────────────────────
    private function _setting($key) {
        $row = $this->db->where('key_name', $key)->get('company_settings')->row();
        return $row ? $row->key_value : null;
    }
}
