<?php // FILE: application/views/marketing/posters.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> Poster Templates</h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / Poster Templates</small>
    </div>
    <a href="<?= base_url('marketing/campaigns') ?>" class="btn btn-outline-dark btn-sm">Back to Campaigns</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Upload New Background</div>
            <div class="card-body">
                <form method="post" action="<?= base_url('marketing/poster_upload') ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="small font-weight-bold mb-0">Name *</label>
                        <input type="text" name="name" class="form-control form-control-sm" required placeholder="e.g. Diwali Gold Frame">
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold mb-0">Category</label>
                        <select name="category" class="form-control form-control-sm">
                            <option value="general">General</option>
                            <option value="festival">Festival</option>
                            <option value="sale">Sale / Clearance</option>
                            <option value="new_arrival">New Arrival</option>
                            <option value="loyalty">Loyalty / Membership</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold mb-0">Background Image *</label>
                        <input type="file" name="background" class="form-control-file" accept=".jpg,.jpeg,.png" required>
                        <small class="text-muted">JPG/PNG, up to 4MB. Landscape images (e.g. 1080×1080 or 1200×1500) work best — a dark band is added across the bottom for text.</small>
                    </div>
                    <button type="submit" class="btn btn-dark btn-sm btn-block">Upload</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row">
            <?php if (!empty($posters)): foreach ($posters as $p): ?>
                <div class="col-md-4 col-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="<?= base_url($p->background_path) ?>" class="card-img-top" style="height:140px;object-fit:cover;" alt="<?= htmlspecialchars($p->name) ?>">
                        <div class="card-body p-2">
                            <div class="small font-weight-bold text-truncate"><?= htmlspecialchars($p->name) ?></div>
                            <div class="small text-muted text-capitalize mb-2"><?= htmlspecialchars(str_replace('_', ' ', $p->category)) ?></div>
                            <a href="<?= base_url('marketing/poster_delete/' . $p->id) ?>" class="btn btn-outline-danger btn-sm py-0 w-100"
                               onclick="return confirm('Remove this poster template?');">Remove</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center text-muted py-4">No poster templates yet — upload a background image to get started.</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
