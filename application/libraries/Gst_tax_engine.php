<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Gst_tax_engine
 * 
 * Enterprise GST Tax Engine for Indian Readymade Garments & Multi-Category Retail.
 * Core Principles:
 *  1. PURCHASE AND SALES GST ARE CALCULATED INDEPENDENTLY.
 *  2. Garment Threshold Slabs (<= Rs. 2,500 -> 5%, > Rs. 2,500 -> 18%) are transaction-based,
 *     evaluated per piece on actual net taxable transaction value, not purchase cost or nominal MRP.
 *  3. Non-garment products (FMCG, general goods) strictly use their master configured rates.
 *  4. Tax-inclusive and tax-exclusive pricing models are fully supported with decimal precision.
 *  5. Configurable, date-effective rules table (`gst_tax_rules`).
 *  6. Intra-state (CGST + SGST) vs Inter-state (IGST) split.
 *  7. Input Tax Credit (ITC) tracking separate from cost expense ledger.
 */
class Gst_tax_engine {

    protected $CI;
    protected $rules_cache = [];
    protected $company_cache = null;

    public function __construct() {
        $this->CI =& get_instance();
        $this->load_rules();
    }

    /**
     * Load active tax rules from database into memory cache
     */
    public function load_rules($force_reload = false) {
        if (!empty($this->rules_cache) && !$force_reload) {
            return $this->rules_cache;
        }

        if ($this->CI->db->table_exists('gst_tax_rules')) {
            $rows = $this->CI->db->where('is_active', 1)
                                 ->order_by('effective_from', 'DESC')
                                 ->get('gst_tax_rules')->result();
            $this->rules_cache = $rows ?: [];
        } else {
            $this->rules_cache = [];
        }
        return $this->rules_cache;
    }

    /**
     * Get company/location state and GSTIN for interstate determination
     */
    public function get_company_gst_profile($location_id = null) {
        if ($this->company_cache !== null && $location_id === null) {
            return $this->company_cache;
        }

        $gstin = '';
        $state = '';
        $state_code = '';

        // If specific location
        if (!empty($location_id) && $this->CI->db->table_exists('locations')) {
            $loc = $this->CI->db->where('id', (int)$location_id)->get('locations')->row();
            if ($loc && !empty($loc->gstin)) {
                $gstin = strtoupper(trim($loc->gstin));
                $state = trim($loc->state ?? '');
            }
        }

        // Fallback to company settings
        if (empty($gstin)) {
            $this->CI->load->model('Setting_model');
            $settings = $this->CI->Setting_model->get_all();
            $gstin = strtoupper(trim($settings['company_gstin'] ?? ($settings['gst_number'] ?? '')));
            $state = trim($settings['company_state'] ?? ($settings['state'] ?? ''));
        }

        if (!empty($gstin) && strlen($gstin) >= 2 && is_numeric(substr($gstin, 0, 2))) {
            $state_code = substr($gstin, 0, 2);
        }

        $profile = [
            'gstin'      => $gstin,
            'state'      => $state,
            'state_code' => $state_code
        ];

        if ($location_id === null) {
            $this->company_cache = $profile;
        }
        return $profile;
    }

    /**
     * Determine if a transaction is interstate
     * 
     * @param string|object|array $party_info Customer/Supplier GSTIN, state, or object
     * @param int|null $location_id Outlet location ID
     * @return bool True if interstate (IGST), false if intra-state (CGST + SGST)
     */
    public function is_interstate($party_info, $location_id = null) {
        $comp = $this->get_company_gst_profile($location_id);
        $comp_state_code = $comp['state_code'];
        $comp_state = strtolower(trim($comp['state']));

        $party_gstin = '';
        $party_state = '';
        $party_state_code = '';

        if (is_object($party_info)) {
            $party_gstin = strtoupper(trim($party_info->gst_number ?? ($party_info->gstin ?? '')));
            $party_state = trim($party_info->state ?? '');
        } elseif (is_array($party_info)) {
            $party_gstin = strtoupper(trim($party_info['gst_number'] ?? ($party_info['gstin'] ?? '')));
            $party_state = trim($party_info['state'] ?? '');
        } elseif (is_string($party_info)) {
            if (strlen(trim($party_info)) === 15) {
                $party_gstin = strtoupper(trim($party_info));
            } else {
                $party_state = trim($party_info);
            }
        }

        if (!empty($party_gstin) && strlen($party_gstin) >= 2 && is_numeric(substr($party_gstin, 0, 2))) {
            $party_state_code = substr($party_gstin, 0, 2);
        }

        // If party has a GSTIN state code and company has a GSTIN state code
        if (!empty($party_state_code) && !empty($comp_state_code)) {
            return ($party_state_code !== $comp_state_code);
        }

        // If state names are provided
        if (!empty($party_state) && !empty($comp_state)) {
            return (strtolower(trim($party_state)) !== $comp_state);
        }

        // Walk-in retail customers without state default to intra-state (same state)
        return false;
    }

    /**
     * Find active tax rule for a given category, HSN and transaction date
     */
    public function get_effective_rule($tax_category = 'garment_slab', $hsn_code = null, $date = null) {
        $tx_date = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        $this->load_rules();

        foreach ($this->rules_cache as $rule) {
            if ($rule->tax_category !== $tax_category) {
                continue;
            }
            if (!empty($rule->effective_from) && $rule->effective_from > $tx_date) {
                continue;
            }
            if (!empty($rule->effective_to) && $rule->effective_to < $tx_date) {
                continue;
            }
            // If HSN prefix specified, check match
            if (!empty($rule->hsn_prefix) && !empty($hsn_code)) {
                $prefixes = array_map('trim', explode(',', $rule->hsn_prefix));
                $matched = false;
                foreach ($prefixes as $pfx) {
                    if (strpos(trim($hsn_code), $pfx) === 0) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) continue;
            }
            return $rule;
        }

        // Default garment threshold rule fallback
        if ($tax_category === 'garment_slab') {
            return (object)[
                'rule_code'           => 'GARMENT_SLAB_2500',
                'rule_name'           => 'Default Garment Slab',
                'tax_category'        => 'garment_slab',
                'threshold_amount'    => 2500.00,
                'rate_below_or_equal' => 5.00,
                'rate_above'          => 18.00,
            ];
        }

        return null;
    }

    /**
     * Check if a product is an eligible garment qualifying for threshold-based GST
     */
    public function is_garment_product($product) {
        if (is_object($product)) {
            if (!empty($product->is_garment)) return true;
            if (isset($product->tax_category) && $product->tax_category === 'garment_slab') return true;
            if (isset($product->product_type) && $product->product_type === 'garment') return true;
            // Check HSN prefix 61, 62, 63
            if (!empty($product->hsn_code)) {
                $hsn2 = substr(trim($product->hsn_code), 0, 2);
                if (in_array($hsn2, ['61', '62', '63'])) return true;
            }
        } elseif (is_array($product)) {
            if (!empty($product['is_garment'])) return true;
            if (isset($product['tax_category']) && $product['tax_category'] === 'garment_slab') return true;
            if (isset($product['product_type']) && $product['product_type'] === 'garment') return true;
            if (!empty($product['hsn_code'])) {
                $hsn2 = substr(trim($product['hsn_code']), 0, 2);
                if (in_array($hsn2, ['61', '62', '63'])) return true;
            }
        }
        return false;
    }

    /**
     * Determine GST Rate for a garment line based on taxable value per piece (Tax-Exclusive)
     * 
     * Legal Rule:
     *  - Taxable sale value <= Rs. 2,500.00 per piece: 5% GST
     *  - Taxable sale value > Rs. 2,500.00 per piece: 18% GST
     */
    public function determine_garment_rate_exclusive($taxable_value_per_piece, $date = null, $hsn_code = null) {
        $rule = $this->get_effective_rule('garment_slab', $hsn_code, $date);
        $threshold = (float)($rule->threshold_amount ?? 2500.00);
        $low_rate  = (float)($rule->rate_below_or_equal ?? 5.00);
        $high_rate = (float)($rule->rate_above ?? 18.00);

        // Threshold evaluation (decimal comparison)
        if (round((float)$taxable_value_per_piece, 4) <= round($threshold, 4)) {
            return [
                'rate'        => $low_rate,
                'rule_code'   => $rule->rule_code ?? 'GARMENT_SLAB_5',
                'threshold'   => $threshold,
                'is_slab'     => true,
            ];
        } else {
            return [
                'rate'        => $high_rate,
                'rule_code'   => $rule->rule_code ?? 'GARMENT_SLAB_18',
                'threshold'   => $threshold,
                'is_slab'     => true,
            ];
        }
    }

    /**
     * Determine GST Rate for a garment line based on inclusive price per piece (Tax-Inclusive)
     * 
     * In tax-inclusive retail, the threshold applies to the TAXABLE assessable value:
     *  - If inclusive price / (1 + 0.05) <= 2500.00 (i.e. inclusive price <= 2625.00),
     *    then the taxable value is <= 2500.00, so 5% applies.
     *  - If inclusive price / (1 + 0.05) > 2500.00 (i.e. inclusive price > 2625.00),
     *    then 5% cannot apply because the taxable value would exceed 2,500.00. Hence 18% applies.
     */
    public function determine_garment_rate_inclusive($inclusive_price_per_piece, $date = null, $hsn_code = null) {
        $rule = $this->get_effective_rule('garment_slab', $hsn_code, $date);
        $threshold = (float)($rule->threshold_amount ?? 2500.00);
        $low_rate  = (float)($rule->rate_below_or_equal ?? 5.00);
        $high_rate = (float)($rule->rate_above ?? 18.00);

        $inc = (float)$inclusive_price_per_piece;
        // Test if at low rate the taxable value is <= threshold
        $taxable_at_low = $inc / (1 + ($low_rate / 100));

        if (round($taxable_at_low, 4) <= round($threshold, 4)) {
            return [
                'rate'        => $low_rate,
                'rule_code'   => $rule->rule_code ?? 'GARMENT_SLAB_5',
                'threshold'   => $threshold,
                'is_slab'     => true,
            ];
        } else {
            return [
                'rate'        => $high_rate,
                'rule_code'   => $rule->rule_code ?? 'GARMENT_SLAB_18',
                'threshold'   => $threshold,
                'is_slab'     => true,
            ];
        }
    }

    /**
     * CALCULATE SALES LINE TAX (INDEPENDENT OF PURCHASE TAX)
     * 
     * Evaluates transaction value per piece, determines 5% or 18% (or master rate for non-garments),
     * handles tax-inclusive/exclusive prices, discounts, and splits CGST/SGST/IGST.
     * 
     * @param array $params [
     *    'product'          => product object or array,
     *    'unit_price'       => selling price per unit,
     *    'qty'              => quantity sold (pcs),
     *    'discount'         => discount amount on line (default 0),
     *    'is_tax_inclusive' => bool (true for retail MRP / inclusive POS),
     *    'is_interstate'    => bool,
     *    'transaction_date' => string date (default today),
     * ]
     * @return array Line tax calculation result
     */
    public function calculate_sales_line(array $params) {
        $product          = $params['product'] ?? null;
        $qty              = max(1, (float)($params['qty'] ?? 1));
        $unit_price       = (float)($params['unit_price'] ?? 0);
        $discount         = (float)($params['discount'] ?? 0);
        $is_tax_inclusive = isset($params['is_tax_inclusive']) ? (bool)$params['is_tax_inclusive'] : true;
        $is_interstate    = !empty($params['is_interstate']);
        $tx_date          = $params['transaction_date'] ?? date('Y-m-d');
        $hsn_code         = is_object($product) ? ($product->hsn_code ?? '') : ($product['hsn_code'] ?? '');

        $is_garment = $this->is_garment_product($product);

        $rate_info = null;
        $rate = 0.00;
        $rule_code = 'STANDARD';

        if ($is_garment) {
            // Garment threshold-based calculation
            if ($is_tax_inclusive) {
                // Effective inclusive price per piece after line discount
                $net_inclusive_line = max(0, ($unit_price * $qty) - $discount);
                $net_inclusive_pc   = $qty > 0 ? ($net_inclusive_line / $qty) : 0;
                $rate_info          = $this->determine_garment_rate_inclusive($net_inclusive_pc, $tx_date, $hsn_code);
            } else {
                // Exclusive price per piece after line discount
                $net_exclusive_line = max(0, ($unit_price * $qty) - $discount);
                $net_exclusive_pc   = $qty > 0 ? ($net_exclusive_line / $qty) : 0;
                $rate_info          = $this->determine_garment_rate_exclusive($net_exclusive_pc, $tx_date, $hsn_code);
            }
            $rate      = (float)$rate_info['rate'];
            $rule_code = $rate_info['rule_code'] . ($rate <= 5.0 ? '_5' : '_18');
        } else {
            // Non-garment product (FMCG / General goods): strictly use master GST rate
            $master_rate = 0.00;
            if (is_object($product)) {
                $master_rate = isset($product->gst_percent) ? (float)$product->gst_percent : (float)($product->sales_gst_rate ?? 0);
            } elseif (is_array($product)) {
                $master_rate = isset($product['gst_percent']) ? (float)$product['gst_percent'] : (float)($product['sales_gst_rate'] ?? 0);
            }
            $rate = $master_rate;
            $rule_code = 'STANDARD_MASTER_' . (int)$rate;
        }

        // Monetary calculations (decimal-safe)
        if ($is_tax_inclusive) {
            $line_total   = round(max(0, ($unit_price * $qty) - $discount), 2);
            $line_taxable = round($line_total / (1 + ($rate / 100)), 2);
            $line_gst     = round($line_total - $line_taxable, 2);
        } else {
            $line_taxable = round(max(0, ($unit_price * $qty) - $discount), 2);
            $line_gst     = round(($line_taxable * $rate) / 100, 2);
            $line_total   = round($line_taxable + $line_gst, 2);
        }

        // Split taxes: Interstate -> IGST, Intra-state -> CGST + SGST (50/50)
        if ($is_interstate) {
            $igst_percent = $rate;
            $cgst_percent = 0.00;
            $sgst_percent = 0.00;
            $igst_amount  = $line_gst;
            $cgst_amount  = 0.00;
            $sgst_amount  = 0.00;
        } else {
            $cgst_percent = round($rate / 2, 2);
            $sgst_percent = round($rate - $cgst_percent, 2);
            $cgst_amount  = round($line_gst / 2, 2);
            $sgst_amount  = round($line_gst - $cgst_amount, 2);
            $igst_percent = 0.00;
            $igst_amount  = 0.00;
        }

        $taxable_per_piece = $qty > 0 ? round($line_taxable / $qty, 2) : 0;

        return [
            'is_garment'        => $is_garment,
            'is_tax_inclusive'  => $is_tax_inclusive,
            'is_interstate'     => $is_interstate,
            'unit_price'        => round($unit_price, 2),
            'qty'               => $qty,
            'discount'          => round($discount, 2),
            'taxable_amount'    => $line_taxable,
            'taxable_per_piece' => $taxable_per_piece,
            'gst_percent'       => $rate,
            'gst_amount'        => $line_gst,
            'cgst_percent'      => $cgst_percent,
            'sgst_percent'      => $sgst_percent,
            'igst_percent'      => $igst_percent,
            'cgst_amount'       => $cgst_amount,
            'sgst_amount'       => $sgst_amount,
            'igst_amount'       => $igst_amount,
            'total_amount'      => $line_total,
            'tax_rule_applied'  => $rule_code,
        ];
    }

    /**
     * CALCULATE PURCHASE LINE TAX (INDEPENDENT OF SALES TAX)
     * 
     * Captures supplier invoice taxable value, rate, CGST/SGST/IGST, and tracks eligible Input Tax Credit (ITC).
     * Validates supplier GST rate against legal thresholds if garment.
     * 
     * @param array $params [
     *    'product'           => product object or array,
     *    'price'             => basic purchase price per unit from supplier invoice,
     *    'qty'               => quantity purchased,
     *    'discount'          => discount from supplier,
     *    'supplier_gst_rate' => GST rate on supplier invoice (or null to evaluate from taxable cost),
     *    'is_tax_inclusive'  => bool (default false for purchases),
     *    'is_interstate'     => bool,
     *    'itc_eligibility'   => 'eligible'|'ineligible'|'blocked' (default 'eligible'),
     *    'transaction_date'  => string date,
     * ]
     * @return array Purchase tax line result
     */
    public function calculate_purchase_line(array $params) {
        $product           = $params['product'] ?? null;
        $qty               = max(1, (float)($params['qty'] ?? 1));
        $price             = (float)($params['price'] ?? 0);
        $discount          = (float)($params['discount'] ?? 0);
        $supplier_gst_rate = isset($params['supplier_gst_rate']) ? (float)$params['supplier_gst_rate'] : null;
        $is_tax_inclusive  = !empty($params['is_tax_inclusive']);
        $is_interstate     = !empty($params['is_interstate']);
        $itc_eligibility   = $params['itc_eligibility'] ?? 'eligible';
        $tx_date           = $params['transaction_date'] ?? date('Y-m-d');
        $hsn_code          = is_object($product) ? ($product->hsn_code ?? '') : ($product['hsn_code'] ?? '');

        $is_garment = $this->is_garment_product($product);

        // Compute taxable amount
        if ($is_tax_inclusive) {
            $gross = max(0, ($price * $qty) - $discount);
            // If supplier gave rate, use it, else determine from garment slab
            if ($supplier_gst_rate !== null) {
                $rate = $supplier_gst_rate;
            } else {
                $eval = $this->determine_garment_rate_inclusive($qty > 0 ? $gross / $qty : 0, $tx_date, $hsn_code);
                $rate = $eval['rate'];
            }
            $line_taxable = round($gross / (1 + ($rate / 100)), 2);
            $line_gst     = round($gross - $line_taxable, 2);
            $line_total   = $gross;
        } else {
            $line_taxable = round(max(0, ($price * $qty) - $discount), 2);
            $taxable_pc   = $qty > 0 ? ($line_taxable / $qty) : 0;

            if ($supplier_gst_rate !== null) {
                $rate = $supplier_gst_rate;
            } elseif ($is_garment) {
                $eval = $this->determine_garment_rate_exclusive($taxable_pc, $tx_date, $hsn_code);
                $rate = $eval['rate'];
            } else {
                $master_rate = 0.00;
                if (is_object($product)) {
                    $master_rate = isset($product->gst_percent) ? (float)$product->gst_percent : (float)($product->purchase_gst_rate ?? 0);
                } elseif (is_array($product)) {
                    $master_rate = isset($product['gst_percent']) ? (float)$product['gst_percent'] : (float)($product['purchase_gst_rate'] ?? 0);
                }
                $rate = $master_rate;
            }

            $line_gst   = round(($line_taxable * $rate) / 100, 2);
            $line_total = round($line_taxable + $line_gst, 2);
        }

        // Validate supplier rate if garment
        $validation_warning = null;
        if ($is_garment) {
            $taxable_pc = $qty > 0 ? ($line_taxable / $qty) : 0;
            $legal_eval = $this->determine_garment_rate_exclusive($taxable_pc, $tx_date, $hsn_code);
            if (abs($legal_eval['rate'] - $rate) > 0.01) {
                $validation_warning = "Supplier invoice GST rate ({$rate}%) differs from statutory slab rate ({$legal_eval['rate']}%) for taxable value of Rs. " . number_format($taxable_pc, 2) . " per piece.";
            }
        }

        // Split taxes: Interstate -> IGST, Intra-state -> CGST + SGST (50/50)
        if ($is_interstate) {
            $igst_percent = $rate;
            $cgst_percent = 0.00;
            $sgst_percent = 0.00;
            $igst_amount  = $line_gst;
            $cgst_amount  = 0.00;
            $sgst_amount  = 0.00;
        } else {
            $cgst_percent = round($rate / 2, 2);
            $sgst_percent = round($rate - $cgst_percent, 2);
            $cgst_amount  = round($line_gst / 2, 2);
            $sgst_amount  = round($line_gst - $cgst_amount, 2);
            $igst_percent = 0.00;
            $igst_amount  = 0.00;
        }

        // ITC Amount tracking
        $itc_amount = ($itc_eligibility === 'eligible') ? $line_gst : 0.00;

        return [
            'is_garment'         => $is_garment,
            'is_tax_inclusive'   => $is_tax_inclusive,
            'is_interstate'      => $is_interstate,
            'price'              => round($price, 2),
            'qty'                => $qty,
            'discount'           => round($discount, 2),
            'taxable_amount'     => $line_taxable,
            'taxable_per_piece'  => $qty > 0 ? round($line_taxable / $qty, 2) : 0,
            'gst_percent'        => $rate,
            'gst_amount'         => $line_gst,
            'cgst_percent'       => $cgst_percent,
            'sgst_percent'       => $sgst_percent,
            'igst_percent'       => $igst_percent,
            'cgst_amount'        => $cgst_amount,
            'sgst_amount'        => $sgst_amount,
            'igst_amount'        => $igst_amount,
            'total_amount'       => $line_total,
            'itc_eligibility'    => $itc_eligibility,
            'itc_amount'         => $itc_amount,
            'validation_warning' => $validation_warning,
            'tax_rule_applied'   => $is_garment ? ('PURCHASE_GARMENT_' . (int)$rate) : ('PURCHASE_STANDARD_' . (int)$rate)
        ];
    }
}
