<?php
// ============================================================
// FILE: application/views/reports/executive_summary.php
// Single-page monthly Executive Summary for owners/management.
// ============================================================
$cur  = $cur ?? [];
$prev = $prev ?? [];

if (!function_exists('ex_growth')) {
function ex_growth($cv, $pv) {
    $cv = (float)$cv; $pv = (float)$pv;
    $diff = $cv - $pv;
    $pct  = $pv != 0 ? round(($diff / abs($pv)) * 100, 1) : ($cv > 0 ? 100 : 0);
    return ['diff' => $diff, 'pct' => $pct, 'up' => $diff >= 0];
}
}
if (!function_exists('ex_fmt')) {
function ex_fmt($val, $type = 'currency') {
    if ($type === 'currency') return '₹ ' . number_format((float)$val, 0);
    if ($type === 'currency2') return '₹ ' . number_format((float)$val, 2);
    if ($type === 'percent') return number_format((float)$val, 1) . '%';
    if ($type === 'days') return number_format((float)$val, 1) . ' days';
    return number_format((float)$val, 0);
}
}
if (!function_exists('ex_kpi_card')) {
function ex_kpi_card($label, $icon, $cv, $pv, $type = 'currency', $invert = false, $basis = '') {
    $g = ex_growth($cv, $pv);
    $good = $invert ? !$g['up'] : $g['up'];
    $arrow_class = $good ? 'text-success' : 'text-danger';
    $arrow = $g['up'] ? '&#9650;' : '&#9660;';
    ob_start();
    ?>
    <div class="col-6 col-md-4 col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100 exec-kpi-card">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="text-muted small"><?= $icon ?> <?= htmlspecialchars($label) ?><?= $basis ? ' <span style="font-size:.72em;">('.htmlspecialchars($basis).')</span>' : '' ?></div>
                </div>
                <div class="h5 font-weight-bold mb-1 mt-1"><?= ex_fmt($cv, $type) ?></div>
                <div class="small <?= $arrow_class ?> font-weight-bold">
                    <?= $arrow ?> <?= abs($g['pct']) ?>%
                    <span class="text-muted font-weight-normal">vs <?= ex_fmt($pv, $type) ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
}

// ── Business Health Meter — simple heuristic 0-100 composite, not an accounting figure ──
$sales_growth   = ex_growth($cur['revenue'] ?? 0, $prev['revenue'] ?? 0)['pct'];
$gp_margin      = $cur['gross_margin'] ?? 0;
$avg_stock_val  = ((($opening_stock['value'] ?? 0) + ($closing_stock['value'] ?? 0)) / 2) ?: 1;
$turnover_ratio = $avg_stock_val > 0 ? ($cur['cogs'] ?? 0) / $avg_stock_val : 0;
$purchase_ratio = ($cur['revenue'] ?? 0) > 0 ? ($cur['purchases'] ?? 0) / $cur['revenue'] : 1;
$cust_growth    = ex_growth($cur['total_customers'] ?? 0, $prev['total_customers'] ?? 0)['pct'];
$cash_ratio     = ($cur['revenue'] ?? 0) > 0 ? (($cur['cash_sales'] ?? 0) / ($cur['revenue'] ?? 1)) * 100 : 0;

if (!function_exists('ex_score')) {
function ex_score($val, $green_min, $yellow_min) {
    if ($val >= $green_min) return 100;
    if ($val >= $yellow_min) return 60;
    return max(0, 25 + ($val * 2));
}
}
$score_sales     = ex_score($sales_growth, 5, -5);
$score_inventory = $turnover_ratio >= 1 ? 100 : ($turnover_ratio >= 0.5 ? 60 : 30);
$score_purchase  = $purchase_ratio <= 0.8 ? 100 : ($purchase_ratio <= 1.1 ? 60 : 30);
$score_gp        = $gp_margin >= 20 ? 100 : ($gp_margin >= 10 ? 60 : 30);
$score_customer  = ex_score($cust_growth, 5, -5);
$score_cash      = $cash_ratio >= 40 ? 100 : ($cash_ratio >= 15 ? 70 : 40);
$overall_score   = round(($score_sales + $score_inventory + $score_purchase + $score_gp + $score_customer + $score_cash) / 6);

if (!function_exists('ex_health_color')) {
function ex_health_color($s) { return $s >= 75 ? 'success' : ($s >= 45 ? 'warning' : 'danger'); }
}
if (!function_exists('ex_health_label')) {
function ex_health_label($s) { return $s >= 75 ? 'Excellent' : ($s >= 45 ? 'Average' : 'Needs Attention'); }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-chart-line mr-1"></i> Executive Summary</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Executive Summary — <?= date('F Y', strtotime($from)) ?></small>
    </div>
    <div class="d-flex" style="gap:5px;">
        <a href="<?= base_url('reports/executive_summary?month='.$month.'&location_id='.($location_id ?? '')) ?>&export=excel" class="btn btn-success btn-sm"><i class="fas fa-file-excel mr-1"></i> Excel</a>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fas fa-print mr-1"></i> Print</button>
    </div>
</div>

<!-- Print header (visible only when printing) -->
<div class="d-none print-only text-center mb-3">
    <h4 class="font-weight-bold mb-0">Executive Summary</h4>
    <div><?= date('F Y', strtotime($from)) ?><?= !empty($location_id) ? ' — ' . htmlspecialchars(array_reduce((array)($locations??[]), fn($c,$l)=>$l->id==$location_id?$l->name:$c,'')) : ' — All Locations' ?></div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="form-inline">
            <label class="mr-2 small">Month</label>
            <input type="month" name="month" class="form-control form-control-sm mr-2" value="<?= $month ?>" max="<?= date('Y-m') ?>">
            <label class="mr-2 small">Location</label>
            <select name="location_id" class="form-control form-control-sm mr-2" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
                <?php if (count($locations ?? []) > 1): ?>
                <option value="">All Locations</option>
                <?php endif; ?>
                <?php foreach ($locations ?? [] as $loc): ?>
                <option value="<?= $loc->id ?>" <?= ($location_id ?? 0) == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-dark btn-sm">Apply</button>
        </form>
    </div>
</div>

<!-- Business Health Meter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-dark text-white py-2 font-weight-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-heartbeat mr-1"></i> Business Health Meter</span>
        <span class="badge badge-<?= ex_health_color($overall_score) ?>" style="font-size:.9rem;">Overall: <?= $overall_score ?>% — <?= ex_health_label($overall_score) ?></span>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <?php
            $meters = [
                'Sales Performance' => $score_sales,
                'Inventory Health'  => $score_inventory,
                'Purchase Control'  => $score_purchase,
                'Gross Profit'      => $score_gp,
                'Customer Growth'   => $score_customer,
                'Cash Flow'         => $score_cash,
            ];
            foreach ($meters as $label => $score):
            ?>
            <div class="col-6 col-md-2 mb-2">
                <div class="text-muted small mb-1"><?= $label ?></div>
                <div class="progress" style="height:8px;">
                    <div class="progress-bar bg-<?= ex_health_color($score) ?>" style="width:<?= $score ?>%"></div>
                </div>
                <div class="small font-weight-bold text-<?= ex_health_color($score) ?> mt-1"><?= round($score) ?>%</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Alerts -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-warning text-dark py-2 font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Alerts</div>
    <div class="card-body py-2">
        <div class="row">
            <?php
            $alerts = [
                ['label'=>'Low Stock Items',        'val'=>$low_stock_count ?? 0,        'url'=>'reports/stock?item_name=', 'icon'=>'📉'],
                ['label'=>'Negative Stock',         'val'=>$negative_stock_count ?? 0,   'url'=>'Reports/negative_stock_audit', 'icon'=>'⚠️'],
                ['label'=>'Dead/Slow Stock Items',  'val'=>count($dead_slow_stock ?? []),'url'=>null, 'icon'=>'🐌'],
                ['label'=>'Pending GRN',            'val'=>$pending_grn_count ?? 0,      'url'=>'grn?status=pending', 'icon'=>'📦'],
                ['label'=>'Pending Purchase Orders','val'=>$pending_po_count ?? 0,       'url'=>'purchase_orders', 'icon'=>'🛒'],
                ['label'=>'Outstanding Receivable', 'val'=>'₹'.number_format($outstanding_receivable ?? 0,0), 'url'=>'reports/outstanding', 'icon'=>'💰'],
                ['label'=>'Outstanding Payable',    'val'=>'₹'.number_format($outstanding_payable ?? 0,0),    'url'=>'reports/outstanding', 'icon'=>'💸'],
            ];
            foreach ($alerts as $a):
                $is_zero = $a['val'] === 0 || $a['val'] === '₹0';
            ?>
            <div class="col-6 col-md-3 col-lg-auto mb-2">
                <?php if ($a['url']): ?><a href="<?= base_url($a['url']) ?>" class="text-decoration-none"><?php endif; ?>
                <div class="px-3 py-2 rounded border <?= $is_zero ? 'bg-light text-muted' : 'bg-white' ?>" style="font-size:.83rem;">
                    <div><?= $a['icon'] ?> <?= $a['label'] ?></div>
                    <div class="font-weight-bold <?= $is_zero ? '' : 'text-danger' ?>" style="font-size:1.05rem;"><?= $a['val'] ?></div>
                </div>
                <?php if ($a['url']): ?></a><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row">
    <?= ex_kpi_card('Total Sales', '💵', $cur['revenue'] ?? 0, $prev['revenue'] ?? 0, 'currency', false, 'excl. GST') ?>
    <?= ex_kpi_card('Total Purchases', '🛒', $cur['purchases'] ?? 0, $prev['purchases'] ?? 0, 'currency', true, 'excl. GST') ?>
    <?= ex_kpi_card('Opening Stock Value', '📦', $opening_stock['value'] ?? 0, 0, 'currency', false, 'basic cost') ?>
    <?= ex_kpi_card('Closing Stock Value', '📦', $closing_stock['value'] ?? 0, $opening_stock['value'] ?? 0, 'currency', false, 'basic cost') ?>
    <?= ex_kpi_card('Stock Movement', '🔁', ($closing_stock['value'] ?? 0) - ($opening_stock['value'] ?? 0), 0, 'currency', false, 'basic cost') ?>
    <?= ex_kpi_card('Gross Profit', '📈', $cur['gross_profit'] ?? 0, $prev['gross_profit'] ?? 0, 'currency') ?>
    <?= ex_kpi_card('Gross Margin %', '📊', $cur['gross_margin'] ?? 0, $prev['gross_margin'] ?? 0, 'percent') ?>
    <?= ex_kpi_card('Total Customers', '👥', $cur['total_customers'] ?? 0, $prev['total_customers'] ?? 0, 'plain') ?>
    <?= ex_kpi_card('Total Bills', '🧾', $cur['bill_count'] ?? 0, $prev['bill_count'] ?? 0, 'plain') ?>
    <?= ex_kpi_card('Average Bill Value', '🧮', $cur['avg_bill_value'] ?? 0, $prev['avg_bill_value'] ?? 0, 'currency2', false, 'excl. GST') ?>
    <?= ex_kpi_card('Total Qty Sold', '📦', $cur['qty_sold'] ?? 0, $prev['qty_sold'] ?? 0, 'plain') ?>
    <?= ex_kpi_card('Inventory Turnover Ratio', '🔄', $turnover_ratio, 0, 'plain') ?>
    <?= ex_kpi_card('Stock Holding Days', '📅', $turnover_ratio > 0 ? 30 / $turnover_ratio : 0, 0, 'days') ?>
    <?= ex_kpi_card('Purchase Return', '↩️', $cur['purchase_return'] ?? 0, $prev['purchase_return'] ?? 0, 'currency', true, 'incl. GST') ?>
    <?= ex_kpi_card('Sales Return', '↩️', $cur['sales_return'] ?? 0, $prev['sales_return'] ?? 0, 'currency', true, 'excl. GST') ?>
    <?= ex_kpi_card('Total Discount Given', '🏷️', $cur['discount_given'] ?? 0, $prev['discount_given'] ?? 0, 'currency', true) ?>
    <?= ex_kpi_card('GST Collected', '🧾', $cur['gst_collected'] ?? 0, $prev['gst_collected'] ?? 0, 'currency') ?>
    <?= ex_kpi_card('Outstanding Receivable', '💰', $outstanding_receivable ?? 0, 0, 'currency', true, 'incl. GST') ?>
    <?= ex_kpi_card('Outstanding Payable', '💸', $outstanding_payable ?? 0, 0, 'currency', true, 'incl. GST') ?>
    <?= ex_kpi_card('Cash Sales', '💵', $cur['cash_sales'] ?? 0, $prev['cash_sales'] ?? 0, 'currency', false, 'incl. GST') ?>
    <?= ex_kpi_card('Credit Sales', '📋', $cur['credit_sales'] ?? 0, $prev['credit_sales'] ?? 0, 'currency', true, 'incl. GST') ?>
</div>

<!-- Charts -->
<div class="row mt-2">
    <div class="col-lg-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Daily Sales Trend <small class="text-muted font-weight-normal">(excl. GST)</small></div>
            <div class="card-body"><canvas id="dailySalesChart" height="140"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Sales vs Purchase (Last 6 Months) <small class="text-muted font-weight-normal">(excl. GST)</small></div>
            <div class="card-body"><canvas id="salesPurchaseChart" height="140"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Gross Profit Trend (Last 6 Months)</div>
            <div class="card-body"><canvas id="gpTrendChart" height="140"></canvas></div>
        </div>
    </div>
    <div class="col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Sales by Category <small class="text-muted font-weight-normal">(excl. GST)</small></div>
            <div class="card-body"><canvas id="categoryPieChart" height="160"></canvas></div>
        </div>
    </div>
    <div class="col-lg-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Payment Mode Split <small class="text-muted font-weight-normal">(incl. GST)</small></div>
            <div class="card-body"><canvas id="paymentPieChart" height="160"></canvas></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Top Categories by Sales</div>
            <div class="card-body"><canvas id="topCategoryBar" height="160"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Top Selling Products <small class="text-muted font-weight-normal">(excl. GST)</small></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size:.8rem;">
                        <thead class="thead-light"><tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Sales</th><th class="text-right">GP</th></tr></thead>
                        <tbody>
                        <?php if (!empty($top_products)): foreach ($top_products as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p->name) ?></td>
                                <td class="text-right"><?= number_format($p->qty_sold,0) ?></td>
                                <td class="text-right">₹<?= number_format($p->revenue,0) ?></td>
                                <td class="text-right <?= $p->gross_profit>=0?'text-success':'text-danger' ?>">₹<?= number_format($p->gross_profit,0) ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No sales this period.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slow-Moving / Dead Stock -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Slow-Moving / Dead Stock <small class="text-muted">(in stock, not sold 30+ days)</small></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.8rem;">
                <thead class="thead-light"><tr><th>Item</th><th class="text-right">Days Not Sold</th><th class="text-right">Stock Qty</th><th class="text-right">Stock Value</th></tr></thead>
                <tbody>
                <?php if (!empty($dead_slow_stock)): foreach ($dead_slow_stock as $d):
                    $days = (int)floor((time() - strtotime($d->last_sold ?? '1970-01-01')) / 86400);
                ?>
                    <tr>
                        <td><?= htmlspecialchars($d->name) ?></td>
                        <td class="text-right <?= $days>90?'text-danger font-weight-bold':'text-warning' ?>"><?= $d->last_sold ? $days : 'Never sold' ?></td>
                        <td class="text-right"><?= number_format($d->stock_qty,0) ?></td>
                        <td class="text-right">₹<?= number_format($d->stock_value,0) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">No slow-moving stock found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($branch_perf)): ?>
<!-- Branch Performance -->
<?php
$best_sales = null; $best_gp = null;
foreach ($branch_perf as $b) {
    if (!$best_sales || $b->sales > $best_sales->sales) $best_sales = $b;
    if (!$best_gp || $b->gross_profit > $best_gp->gross_profit) $best_gp = $b;
}
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-dark text-white py-2 font-weight-bold">Branch Performance <small style="opacity:.75;font-weight:normal;">(Sales/Purchase/GP excl. GST, Stock Value at basic cost)</small></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                <thead class="thead-light">
                    <tr><th>Branch</th><th class="text-right">Sales</th><th class="text-right">Purchase</th><th class="text-right">GP</th><th class="text-right">Margin</th><th class="text-right">Stock Value</th><th class="text-right">Bills</th><th class="text-right">Customers</th></tr>
                </thead>
                <tbody>
                <?php foreach ($branch_perf as $b): ?>
                    <tr>
                        <td class="font-weight-bold">
                            <?= htmlspecialchars($b->name) ?>
                            <?php if ($b->id === $best_sales->id): ?><span class="badge badge-success ml-1">Top Sales</span><?php endif; ?>
                            <?php if ($b->id === $best_gp->id): ?><span class="badge badge-info ml-1">Top GP</span><?php endif; ?>
                        </td>
                        <td class="text-right">₹<?= number_format($b->sales,0) ?></td>
                        <td class="text-right">₹<?= number_format($b->purchases,0) ?></td>
                        <td class="text-right <?= $b->gross_profit>=0?'text-success':'text-danger' ?>">₹<?= number_format($b->gross_profit,0) ?></td>
                        <td class="text-right"><?= $b->margin ?>%</td>
                        <td class="text-right">₹<?= number_format($b->stock_value,0) ?></td>
                        <td class="text-right"><?= $b->bills ?></td>
                        <td class="text-right"><?= $b->customers ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const dailyLabels = <?= json_encode(array_map(fn($d) => date('d M', strtotime($d->d)), $daily_sales ?? [])) ?>;
const dailyRevenue = <?= json_encode(array_map(fn($d) => (float)$d->revenue, $daily_sales ?? [])) ?>;
new Chart(document.getElementById('dailySalesChart'), {
    type: 'line',
    data: { labels: dailyLabels, datasets: [{ label: 'Sales', data: dailyRevenue, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,.1)', tension: .3, fill: true }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

const trendLabels = <?= json_encode(array_map(fn($m) => $m['label'], $monthly_trend ?? [])) ?>;
const trendSales = <?= json_encode(array_map(fn($m) => $m['revenue'], $monthly_trend ?? [])) ?>;
const trendPurch = <?= json_encode(array_map(fn($m) => $m['purchases'], $monthly_trend ?? [])) ?>;
const trendGP    = <?= json_encode(array_map(fn($m) => $m['gross_profit'], $monthly_trend ?? [])) ?>;
new Chart(document.getElementById('salesPurchaseChart'), {
    type: 'bar',
    data: { labels: trendLabels, datasets: [
        { label: 'Sales', data: trendSales, backgroundColor: '#28a745' },
        { label: 'Purchase', data: trendPurch, backgroundColor: '#fd7e14' },
    ]},
    options: { scales: { y: { beginAtZero: true } } }
});
new Chart(document.getElementById('gpTrendChart'), {
    type: 'line',
    data: { labels: trendLabels, datasets: [{ label: 'Gross Profit', data: trendGP, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,.1)', tension: .3, fill: true }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

const catLabels = <?= json_encode(array_map(fn($c) => $c->category_name, $category_sales ?? [])) ?>;
const catRevenue = <?= json_encode(array_map(fn($c) => (float)$c->revenue, $category_sales ?? [])) ?>;
const palette = ['#28a745','#007bff','#fd7e14','#dc3545','#6f42c1','#20c997','#ffc107','#17a2b8','#6610f2','#e83e8c'];
new Chart(document.getElementById('categoryPieChart'), {
    type: 'pie',
    data: { labels: catLabels, datasets: [{ data: catRevenue, backgroundColor: palette }] },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } } } }
});
new Chart(document.getElementById('topCategoryBar'), {
    type: 'bar',
    data: { labels: catLabels, datasets: [{ label: 'Sales', data: catRevenue, backgroundColor: '#007bff' }] },
    options: { indexAxis: 'y', plugins: { legend: { display: false } } }
});

const payLabels = <?= json_encode(array_map(fn($p) => ucfirst($p->payment_mode), $payment_modes ?? [])) ?>;
const payAmounts = <?= json_encode(array_map(fn($p) => (float)$p->amount, $payment_modes ?? [])) ?>;
new Chart(document.getElementById('paymentPieChart'), {
    type: 'pie',
    data: { labels: payLabels, datasets: [{ data: payAmounts, backgroundColor: palette }] },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } } } }
});
</script>

<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    .card { break-inside: avoid; box-shadow: none !important; border: 1px solid #ddd !important; }
}
.exec-kpi-card { border-left: 3px solid #dee2e6; }
</style>
