<?php
// ============================================================
// FILE: application/controllers/Billing.php  (UPDATED)
//
// Key changes vs original:
//  1. get_product_ajax() now returns igst_percent, cgst_percent,
//     sgst_percent from hsn_master via Product_model.
//  2. create(): per-line cgst_amount/sgst_amount/igst_amount
//     stored in invoice_items based on is_interstate flag.
//  3. cancel(): restores stock using internal_qty via multiplier
//     (same logic fix as original, with per-line GST restore-safe).
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Billing extends Auth_Controller {

    // Loading the whole catalog unbounded (embedded as inline JSON for the offline-POS
    // IndexedDB seed) crashes past a few thousand products — 56k+ on guptabros OOM'd the
    // web memory_limit. Cap it; the billing screen's own product search/scan already hits
    // the DB per lookup rather than relying solely on this initial array.
    const PRODUCTS_PAGE_LIMIT = 500;

    public function __construct() {
        parent::__construct();
        $this->load->model(['Invoice_model', 'Customer_model', 'Product_model', 'Location_model', 'Plu_model']);
        $this->load->library('form_validation');
    }

    // List all invoices
    public function index() {
        $data['page_title'] = 'Sales Invoices';
        $from = $this->input->get('from', TRUE) ?: date('Y-m-d');
        $to   = $this->input->get('to', TRUE)   ?: date('Y-m-d');
        $data['from']       = $from;
        $data['to']         = $to;

        // Outlet/warehouse staff have a fixed location and must never see another
        // outlet's invoices — their fixed location always wins over any query param.
        $fixed_loc = $this->session->userdata('location_id');
        $location_id = $fixed_loc ? (int)$fixed_loc : ((int)$this->input->get('location_id', TRUE) ?: null);
        $data['location_id'] = $location_id;
        $data['locations']   = $fixed_loc
            ? array_values(array_filter($this->Location_model->get_all(true), function($l) use ($fixed_loc) { return (int)$l->id === (int)$fixed_loc; }))
            : $this->Location_model->get_all(true);

        $data['invoices']   = $this->Invoice_model->get_all_with_customer($from, $to, $location_id);

        $counter_totals = [];
        foreach ($data['invoices'] as $inv) {
            if ($inv->status === 'cancelled') continue;
            $label = !empty($inv->device_label) ? $inv->device_label : 'Unassigned';
            if (!isset($counter_totals[$label])) {
                $counter_totals[$label] = ['count' => 0, 'total' => 0];
            }
            $counter_totals[$label]['count']++;
            $counter_totals[$label]['total'] += $inv->total_amount;
        }
        arsort($counter_totals);
        $data['counter_totals'] = $counter_totals;
        $data['grand_total'] = [
            'count' => array_sum(array_column($counter_totals, 'count')),
            'total' => array_sum(array_column($counter_totals, 'total')),
        ];

        $perm = $this->db->where('user_id', $this->session->userdata('user_id'))->get('user_permissions')->row();
        $allPm = $this->db->where('key_name', 'allow_all_payment_mode_edit')->get('company_settings')->row();
        $data['allow_payment_mode_change'] = ($perm ? (int)$perm->allow_payment_mode_change : 0)
            || ($allPm && $allPm->key_value === '1');
        $data['payment_modes'] = $this->_get_payment_modes();

        $this->render('billing/index', $data);
    }

    // Create new invoice
    public function create() {
        $data['page_title']      = 'New Invoice';
        $data['customers']       = $this->Customer_model->get_all_with_balance();
        $data['products']        = $this->Product_model->get_all($this->active_location_id(), self::PRODUCTS_PAGE_LIMIT);
        $data['company']         = $this->Invoice_model->get_company_settings();
        $data['settings']        = array_merge($this->db->get('company_settings')->row_array() ?: [], $data['company']);
        $data['billing_loc_id']  = $this->active_location_id();
        $data['can_discount']    = $this->can_module('mod_discount');
        $data['can_loyalty']     = $this->can_module('mod_loyalty');
        $data['payment_modes']   = $this->_get_payment_modes();

        // Billing UI version toggle (Settings > Billing). 'v2' is the new redesigned
        // screen running alongside the original 'v1' while it's being validated.
        $billing_view = (($data['company']['billing_ui_version'] ?? 'v1') === 'v2')
            ? 'billing/create_v2' : 'billing/create';

        $perm = $this->db->where('user_id', $this->session->userdata('user_id'))->get('user_permissions')->row();
        $data['allow_due_bills'] = $perm ? (int)$perm->allow_due_bills : 0;
        $data['allow_payment_mode_change'] = $perm ? (int)$perm->allow_payment_mode_change : 0;
        $data['allow_price_override'] = $perm && isset($perm->allow_price_override) ? (int)$perm->allow_price_override : 1;

        // Pre-fill from converted order request — one-shot: consumed and cleared here so a
        // stale conversion doesn't keep pre-filling every later "New Bill" in this session.
        // The order_id travels onward via the posted 'convert_order_id' hidden field, not session.
        $convert_order = $this->session->userdata('convert_order');
        if ($convert_order) {
            $data['convert_order'] = $convert_order;
            $this->session->unset_userdata('convert_order');
        }

        // Same one-shot pattern for a GRN being converted to a pass-through sales
        // invoice (Grn::convert_to_invoice). Travels onward via 'convert_grn_id'.
        $convert_grn = $this->session->userdata('convert_grn');
        if ($convert_grn) {
            $data['convert_grn'] = $convert_grn;
            $this->session->unset_userdata('convert_grn');
        }

        // Same pattern again for a Purchase being converted (Purchases::convert_to_invoice)
        // -- this business's real purchase entries live in Purchases, not GRN.
        $convert_purchase = $this->session->userdata('convert_purchase');
        if ($convert_purchase) {
            $data['convert_purchase'] = $convert_purchase;
            $this->session->unset_userdata('convert_purchase');
        }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $data['submit_token'] = bin2hex(random_bytes(16));
            $this->session->set_userdata('billing_submit_token', $data['submit_token']);
            $this->render($billing_view, $data);
            return;
        }

        // Reject duplicate form submissions (double-click / browser back+resubmit)
        $posted_token  = $this->input->post('submit_token', TRUE);
        $session_token = $this->session->userdata('billing_submit_token');
        if ($posted_token) {
            if (!$session_token || $posted_token !== $session_token) {
                $this->session->set_flashdata('error', 'This invoice was already saved. Please check the invoice list.');
                redirect('billing');
                return;
            }
            $this->session->unset_userdata('billing_submit_token');
        }

        $this->form_validation->set_rules('invoice_date', 'Invoice Date', 'required');
        $this->form_validation->set_rules('invoice_type', 'Invoice Type', 'required');
        $this->form_validation->set_rules('payment_mode', 'Payment Mode', 'required');

        if ($this->form_validation->run() === FALSE) {
            $data['submit_token'] = bin2hex(random_bytes(16));
            $this->session->set_userdata('billing_submit_token', $data['submit_token']);
            $this->session->set_flashdata('error', validation_errors(' | '));
            $this->render($billing_view, $data);
            return;
        }

        $customer_id = (int)$this->input->post('customer_id', TRUE);
        // Auto-assign Walk-in Customer when no customer selected
        if ($customer_id <= 0) {
            $walkin_id   = (int)($data['company']['walkin_customer_id'] ?? 0);
            $customer_id = $walkin_id ?: 4;
        }
        $customer = $this->Customer_model->get_by_id($customer_id);
        // If walk-in customer ID is misconfigured, fall back to the first customer in DB
        if (!$customer) {
            $first = $this->db->order_by('id', 'ASC')->limit(1)->get('customers')->row();
            if (!$first) {
                $this->session->set_flashdata('error', 'No customers found in the system. Please add a Walk-in Customer first.');
                redirect('billing/create');
                return;
            }
            $customer_id = $first->id;
            $customer    = $first;
        }
        $company_state = $data['company']['company_state'] ?? 'Punjab';
        $invoice_type  = $this->input->post('invoice_type', TRUE);
        $is_interstate = ($invoice_type !== 'retail' && !empty($customer->state) &&
            strtolower(trim($customer->state)) !== strtolower(trim($company_state))) ? 1 : 0;

        // Permission check for discount
        $user_can_discount = $this->can_module('mod_discount');

        // Collect line items
        $product_ids  = $this->input->post('product_id');
        $qtys         = $this->input->post('qty');
        $rates        = $this->input->post('rate');
        $discounts    = $this->input->post('item_discount');
        $gst_pcts     = $this->input->post('gst_percent');   // IGST rate per line
        $cgst_pcts    = $this->input->post('cgst_percent');  // from HSN (hidden field)
        $sgst_pcts    = $this->input->post('sgst_percent');  // from HSN (hidden field)
        $multipliers  = $this->input->post('multiplier');
        $base_units   = $this->input->post('base_unit');
        $mrps            = $this->input->post('mrp');
        $serial_json     = $this->input->post('serial_json');
        $custom_names    = $this->input->post('custom_name');
        $variant_ids     = $this->input->post('variant_id');
        $variant_skus    = $this->input->post('variant_sku');
        $size_names      = $this->input->post('size_name');
        $colour_names    = $this->input->post('colour_name');
        $chassis_nos     = $this->input->post('chassis_no');
        $motor_nos       = $this->input->post('motor_no');
        $item_colours    = $this->input->post('item_colour');
        $battery_serials = $this->input->post('battery_serial');
        $charger_serials = $this->input->post('charger_serial');
        $warranty_terms  = $this->input->post('warranty_terms');

        if (empty($product_ids) || !is_array($product_ids)) {
            $this->session->set_flashdata('error', 'Add at least one product.');
            $this->render($billing_view, $data);
            return;
        }

        $items       = [];
        $subtotal    = 0;
        $total_disc  = 0;
        $taxable_amt = 0;
        $total_gst   = 0;

        foreach ($product_ids as $k => $pid) {
            if (empty($pid) || empty($qtys[$k])) continue;
            $pid     = (int)$pid;
            $qty     = (int)$qtys[$k];
            $rate    = (float)$rates[$k];
            $disc    = $user_can_discount ? (float)($discounts[$k] ?? 0) : 0;

            $mult  = isset($multipliers[$k]) ? (int)$multipliers[$k] : 1;
            $unit  = isset($base_units[$k])  ? $base_units[$k]       : 'PCS';
            $mrp   = isset($mrps[$k])        ? (float)$mrps[$k]      : 0;

            $internal_qty = $qty * $mult;

            $product = $this->Product_model->get_by_id($pid);
            if ($mrp <= 0 && $product) {
                $mrp = (float)($product->mrp ?? 0);
            }
            if (!$product) {
                $this->session->set_flashdata('error', 'Product not found: #' . $pid);
                $this->render($billing_view, $data);
                return;
            }

            // Authoritative server-side GST calculation via Gst_tax_engine
            // Evaluates garment threshold independently (<= 2500 -> 5%, > 2500 -> 18%)
            // Never relies on purchase rate or untrusted client input
            $this->load->library('Gst_tax_engine');
            $tax_calc = $this->gst_tax_engine->calculate_sales_line([
                'product'          => $product,
                'unit_price'       => $rate,
                'qty'              => $qty,
                'discount'         => $disc,
                'is_tax_inclusive' => true,
                'is_interstate'    => $is_interstate,
                'transaction_date' => $invoice_date,
            ]);

            $line_subtotal = $rate * $qty;
            $line_disc     = $disc;
            $line_total    = $tax_calc['total_amount'];
            $line_taxable  = $tax_calc['taxable_amount'];
            $line_gst      = $tax_calc['gst_amount'];

            $igst_pct      = $tax_calc['gst_percent'];
            $cgst_pct      = $tax_calc['cgst_percent'];
            $sgst_pct      = $tax_calc['sgst_percent'];
            $line_igst_amt = $tax_calc['igst_amount'];
            $line_cgst_amt = $tax_calc['cgst_amount'];
            $line_sgst_amt = $tax_calc['sgst_amount'];

            $subtotal    += $line_subtotal;
            $total_disc  += $line_disc;
            $taxable_amt += $line_taxable;
            $total_gst   += $line_gst;

            $item_name = $product->name;
            if (!empty($custom_names[$k]) && !empty($product->allow_name_edit)) {
                $item_name = trim($custom_names[$k]);
            }

            $items[] = [
                'product_id'   => $pid,
                'variant_id'   => !empty($variant_ids[$k]) ? (int)$variant_ids[$k] : null,
                'variant_sku'  => !empty($variant_skus[$k]) ? trim($variant_skus[$k]) : null,
                'size_name'    => !empty($size_names[$k]) ? trim($size_names[$k]) : null,
                'colour_name'  => !empty($colour_names[$k]) ? trim($colour_names[$k]) : null,
                'product_name' => $item_name,
                'chassis_no'   => !empty($chassis_nos[$k]) ? trim($chassis_nos[$k]) : null,
                'motor_no'     => !empty($motor_nos[$k]) ? trim($motor_nos[$k]) : null,
                'colour'       => !empty($item_colours[$k]) ? trim($item_colours[$k]) : (!empty($colour_names[$k]) ? trim($colour_names[$k]) : ($product->colour ?? null)),
                'battery_serial' => !empty($battery_serials[$k]) ? trim($battery_serials[$k]) : null,
                'charger_serial' => !empty($charger_serials[$k]) ? trim($charger_serials[$k]) : null,
                'warranty_terms' => !empty($warranty_terms[$k]) ? trim($warranty_terms[$k]) : ($product->warranty_policy ?? null),
                'serial_json'  => is_array($serial_json) ? ($serial_json[$k] ?? null) : null,
                'hsn_code'     => $product->hsn_code ?? '',
                'qty'          => $qty,
                'unit'         => $unit,
                'mrp'          => $mrp,
                'rate'         => $rate,
                'discount'     => $line_disc,
                'taxable_amt'  => $line_taxable,
                'gst_percent'  => $igst_pct,
                'gst_amount'   => $line_gst,
                // NEW per-line GST columns
                'cgst_percent' => $cgst_pct,
                'sgst_percent' => $sgst_pct,
                'igst_percent' => $igst_pct,
                'cgst_amount'  => $line_cgst_amt,
                'sgst_amount'  => $line_sgst_amt,
                'igst_amount'  => $line_igst_amt,
                'total'            => $line_total,
                'tax_inclusive'    => 1,
                'tax_rule_applied' => $tax_calc['tax_rule_applied'],
            ];
        }

        if (empty($items)) {
            $this->session->set_flashdata('error', 'No valid items in invoice.');
            $this->render($billing_view, $data);
            return;
        }

        // ── Negative stock check ───────────────────────────────────────────
        $company = $this->Invoice_model->get_company_settings();
        if (($company['allow_negative_stock'] ?? '1') !== '1') {
            $billing_loc = $this->active_location_id() ?: 1;
            $this->load->model('Location_model');
            $this->load->model('Garment_model');
            $stock_errors = [];
            foreach ($items as $k => $it) {
                $mult = isset($multipliers[$k]) ? (int)$multipliers[$k] : 1;
                $need = $it['qty'] * $mult;
                if (!empty($it['variant_id'])) {
                    $avail = $this->Garment_model->get_variant_stock($it['variant_id'], $billing_loc);
                    if ($avail < $need) {
                        $stock_errors[] = $it['product_name'] . ' (' . ($it['size_name'] ?: '') . '): need ' . $need . ', available ' . (float)$avail;
                    }
                } else {
                    $avail = $this->Location_model->get_stock($it['product_id'], $billing_loc);
                    if ($avail < $need) {
                        $stock_errors[] = $it['product_name'] . ': need ' . $need . ', available ' . (float)$avail;
                    }
                }
            }
            if (!empty($stock_errors)) {
                $this->session->set_flashdata('error', 'Insufficient stock (negative billing disabled): ' . implode('; ', $stock_errors));
                $this->render($billing_view, $data);
                return;
            }
        }

        // ── Price override check ──────────────────────────────────────────
        $allow_override = $perm && isset($perm->allow_price_override) ? (int)$perm->allow_price_override : 1;
        if (!$allow_override) {
            $rate_errors = [];
            foreach ($items as $it) {
                $p = $this->Product_model->get_by_id($it['product_id']);
                if ($p && abs((float)$it['rate'] - (float)$p->selling_price) > 0.01) {
                    $rate_errors[] = $it['product_name'] . ': submitted ' . $it['rate'] . ', expected ' . $p->selling_price;
                }
            }
            if (!empty($rate_errors)) {
                $this->session->set_flashdata('error', 'Price override not allowed: ' . implode('; ', $rate_errors));
                $this->render($billing_view, $data);
                return;
            }
        }

        // ── Serial number validation (server-side, authoritative) ───────────
        // Nothing is written until every scanned serial is proven sellable:
        // right item, in stock, at this location, and not already on this bill.
        $this->load->model('Serial_model');
        $serial_plan   = [];   // line index => [serial rows]
        $serial_errors = [];
        if ($this->Serial_model->schema_ready()) {
            $smap    = $this->Serial_model->serialized_map(array_column($items, 'product_id'));
            $bill_loc = $this->active_location_id() ?: null;
            $seen_sn = [];

            foreach ($items as $k => $it) {
                $prod = $smap[(int)$it['product_id']] ?? null;
                if (!$prod || (int)$prod->maintain_serial !== 1) continue;

                $list = json_decode((string)(is_array($serial_json) ? ($serial_json[$k] ?? '') : ''), true);
                if (!is_array($list)) $list = [];

                // Auto-fallback: if chassis_no is provided directly on the line
                if (empty($list) && !empty($it['chassis_no'])) {
                    $list = [$it['chassis_no']];
                }

                $need = (int)$it['qty'];
                if (count($list) !== $need) {
                    $serial_errors[] = 'Please scan/select all ' . $need . ' serial/chassis number(s) for "'
                                     . $prod->name . '" (' . count($list) . ' selected).';
                    continue;
                }

                $rows = [];
                foreach ($list as $sn) {
                    $key = $this->Serial_model->normalize($sn);
                    if (isset($seen_sn[$key])) {
                        $serial_errors[] = 'Serial/Chassis Number ' . $key . ' appears more than once on this invoice.';
                        continue;
                    }
                    $seen_sn[$key] = true;

                    $chk = $this->Serial_model->validate_outbound($key, $it['product_id'], $bill_loc);
                    if (!$chk['ok']) { $serial_errors[] = $chk['error']; continue; }
                    $rows[] = $chk['row'];
                }

                // If battery serial specified, validate availability
                if (!empty($it['battery_serial'])) {
                    $bat_serials = array_filter(array_map('trim', explode(',', $it['battery_serial'])));
                    foreach ($bat_serials as $bsn) {
                        $bkey = $this->Serial_model->normalize($bsn);
                        if (isset($seen_sn[$bkey])) {
                            $serial_errors[] = 'Battery Serial Number ' . $bkey . ' appears more than once on this invoice.';
                            continue;
                        }
                        $seen_sn[$bkey] = true;
                        $bchk = $this->Serial_model->validate_outbound($bkey, null, $bill_loc);
                        if (!$bchk['ok']) {
                            $serial_errors[] = 'Battery: ' . $bchk['error'];
                        }
                    }
                }

                // If charger serial specified, validate availability
                if (!empty($it['charger_serial'])) {
                    $ckey = $this->Serial_model->normalize($it['charger_serial']);
                    if (isset($seen_sn[$ckey])) {
                        $serial_errors[] = 'Charger Serial Number ' . $ckey . ' appears more than once on this invoice.';
                    } else {
                        $seen_sn[$ckey] = true;
                        $cchk = $this->Serial_model->validate_outbound($ckey, null, $bill_loc);
                        if (!$cchk['ok']) {
                            $serial_errors[] = 'Charger: ' . $cchk['error'];
                        }
                    }
                }

                if (count($rows) === $need) $serial_plan[$k] = $rows;
            }
        }

        if ($serial_errors) {
            $this->session->set_flashdata('error',
                'Serial number problems:<br>• ' . implode('<br>• ', array_slice($serial_errors, 0, 12)));
            $this->render($billing_view, $data);
            return;
        }

        // Invoice-level CGST/SGST/IGST totals
        $cgst_amount = $is_interstate ? 0 : round(array_sum(array_column($items, 'cgst_amount')), 2);
        $sgst_amount = $is_interstate ? 0 : round(array_sum(array_column($items, 'sgst_amount')), 2);
        $igst_amount = $is_interstate ? $total_gst : 0;

        $invoice_date = $this->input->post('invoice_date', TRUE);
        $payment_mode = $this->input->post('payment_mode', TRUE) ?: 'cash';

        // Build enriched items for SchemeEngine (adds brand_id, category_id, carton_size)
        $this->load->library('SchemeEngine');
        $scheme_items = [];
        foreach ($items as $item) {
            $p = $this->db->select('brand_id, category_id, carton_size')
                          ->where('id', $item['product_id'])->get('products')->row();
            $scheme_items[] = array_merge($item, [
                'uom'         => 'pcs',
                'carton_size' => (int)($p->carton_size ?? 1),
                'brand_id'    => (int)($p->brand_id    ?? 0),
                'category_id' => (int)($p->category_id ?? 0),
            ]);
        }
        $scheme_result = $this->schemeengine->evaluate(
            $customer_id, $scheme_items, $taxable_amt, $payment_mode, $invoice_date
        );
        // Scheme discount is informational only — the item's rate is already the
        // scheme/promotional selling price (MRP vs rate is shown per line on the
        // invoice), so subtracting the scheme's computed amount again here would
        // double-count the same saving. It's still logged to scheme_application_log
        // (below) and listed in the invoice notes for audit purposes.
        $pre_cd_total = $taxable_amt + $total_gst;

        // Calculate the CD-eligible total by excluding products with no_discount = 1
        $cd_eligible_total = 0;
        foreach ($items as $item) {
            $product = $this->Product_model->get_by_id($item['product_id']);
            if (!$product || (int)($product->no_discount ?? 0) !== 1) {
                $cd_eligible_total += $item['total'];
            }
        }
        if ($cd_eligible_total < 0) $cd_eligible_total = 0;

        $cd_percent   = $user_can_discount ? ((float)$this->input->post('cd_percent', TRUE) ?: 0) : 0;
        $cd_percent   = max(0, min(100, $cd_percent));
        $cd_amount    = (float)$this->input->post('cd_amount', TRUE) ?: 0;

        if ($cd_amount <= 0 && $cd_percent > 0) {
            $cd_amount = round($cd_eligible_total * $cd_percent / 100, 2);
        } else if ($cd_amount > 0 && $cd_eligible_total > 0) {
            // Recalculate percent if there is a discrepancy to keep DB values aligned
            if (abs($cd_amount - ($cd_eligible_total * $cd_percent / 100)) > 0.05) {
                $cd_percent = max(0, min(100, ($cd_amount / $cd_eligible_total) * 100));
            }
        }

        // ── Store-Level Discount (admin/manager only) ──
        $bill_disc_type   = 'none';
        $bill_disc_pct    = 0.0;
        $bill_disc_amt    = 0.0;
        $bill_disc_reason = '';
        if ($this->is_admin() && $user_can_discount) {
            $bill_disc_type   = $this->input->post('bill_discount_type',    TRUE) ?: 'none';
            $bill_disc_reason = $this->input->post('bill_discount_reason',  TRUE) ?: '';
            if ($bill_disc_type === 'percent') {
                $bill_disc_pct = (float)$this->input->post('bill_discount_percent', TRUE);
                $bill_disc_pct = max(0, min(100, $bill_disc_pct));
                $bill_disc_amt = round($pre_cd_total * $bill_disc_pct / 100, 2);
            } elseif ($bill_disc_type === 'fixed') {
                $bill_disc_amt = (float)$this->input->post('bill_discount_amount', TRUE);
                $bill_disc_amt = max(0, $bill_disc_amt);
                if ($pre_cd_total > 0) {
                    $bill_disc_pct = round(($bill_disc_amt / $pre_cd_total) * 100, 2);
                }
            }
            if ($bill_disc_type !== 'none' && empty($bill_disc_reason)) {
                $bill_disc_reason = 'Store discount';
            }
        }

        // ── Multi-Payment Splits ──
        // Only treat a bill as "split" if 2+ distinct payment lines actually carry an
        // amount. The split-payment UI can be opened and closed with just one mode
        // filled in — saving that as payment_mode='split' would misclassify a plain
        // cash/card/UPI sale, skewing payment-mode reports and hiding it from single-mode totals.
        $pay_modes    = $this->input->post('pay_mode');
        $pay_amounts  = $this->input->post('pay_amount');
        $valid_pay_lines = [];
        if (!empty($pay_modes) && is_array($pay_modes)) {
            foreach ($pay_modes as $k => $pm) {
                if (empty($pm) || (float)($pay_amounts[$k] ?? 0) <= 0) continue;
                $valid_pay_lines[$k] = $pm;
            }
        }
        $is_multi_pay = count($valid_pay_lines) > 1;

        if ($is_multi_pay) {
            $paid_amount = 0;
            foreach ($valid_pay_lines as $k => $pm) {
                if ($pm !== 'credit') {
                    $paid_amount += (float)$pay_amounts[$k];
                }
            }
        } elseif (count($valid_pay_lines) === 1) {
            $k = array_key_first($valid_pay_lines);
            $single_pay_mode = $valid_pay_lines[$k];
            $paid_amount = $single_pay_mode !== 'credit' ? (float)$pay_amounts[$k] : 0;
        } else {
            $paid_amount = (float)$this->input->post('paid_amount', TRUE) ?: 0;
            $single_pay_mode = $payment_mode; // fallback if no split UI used
            if ($single_pay_mode === 'credit') { $paid_amount = 0; }
        }

        // Snapshot the as-typed amount BEFORE the net-payable cap further down —
        // that's what lets the receipt show "Change Returned" for a cash sale where
        // the customer handed over more than the bill (e.g. Rs.5000 on a Rs.3449
        // bill). Only meaningfully different from paid_amount in that single-mode
        // cash case; split/card lines have no "tendered more than owed" concept.
        $amount_tendered = $paid_amount;

        // --- Due Bill Access Control ---
        $perm = $this->db->where('user_id', $this->session->userdata('user_id'))->get('user_permissions')->row();
        $allow_due_bills = $perm ? (int)$perm->allow_due_bills : 0;
        $role = $this->session->userdata('role');
        
        $has_credit = ($payment_mode === 'credit' || (isset($single_pay_mode) && $single_pay_mode === 'credit') || in_array('credit', $valid_pay_lines));
        if ($has_credit && $role !== 'admin' && $role !== 'superadmin' && !$allow_due_bills) {
            $this->session->set_flashdata('error', 'You are not authorized to create Due Bills.');
            redirect('billing/create');
            return;
        }
        // -------------------------------

        // ── Credit Due Date ──
        $credit_due_date = $this->input->post('credit_due_date', TRUE) ?: null;

        $total_amount = round($pre_cd_total - $cd_amount - $bill_disc_amt, 2);

        // ── Recalculate taxable / GST after CD + bill discount ────────
        // CD and bill discount reduce the GST-inclusive transaction value,
        // so taxable and GST must be re-extracted from the reduced total.
        // Distribute proportionally so multi-rate invoices stay correct.
        $whole_disc = $cd_amount + $bill_disc_amt;
        if ($whole_disc > 0 && $pre_cd_total > 0) {
            $ratio = $total_amount / $pre_cd_total;
            $taxable_amt = round($taxable_amt * $ratio, 2);
            $total_gst   = round($total_amount - $taxable_amt, 2);
            $cgst_amount = $is_interstate ? 0 : round($total_gst / 2, 2);
            $sgst_amount = $is_interstate ? 0 : round($total_gst - $cgst_amount, 2);
            $igst_amount = $is_interstate ? $total_gst : 0;
        }

        // ── Round Off (optional): round to nearest ₹1 — <50 paise rounds
        // down, >=50 paise rounds up — so the guest pays a whole rupee
        // amount instead of leaving a few paise stuck in Balance Due.
        $round_off = 0.0;
        if ($this->input->post('round_off_enabled')) {
            $paise = round(($total_amount - floor($total_amount)) * 100);
            if ($paise > 0) {
                $round_off = $paise < 50 ? -($paise / 100) : ((100 - $paise) / 100);
                $round_off = round($round_off, 2);
            }
            $total_amount = round($total_amount + $round_off, 2);
        }

        $paid_amount  = $paid_amount;

        // Credit check for wholesale customers
        if ($customer->type === 'wholesale' && $customer->credit_limit > 0) {
            $new_balance = $customer->balance + ($total_amount - $paid_amount);
            if ($new_balance > $customer->credit_limit) {
                $this->session->set_flashdata('error',
                    'Credit limit exceeded! Limit: ₹' . number_format($customer->credit_limit, 2) .
                    ' | Current balance: ₹' . number_format($customer->balance, 2) .
                    ' | This invoice due: ₹' . number_format($total_amount - $paid_amount, 2));
                $this->render($billing_view, $data);
                return;
            }
        }

        $wallet_used = (float)$this->input->post('wallet_amount_used', TRUE) ?: 0;
        $wallet_credited = (float)$this->input->post('wallet_amount_credited', TRUE) ?: 0;

        // paid_amount must never exceed what's actually owed on this invoice. The
        // billing screen lets a cashier type the cash tendered (e.g. customer hands
        // over ₹500 for a ₹265 bill) and only caps it to the net payable amount when
        // "credit excess to wallet" is checked — otherwise the raw tendered amount
        // was being stored as paid_amount, inflating Collected/paid totals in reports
        // by whatever change was physically handed back. Cap here as the source of
        // truth; the excess is NOT auto-credited to the wallet (that's already handled
        // client-side when the cashier explicitly opts in) — it was either physical
        // cash change already handed back, or a stray over-typed amount, neither of
        // which the customer is still owed.
        $net_payable = round($total_amount - $wallet_used, 2);

        // Non-credit electronic modes (UPI, card, neft) always collect the full
        // amount — a zero or negative paid_amount is a client-side bug, not a
        // legitimate partial payment. Default to net_payable so reports stay
        // correct even when JS state leaks across bills.
        $single_pm = isset($single_pay_mode) ? $single_pay_mode : $payment_mode;
        if (!$is_multi_pay && !in_array($single_pm, ['credit', 'split']) && $paid_amount <= 0) {
            $paid_amount = $net_payable;
        }

        if ($paid_amount > $net_payable) {
            $paid_amount = $net_payable;
        }

        $due_amount  = round($total_amount - $paid_amount - $wallet_used, 2);
        $status      = $due_amount <= 0 ? 'paid' : (($paid_amount > 0 || $wallet_used > 0) ? 'partial' : 'unpaid');
        $billing_location_id_for_no = $this->active_location_id() ?: 1;
        try {
            $doc_no = $this->Invoice_model->generate_invoice_no($billing_location_id_for_no, $invoice_date);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            $this->render($billing_view, $data);
            return;
        }
        $invoice_no  = $doc_no['number'];

        // ── Loyalty (§7 item-wise, §15 redemption) ──────────────────────────
        // Points are computed per line, never bill-total ÷ rate, and frozen onto
        // the invoice lines below so a later rule change cannot rewrite them.
        // Redemption is a TENDER like the wallet — it never touches taxable
        // value or GST.
        $this->load->model('Loyalty_model');
        $loy = ['lines' => [], 'total' => 0.0];
        $loy_customer = null; $loy_points_used = 0.0; $loy_discount = 0.0;

        if ($this->can_module('mod_loyalty') && $this->Loyalty_model->schema_ready() && $customer_id) {
            $loy_customer = $this->db->where('id', (int)$customer_id)->get('customers')->row();

            // Which products a points-excluding scheme touched on this bill (§5).
            $blocked = [];
            if (!empty($scheme_result['discount_breakup'])
                && $this->db->field_exists('allow_loyalty_points', 'scheme_master')) {
                $ids = array_filter(array_column($scheme_result['discount_breakup'], 'scheme_id'));
                if ($ids) {
                    $no_pts = $this->db->select('id')->where_in('id', $ids)
                                       ->where('allow_loyalty_points', 0)->get('scheme_master')->result();
                    if ($no_pts) {
                        $bad = array_column($no_pts, 'id');
                        foreach ($scheme_result['discount_breakup'] as $d) {
                            if (!in_array($d['scheme_id'] ?? 0, $bad)) continue;
                            // A scheme with no product detail blocks the whole bill.
                            if (!empty($d['product_id'])) $blocked[(int)$d['product_id']] = true;
                            else foreach ($items as $it) $blocked[(int)$it['product_id']] = true;
                        }
                    }
                }
            }

            $calc = [];
            foreach ($items as $k => $it) {
                $calc[$k] = [
                    'product_id'     => $it['product_id'],
                    'qty'            => $it['qty'],
                    'total'          => $it['total'],          // GST-inclusive line total
                    'scheme_blocked' => isset($blocked[(int)$it['product_id']]),
                ];
            }
            $loy = $this->Loyalty_model->calculate($calc, $loy_customer);

            // Redemption cap is decided server-side; the browser only suggests.
            $want = (float)($this->input->post('loyalty_points_used', TRUE) ?: 0);
            if ($want > 0) {
                $lscheme = $this->Loyalty_model->active_scheme($loy_customer->loyalty_tier ?? null);
                $cap = $this->Loyalty_model->max_redeemable($loy_customer, $total_amount, $lscheme);
                $loy_points_used = min($want, $cap);
                $loy_discount    = $this->Loyalty_model->points_to_money($loy_points_used, $lscheme);
                if ($loy_discount > 0) {
                    // Tender, like the wallet: reduces what is still owed.
                    $due_amount = round($due_amount - $loy_discount, 2);
                    if ($due_amount <= 0) { $due_amount = 0; $status = 'paid'; }
                }
            }
        }

        $notes = $this->input->post('notes', TRUE) ?: '';
        if (!empty($scheme_result['discount_breakup'])) {
            $applied_names = [];
            foreach ($scheme_result['discount_breakup'] as $d) {
                $applied_names[] = $d['scheme_name'] . ' (-₹' . number_format($d['discount'], 2) . ')';
            }
            $notes .= "\nApplied Scheme(s): " . implode(', ', $applied_names);
            $notes  = trim($notes);
        }

        $invoice_data = [
            'invoice_no'             => $invoice_no,
            'financial_year'         => $doc_no['financial_year'],
            'customer_id'            => $customer_id,
            'invoice_type'           => $this->input->post('invoice_type', TRUE),
            'invoice_date'           => $invoice_date,
            'subtotal'               => $subtotal,
            'discount'               => $total_disc,
            'taxable_amount'         => $taxable_amt,
            'cgst_amount'            => $cgst_amount,
            'sgst_amount'            => $sgst_amount,
            'igst_amount'            => $igst_amount,
            'total_gst'              => $total_gst,
            'cd_percent'             => $cd_percent,
            'cd_amount'              => $cd_amount,
            'round_off'              => $round_off,
            'total_amount'           => $total_amount,
            'paid_amount'            => $paid_amount,
            'amount_tendered'        => $amount_tendered,
            'due_amount'             => $due_amount,
            'wallet_amount_used'     => $wallet_used,
            'wallet_amount_credited' => $wallet_credited,
            'payment_mode'           => $is_multi_pay ? 'split' : ($single_pay_mode ?? $this->input->post('payment_mode', TRUE)),
            'bill_discount_type'     => $bill_disc_type,
            'bill_discount_percent'  => $bill_disc_pct,
            'bill_discount_amount'   => $bill_disc_amt,
            'bill_discount_reason'   => $bill_disc_reason,
            'bill_discount_by'       => ($bill_disc_amt > 0) ? $this->session->userdata('user_id') : null,
            'multi_payment'          => $is_multi_pay ? 1 : 0,
            'credit_due_date'        => $credit_due_date,
            'is_interstate'          => $is_interstate,
            'is_delivery'            => $this->input->post('is_delivery') ? 1 : 0,
            'notes'                  => $notes,
            'status'                 => $status,
            'created_by'             => $this->session->userdata('user_id'),
            'shift_id'               => $this->session->userdata('shift_id'),
            'location_id'            => $this->active_location_id() ?: 1,
            'device_label'           => substr(trim($this->input->post('device_label', TRUE) ?: ''), 0, 50) ?: null,
        ];

        $this->db->trans_start();
        $invoice_id = $this->Invoice_model->create($invoice_data, $items);

        // Apply wallet adjustments
        if ($wallet_used > 0) {
            $this->Customer_model->adjust_wallet(
                $customer_id, 
                $wallet_used, 
                'debit', 
                'sale_payment', 
                $invoice_id, 
                $invoice_no, 
                'Used ₹' . number_format($wallet_used, 2) . ' from wallet for bill #' . $invoice_no,
                $this->session->userdata('user_id')
            );
        }
        if ($wallet_credited > 0) {
            $this->Customer_model->adjust_wallet(
                $customer_id, 
                $wallet_credited, 
                'credit', 
                'sale_excess', 
                $invoice_id, 
                $invoice_no, 
                'Credited excess payment of ₹' . number_format($wallet_credited, 2) . ' from bill #' . $invoice_no,
                $this->session->userdata('user_id')
            );
        }

        // Save multi-payment splits
        if ($is_multi_pay) {
            foreach ($pay_modes as $k => $pm) {
                $amt = (float)($pay_amounts[$k] ?? 0);
                if (empty($pm) || $amt <= 0) continue;
                $split = [
                    'invoice_id'   => $invoice_id,
                    'payment_mode' => $pm,
                    'amount'       => $amt,
                    'card_type'    => $this->input->post('card_type_'  . $k, TRUE) ?: null,
                    'card_last4'   => $this->input->post('card_last4_' . $k, TRUE) ?: null,
                    'card_ref'     => $this->input->post('card_ref_'   . $k, TRUE) ?: null,
                    'bank_name'    => $this->input->post('bank_name_'  . $k, TRUE) ?: null,
                    'reference_no' => $this->input->post('pay_ref_'    . $k, TRUE) ?: null,
                ];
                $this->db->insert('invoice_payment_splits', $split);
            }
        }

        // ── Loyalty: freeze the award onto the lines, then post the ledger ──
        // Runs only after the invoice exists, so a failed bill leaves no points
        // (§12, §35). The per-line snapshot is what makes an item-wise return
        // reversal possible and keeps history immune to later rule changes.
        if ($this->can_module('mod_loyalty') && $this->Loyalty_model->schema_ready() && $loy_customer) {
            $ii = $this->db->select('id')->where('invoice_id', $invoice_id)
                           ->order_by('id', 'ASC')->get('invoice_items')->result();

            foreach ($items as $k => $it) {
                if (!isset($ii[$k])) continue;                    // insert order matches $items
                $ln = $loy['lines'][$k] ?? ['points' => 0, 'base' => 0, 'reason' => null];
                $this->db->where('id', $ii[$k]->id)->update('invoice_items', [
                    'loyalty_points'       => $ln['points'],
                    'loyalty_base_amount'  => $ln['base'],
                    'loyalty_block_reason' => $ln['reason'],
                ]);
            }

            $before = (float)$loy_customer->loyalty_points;

            // Redeem first: the customer must actually hold the points before
            // any newly earned ones are credited.
            if ($loy_points_used > 0) {
                $this->Loyalty_model->redeem($loy_customer, $loy_points_used,
                    $invoice_id, $invoice_no, null, $billing_location_id ?? null);
            }
            $earned = $this->Loyalty_model->earn_for_invoice(
                $invoice_id, $loy_customer, $invoice_no, $billing_location_id ?? null);

            // Read the closing balance into a variable FIRST. Calling a query
            // builder method inside the arguments of another QB chain lets its
            // where() pile onto the pending one — balance() would run as
            // "WHERE id = <invoice> AND id = <customer>", match nothing and
            // return 0.
            $after = $this->Loyalty_model->balance($loy_customer->id);

            $this->db->where('id', $invoice_id)->update('invoices', [
                'loyalty_points_before'   => $before,
                'loyalty_points_earned'   => $earned,
                'loyalty_points_redeemed' => $loy_points_used,
                'loyalty_discount'        => $loy_discount,
                'loyalty_points_after'    => $after,
            ]);

            if ($earned > 0 || $loy_points_used > 0) {
                $this->log_activity('loyalty', 'invoice',
                    'Bill ' . $invoice_no . ': earned ' . (int)$earned
                    . ', redeemed ' . (int)$loy_points_used . ' point(s)', $invoice_id, $invoice_no);
            }
        }

        // Save bill discount audit trail
        if ($bill_disc_type !== 'none' && $bill_disc_amt > 0) {
            $this->db->insert('invoice_bill_discounts', [
                'invoice_id'       => $invoice_id,
                'invoice_no'       => $invoice_no,
                'discount_type'    => $bill_disc_type,
                'discount_percent' => $bill_disc_pct,
                'discount_amount'  => $bill_disc_amt,
                'reason'           => $bill_disc_reason,
                'applied_by'       => $this->session->userdata('user_id'),
                'applied_by_name'  => $this->session->userdata('name'),
                'applied_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        if (!empty($scheme_result['schemes_applied'])) {
            $this->schemeengine->log_applied(
                $invoice_id, $invoice_no, $customer_id, $scheme_result,
                $this->session->userdata('user_id')
            );
        }

        $billing_location_id = $this->active_location_id() ?: 1;
        $this->load->model('Location_model');
        $this->load->model('Garment_model');
        foreach ($items as $k => $item) {
            $mult         = isset($multipliers[$k]) ? (int)$multipliers[$k] : 1;
            $internal_qty = $item['qty'] * $mult;
            
            if (!empty($item['variant_id'])) {
                $this->Garment_model->decrease_variant_stock($item['variant_id'], $item['product_id'], $billing_location_id, $internal_qty);
            } else {
                $this->Product_model->decrease_stock($item['product_id'], $internal_qty);
                $this->Location_model->decrease_stock($item['product_id'], $billing_location_id, $internal_qty);
            }

            $before = $this->Location_model->get_stock($item['product_id'], $billing_location_id);
            $after  = $this->Location_model->get_stock($item['product_id'], $billing_location_id);
            $this->Location_model->log_movement([
                'product_id'     => $item['product_id'],
                'variant_id'     => $item['variant_id'] ?? null,
                'variant_sku'    => $item['variant_sku'] ?? null,
                'size_name'      => $item['size_name'] ?? null,
                'colour_name'    => $item['colour_name'] ?? null,
                'product_name'   => $item['product_name'] ?? '',
                'location_id'    => $billing_location_id,
                'location_name'  => $this->active_location_name(),
                'movement_type'  => 'sale_out',
                'quantity'       => $internal_qty,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_type' => 'invoice',
                'reference_id'   => $invoice_id,
                'reference_no'   => $invoice_no,
                'user_id'        => $this->session->userdata('user_id'),
                'user_name'      => $this->session->userdata('name'),
            ]);
        }

        // ── Serial status + warranty records ────────────────────────────────
        // Warranty is stored per sale, not read back from the item master, so
        // editing an item's warranty_days later never rewrites what was sold.
        if ($this->Serial_model->schema_ready()) {
            $smap = $this->Serial_model->serialized_map(array_column($items, 'product_id'));

            // invoice_items are inserted in the same order as $items, so the
            // nth row belongs to the nth line.
            $ii_rows = $this->db->select('id, product_id')->where('invoice_id', $invoice_id)
                                ->order_by('id', 'ASC')->get('invoice_items')->result();

            foreach ($items as $k => $it) {
                $prod = $smap[(int)$it['product_id']] ?? null;
                if (!$prod) continue;

                $ii_id     = isset($ii_rows[$k]) ? (int)$ii_rows[$k]->id : null;
                $w_on      = (int)($prod->warranty_applicable ?? 0) === 1;
                $w_days    = (int)($prod->warranty_days ?? 0);
                $start     = $invoice_date ?: date('Y-m-d');
                $expiry    = $w_on ? $this->Serial_model->calc_expiry($start, $w_days) : null;

                if (!empty($serial_plan[$k])) {
                    foreach ($serial_plan[$k] as $srow) {
                        $this->Serial_model->set_status($srow->id, 'SOLD', [
                            'txn_type'     => 'SOLD',
                            'reference_id' => $invoice_id,
                            'reference_no' => $invoice_no,
                            'customer_id'  => $customer_id,
                            'location_id'  => null,   // the unit has left the building
                            'remarks'      => 'Sold on ' . $invoice_no,
                        ]);
                        $this->Serial_model->link_doc($srow->id, 'invoice', $invoice_id, $ii_id);

                        $ctype = 'other';
                        if (!empty($srow->item_type)) $ctype = $srow->item_type;
                        elseif (($prod->product_type ?? '') === 'electric_scooter') $ctype = 'scooter';

                        if ($w_on && $expiry) {
                            $this->db->insert('serial_warranties', [
                                'serial_id'            => $srow->id,
                                'product_id'           => $it['product_id'],
                                'component_type'       => $ctype,
                                'invoice_id'           => $invoice_id,
                                'invoice_item_id'      => $ii_id,
                                'customer_id'          => $customer_id ?: null,
                                'sale_date'            => $start,
                                'warranty_start_date'  => $start,
                                'warranty_days'        => $w_days,
                                'warranty_expiry_date' => $expiry,
                                'policy_terms'         => $it['warranty_terms'] ?: ($prod->warranty_policy ?? null),
                                'created_by'           => $this->session->userdata('user_id'),
                            ]);
                        }

                        // Also process linked battery serials if this is a scooter
                        if (!empty($it['battery_serial'])) {
                            $bat_serials = array_filter(array_map('trim', explode(',', $it['battery_serial'])));
                            foreach ($bat_serials as $bsn) {
                                $bdetail = $this->Serial_model->get_detail($bsn);
                                if ($bdetail) {
                                    $this->Serial_model->set_status($bdetail->id, 'SOLD', [
                                        'txn_type'     => 'SOLD',
                                        'reference_id' => $invoice_id,
                                        'reference_no' => $invoice_no,
                                        'customer_id'  => $customer_id,
                                        'location_id'  => null,
                                        'remarks'      => 'Sold with Scooter ' . ($it['chassis_no'] ?: $srow->serial_no) . ' on ' . $invoice_no,
                                    ]);
                                    $this->db->where('id', $bdetail->id)->update('item_serials', [
                                        'linked_scooter_serial_id' => $srow->id,
                                        'linked_chassis_no'        => $it['chassis_no'] ?: $srow->serial_no,
                                        'customer_id'              => $customer_id,
                                        'invoice_id'               => $invoice_id,
                                        'sale_date'                => $start,
                                    ]);
                                    $this->Serial_model->link_doc($bdetail->id, 'invoice', $invoice_id, $ii_id);
                                    $b_days = (int)($bdetail->warranty_days ?: $w_days);
                                    $b_exp  = $this->Serial_model->calc_expiry($start, $b_days);
                                    $this->db->insert('serial_warranties', [
                                        'serial_id'            => $bdetail->id,
                                        'product_id'           => $bdetail->product_id,
                                        'component_type'       => 'battery',
                                        'invoice_id'           => $invoice_id,
                                        'invoice_item_id'      => $ii_id,
                                        'customer_id'          => $customer_id ?: null,
                                        'sale_date'            => $start,
                                        'warranty_start_date'  => $start,
                                        'warranty_days'        => $b_days,
                                        'warranty_expiry_date' => $b_exp,
                                        'policy_terms'         => 'Battery Warranty',
                                        'created_by'           => $this->session->userdata('user_id'),
                                    ]);
                                }
                            }
                        }

                        // Also process linked charger serial if this is a scooter
                        if (!empty($it['charger_serial'])) {
                            $cdetail = $this->Serial_model->get_detail($it['charger_serial']);
                            if ($cdetail) {
                                $this->Serial_model->set_status($cdetail->id, 'SOLD', [
                                    'txn_type'     => 'SOLD',
                                    'reference_id' => $invoice_id,
                                    'reference_no' => $invoice_no,
                                    'customer_id'  => $customer_id,
                                    'location_id'  => null,
                                    'remarks'      => 'Sold with Scooter ' . ($it['chassis_no'] ?: $srow->serial_no) . ' on ' . $invoice_no,
                                ]);
                                $this->db->where('id', $cdetail->id)->update('item_serials', [
                                    'linked_scooter_serial_id' => $srow->id,
                                    'linked_chassis_no'        => $it['chassis_no'] ?: $srow->serial_no,
                                    'customer_id'              => $customer_id,
                                    'invoice_id'               => $invoice_id,
                                    'sale_date'                => $start,
                                ]);
                                $this->Serial_model->link_doc($cdetail->id, 'invoice', $invoice_id, $ii_id);
                                $c_days = (int)($cdetail->warranty_days ?: 365);
                                $c_exp  = $this->Serial_model->calc_expiry($start, $c_days);
                                $this->db->insert('serial_warranties', [
                                    'serial_id'            => $cdetail->id,
                                    'product_id'           => $cdetail->product_id,
                                    'component_type'       => 'charger',
                                    'invoice_id'           => $invoice_id,
                                    'invoice_item_id'      => $ii_id,
                                    'customer_id'          => $customer_id ?: null,
                                    'sale_date'            => $start,
                                    'warranty_start_date'  => $start,
                                    'warranty_days'        => $c_days,
                                    'warranty_expiry_date' => $c_exp,
                                    'policy_terms'         => 'Charger Warranty',
                                    'created_by'           => $this->session->userdata('user_id'),
                                ]);
                            }
                        }

                        // Also record motor warranty if specified
                        if (!empty($it['motor_no'])) {
                            $this->db->insert('serial_warranties', [
                                'serial_id'            => null,
                                'product_id'           => $it['product_id'],
                                'component_type'       => 'motor',
                                'invoice_id'           => $invoice_id,
                                'invoice_item_id'      => $ii_id,
                                'customer_id'          => $customer_id ?: null,
                                'sale_date'            => $start,
                                'warranty_start_date'  => $start,
                                'warranty_days'        => $w_days,
                                'warranty_expiry_date' => $expiry,
                                'policy_terms'         => 'Motor Warranty (' . $it['motor_no'] . ')',
                                'created_by'           => $this->session->userdata('user_id'),
                            ]);
                        }
                    }
                } elseif ($w_on && $expiry && (int)($prod->maintain_serial ?? 0) !== 1) {
                    // Warranty item WITHOUT serial tracking: one warranty row per
                    // unit sold, so each can be claimed separately later.
                    for ($n = 0; $n < (int)$it['qty']; $n++) {
                        $this->db->insert('serial_warranties', [
                            'serial_id'            => null,
                            'product_id'           => $it['product_id'],
                            'invoice_id'           => $invoice_id,
                            'invoice_item_id'      => $ii_id,
                            'customer_id'          => $customer_id ?: null,
                            'sale_date'            => $start,
                            'warranty_start_date'  => $start,
                            'warranty_days'        => $w_days,
                            'warranty_expiry_date' => $expiry,
                            'created_by'           => $this->session->userdata('user_id'),
                        ]);
                    }
                }
            }
        }

        if ($due_amount > 0) {
            $this->Customer_model->increase_balance($customer_id, $due_amount);
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $db_err = $this->db->error();
            $msg    = 'Database error — Invoice not saved.';
            if (!empty($db_err['message'])) $msg .= ' Detail: ' . $db_err['message'];
            $this->session->set_flashdata('error', $msg);
            redirect('billing/create');
        }

        // Mark source order request as converted (order_id comes from the posted hidden
        // field, not session — the session copy was already consumed/cleared on GET).
        $convert_order_id = (int)$this->input->post('convert_order_id', TRUE);
        if ($convert_order_id) {
            $this->db->where('id', $convert_order_id)->update('order_requests', [
                'status'     => 'converted',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Same for a GRN converted to this pass-through invoice — tags the GRN so
        // it can't be converted a second time and its view page can link to the invoice.
        $convert_grn_id = (int)$this->input->post('convert_grn_id', TRUE);
        if ($convert_grn_id) {
            $this->load->model('Grn_model');
            $this->Grn_model->mark_converted($convert_grn_id, $invoice_id);
        }

        // Same for a Purchase converted to this pass-through invoice.
        $convert_purchase_id = (int)$this->input->post('convert_purchase_id', TRUE);
        if ($convert_purchase_id) {
            $this->load->model('Purchase_model');
            $this->Purchase_model->mark_converted($convert_purchase_id, $invoice_id);
        }

        // WhatsApp Marketing conversion attribution — if this customer received
        // a campaign send in the last 30 days, credit this sale to it.
        if ($customer_id) {
            $this->load->model('Marketing_model');
            $this->Marketing_model->attribute_conversion($customer_id, $invoice_id);
        }

        $company_settings = $this->Invoice_model->get_company_settings();
        if (isset($company_settings['print_mode']) && $company_settings['print_mode'] === 'direct') {
            $this->session->set_flashdata('auto_print_id', $invoice_id);
            $this->log_activity('billing', 'create_bill', 'Created invoice #' . $invoice_no, $invoice_id, $invoice_no);
            $this->session->set_flashdata('success', 'Invoice #' . $invoice_no . ' created successfully.');
            redirect('billing/create');
            return;
        }

        $this->log_activity('billing', 'create_bill', 'Created invoice #' . $invoice_no, $invoice_id, $invoice_no);
        $this->session->set_flashdata('success', 'Invoice #' . $invoice_no . ' created successfully.');
        $this->session->set_flashdata('print_confirm_id', $invoice_id);
        redirect('billing/create');
    }

    // View invoice (HTML)
    public function invoice($id = null) {
        if (!$id) redirect('billing');
        $data['page_title'] = 'Invoice';
        $data['invoice']    = $this->Invoice_model->get_by_id($id);
        $data['items']      = $this->Invoice_model->get_items($id);
        $data['company']    = $this->Invoice_model->get_company_settings();
        if (!$data['invoice']) {
            $this->session->set_flashdata('error', 'Invoice not found.');
            redirect('billing');
        }
        $data['schemes_applied'] = $this->db->where('invoice_id', $id)->where('scheme_name IS NOT NULL')->get('scheme_application_log')->result();
        $perm = $this->db->where('user_id', $this->session->userdata('user_id'))->get('user_permissions')->row();
        $allPm = $this->db->where('key_name', 'allow_all_payment_mode_edit')->get('company_settings')->row();
        $data['allow_payment_mode_change'] = ($perm ? (int)$perm->allow_payment_mode_change : 0)
            || ($allPm && $allPm->key_value === '1');
        $this->load->model('Serial_model');
        $data['serials']       = $this->Serial_model->get_for_invoice($id);
        $data['item_warranty'] = $this->Serial_model->warranties_for_invoice($id);
        $data['payment_modes'] = $this->_get_payment_modes();
        $this->render('billing/invoice', $data);
    }

    public function print_invoice($id = null) {
        if (!$id) redirect('billing');
        $data['invoice']  = $this->Invoice_model->get_by_id($id);
        $data['items']    = $this->Invoice_model->get_items($id);
        $data['company']  = $this->Invoice_model->get_company_settings();
        if (!$data['invoice']) show_404();
        $data['customer']  = $this->Customer_model->get_by_id($data['invoice']->customer_id);
        $data['splits']    = $this->db->where('invoice_id', $id)->get('invoice_payment_splits')->result();
        $data['outlet']    = $data['invoice']->location_id ? $this->Location_model->get_by_id($data['invoice']->location_id) : null;
        $data['schemes_applied'] = $this->db->where('invoice_id', $id)->where('scheme_name IS NOT NULL')->get('scheme_application_log')->result();
        // Serial + warranty details so the printed bill doubles as the
        // customer's warranty card — without the serial on paper there is
        // nothing to match a claim against later.
        $this->load->model('Serial_model');
        $data['serials']     = $this->Serial_model->get_for_invoice($id);
        $data['item_warranty'] = $this->Serial_model->warranties_for_invoice($id);
        $data['show_loyalty'] = $this->can_module('mod_loyalty');
        $this->load->view('billing/print', $data);
    }

    // AJAX: get product details + HSN GST rates for billing form
    public function get_product_ajax($id = null) {
        if (!$id || !$this->input->is_ajax_request()) show_404();
        // Pass active location so stock shown is for this outlet, not global total
        $location_id = $this->active_location_id();
        $product = $this->Product_model->get_for_billing($id, $location_id);
        if ($product) {
            $this->load->library('Gst_tax_engine');
            $sp = (float)$product->selling_price > 0 ? (float)$product->selling_price : (float)$product->mrp;
            $live_calc = $this->gst_tax_engine->calculate_sales_line([
                'product'          => $product,
                'unit_price'       => $sp,
                'qty'              => 1,
                'discount'         => 0,
                'is_tax_inclusive' => true,
                'is_interstate'    => false,
            ]);
            $product->gst_percent  = (float)$live_calc['gst_percent'];
            $product->igst_percent = (float)$live_calc['igst_percent'];
            $product->cgst_percent = (float)$live_calc['cgst_percent'];
            $product->sgst_percent = (float)$live_calc['sgst_percent'];
        }
        header('Content-Type: application/json');
        echo json_encode($product);
    }

    // AJAX: barcode scanner lookup — PLU-aware
    public function get_by_barcode_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $barcode     = $this->input->post('barcode', TRUE);
        $location_id = $this->active_location_id();
        header('Content-Type: application/json');

        // Check Garment variant first
        $this->load->model('Garment_model');
        $gv = $this->Garment_model->get_variant_by_barcode($barcode, $location_id);
        if (!$gv) $gv = $this->Garment_model->get_variant_by_barcode($barcode, $location_id, true); // unique variant SKU
        if ($gv && !empty($gv->ambiguous)) {
            echo json_encode(['success' => false, 'ambiguous' => true, 'message' => 'This barcode is assigned to more than one item. Please search the product by name and choose the size/colour.']);
            return;
        }
        if ($gv) {
            $this->load->library('Gst_tax_engine');
            $sp = (float)$gv->selling_price > 0 ? (float)$gv->selling_price : (float)$gv->mrp;
            $live_calc = $this->gst_tax_engine->calculate_sales_line([
                'product'          => $gv,
                'unit_price'       => $sp,
                'qty'              => 1,
                'discount'         => 0,
                'is_tax_inclusive' => true,
                'is_interstate'    => false,
            ]);

            $product_obj = (object)[
                'id'              => (int)$gv->product_id,
                'variant_id'      => (int)$gv->id,
                'variant_sku'     => $gv->variant_sku,
                'size_name'       => $gv->size_name,
                'colour_name'     => $gv->colour_name,
                'name'            => $gv->product_name . ' [' . $gv->colour_name . ' / ' . $gv->size_name . ']',
                'item_code'       => $gv->variant_sku,
                'hsn_code'        => $gv->hsn_code,
                'selling_price'   => (float)$gv->selling_price,
                'mrp'             => (float)$gv->mrp,
                'gst_percent'     => (float)$live_calc['gst_percent'],
                'igst_percent'    => (float)$live_calc['igst_percent'],
                'cgst_percent'    => (float)$live_calc['cgst_percent'],
                'sgst_percent'    => (float)$live_calc['sgst_percent'],
                'stock_qty'       => (float)$gv->loc_stock_qty,
                'unit'            => $gv->unit ?: 'PCS',
                'carton_size'     => 1,
                'description'     => $gv->style_code ?: '',
                'image1'          => $gv->image1,
                'no_discount'     => 0,
                'scan_unit'       => 'PCS',
                'scan_multiplier' => 1,
                'is_garment'      => 1
            ];
            // 'variant' = the exact scanned size/colour row; the billing page adds it directly (no matrix popup)
            echo json_encode(['success' => true, 'mode' => 'garment', 'product' => $product_obj, 'variant' => $gv]);
            return;
        }

        // Try PLU system first
        $plus = $this->Plu_model->get_by_barcode($barcode, $location_id);

        if (!empty($plus)) {
            if (count($plus) === 1) {
                $plu_data = $this->Plu_model->get_for_billing($plus[0]->id, $location_id);
                echo json_encode(['success' => true, 'mode' => 'single', 'plu' => $plu_data, 'count' => 1]);
            } else {
                $plu_list = [];
                foreach ($plus as $p) {
                    $plu_list[] = [
                        'id'           => $p->id,
                        'plu_code'     => $p->plu_code,
                        'item_name'    => $p->item_name,
                        'item_code'    => $p->item_code,
                        'mrp'          => (float)$p->mrp,
                        'selling_rate' => (float)$p->selling_rate,
                        'stock_qty'    => (float)$p->stock_qty,
                        'batch_no'     => $p->batch_no,
                        'expiry_date'  => $p->expiry_date,
                        'gst_percent'  => (float)$p->gst_percent,
                        'unit'         => $p->unit,
                    ];
                }
                echo json_encode([
                    'success'   => true,
                    'mode'      => 'multiple',
                    'plus'      => $plu_list,
                    'item_name' => $plus[0]->item_name,
                    'barcode'   => $barcode,
                    'count'     => count($plus),
                ]);
            }
            return;
        }

        // Serial / VIN lookup for EV Scooters, Batteries, Chargers
        $this->load->model('Serial_model');
        if ($this->Serial_model->schema_ready()) {
            $sdetail = $this->Serial_model->get_detail($barcode);
            if ($sdetail) {
                if ($sdetail->status !== Serial_model::AVAILABLE) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Serial Number ' . $sdetail->serial_no . ' is ' . $sdetail->status . ' (' . $this->Serial_model->status_message($sdetail->serial_no, $sdetail->status) . ')'
                    ]);
                    return;
                }
                if ($location_id && $sdetail->location_id && (int)$sdetail->location_id !== (int)$location_id) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Serial Number ' . $sdetail->serial_no . ' is at ' . ($sdetail->location_name ?: 'another outlet') . ', not available at this outlet.'
                    ]);
                    return;
                }

                $prod = $this->Product_model->get_for_billing($sdetail->product_id, $location_id);
                if ($prod) {
                    $this->load->library('Gst_tax_engine');
                    $sp = (float)$prod->selling_price > 0 ? (float)$prod->selling_price : (float)$prod->mrp;
                    $live_calc = $this->gst_tax_engine->calculate_sales_line([
                        'product'          => $prod,
                        'unit_price'       => $sp,
                        'qty'              => 1,
                        'discount'         => 0,
                        'is_tax_inclusive' => true,
                        'is_interstate'    => false,
                    ]);
                    $prod->gst_percent  = (float)$live_calc['gst_percent'];
                    $prod->igst_percent = (float)$live_calc['igst_percent'];
                    $prod->cgst_percent = (float)$live_calc['cgst_percent'];
                    $prod->sgst_percent = (float)$live_calc['sgst_percent'];

                    echo json_encode([
                        'success'       => true,
                        'mode'          => 'serial_ev',
                        'product'       => $prod,
                        'serial'        => $sdetail,
                        'scanned_sn'    => $sdetail->serial_no,
                        'chassis_no'    => $sdetail->chassis_no ?: ($sdetail->item_type === 'scooter' ? $sdetail->serial_no : ''),
                        'motor_no'      => $sdetail->motor_no ?: '',
                        'colour'        => $sdetail->colour ?: '',
                        'item_type'     => $sdetail->item_type,
                    ]);
                    return;
                }
            }
        }

        // Legacy product barcode fallback
        $product = $this->Product_model->get_by_barcode($barcode);
        if ($product) {
            echo json_encode(['success' => true, 'mode' => 'legacy', 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Barcode not found']);
        }
    }

    /**
     * AJAX endpoint to return the size x colour matrix for a garment product in Billing / POS
     */
    public function get_variants_matrix_ajax($product_id = null) {
        if (!$product_id) {
            $product_id = (int)$this->input->get('product_id');
        }
        $product = $this->db->where('id', (int)$product_id)->get('products')->row();
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            return;
        }

        $this->load->model('Garment_model');
        $active_loc = $this->active_location_id() ?: 1;
        $variants = $this->Garment_model->get_variants_by_product($product_id, $active_loc);

        $sizes   = [];
        $colours = [];
        $matrix  = [];

        foreach ($variants as $v) {
            $c_key = $v->colour_name ?: 'Standard';
            $s_key = $v->size_name ?: 'Free Size';

            if (!isset($colours[$c_key])) {
                $colours[$c_key] = [
                    'id'   => $v->colour_id,
                    'name' => $c_key,
                    'hex'  => $v->colour_hex ?: '#808080'
                ];
            }

            if (!isset($sizes[$s_key])) {
                $sizes[$s_key] = [
                    'id'          => $v->size_id,
                    'name'        => $s_key,
                    'measurement' => $v->size_measurement ?: '',
                    'sort_order'  => (int)($v->size_sort_order ?? 999)
                ];
            }

            $matrix[$c_key][$s_key] = [
                'id'            => (int)$v->id,
                'variant_sku'   => $v->variant_sku,
                'barcode'       => $v->barcode,
                'cost_price'    => (float)($v->cost_price ?: $product->basic_cost),
                'mrp'           => (float)($v->mrp ?: $product->mrp),
                'selling_price' => (float)($v->selling_price ?: $product->selling_price),
                'stock_qty'     => (float)($v->loc_stock_qty !== null ? $v->loc_stock_qty : $v->stock_qty),
            ];
        }

        uasort($sizes, function($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        $hsn_row = $product->hsn_id ? $this->db->where('id', $product->hsn_id)->get('hsn_master')->row() : null;

        header('Content-Type: application/json');
        echo json_encode([
            'success'  => true,
            'product'  => [
                'id'               => (int)$product->id,
                'name'             => $product->name,
                'item_code'        => $product->item_code,
                'style_code'       => $product->style_code ?: '',
                'design_no'        => $product->design_no ?: '',
                'garment_category' => $product->garment_category ?: '',
                'gender'           => $product->gender ?: '',
                'basic_cost'       => (float)$product->basic_cost,
                'purchase_price'   => (float)$product->purchase_price,
                'mrp'              => (float)$product->mrp,
                'selling_price'    => (float)$product->selling_price,
                'gst_percent'      => (float)($hsn_row ? $hsn_row->igst_percent : $product->gst_percent),
                'hsn_id'           => $product->hsn_id,
                'hsn_code'         => $hsn_row ? $hsn_row->hsn_code : $product->hsn_code,
                'unit'             => $product->unit ?: 'PCS',
                'is_garment'       => (int)$product->is_garment,
            ],
            'sizes'    => array_values($sizes),
            'colours'  => array_values($colours),
            'matrix'   => $matrix,
            'count'    => count($variants)
        ]);
    }

    // AJAX: get specific PLU data for billing
    public function get_plu_ajax($id = null) {
        if (!$id || !$this->input->is_ajax_request()) show_404();
        $plu = $this->Plu_model->get_for_billing($id, $this->active_location_id());
        header('Content-Type: application/json');
        echo json_encode($plu ?: ['error' => 'Not found']);
    }

    // AJAX: get customer details
    public function get_customer_ajax($id = null) {
        if (!$id || !$this->input->is_ajax_request()) show_404();
        $customer = $this->Customer_model->get_by_id($id);
        header('Content-Type: application/json');
        echo json_encode($customer);
    }

    // Cancel invoice (admin only)
    public function cancel($id = null) {
        $this->require_admin();
        $inv = $this->Invoice_model->get_by_id($id);
        if (!$inv || $inv->status === 'cancelled') {
            $this->session->set_flashdata('error', 'Cannot cancel this invoice.');
            redirect('billing');
        }
        $items = $this->Invoice_model->get_items($id);
        $this->db->trans_start();
        foreach ($items as $item) {
            $this->Product_model->increase_stock($item->product_id, $item->qty);
        }
        if ($inv->due_amount > 0) {
            $this->Customer_model->decrease_balance($inv->customer_id, $inv->due_amount);
        }
        
        // Reverse customer wallet adjustments
        if (isset($inv->wallet_amount_used) && $inv->wallet_amount_used > 0) {
            $this->Customer_model->adjust_wallet(
                $inv->customer_id,
                $inv->wallet_amount_used,
                'credit',
                'refund',
                $inv->id,
                $inv->invoice_no,
                'Restored used wallet balance of ₹' . number_format($inv->wallet_amount_used, 2) . ' from cancelled bill #' . $inv->invoice_no,
                $this->session->userdata('user_id')
            );
        }
        if (isset($inv->wallet_amount_credited) && $inv->wallet_amount_credited > 0) {
            $this->Customer_model->adjust_wallet(
                $inv->customer_id,
                $inv->wallet_amount_credited,
                'debit',
                'refund',
                $inv->id,
                $inv->invoice_no,
                'Reversed excess wallet deposit of ₹' . number_format($inv->wallet_amount_credited, 2) . ' from cancelled bill #' . $inv->invoice_no,
                $this->session->userdata('user_id')
            );
        }

        // Serials sold on this bill go back into stock at the billing location,
        // and their warranty rows are marked returned rather than deleted — the
        // history of the sale stays readable.
        $this->load->model('Serial_model');
        $released = 0;
        if ($this->Serial_model->schema_ready()) {
            foreach ($this->Serial_model->get_by_doc('invoice', $id) as $srow) {
                if ($srow->status !== 'SOLD') continue;   // already returned/repaired: leave it
                $this->Serial_model->set_status($srow->id, 'IN_STOCK', [
                    'txn_type'     => 'INVOICE_CANCELLED',
                    'reference_id' => $id,
                    'reference_no' => $inv->invoice_no,
                    'location_id'  => $inv->location_id ?: ($this->active_location_id() ?: null),
                    'remarks'      => 'Invoice cancelled — unit returned to stock',
                ]);
                $released++;
            }
            $this->db->where('invoice_id', $id)->update('serial_warranties', ['is_returned' => 1]);
        }

        // Loyalty (§19): earned points come back out, redeemed points go back
        // to the customer. Reversal rows are written — the original EARN is
        // never deleted.
        $this->load->model('Loyalty_model');
        if ($this->Loyalty_model->schema_ready()) {
            $rev = $this->Loyalty_model->reverse_for_cancel($id, $inv->invoice_no);
            if ($rev['reversed'] > 0 || $rev['restored'] > 0) {
                $this->log_activity('loyalty', 'bill_cancel',
                    'Bill ' . $inv->invoice_no . ' cancelled: ' . (int)$rev['reversed']
                    . ' point(s) reversed, ' . (int)$rev['restored'] . ' redeemed point(s) returned',
                    $id, $inv->invoice_no);
            }
        }

        $this->Invoice_model->update_status($id, 'cancelled');
        $this->db->trans_complete();

        if ($released) {
            $this->log_activity('billing', 'serials_released',
                $released . ' serial number(s) returned to stock from cancelled invoice ' . $inv->invoice_no,
                $id, $inv->invoice_no);
        }
        $this->log_activity('billing', 'cancel_bill', 'Cancelled invoice #' . $inv->invoice_no, $id, $inv->invoice_no);
        $this->session->set_flashdata('success', 'Invoice #' . $inv->invoice_no . ' cancelled. Stock restored.');
        redirect('billing/invoice/' . $id);
    }

    // Change payment mode only (admin only) - for correcting a cashier's
    // mis-selected mode (e.g. Cash punched instead of Card) without touching
    // items, totals, or anything else on the bill.
    public function update_payment_mode($id = null) {
        $role = $this->session->userdata('role');
        $perm = $this->db->where('user_id', $this->session->userdata('user_id'))->get('user_permissions')->row();
        $allPm = $this->db->where('key_name', 'allow_all_payment_mode_edit')->get('company_settings')->row();
        $allow_payment_mode_change = ($perm ? (int)$perm->allow_payment_mode_change : 0)
            || ($allPm && $allPm->key_value === '1');

        if ($role !== 'admin' && $role !== 'superadmin' && !$allow_payment_mode_change) {
            $this->session->set_flashdata('error', 'You do not have permission to change payment mode.');
            redirect('billing');
        }

        if (!$id) redirect('billing');
        $inv = $this->Invoice_model->get_by_id($id);
        if (!$inv) { $this->session->set_flashdata('error', 'Invoice not found.'); redirect('billing'); }
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('billing');

        if ($inv->status === 'cancelled') {
            $this->session->set_flashdata('error', 'Cannot change payment mode on a cancelled invoice.');
            redirect('billing');
        }

        $new_mode = $this->input->post('payment_mode', TRUE);
        $reason = $this->input->post('reason', TRUE);
        $remarks = $this->input->post('remarks', TRUE);
        
        if (empty(trim($reason))) {
            $this->session->set_flashdata('error', 'Reason is mandatory for changing payment mode.');
            redirect('billing');
        }

        $valid_modes = ['split'];
        $db_modes = $this->_get_payment_modes();
        foreach ($db_modes as $pm) { $valid_modes[] = $pm->code; }
        if (!in_array($new_mode, $valid_modes, true)) {
            $this->session->set_flashdata('error', 'Invalid payment mode.');
            redirect('billing');
        }
        $old_mode = $inv->payment_mode;

        if ($new_mode === 'split') {
            $split_modes = $this->input->post('split_mode');
            $split_amounts = $this->input->post('split_amount');
            $total_split = 0;
            $valid_splits = [];
            if (is_array($split_modes)) {
                foreach ($split_modes as $k => $sm) {
                    $amt = (float)($split_amounts[$k] ?? 0);
                    if ($amt > 0) {
                        $valid_splits[] = ['mode' => $sm, 'amount' => $amt];
                        $total_split += $amt;
                    }
                }
            }
            if (count($valid_splits) < 2) {
                $this->session->set_flashdata('error', 'Split payment requires at least 2 amounts.');
                redirect('billing');
            }
            if (abs($total_split - $inv->total_amount) > 0.05) {
                $this->session->set_flashdata('error', 'Split amounts total (' . $total_split . ') must match invoice total (' . $inv->total_amount . ').');
                redirect('billing');
            }

            $this->db->trans_start();
            $this->db->where('id', $id)->update('invoices', ['payment_mode' => 'split', 'multi_payment' => 1]);
            $this->db->where('invoice_id', $id)->delete('invoice_payment_splits');
            foreach ($valid_splits as $s) {
                $this->db->insert('invoice_payment_splits', [
                    'invoice_id' => $id,
                    'payment_mode' => $s['mode'],
                    'amount' => $s['amount']
                ]);
            }
            
            $this->db->insert('audit_payment_mode_changes', [
                'invoice_id' => $id,
                'bill_no'    => $inv->invoice_no,
                'old_mode'   => $old_mode,
                'new_mode'   => 'split',
                'changed_by' => $this->session->userdata('user_id'),
                'reason'     => $reason,
                'remarks'    => $remarks,
                'terminal'   => $this->input->ip_address(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->db->trans_complete();

            $this->log_activity('billing', 'change_payment_mode', 'Changed payment mode for invoice #' . $inv->invoice_no . ' from ' . strtoupper($old_mode) . ' to SPLIT', $id, $inv->invoice_no);
            $this->session->set_flashdata('success', 'Payment mode changed to SPLIT.');
            redirect('billing');
            return;
        }

        if ($new_mode !== $inv->payment_mode) {
            $this->db->trans_start();
            $this->db->where('id', $id)->update('invoices', ['payment_mode' => $new_mode, 'multi_payment' => 0]);
            $this->db->where('invoice_id', $id)->delete('invoice_payment_splits');
            
            $this->db->insert('audit_payment_mode_changes', [
                'invoice_id' => $id,
                'bill_no'    => $inv->invoice_no,
                'old_mode'   => $old_mode,
                'new_mode'   => $new_mode,
                'changed_by' => $this->session->userdata('user_id'),
                'reason'     => $reason,
                'remarks'    => $remarks,
                'terminal'   => $this->input->ip_address(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->db->trans_complete();

            $this->log_activity('billing', 'change_payment_mode',
                'Changed payment mode for invoice #' . $inv->invoice_no . ' from ' . strtoupper($old_mode) . ' to ' . strtoupper($new_mode),
                $id, $inv->invoice_no);
            $this->session->set_flashdata('success', 'Payment mode for #' . $inv->invoice_no . ' changed from ' . strtoupper($old_mode) . ' to ' . strtoupper($new_mode) . '.');
        }
        redirect('billing');
    }

    public function pdf($id = null) {
        if (!$id) redirect('billing');
        $data['invoice']  = $this->Invoice_model->get_by_id($id);
        $data['items']    = $this->Invoice_model->get_items($id);
        $data['company']  = $this->Invoice_model->get_company_settings();
        $data['customer'] = $this->Customer_model->get_by_id($data['invoice']->customer_id);
        if (!$data['invoice']) redirect('billing');
        $data['schemes_applied'] = $this->db->where('invoice_id', $id)->where('scheme_name IS NOT NULL')->get('scheme_application_log')->result();
        if (!class_exists('Mpdf\\Mpdf')) {
            redirect(base_url('billing/print_invoice/' . $id));
        }
        $this->load->library('pdf_generator');
        $html     = $this->load->view('billing/pdf_template', $data, TRUE);
        $filename = 'Invoice_' . $data['invoice']->invoice_no . '_' . date('Ymd');
        $this->pdf_generator->generate($html, $filename, 'download');
    }

    public function email_invoice($id = null) {
        if (!$id) redirect('billing');
        $invoice = $this->Invoice_model->get_by_id($id);
        $items   = $this->Invoice_model->get_items($id);
        $company = $this->Invoice_model->get_company_settings();
        if (!$invoice) redirect('billing');
        $to_email = $this->input->post('email', TRUE);
        if (!$to_email || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Invalid email address.');
            redirect('billing/invoice/' . $id);
        }

        $customer       = $this->Customer_model->get_by_id($invoice->customer_id);
        $schemes_applied = $this->db->where('invoice_id', $id)->where('scheme_name IS NOT NULL')->get('scheme_application_log')->result();
        $data       = ['invoice' => $invoice, 'items' => $items, 'company' => $company, 'customer' => $customer, 'schemes_applied' => $schemes_applied];
        $email_body = $this->load->view('billing/email_template', $data, TRUE);

        // Generate PDF attachment using mPDF
        $pdf_tmp_path = null;
        $pdf_filename = 'Invoice_' . $invoice->invoice_no . '.pdf';
        try {
            $this->load->library('pdf_generator');
            $pdf_html     = $this->load->view('billing/pdf_template', $data, TRUE);
            $pdf_string   = $this->pdf_generator->as_string($pdf_html, 'Invoice_' . $invoice->invoice_no);
            $pdf_tmp_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $pdf_filename;
            file_put_contents($pdf_tmp_path, $pdf_string);
        } catch (\Exception $e) {
            $pdf_tmp_path = null; // send without attachment if PDF fails
        }

        $this->load->library('email');
        $this->email->initialize([
            'protocol'    => 'smtp',
            'smtp_host'   => $this->db->where('key_name','smtp_host')->get('company_settings')->row()->key_value ?? 'smtp.gmail.com',
            'smtp_port'   => 587,
            'smtp_user'   => $this->db->where('key_name','smtp_user')->get('company_settings')->row()->key_value ?? '',
            'smtp_pass'   => $this->db->where('key_name','smtp_pass')->get('company_settings')->row()->key_value ?? '',
            'smtp_crypto' => 'tls',
            'mailtype'    => 'html',
            'charset'     => 'utf-8',
        ]);
        $this->email->from($company['company_email'] ?? 'noreply@viewchew.com', $company['company_name'] ?? 'View & Chew');
        $this->email->to($to_email);
        $this->email->subject('Invoice ' . $invoice->invoice_no . ' from ' . ($company['company_name'] ?? 'View & Chew'));
        $this->email->message($email_body);

        // Attach PDF if generated successfully
        if ($pdf_tmp_path && file_exists($pdf_tmp_path)) {
            $this->email->attach($pdf_tmp_path, 'attachment', $pdf_filename, 'application/pdf');
        }

        if ($this->email->send()) {
            $att_note = $pdf_tmp_path ? ' (PDF attached)' : '';
            $this->session->set_flashdata('success', 'Invoice emailed to ' . $to_email . $att_note);
        } else {
            $this->session->set_flashdata('error', 'Email failed. Check SMTP settings.');
        }

        // Clean up temp PDF file
        if ($pdf_tmp_path && file_exists($pdf_tmp_path)) {
            @unlink($pdf_tmp_path);
        }

        redirect('billing/invoice/' . $id);
    }

    public function scan() {
        $data['page_title'] = 'Barcode Billing';
        $data['customers']  = $this->Customer_model->get_all_for_select();
        $data['products']   = $this->Product_model->get_all(null, self::PRODUCTS_PAGE_LIMIT);
        $data['company']    = $this->Invoice_model->get_company_settings();
        $data['settings']   = array_merge($this->db->get('company_settings')->row_array() ?: [], $data['company']);
        $this->render('billing/barcode_billing', $data);
    }

    // ============================================================
    // BILL MODIFY SYSTEM
    // ============================================================

    // Edit an existing invoice
    public function edit($id) {
        // Check permission setting (key-value lookup)
        $mod_row = $this->db->where('key_name', 'enable_bill_modify')->get('company_settings')->row();
        $bill_modify_enabled = $mod_row ? (bool)$mod_row->key_value : true;
        // Also check direct column (added in migration 06)
        if (!$mod_row) {
            $first_row = $this->db->limit(1)->get('company_settings')->row();
            if ($first_row && isset($first_row->enable_bill_modify)) {
                $bill_modify_enabled = (bool)$first_row->enable_bill_modify;
            }
        }
        if (!$bill_modify_enabled) {
            $this->session->set_flashdata('error', 'Bill editing is disabled in Settings.');
            redirect('billing/invoice/' . $id);
        }

        // Role check
        if ($this->session->userdata('role') === 'cashier') {
            $this->session->set_flashdata('error', 'Access denied: Cashiers cannot modify bills.');
            redirect('billing/invoice/' . $id);
        }
        if ($this->session->userdata('role') !== 'superadmin' && $this->session->userdata('role') !== 'admin' && $this->session->userdata('role') !== 'manager') {
            $perm = $this->db->where('user_id', $this->session->userdata('user_id'))
                             ->get('user_permissions')->row();
            if (!$perm || !$perm->can_edit_bill) {
                $this->session->set_flashdata('error', 'You do not have permission to edit bills.');
                redirect('billing/invoice/' . $id);
            }
        }

        $invoice = $this->Invoice_model->get_by_id($id);
        if (!$invoice || $invoice->status === 'cancelled') {
            $this->session->set_flashdata('error', 'Invoice cannot be edited.');
            redirect('billing');
        }

        $data['page_title'] = 'Edit Invoice #' . $invoice->invoice_no;
        $data['invoice']    = $invoice;
        $data['items']      = $this->Invoice_model->get_items($id);
        $data['customers']  = $this->Customer_model->get_all_for_select();
        $data['products']   = $this->Product_model->get_all(null, self::PRODUCTS_PAGE_LIMIT);
        // Backfill: this invoice's own line items must always be selectable in their
        // row's dropdown even if they fall outside the capped list above (e.g. not
        // among the first PRODUCTS_PAGE_LIMIT alphabetically) — otherwise editing a
        // bill would silently blank out any such row's product.
        $loaded_ids = array_map(function($p) { return (int)$p->id; }, $data['products']);
        $item_ids   = array_map(function($it) { return (int)$it->product_id; }, $data['items']);
        $missing_ids = array_diff($item_ids, $loaded_ids);
        if ($missing_ids) {
            $data['products'] = array_merge($data['products'], $this->Product_model->get_by_ids($missing_ids));
        }
        $data['company']    = $this->Invoice_model->get_company_settings();
        $data['settings']   = array_merge($this->db->get('company_settings')->row_array() ?: [], $data['company']);
        $data['pay_splits'] = $this->db->where('invoice_id', $id)->get('invoice_payment_splits')->result();
        $data['payment_modes'] = $this->_get_payment_modes();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('billing/edit', $data);
            return;
        }

        // ---- Process edit ----
        $product_ids = $this->input->post('product_id');
        $qtys        = $this->input->post('qty');
        $rates       = $this->input->post('rate');
        $discounts   = $this->input->post('item_discount');
        $gst_pcts    = $this->input->post('gst_percent');

        if (empty($product_ids) || !is_array($product_ids)) {
            $this->session->set_flashdata('error', 'Add at least one product.');
            $this->render('billing/edit', $data);
            return;
        }

        // ── Determine payment mode up front (before any stock mutation below) so a
        // missing reason can bail out cleanly, without leaving stock half-reversed.
        // Same audit trail as the dedicated "Pay Mode" button, so both routes show
        // up together in reports/payment_mode_changes.
        $pay_modes   = $this->input->post('pay_mode');
        $pay_amounts = $this->input->post('pay_amount');
        $valid_pay_lines = [];
        if (!empty($pay_modes) && is_array($pay_modes)) {
            foreach ($pay_modes as $k => $pm) {
                if (empty($pm) || (float)($pay_amounts[$k] ?? 0) <= 0) continue;
                $valid_pay_lines[$k] = $pm;
            }
        }
        $is_multi_pay = count($valid_pay_lines) > 1;
        $old_payment_mode = $invoice->payment_mode;
        $payment_mode = $is_multi_pay
            ? 'split'
            : (count($valid_pay_lines) === 1 ? reset($valid_pay_lines) : $invoice->payment_mode);

        $pm_change_reason = $this->input->post('pm_change_reason', TRUE);
        if ($payment_mode !== $old_payment_mode && empty(trim($pm_change_reason ?? ''))) {
            $this->session->set_flashdata('error', 'Payment mode changed — please give a reason for the change.');
            $this->render('billing/edit', $data);
            return;
        }

        // Snapshot old items for audit log
        $old_items = $data['items'];
        $old_total = $invoice->total_amount;

        // Reverse old stock
        foreach ($old_items as $old_item) {
            $multiplier = isset($old_item->multiplier) ? (float)$old_item->multiplier : 1;
            $old_qty    = isset($old_item->internal_qty) ? (float)$old_item->internal_qty : (float)$old_item->qty * $multiplier;
            $this->db->where('id', $old_item->product_id)
                     ->set('stock_qty', 'stock_qty + ' . $old_qty, FALSE)
                     ->update('products');
        }

        // Build new items
        $new_items   = [];
        $subtotal    = 0;
        $total_disc  = 0;
        $taxable_amt = 0;

        $customer    = $this->Customer_model->get_by_id($invoice->customer_id);
        $company_state = $data['company']['company_state'] ?? 'Punjab';
        $is_interstate = ($invoice->invoice_type !== 'retail' && !empty($customer->state) &&
            strtolower(trim($customer->state)) !== strtolower(trim($company_state))) ? 1 : 0;

        foreach ($product_ids as $i => $pid) {
            $pid = (int)$pid; if (!$pid) continue;
            $qty      = (float)($qtys[$i] ?? 1);
            $rate     = (float)($rates[$i] ?? 0);
            $disc_pct = (float)($discounts[$i] ?? 0);
            $gst_pct  = (float)($gst_pcts[$i] ?? 0);

            // Rate is GST-inclusive: it's the actual price the customer pays.
            // Taxable value + GST are extracted backwards out of that price.
            $disc_amt   = round($rate * $qty * ($disc_pct / 100), 2);
            $line_total = round(($rate * $qty) - $disc_amt, 2);
            $taxable    = round($line_total / (1 + $gst_pct / 100), 2);
            $gst_total  = round($line_total - $taxable, 2);
            $cgst_pct  = $is_interstate ? 0 : $gst_pct / 2;
            $sgst_pct  = $is_interstate ? 0 : $gst_pct / 2;
            $igst_pct  = $is_interstate ? $gst_pct : 0;
            $igst_amt  = $is_interstate ? $gst_total : 0;
            $cgst_amt  = $is_interstate ? 0 : round($gst_total / 2, 2);
            $sgst_amt  = $is_interstate ? 0 : round($gst_total - $cgst_amt, 2);

            $subtotal    += $rate * $qty;
            $total_disc  += $disc_amt;
            $taxable_amt += $taxable;

            $product = $this->Product_model->get_by_id($pid);
            $new_items[] = [
                'product_id'   => $pid,
                'product_name' => $product ? $product->name : 'Unknown',
                'hsn_code'     => $product ? ($product->hsn_code ?? '') : '',
                'qty'          => $qty,
                'unit'         => $product ? $product->unit : 'PCS',
                'mrp'          => $product ? ($product->mrp ?? 0) : 0,
                'rate'         => $rate,
                'discount'     => $disc_amt,
                'taxable_amt'  => $taxable,
                'gst_percent'  => $gst_pct,
                'cgst_percent' => $cgst_pct,
                'sgst_percent' => $sgst_pct,
                'igst_percent' => $igst_pct,
                'cgst_amount'  => $cgst_amt,
                'sgst_amount'  => $sgst_amt,
                'igst_amount'  => $igst_amt,
                'gst_amount'   => $gst_total,
                'total'        => $line_total,
            ];

            // Deduct new stock
            $this->db->where('id', $pid)
                     ->set('stock_qty', 'stock_qty - ' . $qty, FALSE)
                     ->update('products');
        }

        $total_gst    = array_sum(array_column($new_items, 'gst_amount'));
        $pre_edit_total = $taxable_amt + $total_gst;
        $total_amount = $pre_edit_total;
        $invoice_disc = (float)$this->input->post('invoice_discount');
        if ($invoice_disc > 0) {
            $total_amount -= $invoice_disc;
        }

        // Recalculate taxable/GST after invoice-level discount
        if ($invoice_disc > 0 && $pre_edit_total > 0) {
            $ratio = $total_amount / $pre_edit_total;
            $taxable_amt = round($taxable_amt * $ratio, 2);
            $total_gst   = round($total_amount - $taxable_amt, 2);
        }

        // pay_modes/pay_amounts/valid_pay_lines/is_multi_pay/payment_mode were already
        // derived above (before stock mutation) for the reason-check.
        $paid_amount = 0;
        foreach ($valid_pay_lines as $k => $pm) {
            if ($pm !== 'credit') $paid_amount += (float)$pay_amounts[$k];
        }
        if ($paid_amount > $total_amount) $paid_amount = $total_amount;

        $due_amount = round($total_amount - $paid_amount, 2);
        $status = $due_amount <= 0 ? 'paid' : ($paid_amount > 0 ? 'partial' : 'unpaid');

        // Update invoice
        $cgst_total = $is_interstate ? 0 : round($total_gst / 2, 2);
        $sgst_total = $is_interstate ? 0 : round($total_gst - $cgst_total, 2);
        $igst_total = $is_interstate ? $total_gst : 0;
        $this->db->where('id', $id)->update('invoices', [
            'subtotal'       => $subtotal,
            'discount'       => $total_disc + $invoice_disc,
            'taxable_amount' => $taxable_amt,
            'cgst_amount'    => $cgst_total,
            'sgst_amount'    => $sgst_total,
            'igst_amount'    => $igst_total,
            'total_gst'      => $total_gst,
            'total_amount'   => $total_amount,
            'paid_amount'    => $paid_amount,
            'due_amount'     => $due_amount,
            'status'         => $status,
            'payment_mode'   => $payment_mode,
            'multi_payment'  => $is_multi_pay ? 1 : 0,
            'is_interstate'  => $is_interstate,
            'is_delivery'    => $this->input->post('is_delivery') ? 1 : 0,
            'notes'          => $this->input->post('notes', TRUE),
        ]);

        // Replace payment splits (only meaningful when multi-payment; single-mode
        // payments live solely on the invoices row above, matching create()'s convention)
        $this->db->where('invoice_id', $id)->delete('invoice_payment_splits');
        if ($is_multi_pay) {
            foreach ($valid_pay_lines as $k => $pm) {
                $this->db->insert('invoice_payment_splits', [
                    'invoice_id'   => $id,
                    'payment_mode' => $pm,
                    'amount'       => (float)$pay_amounts[$k],
                ]);
            }
        }

        // Replace items
        $this->db->where('invoice_id', $id)->delete('invoice_items');
        foreach ($new_items as &$ni) { $ni['invoice_id'] = $id; }
        $this->db->insert_batch('invoice_items', $new_items);

        // Audit log
        $this->db->insert('invoice_edit_log', [
            'invoice_id'     => $id,
            'invoice_no'     => $invoice->invoice_no,
            'edit_type'      => 'modify',
            'old_items'      => json_encode($old_items),
            'new_items'      => json_encode($new_items),
            'old_total'      => $old_total,
            'new_total'      => $total_amount,
            'reason'         => $this->input->post('edit_reason', TRUE),
            'edited_by'      => $this->session->userdata('user_id'),
            'edited_by_name' => $this->session->userdata('name'),
            'ip_address'     => $this->input->ip_address(),
        ]);

        // Payment mode changed as part of this edit — log it the same way the
        // dedicated "Pay Mode" button does, so it shows up in the same report.
        if ($payment_mode !== $old_payment_mode) {
            $this->db->insert('audit_payment_mode_changes', [
                'invoice_id' => $id,
                'bill_no'    => $invoice->invoice_no,
                'old_mode'   => $old_payment_mode,
                'new_mode'   => $payment_mode,
                'changed_by' => $this->session->userdata('user_id'),
                'reason'     => $pm_change_reason,
                'remarks'    => 'Changed via Edit Bill',
                'terminal'   => $this->input->ip_address(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->log_activity('billing', 'change_payment_mode', 'Changed payment mode for invoice #' . $invoice->invoice_no . ' from ' . strtoupper($old_payment_mode) . ' to ' . strtoupper($payment_mode) . ' (via edit)', $id, $invoice->invoice_no);
        }

        // Also write to audit_logs
        $this->db->insert('audit_logs', [
            'module'      => 'billing',
            'action'      => 'edit',
            'record_id'   => $id,
            'record_no'   => $invoice->invoice_no,
            'old_amount'  => $old_total,
            'new_amount'  => $total_amount,
            'description' => 'Invoice modified. Old total: ' . $old_total . ', New total: ' . $total_amount,
            'user_id'     => $this->session->userdata('user_id'),
            'user_name'   => $this->session->userdata('name'),
            'ip_address'  => $this->input->ip_address(),
        ]);

        $this->session->set_flashdata('success', 'Invoice updated. Audit log recorded.');

        $company_settings = $this->Invoice_model->get_company_settings();
        if (isset($company_settings['print_mode']) && $company_settings['print_mode'] === 'direct') {
            $this->session->set_flashdata('prompt_silent_print', true);
        }

        redirect('billing/invoice/' . $id);
    }

    // View audit log for an invoice
    public function audit_log($id) {
        $invoice = $this->Invoice_model->get_by_id($id);
        if (!$invoice) show_404();

        $data['page_title'] = 'Audit Log: ' . $invoice->invoice_no;
        $data['invoice']    = $invoice;
        $data['logs']       = $this->db->where('invoice_id', $id)
                                       ->order_by('edited_at', 'DESC')
                                       ->get('invoice_edit_log')->result();
        $data['audit_logs'] = $this->db->where('record_id', $id)->where('module','billing')
                                        ->order_by('created_at','DESC')
                                        ->get('audit_logs')->result();
        $this->render('billing/audit_log', $data);
    }

    // View all audit logs (admin)
    public function all_audit_logs() {
        if ($this->session->userdata('role') !== 'admin') {
            $this->session->set_flashdata('error', 'Admin access required.');
            redirect('billing');
        }
        $data['page_title'] = 'All Bill Modification Logs';
        $data['logs']       = $this->db->order_by('edited_at','DESC')->get('invoice_edit_log')->result();
        $this->render('billing/all_audit_logs', $data);
    }

    // AJAX: get payment splits for an invoice
    public function get_payment_splits_ajax($invoice_id = null) {
        if (!$invoice_id || !$this->input->is_ajax_request()) show_404();
        $splits = $this->db->where('invoice_id', $invoice_id)->get('invoice_payment_splits')->result();
        header('Content-Type: application/json');
        echo json_encode($splits);
    }

    // AJAX: lookup customer by phone number
    public function get_customer_by_phone_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $phone = trim($this->input->post('phone', TRUE));
        header('Content-Type: application/json');

        if (empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
            return;
        }

        // Normalize and get last 10 digits
        $digits10 = substr(preg_replace('/\D/', '', $phone), -10);

        if (strlen($digits10) < 10) {
            $this->db->like('phone', $phone);
        } else {
            $this->db->like('phone', $digits10);
        }

        $customers = $this->db->where('status', 1)->get('customers')->result();
        $match = null;

        if (!empty($customers)) {
            foreach ($customers as $c) {
                if (substr(preg_replace('/\D/', '', $c->phone), -10) === $digits10) {
                    $match = $c;
                    break;
                }
            }
        }

        if ($match) {
            echo json_encode(['success' => true, 'customer' => $match]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Customer not found.']);
        }
    }

    // AJAX: quick create customer for B2C billing
    public function create_customer_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');

        $name  = trim($this->input->post('name', TRUE));
        $phone = trim($this->input->post('phone', TRUE));

        if (empty($name) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Name and Phone are required.']);
            return;
        }

        // Check duplicate phone
        $digits10 = substr(preg_replace('/\D/', '', $phone), -10);
        if (strlen($digits10) >= 10) {
            $all = $this->db->where('status', 1)->get('customers')->result();
            foreach ($all as $c) {
                if (substr(preg_replace('/\D/', '', $c->phone), -10) === $digits10) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'A customer with this phone number already exists.',
                        'customer' => $c
                    ]);
                    return;
                }
            }
        }

        $address = trim($this->input->post('address', TRUE));

        $data = [
            'name'          => $name,
            'phone'         => $phone,
            'address'       => $address,
            'type'          => 'retail',
            'state'         => 'Punjab',
            'status'        => 1,
            'portal_active' => 0,
            'credit_limit'  => 0,
            'balance'       => 0,
        ];

        $insert_id = $this->Customer_model->create($data);

        if ($insert_id) {
            $new_customer = $this->Customer_model->get_by_id($insert_id);
            echo json_encode(['success' => true, 'customer' => $new_customer]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save customer.']);
        }
    }

    // AJAX: verify manager PIN for item deletion authorization
    public function verify_manager_pin_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $pin = trim($this->input->post('pin', TRUE));
        header('Content-Type: application/json');

        if (empty($pin)) {
            echo json_encode(['success' => false, 'error' => 'PIN is required.']);
            return;
        }

        // Check for manager or admin user whose PIN (password) matches
        $manager = $this->db->select('id, name, role')
            ->where_in('role', ['manager', 'admin', 'superadmin'])
            ->where('status', 1)
            ->where('password', md5($pin))
            ->get('users')->row();

        if (!$manager) {
            // Also try SHA1 (fallback for different hash schemes)
            $manager = $this->db->select('id, name, role')
                ->where_in('role', ['manager', 'admin', 'superadmin'])
                ->where('status', 1)
                ->where('password', sha1($pin))
                ->get('users')->row();
        }

        // Default fallback PIN
        $default_pin = $this->db->where('key_name', 'manager_delete_pin')->get('company_settings')->row();
        $fallback    = $default_pin ? $default_pin->key_value : '1234';

        $authorized_by = null;
        if ($manager) {
            $authorized_by = $manager->name;
            $this->log_activity('item_delete_auth', 'billing', 'Manager ' . $manager->name . ' authorized item deletion', 0, 'billing');
        } elseif ($pin === $fallback) {
            // Default/fallback PIN path — previously logged nothing at all, which left
            // the most commonly used PIN for this action with zero audit trail.
            $authorized_by = 'Manager (default PIN)';
        }

        if ($authorized_by) {
            $this->_log_item_removal($authorized_by);
            echo json_encode(['success' => true, 'authorized_by' => $authorized_by]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid PIN. Authorization denied.']);
        }
    }

    // Records what was scanned-then-deleted for the "Removed Items" report — logged at
    // delete time (not at invoice save time), since the whole point is catching removals
    // on bills that may never get saved at all. Wrapped so a logging failure can never
    // block the actual item removal the cashier/manager just authorized.
    private function _log_item_removal($authorized_by) {
        try {
            $qty  = (float)$this->input->post('qty', TRUE);
            $rate = (float)$this->input->post('rate', TRUE);
            $this->db->insert('billing_item_removals', [
                'removed_at'    => date('Y-m-d H:i:s'),
                'location_id'   => $this->active_location_id() ?: null,
                'device_label'  => $this->input->post('device_label', TRUE) ?: null,
                'user_id'       => $this->session->userdata('user_id'),
                'user_name'     => $this->session->userdata('name'),
                'authorized_by' => $authorized_by,
                'product_id'    => (int)$this->input->post('product_id', TRUE) ?: null,
                'product_name'  => $this->input->post('product_name', TRUE) ?: 'Unknown item',
                'item_code'     => $this->input->post('item_code', TRUE) ?: null,
                'qty'           => $qty,
                'rate'          => $rate,
                'line_value'    => round($qty * $rate, 2),
                'customer_id'   => (int)$this->input->post('customer_id', TRUE) ?: null,
                'customer_name' => $this->input->post('customer_name', TRUE) ?: null,
            ]);
        } catch (Exception $e) {
            log_message('error', 'billing_item_removals insert failed: ' . $e->getMessage());
        }
    }

    // AJAX: most recent invoice for a customer (v2 billing UI "Last Bill" panel) —
    // read-only GET lookup, no CSRF token needed since CI3 only checks CSRF on POST.
    public function get_last_invoice_ajax($customer_id = null) {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');
        $customer_id = (int)$customer_id;
        if (!$customer_id) {
            echo json_encode(['success' => true, 'has_previous' => false]);
            return;
        }
        $row = $this->db->select('invoice_no, total_amount, invoice_date')
            ->where('customer_id', $customer_id)
            ->where('status !=', 'cancelled')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('invoices')->row();
        if (!$row) {
            echo json_encode(['success' => true, 'has_previous' => false]);
            return;
        }
        echo json_encode([
            'success'      => true,
            'has_previous' => true,
            'invoice_no'   => $row->invoice_no,
            'total_amount' => (float)$row->total_amount,
            'invoice_date' => $row->invoice_date,
        ]);
    }

    // ── Hold Bill / Recall Bill (v2 billing UI) ──
    // The full cart state (items, customer, discounts, notes) is built and read
    // entirely client-side as JSON — this endpoint just stores/returns that blob.
    public function hold_bill_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');

        $payload = $this->input->post('payload', TRUE);
        $items   = json_decode($payload, true);
        if (!$payload || !is_array($items)) {
            echo json_encode(['success' => false, 'message' => 'Invalid payload']);
            return;
        }

        $data = [
            'location_id'   => $this->active_location_id() ?: 1,
            'customer_id'   => (int)$this->input->post('customer_id', TRUE) ?: null,
            'customer_name' => $this->input->post('customer_name', TRUE) ?: 'Walk-in Customer',
            'item_count'    => (int)$this->input->post('item_count', TRUE),
            'total_amount'  => (float)$this->input->post('total_amount', TRUE),
            'payload'       => $payload,
            'created_by'    => $this->session->userdata('user_id'),
        ];
        $this->db->insert('held_bills', $data);
        $id = $this->db->insert_id();

        $loc = $this->active_location_id();
        if ($loc) {
            $this->db->where('location_id', $loc);
        }
        $count = $this->db->count_all_results('held_bills');
        $this->log_activity('billing', 'hold_bill', 'Held bill for ' . $data['customer_name'], $id, null);
        echo json_encode([
            'success'      => true,
            'id'           => $id,
            'count'        => $count,
            'hold_number'  => 'H-' . str_pad($id, 4, '0', STR_PAD_LEFT),
            'hold_date'    => date('d/m/Y h:i A'),
            'held_by'      => $this->session->userdata('name') ?: 'Staff',
        ]);
    }

    public function get_held_bills_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');

        $loc = $this->active_location_id();
        $this->db->select('id, customer_name, item_count, total_amount, created_at');
        if ($loc) {
            $this->db->where('location_id', $loc);
        }
        $rows = $this->db->order_by('id', 'DESC')
            ->get('held_bills')->result();

        echo json_encode(['success' => true, 'bills' => $rows]);
    }

    public function recall_bill_ajax($id = null) {
        if (!$id || !$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');

        $loc = $this->active_location_id();
        $this->db->where('id', (int)$id);
        if ($loc) {
            $this->db->where('location_id', $loc);
        }
        $row = $this->db->get('held_bills')->row();
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Held bill not found']);
            return;
        }
        // Recalling consumes it — same "pick it up, it's off the hold list" behavior
        // as a real POS. If the cashier needs it again they'd have to re-hold it.
        $this->db->where('id', $row->id)->delete('held_bills');
        $this->log_activity('billing', 'recall_bill', 'Recalled held bill for ' . $row->customer_name, $row->id, null);

        echo json_encode(['success' => true, 'payload' => $row->payload]);
    }

    // Direct Silent Print via Powershell
    public function get_recent_bills_ajax() {
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }
        
        $date = $this->input->get('date', TRUE) ?: date('Y-m-d');
        
        $this->db->select('invoices.id, invoices.invoice_no as bill_no, invoices.invoice_date as bill_date, invoices.total_amount as grand_total, customers.name as customer_name, SUM(invoice_items.qty) as total_qty');
        $this->db->from('invoices');
        $this->db->join('customers', 'customers.id = invoices.customer_id', 'left');
        $this->db->join('invoice_items', 'invoice_items.invoice_id = invoices.id', 'left');
        $this->db->where('DATE(invoices.invoice_date)', $date);
        $this->db->group_by('invoices.id');
        $this->db->order_by('invoices.invoice_date', 'DESC');
        $this->db->order_by('invoices.invoice_no', 'DESC');
        
        $query = $this->db->get();
        $bills = $query->result_array();
        
        echo json_encode(['status' => 'success', 'bills' => $bills]);
    }

    public function silent_print($id = null) {
        if (!$id || !$this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $invoice = $this->Invoice_model->get_by_id($id);
        $items = $this->Invoice_model->get_items($id);
        $company = $this->Invoice_model->get_company_settings();
        
        if (!$invoice) {
            echo json_encode(['success' => false, 'message' => 'Invoice not found']);
            return;
        }

        $printer_name = $company['default_printer'] ?? '';
        if (empty($printer_name)) {
            echo json_encode(['success' => false, 'message' => 'Default printer not configured in settings.']);
            return;
        }

        // Build Plain Text Receipt
        $text = str_pad($company['company_name'] ?? 'STORE', 40, " ", STR_PAD_BOTH) . "\r\n";
        $text .= str_pad("Invoice No: " . $invoice->invoice_no, 40, " ", STR_PAD_RIGHT) . "\r\n";
        $text .= str_pad("Date: " . date('Y-m-d H:i', strtotime($invoice->created_at)), 40, " ", STR_PAD_RIGHT) . "\r\n";
        $text .= str_repeat("-", 40) . "\r\n";
        $text .= str_pad("Item", 20) . str_pad("Qty", 5) . str_pad("Rate", 7) . str_pad("Total", 8, " ", STR_PAD_LEFT) . "\r\n";
        $text .= str_repeat("-", 40) . "\r\n";
        
        foreach ($items as $item) {
            $name = substr($item->product_name, 0, 19);
            $qty = $item->qty;
            $rate = number_format($item->rate, 2);
            $total = number_format($item->total, 2);
            $text .= str_pad($name, 20) . str_pad($qty, 5) . str_pad($rate, 7) . str_pad($total, 8, " ", STR_PAD_LEFT) . "\r\n";
        }
        
        $text .= str_repeat("-", 40) . "\r\n";
        $text .= str_pad("Subtotal:", 30) . str_pad(number_format($invoice->subtotal, 2), 10, " ", STR_PAD_LEFT) . "\r\n";
        if ($invoice->discount > 0) {
            $text .= str_pad("Discount:", 30) . str_pad(number_format($invoice->discount, 2), 10, " ", STR_PAD_LEFT) . "\r\n";
        }
        $text .= str_repeat("-", 40) . "\r\n";
        $text .= str_pad("Grand Total:", 30) . str_pad(number_format($invoice->grand_total, 2), 10, " ", STR_PAD_LEFT) . "\r\n";
        $text .= str_repeat("-", 40) . "\r\n";
        $text .= "Payment Mode: " . strtoupper($invoice->payment_mode) . "\r\n";
        $text .= str_repeat("=", 40) . "\r\n";
        $text .= str_pad("Thank you! Visit again!", 40, " ", STR_PAD_BOTH) . "\r\n\r\n\r\n\r\n"; // Feed lines

        $filepath = FCPATH . 'assets/temp_print_' . $id . '.txt';
        file_put_contents($filepath, $text);

        // Escape printer name
        $printer_name_esc = str_replace("'", "''", $printer_name);
        
        // Execute powershell print
        $cmd = 'powershell -Command "Get-Content \'' . $filepath . '\' | Out-Printer -Name \'' . $printer_name_esc . '\'"';
        exec($cmd, $output, $return_var);

        if ($return_var === 0) {
            $this->db->insert('audit_print_logs', [
                'invoice_id' => $id,
                'invoice_no' => $invoice->invoice_no,
                'print_date' => date('Y-m-d H:i:s'),
                'user_id' => $this->session->userdata('user_id'),
                'printer_name' => $printer_name,
                'status' => 'success'
            ]);
            @unlink($filepath);
            echo json_encode(['success' => true]);
        } else {
            $this->db->insert('audit_print_logs', [
                'invoice_id' => $id,
                'invoice_no' => $invoice->invoice_no,
                'print_date' => date('Y-m-d H:i:s'),
                'user_id' => $this->session->userdata('user_id'),
                'printer_name' => $printer_name,
                'status' => 'failed',
                'error_msg' => implode("\n", $output)
            ]);
            echo json_encode(['success' => false, 'message' => 'Printer error. Check connection.']);
        }
    }

    private function _get_payment_modes() {
        if (!$this->db->table_exists('payment_modes')) {
            return $this->_default_payment_modes();
        }
        $this->_ensure_payment_mode_varchar();
        $modes = $this->db->where('status', 1)->order_by('sort_order')->get('payment_modes')->result();
        return !empty($modes) ? $modes : $this->_default_payment_modes();
    }

    private function _ensure_payment_mode_varchar() {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        $col = $this->db->query("SHOW COLUMNS FROM invoices LIKE 'payment_mode'")->row();
        if ($col && stripos($col->Type, 'enum') === 0) {
            $this->db->query("ALTER TABLE invoices MODIFY COLUMN payment_mode VARCHAR(30) NOT NULL DEFAULT 'cash'");
        }
    }

    private function _default_payment_modes() {
        return array_map(function($m) {
            return (object) $m;
        }, [
            ['code'=>'cash','name'=>'Cash','icon'=>'fas fa-money-bill','is_cash_type'=>1,'show_in_quick'=>1,'show_in_other'=>0,'show_in_split'=>1,'needs_ref'=>0,'is_credit'=>0],
            ['code'=>'card','name'=>'Card','icon'=>'fas fa-credit-card','is_cash_type'=>0,'show_in_quick'=>1,'show_in_other'=>0,'show_in_split'=>1,'needs_ref'=>1,'is_credit'=>0],
            ['code'=>'upi','name'=>'UPI','icon'=>'fas fa-qrcode','is_cash_type'=>0,'show_in_quick'=>1,'show_in_other'=>0,'show_in_split'=>1,'needs_ref'=>0,'is_credit'=>0],
            ['code'=>'neft','name'=>'NEFT/Bank','icon'=>'fas fa-university','is_cash_type'=>0,'show_in_quick'=>0,'show_in_other'=>1,'show_in_split'=>1,'needs_ref'=>0,'is_credit'=>0],
            ['code'=>'cheque','name'=>'Cheque','icon'=>'fas fa-money-check','is_cash_type'=>0,'show_in_quick'=>0,'show_in_other'=>1,'show_in_split'=>1,'needs_ref'=>0,'is_credit'=>0],
            ['code'=>'credit','name'=>'Credit','icon'=>'fas fa-file-invoice','is_cash_type'=>0,'show_in_quick'=>0,'show_in_other'=>1,'show_in_split'=>1,'needs_ref'=>0,'is_credit'=>1],
        ]);
    }

}
