<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// ─── Form-level permissions ──────────────────────────────────────────────────
define('FORM_PERMISSIONS', [
    // Core
    'frm_dashboard'            => ['label' => 'Dashboard',                          'group' => 'Core'],
    // Billing & Sales
    'frm_billing'              => ['label' => 'Billing — Invoice List',              'group' => 'Billing & Sales'],
    'frm_billing_create'       => ['label' => 'Billing — Create / Edit Invoice',     'group' => 'Billing & Sales'],
    'frm_estimates'            => ['label' => 'Estimates',                           'group' => 'Billing & Sales'],
    'frm_sales_returns'        => ['label' => 'Sales Returns (Credit Notes)',        'group' => 'Billing & Sales'],
    'frm_payments_receive'     => ['label' => 'Payments — Receive from Customer',   'group' => 'Billing & Sales'],
    'frm_order_requests'       => ['label' => 'Order Requests (Portal Orders)',      'group' => 'Billing & Sales'],
    // Products & Masters
    'frm_products'             => ['label' => 'Products — List',                     'group' => 'Products & Masters'],
    'frm_products_add'         => ['label' => 'Products — Add / Edit',              'group' => 'Products & Masters'],
    'frm_products_import'      => ['label' => 'Products — Import',                  'group' => 'Products & Masters'],
    'frm_plu'                  => ['label' => 'PLU Master',                          'group' => 'Products & Masters'],
    'frm_hsn'                  => ['label' => 'HSN Master',                          'group' => 'Products & Masters'],
    'frm_masters'              => ['label' => 'Masters (Categories / Groups / Tags)','group' => 'Products & Masters'],
    'frm_schemes'              => ['label' => 'Schemes / Promotions',                'group' => 'Products & Masters'],
    // Customers & Suppliers
    'frm_customers'            => ['label' => 'Customers',                           'group' => 'Customers & Suppliers'],
    'frm_suppliers'            => ['label' => 'Suppliers',                           'group' => 'Customers & Suppliers'],
    // Purchases
    'frm_purchases'            => ['label' => 'Purchases (Direct GRN)',              'group' => 'Purchases'],
    'frm_purchase_orders'      => ['label' => 'Purchase Orders',                     'group' => 'Purchases'],
    'frm_grn'                  => ['label' => 'GRN — Goods Receipt',                'group' => 'Purchases'],
    'frm_rfq'                  => ['label' => 'RFQ — Request for Quotation',        'group' => 'Purchases'],
    'frm_purchase_returns'     => ['label' => 'Purchase Returns (Debit Notes)',      'group' => 'Purchases'],
    'frm_payments_supplier'    => ['label' => 'Payments — Pay Supplier',            'group' => 'Purchases'],
    'frm_payments_print_cheque'=> ['label' => 'Payments — Print Cheque',            'group' => 'Purchases'],
    'frm_purchase_suggestions' => ['label' => 'Purchase Suggestions',               'group' => 'Purchases'],
    // Inventory
    'frm_inventory'            => ['label' => 'Inventory / Stock Report',            'group' => 'Inventory'],
    'frm_stock_transfers'      => ['label' => 'Stock Transfers',                     'group' => 'Inventory'],
    'frm_stock_transfers_edit' => ['label' => 'Stock Transfers — Edit',             'group' => 'Inventory'],
    'frm_stock_take'           => ['label' => 'Stock Take',                          'group' => 'Inventory'],
    // Reports — individual report access
    'frm_rpt_sales'            => ['label' => 'Sales Report',                        'group' => 'Reports'],
    'frm_rpt_profit_loss'      => ['label' => 'Profit & Loss',                       'group' => 'Reports'],
    'frm_rpt_stock'            => ['label' => 'Stock Report',                        'group' => 'Reports'],
    'frm_rpt_purchase'         => ['label' => 'Purchase Report',                     'group' => 'Reports'],
    'frm_rpt_daily_shift'      => ['label' => 'Daily & Shift Reports',               'group' => 'Reports'],
    'frm_rpt_party_ledger'     => ['label' => 'Customer / Supplier Ledger',          'group' => 'Reports'],
    'frm_rpt_outstanding'      => ['label' => 'Outstanding Dues',                    'group' => 'Reports'],
    'frm_rpt_gst'              => ['label' => 'GST Reports (GSTR-1/2/3B)',           'group' => 'Reports'],
    'frm_rpt_inventory'        => ['label' => 'Inventory Analytics & Fast/Slow',     'group' => 'Reports'],
    'frm_rpt_category'         => ['label' => 'Category / Brand / Supplier Report',  'group' => 'Reports'],
    'frm_rpt_credit_notes'     => ['label' => 'Credit Notes / Sales Returns',        'group' => 'Reports'],
    'frm_rpt_audit_log'        => ['label' => 'Audit Log',                           'group' => 'Reports'],
    'frm_rpt_wallet_ledger'    => ['label' => 'Wallet Ledger',                       'group' => 'Reports'],
    'frm_rpt_portal_funnel'    => ['label' => 'Portal Order Funnel',                 'group' => 'Reports'],
    'frm_rpt_po_grn_variance'  => ['label' => 'PO vs GRN Variance',                  'group' => 'Reports'],
    'frm_rpt_stock_take_variance' => ['label' => 'Stock-Take Variance',              'group' => 'Reports'],
    'frm_rpt_trial_balance'    => ['label' => 'Trial Balance',                        'group' => 'Reports'],
    'frm_rpt_general_ledger'   => ['label' => 'General Ledger',                       'group' => 'Reports'],
    'frm_rpt_schemes'          => ['label' => 'Scheme Sales Report',                  'group' => 'Reports'],
    // Report export permissions
    'frm_rpt_export'           => ['label' => 'Export Reports (Excel / PDF)',         'group' => 'Report Exports'],
    'frm_rpt_email'            => ['label' => 'Email Reports',                        'group' => 'Report Exports'],
    'frm_rpt_print'            => ['label' => 'Print Reports',                        'group' => 'Report Exports'],
    // Accounting — Chart of Accounts / Journal Entries / Expenses
    'frm_coa_manage'           => ['label' => 'Chart of Accounts — Manage',           'group' => 'Accounting'],
    'frm_journal_create'       => ['label' => 'Journal Entries — Create',             'group' => 'Accounting'],
    'frm_journal_reverse'      => ['label' => 'Journal Entries — Reverse',            'group' => 'Accounting'],
    'frm_bank_accounts'        => ['label' => 'Bank & Cash Accounts — Manage',        'group' => 'Accounting'],
    // Cash Management
    'frm_cash_dashboard'       => ['label' => 'Cash Dashboard',                      'group' => 'Cash Management'],
    'frm_cash_handover'        => ['label' => 'Cash Handover',                       'group' => 'Cash Management'],
    'frm_cash_payin'           => ['label' => 'Cash Pay-In',                         'group' => 'Cash Management'],
    'frm_cash_payout'          => ['label' => 'Cash Pay-Out',                        'group' => 'Cash Management'],
    'frm_cash_ledger'          => ['label' => 'Cash Ledger Report',                  'group' => 'Cash Management'],
    'frm_cash_categories'      => ['label' => 'Cash Categories',                     'group' => 'Cash Management'],
    // Admin
    'frm_settings'             => ['label' => 'Settings',                            'group' => 'Admin'],
    'frm_locations'            => ['label' => 'Locations',                           'group' => 'Admin'],
    'frm_user_management'      => ['label' => 'User Management',                     'group' => 'Admin'],
    'frm_data_reset'           => ['label' => 'Data Reset',                          'group' => 'Admin'],
    // WhatsApp Marketing (premium, vendor-controlled module)
    'frm_wa_dashboard'         => ['label' => 'Marketing — Dashboard',               'group' => 'WhatsApp Marketing'],
    'frm_wa_campaigns'         => ['label' => 'Marketing — Campaigns',               'group' => 'WhatsApp Marketing'],
    'frm_wa_responses'         => ['label' => 'Marketing — Customer Responses',      'group' => 'WhatsApp Marketing'],
]);

// ─── Module list used across User Management + Header ───────────────────────
define('ERP_MODULES', [
    'mod_dashboard'       => 'Dashboard',
    'mod_billing'         => 'Billing',
    'mod_reservation'     => 'Reservation',
    'mod_check_in'        => 'Check-In',
    'mod_check_out'       => 'Check-Out',
    'mod_guest_master'    => 'Customer & Supplier Master',
    'mod_room_master'     => 'Room Master',
    'mod_item_master'     => 'Item Master',
    'mod_inventory'       => 'Inventory',
    'mod_purchase'        => 'Purchase',
    'mod_accounts'        => 'Accounts',
    'mod_accounting'      => 'Accounting',
    'mod_reports'         => 'Reports',
    'mod_day_open'        => 'Day Open',
    'mod_day_close'       => 'Day Close',
    'mod_shift_open'      => 'Shift Open',
    'mod_shift_close'     => 'Shift Close',
    'mod_user_management' => 'User Management',
    'mod_settings'        => 'Settings',
    'mod_locations'       => 'Locations',
    'mod_stock_transfers' => 'Stock Transfers',
    'mod_stock_take'      => 'Stock Take',
    'mod_discount'        => 'Give Discount in Billing',
    'mod_whatsapp_marketing' => 'WhatsApp Marketing',
    'mod_warranty'        => 'Warranty & Serial Numbers',
    'mod_loyalty'         => 'Loyalty & Rewards',
    'mod_sms'             => 'SMS & OTP',
    'mod_cash_management' => 'Cash Management',
    'mod_webstore'        => 'Online Store (Web Orders)',
]);

class MY_Controller extends CI_Controller {
    public function __construct() {
        parent::__construct();
        // csrf_regenerate is ON, so CI3 already rotated the CSRF hash (and its
        // HttpOnly cookie) for this request before the controller ran. Echo the
        // new hash back as a header so footer.php's ajaxSend can stay in sync —
        // JS can't read the cookie itself, and reusing a stale hash 403s any
        // second AJAX POST fired from the same page load.
        $this->output->set_header('X-CSRF-TOKEN: ' . $this->security->get_csrf_hash());
        // MySQL's own CURRENT_TIMESTAMP defaults follow the DB server's system
        // clock (UTC on Hostinger), while PHP is already set to Asia/Kolkata
        // (see index.php). Align the session so DB-generated timestamps match.
        $this->db->query("SET time_zone = '+05:30'");
        // Load company settings and make them available globally in all views
        if ($this->db->table_exists('company_settings')) {
            $rows = $this->db->get('company_settings')->result();
            $company = [];
            foreach ($rows as $r) {
                $company[$r->key_name] = $r->key_value;
            }
            $this->load->vars(['company' => $company]);
        }
    }
    protected function render($view, $data = []) {
        $data['is_owner'] = $this->is_owner();
        $data['is_admin'] = $this->is_admin();
        $this->load->view('layouts/header', $data);
        $this->load->view($view, $data);
        $this->load->view('layouts/footer');
    }
    // Centrally insert audit logs
    protected function log_activity($module, $action, $description = '', $record_id = 0, $record_no = '', $user_id = null, $user_name = null) {
        $u_id   = $user_id   ?: ($this->session->userdata('user_id') ?: 0);
        $u_name = $user_name ?: ($this->session->userdata('name') ?? 'System');
        $this->db->insert('audit_logs', [
            'module'        => $module,
            'action'        => $action,
            'record_id'     => $record_id,
            'record_no'     => $record_no,
            'description'   => $description,
            'user_id'       => $u_id,
            'user_name'     => $u_name,
            'ip_address'    => $this->input->ip_address(),
            'location_id'   => $this->session->userdata('active_location_id'),
            'location_name' => $this->session->userdata('active_location_name'),
            'created_at'    => date('Y-m-d H:i:s')
        ]);
    }

    // ── Location context helpers ──────────────────────────────────────────────
    // Returns the location_id to filter queries with (null = all locations)
    protected function active_location_id()
    {
        // Outlet/warehouse users: their fixed location
        $fixed = $this->session->userdata('location_id');
        if ($fixed) return (int)$fixed;
        // Owner/admin: whatever they've switched to (null = all)
        $active = $this->session->userdata('active_location_id');
        return $active ? (int)$active : null;
    }

    protected function active_location_name()
    {
        return $this->session->userdata('active_location_name') ?: 'All Locations';
    }

    protected function is_owner()
    {
        $role = $this->session->userdata('role');
        if ($role === 'owner' || $role === 'superadmin') return true;
        return is_null($this->session->userdata('location_id'));
    }

    // Check if current user can access a specific form/page
    protected function can_form($key) {
        $role = $this->session->userdata('role');
        if ($role === 'superadmin' || $role === 'owner') return true;
        $perms = $this->session->userdata('form_perms') ?: [];
        // Default allow if key not explicitly set
        return !isset($perms[$key]) || !empty($perms[$key]);
    }
}

// ─── Main ERP Auth Controller ────────────────────────────────────────────────
class Auth_Controller extends MY_Controller {
    public function __construct() {
        parent::__construct();
        if ($this->input->is_cli_request()) {
            return;
        }
        if (!$this->session->userdata('user_id')) {
            $this->session->set_flashdata('error', 'Please login to continue.');
            redirect('login');
        }

        // Re-read this user's module permissions every request so a change made in
        // User Management takes effect without the user logging out and in again.
        $__role_now = $this->session->userdata('role');
        if (!in_array($__role_now, ['superadmin', 'owner'], true) && (int)$this->session->userdata('user_id') !== 9999 && $this->db->table_exists('module_permissions')) {
            $__mp = $this->session->userdata('module_perms') ?: [];
            foreach ($this->db->where('user_id', $this->session->userdata('user_id'))->get('module_permissions')->result() as $__r) {
                if ($__r->module_key !== 'mod_whatsapp_marketing' || (int)$__r->is_enabled === 1) $__mp[$__r->module_key] = (int)$__r->is_enabled;
            }
            $this->session->set_userdata('module_perms', $__mp);
        }

        $ctrl = $this->router->class;
        $ctrl_lower = strtolower($ctrl);
        $action = strtolower($this->router->method);

        // 1. Module Access Authorization check
        $ctrl_map = [
            'usermanagement' => 'mod_user_management',
            'settings'       => 'mod_settings',
            'reports'        => 'mod_reports',
            'purchases'      => 'mod_purchase',
            'inventory'      => 'mod_inventory',
            'payments'       => 'mod_accounts',
            'billing'        => 'mod_billing',
            'sales_returns'  => 'mod_billing',
            'refunds'          => 'mod_billing',
            'locations'        => 'mod_locations',
            'stocktransfers'   => 'mod_stock_transfers',
            'stocktake'        => 'mod_stock_take',
            'customers'        => 'mod_guest_master',
            'suppliers'      => 'mod_guest_master',
            'masters'        => 'mod_item_master',
            'products'       => 'mod_item_master',
            'garments'       => 'mod_item_master',
            'garmentreports' => 'mod_reports',
            'accounting'     => 'mod_accounting',
            'marketing'      => 'mod_whatsapp_marketing',
            'cashmanagement' => 'mod_cash_management',
            'webadmin'       => 'mod_webstore',
        ];
        
        if (isset($ctrl_map[$ctrl_lower])) {
            $required_perm = $ctrl_map[$ctrl_lower];
            if (!$this->can_module($required_perm)) {
                $this->session->set_flashdata('error', 'Access denied: You do not have permission for this module.');
                redirect('dashboard');
            }
        }

        // Form-level permission check (skip AJAX calls)
        if (!$this->is_superadmin() && !$this->input->is_ajax_request()) {
            $form_uri_map = [
                'dashboard:index'              => 'frm_dashboard',
                'billing:index'                => 'frm_billing',
                'billing:create'               => 'frm_billing_create',
                'billing:edit'                 => 'frm_billing_create',
                'billing:scan'                 => 'frm_billing_create',
                'estimates:index'              => 'frm_estimates',
                'estimates:create'             => 'frm_estimates',
                'estimates:edit'               => 'frm_estimates',
                'salesreturns:index'           => 'frm_sales_returns',
                'salesreturns:create'          => 'frm_sales_returns',
                'salesreturns:save'            => 'frm_sales_returns',
                'payments:index'               => 'frm_payments_receive',
                'payments:receive'             => 'frm_payments_receive',
                'payments:pay_supplier'        => 'frm_payments_supplier',
                'payments:print_cheque'        => 'frm_payments_print_cheque',
                'payments:save_cheque_layout'  => 'frm_payments_print_cheque',
                'orderrequests:index'          => 'frm_order_requests',
                'orderrequests:view'           => 'frm_order_requests',
                'orderrequests:convert'        => 'frm_order_requests',
                'products:index'               => 'frm_products',
                'products:add'                 => 'frm_products_add',
                'products:edit'                => 'frm_products_add',
                'products:import'              => 'frm_products_import',
                'products:import_preview'      => 'frm_products_import',
                'products:import_confirm'      => 'frm_products_import',
                'plu:index'                    => 'frm_plu',
                'plu:create'                   => 'frm_plu',
                'plu:edit'                     => 'frm_plu',
                'hsn:index'                    => 'frm_hsn',
                'hsn:add'                      => 'frm_hsn',
                'hsn:edit'                     => 'frm_hsn',
                'masters:sub_categories'       => 'frm_masters',
                'masters:groups'               => 'frm_masters',
                'masters:departments'          => 'frm_masters',
                'masters:tags'                 => 'frm_masters',
                'masters:edit_brand'           => 'frm_masters',
                'schemes:index'                => 'frm_schemes',
                'schemes:create'               => 'frm_schemes',
                'schemes:edit'                 => 'frm_schemes',
                'schemes:report'               => 'frm_schemes',
                'customers:index'              => 'frm_customers',
                'customers:add'                => 'frm_customers',
                'customers:edit'               => 'frm_customers',
                'customers:ledger'             => 'frm_customers',
                'customers:wallet_history'     => 'frm_customers',
                'customers:adjust_wallet'      => 'frm_customers',
                'suppliers:index'              => 'frm_suppliers',
                'suppliers:add'                => 'frm_suppliers',
                'suppliers:edit'               => 'frm_suppliers',
                'suppliers:view'               => 'frm_suppliers',
                'suppliers:products'           => 'frm_suppliers',
                'suppliers:documents'          => 'frm_suppliers',
                'purchases:index'              => 'frm_purchases',
                'purchases:create'             => 'frm_purchases',
                'purchases:edit'               => 'frm_purchases',
                'purchases:convert_to_invoice' => 'frm_purchases',
                'purchaseorders:index'         => 'frm_purchase_orders',
                'purchaseorders:create'        => 'frm_purchase_orders',
                'purchaseorders:edit'          => 'frm_purchase_orders',
                'purchaseorders:view'          => 'frm_purchase_orders',
                'grn:index'                    => 'frm_grn',
                'grn:create'                   => 'frm_grn',
                'grn:view'                     => 'frm_grn',
                'grn:convert_to_invoice'       => 'frm_grn',
                'rfq:index'                    => 'frm_rfq',
                'rfq:create'                   => 'frm_rfq',
                'rfq:view'                     => 'frm_rfq',
                'rfq:compare'                  => 'frm_rfq',
                'purchasereturns:index'        => 'frm_purchase_returns',
                'purchasereturns:create'       => 'frm_purchase_returns',
                'purchasereturns:edit'         => 'frm_purchase_returns',
                'purchasesuggestions:index'    => 'frm_purchase_suggestions',
                'inventory:index'              => 'frm_inventory',
                'inventory:report'             => 'frm_inventory',
                'inventory:adjust'             => 'frm_inventory',
                'inventory:movements'          => 'frm_inventory',
                'stocktransfers:index'         => 'frm_stock_transfers',
                'stocktransfers:create'        => 'frm_stock_transfers',
                'stocktransfers:view'          => 'frm_stock_transfers',
                'stocktake:index'              => 'frm_stock_take',
                'stocktake:create'             => 'frm_stock_take',
                'stocktake:count'              => 'frm_stock_take',
                'stocktake:view'               => 'frm_stock_take',
                'reports:index'                => 'frm_reports',
                'reports:sales'                => 'frm_reports',
                'reports:purchases'            => 'frm_reports',
                'reports:stock'                => 'frm_reports',
                'reports:profit_loss'          => 'frm_reports',
                'reports:gst'                  => 'frm_reports',
                'reports:outstanding'          => 'frm_reports',
                'reports:party_ledger'         => 'frm_reports',
                'reports:gstr1'                => 'frm_reports',
                'reports:hsn_summary_report'   => 'frm_reports',
                'reports:export_hsn_summary_report' => 'frm_reports',
                'reports:tally_export'         => 'frm_reports',
                'reports:export_tally_xml'     => 'frm_reports',
                'reports:gstr2'                => 'frm_reports',
                'reports:gstr3b'               => 'frm_reports',
                'reports:inventory_analytics'  => 'frm_reports',
                'reports:fast_slow_report'     => 'frm_reports',
                'reports:category_report'      => 'frm_reports',
                'reports:audit_log'            => 'frm_reports',
                'reports:credit_sales'         => 'frm_reports',
                'reports:refunds_report'       => 'frm_reports',
                'reports:outlet_sales'         => 'frm_reports',
                'settings:index'               => 'frm_settings',
                'settings:save'                => 'frm_settings',
                'locations:index'              => 'frm_locations',
                'locations:add'                => 'frm_locations',
                'locations:edit'               => 'frm_locations',
                'usermanagement:index'         => 'frm_user_management',
                'usermanagement:add'           => 'frm_user_management',
                'usermanagement:edit'          => 'frm_user_management',
                'datareset:index'              => 'frm_data_reset',
                'datareset:execute'            => 'frm_data_reset',
                'accounting:chart_of_accounts' => 'frm_coa_manage',
                'accounting:save_account'      => 'frm_coa_manage',
                'accounting:delete_account'    => 'frm_coa_manage',
                'accounting:journal_entries'   => 'frm_journal_create',
                'accounting:journal_create'    => 'frm_journal_create',
                'accounting:journal_view'      => 'frm_journal_create',
                'accounting:journal_reverse'   => 'frm_journal_reverse',
                'accounting:bank_accounts'       => 'frm_bank_accounts',
                'accounting:save_bank_account'   => 'frm_bank_accounts',
                'accounting:delete_bank_account' => 'frm_bank_accounts',
                'accounting:payment_mode_mapping'      => 'frm_bank_accounts',
                'accounting:save_payment_mode_mapping' => 'frm_bank_accounts',
                'reports:trial_balance'        => 'frm_rpt_trial_balance',
                'reports:general_ledger'       => 'frm_rpt_general_ledger',
                'reports:scheme_sales'         => 'frm_rpt_schemes',
                'marketing:index'              => 'frm_wa_dashboard',
                'marketing:campaigns'          => 'frm_wa_campaigns',
                'marketing:campaign_create'    => 'frm_wa_campaigns',
                'marketing:campaign_view'      => 'frm_wa_campaigns',
                'marketing:templates'          => 'frm_wa_campaigns',
                'marketing:template_edit'      => 'frm_wa_campaigns',
                'marketing:campaign_start'     => 'frm_wa_campaigns',
                'marketing:campaign_runner'    => 'frm_wa_campaigns',
                'marketing:process_batch_ajax' => 'frm_wa_campaigns',
                'marketing:campaign_pause'     => 'frm_wa_campaigns',
                'marketing:campaign_resume'    => 'frm_wa_campaigns',
                'marketing:campaign_cancel'    => 'frm_wa_campaigns',
                'marketing:posters'            => 'frm_wa_campaigns',
                'marketing:poster_upload'      => 'frm_wa_campaigns',
                'marketing:poster_delete'      => 'frm_wa_campaigns',
                'marketing:poster_generate'    => 'frm_wa_campaigns',
                'marketing:ai_generate_copy_ajax' => 'frm_wa_campaigns',
                'marketing:responses'          => 'frm_wa_responses',
                'marketing:response_edit'      => 'frm_wa_responses',
                'marketing:analytics'          => 'frm_wa_dashboard',
                'marketing:campaign_wizard'        => 'frm_wa_campaigns',
                'marketing:wizard_preview_count_ajax' => 'frm_wa_campaigns',
                'marketing:campaign_wizard_submit' => 'frm_wa_campaigns',
                'cashmanagement:index'             => 'frm_cash_dashboard',
                'cashmanagement:handover'          => 'frm_cash_handover',
                'cashmanagement:save_handover'     => 'frm_cash_handover',
                'cashmanagement:save_payin'        => 'frm_cash_payin',
                'cashmanagement:save_payout'       => 'frm_cash_payout',
                'cashmanagement:ledger'            => 'frm_cash_ledger',
                'cashmanagement:categories'        => 'frm_cash_categories',
                'cashmanagement:save_category'     => 'frm_cash_categories',
                'cashmanagement:void_transaction'  => 'frm_cash_dashboard',
                'cashmanagement:approve_handover'  => 'frm_cash_handover',
            ];
            $ctrl_action = $ctrl_lower . ':' . $action;
            if (isset($form_uri_map[$ctrl_action])) {
                $form_key   = $form_uri_map[$ctrl_action];
                $form_perms = $this->session->userdata('form_perms') ?: [];
                if (isset($form_perms[$form_key]) && (int)$form_perms[$form_key] === 0) {
                    // Dashboard is always accessible (shows basic view instead of blocking)
                    if ($ctrl_lower === 'dashboard') return;
                    $this->session->set_flashdata('error', 'Access denied: You do not have permission to access this page.');
                    redirect('dashboard');
                }
            }
        }

        // Only superadmin can access estimates
        if ($ctrl_lower === 'estimates' && $this->session->userdata('role') !== 'superadmin') {
            $this->session->set_flashdata('error', 'Access denied: Only Super-Admins can access Estimates.');
            redirect('dashboard');
        }

        // 2. Day Open check for transactional pages
        // Draft/autosave actions are pre-commit scratch data (not a real Purchase/GRN
        // yet), so they must keep working even when the business day is closed —
        // only the actual finalize actions (create/edit/accept) should be gated.
        $draft_bypass_actions = ['autosave_ajax', 'check_draft_ajax', 'resume_draft_ajax', 'heartbeat_ajax', 'delete_draft_ajax', 'drafts'];
        $is_draft_action = in_array($ctrl_lower, ['purchases', 'grn']) && in_array($action, $draft_bypass_actions);
        $bypass_controllers = ['auth', 'dashboard', 'shifts', 'usermanagement', 'settings', 'locations', 'stocktake', 'stocktransfers'];
        if (!in_array($ctrl_lower, $bypass_controllers) && !$is_draft_action) {
            $bd_loc = $this->active_location_id() ?: 0;
            $has_loc_col = $this->db->field_exists('location_id', 'business_days');
            $this->db->where('status', 'open');
            if ($has_loc_col && $bd_loc > 0) {
                $this->db->group_start()->where('location_id', $bd_loc)->or_where('location_id', 0)->group_end();
            }
            $active_day = $this->db->get('business_days')->row();
            if (!$active_day) {
                $this->session->set_flashdata('error', 'Operation blocked: A Business Day must be opened by a Manager/Super Admin first.');
                redirect('dashboard');
            }
        }

        // 3. Shift-ID refresh for billing — applies to ALL roles
        // Always re-read from DB so that re-logins don't lose the shift_id in session.
        if ($ctrl_lower === 'billing' && in_array($action, ['create', 'save_ajax', 'scan', 'add', 'edit', 'index'])) {
            $uid          = $this->session->userdata('user_id');
            $active_shift = $this->db->where('cashier_id', $uid)
                                     ->where('status', 'open')
                                     ->get('cashier_shifts')->row();
            if ($active_shift) {
                $this->session->set_userdata('shift_id', $active_shift->id);
            } else {
                $this->session->unset_userdata('shift_id');
                // Only block cashiers from billing without an open shift
                if ($this->session->userdata('role') === 'cashier') {
                    $this->session->set_flashdata('error', 'Please open your Cashier Shift first before starting billing.');
                    redirect('shifts/shift_open');
                }
            }
        }
    }

    protected function is_superadmin() {
        $r = $this->session->userdata('role');
        return ($r === 'superadmin' || $r === 'owner');
    }

    protected function is_admin() {
        $r = $this->session->userdata('role');
        return in_array($r, ['admin', 'superadmin', 'owner', 'manager']);
    }

    protected function require_admin() {
        if (!$this->is_admin()) {
            $this->session->set_flashdata('error', 'Permission denied.');
            redirect('dashboard');
        }
    }

    protected function require_superadmin() {
        if (!$this->is_superadmin()) {
            $this->session->set_flashdata('error', 'Super-Admin access required.');
            redirect('dashboard');
        }
    }

    protected function require_system_owner() {
        if ((int)$this->session->userdata('user_id') !== 9999) {
            $this->session->set_flashdata('error', 'Access restricted to system owner only.');
            redirect('dashboard');
        }
    }

    // Check if current user can access a given module key
    protected function can_module($key) {
        if ($this->is_superadmin()) return true; // owner + superadmin get everything
        $perms = $this->session->userdata('module_perms') ?? [];
        return !empty($perms[$key]);
    }
}

// ─── Customer Portal Controller ──────────────────────────────────────────────
class Portal_Controller extends MY_Controller {
    public function __construct() {
        parent::__construct();
        if (!$this->session->userdata('portal_customer_id')) {
            redirect('portal/login');
        }
    }

    protected function portal_render($view, $data = []) {
        $data['portal_customer'] = $this->session->userdata('portal_customer');
        $this->load->view('portal/layout_header', $data);
        $this->load->view($view, $data);
        $this->load->view('portal/layout_footer');
    }
}

