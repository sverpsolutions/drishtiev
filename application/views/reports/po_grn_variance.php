<?php // FILE: application/views/reports/po_grn_variance.php ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-exchange-alt text-primary"></i> PO vs GRN Variance</h5>
        <small class="text-muted">Ordered vs received/accepted/rejected quantity, per purchase order line</small>
    </div>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="fas fa-print"></i> Print</button>
</div>

<!-- Filter Form -->
<div class="card border-0 shadow-sm mb-3">
<div class="card-body py-3">
<form method="get" action="<?= base_url('reports/po_grn_variance') ?>" class="form-row align-items-end">
    <div class="col-md-3">
        <label class="small font-weight-bold">From Date</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
    </div>
    <div class="col-md-3">
        <label class="small font-weight-bold">To Date</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
    </div>
    <div class="col-md-3">
        <div class="form-check mt-4">
            <input type="checkbox" class="form-check-input" id="showAll" name="show_all" value="1" <?= !$pending_only ? 'checked' : '' ?> onchange="this.form.submit()">
            <label class="form-check-label small" for="showAll">Show all lines (not just pending)</label>
        </div>
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Apply</button>
    </div>
</form>
</div>
</div>

<?php
$total_pending_qty = array_sum(array_column($lines, 'pending_qty'));
$total_rejected_qty = array_sum(array_column($lines, 'rejected_qty'));
$po_count = count(array_unique(array_column($lines, 'po_number')));
?>

<!-- Stat Cards -->
<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3 text-center">
            <div class="text-muted small">POs Involved</div>
            <div class="h5 font-weight-bold mb-0"><?= $po_count ?></div>
        </div></div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3 text-center">
            <div class="text-muted small">Line Items</div>
            <div class="h5 font-weight-bold mb-0"><?= count($lines) ?></div>
        </div></div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3 text-center">
            <div class="text-muted small">Total Pending Qty</div>
            <div class="h5 font-weight-bold mb-0 text-warning"><?= number_format($total_pending_qty, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3 text-center">
            <div class="text-muted small">Total Rejected Qty</div>
            <div class="h5 font-weight-bold mb-0 text-danger"><?= number_format($total_rejected_qty, 2) ?></div>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
<div class="card-header bg-dark text-white py-2"><span class="font-weight-bold">Line-Level Detail</span></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover table-bordered mb-0" style="font-size:.82rem;">
<thead class="thead-dark">
<tr>
    <th>PO Number</th>
    <th>Supplier</th>
    <th>Product</th>
    <th class="text-right">Ordered</th>
    <th class="text-right">Accepted</th>
    <th class="text-right">Rejected</th>
    <th class="text-right">Pending</th>
    <th>PO Status</th>
    <th>Expected Delivery</th>
</tr>
</thead>
<tbody>
<?php foreach ($lines as $l): ?>
<tr class="<?= $l['pending_qty'] > 0 ? 'table-warning' : '' ?>">
    <td><?= htmlspecialchars($l['po_number']) ?></td>
    <td><?= htmlspecialchars($l['supplier_name']) ?></td>
    <td><?= htmlspecialchars($l['product_name'] ?? 'N/A') ?> <small class="text-muted"><?= $l['item_code'] ?? '' ?></small></td>
    <td class="text-right"><?= number_format($l['ordered_qty'], 2) ?></td>
    <td class="text-right text-success"><?= number_format($l['accepted_qty'], 2) ?></td>
    <td class="text-right <?= $l['rejected_qty'] > 0 ? 'text-danger font-weight-bold' : 'text-muted' ?>"><?= number_format($l['rejected_qty'], 2) ?></td>
    <td class="text-right <?= $l['pending_qty'] > 0 ? 'text-warning font-weight-bold' : 'text-muted' ?>"><?= number_format($l['pending_qty'], 2) ?></td>
    <td><span class="badge badge-secondary"><?= ucfirst(str_replace('_', ' ', $l['po_status'])) ?></span></td>
    <td><?= $l['expected_delivery'] ? date('d-m-Y', strtotime($l['expected_delivery'])) : 'N/A' ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($lines)): ?>
<tr><td colspan="9" class="text-center text-muted py-4">No PO lines found for the selected filters.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
