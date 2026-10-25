<?php
// ============================================================
// FILE: application/libraries/AccountingHooks.php
// Single source of truth for every double-entry posting formula in the ERP. Each
// post_*()/reverse_*() method is meant to be called from inside the relevant
// controller's EXISTING trans_start()/trans_complete() block, right before
// trans_complete() — exactly like the existing Customer_model::increase_balance()/
// adjust_wallet() calls it sits alongside. None of these are wired into any
// controller yet (that's Phase 3 of the accounting module rollout); this file is
// inert until those call sites are added.
//
// Every method checks the 'accounting_autopost_enabled' company_settings flag first
// and no-ops if it's off ('0'/unset) — a kill switch so Phase 3 can be deployed to
// production before being enabled, and instantly disabled again without a redeploy
// if a posting bug ever surfaces.
//
// All formulas below are balance-checked against the exact persisted columns in
// Billing.php / Purchases.php / Payments.php / Refunds.php / SalesReturns.php /
// PurchaseReturns.php — see the accounting module plan for the derivation of each.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class AccountingHooks {

    private $CI;
    private $enabled;
    private $account_cache = [];

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Ledger_model');
        $row = $this->CI->db->get_where('company_settings', ['key_name' => 'accounting_autopost_enabled'])->row();
        $this->enabled = $row && $row->key_value === '1';
    }

    public function is_enabled() {
        return $this->enabled;
    }

    // ─── Account resolution helpers ──────────────────────────────────────

    private function acct($code) {
        if (!isset($this->account_cache[$code])) {
            $a = $this->CI->Ledger_model->get_account_by_code($code);
            if (!$a) throw new Exception("AccountingHooks: chart of accounts code '{$code}' not found — check sql/40_accounting_module.sql was run.");
            $this->account_cache[$code] = $a;
        }
        return $this->account_cache[$code];
    }

    // Resolves a payment_mode string to the ledger account it should hit, via
    // payment_mode_accounts (location-specific row first, else the global NULL row).
    // 'credit' and 'split' are never valid — they have no cash/bank leg of their own.
    public function resolve_payment_account($payment_mode, $location_id = null) {
        if (in_array($payment_mode, ['credit', 'split'], true)) {
            throw new Exception("resolve_payment_account() called with '{$payment_mode}' — this mode has no cash/bank leg, do not map it.");
        }
        $db = $this->CI->db;
        $row = null;
        if ($location_id) {
            $row = $db->get_where('payment_mode_accounts', ['location_id' => (int)$location_id, 'payment_mode' => $payment_mode])->row();
        }
        if (!$row) {
            $row = $db->get_where('payment_mode_accounts', ['location_id' => 0, 'payment_mode' => $payment_mode])->row();
        }
        if (!$row) {
            throw new Exception("No payment_mode_accounts mapping for mode '{$payment_mode}' — configure it under Accounting > Payment Mode Mapping.");
        }
        return (int)$row->account_id;
    }

    private function line($account_id, $debit = 0, $credit = 0, $party_type = null, $party_id = null, $narration = null) {
        return ['account_id' => $account_id, 'debit' => $debit, 'credit' => $credit, 'party_type' => $party_type, 'party_id' => $party_id, 'narration' => $narration];
    }

    // ─── Sales: Billing::create() / cancel() / update_payment_mode() ────

    // $inv keys: id, invoice_no, invoice_date, customer_id, location_id,
    // taxable_amount, cgst_amount, sgst_amount, igst_amount, paid_amount,
    // wallet_amount_used, wallet_amount_credited, due_amount, cd_amount,
    // bill_discount_amount, payment_mode, splits (optional array of
    // ['payment_mode'=>, 'amount'=>] for multi-mode payments).
    public function post_invoice(array $inv, $created_by = null) {
        if (!$this->enabled) return null;

        $lines = [];

        // Cash/bank leg — split across invoice_payment_splits if multi-mode, else one
        // line for the single payment_mode.
        $cash_paid = (float)($inv['paid_amount'] ?? 0);
        if (!empty($inv['splits'])) {
            foreach ($inv['splits'] as $s) {
                if ((float)$s['amount'] <= 0) continue;
                $lines[] = $this->line($this->resolve_payment_account($s['payment_mode'], $inv['location_id'] ?? null), (float)$s['amount'], 0);
            }
        } elseif ($cash_paid > 0 && !empty($inv['payment_mode']) && !in_array($inv['payment_mode'], ['credit', 'split'], true)) {
            $lines[] = $this->line($this->resolve_payment_account($inv['payment_mode'], $inv['location_id'] ?? null), $cash_paid, 0);
        }

        $wallet_credited = (float)($inv['wallet_amount_credited'] ?? 0);
        if ($wallet_credited > 0) {
            $lines[] = $this->line($this->resolve_payment_account($inv['payment_mode'] ?? 'cash', $inv['location_id'] ?? null), $wallet_credited, 0);
        }

        $due = (float)($inv['due_amount'] ?? 0);
        if ($due > 0) {
            $lines[] = $this->line($this->acct('1130')->id, $due, 0, 'customer', $inv['customer_id']);
        }

        $wallet_used = (float)($inv['wallet_amount_used'] ?? 0);
        if ($wallet_used > 0) {
            $lines[] = $this->line($this->acct('2130')->id, $wallet_used, 0);
        }

        $discount = (float)($inv['cd_amount'] ?? 0) + (float)($inv['bill_discount_amount'] ?? 0);
        if ($discount > 0) {
            $lines[] = $this->line($this->acct('5210')->id, $discount, 0);
        }

        $lines[] = $this->line($this->acct('4100')->id, 0, (float)($inv['taxable_amount'] ?? 0));
        if (!empty($inv['cgst_amount'])) $lines[] = $this->line($this->acct('2121')->id, 0, (float)$inv['cgst_amount']);
        if (!empty($inv['sgst_amount'])) $lines[] = $this->line($this->acct('2122')->id, 0, (float)$inv['sgst_amount']);
        if (!empty($inv['igst_amount'])) $lines[] = $this->line($this->acct('2123')->id, 0, (float)$inv['igst_amount']);

        return $this->CI->Ledger_model->post(
            $inv['invoice_date'], 'Invoice ' . $inv['invoice_no'], 'invoice', 'invoice', $inv['id'], $inv['invoice_no'],
            $lines, $created_by, $inv['location_id'] ?? null
        );
    }

    public function reverse_invoice($invoice_id, $reason = 'Invoice cancelled', $reversal_date = null, $created_by = null) {
        if (!$this->enabled) return null;
        $je = $this->CI->db->get_where('journal_entries', ['ref_type' => 'invoice', 'ref_id' => $invoice_id, 'status' => 'posted'])->row();
        if (!$je) return null; // nothing was posted for this invoice (e.g. flag was off at creation time)
        return $this->CI->Ledger_model->reverse($je->id, $reversal_date ?: date('Y-m-d'), $reason, $created_by);
    }

    // ─── Purchases: Purchases::create() / edit() ─────────────────────────

    // $p keys: id, purchase_no, purchase_date, supplier_id, location_id,
    // taxable_amount, total_gst, due_amount, paid_amount, discount, payment_mode.
    public function post_purchase(array $p, $created_by = null) {
        if (!$this->enabled) return null;

        $lines = [];
        $lines[] = $this->line($this->acct('5110')->id, (float)($p['taxable_amount'] ?? 0), 0);
        if (!empty($p['total_gst'])) $lines[] = $this->line($this->acct('1140')->id, (float)$p['total_gst'], 0);

        $due = (float)($p['due_amount'] ?? 0);
        if ($due > 0) $lines[] = $this->line($this->acct('2110')->id, 0, $due, 'supplier', $p['supplier_id']);

        $paid = (float)($p['paid_amount'] ?? 0);
        if ($paid > 0 && !empty($p['payment_mode']) && !in_array($p['payment_mode'], ['credit', 'split'], true)) {
            $lines[] = $this->line($this->resolve_payment_account($p['payment_mode'], $p['location_id'] ?? null), 0, $paid);
        }

        if (!empty($p['discount'])) $lines[] = $this->line($this->acct('4200')->id, 0, (float)$p['discount']);

        return $this->CI->Ledger_model->post(
            $p['purchase_date'], 'Purchase ' . $p['purchase_no'], 'purchase', 'purchase', $p['id'], $p['purchase_no'],
            $lines, $created_by, $p['location_id'] ?? null
        );
    }

    public function reverse_purchase($purchase_id, $reason = 'Purchase amended', $reversal_date = null, $created_by = null) {
        if (!$this->enabled) return null;
        $je = $this->CI->db->get_where('journal_entries', ['ref_type' => 'purchase', 'ref_id' => $purchase_id, 'status' => 'posted'])->row();
        if (!$je) return null;
        return $this->CI->Ledger_model->reverse($je->id, $reversal_date ?: date('Y-m-d'), $reason, $created_by);
    }

    // ─── Payments: Payments::receive() / pay_supplier() ──────────────────

    public function post_customer_payment($payment_no, $customer_id, $amount, $payment_mode, $payment_date, $location_id = null, $created_by = null) {
        if (!$this->enabled) return null;
        $lines = [
            $this->line($this->resolve_payment_account($payment_mode, $location_id), (float)$amount, 0),
            $this->line($this->acct('1130')->id, 0, (float)$amount, 'customer', $customer_id),
        ];
        return $this->CI->Ledger_model->post($payment_date, 'Payment received ' . $payment_no, 'customer_payment', 'payment', null, $payment_no, $lines, $created_by, $location_id);
    }

    public function post_supplier_payment($payment_no, $supplier_id, $amount, $payment_mode, $payment_date, $location_id = null, $created_by = null) {
        if (!$this->enabled) return null;
        $lines = [
            $this->line($this->acct('2110')->id, (float)$amount, 0, 'supplier', $supplier_id),
            $this->line($this->resolve_payment_account($payment_mode, $location_id), 0, (float)$amount),
        ];
        return $this->CI->Ledger_model->post($payment_date, 'Payment to supplier ' . $payment_no, 'vendor_payment', 'payment', null, $payment_no, $lines, $created_by, $location_id);
    }

    // ─── Refunds::_process_refund() (POS-style refund tool) ──────────────

    // $refund keys: id, refund_no, refund_date, invoice_id, customer_id, location_id,
    // refund_mode (cash|upi|neft|cheque|card|credit_note), refund_amount, due_amount
    // (the invoice's due_amount at refund time, for the credit_note cap).
    // $items: array of ['taxable_amt'=>, 'gst_amount'=>].
    public function post_refund(array $refund, array $items, $created_by = null) {
        if (!$this->enabled) return null;

        $taxable_total = array_sum(array_column($items, 'taxable_amt'));
        $gst_total     = array_sum(array_column($items, 'gst_amount'));

        $lines = [];
        if ($taxable_total > 0) $lines[] = $this->line($this->acct('4100')->id, (float)$taxable_total, 0);
        if ($gst_total > 0)     $lines[] = $this->line($this->acct('2120')->id, (float)$gst_total, 0);

        $refund_amount = (float)$refund['refund_amount'];
        if ($refund['refund_mode'] === 'credit_note') {
            $reduce = min($refund_amount, (float)($refund['due_amount'] ?? 0));
            if ($reduce > 0) $lines[] = $this->line($this->acct('1130')->id, 0, $reduce, 'customer', $refund['customer_id']);
            $excess = $refund_amount - $reduce;
            // Existing gap in Refunds::_process_refund(): refunding more than the
            // invoice's due_amount as a "credit note" has nowhere to go today (no
            // wallet credit, no cash out). Route it to Refund Suspense so it's a
            // visible balance to investigate/clear, instead of silently vanishing.
            if ($excess > 0.004) $lines[] = $this->line($this->acct('2140')->id, 0, $excess);
        } else {
            $lines[] = $this->line($this->resolve_payment_account($refund['refund_mode'], $refund['location_id'] ?? null), 0, $refund_amount);
        }

        return $this->CI->Ledger_model->post(
            $refund['refund_date'], 'Refund ' . $refund['refund_no'], 'invoice_cancel', 'refund', $refund['id'], $refund['refund_no'],
            $lines, $created_by, $refund['location_id'] ?? null
        );
    }

    // ─── SalesReturns::save()/cancel() (GST Credit Notes) ────────────────

    // $cn keys: id, return_no, return_date, customer_id, location_id,
    // taxable_amount, total_gst, total_amount, refund_method (balance|wallet|cash).
    public function post_credit_note(array $cn, $created_by = null) {
        if (!$this->enabled) return null;

        $lines = [];
        if (!empty($cn['taxable_amount'])) $lines[] = $this->line($this->acct('4100')->id, (float)$cn['taxable_amount'], 0);
        if (!empty($cn['total_gst']))      $lines[] = $this->line($this->acct('2120')->id, (float)$cn['total_gst'], 0);

        $total = (float)$cn['total_amount'];
        switch ($cn['refund_method']) {
            case 'wallet':
                $lines[] = $this->line($this->acct('2130')->id, 0, $total);
                break;
            case 'cash':
                // No bank/cash-account field exists on this form today — routed to the
                // default cash account, matching SME reality (till cash refunds).
                $lines[] = $this->line($this->resolve_payment_account('cash', $cn['location_id'] ?? null), 0, $total);
                break;
            default: // 'balance'
                $lines[] = $this->line($this->acct('1130')->id, 0, $total, 'customer', $cn['customer_id']);
        }

        return $this->CI->Ledger_model->post(
            $cn['return_date'], 'Credit Note ' . $cn['return_no'], 'credit_note', 'sales_return', $cn['id'], $cn['return_no'],
            $lines, $created_by, $cn['location_id'] ?? null
        );
    }

    public function reverse_credit_note($return_id, $reason = 'Credit note cancelled', $reversal_date = null, $created_by = null) {
        if (!$this->enabled) return null;
        $je = $this->CI->db->get_where('journal_entries', ['ref_type' => 'sales_return', 'ref_id' => $return_id, 'status' => 'posted'])->row();
        if (!$je) return null;
        return $this->CI->Ledger_model->reverse($je->id, $reversal_date ?: date('Y-m-d'), $reason, $created_by);
    }

    // ─── PurchaseReturns::save()/cancel() (Debit Notes) ───────────────────

    // $dn keys: id, return_no, return_date, supplier_id, location_id,
    // taxable_amount, total_gst, total_amount.
    // Net-new posting rule — Purchase_return_model never touches purchases.due_amount
    // or any supplier balance today, so there's nothing existing to mirror. The GL's
    // Sundry Creditors becomes the source of truth for debit notes going forward; see
    // the accounting module plan's "Known gap" note before enabling this in Phase 3.
    public function post_debit_note(array $dn, $created_by = null) {
        if (!$this->enabled) return null;

        $lines = [];
        $lines[] = $this->line($this->acct('2110')->id, (float)$dn['total_amount'], 0, 'supplier', $dn['supplier_id']);
        if (!empty($dn['taxable_amount'])) $lines[] = $this->line($this->acct('5110')->id, 0, (float)$dn['taxable_amount']);
        if (!empty($dn['total_gst']))      $lines[] = $this->line($this->acct('1140')->id, 0, (float)$dn['total_gst']);

        return $this->CI->Ledger_model->post(
            $dn['return_date'], 'Debit Note ' . $dn['return_no'], 'purchase_return', 'purchase_return', $dn['id'], $dn['return_no'],
            $lines, $created_by, $dn['location_id'] ?? null
        );
    }

    public function reverse_debit_note($return_id, $reason = 'Debit note cancelled', $reversal_date = null, $created_by = null) {
        if (!$this->enabled) return null;
        $je = $this->CI->db->get_where('journal_entries', ['ref_type' => 'purchase_return', 'ref_id' => $return_id, 'status' => 'posted'])->row();
        if (!$je) return null;
        return $this->CI->Ledger_model->reverse($je->id, $reversal_date ?: date('Y-m-d'), $reason, $created_by);
    }

    // ─── Expenses (Phase 2 module — posts directly, no retrofit risk) ────

    // $exp keys: id, expense_no, expense_date, account_id (the category), amount,
    // tax_amount, total_amount, payment_status (paid|unpaid), payment_mode,
    // location_id, supplier_id (nullable, for the Expenses Payable party tag).
    public function post_expense(array $exp, $created_by = null) {
        if (!$this->enabled) return null;

        $lines = [];
        $lines[] = $this->line((int)$exp['account_id'], (float)$exp['total_amount'], 0);

        if ($exp['payment_status'] === 'paid') {
            $lines[] = $this->line($this->resolve_payment_account($exp['payment_mode'], $exp['location_id'] ?? null), 0, (float)$exp['total_amount']);
        } else {
            $lines[] = $this->line($this->acct('2150')->id, 0, (float)$exp['total_amount'], $exp['supplier_id'] ? 'supplier' : null, $exp['supplier_id'] ?? null);
        }

        return $this->CI->Ledger_model->post(
            $exp['expense_date'], 'Expense ' . $exp['expense_no'], 'expense', 'expense', $exp['id'], $exp['expense_no'],
            $lines, $created_by, $exp['location_id'] ?? null
        );
    }

    public function reverse_expense($expense_id, $reason = 'Expense voided', $reversal_date = null, $created_by = null) {
        if (!$this->enabled) return null;
        $je = $this->CI->db->get_where('journal_entries', ['ref_type' => 'expense', 'ref_id' => $expense_id, 'status' => 'posted'])->row();
        if (!$je) return null;
        return $this->CI->Ledger_model->reverse($je->id, $reversal_date ?: date('Y-m-d'), $reason, $created_by);
    }
}
