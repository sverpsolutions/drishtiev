<?php
// FILE: application/views/reports/stock_movement.php
$cur_view = $view ?? 'details';
$type_labels = [
    'purchase_in'    => ['Purchase In',    'success',   'fa-arrow-down'],
    'sale_out'       => ['Sale Out',       'primary',   'fa-arrow-up'],
    'transfer_in'    => ['Transfer In',    'info',      'fa-exchange-alt'],
    'transfer_out'   => ['Transfer Out',   'warning',   'fa-exchange-alt'],
    'adjustment_in'  => ['Adjustment In',  'secondary', 'fa-plus-circle'],
    'adjustment_out' => ['Adjustment Out', 'dark',      'fa-minus-circle'],
    'refund_in'      => ['Sales Refund',   'danger',    'fa-undo'],
    'opening_stock'  => ['Opening Stock',  'light',     'fa-box'],
];
$doc_labels = [
    'invoice'        => ['INV',  'billing/view/'],
    'purchase'       => ['PUR',  'purchases/view/'],
    'stock_transfer' => ['TRF',  ''],
    'adjustment'     => ['ADJ',  ''],
    'stock_take'     => ['STK',  ''],
    'refund'         => ['RET',  'refunds/view/'],
];

function build_qs($filters, $extra = []) {
    return http_build_query(array_merge(array_filter([
        'location_id'   => $filters['location_id'] ?? '',
        'movement_type' => $filters['movement_type'] ?? '',
        'date_from'     => $filters['date_from'] ?? '',
        'date_to'       => $filters['date_to'] ?? '',
        'product_id'    => $filters['product_id'] ?? '',
        'item_code'     => $filters['item_code'] ?? '',
        'ean_code'      => $filters['ean_code'] ?? '',
    ]), $extra));
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="font-weight-bold mb-0"><i class="fas fa-exchange-alt text-primary mr-2"></i>Stock Movement Report</h5>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/stock_movement','export_params'=>$export_params??'','export_title'=>$export_title??'Stock Movement Report']); ?>
    </div>
</div>

<form method="GET" class="mb-3" id="smForm">
    <input type="hidden" name="view" id="smView" value="<?= $cur_view ?>">
    <div class="form-row align-items-end">
        <div class="form-group col-md-2 mb-2">
            <label class="small font-weight-bold">Location</label>
            <select name="location_id" class="form-control form-control-sm">
                <?php if (count($locations) > 1): ?>
                <option value="">All Locations</option>
                <?php endif; ?>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= ($filters['location_id'] ?? '') == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small font-weight-bold">Movement Type</label>
            <select name="movement_type" class="form-control form-control-sm">
                <option value="">All Types</option>
                <?php foreach ($type_labels as $mt => $info): ?>
                    <option value="<?= $mt ?>" <?= ($filters['movement_type'] ?? '') === $mt ? 'selected' : '' ?>><?= $info[0] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small font-weight-bold">Product</label>
            <select name="product_id" class="form-control form-control-sm select2">
                <option value="">-- All Products --</option>
                <?php foreach ($products ?? [] as $prod): ?>
                    <option value="<?= $prod->id ?>" <?= ($filters['product_id'] ?? '') == $prod->id ? 'selected' : '' ?>><?= htmlspecialchars($prod->name . ($prod->item_code ? ' ['.$prod->item_code.']' : '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-1 mb-2">
            <label class="small font-weight-bold">Item Code</label>
            <input type="text" name="item_code" class="form-control form-control-sm" placeholder="e.g. 154539" value="<?= htmlspecialchars($filters['item_code'] ?? '') ?>">
        </div>
        <div class="form-group col-md-1 mb-2">
            <label class="small font-weight-bold">EAN Code</label>
            <input type="text" name="ean_code" class="form-control form-control-sm" placeholder="Barcode" value="<?= htmlspecialchars($filters['ean_code'] ?? '') ?>">
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small font-weight-bold">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $filters['date_from'] ?? '' ?>">
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small font-weight-bold">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $filters['date_to'] ?? '' ?>">
        </div>
        <div class="form-group col-auto mb-2">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
            <a href="<?= base_url('reports/stock_movement') ?>" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
        </div>
    </div>
</form>

<!-- View Tabs -->
<ul class="nav nav-tabs mb-0 no-print" style="border-bottom:0;">
    <li class="nav-item">
        <a class="nav-link <?= $cur_view === 'details' ? 'active font-weight-bold' : '' ?>" href="#" onclick="switchView('details');return false;">
            <i class="fas fa-list mr-1"></i>Details
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $cur_view === 'summary' ? 'active font-weight-bold' : '' ?>" href="#" onclick="switchView('summary');return false;">
            <i class="fas fa-chart-bar mr-1"></i>Summary
        </a>
    </li>
</ul>

<?php if ($cur_view === 'summary'): ?>
<!-- ═══ SUMMARY VIEW ═══ -->
<?php
    $tot_open = $tot_in = $tot_out = $tot_close = 0;
    foreach ($summary ?? [] as $s) {
        $tot_open  += $s->opening_stock;
        $tot_in    += $s->stock_in;
        $tot_out   += $s->stock_out;
        $tot_close += $s->closing_stock;
    }
?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold small text-uppercase text-muted">
            Movement Summary &mdash; <?= date('d M Y', strtotime($filters['date_from'])) ?> to <?= date('d M Y', strtotime($filters['date_to'])) ?>
        </span>
        <span class="badge badge-pill badge-primary"><?= count($summary ?? []) ?> Products</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" id="smSummaryTable">
            <thead>
                <tr style="background:#f1f3f5;">
                    <th class="border-top-0" style="width:40px;">#</th>
                    <th class="border-top-0" style="width:100px;">Item Code</th>
                    <th class="border-top-0">Product Name</th>
                    <th class="border-top-0 text-center" style="width:50px;">Unit</th>
                    <th class="border-top-0 text-right" style="width:90px;">MRP</th>
                    <th class="border-top-0 text-right" style="width:100px;">Opening</th>
                    <th class="border-top-0 text-right text-success" style="width:100px;">Stock In</th>
                    <th class="border-top-0 text-right text-danger" style="width:100px;">Stock Out</th>
                    <th class="border-top-0 text-right" style="width:100px;">Closing</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($summary)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i>No movements in this period.</td></tr>
            <?php else: ?>
                <?php $i = 0; foreach ($summary as $s): $i++; ?>
                <tr>
                    <td class="text-muted"><?= $i ?></td>
                    <td><code class="text-dark"><?= htmlspecialchars($s->item_code) ?></code></td>
                    <td class="font-weight-bold"><?= htmlspecialchars($s->product_name) ?></td>
                    <td class="text-center text-muted small"><?= htmlspecialchars($s->unit ?: 'PCS') ?></td>
                    <td class="text-right"><?= number_format($s->mrp, 2) ?></td>
                    <td class="text-right"><?= number_format($s->opening_stock, 2) ?></td>
                    <td class="text-right text-success font-weight-bold"><?= $s->stock_in > 0 ? '+'.number_format($s->stock_in, 2) : '-' ?></td>
                    <td class="text-right text-danger font-weight-bold"><?= $s->stock_out > 0 ? '-'.number_format($s->stock_out, 2) : '-' ?></td>
                    <td class="text-right font-weight-bold"><?= number_format($s->closing_stock, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($summary)): ?>
            <tfoot>
                <tr style="background:#e9ecef; font-weight:bold;">
                    <td colspan="5" class="text-right text-uppercase small">Totals</td>
                    <td class="text-right"><?= number_format($tot_open, 2) ?></td>
                    <td class="text-right text-success">+<?= number_format($tot_in, 2) ?></td>
                    <td class="text-right text-danger">-<?= number_format($tot_out, 2) ?></td>
                    <td class="text-right"><?= number_format($tot_close, 2) ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php else: ?>
<!-- ═══ DETAILS VIEW ═══ -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold small text-uppercase text-muted">
            Movement Details &mdash; <?= date('d M Y', strtotime($filters['date_from'])) ?> to <?= date('d M Y', strtotime($filters['date_to'])) ?>
        </span>
        <span class="text-muted small"><i class="fas fa-info-circle mr-1"></i>Showing latest 500 records. Use date filters to narrow results.</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" id="smDetailsTable">
            <thead class="thead-dark">
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Before</th>
                    <th class="text-right">After</th>
                    <th>Reference</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($movements)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i>No movements found.</td></tr>
            <?php else: ?>
                <?php foreach ($movements as $m):
                    $tinfo  = $type_labels[$m->movement_type] ?? ['Unknown', 'secondary', 'fa-question'];
                    $dinfo  = $doc_labels[$m->reference_type ?? ''] ?? ['DOC', ''];
                    $is_in  = in_array($m->movement_type, ['purchase_in','transfer_in','adjustment_in','refund_in','opening_stock']);
                    $qty_class = $is_in ? 'text-success' : 'text-danger';
                    $qty_sign  = $is_in ? '+' : '-';
                ?>
                <tr class="smr-<?= $m->movement_type ?>">
                    <td class="text-nowrap"><?= date('d M Y H:i', strtotime($m->created_at)) ?></td>
                    <td class="font-weight-bold"><?= htmlspecialchars($m->product_name) ?></td>
                    <td><?= htmlspecialchars($m->location_name) ?></td>
                    <td>
                        <span class="badge badge-<?= $tinfo[1] ?>" style="font-size:.73rem;">
                            <i class="fas <?= $tinfo[2] ?> mr-1"></i><?= strtolower($tinfo[0]) ?>
                        </span>
                    </td>
                    <td class="text-right font-weight-bold <?= $qty_class ?>"><?= number_format($m->quantity, 3) ?></td>
                    <td class="text-right"><?= number_format($m->balance_before, 3) ?></td>
                    <td class="text-right font-weight-bold"><?= number_format($m->balance_after, 3) ?></td>
                    <td>
                        <?php if (!empty($m->reference_no)): ?>
                            <?php if (!empty($dinfo[1]) && !empty($m->reference_id)): ?>
                                <a href="<?= base_url($dinfo[1] . $m->reference_id) ?>"><?= htmlspecialchars($m->reference_no) ?></a>
                            <?php else: ?>
                                <?= htmlspecialchars($m->reference_no) ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($m->user_name ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
    #smDetailsTable td, #smSummaryTable td { vertical-align:middle; font-size:.83rem; }
    #smDetailsTable th  { font-size:.8rem; white-space:nowrap; }
    #smSummaryTable th  { font-size:.75rem; letter-spacing:.5px; white-space:nowrap; }
    #smSummaryTable code { background:none; padding:0; font-size:.8rem; }
    .nav-tabs .nav-link { font-size:.85rem; color:#6c757d; padding:.5rem 1rem; }
    .nav-tabs .nav-link.active { color:#495057; background:#fff; border-bottom-color:#fff; }
    /* Row colors by movement type */
    .smr-sale_out        { background:#fff; }
    .smr-purchase_in     { background:#e8f5e9; }
    .smr-refund_in       { background:#ffebee; }
    .smr-transfer_in     { background:#e3f2fd; }
    .smr-transfer_out    { background:#fff8e1; }
    .smr-adjustment_in   { background:#f1f8e9; }
    .smr-adjustment_out  { background:#fce4ec; }
    .smr-opening_stock   { background:#f5f5f5; }
    .smr-sale_out:hover        { background:#f5f5f5 !important; }
    .smr-purchase_in:hover     { background:#c8e6c9 !important; }
    .smr-refund_in:hover       { background:#ffcdd2 !important; }
    .smr-transfer_in:hover     { background:#bbdefb !important; }
    .smr-transfer_out:hover    { background:#fff9c4 !important; }
    .smr-adjustment_in:hover   { background:#dcedc8 !important; }
    .smr-adjustment_out:hover  { background:#f8bbd0 !important; }
    .smr-opening_stock:hover   { background:#eeeeee !important; }
    @media print { .no-print{display:none} .card{box-shadow:none!important} }
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if($.fn.select2) {
        $('.select2').select2({ placeholder:"-- All Products --", allowClear:true, width:'100%' });
    }
});
function switchView(v) {
    document.getElementById('smView').value = v;
    document.getElementById('smForm').submit();
}
</script>
