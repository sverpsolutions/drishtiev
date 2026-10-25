<?php // FILE: application/views/plu/import_preview.php
$importable = $counts['new'] + $counts['update'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-eye text-primary mr-1"></i> PLU Import — Preview</h5>
        <small class="text-muted"><?= htmlspecialchars($filename) ?> &middot; <?= count($preview) ?> row(s) read &middot; nothing saved yet</small>
    </div>
    <a href="<?= base_url('plu/import') ?>" class="btn btn-outline-secondary btn-sm">&larr; Choose another file</a>
</div>

<div class="row">
    <?php
    $tiles = [
        ['New PLUs',       $counts['new'],     '#16a34a', 'fa-plus-circle'],
        ['Existing (update)', $counts['update'], '#0284c7', 'fa-sync-alt'],
        ['Warnings',       $counts['warning'], '#d97706', 'fa-exclamation-triangle'],
        ['Errors',         $counts['error'],   '#dc2626', 'fa-times-circle'],
    ];
    foreach ($tiles as $t): ?>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid <?= $t[2] ?> !important;">
                <div class="card-body py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted" style="font-size:.78rem;"><?= $t[0] ?></div>
                        <div class="font-weight-bold" style="font-size:1.7rem;line-height:1.1;color:<?= $t[2] ?>;"><?= number_format($t[1]) ?></div>
                    </div>
                    <i class="fas <?= $t[3] ?> fa-2x" style="color:<?= $t[2] ?>;opacity:.22;"></i>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($importable === 0): ?>
    <div class="alert alert-danger">
        <i class="fas fa-times-circle mr-1"></i>
        <strong>Nothing can be imported.</strong> Every row has an error — see the Reason column below.
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="post" action="<?= base_url('plu/import_confirm') ?>" id="pluConfirmForm">
            <div class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" id="upd" name="update_existing" value="1" checked>
                <label class="custom-control-label" for="upd">
                    <strong>Update the <?= number_format($counts['update']) ?> PLU(s) that already exist</strong>
                    <div class="text-muted" style="font-size:.82rem;">Untick to create new PLUs only and leave existing prices untouched. Blank cells never overwrite a saved value.</div>
                </label>
            </div>
            <div class="custom-control custom-checkbox mb-3">
                <input type="checkbox" class="custom-control-input" id="stk" name="import_stock" value="1">
                <label class="custom-control-label" for="stk">
                    <strong>Also import the Stock Qty column</strong>
                    <div class="text-muted" style="font-size:.82rem;">Off by default — a price sheet would otherwise overwrite counted stock. New PLUs start at 0 unless this is ticked.</div>
                </label>
            </div>

            <button type="submit" class="btn btn-success" <?= $importable === 0 ? 'disabled' : '' ?>>
                <i class="fas fa-check mr-1"></i> Import <?= number_format($importable) ?> Row(s)
            </button>
            <a href="<?= base_url('plu') ?>" class="btn btn-outline-secondary ml-1">Cancel</a>
            <?php if ($counts['error']): ?>
                <span class="text-danger ml-3" style="font-size:.85rem;">
                    <i class="fas fa-info-circle"></i>
                    <?= number_format($counts['error']) ?> row(s) with errors will be skipped — you can download the full list afterwards.
                </span>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold">Row by row</span>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary active" data-filter="all">All</button>
            <button type="button" class="btn btn-outline-danger"  data-filter="error">Errors</button>
            <button type="button" class="btn btn-outline-warning" data-filter="warn">Warnings</button>
        </div>
    </div>
    <div class="table-responsive" style="max-height:60vh;overflow-y:auto;">
        <table class="table table-sm table-hover mb-0" id="pluPreviewTable" style="font-size:.82rem;">
            <thead class="thead-dark" style="position:sticky;top:0;z-index:2;">
                <tr>
                    <th>Row</th><th>Action</th><th>Product</th><th>Item Code</th>
                    <th class="text-right">MRP</th><th class="text-right">Sell</th>
                    <th class="text-right">Cost</th><th class="text-right">GST%</th>
                    <th>Batch</th><th>Expiry</th><th>Notes</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($preview as $r):
                $cls = $r['action'] === 'error' ? 'table-danger'
                     : (!empty($r['warnings']) ? 'table-warning' : '');
                $flag = $r['action'] === 'error' ? 'error' : (!empty($r['warnings']) ? 'warn' : 'ok');
            ?>
                <tr class="<?= $cls ?>" data-flag="<?= $flag ?>">
                    <td class="text-muted"><?= $r['line'] ?></td>
                    <td>
                        <?php if ($r['action'] === 'new'): ?>
                            <span class="badge badge-success">New</span>
                        <?php elseif ($r['action'] === 'update'): ?>
                            <span class="badge badge-info">Update</span>
                            <?php if ($r['plu_code']): ?><div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($r['plu_code']) ?></div><?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-danger">Error</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['product_name']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($r['item_code']) ?></td>
                    <td class="text-right font-weight-bold"><?= $r['mrp'] > 0 ? number_format($r['mrp'], 2) : '—' ?></td>
                    <td class="text-right"><?= $r['selling_rate'] > 0 ? number_format($r['selling_rate'], 2) : '—' ?></td>
                    <td class="text-right"><?= $r['purchase_rate'] > 0 ? number_format($r['purchase_rate'], 2) : '—' ?></td>
                    <td class="text-right"><?= $r['gst_percent'] > 0 ? rtrim(rtrim(number_format($r['gst_percent'], 2), '0'), '.') : '—' ?></td>
                    <td><?= htmlspecialchars($r['batch_no']) ?: '—' ?></td>
                    <td><?= $r['expiry_date'] ? date('d-M-Y', strtotime($r['expiry_date'])) : '—' ?></td>
                    <td style="font-size:.78rem;">
                        <?php foreach ($r['errors'] as $e): ?>
                            <div class="text-danger"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($e) ?></div>
                        <?php endforeach; ?>
                        <?php foreach ($r['warnings'] as $w): ?>
                            <div class="text-warning"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($w) ?></div>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('[data-filter]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('[data-filter]').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var f = btn.dataset.filter;
        document.querySelectorAll('#pluPreviewTable tbody tr').forEach(function (tr) {
            tr.style.display = (f === 'all' || tr.dataset.flag === f) ? '' : 'none';
        });
    });
});
</script>
