<?php
// ============================================================
// FILE: application/controllers/ArmyQuotations.php
// Army Quotation Management — fully independent module.
// Does not touch any existing billing/invoice/estimate table or logic.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class ArmyQuotations extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Army_quotation_model');
        $this->load->model('Army_company_model');
        $this->load->model('Army_terms_model');
        if (!$this->is_admin()) {
            $this->session->set_flashdata('error', 'Permission denied.');
            redirect('dashboard');
        }
    }

    public function index() {
        $filters = [
            'quotation_no'  => $this->input->get('quotation_no', TRUE),
            'customer_name' => $this->input->get('customer_name', TRUE),
            'company_id'    => $this->input->get('company_id', TRUE),
            'mobile'        => $this->input->get('mobile', TRUE),
            'status'        => $this->input->get('status', TRUE),
            'from'          => $this->input->get('from', TRUE),
            'to'            => $this->input->get('to', TRUE),
        ];
        $data['page_title']  = 'Army Quotations';
        $data['filters']     = $filters;
        $data['quotations']  = $this->Army_quotation_model->search($filters);
        $data['companies']   = $this->Army_company_model->get_active();
        $this->render('army/quotations/index', $data);
    }

    public function create() {
        $data['page_title'] = 'New Army Quotation';
        $data['quotation']  = null;
        $data['items']      = [];
        $data['companies']  = $this->Army_company_model->get_active();
        $data['default_terms'] = $this->Army_terms_model->get_default();

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $result = $this->_save(null);
            if (isset($result['error'])) {
                $this->session->set_flashdata('error', $result['error']);
                $this->render('army/quotations/form', $data);
                return;
            }
            $this->session->set_flashdata('success', 'Quotation created successfully.');
            redirect('army_quotations/view/' . $result['id']);
        }

        $this->render('army/quotations/form', $data);
    }

    public function edit($id = null) {
        if (!$id) redirect('army_quotations');
        $quotation = $this->Army_quotation_model->get_by_id($id);
        if (!$quotation) {
            $this->session->set_flashdata('error', 'Quotation not found.');
            redirect('army_quotations');
        }
        $data['page_title'] = 'Edit Quotation #' . $quotation->quotation_no;
        $data['quotation']  = $quotation;
        $data['items']      = $this->Army_quotation_model->get_items($id);
        $data['companies']  = $this->Army_company_model->get_active();
        $data['default_terms'] = $quotation->terms_text;

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $result = $this->_save($id);
            if (isset($result['error'])) {
                $this->session->set_flashdata('error', $result['error']);
                $this->render('army/quotations/form', $data);
                return;
            }
            $this->session->set_flashdata('success', 'Quotation updated successfully.');
            redirect('army_quotations/view/' . $result['id']);
        }

        $this->render('army/quotations/form', $data);
    }

    // Shared create/update handler. Returns ['id' => quotation_id] on success or
    // ['error' => message] on failure — an explicit shape rather than branching on
    // is_string()/truthiness, since a successful edit's id can itself be a numeric
    // string (URL segments are always strings in CI3), which made is_string()
    // misclassify a successful save as an error.
    private function _save($id) {
        $company_id    = (int)$this->input->post('company_id', TRUE);
        $customer_name = $this->input->post('customer_name', TRUE);
        $particulars   = $this->input->post('particulars');
        $qtys          = $this->input->post('qty');
        $units         = $this->input->post('unit');
        $rates         = $this->input->post('rate');
        $gst_percent   = (float)$this->input->post('gst_percent', TRUE);

        if (!$company_id) return ['error' => 'Company is required.'];
        if (empty($customer_name)) return ['error' => 'Customer Name is required.'];
        if ($gst_percent < 0) return ['error' => 'GST % cannot be negative.'];
        if (empty($particulars) || !is_array($particulars)) return ['error' => 'At least one quotation item is required.'];

        $items = [];
        $sub_total = 0;
        foreach ($particulars as $k => $p) {
            $p = trim($p);
            if ($p === '') continue;
            $qty  = (float)($qtys[$k] ?? 0);
            $unit = trim($units[$k] ?? '') ?: 'Nos';
            $rate = (float)($rates[$k] ?? 0);
            if ($qty == 0)  return ['error' => 'Qty cannot be zero (row: ' . htmlspecialchars($p) . ').'];
            if ($rate == 0) return ['error' => 'Rate cannot be zero (row: ' . htmlspecialchars($p) . ').'];
            $amount = round($qty * $rate, 2);
            $sub_total += $amount;
            $items[] = ['particulars' => $p, 'qty' => $qty, 'unit' => $unit, 'rate' => $rate, 'amount' => $amount];
        }
        if (empty($items)) return ['error' => 'At least one quotation item is required.'];

        $sub_total   = round($sub_total, 2);
        $gst_amount  = round($sub_total * $gst_percent / 100, 2);
        $grand_total = round($sub_total + $gst_amount, 2);

        $header = [
            'quotation_date' => $this->input->post('quotation_date', TRUE) ?: date('Y-m-d'),
            'valid_till'     => $this->input->post('valid_till', TRUE) ?: null,
            'company_id'     => $company_id,
            'customer_name'  => $customer_name,
            'contact_person' => $this->input->post('contact_person', TRUE),
            'address'        => $this->input->post('address', FALSE), // preserve raw line breaks
            'mobile'         => $this->input->post('mobile', TRUE),
            'email'          => $this->input->post('email', TRUE),
            'subject'        => $this->input->post('subject', TRUE),
            'reference'      => $this->input->post('reference', TRUE),
            'prepared_by'    => $this->input->post('prepared_by', TRUE),
            'sub_total'      => $sub_total,
            'gst_percent'    => $gst_percent,
            'gst_amount'     => $gst_amount,
            'grand_total'    => $grand_total,
            'terms_text'     => $this->input->post('terms_text', FALSE), // preserve raw line breaks
        ];

        if ($id) {
            $ok = $this->Army_quotation_model->update($id, $header, $items);
            return $ok ? ['id' => (int)$id] : ['error' => 'Save failed — please try again.'];
        }

        $header['quotation_no'] = $this->Army_quotation_model->generate_quotation_no();
        $header['status']       = 'draft';
        $header['created_by']   = $this->session->userdata('user_id');
        $new_id = $this->Army_quotation_model->create($header, $items);
        return $new_id ? ['id' => (int)$new_id] : ['error' => 'Save failed — please try again.'];
    }

    public function view($id = null) {
        if (!$id) redirect('army_quotations');
        $quotation = $this->Army_quotation_model->get_by_id($id);
        if (!$quotation) {
            $this->session->set_flashdata('error', 'Quotation not found.');
            redirect('army_quotations');
        }
        $data['page_title'] = 'Quotation #' . $quotation->quotation_no;
        $data['quotation']  = $quotation;
        $data['items']      = $this->Army_quotation_model->get_items($id);
        $this->render('army/quotations/view', $data);
    }

    public function duplicate($id = null) {
        if (!$id) redirect('army_quotations');
        $new_id = $this->Army_quotation_model->duplicate($id);
        if (!$new_id) {
            $this->session->set_flashdata('error', 'Could not duplicate — original quotation not found.');
            redirect('army_quotations');
        }
        $this->session->set_flashdata('success', 'Quotation duplicated as a new draft.');
        redirect('army_quotations/view/' . $new_id);
    }

    public function delete($id = null) {
        if (!$id) redirect('army_quotations');
        $this->Army_quotation_model->delete($id);
        $this->session->set_flashdata('success', 'Quotation deleted.');
        redirect('army_quotations');
    }

    public function pdf($id = null) {
        if (!$id) redirect('army_quotations');
        $quotation = $this->Army_quotation_model->get_by_id($id);
        if (!$quotation) show_404();
        $data['quotation'] = $quotation;
        $data['items']     = $this->Army_quotation_model->get_items($id);

        $this->load->library('pdf_generator');
        $html     = $this->load->view('army/quotations/pdf_template', $data, TRUE);
        $filename = 'Quotation_' . $quotation->quotation_no;
        // Force A4 regardless of the store's POS receipt-printer setting.
        $this->pdf_generator->generate($html, $filename, 'download', 'A4');
    }

    public function print_view($id = null) {
        if (!$id) redirect('army_quotations');
        $quotation = $this->Army_quotation_model->get_by_id($id);
        if (!$quotation) show_404();
        $data['quotation'] = $quotation;
        $data['items']     = $this->Army_quotation_model->get_items($id);
        $this->load->view('army/quotations/print', $data);
    }

    public function send_email($id = null) {
        if (!$id) redirect('army_quotations');
        $quotation = $this->Army_quotation_model->get_by_id($id);
        if (!$quotation) show_404();

        $to_email  = $this->input->post('to_email', TRUE);
        $cc_email  = $this->input->post('cc_email', TRUE);
        $bcc_email = $this->input->post('bcc_email', TRUE);
        $subject   = $this->input->post('email_subject', TRUE) ?: ('Quotation ' . $quotation->quotation_no);
        $message   = $this->input->post('email_message', FALSE) ?: '';

        if (!$to_email || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Please enter a valid "To" email address.');
            redirect('army_quotations/view/' . $id);
        }

        $items = $this->Army_quotation_model->get_items($id);

        // Generate the PDF fresh at send time (not stored) and attach it.
        $pdf_tmp_path = null;
        $pdf_filename = 'Quotation_' . $quotation->quotation_no . '.pdf';
        try {
            $this->load->library('pdf_generator');
            $pdf_html   = $this->load->view('army/quotations/pdf_template', ['quotation' => $quotation, 'items' => $items], TRUE);
            $pdf_string = $this->pdf_generator->as_string($pdf_html, 'Quotation_' . $quotation->quotation_no);
            $pdf_tmp_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $pdf_filename;
            file_put_contents($pdf_tmp_path, $pdf_string);
        } catch (\Exception $e) {
            $pdf_tmp_path = null; // send without attachment if PDF generation fails
        }

        // SMTP transport comes from the main app's single company_settings account —
        // Army_CompanyMaster doesn't keep its own SMTP config. The "From" identity
        // still uses the quotation's own company name/email where available.
        $this->load->library('email');
        $this->email->initialize([
            'protocol'    => 'smtp',
            'smtp_host'   => $this->db->where('key_name', 'smtp_host')->get('company_settings')->row()->key_value ?? 'smtp.gmail.com',
            'smtp_port'   => 587,
            'smtp_user'   => $this->db->where('key_name', 'smtp_user')->get('company_settings')->row()->key_value ?? '',
            'smtp_pass'   => $this->db->where('key_name', 'smtp_pass')->get('company_settings')->row()->key_value ?? '',
            'smtp_crypto' => 'tls',
            'mailtype'    => 'html',
            'charset'     => 'utf-8',
        ]);
        $from_email = $quotation->company_email ?: ($this->db->where('key_name', 'smtp_user')->get('company_settings')->row()->key_value ?? 'noreply@erp.local');
        $this->email->from($from_email, $quotation->company_name);
        $this->email->to($to_email);
        if ($cc_email)  $this->email->cc($cc_email);
        if ($bcc_email) $this->email->bcc($bcc_email);
        $this->email->subject($subject);
        $this->email->message(nl2br(htmlspecialchars($message)));
        if ($pdf_tmp_path && file_exists($pdf_tmp_path)) {
            $this->email->attach($pdf_tmp_path, 'attachment', $pdf_filename, 'application/pdf');
        }

        $sent  = $this->email->send();
        $error = $sent ? null : $this->email->print_debugger(['headers']);

        $this->db->insert('Army_EmailHistory', [
            'quotation_id'  => $id,
            'to_email'      => $to_email,
            'cc_email'      => $cc_email,
            'bcc_email'     => $bcc_email,
            'subject'       => $subject,
            'message'       => $message,
            'status'        => $sent ? 'sent' : 'failed',
            'error_message' => $sent ? null : substr(strip_tags((string)$error), 0, 250),
            'sent_by'       => $this->session->userdata('user_id'),
            'sent_at'       => date('Y-m-d H:i:s'),
        ]);

        if ($pdf_tmp_path && file_exists($pdf_tmp_path)) {
            @unlink($pdf_tmp_path);
        }

        if ($sent) {
            $this->Army_quotation_model->set_status($id, 'sent');
            $this->session->set_flashdata('success', 'Quotation emailed to ' . $to_email . ' (PDF attached).');
        } else {
            $this->session->set_flashdata('error', 'Email failed. Check SMTP settings.');
        }
        redirect('army_quotations/view/' . $id);
    }
}
