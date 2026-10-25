<?php // FILE: application/views/hsn/add.php
$editing = isset($hsn);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 font-weight-bold"><?= $editing ? 'Edit HSN Code' : 'Add HSN Code' ?></h5>
    <a href="<?= base_url('hsn') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>
<?php if (validation_errors()): ?>
    <div class="alert alert-danger"><?= validation_errors() ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:560px;">
    <div class="card-body">
        <?= form_open($editing ? 'hsn/edit/'.$hsn->id : 'hsn/add') ?>

        <div class="form-group">
            <label>HSN Code <span class="text-danger">*</span></label>
            <input type="text" name="hsn_code" class="form-control font-weight-bold"
                   value="<?= htmlspecialchars($editing ? $hsn->hsn_code : $this->input->post('hsn_code')) ?>"
                   placeholder="e.g. 1905" required maxlength="20">
            <small class="text-muted">4–8 digit code as per GST tariff</small>
        </div>

        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" class="form-control"
                   value="<?= htmlspecialchars($editing ? $hsn->description : $this->input->post('description')) ?>"
                   placeholder="e.g. Biscuits, Pastry, Cakes" maxlength="255">
        </div>

        <div class="form-group">
            <label>IGST % <span class="text-danger">*</span> <small class="text-muted">(CGST & SGST auto-split as half each)</small></label>
            <select name="igst_percent" class="form-control font-weight-bold" id="igstSelect" required>
                <?php
                $rates  = [0, 0.1, 0.25, 1, 1.5, 3, 5, 12, 18, 40];
                $current = $editing ? $hsn->igst_percent : $this->input->post('igst_percent');
                foreach ($rates as $r):
                ?>
                <option value="<?= $r ?>" <?= $current == $r ? 'selected' : '' ?>><?= $r ?>%</option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Live preview -->
        <div class="alert alert-light border" id="ratePreview" style="font-size:.87rem;">
            <strong>Rate Preview:</strong><br>
            IGST: <span id="prev_igst">0</span>% &nbsp;|&nbsp;
            CGST: <span id="prev_cgst">0</span>% &nbsp;|&nbsp;
            SGST: <span id="prev_sgst">0</span>%
        </div>

        <button type="submit" class="btn btn-dark px-5"><?= $editing ? 'Update HSN' : 'Save HSN Code' ?></button>
        <?= form_close() ?>
    </div>
</div>

<script>
document.getElementById('igstSelect').addEventListener('change', updatePreview);
updatePreview();
function updatePreview() {
    var igst = parseFloat(document.getElementById('igstSelect').value) || 0;
    var half = Math.round((igst / 2) * 100) / 100;
    document.getElementById('prev_igst').textContent = igst.toFixed(2);
    document.getElementById('prev_cgst').textContent = half.toFixed(2);
    document.getElementById('prev_sgst').textContent = half.toFixed(2);
}
</script>
