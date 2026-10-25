<?php // FILE: application/views/reports/fast_slow_report.php ?>

<?php
$fast_rev    = array_sum(array_column((array)$fast, 'total_revenue'));
$fast_qty    = array_sum(array_column((array)$fast, 'total_sold'));
$slow_val    = array_sum(array_column((array)$slow, 'stock_value'));
$slow_qty    = array_sum(array_column((array)$slow, 'total_sold'));
$dead_val    = array_sum(array_column((array)$no_movement, 'stock_value'));
$dead_stock  = array_sum(array_column((array)$no_movement, 'current_stock'));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0">
            <i class="fas fa-tachometer-alt text-primary mr-1"></i> Fast &amp; Slow Movement Report
        </h5>
        <small class="text-muted"><?= $from ?> to <?= $to ?></small>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/fast_slow_report','export_params'=>$export_params??'','export_title'=>$export_title??'Fast & Slow Movement']); ?>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end g-2">
            <div class="col-auto">
                <label class="small font-weight-bold">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
            </div>
            <div class="col-auto">
                <label class="small font-weight-bold">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
            </div>
            <div class="col-auto">
                <label class="small font-weight-bold">Category</label>
                <select name="category_id" class="form-control form-control-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat->id ?>" <?= $category_id == $cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="small font-weight-bold">Brand</label>
                <select name="brand_id" class="form-control form-control-sm">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $br): ?>
                    <option value="<?= $br->id ?>" <?= $brand_id == $br->id ? 'selected' : '' ?>><?= htmlspecialchars($br->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="small font-weight-bold">Fast &ge; (units)</label>
                <input type="number" name="fast_thresh" class="form-control form-control-sm" value="<?= $fast_thresh ?>" min="1" style="width:80px">
            </div>
            <div class="col-auto">
                <label class="small font-weight-bold">Slow &lt; (units)</label>
                <input type="number" name="slow_thresh" class="form-control form-control-sm" value="<?= $slow_thresh ?>" min="1" style="width:80px">
            </div>
            <?php if (!empty($locations)): ?>
            <div class="col-auto">
                <label class="small font-weight-bold">Location</label>
                <select name="location_id" class="form-control form-control-sm" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
                    <?php if (count($locations) > 1): ?>
                    <option value="">All Locations</option>
                    <?php endif; ?>
                    <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= (isset($_GET['location_id']) && $_GET['location_id'] == $loc->id) ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-auto">
                <label class="small">&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-sm d-block"><i class="fas fa-search mr-1"></i> Analyze</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-left:4px solid #28a745 !important;">
            <div class="text-success font-weight-bold" style="font-size:1.4rem"><?= count($fast) ?></div>
            <div class="small">Fast Moving Items</div>
            <div class="text-muted x-small"><?= number_format($fast_qty, 0) ?> units &bull; ₹<?= number_format($fast_rev, 0) ?> revenue</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-left:4px solid #ffc107 !important;">
            <div class="text-warning font-weight-bold" style="font-size:1.4rem"><?= count($slow) ?></div>
            <div class="small">Slow Moving Items</div>
            <div class="text-muted x-small"><?= number_format($slow_qty, 0) ?> units &bull; ₹<?= number_format($slow_val, 0) ?> stock value</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-left:4px solid #dc3545 !important;">
            <div class="text-danger font-weight-bold" style="font-size:1.4rem"><?= count($no_movement) ?></div>
            <div class="small">No Movement Items</div>
            <div class="text-muted x-small"><?= number_format($dead_stock, 0) ?> units &bull; ₹<?= number_format($dead_val, 0) ?> locked value</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3" style="border-left:4px solid #17a2b8 !important;">
            <div class="text-info font-weight-bold" style="font-size:1.4rem">₹<?= number_format($dead_val + $slow_val, 0) ?></div>
            <div class="small">Slow/Dead Stock Value</div>
            <div class="text-muted x-small">Capital locked in non-moving items</div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-0" id="movementTab">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#tab_fast">
            <i class="fas fa-bolt text-success mr-1"></i> Fast Moving
            <span class="badge badge-success badge-pill ml-1"><?= count($fast) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab_slow">
            <i class="fas fa-hourglass-half text-warning mr-1"></i> Slow Moving
            <span class="badge badge-warning badge-pill ml-1"><?= count($slow) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab_dead">
            <i class="fas fa-ban text-danger mr-1"></i> No Movement
            <span class="badge badge-danger badge-pill ml-1"><?= count($no_movement) ?></span>
        </a>
    </li>
</ul>

<div class="tab-content">

    <!-- FAST MOVING -->
    <div class="tab-pane fade show active" id="tab_fast">
        <div class="card border-0 shadow-sm" style="border-radius:0 4px 4px 4px;">
            <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-bolt mr-1"></i> Fast Moving — &ge; <?= $fast_thresh ?> units sold (<?= $from ?> to <?= $to ?>)</span>
                <span class="badge badge-light text-success"><?= count($fast) ?> products</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="tbl_fast">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th class="text-right">Qty Sold</th>
                                <th class="text-right">Revenue (₹)</th>
                                <th class="text-right">Txns</th>
                                <th class="text-right">Current Stock</th>
                                <th>Last Sale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($fast)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No fast moving items for this period.</td></tr>
                            <?php else: ?>
                            <?php foreach ($fast as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r->product_name) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($r->item_code) ?></small>
                                </td>
                                <td><small><?= htmlspecialchars($r->category_name) ?: '—' ?></small></td>
                                <td><small><?= htmlspecialchars($r->brand_name) ?: '—' ?></small></td>
                                <td class="text-right">
                                    <span class="font-weight-bold text-success"><?= number_format($r->total_sold, 0) ?></span>
                                    <div class="progress mt-1" style="height:3px">
                                        <?php $pct = $fast_qty > 0 ? min(100, $r->total_sold / $fast_qty * 100) : 0; ?>
                                        <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                                    </div>
                                </td>
                                <td class="text-right">₹<?= number_format($r->total_revenue, 0) ?></td>
                                <td class="text-right"><?= $r->txn_count ?></td>
                                <td class="text-right <?= $r->current_stock <= 0 ? 'text-danger font-weight-bold' : ($r->current_stock <= 5 ? 'text-warning' : '') ?>">
                                    <?= number_format($r->current_stock, 0) ?>
                                    <?php if ($r->current_stock <= 0): ?><br><small class="badge badge-danger">OUT OF STOCK</small><?php endif; ?>
                                    <?php if ($r->current_stock > 0 && $r->current_stock <= 5): ?><br><small class="badge badge-warning">LOW STOCK</small><?php endif; ?>
                                </td>
                                <td><small><?= $r->last_sale_date ? date('d/m/Y', strtotime($r->last_sale_date)) : '—' ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-success font-weight-bold">
                                <td colspan="4">Total</td>
                                <td class="text-right"><?= number_format($fast_qty, 0) ?></td>
                                <td class="text-right">₹<?= number_format($fast_rev, 0) ?></td>
                                <td colspan="3"></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SLOW MOVING -->
    <div class="tab-pane fade" id="tab_slow">
        <div class="card border-0 shadow-sm" style="border-radius:0 4px 4px 4px;">
            <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-hourglass-half mr-1"></i> Slow Moving — &lt; <?= $slow_thresh ?> units sold (<?= $from ?> to <?= $to ?>)</span>
                <span class="badge badge-dark"><?= count($slow) ?> products</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="tbl_slow">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th class="text-right">Qty Sold</th>
                                <th class="text-right">Stock Value (₹)</th>
                                <th class="text-right">Current Stock</th>
                                <th>Last Sale</th>
                                <th class="text-right">Days Idle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($slow)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No slow moving items for this period.</td></tr>
                            <?php else: ?>
                            <?php foreach ($slow as $i => $r): ?>
                            <?php $idle = (int)$r->days_since_last_sale; ?>
                            <tr class="<?= $idle > 60 ? 'table-warning' : '' ?>">
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r->product_name) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($r->item_code) ?></small>
                                </td>
                                <td><small><?= htmlspecialchars($r->category_name) ?: '—' ?></small></td>
                                <td><small><?= htmlspecialchars($r->brand_name) ?: '—' ?></small></td>
                                <td class="text-right text-warning font-weight-bold"><?= number_format($r->total_sold, 0) ?></td>
                                <td class="text-right">₹<?= number_format($r->stock_value, 0) ?></td>
                                <td class="text-right"><?= number_format($r->current_stock, 0) ?></td>
                                <td><small><?= $r->last_sale_date ? date('d/m/Y', strtotime($r->last_sale_date)) : '<em class="text-muted">Never sold</em>' ?></small></td>
                                <td class="text-right <?= $idle > 60 ? 'text-danger font-weight-bold' : '' ?>"><?= $idle ?: '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-warning font-weight-bold">
                                <td colspan="4">Total</td>
                                <td class="text-right"><?= number_format($slow_qty, 0) ?></td>
                                <td class="text-right">₹<?= number_format($slow_val, 0) ?></td>
                                <td colspan="3"></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- NO MOVEMENT -->
    <div class="tab-pane fade" id="tab_dead">
        <div class="card border-0 shadow-sm" style="border-radius:0 4px 4px 4px;">
            <div class="card-header bg-danger text-white py-2 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-ban mr-1"></i> No Movement — 0 units sold in period (<?= $from ?> to <?= $to ?>)</span>
                <span class="badge badge-light text-danger"><?= count($no_movement) ?> products &bull; ₹<?= number_format($dead_val, 0) ?> locked</span>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($no_movement)): ?>
                <div class="alert alert-danger m-2 py-1 mb-0">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <strong>₹<?= number_format($dead_val, 0) ?></strong> capital locked in <?= count($no_movement) ?> products with zero sales this period. Consider discounting or returning to supplier.
                </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="tbl_dead">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th class="text-right">Current Stock</th>
                                <th class="text-right">Stock Value (₹)</th>
                                <th>Last Ever Sale</th>
                                <th class="text-right">Days Since Sale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($no_movement)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">All products had at least some movement. </td></tr>
                            <?php else: ?>
                            <?php foreach ($no_movement as $i => $r): ?>
                            <tr class="table-danger">
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r->product_name) ?></strong><br>
                                    <small><?= htmlspecialchars($r->item_code) ?></small>
                                </td>
                                <td><small><?= htmlspecialchars($r->category_name) ?: '—' ?></small></td>
                                <td><small><?= htmlspecialchars($r->brand_name) ?: '—' ?></small></td>
                                <td class="text-right font-weight-bold"><?= number_format($r->current_stock, 0) ?></td>
                                <td class="text-right font-weight-bold">₹<?= number_format($r->stock_value, 0) ?></td>
                                <td><small><?= $r->last_sale_date ? date('d/m/Y', strtotime($r->last_sale_date)) : '<em class="text-muted">Never sold</em>' ?></small></td>
                                <td class="text-right text-danger font-weight-bold"><?= $r->days_since_last_sale ?: '∞' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-danger font-weight-bold">
                                <td colspan="4">Total</td>
                                <td class="text-right"><?= number_format($dead_stock, 0) ?></td>
                                <td class="text-right">₹<?= number_format($dead_val, 0) ?></td>
                                <td colspan="2"></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
@media print {
    .btn, form, .nav-tabs { display: none !important; }
    .tab-pane { display: block !important; opacity: 1 !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; margin-bottom: 1rem; }
}
</style>
