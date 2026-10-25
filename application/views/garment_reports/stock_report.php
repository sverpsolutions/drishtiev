<?php
// ============================================================
// FILE: application/views/garment_reports/stock_report.php
// Garment Stock Valuation & Reorder Report (Size/Colour/Article Filter-wise)
// ============================================================

// Compute summary KPIs
$kpi_total_qty     = 0;
$kpi_total_cost    = 0;
$kpi_total_mrp     = 0;
$kpi_low_count     = 0;
$kpi_zero_count    = 0;
$styles_tracker    = [];

if (!empty($items)) {
    foreach ($items as $it) {
        $qty = (float)$it->stock_qty;
        $kpi_total_qty  += $qty;
        $kpi_total_cost += (float)$it->stock_val_cost;
        $kpi_total_mrp  += (float)$it->stock_val_mrp;
        $styles_tracker[$it->product_id] = true;

        if ($qty <= 0) {
            $kpi_zero_count++;
        } elseif ($qty <= (float)$it->min_stock) {
            $kpi_low_count++;
        }
    }
}
$kpi_unique_styles   = count($styles_tracker);
$kpi_total_variants  = count($items ?? []);

// Base filter params for quick URL building
$base_filter_params = $filters;
?>

<style>
@media print {
    .no-print, .main-header, .sidebar, .card-filter, .btn, .breadcrumb, form {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    body {
        background: #fff !important;
        font-size: 11pt;
    }
    .table td, .table th {
        padding: 4px 6px !important;
    }
}

.kpi-card {
    border-radius: 8px;
    transition: transform 0.15s ease-in-out;
}
.kpi-card:hover {
    transform: translateY(-2px);
}
.size-pill-btn {
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 0.82rem;
    font-weight: 600;
    margin: 3px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    text-decoration: none !important;
}
.size-pill-btn:hover {
    transform: scale(1.04);
}
.color-swatch-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    display: inline-block;
    border: 1px solid rgba(0,0,0,0.2);
    vertical-align: middle;
    margin-right: 5px;
}
</style>

<!-- Top Header & Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="mb-1 font-weight-bold text-dark">
            <i class="fas fa-boxes text-primary mr-2"></i>Garment Stock &amp; Inventory Report
        </h4>
        <p class="text-muted small mb-0">
            Real-time variant stock balances, size-wise pieces, colour/article breakdown, cost &amp; retail valuation
        </p>
    </div>
    <div class="mt-2 mt-md-0">
        <?php $csv_params = http_build_query(array_merge($filters, ['export' => 'csv'])); ?>
        <a href="<?= base_url('garmentreports/stock_report?' . $csv_params) ?>" class="btn btn-success btn-sm mr-1 shadow-sm">
            <i class="fas fa-file-excel mr-1"></i> Export CSV
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm mr-1 shadow-sm">
            <i class="fas fa-print mr-1"></i> Print
        </button>
        <a href="<?= base_url('garmentreports') ?>" class="btn btn-outline-dark btn-sm shadow-sm">
            <i class="fas fa-th mr-1"></i> Reports Hub
        </a>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row mb-3 no-print">
    <div class="col-xl-2 col-md-4 col-6 mb-2">
        <div class="card border-0 shadow-sm kpi-card bg-primary text-white p-3">
            <div class="small font-weight-bold text-uppercase text-white-50">Total Pieces</div>
            <div class="h3 font-weight-bold mb-0 mt-1"><?= number_format($kpi_total_qty, 0) ?> <small style="font-size: 0.6em;">pcs</small></div>
            <small class="text-white-50 mt-1"><?= $kpi_total_variants ?> active variant rows</small>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 mb-2">
        <div class="card border-0 shadow-sm kpi-card bg-info text-white p-3">
            <div class="small font-weight-bold text-uppercase text-white-50">Styles / Articles</div>
            <div class="h3 font-weight-bold mb-0 mt-1"><?= $kpi_unique_styles ?></div>
            <small class="text-white-50 mt-1">Unique style garments</small>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-6 mb-2">
        <div class="card border-0 shadow-sm kpi-card bg-white border-left border-dark p-3" style="border-left-width: 4px !important;">
            <div class="small font-weight-bold text-uppercase text-muted">Cost Valuation</div>
            <div class="h4 font-weight-bold text-dark mb-0 mt-1">₹<?= number_format($kpi_total_cost, 2) ?></div>
            <small class="text-muted mt-1">Total investment at cost</small>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6 mb-2">
        <div class="card border-0 shadow-sm kpi-card bg-white border-left border-success p-3" style="border-left-width: 4px !important;">
            <div class="small font-weight-bold text-uppercase text-muted">Retail Valuation (MRP)</div>
            <div class="h4 font-weight-bold text-success mb-0 mt-1">₹<?= number_format($kpi_total_mrp, 2) ?></div>
            <small class="text-muted mt-1">Potential retail sales value</small>
        </div>
    </div>
    <div class="col-xl-2 col-md-6 col-12 mb-2">
        <div class="card border-0 shadow-sm kpi-card bg-white border-left <?= ($kpi_zero_count > 0 || $kpi_low_count > 0) ? 'border-danger' : 'border-secondary' ?> p-3" style="border-left-width: 4px !important;">
            <div class="small font-weight-bold text-uppercase text-muted">Stock Alerts</div>
            <div class="mt-1">
                <?php if ($kpi_zero_count > 0): ?>
                    <span class="badge badge-danger mr-1"><?= $kpi_zero_count ?> Zero</span>
                <?php endif; ?>
                <?php if ($kpi_low_count > 0): ?>
                    <span class="badge badge-warning text-dark font-weight-bold"><?= $kpi_low_count ?> Low</span>
                <?php endif; ?>
                <?php if ($kpi_zero_count == 0 && $kpi_low_count == 0): ?>
                    <span class="badge badge-success">All Healthy</span>
                <?php endif; ?>
            </div>
            <small class="text-muted mt-1 d-block">Reorder attention items</small>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- QUICK SIZE-WISE STOCK EXPLORER BAR -->
<!-- ============================================================== -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-header bg-white py-2 border-bottom-0 d-flex justify-content-between align-items-center">
        <div>
            <span class="font-weight-bold text-dark" style="font-size: 0.92rem;">
                <i class="fas fa-ruler-horizontal text-primary mr-2"></i>Quick Size-wise Stock Breakdown
            </span>
            <span class="text-muted small ml-2">Click any size to view how many items/pieces are in stock</span>
        </div>
        <?php if (!empty($filters['size'])): ?>
            <?php 
                $clear_size_params = $filters; 
                $clear_size_params['size'] = ''; 
            ?>
            <a href="<?= base_url('garmentreports/stock_report?' . http_build_query($clear_size_params)) ?>" class="badge badge-pill badge-danger py-1 px-2 text-decoration-none">
                <i class="fas fa-times-circle mr-1"></i>Clear Size Filter (Showing: <?= htmlspecialchars($filters['size']) ?>)
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body pt-0 pb-3">
        <div class="d-flex flex-wrap align-items-center">
            <?php
            // Calculate total pieces across all sizes in the breakdown
            $breakdown_total_pcs = 0;
            if (!empty($size_breakdown)) {
                foreach ($size_breakdown as $sb) {
                    $breakdown_total_pcs += (float)$sb->total_stock_qty;
                }
            }

            // All Sizes pill
            $all_sizes_params = $filters;
            $all_sizes_params['size'] = '';
            $is_all_active = empty($filters['size']);
            ?>
            <a href="<?= base_url('garmentreports/stock_report?' . http_build_query($all_sizes_params)) ?>" 
               class="size-pill-btn <?= $is_all_active ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary bg-white text-dark' ?>">
                <i class="fas fa-layer-group mr-1.5"></i> All Sizes 
                <span class="badge badge-pill <?= $is_all_active ? 'badge-light text-dark font-weight-bold' : 'badge-secondary' ?> ml-1.5">
                    <?= number_format($breakdown_total_pcs, 0) ?> pcs
                </span>
            </a>

            <?php if (!empty($size_breakdown)): ?>
                <?php foreach ($size_breakdown as $sb): 
                    $is_current_size = (!empty($filters['size']) && strtolower(trim($filters['size'])) === strtolower(trim($sb->size_name)));
                    $sb_params = $filters;
                    $sb_params['size'] = $sb->size_name;
                    $sb_qty = (float)$sb->total_stock_qty;
                ?>
                    <a href="<?= base_url('garmentreports/stock_report?' . http_build_query($sb_params)) ?>" 
                       class="size-pill-btn <?= $is_current_size ? 'btn-primary text-white shadow font-weight-bold' : 'btn-outline-dark bg-white' ?>"
                       title="<?= htmlspecialchars($sb->size_name) ?>: <?= number_format($sb_qty, 0) ?> pcs across <?= $sb->styles_count ?> styles (Val: ₹<?= number_format((float)$sb->total_val_cost, 0) ?>)">
                        <span class="<?= $is_current_size ? 'text-warning font-weight-bolder mr-1' : 'mr-1' ?>">
                            <?= htmlspecialchars($sb->size_name) ?>:
                        </span>
                        <span class="badge badge-pill <?= $is_current_size ? 'badge-light text-primary font-weight-bold' : ($sb_qty > 0 ? 'badge-success' : 'badge-secondary') ?>">
                            <?= number_format($sb_qty, 0) ?> pcs
                        </span>
                        <?php if ($is_current_size): ?>
                            <i class="fas fa-check-circle ml-1.5 text-warning"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="text-muted small ml-2">No size stock records found for current criteria.</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- ADVANCED MULTI-DIMENSIONAL FILTERS -->
<!-- ============================================================== -->
<div class="card border-0 shadow-sm mb-4 card-filter no-print">
    <div class="card-body py-3">
        <form method="get" action="<?= base_url('garmentreports/stock_report') ?>" id="filterForm">
            <!-- Row 1: Search, Size, Colour, Stock Status -->
            <div class="form-row">
                <div class="col-lg-4 col-md-6 mb-2">
                    <label class="small text-muted font-weight-bold mb-1">
                        <i class="fas fa-search mr-1 text-primary"></i>Keyword / Article / Style / Barcode
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search style code, article no, barcode, SKU, name..." 
                               value="<?= htmlspecialchars($filters['search']) ?>">
                        <?php if (!empty($filters['search'])): ?>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="this.form.search.value=''; this.form.submit();">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-2 col-md-3 col-6 mb-2">
                    <label class="small text-muted font-weight-bold mb-1">
                        <i class="fas fa-ruler mr-1 text-secondary"></i>Size
                    </label>
                    <select name="size" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">All Sizes</option>
                        <?php if (!empty($sizes)): ?>
                            <?php foreach ($sizes as $sz): 
                                $sz_name = is_object($sz) ? $sz->name : $sz;
                            ?>
                                <option value="<?= htmlspecialchars($sz_name) ?>" <?= ($filters['size'] === $sz_name) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sz_name) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-6 mb-2">
                    <label class="small text-muted font-weight-bold mb-1">
                        <i class="fas fa-palette mr-1 text-secondary"></i>Colour
                    </label>
                    <select name="colour" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">All Colours</option>
                        <?php if (!empty($colours)): ?>
                            <?php foreach ($colours as $clr): 
                                $clr_name = is_object($clr) ? $clr->name : $clr;
                            ?>
                                <option value="<?= htmlspecialchars($clr_name) ?>" <?= ($filters['colour'] === $clr_name) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($clr_name) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-lg-4 col-md-6 mb-2">
                    <label class="small text-muted font-weight-bold mb-1">
                        <i class="fas fa-signal mr-1 text-secondary"></i>Stock Status
                    </label>
                    <select name="stock_filter" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="" <?= ($filters['stock_filter'] === '') ? 'selected' : '' ?>>All Variants (Show Everything)</option>
                        <option value="available" <?= ($filters['stock_filter'] === 'available') ? 'selected' : '' ?>>In Stock Only (&gt; 0 pcs)</option>
                        <option value="low" <?= ($filters['stock_filter'] === 'low') ? 'selected' : '' ?>>Low Stock Only (&le; Min Reorder)</option>
                        <option value="zero" <?= ($filters['stock_filter'] === 'zero') ? 'selected' : '' ?>>Out of Stock / Zero Stock Only</option>
                    </select>
                </div>
            </div>

            <!-- Row 2: Category, Gender, Brand, Location + Submit/Reset -->
            <div class="form-row align-items-end pt-1">
                <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                    <label class="small text-muted font-weight-bold mb-1">Garment Category</label>
                    <select name="category" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">All Garment Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($filters['category'] === $cat) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-6 mb-2 mb-lg-0">
                    <label class="small text-muted font-weight-bold mb-1">Gender / Target</label>
                    <select name="gender" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">All Genders</option>
                        <?php foreach ($genders as $gnd): ?>
                            <option value="<?= htmlspecialchars($gnd) ?>" <?= ($filters['gender'] === $gnd) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($gnd) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3 col-6 mb-2 mb-lg-0">
                    <label class="small text-muted font-weight-bold mb-1">Brand</label>
                    <select name="brand" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $b): ?>
                            <option value="<?= htmlspecialchars($b->name) ?>" <?= ($filters['brand'] === $b->name) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                    <label class="small text-muted font-weight-bold mb-1">Outlet / Location</label>
                    <select name="location_id" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">All Outlets / Consolidated</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= (int)$loc->id ?>" <?= ($filters['location_id'] == $loc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6 mb-2 mb-lg-0 d-flex">
                    <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold mr-1">
                        <i class="fas fa-filter mr-1"></i> Apply
                    </button>
                    <a href="<?= base_url('garmentreports/stock_report') ?>" class="btn btn-outline-secondary btn-sm" title="Reset all filters">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- DETAILED STOCK VALUATION TABLE -->
<!-- ============================================================== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-2.5 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <span class="font-weight-bold text-dark" style="font-size: 0.95rem;">
                <i class="fas fa-list text-secondary mr-2"></i>Garment Stock Balances &amp; Valuation
            </span>
            <span class="badge badge-light border ml-2 text-dark font-weight-normal">
                Showing <?= count($items ?? []) ?> variant rows
                <?php if (!empty($filters['size'])): ?>
                    &bull; <strong class="text-primary">Size: <?= htmlspecialchars($filters['size']) ?></strong>
                <?php endif; ?>
            </span>
        </div>
        <small class="text-muted d-none d-md-inline">
            <span class="badge badge-success mr-1">&nbsp;</span> In Stock
            <span class="badge badge-warning text-dark mr-1">&nbsp;</span> Low Stock
            <span class="badge badge-danger mr-1">&nbsp;</span> Out of Stock
        </small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle" style="font-size: 0.85rem;">
                <thead class="thead-dark text-nowrap">
                    <tr>
                        <th style="width: 35px;" class="text-center">#</th>
                        <th>Product / Style Name</th>
                        <th>Article / Style Code</th>
                        <th>Category</th>
                        <th>Gender</th>
                        <th>Colour</th>
                        <th class="text-center">Size</th>
                        <th>Barcode / SKU</th>
                        <th class="text-center">Stock Qty</th>
                        <th class="text-center">Min</th>
                        <th class="text-right">Cost Price</th>
                        <th class="text-right">Valuation (Cost)</th>
                        <th class="text-right">MRP</th>
                        <th class="text-right">Valuation (MRP)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="14" class="text-center py-5 text-muted">
                                <div class="my-4">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-50"></i>
                                    <h6>No garments match your active filters</h6>
                                    <p class="small text-muted mb-3">Try clearing some filters or searching with a broader keyword.</p>
                                    <a href="<?= base_url('garmentreports/stock_report') ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-redo mr-1"></i> Reset All Filters
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $total_qty  = 0;
                        $total_cost = 0;
                        $total_mrp  = 0;
                        foreach ($items as $i => $item): 
                            $qty = (float)$item->stock_qty;
                            $vc  = (float)$item->stock_val_cost;
                            $vm  = (float)$item->stock_val_mrp;
                            $total_qty  += $qty;
                            $total_cost += $vc;
                            $total_mrp  += $vm;
                            $is_low = ($qty <= (float)$item->min_stock && $qty > 0);
                            $is_out = ($qty <= 0);
                        ?>
                            <tr class="<?= $is_out ? 'table-danger-light' : ($is_low ? 'table-warning-light' : '') ?>">
                                <td class="text-center text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <div class="font-weight-bold text-dark">
                                        <a href="<?= base_url('garments/view/' . $item->product_id) ?>" class="text-dark font-weight-bold">
                                            <?= htmlspecialchars($item->product_name) ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($item->brand)): ?>
                                        <small class="badge badge-light border text-muted mr-1"><?= htmlspecialchars($item->brand) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($item->fabric_type)): ?>
                                        <small class="text-muted mr-1"><i class="fas fa-layer-group text-secondary"></i> <?= htmlspecialchars($item->fabric_type) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($item->style_code)): ?>
                                        <span class="font-weight-bold text-dark"><?= htmlspecialchars($item->style_code) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item->mfr_article_no)): ?>
                                        <div><small class="text-muted">Art: <?= htmlspecialchars($item->mfr_article_no) ?></small></div>
                                    <?php elseif (!empty($item->design_no)): ?>
                                        <div><small class="text-muted">Des: <?= htmlspecialchars($item->design_no) ?></small></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-light border text-secondary font-weight-normal">
                                        <?= htmlspecialchars($item->garment_category ?: ($item->category ?: '-')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($item->gender)): ?>
                                        <span class="badge badge-light border text-dark"><?= htmlspecialchars($item->gender) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($item->colour_hex)): ?>
                                            <span class="color-swatch-dot" style="background-color: <?= htmlspecialchars($item->colour_hex) ?>;"></span>
                                        <?php endif; ?>
                                        <span class="font-weight-bold text-dark"><?= htmlspecialchars($item->colour_name) ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-primary px-2 py-1 font-weight-bold" style="font-size: 0.85rem;">
                                        <?= htmlspecialchars($item->size_name) ?>
                                    </span>
                                </td>
                                <td>
                                    <div><code><?= htmlspecialchars($item->barcode) ?></code></div>
                                    <small class="text-muted"><?= htmlspecialchars($item->variant_sku) ?></small>
                                </td>
                                <td class="text-center font-weight-bold">
                                    <?php if ($is_out): ?>
                                        <span class="badge badge-danger px-2 py-1">0 pcs</span>
                                    <?php elseif ($is_low): ?>
                                        <span class="badge badge-warning text-dark font-weight-bold px-2 py-1">
                                            <?= number_format($qty, 0) ?> pcs (Low)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success px-2 py-1 font-weight-bold">
                                            <?= number_format($qty, 0) ?> pcs
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-muted"><?= (int)$item->min_stock ?></td>
                                <td class="text-right text-muted">₹<?= number_format((float)$item->cost_price, 2) ?></td>
                                <td class="text-right font-weight-bold text-dark">₹<?= number_format($vc, 2) ?></td>
                                <td class="text-right text-muted">₹<?= number_format((float)$item->mrp, 2) ?></td>
                                <td class="text-right font-weight-bold text-success">₹<?= number_format($vm, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Table Summary Footer -->
                        <tr class="table-dark font-weight-bold" style="font-size: 0.92rem;">
                            <td colspan="8" class="text-right text-uppercase">
                                Filtered Total Summary (<?= count($items) ?> variants):
                            </td>
                            <td class="text-center text-warning" style="font-size: 1.05rem;">
                                <?= number_format($total_qty, 0) ?> pcs
                            </td>
                            <td></td>
                            <td></td>
                            <td class="text-right text-info" style="font-size: 1.05rem;">
                                ₹<?= number_format($total_cost, 2) ?>
                            </td>
                            <td></td>
                            <td class="text-right text-success" style="font-size: 1.05rem;">
                                ₹<?= number_format($total_mrp, 2) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
