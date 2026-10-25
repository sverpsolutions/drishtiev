<?php
// ============================================================
// FILE: application/views/garments/index.php
// Garment & Clothing Style Master
// ============================================================
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-tshirt text-primary mr-2"></i>Garment & Apparel Master</h5>
        <small class="text-muted">Manage garment parent styles, size-colour variants, design numbers, and barcode tags</small>
    </div>
    <div>
        <a href="<?= base_url('garments/sizes') ?>" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="fas fa-ruler-combined"></i> Size Sets
        </a>
        <a href="<?= base_url('garments/colours') ?>" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="fas fa-palette"></i> Colours
        </a>
        <a href="<?= base_url('garments/import') ?>" class="btn btn-outline-success btn-sm mr-1">
            <i class="fas fa-file-csv"></i> Import CSV
        </a>
        <a href="<?= base_url('garments/print_tags') ?>" class="btn btn-outline-info btn-sm mr-1" target="_blank">
            <i class="fas fa-barcode"></i> Print Tags
        </a>
        <a href="<?= base_url('garments/create') ?>" class="btn btn-primary btn-sm font-weight-bold">
            <i class="fas fa-plus"></i> + Add Garment Style
        </a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-1"></i> <?= $this->session->flashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle mr-1"></i> <?= $this->session->flashdata('error') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="get" action="<?= base_url('garments') ?>" class="form-row align-items-center">
            <div class="col-md-3 mb-2 mb-md-0">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search Style Name, Code, Barcode..." value="<?= htmlspecialchars($filters['q']) ?>">
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <select name="garment_category" class="form-control form-control-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= ($filters['garment_category'] === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <select name="gender" class="form-control form-control-sm">
                    <option value="">All Genders</option>
                    <option value="Men" <?= ($filters['gender'] === 'Men') ? 'selected' : '' ?>>Men</option>
                    <option value="Women" <?= ($filters['gender'] === 'Women') ? 'selected' : '' ?>>Women</option>
                    <option value="Boys" <?= ($filters['gender'] === 'Boys') ? 'selected' : '' ?>>Boys</option>
                    <option value="Girls" <?= ($filters['gender'] === 'Girls') ? 'selected' : '' ?>>Girls</option>
                    <option value="Unisex" <?= ($filters['gender'] === 'Unisex') ? 'selected' : '' ?>>Unisex</option>
                </select>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <select name="brand" class="form-control form-control-sm">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= htmlspecialchars($b->name) ?>" <?= ($filters['brand'] === $b->name) ? 'selected' : '' ?>><?= htmlspecialchars($b->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <select name="fabric_type" class="form-control form-control-sm">
                    <option value="">All Fabrics</option>
                    <?php foreach ($fabrics as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>" <?= ($filters['fabric_type'] === $f) ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- Styles List Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size: 0.86rem;">
                <thead class="thead-dark">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Style / Product</th>
                        <th>Style / Design No</th>
                        <th>Category</th>
                        <th>Gender</th>
                        <th>Fabric / Fit</th>
                        <th>Brand</th>
                        <th class="text-center">Variants</th>
                        <th class="text-right">Price Range</th>
                        <th class="text-center">Total Stock</th>
                        <th class="text-right" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($styles)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="fas fa-tshirt fa-3x mb-3 text-secondary d-block"></i>
                                No garment styles found. Click <strong>+ Add Garment Style</strong> to create your first clothing style with size & colour variants.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($styles as $i => $s): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <div class="font-weight-bold text-dark">
                                        <a href="<?= base_url('garments/view/' . $s->id) ?>" class="text-dark">
                                            <?= htmlspecialchars($s->name) ?>
                                        </a>
                                    </div>
                                    <small class="text-muted">SKU: <?= htmlspecialchars($s->item_code) ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-light border font-weight-normal">
                                        <?= htmlspecialchars($s->style_code ?: $s->design_no ?: '-') ?>
                                    </span>
                                    <?php if (!empty($s->mfr_article_no)): ?>
                                        <div class="small text-muted" title="Mfr Article No"><?= htmlspecialchars($s->mfr_article_no) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-info"><?= htmlspecialchars($s->garment_category ?: $s->category ?: 'Garment') ?></span></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($s->gender ?: 'Unisex') ?></span></td>
                                <td>
                                    <?= htmlspecialchars($s->fabric_type ?: '-') ?>
                                    <?php if (!empty($s->fit_type)): ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($s->fit_type) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($s->brand ?: '-') ?></td>
                                <td class="text-center">
                                    <span class="badge badge-pill badge-primary px-2 py-1">
                                        <?= (int)$s->variant_count ?> sizes/colours
                                    </span>
                                </td>
                                <td class="text-right font-weight-bold">
                                    <?php if ($s->min_sp == $s->max_sp): ?>
                                        ₹<?= number_format((float)$s->min_sp, 2) ?>
                                    <?php else: ?>
                                        ₹<?= number_format((float)$s->min_sp, 2) ?> - ₹<?= number_format((float)$s->max_sp, 2) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($s->max_mrp)): ?>
                                        <div class="small text-muted font-weight-normal">MRP: ₹<?= number_format((float)$s->max_mrp, 2) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php $stk = (float)$s->total_garment_stock; ?>
                                    <span class="badge badge-pill <?= $stk > 0 ? 'badge-success' : 'badge-danger' ?> px-2 py-1">
                                        <?= number_format($stk, 0) ?> pcs
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a href="<?= base_url('garments/view/' . $s->id) ?>" class="btn btn-outline-info btn-sm py-0 px-2" title="View Variants & Stock">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('garments/edit/' . $s->id) ?>" class="btn btn-outline-secondary btn-sm py-0 px-2" title="Edit Style">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= base_url('garments/print_tags?product_id=' . $s->id) ?>" class="btn btn-outline-dark btn-sm py-0 px-2" target="_blank" title="Print Barcode Labels">
                                        <i class="fas fa-barcode"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
