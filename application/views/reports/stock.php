<?php // FILE: application/views/reports/stock.php ?>
<?php $view = $view ?? 'details'; $item_filters = $item_filters ?? []; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Stock Report<?= $view==='summary' ? ' — Summary' : ' — Details' ?></h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Stock</small>
        <?php if (!empty($filter_summary)): ?>
        <div class="mt-1"><span class="badge badge-dark" style="font-size:.75rem;"><?= htmlspecialchars(implode(' | ', $filter_summary)) ?></span></div>
        <?php endif; ?>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/stock','export_params'=>$export_params??'','export_title'=>$export_title??'Stock Report']); ?>
    </div>
</div>

<!-- Summary / Details toggle -->
<div class="btn-group mb-3 no-print" role="group">
    <a href="<?= base_url('reports/stock?view=summary') ?>" class="btn btn-sm <?= $view==='summary' ? 'btn-dark' : 'btn-outline-dark' ?>">Summary</a>
    <a href="<?= base_url('reports/stock?view=details') ?>" class="btn btn-sm <?= $view==='details' ? 'btn-dark' : 'btn-outline-dark' ?>">Details</a>
</div>

<!-- Filter bar: narrows both Summary and Details, and is the "sub-filter" used
     to drill from a Summary group straight into its matching Details rows -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end g-2">
            <input type="hidden" name="view" value="<?= $view ?>">
            <div class="col-md-2">
                <label class="small mb-0">Item Name</label>
                <input type="text" name="item_name" class="form-control form-control-sm" placeholder="e.g. Limca" value="<?= htmlspecialchars($item_filters['item_name'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Item Code</label>
                <input type="text" name="item_code" class="form-control form-control-sm" value="<?= htmlspecialchars($item_filters['item_code'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">EAN Code</label>
                <input type="text" name="ean_code" class="form-control form-control-sm" placeholder="scan or type" value="<?= htmlspecialchars($item_filters['ean_code'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Category</label>
                <select name="category_id" class="form-control form-control-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories ?? [] as $c): ?>
                    <option value="<?= $c->id ?>" <?= ($item_filters['category_id'] ?? '')==$c->id?'selected':'' ?>><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Brand</label>
                <select name="brand_id" class="form-control form-control-sm">
                    <option value="">All Brands</option>
                    <?php foreach ($brands ?? [] as $b): ?>
                    <option value="<?= $b->id ?>" <?= ($item_filters['brand_id'] ?? '')==$b->id?'selected':'' ?>><?= htmlspecialchars($b->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Supplier</label>
                <select name="supplier_id" class="form-control form-control-sm">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers ?? [] as $s): ?>
                    <option value="<?= $s->id ?>" <?= ($item_filters['supplier_id'] ?? '')==$s->id?'selected':'' ?>><?= htmlspecialchars($s->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Location</label>
                <select name="location_id" class="form-control form-control-sm" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
                    <?php if (count($locations ?? []) > 1): ?>
                    <option value="">All Locations</option>
                    <?php endif; ?>
                    <?php foreach ($locations ?? [] as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= ($item_filters['location_id'] ?? '')==$loc->id?'selected':'' ?>><?= htmlspecialchars($loc->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small mb-0">QOH (Stock Qty)</label>
                <div class="d-flex" style="gap:4px;">
                    <select name="qoh_op" class="form-control form-control-sm" style="width:65px;">
                        <option value="">Any</option>
                        <option value="eq" <?= ($item_filters['qoh_op'] ?? '')==='eq'?'selected':'' ?>>=</option>
                        <option value="lt" <?= ($item_filters['qoh_op'] ?? '')==='lt'?'selected':'' ?>>&lt;</option>
                        <option value="lte" <?= ($item_filters['qoh_op'] ?? '')==='lte'?'selected':'' ?>>&le;</option>
                        <option value="gt" <?= ($item_filters['qoh_op'] ?? '')==='gt'?'selected':'' ?>>&gt;</option>
                        <option value="gte" <?= ($item_filters['qoh_op'] ?? '')==='gte'?'selected':'' ?>>&ge;</option>
                    </select>
                    <input type="number" name="qoh_val" class="form-control form-control-sm" placeholder="Qty" step="1" value="<?= htmlspecialchars($item_filters['qoh_val'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-1">
                <label class="small mb-0">&nbsp;</label>
                <button type="submit" class="btn btn-dark btn-sm btn-block">Filter</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($item_filters['location_id'])): ?>
<div class="alert alert-info py-2 mb-3 no-print" style="font-size:.85rem;">
    <i class="fas fa-store mr-1"></i> Showing stock for:
    <strong><?= htmlspecialchars(array_reduce((array)($locations ?? []), fn($c, $l) => $l->id == $item_filters['location_id'] ? $l->name : $c, 'Unknown')) ?></strong>
    — <a href="<?= base_url('reports/stock?view='.$view) ?>">clear</a>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="text-muted small">Net Stock Value <span class="badge badge-info">excl. GST</span></div>
            <div class="h5 font-weight-bold text-primary mb-0">₹ <?= number_format($net_value ?? 0, 2) ?></div>
            <small class="text-muted">Basic Cost × QOH &nbsp;(After GST Input Credit)</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="text-muted small">Gross Stock Value <span class="badge badge-warning">incl. GST</span></div>
            <div class="h5 font-weight-bold text-success mb-0">₹ <?= number_format($total_value, 2) ?></div>
            <small class="text-muted">Cost Price × QOH &nbsp;(Total Paid)</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="text-muted small">MRP Value (Selling)</div>
            <div class="h5 font-weight-bold text-warning mb-0" id="totalMrpVal">—</div>
            <small class="text-muted">Selling Price × QOH</small>
        </div>
    </div>
</div>

<div class="alert alert-light border py-2 px-3 mb-3" style="font-size:.83rem;">
    <strong>GST Note:</strong>
    <strong>Net Value</strong> = Basic Cost × Qty (your effective cost after claiming GST input).
    <strong>Gross Value</strong> = Cost Price × Qty (actual cash paid incl. GST).
    <strong>WSP</strong> = Wholesale / Whole Carton Price.
</div>

<?php if ($view !== 'summary'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.82rem;" id="stockTable">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Item Code</th>
                        <th>EAN Code</th>
                        <th>Cat.</th>
                        <th>Brand</th>
                        <th>Supplier</th>
                        <th>HSN</th>
                        <th class="text-center">Stock</th>
                        <?php if (empty($item_filters['location_id'])): foreach ($locations ?? [] as $loc): ?>
                        <th class="text-center text-muted" style="font-size:.72rem;font-weight:600;" title="<?= htmlspecialchars($loc->name) ?>"><?= htmlspecialchars($loc->code ?: $loc->name) ?></th>
                        <?php endforeach; endif; ?>
                        <th class="text-right">Basic Cost</th>
                        <th class="text-right">CP (incl.GST)</th>
                        <th class="text-right">MRP</th>
                        <th class="text-right">SP</th>
                        <th class="text-right">WSP</th>
                        <th class="text-center">GST%</th>
                        <th class="text-right bg-primary text-white">Net Value</th>
                        <th class="text-right bg-success text-white">Gross Value</th>
                        <th class="text-right">MRP Value</th>
                        <th>Expiry</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $total_mrp_val  = 0;
                $total_net_val  = 0;
                $total_gross_val = 0;
                $row_offset = (($current_page ?? 1) - 1) * ($per_page ?? 500);
                if (!empty($products)):
                foreach ($products as $i => $p):
                    $total_mrp_val  += $p->mrp_value ?? 0;
                    $total_net_val  += $p->net_cost_value ?? 0;
                    $total_gross_val += $p->gross_cost_value ?? 0;
                    $expiry_class = '';
                    if ($p->expiry_date) {
                        $days = ceil((strtotime($p->expiry_date) - time()) / 86400);
                        $expiry_class = $days < 0 ? 'text-danger font-weight-bold' : ($days <= 30 ? 'text-warning' : '');
                    }
                ?>
                <tr>
                    <td><?= $row_offset + $i + 1 ?></td>
                    <td><?= htmlspecialchars($p->name) ?></td>
                    <td><code><?= htmlspecialchars($p->item_code ?? '—') ?></code></td>
                    <td><code><?= htmlspecialchars($p->ean_code ?: '—') ?></code></td>
                    <td><?= htmlspecialchars($p->category_name ?? $p->category) ?></td>
                    <td><?= htmlspecialchars($p->brand_name ?? '—') ?></td>
                    <td><?= htmlspecialchars($p->supplier_name ?? '—') ?></td>
                    <td><code><?= $p->hsn_code ?? '—' ?></code></td>
                    <td class="text-center">
                        <span class="badge badge-<?= $p->stock_qty == 0 ? 'danger' : ($p->stock_qty <= 10 ? 'warning':'success') ?>">
                            <?= $p->stock_qty ?> <?= $p->unit ?>
                        </span>
                    </td>
                    <?php if (empty($item_filters['location_id'])): foreach ($locations ?? [] as $loc):
                        $lq = $p->loc_qty[$loc->id] ?? 0;
                    ?>
                    <td class="text-center <?= $lq > 0 ? 'text-dark' : 'text-muted' ?>" style="font-size:.78rem;"><?= $lq > 0 ? $lq : '—' ?></td>
                    <?php endforeach; endif; ?>
                    <td class="text-right font-weight-bold text-primary">₹ <?= number_format($p->basic_cost ?? 0, 2) ?></td>
                    <td class="text-right text-success">₹ <?= number_format($p->purchase_price, 2) ?></td>
                    <td class="text-right">₹ <?= number_format($p->mrp ?? 0, 2) ?></td>
                    <td class="text-right">₹ <?= number_format($p->selling_price, 2) ?></td>
                    <td class="text-right text-warning">₹ <?= number_format($p->wsp ?? 0, 2) ?></td>
                    <td class="text-center"><?= $p->gst_percent ?>%</td>
                    <td class="text-right font-weight-bold text-primary">₹ <?= number_format($p->net_cost_value ?? 0, 2) ?></td>
                    <td class="text-right text-success">₹ <?= number_format($p->gross_cost_value ?? 0, 2) ?></td>
                    <td class="text-right">₹ <?= number_format($p->mrp_value ?? 0, 2) ?></td>
                    <td class="<?= $expiry_class ?>">
                        <?= $p->expiry_date ? date('d M Y', strtotime($p->expiry_date)) : '—' ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <!-- 15 = every column up to and including GST%, plus one more per
                             per-location column when "All Locations" is active, so the three
                             value totals below line up under their own columns. -->
                        <td colspan="<?= 15 + (empty($item_filters['location_id']) ? count($locations ?? []) : 0) ?>" class="text-right">Totals:</td>
                        <td class="text-right text-primary">₹ <?= number_format($total_net_val, 2) ?></td>
                        <td class="text-right text-success">₹ <?= number_format($total_gross_val, 2) ?></td>
                        <td class="text-right">₹ <?= number_format($total_mrp_val, 2) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php
// Pagination controls
$total_rows   = $total_rows ?? 0;
$current_page = $current_page ?? 1;
$per_page     = $per_page ?? 500;
$total_pages  = $total_rows > 0 ? (int)ceil($total_rows / $per_page) : 1;
if ($total_pages > 1):
    $base_params = array_merge(['view' => 'details'], $item_filters ?? []);
?>
<nav class="d-flex justify-content-between align-items-center mt-3 mb-3 no-print">
    <small class="text-muted">
        Showing <?= number_format($row_offset + 1) ?>–<?= number_format(min($row_offset + $per_page, $total_rows)) ?>
        of <?= number_format($total_rows) ?> products
    </small>
    <ul class="pagination pagination-sm mb-0">
        <?php if ($current_page > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('reports/stock?' . http_build_query(array_merge($base_params, ['page' => $current_page - 1]))) ?>">&laquo; Prev</a></li>
        <?php endif; ?>
        <?php
        $start_p = max(1, $current_page - 3);
        $end_p   = min($total_pages, $current_page + 3);
        if ($start_p > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('reports/stock?' . http_build_query(array_merge($base_params, ['page' => 1]))) ?>">1</a></li>
        <?php if ($start_p > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif;
        for ($pg = $start_p; $pg <= $end_p; $pg++): ?>
        <li class="page-item <?= $pg == $current_page ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('reports/stock?' . http_build_query(array_merge($base_params, ['page' => $pg]))) ?>"><?= $pg ?></a></li>
        <?php endfor;
        if ($end_p < $total_pages): ?>
        <?php if ($end_p < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('reports/stock?' . http_build_query(array_merge($base_params, ['page' => $total_pages]))) ?>"><?= $total_pages ?></a></li>
        <?php endif; ?>
        <?php if ($current_page < $total_pages): ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('reports/stock?' . http_build_query(array_merge($base_params, ['page' => $current_page + 1]))) ?>">Next &raquo;</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
<?php $total_mrp_val = $total_mrp_val ?? 0; ?>

<?php else: ?>

<?php
// Summary tab — three grouped breakdowns (category / brand / supplier), each
// row linking straight into Details pre-filtered to that same group (the
// "sub-filter" drill-down).
$group_labels = ['category' => ['Category', 'category_id'], 'brand' => ['Brand', 'brand_id'], 'supplier' => ['Supplier', 'supplier_id']];
// Any one grouping partitions the same (filtered) product set exactly once, so
// summing it gives the correct overall MRP value for the top summary card.
$total_mrp_val = array_sum(array_map(fn($g) => $g->mrp, $groups['category'] ?? []));
?>
<?php foreach ($group_labels as $type => [$label, $param]): $rowsByName = $groups[$type] ?? []; ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-dark text-white py-2"><strong><?= $label ?>-wise Stock Summary</strong></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.83rem;">
                <thead class="thead-light">
                    <tr>
                        <th><?= $label ?></th>
                        <th class="text-right"># Products</th>
                        <th class="text-right">Stock Qty</th>
                        <th class="text-right">Net Value</th>
                        <th class="text-right">Gross Value</th>
                        <th class="text-right">MRP Value</th>
                        <th class="text-center no-print">Details</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($rowsByName)): $g_qty=0;$g_net=0;$g_gross=0;$g_mrp=0;$g_count=0; foreach ($rowsByName as $g):
                    $g_qty+=$g->qty; $g_net+=$g->net; $g_gross+=$g->gross; $g_mrp+=$g->mrp; $g_count+=$g->count;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($g->name) ?></td>
                        <td class="text-right"><?= $g->count ?></td>
                        <td class="text-right"><?= $g->qty ?></td>
                        <td class="text-right">₹ <?= number_format($g->net,2) ?></td>
                        <td class="text-right">₹ <?= number_format($g->gross,2) ?></td>
                        <td class="text-right">₹ <?= number_format($g->mrp,2) ?></td>
                        <td class="text-center no-print">
                            <?php if ($g->id): ?>
                            <a href="<?= base_url('reports/stock?view=details&'.$param.'='.$g->id) ?>" class="btn btn-outline-dark btn-sm py-0">View</a>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="bg-light font-weight-bold">
                    <td class="text-right">TOTAL</td>
                    <td class="text-right"><?= $g_count ?></td>
                    <td class="text-right"><?= $g_qty ?></td>
                    <td class="text-right">₹ <?= number_format($g_net,2) ?></td>
                    <td class="text-right">₹ <?= number_format($g_gross,2) ?></td>
                    <td class="text-right">₹ <?= number_format($g_mrp,2) ?></td>
                    <td class="no-print"></td>
                </tr>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">No products match this filter.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<script>
document.getElementById('totalMrpVal').textContent = '₹ <?= number_format(isset($mrp_total) ? (float)$mrp_total : $total_mrp_val, 2) ?>';
</script>
<style>@media print { .no-print { display:none; } }</style>
