
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-balance-scale text-success mr-2"></i>Quote Comparison — <?= htmlspecialchars($rfq->rfq_number) ?></h5>
    <div>
        <a href="<?= base_url('rfq/view/'.$rfq->id) ?>" class="btn btn-sm btn-outline-secondary mr-2">← RFQ</a>
        <a href="<?= base_url('rfq/convert_to_po/'.$rfq->id) ?>" class="btn btn-sm btn-success" onclick="return confirm('Convert best quotes to Purchase Order?')"><i class="fas fa-file-invoice mr-1"></i> Convert to PO</a>
    </div>
</div>

<!-- Comparison matrix -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0" style="font-size:.78rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Product</th><th>Req. Qty</th>
                        <?php foreach ($suppliers as $sup): ?>
                        <th class="text-center"><?= htmlspecialchars($sup->supplier_name) ?><br><small class="text-muted font-weight-normal"><?= $sup->status==='responded'?'<span class="badge badge-success">Responded</span>':'<span class="badge badge-warning">Pending</span>' ?></small></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($item->product_name) ?></strong></td>
                    <td><?= number_format($item->qty_required,2) ?> <?= $item->unit ?></td>
                    <?php
                    $prices = [];
                    foreach ($suppliers as $sup) {
                        $k = $sup->supplier_id.'_'.$item->id;
                        $prices[$sup->supplier_id] = $quotation_matrix[$k] ?? null;
                    }
                    $min = null;
                    foreach ($prices as $p) { if ($p && ($min===null||$p['rate']<$min)) $min=$p['rate']; }
                    foreach ($suppliers as $sup):
                        $q = $prices[$sup->supplier_id];
                    ?>
                    <td class="text-center <?= $q&&$q['rate']==$min?'table-success':'' ?>">
                        <?php if ($q): ?>
                        <div class="font-weight-bold <?= $q['rate']==$min?'text-success':'' ?>">₹<?= number_format($q['rate'],2) ?></div>
                        <?php if ($q['delivery_days']): ?><small class="text-muted"><?= $q['delivery_days'] ?>d delivery</small><?php endif; ?>
                        <?php if ($q['remarks']): ?><small class="d-block text-muted"><?= htmlspecialchars($q['remarks']) ?></small><?php endif; ?>
                        <?php if ($q['rate']==$min): ?><span class="badge badge-success mt-1">Best Price</span><?php endif; ?>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Total comparison -->
<?php if (!empty($supplier_totals)): ?>
<div class="row">
    <?php foreach ($supplier_totals as $st): ?>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm <?= $st['is_best']?'border-success':'' ?> p-3 text-center">
            <?php if ($st['is_best']): ?><div class="badge badge-success mb-2">BEST OVERALL</div><?php endif; ?>
            <div class="font-weight-bold"><?= htmlspecialchars($st['supplier_name']) ?></div>
            <div class="h4 text-success mb-0">₹<?= number_format($st['total'],2) ?></div>
            <div style="font-size:.7rem" class="text-muted"><?= $st['items_quoted'] ?>/<?= count($items) ?> items quoted</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
