<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 font-weight-bold">Categories Master</h5>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-2 font-weight-bold text-dark">Add Category</div>
            <div class="card-body">
                <?= form_open_multipart('masters/categories') ?>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Beverages" required autofocus>
                </div>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="0" min="0">
                    <small class="form-text text-muted">Lower numbers appear first in customer view.</small>
                </div>
                <div class="form-group mb-4">
                    <label class="font-weight-bold small text-muted">Category Picture / Icon</label>
                    <div class="custom-file">
                        <input type="file" name="image" class="custom-file-input" id="cat_image" accept="image/*">
                        <label class="custom-file-label" for="cat_image">Choose file</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark btn-block font-weight-bold">Save Category</button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0" style="vertical-align: middle;">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Category Name</th>
                            <th class="text-center">Order</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($categories)): ?>
                            <?php foreach($categories as $k => $c): ?>
                            <tr>
                                <td class="align-middle"><?= $k+1 ?></td>
                                <td class="align-middle">
                                    <?php if(!empty($c->image)): ?>
                                        <img src="<?= base_url('assets/uploads/categories/'.$c->image) ?>" alt="img" style="height: 35px; width: 35px; object-fit: cover; border-radius: 4px; border: 1px solid #dee2e6;">
                                    <?php else: ?>
                                        <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 4px; font-size: 10px;">🏷️</div>
                                    <?php endif; ?>
                                </td>
                                <td class="font-weight-bold align-middle text-dark"><?= htmlspecialchars($c->name) ?></td>
                                <td class="text-center align-middle"><span class="badge badge-secondary px-2 py-1"><?= $c->display_order ?></span></td>
                                <td class="text-right align-middle">
                                    <a href="<?= base_url('masters/edit_category/'.$c->id) ?>" class="btn btn-outline-primary btn-sm mr-1">Edit</a>
                                    <a href="<?= base_url('masters/delete_category/'.$c->id) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure? This will delete the category.')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No categories added yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    // Show filename in file input
    var fileInput = document.getElementById('cat_image');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            var fileName = e.target.files[0]?.name || 'Choose file';
            var label = e.target.nextElementSibling;
            if (label) label.innerText = fileName;
        });
    }
});
</script>
