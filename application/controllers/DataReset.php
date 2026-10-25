<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DataReset extends Auth_Controller {

    private $_table_groups = [
        'Transactions' => [
            'invoices'              => ['label' => 'Invoices (Sales Bills)',             'deps' => ['invoice_items','invoice_edit_log','payments']],
            'invoice_items'         => ['label' => 'Invoice Items',                      'deps' => []],
            'invoice_edit_log'      => ['label' => 'Invoice Edit Logs',                  'deps' => []],
            'estimates'             => ['label' => 'Estimates / Quotations',             'deps' => ['estimate_items']],
            'estimate_items'        => ['label' => 'Estimate Items',                     'deps' => []],
            'payments'              => ['label' => 'Payments',                           'deps' => []],
        ],
        'Purchases (Old)' => [
            'purchases'             => ['label' => 'Purchases (Legacy)',                 'deps' => ['purchase_items','purchase_returns']],
            'purchase_items'        => ['label' => 'Purchase Items (Legacy)',            'deps' => []],
            'purchase_returns'      => ['label' => 'Purchase Returns',                   'deps' => ['purchase_return_items']],
            'purchase_return_items' => ['label' => 'Purchase Return Items',              'deps' => []],
        ],
        'PO System' => [
            'purchase_orders'       => ['label' => 'Purchase Orders (PO)',               'deps' => ['purchase_order_items','purchase_approvals','grn','vendor_messages']],
            'purchase_order_items'  => ['label' => 'PO Line Items',                      'deps' => []],
            'purchase_approvals'    => ['label' => 'PO Approvals Log',                   'deps' => []],
            'grn'                   => ['label' => 'Goods Receipt Notes (GRN)',          'deps' => ['grn_items']],
            'grn_items'             => ['label' => 'GRN Line Items',                     'deps' => []],
            'rfq'                   => ['label' => 'Request for Quotations (RFQ)',       'deps' => ['rfq_items','rfq_suppliers','rfq_quotations']],
            'rfq_items'             => ['label' => 'RFQ Items',                          'deps' => []],
            'rfq_suppliers'         => ['label' => 'RFQ Supplier Invites',              'deps' => []],
            'rfq_quotations'        => ['label' => 'RFQ Quotations (Supplier Quotes)',  'deps' => []],
            'vendor_messages'       => ['label' => 'Vendor Chat Messages',               'deps' => []],
            'vendor_performance'    => ['label' => 'Vendor Performance Scores',         'deps' => []],
            'supplier_visit_logs'   => ['label' => 'Supplier Visit Logs',               'deps' => []],
            'supplier_ledger'       => ['label' => 'Supplier Ledger',                   'deps' => []],
        ],
        'Stock & Inventory' => [
            'stock_ledger'          => ['label' => 'Stock Ledger (Movement Log)',        'deps' => []],
            'stock_adjustments'     => ['label' => 'Stock Adjustments',                  'deps' => []],
        ],
        'Products & Masters' => [
            'products'              => ['label' => 'Products (Item Master)',              'deps' => ['product_barcodes','plu_master','stock_ledger','invoice_items','purchase_items']],
            'product_barcodes'      => ['label' => 'Product Barcodes',                   'deps' => []],
            'plu_master'            => ['label' => 'PLU (Price Look-Up) Entries',        'deps' => []],
            'brands'                => ['label' => 'Brands Master',                      'deps' => []],
            'categories'            => ['label' => 'Categories Master',                  'deps' => []],
            'hsn_master'            => ['label' => 'HSN / SAC Master (Tax Codes)',       'deps' => []],
            'supplier_products'     => ['label' => 'Supplier Product Catalogue',         'deps' => []],
        ],
        'Customers & Suppliers' => [
            'customers'             => ['label' => 'Customers',                          'deps' => ['invoices','payments','estimates']],
            'suppliers'             => ['label' => 'Suppliers',                          'deps' => ['purchases','purchase_orders','vendor_messages']],
            'supplier_visit_schedules' => ['label' => 'Supplier Visit Schedules',       'deps' => ['supplier_visit_logs']],
            'supplier_documents'    => ['label' => 'Supplier Documents',                 'deps' => []],
        ],
        'Orders (Portal / B2C)' => [
            'order_requests'        => ['label' => 'Customer Orders (Portal)',            'deps' => ['order_request_items']],
            'order_request_items'   => ['label' => 'Order Items',                        'deps' => []],
        ],
        'Schemes & Logs' => [
            'schemes'               => ['label' => 'Schemes',                            'deps' => ['scheme_transactions']],
            'scheme_transactions'   => ['label' => 'Scheme Transactions',                'deps' => []],
            'audit_logs'            => ['label' => 'Audit / Activity Logs',              'deps' => []],
            'user_permissions'      => ['label' => 'User Permissions',                   'deps' => []],
        ],
        'Accounting' => [
            'journal_entries'       => ['label' => 'Journal Entries',                    'deps' => ['journal_entry_lines']],
            'journal_entry_lines'   => ['label' => 'Journal Entry Lines',                'deps' => []],
            'chart_of_accounts'     => ['label' => 'Chart of Accounts (resets seeded accounts too — re-run sql/40_accounting_module.sql after)', 'deps' => ['journal_entries','bank_accounts','payment_mode_accounts']],
            'bank_accounts'         => ['label' => 'Bank & Cash Accounts',               'deps' => []],
            'payment_mode_accounts' => ['label' => 'Payment Mode Mapping',               'deps' => []],
        ],
    ];

    // Safe ordered list for truncation (children before parents)
    private $_reset_order = [
        // Orders (children first)
        'order_request_items','order_requests',
        // PO system children first
        'purchase_order_items','purchase_approvals','grn_items','rfq_items',
        'rfq_suppliers','rfq_quotations','vendor_messages','vendor_performance',
        'supplier_visit_logs','supplier_ledger','supplier_documents','supplier_products',
        // PO parents
        'grn','rfq','purchase_orders',
        // Legacy purchase children
        'scheme_transactions','audit_logs','stock_ledger','stock_adjustments',
        'invoice_edit_log','invoice_items','purchase_return_items','purchase_returns',
        'purchase_items','estimate_items','payments',
        // Legacy purchase parents
        'invoices','purchases','estimates',
        // Masters
        'product_barcodes','plu_master','user_permissions','hsn_master','supplier_visit_schedules',
        'schemes','products',
        'customers','suppliers','brands','categories',
        // Accounting (children before parents; chart_of_accounts last since journal
        // lines / bank accounts / payment mode mapping all reference it)
        'journal_entry_lines','journal_entries','bank_accounts','payment_mode_accounts','chart_of_accounts',
    ];

    public function __construct() {
        parent::__construct();
        $this->require_system_owner();
    }

    // -------------------------------------------------------
    // SHOW form
    // -------------------------------------------------------
    // Tables that support per-location DELETE (have location_id or can be
    // resolved via a parent that has one).  Format: table => strategy.
    //   'direct'        — table itself has location_id
    //   'via:parent.fk' — DELETE WHERE fk IN (SELECT id FROM parent WHERE location_id=?)
    private $_location_tables = [
        'invoices'              => 'direct',
        'invoice_items'         => 'via:invoices.invoice_id',
        'invoice_edit_log'      => 'via:invoices.invoice_id',
        'invoice_payment_splits'=> 'via:invoices.invoice_id',
        'payments'              => 'via:invoices.invoice_id',
        'scheme_transactions'   => 'via:invoices.invoice_id',
        'scheme_application_log'=> 'via:invoices.invoice_id',
        'sales_returns'         => 'via:invoices.invoice_id',
        'purchases'             => 'direct',
        'purchase_items'        => 'via:purchases.purchase_id',
        'purchase_returns'      => 'via:purchases.purchase_id',
        'purchase_return_items' => 'via_prn',
        'purchase_orders'       => 'direct',
        'purchase_order_items'  => 'via:purchase_orders.po_id',
        'purchase_approvals'    => 'via:purchase_orders.po_id',
        'grn'                   => 'direct',
        'grn_items'             => 'via:grn.grn_id',
        'audit_logs'            => 'direct',
        'held_bills'            => 'direct',
    ];

    public function index() {
        $data['page_title']   = 'Data Reset';
        $data['table_groups'] = $this->_table_groups;
        $data['table_counts'] = $this->_get_counts();
        $this->load->model('Location_model');
        $data['locations']    = $this->Location_model->get_all();
        $data['location_tables'] = $this->_location_tables;
        $data['location_counts'] = $this->_get_location_counts();
        $this->render('admin/data_reset', $data);
    }

    // -------------------------------------------------------
    // EXECUTE reset
    // -------------------------------------------------------
    public function execute() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('data_reset');
        }

        // Confirm phrase
        $phrase = strtoupper(trim($this->input->post('confirm_phrase', TRUE)));
        if ($phrase !== 'RESET') {
            $this->session->set_flashdata('error', 'Confirmation phrase incorrect. Type RESET to proceed.');
            redirect('data_reset');
        }

        // Only sverpadmin (user 9999) with the correct key can execute reset
        $password = $this->input->post('admin_password', TRUE);
        if ((int)$this->session->userdata('user_id') !== 9999 || $password !== 'Mci8yo@257') {
            $this->session->set_flashdata('error', 'Incorrect password. Reset aborted.');
            redirect('data_reset');
        }

        $selected = $this->input->post('tables') ?: [];
        if (empty($selected)) {
            $this->session->set_flashdata('error', 'No tables selected.');
            redirect('data_reset');
        }

        // Build allowed list
        $all_allowed = [];
        foreach ($this->_table_groups as $tables) {
            $all_allowed = array_merge($all_allowed, array_keys($tables));
        }

        // Filter & order
        $to_reset = [];
        foreach ($this->_reset_order as $tbl) {
            if (in_array($tbl, $selected) && in_array($tbl, $all_allowed)) {
                $to_reset[] = $tbl;
            }
        }

        // Truncate
        $reset_count = 0;
        $errors      = [];
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($to_reset as $tbl) {
            try {
                $this->db->truncate($tbl);
                $reset_count++;
            } catch (Exception $e) {
                $errors[] = $tbl . ': ' . $e->getMessage();
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        // Reset AUTO_INCREMENT for key tables
        $ai_tables = [
            'products','invoices','purchases','estimates','customers','suppliers','payments',
            'purchase_orders','purchase_order_items','grn','grn_items',
            'rfq','rfq_items','rfq_quotations','vendor_messages','hsn_master','plu_master',
        ];
        foreach ($ai_tables as $tbl) {
            if (in_array($tbl, $to_reset)) {
                $this->db->query("ALTER TABLE `{$tbl}` AUTO_INCREMENT = 1");
            }
        }

        // Write audit log (if it wasn't itself reset)
        if (!in_array('audit_logs', $to_reset)) {
            $this->db->insert('audit_logs', [
                'module'      => 'data_reset',
                'action'      => 'reset',
                'record_id'   => 0,
                'record_no'   => 'DATA-RESET',
                'old_data'    => json_encode($to_reset),
                'new_data'    => null,
                'old_amount'  => 0,
                'new_amount'  => 0,
                'description' => 'Data reset executed for: ' . implode(', ', $to_reset),
                'user_id'     => $this->session->userdata('user_id'),
                'user_name'   => $this->session->userdata('name'),
                'ip_address'  => $this->input->ip_address(),
            ]);
        }

        $msg = "{$reset_count} table(s) cleared successfully: " . implode(', ', $to_reset) . '.';
        if (!empty($errors)) $msg .= ' | Errors: ' . implode('; ', $errors);
        $this->session->set_flashdata('success', $msg);
        redirect('data_reset');
    }

    // -------------------------------------------------------
    // EXECUTE store-level reset (DELETE WHERE location_id = ?)
    // -------------------------------------------------------
    public function execute_store() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('data_reset');
        }

        $phrase = strtoupper(trim($this->input->post('confirm_phrase_store', TRUE)));
        if ($phrase !== 'RESET') {
            $this->session->set_flashdata('error', 'Confirmation phrase incorrect. Type RESET to proceed.');
            redirect('data_reset');
        }

        $password = $this->input->post('admin_password_store', TRUE);
        if ((int)$this->session->userdata('user_id') !== 9999 || $password !== 'Mci8yo@257') {
            $this->session->set_flashdata('error', 'Incorrect password. Store reset aborted.');
            redirect('data_reset');
        }

        $location_id = (int)$this->input->post('location_id', TRUE);
        if ($location_id <= 0) {
            $this->session->set_flashdata('error', 'Please select a store/outlet.');
            redirect('data_reset');
        }

        $this->load->model('Location_model');
        $location = $this->Location_model->get_by_id($location_id);
        if (!$location) {
            $this->session->set_flashdata('error', 'Location not found.');
            redirect('data_reset');
        }

        $selected = $this->input->post('store_tables') ?: [];
        if (empty($selected)) {
            $this->session->set_flashdata('error', 'No tables selected for store reset.');
            redirect('data_reset');
        }

        $deleted_counts = [];
        $errors = [];
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        // Process in a safe order: children before parents
        $child_first = [
            'invoice_payment_splits', 'invoice_items', 'invoice_edit_log', 'payments',
            'scheme_transactions', 'scheme_application_log', 'sales_returns',
            'purchase_order_items', 'purchase_approvals',
            'grn_items', 'grn',
            'purchase_return_items', 'purchase_returns', 'purchase_items',
            'invoices', 'purchases', 'purchase_orders',
            'audit_logs', 'held_bills',
        ];

        foreach ($child_first as $tbl) {
            if (!in_array($tbl, $selected) || !isset($this->_location_tables[$tbl])) continue;

            $strategy = $this->_location_tables[$tbl];
            try {
                $cnt = 0;
                if ($strategy === 'direct') {
                    $cnt = $this->db->where('location_id', $location_id)->count_all_results($tbl);
                    $this->db->where('location_id', $location_id)->delete($tbl);
                } elseif ($strategy === 'via_prn') {
                    // purchase_return_items.prn_id -> purchase_returns -> purchases.location_id
                    $prn_ids = $this->db->select('pr.id')
                        ->from('purchase_returns pr')
                        ->join('purchases p', 'p.id = pr.purchase_id')
                        ->where('p.location_id', $location_id)
                        ->get()->result_array();
                    $prn_ids = array_column($prn_ids, 'id');
                    if (!empty($prn_ids)) {
                        $cnt = $this->db->where_in('prn_id', $prn_ids)->count_all_results($tbl);
                        $this->db->where_in('prn_id', $prn_ids)->delete($tbl);
                    }
                } else {
                    // 'via:parent_table.fk_column'
                    $parts = explode('.', str_replace('via:', '', $strategy));
                    $parent = $parts[0];
                    $fk     = $parts[1];

                    $parent_ids = $this->db->select('id')
                        ->where('location_id', $location_id)
                        ->get($parent)->result_array();
                    $parent_ids = array_column($parent_ids, 'id');

                    if (!empty($parent_ids)) {
                        $cnt = $this->db->where_in($fk, $parent_ids)->count_all_results($tbl);
                        $this->db->where_in($fk, $parent_ids)->delete($tbl);
                    }
                }
                if ($cnt > 0) $deleted_counts[$tbl] = $cnt;
            } catch (Exception $e) {
                $errors[] = $tbl . ': ' . $e->getMessage();
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        // Audit log
        $this->db->insert('audit_logs', [
            'module'      => 'data_reset',
            'action'      => 'store_reset',
            'record_id'   => $location_id,
            'record_no'   => 'STORE-RESET',
            'old_data'    => json_encode($deleted_counts),
            'new_data'    => null,
            'old_amount'  => 0,
            'new_amount'  => 0,
            'description' => 'Store reset for ' . $location->name . ' (ID:' . $location_id . '): ' . implode(', ', array_keys($deleted_counts)),
            'user_id'     => $this->session->userdata('user_id'),
            'user_name'   => $this->session->userdata('name'),
            'ip_address'  => $this->input->ip_address(),
        ]);

        $total = array_sum($deleted_counts);
        $detail = [];
        foreach ($deleted_counts as $t => $c) { $detail[] = "{$t}({$c})"; }
        $msg = "Store reset for <strong>{$location->name}</strong>: {$total} record(s) deleted from " . count($deleted_counts) . " table(s).";
        if (!empty($detail)) $msg .= '<br><small>' . implode(', ', $detail) . '</small>';
        if (!empty($errors)) $msg .= '<br><span class="text-danger">Errors: ' . implode('; ', $errors) . '</span>';
        $this->session->set_flashdata('success', $msg);
        redirect('data_reset');
    }

    // -------------------------------------------------------
    private function _get_location_counts() {
        $counts = [];
        foreach ($this->_location_tables as $tbl => $strategy) {
            if ($strategy === 'direct') {
                $rows = $this->db->select("location_id, COUNT(*) as cnt")
                    ->group_by('location_id')->get($tbl)->result();
                foreach ($rows as $r) {
                    $counts[$tbl][$r->location_id] = (int)$r->cnt;
                }
            }
        }
        return $counts;
    }

    // -------------------------------------------------------
    private function _get_counts() {
        $counts = [];
        foreach ($this->_table_groups as $tables) {
            foreach (array_keys($tables) as $tbl) {
                $row = $this->db->query("SELECT COUNT(*) AS cnt FROM `{$tbl}`")->row();
                $counts[$tbl] = $row ? (int)$row->cnt : 0;
            }
        }
        return $counts;
    }
}
