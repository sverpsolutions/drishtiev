<?php if (!empty($products)): ?>
    <?php foreach ($products as $i => $p):
        $stock_class = $p->stock_qty == 0 ? 'out' : ($p->stock_qty <= 10 ? 'low' : 'ok');
        $badge_cls   = $p->stock_qty == 0 ? 'danger' : ($p->stock_qty <= 10 ? 'warning' : 'success');
        $expiring    = false;
        $expired     = false;
        if ($p->expiry_date) {
            $days_left = ceil((strtotime($p->expiry_date) - time()) / 86400);
            $expiring  = $days_left <= 30 && $days_left >= 0;
            $expired   = $days_left < 0;
        }
    ?>
    <tr data-stock="<?= $stock_class ?>">
        <td><?= $i + 1 ?></td>
        <td><code style="font-size:.78rem;"><?= htmlspecialchars($p->item_code ?? '—') ?></code></td>
        <td class="font-weight-bold"><?= htmlspecialchars($p->name) ?></td>
        <td><?= htmlspecialchars($p->category_name ?? $p->category ?? '') ?></td>
        <td><code><?= htmlspecialchars($p->hsn_code ?? '—') ?></code></td>
        <td class="text-center">
            <span class="badge badge-<?= $badge_cls ?>">
                <?= $p->stock_qty ?> <?= $p->unit ?>
            </span>
        </td>
        <td class="text-right">₹ <?= number_format($p->purchase_price, 2) ?></td>
        <td class="text-right">₹ <?= number_format($p->selling_price, 2) ?></td>
        <td class="text-right">₹ <?= number_format($p->stock_value, 2) ?></td>
        <td>
            <?php if ($p->expiry_date): ?>
                <span class="badge badge-<?= $expired ? 'danger' : ($expiring ? 'warning' : 'secondary') ?>">
                    <?= date('d M Y', strtotime($p->expiry_date)) ?>
                    <?= $expired ? '(EXPIRED)' : ($expiring ? '('.$days_left.'d)' : '') ?>
                </span>
            <?php else: ?>—<?php endif; ?>
        </td>
        <td class="text-center">
            <a href="<?= base_url('inventory/movements/'.$p->id) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2">Movements</a>
            <?php if (!empty($is_admin)): ?>
            <button class="btn btn-sm btn-outline-warning py-0 px-2"
                    onclick="openAdjust(<?= $p->id ?>, '<?= addslashes($p->name) ?>', <?= $p->stock_qty ?>)">Adjust</button>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="11" class="text-center text-muted py-4">No products found.</td></tr>
<?php endif; ?>
