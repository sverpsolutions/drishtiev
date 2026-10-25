<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Refunds extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Refund_model', 'Invoice_model', 'Customer_model', 'Product_model']);
    }

    // Refund list with search
    public function index() {
        $filters = [
            'invoice_no'     => $this->input->get('invoice_no',     TRUE),
            'customer_phone' => $this->input->get('customer_phone', TRUE),
            'date_from'      => $this->input->get('date_from',      TRUE),
            'date_to'        => $this->input->get('date_to',        TRUE),
        ];
        $data['page_title'] = 'Refunds & Returns';
        $data['refunds']    = $this->Refund_model->search(array_filter($filters));
        $data['filters']    = $filters;
        $this->render('refunds/index', $data);
    }

    // Create refund: show search form → show original bill → process
    public function create() {
        $data['page_title'] = 'New Refund / Return';
        $data['invoice']    = null;
        $data['items']      = [];

        // Step 1: search by invoice_no or phone
        $search_no    = $this->input->post('search_invoice_no',  TRUE) ?: $this->input->get('invoice_no', TRUE);
        $search_phone = $this->input->post('search_phone',        TRUE);

        if ($search_no || $search_phone) {
            $inv = null;
            if ($search_no) {
                $inv = $this->db->where('invoice_no', strtoupper(trim($search_no)))->get('invoices')->row();
            } elseif ($search_phone) {
                $digits = substr(preg_replace('/\D/', '', $search_phone), -10);
                $cust = $this->db->like('phone', $digits)->limit(1)->get('customers')->row();
                if ($cust) {
                    $inv = $this->db->where('customer_id', $cust->id)->order_by('id', 'DESC')->limit(1)->get('invoices')->row();
                }
            }
            if ($inv) {
                $data['invoice'] = $this->Invoice_model->get_by_id($inv->id);
                $data['items']   = $this->Invoice_model->get_items($inv->id);
                // Flag which lines need a serial scanned back in. Done here
                // rather than in the shared get_items() so no other screen is
                // affected by the extra lookup.
                $this->load->model('Serial_model');
                if ($this->Serial_model->schema_ready() && $data['items']) {
                    $smap = $this->Serial_model->serialized_map(array_column($data['items'], 'product_id'));
                    foreach ($data['items'] as $it) {
                        $it->maintain_serial = (int)($smap[(int)$it->product_id]->maintain_serial ?? 0);
                    }
                }
                $data['prior_refunds'] = $this->Refund_model->get_by_invoice($inv->id);
                $data['total_refunded'] = $this->Refund_model->get_total_refunded_for_invoice($inv->id);
            } else {
                $this->session->set_flashdata('error', 'Invoice not found. Check the bill number or customer phone.');
            }
        }

        // Step 2: process refund
        if ($this->input->server('REQUEST_METHOD') === 'POST' && $this->input->post('invoice_id')) {
            $this->_process_refund($data);
            return;
        }

        $this->render('refunds/create', $data);
    }

    private function _process_refund(&$data) {
        $invoice_id   = (int)$this->input->post('invoice_id');
        $refund_type  = $this->input->post('refund_type', TRUE);  // full | partial
        $refund_mode  = $this->input->post('refund_mode', TRUE);
        $reason       = trim($this->input->post('reason', TRUE));

        $invoice = $this->Invoice_model->get_by_id($invoice_id);
        if (!$invoice || $invoice->status === 'cancelled') {
            $this->session->set_flashdata('error', 'Invalid or cancelled invoice.');
            redirect('refunds/create');
            return;
        }

        if (empty($reason)) {
            $this->session->set_flashdata('error', 'Reason for refund is required.');
            redirect('refunds/create?invoice_no=' . $invoice->invoice_no);
            return;
        }

        $original_items = $this->Invoice_model->get_items($invoice_id);

        // Already-refunded amount
        $already_refunded = $this->Refund_model->get_total_refunded_for_invoice($invoice_id);

        $refund_items   = [];
        $refund_amount  = 0;

        if ($refund_type === 'full') {
            // Refund entire remaining amount and all items
            $refund_amount = round($invoice->total_amount - $already_refunded, 2);
            foreach ($original_items as $oi) {
                $refund_items[] = [
                    'invoice_item_id' => $oi->id,
                    'product_id'      => $oi->product_id,
                    'product_name'    => $oi->product_name,
                    'hsn_code'        => $oi->hsn_code ?? '',
                    'qty'             => $oi->qty,
                    'unit'            => $oi->unit,
                    'rate'            => $oi->rate,
                    'taxable_amt'     => $oi->taxable_amt,
                    'gst_percent'     => $oi->gst_percent,
                    'gst_amount'      => $oi->gst_amount,
                    'total'           => $oi->total,
                ];
            }
        } else {
            // Partial: only selected items/qty
            $ref_item_ids = $this->input->post('ref_item_id') ?: [];
            $ref_qtys     = $this->input->post('ref_qty') ?: [];

            foreach ($ref_item_ids as $k => $item_id) {
                $ref_qty = (float)($ref_qtys[$k] ?? 0);
                if ($ref_qty <= 0) continue;

                // Find original item
                $oi = null;
                foreach ($original_items as $orig) {
                    if ((int)$orig->id === (int)$item_id) { $oi = $orig; break; }
                }
                if (!$oi) continue;

                $qty_ratio     = $ref_qty / max(1, $oi->qty);
                $line_taxable  = round($oi->taxable_amt * $qty_ratio, 2);
                $line_gst      = round($oi->gst_amount  * $qty_ratio, 2);
                $line_total    = round($oi->total        * $qty_ratio, 2);

                $refund_amount += $line_total;
                $refund_items[] = [
                    'invoice_item_id' => $oi->id,
                    'product_id'      => $oi->product_id,
                    'product_name'    => $oi->product_name,
                    'hsn_code'        => $oi->hsn_code ?? '',
                    'qty'             => $ref_qty,
                    'unit'            => $oi->unit,
                    'rate'            => $oi->rate,
                    'taxable_amt'     => $line_taxable,
                    'gst_percent'     => $oi->gst_percent,
                    'gst_amount'      => $line_gst,
                    'total'           => $line_total,
                ];
            }
            $refund_amount = round($refund_amount, 2);
        }

        if ($refund_amount <= 0 || empty($refund_items)) {
            $this->session->set_flashdata('error', 'No items selected for refund or zero amount.');
            redirect('refunds/create?invoice_no=' . $invoice->invoice_no);
            return;
        }

        $max_refundable = round($invoice->total_amount - $already_refunded, 2);
        if ($refund_amount > $max_refundable + 0.01) {
            $this->session->set_flashdata('error', 'Refund amount (₹' . number_format($refund_amount, 2) . ') exceeds refundable amount (₹' . number_format($max_refundable, 2) . ').');
            redirect('refunds/create?invoice_no=' . $invoice->invoice_no);
            return;
        }

        $refund_no = $this->Refund_model->generate_refund_no();

        $refund_data = [
            'refund_no'       => $refund_no,
            'invoice_id'      => $invoice_id,
            'invoice_no'      => $invoice->invoice_no,
            'customer_id'     => $invoice->customer_id,
            'refund_type'     => $refund_type,
            'refund_amount'   => $refund_amount,
            'refund_mode'     => $refund_mode,
            'reason'          => $reason,
            'notes'           => trim($this->input->post('notes', TRUE)),
            'status'          => 'processed',
            'created_by'      => $this->session->userdata('user_id'),
            'created_by_name' => $this->session->userdata('name'),
        ];

        // Cumulative amount already reversed from the customer's balance via
        // credit_note refunds on this invoice, before this refund is recorded.
        $prior_credit_note_refunded = (float)$this->db
            ->select_sum('refund_amount')
            ->where('invoice_id', $invoice_id)
            ->where('refund_mode', 'credit_note')
            ->get('refunds')->row()->refund_amount;

        // ── Serial validation for the sales return ──────────────────────────
        // The scanned unit must be one that actually went out on this invoice —
        // that is what lets the counter find the original sale from the serial
        // alone, and stops a unit from another bill being returned here.
        $this->load->model('Serial_model');
        $ret_serials = [];
        $ret_errors  = [];
        if ($this->Serial_model->schema_ready()) {
            $sold = [];
            foreach ($this->Serial_model->get_by_doc('invoice', $invoice_id) as $s) $sold[$s->serial_no] = $s;

            $by_product = [];
            foreach ($refund_items as $ri) {
                $by_product[(int)$ri['product_id']] = ($by_product[(int)$ri['product_id']] ?? 0) + (int)$ri['qty'];
            }
            $smap = $this->Serial_model->serialized_map(array_keys($by_product));

            $posted = json_decode((string)$this->input->post('return_serial_json'), true);
            if (!is_array($posted)) $posted = [];
            $seen = [];
            foreach ($posted as $sn) {
                $key = $this->Serial_model->normalize($sn);
                if ($key === '' || isset($seen[$key])) continue;
                $seen[$key] = true;
                if (!isset($sold[$key])) {
                    $ret_errors[] = 'Serial Number ' . $key . ' was not sold on invoice ' . $invoice->invoice_no . '.';
                    continue;
                }
                if ($sold[$key]->status !== 'SOLD') {
                    $ret_errors[] = 'Serial Number ' . $key . ' has already been returned (status: '
                                  . $sold[$key]->status . ').';
                    continue;
                }
                $ret_serials[] = $sold[$key];
            }

            // Every serialized unit being refunded must be identified.
            foreach ($by_product as $pid => $qty) {
                $prod = $smap[$pid] ?? null;
                if (!$prod || (int)$prod->maintain_serial !== 1) continue;
                $have = 0;
                foreach ($ret_serials as $s) if ((int)$s->product_id === $pid) $have++;
                if ($have !== $qty) {
                    $ret_errors[] = 'Scan exactly ' . $qty . ' serial number(s) being returned for "'
                                  . $prod->name . '" (' . $have . ' scanned).';
                }
            }
        }
        if ($ret_errors) {
            $this->session->set_flashdata('error',
                'Serial number problems:<br>• ' . implode('<br>• ', array_slice($ret_errors, 0, 12)));
            redirect('refunds/create/' . $invoice_id);
        }

        $this->db->trans_start();
        $refund_id = $this->Refund_model->create($refund_data, $refund_items);

        // Restore stock for returned items (location-level + global + movement log)
        $this->load->model('Location_model');
        $refund_location_id = (int)($invoice->location_id ?: 1);
        $loc_obj = $this->Location_model->get_by_id($refund_location_id);
        $refund_loc_name = $loc_obj ? $loc_obj->name : '';
        foreach ($refund_items as $ri) {
            $before = $this->Location_model->get_stock($ri['product_id'], $refund_location_id);
            $this->Location_model->increase_stock($ri['product_id'], $refund_location_id, $ri['qty']);
            $after  = $this->Location_model->get_stock($ri['product_id'], $refund_location_id);
            $this->Location_model->log_movement([
                'product_id'     => $ri['product_id'],
                'product_name'   => $ri['product_name'] ?? '',
                'location_id'    => $refund_location_id,
                'location_name'  => $refund_loc_name,
                'movement_type'  => 'refund_in',
                'quantity'       => $ri['qty'],
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_type' => 'refund',
                'reference_id'   => $refund_id,
                'reference_no'   => $refund_no,
                'user_id'        => $this->session->userdata('user_id'),
                'user_name'      => $this->session->userdata('name'),
            ]);
        }

        // ── Loyalty (§18): reverse ONLY the points the returned lines earned ─
        // refund_items.invoice_item_id points straight at the original line, so
        // a bill that earned 50 points across three items gives back exactly
        // the returned item's share — not the whole 50.
        $this->load->model('Loyalty_model');
        if ($this->Loyalty_model->schema_ready() && $invoice->customer_id) {
            $ret_item_ids = []; $ret_ratios = [];
            foreach ($refund_items as $ri) {
                if (empty($ri['invoice_item_id'])) continue;
                $iid = (int)$ri['invoice_item_id'];
                $ret_item_ids[] = $iid;
                // A partial-quantity return gives back a proportional share.
                $orig = null;
                foreach ($original_items as $oi) if ((int)$oi->id === $iid) { $orig = $oi; break; }
                $ret_ratios[$iid] = ($orig && (float)$orig->qty > 0)
                    ? min(1, (float)$ri['qty'] / (float)$orig->qty) : 1.0;
            }
            if ($ret_item_ids) {
                $reversed = $this->Loyalty_model->reverse_for_return(
                    $invoice_id, $ret_item_ids, $refund_id, $refund_no, $ret_ratios);
                if ($reversed > 0) {
                    $this->log_activity('loyalty', 'sales_return',
                        (int)$reversed . ' point(s) reversed on return ' . $refund_no
                        . ' against bill ' . $invoice->invoice_no, $refund_id, $refund_no);
                }
            }
        }

        // Returned units land in SALES_RETURN, NOT straight back into sellable
        // stock — the spec requires a deliberate disposition (back to stock,
        // repair, inspection or scrap) before they can be sold again.
        foreach ($ret_serials as $srow) {
            $this->Serial_model->set_status($srow->id, 'SALES_RETURN', [
                'txn_type'     => 'SALES_RETURN',
                'reference_id' => $refund_id,
                'reference_no' => $refund_no,
                'customer_id'  => $invoice->customer_id,
                'location_id'  => $this->active_location_id() ?: null,
                'remarks'      => 'Returned by customer on ' . $refund_no . ' (invoice ' . $invoice->invoice_no . ')',
            ]);
            $this->Serial_model->link_doc($srow->id, 'sales_return', $refund_id);
            $this->db->where('serial_id', $srow->id)->where('invoice_id', $invoice_id)
                     ->update('serial_warranties', ['is_returned' => 1]);
        }

        // If credit mode, reduce customer balance (they owe less), capped to what's
        // still outstanding on the invoice after any prior credit_note refunds.
        $credit_note_reduce_this_call = 0;
        if ($refund_mode === 'credit_note' && $invoice->due_amount > 0) {
            $remaining_capacity = max(0, $invoice->due_amount - $prior_credit_note_refunded);
            $credit_note_reduce_this_call = min($refund_amount, $remaining_capacity);
            if ($credit_note_reduce_this_call > 0) {
                $this->Customer_model->decrease_balance($invoice->customer_id, $credit_note_reduce_this_call);
            }
        }

        // Update invoice status based on total refunded
        $new_total_refunded = $already_refunded + $refund_amount;
        if ($new_total_refunded >= $invoice->total_amount - 0.01) {
            // Invoice is being fully cancelled, so it will no longer count as
            // outstanding anywhere (all balance/due queries exclude status='cancelled').
            // Reverse any remaining due that hasn't already been credited back above —
            // regardless of refund mode — mirroring Billing::cancel().
            $total_credit_note_reduced = $prior_credit_note_refunded + $credit_note_reduce_this_call;
            $remaining_due = round($invoice->due_amount - $total_credit_note_reduced, 2);
            if ($remaining_due > 0) {
                $this->Customer_model->decrease_balance($invoice->customer_id, $remaining_due);
            }
            $this->Invoice_model->update_status($invoice_id, 'cancelled');
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error. Refund not saved.');
            redirect('refunds/create?invoice_no=' . $invoice->invoice_no);
            return;
        }

        $this->log_activity('refunds', 'create_refund', 'Refund #' . $refund_no . ' for invoice #' . $invoice->invoice_no . ' — ₹' . number_format($refund_amount, 2), $refund_id, $refund_no);
        $this->session->set_flashdata('success', 'Refund #' . $refund_no . ' processed. ₹' . number_format($refund_amount, 2) . ' to be returned via ' . strtoupper($refund_mode) . '. Stock restored.');
        redirect('refunds/view/' . $refund_id);
    }

    // View a single refund
    public function view($id = null) {
        if (!$id) redirect('refunds');
        $data['page_title'] = 'Refund Details';
        $data['refund']     = $this->Refund_model->get_by_id($id);
        $data['items']      = $this->Refund_model->get_items($id);
        $data['company']    = $this->Invoice_model->get_company_settings();
        if (!$data['refund']) show_404();
        $this->render('refunds/view', $data);
    }

    // AJAX: get invoice details for refund modal
    public function get_invoice_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $no  = strtoupper(trim($this->input->post('invoice_no', TRUE)));
        $inv = $this->db->where('invoice_no', $no)->get('invoices')->row();
        header('Content-Type: application/json');
        if ($inv) {
            echo json_encode(['success' => true, 'invoice' => $inv]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
        }
    }
}
