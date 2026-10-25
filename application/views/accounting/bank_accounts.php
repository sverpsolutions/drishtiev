<?php // FILE: application/views/accounting/bank_accounts.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Bank &amp; Cash Accounts</h5>
        <small class="text-muted">Each account here is also a ledger account in the Chart of Accounts</small>
    </div>
    <div>
        <a href="<?= base_url('accounting/payment_mode_mapping') ?>" class="btn btn-outline-dark btn-sm">Payment Mode Mapping</a>
        <button class="btn btn-dark btn-sm ml-1" data-toggle="modal" data-target="#baModal">+ Add Account</button>
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
                        <th>Account</th>
                        <th>Kind</th>
                        <th>Bank</th>
                        <th>Account No.</th>
                        <th>Ledger Code</th>
                        <th class="text-center">Default</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bank_accounts as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b->account_name) ?></td>
                        <td class="text-capitalize"><?= htmlspecialchars($b->account_kind) ?></td>
                        <td><?= htmlspecialchars($b->bank_name ?? '') ?></td>
                        <td><?= htmlspecialchars($b->account_number ?? '') ?></td>
                        <td><code><?= htmlspecialchars($b->account_code) ?></code></td>
                        <td class="text-center"><?= $b->is_default ? '<i class="fas fa-star text-warning"></i>' : '' ?></td>
                        <td class="text-center">
                            <?php if (!$b->is_default): ?>
                            <a href="<?= base_url('accounting/delete_bank_account/' . $b->id) ?>" class="btn btn-outline-danger btn-sm py-0" onclick="return confirm('Remove this account?')"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="baModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="<?= base_url('accounting/save_bank_account') ?>">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Bank / Cash Account</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
            <label class="font-weight-bold">Account Name</label>
            <input type="text" name="account_name" class="form-control" required placeholder="e.g. HDFC Current A/C">
        </div>
        <div class="form-group">
            <label class="font-weight-bold">Kind</label>
            <select name="account_kind" class="form-control" required>
                <option value="bank">Bank</option>
                <option value="cash">Cash (till / drawer)</option>
            </select>
        </div>
        <div class="form-row">
            <div class="col form-group">
                <label class="font-weight-bold">Bank Name</label>
                <input type="text" name="bank_name" class="form-control">
            </div>
            <div class="col form-group">
                <label class="font-weight-bold">Account Number</label>
                <input type="text" name="account_number" class="form-control">
            </div>
        </div>
        <div class="form-row">
            <div class="col form-group">
                <label class="font-weight-bold">IFSC</label>
                <input type="text" name="ifsc" class="form-control">
            </div>
            <div class="col form-group">
                <label class="font-weight-bold">Branch</label>
                <input type="text" name="branch" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label class="font-weight-bold">Opening Balance</label>
            <input type="text" name="opening_balance" class="form-control" value="0.00">
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
