<?php // FILE: application/views/hsn/import_preview.php ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-eye text-info"></i> HSN Import Preview</h5>
    <a href="<?= base_url('hsn/import') ?>" class="btn btn-sm btn-outline-secondary">&larr; Re-upload</a>
</div>

<?php
$total    = count($preview);
$errors   = array_filter($preview, fn($r) => !empty($r['error']));
$exists   = array_filter($preview, fn($r) => $r['exists'] && empty($r['error']));
$newItems = array_filter($preview, fn($r) => !$r['exists'] && empty($r['error']));
?>

<div class="row mb-3">
    <div class="col-md-3"><div class="card border-primary text-center py-2">
        <div class="h4 font-weight-bold text-primary"><?= $total ?></div><small>Total Rows</small></div></div>
    <div class="col-md-3"><div class="card border-success text-center py-2">
        <div class="h4 font-weight-bold text-success"><?= count($newItems) ?></div><small>New HSN Codes</small></div></div>
    <div class="col-md-3"><div class="card border-warning text-center py-2">
        <div class="h4 font-weight-bold text-warning"><?= count($exists) ?></div><small>Already Exists</small></div></div>
    <div class="col-md-3"><div class="card border-danger text-center py-2">
        <div class="h4 font-weight-bold text-danger"><?= count($errors) ?></div><small>Errors</small></div></div>
</div>

<form method="post" action="<?= base_url('hsn/import_confirm') ?>">

<div class="card shadow-sm mb-3">
<div class="card-body">
<div class="form-row align-items-center">
    <div class="col-md-5">
        <label class="font-weight-bold">For Duplicate HSN codes (already exists):</label>
        <div class="mt-1">
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
        <button type="submit" class="btn btn-success btn-lg"
                onclick="return confirm('Confirm import with selected mode?')">
            <i class="fas fa-file-import"></i> Confirm &amp; Import
        </button>
    </div>
    <?php if (count($errors) > 0): ?>
    <div class="col-md-3">
        <div class="alert alert-warning mb-0 py-2" style="font-size:.82rem;">
            <strong><?= count($errors) ?> row(s)</strong> with errors will be skipped.
        </div>
    </div>
    <?php endif; ?>
</div>
</div>
</div>

<div class="card border-0 shadow-sm">
<div class="card-body p-0">
<table class="table table-sm table-hover mb-0" style="font-size:.83rem;">
    <thead class="thead-dark">
        <tr>
            <th>#</th>
            <th>HSN Code</th>
            <th>Description</th>
            <th class="text-center">IGST %</th>
            <th class="text-center">CGST %</th>
            <th class="text-center">SGST %</th>
            <th class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($preview as $i => $r): ?>
        <tr class="<?= !empty($r['error']) ? 'table-danger' : ($r['exists'] ? 'table-warning' : '') ?>">
            <td><?= $i + 1 ?></td>
            <td><code><?= htmlspecialchars($r['hsn_code']) ?></code></td>
            <td><?= htmlspecialchars($r['description']) ?></td>
            <td class="text-center">
                <span class="badge badge-<?= $r['igst_percent'] == 0 ? 'secondary' : ($r['igst_percent'] <= 5 ? 'success' : ($r['igst_percent'] <= 12 ? 'info' : ($r['igst_percent'] <= 18 ? 'warning' : 'danger'))) ?>">
                    <?= number_format($r['igst_percent'], 2) ?>%
                </span>
            </td>
            <td class="text-center"><?= number_format($r['cgst_percent'], 2) ?>%</td>
            <td class="text-center"><?= number_format($r['sgst_percent'], 2) ?>%</td>
            <td class="text-center">
                <?php if (!empty($r['error'])): ?>
                    <span class="badge badge-danger" title="<?= htmlspecialchars($r['error']) ?>">Error</span>
                <?php elseif ($r['exists']): ?>
                    <span class="badge badge-warning">Exists</span>
                <?php else: ?>
                    <span class="badge badge-success">New</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

</form>
