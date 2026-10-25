<?php
// ============================================================
// FILE: application/libraries/SchemeEngine.php
// FMCG Scheme Calculation Engine — Production Ready
//
// Supports:
//   QTY_SLAB   — Buy X get Y free (recursive)
//   VALUE_DISC — % or flat discount on bill value
//   FREE_ITEM  — Free product on purchase
//   MIX_MATCH  — Buy from defined group → get benefit
//   COMBO      — Bundle pricing
//   CARTON     — Carton qty triggers
//   TARGET     — Monthly target bonus (cumulative)
//   CASH_DISC  — Cash payment discount
//   CREDIT_NOTE— Post-bill credit note
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class SchemeEngine {

    private $CI;
    private $invoice_date;
    private $invoice_time;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    // ============================================================
    // MAIN ENTRY POINT
    // Input:
    //   $customer_id  — int
    //   $items        — array of:
    //       ['product_id', 'product_name', 'qty', 'uom' (pcs/crt),
    //        'rate', 'taxable_amt', 'gst_percent',
    //        'brand_id', 'category_id', 'carton_size']
    //   $bill_total   — float (taxable total)
    //   $payment_mode — string (cash/credit/upi/etc.)
    //   $invoice_date — string Y-m-d (default today)
    // Output:
    //   ['schemes_applied', 'free_items', 'total_discount',
    //    'discount_breakup', 'credit_notes', 'warnings']
    // ============================================================
    public function evaluate($customer_id, $items, $bill_total, $payment_mode = 'credit', $invoice_date = null, $invoice_time = null) {
        if (empty($items)) return $this->_empty_result();

        $this->invoice_date = $invoice_date ?: date('Y-m-d');
        $this->invoice_time = $invoice_time ?: date('H:i:s');
        $result = $this->_empty_result();

        // Load active schemes applicable to this date and time
        $schemes = $this->_get_active_schemes($this->invoice_date, $this->invoice_time);
        if (empty($schemes)) return $result;

        // Filter to schemes applicable to this customer
        $schemes = $this->_filter_by_customer($schemes, $customer_id);

        // Sort by priority ASC (1 = highest priority first)
        usort($schemes, fn($a, $b) => $a->priority <=> $b->priority);

        // Enrich items with brand/category if not provided
        $items = $this->_enrich_items($items);

        // Track which schemes are exclusive (no combination)
        $exclusive_applied = false;

        foreach ($schemes as $scheme) {
            // Skip if exclusive scheme already applied
            if ($exclusive_applied && !$scheme->is_combination_allowed) continue;
            if ($exclusive_applied) continue; // If exclusive was first, stop

            $applied = null;

            switch ($scheme->scheme_type_code) {
                case 'QTY_SLAB':
                    $applied = $this->_apply_qty_slab($scheme, $items, $customer_id, $invoice_date);
                    break;
                case 'VALUE_DISC':
                    $applied = $this->_apply_value_discount($scheme, $items, $bill_total);
                    break;
                case 'FREE_ITEM':
                    $applied = $this->_apply_free_item($scheme, $items);
                    break;
                case 'MIX_MATCH':
                    $applied = $this->_apply_mix_match($scheme, $items);
                    break;
                case 'CARTON':
                    $applied = $this->_apply_carton($scheme, $items);
                    break;
                case 'TARGET':
                    $applied = $this->_apply_target($scheme, $items, $customer_id, $invoice_date);
                    break;
                case 'CASH_DISC':
                    $applied = $this->_apply_cash_discount($scheme, $bill_total, $payment_mode);
                    break;
                case 'CREDIT_NOTE':
                    $applied = $this->_apply_credit_note_scheme($scheme, $items, $bill_total);
                    break;
                case 'CHEAPEST_FREE':
                    $applied = $this->_apply_cheapest_free($scheme, $items);
                    break;
            }

            if (!$applied || ($applied['discount'] <= 0 && empty($applied['free_items']))) continue;

            // Cap benefit if max set
            if ($scheme->max_benefit_per_invoice > 0 && $applied['discount'] > $scheme->max_benefit_per_invoice) {
                $applied['discount'] = $scheme->max_benefit_per_invoice;
                $applied['note'] = ($applied['note'] ?? '') . ' [capped at ₹' . number_format($scheme->max_benefit_per_invoice, 2) . ']';
            }

            $result['total_discount']  += $applied['discount'];
            $result['discount_breakup'][] = [
                'scheme_id'    => $scheme->id,
                'scheme_name'  => $scheme->name,
                'scheme_type'  => $scheme->scheme_type_code,
                'benefit_type' => $applied['benefit_type'],
                'discount'     => round($applied['discount'], 2),
                'note'         => $applied['note'] ?? '',
                'product_ids'  => array_values(array_unique($applied['product_ids'] ?? [])),
            ];
            $result['schemes_applied'][] = $scheme->id;

            foreach (($applied['free_items'] ?? []) as $fi) {
                $result['free_items'][] = array_merge($fi, ['scheme_id' => $scheme->id, 'scheme_name' => $scheme->name]);
            }

            foreach (($applied['credit_notes'] ?? []) as $cn) {
                $result['credit_notes'][] = $cn;
            }

            if (!$scheme->is_combination_allowed) {
                $exclusive_applied = true;
            }
        }

        $result['total_discount'] = round($result['total_discount'], 2);
        return $result;
    }

    // ── Log applied schemes after invoice is saved ────────────
    public function log_applied($invoice_id, $invoice_no, $customer_id, $result, $applied_by = null) {
        foreach ($result['discount_breakup'] as $d) {
            $this->CI->db->insert('scheme_application_log', [
                'invoice_id'       => $invoice_id,
                'invoice_no'       => $invoice_no,
                'customer_id'      => $customer_id,
                'scheme_id'        => $d['scheme_id'],
                'scheme_name'      => $d['scheme_name'],
                'scheme_type'      => $d['scheme_type'],
                'applied_on_value' => 0,
                'discount_given'   => $d['discount'],
                'benefit_type'     => $this->_benefit_enum($d['benefit_type']),
                'applied_by'       => $applied_by,
                'applied_at'       => date('Y-m-d H:i:s'),
            ]);
        }
        foreach ($result['free_items'] as $fi) {
            $this->CI->db->insert('scheme_application_log', [
                'invoice_id'      => $invoice_id,
                'invoice_no'      => $invoice_no,
                'customer_id'     => $customer_id,
                'scheme_id'       => $fi['scheme_id'],
                'scheme_name'     => $fi['scheme_name'],
                'scheme_type'     => 'FREE_ITEM',
                'free_product_id' => $fi['product_id'],
                'free_qty'        => $fi['qty'],
                'discount_given'  => 0,
                'benefit_type'    => 'free_item',
                'applied_by'      => $applied_by,
                'applied_at'      => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ============================================================
    // SCHEME HANDLERS
    // ============================================================

    // ── QTY_SLAB: Buy X Get Y Free ───────────────────────────
    private function _apply_qty_slab($scheme, $items, $customer_id, $invoice_date) {
        $result = ['discount' => 0, 'free_items' => [], 'benefit_type' => 'free_item', 'note' => '', 'product_ids' => []];
        $slabs  = $this->_get_slabs($scheme->id);

        // A QTY_SLAB scheme created without explicit slab rows still carries a
        // complete rule on its header (min qty + reward), and the create form
        // exposes exactly those fields — so filling them in and adding no slabs
        // is a reasonable thing to do. Treat the header as one implicit slab
        // rather than silently awarding nothing.
        if (empty($slabs) && (float)$scheme->min_qty > 0
            && ((float)$scheme->free_qty > 0 || (float)$scheme->discount_percent > 0
                || (float)$scheme->discount_value > 0)) {
            $slabs = [(object)[
                'min_qty'          => (float)$scheme->min_qty,
                'max_qty'          => 0,                       // 0 = uncapped top tier
                'free_qty'         => (float)$scheme->free_qty,
                'discount_percent' => (float)$scheme->discount_percent,
                'discount_value'   => (float)$scheme->discount_value,
                'free_item_id'     => $scheme->free_item_id ?? null,
            ]];
        }

        $mapped_ids = $this->_get_item_map_ids($scheme->id);

        foreach ($items as $item) {
            if (!$this->_item_matches_scheme($item, $scheme, $mapped_ids)) continue;

            // Convert to PCS
            $qty_pcs = $this->_to_pcs($item['qty'], $item['uom'] ?? 'pcs', $item['carton_size'] ?? 1);

            // Find matching slab
            $matched_slab = null;
            foreach ($slabs as $slab) {
                $max = $slab->max_qty > 0 ? $slab->max_qty : PHP_INT_MAX;
                if ($qty_pcs >= $slab->min_qty && $qty_pcs <= $max) {
                    $matched_slab = $slab;
                }
            }
            if (!$matched_slab && !empty($slabs)) {
                // Only fall back to the highest slab when qty exceeds its max (uncapped
                // top tier) — NOT when qty is simply below every slab's minimum, which
                // must grant no benefit at all.
                $last_slab = end($slabs);
                $last_max  = $last_slab->max_qty > 0 ? $last_slab->max_qty : PHP_INT_MAX;
                if ($qty_pcs > $last_max) {
                    $matched_slab = $last_slab;
                }
            }

            if ($matched_slab) {
                $free_qty = (float)$matched_slab->free_qty;
                $disc_pct = (float)$matched_slab->discount_percent;
                $disc_val = (float)$matched_slab->discount_value;

                // Fallback to main scheme if slab has no reward defined
                if ($free_qty <= 0 && $disc_pct <= 0 && $disc_val <= 0) {
                    $free_qty = (float)$scheme->free_qty;
                    $disc_pct = (float)$scheme->discount_percent;
                    $disc_val = (float)$scheme->discount_value;
                }

                // Recursive: multiply benefit (e.g. buy 10 get 1, buy 20 get 2)
                if ($scheme->is_recursive && $matched_slab->min_qty > 0) {
                    $multiplier = floor($qty_pcs / $matched_slab->min_qty);
                    $free_qty   = $free_qty * $multiplier;
                }

                if ($free_qty > 0) {
                    $free_pid = $matched_slab->free_item_id ?: $item['product_id'];
                    $result['free_items'][] = [
                        'product_id'   => $free_pid,
                        'product_name' => $this->_product_name($free_pid),
                        'qty'          => $free_qty,
                        'uom'          => 'pcs',
                        'rate'         => 0,
                        'note'         => "Free on buying {$qty_pcs} pcs",
                    ];
                    $result['note'] .= "Buy {$qty_pcs} pcs → Get {$free_qty} free. ";
                }

                if ($disc_pct > 0) {
                    $result['discount']     += round($this->_item_mrp_value($item) * $disc_pct / 100, 2);
                    $result['benefit_type']  = 'discount_percent';
                    $result['note']         .= "{$disc_pct}% discount applied. ";
                    $result['product_ids'][] = $item['product_id'];
                }
                if ($disc_val > 0) {
                    $result['discount']     += $disc_val;
                    $result['benefit_type']  = 'discount_value';
                    $result['product_ids'][] = $item['product_id'];
                }
            }
        }
        return $result;
    }

    // ── VALUE_DISC: Discount on bill value ────────────────────
    private function _apply_value_discount($scheme, $items, $bill_total) {
        $result = ['discount' => 0, 'free_items' => [], 'benefit_type' => 'discount_percent', 'note' => '', 'product_ids' => []];

        // Calculate eligible value (scheme-mapped items only, or whole bill)
        $mapped_ids   = $this->_get_item_map_ids($scheme->id);
        $eligible_val = 0;

        foreach ($items as $item) {
            if ($this->_item_matches_scheme($item, $scheme, $mapped_ids)) {
                $eligible_val += $this->_item_mrp_value($item);
                $result['product_ids'][] = $item['product_id'];
            }
        }

        if ($eligible_val < $scheme->min_value) return $result;

        // Check slabs first
        $slabs = $this->_get_slabs($scheme->id);
        if (!empty($slabs)) {
            foreach (array_reverse($slabs) as $slab) {
                if ($eligible_val >= $slab->min_value) {
                    $disc_pct = (float)$slab->discount_percent;
                    $disc_val = (float)$slab->discount_value;

                    // Fallback to main scheme if slab has no reward defined
                    if ($disc_pct <= 0 && $disc_val <= 0) {
                        $disc_pct = (float)$scheme->discount_percent;
                        $disc_val = (float)$scheme->discount_value;
                    }

                    $disc_pct2 = (float)($slab->discount_percent2 ?? 0);
                    if ($disc_pct2 <= 0) $disc_pct2 = (float)($scheme->discount_percent2 ?? 0);

                    if ($disc_pct > 0) {
                        $c = $this->_cascade_discount($eligible_val, $disc_pct, $disc_pct2);
                        $result['discount']     = $c['discount'];
                        $result['benefit_type'] = 'discount_percent';
                        $result['note']         = "₹{$eligible_val} eligible → " . $c['label'];
                    } elseif ($disc_val > 0) {
                        $result['discount']     = $disc_val;
                        $result['benefit_type'] = 'discount_value';
                        $result['note']         = "Flat ₹{$disc_val} discount";
                    }
                    break;
                }
            }
        } else {
            // Direct from scheme_master
            if ($scheme->discount_percent > 0) {
                $c = $this->_cascade_discount($eligible_val, (float)$scheme->discount_percent,
                                              (float)($scheme->discount_percent2 ?? 0));
                $result['discount']     = $c['discount'];
                $result['benefit_type'] = 'discount_percent';
                $result['note']         = $c['label'] . " on ₹{$eligible_val}";
            } elseif ($scheme->discount_value > 0) {
                $result['discount']     = $scheme->discount_value;
                $result['benefit_type'] = 'discount_value';
            }
        }
        return $result;
    }

    // ── FREE_ITEM: Free product on purchase ───────────────────
    private function _apply_free_item($scheme, $items) {
        $result     = ['discount' => 0, 'free_items' => [], 'benefit_type' => 'free_item', 'note' => ''];
        $mapped_ids = $this->_get_item_map_ids($scheme->id);
        $triggered  = false;

        foreach ($items as $item) {
            if (!$this->_item_matches_scheme($item, $scheme, $mapped_ids)) continue;
            $qty_pcs = $this->_to_pcs($item['qty'], $item['uom'] ?? 'pcs', $item['carton_size'] ?? 1);
            if ($qty_pcs >= $scheme->min_qty && $this->_item_mrp_value($item) >= $scheme->min_value) {
                $triggered = true;
                break;
            }
        }

        if (!$triggered) return $result;

        // Load free items from scheme_free_items
        $free_rows = $this->CI->db->where('scheme_id', $scheme->id)->get('scheme_free_items')->result();
        foreach ($free_rows as $fr) {
            $qty = $fr->free_qty;
            // recursive
            if ($scheme->is_recursive && $scheme->min_qty > 0) {
                $trigger_item_qty = 0;
                foreach ($items as $item) {
                    if ($this->_item_matches_scheme($item, $scheme, $mapped_ids)) {
                        $trigger_item_qty += $this->_to_pcs($item['qty'], $item['uom'] ?? 'pcs', $item['carton_size'] ?? 1);
                    }
                }
                $qty = $fr->free_qty * floor($trigger_item_qty / $scheme->min_qty);
            }
            $result['free_items'][] = [
                'product_id'   => $fr->free_product_id,
                'product_name' => $this->_product_name($fr->free_product_id),
                'qty'          => $qty,
                'uom'          => $fr->free_uom,
                'rate'         => 0,
                'note'         => 'Free item from scheme',
            ];
        }

        // Fallback: use scheme_master free_qty
        if (empty($result['free_items']) && $scheme->free_qty > 0) {
            $free_pid = $scheme->free_item_id ?: ($mapped_ids[0] ?? null);
            if ($free_pid) {
                $result['free_items'][] = [
                    'product_id'   => $free_pid,
                    'product_name' => $this->_product_name($free_pid),
                    'qty'          => $scheme->free_qty,
                    'uom'          => 'pcs',
                    'rate'         => 0,
                    'note'         => 'Free from scheme',
                ];
            }
        }

        $result['note'] = 'Free item scheme triggered.';
        return $result;
    }

    // ── MIX_MATCH: Buy from groups → benefit ─────────────────
    private function _apply_mix_match($scheme, $items) {
        $result = ['discount' => 0, 'free_items' => [], 'benefit_type' => 'discount_percent', 'note' => '', 'product_ids' => []];

        // Load groups
        $groups = $this->CI->db->where('scheme_id', $scheme->id)->order_by('group_order')->get('scheme_mixmatch_groups')->result();
        if (empty($groups)) return $result;

        $all_satisfied = true;
        $total_mix_qty = 0;
        $total_mix_val = 0;
        $mix_product_ids = [];

        foreach ($groups as $grp) {
            $grp_items = $this->CI->db->where('group_id', $grp->id)->get('scheme_mixmatch_items')->result();
            $grp_pids  = array_column(array_map('get_object_vars', $grp_items), 'product_id');

            $grp_qty = 0;
            $grp_val = 0;
            foreach ($items as $item) {
                if (in_array($item['product_id'], $grp_pids)) {
                    $grp_qty += $this->_to_pcs($item['qty'], $item['uom'] ?? 'pcs', $item['carton_size'] ?? 1);
                    $grp_val += $this->_item_mrp_value($item);
                    $mix_product_ids[] = $item['product_id'];
                }
            }

            if ($grp_qty < $grp->min_qty_from_group) {
                $all_satisfied = false;
                break;
            }
            $total_mix_qty += $grp_qty;
            $total_mix_val += $grp_val;
        }
        $result['product_ids'] = $mix_product_ids;

        if (!$all_satisfied) return $result;

        // Apply benefit
        if ($scheme->discount_percent > 0) {
            $result['discount']     = round($total_mix_val * $scheme->discount_percent / 100, 2);
            $result['benefit_type'] = 'discount_percent';
            $result['note']         = "Mix & Match {$scheme->discount_percent}% on ₹{$total_mix_val}";
        } elseif ($scheme->free_qty > 0) {
            $result['free_items'][] = [
                'product_id'   => $scheme->free_item_id ?? 0,
                'product_name' => $this->_product_name($scheme->free_item_id),
                'qty'          => $scheme->free_qty,
                'uom'          => 'pcs',
                'rate'         => 0,
                'note'         => 'Mix & Match free item',
            ];
            $result['benefit_type'] = 'free_item';
            $result['note']         = "Mix & Match {$scheme->free_qty} free units";
        }
        return $result;
    }

    // ── CARTON: Carton qty triggers ───────────────────────────
    private function _apply_carton($scheme, $items) {
        $result     = ['discount' => 0, 'free_items' => [], 'benefit_type' => 'free_item', 'note' => '', 'product_ids' => []];
        $mapped_ids = $this->_get_item_map_ids($scheme->id);

        foreach ($items as $item) {
            if (!$this->_item_matches_scheme($item, $scheme, $mapped_ids)) continue;

            $crt_size = (int)($item['carton_size'] ?? $scheme->carton_size ?? 1);
            if ($crt_size < 1) $crt_size = 1;

            $qty_pcs = $this->_to_pcs($item['qty'], $item['uom'] ?? 'pcs', $crt_size);
            $qty_crt = floor($qty_pcs / $crt_size);

            if ($qty_crt < $scheme->min_qty) continue;

            $times = $scheme->is_recursive ? floor($qty_crt / $scheme->min_qty) : 1;

            if ($scheme->free_qty > 0) {
                $free_pid = $scheme->free_item_id ?: $item['product_id'];
                $result['free_items'][] = [
                    'product_id'   => $free_pid,
                    'product_name' => $this->_product_name($free_pid),
                    'qty'          => $scheme->free_qty * $times,
                    'uom'          => 'pcs',
                    'rate'         => 0,
                    'note'         => "Free on {$qty_crt} CRT purchase",
                ];
                $result['note'] .= "{$qty_crt} cartons → {$scheme->free_qty} free pcs. ";
            }
            if ($scheme->discount_percent > 0) {
                $result['discount']     += round($this->_item_mrp_value($item) * $scheme->discount_percent / 100 * $times, 2);
                $result['benefit_type']  = 'discount_percent';
                $result['product_ids'][] = $item['product_id'];
            }
        }
        return $result;
    }

    // ── TARGET: Cumulative purchase target ────────────────────
    private function _apply_target($scheme, $items, $customer_id, $invoice_date) {
        $result     = ['discount' => 0, 'free_items' => [], 'benefit_type' => 'discount_percent', 'note' => '', 'product_ids' => []];
        $mapped_ids = $this->_get_item_map_ids($scheme->id);

        $eligible_val = 0;
        foreach ($items as $item) {
            if ($this->_item_matches_scheme($item, $scheme, $mapped_ids)) {
                $eligible_val += $this->_item_mrp_value($item);
                $result['product_ids'][] = $item['product_id'];
            }
        }
        if ($eligible_val <= 0) return $result;

        // Get cumulative past purchases for period (MRP basis, matching current invoice)
        $period_start = $scheme->start_date;
        $q = $this->CI->db->select('COALESCE(SUM(ii.mrp * ii.qty),0) as past_total')
            ->from('invoices i')
            ->join('invoice_items ii', 'ii.invoice_id = i.id')
            ->join('products p', 'p.id = ii.product_id')
            ->where('i.customer_id', $customer_id)
            ->where('i.status !=', 'cancelled')
            ->where('i.invoice_date >=', $period_start)
            ->where('i.invoice_date <=', $invoice_date);

        // Filter by item map
        if (!empty($mapped_ids)) $q->where_in('p.id', $mapped_ids);
        if ($scheme->apply_on === 'brand')    $q->where('p.brand_id', $this->_get_scheme_brand($scheme->id));
        if ($scheme->apply_on === 'category') $q->where('p.category_id', $this->_get_scheme_category($scheme->id));

        $past = (float)$q->get()->row()->past_total;
        $new_total = $past + $eligible_val;

        if ($new_total < $scheme->target_amount) {
            $remaining = $scheme->target_amount - $past;
            $result['note'] = "Target: ₹" . number_format($scheme->target_amount, 0) . " | Done: ₹" . number_format($past, 0) . " | Remaining: ₹" . number_format(max(0, $remaining), 0);
            return $result;
        }

        // Target achieved — apply bonus on current invoice
        $already_paid = (float)$this->CI->db
            ->select('COALESCE(SUM(discount_given),0) as paid')
            ->where('scheme_id', $scheme->id)
            ->where('customer_id', $customer_id)
            ->get('scheme_application_log')->row()->paid;

        if ($already_paid > 0) return $result; // Already rewarded

        if ($scheme->discount_percent > 0) {
            $result['discount']     = round($eligible_val * $scheme->discount_percent / 100, 2);
            $result['benefit_type'] = 'discount_percent';
            $result['note']         = "Target ₹{$scheme->target_amount} achieved! Bonus {$scheme->discount_percent}%";
        }
        return $result;
    }

    // ── CASH_DISC: Cash payment discount ─────────────────────
    private function _apply_cash_discount($scheme, $bill_total, $payment_mode) {
        $result = ['discount' => 0, 'free_items' => [], 'benefit_type' => 'discount_percent', 'note' => ''];
        $cash_modes = ['cash', 'upi', 'neft', 'cheque'];
        if (!in_array(strtolower($payment_mode), $cash_modes)) return $result;

        if ($bill_total < $scheme->min_value) return $result;

        if ($scheme->discount_percent > 0) {
            $result['discount']     = round($bill_total * $scheme->discount_percent / 100, 2);
            $result['benefit_type'] = 'discount_percent';
            $result['note']         = "Cash discount {$scheme->discount_percent}%";
        }
        return $result;
    }

    // ── CREDIT_NOTE: Post-bill credit note ────────────────────
    private function _apply_credit_note_scheme($scheme, $items, $bill_total) {
        $result     = ['discount' => 0, 'free_items' => [], 'credit_notes' => [], 'benefit_type' => 'credit_note', 'note' => ''];
        $mapped_ids = $this->_get_item_map_ids($scheme->id);

        $eligible_val = 0;
        foreach ($items as $item) {
            if ($this->_item_matches_scheme($item, $scheme, $mapped_ids)) {
                $eligible_val += $this->_item_mrp_value($item);
            }
        }
        if ($eligible_val < $scheme->min_value) return $result;

        $cn_amount = round($eligible_val * $scheme->credit_note_percent / 100, 2);
        if ($cn_amount <= 0) return $result;

        $result['credit_notes'][] = [
            'amount'  => $cn_amount,
            'percent' => $scheme->credit_note_percent,
            'timing'  => $scheme->credit_note_timing,
            'note'    => "Credit note {$scheme->credit_note_percent}% = ₹{$cn_amount}",
        ];
        $result['note'] = "Credit note ₹{$cn_amount} will be issued ({$scheme->credit_note_timing})";
        return $result;
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function _get_active_schemes($date, $time) {
        $schemes = $this->CI->db
            ->select('sm.*, st.code as scheme_type_code')
            ->from('scheme_master sm')
            ->join('scheme_types st', 'st.id = sm.scheme_type_id')
            ->where('sm.is_active', 1)
            ->where('sm.start_date <=', $date)
            ->where('sm.end_date >=', $date)
            ->order_by('sm.priority', 'ASC')
            ->get()->result();

        $filtered = [];
        foreach ($schemes as $scheme) {
            // Check time-of-day constraints if set
            if (!empty($scheme->start_time) && !empty($scheme->end_time) && $scheme->start_time != '00:00:00' && $scheme->end_time != '00:00:00') {
                $start = $scheme->start_time;
                $end = $scheme->end_time;
                
                if ($start <= $end) {
                    // Normal range (e.g. 19:00:00 to 23:59:59)
                    if ($time < $start || $time > $end) {
                        continue;
                    }
                } else {
                    // Overnight range (e.g. 19:00:00 to 02:00:00 next day)
                    if ($time < $start && $time > $end) {
                        continue;
                    }
                }
            }
            $filtered[] = $scheme;
        }
        return $filtered;
    }

    private function _filter_by_customer($schemes, $customer_id) {
        $filtered = [];
        foreach ($schemes as $scheme) {
            if ($scheme->applicable_parties === 'all') {
                $filtered[] = $scheme;
                continue;
            }
            $exists = $this->CI->db
                ->where('scheme_id', $scheme->id)
                ->where('customer_id', $customer_id)
                ->count_all_results('scheme_party_map');
            if ($exists > 0) $filtered[] = $scheme;
        }
        return $filtered;
    }

    private function _get_slabs($scheme_id) {
        return $this->CI->db
            ->where('scheme_id', $scheme_id)
            ->order_by('min_qty', 'ASC')
            ->order_by('min_value', 'ASC')
            ->get('scheme_slabs')->result();
    }

    private function _get_item_map_ids($scheme_id) {
        $rows = $this->CI->db
            ->select('product_id')
            ->where('scheme_id', $scheme_id)
            ->where('map_type', 'trigger')
            ->where('product_id IS NOT NULL')
            ->get('scheme_item_map')->result();
        return array_column(array_map('get_object_vars', $rows), 'product_id');
    }

    private function _item_matches_scheme($item, $scheme, $mapped_ids) {
        // Expiry date validation
        if (isset($scheme->expiry_days_trigger) && $scheme->expiry_days_trigger !== null && $scheme->expiry_days_trigger !== '') {
            if (empty($item['expiry_date']) || $item['expiry_date'] == '0000-00-00') {
                return false;
            }
            $days_diff = (int)round((strtotime($item['expiry_date']) - strtotime($this->invoice_date)) / 86400);
            if ($days_diff < 0 || $days_diff > $scheme->expiry_days_trigger) {
                return false;
            }
        }

        // If no items mapped → scheme applies to ALL items
        if ($scheme->apply_on === 'invoice') return true;

        if ($scheme->apply_on === 'item' && !empty($mapped_ids)) {
            return in_array($item['product_id'], $mapped_ids);
        }
        if ($scheme->apply_on === 'brand') {
            $brand_id = $this->_get_scheme_brand($scheme->id);
            return $item['brand_id'] == $brand_id;
        }
        if ($scheme->apply_on === 'category') {
            $cat_id = $this->_get_scheme_category($scheme->id);
            return $item['category_id'] == $cat_id;
        }
        if ($scheme->apply_on === 'brand_category') {
            $brand_id = $this->_get_scheme_brand($scheme->id);
            $cat_id = $this->_get_scheme_category($scheme->id);
            return $item['brand_id'] == $brand_id && $item['category_id'] == $cat_id;
        }
        // Default: no mapping = apply to all
        return empty($mapped_ids);
    }

    private function _enrich_items($items) {
        foreach ($items as &$item) {
            if (empty($item['brand_id']) || empty($item['category_id']) || !isset($item['expiry_date'])) {
                $p = $this->CI->db
                    ->select('brand_id, category_id, carton_size, expiry_date')
                    ->where('id', $item['product_id'])
                    ->get('products')->row();
                if (empty($item['brand_id'])) {
                    $item['brand_id'] = $p->brand_id ?? 0;
                }
                if (empty($item['category_id'])) {
                    $item['category_id'] = $p->category_id ?? 0;
                }
                if (!isset($item['carton_size'])) {
                    $item['carton_size'] = $p->carton_size ?? 1;
                }
                if (!isset($item['expiry_date'])) {
                    $item['expiry_date'] = $p->expiry_date ?? null;
                }
            }
        }
        return $items;
    }

    private function _to_pcs($qty, $uom, $carton_size = 1) {
        if (strtolower($uom) === 'crt') return $qty * max(1, $carton_size);
        return $qty;
    }

    // Schemes give discount on MRP, not on the GST-extracted taxable/billed value —
    // a 10% scheme on a ₹100 MRP item is ₹10, regardless of what rate it was sold at.
    // ── CHEAPEST_FREE: pick N, the cheapest are free ──────────
    /**
     * "Buy 2 get 1 free" across a brand or category, where the customer picks
     * any N qualifying items and the LOWEST-MRP one(s) come free.
     *
     * min_qty  = how many items must be picked up to form one group (3 for 2+1)
     * free_qty = how many of the cheapest in that group are free (1 for 2+1)
     *
     * Ranking is by MRP, per the business rule ("the lower MRP item is free"),
     * but the money taken off is the item's SELLING RATE — that is what the
     * customer would otherwise have paid, and discounting by MRP on a
     * already-discounted line would give away more than the item is sold for.
     *
     * Every unit is expanded individually, so a single line with qty 3 works
     * exactly like three separate lines of qty 1.
     */
    private function _apply_cheapest_free($scheme, $items) {
        $result = ['discount' => 0, 'free_items' => [], 'benefit_type' => 'discount_value',
                   'note' => '', 'product_ids' => []];

        $group_size = (int)$scheme->min_qty;
        $free_each  = (int)$scheme->free_qty ?: 1;
        // A group must be bigger than what it gives away, or everything is free.
        if ($group_size < 2 || $free_each < 1 || $free_each >= $group_size) return $result;

        // Expand eligible lines into individual units. The same matcher every
        // other handler uses, so brand / category / item targeting and the
        // expiry-trigger rule all behave identically here.
        $mapped_ids = $this->_get_item_map_ids($scheme->id);
        $units = [];
        foreach ($items as $item) {
            if (!$this->_item_matches_scheme($item, $scheme, $mapped_ids)) continue;
            $qty = (int)floor($this->_to_pcs($item['qty'], $item['uom'] ?? 'pcs', $item['carton_size'] ?? 1));
            if ($qty < 1) continue;

            $mrp  = (float)($item['mrp'] ?? 0);
            $rate = (float)($item['rate'] ?? 0);
            if ($mrp <= 0) $mrp = $rate;          // no MRP recorded — rank by rate
            for ($i = 0; $i < $qty; $i++) {
                $units[] = ['product_id' => $item['product_id'],
                            'name' => $item['product_name'] ?? '',
                            'mrp' => $mrp, 'rate' => $rate];
            }
            $result['product_ids'][] = $item['product_id'];
        }

        if (count($units) < $group_size) return $result;

        // Cheapest first — those are the ones given away.
        usort($units, function ($a, $b) {
            if ($a['mrp'] == $b['mrp']) return $a['rate'] <=> $b['rate'];
            return $a['mrp'] <=> $b['mrp'];
        });

        $groups = (int)floor(count($units) / $group_size);
        if (!$scheme->is_recursive) $groups = min(1, $groups);   // once per bill unless repeating

        $free_count = $groups * $free_each;
        if ($free_count < 1) return $result;

        $discount = 0; $names = [];
        for ($i = 0; $i < $free_count && $i < count($units); $i++) {
            $discount += $units[$i]['rate'];
            $names[]   = $units[$i]['name'];
        }
        if ($discount <= 0) return $result;

        $result['discount']     = round($discount, 2);
        $result['benefit_type'] = 'discount_value';
        $result['note']         = 'Buy ' . ($group_size - $free_each) . ' get ' . $free_each
                                . ' free — ' . $free_count . ' cheapest item(s) free: '
                                . implode(', ', array_slice($names, 0, 4))
                                . (count($names) > 4 ? '…' : '');
        $result['product_ids']  = array_values(array_unique($result['product_ids']));
        return $result;
    }

    /**
     * "20% + 10% extra" — the second percentage applies to what is LEFT after
     * the first, not to the original value. 100 → 20 off → 80 → 8 off → 72,
     * a total of 28, not 30. That is how a stacked retail offer is normally
     * understood and advertised.
     *
     * Returns ['discount' => float, 'label' => string] so the caller can show
     * the customer how the two steps combined.
     */
    private function _cascade_discount($base, $pct1, $pct2 = 0) {
        $base = (float)$base;
        $d1   = round($base * (float)$pct1 / 100, 2);

        if ($pct2 <= 0) {
            return ['discount' => $d1, 'label' => $this->_pct_label($pct1) . '%'];
        }

        $d2    = round(($base - $d1) * (float)$pct2 / 100, 2);
        $total = round($d1 + $d2, 2);
        // The single rate that would give the same result, for the note.
        $eff   = $base > 0 ? round($total / $base * 100, 2) : 0;

        return [
            'discount' => $total,
            'label'    => $this->_pct_label($pct1) . '% + ' . $this->_pct_label($pct2)
                        . '% extra (= ' . $this->_pct_label($eff) . '% effective)',
        ];
    }

    /** Trims 10.00 to 10 so notes read cleanly. */
    private function _pct_label($p) {
        return rtrim(rtrim(number_format((float)$p, 2, '.', ''), '0'), '.');
    }

    private function _item_mrp_value($item) {
        return (float)($item['mrp'] ?? 0) * (float)($item['qty'] ?? 0);
    }

    private function _product_name($product_id) {
        if (!$product_id) return 'Free Item';
        $p = $this->CI->db->select('name')->where('id', $product_id)->get('products')->row();
        return $p ? $p->name : "Product #$product_id";
    }

    private function _get_scheme_brand($scheme_id) {
        $r = $this->CI->db->select('brand_id')->where('scheme_id', $scheme_id)->where('brand_id IS NOT NULL')->get('scheme_item_map')->row();
        return $r ? $r->brand_id : 0;
    }

    private function _get_scheme_category($scheme_id) {
        $r = $this->CI->db->select('category_id')->where('scheme_id', $scheme_id)->where('category_id IS NOT NULL')->get('scheme_item_map')->row();
        return $r ? $r->category_id : 0;
    }

    private function _benefit_enum($type) {
        $map = ['discount_percent' => 'discount_percent', 'discount_value' => 'discount_value', 'free_item' => 'free_item', 'credit_note' => 'credit_note'];
        return $map[$type] ?? 'discount_percent';
    }

    private function _empty_result() {
        return ['schemes_applied' => [], 'free_items' => [], 'total_discount' => 0, 'discount_breakup' => [], 'credit_notes' => [], 'warnings' => []];
    }
}
