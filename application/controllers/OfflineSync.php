<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OfflineSync Controller
 * Provides REST-style API endpoints for Offline-First POS sync,
 * plus the Sync Dashboard and Offline Report views.
 */
class OfflineSync extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('OfflineSync_model');
        $this->load->model('Invoice_model');
        $this->load->model('Product_model');
        $this->load->model('Customer_model');
        $this->load->model('Inventory_model');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /offline_sync/get_data
    // Full data download for offline cache initialization
    // ─────────────────────────────────────────────────────────────────────────
    public function get_data() {
        if (!$this->input->is_ajax_request() && $this->input->method() !== 'get') {
            $this->_json_error('Invalid request', 405); return;
        }

        $location_id = (int)($this->session->userdata('location_id') ?: 1);
        $data = $this->OfflineSync_model->get_billing_data($location_id);

        // Add auth context so client can verify device binding
        $data['auth'] = [
            'user_id'     => (int)$this->session->userdata('user_id'),
            'user_name'   => $this->session->userdata('name'),
            'role'        => $this->session->userdata('role'),
            'location_id' => $location_id,
        ];

        $this->output
            ->set_content_type('application/json')
            ->set_header('Cache-Control: no-store')
            ->set_output(json_encode(['success' => true, 'data' => $data]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /offline_sync/get_delta?since=<unix_timestamp>
    // Delta sync — only records changed since given timestamp
    // ─────────────────────────────────────────────────────────────────────────
    public function get_delta() {
        $since = (int)$this->input->get('since');
        if (!$since) { $this->_json_error('Missing since parameter'); return; }

        $location_id = (int)($this->session->userdata('location_id') ?: 1);
        $data = $this->OfflineSync_model->get_delta($location_id, $since);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'data' => $data]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /offline_sync/push_invoice
    // Upload a single offline invoice for cloud processing
    // ─────────────────────────────────────────────────────────────────────────
    public function push_invoice() {
        if ($this->input->method() !== 'post') { $this->_json_error('POST required', 405); return; }

        $raw = $this->input->raw_input_stream ?: file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (empty($payload) || empty($payload['offline_ref']) || empty($payload['items'])) {
            $this->_json_error('Invalid payload'); return;
        }

        $offline_ref = trim($payload['offline_ref']);
        $device_id   = trim($payload['device_id'] ?? '');

        // Duplicate check
        if ($this->OfflineSync_model->is_duplicate($offline_ref)) {
            $mapping = $this->db->where('offline_ref', $offline_ref)->get('offline_invoice_mapping')->row();
            $this->_json(['success' => true, 'already_synced' => true,
                'cloud_ref' => $mapping ? $mapping->cloud_invoice_no : '',
                'message'   => 'Invoice already synced']);
            return;
        }

        // Build invoice data
        $invoice_date = $payload['invoice_date'] ?? date('Y-m-d');
        $customer_id  = (int)($payload['customer_id'] ?? 0);
        $location_id  = (int)($this->session->userdata('location_id') ?: ($payload['location_id'] ?? 1));
        $created_by   = (int)($this->session->userdata('user_id') ?: ($payload['user_id'] ?? 1));

        // Compute totals server-side for integrity
        $subtotal = 0; $total_discount = 0; $taxable = 0;
        $cgst = 0; $sgst = 0; $igst = 0; $total_gst = 0;
        $is_interstate = !empty($payload['is_interstate']);

        $items_to_insert = [];
        foreach ($payload['items'] as $item) {
            $pid    = (int)$item['product_id'];
            $qty    = (float)$item['qty'];
            $rate   = (float)$item['rate'];
            $disc   = (float)($item['item_discount'] ?? 0);
            $gst_p  = (float)($item['gst_percent'] ?? 0);

            // Rate is GST-inclusive: it's the actual price the customer pays.
            // Taxable value + GST are extracted backwards out of that price.
            $line_total   = round(($rate * $qty) - $disc, 2);
            $line_taxable = round($line_total / (1 + $gst_p / 100), 2);
            $gst_amt      = round($line_total - $line_taxable, 2);

            $subtotal += $rate * $qty;
            $total_discount += $disc;
            $taxable += $line_taxable;
            $total_gst += $gst_amt;

            // Fetch product name for record integrity
            $prod = $this->db->select('name, hsn_code, unit')->where('id', $pid)->get('products')->row();

            // Per-line GST split (same logic as Billing controller)
            $is_inter = !empty($payload['is_interstate']);
            $line_cgst_amt = $is_inter ? 0 : round($gst_amt / 2, 2);
            $line_sgst_amt = $is_inter ? 0 : round($gst_amt - $line_cgst_amt, 2);
            $line_igst_amt = $is_inter ? $gst_amt : 0;
            $line_cgst_pct = $is_inter ? 0 : $gst_p / 2;
            $line_sgst_pct = $line_cgst_pct;
            $line_igst_pct = $is_inter ? $gst_p : 0;

            $items_to_insert[] = [
                'product_id'   => $pid,
                'product_name' => $prod ? $prod->name : ($item['product_name'] ?? ''),
                'hsn_code'     => $prod ? $prod->hsn_code : '',
                'qty'          => (int)$qty,
                'unit'         => $prod ? $prod->unit : ($item['unit'] ?? 'pcs'),
                'rate'         => $rate,
                'mrp'          => (float)($item['mrp'] ?? 0),
                'discount'     => $disc,
                'taxable_amt'  => $line_taxable,
                'gst_percent'  => $gst_p,
                'gst_amount'   => $gst_amt,
                'cgst_percent' => $line_cgst_pct,
                'sgst_percent' => $line_sgst_pct,
                'igst_percent' => $line_igst_pct,
                'cgst_amount'  => $line_cgst_amt,
                'sgst_amount'  => $line_sgst_amt,
                'igst_amount'  => $line_igst_amt,
                'total'        => $line_total,
                'invoice_no'   => '', // filled after invoice creation below
            ];
        }

        $total_gst = round($total_gst, 2);
        $taxable   = round($taxable, 2);

        if ($is_interstate) {
            $igst = $total_gst;
        } else {
            $cgst = round($total_gst / 2, 2);
            $sgst = round($total_gst - $cgst, 2);
        }

        // CD discount
        $cd_amount = (float)($payload['cd_amount'] ?? 0);
        // Bill discount
        $bill_disc_amount = (float)($payload['bill_discount_amount'] ?? 0);
        $grand_total = round(($taxable + $total_gst) - $cd_amount - $bill_disc_amount, 2);
        $paid_amount = (float)($payload['paid_amount'] ?? $grand_total);
        $wallet_used_amt = (float)($payload['wallet_amount_used'] ?? 0);
        // Cap to what's actually owed — same fix as Billing::create(): the offline
        // billing screen can send the raw cash tendered as paid_amount instead of the
        // amount applied to the bill, which would otherwise inflate Collected totals.
        $net_payable_amt = round($grand_total - $wallet_used_amt, 2);
        if ($paid_amount > $net_payable_amt) {
            $paid_amount = $net_payable_amt;
        }
        $due_amount  = round($grand_total - $paid_amount - $wallet_used_amt, 2);

        // Next cloud invoice number
        try {
            $doc_no = $this->Invoice_model->generate_invoice_no($location_id, $invoice_date);
        } catch (Exception $e) {
            $this->_json_error($e->getMessage(), 422);
            return;
        }
        $inv_number = $doc_no['number'];

        // Bill discount fields
        $bill_disc_type   = $payload['bill_discount_type']   ?? 'none';
        $bill_disc_amount = (float)($payload['bill_discount_amount'] ?? 0);
        $bill_disc_pct    = 0;
        if ($bill_disc_type === 'percent' && $taxable > 0) {
            $bill_disc_pct = ($bill_disc_amount / ($taxable + $total_gst)) * 100;
        }

        $status = $due_amount <= 0 ? 'paid' : ($paid_amount > 0 ? 'partial' : 'unpaid');

        $invoice_data = [
            'invoice_no'            => $inv_number,
            'financial_year'        => $doc_no['financial_year'],
            'customer_id'           => $customer_id,
            'invoice_type'          => $payload['invoice_type'] ?? 'retail',
            'invoice_date'          => $invoice_date,
            'subtotal'              => $subtotal,
            'discount'              => $total_discount,
            'taxable_amount'        => $taxable,
            'cgst_amount'           => $cgst,
            'sgst_amount'           => $sgst,
            'igst_amount'           => $igst,
            'total_gst'             => $total_gst,
            'cd_percent'            => (float)($payload['cd_percent'] ?? 0),
            'cd_amount'             => $cd_amount,
            'bill_discount_type'    => $bill_disc_type,
            'bill_discount_percent' => round($bill_disc_pct, 2),
            'bill_discount_amount'  => $bill_disc_amount,
            'bill_discount_reason'  => $payload['bill_discount_reason'] ?? '',
            'total_amount'          => $grand_total,
            'paid_amount'           => $paid_amount,
            'due_amount'            => max(0, $due_amount),
            'wallet_amount_used'    => (float)($payload['wallet_amount_used'] ?? 0),
            'wallet_amount_credited'=> (float)($payload['wallet_amount_credited'] ?? 0),
            'payment_mode'          => !empty($payload['split_payments']) ? 'split' : ($payload['payment_mode'] ?? 'cash'),
            'multi_payment'         => !empty($payload['split_payments']) ? 1 : 0,
            'is_interstate'         => $is_interstate ? 1 : 0,
            'notes'                 => substr(($payload['notes'] ?? '') . ' [Offline: ' . $offline_ref . ']', 0, 1000),
            'status'                => $status,
            'created_by'            => $created_by,
            'location_id'           => $location_id,
            'offline_ref'           => $offline_ref,
        ];

        // Set invoice_no on each line item (needed by invoice_items table)
        foreach ($items_to_insert as &$it) { $it['invoice_no'] = $inv_number; }
        unset($it);

        $this->db->trans_begin();
        try {
            // Atomically claim this offline_ref before doing anything else. The
            // is_duplicate() check above is only a fast-path optimization — it has a
            // race window (check, then several DB writes, then record_mapping() at the
            // very end), so two near-simultaneous retries from the same flaky POS
            // terminal (exactly what happens on barcode-scan devices with unreliable
            // wifi) could both pass it and both proceed. INSERT IGNORE against
            // offline_invoice_mapping's UNIQUE(offline_ref) makes the claim itself
            // atomic: only one concurrent request can win the row. This matters because
            // in production db_debug is FALSE (see database.php), so a failed duplicate
            // INSERT doesn't throw — the old check-then-insert in record_mapping() would
            // silently no-op on the loser's insert while that loser's invoice creation
            // and customer-balance increment had already gone through and committed,
            // doubling both. That's what inflated one customer's balance into the
            // billions over repeated retries.
            $this->db->query(
                "INSERT IGNORE INTO offline_invoice_mapping (offline_ref, cloud_invoice_id, cloud_invoice_no, device_id, synced_at) VALUES (?, 0, '', ?, NOW())",
                [$offline_ref, $device_id]
            );
            if ((int)$this->db->affected_rows() === 0) {
                // Another request already claimed this offline_ref — it's a genuine
                // duplicate, not a new sale. Bail out before creating anything.
                $this->db->trans_complete();
                $mapping = $this->db->where('offline_ref', $offline_ref)->get('offline_invoice_mapping')->row();
                $this->_json(['success' => true, 'already_synced' => true,
                    'cloud_ref' => $mapping ? $mapping->cloud_invoice_no : '',
                    'message'   => 'Invoice already synced']);
                return;
            }

            $invoice_id = $this->Invoice_model->create($invoice_data, $items_to_insert);

            // Backdate created_at to when the offline sale happened
            $local_ts = $payload['created_at_local'] ?? null;
            if ($local_ts) {
                $this->db->where('id', $invoice_id)->update('invoices', ['created_at' => $local_ts]);
            }

            // Deduct stock for each line item — same path as online billing: the
            // real balance may go negative (never clamped), the product display
            // total is re-synced, and the sale is written to the stock ledger.
            $this->load->model('Location_model');
            $loc_row  = $this->Location_model->get_by_id($location_id);
            $loc_name = $loc_row ? $loc_row->name : '';
            foreach ($items_to_insert as $item) {
                $before = $this->Location_model->get_stock($item['product_id'], $location_id);
                $after  = $this->Location_model->decrease_stock($item['product_id'], $location_id, (float)$item['qty']);
                $this->Location_model->log_movement([
                    'product_id'     => $item['product_id'],
                    'product_name'   => $item['product_name'] ?? '',
                    'location_id'    => $location_id,
                    'location_name'  => $loc_name,
                    'movement_type'  => 'sale_out',
                    'quantity'       => (float)$item['qty'],
                    'balance_before' => $before,
                    'balance_after'  => $after,
                    'reference_type' => 'invoice',
                    'reference_id'   => $invoice_id,
                    'reference_no'   => $invoice_data['invoice_no'] ?? '',
                    'user_id'        => $this->session->userdata('user_id'),
                    'user_name'      => $this->session->userdata('name'),
                    'notes'          => 'Offline sale synced',
                ]);
            }

            // Split payments if any
            if (!empty($payload['split_payments'])) {
                foreach ($payload['split_payments'] as $sp) {
                    $this->db->insert('invoice_payment_splits', [
                        'invoice_id'   => $invoice_id,
                        'payment_mode' => $sp['mode'],
                        'amount'       => (float)$sp['amount'],
                        'card_last4'   => $sp['card_last4'] ?? null,
                        'reference_no' => $sp['reference_no'] ?? null,
                        'created_at'   => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            // Update customer balance for credit sales
            if ($due_amount > 0) {
                $this->db->query(
                    "UPDATE customers SET balance = balance + ? WHERE id=?",
                    [$due_amount, $customer_id]
                );
            }

            // Wallet deduction
            $wallet_used = (float)($payload['wallet_amount_used'] ?? 0);
            if ($wallet_used > 0) {
                $this->db->query(
                    "UPDATE customers SET wallet_balance = GREATEST(0, wallet_balance - ?) WHERE id=?",
                    [$wallet_used, $customer_id]
                );
            }

            // Fill in the placeholder mapping row claimed at the top of this method with
            // the real invoice reference now that it exists.
            $this->db->query(
                "UPDATE offline_invoice_mapping SET cloud_invoice_id = ?, cloud_invoice_no = ? WHERE offline_ref = ?",
                [$invoice_id, $inv_number, $offline_ref]
            );

            // Mark sync queue as synced if it exists
            $q = $this->db->where('offline_ref', $offline_ref)->get('offline_sync_queue')->row();
            if ($q) {
                $this->OfflineSync_model->mark_synced($q->id, $invoice_id, $inv_number);
            }

            // Audit log
            $this->log_activity('OFFLINE_SYNC', "Synced offline invoice $offline_ref → $inv_number (ID:$invoice_id)");

            $this->db->trans_commit();

            $this->_json([
                'success'       => true,
                'offline_ref'   => $offline_ref,
                'cloud_id'      => $invoice_id,
                'cloud_ref'     => $inv_number,
                'message'       => "Invoice synced: $inv_number",
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->_json_error('Sync failed: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /offline_sync/push_customer
    // Upload a customer created while offline
    // ─────────────────────────────────────────────────────────────────────────
    public function push_customer() {
        if ($this->input->method() !== 'post') { $this->_json_error('POST required', 405); return; }

        $raw     = $this->input->raw_input_stream ?: file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (empty($payload['phone']) || empty($payload['name'])) {
            $this->_json_error('Name and phone required'); return;
        }

        // Check if already exists by phone
        $phone  = preg_replace('/\D/', '', $payload['phone']);
        $phone  = substr($phone, -10);
        $exists = $this->db->where('phone', $phone)->get('customers')->row();
        if ($exists) {
            $this->_json(['success' => true, 'already_exists' => true, 'customer_id' => (int)$exists->id]);
            return;
        }

        $this->db->insert('customers', [
            'name'         => trim($payload['name']),
            'phone'        => $phone,
            'address'      => trim($payload['address'] ?? ''),
            'type'         => 'retail',
            'status'       => 'active',
            'balance'      => 0,
            'credit_limit' => 0,
            'wallet_balance' => 0,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
        $cid = $this->db->insert_id();

        $this->_json(['success' => true, 'customer_id' => $cid]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /offline_sync/status_json
    // Lightweight status for the badge
    // ─────────────────────────────────────────────────────────────────────────
    public function status_json() {
        $stats = $this->OfflineSync_model->get_stats();
        $this->_json(['success' => true, 'stats' => $stats]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /offline_sync/retry_failed
    // Reset all failed items to pending
    // ─────────────────────────────────────────────────────────────────────────
    public function retry_failed() {
        $count = $this->OfflineSync_model->retry_failed();
        $this->_json(['success' => true, 'reset' => $count]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /offline_sync/dashboard — Sync Management UI
    // ─────────────────────────────────────────────────────────────────────────
    public function dashboard() {
        $this->can_module('billing');
        $stats   = $this->OfflineSync_model->get_stats();
        $queue   = $this->OfflineSync_model->get_recent_queue(100);
        $mapping = $this->OfflineSync_model->get_mappings(50);

        $data = [
            'page_title' => 'Offline Sync Dashboard',
            'stats'      => $stats,
            'queue'      => $queue,
            'mapping'    => $mapping,
        ];
        $this->load->view('layouts/header', $data);
        $this->load->view('offline_sync/dashboard', $data);
        $this->load->view('layouts/footer');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /offline_sync/report — Offline Billing Report
    // ─────────────────────────────────────────────────────────────────────────
    public function report() {
        $this->can_module('reports');
        $stats   = $this->OfflineSync_model->get_stats();
        $mapping = $this->OfflineSync_model->get_mappings(500);
        $failed  = $this->db->where('status', 'failed')->get('offline_sync_queue')->result_array();

        $data = [
            'page_title' => 'Offline Sync Report',
            'stats'      => $stats,
            'mapping'    => $mapping,
            'failed'     => $failed,
        ];
        $this->load->view('layouts/header', $data);
        $this->load->view('offline_sync/report', $data);
        $this->load->view('layouts/footer');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function _json(array $data, int $code = 200): void {
        $this->output->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    private function _json_error(string $msg, int $code = 400): void {
        $this->_json(['success' => false, 'message' => $msg], $code);
    }
}
