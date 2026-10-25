
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-eye text-info"></i> Import Preview</h4>
    <a href="<?= base_url('products/import') ?>" class="btn btn-sm btn-outline-secondary">← Back / Re-upload</a>
</div>

<?php
$total    = count($preview);
$errors   = array_filter($preview, fn($r) => !empty($r['error']));
$exists   = array_filter($preview, fn($r) => $r['exists'] && empty($r['error']));
$newItems = array_filter($preview, fn($r) => !$r['exists'] && empty($r['error']));
?>

<!-- Stats -->
<div class="row mb-3">
    <div class="col-md-3"><div class="card border-primary text-center py-2">
        <div class="h4 font-weight-bold text-primary"><?= $total ?></div><small>Total Rows</small></div></div>
    <div class="col-md-3"><div class="card border-success text-center py-2">
        <div class="h4 font-weight-bold text-success"><?= count($newItems) ?></div><small>New Items</small></div></div>
    <div class="col-md-3"><div class="card border-warning text-center py-2">
        <div class="h4 font-weight-bold text-warning"><?= count($exists) ?></div><small>Existing (Duplicates)</small></div></div>
    <div class="col-md-3"><div class="card border-danger text-center py-2">
        <div class="h4 font-weight-bold text-danger"><?= count($errors) ?></div><small>Errors</small></div></div>
</div>

<form method="post" action="<?= base_url('products/import_confirm') ?>">

<!-- Mode Selection -->
<div class="card shadow-sm mb-3">
<div class="card-body">
<div class="form-row align-items-center">
    <div class="col-md-4">
        <label><b>For Duplicate Items (same item_code or name):</b></label>
        <div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="mode" id="mode_insert" value="insert" checked>
                <label class="form-check-label" for="mode_insert">Skip duplicates</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="mode" id="mode_update" value="update">
                <label class="form-check-label" for="mode_update">Update existing</label>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Confirm import with selected mode?')">
            <i class="fas fa-file-import"></i> Confirm & Import
        </button>
    </div>
</div>
</div>
</div>

</form>

<!-- Preview Table -->
<div class="card shadow-sm">
<div class="card-header d-flex justify-content-between">
    <b>Data Preview</b>
    <small class="text-muted">
        <?php if (isset($preview_total) && $preview_total > count($preview_display)): ?>
            Showing first <?= count($preview_display) ?> of <?= $preview_total ?> rows — all <?= $preview_total ?> will still be processed on confirm.
        <?php else: ?>
            Rows with errors will be skipped. Review before confirming.
        <?php endif; ?>
    </small>
</div>
<div class="card-body p-0">
<div class="table-responsive" style="max-height:500px; overflow-y:auto;">
<table class="table table-sm table-bordered mb-0">
<thead class="thead-dark" style="position:sticky;top:0;z-index:10;">
<tr>
    <th>Row</th>
    <th>Item Code</th>
    <th>Name/Description</th>
    <th>Brand</th>
    <th>Cat.</th>
    <th>Unit</th>
    <th class="text-right bg-success text-white">Basic Cost</th>
    <th class="text-right">CP (incl.GST)</th>
    <th class="text-right">MRP</th>
    <th class="text-right">SP</th>
    <th class="text-right">WSP</th>
    <th class="text-right">GST%</th>
    <th class="text-right">Stock</th>
    <th>HSN</th>
    <th>PCS Barcode</th>
    <th>CRT Barcode</th>
    <th>Status</th>
    <th>Error</th>
</tr>
</thead>
<tbody>
<?php foreach ($preview_display as $row): ?>
<tr class="<?= !empty($row['error']) ? 'table-danger' : ($row['exists'] ? 'table-warning' : '') ?>">
    <td><?= $row['row'] ?></td>
    <td><?= htmlspecialchars($row['item_code']) ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['brand'] ?? '') ?></td>
    <td><?= htmlspecialchars($row['category']) ?></td>
    <td><?= $row['unit'] ?></td>
    <td class="text-right font-weight-bold text-primary">₹<?= number_format($row['basic_cost'] ?? 0, 2) ?></td>
    <td class="text-right text-success">₹<?= number_format($row['cost_price'], 2) ?></td>
    <td class="text-right">₹<?= number_format($row['mrp'] ?? 0, 2) ?></td>
    <td class="text-right">₹<?= number_format($row['rate'], 2) ?></td>
    <td class="text-right text-warning">₹<?= number_format($row['wsp'] ?? 0, 2) ?></td>
    <td class="text-right">
        <?= $row['gst'] ?>%
        <?php if (!empty($row['hsn_id'])): ?>
            <span class="badge badge-success" title="Auto-filled from HSN Master">HSN✓</span>
        <?php endif; ?>
    </td>
    <td class="text-right"><?= $row['stock'] ?></td>
    <td><code><?= htmlspecialchars($row['hsn_code'] ?? '') ?></code></td>
    <td><small class="text-primary font-weight-bold"><?= htmlspecialchars($row['pcs_barcode'] ?? '') ?></small></td>
    <td><small class="text-secondary"><?= htmlspecialchars($row['crt_barcode'] ?? '') ?></small></td>
    <td>
        <?php if (!empty($row['error'])): ?>
            <span class="badge badge-danger">Error</span>
        <?php elseif ($row['exists']): ?>
            <span class="badge badge-warning">Exists</span>
        <?php else: ?>
            <span class="badge badge-success">New</span>
        <?php endif; ?>
    </td>
    <td><small class="text-danger"><?= htmlspecialchars($row['error']) ?></small></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

