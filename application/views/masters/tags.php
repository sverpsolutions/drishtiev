<?php // FILE: application/views/masters/tags.php ?>
<div class="row">
<div class="col-md-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white py-2 font-weight-bold">Add Tag</div>
        <div class="card-body">
            <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success py-1"><?= $this->session->flashdata('success') ?></div><?php endif; ?>
            <?= form_open('masters/tags') ?>
            <div class="form-group"><label>Tag Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required placeholder="e.g. Bestseller, New Arrival, Clearance"></div>
            <div class="form-group">
                <label>Color</label>
                <input type="color" name="color" class="form-control" style="height:38px;" value="#6c757d">
                <small class="text-muted">Used as badge color for the tag</small>
            </div>
            <button type="submit" class="btn btn-success btn-block">Add Tag</button>
            <?= form_close() ?>
        </div>
    </div>
</div>
<div class="col-md-8">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white py-2 font-weight-bold"><i class="fas fa-tag mr-1"></i> Product Tags (<?= count($tags) ?>)</div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php foreach ($tags as $t): ?>
                <span class="badge badge-pill p-2 mr-1 mb-1" style="background:<?= htmlspecialchars($t->color) ?>;font-size:.9rem;">
                    <?= htmlspecialchars($t->name) ?>
                    <a href="<?= base_url('masters/delete_tag/' . $t->id) ?>" class="text-white ml-1" onclick="return confirm('Delete tag?')" style="text-decoration:none;">×</a>
                </span>
                <?php endforeach; ?>
                <?php if (empty($tags)): ?><p class="text-muted">No tags yet. Add tags above.</p><?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light"><tr><th>Tag Name</th><th>Color</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($tags as $t): ?>
                    <tr>
                        <td><span class="badge badge-pill px-3" style="background:<?= htmlspecialchars($t->color) ?>;color:#fff;"><?= htmlspecialchars($t->name) ?></span></td>
                        <td><code><?= htmlspecialchars($t->color) ?></code></td>
                        <td><span class="badge badge-<?= $t->status ? 'success' : 'secondary' ?>"><?= $t->status ? 'Active' : 'Inactive' ?></span></td>
                        <td><a href="<?= base_url('masters/delete_tag/' . $t->id) ?>" class="btn btn-xs btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
