<?php // FILE: application/views/reports/outlet_sales.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Outlet-wise Sales Report</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Outlet Sales</small>
    </div>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm no-print">&#128438; Print</button>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="form-inline">
            <label class="mr-2" style="font-size:.85rem;">From</label>
            <input type="date" name="from" class="form-control form-control-sm mr-2" value="<?= $from ?>">
            <label class="mr-2" style="font-size:.85rem;">To</label>
            <input type="date" name="to" class="form-control form-control-sm mr-2" value="<?= $to ?>">
            <button type="submit" class="btn btn-dark btn-sm">Apply</button>
        </form>
    </div>
</div>

<?php
// Grand totals
$grand_invoices = $grand_subtotal = $grand_discount = $grand_gst = $grand_total = $grand_paid = $grand_due = 0;
foreach ($outlet_summary as $os) {
    $grand_invoices += $os->invoice_count;
    $grand_subtotal += $os->subtotal;
    $grand_discount += $os->total_discount;
    $grand_gst      += $os->total_gst;
    $grand_total    += $os->total_amount;
    $grand_paid     += $os->paid_amount;
    $grand_due      += $os->due_amount;
}
$summary_cards = [
    ['Total Outlets', count($outlet_summary), false, ''],
    ['Total Invoices', $grand_invoices, false, ''],
    ['Gross Sales', $grand_subtotal, true, ''],
    ['Total Discount', $grand_discount, true, 'text-warning'],
    ['Total GST', $grand_gst, true, 'text-info'],
    ['Net Sales', $grand_total, true, 'text-success font-weight-bold'],
    ['Collected', $grand_paid, true, 'text-success'],
    ['Due', $grand_due, true, 'text-danger'],
];
?>

<!-- Grand totals -->
<div class="row mb-3">
<?php foreach ($summary_cards as [$label, $val, $fmt, $cls]): ?>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <p class="text-muted mb-0" style="font-size:.75rem;"><?= $label ?></p>
                <p class="mb-0 <?= $cls ?>" style="font-size:1rem;font-weight:500;">
                    <?= $fmt ? '&#8377; '.number_format($val,2) : $val ?>
                </p>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- Comparison table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Outlet Comparison — <?= date('d M Y', strtotime($from)) ?> to <?= date('d M Y', strtotime($to)) ?></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.83rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Outlet</th>
                        <th>Code</th>
                        <th>Type</th>
                        <th class="text-right">Invoices</th>
                        <th class="text-right">Subtotal</th>
                        <th class="text-right">Discount</th>
                        <th class="text-right">GST</th>
                        <th class="text-right">Net Sales</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Due</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($outlet_summary)): foreach ($outlet_summary as $os): ?>
                <tr>
                    <td class="font-weight-bold"><?= htmlspecialchars($os->location_name) ?></td>
                    <td><span class="badge badge-secondary"><?= htmlspecialchars($os->code) ?></span></td>
                    <td><?= ucfirst($os->type) ?></td>
                    <td class="text-right"><?= $os->invoice_count ?></td>
                    <td class="text-right">&#8377; <?= number_format($os->subtotal,2) ?></td>
                    <td class="text-right text-warning">&#8377; <?= number_format($os->total_discount,2) ?></td>
                    <td class="text-right text-info">&#8377; <?= number_format($os->total_gst,2) ?></td>
                    <td class="text-right font-weight-bold text-success">&#8377; <?= number_format($os->total_amount,2) ?></td>
                    <td class="text-right text-success">&#8377; <?= number_format($os->paid_amount,2) ?></td>
                    <td class="text-right <?= $os->due_amount > 0 ? 'text-danger' : '' ?>">&#8377; <?= number_format($os->due_amount,2) ?></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No outlet data for this period.</td></tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($outlet_summary)): ?>
                <tfoot class="bg-light font-weight-bold">
                    <tr>
                        <td colspan="3">TOTAL</td>
                        <td class="text-right"><?= $grand_invoices ?></td>
                        <td class="text-right">&#8377; <?= number_format($grand_subtotal,2) ?></td>
                        <td class="text-right text-warning">&#8377; <?= number_format($grand_discount,2) ?></td>
                        <td class="text-right text-info">&#8377; <?= number_format($grand_gst,2) ?></td>
                        <td class="text-right text-success">&#8377; <?= number_format($grand_total,2) ?></td>
                        <td class="text-right text-success">&#8377; <?= number_format($grand_paid,2) ?></td>
                        <td class="text-right text-danger">&#8377; <?= number_format($grand_due,2) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Chart -->
<?php if (!empty($outlet_summary)): ?>
<div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Outlet Sales Comparison (Chart)</div>
    <div class="card-body">
        <canvas id="outletChart" height="80"></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3/dist/chart.min.js"></script>
<script>
(function(){
    var labels = [<?php foreach ($outlet_summary as $os) echo '"'.addslashes($os->location_name).'",'; ?>];
    var sales  = [<?php foreach ($outlet_summary as $os) echo (float)$os->total_amount.','; ?>];
    var paid   = [<?php foreach ($outlet_summary as $os) echo (float)$os->paid_amount.','; ?>];
    var ctx = document.getElementById('outletChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Net Sales (₹)', data: sales, backgroundColor: 'rgba(40,167,69,0.7)' },
                { label: 'Collected (₹)', data: paid,  backgroundColor: 'rgba(0,123,255,0.7)' }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });
})();
</script>
<?php endif; ?>

<!-- Per-outlet top 5 products -->
<?php if (!empty($outlet_top)): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Top 5 Products per Outlet</div>
    <div class="card-body p-0">
        <?php
        // Group by outlet
        $by_outlet = [];
        foreach ($outlet_top as $ot) {
            $key = $ot->location_name . ' [' . $ot->code . ']';
            if (!isset($by_outlet[$key])) $by_outlet[$key] = [];
            if (count($by_outlet[$key]) < 5) $by_outlet[$key][] = $ot;
        }
        foreach ($by_outlet as $loc_label => $items):
        ?>
        <div class="px-3 pt-3">
            <strong style="font-size:.85rem;"><?= htmlspecialchars($loc_label) ?></strong>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-2" style="font-size:.8rem;">
                <thead class="thead-light">
                    <tr><th>#</th><th>Product</th><th>Code</th><th class="text-right">Qty</th><th class="text-right">Revenue</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($item->product_name) ?></td>
                    <td><?= htmlspecialchars($item->item_code) ?></td>
                    <td class="text-right"><?= number_format($item->total_qty,2) ?></td>
                    <td class="text-right font-weight-bold">&#8377; <?= number_format($item->total_val,2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<style>@media print { .no-print { display:none; } }</style>
