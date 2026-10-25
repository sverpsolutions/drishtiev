<?php
// ============================================================
// FILE: application/views/garments/colours.php
// Vehicle & Product Color Master (EV Scooter Dealership)
// ============================================================
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold text-dark">
            <i class="fas fa-palette text-warning mr-2"></i>Color Master (Vehicle &amp; Product Colours)
        </h5>
        <small class="text-muted">Manage EV scooter body colours, paint codes, finishes, and hex swatches for billing and catalog</small>
    </div>
    <div class="d-flex" style="gap: 8px;">
        <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-boxes mr-1"></i> Products
        </a>
        <a href="<?= base_url('billing/create') ?>" class="btn btn-success btn-sm font-weight-bold">
            <i class="fas fa-motorcycle mr-1"></i> New EV Bill
        </a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm">
        <i class="fas fa-check-circle mr-1"></i> <?= $this->session->flashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Add / Edit Colour Form -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-bold text-dark" id="formTitle">
                    <i class="fas fa-plus-circle text-primary mr-1"></i> Add New Colour
                </h6>
                <button type="button" class="btn btn-sm btn-link text-muted p-0" id="btnResetForm" onclick="resetColourForm()" style="display:none;">
                    <i class="fas fa-undo mr-1"></i> Cancel
                </button>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('garments/colours') ?>" id="colourForm">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="id" id="colourId" value="">

                    <div class="form-group">
                        <label class="font-weight-bold small">Colour Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="colourName" class="form-control form-control-sm" placeholder="e.g. Pearl White, Matte Black" required autofocus>
                        <small class="text-muted">Displayed on scooter catalog, billing &amp; invoice print</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Abbreviation / Code</label>
                        <input type="text" name="code" id="colourCode" class="form-control form-control-sm text-uppercase" placeholder="e.g. WHT, MBLK">
                        <small class="text-muted">Short vehicle code for VIN/barcode</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Hex Colour Swatch</label>
                        <div class="input-group input-group-sm mb-2">
                            <input type="color" name="hex_code" id="colorPicker" class="form-control form-control-sm p-1" value="#16A34A" style="max-width: 60px; height: 35px; cursor: pointer;">
                            <input type="text" id="hexText" class="form-control form-control-sm font-weight-bold" value="#16A34A" readonly style="letter-spacing:0.05em;">
                        </div>
                        
                        <!-- Quick EV Presets -->
                        <div class="small text-muted mb-1 font-weight-bold">Quick Presets:</div>
                        <div class="d-flex flex-wrap" style="gap: 5px;">
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#FFFFFF;" title="White" onclick="pickPreset('Pearl White', 'WHT', '#FFFFFF')"></button>
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#1C1C1C;" title="Matte Black" onclick="pickPreset('Matte Black', 'MBLK', '#1C1C1C')"></button>
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#0A0A0A;" title="Glossy Black" onclick="pickPreset('Glossy Black', 'GBLK', '#0A0A0A')"></button>
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#708090;" title="Metallic Grey" onclick="pickPreset('Metallic Grey', 'MGRY', '#708090')"></button>
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#2F4F4F;" title="Gunmetal Grey" onclick="pickPreset('Gunmetal Grey', 'GG', '#2F4F4F')"></button>
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#DC2626;" title="Racing Red" onclick="pickPreset('Racing Red', 'RED', '#DC2626')"></button>
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#990000;" title="Crimson Red" onclick="pickPreset('Crimson Red', 'CRED', '#990000')"></button>
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#1E3A8A;" title="Metallic Blue" onclick="pickPreset('Metallic Blue', 'MBLU', '#1E3A8A')"></button>
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#06B6D4;" title="Electric Cyan" onclick="pickPreset('Electric Cyan', 'CYAN', '#06B6D4')"></button>
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#556B2F;" title="Olive Green" onclick="pickPreset('Olive Green', 'GRN', '#556B2F')"></button>
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#F59E0B;" title="Golden Yellow" onclick="pickPreset('Golden Yellow', 'YEL', '#F59E0B')"></button>
                            <button type="button" class="btn btn-sm btn-light border p-1" style="width:24px;height:24px;background:#EA580C;" title="Sunset Orange" onclick="pickPreset('Sunset Orange', 'ORG', '#EA580C')"></button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold" id="btnSubmitColour">
                        <i class="fas fa-save mr-1"></i> Save Colour
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Colours List -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <h6 class="mb-0 font-weight-bold text-dark mr-2">
                        <i class="fas fa-swatchbook text-info mr-1"></i> Vehicle Colour Palette
                    </h6>
                    <span class="badge badge-secondary"><?= count($colours) ?> Colours</span>
                </div>
                <div style="width: 200px;">
                    <input type="text" id="paletteSearch" class="form-control form-control-sm" placeholder="Filter colours..." oninput="filterColours(this.value)">
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="paletteGrid">
                    <?php if (empty($colours)): ?>
                        <div class="col-12 text-center text-muted py-4">
                            <i class="fas fa-palette fa-2x mb-2 text-muted" style="opacity: 0.4;"></i>
                            <p class="mb-0">No colours found. Add your vehicle colours on the left.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($colours as $col): ?>
                            <div class="col-md-6 col-lg-4 mb-3 colour-card-wrap" data-name="<?= strtolower(htmlspecialchars($col->name)) ?>" data-code="<?= strtolower(htmlspecialchars($col->code ?? '')) ?>">
                                <div class="card border h-100 shadow-sm">
                                    <div class="card-body p-2 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center overflow-hidden mr-2">
                                            <span class="d-inline-block rounded-circle mr-2 border shadow-sm flex-shrink-0" 
                                                  style="width: 30px; height: 30px; background: <?= htmlspecialchars($col->hex_code ?: '#CCCCCC') ?>; border: 2px solid #e2e8f0 !important;"></span>
                                            <div class="overflow-hidden">
                                                <div class="font-weight-bold text-dark text-truncate" title="<?= htmlspecialchars($col->name) ?>" style="font-size:0.86rem;">
                                                    <?= htmlspecialchars($col->name) ?>
                                                </div>
                                                <div class="text-muted small">
                                                    <code><?= htmlspecialchars($col->code ?: '—') ?></code> &bull; <span style="font-family:monospace; font-size:0.75rem;"><?= htmlspecialchars($col->hex_code ?: '#CCCCCC') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-shrink-0" style="gap: 4px;">
                                            <button type="button" class="btn btn-sm btn-outline-info p-1" title="Edit"
                                                    onclick="editColour(<?= (int)$col->id ?>, '<?= addslashes(htmlspecialchars($col->name)) ?>', '<?= addslashes(htmlspecialchars($col->code ?? '')) ?>', '<?= addslashes(htmlspecialchars($col->hex_code ?? '#CCCCCC')) ?>')">
                                                <i class="fas fa-pencil-alt fa-xs"></i>
                                            </button>
                                            <a href="<?= base_url('garments/delete_colour/' . $col->id) ?>" class="btn btn-sm btn-outline-danger p-1" title="Delete"
                                               onclick="return confirm('Delete colour <?= addslashes(htmlspecialchars($col->name)) ?>?');">
                                                <i class="fas fa-trash fa-xs"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const picker = document.getElementById('colorPicker');
    const txt    = document.getElementById('hexText');
    if (picker && txt) {
        picker.addEventListener('input', function() {
            txt.value = this.value.toUpperCase();
        });
    }
});

function pickPreset(name, code, hex) {
    document.getElementById('colourName').value = name;
    document.getElementById('colourCode').value = code;
    document.getElementById('colorPicker').value = hex;
    document.getElementById('hexText').value = hex.toUpperCase();
}

function editColour(id, name, code, hex) {
    document.getElementById('colourId').value = id;
    document.getElementById('colourName').value = name;
    document.getElementById('colourCode').value = code;
    document.getElementById('colorPicker').value = hex;
    document.getElementById('hexText').value = hex.toUpperCase();

    document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-info mr-1"></i> Edit Colour: ' + name;
    document.getElementById('btnSubmitColour').innerHTML = '<i class="fas fa-check mr-1"></i> Update Colour';
    document.getElementById('btnResetForm').style.display = 'inline-block';
    document.getElementById('colourName').focus();
}

function resetColourForm() {
    document.getElementById('colourId').value = '';
    document.getElementById('colourName').value = '';
    document.getElementById('colourCode').value = '';
    document.getElementById('colorPicker').value = '#16A34A';
    document.getElementById('hexText').value = '#16A34A';

    document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle text-primary mr-1"></i> Add New Colour';
    document.getElementById('btnSubmitColour').innerHTML = '<i class="fas fa-save mr-1"></i> Save Colour';
    document.getElementById('btnResetForm').style.display = 'none';
}

function filterColours(q) {
    q = (q || '').toLowerCase().trim();
    const cards = document.querySelectorAll('.colour-card-wrap');
    cards.forEach(function(c) {
        const name = c.getAttribute('data-name') || '';
        const code = c.getAttribute('data-code') || '';
        if (!q || name.indexOf(q) !== -1 || code.indexOf(q) !== -1) {
            c.style.display = 'block';
        } else {
            c.style.display = 'none';
        }
    });
}
</script>
