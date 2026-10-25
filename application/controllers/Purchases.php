<?php
// ============================================================
// FILE: application/controllers/Purchases.php
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchases extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Purchase_model', 'Product_model', 'Supplier_model', 'Hsn_model']);
        $this->load->library('form_validation');
    }

    // List all purchases
    public function index() {
        $data['page_title'] = 'Purchases';
        
        // Advanced Search Filters
        $filters = [
            'supplier_id'   => $this->input->get('supplier_id', TRUE),
            'supplier_code' => $this->input->get('supplier_code', TRUE),
            'invoice_no'    => $this->input->get('invoice_no', TRUE),
            'invoice_date'  => $this->input->get('invoice_date', TRUE),
            'from_date'     => $this->input->get('from_date', TRUE),
            'to_date'       => $this->input->get('to_date', TRUE),
        ];
        
        $data['filters']   = $filters;
        $data['suppliers'] = $this->Supplier_model->get_all_for_select();
        $data['purchases'] = $this->Purchase_model->get_all_with_supplier($filters);
        $this->render('purchases/index', $data);
    }

    // Create purchase order
    const PRODUCTS_PAGE_LIMIT = 2000;

    public function create() {
        $data['page_title'] = 'New Purchase';
        $data['suppliers']  = $this->Supplier_model->get_all_for_select();
        $data['full_suppliers'] = $this->Supplier_model->get_all();
        $data['products']   = $this->Product_model->get_all(null, self::PRODUCTS_PAGE_LIMIT);
        $data['hsn_options']= $this->Hsn_model->get_for_select();
        $data['hsn_master_list'] = $this->db->get('hsn_master')->result_array();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('purchases/create', $data);
            return;
        }

        // Validate header
        $this->form_validation->set_rules('supplier_id',  'Supplier',       'required|integer');
        $this->form_validation->set_rules('invoice_no',   'Invoice No',     'trim|max_length[50]');
        $this->form_validation->set_rules('invoice_date', 'Invoice Date',   'required');
        $this->form_validation->set_rules('payment_mode', 'Payment Mode',   'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors(' | '));
            $this->render('purchases/create', $data);
            return;
        }

        // Collect items from POST arrays
        $product_ids = $this->input->post('product_id');
        $qtys        = $this->input->post('qty');
        $prices      = $this->input->post('price');
        $gst_pcts    = $this->input->post('gst_percent');
        
        $disc_vals   = $this->input->post('disc_val');
        $disc_types  = $this->input->post('disc_type');
        
        $mrps         = $this->input->post('mrp');
        $sps         = $this->input->post('selling_price');
        $variant_ids  = $this->input->post('variant_id');
        $variant_skus = $this->input->post('variant_sku');
        $size_names   = $this->input->post('size_name');
        $colour_names = $this->input->post('colour_name');
        // JSON array of serials per row, emitted by the capture modal. Parallel
        // to product_id[]; empty for every non-serialized item.
        $serial_json = $this->input->post('serial_json');

        if (empty($product_ids) || !is_array($product_ids)) {
            $this->session->set_flashdata('error', 'Add at least one product.');
            $this->render('purchases/create', $data);
            return;
        }

        // Calculate totals with authoritative GST Tax Engine
        $this->load->library('Gst_tax_engine');
        $supplier_id    = (int)$this->input->post('supplier_id', TRUE);
        $supplier       = $this->Supplier_model->get_by_id($supplier_id);
        $active_loc     = $this->active_location_id() ?: 1;
        $is_interstate  = $this->gst_tax_engine->is_interstate($supplier, $active_loc);
        $invoice_date   = $this->input->post('invoice_date', TRUE) ?: date('Y-m-d');

        $items          = [];
        $subtotal_basic = 0;
        $total_taxable  = 0;
        $total_gst      = 0;
        $total_cgst     = 0;
        $total_sgst     = 0;
        $total_igst     = 0;
        $total_itc_el   = 0;

        foreach ($product_ids as $k => $pid) {
            if (empty($pid) || empty($qtys[$k])) continue;
            $qty      = (int)$qtys[$k];
            $price    = (float)$prices[$k];
            $gst_pct  = (float)$gst_pcts[$k];
            
            $basic_amt = $qty * $price;
            
            $d_val = isset($disc_vals[$k]) ? (float)$disc_vals[$k] : 0;
            $d_type = isset($disc_types[$k]) ? $disc_types[$k] : '₹';
            
            if ($d_type === '%') {
                $d_amt = ($basic_amt * $d_val) / 100;
                $d_pct = $d_val;
            } else {
                $d_amt = $d_val;
                $d_pct = $basic_amt > 0 ? ($d_amt / $basic_amt) * 100 : 0;
            }
            if ($d_amt > $basic_amt) $d_amt = $basic_amt;

            $product = $this->Product_model->get_by_id($pid);
            
            // Purchase tax calculated independently of sales tax
            $pur_calc = $this->gst_tax_engine->calculate_purchase_line([
                'product'           => $product,
                'price'             => $price,
                'qty'               => $qty,
                'discount'          => $d_amt,
                'supplier_gst_rate' => $gst_pct,
                'is_tax_inclusive'  => false,
                'is_interstate'     => $is_interstate,
                'itc_eligibility'   => 'eligible',
                'transaction_date'  => $invoice_date,
            ]);

            $taxable = $pur_calc['taxable_amount'];
            $gst_amt = $pur_calc['gst_amount'];
            $total   = $pur_calc['total_amount'];
            
            $subtotal_basic += $basic_amt;
            $total_taxable  += $taxable;
            $total_gst      += $gst_amt;
            $total_cgst     += $pur_calc['cgst_amount'];
            $total_sgst     += $pur_calc['sgst_amount'];
            $total_igst     += $pur_calc['igst_amount'];
            $total_itc_el   += $pur_calc['itc_amount'];

            $items[] = [
                'product_id'       => $pid,
                'variant_id'       => !empty($variant_ids[$k]) ? (int)$variant_ids[$k] : null,
                'variant_sku'      => !empty($variant_skus[$k]) ? trim($variant_skus[$k]) : null,
                'size_name'        => !empty($size_names[$k]) ? trim($size_names[$k]) : null,
                'colour_name'      => !empty($colour_names[$k]) ? trim($colour_names[$k]) : null,
                'qty'              => $qty,
                'unit'             => $product->unit ?? 'PCS',
                'price'            => $price,
                'basic_amount'     => $basic_amt,
                'discount_percent' => $d_pct,
                'discount_amount'  => $d_amt,
                'taxable_amount'   => $taxable,
                'gst_percent'      => $pur_calc['gst_percent'],
                'gst_amount'       => $gst_amt,
                'cgst_percent'     => $pur_calc['cgst_percent'],
                'sgst_percent'     => $pur_calc['sgst_percent'],
                'igst_percent'     => $pur_calc['igst_percent'],
                'cgst_amount'      => $pur_calc['cgst_amount'],
                'sgst_amount'      => $pur_calc['sgst_amount'],
                'igst_amount'      => $pur_calc['igst_amount'],
                'itc_eligibility'  => $pur_calc['itc_eligibility'],
                'itc_amount'       => $pur_calc['itc_amount'],
                'tax_inclusive'    => 0,
                'tax_rule_applied' => $pur_calc['tax_rule_applied'],
                'total'            => $total,
                'mrp'              => isset($mrps[$k]) ? (float)$mrps[$k] : 0,
                'selling_price'    => isset($sps[$k]) ? (float)$sps[$k] : 0,
                // Raw capture-modal output; parsed and validated below, never trusted as-is.
                '_serials_json'    => is_array($serial_json) ? ($serial_json[$k] ?? '') : '',
                '_product_name'    => $product->name ?? '',
            ];
        }

        if (empty($items)) {
            $this->session->set_flashdata('error', 'No valid products in purchase.');
            $this->render('purchases/create', $data);
            return;
        }

        // ── Serial number validation (server-side, authoritative) ───────────
        // Runs before anything is written: a purchase must never be half-saved
        // with missing serials, and the browser checks can be bypassed.
        $serial_plan = [];
        $serial_errors = [];
        $this->load->model('Serial_model');
        if ($this->Serial_model->schema_ready()) {
            $smap = $this->Serial_model->serialized_map(array_column($items, 'product_id'));
            $batch_seen = [];
            foreach ($items as $idx => $it) {
                $prod = $smap[(int)$it['product_id']] ?? null;
                if (!$prod || (int)$prod->maintain_serial !== 1) continue;

                $list = json_decode((string)$it['_serials_json'], true);
                if (!is_array($list)) $list = [];

                $res = $this->Serial_model->validate_inbound(
                    $it['product_id'], $it['qty'], $list, $prod->name
                );
                if (!$res['ok']) {
                    $serial_errors = array_merge($serial_errors, $res['errors']);
                    continue;
                }
                // A serial repeated across two lines of the same purchase would
                // pass each line's own check but still collide on insert.
                foreach ($res['serials'] as $sn) {
                    if (isset($batch_seen[$sn])) {
                        $serial_errors[] = 'Duplicate Serial Number. "' . $sn
                                         . '" appears on more than one line of this purchase.';
                    }
                    $batch_seen[$sn] = true;
                }
                $serial_plan[$idx] = $res['serials'];
            }
        }

        if ($serial_errors) {
            $this->session->set_flashdata('error',
                'Serial number problems:<br>• ' . implode('<br>• ', array_slice($serial_errors, 0, 12)));
            $this->render('purchases/create', $data);
            return;
        }

        $bill_discount = (float)$this->input->post('discount', TRUE) ?: 0;
        $paid_amount   = (float)$this->input->post('paid_amount', TRUE) ?: 0;
        $total_amount  = $total_taxable + $total_gst - $bill_discount;
        $due_amount    = $total_amount - $paid_amount;

        $supplier_id_in = $this->input->post('supplier_id', TRUE);
        $invoice_no_in  = $this->input->post('invoice_no',  TRUE);
        if (trim((string)$invoice_no_in) !== '') {
            $dupe = $this->db->where('supplier_id', $supplier_id_in)
                              ->where('invoice_no', $invoice_no_in)
                              ->get('purchases')->row();
            if ($dupe) {
                $this->session->set_flashdata('error', 'A purchase with invoice number "' . $invoice_no_in . '" already exists for this supplier (Purchase #' . $dupe->purchase_no . ').');
                $this->render('purchases/create', $data);
                return;
            }
        }

        $purchase_no = $this->Purchase_model->generate_purchase_no();

        $purchase_data = [
            'purchase_no'           => $purchase_no,
            'supplier_id'           => $supplier_id_in,
            'invoice_no'            => $invoice_no_in,
            'invoice_date'          => $this->input->post('invoice_date', TRUE),
            'supplier_invoice_no'   => $invoice_no_in,
            'supplier_invoice_date' => $this->input->post('invoice_date', TRUE),
            'subtotal'              => $subtotal_basic,
            'taxable_amount'        => $total_taxable,
            'total_gst'             => $total_gst,
            'cgst_amount'           => $total_cgst,
            'sgst_amount'           => $total_sgst,
            'igst_amount'           => $total_igst,
            'is_interstate'         => $is_interstate ? 1 : 0,
            'total_itc_eligible'    => $total_itc_el,
            'total_itc_ineligible'  => 0.00,
            'discount'              => $bill_discount,
            'total_amount'          => $total_amount,
            'paid_amount'           => $paid_amount,
            'due_amount'            => $due_amount,
            'payment_mode'          => $this->input->post('payment_mode', TRUE),
            'notes'                 => $this->input->post('notes',        TRUE),
            'status'                => $this->input->post('action') === 'draft' ? 'draft' : ($due_amount <= 0 ? 'received' : 'partial'),
            'created_by'            => $this->session->userdata('user_id'),
            'location_id'           => $this->active_location_id() ?: 1,
        ];

        // Save in transaction
        $this->load->model('Location_model');
        $purch_location_id = $this->active_location_id() ?: 1;
        // The _-prefixed keys are controller-side scratch (serial payload, name)
        // and are not columns on purchase_items — strip before the insert.
        $db_items = [];
        foreach ($items as $it) {
            $db_items[] = array_filter($it, function ($k) { return $k[0] !== '_'; }, ARRAY_FILTER_USE_KEY);
        }

        $this->db->trans_start();
        $purchase_id = $this->Purchase_model->create($purchase_data, $db_items);
        $is_draft = $this->input->post('action') === 'draft';
        if (!$is_draft) {
            // Update stock and cost price for each item
            $this->load->model('Garment_model');
            foreach ($items as $item) {
                // Calculate Net Cost Price per unit:
                // When ITC is eligible, recoverable GST is claimed as tax credit, so cost basis is taxable amount.
                // When ITC is ineligible, total amount (including tax) becomes the landed inventory cost.
                $is_itc_el = ($item['itc_eligibility'] ?? 'eligible') === 'eligible';
                $cost_basis = $is_itc_el ? $item['taxable_amount'] : $item['total'];
                $new_cp = $item['qty'] > 0 ? round($cost_basis / $item['qty'], 2) : 0;
                
                // Update Product Master
                if ($new_cp > 0) {
                    $update_data = [
                        'basic_cost' => $item['price'],
                        'cost_price' => $new_cp
                    ];
                    if (isset($item['mrp']) && $item['mrp'] > 0) {
                        $update_data['mrp'] = $item['mrp'];
                    }
                    if (isset($item['selling_price']) && $item['selling_price'] > 0) {
                        $update_data['selling_price'] = $item['selling_price'];
                    }
                    $this->db->where('id', $item['product_id'])->update('products', $update_data);

                    if (!empty($item['variant_id'])) {
                        $v_update = [
                            'purchase_price' => $item['price'],
                            'cost_price'     => $new_cp,
                        ];
                        if (isset($item['mrp']) && $item['mrp'] > 0) $v_update['mrp'] = $item['mrp'];
                        if (isset($item['selling_price']) && $item['selling_price'] > 0) $v_update['selling_price'] = $item['selling_price'];
                        $this->db->where('id', $item['variant_id'])->update('garment_variants', $v_update);
                    }
                }

                if (!empty($item['variant_id'])) {
                    $this->Garment_model->increase_variant_stock($item['variant_id'], $item['product_id'], $purch_location_id, $item['qty']);
                } else {
                    $this->Product_model->increase_stock($item['product_id'], $item['qty']);
                    $this->Location_model->increase_stock($item['product_id'], $purch_location_id, $item['qty']);
                }

                $before = $this->Location_model->get_stock($item['product_id'], $purch_location_id);
                $after  = $this->Location_model->get_stock($item['product_id'], $purch_location_id);
                $this->Location_model->log_movement([
                    'product_id'     => $item['product_id'],
                    'variant_id'     => $item['variant_id'] ?? null,
                    'variant_sku'    => $item['variant_sku'] ?? null,
                    'size_name'      => $item['size_name'] ?? null,
                    'colour_name'    => $item['colour_name'] ?? null,
                    'product_name'   => $item['_product_name'] ?? '',
                    'location_id'    => $purch_location_id,
                    'location_name'  => $this->active_location_name(),
                    'movement_type'  => 'purchase_in',
                    'quantity'       => $item['qty'],
                    'balance_before' => $before,
                    'balance_after'  => $after,
                    'reference_type' => 'purchase',
                    'reference_id'   => $purchase_id,
                    'reference_no'   => $purchase_no,
                    'user_id'        => $this->session->userdata('user_id'),
                    'user_name'      => $this->session->userdata('name'),
                ]);
            }

            // Serial rows are created only for a confirmed purchase — a draft
            // has not brought stock in yet, so its serials do not exist either.
            // Inside the same transaction: if this fails, the purchase rolls back
            // rather than leaving stock without its serials.
            foreach ($serial_plan as $idx => $serials) {
                $it = $items[$idx];
                $this->Serial_model->create_inbound($serials, [
                    'product_id'    => $it['product_id'],
                    'purchase_id'   => $purchase_id,
                    'supplier_id'   => $purchase_data['supplier_id'],
                    'purchase_date' => $purchase_data['invoice_date'] ?: date('Y-m-d'),
                    'location_id'   => $purch_location_id,
                    'mrp'           => $it['mrp'] ?: null,
                    'rate'          => $it['price'],
                    'txn_type'      => 'PURCHASED',
                    'reference_id'  => $purchase_id,
                    'reference_no'  => $purchase_no,
                ]);
            }
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error. Purchase not saved.');
            redirect('purchases/create');
        }

        $serial_count = array_sum(array_map('count', $serial_plan));
        if ($serial_count) {
            $this->log_activity('purchases', 'serials_captured',
                $serial_count . ' serial number(s) recorded on purchase ' . $purchase_no,
                $purchase_id, $purchase_no);
        }

        // Only clear the auto-save draft once the real purchase has actually
        // committed — on failure it stays 'in_progress' so nothing is lost.
        $draft_id = (int)$this->input->post('draft_id', TRUE);
        if ($draft_id) {
            $this->load->model('Entry_draft_model');
            $this->Entry_draft_model->mark_completed($draft_id, $purchase_id);
        }

        $this->session->set_flashdata('success', $is_draft
            ? 'Purchase #' . $purchase_no . ' saved as draft. Stock has not been updated yet.'
            : 'Purchase #' . $purchase_no . ' recorded. Stock updated.');
        redirect('purchases/view/' . $purchase_id);
    }

    // View single purchase
    public function view($id = null) {
        if (!$id) redirect('purchases');
        $data['page_title'] = 'Purchase Details';
        $data['purchase']   = $this->Purchase_model->get_by_id($id);
        $data['items']      = $this->Purchase_model->get_items($id);
        if (!$data['purchase']) { $this->session->set_flashdata('error','Not found.'); redirect('purchases'); }
        $this->render('purchases/view', $data);
    }

    // B2B pass-through billing: bill a whole finalized purchase straight out to a
    // customer at basic cost + markup%. Same feature/pattern as Grn::convert_to_invoice(),
    // built here too since this business's real purchase entries live in Purchases,
    // not GRN. Stashes items in session and hands off to billing/create.
    public function convert_to_invoice($id = null) {
        if (!$id) redirect('purchases');
        $purchase = $this->Purchase_model->get_by_id($id);
        if (!$purchase) redirect('purchases');

        if ($purchase->status === 'draft') {
            $this->session->set_flashdata('error', 'A draft purchase has no stock posted yet — finalize it before converting to a sales invoice.');
            redirect("purchases/view/$id");
        }
        if (!empty($purchase->converted_invoice_id)) {
            $this->session->set_flashdata('error', 'This purchase was already converted to invoice #' . $purchase->converted_invoice_id . '.');
            redirect("purchases/view/$id");
        }
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect("purchases/view/$id");
        }

        $markup = $this->input->post('markup_percent', TRUE);
        if (!is_numeric($markup) || (float)$markup < 0) {
            $this->session->set_flashdata('error', 'Enter a valid markup percentage (0 or more).');
            redirect("purchases/view/$id");
        }
        $markup = (float)$markup;

        $items = $this->Purchase_model->get_items($id);
        if (empty($items)) {
            $this->session->set_flashdata('error', 'This purchase has no items to bill.');
            redirect("purchases/view/$id");
        }

        $this->load->library('Gst_tax_engine');
        $bill_items = [];
        foreach ($items as $it) {
            // Calculate selling value at basic cost + markup%
            $basic_with_markup = (float)$it->price * (1 + $markup / 100);

            // Determine sales GST rate independently based on the sales transaction value
            // NEVER blindly copy purchase GST rate to sales invoice
            $prod_obj = $this->Product_model->get_by_id($it->product_id);
            $sales_calc = $this->gst_tax_engine->calculate_sales_line([
                'product'          => $prod_obj,
                'unit_price'       => $basic_with_markup,
                'qty'              => 1,
                'discount'         => 0,
                'is_tax_inclusive' => false,
                'is_interstate'    => false,
            ]);
            $sales_gst_pct = $sales_calc['gst_percent'];
            $sell_rate = round($basic_with_markup * (1 + $sales_gst_pct / 100), 2);

            $bill_items[] = [
                'product_id'   => $it->product_id,
                'product_name' => $it->product_name,
                'qty'          => (float)$it->qty,
                'unit'         => $it->unit ?? 'PCS',
                'hsn_code'     => $it->hsn_code,
                'gst_percent'  => $sales_gst_pct,
                'sell_rate'    => $sell_rate,
            ];
        }

        $this->session->set_userdata('convert_purchase', [
            'purchase_id'    => $id,
            'purchase_no'    => $purchase->purchase_no,
            'supplier_name'  => $purchase->supplier_name,
            'markup_percent' => $markup,
            'items'          => $bill_items,
        ]);

        redirect('billing/create');
    }

    // Edit purchase
    public function edit($id = null) {
        if (!$id) redirect('purchases');
        $purchase = $this->Purchase_model->get_by_id($id);
        if (!$purchase) { $this->session->set_flashdata('error','Purchase not found.'); redirect('purchases'); }
        
        if ($purchase->status !== 'draft') {
            $this->session->set_flashdata('error', 'Purchase has already been saved and stock updated. Editing is not allowed.');
            redirect('purchases/view/' . $id);
        }

        $data['page_title'] = 'Edit Purchase — ' . $purchase->purchase_no;
        $data['purchase']   = $purchase;
        $data['items']      = $this->Purchase_model->get_items($id);
        $data['suppliers']  = $this->Supplier_model->get_all_for_select();
        $data['full_suppliers'] = $this->Supplier_model->get_all();
        $data['products']   = $this->Product_model->get_all(null, self::PRODUCTS_PAGE_LIMIT);
        $existing_pids = array_map(fn($it) => (int)$it->product_id, $data['items']);
        $loaded_pids   = array_map(fn($p) => (int)$p->id, $data['products']);
        $missing = array_diff($existing_pids, $loaded_pids);
        if ($missing) {
            $data['products'] = array_merge($data['products'], $this->Product_model->get_by_ids($missing));
        }
        $data['hsn_options']= $this->Hsn_model->get_for_select();
        $data['hsn_master_list'] = $this->db->get('hsn_master')->result_array();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('purchases/edit', $data);
            return;
        }

        $this->form_validation->set_rules('supplier_id',  'Supplier',     'required|integer');
        $this->form_validation->set_rules('invoice_date', 'Invoice Date', 'required');
        $this->form_validation->set_rules('payment_mode', 'Payment Mode', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors(' | '));
            $this->render('purchases/edit', $data);
            return;
        }

        $product_ids = $this->input->post('product_id');
        $qtys        = $this->input->post('qty');
        $prices      = $this->input->post('price');
        $gst_pcts    = $this->input->post('gst_percent');
        $disc_vals   = $this->input->post('disc_val');
        $disc_types  = $this->input->post('disc_type');
        $mrps        = $this->input->post('mrp');
        $sps         = $this->input->post('selling_price');

        if (empty($product_ids) || !is_array($product_ids)) {
            $this->session->set_flashdata('error', 'Add at least one product.');
            $this->render('purchases/edit', $data);
            return;
        }

        $this->load->library('Gst_tax_engine');
        $supplier_id_in = (int)$this->input->post('supplier_id', TRUE);
        $supplier       = $this->Supplier_model->get_by_id($supplier_id_in);
        $edit_loc_id    = $purchase->location_id ?: ($this->active_location_id() ?: 1);
        $is_interstate  = $this->gst_tax_engine->is_interstate($supplier, $edit_loc_id);
        $invoice_date_in = $this->input->post('invoice_date', TRUE) ?: date('Y-m-d');

        $items = [];
        $subtotal_basic = 0;
        $total_taxable  = 0;
        $total_gst      = 0;
        $total_cgst     = 0;
        $total_sgst     = 0;
        $total_igst     = 0;
        $total_itc_el   = 0;

        foreach ($product_ids as $k => $pid) {
            if (empty($pid) || empty($qtys[$k])) continue;
            $qty     = (int)$qtys[$k];
            $price   = (float)$prices[$k];
            $gst_pct = (float)$gst_pcts[$k];
            $basic   = $qty * $price;

            $d_val  = isset($disc_vals[$k])  ? (float)$disc_vals[$k]  : 0;
            $d_type = isset($disc_types[$k]) ? $disc_types[$k]         : '%';
            if ($d_type === '%') {
                $d_amt = ($basic * $d_val) / 100;
                $d_pct = $d_val;
            } else {
                $d_amt = $d_val;
                $d_pct = $basic > 0 ? ($d_amt / $basic) * 100 : 0;
            }
            if ($d_amt > $basic) $d_amt = $basic;

            $product = $this->Product_model->get_by_id($pid);
            $pur_calc = $this->gst_tax_engine->calculate_purchase_line([
                'product'           => $product,
                'price'             => $price,
                'qty'               => $qty,
                'discount'          => $d_amt,
                'supplier_gst_rate' => $gst_pct,
                'is_tax_inclusive'  => false,
                'is_interstate'     => $is_interstate,
                'itc_eligibility'   => 'eligible',
                'transaction_date'  => $invoice_date_in,
            ]);

            $taxable = $pur_calc['taxable_amount'];
            $gst_amt = $pur_calc['gst_amount'];
            $total   = $pur_calc['total_amount'];

            $subtotal_basic += $basic;
            $total_taxable  += $taxable;
            $total_gst      += $gst_amt;
            $total_cgst     += $pur_calc['cgst_amount'];
            $total_sgst     += $pur_calc['sgst_amount'];
            $total_igst     += $pur_calc['igst_amount'];
            $total_itc_el   += $pur_calc['itc_amount'];

            $items[] = [
                'product_id'       => $pid,
                'qty'              => $qty,
                'unit'             => $product->unit ?? 'PCS',
                'price'            => $price,
                'basic_amount'     => $basic,
                'discount_percent' => $d_pct,
                'discount_amount'  => $d_amt,
                'taxable_amount'   => $taxable,
                'gst_percent'      => $pur_calc['gst_percent'],
                'gst_amount'       => $gst_amt,
                'cgst_percent'     => $pur_calc['cgst_percent'],
                'sgst_percent'     => $pur_calc['sgst_percent'],
                'igst_percent'     => $pur_calc['igst_percent'],
                'cgst_amount'      => $pur_calc['cgst_amount'],
                'sgst_amount'      => $pur_calc['sgst_amount'],
                'igst_amount'      => $pur_calc['igst_amount'],
                'itc_eligibility'  => $pur_calc['itc_eligibility'],
                'itc_amount'       => $pur_calc['itc_amount'],
                'tax_inclusive'    => 0,
                'tax_rule_applied' => $pur_calc['tax_rule_applied'],
                'total'            => $total,
                'mrp'              => isset($mrps[$k]) ? (float)$mrps[$k] : 0,
                'selling_price'    => isset($sps[$k]) ? (float)$sps[$k] : 0,
            ];
        }

        if (empty($items)) {
            $this->session->set_flashdata('error', 'No valid products.');
            $this->render('purchases/edit', $data);
            return;
        }

        $bill_discount = (float)$this->input->post('discount', TRUE) ?: 0;
        $paid_amount   = (float)$this->input->post('paid_amount', TRUE) ?: 0;
        $total_amount  = $total_taxable + $total_gst - $bill_discount;
        $due_amount    = $total_amount - $paid_amount;

        $invoice_no_in = $this->input->post('invoice_no', TRUE);
        if (trim((string)$invoice_no_in) !== '') {
            $dupe = $this->db->where('supplier_id', $this->input->post('supplier_id', TRUE))
                              ->where('invoice_no', $invoice_no_in)
                              ->where('id !=', $id)
                              ->get('purchases')->row();
            if ($dupe) {
                $this->session->set_flashdata('error', 'A purchase with invoice number "' . $invoice_no_in . '" already exists for this supplier (Purchase #' . $dupe->purchase_no . ').');
                $this->render('purchases/edit', $data);
                return;
            }
        }

        // Note: this method only runs while status is still 'draft' (guarded above),
        // so the previous item set never had stock applied — nothing to reverse here.
        $is_draft = $this->input->post('action') === 'draft';
        $purchase_data = [
            'supplier_id'           => $supplier_id_in,
            'invoice_no'            => $invoice_no_in,
            'invoice_date'          => $invoice_date_in,
            'supplier_invoice_no'   => $invoice_no_in,
            'supplier_invoice_date' => $invoice_date_in,
            'subtotal'              => $subtotal_basic,
            'taxable_amount'        => $total_taxable,
            'total_gst'             => $total_gst,
            'cgst_amount'           => $total_cgst,
            'sgst_amount'           => $total_sgst,
            'igst_amount'           => $total_igst,
            'is_interstate'         => $is_interstate ? 1 : 0,
            'total_itc_eligible'    => $total_itc_el,
            'total_itc_ineligible'  => 0.00,
            'discount'              => $bill_discount,
            'total_amount'          => $total_amount,
            'paid_amount'           => $paid_amount,
            'due_amount'            => $due_amount,
            'payment_mode'          => $this->input->post('payment_mode', TRUE),
            'notes'                 => $this->input->post('notes', TRUE),
            'status'                => $is_draft ? 'draft' : ($due_amount <= 0 ? 'received' : 'partial'),
        ];

        $this->load->model('Location_model');
        $edit_location_id = $purchase->location_id ?: ($this->active_location_id() ?: 1);
        $this->db->trans_start();
        $this->Purchase_model->update($id, $purchase_data, $items);
        if (!$is_draft) {
            foreach ($items as $item) {
                // Calculate Net Cost Price per unit (includes taxes & discounts)
                $new_cp = $item['qty'] > 0 ? round($item['total'] / $item['qty'], 2) : 0;
                
                // Update Product Master
                if ($new_cp > 0) {
                    $update_data = [
                        'basic_cost' => $item['price'],
                        'cost_price' => $new_cp
                    ];
                    if (isset($item['mrp']) && $item['mrp'] > 0) {
                        $update_data['mrp'] = $item['mrp'];
                    }
                    if (isset($item['selling_price']) && $item['selling_price'] > 0) {
                        $update_data['selling_price'] = $item['selling_price'];
                    }
                    $this->db->where('id', $item['product_id'])->update('products', $update_data);
                }

                $this->Product_model->increase_stock($item['product_id'], $item['qty']);
                $before = $this->Location_model->get_stock($item['product_id'], $edit_location_id);
                $this->Location_model->increase_stock($item['product_id'], $edit_location_id, $item['qty']);
                $after  = $this->Location_model->get_stock($item['product_id'], $edit_location_id);
                $this->Location_model->log_movement([
                    'product_id'     => $item['product_id'],
                    'product_name'   => $item['product_name'] ?? '',
                    'location_id'    => $edit_location_id,
                    'location_name'  => $this->active_location_name(),
                    'movement_type'  => 'purchase_in',
                    'quantity'       => $item['qty'],
                    'balance_before' => $before,
                    'balance_after'  => $after,
                    'reference_type' => 'purchase',
                    'reference_id'   => $id,
                    'reference_no'   => $purchase->purchase_no,
                    'user_id'        => $this->session->userdata('user_id'),
                    'user_name'      => $this->session->userdata('name'),
                ]);
            }
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error. Changes not saved.');
            redirect('purchases/edit/' . $id);
        }

        $this->log_activity('purchases', 'edit', ($is_draft ? 'Draft updated: ' : 'Finalized on edit: ') . $purchase->purchase_no, $id);
        $this->session->set_flashdata('success', $is_draft ? 'Draft saved.' : 'Purchase updated and stock added.');
        redirect($is_draft ? ('purchases/edit/' . $id) : ('purchases/view/' . $id));
    }
    // Revert Purchase back to Draft (Admin Only)
    public function revert_to_draft($id = null) {
        if (!$id) redirect('purchases');
        
        // Security check: Only sverpadmin username or superadmin role is allowed to use this
        if ($this->session->userdata('username') !== 'sverpadmin' && $this->session->userdata('role') !== 'superadmin' && $this->session->userdata('role') !== 'admin') {
            $this->session->set_flashdata('error', 'Unauthorized. Only Admins can revert GRNs to draft.');
            redirect('purchases/view/' . $id);
        }

        $purchase = $this->Purchase_model->get_by_id($id);
        if (!$purchase) {
            $this->session->set_flashdata('error', 'Purchase not found.');
            redirect('purchases');
        }

        if ($purchase->status === 'draft') {
            $this->session->set_flashdata('info', 'Purchase is already a draft.');
            redirect('purchases/view/' . $id);
        }

        $items = $this->Purchase_model->get_items($id);
        $this->load->model('Location_model');
        $revert_location_id = $purchase->location_id ?: ($this->active_location_id() ?: 1);

        $this->db->trans_start();

        // 1. Subtract the stock that was added by this GRN
        foreach ($items as $item) {
            // Revert master stock
            $this->Product_model->decrease_stock($item->product_id, $item->qty);
            
            // Revert location stock
            $before = $this->Location_model->get_stock($item->product_id, $revert_location_id);
            $this->Location_model->decrease_stock($item->product_id, $revert_location_id, $item->qty);
            $after  = $this->Location_model->get_stock($item->product_id, $revert_location_id);
            
            // Log the reversal
            $this->Location_model->log_movement([
                'product_id'     => $item->product_id,
                'product_name'   => $item->product_name ?? '',
                'location_id'    => $revert_location_id,
                'location_name'  => $this->active_location_name(),
                'movement_type'  => 'purchase_revert',
                'quantity'       => $item->qty,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_type' => 'purchase',
                'reference_id'   => $id,
                'reference_no'   => $purchase->purchase_no,
                'user_id'        => $this->session->userdata('user_id'),
                'user_name'      => $this->session->userdata('name'),
            ]);
        }

        // 2. Change status back to draft
        $this->db->where('id', $id)->update('purchases', ['status' => 'draft']);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error during revert.');
        } else {
            $this->log_activity('purchases', 'revert_to_draft', 'Admin reverted GRN to draft: ' . $purchase->purchase_no, $id);
            $this->session->set_flashdata('success', 'GRN has been reverted to Draft. Stock has been subtracted. Users can now edit it.');
        }
        
        redirect('purchases/view/' . $id);
    }

    // Purchase Return (PRN)
    public function return_create($purchase_id = null) {
        if (!$purchase_id) redirect('purchases');
        $data['page_title'] = 'Create Purchase Return (PRN)';
        $data['purchase']   = $this->Purchase_model->get_by_id($purchase_id);
        $data['items']      = $this->Purchase_model->get_items($purchase_id);

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('purchases/return_form', $data);
            return;
        }

        $return_qtys = $this->input->post('return_qty');
        $return_items = [];
        $total_return = 0;

        foreach ($data['items'] as $item) {
            $rqty = (int)($return_qtys[$item->id] ?? 0);
            if ($rqty <= 0) continue;
            if ($rqty > $item->qty) $rqty = $item->qty;
            $rtotal = $rqty * $item->price;
            $return_items[] = [
                'product_id'    => $item->product_id,
                'qty'           => $rqty,
                'price'         => $item->price,
                'total'         => $rtotal,
                // post() returns null when the field is absent — indexing that
                // directly emits a warning on PHP 8, so read it into a var first.
                '_serials_json' => (function () use ($item) {
                    $p = $this->input->post('prn_serial_json');
                    return is_array($p) ? ($p[$item->id] ?? '') : '';
                })(),
            ];
            $total_return += $rtotal;
        }

        if (empty($return_items)) {
            $this->session->set_flashdata('error', 'Enter return quantity for at least one item.');
            redirect('purchases/return_create/' . $purchase_id);
        }

        // ── Serial validation for the return ────────────────────────────────
        // A unit can only go back to the supplier if it is still IN STOCK and
        // it actually came in on THIS purchase — returning someone else's stock
        // against this invoice would silently corrupt both.
        $this->load->model('Serial_model');
        $prn_serial_plan = [];
        $prn_errors      = [];
        if ($this->Serial_model->schema_ready()) {
            $smap = $this->Serial_model->serialized_map(array_column($return_items, 'product_id'));
            $seen = [];
            foreach ($return_items as $ix => $ri) {
                $prod = $smap[(int)$ri['product_id']] ?? null;
                if (!$prod || (int)$prod->maintain_serial !== 1) continue;

                $list = json_decode((string)$ri['_serials_json'], true);
                if (!is_array($list)) $list = [];

                if (count($list) !== (int)$ri['qty']) {
                    $prn_errors[] = 'Select exactly ' . $ri['qty'] . ' serial number(s) to return for "'
                                  . $prod->name . '" (' . count($list) . ' selected).';
                    continue;
                }
                $rows = [];
                foreach ($list as $sn) {
                    $key = $this->Serial_model->normalize($sn);
                    if (isset($seen[$key])) { $prn_errors[] = 'Serial ' . $key . ' selected twice.'; continue; }
                    $seen[$key] = true;

                    $chk = $this->Serial_model->validate_outbound($key, $ri['product_id'], null);
                    if (!$chk['ok']) { $prn_errors[] = $chk['error']; continue; }
                    if ((int)$chk['row']->purchase_id !== (int)$purchase_id) {
                        $prn_errors[] = 'Serial ' . $key . ' was not received on this purchase.';
                        continue;
                    }
                    $rows[] = $chk['row'];
                }
                if (count($rows) === (int)$ri['qty']) $prn_serial_plan[$ix] = $rows;
            }
        }
        if ($prn_errors) {
            $this->session->set_flashdata('error',
                'Serial number problems:<br>• ' . implode('<br>• ', array_slice($prn_errors, 0, 12)));
            redirect('purchases/return_create/' . $purchase_id);
        }

        $prn_no = $this->Purchase_model->generate_prn_no();
        $prn_data = [
            'prn_no'       => $prn_no,
            'purchase_id'  => $purchase_id,
            'supplier_id'  => $data['purchase']->supplier_id,
            'return_date'  => date('Y-m-d'),
            'total_amount' => $total_return,
            'reason'       => $this->input->post('reason', TRUE),
            'created_by'   => $this->session->userdata('user_id'),
        ];

        // _-prefixed keys are controller scratch, not columns on the PRN item table.
        $prn_db_items = [];
        foreach ($return_items as $ri) {
            $prn_db_items[] = array_filter($ri, function ($k) { return $k[0] !== '_'; }, ARRAY_FILTER_USE_KEY);
        }

        $this->db->trans_start();
        $prn_id = $this->Purchase_model->create_prn($prn_data, $prn_db_items);
        foreach ($return_items as $ri) {
            $this->Product_model->decrease_stock($ri['product_id'], $ri['qty']);
        }

        // Returned units leave stock but keep their full history.
        $prn_serial_count = 0;
        foreach ($prn_serial_plan as $ix => $rows) {
            foreach ($rows as $srow) {
                $this->Serial_model->set_status($srow->id, 'PURCHASE_RETURN', [
                    'txn_type'     => 'PURCHASE_RETURN',
                    'reference_id' => $prn_id ?: null,
                    'reference_no' => $prn_no,
                    'supplier_id'  => $prn_data['supplier_id'],
                    'location_id'  => null,   // no longer held anywhere
                    'remarks'      => 'Returned to supplier on ' . $prn_no,
                ]);
                if ($prn_id) $this->Serial_model->link_doc($srow->id, 'purchase_return', $prn_id);
                $prn_serial_count++;
            }
        }
        $this->db->trans_complete();

        if ($prn_serial_count) {
            $this->log_activity('purchases', 'serials_returned',
                $prn_serial_count . ' serial number(s) returned to supplier on ' . $prn_no, $prn_id, $prn_no);
        }

        $this->session->set_flashdata('success', 'Purchase Return #' . $prn_no . ' created. Stock adjusted.');
        redirect('purchases/view/' . $purchase_id);
    }

    // Print Purchase Order / GRN
    public function print_po($id) {
        $purchase = $this->Purchase_model->get_by_id($id);
        if (!$purchase) {
            show_404();
        }
        
        $comp_settings = $this->db->get('company_settings')->result();
        $company = [];
        foreach($comp_settings as $s) {
            $company[$s->key_name] = $s->key_value;
        }

        $items = $this->Purchase_model->get_items($id);
        
        foreach ($items as &$item) {
            $q_lp = $this->db->select('pi.qty, p.invoice_date')
                ->from('purchase_items pi')
                ->join('purchases p', 'p.id = pi.purchase_id')
                ->where('pi.product_id', $item->product_id)
                ->where('p.id !=', $id)
                ->where('p.invoice_date <=', $purchase->invoice_date)
                ->order_by('p.invoice_date', 'DESC')
                ->order_by('p.id', 'DESC')
                ->limit(1)
                ->get()->row();

            $item->last_pur_date = '-';
            $item->last_pur_qty = 0;
            $item->sales_since_lp = 0;

            if ($q_lp) {
                $item->last_pur_date = date('d-M-y', strtotime($q_lp->invoice_date));
                $item->last_pur_qty = (float)$q_lp->qty;
                
                $q_sales = $this->db->select_sum('ii.qty', 'total_sold')
                    ->from('invoice_items ii')
                    ->join('invoices i', 'i.id = ii.invoice_id')
                    ->where('ii.product_id', $item->product_id)
                    ->where('i.invoice_date >=', $q_lp->invoice_date)
                    ->where('i.invoice_date <=', $purchase->invoice_date)
                    ->get()->row();
                    
                if ($q_sales && $q_sales->total_sold) {
                    $item->sales_since_lp = (float)$q_sales->total_sold;
                }
            }
        }
        
        $data['purchase'] = $purchase;
        $data['items']    = $items;
        $data['company']  = $company;
        $this->load->view('purchases/print', $data);
    }

    public function get_product_history_ajax() {
        if (!$this->input->is_ajax_request()) return;
        $product_id = (int)$this->input->get('product_id');
        
        $res = [
            'last_purchase_date' => '-',
            'last_purchase_qty'  => 0,
            'sales_since_lp'     => 0,
        ];
        
        if (!$product_id) {
            echo json_encode($res);
            return;
        }

        // Get last purchase date and qty
        $q_lp = $this->db->select('pi.qty, p.invoice_date')
            ->from('purchase_items pi')
            ->join('purchases p', 'p.id = pi.purchase_id')
            ->where('pi.product_id', $product_id)
            ->order_by('p.invoice_date', 'DESC')
            ->order_by('p.id', 'DESC')
            ->limit(1)
            ->get()->row();

        if ($q_lp) {
            $res['last_purchase_date'] = date('d-M-y', strtotime($q_lp->invoice_date));
            $res['last_purchase_qty'] = (float)$q_lp->qty;
            
            // Get sales since last purchase date
            $q_sales = $this->db->select_sum('ii.qty', 'total_sold')
                ->from('invoice_items ii')
                ->join('invoices i', 'i.id = ii.invoice_id')
                ->where('ii.product_id', $product_id)
                ->where('i.invoice_date >=', $q_lp->invoice_date)
                ->get()->row();
                
            if ($q_sales && $q_sales->total_sold) {
                $res['sales_since_lp'] = (float)$q_sales->total_sold;
            }
        }

        echo json_encode($res);
    }

    // ============================================================
    // Draft / Auto-save endpoints
    // ============================================================

    public function autosave_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('Entry_draft_model');
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!$payload) { echo json_encode(['success' => false, 'error' => 'Invalid payload']); return; }

        $draft_id = !empty($payload['draft_id']) ? (int)$payload['draft_id'] : null;
        $user_id  = $this->session->userdata('user_id');

        if ($draft_id && !$this->Entry_draft_model->can_access($draft_id, $user_id, $this->is_admin())) {
            echo json_encode(['success' => false, 'error' => 'Not permitted']);
            return;
        }

        $h = $payload['header'] ?? [];
        $h['header_json'] = json_encode($h);
        $h['location_id'] = $this->active_location_id() ?: 1;

        $is_new = !$draft_id;
        $new_draft_id = $this->Entry_draft_model->save_draft($draft_id, $h, $payload['items'] ?? [], $user_id, 'purchase');
        if ($new_draft_id === false) {
            echo json_encode(['success' => false, 'error' => 'Save failed']);
            return;
        }
        if ($is_new) {
            $this->log_activity('purchases', 'draft_create', 'Draft auto-save started', $new_draft_id);
        }
        echo json_encode(['success' => true, 'draft_id' => $new_draft_id, 'last_autosaved_at' => date('Y-m-d H:i:s')]);
    }

    public function check_draft_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('Entry_draft_model');
        $draft = $this->Entry_draft_model->find_active_for_user($this->session->userdata('user_id'), 'purchase');
        if (!$draft) { echo json_encode(['found' => false]); return; }
        echo json_encode([
            'found'            => true,
            'draft_id'         => $draft->id,
            'last_modified_at' => $draft->last_modified_at,
            'invoice_no'       => $draft->invoice_no,
            'item_count'       => count($this->Entry_draft_model->get_items($draft->id)),
        ]);
    }

    public function resume_draft_ajax($draft_id = null) {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('Entry_draft_model');
        $user_id = $this->session->userdata('user_id');
        if (!$draft_id || !$this->Entry_draft_model->can_access($draft_id, $user_id, $this->is_admin())) {
            echo json_encode(['success' => false, 'error' => 'Not found or not permitted']);
            return;
        }
        $locked_by = $this->Entry_draft_model->locked_by_other($draft_id, $user_id);
        if ($locked_by) {
            echo json_encode(['success' => false, 'error' => 'This draft is currently being edited by ' . $locked_by . '.']);
            return;
        }
        $this->Entry_draft_model->acquire_lock($draft_id, $user_id);
        $draft = $this->Entry_draft_model->get_draft($draft_id);
        $items = $this->Entry_draft_model->get_items($draft_id);
        echo json_encode(['success' => true, 'draft' => $draft, 'items' => $items]);
    }

    public function heartbeat_ajax($draft_id = null) {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('Entry_draft_model');
        $user_id = $this->session->userdata('user_id');
        if (!$draft_id || !$this->Entry_draft_model->can_access($draft_id, $user_id, $this->is_admin())) {
            echo json_encode(['success' => false]);
            return;
        }
        $this->Entry_draft_model->heartbeat($draft_id, $user_id);
        echo json_encode(['success' => true]);
    }

    public function delete_draft_ajax($draft_id = null) {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('Entry_draft_model');
        $user_id = $this->session->userdata('user_id');
        $ok = $draft_id && $this->Entry_draft_model->delete_draft($draft_id, $user_id, $this->is_admin());
        if ($ok) $this->log_activity('purchases', 'draft_delete', 'Draft deleted', $draft_id);
        echo json_encode(['success' => (bool)$ok]);
    }

    public function drafts() {
        $this->load->model('Entry_draft_model');
        $this->Entry_draft_model->cleanup_stale(30);
        $data['page_title'] = 'Purchase Drafts';
        $data['drafts'] = $this->Entry_draft_model->find_all_visible(
            $this->session->userdata('user_id'), $this->is_admin(), 'purchase'
        );
        $this->render('purchases/drafts', $data);
    }

    /**
     * AJAX Endpoint: Fetch structured size x colour variant matrix for purchase entry
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
                'stock_qty'     => (float)$v->loc_stock_qty,
            ];
        }

        uasort($sizes, function($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        $hsn_row = $product->hsn_id ? $this->db->where('id', $product->hsn_id)->get('hsn_master')->row() : null;

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
            ],
            'sizes'    => array_values($sizes),
            'colours'  => array_values($colours),
            'matrix'   => $matrix,
            'count'    => count($variants)
        ]);
    }
}
