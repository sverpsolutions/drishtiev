<?php // FILE: application/views/reports/general_ledger.php
$ep = 'account_id=' . (int)$account_id . '&from=' . urlencode($from) . '&to=' . urlencode($to);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">General Ledger</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / General Ledger</small>
    </div>
    <?php if ($account_id): ?>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route' => 'reports/general_ledger', 'export_params' => $ep, 'export_title' => $ledger['account']->account_name ?? 'General Ledger']); ?>
    </div>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="form-row align-items-end">
            <div class="col-md-4 form-group mb-0">
                <label class="small font-weight-bold mb-0">Account</label>
                <select name="account_id" class="form-control form-control-sm" required>
                    <option value="">Select account...</option>
                    <?php foreach ($accounts as $a): ?>
                        <option value="<?= $a->id ?>" <?= $a->id == $account_id ? 'selected' : '' ?>><?= htmlspecialchars($a->account_code . ' — ' . $a->account_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto form-group mb-0">
                <label class="small font-weight-bold mb-0">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col-auto form-group mb-0">
                <label class="small font-weight-bold mb-0">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col-auto form-group mb-0">
                <button class="btn btn-outline-dark btn-sm">Go</button>
            </div>
        </form>
    </div>
</div>

<?php if ($account_id && $ledger): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">
        <?= htmlspecialchars($ledger['account']->account_code . ' — ' . $ledger['account']->account_name) ?>
        <span class="float-right">Opening: ₹<?= number_format($ledger['opening_balance'], 2) ?> &nbsp; Closing: ₹<?= number_format($ledger['closing_balance'], 2) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Date</th>
                        <th>Entry No</th>
                        <th>Narration</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($ledger['lines'])): foreach ($ledger['lines'] as $l): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($l->entry_date)) ?></td>
                        <td><?= htmlspecialchars($l->entry_no) ?><?= $l->status === 'reversed' ? ' <span class="badge badge-secondary">reversed</span>' : '' ?></td>
                        <td><?= htmlspecialchars($l->line_narration ?: $l->entry_narration) ?></td>
                        <td class="text-right"><?= $l->debit > 0 ? number_format($l->debit, 2) : '' ?></td>
                        <td class="text-right"><?= $l->credit > 0 ? number_format($l->credit, 2) : '' ?></td>
                        <td class="text-right"><?= number_format($l->running_balance, 2) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No transactions in this date range.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php elseif ($account_id): ?>
    <div class="alert alert-warning">Account not found.</div>
<?php endif; ?>
