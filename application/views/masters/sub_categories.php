<?php // FILE: application/views/masters/sub_categories.php ?>
<div class="row">
<div class="col-md-4">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-dark text-white py-2 font-weight-bold">Add Sub-Category</div>
        <div class="card-body">
            <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success py-1"><?= $this->session->flashdata('success') ?></div>
            <?php endif; ?>
            <?= form_open('masters/sub_categories') ?>
            <div class="form-group">
                <label>Parent Category <span class="text-danger">*</span></label>
                <select name="category_id" class="form-control" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sub-Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Code</label>
                <input type="text" name="code" class="form-control" placeholder="Optional short code">
            </div>
            <button type="submit" class="btn btn-success btn-block">Add Sub-Category</button>
            <?= form_close() ?>
        </div>
    </div>
</div>
<div class="col-md-8">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white py-2 font-weight-bold">
            <i class="fas fa-folder-open mr-1"></i> Sub-Categories (<?= count($sub_categories) ?>)
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light"><tr><th>Category</th><th>Sub-Category</th><th>Code</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($sub_categories as $sc): ?>
                <tr>
                    <td><span class="badge badge-secondary"><?= htmlspecialchars($sc->category_name) ?></span></td>
                    <td class="font-weight-bold"><?= htmlspecialchars($sc->name) ?></td>
                    <td><?= htmlspecialchars($sc->code ?? '-') ?></td>
                    <td><span class="badge badge-<?= $sc->status ? 'success' : 'secondary' ?>"><?= $sc->status ? 'Active' : 'Inactive' ?></span></td>
                    <td><a href="<?= base_url('masters/delete_sub_category/' . $sc->id) ?>" class="btn btn-xs btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($sub_categories)): ?><tr><td colspan="5" class="text-center text-muted py-3">No sub-categories yet</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
