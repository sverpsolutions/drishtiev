<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 font-weight-bold">Brands Master</h5>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold">Add Brand</div>
            <div class="card-body">
                <?= form_open_multipart('masters/brands') ?>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Brand Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Amul" required autofocus>
                </div>
                <div class="form-group mb-4">
                    <label class="font-weight-bold small text-muted">Brand Logo / Image</label>
                    <div class="custom-file">
                        <input type="file" name="image" class="custom-file-input" id="brand_image" accept="image/*">
                        <label class="custom-file-label" for="brand_image">Choose file</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark btn-block font-weight-bold">Save Brand</button>
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
                            <th>Logo</th>
                            <th>Brand Name</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($brands)): ?>
                            <?php foreach($brands as $k => $b): ?>
                            <tr>
                                <td class="align-middle"><?= $k+1 ?></td>
                                <td class="align-middle">
                                    <?php if (!empty($b->image)): ?>
                                        <img src="<?= base_url('assets/uploads/brands/'.$b->image) ?>" alt="logo" style="height:35px;width:35px;object-fit:contain;border-radius:4px;border:1px solid #dee2e6;background:#f8f9fa;">
                                    <?php else: ?>
                                        <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="height:35px;width:35px;border-radius:4px;font-size:10px;">🏷️</div>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle"><?= htmlspecialchars($b->name) ?></td>
                                <td class="text-right align-middle">
                                    <a href="<?= base_url('masters/edit_brand/'.$b->id) ?>" class="btn btn-outline-primary btn-sm mr-1">Edit</a>
                                    <a href="<?= base_url('masters/delete_brand/'.$b->id) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this brand?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No brands added yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
