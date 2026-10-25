<?php // FILE: application/views/reports/stock_location_grid.php ?>
<?php $item_filters = $item_filters ?? []; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Stock by Location (Grid)</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Stock by Location (Grid)</small>
        <?php if (!empty($filter_summary)): ?>
        <div class="mt-1"><span class="badge badge-dark" style="font-size:.75rem;"><?= htmlspecialchars(implode(' | ', $filter_summary)) ?></span></div>
        <?php endif; ?>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/stock_location_grid','export_params'=>$export_params??'','export_title'=>$export_title??'Stock by Location (Grid)']); ?>
    </div>
</div>

<div class="alert alert-light border py-2 px-3 mb-3 no-print" style="font-size:.83rem;">
    <i class="fas fa-info-circle mr-1"></i>
    One row per product, one <strong>Qty / CP / MRP / SP</strong> group of columns per location — compare stock and pricing across locations at a glance.
    CP/MRP/SP are the product's master values (this system doesn't price per location); only <strong>Qty</strong> changes by location.
</div>

<!-- Filter bar -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end g-2">
            <div class="col-md-3">
                <label class="small mb-0">Item Name</label>
                <input type="text" name="item_name" class="form-control form-control-sm" placeholder="e.g. Limca" value="<?= htmlspecialchars($item_filters['item_name'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="small mb-0">Item Code</label>
                <input type="text" name="item_code" class="form-control form-control-sm" value="<?= htmlspecialchars($item_filters['item_code'] ?? '') ?>">
            </div>
            <div class="col-md-3">
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
            <div class="col-md-1">
                <button type="submit" class="btn btn-dark btn-sm btn-block">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0" style="font-size:.78rem;white-space:nowrap;">
                <thead class="thead-dark">
                    <tr>
                        <th rowspan="2" class="align-middle">#</th>
                        <th rowspan="2" class="align-middle">Item Code</th>
                        <th rowspan="2" class="align-middle">Product</th>
                        <?php foreach ($locations ?? [] as $loc): ?>
                        <th colspan="4" class="text-center" style="border-left:2px solid #999;"><?= htmlspecialchars($loc->name) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($locations ?? [] as $loc): ?>
                        <th class="text-right" style="border-left:2px solid #999;">Qty</th>
                        <th class="text-right">CP</th>
                        <th class="text-right">MRP</th>
                        <th class="text-right">SP</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($products)): foreach ($products as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><code><?= htmlspecialchars($p->item_code ?? '—') ?></code></td>
                        <td class="font-weight-bold" style="white-space:normal;min-width:180px;"><?= htmlspecialchars($p->name) ?></td>
                        <?php foreach ($locations ?? [] as $loc):
                            $qty = $stock_map[$p->id][$loc->id] ?? 0;
                        ?>
                        <td class="text-right <?= $qty == 0 ? 'text-danger' : 'text-success font-weight-bold' ?>" style="border-left:2px solid #eee;"><?= number_format($qty, 0) ?></td>
                        <td class="text-right">₹<?= number_format($p->purchase_price ?? 0, 2) ?></td>
                        <td class="text-right">₹<?= number_format($p->mrp ?? 0, 2) ?></td>
                        <td class="text-right">₹<?= number_format($p->selling_price ?? 0, 2) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="<?= 3 + 4 * count($locations ?? []) ?>" class="text-center text-muted py-3">No products match this filter.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$total_rows   = $total_rows ?? 0;
$current_page = $current_page ?? 1;
$per_page     = $per_page ?? 500;
$total_pages  = $total_rows > 0 ? (int)ceil($total_rows / $per_page) : 1;
$row_offset   = ($current_page - 1) * $per_page;
if ($total_pages > 1):
    $base_params = $item_filters ?? [];
?>
<nav class="d-flex justify-content-between align-items-center mt-3 mb-3 no-print">
    <small class="text-muted">
        Showing <?= number_format($row_offset + 1) ?>–<?= number_format(min($row_offset + $per_page, $total_rows)) ?>
        of <?= number_format($total_rows) ?> products
    </small>
    <ul class="pagination pagination-sm mb-0">
        <?php if ($current_page > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('reports/stock_location_grid?' . http_build_query(array_merge($base_params, ['page' => $current_page - 1]))) ?>">&laquo; Prev</a></li>
        <?php endif; ?>
        <?php
        $start_p = max(1, $current_page - 3);
        $end_p   = min($total_pages, $current_page + 3);
        if ($start_p > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('reports/stock_location_grid?' . http_build_query(array_merge($base_params, ['page' => 1]))) ?>">1</a></li>
        <?php if ($start_p > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif;
        for ($pg = $start_p; $pg <= $end_p; $pg++): ?>
        <li class="page-item <?= $pg == $current_page ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('reports/stock_location_grid?' . http_build_query(array_merge($base_params, ['page' => $pg]))) ?>"><?= $pg ?></a></li>
        <?php endfor;
        if ($end_p < $total_pages): ?>
        <?php if ($end_p < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('reports/stock_location_grid?' . http_build_query(array_merge($base_params, ['page' => $total_pages]))) ?>"><?= $total_pages ?></a></li>
        <?php endif; ?>
        <?php if ($current_page < $total_pages): ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('reports/stock_location_grid?' . http_build_query(array_merge($base_params, ['page' => $current_page + 1]))) ?>">Next &raquo;</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<style>@media print { .no-print { display:none; } }</style>
