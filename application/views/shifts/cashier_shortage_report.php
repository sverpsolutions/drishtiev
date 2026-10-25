<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 font-weight-bold"><i class="fas fa-user-times text-danger mr-2"></i>Cashier Shortage &amp; Excess Report</h4>
        <small class="text-muted">Track cash short/excess per cashier across shifts — ideal for monthly review</small>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark d-print-none"><i class="fas fa-print mr-1"></i>Print</button>
        <a href="<?= base_url('shifts/shift_reports_list') ?>" class="btn btn-sm btn-outline-secondary ml-1 d-print-none">All Shifts</a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-3 d-print-none">
    <div class="card-body py-2">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">From Date</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">To Date</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">Cashier</label>
                <select name="cashier" class="form-control form-control-sm">
                    <option value="">All Cashiers</option>
                    <?php foreach ($cashiers as $c): ?>
                    <option value="<?= $c->id ?>" <?= $filter_uid == $c->id ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">View Mode</label>
                <select name="view" class="form-control form-control-sm">
                    <option value="summary" <?= $view === 'summary' ? 'selected' : '' ?>>Summary (per cashier)</option>
                    <option value="detail"  <?= $view === 'detail'  ? 'selected' : '' ?>>Detail (per shift)</option>
                </select>
            </div>
            <!-- Month Quick-pick -->
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">Quick Month</label>
                <select class="form-control form-control-sm" id="quickMonth" onchange="applyMonth(this.value)">
                    <option value="">-- Pick Month --</option>
                    <?php
                    for ($i = 0; $i < 12; $i++) {
                        $ts  = strtotime("-$i months");
                        $val = date('Y-m', $ts);
                        $lbl = date('M Y', $ts);
                        echo "<option value=\"$val\">$lbl</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-2 mb-0">
                <button type="submit" class="btn btn-danger btn-sm btn-block"><i class="fas fa-search mr-1"></i>Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Report Header for print -->
<div class="d-none d-print-block text-center mb-3">
    <h5 class="font-weight-bold mb-0"><?= htmlspecialchars($company['company_name'] ?? '') ?></h5>
    <div class="small">Cashier Shortage &amp; Excess Report | <?= date('d M Y', strtotime($from)) ?> to <?= date('d M Y', strtotime($to)) ?></div>
    <div class="small">Printed: <?= date('d-M-Y H:i') ?></div>
</div>

<!-- Grand Summary Cards -->
<div class="row mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-0 bg-dark text-white text-center py-2 shadow-sm">
            <div class="small text-muted">Total Shifts</div>
            <div class="font-weight-bold h4 mb-0"><?= $grand_shifts ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 bg-success text-white text-center py-2 shadow-sm">
            <div class="small">Gross Sales</div>
            <div class="font-weight-bold h4 mb-0">₹<?= number_format($grand_sales, 0) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 bg-danger text-white text-center py-2 shadow-sm">
            <div class="small">Total Shortage</div>
            <div class="font-weight-bold h4 mb-0">₹<?= number_format($grand_short, 2) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 bg-warning text-dark text-center py-2 shadow-sm">
            <div class="small">Total Excess</div>
            <div class="font-weight-bold h4 mb-0">₹<?= number_format($grand_excess, 2) ?></div>
        </div>
    </div>
</div>

<?php if ($view === 'summary'): ?>
<!-- ═══ SUMMARY VIEW — per cashier rolled up ═══ -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-dark text-white font-weight-bold py-2">
        Cashier-wise Summary
        <span class="badge badge-secondary ml-2" style="font-size:.72rem;"><?= date('d M Y', strtotime($from)) ?> → <?= date('d M Y', strtotime($to)) ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($summary)): ?>
            <div class="text-center py-5 text-muted">No closed shifts found for the selected period.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th class="pl-3">#</th>
                        <th>Cashier</th>
                        <th class="text-center">Shifts</th>
                        <th class="text-center">Bills</th>
                        <th class="text-right">Gross Sales</th>
                        <th class="text-right">Sales / Shift</th>
                        <th class="text-right text-danger">Total Shortage</th>
                        <th class="text-right text-success">Total Excess</th>
                        <th class="text-right">Net Diff</th>
                        <th class="text-right text-danger">Worst Single</th>
                        <th class="text-center d-print-none">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($summary as $i => $row):
                    $net = (float)$row['net_diff'];
                    $netClass = $net < 0 ? 'text-danger' : ($net > 0 ? 'text-success' : 'text-muted');
                    $shortPct = $row['total_sales'] > 0 ? ($row['short_total'] / $row['total_sales'] * 100) : 0;
                ?>
                <tr>
                    <td class="pl-3 align-middle text-muted"><?= $i+1 ?></td>
                    <td class="align-middle font-weight-bold"><?= htmlspecialchars($row['cashier_name']) ?></td>
                    <td class="text-center align-middle"><?= $row['shifts'] ?></td>
                    <td class="text-center align-middle"><?= number_format($row['bills']) ?></td>
                    <td class="text-right align-middle font-weight-bold">₹<?= number_format($row['total_sales'], 0) ?></td>
                    <td class="text-right align-middle text-muted">₹<?= $row['shifts'] > 0 ? number_format($row['total_sales'] / $row['shifts'], 0) : 0 ?></td>
                    <td class="text-right align-middle font-weight-bold <?= $row['short_total'] > 0 ? 'text-danger' : 'text-muted' ?>">
                        <?php if ($row['short_total'] > 0): ?>
                            ₹<?= number_format($row['short_total'], 2) ?>
                            <div class="progress mt-1" style="height:4px;">
                                <div class="progress-bar bg-danger" style="width:<?= min(100, $shortPct * 20) ?>%"></div>
                            </div>
                            <small class="text-muted"><?= number_format($shortPct, 2) ?>% of sales</small>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-right align-middle <?= $row['excess_total'] > 0 ? 'text-success font-weight-bold' : 'text-muted' ?>">
                        <?= $row['excess_total'] > 0 ? '₹'.number_format($row['excess_total'], 2) : '—' ?>
                    </td>
                    <td class="text-right align-middle font-weight-bold <?= $netClass ?>">
                        <?= $net >= 0 ? '+' : '' ?>₹<?= number_format(abs($net), 2) ?>
                    </td>
                    <td class="text-right align-middle <?= $row['worst_short'] > 0 ? 'text-danger' : 'text-muted' ?>">
                        <?php if ($row['worst_short'] > 0): ?>
                            ₹<?= number_format($row['worst_short'], 2) ?>
                            <div><a href="<?= base_url('shifts/shift_thermal/'.$row['worst_shift']) ?>" target="_blank" class="small d-print-none">(Shift #<?= $row['worst_shift'] ?>)</a></div>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-center align-middle d-print-none">
                        <a href="<?= base_url('shifts/cashier_shortage_report?from='.$from.'&to='.$to.'&cashier='.$row['cashier_id'].'&view=detail') ?>"
                           class="btn btn-xs btn-outline-primary" title="Drill into shifts">
                            <i class="fas fa-list"></i> Shifts
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light font-weight-bold" style="font-size:.85rem;">
                    <tr>
                        <td colspan="2" class="pl-3 text-right">TOTALS →</td>
                        <td class="text-center"><?= $grand_shifts ?></td>
                        <td class="text-center"><?= number_format(array_sum(array_column($summary,'bills'))) ?></td>
                        <td class="text-right">₹<?= number_format($grand_sales, 0) ?></td>
                        <td></td>
                        <td class="text-right text-danger">₹<?= number_format($grand_short, 2) ?></td>
                        <td class="text-right text-success">₹<?= number_format($grand_excess, 2) ?></td>
                        <td class="text-right <?= ($grand_excess - $grand_short) >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= ($grand_excess - $grand_short) >= 0 ? '+' : '' ?>₹<?= number_format(abs($grand_excess - $grand_short), 2) ?>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Day-wise trend chart -->
<?php if (!empty($daily)): ?>
<div class="card border-0 shadow-sm mb-4 d-print-none">
    <div class="card-header bg-dark text-white font-weight-bold py-2">Daily Shortage &amp; Excess Trend</div>
    <div class="card-body">
        <canvas id="trendChart" height="80"></canvas>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- ═══ DETAIL VIEW — every shift row ═══ -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-dark text-white font-weight-bold py-2 d-flex justify-content-between">
        <span>Shift-by-Shift Detail</span>
        <?php if ($filter_uid): ?>
            <span class="badge badge-info"><?= htmlspecialchars($shifts[0]->cashier_name ?? '') ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($shifts)): ?>
            <div class="text-center py-5 text-muted">No closed shifts found.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:.83rem;">
                <thead class="thead-dark">
                    <tr>
                        <th class="pl-3">Shift #</th>
                        <th>Date</th>
                        <th>Cashier</th>
                        <th>Counter</th>
                        <th>Type</th>
                        <th class="text-center">Bills</th>
                        <th class="text-right">Gross Sales</th>
                        <th class="text-right">Opening Cash</th>
                        <th class="text-right">Expected</th>
                        <th class="text-right">Declared</th>
                        <th class="text-right text-danger">SHORT</th>
                        <th class="text-right text-success">EXCESS</th>
                        <th class="text-center d-print-none">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $prev_cashier = null;
                $sub_short = 0; $sub_excess = 0; $sub_sales = 0; $sub_count = 0;
                foreach ($shifts as $s):
                    // cashier subtotal break
                    if ($prev_cashier !== null && $prev_cashier !== $s->cashier_id):
                ?>
                <tr class="bg-light font-weight-bold" style="font-size:.8rem;border-top:2px solid #dee2e6;">
                    <td colspan="6" class="pl-3 text-right text-muted">Subtotal →</td>
                    <td class="text-right">₹<?= number_format($sub_sales, 0) ?></td>
                    <td colspan="2"></td>
                    <td></td>
                    <td class="text-right text-danger">₹<?= number_format($sub_short, 2) ?></td>
                    <td class="text-right text-success">₹<?= number_format($sub_excess, 2) ?></td>
                    <td class="d-print-none"></td>
                </tr>
                <?php $sub_short=0; $sub_excess=0; $sub_sales=0; $sub_count=0; endif;
                    $prev_cashier = $s->cashier_id;
                    $sub_short  += (float)$s->short_amount;
                    $sub_excess += (float)$s->excess_amount;
                    $sub_sales  += (float)$s->total_sales;
                    $sub_count++;
                    $expected = $s->opening_cash + $s->system_cash;
                ?>
                <tr>
                    <td class="pl-3 align-middle font-weight-bold">#<?= $s->id ?></td>
                    <td class="align-middle"><?= date('d-M-Y', strtotime($s->opened_at)) ?><br><small class="text-muted"><?= date('H:i', strtotime($s->opened_at)) ?>–<?= $s->closed_at ? date('H:i', strtotime($s->closed_at)) : '—' ?></small></td>
                    <td class="align-middle font-weight-bold"><?= htmlspecialchars($s->cashier_name) ?></td>
                    <td class="align-middle"><?= htmlspecialchars($s->system_name ?? '—') ?></td>
                    <td class="align-middle"><span class="badge badge-light border"><?= htmlspecialchars($s->shift_name ?? 'General') ?></span></td>
                    <td class="text-center align-middle"><?= $s->bill_count ?></td>
                    <td class="text-right align-middle">₹<?= number_format($s->total_sales, 0) ?></td>
                    <td class="text-right align-middle text-muted">₹<?= number_format($s->opening_cash, 2) ?></td>
                    <td class="text-right align-middle text-primary font-weight-bold">₹<?= number_format($expected, 2) ?></td>
                    <td class="text-right align-middle">₹<?= number_format($s->actual_cash, 2) ?></td>
                    <td class="text-right align-middle <?= $s->short_amount > 0 ? 'text-danger font-weight-bold' : 'text-muted' ?>">
                        <?= $s->short_amount > 0 ? '₹'.number_format($s->short_amount, 2) : '—' ?>
                    </td>
                    <td class="text-right align-middle <?= $s->excess_amount > 0 ? 'text-success font-weight-bold' : 'text-muted' ?>">
                        <?= $s->excess_amount > 0 ? '₹'.number_format($s->excess_amount, 2) : '—' ?>
                    </td>
                    <td class="text-center align-middle d-print-none">
                        <a href="<?= base_url('shifts/shift_thermal/'.$s->id) ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-receipt"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <!-- last cashier subtotal -->
                <?php if (!empty($shifts)): ?>
                <tr class="bg-light font-weight-bold" style="font-size:.8rem;border-top:2px solid #dee2e6;">
                    <td colspan="6" class="pl-3 text-right text-muted">Subtotal →</td>
                    <td class="text-right">₹<?= number_format($sub_sales, 0) ?></td>
                    <td colspan="2"></td>
                    <td></td>
                    <td class="text-right text-danger">₹<?= number_format($sub_short, 2) ?></td>
                    <td class="text-right text-success">₹<?= number_format($sub_excess, 2) ?></td>
                    <td class="d-print-none"></td>
                </tr>
                <?php endif; ?>
                </tbody>
                <tfoot class="font-weight-bold bg-dark text-white" style="font-size:.85rem;">
                    <tr>
                        <td colspan="5" class="pl-3 text-right">GRAND TOTAL →</td>
                        <td class="text-center"><?= array_sum(array_column($shifts,'bill_count')) ?></td>
                        <td class="text-right">₹<?= number_format($grand_sales, 0) ?></td>
                        <td colspan="2"></td>
                        <td></td>
                        <td class="text-right text-danger">₹<?= number_format($grand_short, 2) ?></td>
                        <td class="text-right text-success">₹<?= number_format($grand_excess, 2) ?></td>
                        <td class="d-print-none"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Closing remarks / notes legend -->
<?php if ($view === 'detail' && !empty($shifts)): ?>
<div class="card border-0 shadow-sm mb-4 d-print-none">
    <div class="card-header py-2 font-weight-bold" style="background:#fff3cd;">Shift Remarks</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:.82rem;">
            <thead class="thead-light"><tr><th>Shift #</th><th>Cashier</th><th>Date</th><th>Closing Remarks</th></tr></thead>
            <tbody>
            <?php foreach ($shifts as $s): if (empty($s->close_remarks)) continue; ?>
            <tr>
                <td>#<?= $s->id ?></td>
                <td><?= htmlspecialchars($s->cashier_name) ?></td>
                <td><?= date('d-M-Y', strtotime($s->opened_at)) ?></td>
                <td class="font-italic text-muted"><?= htmlspecialchars($s->close_remarks) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
@media print {
    .d-print-none { display:none !important; }
    .d-none.d-print-block { display:block !important; }
    .card { box-shadow:none !important; border:1px solid #dee2e6 !important; }
    body  { font-size: 11px; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3/dist/chart.min.js"></script>
<script>
function applyMonth(val) {
    if (!val) return;
    var parts = val.split('-');
    var y = parseInt(parts[0]), m = parseInt(parts[1]);
    var last = new Date(y, m, 0).getDate();
    document.querySelector('[name=from]').value = y + '-' + String(m).padStart(2,'0') + '-01';
    document.querySelector('[name=to]').value   = y + '-' + String(m).padStart(2,'0') + '-' + String(last).padStart(2,'0');
}

<?php if (!empty($daily) && $view === 'summary'): ?>
(function() {
    var labels  = <?= json_encode(array_keys($daily)) ?>;
    var shorts  = <?= json_encode(array_values(array_map(fn($d) => round($d['short'],2),  $daily))) ?>;
    var excesses= <?= json_encode(array_values(array_map(fn($d) => round($d['excess'],2), $daily))) ?>;
    var ctx = document.getElementById('trendChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Shortage (₹)',  data: shorts,   backgroundColor: 'rgba(220,53,69,.7)',  borderColor: '#dc3545', borderWidth:1 },
                { label: 'Excess (₹)',    data: excesses, backgroundColor: 'rgba(40,167,69,.6)',  borderColor: '#28a745', borderWidth:1 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position:'top' }, tooltip: { callbacks: { label: ctx => '₹' + ctx.raw.toFixed(2) } } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => '₹'+v } } }
        }
    });
})();
<?php endif; ?>
</script>
