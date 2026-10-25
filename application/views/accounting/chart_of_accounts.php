<?php // FILE: application/views/accounting/chart_of_accounts.php
$by_id = [];
foreach ($accounts as $a) $by_id[$a->id] = $a;
$depth_of = function ($a) use (&$depth_of, $by_id) {
    $d = 0;
    $cur = $a;
    while ($cur->parent_id && isset($by_id[$cur->parent_id])) {
        $d++;
        $cur = $by_id[$cur->parent_id];
        if ($d > 10) break;
    }
    return $d;
};
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Chart of Accounts</h5>
        <small class="text-muted">Ledger accounts used by Journal Entries, Expenses, and every auto-posted transaction</small>
    </div>
    <div>
        <a href="<?= base_url('accounting/journal_entries') ?>" class="btn btn-outline-dark btn-sm">Journal Entries</a>
        <button class="btn btn-dark btn-sm ml-1" data-toggle="modal" data-target="#acctModal" onclick="resetAcctForm()">+ Add Account</button>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Code</th>
                        <th>Account</th>
                        <th>Type</th>
                        <th class="text-right">Opening Balance</th>
                        <th class="text-center">System</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($accounts as $a): $depth = $depth_of($a); ?>
                    <tr>
                        <td><code><?= htmlspecialchars($a->account_code) ?></code></td>
                        <td style="padding-left:<?= 12 + $depth * 22 ?>px;">
                            <?= $a->is_group ? '<strong>' : '' ?><?= htmlspecialchars($a->account_name) ?><?= $a->is_group ? '</strong> <span class="badge badge-light border">group</span>' : '' ?>
                        </td>
                        <td><span class="text-capitalize"><?= htmlspecialchars($a->account_type) ?></span></td>
                        <td class="text-right"><?= $a->is_group ? '—' : '₹' . number_format($a->opening_balance, 2) . ' ' . ($a->opening_balance_type === 'credit' ? 'Cr' : 'Dr') ?></td>
                        <td class="text-center"><?= $a->is_system ? '<i class="fas fa-lock text-muted" title="System account"></i>' : '' ?></td>
                        <td class="text-center">
                            <button class="btn btn-outline-secondary btn-sm py-0" onclick='editAcct(<?= json_encode($a) ?>)'><i class="fas fa-edit"></i></button>
                            <?php if (!$a->is_system): ?>
                            <a href="<?= base_url('accounting/delete_account/' . $a->id) ?>" class="btn btn-outline-danger btn-sm py-0" onclick="return confirm('Delete this account?')"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit Account -->
<div class="modal fade" id="acctModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="<?= base_url('accounting/save_account') ?>">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="acctModalTitle">Add Account</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="f_id" value="">
        <div class="form-group">
            <label class="font-weight-bold">Account Name</label>
            <input type="text" name="account_name" id="f_name" class="form-control" required>
        </div>
        <div class="form-row">
            <div class="col form-group">
                <label class="font-weight-bold">Account Code</label>
                <input type="text" name="account_code" id="f_code" class="form-control" required>
            </div>
            <div class="col form-group">
                <label class="font-weight-bold">Type</label>
                <select name="account_type" id="f_type" class="form-control" required>
                    <option value="asset">Asset</option>
                    <option value="liability">Liability</option>
                    <option value="equity">Equity</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="font-weight-bold">Parent (Group) Account</label>
            <select name="parent_id" id="f_parent" class="form-control">
                <option value="">— none (top-level) —</option>
                <?php foreach ($accounts as $a): if (!$a->is_group) continue; ?>
                    <option value="<?= $a->id ?>"><?= str_repeat('— ', $depth_of($a)) . htmlspecialchars($a->account_code . ' ' . $a->account_name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="col-auto form-group">
                <div class="custom-control custom-checkbox mt-4">
                    <input type="checkbox" class="custom-control-input" name="is_group" id="f_is_group" value="1">
                    <label class="custom-control-label" for="f_is_group">This is a group (header, not postable)</label>
                </div>
            </div>
        </div>
        <div class="form-row" id="f_opening_wrap">
            <div class="col form-group">
                <label class="font-weight-bold">Opening Balance</label>
                <input type="text" name="opening_balance" id="f_opening_balance" class="form-control" value="0.00">
            </div>
            <div class="col form-group">
                <label class="font-weight-bold">&nbsp;</label>
                <select name="opening_balance_type" id="f_opening_type" class="form-control">
                    <option value="debit">Debit</option>
                    <option value="credit">Credit</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="font-weight-bold">Description</label>
            <input type="text" name="description" id="f_description" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-dark">Save</button>
      </div>
    </div>
    </form>
  </div>
</div>

<script>
function resetAcctForm() {
    document.getElementById('acctModalTitle').textContent = 'Add Account';
    document.getElementById('f_id').value = '';
    document.getElementById('f_name').value = '';
    document.getElementById('f_code').value = '';
    document.getElementById('f_type').value = 'asset';
    document.getElementById('f_parent').value = '';
    document.getElementById('f_is_group').checked = false;
    document.getElementById('f_opening_balance').value = '0.00';
    document.getElementById('f_opening_type').value = 'debit';
    document.getElementById('f_description').value = '';
}
function editAcct(a) {
    document.getElementById('acctModalTitle').textContent = 'Edit Account';
    document.getElementById('f_id').value = a.id;
    document.getElementById('f_name').value = a.account_name;
    document.getElementById('f_code').value = a.account_code;
    document.getElementById('f_type').value = a.account_type;
    document.getElementById('f_parent').value = a.parent_id || '';
    document.getElementById('f_is_group').checked = a.is_group == 1;
    document.getElementById('f_opening_balance').value = parseFloat(a.opening_balance).toFixed(2);
    document.getElementById('f_opening_type').value = a.opening_balance_type;
    document.getElementById('f_description').value = a.description || '';
    $('#acctModal').modal('show');
}
</script>
