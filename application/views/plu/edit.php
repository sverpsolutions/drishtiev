<?php // FILE: application/views/plu/edit.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-edit text-primary mr-1"></i> Edit PLU: <?= htmlspecialchars($plu->plu_code) ?></h5>
        <small class="text-muted"><a href="<?= base_url('plu') ?>">PLU Master</a> / Edit</small>
    </div>
    <a href="<?= base_url('plu?product_id=' . $plu->product_id) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<?= form_open('plu/edit/' . $plu->id) ?>
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-primary text-white py-2" style="font-size:.85rem;">
                PLU: <strong><?= htmlspecialchars($plu->plu_code) ?></strong>
                &nbsp;|&nbsp; Product: <strong><?= htmlspecialchars($plu->item_name) ?></strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Barcode</label>
                            <input type="text" name="barcode" class="form-control" value="<?= htmlspecialchars($plu->barcode ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Batch No</label>
                            <input type="text" name="batch_no" class="form-control" value="<?= htmlspecialchars($plu->batch_no ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-bold text-danger">MRP <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" name="mrp" class="form-control font-weight-bold" step="0.01" min="0" value="<?= $plu->mrp ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Purchase Rate</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" name="purchase_rate" class="form-control" step="0.01" min="0" value="<?= $plu->purchase_rate ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Selling Rate</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" name="selling_rate" class="form-control" step="0.01" min="0" value="<?= $plu->selling_rate ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Landing Cost</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" name="landing_cost" class="form-control" step="0.01" min="0" value="<?= $plu->landing_cost ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>GST %</label>
                            <input type="number" name="gst_percent" class="form-control" step="0.01" min="0" max="100" value="<?= $plu->gst_percent ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Mfg Date</label>
                            <input type="date" name="mfg_date" class="form-control" value="<?= $plu->mfg_date ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control" value="<?= $plu->expiry_date ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="1" <?= $plu->status ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= !$plu->status ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Supplier</label>
                            <select name="supplier_id" class="form-control">
                                <option value="">-- None --</option>
                                <?php foreach ($suppliers as $sid => $sname): ?>
                                <option value="<?= $sid ?>" <?= $plu->supplier_id == $sid ? 'selected' : '' ?>><?= htmlspecialchars($sname) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Location</label>
                            <select name="location_id" class="form-control">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $loc): ?>
                                <option value="<?= $loc->id ?>" <?= $plu->location_id == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fas fa-save mr-1"></i> Update PLU</button>
        <a href="<?= base_url('plu?product_id=' . $plu->product_id) ?>" class="btn btn-outline-secondary btn-lg ml-2">Cancel</a>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white py-2" style="font-size:.85rem;">Current Stock</div>
            <div class="card-body text-center">
                <div style="font-size:2.5rem;font-weight:700;" class="<?= $plu->stock_qty <= 0 ? 'text-danger' : 'text-success' ?>">
                    <?= number_format($plu->stock_qty, 2) ?>
                </div>
                <div class="text-muted small">Units in stock</div>
                <hr>
                <div class="text-left small">
                    <div><strong>PLU Code:</strong> <?= htmlspecialchars($plu->plu_code) ?></div>
                    <div><strong>Product:</strong> <?= htmlspecialchars($plu->item_name) ?></div>
                    <div><strong>Supplier:</strong> <?= htmlspecialchars($plu->supplier_name ?? 'N/A') ?></div>
                    <div><strong>Location:</strong> <?= htmlspecialchars($plu->location_name ?? 'All') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>
