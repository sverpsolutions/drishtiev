<?php // FILE: application/views/reports/location_stock.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="font-weight-bold mb-0">Location Stock Report</h5>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/location_stock','export_params'=>$export_params??'','export_title'=>$export_title??'Location Stock Report']); ?>
    </div>
</div>

<!-- Location Tabs -->
<ul class="nav nav-pills mb-3 flex-wrap no-print">
<?php foreach ($locations as $loc): ?>
    <li class="nav-item mr-1 mb-1">
        <a class="nav-link <?= $loc->id == ($current_location->id ?? 0) ? 'active' : '' ?>"
           href="<?= base_url('reports/location_stock?location_id=' . $loc->id) ?>">
            <span class="badge badge-<?= $loc->type === 'warehouse' ? 'primary' : 'success' ?> mr-1"><?= htmlspecialchars($loc->code) ?></span>
            <?= htmlspecialchars($loc->name) ?>
        </a>
    </li>
<?php endforeach; ?>
</ul>

<?php if ($current_location): ?>
<div class="d-flex justify-content-between mb-3">
    <h6 class="font-weight-bold"><?= htmlspecialchars($current_location->name) ?> — <?= ucfirst($current_location->type) ?></h6>
    <div>
        <strong>Stock Value:</strong>
        <span class="badge badge-success" style="font-size:.9rem;">&#8377;<?= number_format($stock_value, 2) ?></span>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Unit</th>
                    <th class="text-right">Stock Qty</th>
                    <th class="text-right">Min Stock</th>
                    <th class="text-right">Sale Price</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($stock as $i => $p): ?>
                <tr class="<?= ($p->stock_qty <= $p->min_stock) ? 'table-warning' : '' ?>">
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($p->name) ?></td>
                    <td><small><?= htmlspecialchars($p->item_code) ?></small></td>
                    <td><?= htmlspecialchars($p->unit) ?></td>
                    <td class="text-right font-weight-bold"><?= number_format($p->stock_qty, 3) ?></td>
                    <td class="text-right text-muted"><?= $p->min_stock ?></td>
                    <td class="text-right">&#8377;<?= number_format($p->selling_price, 2) ?></td>
                    <td>
                        <?php if ($p->stock_qty <= 0): ?>
                            <span class="badge badge-danger">Out of Stock</span>
                        <?php elseif ($p->stock_qty <= $p->min_stock): ?>
                            <span class="badge badge-warning">Low</span>
                        <?php else: ?>
                            <span class="badge badge-success">OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<style>@media print { .no-print { display:none; } }</style>
