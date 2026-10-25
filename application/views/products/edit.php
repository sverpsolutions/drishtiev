<?php // FILE: application/views/products/edit.php ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 font-weight-bold">Edit Product</h5>
    <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>
<?php if (validation_errors()): ?>
    <div class="alert alert-danger"><?= validation_errors() ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?= form_open_multipart('products/edit/'.$product->id) ?>

        <?php 
        $is_garment = (!empty($product->is_garment) || ($product->product_type ?? '') === 'garment');
        $store_business_type = $company['retail_business_type'] ?? 'ev_dealership';
        ?>

        <!-- 1. Basic Info & Product Classification -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="font-weight-bold text-muted mb-0">1. Basic Info</h6>
        </div>

        <?php $this->load->view('products/_ev_fields', ['product' => $product]); ?>

        <!-- 1. Product Name, Item Code, Brand, Category (ON TOP) -->
        <div class="row">
            <div class="col-md-5 mb-3">
                <label class="font-weight-bold text-dark" style="font-size: 1rem;">Product Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control form-control-lg font-weight-bold border-primary shadow-sm" value="<?= htmlspecialchars($product->name) ?>" required>
            </div>
            <div class="col-md-2 mb-3">
                <label class="font-weight-bold text-dark">Item Code</label>
                <input type="text" name="item_code" class="form-control font-weight-bold bg-light" value="<?= htmlspecialchars($product->item_code ?? '') ?>" maxlength="6">
            </div>
            <div class="col-md-3 mb-3">
                <label class="font-weight-bold d-flex justify-content-between align-items-center">
                    <span>Brand</span>
                    <a href="<?= base_url('masters/brands') ?>" target="_blank" class="small text-success">+ Add Brand</a>
                </label>
                <select name="brand_id" class="form-control">
                    <option value="">-- None --</option>
                    <?php foreach($brands as $b): ?>
                    <option value="<?= $b->id ?>" <?= $b->id == $product->brand_id ? 'selected':'' ?>><?= htmlspecialchars($b->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label class="font-weight-bold d-flex justify-content-between align-items-center">
                    <span>Category</span>
                    <a href="<?= base_url('masters/categories') ?>" target="_blank" class="small text-success">+ Add Category</a>
                </label>
                <select name="category_id" class="form-control">
                    <option value="">-- None --</option>
                    <?php foreach($categories as $c): ?>
                    <option value="<?= $c->id ?>" <?= $c->id == $product->category_id ? 'selected':'' ?>><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Garment Specific Attributes (Dynamically shown for garments) -->
        <div id="garmentFieldsSection" class="card border border-info shadow-sm mb-4" style="display: <?= $is_garment ? 'block' : 'none' ?>; background: #f8fafc;">
            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                <span class="font-weight-bold"><i class="fas fa-tshirt mr-1"></i> Clothing &amp; Garment Specifications</span>
                <span class="badge badge-light text-primary font-weight-bold">Size &amp; Colour Enabled</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold d-flex justify-content-between align-items-center">
                            <span>Garment Category</span>
                            <a href="javascript:void(0)" onclick="quickAddAttr('category', 'Garment Category', 'garment_category')" class="badge badge-primary text-white" title="Add New Category" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                        </label>
                        <select name="garment_category" class="form-control">
                            <option value="">-- Select Category --</option>
                            <?php foreach (($garment_categories ?? []) as $gcat): ?>
                                <option value="<?= htmlspecialchars($gcat) ?>" <?= ($product->garment_category ?? '') === $gcat ? 'selected' : '' ?>><?= htmlspecialchars($gcat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Gender / Demographic</label>
                        <select name="gender" class="form-control">
                            <?php foreach (['Women', 'Men', 'Boys', 'Girls', 'Unisex'] as $gnd): ?>
                                <option value="<?= $gnd ?>" <?= ($product->gender ?? '') === $gnd ? 'selected' : '' ?>><?= $gnd ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Style Code</label>
                        <input type="text" name="style_code" id="style_code_input" class="form-control" value="<?= htmlspecialchars($product->style_code ?? '') ?>" placeholder="e.g. LEFB-4191">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Design Number</label>
                        <input type="text" name="design_no" id="design_no_input" class="form-control" value="<?= htmlspecialchars($product->design_no ?? '') ?>" placeholder="e.g. D-15852">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold d-flex justify-content-between align-items-center">
                            <span>Fabric Type</span>
                            <a href="javascript:void(0)" onclick="quickAddAttr('fabric', 'Fabric Type', 'fabric_type')" class="badge badge-primary text-white" title="Add New Fabric" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                        </label>
                        <select name="fabric_type" class="form-control">
                            <option value="">-- Select Fabric --</option>
                            <?php foreach (($fabric_types ?? []) as $fab): ?>
                                <option value="<?= htmlspecialchars($fab) ?>" <?= ($product->fabric_type ?? '') === $fab ? 'selected' : '' ?>><?= htmlspecialchars($fab) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Pattern / Print</label>
                        <input type="text" name="pattern_print" class="form-control" value="<?= htmlspecialchars($product->pattern_print ?? '') ?>" placeholder="e.g. Printed, Plain, Striped">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold d-flex justify-content-between align-items-center">
                            <span>Fit Type</span>
                            <a href="javascript:void(0)" onclick="quickAddAttr('fit', 'Fit Type', 'fit_type')" class="badge badge-primary text-white" title="Add New Fit" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                        </label>
                        <select name="fit_type" class="form-control">
                            <option value="">-- Select Fit --</option>
                            <?php foreach (($fit_types ?? []) as $ft): ?>
                                <option value="<?= htmlspecialchars($ft) ?>" <?= ($product->fit_type ?? '') === $ft ? 'selected' : '' ?>><?= htmlspecialchars($ft) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold d-flex justify-content-between align-items-center">
                            <span>Sleeve Type</span>
                            <a href="javascript:void(0)" onclick="quickAddAttr('sleeve', 'Sleeve Type', 'sleeve_type')" class="badge badge-primary text-white" title="Add New Sleeve" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                        </label>
                        <select name="sleeve_type" class="form-control">
                            <option value="">-- Select Sleeve --</option>
                            <?php foreach (($sleeve_types ?? []) as $sl): ?>
                                <option value="<?= htmlspecialchars($sl) ?>" <?= ($product->sleeve_type ?? '') === $sl ? 'selected' : '' ?>><?= htmlspecialchars($sl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold d-flex justify-content-between align-items-center">
                            <span>Neck Type</span>
                            <a href="javascript:void(0)" onclick="quickAddAttr('neck', 'Neck Type', 'neck_type')" class="badge badge-primary text-white" title="Add New Neck" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                        </label>
                        <select name="neck_type" class="form-control">
                            <option value="">-- Select Neck --</option>
                            <?php foreach (($neck_types ?? []) as $nk): ?>
                                <option value="<?= htmlspecialchars($nk) ?>" <?= ($product->neck_type ?? '') === $nk ? 'selected' : '' ?>><?= htmlspecialchars($nk) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Garment Length / Inseam</label>
                        <input type="text" name="garment_length" class="form-control" value="<?= htmlspecialchars($product->garment_length ?? '') ?>" placeholder="e.g. 76 cm, Ankle Length">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Mfr Article No</label>
                        <input type="text" name="mfr_article_no" id="mfr_article_no_input" class="form-control" value="<?= htmlspecialchars($product->mfr_article_no ?? '') ?>" placeholder="e.g. 15852-A-38-PLZ">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Country of Origin</label>
                        <input type="text" name="country_of_origin" class="form-control" value="<?= htmlspecialchars($product->country_of_origin ?? 'India') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="fmcgIngredientsSection" style="<?= ($store_business_type === 'ev_dealership') ? 'display:none;' : '' ?>">
            <?php if (!empty($racks)): ?>
            <div class="col-md-3 mb-3">
                <label>Rack <a href="<?= base_url('masters/racks') ?>" target="_blank" class="small text-success ml-1">+ Add</a></label>
                <select name="rack_id" class="form-control">
                    <option value="">-- None --</option>
                    <?php foreach($racks as $rk): ?>
                    <option value="<?= $rk->id ?>" <?= $rk->id == ($product->rack_id ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($rk->rack_code . ' — ' . $rk->rack_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-9 mb-3">
            <?php else: ?>
            <div class="col-md-12 mb-3">
            <?php endif; ?>
                <label>Ingredients <small class="text-muted">(will be printed on barcodes)</small></label>
                <textarea name="ingredients" class="form-control" rows="2" placeholder="e.g. Wheat flour, sugar, butter, yeast, milk..."><?= htmlspecialchars($product->ingredients ?? '') ?></textarea>
            </div>
        </div>

        <?php if ($store_business_type === 'ev_dealership' && !empty($racks)): ?>
        <div class="row" id="evRackSection">
            <div class="col-md-3 mb-3">
                <label class="font-weight-bold">Rack / Parts Shelf Location <a href="<?= base_url('masters/racks') ?>" target="_blank" class="small text-success ml-1">+ Add</a></label>
                <select name="rack_id" class="form-control">
                    <option value="">-- None --</option>
                    <?php foreach($racks as $rk): ?>
                    <option value="<?= $rk->id ?>" <?= $rk->id == ($product->rack_id ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($rk->rack_code . ' — ' . $rk->rack_name) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Storage rack for spare parts or accessories</small>
            </div>
        </div>
        <?php endif; ?>


        <!-- 2. HSN & GST -->
        <h6 class="font-weight-bold text-muted mb-3 mt-2">2. HSN Code &amp; GST Rates</h6>
        <div class="row align-items-end">
            <div class="col-md-5 mb-3">
                <label>HSN Code <span class="text-danger">*</span> <a href="<?= base_url('hsn/add') ?>" target="_blank" class="small text-success ml-1">+ Add new HSN</a></label>
                <select name="hsn_id" id="hsn_id" class="form-control" required onchange="loadHsnRates(this.value)">
                    <?php foreach($hsn_options as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $val == $product->hsn_id ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-7 mb-3">
                <div class="row">
                    <div class="col">
                        <label class="small text-muted">IGST %</label>
                        <input type="number" id="disp_igst" class="form-control form-control-sm bg-light"
                               value="<?= $product->igst_percent ?? $product->gst_percent ?>" readonly>
                    </div>
                    <div class="col">
                        <label class="small text-muted">CGST %</label>
                        <input type="number" id="disp_cgst" class="form-control form-control-sm bg-light"
                               value="<?= $product->cgst_percent ?? round($product->gst_percent/2,2) ?>" readonly>
                    </div>
                    <div class="col">
                        <label class="small text-muted">SGST %</label>
                        <input type="number" id="disp_sgst" class="form-control form-control-sm bg-light"
                               value="<?= $product->sgst_percent ?? round($product->gst_percent/2,2) ?>" readonly>
                    </div>
                    <input type="hidden" name="gst_percent" id="gst_percent_hidden" value="<?= $product->gst_percent ?>">
                </div>
                <small class="text-muted">Same-state → CGST+SGST. Inter-state → IGST.</small>
            </div>
        </div>

        <!-- 3. Pricing & Stock -->
        <h6 class="font-weight-bold text-muted mb-3 mt-2">3. Pricing &amp; Stock</h6>

        <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.85rem;">
            <strong>Cost Logic:</strong>
            Basic Cost (excl. GST) + GST = <strong>Cost Price (CP)</strong> &nbsp;|&nbsp;
            Net Stock Value = Basic Cost × Qty &nbsp;|&nbsp;
            Gross Stock Value = CP × Qty
        </div>

        <div class="row">
            <div class="col-md-2 mb-3">
                <label class="font-weight-bold text-primary">Basic Cost ₹ <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="basic_cost" id="basic_cost" class="form-control border-primary font-weight-bold"
                       value="<?= $product->basic_cost ?? round($product->purchase_price / (1 + $product->gst_percent/100), 2) ?>"
                       required oninput="calcCostPrice()">
                <small class="text-muted">Purchase price excl. GST</small>
            </div>
            <div class="col-md-2 mb-3">
                <label class="text-secondary">Cost Price ₹ (CP) <small class="text-success">auto</small></label>
                <input type="number" step="0.01" name="purchase_price" id="purchase_price" class="form-control bg-light text-success font-weight-bold"
                       value="<?= $product->purchase_price ?>" readonly>
                <small class="text-muted">Basic Cost + GST tax</small>
            </div>
            <div class="col-md-2 mb-3">
                <label>MRP ₹ <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="mrp" class="form-control" value="<?= $product->mrp ?? 0 ?>" required>
            </div>
            <div class="col-md-2 mb-3">
                <label>Sale Rate ₹ (SP) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="selling_price" class="form-control" value="<?= $product->selling_price ?>" required>
            </div>
            <div class="col-md-2 mb-3">
                <label class="text-warning font-weight-bold">WSP ₹ <small class="text-muted">(Whole Carton)</small></label>
                <input type="number" step="0.01" name="wsp" class="form-control border-warning"
                       value="<?= $product->wsp ?? 0 ?>" placeholder="Whole carton price">
            </div>
            <div class="col-md-2 mb-3">
                <label>Unit <span class="text-danger">*</span></label>
                <select name="unit" class="form-control" required>
                    <?php foreach(['PCS','KG','LTR','BOX'] as $u): ?>
                    <option value="<?= $u ?>" <?= $product->unit == $u ? 'selected':'' ?>><?= $u ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2 mb-3">
                <label class="font-weight-bold">QOH — Qty on Hand</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-success text-white font-weight-bold" style="font-size:1rem;">📦</span>
                    </div>
                    <input type="text" class="form-control font-weight-bold text-success bg-light" style="font-size:1.1rem;" value="<?= rtrim(rtrim(number_format(max(0, (float)$product->stock_qty), 3, '.', ''), '0'), '.') ?>" readonly tabindex="-1" title="QOH cannot be edited here">
                </div>
                <small class="text-muted">Display only — change stock via Purchase / GRN, Transfer, Stock Take or <a href="<?= base_url('inventory') ?>">Stock Adjustment</a></small>
            </div>
            <div class="col-md-2 mb-3">
                <label>Low Stock Alert <span class="text-danger">*</span></label>
                <input type="number" name="low_stock_threshold" class="form-control border-warning"
                       value="<?= $product->low_stock_threshold ?? 10 ?>" min="1" required>
            </div>
            <div class="col-md-2 mb-3 align-self-end pb-2">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="no_discount" id="no_discount" class="custom-control-input" value="1" <?= (!empty($product->no_discount) && $product->no_discount == 1) ? 'checked' : '' ?>>
                    <label class="custom-control-label font-weight-bold text-danger" for="no_discount">🚫 No Discount</label>
                </div>
                <?php if ($this->db->field_exists('allow_name_edit', 'products')): ?>
                <div class="custom-control custom-checkbox mt-2">
                    <input type="checkbox" name="allow_name_edit" id="allow_name_edit" class="custom-control-input" value="1" <?= (!empty($product->allow_name_edit) && $product->allow_name_edit == 1) ? 'checked' : '' ?>>
                    <label class="custom-control-label font-weight-bold text-info" for="allow_name_edit">✏️ Edit Name in Billing</label>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php $this->load->view('products/_warranty_serial_fields', ['product' => $product]); ?>

        <?php $this->load->view('products/_loyalty_fields', ['product' => $product]); ?>

        <!-- 4. Size & Colour Variant Matrix (Clothing & Garments Mode) -->
        <div id="garmentVariantSection" class="card shadow-sm border-primary mb-4" style="display: <?= $is_garment ? 'block' : 'none' ?>;">
            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center flex-wrap">
                <span class="font-weight-bold" style="font-size: 1rem;"><i class="fas fa-layer-group mr-2"></i> 4. Size &amp; Colour Variants (Sellable Apparel Inventory)</span>
                <div>
                    <button type="button" class="btn btn-warning btn-sm font-weight-bold mr-1 text-dark" id="btnToggleGenerator">
                        <i class="fas fa-magic mr-1"></i> Variant Matrix Builder
                    </button>
                    <button type="button" class="btn btn-light btn-sm font-weight-bold text-primary" id="btnAddSingleRow">
                        <i class="fas fa-plus mr-1"></i> Add Single Variant
                    </button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold ml-1"><i class="fas fa-save mr-1"></i> Save</button>
                </div>
            </div>
            <div class="card-body">
                <!-- Collapsible Matrix Generator Panel -->
                <div id="generatorPanel" class="p-3 bg-light rounded border border-info mb-3" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-cogs text-primary mr-1"></i> Step 1: Select Sizes &amp; Colours to Auto-Generate Combinations</h6>
                        <button type="button" class="close text-danger" id="btnCloseGen">&times;</button>
                    </div>
                    <div class="row">
                        <!-- Size Selector Column -->
                        <div class="col-lg-6 border-right pr-lg-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="font-weight-bold text-dark mb-0">
                                    <span class="badge badge-primary mr-1">A</span> Choose Sizes
                                </label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2" id="btnSelectAllSizes">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2" id="btnDeselectAllSizes">Clear</button>
                                </div>
                            </div>
                            <!-- Size Set Dropdown -->
                            <div class="input-group input-group-sm mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white font-weight-bold text-primary"><i class="fas fa-filter mr-1"></i> Filter Size Set</span>
                                </div>
                                <select id="sizeSetSelector" class="form-control font-weight-bold">
                                    <option value="">-- All Size Sets --</option>
                                    <?php foreach (($size_sets ?? []) as $ss): ?>
                                        <option value="<?= (int)$ss->id ?>"><?= htmlspecialchars($ss->name) ?> (<?= htmlspecialchars($ss->code) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Sizes Pills Container -->
                            <div class="border rounded p-3 bg-white d-flex flex-wrap" id="sizeCheckboxes" style="min-height: 180px; max-height: 240px; overflow-y: auto; gap: 8px;">
                                <?php foreach (($all_sizes ?? []) as $sz): ?>
                                    <label class="size-pill-label size-item mb-0 d-inline-flex align-items-center px-3 py-2 bg-light rounded border shadow-sm" data-set-id="<?= (int)$sz->size_set_id ?>" for="sz_<?= $sz->id ?>" style="cursor: pointer; user-select: none;">
                                        <input type="checkbox" class="chk-size mr-2" id="sz_<?= $sz->id ?>" value="<?= $sz->id ?>" data-name="<?= htmlspecialchars($sz->name) ?>" style="width: 16px; height: 16px; cursor: pointer;">
                                        <span class="d-flex flex-column text-left">
                                            <strong class="size-name text-dark" style="font-size: 0.95rem; line-height: 1.1;"><?= htmlspecialchars($sz->name) ?></strong>
                                            <?php if (!empty($sz->measurement)): ?>
                                                <small class="text-muted" style="font-size: 0.72rem; line-height: 1.1;"><?= htmlspecialchars($sz->measurement) ?></small>
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
                                    <span class="badge badge-success mr-1">B</span> Choose Colours
                                </label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-success btn-xs py-0 px-2" id="btnSelectAllColours">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2" id="btnDeselectAllColours">Clear</button>
                                </div>
                            </div>
                            <!-- Colours Pills Container -->
                            <div class="border rounded p-3 bg-white d-flex flex-wrap" id="colourCheckboxes" style="min-height: 220px; max-height: 280px; overflow-y: auto; gap: 8px;">
                                <?php foreach (($all_colours ?? []) as $col): ?>
                                    <label class="colour-pill-label mb-0 d-inline-flex align-items-center px-3 py-2 bg-light rounded border shadow-sm" for="col_<?= $col->id ?>" style="cursor: pointer; user-select: none;">
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
                            <button type="button" class="btn btn-success font-weight-bold shadow-sm" id="btnGenerateRows">
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
                                <th style="width: 90px;" class="text-center">Current Stock</th>
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
                    No size/colour variants generated yet. Click <strong class="text-primary">Variant Matrix Builder</strong> or <strong class="text-primary">+ Add Single Variant</strong> above to generate sellable variants with individual barcodes.
                </div>
            </div>
        </div>

        <!-- 4. Barcode & Physical Identification -->
        <div id="fmcgCartonSection">
            <h6 class="font-weight-bold text-muted mb-3 mt-2" id="section4_title"><?= ($store_business_type === 'ev_dealership') ? '4. Product Barcode &amp; Identification' : '5. Carton Config &amp; Expiry' ?></h6>
            <div class="row">
                <div class="col-md-4 mb-3" id="pcs_barcode_wrapper">
                    <label id="pcs_barcode_label" class="font-weight-bold">Product Barcode (EAN / Custom)</label>
                    <input type="text" name="pcs_barcode" id="pcs_barcode_input" class="form-control border-info font-weight-bold"
                           value="<?= htmlspecialchars($pcs_barcode ?? '') ?>" placeholder="Scan or enter barcode...">
                    <small class="text-muted">Item barcode for quick barcode scanner lookup</small>
                </div>
                <div class="col-md-2 mb-3 fmcg-only-field" style="<?= ($store_business_type === 'ev_dealership') ? 'display:none;' : '' ?>">
                    <label>Carton Size (1 CRT = X PCS)</label>
                    <input type="number" name="carton_size" class="form-control text-primary font-weight-bold"
                           value="<?= $product->carton_size ?? 1 ?>" min="1">
                </div>
                <div class="col-md-2 mb-3 fmcg-only-field" style="<?= ($store_business_type === 'ev_dealership') ? 'display:none;' : '' ?>">
                    <label>Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control"
                           value="<?= $product->expiry_date ?? '' ?>">
                </div>
                <div class="col-md-2 mb-3 fmcg-only-field" style="<?= ($store_business_type === 'ev_dealership') ? 'display:none;' : '' ?>">
                    <label>Expiry Days <small class="text-muted">(Shelf Life)</small></label>
                    <input type="number" name="expiry_days" class="form-control text-danger font-weight-bold"
                           value="<?= htmlspecialchars($product->expiry_days ?? 0) ?>" min="0" placeholder="e.g. 3">
                    <small class="text-muted">Days from printing</small>
                </div>
                <div class="col-md-3 mb-3 fmcg-only-field" style="<?= ($store_business_type === 'ev_dealership') ? 'display:none;' : '' ?>">
                    <label>CRT Barcode</label>
                    <input type="text" name="crt_barcode" class="form-control border-warning"
                           value="<?= htmlspecialchars($crt_barcode ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- 5. Product Images -->
        <h6 class="font-weight-bold text-muted mb-3 mt-2">5. Product Images (Maximum 3)</h6>
        <div class="row mb-3">
            <?php for ($i = 1; $i <= 3; $i++): 
                $img_field = 'image' . $i;
                $has_img = !empty($product->$img_field);
            ?>
            <div class="col-md-4 mb-3">
                <label>Image <?= $i ?> <?= $i === 1 ? '(Primary)' : '' ?></label>
                
                <?php if ($has_img): ?>
                <div class="d-flex align-items-center mb-2 p-2 border rounded bg-white shadow-xs" style="min-height: 80px;">
                    <img src="<?= base_url('assets/uploads/products/' . $product->$img_field) ?>" alt="Image <?= $i ?>" style="height: 70px; width: 70px; object-fit: contain; border-radius: 4px; border: 1px solid #ddd; background:#f8f9fa; margin-right: 12px;">
                    <div>
                        <div class="custom-control custom-checkbox small">
                            <input type="checkbox" class="custom-control-input" id="delete_image<?= $i ?>" name="delete_image<?= $i ?>" value="1">
                            <label class="custom-control-label text-danger font-weight-bold" for="delete_image<?= $i ?>">Delete image</label>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="custom-file">
                    <input type="file" name="image<?= $i ?>" class="custom-file-input" id="image<?= $i ?>" accept="image/*">
                    <label class="custom-file-label" for="image<?= $i ?>">Choose file</label>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <hr>
        <div style="position:sticky;bottom:0;z-index:50;background:#fff;border-top:2px solid #212529;padding:10px 0;box-shadow:0 -4px 10px rgba(0,0,0,.12);">
            <button type="submit" class="btn btn-success btn-lg px-5 font-weight-bold"><i class="fas fa-save mr-1"></i> Save Changes</button>
            <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary btn-lg ml-2">Cancel</a>
        </div>
        <?= form_close() ?>
    </div>
</div>

<script>
function calcCostPrice() {
    var basic = parseFloat(document.getElementById('basic_cost').value) || 0;
    var gst   = parseFloat(document.getElementById('gst_percent_hidden').value) || 0;
    var cp    = basic * (1 + gst / 100);
    document.getElementById('purchase_price').value = cp > 0 ? cp.toFixed(2) : '';
}

function loadHsnRates(hsnId) {
    if (!hsnId) return;
    fetch('<?= base_url("hsn/get_rates_ajax/") ?>' + hsnId, {
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('disp_igst').value = d.igst_percent;
        document.getElementById('disp_cgst').value = d.cgst_percent;
        document.getElementById('disp_sgst').value = d.sgst_percent;
        document.getElementById('gst_percent_hidden').value = d.igst_percent;
        calcCostPrice();
    });
}
function toggleProductType(type) {
    var garmentSec     = document.getElementById('garmentFieldsSection');
    var garmentVarSec  = document.getElementById('garmentVariantSection');
    var fmcgIngSec     = document.getElementById('fmcgIngredientsSection');
    var fmcgCartonSec  = document.getElementById('fmcgCartonSection');
    var sec4Title      = document.getElementById('section4_title');
    var pcsBcLabel     = document.getElementById('pcs_barcode_label');
    var pcsBcWrap      = document.getElementById('pcs_barcode_wrapper');
    var fmcgFields     = document.querySelectorAll('.fmcg-only-field');

    if (type === 'ev_dealership') {
        if (garmentSec) garmentSec.style.display = 'none';
        if (garmentVarSec) garmentVarSec.style.display = 'none';
        if (fmcgIngSec) fmcgIngSec.style.display = 'none';
        fmcgFields.forEach(function(el) { el.style.display = 'none'; });
        if (sec4Title) sec4Title.textContent = '4. Product Barcode & Identification';
        if (pcsBcLabel) pcsBcLabel.textContent = 'Product Barcode';
        if (pcsBcWrap) pcsBcWrap.className = 'col-md-4 mb-3';
    } else if (type === 'garment') {
        if (garmentSec) garmentSec.style.display = 'block';
        if (garmentVarSec) garmentVarSec.style.display = 'block';
        if (fmcgIngSec) fmcgIngSec.style.display = 'none';
        fmcgFields.forEach(function(el) { el.style.display = 'none'; });
    } else {
        if (garmentSec) garmentSec.style.display = 'none';
        if (garmentVarSec) garmentVarSec.style.display = 'none';
        if (fmcgIngSec) fmcgIngSec.style.display = 'block';
        fmcgFields.forEach(function(el) { el.style.display = 'block'; });
        if (sec4Title) sec4Title.textContent = '4. Carton Config & Expiry';
        if (pcsBcLabel) pcsBcLabel.textContent = 'PCS Barcode';
        if (pcsBcWrap) pcsBcWrap.className = 'col-md-3 mb-3';
    }
}

window.addEventListener('DOMContentLoaded', function() {
    // Show filename in file input
    document.querySelectorAll('.custom-file-input').forEach(function(el) {
        el.addEventListener('change', function(e) {
            var fileName = e.target.files[0]?.name || 'Choose file';
            var nextSibling = e.target.nextElementSibling;
            if (nextSibling) nextSibling.innerText = fileName;
        });
    });

    // Initialize toggle state
    var storeType = '<?= $store_business_type ?>';
    if (storeType === 'clothing_only') {
        toggleProductType('garment');
    } else if (storeType === 'fmcg_only') {
        toggleProductType('fmcg');
    } else if (storeType === 'ev_dealership') {
        toggleProductType(<?= $is_garment ? "'garment'" : "'ev_dealership'" ?>);
    } else {
        var isGarmentChecked = <?= $is_garment ? 'true' : 'false' ?>;
        toggleProductType(isGarmentChecked ? 'garment' : 'ev_dealership');
    }

    // --- Variant Matrix Builder Event Handlers ---
    var btnToggleGen = document.getElementById('btnToggleGenerator');
    var genPanel     = document.getElementById('generatorPanel');
    var btnCloseGen  = document.getElementById('btnCloseGen');
    var btnCancelBot = document.getElementById('btnCancelGenBottom');
    var btnGenRows   = document.getElementById('btnGenerateRows');
    var btnAddSingle = document.getElementById('btnAddSingleRow');
    var variantRows  = document.getElementById('variantRows');
    var noVarMsg     = document.getElementById('noVariantsMsg');
    var sizeSetSel   = document.getElementById('sizeSetSelector');

    if (btnToggleGen && genPanel) {
        btnToggleGen.addEventListener('click', function() {
            genPanel.style.display = genPanel.style.display === 'none' ? 'block' : 'none';
        });
    }
    if (btnCloseGen && genPanel) {
        btnCloseGen.addEventListener('click', function() { genPanel.style.display = 'none'; });
    }
    if (btnCancelBot && genPanel) {
        btnCancelBot.addEventListener('click', function() { genPanel.style.display = 'none'; });
    }

    // Size Set Filter
    if (sizeSetSel) {
        sizeSetSel.addEventListener('change', function() {
            var setId = this.value;
            document.querySelectorAll('.size-item').forEach(function(el) {
                var itemSetId = el.getAttribute('data-set-id');
                var chk = el.querySelector('.chk-size');
                if (!setId) {
                    el.style.display = 'inline-flex';
                } else if (itemSetId === setId) {
                    el.style.display = 'inline-flex';
                    if (chk) chk.checked = true;
                } else {
                    el.style.display = 'none';
                    if (chk) chk.checked = false;
                }
            });
            updateMatrixSummary();
        });
    }

    // Select/Deselect All buttons
    var btnSelAllSizes = document.getElementById('btnSelectAllSizes');
    var btnClrSizes    = document.getElementById('btnDeselectAllSizes');
    if (btnSelAllSizes) {
        btnSelAllSizes.addEventListener('click', function() {
            document.querySelectorAll('.size-item').forEach(function(el) {
                if (el.style.display !== 'none') {
                    var chk = el.querySelector('.chk-size');
                    if (chk) chk.checked = true;
                }
            });
            updateMatrixSummary();
        });
    }
    if (btnClrSizes) {
        btnClrSizes.addEventListener('click', function() {
            document.querySelectorAll('.chk-size').forEach(function(c) { c.checked = false; });
            updateMatrixSummary();
        });
    }

    var btnSelAllCols = document.getElementById('btnSelectAllColours');
    var btnClrCols    = document.getElementById('btnDeselectAllColours');
    if (btnSelAllCols) {
        btnSelAllCols.addEventListener('click', function() {
            document.querySelectorAll('.chk-colour').forEach(function(c) { c.checked = true; });
            updateMatrixSummary();
        });
    }
    if (btnClrCols) {
        btnClrCols.addEventListener('click', function() {
            document.querySelectorAll('.chk-colour').forEach(function(c) { c.checked = false; });
            updateMatrixSummary();
        });
    }

    document.querySelectorAll('.chk-size, .chk-colour').forEach(function(el) {
        el.addEventListener('change', updateMatrixSummary);
    });

    function updateMatrixSummary() {
        var sCount = document.querySelectorAll('.chk-size:checked').length;
        var cCount = document.querySelectorAll('.chk-colour:checked').length;
        var total = sCount * cCount;
        var txt = document.getElementById('selectionSummaryText');
        if (txt) {
            if (total > 0) {
                txt.innerHTML = '<span class="text-success font-weight-bold"><i class="fas fa-check mr-1"></i> Ready: ' + sCount + ' sizes × ' + cCount + ' colours = ' + total + ' variant combination rows</span>';
            } else {
                txt.innerHTML = '<i class="fas fa-info-circle text-primary mr-1"></i> Pick sizes and colours above to preview combination count.';
            }
        }
    }

    // Generate Combination Rows via AJAX
    if (btnGenRows) {
        btnGenRows.addEventListener('click', function() {
            var sizeIds = [];
            document.querySelectorAll('.chk-size:checked').forEach(function(c) { sizeIds.push(c.value); });
            var colourIds = [];
            document.querySelectorAll('.chk-colour:checked').forEach(function(c) { colourIds.push(c.value); });

            if (sizeIds.length === 0 || colourIds.length === 0) {
                alert('Please select at least one Size and one Colour.');
                return;
            }

            var styleCode = (document.getElementById('style_code_input')?.value || document.querySelector('input[name="product_name"]')?.value || 'GARMENT').trim();
            var baseMrp   = document.getElementById('mrp')?.value || '799.00';
            var baseSp    = document.getElementById('selling_price')?.value || '799.00';
            var baseCost  = document.getElementById('basic_cost')?.value || '350.00';

            var formData = new FormData();
            var csrfName = window._csrfName || 'csrf_test_name';
            var csrfHash = window._csrfHash || document.querySelector('input[name="' + csrfName + '"]')?.value;
            if (csrfName && csrfHash) formData.append(csrfName, csrfHash);
            formData.append('style_code', styleCode);
            formData.append('base_mrp', baseMrp);
            formData.append('base_sp', baseSp);
            formData.append('base_cost', baseCost);
            sizeIds.forEach(function(id) { formData.append('size_ids[]', id); });
            colourIds.forEach(function(id) { formData.append('colour_ids[]', id); });

            btnGenRows.disabled = true;
            btnGenRows.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generating...';

            fetch('<?= base_url("garments/generate_matrix_ajax") ?>', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                btnGenRows.disabled = false;
                btnGenRows.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Generate Combinations';

                if (data.status === 'success' && data.matrix) {
                    if (noVarMsg) noVarMsg.style.display = 'none';
                    data.matrix.forEach(function(row) {
                        var duplicate = false;
                        document.querySelectorAll('.variant-row').forEach(function(vr) {
                            var szInput = vr.querySelector('input[name="var_size[]"]');
                            var colInput = vr.querySelector('input[name="var_colour[]"]');
                            if (szInput && colInput && szInput.value.toUpperCase() === row.size_name.toUpperCase() && colInput.value.toUpperCase() === row.colour_name.toUpperCase()) {
                                duplicate = true;
                            }
                        });
                        if (!duplicate) {
                            appendVariantRow(row);
                        }
                    });
                    if (genPanel) genPanel.style.display = 'none';
                } else {
                    alert(data.message || 'Error generating matrix');
                }
            })
            .catch(function(err) {
                btnGenRows.disabled = false;
                btnGenRows.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Generate Combinations';
                alert('Request failed');
            });
        });
    }

    // Add Single Variant Row
    if (btnAddSingle) {
        btnAddSingle.addEventListener('click', function() {
            var styleCode = (document.getElementById('style_code_input')?.value || document.querySelector('input[name="product_name"]')?.value || 'GARMENT').trim();
            var baseMrp   = document.getElementById('mrp')?.value || '799.00';
            var baseSp    = document.getElementById('selling_price')?.value || '799.00';
            var baseCost  = document.getElementById('basic_cost')?.value || '350.00';

            var cleanCode = styleCode.replace(/[^A-Za-z0-9]/g, '').toUpperCase().substr(0, 8) || 'GARMENT';
            var rndSku    = cleanCode + '-M-' + Math.floor(1000 + Math.random() * 9000);
            var rndBc     = '890' + Math.floor(1000000000 + Math.random() * 9000000000);

            appendVariantRow({
                size_id: '',
                size_name: 'M',
                colour_id: '',
                colour_name: 'Standard',
                colour_hex: '#CCCCCC',
                sku: rndSku,
                barcode: rndBc,
                mrp: baseMrp,
                sp: baseSp,
                cost: baseCost,
                opening_stock: 0,
                min_stock: 2,
                reorder: 5
            });
            if (noVarMsg) noVarMsg.style.display = 'none';
        });
    }

    function appendVariantRow(data) {
        var tr = document.createElement('tr');
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
                <input type="number" name="var_opening_stock[]" class="form-control form-control-sm text-center font-weight-bold" value="${data.opening_stock || 0}">
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

    if (variantRows) {
        variantRows.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remove-row')) {
                var row = e.target.closest('tr');
                row.remove();
                if (variantRows.querySelectorAll('tr').length === 0 && noVarMsg) {
                    noVarMsg.style.display = 'block';
                }
            }
        });
    }
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
