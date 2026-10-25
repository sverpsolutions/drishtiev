<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-th mr-2"></i>Rack: <?= htmlspecialchars($rack->rack_code) ?></h5>
        <small class="text-muted"><?= htmlspecialchars($rack->rack_name) ?><?= !empty($rack->location_name) ? ' &middot; ' . htmlspecialchars($rack->location_name) : '' ?></small>
    </div>
    <div>
        <a href="<?= base_url('masters/rack_qr/' . $rack->id) ?>" target="_blank" class="btn btn-outline-dark btn-sm mr-1"><i class="fas fa-qrcode mr-1"></i>Print QR</a>
        <a href="<?= base_url('masters/racks') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
    </div>
</div>

<?php if (!empty($rack->description)): ?>
<div class="alert alert-light py-2 small"><?= htmlspecialchars($rack->description) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Item Code</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th class="text-right">MRP</th>
                    <th class="text-right">SP</th>
                    <th class="text-center">Stock</th>
                    <th>Unit</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $k => $p): ?>
                    <tr>
                        <td><?= $k + 1 ?></td>
                        <td><code><?= htmlspecialchars($p->item_code) ?></code></td>
                        <td><?= htmlspecialchars($p->name) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($p->category_name ?? '-') ?></td>
                        <td class="text-right"><?= number_format($p->mrp, 2) ?></td>
                        <td class="text-right"><?= number_format($p->selling_price, 2) ?></td>
                        <td class="text-center font-weight-bold <?= $p->stock_qty <= 0 ? 'text-danger' : '' ?>"><?= $p->stock_qty ?></td>
                        <td><?= htmlspecialchars($p->unit ?? 'PCS') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No products assigned to this rack yet.</td></tr>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($products)): ?>
            <tfoot class="bg-light">
                <tr class="font-weight-bold">
                    <td colspan="6" class="text-right">Total Items: <?= count($products) ?></td>
                    <td class="text-center"><?= array_sum(array_column($products, 'stock_qty')) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
