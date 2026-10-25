<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loyalty points engine — eligibility, calculation, ledger, redemption, reversal.
 *
 * Two rules hold everywhere in this class:
 *   1. The ledger is the truth. `customers.loyalty_points` is a cached balance
 *      kept in step by adjust_points(); nothing else may write it. Same
 *      contract as customers.wallet_balance / customer_wallet_transactions.
 *   2. Points awarded on a bill are frozen onto invoice_items at sale time.
 *      Historical points are NEVER recalculated from the current item master.
 *
 * Business decisions (confirmed 2026-08-11):
 *   * Redemption is a TENDER, not a goods discount — it never touches taxable
 *     value or GST.
 *   * The earning base is the line total INCLUDING GST, i.e. what the customer
 *     actually pays for that line after item discount.
 */
class Loyalty_model extends CI_Model {

    public function schema_ready() {
        return $this->db->table_exists('loyalty_transactions');
    }

    // ── Rules ───────────────────────────────────────────────────────────────

    /** The rule in force for a tier today; highest priority wins. */
    public function active_scheme($tier = null) {
        $today = date('Y-m-d');
        $this->db->from('loyalty_schemes')->where('is_active', 1)
                 ->group_start()->where('start_date IS NULL', null, false)->or_where('start_date <=', $today)->group_end()
                 ->group_start()->where('end_date IS NULL', null, false)->or_where('end_date >=', $today)->group_end();
        if ($tier) {
            $this->db->group_start()->where('tier', $tier)->or_where('tier IS NULL', null, false)->group_end();
            // A tier-specific rule must beat the catch-all.
            $this->db->order_by('CASE WHEN tier IS NULL THEN 1 ELSE 0 END', 'ASC', false);
        } else {
            $this->db->where('tier IS NULL', null, false);
        }
        return $this->db->order_by('priority', 'DESC')->order_by('id', 'ASC')->limit(1)->get()->row();
    }

    /**
     * The catch-all customer used for bills with no named buyer. It must never
     * accumulate points however its flag is set, or every anonymous sale in the
     * shop pools into one meaningless account (§11).
     */
    public function is_walkin($customer_id) {
        static $walkin = null;
        if ($walkin === null) {
            $r = $this->db->select('key_value')->where('key_name', 'walkin_customer_id')
                          ->get('company_settings')->row();
            $walkin = ($r && ctype_digit((string)$r->key_value)) ? (int)$r->key_value : 4;
        }
        return (int)$customer_id === $walkin;
    }

    // ── Eligibility (spec §6 priority chain) ────────────────────────────────

    /**
     * Why a line may not earn. Returns NULL when it is eligible, otherwise a
     * short reason that is stored on the invoice line and shown on reports.
     *
     * Order matters: the first rule that says NO wins, and nothing later can
     * grant points back.
     */
    public function block_reason($product, $scheme_blocked = false, $customer = null) {
        if ($scheme_blocked)                                  return 'scheme';
        if ($product && (int)($product->allow_loyalty_points ?? 1) !== 1) return 'item';
        if ($product && isset($product->category_allows) && (int)$product->category_allows !== 1) return 'category';
        if ($customer && (int)($customer->loyalty_member ?? 0) !== 1)     return 'customer';
        if ($customer && ($customer->loyalty_status ?? 'active') !== 'active') return 'customer';
        return null;
    }

    /**
     * Products with their category's rule folded in — one query for the whole
     * bill rather than one per line.
     */
    public function product_rules(array $product_ids) {
        $out = [];
        $ids = array_filter(array_map('intval', $product_ids));
        if (!$ids || !$this->db->field_exists('allow_loyalty_points', 'products')) return $out;

        $rows = $this->db
            ->select('p.id, p.name, p.allow_loyalty_points, p.loyalty_points_per_unit,
                      p.loyalty_amount_per_point, p.category_id,
                      COALESCE(c.allow_loyalty_points, 1) AS category_allows')
            ->from('products p')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->where_in('p.id', array_unique($ids))
            ->get()->result();
        foreach ($rows as $r) $out[(int)$r->id] = $r;
        return $out;
    }

    // ── Calculation ─────────────────────────────────────────────────────────

    /** Points for one eligible line. Never returns a negative. */
    public function points_for_line($product, $base_amount, $qty, $scheme, $tier_multiplier = 1.0) {
        if (!$scheme) return 0.0;
        $base = max(0, (float)$base_amount);
        $qty  = max(0, (float)$qty);

        // An item-level fixed rate beats everything; then an item-level
        // rupees-per-point; then the scheme's own rule.
        if ($product && $product->loyalty_points_per_unit !== null && $product->loyalty_points_per_unit !== '') {
            $pts = (float)$product->loyalty_points_per_unit * $qty;
        } elseif ($product && $product->loyalty_amount_per_point !== null && (float)$product->loyalty_amount_per_point > 0) {
            $pts = $base / (float)$product->loyalty_amount_per_point;
        } else {
            switch ($scheme->calc_type) {
                case 'fixed_per_unit':
                    $pts = (float)$scheme->fixed_points * $qty; break;
                case 'points_per_amount':
                    $pts = $base * (float)$scheme->points_per_amount; break;
                case 'amount_per_point':
                default:
                    $per = (float)$scheme->amount_per_point;
                    $pts = $per > 0 ? $base / $per : 0;
            }
        }

        $pts *= (float)($tier_multiplier ?: 1);

        switch ($scheme->round_mode) {
            case 'round': $pts = round($pts); break;
            case 'ceil':  $pts = ceil($pts);  break;
            // floor by default — never award a part point
            default:      $pts = floor($pts);
        }
        return max(0.0, (float)$pts);
    }

    /**
     * Points for a whole bill, line by line (spec §7 — never bill-total ÷ rate).
     *
     * $items   : array of ['product_id','qty','total', optional 'scheme_blocked']
     * $customer: customer row, or NULL for a walk-in (earns nothing, §11)
     *
     * Returns ['lines' => [idx => ['points','base','reason']], 'total' => float]
     */
    public function calculate($items, $customer = null) {
        $out = ['lines' => [], 'total' => 0.0];
        if (!$this->schema_ready() || !$items) return $out;

        $enrolled = $customer && (int)($customer->loyalty_member ?? 0) === 1
                              && ($customer->loyalty_status ?? 'active') === 'active'
                              && !$this->is_walkin($customer->id ?? 0);

        $scheme = $this->active_scheme($customer->loyalty_tier ?? null);
        $rules  = $this->product_rules(array_column($items, 'product_id'));
        $mult   = $scheme ? (float)$scheme->tier_multiplier : 1.0;

        foreach ($items as $k => $it) {
            $prod   = $rules[(int)$it['product_id']] ?? null;
            $reason = $this->block_reason($prod, !empty($it['scheme_blocked']),
                                          $enrolled ? $customer : (object)['loyalty_member' => 0]);
            $base   = (float)($it['total'] ?? 0);   // GST-inclusive line total

            if ($reason !== null || !$scheme) {
                $out['lines'][$k] = ['points' => 0.0, 'base' => 0.0, 'reason' => $reason ?: 'no_scheme'];
                continue;
            }
            $pts = $this->points_for_line($prod, $base, $it['qty'] ?? 0, $scheme, $mult);
            $out['lines'][$k] = ['points' => $pts, 'base' => $base, 'reason' => null];
            $out['total'] += $pts;
        }
        return $out;
    }

    // ── Ledger ──────────────────────────────────────────────────────────────

    /**
     * The ONLY sanctioned way to move a customer's balance. Updates the cached
     * balance and writes the ledger row together, and refuses to go negative —
     * same contract as Customer_model::adjust_wallet().
     *
     * $direction: 'credit' adds points, 'debit' removes them.
     */
    public function adjust_points($customer_id, $points, $direction, $txn_type, array $meta = []) {
        if (!$this->schema_ready()) return false;
        $customer_id = (int)$customer_id;
        $points      = round((float)$points, 2);
        if ($customer_id <= 0 || $points <= 0) return false;

        $cust = $this->db->select('loyalty_points, loyalty_lifetime_earned, loyalty_lifetime_redeemed, loyalty_lifetime_expired')
                         ->where('id', $customer_id)->get('customers')->row();
        if (!$cust) return false;

        $opening = (float)$cust->loyalty_points;
        $closing = $direction === 'credit' ? $opening + $points : $opening - $points;
        if ($closing < 0) return false;   // a balance must never go negative

        $upd = ['loyalty_points' => $closing];
        if ($txn_type === 'EARN')   $upd['loyalty_lifetime_earned']   = (float)$cust->loyalty_lifetime_earned + $points;
        if ($txn_type === 'REDEEM') $upd['loyalty_lifetime_redeemed'] = (float)$cust->loyalty_lifetime_redeemed + $points;
        if ($txn_type === 'EXPIRY') $upd['loyalty_lifetime_expired']  = (float)$cust->loyalty_lifetime_expired + $points;
        $this->db->where('id', $customer_id)->update('customers', $upd);

        $this->db->insert('loyalty_transactions', [
            'customer_id'     => $customer_id,
            'txn_type'        => $txn_type,
            'points'          => $points,
            'opening_balance' => $opening,
            'closing_balance' => $closing,
            'reference_type'  => $meta['reference_type']  ?? null,
            'reference_id'    => $meta['reference_id']    ?? null,
            'reference_no'    => $meta['reference_no']    ?? null,
            'invoice_id'      => $meta['invoice_id']      ?? null,
            'invoice_item_id' => $meta['invoice_item_id'] ?? null,
            'product_id'      => $meta['product_id']      ?? null,
            'earned_on'       => $meta['earned_on']       ?? ($txn_type === 'EARN' ? date('Y-m-d') : null),
            'expiry_date'     => $meta['expiry_date']     ?? null,
            'remarks'         => $meta['remarks']         ?? null,
            'location_id'     => $meta['location_id']     ?? null,
            'created_by'      => $this->session->userdata('user_id'),
            'created_by_name' => $this->session->userdata('name'),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function balance($customer_id) {
        $r = $this->db->select('loyalty_points')->where('id', (int)$customer_id)->get('customers')->row();
        return $r ? (float)$r->loyalty_points : 0.0;
    }

    /** Ledger-derived balance, for the reconciliation report (§14). */
    public function ledger_balance($customer_id) {
        $r = $this->db->query(
            "SELECT COALESCE(SUM(CASE WHEN txn_type IN ('EARN','MANUAL_CREDIT') THEN points
                                      WHEN txn_type IN ('REDEEM','EXPIRY','MANUAL_DEBIT',
                                                        'SALES_RETURN_REVERSAL','BILL_CANCEL_REVERSAL')
                                      THEN -points ELSE 0 END), 0) AS bal
               FROM loyalty_transactions WHERE customer_id = ?", [(int)$customer_id])->row();
        return $r ? (float)$r->bal : 0.0;
    }

    public function statement($customer_id, $from = null, $to = null, $limit = 500) {
        $this->db->from('loyalty_transactions')->where('customer_id', (int)$customer_id);
        if ($from) $this->db->where('created_at >=', $from . ' 00:00:00');
        if ($to)   $this->db->where('created_at <=', $to . ' 23:59:59');
        return $this->db->order_by('id', 'DESC')->limit($limit)->get()->result();
    }

    // ── Earning ─────────────────────────────────────────────────────────────

    /**
     * Awards the points already computed onto the saved invoice lines. Call
     * INSIDE the invoice transaction so a failed bill never leaves points
     * behind (§12, §35).
     *
     * One ledger row per earning line, carrying invoice_item_id — that is what
     * lets a sales return reverse exactly the right line later (§18).
     */
    public function earn_for_invoice($invoice_id, $customer, $invoice_no, $location_id = null) {
        if (!$this->schema_ready() || !$customer) return 0.0;
        if ((int)($customer->loyalty_member ?? 0) !== 1) return 0.0;
        if (($customer->loyalty_status ?? 'active') !== 'active') return 0.0;
        if ($this->is_walkin($customer->id)) return 0.0;   // §11, whatever the flag says

        $scheme  = $this->active_scheme($customer->loyalty_tier ?? null);
        $expires = ($scheme && (int)$scheme->expiry_days > 0)
                 ? date('Y-m-d', strtotime('+' . (int)$scheme->expiry_days . ' day')) : null;

        $lines = $this->db->select('id, product_id, loyalty_points')
                          ->where('invoice_id', (int)$invoice_id)
                          ->where('loyalty_points >', 0)
                          ->get('invoice_items')->result();

        $total = 0.0;
        foreach ($lines as $ln) {
            $this->adjust_points($customer->id, $ln->loyalty_points, 'credit', 'EARN', [
                'reference_type'  => 'invoice',
                'reference_id'    => $invoice_id,
                'reference_no'    => $invoice_no,
                'invoice_id'      => $invoice_id,
                'invoice_item_id' => $ln->id,
                'product_id'      => $ln->product_id,
                'earned_on'       => date('Y-m-d'),
                'expiry_date'     => $expires,
                'location_id'     => $location_id,
                'remarks'         => 'Earned on ' . $invoice_no,
            ]);
            $total += (float)$ln->loyalty_points;
        }
        return $total;
    }

    // ── Redemption ──────────────────────────────────────────────────────────

    /**
     * How many points this customer may actually spend on a bill of this size,
     * applying every cap in §16. Returned as points, not rupees.
     */
    public function max_redeemable($customer, $bill_amount, $scheme = null) {
        if (!$customer || (int)($customer->loyalty_member ?? 0) !== 1) return 0.0;
        $scheme = $scheme ?: $this->active_scheme($customer->loyalty_tier ?? null);
        if (!$scheme) return 0.0;

        $bill = (float)$bill_amount;
        if ($bill < (float)$scheme->min_bill_amount) return 0.0;

        $rate = (float)$scheme->redeem_value_per_point ?: 1.0;
        $cap  = (float)$customer->loyalty_points;                       // cannot spend what they lack

        if ((float)$scheme->max_redeem_percent > 0) {                   // % of bill cap
            $cap = min($cap, ($bill * (float)$scheme->max_redeem_percent / 100) / $rate);
        }
        if ((float)$scheme->max_redeem_points > 0) {                    // absolute cap
            $cap = min($cap, (float)$scheme->max_redeem_points);
        }
        $cap = min($cap, $bill / $rate);                                // never exceed the bill

        $cap = floor($cap);
        if ($cap < (float)$scheme->min_redeem_points) return 0.0;       // below the floor, allow nothing
        return max(0.0, $cap);
    }

    public function points_to_money($points, $scheme = null) {
        $scheme = $scheme ?: $this->active_scheme();
        $rate = $scheme ? ((float)$scheme->redeem_value_per_point ?: 1.0) : 1.0;
        return round((float)$points * $rate, 2);
    }

    /** Debits the points and records the redemption. Call inside the invoice transaction. */
    public function redeem($customer, $points, $invoice_id, $invoice_no, $scheme = null, $location_id = null) {
        if (!$this->schema_ready() || !$customer || $points <= 0) return 0.0;
        $scheme = $scheme ?: $this->active_scheme($customer->loyalty_tier ?? null);
        $money  = $this->points_to_money($points, $scheme);

        $ok = $this->adjust_points($customer->id, $points, 'debit', 'REDEEM', [
            'reference_type' => 'invoice',
            'reference_id'   => $invoice_id,
            'reference_no'   => $invoice_no,
            'invoice_id'     => $invoice_id,
            'location_id'    => $location_id,
            'remarks'        => 'Redeemed ' . (int)$points . ' points (₹' . number_format($money, 2) . ') on ' . $invoice_no,
        ]);
        if (!$ok) return 0.0;

        $this->db->insert('loyalty_redemptions', [
            'customer_id'     => $customer->id,
            'invoice_id'      => $invoice_id,
            'invoice_no'      => $invoice_no,
            'points_redeemed' => $points,
            'discount_amount' => $money,
            'conversion_rate' => $scheme ? $scheme->redeem_value_per_point : 1,
            'scheme_id'       => $scheme->id ?? null,
            'created_by'      => $this->session->userdata('user_id'),
        ]);
        return $money;
    }

    // ── Reversal ────────────────────────────────────────────────────────────

    /**
     * Reverses the points for specific returned lines (§18) — only what those
     * lines earned, never the whole bill. $item_ids are invoice_items.id values
     * taken from refund_items.invoice_item_id.
     *
     * $ratio lets a partial-quantity return give back a proportional share.
     */
    public function reverse_for_return($invoice_id, array $item_ids, $refund_id, $refund_no, $ratios = []) {
        if (!$this->schema_ready() || !$item_ids) return 0.0;

        $rows = $this->db->where('invoice_id', (int)$invoice_id)
                         ->where('txn_type', 'EARN')
                         ->where_in('invoice_item_id', $item_ids)
                         ->get('loyalty_transactions')->result();

        $total = 0.0;
        foreach ($rows as $r) {
            $ratio     = isset($ratios[$r->invoice_item_id]) ? min(1, max(0, (float)$ratios[$r->invoice_item_id])) : 1.0;
            $available = (float)$r->points - (float)$r->reversed_points - (float)$r->expired_points;
            $take      = floor(min($available, (float)$r->points * $ratio));
            if ($take <= 0) continue;

            // A customer who has already spent the points can go to zero but
            // never below; take what is left rather than failing the return.
            $bal = $this->balance($r->customer_id);
            if ($take > $bal) $take = floor($bal);
            if ($take <= 0) continue;

            $this->adjust_points($r->customer_id, $take, 'debit', 'SALES_RETURN_REVERSAL', [
                'reference_type'  => 'refund',
                'reference_id'    => $refund_id,
                'reference_no'    => $refund_no,
                'invoice_id'      => $invoice_id,
                'invoice_item_id' => $r->invoice_item_id,
                'product_id'      => $r->product_id,
                'remarks'         => 'Reversed on return ' . $refund_no,
            ]);
            $this->db->where('id', $r->id)
                     ->set('reversed_points', 'reversed_points + ' . $take, false)
                     ->update('loyalty_transactions');
            $total += $take;
        }
        return $total;
    }

    /** Full reversal when a bill is cancelled (§19) — earned points out, redeemed points back. */
    public function reverse_for_cancel($invoice_id, $invoice_no) {
        if (!$this->schema_ready()) return ['reversed' => 0.0, 'restored' => 0.0];
        $out = ['reversed' => 0.0, 'restored' => 0.0];

        // Restore the redeemed points FIRST. Undoing the bill undoes the
        // spending before the earning — and doing it the other way round lets
        // the never-go-negative clamp eat part of the reversal on a bill that
        // both earned and redeemed.
        foreach ($this->db->where('invoice_id', (int)$invoice_id)->where('txn_type', 'REDEEM')
                          ->get('loyalty_transactions')->result() as $r) {
            $this->adjust_points($r->customer_id, $r->points, 'credit', 'BILL_CANCEL_REVERSAL', [
                'reference_type' => 'invoice', 'reference_id' => $invoice_id, 'reference_no' => $invoice_no,
                'invoice_id' => $invoice_id,
                'remarks' => 'Redeemed points returned — bill ' . $invoice_no . ' cancelled',
            ]);
            $out['restored'] += (float)$r->points;
        }

        foreach ($this->db->where('invoice_id', (int)$invoice_id)->where('txn_type', 'EARN')
                          ->get('loyalty_transactions')->result() as $r) {
            $take = (float)$r->points - (float)$r->reversed_points - (float)$r->expired_points;
            if ($take <= 0) continue;
            $bal = $this->balance($r->customer_id);
            if ($take > $bal) $take = floor($bal);
            if ($take <= 0) continue;
            $this->adjust_points($r->customer_id, $take, 'debit', 'BILL_CANCEL_REVERSAL', [
                'reference_type' => 'invoice', 'reference_id' => $invoice_id, 'reference_no' => $invoice_no,
                'invoice_id' => $invoice_id, 'invoice_item_id' => $r->invoice_item_id,
                'remarks' => 'Bill ' . $invoice_no . ' cancelled',
            ]);
            $this->db->where('id', $r->id)->set('reversed_points', 'reversed_points + ' . $take, false)
                     ->update('loyalty_transactions');
            $out['reversed'] += $take;
        }

        $this->db->where('invoice_id', (int)$invoice_id)->update('loyalty_redemptions', ['is_reversed' => 1]);
        return $out;
    }

    // ── Expiry (§20) ────────────────────────────────────────────────────────

    /** Expires everything due up to $as_of. Safe to run repeatedly. */
    public function expire_due($as_of = null) {
        if (!$this->schema_ready()) return ['customers' => 0, 'points' => 0.0];
        $as_of = $as_of ?: date('Y-m-d');

        $rows = $this->db->select('id, customer_id, points, reversed_points, expired_points, expiry_date, reference_no')
            ->from('loyalty_transactions')
            ->where('txn_type', 'EARN')
            ->where('expiry_date IS NOT NULL', null, false)
            ->where('expiry_date <=', $as_of)
            ->where('points > (reversed_points + expired_points)', null, false)
            ->order_by('expiry_date', 'ASC')
            ->get()->result();

        $seen = []; $total = 0.0;
        foreach ($rows as $r) {
            $take = (float)$r->points - (float)$r->reversed_points - (float)$r->expired_points;
            if ($take <= 0) continue;
            $bal = $this->balance($r->customer_id);
            if ($take > $bal) $take = floor($bal);   // already spent — nothing left to expire
            if ($take <= 0) {
                $this->db->where('id', $r->id)->set('expired_points', 'points - reversed_points', false)
                         ->update('loyalty_transactions');
                continue;
            }
            $this->adjust_points($r->customer_id, $take, 'debit', 'EXPIRY', [
                'reference_type' => 'expiry', 'reference_id' => $r->id,
                'remarks' => 'Points earned on ' . ($r->reference_no ?: '—') . ' expired on ' . $r->expiry_date,
            ]);
            $this->db->where('id', $r->id)->set('expired_points', 'expired_points + ' . $take, false)
                     ->update('loyalty_transactions');
            $seen[$r->customer_id] = true; $total += $take;
        }
        return ['customers' => count($seen), 'points' => $total];
    }

    /** Points due to expire in the next N days (§21). */
    public function expiring_soon($days = 30, $from = null, $to = null, $limit = 1000) {
        $from = $from ?: date('Y-m-d');
        $to   = $to   ?: date('Y-m-d', strtotime('+' . (int)$days . ' day'));
        // Raw query on purpose: the query builder mangles the arithmetic in the
        // SELECT list when it applies identifier protection.
        return $this->db->query(
            "SELECT c.id AS customer_id, c.name AS customer_name, c.phone, c.loyalty_tier,
                    (lt.points - lt.reversed_points - lt.expired_points) AS points,
                    lt.earned_on, lt.expiry_date,
                    DATEDIFF(lt.expiry_date, CURDATE()) AS days_left, lt.reference_no
               FROM loyalty_transactions lt
               JOIN customers c ON c.id = lt.customer_id
              WHERE lt.txn_type = 'EARN'
                AND lt.expiry_date >= ? AND lt.expiry_date <= ?
                AND lt.points > (lt.reversed_points + lt.expired_points)
              ORDER BY lt.expiry_date ASC
              LIMIT " . (int)$limit, [$from, $to])->result();
    }

    // ── Reports (§27) ───────────────────────────────────────────────────────

    /** One entry point for every loyalty report; all index-backed and capped. */
    public function report($type, array $f = []) {
        $limit = (int)($f['limit'] ?? 1000);
        $from  = $f['from'] ?? null;
        $to    = $f['to'] ?? null;

        // Shared date filter for ledger-based reports.
        $ledger = function ($txn_types) use ($from, $to) {
            $this->db->select('lt.created_at, c.name AS customer_name, c.phone, c.loyalty_tier,
                               lt.txn_type, lt.points, lt.closing_balance, lt.reference_no,
                               lt.remarks, lt.created_by_name')
                ->from('loyalty_transactions lt')
                ->join('customers c', 'c.id = lt.customer_id', 'left')
                ->where_in('lt.txn_type', $txn_types);
            if ($from) $this->db->where('lt.created_at >=', $from . ' 00:00:00');
            if ($to)   $this->db->where('lt.created_at <=', $to . ' 23:59:59');
            return $this->db->order_by('lt.id', 'DESC');
        };

        switch ($type) {
            case 'balances':
                $this->db->select('c.name AS customer_name, c.phone, c.loyalty_card_no, c.loyalty_tier,
                                   c.loyalty_points, c.loyalty_lifetime_earned, c.loyalty_lifetime_redeemed,
                                   c.loyalty_lifetime_expired, c.loyalty_status')
                    ->from('customers c')->where('c.loyalty_member', 1);
                if (!empty($f['q'])) $this->db->group_start()->like('c.name', $f['q'])->or_like('c.phone', $f['q'])->group_end();
                return $this->db->order_by('c.loyalty_points', 'DESC')->limit($limit)->get()->result();

            case 'top_customers':
                return $this->db->select('c.name AS customer_name, c.phone, c.loyalty_tier,
                                          c.loyalty_points, c.loyalty_lifetime_earned')
                    ->from('customers c')->where('c.loyalty_member', 1)
                    ->order_by('c.loyalty_lifetime_earned', 'DESC')->limit($limit)->get()->result();

            case 'earned':   return $ledger(['EARN'])->limit($limit)->get()->result();
            case 'redeemed': return $ledger(['REDEEM'])->limit($limit)->get()->result();
            case 'expired':  return $ledger(['EXPIRY'])->limit($limit)->get()->result();
            case 'adjustments': return $ledger(['MANUAL_CREDIT', 'MANUAL_DEBIT', 'ADJUSTMENT'])->limit($limit)->get()->result();
            case 'return_reversals': return $ledger(['SALES_RETURN_REVERSAL'])->limit($limit)->get()->result();
            case 'cancel_reversals': return $ledger(['BILL_CANCEL_REVERSAL'])->limit($limit)->get()->result();

            case 'expiring':
                return $this->expiring_soon((int)($f['days'] ?? 30), $from, $to, $limit);

            case 'itemwise':
                $this->db->select('p.name AS product_name, p.item_code, cat.name AS category_name,
                                   SUM(ii.loyalty_points) AS points, SUM(ii.qty) AS qty,
                                   SUM(ii.loyalty_base_amount) AS base_amount')
                    ->from('invoice_items ii')
                    ->join('products p', 'p.id = ii.product_id', 'left')
                    ->join('categories cat', 'cat.id = p.category_id', 'left')
                    ->join('invoices i', 'i.id = ii.invoice_id', 'left')
                    ->where('ii.loyalty_points >', 0);
                if ($from) $this->db->where('i.invoice_date >=', $from);
                if ($to)   $this->db->where('i.invoice_date <=', $to);
                return $this->db->group_by('ii.product_id')->order_by('points', 'DESC')
                                ->limit($limit)->get()->result();

            case 'categorywise':
                $this->db->select('cat.name AS category_name, COALESCE(cat.allow_loyalty_points,1) AS allows,
                                   SUM(ii.loyalty_points) AS points, SUM(ii.loyalty_base_amount) AS base_amount')
                    ->from('invoice_items ii')
                    ->join('products p', 'p.id = ii.product_id', 'left')
                    ->join('categories cat', 'cat.id = p.category_id', 'left')
                    ->join('invoices i', 'i.id = ii.invoice_id', 'left');
                if ($from) $this->db->where('i.invoice_date >=', $from);
                if ($to)   $this->db->where('i.invoice_date <=', $to);
                return $this->db->group_by('p.category_id')->order_by('points', 'DESC')
                                ->limit($limit)->get()->result();

            case 'non_eligible':   // §28 — the configuration audit
                return $this->db->select("p.item_code, p.name AS product_name, cat.name AS category_name,
                                          b.name AS brand_name, p.loyalty_block_reason,
                                          CASE WHEN COALESCE(cat.allow_loyalty_points,1)=0
                                               THEN 'Category blocked' ELSE 'Item blocked' END AS blocked_by,
                                          p.updated_at", false)
                    ->from('products p')
                    ->join('categories cat', 'cat.id = p.category_id', 'left')
                    ->join('brands b', 'b.id = p.brand_id', 'left')
                    ->group_start()
                        ->where('p.allow_loyalty_points', 0)
                        ->or_where('COALESCE(cat.allow_loyalty_points,1)', 0)
                    ->group_end()
                    ->order_by('cat.name')->order_by('p.name')->limit($limit)->get()->result();

            case 'scheme_excluded':   // §27 item report 15
                return $this->db->select('ii.loyalty_block_reason AS reason, p.name AS product_name,
                                          i.invoice_no, i.invoice_date, ii.total AS amount, c.name AS customer_name')
                    ->from('invoice_items ii')
                    ->join('invoices i', 'i.id = ii.invoice_id')
                    ->join('products p', 'p.id = ii.product_id', 'left')
                    ->join('customers c', 'c.id = i.customer_id', 'left')
                    ->where('ii.loyalty_block_reason', 'scheme')
                    ->order_by('i.id', 'DESC')->limit($limit)->get()->result();

            case 'reconciliation':   // §14 — cached balance vs ledger
                return $this->db->query(
                    "SELECT c.name AS customer_name, c.phone, c.loyalty_points AS cached,
                            COALESCE(SUM(CASE WHEN lt.txn_type IN ('EARN','MANUAL_CREDIT') THEN lt.points
                                              WHEN lt.txn_type IN ('REDEEM','EXPIRY','MANUAL_DEBIT',
                                                   'SALES_RETURN_REVERSAL','BILL_CANCEL_REVERSAL')
                                              THEN -lt.points ELSE 0 END), 0) AS ledger,
                            c.loyalty_points - COALESCE(SUM(CASE WHEN lt.txn_type IN ('EARN','MANUAL_CREDIT') THEN lt.points
                                              WHEN lt.txn_type IN ('REDEEM','EXPIRY','MANUAL_DEBIT',
                                                   'SALES_RETURN_REVERSAL','BILL_CANCEL_REVERSAL')
                                              THEN -lt.points ELSE 0 END), 0) AS difference
                       FROM customers c
                       LEFT JOIN loyalty_transactions lt ON lt.customer_id = c.id
                      WHERE c.loyalty_member = 1
                      GROUP BY c.id
                     HAVING difference <> 0
                      LIMIT " . $limit)->result();
        }
        return [];
    }

    /**
     * Points earned vs redeemed over a date range.
     *
     * Returns ['rows' => per-customer breakdown, 'totals' => grand totals].
     * The summary view uses only 'totals'; the detail view uses both.
     *
     * Expired and reversed points are carried as their own columns rather than
     * folded into "earned" — otherwise the net movement would not reconcile
     * against the ledger and the report would quietly lie.
     */
    public function earn_redeem_report($from = null, $to = null) {
        $out = ['rows' => [], 'totals' => (object)[
            'earned' => 0, 'redeemed' => 0, 'manual_credit' => 0, 'manual_debit' => 0,
            'expired' => 0, 'reversed' => 0, 'net' => 0, 'customers' => 0,
        ]];
        if (!$this->schema_ready()) return $out;

        $from = $from ?: date('Y-m-01');
        $to   = $to   ?: date('Y-m-d');

        $cols = "SUM(CASE WHEN lt.txn_type='EARN' THEN lt.points ELSE 0 END) AS earned,
                 SUM(CASE WHEN lt.txn_type='REDEEM' THEN lt.points ELSE 0 END) AS redeemed,
                 SUM(CASE WHEN lt.txn_type='MANUAL_CREDIT' THEN lt.points ELSE 0 END) AS manual_credit,
                 SUM(CASE WHEN lt.txn_type='MANUAL_DEBIT' THEN lt.points ELSE 0 END) AS manual_debit,
                 SUM(CASE WHEN lt.txn_type='EXPIRY' THEN lt.points ELSE 0 END) AS expired,
                 SUM(CASE WHEN lt.txn_type IN ('SALES_RETURN_REVERSAL','BILL_CANCEL_REVERSAL')
                          THEN lt.points ELSE 0 END) AS reversed";

        $out['rows'] = $this->db->query(
            "SELECT c.id AS customer_id, c.name AS customer_name, c.phone, c.loyalty_tier,
                    c.loyalty_points AS balance, $cols,
                    COUNT(*) AS txns
               FROM loyalty_transactions lt
               JOIN customers c ON c.id = lt.customer_id
              WHERE lt.created_at >= ? AND lt.created_at <= ?
              GROUP BY lt.customer_id
              ORDER BY earned DESC, redeemed DESC
              LIMIT 5000", [$from . ' 00:00:00', $to . ' 23:59:59'])->result();

        $t = $this->db->query(
            "SELECT $cols, COUNT(DISTINCT lt.customer_id) AS customers, COUNT(*) AS txns
               FROM loyalty_transactions lt
              WHERE lt.created_at >= ? AND lt.created_at <= ?",
            [$from . ' 00:00:00', $to . ' 23:59:59'])->row();

        if ($t) {
            $t->net = (float)$t->earned + (float)$t->manual_credit
                    - (float)$t->redeemed - (float)$t->manual_debit
                    - (float)$t->expired - (float)$t->reversed;
            $out['totals'] = $t;
        }
        return $out;
    }

    public function report_meta($type) {
        $m = [
            'balances'         => ['Customer Loyalty Balance', ['customer_name'=>'Customer','phone'=>'Mobile','loyalty_card_no'=>'Card','loyalty_tier'=>'Tier','loyalty_points'=>'Points','loyalty_lifetime_earned'=>'Earned','loyalty_lifetime_redeemed'=>'Redeemed','loyalty_lifetime_expired'=>'Expired','loyalty_status'=>'Status']],
            'top_customers'    => ['Top Loyalty Customers',    ['customer_name'=>'Customer','phone'=>'Mobile','loyalty_tier'=>'Tier','loyalty_points'=>'Balance','loyalty_lifetime_earned'=>'Lifetime Earned']],
            'earned'           => ['Points Earned',            ['created_at'=>'When','customer_name'=>'Customer','phone'=>'Mobile','points'=>'Points','closing_balance'=>'Balance','reference_no'=>'Bill','remarks'=>'Remarks']],
            'redeemed'         => ['Points Redeemed',          ['created_at'=>'When','customer_name'=>'Customer','phone'=>'Mobile','points'=>'Points','closing_balance'=>'Balance','reference_no'=>'Bill','remarks'=>'Remarks']],
            'expired'          => ['Points Expired',           ['created_at'=>'When','customer_name'=>'Customer','phone'=>'Mobile','points'=>'Points','closing_balance'=>'Balance','remarks'=>'Remarks']],
            'adjustments'      => ['Manual Adjustments',       ['created_at'=>'When','customer_name'=>'Customer','txn_type'=>'Type','points'=>'Points','closing_balance'=>'Balance','remarks'=>'Reason','created_by_name'=>'By']],
            'return_reversals' => ['Sales Return Reversals',   ['created_at'=>'When','customer_name'=>'Customer','points'=>'Points','closing_balance'=>'Balance','reference_no'=>'Return','remarks'=>'Remarks']],
            'cancel_reversals' => ['Cancelled Bill Reversals', ['created_at'=>'When','customer_name'=>'Customer','points'=>'Points','closing_balance'=>'Balance','reference_no'=>'Bill','remarks'=>'Remarks']],
            'expiring'         => ['Points Expiring Soon',     ['customer_name'=>'Customer','phone'=>'Mobile','loyalty_tier'=>'Tier','points'=>'Points','earned_on'=>'Earned','expiry_date'=>'Expires','days_left'=>'Days Left','reference_no'=>'Bill']],
            'itemwise'         => ['Item-wise Points',         ['product_name'=>'Item','item_code'=>'Code','category_name'=>'Category','qty'=>'Qty Sold','base_amount'=>'Eligible Value','points'=>'Points']],
            'categorywise'     => ['Category-wise Points',     ['category_name'=>'Category','allows'=>'Allows','base_amount'=>'Eligible Value','points'=>'Points']],
            'non_eligible'     => ['Non-Eligible Items',       ['item_code'=>'Code','product_name'=>'Item','category_name'=>'Category','brand_name'=>'Brand','blocked_by'=>'Blocked By','loyalty_block_reason'=>'Reason','updated_at'=>'Updated']],
            'scheme_excluded'  => ['Scheme Point Exclusions',  ['invoice_date'=>'Date','invoice_no'=>'Bill','customer_name'=>'Customer','product_name'=>'Item','amount'=>'Amount','reason'=>'Excluded By']],
            'reconciliation'   => ['Balance Reconciliation',   ['customer_name'=>'Customer','phone'=>'Mobile','cached'=>'Cached Balance','ledger'=>'Ledger Balance','difference'=>'Difference']],
        ];
        return $m[$type] ?? null;
    }

    public function report_types() {
        return [
            'Customer' => ['balances', 'top_customers', 'reconciliation'],
            'Transactions' => ['earned', 'redeemed', 'expired', 'expiring',
                               'adjustments', 'return_reversals', 'cancel_reversals'],
            'Items' => ['itemwise', 'categorywise', 'non_eligible', 'scheme_excluded'],
        ];
    }

    // ── Customer lookup for billing (§10) ───────────────────────────────────
    public function find_customer($q) {
        $q = trim((string)$q);
        if ($q === '') return null;
        return $this->db->from('customers')
            ->group_start()
                ->where('phone', $q)->or_where('loyalty_card_no', $q)->or_where('id', (int)$q)
            ->group_end()
            ->limit(1)->get()->row();
    }
}
