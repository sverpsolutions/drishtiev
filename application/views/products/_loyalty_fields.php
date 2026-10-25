<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// Shared by products/add.php and products/edit.php. $product is the row on
// edit, NULL on add. Guarded so the partial still renders on an install where
// sql/45_loyalty.sql has not run.
$l_on   = !isset($product->allow_loyalty_points) ? true : (int)$product->allow_loyalty_points === 1;
$l_ppu  = $product->loyalty_points_per_unit  ?? '';
$l_app  = $product->loyalty_amount_per_point ?? '';
$l_why  = $product->loyalty_block_reason     ?? '';
?>

<div class="card border-success mb-3">
    <div class="card-header bg-light py-2">
        <strong class="text-success"><i class="fas fa-gift mr-1"></i> Loyalty Points</strong>
        <small class="text-muted ml-2">On by default — switch off for dairy, tobacco, promotional stock etc.</small>
    </div>
    <div class="card-body pb-2">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox mt-2">
                    <input type="checkbox" name="allow_loyalty_points" id="allow_loyalty_points"
                           class="custom-control-input" value="1" <?= $l_on ? 'checked' : '' ?>>
                    <label class="custom-control-label font-weight-bold" for="allow_loyalty_points">
                        Allow Loyalty Points
                    </label>
                </div>
                <small class="text-muted d-block mt-1" id="loyaltyStateHint"></small>
            </div>

            <div class="col-md-3 mb-3 loyalty-on">
                <label>Points per Unit <small class="text-muted">(optional)</small></label>
                <input type="number" step="0.001" min="0" name="loyalty_points_per_unit"
                       class="form-control" value="<?= htmlspecialchars((string)$l_ppu) ?>"
                       placeholder="e.g. 10">
                <small class="text-muted">Fixed points for every unit sold.</small>
            </div>

            <div class="col-md-3 mb-3 loyalty-on">
                <label>&#8377; per Point <small class="text-muted">(optional)</small></label>
                <input type="number" step="0.01" min="0" name="loyalty_amount_per_point"
                       class="form-control" value="<?= htmlspecialchars((string)$l_app) ?>"
                       placeholder="e.g. 50">
                <small class="text-muted">Overrides the global rate for this item.</small>
            </div>

            <div class="col-md-3 mb-3 loyalty-off" style="display:none;">
                <label>Reason <small class="text-muted">(shown on reports)</small></label>
                <input type="text" name="loyalty_block_reason" class="form-control" maxlength="120"
                       value="<?= htmlspecialchars((string)$l_why) ?>"
                       placeholder="e.g. Dairy — margin too thin">
            </div>

            <div class="col-12">
                <div class="alert alert-light border py-2 px-3 mb-2" style="font-size:.8rem;">
                    <i class="fas fa-info-circle mr-1"></i>
                    Leave both rate boxes blank to use the global rule from
                    <strong>Loyalty &rarr; Settings</strong>. A category set to "no points", or a
                    promotional scheme that excludes points, will override this item and award
                    <strong>zero</strong> regardless of what is set here.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var chk = document.getElementById('allow_loyalty_points'),
        hint = document.getElementById('loyaltyStateHint');
    function sync() {
        var on = chk && chk.checked;
        document.querySelectorAll('.loyalty-on').forEach(function (e) { e.style.display = on ? '' : 'none'; });
        document.querySelectorAll('.loyalty-off').forEach(function (e) { e.style.display = on ? 'none' : ''; });
        if (hint) {
            hint.innerHTML = on
                ? '<span class="text-success">Customers earn points on this item.</span>'
                : '<span class="text-danger font-weight-bold">This item will always award 0 points.</span>';
        }
    }
    if (chk) chk.addEventListener('change', sync);
    sync();
})();
</script>
