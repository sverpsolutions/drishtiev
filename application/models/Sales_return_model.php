<?php
// ============================================================
// FILE: application/models/Sales_return_model.php
// Handles Sales Returns / Credit Notes (GSTR-1 Section 9B)
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Sales_return_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    // ─── Generate Credit Note number ─────────────────────────
    public function generate_number() {
        $prefix = $this->_setting('sales_return_prefix') ?: 'CN';
        $seq    = (int)($this->_setting('sales_return_seq') ?? 0) + 1;
        $year   = date('Y');
        $num    = $prefix . '-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $this->db->where('key_name', 'sales_return_seq')
                 ->update('company_settings', ['key_value' => $seq]);
        return $num;
    }

    // ─── Get all credit notes ─────────────────────────────────
    public function get_all($from = null, $to = null, $customer_id = null) {
        $this->db->select('sr.*, c.name as customer_name, c.gst_number as customer_gst,
                           i.invoice_no as original_invoice_no')
                 ->from('sales_returns sr')
                 ->join('customers c', 'c.id = sr.customer_id', 'left')
                 ->join('invoices i', 'i.id = sr.invoice_id', 'left')
                 ->order_by('sr.return_date', 'DESC');

        if ($from) $this->db->where('sr.return_date >=', $from);
        if ($to)   $this->db->where('sr.return_date <=', $to);
        if ($customer_id) $this->db->where('sr.customer_id', $customer_id);

        return $this->db->get()->result();
    }

    // ─── Get single credit note with items ───────────────────
    public function get_by_id($id) {
        $ret = $this->db->select('sr.*, c.name as customer_name, c.gst_number as customer_gst,
                                  c.address as customer_address, c.city as customer_city,
                                  c.state as customer_state, c.phone as customer_phone,
                                  i.invoice_no as original_invoice_no, i.invoice_date as original_invoice_date')
                        ->from('sales_returns sr')
                        ->join('customers c', 'c.id = sr.customer_id', 'left')
                        ->join('invoices i', 'i.id = sr.invoice_id', 'left')
                        ->where('sr.id', $id)
                        ->get()->row();

        if ($ret) {
            $ret->items = $this->db->where('sales_return_id', $id)
                                   ->get('sales_return_items')->result();
        }
        return $ret;
    }

    // ─── Create sales return + credit note ───────────────────
    public function create($data, $items) {
        $this->db->trans_start();

        $this->db->insert('sales_returns', $data);
        $return_id = $this->db->insert_id();

        foreach ($items as $item) {
            $item['sales_return_id'] = $return_id;
            $this->db->insert('sales_return_items', $item);

            // Sales return = customer returns goods TO us = stock increases
            if (!empty($item['product_id'])) {
                if (!empty($item['variant_id'])) {
                    $this->load->model('Garment_model');
                    $location_id = $data['location_id'] ?? $this->session->userdata('location_id') ?? 1;
                    $this->Garment_model->increase_variant_stock($item['variant_id'], $item['product_id'], $location_id, (float)$item['qty']);
                } else {
                    $this->db->where('id', $item['product_id'])
                             ->set('stock_qty', 'stock_qty + ' . (float)$item['qty'], FALSE)
                             ->update('products');
                }

                // Log to stock ledger
                $_srow = $this->db->select('stock_qty')->where('id',$item['product_id'])->get('products')->row();
                $cur_stock = (float)($_srow->stock_qty ?? 0);
                $this->db->insert('stock_ledger', [
                    'product_id'  => $item['product_id'],
                    'variant_id'  => $item['variant_id'] ?? null,
                    'variant_sku' => $item['variant_sku'] ?? null,
                    'size_name'   => $item['size_name'] ?? null,
                    'colour_name' => $item['colour_name'] ?? null,
                    'txn_type'    => 'sale_return',
                    'txn_id'      => $return_id,
                    'txn_no'      => $data['return_number'] ?? '',
                    'qty_in'      => (float)$item['qty'],
                    'qty_out'     => 0,
                    'balance_qty' => $cur_stock,
                    'rate'        => (float)$item['rate'],
                    'notes'       => 'Sales Return: ' . ($data['return_number'] ?? ''),
                    'created_by'  => $data['created_by'] ?? null,
                ]);
            }
        }

        // Update customer balance based on refund method
        if (!empty($data['customer_id']) && !empty($data['total_amount'])) {
            $refund_method = isset($data['refund_method']) ? $data['refund_method'] : 'balance';
            if ($refund_method === 'wallet') {
                $this->load->model('Customer_model');
                $this->Customer_model->adjust_wallet(
                    $data['customer_id'],
                    (float)$data['total_amount'],
                    'credit',
                    'refund',
                    $return_id,
                    $data['return_number'],
                    'Refunded credit note #' . $data['return_number'] . ' to wallet',
                    $data['created_by'] ?? null
                );
            } else if ($refund_method === 'cash') {
                // Do nothing
            } else {
                // Default: reduce customer outstanding balance
                $this->db->where('id', $data['customer_id'])
                         ->set('balance', 'balance - ' . (float)$data['total_amount'], FALSE)
                         ->update('customers');
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $return_id : false;
    }

    // ─── Cancel credit note ───────────────────────────────────
    public function cancel($id) {
        $ret = $this->get_by_id($id);
        if (!$ret || $ret->status === 'cancelled') return false;

        $this->db->trans_start();
        $this->db->where('id', $id)->update('sales_returns', ['status' => 'cancelled']);

        foreach ($ret->items as $item) {
            if (!empty($item->product_id)) {
                if (!empty($item->variant_id)) {
                    $this->load->model('Garment_model');
                    $location_id = $ret->location_id ?? $this->session->userdata('location_id') ?? 1;
                    $this->Garment_model->decrease_variant_stock($item->variant_id, $item->product_id, $location_id, (float)$item->qty);
                } else {
                    $this->db->where('id', $item->product_id)
                             ->set('stock_qty', 'stock_qty - ' . (float)$item->qty, FALSE)
                             ->update('products');
                }
            }
        }

        // Reverse balance adjustment based on refund method
        if (!empty($ret->customer_id) && !empty($ret->total_amount)) {
            $refund_method = isset($ret->refund_method) ? $ret->refund_method : 'balance';
            if ($refund_method === 'wallet') {
                $this->load->model('Customer_model');
                $this->Customer_model->adjust_wallet(
                    $ret->customer_id,
                    (float)$ret->total_amount,
                    'debit',
                    'refund',
                    $ret->id,
                    $ret->return_number,
                    'Reversed wallet refund due to cancelled credit note #' . $ret->return_number,
                    $this->session->userdata('user_id')
                );
            } else if ($refund_method === 'cash') {
                // Do nothing
            } else {
                $this->db->where('id', $ret->customer_id)
                         ->set('balance', 'balance + ' . (float)$ret->total_amount, FALSE)
                         ->update('customers');
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // ─── For GSTR-1 (Section 9B — Credit Notes) ──────────────
    public function get_for_gstr1($from, $to) {
        return $this->db->select('sr.*, c.name as customer_name, c.gst_number as customer_gst,
                                  c.state as customer_state, i.invoice_no as original_invoice_no')
                        ->from('sales_returns sr')
                        ->join('customers c', 'c.id = sr.customer_id', 'left')
                        ->join('invoices i', 'i.id = sr.invoice_id', 'left')
                        ->where('sr.return_date >=', $from)
                        ->where('sr.return_date <=', $to)
                        ->where('sr.status', 'confirmed')
                        ->order_by('sr.return_date')
                        ->get()->result();
    }

    // ─── For GSTR-3B (negative in outward supplies) ──────────
    public function get_summary_for_gstr3b($from, $to) {
        $row = $this->db->select_sum('taxable_amount')
                        ->select_sum('cgst_amount')
                        ->select_sum('sgst_amount')
                        ->select_sum('igst_amount')
                        ->select_sum('total_gst')
                        ->select_sum('total_amount')
                        ->where('return_date >=', $from)
                        ->where('return_date <=', $to)
                        ->where('status', 'confirmed')
                        ->get('sales_returns')->row();
        return $row ?? (object)[
            'taxable_amount'=>0,'cgst_amount'=>0,'sgst_amount'=>0,
            'igst_amount'=>0,'total_gst'=>0,'total_amount'=>0
        ];
    }

    // ─── HSN summary for GSTR-1 ──────────────────────────────
    public function get_hsn_summary($from, $to) {
        return $this->db->select('sri.hsn_code, sri.gst_percent,
                                  SUM(sri.qty) as total_qty,
                                  SUM(sri.taxable_amt) as taxable_amount,
                                  SUM(sri.cgst_amount) as cgst_amount,
                                  SUM(sri.sgst_amount) as sgst_amount,
                                  SUM(sri.igst_amount) as igst_amount,
                                  SUM(sri.gst_amount) as total_gst')
                        ->from('sales_return_items sri')
                        ->join('sales_returns sr', 'sr.id = sri.sales_return_id')
                        ->where('sr.return_date >=', $from)
                        ->where('sr.return_date <=', $to)
                        ->where('sr.status', 'confirmed')
                        ->group_by('sri.hsn_code, sri.gst_percent')
                        ->order_by('sri.hsn_code')
                        ->get()->result();
    }

    // ─── Private helper ───────────────────────────────────────
    private function _setting($key) {
        $row = $this->db->where('key_name', $key)->get('company_settings')->row();
        return $row ? $row->key_value : null;
    }
}
