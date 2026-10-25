<?php
// ============================================================
// FILE: application/views/garments/view.php
// Garment Style Details & Variants Stock Matrix
// ============================================================
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold text-dark">
            <i class="fas fa-tshirt text-primary mr-2"></i><?= htmlspecialchars($style->name) ?>
        </h5>
        <small class="text-muted">Style Code: <strong class="text-primary"><?= htmlspecialchars($style->style_code ?: $style->item_code) ?></strong> &bull; Design No: <strong><?= htmlspecialchars($style->design_no ?: '-') ?></strong></small>
    </div>
    <div>
        <a href="<?= base_url('garments/edit/' . $style->id) ?>" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="fas fa-edit mr-1"></i> Edit Style
        </a>
        <a href="<?= base_url('garments/print_tags?product_id=' . $style->id) ?>" class="btn btn-outline-info btn-sm mr-1" target="_blank">
            <i class="fas fa-barcode mr-1"></i> Print All Tags
        </a>
        <a href="<?= base_url('garments') ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
</div>

<!-- Style Overview Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-2 text-center border-right">
                <?php if (!empty($style->image1) && file_exists(FCPATH . 'uploads/products/' . $style->image1)): ?>
                    <img src="<?= base_url('uploads/products/' . $style->image1) ?>" class="img-fluid rounded mb-2 border" style="max-height: 140px; object-fit: contain;">
                <?php else: ?>
                    <div class="bg-light rounded p-4 border text-muted mb-2">
                        <i class="fas fa-tshirt fa-4x"></i>
                    </div>
                <?php endif; ?>
                <div class="badge badge-primary px-2 py-1"><?= htmlspecialchars($style->garment_category ?: 'Garment') ?></div>
            </div>
            <div class="col-md-10">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <small class="text-muted d-block">Gender</small>
                        <span class="font-weight-bold"><?= htmlspecialchars($style->gender ?: '-') ?></span>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted d-block">Brand</small>
                        <span class="font-weight-bold"><?= htmlspecialchars($style->brand ?: '-') ?></span>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted d-block">Fabric Type</small>
                        <span class="font-weight-bold"><?= htmlspecialchars($style->fabric_type ?: '-') ?></span>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted d-block">Fit & Pattern</small>
                        <span class="font-weight-bold"><?= htmlspecialchars($style->fit_type ?: '-') ?> &bull; <?= htmlspecialchars($style->pattern_print ?: '-') ?></span>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted d-block">Sleeve & Neck</small>
                        <span class="font-weight-bold"><?= htmlspecialchars($style->sleeve_type ?: '-') ?> &bull; <?= htmlspecialchars($style->neck_type ?: '-') ?></span>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted d-block">HSN / GST Rate</small>
                        <span class="font-weight-bold"><?= htmlspecialchars($style->hsn_code ?: '-') ?> (<?= (float)$style->gst_percent ?>%)</span>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted d-block">Mfr Article No</small>
                        <span class="font-weight-bold"><?= htmlspecialchars($style->mfr_article_no ?: '-') ?></span>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted d-block">Country of Origin</small>
                        <span class="font-weight-bold"><?= htmlspecialchars($style->country_of_origin ?: 'India') ?></span>
                    </div>
                </div>
                <?php if (!empty($style->description)): ?>
                    <div class="mt-2 text-muted small border-top pt-2">
                        <strong>Description:</strong> <?= htmlspecialchars($style->description) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Variants & Location Stock Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-boxes text-success mr-2"></i>Variant Inventory & Location Stock</h6>
        <span class="badge badge-dark"><?= count($variants) ?> variants configured</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" style="font-size: 0.86rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Size</th>
                        <th>Colour</th>
                        <th>Variant SKU</th>
                        <th>Barcode (EAN-13)</th>
                        <th class="text-right">MRP</th>
                        <th class="text-right">Selling Price</th>
                        <th class="text-right">Cost Price</th>
                        <?php foreach ($locations as $loc): ?>
                            <th class="text-center bg-secondary text-white"><?= htmlspecialchars($loc->name) ?></th>
                        <?php endforeach; ?>
                        <th class="text-center font-weight-bold bg-dark text-white">Total Stock</th>
                        <th class="text-center">Min Stock</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($variants)): ?>
                        <tr>
                            <td colspan="<?= 10 + count($locations) ?>" class="text-center py-4 text-muted">
                                No variants configured for this style. Click <a href="<?= base_url('garments/edit/' . $style->id) ?>">Edit Style</a> to generate variants.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $total_all_stock = 0;
                        foreach ($variants as $idx => $v): 
                            $v_total_stock = (float)$v->stock_qty;
                            $total_all_stock += $v_total_stock;
                        ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="badge badge-light border font-weight-bold text-dark px-2 py-1"><?= htmlspecialchars($v->size_name) ?></span></td>
                                <td>
                                    <?php if (!empty($v->colour_hex)): ?>
                                        <span class="d-inline-block rounded-circle mr-1 border align-middle" style="width: 14px; height: 14px; background: <?= htmlspecialchars($v->colour_hex) ?>;"></span>
                                    <?php endif; ?>
                                    <span class="font-weight-bold"><?= htmlspecialchars($v->colour_name) ?></span>
                                </td>
                                <td><code><?= htmlspecialchars($v->variant_sku) ?></code></td>
                                <td>
                                    <span class="font-weight-bold text-dark"><i class="fas fa-barcode mr-1 text-muted"></i><?= htmlspecialchars($v->barcode) ?></span>
                                </td>
                                <td class="text-right">₹<?= number_format((float)$v->mrp, 2) ?></td>
                                <td class="text-right font-weight-bold text-primary">₹<?= number_format((float)$v->selling_price, 2) ?></td>
                                <td class="text-right text-muted">₹<?= number_format((float)$v->cost_price, 2) ?></td>
                                <?php foreach ($locations as $loc): ?>
                                    <?php 
                                    $loc_qty = (float)($loc_stock_matrix[$v->id][$loc->id] ?? 0);
                                    ?>
                                    <td class="text-center font-weight-bold <?= $loc_qty > 0 ? 'text-success' : 'text-muted' ?>">
                                        <?= number_format($loc_qty, 0) ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center font-weight-bold">
                                    <span class="badge badge-pill <?= $v_total_stock > 0 ? 'badge-success' : 'badge-danger' ?> px-2 py-1">
                                        <?= number_format($v_total_stock, 0) ?> pcs
                                    </span>
                                </td>
                                <td class="text-center text-muted"><?= (int)$v->min_stock ?></td>
                                <td class="text-right">
                                    <a href="<?= base_url('garments/print_tags?variant_id=' . $v->id) ?>" class="btn btn-outline-dark btn-sm py-0 px-2" target="_blank" title="Print Tag">
                                        <i class="fas fa-print"></i> Tag
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-active font-weight-bold">
                            <td colspan="8" class="text-right">TOTAL STYLE INVENTORY:</td>
                            <?php foreach ($locations as $loc): ?>
                                <?php
                                $col_total = 0;
                                foreach ($variants as $v) {
                                    $col_total += (float)($loc_stock_matrix[$v->id][$loc->id] ?? 0);
                                }
                                ?>
                                <td class="text-center text-primary"><?= number_format($col_total, 0) ?></td>
                            <?php endforeach; ?>
                            <td class="text-center text-success font-weight-bold" style="font-size: 1rem;"><?= number_format($total_all_stock, 0) ?> pcs</td>
                            <td colspan="2"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
