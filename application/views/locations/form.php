<?php // FILE: application/views/locations/form.php
$is_edit    = isset($location);
$action     = $is_edit ? base_url('locations/edit/' . $location->id) : base_url('locations/add');
$preset_type = $this->input->get('type') ?: ($location->type ?? 'outlet');
$is_wh = $preset_type === 'warehouse';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0">
            <i class="fas fa-<?= $is_wh ? 'warehouse' : 'store' ?> mr-2"></i>
            <?= $is_edit ? 'Edit ' . htmlspecialchars($location->name) : ($is_wh ? 'Add Warehouse' : 'Add Outlet') ?>
        </h5>
        <small class="text-muted">
            <a href="<?= base_url('locations') ?>">Outlet Master</a> /
            <?= $is_edit ? htmlspecialchars($location->name) : 'New' ?>
        </small>
    </div>
    <a href="<?= base_url('locations') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

<div class="row">
<div class="col-md-8">

<!-- Basic Info -->
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header py-2 bg-<?= $is_wh ? 'primary' : 'success' ?> text-white">
        <strong><i class="fas fa-info-circle mr-2"></i>Basic Information</strong>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= $action ?>" id="locationForm">

            <div class="form-row">
                <div class="form-group col-md-7">
                    <label class="font-weight-bold small">Location Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required maxlength="100"
                           value="<?= set_value('name', $location->name ?? '') ?>"
                           placeholder="e.g. Main Outlet, City Mall, North Warehouse">
                </div>
                <div class="form-group col-md-3">
                    <label class="font-weight-bold small">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required maxlength="10"
                           value="<?= set_value('code', $location->code ?? '') ?>"
                           style="text-transform:uppercase;" placeholder="OT1">
                    <small class="text-muted">Short code for billing</small>
                </div>
                <div class="form-group col-md-2">
                    <label class="font-weight-bold small">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" min="0"
                           value="<?= set_value('sort_order', $location->sort_order ?? 0) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-control">
                        <option value="outlet"    <?= $preset_type === 'outlet'    ? 'selected' : '' ?>>Outlet (Retail Store)</option>
                        <option value="warehouse" <?= $preset_type === 'warehouse' ? 'selected' : '' ?>>Warehouse (Storage)</option>
                    </select>
                </div>
                <?php if ($is_edit): ?>
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" <?= ($location->status ?? 1) ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= !($location->status ?? 1) ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
</div><!-- /card-body -->
</div><!-- /card -->

<!-- Contact & Address -->
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header py-2 bg-light">
        <strong><i class="fas fa-map-marker-alt mr-2 text-danger"></i>Address & Contact</strong>
    </div>
    <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= set_value('phone', $location->phone ?? '') ?>"
                           placeholder="Outlet contact number">
                </div>
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= set_value('email', $location->email ?? '') ?>"
                           placeholder="outlet@example.com">
                </div>
            </div>

            <div class="form-group">
                <label class="font-weight-bold small">Address Line</label>
                <textarea name="address" class="form-control" rows="2"
                          placeholder="Shop / Building / Street address"><?= set_value('address', $location->address ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group col-md-5">
                    <label class="font-weight-bold small">City</label>
                    <input type="text" name="city" class="form-control"
                           value="<?= set_value('city', $location->city ?? '') ?>"
                           placeholder="City">
                </div>
                <div class="form-group col-md-4">
                    <label class="font-weight-bold small">State</label>
                    <input type="text" name="state" class="form-control"
                           value="<?= set_value('state', $location->state ?? 'Uttar Pradesh') ?>"
                           placeholder="State">
                </div>
                <div class="form-group col-md-3">
                    <label class="font-weight-bold small">Pincode</label>
                    <input type="text" name="pincode" class="form-control" maxlength="10"
                           value="<?= set_value('pincode', $location->pincode ?? '') ?>"
                           placeholder="226001">
                </div>
            </div>
    </div>
</div>

<!-- GST & Legal -->
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header py-2 bg-light">
        <strong><i class="fas fa-file-invoice mr-2 text-primary"></i>GST & Legal Details <small class="font-weight-normal text-muted">(printed on Tax Invoice)</small></strong>
    </div>
    <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">GSTIN</label>
                    <input type="text" name="gstin" class="form-control" maxlength="20"
                           style="text-transform:uppercase;"
                           value="<?= set_value('gstin', $location->gstin ?? '') ?>"
                           placeholder="22AAAAA0000A1Z5">
                    <small class="text-muted">15-digit GST number of this outlet</small>
                </div>
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">PAN</label>
                    <input type="text" name="pan" class="form-control" maxlength="20"
                           style="text-transform:uppercase;"
                           value="<?= set_value('pan', $location->pan ?? '') ?>"
                           placeholder="AAAAA0000A">
                </div>
            </div>

            <hr>
            <div class="d-flex">
                <button type="submit" class="btn btn-<?= $is_wh ? 'primary' : 'success' ?> mr-2">
                    <i class="fas fa-save mr-1"></i> <?= $is_edit ? 'Update Location' : 'Create Location' ?>
                </button>
                <a href="<?= base_url('locations') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div><!-- /col-md-8 -->

<!-- Sidebar Help -->
<div class="col-md-4">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 bg-light"><strong>Tips</strong></div>
        <div class="card-body p-3" style="font-size:.85rem;">
            <p class="mb-2"><i class="fas fa-store text-success mr-1"></i> <strong>Outlets</strong> are retail billing points. Cashiers are assigned to an outlet.</p>
            <p class="mb-2"><i class="fas fa-warehouse text-primary mr-1"></i> <strong>Warehouses</strong> are storage locations for stock transfers.</p>
            <p class="mb-2"><i class="fas fa-file-invoice text-danger mr-1"></i> GSTIN and address are printed on the <strong>Tax Invoice</strong> header for this outlet.</p>
            <p class="mb-0"><i class="fas fa-tag text-warning mr-1"></i> Code is used in invoice numbers (e.g. <code>OT1-0001</code>).</p>
        </div>
    </div>

    <?php if ($is_edit): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header py-2 bg-light"><strong>Quick Links</strong></div>
        <div class="card-body p-3">
            <a href="<?= base_url('locations/stock/' . $location->id) ?>" class="btn btn-outline-secondary btn-sm btn-block mb-2">
                <i class="fas fa-boxes mr-1"></i> View Stock
            </a>
            <a href="<?= base_url('reports/sales?location_id=' . $location->id) ?>" class="btn btn-outline-primary btn-sm btn-block">
                <i class="fas fa-chart-bar mr-1"></i> Sales Report
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

</div><!-- /row -->
