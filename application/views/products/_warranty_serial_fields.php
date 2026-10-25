<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// Shared by products/add.php and products/edit.php. $product is the row on
// edit, NULL on add. Guarded with isset() throughout so the partial still
// renders on an install where sql/43_serial_warranty.sql has not run yet.
$w_on    = !empty($product->warranty_applicable);
$w_days  = isset($product->warranty_days) ? (int)$product->warranty_days : 0;
$s_on    = !empty($product->maintain_serial);
$s_meth  = $product->serial_entry_method ?? 'scanner';
?>

<div class="card border-info mb-3">
    <div class="card-header bg-light py-2">
        <strong class="text-info"><i class="fas fa-shield-alt mr-1"></i> Warranty &amp; Serial Number Tracking</strong>
        <small class="text-muted ml-2">Leave both off for a normal quantity-based item</small>
    </div>
    <div class="card-body pb-2">
        <div class="row">

            <!-- ── Warranty ───────────────────────────────────────────── -->
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox mt-2">
                    <input type="checkbox" name="warranty_applicable" id="warranty_applicable"
                           class="custom-control-input" value="1" <?= $w_on ? 'checked' : '' ?>>
                    <label class="custom-control-label font-weight-bold" for="warranty_applicable">
                        Warranty Applicable
                    </label>
                </div>
            </div>

            <div class="col-md-3 mb-3" id="warrantyDaysWrap" style="<?= $w_on ? '' : 'display:none;' ?>">
                <label>Warranty Period (Days) <span class="text-danger">*</span></label>
                <input type="number" name="warranty_days" id="warranty_days" class="form-control"
                       value="<?= $w_days ?>" min="0" step="1" placeholder="e.g. 365">
                <small class="text-muted" id="warrantyHint"></small>
            </div>

            <div class="col-md-3 mb-3" id="warrantyStartWrap" style="<?= $w_on ? '' : 'display:none;' ?>">
                <label>Warranty Starts From</label>
                <input type="text" class="form-control" value="Sales Invoice Date" readonly>
                <small class="text-muted">Expiry is calculated automatically at billing</small>
            </div>

            <div class="col-md-6 mb-3" id="warrantyPolicyWrap" style="<?= $w_on ? '' : 'display:none;' ?>">
                <label>Warranty Policy / Coverage Terms</label>
                <input type="text" name="warranty_policy" id="warranty_policy" class="form-control"
                       value="<?= htmlspecialchars($product->warranty_policy ?? '') ?>"
                       placeholder="e.g. 3 Years or 30,000 KM warranty on battery, motor & controller">
            </div>

            <!-- ── Serial numbers ─────────────────────────────────────── -->
            <div class="col-md-3 mb-3">
                <div class="custom-control custom-checkbox mt-2">
                    <input type="checkbox" name="maintain_serial" id="maintain_serial"
                           class="custom-control-input" value="1" <?= $s_on ? 'checked' : '' ?>>
                    <label class="custom-control-label font-weight-bold" for="maintain_serial">
                        Maintain Serial Number
                    </label>
                </div>
            </div>

            <div class="col-md-3 mb-3" id="serialMethodWrap" style="<?= $s_on ? '' : 'display:none;' ?>">
                <label>Serial Number Entry Method</label>
                <select name="serial_entry_method" class="form-control">
                    <option value="scanner" <?= $s_meth === 'scanner' ? 'selected' : '' ?>>Barcode Scanner</option>
                    <option value="manual"  <?= $s_meth === 'manual'  ? 'selected' : '' ?>>Manual Entry</option>
                    <option value="excel"   <?= $s_meth === 'excel'   ? 'selected' : '' ?>>Excel Import</option>
                </select>
            </div>

            <div class="col-md-6 mb-3" id="serialNoteWrap" style="<?= $s_on ? '' : 'display:none;' ?>">
                <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:.82rem;">
                    <i class="fas fa-info-circle mr-1"></i>
                    Every unit of this item will need its own serial number at purchase and at billing.
                    Serialized items <strong>cannot be billed offline</strong> — the serial has to be
                    checked against the server before it can be sold.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var wChk = document.getElementById('warranty_applicable'),
        sChk = document.getElementById('maintain_serial'),
        wDays = document.getElementById('warranty_days'),
        hint  = document.getElementById('warrantyHint');

    function show(id, on) {
        var el = document.getElementById(id);
        if (el) el.style.display = on ? '' : 'none';
    }

    // Shows the operator what the day count actually means in months/years,
    // so "365" vs "3650" is not a silent typo.
    function updateHint() {
        if (!hint || !wDays) return;
        var d = parseInt(wDays.value, 10);
        if (!d || d < 1) { hint.textContent = ''; return; }
        var txt = d + ' days';
        if (d % 365 === 0)      txt += '  ≈  ' + (d / 365) + ' year(s)';
        else if (d % 30 === 0)  txt += '  ≈  ' + (d / 30) + ' month(s)';
        hint.textContent = txt;
    }

    function syncWarranty() {
        var on = wChk && wChk.checked;
        show('warrantyDaysWrap', on);
        show('warrantyStartWrap', on);
        show('warrantyPolicyWrap', on);
        if (wDays) wDays.required = !!on;
        updateHint();
    }
    function syncSerial() {
        var on = sChk && sChk.checked;
        show('serialMethodWrap', on);
        show('serialNoteWrap', on);
    }

    if (wChk)  wChk.addEventListener('change', syncWarranty);
    if (sChk)  sChk.addEventListener('change', syncSerial);
    if (wDays) wDays.addEventListener('input', updateHint);
    syncWarranty(); syncSerial();
})();
</script>
