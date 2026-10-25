<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-magic text-info mr-2"></i>Smart Purchase Suggestions</h5>
        <small class="text-muted">AI-powered quantity recommendations based on sales analysis</small>
    </div>
    <div class="d-flex" style="gap:5px;">
        <?php if (!empty($suggestions)): ?>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print mr-1"></i> Print</button>
        <?php endif; ?>
        <?php if (!empty($suggestions) && $supplier_id): ?>
        <a href="<?= base_url('purchase_orders/create?supplier_id='.$supplier_id) ?>" class="btn btn-success btn-sm"><i class="fas fa-file-invoice mr-1"></i> Create PO from Suggestions</a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Panel -->
<div class="card border-0 shadow-sm mb-3 d-print-none">
    <div class="card-body py-2">
        <form method="get" class="form-inline flex-wrap">
            <label class="mr-2 font-weight-bold small">Supplier:</label>
            <select name="supplier_id" class="form-control form-control-sm mr-3 mb-1">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $sid => $sname): ?>
                <option value="<?= $sid ?>" <?= $supplier_id==$sid?'selected':'' ?>><?= htmlspecialchars($sname) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="mr-2 font-weight-bold small">Brand:</label>
            <select name="brand_id" class="form-control form-control-sm mr-3 mb-1">
                <option value="">All Brands</option>
                <?php foreach ($brands as $b): ?>
                <option value="<?= $b->id ?>" <?= $brand_id==$b->id?'selected':'' ?>><?= htmlspecialchars($b->name) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="mr-2 font-weight-bold small">Period:</label>
            <?php $ps=[7,10,15,30,45,60]; ?>
            <?php foreach ($ps as $p): ?>
            <div class="custom-control custom-radio custom-control-inline mb-1">
                <input type="radio" name="days" id="p<?= $p ?>" value="<?= $p ?>" class="custom-control-input" <?= $days==$p&&!$from_date?'checked':'' ?>>
                <label class="custom-control-label" for="p<?= $p ?>"><?= $p ?>d</label>
            </div>
            <?php endforeach; ?>
            <div class="mx-2 mb-1"><small class="text-muted">or Custom:</small></div>
            <input type="date" name="from_date" class="form-control form-control-sm mr-1 mb-1" value="<?= $from_date ?>">
            <span class="mb-1 mr-1">–</span>
            <input type="date" name="to_date"   class="form-control form-control-sm mr-2 mb-1" value="<?= $to_date ?>">
            <button type="submit" class="btn btn-dark btn-sm mb-1"><i class="fas fa-calculator mr-1"></i> Calculate</button>
        </form>
    </div>
</div>

<?php if (empty($suggestions) && !$supplier_id && !$brand_id && !$from_date): ?>
<div class="alert alert-info d-print-none">Select a supplier, brand, or date range and click Calculate to see smart purchase suggestions.</div>
<?php elseif (empty($suggestions)): ?>
<div class="alert alert-warning d-print-none">No products found for the selected criteria.</div>
<?php else: ?>

<?php
$print_scope = 'All Suppliers / Brands';
if ($supplier_id) {
    $print_scope = 'Supplier: ' . ($suppliers[$supplier_id] ?? '');
} elseif ($brand_id) {
    $matched_brand = current(array_filter($brands, fn($b) => $b->id == $brand_id));
    $print_scope = 'Brand: ' . ($matched_brand ? $matched_brand->name : '');
}
?>
<div class="d-none d-print-block mb-3">
    <h5 class="font-weight-bold mb-0">Smart Purchase Suggestions</h5>
    <div class="small text-muted">
        <?= htmlspecialchars($print_scope) ?>
        &nbsp;|&nbsp; Printed: <?= date('d M Y H:i') ?>
    </div>
</div>

<!-- Formula Info -->
<div class="alert alert-light border-left border-info mb-3 d-print-none" style="border-left-width:4px!important;font-size:.82rem;">
    <i class="fas fa-info-circle text-info mr-1"></i>
    <strong>Formula:</strong> Suggested Qty = (Avg Daily Sales × (Visit Cycle + Lead Time)) + Safety Stock − Current Stock − Pending PO Qty
</div>

<!-- Stats -->
<?php
$fast  = count(array_filter($suggestions, fn($s) => $s['tag']==='fast'));
$slow  = count(array_filter($suggestions, fn($s) => $s['tag']==='slow'));
$dead  = count(array_filter($suggestions, fn($s) => $s['tag']==='dead'));
$need  = count(array_filter($suggestions, fn($s) => $s['suggested_qty']>0));
$total_val = array_sum(array_map(fn($s) => $s['suggested_qty'] * $s['last_purchase_price'], $suggestions));
?>
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2"><div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 text-danger"><?= $need ?></div><div style="font-size:.65rem">Need Purchase</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 text-success"><?= $fast ?></div><div style="font-size:.65rem">Fast Moving</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 text-warning"><?= $slow ?></div><div style="font-size:.65rem">Slow Moving</div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 text-secondary"><?= count($suggestions) ?></div><div style="font-size:.65rem">Total Products</div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold">Suggestions (<?= count($suggestions) ?> products)</span>
        <input type="text" id="suggFilter" class="form-control form-control-sm d-print-none" style="width:200px" placeholder="Search...">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" id="suggTable" style="font-size:.78rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Product</th><th>Supplier</th><th>Brand</th><th>Stock</th><th>Sales (<?= $days ?>d)</th><th>Avg/Day</th>
                        <th>Visit Every</th><th>Lead Time</th><th>Pending PO</th>
                        <th>Safety Stock</th><th>Cover Days</th><th class="text-success">Suggested Qty</th>
                        <th>Last Price</th><th>Tag</th>
                    </tr>
                </thead>
                <tbody>
                <?php $tc=['fast'=>'success','medium'=>'info','slow'=>'warning','dead'=>'danger']; ?>
                <?php foreach ($suggestions as $sg): ?>
                <tr class="<?= $sg['suggested_qty']<=0?'text-muted':'' ?>">
                    <td><strong><?= htmlspecialchars($sg['name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($sg['item_code']) ?></small></td>
                    <td><?= htmlspecialchars($sg['supplier_name'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
                    <td><?= htmlspecialchars($sg['brand_name'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
                    <td><?php $s=$sg['current_stock']; $sc=$s<=0?'text-danger':($s<10?'text-warning':'text-success'); ?><span class="<?= $sc ?> font-weight-bold"><?= number_format($s,0) ?></span></td>
                    <td><?= number_format($sg['total_sales_qty'],1) ?></td>
                    <td><?= $sg['avg_daily_sales'] ?></td>
                    <td><?= $sg['visit_cycle_days'] ?>d</td>
                    <td><?= $sg['lead_time_days'] ?>d</td>
                    <td><?= number_format($sg['pending_po_qty'],0) ?></td>
                    <td><?= $sg['safety_stock'] ?></td>
                    <td><?php $cd=$sg['cover_days']; $cc=$cd<=3?'text-danger':($cd<=7?'text-warning':'text-success'); ?><span class="<?= $cc ?>"><?= $cd === 999 ? '∞' : $cd ?></span></td>
                    <td class="font-weight-bold <?= $sg['suggested_qty']>0?'text-success':'' ?>"><?= $sg['suggested_qty'] > 0 ? $sg['suggested_qty'] : '-' ?></td>
                    <td>₹<?= number_format($sg['last_purchase_price'],2) ?></td>
                    <td><span class="badge badge-<?= $tc[$sg['tag']]??'secondary' ?>"><?= ucfirst($sg['tag']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
document.getElementById('suggFilter').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#suggTable tbody tr').forEach(function(r) {
        r.style.display = pmatch(q, r.textContent) ? '' : 'none';
    });
});
</script>
<?php endif; ?>
