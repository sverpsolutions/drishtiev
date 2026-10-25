<?php // FILE: application/views/plu/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-tags text-warning mr-1"></i> PLU Master</h5>
        <small class="text-muted">Price Look-Up entries — one product can have unlimited PLUs for different MRPs</small>
    </div>
    <div class="d-flex" style="gap:6px;">
        <a href="<?= base_url('plu/import') ?>" class="btn btn-outline-success btn-sm">
            <i class="fas fa-file-excel mr-1"></i> Import from Excel
        </a>
        <a href="<?= base_url('plu/create') ?>" class="btn btn-success btn-sm">
            <i class="fas fa-plus mr-1"></i> New PLU
        </a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $this->session->flashdata('success') ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?= $this->session->flashdata('warning') ?>
        <a href="<?= base_url('plu/import_issues') ?>" class="btn btn-sm btn-outline-dark ml-2">
            <i class="fas fa-file-excel mr-1"></i> Download issues
        </a>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" action="" class="form-inline">
            <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Search name / PLU / barcode / batch..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" style="min-width:240px;">
            <select name="product_id" class="form-control form-control-sm mr-2" style="min-width:180px;">
                <option value="">All Products</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p->id ?>" <?= ($filters['product_id'] ?? '') == $p->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-control form-control-sm mr-2">
                <option value="">All Status</option>
                <option value="1" <?= ($filters['status'] ?? '') === '1' ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= ($filters['status'] ?? '') === '0' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search"></i> Filter</button>
            <a href="<?= base_url('plu') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>
    </div>
</div>

<!-- PLU Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered mb-0" id="pluTable">
                <thead class="thead-dark">
                    <tr>
                        <th>PLU Code</th>
                        <th>Item Name</th>
                        <th>Item Code</th>
                        <th>Barcode</th>
                        <th>Batch</th>
                        <th class="text-right">MRP</th>
                        <th class="text-right">Purchase</th>
                        <th class="text-right">Selling</th>
                        <th class="text-right">GST%</th>
                        <th class="text-right">Stock</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($plus)): ?>
                    <tr><td colspan="13" class="text-center text-muted py-4">No PLUs found. <a href="<?= base_url('plu/create') ?>">Create one</a></td></tr>
                <?php else: ?>
                    <?php $last_product = null; foreach ($plus as $p): ?>
                    <?php if ($last_product !== $p->product_id): ?>
                    <tr class="table-secondary">
                        <td colspan="13" class="py-1 px-3 font-weight-bold" style="font-size:.8rem;">
                            <i class="fas fa-box-open mr-1 text-warning"></i>
                            <?= htmlspecialchars($p->item_name) ?>
                            <span class="badge badge-dark ml-1"><?= htmlspecialchars($p->item_code) ?></span>
                            <a href="<?= base_url('plu/create?product_id=' . $p->product_id) ?>" class="btn btn-xs btn-outline-success ml-2">
                                <i class="fas fa-plus"></i> Add PLU
                            </a>
                        </td>
                    </tr>
                    <?php $last_product = $p->product_id; endif; ?>
                    <tr class="<?= $p->stock_qty <= 0 ? 'table-danger' : ($p->stock_qty <= 5 ? 'table-warning' : '') ?>">
                        <td><span class="badge badge-info"><?= htmlspecialchars($p->plu_code) ?></span></td>
                        <td style="font-size:.82rem;"><?= htmlspecialchars($p->item_name) ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($p->item_code) ?></small></td>
                        <td><code><?= htmlspecialchars($p->barcode ?? '-') ?></code></td>
                        <td><?= htmlspecialchars($p->batch_no ?? '-') ?></td>
                        <td class="text-right font-weight-bold">₹<?= number_format($p->mrp, 2) ?></td>
                        <td class="text-right">₹<?= number_format($p->purchase_rate, 2) ?></td>
                        <td class="text-right text-success font-weight-bold">₹<?= number_format($p->selling_rate, 2) ?></td>
                        <td class="text-right"><?= $p->gst_percent ?>%</td>
                        <td class="text-right font-weight-bold <?= $p->stock_qty <= 0 ? 'text-danger' : ($p->stock_qty <= 5 ? 'text-warning' : 'text-success') ?>">
                            <?= number_format($p->stock_qty, 2) ?>
                        </td>
                        <td>
                            <?php if ($p->expiry_date): ?>
                                <?php $exp = strtotime($p->expiry_date); $now = time(); $diff = ($exp - $now) / 86400; ?>
                                <span class="badge <?= $diff < 0 ? 'badge-danger' : ($diff < 30 ? 'badge-warning' : 'badge-success') ?>">
                                    <?= date('d/m/Y', $exp) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p->status): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= base_url('plu/edit/' . $p->id) ?>" class="btn btn-xs btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="<?= base_url('plu/toggle_status/' . $p->id) ?>" class="btn btn-xs btn-outline-<?= $p->status ? 'warning' : 'success' ?>" title="Toggle Status"
                               onclick="return confirm('Toggle status for <?= htmlspecialchars($p->plu_code) ?>?')">
                                <i class="fas fa-<?= $p->status ? 'ban' : 'check' ?>"></i>
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

<div class="mt-2 text-muted small">
    Total PLUs: <strong><?= count($plus) ?></strong>
</div>
