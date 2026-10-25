<?php
// ============================================================
// FILE: application/controllers/Settings.php
// Admin-only: manage company settings, SMTP config, GSTIN
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_system_owner();
    }

    public function index() {
        // Auto-migrate payment_modes table
        if (!$this->db->table_exists('payment_modes')) {
            $sql_path = FCPATH . 'sql/58_payment_modes.sql';
            if (file_exists($sql_path)) {
                $sql = file_get_contents($sql_path);
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                    if ($stmt && stripos($stmt, '--') !== 0) $this->db->query($stmt);
                }
            }
        }

        // Auto-migrate audit_print_logs table
        $this->db->query("CREATE TABLE IF NOT EXISTS audit_print_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            invoice_no VARCHAR(50) NOT NULL,
            print_date DATETIME NOT NULL,
            user_id INT NOT NULL,
            printer_name VARCHAR(100),
            copies INT DEFAULT 1,
            status VARCHAR(20) DEFAULT 'success',
            error_msg TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $data['page_title'] = 'Settings';
        $data['settings']   = $this->_get_all();

        // Auto-generate the barcode API key on first visit so the .NET app has something to use
        if (empty($data['settings']['barcode_api_key'])) {
            $key = bin2hex(random_bytes(24));
            $this->db->insert('company_settings', ['key_name' => 'barcode_api_key', 'key_value' => $key]);
            $data['settings']['barcode_api_key'] = $key;
        }

        $printers = [];
        if (function_exists('shell_exec') && stripos(PHP_OS, 'WIN') !== false) {
            try {
                $output = @shell_exec('powershell -Command "Get-WmiObject -Class Win32_Printer | Select-Object -ExpandProperty Name"');
                if ($output) {
                    $lines = explode("\n", trim($output));
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line)) $printers[] = $line;
                    }
                }
            } catch (Exception $e) {
                // Ignore
            }
        }
        $data['windows_printers'] = $printers;

        $data['categories'] = $this->db->where('status', 1)->order_by('name')->get('categories')->result();
        $data['brands'] = $this->db->where('status', 1)->order_by('name')->get('brands')->result();
        $data['customers_list'] = $this->db->select('id, name, phone')->where('status', 1)->order_by('name')->get('customers')->result();
        $data['payment_modes'] = $this->db->table_exists('payment_modes')
            ? $this->db->order_by('sort_order')->get('payment_modes')->result()
            : [];

        $this->render('settings/index', $data);
    }

    public function save() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('settings');

        $fields = $this->input->post(null, TRUE);
        unset($fields['submit']);

        // Handle toggle switches — checkboxes not submitted when unchecked
        $toggle_keys = [
            'enable_estimate_stock_check', 'block_estimate_if_no_stock',
            'enable_bill_modify', 'enable_excel_import', 'enable_gst',
            'default_cash_sale_mode', 'print_show_tax', 'print_show_pending',
            'plu_show_zero_stock',
            'show_portal_qr_on_bill',
            'require_pin_to_delete_item',
            'allow_all_payment_mode_edit',
            'print_hold_slip',
            'a5_show_logo', 'a5_show_contact', 'a5_show_customer', 'a5_show_code', 'a5_show_size_colour',
            'a5_show_tax', 'a5_show_signature', 'a5_show_stamp', 'a5_show_terms', 'a5_show_footer_msg',
        ];

        // For checkboxes: if not in POST, default to 0
        foreach ($toggle_keys as $tk) {
            if (!isset($fields[$tk])) {
                $fields[$tk] = 0;
            }
        }

        // menu_layout is a radio (not checkbox) — default to top_nav if missing
        if (!isset($fields['menu_layout'])) {
            $fields['menu_layout'] = 'top_nav';
        }

        // retail_business_type radio (both, fmcg_only, clothing_only)
        if (!isset($fields['retail_business_type'])) {
            $fields['retail_business_type'] = 'both';
        }

        // Handle user permissions separately
        $perms = isset($fields['perm']) ? $fields['perm'] : [];
        unset($fields['perm']);

        // Handle logo file upload
        if (!empty($_FILES['company_logo']['name'])) {
            $config['upload_path']   = './assets/uploads/';
            $config['allowed_types'] = 'gif|jpg|jpeg|png';
            $config['max_size']      = 2048; // 2MB
            $config['file_name']     = 'logo_' . time(); // Unique name

            $this->load->library('upload', $config);
            $this->upload->initialize($config);

            if ($this->upload->do_upload('company_logo')) {
                $upload_data = $this->upload->data();
                $logo_path = 'assets/uploads/' . $upload_data['file_name'];
                
                $existing = $this->db->where('key_name', 'company_logo')->get('company_settings')->row();
                if ($existing) {
                    $this->db->where('key_name', 'company_logo')->update('company_settings', ['key_value' => $logo_path]);
                } else {
                    $this->db->insert('company_settings', ['key_name' => 'company_logo', 'key_value' => $logo_path]);
                }
            } else {
                $upload_err = $this->upload->display_errors('', '');
                $this->session->set_flashdata('error', 'Logo upload failed: ' . $upload_err);
                redirect('settings');
            }
        }

        // Stamp / signature images (A5 bill) + "remove" checkboxes for all three print images
        foreach (['company_logo', 'company_stamp', 'company_signature'] as $img_key) {
            $remove = !empty($fields['remove_' . $img_key]);
            unset($fields['remove_' . $img_key]);
            $new_path = null;
            if ($img_key !== 'company_logo' && !empty($_FILES[$img_key]['name'])) {
                $this->load->library('upload');
                $this->upload->initialize([
                    'upload_path' => './assets/uploads/', 'allowed_types' => 'jpg|jpeg|png',
                    'max_size' => 1024, 'file_name' => $img_key . '_' . time(), 'detect_mime' => TRUE,
                ]);
                if (!$this->upload->do_upload($img_key)) {
                    $this->session->set_flashdata('error', $img_key . ' upload failed: ' . $this->upload->display_errors('', ''));
                    redirect('settings');
                }
                $new_path = 'assets/uploads/' . $this->upload->data('file_name');
            } elseif ($remove) {
                $new_path = '';
            }
            if ($new_path !== null) {
                $fields[$img_key] = $new_path; // saved by the key-value loop below
                $this->log_activity('mod_settings', 'print_image', ($new_path === '' ? 'Removed ' : 'Updated ') . $img_key);
            }
        }
        $this->log_activity('mod_settings', 'save_settings', 'Saved company/print settings (printer_type=' . ($fields['printer_type'] ?? '-') . ')');

        // Handle Portal Banner uploads (1 to 4)
        for ($i = 1; $i <= 4; $i++) {
            $field_name = 'portal_banner_' . $i;
            if (!empty($_FILES[$field_name]['name'])) {
                $config['upload_path']   = './assets/uploads/';
                $config['allowed_types'] = 'gif|jpg|jpeg|png';
                $config['max_size']      = 2048; // 2MB
                $config['file_name']     = 'banner_'.$i.'_' . time();
                
                $this->load->library('upload'); // Reloading isn't strictly necessary but safe
                $this->upload->initialize($config);
                
                if ($this->upload->do_upload($field_name)) {
                    $upload_data = $this->upload->data();
                    $banner_path = 'assets/uploads/' . $upload_data['file_name'];
                    
                    $existing = $this->db->where('key_name', $field_name)->get('company_settings')->row();
                    if ($existing) {
                        $this->db->where('key_name', $field_name)->update('company_settings', ['key_value' => $banner_path]);
                    } else {
                        $this->db->insert('company_settings', ['key_name' => $field_name, 'key_value' => $banner_path]);
                    }
                } else {
                    $upload_err = $this->upload->display_errors('', '');
                    $this->session->set_flashdata('error', "Banner $i upload failed: " . $upload_err);
                    redirect('settings');
                }
            }
        }

        // Handle Login Page background image upload
        if (!empty($_FILES['login_bg_image']['name'])) {
            $config['upload_path']   = './assets/uploads/';
            $config['allowed_types'] = 'gif|jpg|jpeg|png';
            $config['max_size']      = 4096; // 4MB — a full-height photo, not an icon
            $config['file_name']     = 'login_bg_' . time();

            $this->load->library('upload');
            $this->upload->initialize($config);

            if ($this->upload->do_upload('login_bg_image')) {
                $upload_data = $this->upload->data();
                $bg_path = 'assets/uploads/' . $upload_data['file_name'];

                $existing = $this->db->where('key_name', 'login_bg_image')->get('company_settings')->row();
                if ($existing) {
                    $this->db->where('key_name', 'login_bg_image')->update('company_settings', ['key_value' => $bg_path]);
                } else {
                    $this->db->insert('company_settings', ['key_name' => 'login_bg_image', 'key_value' => $bg_path]);
                }
            } else {
                $upload_err = $this->upload->display_errors('', '');
                $this->session->set_flashdata('error', 'Login background upload failed: ' . $upload_err);
                redirect('settings');
            }
        }

        // Save settings (key-value or column-based table)
        // First try column-based (our new SQL added columns)
        $settings_row = $this->db->get('company_settings')->row_array();

        if ($settings_row) {
            // Update column-by-column
            $update_data = [];
            $known_cols = ['enable_estimate_stock_check','block_estimate_if_no_stock','enable_bill_modify',
                           'enable_excel_import','enable_gst','default_cash_sale_mode','low_stock_threshold'];
            foreach ($known_cols as $col) {
                if (isset($fields[$col])) {
                    if ($this->db->field_exists($col, 'company_settings')) {
                        $update_data[$col] = (int)$fields[$col];
                    }
                    // Keep in $fields so it ALSO saves as a key-value row
                }
            }
            if (!empty($update_data)) {
                $this->db->update('company_settings', $update_data);
            }
        }

        // Save remaining as key-value
        $blank_keeps_existing = ['smtp_pass', 'whatsapp_access_token', 'whatsapp_ai_api_key',
            'gsp_einvoice_client_secret', 'gsp_einvoice_password', 'gsp_ewb_client_secret', 'gsp_ewb_password', 'gstzen_token'];
        foreach ($fields as $key => $value) {
            // Skip secret fields left blank on re-save (keep existing value) — the form
            // never re-displays these in full, so a blank submit means "unchanged".
            if (in_array($key, $blank_keeps_existing, true) && $value === '') continue;
            $existing = $this->db->where('key_name', $key)->get('company_settings')->row();
            if ($existing) {
                $this->db->where('key_name', $key)->update('company_settings', ['key_value' => $value]);
            } else {
                $this->db->insert('company_settings', ['key_name' => $key, 'key_value' => $value]);
            }
        }

        // Save user permissions
        $has_po_col = $this->db->field_exists('allow_price_override', 'user_permissions');
        foreach ($perms as $user_id => $perm_data) {
            $existing = $this->db->where('user_id', $user_id)->get('user_permissions')->row();
            $row = [
                'can_edit_bill'        => (int)($perm_data['can_edit_bill'] ?? 0),
                'can_delete_estimate'  => (int)($perm_data['can_delete_estimate'] ?? 0),
                'can_view_reports'     => (int)($perm_data['can_view_reports'] ?? 1),
                'can_import_excel'     => (int)($perm_data['can_import_excel'] ?? 0),
                'allow_payment_mode_change' => (int)($perm_data['allow_payment_mode_change'] ?? 0),
                'allow_due_bills'      => (int)($perm_data['allow_due_bills'] ?? 0),
            ];
            if ($has_po_col) {
                $row['allow_price_override'] = (int)($perm_data['allow_price_override'] ?? 1);
            }
            if ($existing) {
                $this->db->where('user_id', $user_id)->update('user_permissions', $row);
            } else {
                $row['user_id'] = $user_id;
                $this->db->insert('user_permissions', $row);
            }
        }

        $this->session->set_flashdata('success', 'Settings saved successfully.');
        redirect('settings');
    }

    public function migrate_price_override_col() {
        if (!$this->db->field_exists('allow_price_override', 'user_permissions')) {
            $this->db->query('ALTER TABLE user_permissions ADD COLUMN allow_price_override TINYINT(1) NOT NULL DEFAULT 1');
            echo 'Column allow_price_override added.';
        } else {
            echo 'Column already exists.';
        }
    }

    public function setup_walkin_and_print_confirm() {
        $results = [];

        // 1. Change print_mode to 'confirm' so the print confirmation dialog shows
        $existing = $this->db->where('key_name', 'print_mode')->get('company_settings')->row();
        if ($existing) {
            $this->db->where('key_name', 'print_mode')->update('company_settings', ['key_value' => 'confirm']);
            $results[] = 'print_mode changed from "' . $existing->key_value . '" to "confirm"';
        } else {
            $this->db->insert('company_settings', ['key_name' => 'print_mode', 'key_value' => 'confirm']);
            $results[] = 'print_mode set to "confirm"';
        }

        // 2. Create Walk-in Customer if not exists
        $walkin = $this->db->like('name', 'Walk-in', 'both')->get('customers')->row();
        if (!$walkin) {
            $this->db->insert('customers', [
                'name'       => 'Walk-in Customer',
                'phone'      => '0000000000',
                'gst_number' => '',
                'status'     => 1
            ]);
            $walkin_id = $this->db->insert_id();
            $results[] = 'Created Walk-in Customer with ID ' . $walkin_id;
        } else {
            $walkin_id = $walkin->id;
            $results[] = 'Walk-in Customer already exists with ID ' . $walkin_id;
        }

        // 3. Set walkin_customer_id in company_settings
        $existing2 = $this->db->where('key_name', 'walkin_customer_id')->get('company_settings')->row();
        if ($existing2) {
            $this->db->where('key_name', 'walkin_customer_id')->update('company_settings', ['key_value' => $walkin_id]);
            $results[] = 'walkin_customer_id updated to ' . $walkin_id;
        } else {
            $this->db->insert('company_settings', ['key_name' => 'walkin_customer_id', 'key_value' => $walkin_id]);
            $results[] = 'walkin_customer_id set to ' . $walkin_id;
        }

        echo '<h3>Setup Complete</h3><ul><li>' . implode('</li><li>', $results) . '</li></ul>';
        echo '<p><a href="' . base_url('billing/create') . '">Go to Billing</a></p>';
    }

    public function regenerate_barcode_api_key() {
        $key = bin2hex(random_bytes(24));
        $existing = $this->db->where('key_name', 'barcode_api_key')->get('company_settings')->row();
        if ($existing) {
            $this->db->where('key_name', 'barcode_api_key')->update('company_settings', ['key_value' => $key]);
        } else {
            $this->db->insert('company_settings', ['key_name' => 'barcode_api_key', 'key_value' => $key]);
        }
        $this->log_activity('mod_settings', 'regenerate_barcode_api_key', 'Regenerated the Barcode Printing API key.');
        $this->session->set_flashdata('success', 'Barcode API key regenerated. Update the key in your .NET printing app.');
        redirect('settings#barcode-api');
    }

    public function test_smtp_email() {
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
            'crlf'        => "\r\n",
            'newline'     => "\r\n"
        ]);

        $company_name = $this->db->where('key_name','company_name')->get('company_settings')->row()->key_value ?? 'Sandal House';
        $smtp_user = $this->db->where('key_name','smtp_user')->get('company_settings')->row()->key_value ?? '';
        
        if (empty($smtp_user)) {
            $this->session->set_flashdata('error', 'SMTP Username is not configured. Please save your settings first.');
            redirect('settings');
        }

        $this->email->from($smtp_user, $company_name);
        $this->email->to($smtp_user);
        $this->email->subject('Test SMTP Email - ' . $company_name);
        $this->email->message("<p>This is a test email to verify your SMTP configuration.</p><p>If you are reading this, your App Password and SMTP settings are working perfectly!</p>");

        if ($this->email->send()) {
            $this->session->set_flashdata('success', 'Test email sent successfully to ' . $smtp_user . '. Your SMTP settings are working!');
        } else {
            $error = $this->email->print_debugger(['headers']);
            $this->session->set_flashdata('error', 'Failed to send test email. Error: ' . strip_tags($error));
        }
        redirect('settings');
    }

    public function test_whatsapp_connection() {
        $this->load->library('WhatsAppClient');
        $result = $this->whatsappclient->verify_credentials();
        if ($result['ok']) {
            $name = $result['verified_name'] ?? $result['display_phone_number'] ?? 'your WhatsApp Business number';
            $this->session->set_flashdata('success', 'WhatsApp API connected — verified as "' . $name . '".');
        } else {
            $this->session->set_flashdata('error', 'WhatsApp API connection failed: ' . $result['error']);
        }
        redirect('settings#whatsapp-marketing');
    }

    public function save_payment_mode() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('settings#payment-modes');

        $id   = (int) $this->input->post('id', TRUE);
        $code = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $this->input->post('code', TRUE))));
        $data = [
            'code'          => $code,
            'name'          => trim($this->input->post('name', TRUE)),
            'icon'          => trim($this->input->post('icon', TRUE)) ?: 'fas fa-money-bill-wave',
            'is_cash_type'  => (int) $this->input->post('is_cash_type', TRUE),
            'show_in_quick' => (int) $this->input->post('show_in_quick', TRUE),
            'show_in_other' => (int) $this->input->post('show_in_other', TRUE),
            'show_in_split' => (int) $this->input->post('show_in_split', TRUE),
            'needs_ref'     => (int) $this->input->post('needs_ref', TRUE),
            'is_credit'     => (int) $this->input->post('is_credit', TRUE),
            'sort_order'    => (int) $this->input->post('sort_order', TRUE),
            'status'        => (int) $this->input->post('status', TRUE),
        ];

        if (empty($data['code']) || empty($data['name'])) {
            $this->session->set_flashdata('error', 'Code and Name are required.');
            redirect('settings#payment-modes');
            return;
        }

        if ($id > 0) {
            $existing = $this->db->where('id', $id)->get('payment_modes')->row();
            if ($existing && $existing->is_system) {
                unset($data['code']);
            }
            $this->db->where('id', $id)->update('payment_modes', $data);
            $this->session->set_flashdata('success', 'Payment mode updated.');
        } else {
            $dup = $this->db->where('code', $data['code'])->get('payment_modes')->row();
            if ($dup) {
                $this->session->set_flashdata('error', 'Code "' . $code . '" already exists.');
                redirect('settings#payment-modes');
                return;
            }
            $data['is_system'] = 0;
            $this->db->insert('payment_modes', $data);
            $this->session->set_flashdata('success', 'Payment mode added.');
        }
        redirect('settings#payment-modes');
    }

    public function delete_payment_mode($id = null) {
        $id = (int) $id;
        $mode = $this->db->where('id', $id)->get('payment_modes')->row();
        if (!$mode || $mode->is_system) {
            $this->session->set_flashdata('error', 'Cannot delete a system payment mode.');
        } else {
            $this->db->where('id', $id)->delete('payment_modes');
            $this->session->set_flashdata('success', 'Payment mode deleted.');
        }
        redirect('settings#payment-modes');
    }

    private function _get_all() {
        $rows = $this->db->get('company_settings')->result();
        $out  = [];
        foreach ($rows as $r) {
            if (!empty($r->key_name)) {
                $out[$r->key_name] = $r->key_value;
            }
        }

        // Also merge column-based settings from company_settings table
        $first = $this->db->limit(1)->get('company_settings')->row();
        if ($first) {
            $cols = [
                'enable_estimate_stock_check', 'block_estimate_if_no_stock',
                'enable_bill_modify', 'enable_excel_import', 'enable_gst',
                'default_cash_sale_mode', 'low_stock_threshold'
            ];
            foreach ($cols as $col) {
                if (isset($first->$col)) {
                    if (!isset($out[$col]) || $out[$col] === '') {
                        $out[$col] = $first->$col;
                    }
                }
            }
        }
        return $out;
    }
}
