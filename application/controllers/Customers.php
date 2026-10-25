<?php
// ============================================================
// FILE: application/controllers/Customers.php
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Customer_model');
        $this->load->library('form_validation');
    }

    public function index() {
        $data['page_title'] = 'Customers';
        $data['customers']  = $this->Customer_model->get_all();
        $this->render('customers/index', $data);
    }

    public function add() {
        $data['page_title'] = 'Add Customer';
        $this->_set_rules();
        if ($this->form_validation->run() === FALSE) {
            $this->render('customers/add', $data);
            return;
        }
        $post = $this->_collect_post();
        // Opening balance (old-system dues) seeds the running balance for a brand-new customer
        $post['balance'] = $post['opening_balance'];
        $new_id = $this->Customer_model->create($post);
        $ob_note = $post['opening_balance'] > 0 ? ' | Opening balance: ₹'.number_format($post['opening_balance'],2) : '';
        $this->log_activity('customers', 'create', 'Added customer "'.$post['name'].'" | Phone: '.($post['phone'] ?? '').($post['gst_number'] ? ' | GSTIN: '.$post['gst_number'] : '').$ob_note, $new_id ?? 0, $post['phone'] ?? '');
        $this->session->set_flashdata('success', 'Customer added successfully.');
        redirect('customers');
    }

    public function edit($id = null) {
        if (!$id) redirect('customers');
        $data['page_title'] = 'Edit Customer';
        $data['customer']   = $this->Customer_model->get_by_id($id);
        if (!$data['customer']) { $this->session->set_flashdata('error','Customer not found.'); redirect('customers'); }

        $this->_set_rules();
        if ($this->form_validation->run() === FALSE) {
            $this->render('customers/edit', $data);
            return;
        }
        $post = $this->_collect_post($data['customer']->aadhaar_number ?? null);
        $old  = $data['customer'];
        $this->Customer_model->update($id, $post);
        // Opening balance can be corrected after entry (e.g. old-dues cleanup) — shift the
        // running balance by the delta so invoices/payments recorded since aren't wiped out
        $old_opening = (float)($old->opening_balance ?? 0);
        $new_opening = (float)($post['opening_balance'] ?? 0);
        if ($new_opening !== $old_opening) {
            $this->Customer_model->increase_balance($id, $new_opening - $old_opening);
        }
        $changes = [];
        if (($post['name']  ?? '') !== $old->name)  $changes[] = 'Name: '.$old->name.' → '.($post['name'] ?? '');
        if (($post['phone'] ?? '') !== $old->phone) $changes[] = 'Phone: '.$old->phone.' → '.($post['phone'] ?? '');
        if (($post['state'] ?? '') !== $old->state) $changes[] = 'State: '.$old->state.' → '.($post['state'] ?? '');
        if ($new_opening !== $old_opening) $changes[] = 'Opening Balance: ₹'.number_format($old_opening,2).' → ₹'.number_format($new_opening,2);
        // Closing a customer's loyalty stops them earning and redeeming — audit it.
        if (isset($post['loyalty_member']) && property_exists($old, 'loyalty_member')
            && (int)$post['loyalty_member'] !== (int)$old->loyalty_member) {
            $changes[] = 'Loyalty: ' . ((int)$old->loyalty_member === 1 ? 'ALLOWED' : 'CLOSED')
                       . ' → ' . ((int)$post['loyalty_member'] === 1 ? 'ALLOWED' : 'CLOSED')
                       . ' (balance ' . number_format((float)($old->loyalty_points ?? 0)) . ' pts kept)';
        }
        $desc = 'Updated customer "'.$old->name.'" (ID #'.$id.')'.($changes ? ' | '.implode(', ',$changes) : '');
        $this->log_activity('customers', 'edit', $desc, $id, $old->phone ?? '');
        $this->session->set_flashdata('success', 'Customer updated.');
        redirect('customers');
    }

    public function delete($id = null) {
        $this->require_admin();
        $cust = $this->Customer_model->get_by_id($id);
        $this->Customer_model->delete($id);
        $this->log_activity('customers', 'delete', 'Deleted customer "'.($cust->name ?? '?').'" (ID #'.$id.')', $id, $cust->phone ?? '');
        $this->session->set_flashdata('success', 'Customer deleted.');
        redirect('customers');
    }

    // Ledger: customer payment history
    public function ledger($id = null) {
        if (!$id) redirect('customers');
        $data['page_title'] = 'Customer Ledger';
        $data['customer']   = $this->Customer_model->get_by_id($id);
        if (!$data['customer']) { redirect('customers'); }
        $this->load->model(['Invoice_model','Payment_model']);
        $invoices = $this->Invoice_model->get_by_customer($id);
        $payments = $this->Payment_model->get_by_party('customer', $id);
        $ledger = [];
        foreach ($invoices as $inv) {
            if ($inv->status === 'cancelled') continue;
            $ledger[] = ['date'=>$inv->invoice_date,'type'=>'invoice','reference'=>$inv->invoice_no,'id'=>$inv->id,'debit'=>$inv->total_amount,'credit'=>0,'note'=>ucfirst($inv->invoice_type).' invoice'];
        }
        foreach ($payments as $pay) {
            $ledger[] = ['date'=>$pay->payment_date,'type'=>'payment','reference'=>$pay->payment_no,'id'=>$pay->id,'debit'=>0,'credit'=>$pay->amount,'note'=>ucfirst($pay->payment_mode).($pay->reference_no?' #'.$pay->reference_no:'')];
        }
        usort($ledger, function($a,$b){ return strcmp($a['date'],$b['date']); });
        $opening_balance = (float)($data['customer']->opening_balance ?? 0);
        $running = $opening_balance;
        foreach ($ledger as &$e) { $running += $e['debit'] - $e['credit']; $e['balance'] = $running; }
        $data['ledger']   = $ledger;
        $data['invoices'] = $invoices;
        $data['payments'] = $payments;
        $data['opening_balance'] = $opening_balance;
        $data['summary']  = ['total_invoiced'=>array_sum(array_column($ledger,'debit')),'total_paid'=>array_sum(array_column($ledger,'credit')),'balance'=>$running];

        // WhatsApp Marketing is vendor-controlled (see header.php's $__wa_visible) —
        // role='superadmin' alone must not surface this, only the literal
        // system-owner account or a user explicitly granted the module.
        $is_system_owner = (int)$this->session->userdata('user_id') === 9999;
        $module_perms = $this->session->userdata('module_perms') ?? [];
        $data['show_wa_activity'] = $is_system_owner || !empty($module_perms['mod_whatsapp_marketing']);
        if ($data['show_wa_activity']) {
            $this->load->model('Marketing_model');
            $data['wa_activity'] = $this->Marketing_model->get_customer_wa_activity($id);
        }

        // EV dealership owned vehicles & active warranties
        $data['vehicles'] = $this->db->select('s.*, p.name as product_name, p.model_name, p.model_number, p.colour as default_colour, i.invoice_no, i.invoice_date')
            ->from('item_serials s')
            ->join('products p', 'p.id = s.product_id', 'left')
            ->join('invoices i', 'i.id = s.invoice_id', 'left')
            ->where('s.customer_id', $id)
            ->where('s.item_type', 'scooter')
            ->order_by('s.id', 'DESC')
            ->get()->result();

        $data['warranties'] = $this->db->select('w.*, s.serial_no, s.chassis_no, s.item_type, p.name as product_name, i.invoice_no')
            ->from('serial_warranties w')
            ->join('item_serials s', 's.id = w.serial_id', 'left')
            ->join('products p', 'p.id = w.product_id', 'left')
            ->join('invoices i', 'i.id = w.invoice_id', 'left')
            ->where('w.customer_id', $id)
            ->order_by('w.id', 'DESC')
            ->get()->result();

        $this->render('customers/ledger', $data);
    }

    // AJAX: get customer info for billing page
    public function get_ajax($id = null) {
        if (!$id || !$this->input->is_ajax_request()) show_404();
        $customer = $this->Customer_model->get_by_id($id);
        echo json_encode($customer);
    }

    private function _set_rules() {
        $this->form_validation->set_rules('name',         'Customer Name', 'required|trim|max_length[150]');
        $this->form_validation->set_rules('phone',        'Phone',         'required|trim|max_length[15]');
        $this->form_validation->set_rules('type',         'Type',          'required|in_list[retail,wholesale]');
        $this->form_validation->set_rules('credit_limit', 'Credit Limit',  'decimal');
        $this->form_validation->set_rules('opening_balance', 'Opening Balance', 'decimal');
        $this->form_validation->set_rules('gst_number',   'GSTIN',         'trim|max_length[20]');
        $this->form_validation->set_rules('pan',          'PAN Number',    'trim|max_length[15]');
        $this->form_validation->set_rules('aadhaar_number','Aadhaar Number','trim|max_length[20]');
        $this->form_validation->set_rules('id_proof_type','ID Proof Type', 'trim|max_length[50]');
        $this->form_validation->set_rules('id_proof_doc', 'Document Ref',  'trim|max_length[100]');
    }

    private function _collect_post($existing_aadhaar = null) {
        return [
            'name'         => $this->input->post('name',         TRUE),
            'phone'        => $this->input->post('phone',        TRUE),
            'email'        => $this->input->post('email',        TRUE),
            'address'      => $this->input->post('address',      TRUE),
            'city'         => $this->input->post('city',         TRUE),
            'state'        => $this->input->post('state',        TRUE),
            'pincode'      => $this->input->post('pincode',      TRUE),
            'type'         => $this->input->post('type',         TRUE),
            'gst_number'   => strtoupper($this->input->post('gst_number', TRUE)),
            'credit_limit'              => $this->input->post('credit_limit', TRUE) ?: 0,
            'opening_balance'           => (float)($this->input->post('opening_balance', TRUE) ?: 0),
            'show_outstanding_in_print' => $this->input->post('show_outstanding_in_print') ? 1 : 0,
            'einvoice_required'         => $this->input->post('einvoice_required') ? 1 : 0,
            'portal_active'             => $this->input->post('portal_active') ? 1 : 0,
            'status'                    => 1,
        ] + $this->_collect_discount_post() + $this->_collect_loyalty_post() + $this->_collect_ev_customer_post($existing_aadhaar);
    }

    private function _collect_ev_customer_post($existing_aadhaar = null) {
        $data = [];
        if ($this->db->field_exists('pan', 'customers')) {
            $data['pan'] = strtoupper(trim($this->input->post('pan', TRUE) ?: ''));
        }
        if ($this->db->field_exists('aadhaar_number', 'customers')) {
            $raw = trim((string)$this->input->post('aadhaar_number', TRUE));
            if (strpos($raw, 'XXXX') !== false) {
                if ($existing_aadhaar) {
                    $data['aadhaar_number'] = $existing_aadhaar;
                }
            } else {
                $digits = preg_replace('/\D/', '', $raw);
                $data['aadhaar_number'] = $digits ?: null;
            }
        }
        if ($this->db->field_exists('id_proof_type', 'customers')) {
            $data['id_proof_type'] = trim($this->input->post('id_proof_type', TRUE) ?: '');
        }
        if ($this->db->field_exists('id_proof_doc', 'customers')) {
            $data['id_proof_doc'] = trim($this->input->post('id_proof_doc', TRUE) ?: '');
        }
        return $data;
    }

    private function _collect_discount_post() {
        if (!$this->db->field_exists('discount_percent', 'customers')) return [];
        return ['discount_percent' => max(0, min(100, (float)($this->input->post('discount_percent', TRUE) ?: 0)))];
    }

    /**
     * Loyalty eligibility for this customer. Defaults to ALLOWED — the form
     * ships the box ticked, so a new customer earns points unless someone
     * deliberately turns it off.
     *
     * Guarded because sql/45_loyalty.sql is per-database.
     */
    private function _collect_loyalty_post() {
        if (!$this->db->field_exists('loyalty_member', 'customers')) return [];
        $allow = $this->input->post('loyalty_member') ? 1 : 0;
        $out = [
            'loyalty_member' => $allow,
            // A closed account keeps its balance and history; it simply stops
            // earning and redeeming, so it can be re-opened without data loss.
            'loyalty_status' => $allow ? 'active' : 'closed',
        ];
        // Stamp the enrolment date once, when the customer is created.
        if ($allow && $this->router->fetch_method() === 'add') {
            $out['loyalty_enrolled_at'] = date('Y-m-d H:i:s');
        }
        return $out;
    }

    // Wallet Ledger History
    public function wallet_history($id = null) {
        if (!$id) redirect('customers');
        $data['customer'] = $this->Customer_model->get_by_id($id);
        if (!$data['customer']) {
            $this->session->set_flashdata('error', 'Customer not found.');
            redirect('customers');
        }
        $data['page_title'] = 'Wallet Ledger: ' . $data['customer']->name;
        
        // Fetch transactions
        $data['transactions'] = $this->db->select('t.*, u.name as user_name')
                                         ->from('customer_wallet_transactions t')
                                         ->join('users u', 'u.id = t.created_by', 'left')
                                         ->where('t.customer_id', $id)
                                         ->order_by('t.created_at', 'DESC')
                                         ->get()->result();
        
        $this->render('customers/wallet_history', $data);
    }

    // Manual Wallet Adjustment
    public function adjust_wallet($id = null) {
        if (!$id) redirect('customers');
        
        $role = $this->session->userdata('role');
        if ($role !== 'superadmin' && $role !== 'admin' && $role !== 'manager') {
            $this->session->set_flashdata('error', 'Access denied: You are not authorized to adjust wallet balance.');
            redirect('customers/wallet_history/' . $id);
        }

        $amount = (float)$this->input->post('amount', TRUE);
        $type = $this->input->post('type', TRUE); // 'credit' or 'debit'
        $remarks = $this->input->post('remarks', TRUE) ?: 'Manual adjustment';

        if ($amount <= 0) {
            $this->session->set_flashdata('error', 'Amount must be greater than zero.');
            redirect('customers/wallet_history/' . $id);
        }

        if (!in_array($type, ['credit', 'debit'])) {
            $this->session->set_flashdata('error', 'Invalid adjustment type.');
            redirect('customers/wallet_history/' . $id);
        }

        $this->db->trans_start();
        $success = $this->Customer_model->adjust_wallet(
            $id,
            $amount,
            $type,
            'manual_adjustment',
            null,
            null,
            $remarks,
            $this->session->userdata('user_id')
        );
        $this->db->trans_complete();

        if ($success && $this->db->trans_status() !== FALSE) {
            $cust = $this->Customer_model->get_by_id($id);
            $this->log_activity('customers', 'wallet_adjust', strtoupper($type).' Rs.'.$amount.' wallet for "'.($cust->name ?? '?').'" | Reason: '.$remarks, $id, $cust->phone ?? '');
            $this->session->set_flashdata('success', 'Wallet adjusted successfully.');
        } else {
            $this->session->set_flashdata('error', 'Wallet adjustment failed. Balance cannot go negative.');
        }
        redirect('customers/wallet_history/' . $id);
    }

    // AJAX: update mobile number without creating a bill
    public function update_mobile_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $id    = (int)$this->input->post('customer_id', TRUE);
        $phone = trim($this->input->post('phone', TRUE));
        if (!$id || empty($phone)) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'Customer ID and phone are required.']));
        }
        $this->db->where('id', $id)->update('customers', ['phone' => $phone]);
        $customer = $this->Customer_model->get_by_id($id);
        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'customer' => $customer]));
    }

    // AJAX: quick mobile lookup — search by phone across customers
    public function mobile_lookup_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $phone = trim($this->input->post('phone', TRUE));
        if (empty($phone)) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'Phone required.']));
        }
        $customers = $this->db->like('phone', $phone)->limit(10)->get('customers')->result();
        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'customers' => $customers]));
    }

    // AJAX quick-add from billing page
    public function quick_add_ajax() {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(403)->set_output('Forbidden');
        }
        $name = trim($this->input->post('name', TRUE));
        if (empty($name)) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'Customer name is required.']));
        }
        $data = [
            'name'            => $name,
            'phone'           => trim($this->input->post('phone',   TRUE)),
            'address'         => trim($this->input->post('address', TRUE)),
            'gst_number'      => strtoupper(trim($this->input->post('gstin', TRUE))),
            'type'            => 'retail',
            'credit_limit'    => 0,
            'status'          => 1,
        ];
        $id = $this->Customer_model->create($data);
        if (!$id) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'Database insert failed.']));
        }
        $opening = (float)$this->input->post('opening_balance', TRUE);
        if ($opening > 0) {
            $this->db->insert('customer_payments', [
                'customer_id' => $id,
                'amount'      => $opening,
                'payment_date'=> date('Y-m-d'),
                'payment_mode'=> 'opening',
                'note'        => 'Opening balance',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
        $customer = ['id' => (int)$id, 'name' => $name, 'phone' => $data['phone']];
        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'customer' => $customer]));
    }

    // ── Excel / CSV bulk import (name, phone, type, gst, opening balance, etc.) ──
    public function import() {
        $data['page_title'] = 'Import Customers';
        if ($this->input->method() !== 'post') {
            $this->render('customers/import', $data);
            return;
        }

        if (empty($_FILES['import_file']['name'])) {
            $this->session->set_flashdata('error', 'Please choose a file to upload.');
            redirect('customers/import');
        }

        $tmp   = $_FILES['import_file']['tmp_name'];
        $fname = strtolower($_FILES['import_file']['name']);
        $errors = [];

        if (substr($fname, -4) === '.csv') {
            $rows = $this->_parse_csv_simple($tmp);
        } elseif (substr($fname, -5) === '.xlsx' || substr($fname, -4) === '.xls') {
            $rows = $this->_parse_xlsx_simple($tmp, $errors);
        } else {
            $this->session->set_flashdata('error', 'Unsupported file type. Please upload a .xlsx or .csv file.');
            redirect('customers/import');
        }

        if (!empty($errors)) {
            $this->session->set_flashdata('error', implode(' ', $errors));
            redirect('customers/import');
        }
        if (count($rows) < 2) {
            $this->session->set_flashdata('error', 'No data rows found in the file.');
            redirect('customers/import');
        }

        // Detect columns from the header row (flexible order)
        $header = array_map(function ($h) { return strtolower(trim((string)$h)); }, $rows[0]);
        $col = ['name' => -1, 'phone' => -1, 'email' => -1, 'gst' => -1, 'type' => -1,
                'address' => -1, 'city' => -1, 'state' => -1, 'pincode' => -1,
                'credit_limit' => -1, 'opening_balance' => -1];
        foreach ($header as $i => $h) {
            if ($col['name'] < 0 && strpos($h, 'name') !== false)                                      $col['name'] = $i;
            elseif ($col['phone'] < 0 && (strpos($h, 'phone') !== false || strpos($h, 'mobile') !== false || strpos($h, 'contact') !== false)) $col['phone'] = $i;
            elseif ($col['email'] < 0 && strpos($h, 'email') !== false)                                 $col['email'] = $i;
            elseif ($col['gst'] < 0 && strpos($h, 'gst') !== false)                                     $col['gst'] = $i;
            elseif ($col['type'] < 0 && strpos($h, 'type') !== false)                                   $col['type'] = $i;
            elseif ($col['pincode'] < 0 && (strpos($h, 'pincode') !== false || strpos($h, 'pin code') !== false || strpos($h, 'zip') !== false)) $col['pincode'] = $i;
            elseif ($col['city'] < 0 && strpos($h, 'city') !== false)                                   $col['city'] = $i;
            elseif ($col['state'] < 0 && strpos($h, 'state') !== false)                                 $col['state'] = $i;
            elseif ($col['address'] < 0 && strpos($h, 'address') !== false)                             $col['address'] = $i;
            elseif ($col['credit_limit'] < 0 && strpos($h, 'credit') !== false)                         $col['credit_limit'] = $i;
            elseif ($col['opening_balance'] < 0 && (strpos($h, 'opening') !== false || strpos($h, 'due') !== false || strpos($h, 'balance') !== false)) $col['opening_balance'] = $i;
        }
        if ($col['name'] < 0) {
            $this->session->set_flashdata('error', 'Could not find a "name" column in the header row. Please use the template.');
            redirect('customers/import');
        }

        $inserted = 0; $skipped = 0; $total_opening = 0;
        for ($r = 1; $r < count($rows); $r++) {
            $row   = $rows[$r];
            $cname = trim((string)($row[$col['name']] ?? ''));
            if ($cname === '') continue; // ignore blank rows

            $phone  = $col['phone']  >= 0 ? trim((string)($row[$col['phone']]  ?? '')) : '';
            $email  = $col['email']  >= 0 ? trim((string)($row[$col['email']]  ?? '')) : '';
            $gst    = $col['gst']    >= 0 ? strtoupper(trim((string)($row[$col['gst']] ?? ''))) : '';
            $type   = $col['type']   >= 0 ? strtolower(trim((string)($row[$col['type']] ?? ''))) : 'retail';
            if (!in_array($type, ['retail', 'wholesale'])) $type = 'retail';
            $address = $col['address'] >= 0 ? trim((string)($row[$col['address']] ?? '')) : '';
            $city    = $col['city']    >= 0 ? trim((string)($row[$col['city']]    ?? '')) : '';
            $state   = $col['state']   >= 0 ? trim((string)($row[$col['state']]   ?? '')) : '';
            $pincode = $col['pincode'] >= 0 ? trim((string)($row[$col['pincode']] ?? '')) : '';
            $credit_limit    = $col['credit_limit']    >= 0 ? (float)($row[$col['credit_limit']]    ?? 0) : 0;
            $opening_balance = $col['opening_balance'] >= 0 ? (float)($row[$col['opening_balance']] ?? 0) : 0;

            // Skip duplicates: same phone (if given), else same name — both among active customers
            $dup = null;
            if ($phone !== '') {
                $dup = $this->db->where('phone', $phone)->where('status', 1)->get('customers')->row();
            }
            if (!$dup) {
                $dup = $this->db->query(
                    "SELECT id FROM customers WHERE LOWER(name) = ? AND status = 1 LIMIT 1",
                    [strtolower($cname)]
                )->row();
            }
            if ($dup) { $skipped++; continue; }

            $this->Customer_model->create([
                'name'            => $cname,
                'phone'           => $phone,
                'email'           => $email,
                'address'         => $address,
                'city'            => $city,
                'state'           => $state,
                'pincode'         => $pincode,
                'type'            => $type,
                'gst_number'      => $gst,
                'credit_limit'    => $credit_limit,
                'opening_balance' => $opening_balance,
                'balance'         => $opening_balance,
                // Any customer carrying an old-system due should see it printed on their next bill
                'show_outstanding_in_print' => $opening_balance > 0 ? 1 : 0,
                'status'          => 1,
            ]);
            $inserted++;
            $total_opening += $opening_balance;
        }

        $msg = "Import complete: {$inserted} customer(s) added.";
        if ($total_opening > 0) $msg .= ' Total opening dues carried over: ₹'.number_format($total_opening, 2).'.';
        if ($skipped) $msg .= " {$skipped} duplicate (existing phone/name) skipped.";
        $this->log_activity('customers', 'import', $msg, 0, '');
        $this->session->set_flashdata('success', $msg);
        redirect('customers');
    }

    // Download a ready-to-fill CSV template
    public function import_template() {
        $headers = ['name', 'phone', 'email', 'type', 'gst_number', 'address', 'city', 'state', 'pincode', 'credit_limit', 'opening_balance'];
        $sample  = ['Ramesh Kumar', '9876543210', '', 'retail', '', '', 'Nishamart', 'Punjab', '', '0', '1500.00'];
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="customer_import_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        fputcsv($out, $sample);
        fclose($out);
        exit;
    }

    private function _parse_csv_simple($path) {
        $rows = [];
        if (($fh = fopen($path, 'r')) !== FALSE) {
            while (($row = fgetcsv($fh)) !== FALSE) { $rows[] = $row; }
            fclose($fh);
        }
        return $rows;
    }

    private function _parse_xlsx_simple($path, &$errors) {
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            $autoload = APPPATH . '../vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            } else {
                $errors[] = 'Excel library not installed on server. Please upload a .csv file instead.';
                return [];
            }
        }
        $rows = [];
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $sheet       = $spreadsheet->getActiveSheet();
            // toArray keeps columns aligned even when some cells are blank
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Exception $e) {
            $errors[] = 'Excel read error: ' . $e->getMessage();
        }
        return $rows;
    }

    public function migrate_discount_col() {
        $this->require_admin();
        if ($this->db->field_exists('discount_percent', 'customers')) {
            echo 'Column discount_percent already exists.'; return;
        }
        $this->db->query("ALTER TABLE `customers` ADD COLUMN `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `credit_limit`");
        echo 'Done — discount_percent column added to customers.';
    }
}
