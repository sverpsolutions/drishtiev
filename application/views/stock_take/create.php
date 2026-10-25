<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header font-weight-bold">
                <i class="fas fa-clipboard-check mr-1"></i> Start New Stock Take
            </div>
            <div class="card-body">

                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
                <?php endif; ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Note:</strong> Starting a new stock take will automatically cancel any existing in-progress stock take for the selected location.
                    All active products will be pre-loaded for counting.
                </div>

                <form method="POST" action="<?= base_url('stock_take/create') ?>">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                    <div class="form-group">
                        <label class="font-weight-bold">Location <span class="text-danger">*</span></label>
                        <?php if ($user_loc_id): ?>
                            <?php
                            $user_loc_obj = null;
                            foreach ($locations as $l) {
                                if ($l->id == $user_loc_id) { $user_loc_obj = $l; break; }
                            }
                            ?>
                            <input type="hidden" name="location_id" value="<?= (int)$user_loc_id ?>">
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user_loc_obj ? $user_loc_obj->name : 'Your Location') ?>" disabled>
                        <?php else: ?>
                            <select name="location_id" class="form-control" required>
                                <option value="">-- Select Location --</option>
                                <?php foreach ($locations as $loc): ?>
                                <option value="<?= $loc->id ?>">
                                    <?= htmlspecialchars($loc->name) ?>
                                    (<?= ucfirst($loc->type) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Notes <small class="text-muted">(optional)</small></label>
                        <textarea name="notes" class="form-control" rows="3"
                            placeholder="e.g. Monthly stock count, End of year audit..."></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('stock_take') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play mr-1"></i> Start Stock Take
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
