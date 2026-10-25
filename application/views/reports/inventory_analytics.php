<?php // FILE: application/views/reports/inventory_analytics.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-chart-pie text-warning mr-1"></i> Inventory Analytics</h5>
        <small class="text-muted">Fast-Moving, Slow-Moving, Dead Stock, ABC Analysis, Turnover Ratio</small>
    </div>
    <div>
        <button class="btn btn-outline-success btn-sm" onclick="window.print()"><i class="fas fa-print mr-1"></i> Print</button>
    </div>
</div>

<!-- Date Filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="form-inline">
            <label class="mr-2 font-weight-bold" style="font-size:.85rem;">Period:</label>
            <input type="date" name="from" class="form-control form-control-sm mr-2" value="<?= $from ?>">
            <span class="mr-2">to</span>
            <input type="date" name="to" class="form-control form-control-sm mr-2" value="<?= $to ?>">
            <label class="mr-2 font-weight-bold" style="font-size:.85rem;">Dead Stock (days):</label>
            <select name="dead_days" class="form-control form-control-sm mr-2">
                <?php foreach ([30,60,90,180,365] as $d): ?>
                <option value="<?= $d ?>" <?= $dead_days == $d ? 'selected' : '' ?>><?= $d ?> Days</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Analyze</button>
        </form>
    </div>
</div>

<!-- Tab Navigation -->
<ul class="nav nav-tabs mb-3" id="analyticsTab">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab_fast"><i class="fas fa-bolt text-success mr-1"></i> Fast Moving</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_slow"><i class="fas fa-hourglass text-warning mr-1"></i> Slow Moving</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_dead"><i class="fas fa-skull-crossbones text-danger mr-1"></i> Dead Stock (<?= $dead_days ?>d)</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_abc"><i class="fas fa-sort-alpha-down text-primary mr-1"></i> ABC Analysis</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_turnover"><i class="fas fa-sync text-info mr-1"></i> Turnover Ratio</a></li>
</ul>

<div class="tab-content">

    <!-- FAST MOVING -->
    <div class="tab-pane fade show active" id="tab_fast">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-2"><i class="fas fa-bolt mr-1"></i> Fast Moving Items (<?= $from ?> to <?= $to ?>)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr><th>#</th><th>Product</th><th>Category</th><th>Brand</th><th class="text-right">Qty Sold</th><th class="text-right">Revenue</th><th class="text-right">Txns</th><th class="text-right">Stock</th></tr>
                        </thead>
                        <tbody>
                            <?php $i=1; foreach ($fast_moving as $r): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><strong><?= htmlspecialchars($r->product_name) ?></strong><br><small class="text-muted"><?= $r->item_code ?></small></td>
                                <td><small><?= htmlspecialchars($r->category_name) ?></small></td>
                                <td><small><?= htmlspecialchars($r->brand_name) ?></small></td>
                                <td class="text-right font-weight-bold text-success"><?= number_format($r->total_sold, 0) ?></td>
                                <td class="text-right">₹<?= number_format($r->total_revenue, 0) ?></td>
                                <td class="text-right"><?= $r->txn_count ?></td>
                                <td class="text-right <?= $r->current_stock <= 0 ? 'text-danger' : '' ?>"><?= number_format($r->current_stock, 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($fast_moving)): ?><tr><td colspan="8" class="text-center text-muted py-3">No data</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SLOW MOVING -->
    <div class="tab-pane fade" id="tab_slow">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark py-2"><i class="fas fa-hourglass mr-1"></i> Slow Moving Items (less than 5 units sold)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr><th>#</th><th>Product</th><th>Category</th><th>Brand</th><th class="text-right">Qty Sold</th><th class="text-right">Current Stock</th><th class="text-right">Stock Value</th></tr>
                        </thead>
                        <tbody>
                            <?php $i=1; foreach ($slow_moving as $r): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><strong><?= htmlspecialchars($r->product_name) ?></strong><br><small class="text-muted"><?= $r->item_code ?></small></td>
                                <td><small><?= htmlspecialchars($r->category_name) ?></small></td>
                                <td><small><?= htmlspecialchars($r->brand_name) ?></small></td>
                                <td class="text-right"><?= number_format($r->total_sold, 0) ?></td>
                                <td class="text-right text-warning font-weight-bold"><?= number_format($r->current_stock, 0) ?></td>
                                <td class="text-right">₹<?= number_format($r->stock_value, 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($slow_moving)): ?><tr><td colspan="7" class="text-center text-muted py-3">No data</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- DEAD STOCK -->
    <div class="tab-pane fade" id="tab_dead">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-danger text-white py-2"><i class="fas fa-skull-crossbones mr-1"></i> Dead Stock — No sales in <?= $dead_days ?> days</div>
            <div class="card-body p-0">
                <?php
                $dead_value = array_sum(array_column((array)$dead_stock, 'dead_stock_value'));
                ?>
                <div class="alert alert-danger m-2 py-1 small">
                    <strong>Total Dead Stock Value: ₹<?= number_format($dead_value, 2) ?></strong> across <?= count($dead_stock) ?> products
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-dark">
                            <tr><th>#</th><th>Product</th><th>Category</th><th class="text-right">Stock</th><th class="text-right">Dead Value</th><th>Last Sale</th><th class="text-right">Days Idle</th></tr>
                        </thead>
                        <tbody>
                            <?php $i=1; foreach ($dead_stock as $r): ?>
                            <tr class="table-danger">
                                <td><?= $i++ ?></td>
                                <td><strong><?= htmlspecialchars($r->product_name) ?></strong><br><small><?= $r->item_code ?></small></td>
                                <td><small><?= htmlspecialchars($r->category_name) ?></small></td>
                                <td class="text-right"><?= number_format($r->current_stock, 0) ?></td>
                                <td class="text-right font-weight-bold">₹<?= number_format($r->dead_stock_value, 0) ?></td>
                                <td><?= $r->last_sale_date ? date('d/m/Y', strtotime($r->last_sale_date)) : '<em class="text-muted">Never sold</em>' ?></td>
                                <td class="text-right text-danger font-weight-bold"><?= $r->days_since_sale ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($dead_stock)): ?><tr><td colspan="7" class="text-center text-muted py-3">No dead stock — great!</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ABC ANALYSIS -->
    <div class="tab-pane fade" id="tab_abc">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-2"><i class="fas fa-sort-alpha-down mr-1"></i> ABC Analysis (by Revenue Contribution)</div>
            <div class="card-body">
                <div class="row mb-3">
                    <?php
                    $a_count = count(array_filter((array)$abc_analysis, fn($r) => $r->abc_class === 'A'));
                    $b_count = count(array_filter((array)$abc_analysis, fn($r) => $r->abc_class === 'B'));
                    $c_count = count(array_filter((array)$abc_analysis, fn($r) => $r->abc_class === 'C'));
                    ?>
                    <div class="col-md-4"><div class="card bg-success text-white text-center p-3"><h4><?= $a_count ?></h4><div>Class A Items</div><small>Top 80% Revenue</small></div></div>
                    <div class="col-md-4"><div class="card bg-warning text-dark text-center p-3"><h4><?= $b_count ?></h4><div>Class B Items</div><small>Next 15% Revenue</small></div></div>
                    <div class="col-md-4"><div class="card bg-secondary text-white text-center p-3"><h4><?= $c_count ?></h4><div>Class C Items</div><small>Bottom 5% Revenue</small></div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr><th>#</th><th>Product</th><th class="text-right">Revenue</th><th class="text-right">Qty</th><th class="text-right">Cumulative %</th><th class="text-center">Class</th></tr>
                        </thead>
                        <tbody>
                            <?php $i=1; foreach ($abc_analysis as $r): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($r->product_name) ?> <small class="text-muted"><?= $r->item_code ?></small></td>
                                <td class="text-right">₹<?= number_format($r->revenue, 0) ?></td>
                                <td class="text-right"><?= number_format($r->qty_sold, 0) ?></td>
                                <td class="text-right"><?= $r->cumulative_pct ?>%</td>
                                <td class="text-center">
                                    <span class="badge badge-<?= $r->abc_class === 'A' ? 'success' : ($r->abc_class === 'B' ? 'warning' : 'secondary') ?> badge-pill px-3">
                                        <?= $r->abc_class ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TURNOVER RATIO -->
    <div class="tab-pane fade" id="tab_turnover">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white py-2"><i class="fas fa-sync mr-1"></i> Inventory Turnover Ratio</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr><th>#</th><th>Product</th><th class="text-right">Current Stock</th><th class="text-right">COGS</th><th class="text-right">Qty Sold</th><th class="text-right">Turnover Ratio</th><th class="text-center">Performance</th></tr>
                        </thead>
                        <tbody>
                            <?php $i=1; foreach ($turnover as $r): ?>
                            <?php if ($r->current_stock <= 0) continue; ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($r->product_name) ?> <small class="text-muted"><?= $r->item_code ?></small></td>
                                <td class="text-right"><?= number_format($r->closing_stock, 0) ?></td>
                                <td class="text-right">₹<?= number_format($r->cogs, 0) ?></td>
                                <td class="text-right"><?= number_format($r->qty_sold, 0) ?></td>
                                <td class="text-right font-weight-bold"><?= number_format($r->turnover_ratio, 2) ?>x</td>
                                <td class="text-center">
                                    <?php if ($r->turnover_ratio >= 6): ?>
                                        <span class="badge badge-success">Excellent</span>
                                    <?php elseif ($r->turnover_ratio >= 3): ?>
                                        <span class="badge badge-info">Good</span>
                                    <?php elseif ($r->turnover_ratio >= 1): ?>
                                        <span class="badge badge-warning">Average</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Poor</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
