<?php // FILE: application/views/reports/negative_stock_audit.php ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <div>
        <h5 class="font-weight-bold mb-0">Negative Stock (Audit)</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Negative Stock</small>
    </div>
    <div class="no-print">
        <a href="<?= base_url('Reports/negative_stock_audit?export=excel' . (!empty($location_id) ? '&location_id=' . (int)$location_id : '')) ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel"></i> Excel</a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i> Print</button>
    </div>
</div>

<div class="alert alert-info py-2" style="font-size:.85rem;">
    Billing is allowed beyond available stock. The <strong>real balance</strong> is kept (e.g. 50 in stock, 51 sold = <strong>-1</strong>) so the next purchase / transfer-in
    recovers it correctly, but every normal screen shows such items as <strong>0</strong>. This list shows the real negative balances for follow-up
    (missed purchase / GRN entry, wrong item billed, or stock take needed).
</div>

<?php if (count($locations ?? []) > 1): ?>
<form method="GET" action="<?= base_url('Reports/negative_stock_audit') ?>" class="form-inline mb-3 no-print">
    <label class="mr-2 small font-weight-bold">Outlet</label>
    <select name="location_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
        <option value="">All Outlets</option>
        <?php foreach ($locations as $l): ?>
        <option value="<?= $l->id ?>" <?= (int)($location_id ?? 0) === (int)$l->id ? 'selected' : '' ?>><?= htmlspecialchars($l->name) ?></option>
        <?php endforeach; ?>
    </select>
</form>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-2 font-weight-bold">
        <?= count($rows) ?> item-location balance(s) below zero
        <?php if ($rows): ?>
        <span class="text-danger ml-2">(total shortfall <?= rtrim(rtrim(number_format(array_sum(array_map(function ($r) { return -(float)$r->actual_qty; }, $rows)), 3), '0'), '.') ?> units)</span>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:.85rem;">
            <thead class="thead-light">
                <tr>
                    <th>Item Code</th><th>Item</th><th>Category</th><th>Location</th>
                    <th class="text-right">Actual Balance</th><th class="text-right">Display Stock</th>
                    <th>Last Movement</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No negative stock balances.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$r->item_code) ?></td>
                    <td><?= htmlspecialchars($r->name) ?></td>
                    <td><?= htmlspecialchars((string)$r->category_name) ?></td>
                    <td><?= htmlspecialchars((string)$r->location_name) ?></td>
                    <td class="text-right font-weight-bold text-danger"><?= rtrim(rtrim(number_format((float)$r->actual_qty, 3), '0'), '.') ?> <?= htmlspecialchars((string)$r->unit) ?></td>
                    <td class="text-right">0</td>
                    <td>
                        <?php if ($r->last): ?>
                            <?= htmlspecialchars(str_replace('_', ' ', $r->last->movement_type)) ?> <?= rtrim(rtrim(number_format((float)$r->last->quantity, 3), '0'), '.') ?>
                            <small class="text-muted d-block"><?= htmlspecialchars((string)$r->last->reference_no) ?> · <?= date('d-M-Y H:i', strtotime($r->last->created_at)) ?></small>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<style>@media print { .no-print { display:none !important; } }</style>
