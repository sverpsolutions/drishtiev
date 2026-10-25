<?php // FILE: application/views/accounting/journal_view.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Journal Entry <?= htmlspecialchars($entry->entry_no) ?></h5>
        <small class="text-muted"><?= date('d M Y', strtotime($entry->entry_date)) ?> — <span class="text-capitalize"><?= str_replace('_', ' ', $entry->source) ?></span></small>
    </div>
    <a href="<?= base_url('accounting/journal_entries') ?>" class="btn btn-outline-dark btn-sm">Back to Journal Entries</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><strong>Status:</strong>
                <?= $entry->status === 'reversed' ? '<span class="badge badge-secondary">Reversed</span>' : '<span class="badge badge-success">Posted</span>' ?>
            </div>
            <div class="col-md-3"><strong>Total:</strong> ₹<?= number_format($entry->total_amount, 2) ?></div>
            <?php if (!empty($entry->ref_type)): ?>
            <div class="col-md-3"><strong>Reference:</strong> <?= htmlspecialchars($entry->ref_type) ?> #<?= $entry->ref_id ?></div>
            <?php endif; ?>
        </div>
        <?php if (!empty($entry->narration)): ?>
            <div class="mt-2"><strong>Narration:</strong> <?= htmlspecialchars($entry->narration) ?></div>
        <?php endif; ?>
        <?php if ($entry->status === 'reversed' && !empty($entry->reversed_note)): ?>
            <div class="mt-2 text-muted"><strong>Reversal note:</strong> <?= htmlspecialchars($entry->reversed_note) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Account</th>
                        <th>Narration</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                    </tr>
                </thead>
                <tbody>
                <?php $td = 0; $tc = 0; foreach ($entry->lines as $l): $td += $l->debit; $tc += $l->credit; ?>
                    <tr>
                        <td><?= htmlspecialchars($l->account_code . ' — ' . $l->account_name) ?></td>
                        <td><?= htmlspecialchars($l->narration ?? '') ?></td>
                        <td class="text-right"><?= $l->debit > 0 ? number_format($l->debit, 2) : '' ?></td>
                        <td class="text-right"><?= $l->credit > 0 ? number_format($l->credit, 2) : '' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold">
                        <td colspan="2" class="text-right">Total</td>
                        <td class="text-right"><?= number_format($td, 2) ?></td>
                        <td class="text-right"><?= number_format($tc, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php if ($entry->status === 'posted' && $is_admin): ?>
<div class="mt-3">
    <form method="post" action="<?= base_url('accounting/journal_reverse/' . $entry->id) ?>" onsubmit="return confirm('Reverse this entry? A new offsetting entry will be posted.');" class="form-inline">
        <input type="text" name="reason" class="form-control form-control-sm mr-2" placeholder="Reason (optional)" style="width:260px;">
        <button type="submit" class="btn btn-outline-danger btn-sm">Reverse Entry</button>
    </form>
</div>
<?php endif; ?>
