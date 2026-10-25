<?php // FILE: application/views/marketing/poster_generate.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> Generate Poster</h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / <a href="<?= base_url('marketing/campaign_view/' . $campaign->id) ?>"><?= htmlspecialchars($campaign->name) ?></a> / Poster</small>
    </div>
    <a href="<?= base_url('marketing/campaign_view/' . $campaign->id) ?>" class="btn btn-outline-dark btn-sm">Back to Campaign</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<?php if (empty($posters)): ?>
    <div class="alert alert-warning">No poster templates uploaded yet. <a href="<?= base_url('marketing/posters') ?>">Upload one first</a>, then come back here.</div>
<?php else: ?>
<form method="post" action="<?= base_url('marketing/poster_generate/' . $campaign->id) ?>">
    <div class="row">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Choose a Background</div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($posters as $p): ?>
                        <div class="col-4 mb-3 text-center">
                            <label class="d-block mb-1" style="cursor:pointer;">
                                <img src="<?= base_url($p->background_path) ?>" class="img-fluid rounded border" style="height:90px;width:100%;object-fit:cover;">
                                <input type="radio" name="poster_template_id" value="<?= $p->id ?>" class="mt-1" required>
                            </label>
                            <div class="small text-truncate"><?= htmlspecialchars($p->name) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Poster Text</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="small font-weight-bold mb-0">Headline</label>
                        <input type="text" name="headline" class="form-control form-control-sm" value="<?= htmlspecialchars($campaign->name) ?>" maxlength="60">
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold mb-0">Offer Text</label>
                        <input type="text" name="offer_text" class="form-control form-control-sm" placeholder="e.g. Flat 20% OFF on Groceries" maxlength="80">
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold mb-0">QR Code Links To</label>
                        <input type="text" name="qr_url" class="form-control form-control-sm" value="<?= base_url() ?>" placeholder="store URL, coupon page, etc.">
                        <small class="text-muted">Printed as a scannable QR in the corner of the poster.</small>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-dark btn-block">Generate Poster</button>
        </div>
    </div>
</form>
<?php endif; ?>
