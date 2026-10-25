<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-edit mr-2"></i>Edit Rack</h5>
    <a href="<?= base_url('masters/racks') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Racks</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?= form_open('masters/edit_rack/' . $rack->id) ?>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Rack Code <span class="text-danger">*</span></label>
                    <input type="text" name="rack_code" class="form-control text-uppercase" value="<?= htmlspecialchars($rack->rack_code) ?>" required maxlength="20">
                </div>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Rack Name <span class="text-danger">*</span></label>
                    <input type="text" name="rack_name" class="form-control" value="<?= htmlspecialchars($rack->rack_name) ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Location</label>
                    <select name="location_id" class="form-control">
                        <option value="">-- All Locations --</option>
                        <?php foreach ($locations as $l): ?>
                        <option value="<?= $l->id ?>" <?= $l->id == $rack->location_id ? 'selected' : '' ?>><?= htmlspecialchars($l->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Description</label>
                    <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($rack->description ?? '') ?>">
                </div>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int)$rack->sort_order ?>" min="0">
                </div>
                <button type="submit" class="btn btn-primary btn-block font-weight-bold">Update Rack</button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>
