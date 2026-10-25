<?php
// ============================================================
// FILE: application/models/Ledger_model.php
// Double-entry posting engine + Chart of Accounts data access. See
// sql/40_accounting_module.sql for schema/seed and the balance convention:
// every account's balance = opening_signed + SUM(debit) - SUM(credit) over
// ALL journal_entry_lines (reversals are real rows, never deletions).
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Ledger_model extends CI_Model {

    // ─── Posting engine ──────────────────────────────────────────────────

    // $lines: array of ['account_id'=>, 'debit'=>0, 'credit'=>0, 'party_type'=>null,
    // 'party_id'=>null, 'narration'=>null]. Must be called from inside the caller's
    // already-open trans_start()/trans_complete() block — this method does not open
    // its own transaction (matches Payment_model::create()'s convention). Throws on
    // an unbalanced entry or a line targeting a group (header) account, so a bad
    // posting call fails loudly inside the caller's transaction instead of silently
    // corrupting the ledger.
    public function post($entry_date, $narration, $source, $ref_type, $ref_id, $ref_no,
                          array $lines, $created_by, $location_id = null) {

        $lines = array_values(array_filter($lines, function ($l) {
            return abs((float)($l['debit'] ?? 0)) > 0.004 || abs((float)($l['credit'] ?? 0)) > 0.004;
        }));

        if (!$lines) {
            throw new Exception('Journal entry has no non-zero lines.');
        }

        $debit_total = 0.0;
        $credit_total = 0.0;
        foreach ($lines as $line) {
            $debit_total  += (float)($line['debit']  ?? 0);
            $credit_total += (float)($line['credit'] ?? 0);
        }
        if (abs($debit_total - $credit_total) > 0.01) {
            throw new Exception("Journal entry does not balance: debit {$debit_total} vs credit {$credit_total}.");
        }

        $account_ids = array_unique(array_column($lines, 'account_id'));
        $groups = $this->db->select('id, account_name')->where_in('id', $account_ids)->where('is_group', 1)->get('chart_of_accounts')->result();
        if ($groups) {
            throw new Exception('Cannot post to group account "' . $groups[0]->account_name . '" — group accounts are headers only.');
        }

        $this->load->library('DocumentNumbering');
        $doc = $this->documentnumbering->generate('journal_entry', (int)($location_id ?? 0), $entry_date);

        $this->db->insert('journal_entries', [
            'entry_no'       => $doc['number'],
            'entry_date'     => $entry_date,
            'narration'      => $narration,
            'source'         => $source,
            'ref_type'       => $ref_type,
            'ref_id'         => $ref_id,
            'ref_no'         => $ref_no,
            'total_amount'   => round($debit_total, 2),
            'status'         => 'posted',
            'financial_year' => $doc['financial_year'],
            'location_id'    => $location_id,
            'created_by'     => $created_by,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        $journal_entry_id = $this->db->insert_id();

        foreach ($lines as $line) {
            $this->db->insert('journal_entry_lines', [
                'journal_entry_id' => $journal_entry_id,
                'account_id'       => $line['account_id'],
                'debit'            => round((float)($line['debit']  ?? 0), 2),
                'credit'           => round((float)($line['credit'] ?? 0), 2),
                'party_type'       => $line['party_type'] ?? null,
                'party_id'         => $line['party_id']   ?? null,
                'narration'        => $line['narration']  ?? null,
            ]);
        }

        return $journal_entry_id;
    }

    // Posts a new reversing entry (debit/credit swapped) dated at $reversal_date and
    // marks the original 'reversed' for display only — the original row still counts
    // in every balance query, so historical Trial Balance/GST-period reports already
    // run/filed are never silently rewritten. Also called from inside the caller's
    // open transaction, same as post().
    public function reverse($journal_entry_id, $reversal_date, $reason, $created_by) {
        $original = $this->db->get_where('journal_entries', ['id' => $journal_entry_id])->row();
        if (!$original) {
            throw new Exception('Journal entry not found.');
        }
        if ($original->status === 'reversed') {
            throw new Exception('Journal entry ' . $original->entry_no . ' is already reversed.');
        }

        $lines = $this->db->where('journal_entry_id', $journal_entry_id)->get('journal_entry_lines')->result();
        $reversed_lines = [];
        foreach ($lines as $l) {
            $reversed_lines[] = [
                'account_id' => $l->account_id,
                'debit'      => $l->credit,
                'credit'     => $l->debit,
                'party_type' => $l->party_type,
                'party_id'   => $l->party_id,
                'narration'  => $l->narration,
            ];
        }

        $new_id = $this->post(
            $reversal_date,
            'Reversal of ' . $original->entry_no . ($reason ? (' — ' . $reason) : ''),
            'reversal',
            'journal_entry',
            $journal_entry_id,
            $original->entry_no,
            $reversed_lines,
            $created_by,
            $original->location_id
        );

        $this->db->where('id', $journal_entry_id)->update('journal_entries', [
            'status'        => 'reversed',
            'reversed_note' => $reason,
        ]);

        return $new_id;
    }

    // ─── Balance / ledger queries ────────────────────────────────────────

    public function get_account_balance($account_id, $as_of_date = null) {
        $account = $this->db->get_where('chart_of_accounts', ['id' => $account_id])->row();
        if (!$account) return 0.0;

        $this->db->select('COALESCE(SUM(jel.debit),0) AS total_debit, COALESCE(SUM(jel.credit),0) AS total_credit')
            ->from('journal_entry_lines jel')
            ->join('journal_entries je', 'je.id = jel.journal_entry_id')
            ->where('jel.account_id', $account_id);
        if ($as_of_date) $this->db->where('je.entry_date <=', $as_of_date);
        $row = $this->db->get()->row();

        $opening_signed = $account->opening_balance_type === 'debit' ? (float)$account->opening_balance : -(float)$account->opening_balance;
        return round($opening_signed + (float)$row->total_debit - (float)$row->total_credit, 2);
    }

    // Date-ranged transaction list for one account with a running balance, for the
    // General Ledger report / account register drill-down.
    public function get_ledger($account_id, $from, $to) {
        $account = $this->db->get_where('chart_of_accounts', ['id' => $account_id])->row();
        if (!$account) return ['account' => null, 'opening_balance' => 0, 'lines' => [], 'closing_balance' => 0];

        $opening_balance = $this->get_account_balance($account_id, date('Y-m-d', strtotime($from . ' -1 day')));

        $rows = $this->db->select(
                'je.entry_date, je.entry_no, je.narration AS entry_narration, je.source, ' .
                'je.ref_type, je.ref_id, je.status, jel.debit, jel.credit, ' .
                'jel.narration AS line_narration, jel.party_type, jel.party_id'
            )
            ->from('journal_entry_lines jel')
            ->join('journal_entries je', 'je.id = jel.journal_entry_id')
            ->where('jel.account_id', $account_id)
            ->where('je.entry_date >=', $from)
            ->where('je.entry_date <=', $to)
            ->order_by('je.entry_date', 'ASC')
            ->order_by('je.id', 'ASC')
            ->get()->result();

        $running = $opening_balance;
        foreach ($rows as $r) {
            $running += (float)$r->debit - (float)$r->credit;
            $r->running_balance = round($running, 2);
        }

        return [
            'account'         => $account,
            'opening_balance' => round($opening_balance, 2),
            'lines'           => $rows,
            'closing_balance' => round($running, 2),
        ];
    }

    // All leaf accounts with a non-zero balance as of $as_of_date, split into
    // Debit/Credit columns — must always net to total_debit === total_credit.
    public function get_trial_balance($as_of_date = null) {
        $as_of_date = $as_of_date ?: date('Y-m-d');
        $accounts = $this->db->where('is_group', 0)->where('status', 1)->order_by('account_code', 'ASC')->get('chart_of_accounts')->result();

        $movement = [];
        $q = $this->db->select('jel.account_id, SUM(jel.debit) AS total_debit, SUM(jel.credit) AS total_credit')
            ->from('journal_entry_lines jel')
            ->join('journal_entries je', 'je.id = jel.journal_entry_id')
            ->where('je.entry_date <=', $as_of_date)
            ->group_by('jel.account_id')
            ->get();
        foreach ($q->result() as $m) {
            $movement[$m->account_id] = ['debit' => (float)$m->total_debit, 'credit' => (float)$m->total_credit];
        }

        $rows = [];
        $total_debit = 0.0;
        $total_credit = 0.0;
        foreach ($accounts as $a) {
            $opening_signed = $a->opening_balance_type === 'debit' ? (float)$a->opening_balance : -(float)$a->opening_balance;
            $m = $movement[$a->id] ?? ['debit' => 0, 'credit' => 0];
            $balance = round($opening_signed + $m['debit'] - $m['credit'], 2);
            if (abs($balance) < 0.005) continue;
            $debit  = $balance > 0 ? $balance : 0;
            $credit = $balance < 0 ? -$balance : 0;
            $total_debit  += $debit;
            $total_credit += $credit;
            $rows[] = ['account' => $a, 'debit' => round($debit, 2), 'credit' => round($credit, 2)];
        }

        return ['rows' => $rows, 'total_debit' => round($total_debit, 2), 'total_credit' => round($total_credit, 2), 'as_of_date' => $as_of_date];
    }

    // All journal entries posted on one date (Day Book).
    public function get_day_book($date) {
        return $this->db->where('entry_date', $date)->order_by('id', 'ASC')->get('journal_entries')->result();
    }

    public function get_entry_with_lines($journal_entry_id) {
        $entry = $this->db->get_where('journal_entries', ['id' => $journal_entry_id])->row();
        if (!$entry) return null;
        $entry->lines = $this->db->select('jel.*, coa.account_code, coa.account_name')
            ->from('journal_entry_lines jel')
            ->join('chart_of_accounts coa', 'coa.id = jel.account_id')
            ->where('jel.journal_entry_id', $journal_entry_id)
            ->get()->result();
        return $entry;
    }

    // ─── Chart of Accounts CRUD ──────────────────────────────────────────

    public function get_accounts_tree() {
        return $this->db->where('status', 1)->order_by('account_code', 'ASC')->get('chart_of_accounts')->result();
    }

    public function get_account($id) {
        return $this->db->get_where('chart_of_accounts', ['id' => $id])->row();
    }

    public function get_account_by_code($code) {
        return $this->db->get_where('chart_of_accounts', ['account_code' => $code])->row();
    }

    // Postable (non-group) accounts, optionally filtered by type — used for account
    // pickers on the manual Journal Entry form and the Expenses category dropdown.
    public function get_leaf_accounts($account_type = null) {
        $this->db->where('is_group', 0)->where('status', 1);
        if ($account_type) $this->db->where('account_type', $account_type);
        return $this->db->order_by('account_code', 'ASC')->get('chart_of_accounts')->result();
    }

    public function account_code_exists($code, $exclude_id = null) {
        $this->db->where('account_code', $code);
        if ($exclude_id) $this->db->where('id !=', $exclude_id);
        return $this->db->get('chart_of_accounts')->num_rows() > 0;
    }

    public function create_account($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('chart_of_accounts', $data);
        return $this->db->insert_id();
    }

    public function update_account($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('chart_of_accounts', $data);
    }

    public function account_has_postings($account_id) {
        return $this->db->where('account_id', $account_id)->get('journal_entry_lines')->num_rows() > 0;
    }

    public function account_has_children($account_id) {
        return $this->db->where('parent_id', $account_id)->get('chart_of_accounts')->num_rows() > 0;
    }

    // Soft-delete only — is_system accounts and anything with postings/children are
    // protected by the controller before this is ever called.
    public function delete_account($id) {
        return $this->db->where('id', $id)->update('chart_of_accounts', ['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
