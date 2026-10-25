<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 font-weight-bold">Edit Category</h5>
    <a href="<?= base_url('masters/categories') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Categories</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?= form_open_multipart('masters/edit_category/'.$category->id) ?>
                
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($category->name) ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?= htmlspecialchars($category->display_order) ?>" min="0">
                    <small class="form-text text-muted">Lower numbers appear first in customer view.</small>
                </div>

                <?php if (property_exists($category, 'allow_loyalty_points')): ?>
                <div class="form-group mb-3 p-2 border rounded" style="background:#f8fff9;">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="allow_loyalty_points" id="cat_loyalty"
                               class="custom-control-input" value="1"
                               <?= (int)$category->allow_loyalty_points === 1 ? 'checked' : '' ?>>
                        <label class="custom-control-label font-weight-bold" for="cat_loyalty">
                            <i class="fas fa-gift mr-1 text-success"></i> Allow Loyalty Points
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        Switch off for categories like <strong>Dairy</strong> or <strong>Tobacco</strong> —
                        every item in the category then earns <strong>zero points</strong>, whatever the
                        item's own setting says.
                    </small>
                </div>
                <?php endif; ?>

                <div class="form-group mb-4">
                    <label class="font-weight-bold small text-muted">Category Picture / Icon</label>
                    
                    <?php if (!empty($category->image)): ?>
                    <div class="d-flex align-items-center mb-3 p-2 border rounded bg-white shadow-xs" style="min-height: 80px;">
                        <img src="<?= base_url('assets/uploads/categories/' . $category->image) ?>" alt="Category Image" style="height: 60px; width: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; background:#f8f9fa; margin-right: 12px;">
                        <div>
                            <div class="custom-control custom-checkbox small">
                                <input type="checkbox" class="custom-control-input" id="delete_image" name="delete_image" value="1">
                                <label class="custom-control-label text-danger font-weight-bold" for="delete_image">Delete current image</label>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="custom-file">
                        <input type="file" name="image" class="custom-file-input" id="cat_image" accept="image/*">
                        <label class="custom-file-label" for="cat_image">Choose file</label>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-dark px-4 font-weight-bold">Save Changes</button>
                    <a href="<?= base_url('masters/categories') ?>" class="btn btn-light px-4">Cancel</a>
                </div>

                <?= form_close() ?>
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
