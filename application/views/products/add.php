<?php 
// FILE: application/views/products/add.php 
$store_business_type = $company['retail_business_type'] ?? 'ev_dealership';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 font-weight-bold">Add Product</h5>
    <div>
        <a href="<?= base_url('products/import') ?>" class="btn btn-outline-success btn-sm mr-2">&#8679; Import Excel/CSV</a>
        <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-2"></i><?= $this->session->flashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $this->session->flashdata('error') ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>
<?php if (validation_errors()): ?>
    <div class="alert alert-danger"><?= validation_errors() ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?= form_open_multipart('products/add') ?>

        <!-- 1. Basic Info & Product Classification -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="font-weight-bold text-muted mb-0">1. Basic Info</h6>
        </div>

        <?php $this->load->view('products/_ev_fields', ['product' => null]); ?>

        <!-- 1. Product Name, Item Code, Brand, Category (ON TOP) -->
        <div class="row">
            <div class="col-md-5 mb-3">
                <label class="font-weight-bold text-dark" style="font-size: 1rem;">Product Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="product_name_input" class="form-control form-control-lg font-weight-bold border-primary shadow-sm" required placeholder="e.g. Drishti Blaze EV Scooter (60V 30Ah) / Li-ion Battery / Charger">
            </div>
            <div class="col-md-2 mb-3">
                <label class="font-weight-bold text-dark">Item Code <small class="text-muted">(auto)</small></label>
                <div class="input-group">
                    <input type="text" name="item_code" id="item_code" class="form-control font-weight-bold bg-light" placeholder="Loading..." data-no-money="1" maxlength="6">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary" title="Generate new code" onclick="fetchNextCode()"><i class="fas fa-sync-alt"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <label class="font-weight-bold d-flex justify-content-between align-items-center">
                    <span>Brand</span>
                    <a href="<?= base_url('masters/brands') ?>" target="_blank" class="small text-success">+ Add Brand</a>
                </label>
                <select name="brand_id" id="brand_id" class="form-control">
                    <option value="">-- None --</option>
                    <?php foreach($brands as $b): ?>
                    <option value="<?= $b->id ?>"><?= htmlspecialchars($b->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label class="font-weight-bold d-flex justify-content-between align-items-center">
                    <span>Category</span>
                    <a href="<?= base_url('masters/categories') ?>" target="_blank" class="small text-success">+ Add Category</a>
                </label>
                <select name="category_id" id="category_id" class="form-control">
                    <option value="">-- None --</option>
                    <?php foreach($categories as $c): ?>
                    <option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Garment Specific Attributes (Dynamically shown for garments) -->
        <div id="garmentFieldsSection" class="card border-info shadow-sm mb-4" style="display: none; background: #f0f7ff; border-width: 2px;">
            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                <span class="font-weight-bold"><i class="fas fa-tshirt mr-2"></i> Clothing &amp; Garment Specifications</span>
                <span class="badge badge-light text-primary font-weight-bold">Apparel Retail Mode</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold d-flex justify-content-between align-items-center">
                            <span>Garment Category <span class="text-danger">*</span></span>
                            <a href="javascript:void(0)" onclick="quickAddAttr('category', 'Garment Category', 'garment_category')" class="badge badge-primary text-white" title="Add New Category" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                        </label>
                        <select name="garment_category" class="form-control">
                            <option value="">-- Select Category --</option>
                            <?php foreach (($garment_categories ?? []) as $gcat): ?>
                                <option value="<?= htmlspecialchars($gcat) ?>"><?= htmlspecialchars($gcat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Gender / Demographic</label>
                        <select name="gender" class="form-control">
                            <option value="Women">Women</option>
                            <option value="Men">Men</option>
                            <option value="Boys">Boys</option>
                            <option value="Girls">Girls</option>
                            <option value="Unisex">Unisex</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Style Code / Style No</label>
                        <input type="text" name="style_code" id="style_code_input" class="form-control" placeholder="e.g. LEFB-4191">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Design Number</label>
                        <input type="text" name="design_no" id="design_no_input" class="form-control" placeholder="e.g. D-15852">
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
                                <option value="<?= htmlspecialchars($fab) ?>"><?= htmlspecialchars($fab) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Pattern / Print</label>
                        <input type="text" name="pattern_print" class="form-control" placeholder="e.g. Printed, Plain, Striped">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold d-flex justify-content-between align-items-center">
                            <span>Fit Type</span>
                            <a href="javascript:void(0)" onclick="quickAddAttr('fit', 'Fit Type', 'fit_type')" class="badge badge-primary text-white" title="Add New Fit" style="text-decoration:none;"><i class="fas fa-plus fa-xs"></i> New</a>
                        </label>
                        <select name="fit_type" class="form-control">
                            <option value="">-- Select Fit --</option>
                            <?php foreach (($fit_types ?? []) as $ft): ?>
                                <option value="<?= htmlspecialchars($ft) ?>"><?= htmlspecialchars($ft) ?></option>
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
                                <option value="<?= htmlspecialchars($sl) ?>"><?= htmlspecialchars($sl) ?></option>
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
                                <option value="<?= htmlspecialchars($nk) ?>"><?= htmlspecialchars($nk) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Garment Length / Inseam</label>
                        <input type="text" name="garment_length" class="form-control" placeholder="e.g. 76 cm, Ankle Length">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Mfr Article No</label>
                        <input type="text" name="mfr_article_no" id="mfr_article_no_input" class="form-control" placeholder="e.g. 15852-A-38-PLZ">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Country of Origin</label>
                        <input type="text" name="country_of_origin" class="form-control" value="India">
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
                    <option value="<?= $rk->id ?>"><?= htmlspecialchars($rk->rack_code . ' — ' . $rk->rack_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-9 mb-3">
            <?php else: ?>
            <div class="col-md-12 mb-3">
            <?php endif; ?>
                <label>Ingredients <small class="text-muted">(will be printed on barcodes)</small></label>
                <textarea name="ingredients" class="form-control" rows="2" placeholder="e.g. Wheat flour, sugar, butter, yeast, milk..."></textarea>
            </div>
        </div>

        <?php if ($store_business_type === 'ev_dealership' && !empty($racks)): ?>
        <div class="row" id="evRackSection">
            <div class="col-md-3 mb-3">
                <label class="font-weight-bold">Rack / Parts Shelf Location <a href="<?= base_url('masters/racks') ?>" target="_blank" class="small text-success ml-1">+ Add</a></label>
                <select name="rack_id" class="form-control">
                    <option value="">-- None --</option>
                    <?php foreach($racks as $rk): ?>
                    <option value="<?= $rk->id ?>"><?= htmlspecialchars($rk->rack_code . ' — ' . $rk->rack_name) ?></option>
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
                    <option value="<?= $val ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-7 mb-3">
                <!-- GST rate display (read-only, driven by HSN) -->
                <div class="row">
                    <div class="col mb-0">
                        <label class="small text-muted">IGST %</label>
                        <input type="number" id="disp_igst" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    <div class="col mb-0">
                        <label class="small text-muted">CGST %</label>
                        <input type="number" id="disp_cgst" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    <div class="col mb-0">
                        <label class="small text-muted">SGST %</label>
                        <input type="number" id="disp_sgst" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    <!-- Hidden field keeps gst_percent in sync for fallback -->
                    <input type="hidden" name="gst_percent" id="gst_percent_hidden" value="0">
                </div>
                <small class="text-muted">Rates auto-filled from HSN Master. Same-state → CGST+SGST. Inter-state → IGST.</small>
            </div>
        </div>

        <!-- 3. Pricing & Stock -->
        <h6 class="font-weight-bold text-muted mb-3 mt-2">3. Pricing &amp; Stock</h6>

        <!-- Live Price Calculator Banner -->
        <div class="card border-primary mb-3" style="background:#f0f7ff;">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                    <small class="text-primary font-weight-bold mr-2"><i class="fas fa-calculator mr-1"></i> Auto-Price Preview:</small>
                    <span class="badge badge-light border px-2 py-1">Basic Cost: <strong id="prev_basic">—</strong></span>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <span class="badge badge-success px-2 py-1">CP: <strong id="prev_cp">—</strong></span>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <span class="badge badge-info px-2 py-1">MRP: <strong id="prev_mrp">—</strong></span>
                    <span class="badge badge-primary px-2 py-1">SP: <strong id="prev_sp">—</strong></span>
                    <span class="badge badge-warning text-dark px-2 py-1">WSP: <strong id="prev_wsp">—</strong></span>
                    <small class="text-muted ml-2">Fields auto-filled when empty. Edit freely after.</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-2 mb-3">
                <label class="font-weight-bold text-primary">Basic Cost ₹ <span class="text-danger">*</span></label>
                <input type="number" step="any" name="basic_cost" id="basic_cost" class="form-control border-primary font-weight-bold"
                       required placeholder="0.00" oninput="onBasicCostInput()">
                <small class="text-muted">Purchase price excl. GST</small>
            </div>
            <div class="col-md-2 mb-3">
                <label class="text-secondary">Cost Price ₹ (CP) <small class="text-success">auto</small></label>
                <input type="number" step="any" name="purchase_price" id="purchase_price" class="form-control bg-light text-success font-weight-bold"
                       placeholder="Basic + GST" readonly tabindex="-1">
                <small class="text-muted">Basic Cost + GST tax</small>
            </div>
            <div class="col-md-2 mb-3">
                <label>MRP ₹ <span class="text-danger">*</span></label>
                <input type="number" step="any" name="mrp" id="mrp" class="form-control" required placeholder="0.00"
                       oninput="updatePreview()">
                <small class="text-muted">Max retail price</small>
            </div>
            <div class="col-md-2 mb-3">
                <label>Sale Rate ₹ (SP) <span class="text-danger">*</span></label>
                <input type="number" step="any" name="selling_price" id="selling_price" class="form-control" required placeholder="0.00"
                       oninput="updatePreview()">
                <small class="text-muted">Selling price per piece</small>
            </div>
            <div class="col-md-2 mb-3">
                <label class="text-warning font-weight-bold">WSP ₹ <small class="text-muted">(Wholesale)</small></label>
                <input type="number" step="any" name="wsp" id="wsp" class="form-control border-warning" placeholder="0.00"
                       oninput="updatePreview()">
                <small class="text-muted">Wholesale/carton price</small>
            </div>
            <div class="col-md-2 mb-3">
                <label>Base Unit <span class="text-danger">*</span></label>
                <select name="unit" class="form-control" required>
                    <option value="PCS">Pieces (PCS)</option>
                    <option value="KG">Kilograms (KG)</option>
                    <option value="LTR">Litres (LTR)</option>
                    <option value="BOX">Box (BOX)</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2 mb-3">
                <label class="font-weight-bold">QOH — Opening Stock</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-success text-white font-weight-bold" style="font-size:1rem;">📦</span>
                    </div>
                    <input type="text" class="form-control font-weight-bold text-success bg-light" style="font-size:1.1rem;" value="0" readonly tabindex="-1" title="QOH cannot be typed here">
                </div>
                <small class="text-muted">Starts at 0 — add stock via Purchase / GRN, Transfer In or Stock Take</small>
            </div>
            <div class="col-md-2 mb-3">
                <label>Low Stock Alert <span class="text-danger">*</span></label>
                <input type="number" name="low_stock_threshold" class="form-control border-warning" value="10" min="1" required>
                <small class="text-muted">Alert when stock ≤ this</small>
            </div>
            <div class="col-md-2 mb-3 align-self-end pb-2">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="no_discount" id="no_discount" class="custom-control-input" value="1">
                    <label class="custom-control-label font-weight-bold text-danger" for="no_discount">🚫 No Discount</label>
                </div>
                <?php if ($this->db->field_exists('allow_name_edit', 'products')): ?>
                <div class="custom-control custom-checkbox mt-2">
                    <input type="checkbox" name="allow_name_edit" id="allow_name_edit" class="custom-control-input" value="1">
                    <label class="custom-control-label font-weight-bold text-info" for="allow_name_edit">✏️ Edit Name in Billing</label>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php $this->load->view('products/_warranty_serial_fields', ['product' => null]); ?>

        <?php $this->load->view('products/_loyalty_fields', ['product' => null]); ?>

        <!-- 4. Size & Colour Variant Matrix (Clothing & Garments Mode) -->
        <div id="garmentVariantSection" class="card shadow-sm border-primary mb-4" style="display: none;">
            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center flex-wrap">
                <span class="font-weight-bold" style="font-size: 1rem;"><i class="fas fa-layer-group mr-2"></i> 4. Size &amp; Colour Variants (Sellable Apparel Inventory)</span>
                <div>
                    <button type="button" class="btn btn-warning btn-sm font-weight-bold mr-1 text-dark" id="btnToggleGenerator">
                        <i class="fas fa-magic mr-1"></i> Variant Matrix Builder
                    </button>
                    <button type="button" class="btn btn-light btn-sm font-weight-bold text-primary" id="btnAddSingleRow">
                        <i class="fas fa-plus mr-1"></i> Add Single Variant
                    </button>
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
                                            <input type="number" name="var_opening_stock[]" class="form-control form-control-sm text-center" value="<?= htmlspecialchars($v->stock_qty ?? 0) ?>">
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
                    <input type="text" name="pcs_barcode" id="pcs_barcode_input" class="form-control border-info font-weight-bold" placeholder="Scan or type barcode...">
                    <small class="text-muted">Item barcode for quick barcode scanner lookup</small>
                </div>
                <div class="col-md-2 mb-3 fmcg-only-field" style="<?= ($store_business_type === 'ev_dealership') ? 'display:none;' : '' ?>">
                    <label>Carton Size (1 CRT = X PCS)</label>
                    <input type="number" name="carton_size" class="form-control text-primary font-weight-bold" value="1" min="1" placeholder="e.g. 24">
                    <small class="text-muted">Set 1 if no cartons</small>
                </div>
                <div class="col-md-2 mb-3 fmcg-only-field" style="<?= ($store_business_type === 'ev_dealership') ? 'display:none;' : '' ?>">
                    <label>Expiry Date <small class="text-muted">(optional)</small></label>
                    <input type="date" name="expiry_date" class="form-control">
                </div>
                <div class="col-md-2 mb-3 fmcg-only-field" style="<?= ($store_business_type === 'ev_dealership') ? 'display:none;' : '' ?>">
                    <label>Expiry Days <small class="text-muted">(Shelf Life)</small></label>
                    <input type="number" name="expiry_days" class="form-control text-danger font-weight-bold" value="0" min="0" placeholder="e.g. 3">
                    <small class="text-muted">Days from printing</small>
                </div>
                <div class="col-md-3 mb-3 fmcg-only-field" style="<?= ($store_business_type === 'ev_dealership') ? 'display:none;' : '' ?>">
                    <label>CRT Barcode</label>
                    <input type="text" name="crt_barcode" class="form-control border-warning" placeholder="Scan carton barcode...">
                </div>
            </div>
        </div>

        <!-- 5. Product Images -->
        <h6 class="font-weight-bold text-muted mb-3 mt-2">5. Product Images (Maximum 3)</h6>
        <div class="row mb-3">
            <div class="col-md-4 mb-3">
                <label>Primary Image (Image 1)</label>
                <div class="custom-file">
                    <input type="file" name="image1" class="custom-file-input" id="image1" accept="image/*">
                    <label class="custom-file-label" for="image1">Choose file</label>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label>Additional Image 2</label>
                <div class="custom-file">
                    <input type="file" name="image2" class="custom-file-input" id="image2" accept="image/*">
                    <label class="custom-file-label" for="image2">Choose file</label>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label>Additional Image 3</label>
                <div class="custom-file">
                    <input type="file" name="image3" class="custom-file-input" id="image3" accept="image/*">
                    <label class="custom-file-label" for="image3">Choose file</label>
                </div>
            </div>
        </div>

        <hr>
        <button type="submit" class="btn btn-dark btn-lg px-5">Save Product</button>
        <?= form_close() ?>
    </div>
</div>

<script>
function r2(v) { return Math.round(v * 100) / 100; }
function fmt(v) { return v > 0 ? v.toFixed(2) : '—'; }

function getBasic() { return parseFloat(document.getElementById('basic_cost').value) || 0; }
function getGst()   { return parseFloat(document.getElementById('gst_percent_hidden').value) || 0; }
function getCP()    { return r2(getBasic() * (1 + getGst() / 100)); }

function onBasicCostInput() {
    var basic = getBasic();
    var cp    = getCP();

    // Always update CP (read-only auto field)
    document.getElementById('purchase_price').value = cp > 0 ? r2(cp) : '';

    // Auto-fill MRP, SP, WSP only if they are currently empty
    var mrpEl = document.getElementById('mrp');
    var spEl  = document.getElementById('selling_price');
    var wspEl = document.getElementById('wsp');

    if (!mrpEl.value && cp > 0) mrpEl.value = r2(cp);
    if (!spEl.value  && cp > 0) spEl.value  = r2(cp);
    if (!wspEl.value && cp > 0) wspEl.value  = r2(cp);

    updatePreview();
}

function updatePreview() {
    var basic = getBasic();
    var cp    = getCP();
    var mrp   = parseFloat(document.getElementById('mrp').value) || 0;
    var sp    = parseFloat(document.getElementById('selling_price').value) || 0;
    var wsp   = parseFloat(document.getElementById('wsp').value) || 0;

    document.getElementById('prev_basic').textContent = basic > 0 ? '₹' + r2(basic).toFixed(2) : '—';
    document.getElementById('prev_cp').textContent    = cp    > 0 ? '₹' + r2(cp).toFixed(2)    : '—';
    document.getElementById('prev_mrp').textContent   = mrp   > 0 ? '₹' + r2(mrp).toFixed(2)   : '—';
    document.getElementById('prev_sp').textContent    = sp    > 0 ? '₹' + r2(sp).toFixed(2)     : '—';
    document.getElementById('prev_wsp').textContent   = wsp   > 0 ? '₹' + r2(wsp).toFixed(2)    : '—';
}

function fetchNextCode() {
    var inp = document.getElementById('item_code');
    inp.placeholder = 'Generating…';
    fetch('<?= base_url('products/next_item_code_ajax') ?>')
        .then(function(r){ return r.json(); })
        .then(function(d){ inp.value = d.code; inp.placeholder = ''; })
        .catch(function(){ inp.placeholder = 'Enter manually'; });
}

function loadHsnRates(hsnId) {
    if (!hsnId) {
        document.getElementById('disp_igst').value = '';
        document.getElementById('disp_cgst').value = '';
        document.getElementById('disp_sgst').value = '';
        document.getElementById('gst_percent_hidden').value = 0;
        onBasicCostInput();
        return;
    }
    fetch('<?= base_url("hsn/get_rates_ajax/") ?>' + hsnId, {
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('disp_igst').value = d.igst_percent;
        document.getElementById('disp_cgst').value = d.cgst_percent;
        document.getElementById('disp_sgst').value = d.sgst_percent;
        document.getElementById('gst_percent_hidden').value = d.igst_percent;
        onBasicCostInput();
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
    var nameInput      = document.getElementById('product_name_input');
    var unitSelect     = document.querySelector('select[name="unit"]');

    if (type === 'ev_dealership') {
        if (garmentSec) garmentSec.style.display = 'none';
        if (garmentVarSec) garmentVarSec.style.display = 'none';
        if (fmcgIngSec) fmcgIngSec.style.display = 'none';
        fmcgFields.forEach(function(el) { el.style.display = 'none'; });
        if (sec4Title) sec4Title.textContent = '4. Product Barcode & Identification';
        if (pcsBcLabel) pcsBcLabel.textContent = 'Product Barcode';
        if (pcsBcWrap) pcsBcWrap.className = 'col-md-4 mb-3';
        if (nameInput && (!nameInput.value || nameInput.value === '')) {
            nameInput.placeholder = 'e.g. Drishti Blaze EV Scooter (60V 30Ah) / Li-ion Battery Pack / Fast Charger';
        }
        if (unitSelect && !unitSelect.value) unitSelect.value = 'PCS';
    } else if (type === 'garment') {
        if (garmentSec) garmentSec.style.display = 'block';
        if (garmentVarSec) garmentVarSec.style.display = 'block';
        if (fmcgIngSec) fmcgIngSec.style.display = 'none';
        fmcgFields.forEach(function(el) { el.style.display = 'none'; });
        if (nameInput && (!nameInput.value || nameInput.value === '')) {
            nameInput.placeholder = 'e.g. Printed Kali Palazzo / Cotton Kurti / Formal Shirt';
        }
        if (unitSelect) unitSelect.value = 'PCS';
    } else {
        if (garmentSec) garmentSec.style.display = 'none';
        if (garmentVarSec) garmentVarSec.style.display = 'none';
        if (fmcgIngSec) fmcgIngSec.style.display = 'block';
        fmcgFields.forEach(function(el) { el.style.display = 'block'; });
        if (sec4Title) sec4Title.textContent = '4. Carton Config & Expiry';
        if (pcsBcLabel) pcsBcLabel.textContent = 'PCS Barcode';
        if (pcsBcWrap) pcsBcWrap.className = 'col-md-3 mb-3';
        if (nameInput && (!nameInput.value || nameInput.value === '')) {
            nameInput.placeholder = 'e.g. Packaged FMCG Item';
        }
    }
}

window.addEventListener('DOMContentLoaded', function() {
    fetchNextCode();

    var sel = document.getElementById('hsn_id');
    if (sel && sel.value) loadHsnRates(sel.value);

    document.querySelectorAll('.custom-file-input').forEach(function(el) {
        el.addEventListener('change', function(e) {
            var fileName = e.target.files[0]?.name || 'Choose file';
            var nextSibling = e.target.nextElementSibling;
            if (nextSibling) nextSibling.innerText = fileName;
        });
    });

    // Initialize toggle state according to store business type
    var storeType = '<?= $store_business_type ?>';
    if (storeType === 'clothing_only') {
        toggleProductType('garment');
    } else if (storeType === 'fmcg_only') {
        toggleProductType('fmcg');
    } else if (storeType === 'ev_dealership') {
        toggleProductType('ev_dealership');
    } else {
        toggleProductType('ev_dealership');
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

            var styleCode = (document.getElementById('style_code_input')?.value || document.getElementById('product_name_input')?.value || 'GARMENT').trim();
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
            var styleCode = (document.getElementById('style_code_input')?.value || document.getElementById('product_name_input')?.value || 'GARMENT').trim();
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
