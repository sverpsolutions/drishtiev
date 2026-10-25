<?php // FILE: application/views/accounting/payment_mode_mapping.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Payment Mode Mapping</h5>
        <small class="text-muted">Which ledger account each payment mode posts to on invoices, purchases, and payments</small>
    </div>
    <a href="<?= base_url('accounting/bank_accounts') ?>" class="btn btn-outline-dark btn-sm">Bank &amp; Cash Accounts</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= base_url('accounting/save_payment_mode_mapping') ?>">
            <?php foreach ($modes as $mode): ?>
            <div class="form-row align-items-center mb-2">
                <div class="col-md-3 text-capitalize font-weight-bold"><?= htmlspecialchars($mode) ?></div>
                <div class="col-md-6">
                    <select name="account_<?= $mode ?>" class="form-control form-control-sm">
                        <option value="">— not mapped —</option>
                        <?php foreach ($accounts as $a): ?>
                            <option value="<?= $a->id ?>" <?= (($mapping[$mode] ?? null) == $a->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a->account_code . ' — ' . $a->account_name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-dark btn-sm mt-2">Save Mapping</button>
        </form>
        <p class="text-muted small mt-3 mb-0">"Credit" and "Split" payment modes never map here — credit sales post to Sundry Debtors, and split payments look up each individual mode.</p>
    </div>
</div>
