<?php
// ============================================================
// FILE: application/controllers/Accounting.php
// Chart of Accounts, manual Journal Entries, Bank & Cash Accounts, and
// payment-mode routing. See sql/40_accounting_module.sql and
// application/models/Ledger_model.php for the underlying schema/engine.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Accounting extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Ledger_model');
        $this->load->library('form_validation');
    }

    public function index() {
        redirect('accounting/chart_of_accounts');
    }

    // ─── Chart of Accounts ────────────────────────────────────────────────

    public function chart_of_accounts() {
        $data['page_title'] = 'Chart of Accounts';
        $data['accounts']   = $this->Ledger_model->get_accounts_tree();
        $this->render('accounting/chart_of_accounts', $data);
    }

    public function save_account() {
        $id = (int)$this->input->post('id');

        $this->form_validation->set_rules('account_name', 'Account Name', 'required|trim');
        $this->form_validation->set_rules('account_code', 'Account Code', 'required|trim');
        $this->form_validation->set_rules('account_type', 'Account Type', 'required|in_list[asset,liability,equity,income,expense]');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors(' '));
            redirect('accounting/chart_of_accounts');
            return;
        }

        $code = trim($this->input->post('account_code', TRUE));
        if ($this->Ledger_model->account_code_exists($code, $id ?: null)) {
            $this->session->set_flashdata('error', 'Account code "' . $code . '" is already in use.');
            redirect('accounting/chart_of_accounts');
            return;
        }

        $parent_id = (int)$this->input->post('parent_id') ?: null;
        if ($parent_id === (int)$id && $id) {
            $this->session->set_flashdata('error', 'An account cannot be its own parent.');
            redirect('accounting/chart_of_accounts');
            return;
        }

        $data = [
            'account_code'         => $code,
            'account_name'         => trim($this->input->post('account_name', TRUE)),
            'account_type'         => $this->input->post('account_type', TRUE),
            'parent_id'            => $parent_id,
            'is_group'             => $this->input->post('is_group') ? 1 : 0,
            'opening_balance'      => (float)$this->input->post('opening_balance'),
            'opening_balance_type' => $this->input->post('opening_balance_type', TRUE) === 'credit' ? 'credit' : 'debit',
            'description'          => trim($this->input->post('description', TRUE)) ?: null,
        ];

        if ($id) {
            $existing = $this->Ledger_model->get_account($id);
            if (!$existing) { show_404(); return; }
            if ($existing->is_system) {
                // System accounts keep their code/type/group-ness fixed — only display
                // fields and opening balance can move, so nothing that Ledger_model's
                // account-code seeding or the AccountingHooks formulas rely on can drift.
                $data['account_code'] = $existing->account_code;
                $data['account_type'] = $existing->account_type;
                $data['is_group']     = $existing->is_group;
            }
            $this->Ledger_model->update_account($id, $data);
            $this->log_activity('accounting', 'edit_account', 'Updated account ' . $data['account_code'] . ' "' . $data['account_name'] . '"', $id, $data['account_code']);
            $this->session->set_flashdata('success', 'Account updated.');
        } else {
            $new_id = $this->Ledger_model->create_account($data);
            $this->log_activity('accounting', 'create_account', 'Created account ' . $data['account_code'] . ' "' . $data['account_name'] . '"', $new_id, $data['account_code']);
            $this->session->set_flashdata('success', 'Account created.');
        }
        redirect('accounting/chart_of_accounts');
    }

    public function delete_account($id = null) {
        $this->require_admin();
        $account = $this->Ledger_model->get_account($id);
        if (!$account) { show_404(); return; }
        if ($account->is_system) {
            $this->session->set_flashdata('error', 'System accounts cannot be deleted.');
        } elseif ($this->Ledger_model->account_has_children($id)) {
            $this->session->set_flashdata('error', 'Cannot delete an account that has sub-accounts.');
        } elseif ($this->Ledger_model->account_has_postings($id)) {
            $this->session->set_flashdata('error', 'Cannot delete an account that has journal postings against it.');
        } else {
            $this->Ledger_model->delete_account($id);
            $this->log_activity('accounting', 'delete_account', 'Deleted account ' . $account->account_code . ' "' . $account->account_name . '"', $id, $account->account_code);
            $this->session->set_flashdata('success', 'Account deleted.');
        }
        redirect('accounting/chart_of_accounts');
    }

    // ─── Manual Journal Entries ───────────────────────────────────────────

    public function journal_entries() {
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to', TRUE) ?: date('Y-m-d');

        $data['page_title'] = 'Journal Entries';
        $data['from'] = $from;
        $data['to']   = $to;
        $data['entries'] = $this->db->where('entry_date >=', $from)->where('entry_date <=', $to)
            ->order_by('entry_date', 'DESC')->order_by('id', 'DESC')->get('journal_entries')->result();
        $this->render('accounting/journal_entries', $data);
    }

    public function journal_create() {
        $data['page_title'] = 'New Journal Entry';
        $data['accounts']   = $this->Ledger_model->get_leaf_accounts();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('accounting/journal_create', $data);
            return;
        }

        $entry_date  = $this->input->post('entry_date', TRUE) ?: date('Y-m-d');
        $narration   = trim($this->input->post('narration', TRUE));
        $account_ids = $this->input->post('account_id') ?: [];
        $debits      = $this->input->post('debit') ?: [];
        $credits     = $this->input->post('credit') ?: [];
        $line_notes  = $this->input->post('line_narration') ?: [];

        $lines = [];
        foreach ($account_ids as $i => $acc_id) {
            $acc_id = (int)$acc_id;
            $debit  = (float)($debits[$i] ?? 0);
            $credit = (float)($credits[$i] ?? 0);
            if (!$acc_id || (!$debit && !$credit)) continue;
            $lines[] = [
                'account_id' => $acc_id,
                'debit'      => $debit,
                'credit'     => $credit,
                'narration'  => trim($line_notes[$i] ?? '') ?: null,
            ];
        }

        if (count($lines) < 2) {
            $this->session->set_flashdata('error', 'A journal entry needs at least two lines.');
            $this->render('accounting/journal_create', $data);
            return;
        }

        $this->db->trans_start();
        try {
            $je_id = $this->Ledger_model->post(
                $entry_date, $narration ?: null, 'manual', null, null, null,
                $lines, $this->session->userdata('user_id'), $this->active_location_id()
            );
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) throw new Exception('Database error while posting the entry.');
            $this->log_activity('accounting', 'journal_create', 'Posted manual journal entry', $je_id, '');
            $this->session->set_flashdata('success', 'Journal entry posted.');
            redirect('accounting/journal_view/' . $je_id);
        } catch (Exception $e) {
            $this->db->trans_complete();
            $this->session->set_flashdata('error', $e->getMessage());
            $this->render('accounting/journal_create', $data);
        }
    }

    public function journal_view($id = null) {
        $entry = $this->Ledger_model->get_entry_with_lines($id);
        if (!$entry) { show_404(); return; }
        $data['page_title'] = 'Journal Entry ' . $entry->entry_no;
        $data['entry'] = $entry;
        $this->render('accounting/journal_view', $data);
    }

    public function journal_reverse($id = null) {
        $this->require_admin();
        $entry = $this->db->get_where('journal_entries', ['id' => $id])->row();
        if (!$entry) { show_404(); return; }
        if ($entry->status === 'reversed') {
            $this->session->set_flashdata('error', 'This entry is already reversed.');
            redirect('accounting/journal_view/' . $id);
            return;
        }

        $reason = trim($this->input->post('reason', TRUE)) ?: 'Manual reversal';
        $this->db->trans_start();
        try {
            $new_id = $this->Ledger_model->reverse($id, date('Y-m-d'), $reason, $this->session->userdata('user_id'));
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) throw new Exception('Database error while reversing the entry.');
            $this->log_activity('accounting', 'journal_reverse', 'Reversed journal entry ' . $entry->entry_no . ' via new entry', $new_id, $entry->entry_no);
            $this->session->set_flashdata('success', 'Entry reversed.');
            redirect('accounting/journal_view/' . $new_id);
        } catch (Exception $e) {
            $this->db->trans_complete();
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('accounting/journal_view/' . $id);
        }
    }

    // ─── Bank & Cash Accounts ─────────────────────────────────────────────

    public function bank_accounts() {
        $data['page_title'] = 'Bank & Cash Accounts';
        $data['bank_accounts'] = $this->db->select('bank_accounts.*, coa.account_code, coa.account_name AS ledger_name')
            ->from('bank_accounts')
            ->join('chart_of_accounts coa', 'coa.id = bank_accounts.account_id')
            ->where('bank_accounts.status', 1)
            ->order_by('bank_accounts.is_default', 'DESC')
            ->get()->result();
        $this->render('accounting/bank_accounts', $data);
    }

    public function save_bank_account() {
        $this->form_validation->set_rules('account_name', 'Account Name', 'required|trim');
        $this->form_validation->set_rules('account_kind', 'Kind', 'required|in_list[cash,bank]');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors(' '));
            redirect('accounting/bank_accounts');
            return;
        }

        $name = trim($this->input->post('account_name', TRUE));
        $kind = $this->input->post('account_kind', TRUE);
        $parent_code = $kind === 'cash' ? '1100' : '1120';

        $this->db->trans_start();
        $parent = $this->Ledger_model->get_account_by_code($parent_code);
        $last = $this->db->select_max('account_code')->where('parent_id', $parent->id)->get('chart_of_accounts')->row();
        $next_code = $last && $last->account_code ? ((int)$last->account_code + 1) : ((int)$parent_code + 1);

        $coa_id = $this->Ledger_model->create_account([
            'account_code' => (string)$next_code,
            'account_name' => $name,
            'account_type' => 'asset',
            'parent_id'    => $parent->id,
            'is_group'     => 0,
            'opening_balance' => (float)$this->input->post('opening_balance'),
            'opening_balance_type' => 'debit',
        ]);

        $this->db->insert('bank_accounts', [
            'account_id'     => $coa_id,
            'account_name'   => $name,
            'account_kind'   => $kind,
            'bank_name'      => trim($this->input->post('bank_name', TRUE)) ?: null,
            'account_number' => trim($this->input->post('account_number', TRUE)) ?: null,
            'ifsc'           => trim($this->input->post('ifsc', TRUE)) ?: null,
            'branch'         => trim($this->input->post('branch', TRUE)) ?: null,
            'is_default'     => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Database error while creating the account.');
        } else {
            $this->log_activity('accounting', 'create_bank_account', 'Added ' . $kind . ' account "' . $name . '"', $coa_id, (string)$next_code);
            $this->session->set_flashdata('success', 'Account added.');
        }
        redirect('accounting/bank_accounts');
    }

    public function delete_bank_account($id = null) {
        $this->require_admin();
        $ba = $this->db->get_where('bank_accounts', ['id' => $id])->row();
        if (!$ba) { show_404(); return; }
        if ($ba->is_default) {
            $this->session->set_flashdata('error', 'Cannot remove the default account — set another account as default first.');
        } elseif ($this->Ledger_model->account_has_postings($ba->account_id)) {
            $this->session->set_flashdata('error', 'Cannot remove an account that already has journal postings — mark it inactive instead is not yet supported, contact an admin.');
        } else {
            $this->db->where('id', $id)->update('bank_accounts', ['status' => 0]);
            $this->log_activity('accounting', 'delete_bank_account', 'Removed bank/cash account "' . $ba->account_name . '"', $id, '');
            $this->session->set_flashdata('success', 'Account removed.');
        }
        redirect('accounting/bank_accounts');
    }

    // ─── Payment Mode → Account Mapping ───────────────────────────────────

    public function payment_mode_mapping() {
        $data['page_title'] = 'Payment Mode Mapping';
        $data['modes']    = ['cash', 'upi', 'neft', 'cheque', 'card'];
        $data['accounts'] = $this->Ledger_model->get_leaf_accounts('asset');
        $rows = $this->db->where('location_id', 0)->get('payment_mode_accounts')->result();
        $data['mapping'] = [];
        foreach ($rows as $r) $data['mapping'][$r->payment_mode] = (int)$r->account_id;
        $this->render('accounting/payment_mode_mapping', $data);
    }

    public function save_payment_mode_mapping() {
        $modes = ['cash', 'upi', 'neft', 'cheque', 'card'];
        $this->db->trans_start();
        foreach ($modes as $mode) {
            $account_id = (int)$this->input->post('account_' . $mode);
            if (!$account_id) continue;
            $existing = $this->db->where('location_id', 0)->where('payment_mode', $mode)->get('payment_mode_accounts')->row();
            if ($existing) {
                $this->db->where('id', $existing->id)->update('payment_mode_accounts', ['account_id' => $account_id]);
            } else {
                $this->db->insert('payment_mode_accounts', ['location_id' => 0, 'payment_mode' => $mode, 'account_id' => $account_id]);
            }
        }
        $this->db->trans_complete();
        $this->log_activity('accounting', 'payment_mode_mapping', 'Updated payment mode account mapping', 0, '');
        $this->session->set_flashdata('success', 'Payment mode mapping saved.');
        redirect('accounting/payment_mode_mapping');
    }
}
