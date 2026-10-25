<?php // FILE: application/views/reports/stock_take_variance.php ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-balance-scale text-primary"></i> Stock-Take Variance</h5>
        <small class="text-muted">Shortage/surplus value impact across all completed stock-takes in a period</small>
    </div>
</div>

<!-- Filter Form -->
<div class="card border-0 shadow-sm mb-3">
<div class="card-body py-3">
<form method="get" action="<?= base_url('reports/stock_take_variance') ?>" class="form-row align-items-end">
    <div class="col-md-3">
        <label class="small font-weight-bold">From Date</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
    </div>
    <div class="col-md-3">
        <label class="small font-weight-bold">To Date</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
    </div>
    <div class="col-md-3">
        <?php include APPPATH.'views/reports/_location_filter.php'; ?>
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Apply</button>
    </div>
</form>
</div>
</div>

<!-- Stat Cards -->
<div class="row mb-3">
    <div class="col-md-4 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3 text-center">
            <div class="text-muted small">Stock-Takes in Period</div>
            <div class="h5 font-weight-bold mb-0"><?= count($takes) ?></div>
        </div></div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3 text-center">
            <div class="text-muted small">Total Surplus Value</div>
            <div class="h5 font-weight-bold mb-0 text-success">₹<?= number_format($total_surplus_value, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3 text-center">
            <div class="text-muted small">Total Shortage Value</div>
            <div class="h5 font-weight-bold mb-0 text-danger">₹<?= number_format($total_shortage_value, 2) ?></div>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
<div class="card-header bg-dark text-white py-2"><span class="font-weight-bold">Per Stock-Take Rollup</span></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover table-bordered mb-0" style="font-size:.85rem;">
<thead class="thead-dark">
<tr>
    <th>Take No</th>
    <th>Location</th>
    <th>Finalized</th>
    <th class="text-right">Variance Lines</th>
    <th class="text-right">Surplus Value</th>
    <th class="text-right">Shortage Value</th>
    <th class="text-right">Net Impact</th>
    <th></th>
</tr>
</thead>
<tbody>
<?php foreach ($takes as $t): $net = (float)$t['surplus_value'] - (float)$t['shortage_value']; ?>
<tr>
    <td><?= htmlspecialchars($t['take_no']) ?></td>
    <td><?= htmlspecialchars($t['location_name']) ?></td>
    <td><?= $t['finalized_at'] ? date('d-m-Y', strtotime($t['finalized_at'])) : 'N/A' ?></td>
    <td class="text-right"><?= (int)$t['variance_lines'] ?></td>
    <td class="text-right text-success">₹<?= number_format($t['surplus_value'], 2) ?></td>
    <td class="text-right text-danger">₹<?= number_format($t['shortage_value'], 2) ?></td>
    <td class="text-right font-weight-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">₹<?= number_format($net, 2) ?></td>
    <td><a href="<?= base_url('stock_take/variance_report/'.$t['id']) ?>" target="_blank" class="btn btn-outline-primary btn-xs">View Detail</a></td>
</tr>
<?php endforeach; ?>
<?php if (empty($takes)): ?>
<tr><td colspan="8" class="text-center text-muted py-4">No completed stock-takes found for the selected filters.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
