<?php
// ============================================================
// FILE: application/views/garment_reports/size_matrix.php
// Size-wise Sales & Stock Matrix
// ============================================================
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-th-large text-primary mr-2"></i>Size-wise Sales & Stock Matrix</h5>
        <small class="text-muted">Compare units sold and live stock across all sizes for each garment style</small>
    </div>
    <div>
        <?php
        $csv_params = http_build_query(array_merge($filters, ['export' => 'csv']));
        ?>
        <a href="<?= base_url('garmentreports/size_matrix?' . $csv_params) ?>" class="btn btn-outline-success btn-sm mr-1">
            <i class="fas fa-file-excel mr-1"></i> Export CSV
        </a>
        <a href="<?= base_url('garmentreports') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Reports Hub
        </a>
    </div>
</div>

<!-- Filter card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="get" action="<?= base_url('garmentreports/size_matrix') ?>" class="form-row align-items-center">
            <div class="col-md-2 mb-2 mb-md-0">
                <label class="small text-muted font-weight-bold mb-0">From Date</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['from']) ?>">
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <label class="small text-muted font-weight-bold mb-0">To Date</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['to']) ?>">
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="small text-muted font-weight-bold mb-0">Location / Outlet</label>
                <select name="location_id" class="form-control form-control-sm">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= (int)$loc->id ?>" <?= ($filters['location_id'] == $loc->id) ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="small text-muted font-weight-bold mb-0">Brand</label>
                <select name="brand" class="form-control form-control-sm">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= htmlspecialchars($b->name) ?>" <?= ($filters['brand'] === $b->name) ? 'selected' : '' ?>><?= htmlspecialchars($b->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 text-right pt-3">
                <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter mr-1"></i> Filter Matrix</button>
            </div>
        </form>
    </div>
</div>

<!-- Matrix Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" style="font-size: 0.82rem;">
                <thead class="thead-dark text-center">
                    <tr>
                        <th rowspan="2" class="align-middle text-left" style="min-width: 180px;">Garment Style</th>
                        <th rowspan="2" class="align-middle" style="min-width: 90px;">Style Code</th>
                        <th rowspan="2" class="align-middle" style="min-width: 90px;">Category</th>
                        <?php foreach ($sizes as $sz): ?>
                            <th colspan="2" class="bg-secondary text-white"><?= htmlspecialchars($sz) ?></th>
                        <?php endforeach; ?>
                        <th colspan="2" class="bg-primary text-white">Total</th>
                    </tr>
                    <tr>
                        <?php foreach ($sizes as $sz): ?>
                            <th class="bg-light text-dark font-weight-bold" style="width: 55px;" title="Sold Quantity">Sold</th>
                            <th class="bg-light text-primary font-weight-bold" style="width: 55px;" title="Live Stock">Stock</th>
                        <?php endforeach; ?>
                        <th class="bg-info text-white font-weight-bold" style="width: 60px;">Sold</th>
                        <th class="bg-success text-white font-weight-bold" style="width: 60px;">Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($styles)): ?>
                        <tr>
                            <td colspan="<?= 5 + count($sizes) * 2 ?>" class="text-center py-5 text-muted">
                                No sales or stock records found for the selected criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $grand_sold = 0;
                        $grand_stock = 0;
                        $size_sold_totals = [];
                        $size_stock_totals = [];
                        foreach ($sizes as $sz) {
                            $size_sold_totals[$sz] = 0;
                            $size_stock_totals[$sz] = 0;
                        }

                        foreach ($styles as $st): 
                            $row_sold = 0;
                            $row_stock = 0;
                        ?>
                            <tr>
                                <td class="font-weight-bold text-dark">
                                    <a href="<?= base_url('garments/view/' . $st->product_id) ?>" class="text-dark">
                                        <?= htmlspecialchars($st->product_name) ?>
                                    </a>
                                </td>
                                <td><code><?= htmlspecialchars($st->style_code ?: $st->design_no ?: $st->item_code) ?></code></td>
                                <td><span class="badge badge-light border"><?= htmlspecialchars($st->category ?: '-') ?></span></td>
                                <?php foreach ($sizes as $sz): 
                                    $sold = (float)($sales_map[$st->product_id][$sz]['qty'] ?? 0);
                                    $stk  = (float)($stock_map[$st->product_id][$sz] ?? 0);
                                    $row_sold += $sold;
                                    $row_stock += $stk;
                                    $size_sold_totals[$sz] += $sold;
                                    $size_stock_totals[$sz] += $stk;
                                ?>
                                    <td class="text-center <?= $sold > 0 ? 'font-weight-bold text-dark bg-warning-light' : 'text-muted' ?>">
                                        <?= $sold > 0 ? number_format($sold, 0) : '-' ?>
                                    </td>
                                    <td class="text-center <?= $stk > 0 ? 'font-weight-bold text-primary' : 'text-danger' ?>">
                                        <?= number_format($stk, 0) ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center font-weight-bold bg-light text-dark">
                                    <?= number_format($row_sold, 0) ?>
                                </td>
                                <td class="text-center font-weight-bold bg-light <?= $row_stock > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format($row_stock, 0) ?>
                                </td>
                            </tr>
                            <?php 
                            $grand_sold += $row_sold;
                            $grand_stock += $row_stock;
                        endforeach; 
                        ?>
                        <!-- Summary Row -->
                        <tr class="table-dark font-weight-bold text-center">
                            <td colspan="3" class="text-right">TOTALS ACROSS SIZES:</td>
                            <?php foreach ($sizes as $sz): ?>
                                <td class="text-warning"><?= number_format($size_sold_totals[$sz], 0) ?></td>
                                <td class="text-info"><?= number_format($size_stock_totals[$sz], 0) ?></td>
                            <?php endforeach; ?>
                            <td class="text-warning font-weight-bold" style="font-size: 0.95rem;"><?= number_format($grand_sold, 0) ?></td>
                            <td class="text-success font-weight-bold" style="font-size: 0.95rem;"><?= number_format($grand_stock, 0) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
