<?php // Scheme Item List — which products every scheme (or a selected one) applies to ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-list-check text-primary mr-2"></i>Scheme Item List</h4>
        <small class="text-muted">Which products each scheme applies to</small>
    </div>
    <div class="d-flex" style="gap:.5rem">
        <a href="<?= base_url('schemes') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left mr-1"></i>Back</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end">
            <div class="col-md-6">
                <label class="small text-muted mb-1">Scheme</label>
                <select name="scheme_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Schemes</option>
                    <?php foreach ($schemes as $sc): ?>
                        <option value="<?= $sc->id ?>" <?= ((int)$scheme_id === (int)$sc->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sc->name) ?> <?= $sc->is_active ? '' : '(Inactive)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <a href="<?= base_url('schemes/item_list') ?>" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
            </div>
            <div class="col-md-3 text-right">
                <a href="<?= base_url('schemes/item_list_excel' . ($scheme_id ? '?scheme_id=' . $scheme_id : '')) ?>" class="btn btn-success btn-sm w-100">
                    <i class="bi bi-file-earmark-excel mr-1"></i>Export Excel
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-box-seam mr-1"></i>Applicable Items (<?= count($rows) ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Scheme</th>
                        <th>Type</th>
                        <th>Applies On</th>
                        <th>Status</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Item Code</th>
                        <th>Product</th>
                        <th>Brand</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="10" class="text-center text-muted small py-3">No schemes found</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a href="<?= base_url('schemes/view/' . $row->scheme_id) ?>"><?= htmlspecialchars($row->scheme_name) ?></a></td>
                        <td><span class="badge badge-secondary"><?= htmlspecialchars($row->type_label) ?></span></td>
                        <td><?= $row->apply_on === 'brand_category' ? 'Brand & Category' : ucfirst($row->apply_on) ?></td>
                        <td><?= $row->is_active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>' ?></td>
                        <td><?= date('d/m/Y', strtotime($row->start_date)) ?></td>
                        <td><?= date('d/m/Y', strtotime($row->end_date)) ?></td>
                        <td><code><?= htmlspecialchars($row->item_code ?: '—') ?></code></td>
                        <td><?= htmlspecialchars($row->product_name) ?></td>
                        <td><?= htmlspecialchars($row->brand_name ?: '—') ?></td>
                        <td><?= htmlspecialchars($row->category_name ?: '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
