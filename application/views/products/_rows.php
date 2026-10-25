<?php
// ============================================================
// FILE: application/views/products/_rows.php
// Shared <tr> markup for the Products list — rendered both on
// initial page load (products/index) and by Products::list_ajax()
// so search results look identical to the default listing.
// ============================================================
?>
<?php if (!empty($products)): ?>
    <?php foreach ($products as $i => $p): ?>
    <tr class="product-row" data-href="<?= base_url('products/edit/' . $p->id) ?>" style="cursor: pointer;">
        <td><?= $i + 1 ?></td>
        <td>
            <?php if (!empty($p->image1)): ?>
                <img src="<?= base_url('assets/uploads/products/' . $p->image1) ?>" alt="img" style="height: 35px; width: 35px; object-fit: cover; border-radius: 4px;">
            <?php else: ?>
                <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 4px; font-size: 8px;">No Img</div>
            <?php endif; ?>
        </td>
        <td>
            <div class="font-weight-bold text-dark">
                <?= htmlspecialchars($p->name) ?>
                <?php if (($p->product_type ?? '') === 'electric_scooter'): ?>
                    <span class="badge badge-success ml-1" style="font-size: 0.7rem;"><i class="fas fa-motorcycle mr-1"></i>EV Scooter</span>
                <?php elseif (($p->product_type ?? '') === 'battery'): ?>
                    <span class="badge badge-warning text-dark ml-1" style="font-size: 0.7rem;"><i class="fas fa-car-battery mr-1"></i>Battery</span>
                <?php elseif (($p->product_type ?? '') === 'charger'): ?>
                    <span class="badge badge-info ml-1" style="font-size: 0.7rem;"><i class="fas fa-charging-station mr-1"></i>Charger</span>
                <?php elseif (($p->product_type ?? '') === 'motor'): ?>
                    <span class="badge badge-secondary ml-1" style="font-size: 0.7rem;"><i class="fas fa-cogs mr-1"></i>Motor</span>
                <?php elseif (!empty($p->is_garment)): ?>
                    <span class="badge badge-info ml-1" style="font-size: 0.7rem;"><i class="fas fa-tshirt mr-1"></i>Garment</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($p->model_number) || !empty($p->colour)): ?>
                <div class="text-secondary" style="font-size:0.75rem;">
                    <?php if (!empty($p->model_number)): ?><strong>Model:</strong> <?= htmlspecialchars($p->model_number) ?><?php endif; ?>
                    <?php if (!empty($p->colour)): ?> | <strong>Colour:</strong> <?= htmlspecialchars($p->colour) ?><?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($p->style_code)): ?>
                <span class="badge badge-light border text-secondary" style="font-size:0.72rem;">Style: <?= htmlspecialchars($p->style_code) ?></span>
            <?php endif; ?>
            <?php if(!empty($p->description)): ?><div class="text-muted" style="font-size:0.75rem;line-height:1;margin-top:2px;"><?= htmlspecialchars($p->description) ?></div><?php endif; ?>
            <div class="text-muted mt-1" style="font-size:0.75rem;">Item Code: <?= htmlspecialchars($p->item_code ?? '—') ?></div>
        </td>
        <td><?= htmlspecialchars($p->category_name ?? $p->category) ?></td>
        <td><?= htmlspecialchars($p->brand_name ?? $p->brand ?? '—') ?></td>
        <td><code><?= htmlspecialchars($p->hsn_code ?? '—') ?></code></td>
        <td class="text-right text-primary font-weight-bold">₹ <?= number_format($p->basic_cost ?? 0, 2) ?></td>
        <td class="text-right text-success">₹ <?= number_format($p->purchase_price, 2) ?></td>
        <td class="text-right">₹ <?= number_format($p->mrp ?? 0, 2) ?></td>
        <td class="text-right font-weight-bold">₹ <?= number_format($p->selling_price, 2) ?></td>
        <td class="text-right text-warning">₹ <?= number_format($p->wsp ?? 0, 2) ?></td>
        <td class="text-center"><?= $p->gst_percent ?>%</td>
        <td class="text-center">
            <span class="badge badge-<?= $p->stock_qty == 0 ? 'danger' : ($p->stock_qty <= 10 ? 'warning' : 'success') ?>">
                <?= $p->stock_qty ?> <?= $p->unit ?>
            </span>
        </td>
        <td>
            <div style="font-size:0.8rem; font-weight:bold; color:#0056b3;">
                <?= htmlspecialchars($p->pcs_barcode ?: ($p->item_code ?: '—')) ?>
            </div>
        </td>
        <td class="text-center">
            <a href="<?= base_url('products/edit/' . $p->id) ?>" class="btn btn-xs btn-outline-primary btn-sm py-0 px-2">Edit</a>
            <?php if (!empty($p->is_garment)): ?>
            <a href="<?= base_url('garments/view/' . $p->id) ?>" class="btn btn-xs btn-outline-info btn-sm py-0 px-2" title="View Garment Variants & Matrix"><i class="fas fa-layer-group"></i></a>
            <?php endif; ?>
            <?php if ($this->session->userdata('role') === 'admin'): ?>
            <a href="<?= base_url('products/delete/' . $p->id) ?>"
               class="btn btn-xs btn-outline-danger btn-sm py-0 px-2"
               onclick="return confirm('Delete this product?')">Del</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="14" class="text-center text-muted py-4">No products found. <a href="<?= base_url('products/add') ?>">Add one now.</a></td></tr>
<?php endif; ?>
