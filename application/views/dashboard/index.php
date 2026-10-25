<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- Dashboard Header -->
<?php $dash_role = $this->session->userdata('role'); ?>
<div class="ds-page-header mb-4">
    <div>
        <h1 class="ds-page-title">
            <?php if ($dash_role === 'owner'): ?>
                <i class="fas fa-crown mr-2" style="color:var(--ds-warning);font-size:.9rem;"></i>Owner Dashboard
            <?php else: ?>
                <i class="fas fa-chart-line mr-2" style="color:var(--ds-primary);font-size:.9rem;"></i>Dashboard
            <?php endif; ?>
        </h1>
        <div class="ds-page-sub">
            Welcome back, <strong><?= htmlspecialchars($this->session->userdata('name')) ?></strong>
            <?php if ($is_owner && $active_location): ?> &mdash; Viewing <strong><?= htmlspecialchars($active_location->name) ?></strong><?php elseif ($is_owner): ?> &mdash; <span style="color:var(--ds-success);">All Locations</span><?php endif; ?>
            &nbsp;·&nbsp; <?= date('l, d F Y') ?>
        </div>
    </div>
    <div class="d-flex gap-2" style="gap:8px;">
        <?php if (in_array($dash_role, ['superadmin','owner'])): ?>
        <a href="<?= base_url('estimates/create') ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-alt mr-1"></i>New Estimate</a>
        <?php endif; ?>
        <a href="<?= base_url('billing/create') ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus mr-1"></i>New Invoice</a>
    </div>
</div>

<!-- ── Row 1: Primary KPIs ── -->
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-3">
        <div class="ds-kpi kpi-blue">
            <div class="ds-kpi-icon"><i class="fas fa-receipt"></i></div>
            <div class="ds-kpi-body">
                <div class="ds-kpi-label">Today's Sales <small class="text-muted font-weight-normal">(incl. GST)</small></div>
                <div class="ds-kpi-value">₹<?= number_format($today_sales, 0) ?></div>
                <div class="ds-kpi-sub"><i class="fas fa-file-invoice" style="font-size:.65rem;"></i> <?= $today_invoice_count ?> invoices today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="ds-kpi kpi-green">
            <div class="ds-kpi-icon"><i class="fas fa-chart-line"></i></div>
            <div class="ds-kpi-body">
                <div class="ds-kpi-label">Month Sales <small class="text-muted font-weight-normal">(incl. GST)</small></div>
                <div class="ds-kpi-value">₹<?= number_format($month_sales, 0) ?></div>
                <div class="ds-kpi-sub"><?= date('F Y') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="ds-kpi kpi-sky">
            <div class="ds-kpi-icon"><i class="fas fa-coins"></i></div>
            <div class="ds-kpi-body">
                <div class="ds-kpi-label">Month Profit</div>
                <div class="ds-kpi-value">₹<?= number_format($month_profit, 0) ?></div>
                <div class="ds-kpi-sub">Gross margin this month</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="ds-kpi kpi-amber">
            <div class="ds-kpi-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="ds-kpi-body">
                <div class="ds-kpi-label">Month Purchases</div>
                <div class="ds-kpi-value">₹<?= number_format($month_purchases, 0) ?></div>
                <div class="ds-kpi-sub"><?= date('F Y') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Row 2: Secondary KPIs ── -->
<div class="row mb-3">
    <?php if (in_array($this->session->userdata('role'), ['superadmin','owner'])): ?>
    <div class="col-6 col-md-3 mb-3">
        <div class="ds-kpi kpi-purple">
            <div class="ds-kpi-icon"><i class="fas fa-file-alt"></i></div>
            <div class="ds-kpi-body">
                <div class="ds-kpi-label">Estimates (Month)</div>
                <div class="ds-kpi-value">₹<?= number_format($estimate_total_value, 0) ?></div>
                <div class="ds-kpi-sub"><?= $estimate_pending ?> pending</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="ds-kpi kpi-orange">
            <div class="ds-kpi-icon"><i class="fas fa-cash-register"></i></div>
            <div class="ds-kpi-body">
                <div class="ds-kpi-label">Cash Sales (No Bill)</div>
                <div class="ds-kpi-value">₹<?= number_format($estimate_cash_value, 0) ?></div>
                <div class="ds-kpi-sub"><?= $estimate_cash_count ?> transactions</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-6 col-md-<?= $this->session->userdata('role') === 'superadmin' ? '3' : '6' ?> mb-3">
        <div class="ds-kpi kpi-teal">
            <div class="ds-kpi-icon"><i class="fas fa-boxes"></i></div>
            <div class="ds-kpi-body">
                <div class="ds-kpi-label">Products</div>
                <div class="ds-kpi-value"><?= number_format($total_products) ?></div>
                <div class="ds-kpi-sub"><?= $total_customers ?> customers · <?= $total_suppliers ?> suppliers</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-<?= $this->session->userdata('role') === 'superadmin' ? '3' : '6' ?> mb-3">
        <div class="ds-kpi <?= $low_stock_count > 0 ? 'kpi-red' : 'kpi-green' ?>">
            <div class="ds-kpi-icon"><i class="fas fa-<?= $low_stock_count > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i></div>
            <div class="ds-kpi-body">
                <div class="ds-kpi-label">Low Stock Alert</div>
                <div class="ds-kpi-value"><?= $low_stock_count ?> items</div>
                <div class="ds-kpi-sub">
                    <?= $low_stock_count > 0
                        ? '<a href="'.base_url('reports/stock').'" style="color:var(--ds-danger);">View items →</a>'
                        : '<span style="color:var(--ds-success);">All levels OK</span>' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── PLU Stock Analytics Row ── -->
<?php if (isset($plu_stock_value)): ?>
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="card shadow-sm h-100 border-primary" style="border-left:4px solid #007bff !important;">
            <div class="card-body py-2 px-3">
                <div class="text-muted small">PLU Stock Value</div>
                <div class="h5 font-weight-bold text-primary mb-0">₹<?= number_format($plu_stock_value, 0) ?></div>
                <div class="small text-muted"><a href="<?= base_url('plu') ?>">View PLUs →</a></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card shadow-sm h-100 <?= ($plu_low_stock ?? 0) > 0 ? 'border-warning' : '' ?>" style="border-left:4px solid <?= ($plu_low_stock ?? 0) > 0 ? '#ffc107' : '#28a745' ?>;">
            <div class="card-body py-2 px-3">
                <div class="text-muted small">PLU Low Stock</div>
                <div class="h5 font-weight-bold <?= ($plu_low_stock ?? 0) > 0 ? 'text-warning' : 'text-success' ?> mb-0"><?= $plu_low_stock ?? 0 ?> PLUs</div>
                <div class="small text-muted">≤ 5 units remaining</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card shadow-sm h-100 <?= ($plu_out_of_stock ?? 0) > 0 ? 'border-danger' : '' ?>" style="border-left:4px solid <?= ($plu_out_of_stock ?? 0) > 0 ? '#dc3545' : '#28a745' ?>;">
            <div class="card-body py-2 px-3">
                <div class="text-muted small">PLU Out of Stock</div>
                <div class="h5 font-weight-bold <?= ($plu_out_of_stock ?? 0) > 0 ? 'text-danger' : 'text-success' ?> mb-0"><?= $plu_out_of_stock ?? 0 ?> PLUs</div>
                <div class="small text-muted">Zero stock</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card shadow-sm h-100 <?= ($plu_expiring_soon ?? 0) > 0 ? 'border-danger' : '' ?>" style="border-left:4px solid <?= ($plu_expiring_soon ?? 0) > 0 ? '#fd7e14' : '#28a745' ?>;">
            <div class="card-body py-2 px-3">
                <div class="text-muted small">Expiring in 30 Days</div>
                <div class="h5 font-weight-bold <?= ($plu_expiring_soon ?? 0) > 0 ? 'text-danger' : 'text-success' ?> mb-0"><?= $plu_expiring_soon ?? 0 ?> PLUs</div>
                <div class="small text-muted"><a href="<?= base_url('plu?expiring=1') ?>">Check expiry →</a></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Row 3: Charts ── -->
<div class="row mb-3">
    <div class="col-md-7 mb-3">
    <div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span style="font-weight:700;font-size:.8375rem;">Daily Sales — Last 7 Days</span>
        <a href="<?= base_url('reports/sales') ?>" class="btn btn-xs btn-outline-primary">Full Report</a>
    </div>
    <div class="card-body"><canvas id="dailyChart" height="120"></canvas></div>
    </div></div>

    <div class="col-md-5 mb-3">
    <div class="card h-100">
    <div class="card-header">
        <span style="font-weight:700;font-size:.8375rem;">Monthly Sales — 6 Months</span>
    </div>
    <div class="card-body"><canvas id="monthlyChart" height="140"></canvas></div>
    </div></div>
</div>

<!-- ── Row 4: Top Items + Low Stock ── -->
<div class="row mb-3">
    <div class="col-md-6 mb-3">
    <div class="card shadow-sm">
    <div class="card-header py-2"><b>🏆 Top Selling Items (Month)</b></div>
    <div class="card-body p-0">
    <table class="table table-sm table-hover mb-0">
    <thead class="thead-light"><tr><th>#</th><th>Product</th><th class="text-right">Qty</th><th class="text-right">Value (incl. GST)</th></tr></thead>
    <tbody>
    <?php foreach ($top_items as $i => $item): ?>
    <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($item->product_name) ?></td>
        <td class="text-right"><?= number_format($item->total_qty, 1) ?></td>
        <td class="text-right font-weight-bold">₹<?= number_format($item->total_val, 2) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($top_items)): ?><tr><td colspan="4" class="text-center text-muted">No sales this month.</td></tr><?php endif; ?>
    </tbody>
    </table>
    </div></div></div>

    <div class="col-md-6 mb-3">
    <div class="card shadow-sm <?= $low_stock_count > 0 ? 'border-danger' : '' ?>">
    <div class="card-header py-2 d-flex justify-content-between <?= $low_stock_count > 0 ? 'bg-danger text-white' : '' ?>">
        <b>⚠ Low Stock Items</b>
        <a href="<?= base_url('reports/stock') ?>" class="btn btn-xs btn-<?= $low_stock_count > 0 ? 'light' : 'outline-secondary' ?> btn-sm">View All</a>
    </div>
    <div class="card-body p-0">
    <table class="table table-sm mb-0">
    <thead class="thead-light"><tr><th>Code</th><th>Product</th><th class="text-right">Stock</th><th class="text-right">Min</th></tr></thead>
    <tbody>
    <?php foreach ($low_stock_items as $item): ?>
    <tr class="<?= $item->stock_qty <= 0 ? 'table-danger' : 'table-warning' ?>">
        <td><small><?= $item->item_code ?? '-' ?></small></td>
        <td><?= htmlspecialchars($item->product_name) ?></td>
        <td class="text-right <?= $item->stock_qty <= 0 ? 'text-danger font-weight-bold' : 'text-warning' ?>"><?= $item->stock_qty ?></td>
        <td class="text-right text-muted"><?= $item->threshold ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($low_stock_items)): ?><tr><td colspan="4" class="text-center text-success">✓ All stock levels OK.</td></tr><?php endif; ?>
    </tbody>
    </table>
    </div></div></div>
</div>

<!-- ── Row 5: Recent Activity ── -->
<div class="row">
    <div class="col-md-<?= $this->session->userdata('role') === 'superadmin' ? '7' : '12' ?> mb-3">
    <div class="card shadow-sm">
    <div class="card-header py-2 d-flex justify-content-between">
        <b>Recent Invoices</b>
        <a href="<?= base_url('billing') ?>" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body p-0">
    <table class="table table-sm table-hover mb-0">
    <thead class="thead-light"><tr><th>Invoice</th><th>Customer</th><th>Date</th><th class="text-right">Amount</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($recent_invoices as $inv): ?>
    <tr>
        <td><a href="<?= base_url('billing/invoice/'.$inv->id) ?>"><?= $inv->invoice_no ?></a></td>
        <td><?= htmlspecialchars($inv->customer_name) ?></td>
        <td><?= date('d-M', strtotime($inv->invoice_date)) ?></td>
        <td class="text-right">₹<?= number_format($inv->total_amount, 2) ?></td>
        <td><span class="status-pill status-<?= $inv->status ?>"><?= ucfirst($inv->status) ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div></div></div>

    <?php if (in_array($this->session->userdata('role'), ['superadmin','owner'])): ?>
    <div class="col-md-5 mb-3">
    <div class="card shadow-sm">
    <div class="card-header py-2 d-flex justify-content-between">
        <b>Recent Estimates</b>
        <a href="<?= base_url('estimates') ?>" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body p-0">
    <table class="table table-sm table-hover mb-0">
    <thead class="thead-light"><tr><th>Estimate</th><th>Customer</th><th class="text-right">Amount</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($recent_estimates as $e):
        $sb=['pending'=>'warning','converted'=>'info','closed_cash'=>'success','cancelled'=>'danger']; ?>
    <tr>
        <td><a href="<?= base_url('estimates/view/'.$e->id) ?>"><?= $e->estimate_no ?></a></td>
        <td><?= htmlspecialchars(substr($e->customer_name,0,15)) ?></td>
        <td class="text-right">₹<?= number_format($e->total_amount, 2) ?></td>
        <td><span class="badge badge-<?= $sb[$e->status]??'secondary' ?>" style="font-size:10px;"><?= ucfirst(str_replace('_',' ',$e->status)) ?></span></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($recent_estimates)): ?><tr><td colspan="4" class="text-center text-muted">No estimates yet.</td></tr><?php endif; ?>
    </tbody>
    </table>
    </div></div></div>
    <?php endif; ?>
</div>

<?php if ($is_owner && !empty($outlet_month_stats)): ?>
<!-- ── Owner View: Per-Outlet Performance ── -->
<hr class="my-3">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="font-weight-bold mb-0"><i class="fas fa-store mr-2 text-success"></i>Outlet Performance — <?= date('M Y') ?> <small class="text-muted font-weight-normal">(Sales incl. GST)</small></h6>
    <a href="<?= base_url('reports/sales') ?>" class="btn btn-sm btn-outline-primary">Full Sales Report</a>
</div>

<div class="row mb-3">
<?php foreach ($outlet_month_stats as $os):
    $color = $os->type === 'warehouse' ? '#007bff' : '#28a745';
    $icon  = $os->type === 'warehouse' ? 'fa-warehouse' : 'fa-store';
?>
<div class="col-6 col-md-3 mb-3">
<div class="card shadow-sm h-100" style="border-left:4px solid <?= $color ?>;">
<div class="card-body py-2 px-3">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="badge badge-<?= $os->type === 'warehouse' ? 'primary' : 'success' ?>"><?= htmlspecialchars($os->code) ?></span>
        <i class="fas <?= $icon ?> text-muted" style="font-size:.85rem;"></i>
    </div>
    <div class="font-weight-bold text-truncate small mb-0"><?= htmlspecialchars($os->location_name) ?></div>
    <div class="h5 font-weight-bold mb-0" style="color:<?= $color ?>;">₹<?= number_format($os->month_sales, 0) ?></div>
    <div class="small text-muted"><?= $os->invoice_count ?> invoices</div>
</div>
</div>
</div>
<?php endforeach; ?>
</div>

<!-- Outlet comparison chart -->
<div class="row mb-3">
    <div class="col-md-7 mb-3">
    <div class="card shadow-sm">
    <div class="card-header py-2"><b>Outlet-wise Sales — <?= date('M Y') ?></b></div>
    <div class="card-body"><canvas id="outletChart" height="120"></canvas></div>
    </div></div>

    <div class="col-md-5 mb-3">
    <div class="card shadow-sm">
    <div class="card-header py-2"><b>Top Products This Month</b></div>
    <div class="card-body p-0">
    <table class="table table-sm mb-0">
    <thead class="thead-light"><tr><th>Product</th><th>Outlet</th><th class="text-right">Value (incl. GST)</th></tr></thead>
    <tbody>
    <?php
    // Group and show top 10 products across all outlets
    $tp_all = [];
    foreach ($outlet_top_products as $tp) {
        $key = $tp->product_name;
        if (!isset($tp_all[$key])) $tp_all[$key] = ['name'=>$tp->product_name,'outlet'=>$tp->location_name,'val'=>0];
        $tp_all[$key]['val'] += $tp->total_val;
    }
    usort($tp_all, fn($a,$b) => $b['val'] <=> $a['val']);
    foreach (array_slice($tp_all, 0, 10) as $tp):
    ?>
    <tr>
        <td class="small"><?= htmlspecialchars(substr($tp['name'],0,22)) ?></td>
        <td class="small text-muted"><?= htmlspecialchars(substr($tp['outlet'],0,12)) ?></td>
        <td class="text-right small font-weight-bold">₹<?= number_format($tp['val'],0) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($tp_all)): ?><tr><td colspan="3" class="text-center text-muted">No sales data.</td></tr><?php endif; ?>
    </tbody>
    </table>
    </div>
    </div>
    </div>
</div>
<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
var dailyLabels = <?= json_encode(array_map(fn($r) => date('d M', strtotime($r->day)), $daily_sales)) ?>;
var dailyData   = <?= json_encode(array_map(fn($r) => round($r->total, 2), $daily_sales)) ?>;

var chartDefaults = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 } } },
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' }, ticks: { callback: v => '₹' + v.toLocaleString(), font: { size: 11 } } }
    }
};
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: { labels: dailyLabels, datasets: [{ label:'Sales (₹)', data:dailyData,
        backgroundColor: 'rgba(30,64,175,0.75)',
        borderColor: '#1e40af', borderWidth: 0, borderRadius: 6,
        hoverBackgroundColor: '#1e40af'
    }] },
    options: chartDefaults
});

var monthLabels = <?= json_encode(array_map(fn($r) => $r->month_label, $monthly_sales)) ?>;
var monthData   = <?= json_encode(array_map(fn($r) => round($r->total, 2), $monthly_sales)) ?>;

new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: { labels: monthLabels, datasets: [{ label:'Monthly (₹)', data: monthData,
        fill: true,
        backgroundColor: 'rgba(22,163,74,0.08)',
        borderColor: '#16a34a', borderWidth: 2,
        tension: 0.38, pointRadius: 4,
        pointBackgroundColor: '#16a34a', pointBorderColor: '#fff', pointBorderWidth: 2
    }] },
    options: chartDefaults
});

<?php if ($is_owner && !empty($outlet_month_stats)): ?>
var outletLabels = <?= json_encode(array_map(fn($r) => $r->location_name, $outlet_month_stats)) ?>;
var outletData   = <?= json_encode(array_map(fn($r) => round($r->month_sales, 2), $outlet_month_stats)) ?>;
var outletColors = <?= json_encode(array_map(fn($r) => $r->type === 'warehouse' ? 'rgba(0,123,255,0.7)' : 'rgba(40,167,69,0.7)', $outlet_month_stats)) ?>;
new Chart(document.getElementById('outletChart'), {
    type: 'bar',
    data: {
        labels: outletLabels,
        datasets: [{ label:'Month Sales (₹)', data:outletData, backgroundColor:outletColors, borderRadius:4 }]
    },
    options: {
        responsive:true,
        plugins:{legend:{display:false}},
        scales:{ y:{beginAtZero:true, ticks:{callback:v=>'₹'+v.toLocaleString()}} }
    }
});
<?php endif; ?>
</script>
