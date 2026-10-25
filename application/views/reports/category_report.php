<?php // FILE: application/views/reports/category_report.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-folder text-warning mr-1"></i> Category Report</h5>
        <small class="text-muted">Sales, Profit, and Inventory by Category — <?= $from ?> to <?= $to ?></small>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/category_report','export_params'=>$export_params??'','export_title'=>$export_title??'Category Report']); ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="form-inline">
            <input type="date" name="from" class="form-control form-control-sm mr-2" value="<?= $from ?>">
            <span class="mr-2">to</span>
            <input type="date" name="to" class="form-control form-control-sm mr-2" value="<?= $to ?>">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
        </form>
    </div>
</div>

<?php
$total_sales  = array_sum(array_column((array)$categories, 'total_sales'));
$total_profit = array_sum(array_column((array)$categories, 'gross_profit'));
$total_qty    = array_sum(array_column((array)$categories, 'total_qty'));
?>
<div class="row mb-3">
    <div class="col-md-3"><div class="card bg-primary text-white text-center p-3"><h5>₹<?= number_format($total_sales, 0) ?></h5><small>Total Sales</small></div></div>
    <div class="col-md-3"><div class="card bg-success text-white text-center p-3"><h5>₹<?= number_format($total_profit, 0) ?></h5><small>Gross Profit</small></div></div>
    <div class="col-md-3"><div class="card bg-info text-white text-center p-3"><h5><?= number_format($total_qty, 0) ?></h5><small>Total Qty Sold</small></div></div>
    <div class="col-md-3"><div class="card bg-warning text-dark text-center p-3"><h5><?= $total_sales > 0 ? number_format(($total_profit/$total_sales)*100, 1) : 0 ?>%</h5><small>Avg Margin</small></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th class="text-right">Qty Sold</th>
                        <th class="text-right">Sales Value</th>
                        <th class="text-right">Gross Profit</th>
                        <th class="text-right">Margin %</th>
                        <th class="text-right">Share %</th>
                        <th class="text-right">Current Stock</th>
                        <th class="text-right">Stock Value (₹)</th>
                        <th class="text-right">Products</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($categories as $r): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="font-weight-bold"><?= htmlspecialchars($r->category_name) ?></td>
                        <td class="text-right"><?= number_format($r->total_qty, 0) ?></td>
                        <td class="text-right font-weight-bold">₹<?= number_format($r->total_sales, 0) ?></td>
                        <td class="text-right <?= $r->gross_profit >= 0 ? 'text-success' : 'text-danger' ?> font-weight-bold">
                            ₹<?= number_format($r->gross_profit, 0) ?>
                        </td>
                        <td class="text-right">
                            <?= $r->total_sales > 0 ? number_format(($r->gross_profit/$r->total_sales)*100, 1) : 0 ?>%
                        </td>
                        <td class="text-right">
                            <?= $total_sales > 0 ? number_format(($r->total_sales/$total_sales)*100, 1) : 0 ?>%
                            <div class="progress mt-1" style="height:4px;">
                                <div class="progress-bar bg-primary" style="width:<?= $total_sales > 0 ? ($r->total_sales/$total_sales)*100 : 0 ?>%"></div>
                            </div>
                        </td>
                        <td class="text-right"><?= number_format($r->current_stock ?? 0, 0) ?></td>
                        <td class="text-right text-warning font-weight-bold">₹<?= number_format($r->stock_value ?? 0, 0) ?></td>
                        <td class="text-right text-muted"><?= $r->product_count ?? 0 ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No sales in this period</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($categories)): ?>
                <tfoot class="font-weight-bold bg-light">
                    <tr>
                        <td colspan="2">TOTAL</td>
                        <td class="text-right"><?= number_format($total_qty, 0) ?></td>
                        <td class="text-right">₹<?= number_format($total_sales, 0) ?></td>
                        <td class="text-right text-success">₹<?= number_format($total_profit, 0) ?></td>
                        <td class="text-right"><?= $total_sales > 0 ? number_format(($total_profit/$total_sales)*100, 1) : 0 ?>%</td>
                        <td class="text-right">100%</td>
                        <td class="text-right"><?= number_format(array_sum(array_map(fn($c)=>$c->current_stock??0,(array)$categories)),0) ?></td>
                        <td class="text-right text-warning">₹<?= number_format(array_sum(array_map(fn($c)=>$c->stock_value??0,(array)$categories)),0) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
