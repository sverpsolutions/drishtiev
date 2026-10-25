<?php
// ============================================================
// FILE: application/views/garments/form.php
// Add / Edit Garment Style & Dynamic Size-Colour Variant Matrix
// ============================================================
$is_edit = !empty($style);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold text-dark">
            <i class="fas fa-tshirt text-primary mr-2"></i><?= $is_edit ? 'Edit Garment Style' : 'Add New Garment Style' ?>
        </h5>
        <small class="text-muted">Configure style details, fabric attributes, and generate size × colour inventory variants</small>
    </div>
    <div>
        <a href="<?= base_url('garments') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Styles
        </a>
    </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle mr-1"></i> <?= $this->session->flashdata('error') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<form method="post" action="<?= base_url('garments/save') ?>" enctype="multipart/form-data" id="garmentForm">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
    <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?= (int)$style->id ?>">
    <?php endif; ?>

    <!-- Card 1: Style Information -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-info-circle text-info mr-1"></i> 1. Parent Style & Classification</h6>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold">Product / Style Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="styleName" class="form-control" required placeholder="e.g. PRINTED KALI PALAZZO" value="<?= htmlspecialchars($style->name ?? '') ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">Style Code / Number</label>
                    <input type="text" name="style_code" id="styleCode" class="form-control" placeholder="e.g. LEFB-4191" value="<?= htmlspecialchars($style->style_code ?? '') ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">Design Number</label>
                    <input type="text" name="design_no" id="designNo" class="form-control" placeholder="e.g. D-15852" value="<?= htmlspecialchars($style->design_no ?? '') ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">Parent Item Code (SKU)</label>
                    <input type="text" name="item_code" class="form-control" placeholder="Auto-generated if empty" value="<?= htmlspecialchars($style->item_code ?? '') ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">Mfr Article No</label>
                    <input type="text" name="mfr_article_no" class="form-control" placeholder="e.g. 15852-A-38-PLZ" value="<?= htmlspecialchars($style->mfr_article_no ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold d-flex justify-content-between align-items-center">
                        <span>Garment Category <span class="text-danger">*</span></span>
                        <a href="javascript:void(0)" onclick="quickAddAttr('category', 'Garment Category', 'garment_category')" class="badge badge-primary text-white" title="Add New Category" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                    </label>
                    <select name="garment_category" class="form-control" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= (($style->garment_category ?? '') === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">Gender / Demographic</label>
                    <select name="gender" class="form-control">
                        <option value="Women" <?= (($style->gender ?? '') === 'Women') ? 'selected' : '' ?>>Women</option>
                        <option value="Men" <?= (($style->gender ?? '') === 'Men') ? 'selected' : '' ?>>Men</option>
                        <option value="Boys" <?= (($style->gender ?? '') === 'Boys') ? 'selected' : '' ?>>Boys</option>
                        <option value="Girls" <?= (($style->gender ?? '') === 'Girls') ? 'selected' : '' ?>>Girls</option>
                        <option value="Unisex" <?= (($style->gender ?? '') === 'Unisex') ? 'selected' : '' ?>>Unisex</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">Brand</label>
                    <select name="brand" class="form-control">
                        <option value="">-- Select Brand --</option>
                        <?php foreach ($brands as $b): ?>
                            <option value="<?= htmlspecialchars($b->name) ?>" <?= (($style->brand ?? '') === $b->name) ? 'selected' : '' ?>><?= htmlspecialchars($b->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold d-flex justify-content-between align-items-center">
                        <span>Fabric Type</span>
                        <a href="javascript:void(0)" onclick="quickAddAttr('fabric', 'Fabric Type', 'fabric_type')" class="badge badge-primary text-white" title="Add New Fabric" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                    </label>
                    <select name="fabric_type" class="form-control">
                        <option value="">-- Select Fabric --</option>
                        <?php foreach ($fabrics as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>" <?= (($style->fabric_type ?? '') === $f) ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">Pattern / Print</label>
                    <input type="text" name="pattern_print" class="form-control" placeholder="e.g. Printed, Solid, Striped" value="<?= htmlspecialchars($style->pattern_print ?? '') ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold d-flex justify-content-between align-items-center">
                        <span>Fit Type</span>
                        <a href="javascript:void(0)" onclick="quickAddAttr('fit', 'Fit Type', 'fit_type')" class="badge badge-primary text-white" title="Add New Fit" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                    </label>
                    <select name="fit_type" class="form-control">
                        <option value="">-- Select Fit --</option>
                        <?php foreach ($fits as $ft): ?>
                            <option value="<?= htmlspecialchars($ft) ?>" <?= (($style->fit_type ?? '') === $ft) ? 'selected' : '' ?>><?= htmlspecialchars($ft) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold d-flex justify-content-between align-items-center">
                        <span>Sleeve Type</span>
                        <a href="javascript:void(0)" onclick="quickAddAttr('sleeve', 'Sleeve Type', 'sleeve_type')" class="badge badge-primary text-white" title="Add New Sleeve" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                    </label>
                    <select name="sleeve_type" class="form-control">
                        <option value="">-- Select Sleeve --</option>
                        <?php foreach ($sleeves as $sl): ?>
                            <option value="<?= htmlspecialchars($sl) ?>" <?= (($style->sleeve_type ?? '') === $sl) ? 'selected' : '' ?>><?= htmlspecialchars($sl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold d-flex justify-content-between align-items-center">
                        <span>Neck Type</span>
                        <a href="javascript:void(0)" onclick="quickAddAttr('neck', 'Neck Type', 'neck_type')" class="badge badge-primary text-white" title="Add New Neck" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                    </label>
                    <select name="neck_type" class="form-control">
                        <option value="">-- Select Neck --</option>
                        <?php foreach ($necks as $nk): ?>
                            <option value="<?= htmlspecialchars($nk) ?>" <?= (($style->neck_type ?? '') === $nk) ? 'selected' : '' ?>><?= htmlspecialchars($nk) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">Length / Rise</label>
                    <input type="text" name="garment_length" class="form-control" placeholder="e.g. Ankle Length, 76 cm" value="<?= htmlspecialchars($style->garment_length ?? '') ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">HSN Code</label>
                    <input type="text" name="hsn_code" class="form-control" placeholder="e.g. 6204" value="<?= htmlspecialchars($style->hsn_code ?? '6204') ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">GST Rate (%)</label>
                    <select name="gst_percent" class="form-control">
                        <option value="5.0" <?= (($style->gst_percent ?? 5.0) == 5.0) ? 'selected' : '' ?>>5% (Garments &lt; ₹1000)</option>
                        <option value="12.0" <?= (($style->gst_percent ?? 5.0) == 12.0) ? 'selected' : '' ?>>12% (Garments &gt; ₹1000)</option>
                        <option value="18.0" <?= (($style->gst_percent ?? 5.0) == 18.0) ? 'selected' : '' ?>>18% (Accessories)</option>
                        <option value="0.0" <?= (($style->gst_percent ?? 5.0) == 0.0) ? 'selected' : '' ?>>0% (Exempt)</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="font-weight-bold">Country of Origin</label>
                    <input type="text" name="country_of_origin" class="form-control" value="<?= htmlspecialchars($style->country_of_origin ?? 'India') ?>">
                </div>
            </div>

            <!-- Base Pricing Defaults for Matrix Auto-fill -->
            <div class="p-3 bg-light rounded border mb-2">
                <div class="font-weight-bold text-dark mb-2"><i class="fas fa-tags text-success mr-1"></i> Base Default Pricing (Applied to new variants)</div>
                <div class="form-row">
                    <div class="col-md-3">
                        <label class="small text-muted font-weight-bold">Default MRP (₹)</label>
                        <input type="number" step="0.01" name="base_mrp" id="baseMrp" class="form-control form-control-sm" placeholder="799.00" value="<?= htmlspecialchars($style->mrp ?? '799.00') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted font-weight-bold">Default Selling Price (₹)</label>
                        <input type="number" step="0.01" name="base_sp" id="baseSp" class="form-control form-control-sm" placeholder="799.00" value="<?= htmlspecialchars($style->selling_price ?? '799.00') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted font-weight-bold">Default Cost / Purchase Price (₹)</label>
                        <input type="number" step="0.01" name="base_cost" id="baseCost" class="form-control form-control-sm" placeholder="350.00" value="<?= htmlspecialchars($style->cost_price ?? '350.00') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted font-weight-bold">Product Image</label>
                        <input type="file" name="image1" class="form-control-file form-control-sm">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Size & Colour Variant Matrix Generator -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-th text-primary mr-1"></i> 2. Size & Colour Variant Matrix</h6>
                <small class="text-muted">Select sizes and colours below to auto-generate variant inventory rows</small>
            </div>
            <div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnShowGenerator">
                    <i class="fas fa-magic mr-1"></i> Variant Matrix Builder
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAddSingleRow">
                    <i class="fas fa-plus mr-1"></i> Add Single Variant Row
                </button>
            </div>
        </div>
        <div class="card-body">

            <!-- Interactive Generator Panel -->
            <div id="generatorPanel" class="p-4 bg-white border border-primary rounded shadow-sm mb-4" style="display: none; border-width: 2px !important;">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div>
                        <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-magic text-primary mr-2"></i> Quick Size &amp; Colour Matrix Generator</h6>
                        <small class="text-muted">Select sizes and colours below to generate all sellable barcode variants with one click.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelGenerator">&times; Close</button>
                </div>

                <div class="row">
                    <!-- Size Selector Column -->
                    <div class="col-lg-6 border-right pr-lg-4 mb-3 mb-lg-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="font-weight-bold text-dark mb-0">
                                <span class="badge badge-primary mr-1">1</span> Step A: Choose Sizes
                            </label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2" id="btnSelectAllSizes">Select All</button>
                                <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2" id="btnDeselectAllSizes">Clear</button>
                            </div>
                        </div>

                        <!-- Size Set Dropdown -->
                        <div class="input-group input-group-sm mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light font-weight-bold text-primary"><i class="fas fa-filter mr-1"></i> Filter Size Set</span>
                            </div>
                            <select id="sizeSetSelector" class="form-control font-weight-bold">
                                <option value="">-- All Size Sets --</option>
                                <?php foreach ($size_sets as $ss): ?>
                                    <option value="<?= (int)$ss->id ?>"><?= htmlspecialchars($ss->name) ?> (<?= htmlspecialchars($ss->code) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Sizes Pills Container -->
                        <div class="border rounded p-3 bg-light d-flex flex-wrap" id="sizeCheckboxes" style="min-height: 200px; max-height: 280px; overflow-y: auto; gap: 8px;">
                            <?php foreach ($all_sizes as $sz): ?>
                                <label class="size-pill-label size-item mb-0 d-inline-flex align-items-center px-3 py-2 bg-white rounded border shadow-sm" data-set-id="<?= (int)$sz->size_set_id ?>" for="sz_<?= $sz->id ?>" style="cursor: pointer; user-select: none; transition: all 0.15s ease;">
                                    <input type="checkbox" class="chk-size mr-2" id="sz_<?= $sz->id ?>" value="<?= $sz->id ?>" data-name="<?= htmlspecialchars($sz->name) ?>" style="width: 16px; height: 16px; cursor: pointer;">
                                    <span class="d-flex flex-column text-left">
                                        <strong class="size-name text-dark" style="font-size: 0.95rem; line-height: 1.1;"><?= htmlspecialchars($sz->name) ?></strong>
                                        <?php if (!empty($sz->measurement)): ?>
                                            <small class="text-muted" style="font-size: 0.72rem; line-height: 1.1; margin-top: 2px;"><?= htmlspecialchars($sz->measurement) ?></small>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Colour Selector Column -->
                    <div class="col-lg-6 pl-lg-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="font-weight-bold text-dark mb-0">
                                <span class="badge badge-success mr-1">2</span> Step B: Choose Colours
                            </label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-success btn-xs py-0 px-2" id="btnSelectAllColours">Select All</button>
                                <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2" id="btnDeselectAllColours">Clear</button>
                            </div>
                        </div>

                        <!-- Colours Pills Container -->
                        <div class="border rounded p-3 bg-light d-flex flex-wrap" id="colourCheckboxes" style="min-height: 245px; max-height: 325px; overflow-y: auto; gap: 8px;">
                            <?php foreach ($all_colours as $col): ?>
                                <label class="colour-pill-label mb-0 d-inline-flex align-items-center px-3 py-2 bg-white rounded border shadow-sm" for="col_<?= $col->id ?>" style="cursor: pointer; user-select: none; transition: all 0.15s ease;">
                                    <input type="checkbox" class="chk-colour mr-2" id="col_<?= $col->id ?>" value="<?= $col->id ?>" data-name="<?= htmlspecialchars($col->name) ?>" data-hex="<?= htmlspecialchars($col->hex_code) ?>" style="width: 16px; height: 16px; cursor: pointer;">
                                    <span class="rounded-circle mr-2 border shadow-sm" style="width: 16px; height: 16px; background: <?= htmlspecialchars($col->hex_code ?: '#CCC') ?>; flex-shrink: 0;"></span>
                                    <strong class="col-name text-dark" style="font-size: 0.88rem;"><?= htmlspecialchars($col->name) ?></strong>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap">
                    <div id="selectionSummaryText" class="text-muted font-weight-bold small mb-2 mb-md-0">
                        <i class="fas fa-info-circle text-primary mr-1"></i> Pick sizes and colours above to preview combination count.
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary btn-sm mr-2" id="btnCancelGenBottom">Cancel</button>
                        <button type="button" class="btn btn-primary font-weight-bold shadow-sm" id="btnGenerateRows">
                            <i class="fas fa-check-circle mr-1"></i> Generate Combinations
                        </button>
                    </div>
                </div>
            </div>

            <!-- Variants Table -->
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0" id="variantTable" style="font-size: 0.85rem;">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 110px;">Size</th>
                            <th style="width: 120px;">Colour</th>
                            <th style="width: 160px;">Variant SKU</th>
                            <th style="width: 170px;">Barcode (EAN-13)</th>
                            <th style="width: 95px;" class="text-right">MRP (₹)</th>
                            <th style="width: 95px;" class="text-right">Selling (₹)</th>
                            <th style="width: 95px;" class="text-right">Cost (₹)</th>
                            <th style="width: 90px;" class="text-center">Opening Stock</th>
                            <th style="width: 80px;" class="text-center">Min Stock</th>
                            <th style="width: 45px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="variantRows">
                        <?php if (!empty($variants)): ?>
                            <?php foreach ($variants as $idx => $v): ?>
                                <tr class="variant-row" data-id="<?= $v->id ?>">
                                    <td>
                                        <input type="hidden" name="var_id[]" value="<?= $v->id ?>">
                                        <input type="hidden" name="var_size_id[]" value="<?= $v->size_id ?>">
                                        <input type="text" name="var_size[]" class="form-control form-control-sm font-weight-bold" value="<?= htmlspecialchars($v->size_name) ?>" required>
                                    </td>
                                    <td>
                                        <input type="hidden" name="var_colour_id[]" value="<?= $v->colour_id ?>">
                                        <div class="input-group input-group-sm">
                                            <?php if (!empty($v->colour_hex)): ?>
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text p-1"><span class="d-inline-block rounded-circle" style="width: 14px; height: 14px; background: <?= htmlspecialchars($v->colour_hex) ?>;"></span></span>
                                                </div>
                                            <?php endif; ?>
                                            <input type="text" name="var_colour[]" class="form-control form-control-sm" value="<?= htmlspecialchars($v->colour_name) ?>" required>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="var_sku[]" class="form-control form-control-sm" value="<?= htmlspecialchars($v->variant_sku) ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" name="var_barcode[]" class="form-control form-control-sm var-barcode" value="<?= htmlspecialchars($v->barcode) ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="var_mrp[]" class="form-control form-control-sm text-right" value="<?= htmlspecialchars($v->mrp) ?>">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="var_sp[]" class="form-control form-control-sm text-right" value="<?= htmlspecialchars($v->selling_price) ?>">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="var_cost[]" class="form-control form-control-sm text-right" value="<?= htmlspecialchars($v->cost_price) ?>">
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success"><?= number_format((float)$v->stock_qty, 0) ?> pcs</span>
                                        <input type="hidden" name="var_opening_stock[]" value="">
                                    </td>
                                    <td>
                                        <input type="number" name="var_min_stock[]" class="form-control form-control-sm text-center" value="<?= htmlspecialchars($v->min_stock) ?>">
                                        <input type="hidden" name="var_reorder[]" value="<?= htmlspecialchars($v->reorder_level) ?>">
                                        <input type="hidden" name="var_status[]" value="<?= htmlspecialchars($v->status) ?>">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-remove-row"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="noVariantsMsg" class="text-center py-4 text-muted" style="<?= !empty($variants) ? 'display:none;' : '' ?>">
                <i class="fas fa-layer-group fa-2x mb-2 text-secondary d-block"></i>
                No variants added yet. Click <strong>Variant Matrix Builder</strong> above to generate sizes & colours.
            </div>
        </div>
    </div>

    <!-- Submit bar -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
            <a href="<?= base_url('garments') ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-success px-4 font-weight-bold" id="btnSubmitGarment">
                <i class="fas fa-save mr-1"></i> Save Style & Variants
            </button>
        </div>
    </div>
</form>

<style>
.size-pill-label, .colour-pill-label {
    border-color: #cbd5e1 !important;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}
.size-pill-label:hover, .colour-pill-label:hover {
    border-color: #94a3b8 !important;
    background-color: #f8fafc !important;
}
.size-pill-label.active-pill {
    border-color: #2563eb !important;
    background-color: #eff6ff !important;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25) !important;
}
.size-pill-label.active-pill .size-name {
    color: #1d4ed8 !important;
}
.colour-pill-label.active-pill {
    border-color: #16a34a !important;
    background-color: #f0fdf4 !important;
    box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.25) !important;
}
.colour-pill-label.active-pill .col-name {
    color: #15803d !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnShowGenerator = document.getElementById('btnShowGenerator');
    const generatorPanel   = document.getElementById('generatorPanel');
    const btnCancelGen     = document.getElementById('btnCancelGenerator');
    const btnCancelBottom  = document.getElementById('btnCancelGenBottom');
    const btnGenerateRows  = document.getElementById('btnGenerateRows');
    const btnAddSingleRow  = document.getElementById('btnAddSingleRow');
    const variantRows      = document.getElementById('variantRows');
    const noVariantsMsg    = document.getElementById('noVariantsMsg');
    const sizeSetSelector  = document.getElementById('sizeSetSelector');
    const summaryText      = document.getElementById('selectionSummaryText');

    // Toggle Generator Panel
    btnShowGenerator.addEventListener('click', function() {
        const isHidden = (generatorPanel.style.display === 'none' || !generatorPanel.style.display);
        generatorPanel.style.display = isHidden ? 'block' : 'none';
        if (isHidden) {
            updateMatrixSummary();
        }
    });
    if (btnCancelGen) {
        btnCancelGen.addEventListener('click', function() {
            generatorPanel.style.display = 'none';
        });
    }
    if (btnCancelBottom) {
        btnCancelBottom.addEventListener('click', function() {
            generatorPanel.style.display = 'none';
        });
    }

    function updatePill(label) {
        const chk = label.querySelector('input[type="checkbox"]');
        if (chk && chk.checked) {
            label.classList.add('active-pill');
        } else {
            label.classList.remove('active-pill');
        }
    }

    function updateMatrixSummary() {
        const sCount = document.querySelectorAll('.chk-size:checked').length;
        const cCount = document.querySelectorAll('.chk-colour:checked').length;
        const total = sCount * cCount;
        if (summaryText) {
            if (total > 0) {
                summaryText.innerHTML = '<span class="text-success"><i class="fas fa-check-circle mr-1"></i> Ready: <strong>' + sCount + ' sizes</strong> &times; <strong>' + cCount + ' colours</strong> = <strong>' + total + ' variant rows</strong></span>';
            } else {
                summaryText.innerHTML = '<span class="text-muted"><i class="fas fa-info-circle text-primary mr-1"></i> Selected: ' + sCount + ' sizes, ' + cCount + ' colours (select at least 1 size and 1 colour)</span>';
            }
        }
    }

    // Attach click/change listeners to all pill labels & inputs
    document.querySelectorAll('.size-pill-label').forEach(label => {
        const chk = label.querySelector('.chk-size');
        chk.addEventListener('change', function() {
            updatePill(label);
            updateMatrixSummary();
        });
        updatePill(label);
    });

    document.querySelectorAll('.colour-pill-label').forEach(label => {
        const chk = label.querySelector('.chk-colour');
        chk.addEventListener('change', function() {
            updatePill(label);
            updateMatrixSummary();
        });
        updatePill(label);
    });

    // Select / Deselect All Sizes
    const btnSelectAllSizes = document.getElementById('btnSelectAllSizes');
    const btnDeselectAllSizes = document.getElementById('btnDeselectAllSizes');
    if (btnSelectAllSizes) {
        btnSelectAllSizes.addEventListener('click', function() {
            document.querySelectorAll('.size-item').forEach(el => {
                if (el.style.display !== 'none') {
                    const chk = el.querySelector('.chk-size');
                    if (chk) chk.checked = true;
                    updatePill(el);
                }
            });
            updateMatrixSummary();
        });
    }
    if (btnDeselectAllSizes) {
        btnDeselectAllSizes.addEventListener('click', function() {
            document.querySelectorAll('.size-item').forEach(el => {
                const chk = el.querySelector('.chk-size');
                if (chk) chk.checked = false;
                updatePill(el);
            });
            updateMatrixSummary();
        });
    }

    // Select / Deselect All Colours
    const btnSelectAllColours = document.getElementById('btnSelectAllColours');
    const btnDeselectAllColours = document.getElementById('btnDeselectAllColours');
    if (btnSelectAllColours) {
        btnSelectAllColours.addEventListener('click', function() {
            document.querySelectorAll('.colour-pill-label').forEach(label => {
                const chk = label.querySelector('.chk-colour');
                if (chk) chk.checked = true;
                updatePill(label);
            });
            updateMatrixSummary();
        });
    }
    if (btnDeselectAllColours) {
        btnDeselectAllColours.addEventListener('click', function() {
            document.querySelectorAll('.colour-pill-label').forEach(label => {
                const chk = label.querySelector('.chk-colour');
                if (chk) chk.checked = false;
                updatePill(label);
            });
            updateMatrixSummary();
        });
    }

    // Filter sizes by Size Set dropdown
    if (sizeSetSelector) {
        sizeSetSelector.addEventListener('change', function() {
            const setId = this.value;
            document.querySelectorAll('.size-item').forEach(el => {
                const itemSetId = el.getAttribute('data-set-id');
                const chk = el.querySelector('.chk-size');
                if (!setId) {
                    el.style.display = 'inline-flex';
                } else if (itemSetId === setId) {
                    el.style.display = 'inline-flex';
                    if (chk) chk.checked = true;
                } else {
                    el.style.display = 'none';
                    if (chk) chk.checked = false;
                }
                updatePill(el);
            });
            updateMatrixSummary();
        });
    }

    // Generate Combination Rows via AJAX
    btnGenerateRows.addEventListener('click', function() {
        const sizeIds = [];
        document.querySelectorAll('.chk-size:checked').forEach(c => sizeIds.push(c.value));
        const colourIds = [];
        document.querySelectorAll('.chk-colour:checked').forEach(c => colourIds.push(c.value));

        if (sizeIds.length === 0 || colourIds.length === 0) {
            alert('Please select at least one Size and one Colour.');
            return;
        }

        const styleCode = document.getElementById('styleCode').value.trim() || document.getElementById('styleName').value.trim() || 'GARMENT';
        const baseMrp   = document.getElementById('baseMrp').value || '799.00';
        const baseSp    = document.getElementById('baseSp').value || '799.00';
        const baseCost  = document.getElementById('baseCost').value || '350.00';

        const csrfName  = '<?= $this->security->get_csrf_token_name() ?>';
        const csrfHash  = document.querySelector('input[name="' + csrfName + '"]').value;

        const formData = new FormData();
        formData.append(csrfName, csrfHash);
        formData.append('style_code', styleCode);
        formData.append('base_mrp', baseMrp);
        formData.append('base_sp', baseSp);
        formData.append('base_cost', baseCost);
        sizeIds.forEach(id => formData.append('size_ids[]', id));
        colourIds.forEach(id => formData.append('colour_ids[]', id));

        btnGenerateRows.disabled = true;
        btnGenerateRows.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generating...';

        fetch('<?= base_url('garments/generate_matrix_ajax') ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btnGenerateRows.disabled = false;
            btnGenerateRows.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Generate Combinations';

            if (data.status === 'success' && data.matrix) {
                noVariantsMsg.style.display = 'none';
                data.matrix.forEach(row => {
                    // Check if already in table
                    let duplicate = false;
                    document.querySelectorAll('.variant-row').forEach(vr => {
                        const szInput = vr.querySelector('input[name="var_size[]"]');
                        const colInput = vr.querySelector('input[name="var_colour[]"]');
                        if (szInput && colInput && szInput.value.toUpperCase() === row.size_name.toUpperCase() && colInput.value.toUpperCase() === row.colour_name.toUpperCase()) {
                            duplicate = true;
                        }
                    });
                    if (!duplicate) {
                        appendVariantRow(row);
                    }
                });
                generatorPanel.style.display = 'none';
            } else {
                alert(data.message || 'Error generating matrix');
            }
        })
        .catch(err => {
            btnGenerateRows.disabled = false;
            btnGenerateRows.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Generate Combinations';
            alert('Request failed');
        });
    });

    // Add Single Variant Row
    btnAddSingleRow.addEventListener('click', function() {
        const styleCode = document.getElementById('styleCode').value.trim() || 'GARMENT';
        const baseMrp   = document.getElementById('baseMrp').value || '799.00';
        const baseSp    = document.getElementById('baseSp').value || '799.00';
        const baseCost  = document.getElementById('baseCost').value || '350.00';
        const rnd = Math.floor(1000 + Math.random() * 9000);

        appendVariantRow({
            size_id: '',
            size_name: 'M',
            colour_id: '',
            colour_name: 'Standard',
            colour_hex: '#CCCCCC',
            sku: styleCode + '-STD-' + rnd,
            barcode: '890' + Math.floor(100000000 + Math.random() * 900000000),
            mrp: baseMrp,
            sp: baseSp,
            cost: baseCost,
            opening_stock: 0,
            min_stock: 2,
            reorder: 5
        });
        noVariantsMsg.style.display = 'none';
    });

    function appendVariantRow(data) {
        const tr = document.createElement('tr');
        tr.className = 'variant-row';
        tr.innerHTML = `
            <td>
                <input type="hidden" name="var_id[]" value="">
                <input type="hidden" name="var_size_id[]" value="${data.size_id || ''}">
                <input type="text" name="var_size[]" class="form-control form-control-sm font-weight-bold" value="${data.size_name}" required>
            </td>
            <td>
                <input type="hidden" name="var_colour_id[]" value="${data.colour_id || ''}">
                <div class="input-group input-group-sm">
                    ${data.colour_hex ? `<div class="input-group-prepend"><span class="input-group-text p-1"><span class="d-inline-block rounded-circle" style="width: 14px; height: 14px; background: ${data.colour_hex};"></span></span></div>` : ''}
                    <input type="text" name="var_colour[]" class="form-control form-control-sm" value="${data.colour_name}" required>
                </div>
            </td>
            <td>
                <input type="text" name="var_sku[]" class="form-control form-control-sm" value="${data.sku}" required>
            </td>
            <td>
                <input type="text" name="var_barcode[]" class="form-control form-control-sm var-barcode" value="${data.barcode}" required>
            </td>
            <td>
                <input type="number" step="0.01" name="var_mrp[]" class="form-control form-control-sm text-right" value="${data.mrp}">
            </td>
            <td>
                <input type="number" step="0.01" name="var_sp[]" class="form-control form-control-sm text-right" value="${data.sp}">
            </td>
            <td>
                <input type="number" step="0.01" name="var_cost[]" class="form-control form-control-sm text-right" value="${data.cost}">
            </td>
            <td>
                <input type="number" name="var_opening_stock[]" class="form-control form-control-sm text-center" value="${data.opening_stock || 0}">
            </td>
            <td>
                <input type="number" name="var_min_stock[]" class="form-control form-control-sm text-center" value="${data.min_stock || 2}">
                <input type="hidden" name="var_reorder[]" value="${data.reorder || 5}">
                <input type="hidden" name="var_status[]" value="1">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-remove-row"><i class="fas fa-trash-alt"></i></button>
            </td>
        `;
        variantRows.appendChild(tr);
    }

    // Remove row
    variantRows.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-row')) {
            const row = e.target.closest('tr');
            row.remove();
            if (variantRows.querySelectorAll('tr').length === 0) {
                noVariantsMsg.style.display = 'block';
            }
        }
    });
});

function quickAddAttr(type, label, selectName) {
    var val = prompt("Enter new " + label + ":");
    if (!val || !val.trim()) return;
    val = val.trim();

    var postData = { type: type, value: val };
    if (window._csrfName && window._csrfHash) {
        postData[window._csrfName] = window._csrfHash;
    }

    $.ajax({
        url: '<?= base_url("garments/add_attribute_ajax") ?>',
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                var select = $('select[name="' + selectName + '"]');
                var exists = false;
                select.find('option').each(function() {
                    if ($(this).text().toLowerCase() === val.toLowerCase()) {
                        exists = true;
                        select.val($(this).val());
                    }
                });
                if (!exists) {
                    select.append($('<option>', { value: val, text: val }));
                    select.val(val);
                }
            } else {
                alert(res.message || 'Error adding attribute.');
            }
        },
        error: function() {
            alert('Server error while saving attribute.');
        }
    });
}
</script>
