<?php // FILE: application/views/cash_management/categories.php ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-tags mr-1"></i> Cash Categories</h5>
        <small class="text-muted">Manage pay-in and pay-out categories</small>
    </div>
    <div>
        <a href="<?= base_url('cashManagement') ?>" class="btn btn-sm btn-outline-secondary mr-1"><i class="fas fa-arrow-left"></i> Back</a>
        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#catModal" onclick="resetCatForm()"><i class="fas fa-plus"></i> Add Category</button>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="row">
    <!-- Pay-In Categories -->
    <div class="col-md-6 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-2">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-plus-circle mr-1"></i> Pay-In Categories</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0" style="font-size:.85rem;">
                    <thead class="thead-light"><tr><th>#</th><th>Name</th><th>Status</th><th class="text-center">Action</th></tr></thead>
                    <tbody>
                    <?php if (!empty($payin_cats)): foreach ($payin_cats as $i => $c): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="font-weight-bold"><?= htmlspecialchars($c->name) ?></td>
                            <td><span class="badge badge-<?= $c->status ? 'success' : 'secondary' ?>"><?= $c->status ? 'Active' : 'Disabled' ?></span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:.75rem;"
                                    onclick="editCat(<?= $c->id ?>, '<?= addslashes($c->name) ?>', 'pay_in', '<?= addslashes($c->description ?? '') ?>', <?= $c->status ?>, <?= $c->sort_order ?>)">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No pay-in categories.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pay-Out Categories -->
    <div class="col-md-6 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-danger text-white py-2">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-minus-circle mr-1"></i> Pay-Out Categories</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0" style="font-size:.85rem;">
                    <thead class="thead-light"><tr><th>#</th><th>Name</th><th>Status</th><th class="text-center">Action</th></tr></thead>
                    <tbody>
                    <?php if (!empty($payout_cats)): foreach ($payout_cats as $i => $c): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="font-weight-bold"><?= htmlspecialchars($c->name) ?></td>
                            <td><span class="badge badge-<?= $c->status ? 'success' : 'secondary' ?>"><?= $c->status ? 'Active' : 'Disabled' ?></span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:.75rem;"
                                    onclick="editCat(<?= $c->id ?>, '<?= addslashes($c->name) ?>', 'pay_out', '<?= addslashes($c->description ?? '') ?>', <?= $c->status ?>, <?= $c->sort_order ?>)">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No pay-out categories.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title font-weight-bold mb-0" id="catModalTitle">Add Category</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <?= form_open('cashManagement/save_category') ?>
            <input type="hidden" name="id" id="cat_id" value="0">
            <div class="modal-body">
                <div class="form-group">
                    <label style="font-size:.85rem;">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="cat_name" class="form-control form-control-sm" required>
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Type <span class="text-danger">*</span></label>
                    <select name="type" id="cat_type" class="form-control form-control-sm" required>
                        <option value="pay_in">Pay-In</option>
                        <option value="pay_out">Pay-Out</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Description</label>
                    <input type="text" name="description" id="cat_desc" class="form-control form-control-sm">
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Sort Order</label>
                    <input type="number" name="sort_order" id="cat_sort" class="form-control form-control-sm" value="0">
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Status</label>
                    <select name="status" id="cat_status" class="form-control form-control-sm">
                        <option value="1">Active</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="submit" class="btn btn-sm btn-primary">Save</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
function resetCatForm() {
    document.getElementById('catModalTitle').textContent = 'Add Category';
    document.getElementById('cat_id').value = 0;
    document.getElementById('cat_name').value = '';
    document.getElementById('cat_type').value = 'pay_out';
    document.getElementById('cat_desc').value = '';
    document.getElementById('cat_sort').value = 0;
    document.getElementById('cat_status').value = '1';
}
function editCat(id, name, type, desc, status, sort) {
    document.getElementById('catModalTitle').textContent = 'Edit Category';
    document.getElementById('cat_id').value = id;
    document.getElementById('cat_name').value = name;
    document.getElementById('cat_type').value = type;
    document.getElementById('cat_desc').value = desc;
    document.getElementById('cat_sort').value = sort;
    document.getElementById('cat_status').value = status;
    $('#catModal').modal('show');
}
</script>
