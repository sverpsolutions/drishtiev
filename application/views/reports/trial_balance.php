<?php // FILE: application/views/reports/trial_balance.php
$ep = 'as_of=' . urlencode($as_of);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Trial Balance</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Trial Balance</small>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route' => 'reports/trial_balance', 'export_params' => $ep, 'export_title' => $export_title]); ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="form-row align-items-end">
            <div class="col-auto form-group mb-0">
                <label class="small font-weight-bold mb-0">As of</label>
                <input type="date" name="as_of" class="form-control form-control-sm" value="<?= htmlspecialchars($as_of) ?>">
            </div>
            <div class="col-auto form-group mb-0">
                <button class="btn btn-outline-dark btn-sm">Go</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">
        As of <?= date('d M Y', strtotime($as_of)) ?>
        <?php $balanced = abs($tb['total_debit'] - $tb['total_credit']) < 0.01; ?>
        <span class="float-right <?= $balanced ? 'text-success' : 'text-danger' ?>">
            <?= $balanced ? '✓ Balanced' : '✗ NOT BALANCED' ?>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Code</th>
                        <th>Account</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($tb['rows'])): foreach ($tb['rows'] as $r): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($r['account']->account_code) ?></code></td>
                        <td><a href="<?= base_url('reports/general_ledger?account_id=' . $r['account']->id) ?>"><?= htmlspecialchars($r['account']->account_name) ?></a></td>
                        <td class="text-right"><?= $r['debit'] > 0 ? number_format($r['debit'], 2) : '' ?></td>
                        <td class="text-right"><?= $r['credit'] > 0 ? number_format($r['credit'], 2) : '' ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No postings yet.</td></tr>
                <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold">
                        <td colspan="2" class="text-right">Total</td>
                        <td class="text-right"><?= number_format($tb['total_debit'], 2) ?></td>
                        <td class="text-right"><?= number_format($tb['total_credit'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
