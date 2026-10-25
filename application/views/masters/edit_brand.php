<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 font-weight-bold">Edit Brand</h5>
    <a href="<?= base_url('masters/brands') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Brands</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?= form_open_multipart('masters/edit_brand/'.$brand->id) ?>

                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Brand Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($brand->name) ?>" required>
                </div>

                <div class="form-group mb-4">
                    <label class="font-weight-bold small text-muted">Brand Logo / Image</label>

                    <?php if (!empty($brand->image)): ?>
                    <div class="d-flex align-items-center mb-3 p-2 border rounded bg-white" style="min-height:80px;">
                        <img src="<?= base_url('assets/uploads/brands/'.$brand->image) ?>" alt="Brand Logo" style="height:60px;width:60px;object-fit:contain;border-radius:4px;border:1px solid #ddd;background:#f8f9fa;margin-right:12px;">
                        <div>
                            <div class="custom-control custom-checkbox small">
                                <input type="checkbox" class="custom-control-input" id="delete_image" name="delete_image" value="1">
                                <label class="custom-control-label text-danger font-weight-bold" for="delete_image">Delete current image</label>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="custom-file">
                        <input type="file" name="image" class="custom-file-input" id="brand_image" accept="image/*">
                        <label class="custom-file-label" for="brand_image">Choose file</label>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-dark px-4 font-weight-bold">Save Changes</button>
                    <a href="<?= base_url('masters/brands') ?>" class="btn btn-light px-4">Cancel</a>
                </div>

                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('brand_image').addEventListener('change', function(e) {
    var label = e.target.nextElementSibling;
    if (label) label.innerText = e.target.files[0]?.name || 'Choose file';
});
</script>
