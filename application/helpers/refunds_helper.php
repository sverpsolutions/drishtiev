<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared refund-netting helper. Refunds (direct cash-back against an invoice)
 * and sales_returns (credit notes / confirmed returns) both represent money
 * given back to a customer, but neither one adjusts the original invoice's
 * own total_amount/paid_amount — so every sales/revenue total in this app
 * must subtract these separately, or it silently overstates real revenue.
 *
 * Note: neither `refunds` nor `sales_returns` carries a location_id column,
 * so these totals cannot currently be split per-outlet.
 */
if (!function_exists('get_refund_summary')) {
    function get_refund_summary($from, $to) {
        $CI =& get_instance();
        $out = ['total' => 0.0, 'cash' => 0.0, 'upi' => 0.0, 'card' => 0.0, 'bank' => 0.0];

        // Every caller of this helper first sums invoices with status != 'cancelled',
        // then subtracts this total from it. A refund that fully cancelled its original
        // invoice already has that invoice's total excluded from the caller's base sum —
        // subtracting the refund again on top double-counts it and under-reports real
        // sales by the refunded amount (confirmed live: a nishamart invoice refunded and
        // cancelled showed correctly in Reports::sales_summary(), which already guards
        // against this, but silently wrong on the Dashboard and every other caller of
        // this shared helper, which didn't). Excluding cancelled-invoice refunds here
        // fixes it everywhere at once instead of patching each call site separately.
        $refunds = $CI->db->select('refunds.*')
                          ->join('invoices', 'invoices.id = refunds.invoice_id', 'left')
                          ->where('DATE(refunds.created_at) >=', $from)
                          ->where('DATE(refunds.created_at) <=', $to)
                          ->where('refunds.status', 'processed')
                          ->where("(invoices.status IS NULL OR invoices.status != 'cancelled')", NULL, FALSE)
                          ->get('refunds')->result();
        foreach ($refunds as $r) {
            $out['total'] += (float)$r->refund_amount;
            switch ($r->refund_mode) {
                case 'cash':                             $out['cash'] += $r->refund_amount; break;
                case 'upi':                              $out['upi']  += $r->refund_amount; break;
                case 'card':                             $out['card'] += $r->refund_amount; break;
                case 'neft': case 'cheque': case 'bank': $out['bank'] += $r->refund_amount; break;
                // 'credit_note': no real cash movement, only reduces Net Sales via 'total'
            }
        }

        if ($CI->db->table_exists('sales_returns')) {
            $returns = $CI->db->select('sales_returns.*')
                              ->join('invoices', 'invoices.id = sales_returns.invoice_id', 'left')
                              ->where('sales_returns.return_date >=', $from)
                              ->where('sales_returns.return_date <=', $to)
                              ->where('sales_returns.status', 'confirmed')
                              ->where("(invoices.status IS NULL OR invoices.status != 'cancelled')", NULL, FALSE)
                              ->get('sales_returns')->result();
            foreach ($returns as $sr) {
                $out['total'] += (float)$sr->total_amount;
                if ($sr->refund_method === 'cash') $out['cash'] += $sr->total_amount;
                // 'balance'/store-credit: no real cash movement
            }
        }

        return $out;
    }

    // Convenience wrapper for single-day calculations (shift close, day close).
    function get_refund_summary_for_date($date) {
        return get_refund_summary($date, $date);
    }
}
