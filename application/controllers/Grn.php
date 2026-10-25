<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Grn extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Grn_model','Purchase_order_model','Supplier_model','Product_model','Invoice_model']);
    }

    public function index() {
        $data['page_title'] = 'Goods Receipt Notes (GRN)';
        $data['grns']       = $this->Grn_model->get_all();
        $this->render('grn/index', $data);
    }

    public function create($po_id = null) {
        $data['page_title'] = 'New GRN';
        $po = $po_id ? $this->Purchase_order_model->get_by_id($po_id) : null;
        if (!$po) {
            // Show PO selector
            $data['approved_pos'] = $this->Purchase_order_model->get_all(['status' => 'approved']);
            $data['approved_pos'] = array_merge($data['approved_pos'], $this->Purchase_order_model->get_all(['status' => 'vendor_confirmed']));
            $this->render('grn/select_po', $data);
            return;
        }
        $data['po']    = $po;
        $data['items'] = $this->Purchase_order_model->get_items($po_id);

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('grn/create', $data);
            return;
        }

        // Validate mandatory invoice number
        if (!trim($this->input->post('supplier_invoice_no', true))) {
            $this->session->set_flashdata('error', 'Supplier Invoice Number is required.');
            $this->render('grn/create', $data);
            return;
        }

        $grn_items = [];
        $po_item_ids = $this->input->post('po_item_id');
        $product_ids = $this->input->post('product_id');
        $recv_qtys   = $this->input->post('received_qty');
        $acc_qtys    = $this->input->post('accepted_qty');
        $rej_qtys    = $this->input->post('rejected_qty');
        $rates       = $this->input->post('rate');
        $gsts        = $this->input->post('gst_pct');
        $rej_reasons = $this->input->post('rejection_reason');
        $batches     = $this->input->post('batch_no');
        $expiries    = $this->input->post('expiry_date');
        $mrps        = $this->input->post('mrp');
        $sps         = $this->input->post('sp');

        $variant_ids = $this->input->post('variant_id');
        $variant_skus = $this->input->post('variant_sku');
        $size_names = $this->input->post('size_name');
        $colour_names = $this->input->post('colour_name');
        $chassis_nos  = $this->input->post('chassis_no');
        $motor_nos    = $this->input->post('motor_no');
        $serials_json = $this->input->post('serials_json');

        foreach ((array)$product_ids as $i => $pid) {
            $rq = (float)($recv_qtys[$i] ?? 0);
            if (!$pid || $rq <= 0) continue;
            $rate    = (float)($rates[$i] ?? 0);
            $gst     = (float)($gsts[$i]  ?? 0);
            $acc     = (float)($acc_qtys[$i] ?? $rq);
            $rej     = (float)($rej_qtys[$i]  ?? 0);
            $taxable = $acc * $rate;
            $grn_items[] = [
                'po_item_id'       => (int)($po_item_ids[$i] ?? 0),
                'product_id'       => (int)$pid,
                'variant_id'       => !empty($variant_ids[$i]) ? (int)$variant_ids[$i] : null,
                'variant_sku'      => $variant_skus[$i] ?? null,
                'size_name'        => $size_names[$i] ?? null,
                'colour_name'      => $colour_names[$i] ?? null,
                'chassis_no'       => !empty($chassis_nos[$i]) ? trim($chassis_nos[$i]) : null,
                'motor_no'         => !empty($motor_nos[$i]) ? trim($motor_nos[$i]) : null,
                'serials_json'     => is_array($serials_json) ? ($serials_json[$i] ?? null) : null,
                'ordered_qty'      => $data['items'][$i]->ordered_qty ?? $rq,
                'received_qty'     => $rq,
                'accepted_qty'     => $acc,
                'rejected_qty'     => $rej,
                'rate'             => $rate,
                'gst_pct'          => $gst,
                'amount'           => round($taxable + $taxable * $gst / 100, 2),
                'batch_no'         => $batches[$i] ?? '',
                'expiry_date'      => $expiries[$i] ?? null,
                'rejection_reason' => $rej_reasons[$i] ?? '',
                'mrp'              => isset($mrps[$i]) ? (float)$mrps[$i] : 0,
                'selling_price'    => isset($sps[$i]) ? (float)$sps[$i] : 0,
            ];
        }

        if (empty($grn_items)) {
            $this->session->set_flashdata('error', 'No valid items entered.');
            $this->render('grn/create', $data);
            return;
        }

        $supplier_invoice_no = $this->input->post('supplier_invoice_no', true);
        $dupe = $this->db->where('supplier_id', $po->supplier_id)
                          ->where('supplier_invoice_no', $supplier_invoice_no)
                          ->get('grn')->row();
        if ($dupe) {
            $this->session->set_flashdata('error', 'A GRN with invoice number "' . $supplier_invoice_no . '" already exists for this supplier (GRN #' . $dupe->grn_number . ').');
            $this->render('grn/create', $data);
            return;
        }

        $gross = array_sum(array_column($grn_items, 'amount'));
        $grn_data = [
            'po_id'                 => $po_id,
            'supplier_id'           => $po->supplier_id,
            'location_id'           => $po->location_id,
            'received_date'         => $this->input->post('received_date', true) ?: date('Y-m-d'),
            'vehicle_no'            => $this->input->post('vehicle_no',    true),
            'driver_name'           => $this->input->post('driver_name',   true),
            'supplier_invoice_no'   => $supplier_invoice_no,
            'supplier_invoice_date' => $this->input->post('supplier_invoice_date', true) ?: null,
            'eway_bill'             => $this->input->post('eway_bill',     true),
            'lr_number'             => $this->input->post('lr_number',     true),
            'status'                => 'pending',
            'gross_amount'          => $gross,
            'net_total'             => $gross,
            'received_by'           => $this->session->userdata('user_id'),
        ];

        $this->db->trans_start();
        $grn_id = $this->Grn_model->create($grn_data, $grn_items);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error. GRN not saved.');
            $this->render('grn/create', $data);
            return;
        }

        $draft_id = (int)$this->input->post('draft_id', true);
        if ($draft_id) {
            $this->load->model('Entry_draft_model');
            $this->Entry_draft_model->mark_completed($draft_id, $grn_id);
        }

        $this->log_activity('grn', 'create', 'GRN created for PO#' . $po->po_number, $grn_id);
        $this->session->set_flashdata('success', 'GRN created. Proceed to Quality Check.');
        redirect("grn/view/$grn_id");
    }

    // ── Direct GRN without a Purchase Order ──────────────────────────────────
    public function create_direct() {
        $data['page_title'] = 'New GRN (Without PO)';
        $data['suppliers']  = $this->Supplier_model->get_all();
        $data['products']   = $this->Product_model->get_all($this->active_location_id());

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('grn/create_direct', $data);
            return;
        }

        $supplier_id = (int)$this->input->post('supplier_id');
        if (!$supplier_id) {
            $this->session->set_flashdata('error', 'Please select a supplier.');
            $this->render('grn/create_direct', $data);
            return;
        }
        if (!trim($this->input->post('supplier_invoice_no', true))) {
            $this->session->set_flashdata('error', 'Supplier Invoice Number is required.');
            $this->render('grn/create_direct', $data);
            return;
        }

        $product_ids = $this->input->post('product_id');
        $recv_qtys   = $this->input->post('received_qty');
        $acc_qtys    = $this->input->post('accepted_qty');
        $rej_qtys    = $this->input->post('rejected_qty');
        $rates       = $this->input->post('rate');
        $gsts        = $this->input->post('gst_pct');
        $rej_reasons = $this->input->post('rejection_reason');
        $batches     = $this->input->post('batch_no');
        $expiries    = $this->input->post('expiry_date');
        $mrps        = $this->input->post('mrp');
        $sps         = $this->input->post('sp');
        $variant_ids = $this->input->post('variant_id');
        $variant_skus = $this->input->post('variant_sku');
        $size_names = $this->input->post('size_name');
        $colour_names = $this->input->post('colour_name');
        $chassis_nos  = $this->input->post('chassis_no');
        $motor_nos    = $this->input->post('motor_no');
        $serials_json = $this->input->post('serials_json');

        $grn_items = [];
        foreach ((array)$product_ids as $i => $pid) {
            $rq = (float)($recv_qtys[$i] ?? 0);
            if (!$pid || $rq <= 0) continue;
            $rate = (float)($rates[$i] ?? 0);
            $gst  = (float)($gsts[$i]  ?? 0);
            $acc  = (isset($acc_qtys[$i]) && $acc_qtys[$i] !== '') ? (float)$acc_qtys[$i] : $rq;
            $rej  = (float)($rej_qtys[$i] ?? 0);
            $taxable = $acc * $rate;
            $grn_items[] = [
                'po_item_id'       => null,
                'product_id'       => (int)$pid,
                'variant_id'       => !empty($variant_ids[$i]) ? (int)$variant_ids[$i] : null,
                'variant_sku'      => $variant_skus[$i] ?? null,
                'size_name'        => $size_names[$i] ?? null,
                'colour_name'      => $colour_names[$i] ?? null,
                'chassis_no'       => !empty($chassis_nos[$i]) ? trim($chassis_nos[$i]) : null,
                'motor_no'         => !empty($motor_nos[$i]) ? trim($motor_nos[$i]) : null,
                'serials_json'     => is_array($serials_json) ? ($serials_json[$i] ?? null) : null,
                'ordered_qty'      => 0,
                'received_qty'     => $rq,
                'accepted_qty'     => $acc,
                'rejected_qty'     => $rej,
                'rate'             => $rate,
                'gst_pct'          => $gst,
                'amount'           => round($taxable + $taxable * $gst / 100, 2),
                'batch_no'         => $batches[$i] ?? '',
                'expiry_date'      => ($expiries[$i] ?? '') ?: null,
                'rejection_reason' => $rej_reasons[$i] ?? '',
                'mrp'              => isset($mrps[$i]) ? (float)$mrps[$i] : 0,
                'selling_price'    => isset($sps[$i]) ? (float)$sps[$i] : 0,
            ];
        }

        if (empty($grn_items)) {
            $this->session->set_flashdata('error', 'No valid items entered. Add at least one product with received quantity.');
            $this->render('grn/create_direct', $data);
            return;
        }

        $supplier_invoice_no = $this->input->post('supplier_invoice_no', true);
        $dupe = $this->db->where('supplier_id', $supplier_id)
                          ->where('supplier_invoice_no', $supplier_invoice_no)
                          ->get('grn')->row();
        if ($dupe) {
            $this->session->set_flashdata('error', 'A GRN with invoice number "' . $supplier_invoice_no . '" already exists for this supplier (GRN #' . $dupe->grn_number . ').');
            $this->render('grn/create_direct', $data);
            return;
        }

        $gross = array_sum(array_column($grn_items, 'amount'));
        $grn_data = [
            'po_id'                 => null,
            'supplier_id'           => $supplier_id,
            'location_id'           => $this->active_location_id(),
            'received_date'         => $this->input->post('received_date', true) ?: date('Y-m-d'),
            'vehicle_no'            => $this->input->post('vehicle_no',    true),
            'driver_name'           => $this->input->post('driver_name',   true),
            'supplier_invoice_no'   => $supplier_invoice_no,
            'supplier_invoice_date' => $this->input->post('supplier_invoice_date', true) ?: null,
            'eway_bill'             => $this->input->post('eway_bill',     true),
            'lr_number'             => $this->input->post('lr_number',     true),
            'status'                => 'pending',
            'gross_amount'          => $gross,
            'net_total'             => $gross,
            'received_by'           => $this->session->userdata('user_id'),
        ];

        $this->db->trans_start();
        $grn_id = $this->Grn_model->create($grn_data, $grn_items);
        $will_accept = $this->input->post('action') === 'update';
        if ($will_accept) {
            $this->Grn_model->accept($grn_id, $this->session->userdata('user_id'), 'Auto-accepted on direct GRN save');
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error. GRN not saved.');
            $this->render('grn/create_direct', $data);
            return;
        }

        $draft_id = (int)$this->input->post('draft_id', true);
        if ($draft_id) {
            $this->load->model('Entry_draft_model');
            $this->Entry_draft_model->mark_completed($draft_id, $grn_id);
        }

        $this->log_activity('grn', 'create', 'Direct GRN created (no PO)', $grn_id);
        if ($will_accept) {
            $this->log_activity('grn', 'accept', 'Direct GRN accepted, stock updated', $grn_id);
            $this->session->set_flashdata('success', 'GRN saved and stock updated successfully.');
        } else {
            $this->session->set_flashdata('success', 'GRN saved as draft (pending). Stock not yet updated.');
        }
        redirect("grn/view/$grn_id");
    }

    public function view($id = null) {
        if (!$id) redirect('grn');
        $data['page_title'] = 'GRN Details';
        $data['grn']   = $this->Grn_model->get_by_id($id);
        $data['items'] = $this->Grn_model->get_items($id);
        $this->render('grn/view', $data);
    }

    // B2B pass-through billing: bill the whole GRN straight out to a customer at
    // basic_cost + markup%. Stashes items in session and hands off to billing/create,
    // the same way OrderRequests::convert() hands a portal order to billing.
    public function convert_to_invoice($id = null) {
        if (!$id) redirect('grn');
        $grn = $this->Grn_model->get_by_id($id);
        if (!$grn) redirect('grn');

        if ($grn->status !== 'accepted') {
            $this->session->set_flashdata('error', 'Only an accepted GRN can be converted to a sales invoice.');
            redirect("grn/view/$id");
        }
        if (!empty($grn->converted_invoice_id)) {
            $this->session->set_flashdata('error', 'This GRN was already converted to invoice #' . $grn->converted_invoice_id . '.');
            redirect("grn/view/$id");
        }
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect("grn/view/$id");
        }

        $markup = $this->input->post('markup_percent', TRUE);
        if (!is_numeric($markup) || (float)$markup < 0) {
            $this->session->set_flashdata('error', 'Enter a valid markup percentage (0 or more).');
            redirect("grn/view/$id");
        }
        $markup = (float)$markup;

        $items = $this->Grn_model->get_accepted_items($id);
        if (empty($items)) {
            $this->session->set_flashdata('error', 'This GRN has no accepted items to bill.');
            redirect("grn/view/$id");
        }

        $bill_items = [];
        foreach ($items as $it) {
            // grn_items.rate is basic cost (GST-exclusive) — add the markup on basic,
            // then apply GST on top to get the GST-inclusive rate Billing::create() expects.
            $basic_with_markup = (float)$it->rate * (1 + $markup / 100);
            $sell_rate = round($basic_with_markup * (1 + (float)$it->gst_pct / 100), 2);
            $bill_items[] = [
                'product_id'   => $it->product_id,
                'product_name' => $it->product_name,
                'qty'          => (float)$it->accepted_qty,
                'unit'         => $it->unit,
                'hsn_code'     => $it->hsn_code,
                'gst_percent'  => (float)$it->gst_pct,
                'sell_rate'    => $sell_rate,
            ];
        }

        $this->session->set_userdata('convert_grn', [
            'grn_id'         => $id,
            'grn_number'     => $grn->grn_number,
            'supplier_name'  => $grn->supplier_name,
            'markup_percent' => $markup,
            'items'          => $bill_items,
        ]);

        redirect('billing/create');
    }

    // AJAX: barcode/item-code scan lookup for the direct-GRN scan bar — mirrors
    // Billing::get_by_barcode_ajax so cashiers get the same multi-PLU picker.
    public function get_by_barcode_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('Plu_model');
        $barcode = $this->input->post('barcode', TRUE);
        header('Content-Type: application/json');

        $plus = $this->Plu_model->get_by_barcode($barcode, $this->active_location_id());

        if (!empty($plus)) {
            $plu_list = array_map(function($p) {
                return [
                    'id'            => $p->id,
                    'product_id'    => $p->product_id,
                    'plu_code'      => $p->plu_code,
                    'item_name'     => $p->item_name,
                    'item_code'     => $p->item_code,
                    'hsn_code'      => $p->hsn_code ?? '',
                    'mrp'           => (float)$p->mrp,
                    'purchase_rate' => (float)$p->purchase_rate,
                    'selling_rate'  => (float)$p->selling_rate,
                    'gst_percent'   => (float)$p->gst_percent,
                    'stock_qty'     => (float)$p->stock_qty,
                ];
            }, $plus);

            if (count($plu_list) === 1) {
                echo json_encode(['success' => true, 'mode' => 'single', 'plu' => $plu_list[0]]);
            } else {
                echo json_encode(['success' => true, 'mode' => 'multiple', 'plus' => $plu_list, 'barcode' => $barcode, 'count' => count($plu_list)]);
            }
            return;
        }

        // Legacy fallback: no PLU exists for this barcode — resolve to a plain product
        // and pull the full row so we still have basic_cost (not returned by the
        // lightweight billing-lookup helpers, which only carry selling price/MRP).
        $found = $this->Product_model->get_by_barcode($barcode);
        if ($found) {
            $product = $this->Product_model->get_by_id($found->id);
            echo json_encode(['success' => true, 'mode' => 'legacy', 'product' => [
                'id'           => $product->id,
                'item_code'    => $product->item_code,
                'name'         => $product->name,
                'hsn_code'     => $product->hsn_code_display ?: $product->hsn_code,
                'basic_cost'   => (float)$product->basic_cost,
                'mrp'          => (float)$product->mrp,
                'selling_price'=> (float)$product->selling_price,
                'gst_percent'  => (float)$product->gst_percent,
            ]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Barcode not found']);
        }
    }

    public function print_grn($id = null) {
        if (!$id) redirect('grn');
        $data['page_title'] = 'Print GRN';
        $data['grn']        = $this->Grn_model->get_by_id($id);
        $data['items']      = $this->Grn_model->get_items($id);
        $data['company']    = $this->Invoice_model->get_company_settings();
        // Render standalone — no sidebar layout
        $this->load->view('grn/print', $data);
    }

    public function edit($id = null) {
        if (!$id) redirect('grn');
        $grn = $this->Grn_model->get_by_id($id);
        if (!$grn) redirect('grn');

        $data['page_title'] = 'Edit GRN — ' . $grn->grn_number;
        $data['grn']        = $grn;
        $data['items']      = $this->Grn_model->get_items($id);
        $data['suppliers']  = $this->Supplier_model->get_all();
        $data['products']   = $this->Product_model->get_all($grn->location_id ?: $this->active_location_id());
        $data['is_admin']   = $this->is_admin();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('grn/edit', $data);
            return;
        }

        if (!trim($this->input->post('supplier_invoice_no', true))) {
            $this->session->set_flashdata('error', 'Supplier Invoice Number is required.');
            $this->render('grn/edit', $data);
            return;
        }

        $will_authorize = $this->input->post('action') === 'update';
        if ($will_authorize) {
            $this->require_admin();
        }

        $po_item_ids = $this->input->post('po_item_id');
        $product_ids = $this->input->post('product_id');
        $recv_qtys   = $this->input->post('received_qty');
        $acc_qtys    = $this->input->post('accepted_qty');
        $rej_qtys    = $this->input->post('rejected_qty');
        $rates       = $this->input->post('rate');
        $gsts        = $this->input->post('gst_pct');
        $rej_reasons = $this->input->post('rejection_reason');
        $batches     = $this->input->post('batch_no');
        $expiries    = $this->input->post('expiry_date');
        $mrps        = $this->input->post('mrp');
        $sps         = $this->input->post('sp');

        $grn_items = [];
        foreach ((array)$product_ids as $i => $pid) {
            $rq = (float)($recv_qtys[$i] ?? 0);
            if (!$pid || $rq <= 0) continue;
            $rate = (float)($rates[$i] ?? 0);
            $gst  = (float)($gsts[$i]  ?? 0);
            $acc  = (isset($acc_qtys[$i]) && $acc_qtys[$i] !== '') ? (float)$acc_qtys[$i] : $rq;
            $rej  = (float)($rej_qtys[$i] ?? 0);
            $taxable = $acc * $rate;
            $grn_items[] = [
                'po_item_id'       => !empty($po_item_ids[$i]) ? (int)$po_item_ids[$i] : null,
                'product_id'       => (int)$pid,
                'ordered_qty'      => $data['items'][$i]->ordered_qty ?? 0,
                'received_qty'     => $rq,
                'accepted_qty'     => $acc,
                'rejected_qty'     => $rej,
                'rate'             => $rate,
                'gst_pct'          => $gst,
                'amount'           => round($taxable + $taxable * $gst / 100, 2),
                'batch_no'         => $batches[$i] ?? '',
                'expiry_date'      => ($expiries[$i] ?? '') ?: null,
                'rejection_reason' => $rej_reasons[$i] ?? '',
                'mrp'              => isset($mrps[$i]) ? (float)$mrps[$i] : 0,
                'selling_price'    => isset($sps[$i]) ? (float)$sps[$i] : 0,
            ];
        }

        if (empty($grn_items)) {
            $this->session->set_flashdata('error', 'No valid items entered.');
            $this->render('grn/edit', $data);
            return;
        }

        $gross  = array_sum(array_column($grn_items, 'amount'));
        $header = [
            'received_date'         => $this->input->post('received_date', true) ?: date('Y-m-d'),
            'vehicle_no'            => $this->input->post('vehicle_no',    true),
            'driver_name'           => $this->input->post('driver_name',   true),
            'supplier_invoice_no'   => $this->input->post('supplier_invoice_no',   true),
            'supplier_invoice_date' => $this->input->post('supplier_invoice_date', true) ?: null,
            'eway_bill'             => $this->input->post('eway_bill',     true),
            'lr_number'             => $this->input->post('lr_number',     true),
            'gross_amount'          => $gross,
            'net_total'             => $gross,
        ];

        $this->db->trans_start();
        $this->Grn_model->update($id, $header, $grn_items);
        if ($will_authorize) {
            $this->Grn_model->accept($id, $this->session->userdata('user_id'), $this->input->post('qc_notes', true));
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error. Changes not saved.');
            redirect("grn/edit/$id");
        }

        $this->log_activity('grn', 'edit', 'GRN edited: ' . $grn->grn_number, $id);
        if ($will_authorize) {
            $this->log_activity('grn', 'accept', 'GRN re-authorized after edit', $id);
            $this->session->set_flashdata('success', 'GRN updated and authorized. Stock adjusted.');
        } else {
            $this->session->set_flashdata('success', 'GRN updated and saved as draft.');
        }
        redirect("grn/view/$id");
    }

    public function accept($id = null) {
        if (!$id) redirect('grn');
        $this->require_admin();
        $notes = $this->input->post('qc_notes', true);
        $this->db->trans_start();
        $this->Grn_model->accept($id, $this->session->userdata('user_id'), $notes);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error. GRN not accepted.');
            redirect("grn/view/$id");
        }

        $this->log_activity('grn', 'accept', 'GRN accepted, stock updated', $id);
        $this->session->set_flashdata('success', 'GRN accepted. Stock has been updated.');
        redirect("grn/view/$id");
    }

    // ============================================================
    // Draft / Auto-save endpoints
    // ============================================================

    public function autosave_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('Entry_draft_model');
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!$payload) { echo json_encode(['success' => false, 'error' => 'Invalid payload']); return; }

        $draft_id   = !empty($payload['draft_id']) ? (int)$payload['draft_id'] : null;
        $user_id    = $this->session->userdata('user_id');
        $entry_type = ($payload['entry_type'] ?? '') === 'grn' ? 'grn' : 'grn_direct';

        // TRUE = shared: whoever is continuing the receipt may save to it, not
        // only the user who started it.
        if ($draft_id && !$this->Entry_draft_model->can_access($draft_id, $user_id, $this->is_admin(), true)) {
            echo json_encode(['success' => false, 'error' => 'Not permitted']);
            return;
        }

        $h = $payload['header'] ?? [];
        $h['header_json'] = json_encode($h);
        $h['location_id'] = $this->active_location_id();
        if (!empty($payload['po_id'])) $h['po_id'] = (int)$payload['po_id'];

        $is_new = !$draft_id;
        $new_draft_id = $this->Entry_draft_model->save_draft($draft_id, $h, $payload['items'] ?? [], $user_id, $entry_type);
        if ($new_draft_id === false) {
            echo json_encode(['success' => false, 'error' => 'Save failed']);
            return;
        }
        if ($is_new) {
            $this->log_activity('grn', 'draft_create', 'Draft auto-save started', $new_draft_id);
        }
        echo json_encode(['success' => true, 'draft_id' => $new_draft_id, 'last_autosaved_at' => date('Y-m-d H:i:s')]);
    }

    public function check_draft_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('Entry_draft_model');
        $entry_type = $this->input->get('entry_type') === 'grn' ? 'grn' : 'grn_direct';
        $po_id      = $this->input->get('po_id') ? (int)$this->input->get('po_id') : null;
        $draft = $this->Entry_draft_model->find_active_for_user($this->session->userdata('user_id'), $entry_type, $po_id);
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
        if (!$draft_id || !$this->Entry_draft_model->can_access($draft_id, $user_id, $this->is_admin(), true)) {
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
        if (!$draft_id || !$this->Entry_draft_model->can_access($draft_id, $user_id, $this->is_admin(), true)) {
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
        if ($ok) $this->log_activity('grn', 'draft_delete', 'Draft deleted', $draft_id);
        echo json_encode(['success' => (bool)$ok]);
    }

    public function drafts() {
        $this->load->model('Entry_draft_model');
        $this->Entry_draft_model->cleanup_stale(30);
        $data['page_title'] = 'GRN Drafts';
        // GRN drafts are shared across users — a receipt started on one counter
        // must be visible and finishable from any other.
        $data['drafts'] = array_merge(
            $this->Entry_draft_model->find_all_visible($this->session->userdata('user_id'), $this->is_admin(), 'grn', true),
            $this->Entry_draft_model->find_all_visible($this->session->userdata('user_id'), $this->is_admin(), 'grn_direct', true)
        );
        $data['current_user_id'] = (int)$this->session->userdata('user_id');
        $this->render('grn/drafts', $data);
    }
    
    public function alter_db() {
        $this->db->query("ALTER TABLE grn_items ADD COLUMN mrp decimal(12,2) DEFAULT 0.00");
        $this->db->query("ALTER TABLE grn_items ADD COLUMN selling_price decimal(12,2) DEFAULT 0.00");
        echo "DB ALTERED SUCCESSFULLY";
    }
}
