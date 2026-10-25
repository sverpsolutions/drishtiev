<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PurchaseOrders extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Purchase_order_model','Supplier_model','Product_model','Purchase_suggestion_model','Invoice_model']);
        $this->load->library('form_validation');
    }

    public function index() {
        $data['page_title'] = 'Purchase Orders';
        $filters = [
            'status'      => $this->input->get('status'),
            'supplier_id' => $this->input->get('supplier_id'),
            'from_date'   => $this->input->get('from_date'),
            'to_date'     => $this->input->get('to_date'),
            'location_id' => $this->active_location_id(),
        ];
        $data['pos']       = $this->Purchase_order_model->get_all($filters);
        $data['stats']     = $this->Purchase_order_model->get_dashboard_stats($this->active_location_id());
        $data['suppliers'] = $this->Supplier_model->get_all_for_select();
        $data['filters']   = $filters;
        $this->render('purchase_orders/index', $data);
    }

    public function create() {
        $data['page_title']  = 'New Purchase Order';
        $data['suppliers']   = $this->Supplier_model->get_all_for_select();
        $data['products']    = $this->Product_model->get_all();
        $data['locations']   = $this->db->where('status', 1)->get('locations')->result();
        $data['supplier_id'] = (int)$this->input->get('supplier_id');

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            // Pre-load suggestion if supplier given
            if ($data['supplier_id']) {
                $data['suggestions'] = $this->Purchase_suggestion_model->get_suggestions($data['supplier_id'], 30);
            }
            $data['is_edit'] = false;
            $data['po']      = null;
            $data['items']   = [];
            $this->render('purchase_orders/create', $data);
            return;
        }

        // Save PO
        $items = $this->_collect_items();
        if (empty($items)) {
            $this->session->set_flashdata('error', 'Add at least one item.');
            $this->render('purchase_orders/create', $data);
            return;
        }

        $totals = $this->Purchase_order_model->calc_totals($items);
        $header = [
            'supplier_id'      => (int)$this->input->post('supplier_id'),
            'location_id'      => (int)$this->input->post('location_id') ?: $this->active_location_id(),
            'expected_delivery'=> $this->input->post('expected_delivery', true),
            'delivery_address' => $this->input->post('delivery_address',  true),
            'payment_terms'    => $this->input->post('payment_terms',     true),
            'reference'        => $this->input->post('reference',         true),
            'remarks'          => $this->input->post('remarks',           true),
            'freight_charges'  => (float)$this->input->post('freight_charges'),
            'gross_amount'     => round($totals['gross'], 2),
            'total_discount'   => round($totals['discount'], 2),
            'total_tax'        => round($totals['tax'], 2),
            'net_total'        => round($totals['gross'] - $totals['discount'] + $totals['tax'] + (float)$this->input->post('freight_charges'), 2),
            'status'           => $this->input->post('action') === 'submit' ? 'submitted' : 'draft',
            'buyer_id'         => $this->session->userdata('user_id'),
            'created_by'       => $this->session->userdata('user_id'),
        ];
        $po_id = $this->Purchase_order_model->create($header, $totals['items']);

        if ($this->input->post('action') === 'submit') {
            $this->Purchase_order_model->submit_for_approval($po_id);
        }

        $this->log_activity('purchase_orders', 'create', 'PO created', $po_id);
        $this->session->set_flashdata('success', 'Purchase Order created successfully.');
        redirect("purchase_orders/view/$po_id");
    }

    public function view($id = null) {
        if (!$id) redirect('purchase_orders');
        $data['page_title']   = 'Purchase Order';
        $data['po']           = $this->Purchase_order_model->get_by_id($id);
        if (!$data['po']) { $this->session->set_flashdata('error','PO not found.'); redirect('purchase_orders'); }
        $data['items']        = $this->Purchase_order_model->get_items($id);
        $data['approval_log'] = $this->Purchase_order_model->get_approval_log($id);
        $data['messages']     = $this->db->where('po_id', $id)->order_by('created_at')->get('vendor_messages')->result();
        $data['is_admin']     = $this->is_admin();
        $this->render('purchase_orders/view', $data);
    }

    public function edit($id = null) {
        if (!$id) redirect('purchase_orders');
        $data['page_title'] = 'Edit Purchase Order';
        $data['po']         = $this->Purchase_order_model->get_by_id($id);
        if (!$data['po'] || !in_array($data['po']->status, ['draft','submitted'])) {
            $this->session->set_flashdata('error', 'Only Draft/Submitted POs can be edited.');
            redirect("purchase_orders/view/$id");
        }
        $data['suppliers'] = $this->Supplier_model->get_all_for_select();
        $data['products']  = $this->Product_model->get_all();
        $data['items']     = $this->Purchase_order_model->get_items($id);
        $data['locations'] = $this->db->where('status', 1)->get('locations')->result();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $data['is_edit']     = true;
            $data['suggestions'] = [];
            $this->render('purchase_orders/edit', $data);
            return;
        }

        $items  = $this->_collect_items();
        $totals = $this->Purchase_order_model->calc_totals($items);
        $header = [
            'supplier_id'      => (int)$this->input->post('supplier_id'),
            'location_id'      => (int)$this->input->post('location_id'),
            'expected_delivery'=> $this->input->post('expected_delivery', true),
            'payment_terms'    => $this->input->post('payment_terms',     true),
            'remarks'          => $this->input->post('remarks',           true),
            'freight_charges'  => (float)$this->input->post('freight_charges'),
            'gross_amount'     => round($totals['gross'], 2),
            'total_discount'   => round($totals['discount'], 2),
            'total_tax'        => round($totals['tax'], 2),
            'net_total'        => round($totals['gross'] - $totals['discount'] + $totals['tax'] + (float)$this->input->post('freight_charges'), 2),
            'status'           => $this->input->post('action') === 'submit' ? 'submitted' : 'draft',
        ];
        $this->Purchase_order_model->update($id, $header, $totals['items']);
        if ($this->input->post('action') === 'submit') {
            $this->Purchase_order_model->submit_for_approval($id);
        }
        $this->session->set_flashdata('success', 'PO updated.');
        redirect("purchase_orders/view/$id");
    }

    public function approve($id = null) {
        if (!$id) redirect('purchase_orders');
        $data['page_title']   = 'Approve Purchase Order';
        $data['po']           = $this->Purchase_order_model->get_by_id($id);
        $data['items']        = $this->Purchase_order_model->get_items($id);
        $data['approval_log'] = $this->Purchase_order_model->get_approval_log($id);
        $data['is_admin']     = $this->is_admin();
        $this->render('purchase_orders/approve', $data);
    }

    public function do_approve($id = null) {
        if (!$id) redirect('purchase_orders');
        $action  = $this->input->post('action'); // approve / reject
        $remarks = $this->input->post('remarks', true);
        $user_id = $this->session->userdata('user_id');
        if ($action === 'approve') {
            $this->Purchase_order_model->approve($id, $user_id, $remarks);
            $this->session->set_flashdata('success', 'PO approved.');
        } else {
            $this->Purchase_order_model->reject($id, $user_id, $remarks);
            $this->session->set_flashdata('success', 'PO rejected.');
        }
        redirect("purchase_orders/view/$id");
    }

    public function cancel($id = null) {
        if (!$id) redirect('purchase_orders');
        $this->require_admin();
        $this->Purchase_order_model->update_status($id, 'cancelled');
        $this->session->set_flashdata('success', 'PO cancelled.');
        redirect('purchase_orders');
    }

    public function print_po($id = null) {
        if (!$id) redirect('purchase_orders');
        $data['po']    = $this->Purchase_order_model->get_by_id($id);
        $data['items'] = $this->Purchase_order_model->get_items($id);
        $data['company'] = $this->db->get('company_settings')->result();
        // Render without sidebar
        $this->load->view('purchase_orders/print', $data);
    }

    // AJAX: get products for supplier with suggestions
    public function get_supplier_products_ajax() {
        $supplier_id = (int)$this->input->get('supplier_id');
        $days        = (int)$this->input->get('days') ?: 30;
        $suggestions = $this->Purchase_suggestion_model->get_suggestions($supplier_id, $days);
        $this->output->set_content_type('application/json')->set_output(json_encode($suggestions));
    }

    // Send PO approval request via Email
    public function send_approval_email($id = null) {
        if (!$id) redirect('purchase_orders');
        $po      = $this->Purchase_order_model->get_by_id($id);
        $items   = $this->Purchase_order_model->get_items($id);
        $company = $this->Invoice_model ? $this->Invoice_model->get_company_settings() : [];

        if (!$po) { $this->session->set_flashdata('error', 'PO not found.'); redirect('purchase_orders'); }

        $to_email = trim($this->input->post('approval_email', true));
        if (empty($to_email)) {
            // Try manager/admin email
            $manager = $this->db->select('email, name')->where_in('role',['admin','manager','superadmin'])->where('status',1)->limit(1)->get('users')->row();
            $to_email = $manager->email ?? '';
        }
        if (empty($to_email)) {
            $this->session->set_flashdata('error', 'No approval email configured. Go to PO → Send Email Approval.');
            redirect("purchase_orders/approve/$id");
        }

        $po_url = base_url("purchase_orders/approve/$id");
        $subject = "PO #{$po->po_number} — Approval Required";

        $items_html = '<table border="1" cellpadding="4" style="border-collapse:collapse;width:100%;"><tr style="background:#eee;"><th>#</th><th>Product</th><th>Qty</th><th>Rate</th><th>Total</th></tr>';
        $num = 1;
        foreach ($items as $item) {
            $items_html .= "<tr><td>{$num}</td><td>" . htmlspecialchars($item->product_name ?? '') . "</td><td>{$item->ordered_qty} {$item->unit}</td><td>₹{$item->rate}</td><td>₹" . number_format($item->ordered_qty * $item->rate, 2) . "</td></tr>";
            $num++;
        }
        $items_html .= "<tr><td colspan='4' align='right'><strong>Net Total:</strong></td><td><strong>₹" . number_format($po->net_total, 2) . "</strong></td></tr></table>";

        $message = "
        <h3>Purchase Order Approval Request</h3>
        <p>Dear Approver,</p>
        <p>A new Purchase Order has been submitted for your approval:</p>
        <table cellpadding='4'>
            <tr><td><b>PO Number:</b></td><td>{$po->po_number}</td></tr>
            <tr><td><b>Supplier:</b></td><td>" . htmlspecialchars($po->supplier_name ?? '') . "</td></tr>
            <tr><td><b>Date:</b></td><td>{$po->po_date}</td></tr>
            <tr><td><b>Net Total:</b></td><td>₹" . number_format($po->net_total, 2) . "</td></tr>
            <tr><td><b>Remarks:</b></td><td>" . htmlspecialchars($po->remarks ?? '') . "</td></tr>
        </table>
        <br>$items_html<br>
        <p><a href='$po_url' style='background:#28a745;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px;'>✓ Review & Approve PO</a></p>
        <p style='color:#666;font-size:.85em;'>This email was sent from " . htmlspecialchars($company['company_name'] ?? 'ERP System') . "</p>
        ";

        $this->load->library('email');
        $this->email->initialize([
            'protocol'  => 'smtp',
            'smtp_host' => $company['smtp_host'] ?? 'localhost',
            'smtp_port' => (int)($company['smtp_port'] ?? 587),
            'smtp_user' => $company['smtp_user'] ?? '',
            'smtp_pass' => $company['smtp_pass'] ?? '',
            'mailtype'  => 'html',
        ]);
        $this->email->from($company['smtp_user'] ?? 'noreply@' . $_SERVER['HTTP_HOST'], $company['company_name'] ?? 'ERP');
        $this->email->to($to_email);
        $this->email->subject($subject);
        $this->email->message($message);

        // Log approval request
        $this->db->insert('po_approval_log', [
            'po_id'      => $id,
            'action'     => 'email_sent',
            'user_id'    => $this->session->userdata('user_id'),
            'remarks'    => 'Approval email sent to: ' . $to_email,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->email->send()) {
            $this->session->set_flashdata('success', "Approval email sent to $to_email.");
        } else {
            $this->session->set_flashdata('error', 'Email sending failed. Check SMTP settings.');
        }
        redirect("purchase_orders/view/$id");
    }

    // Send PO approval link via WhatsApp (web.whatsapp.com redirect)
    public function whatsapp_approval($id = null) {
        if (!$id) redirect('purchase_orders');
        $po    = $this->Purchase_order_model->get_by_id($id);
        $phone = trim($this->input->post('wa_phone', true));
        if (empty($phone)) {
            $this->session->set_flashdata('error', 'WhatsApp number required.');
            redirect("purchase_orders/approve/$id");
        }
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) === 10) $phone = '91' . $phone;

        $approve_url = base_url("purchase_orders/approve/$id");
        $msg = "Approval Required: PO#{$po->po_number} — " . htmlspecialchars($po->supplier_name ?? '') . " — ₹" . number_format($po->net_total, 2) . ". Review here: $approve_url";

        $this->db->insert('po_approval_log', [
            'po_id'      => $id,
            'action'     => 'whatsapp_sent',
            'user_id'    => $this->session->userdata('user_id'),
            'remarks'    => 'WhatsApp approval link sent to: +' . $phone,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $wa_url = 'https://wa.me/' . $phone . '?text=' . urlencode($msg);
        redirect($wa_url);
    }

    // Send message on PO
    public function send_message_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $po_id       = (int)$this->input->post('po_id');
        $supplier_id = (int)$this->input->post('supplier_id');
        $msg         = $this->input->post('message', true);
        $this->db->insert('vendor_messages', [
            'supplier_id' => $supplier_id, 'po_id' => $po_id,
            'sender_type' => 'buyer', 'sender_id' => $this->session->userdata('user_id'),
            'message' => $msg, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true]));
    }

    private function _collect_items() {
        $pids    = $this->input->post('product_id');
        $qtys    = $this->input->post('ordered_qty');
        $rates   = $this->input->post('rate');
        $discs   = $this->input->post('discount_pct');
        $gsts    = $this->input->post('gst_pct');
        $sugg    = $this->input->post('suggested_qty');
        $stocks  = $this->input->post('current_stock');
        $avgsales= $this->input->post('avg_daily_sales');
        $units   = $this->input->post('unit');
        $items = [];
        if (!is_array($pids)) return $items;
        foreach ($pids as $i => $pid) {
            $qty = (float)($qtys[$i] ?? 0);
            if (!$pid || $qty <= 0) continue;
            $items[] = [
                'product_id'      => (int)$pid,
                'ordered_qty'     => $qty,
                'rate'            => (float)($rates[$i]  ?? 0),
                'discount_pct'    => (float)($discs[$i]  ?? 0),
                'gst_pct'         => (float)($gsts[$i]   ?? 0),
                'suggested_qty'   => (float)($sugg[$i]   ?? 0),
                'current_stock'   => (float)($stocks[$i] ?? 0),
                'avg_daily_sales' => (float)($avgsales[$i] ?? 0),
                'unit'            => $units[$i] ?? 'PCS',
            ];
        }
        return $items;
    }
}
