<?php // FILE: application/views/reports/scheme_sales.php
$ep = 'from=' . urlencode($from) . '&to=' . urlencode($to);
$s = $summary;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Scheme Sales Report</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Scheme Sales</small>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route' => 'reports/scheme_sales', 'export_params' => $ep, 'export_title' => $export_title]); ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="form-row align-items-end">
            <div class="col-auto form-group mb-0">
                <label class="small font-weight-bold mb-0">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col-auto form-group mb-0">
                <label class="small font-weight-bold mb-0">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col-auto form-group mb-0">
                <button class="btn btn-outline-dark btn-sm">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2">
            <div class="text-muted small">Total Sales</div>
            <div class="h5 mb-0 font-weight-bold">₹<?= number_format($s['total_sales'], 2) ?></div>
            <div class="text-muted small"><?= $s['total_invoices'] ?> invoices</div>
        </div></div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2">
            <div class="text-muted small">Scheme Sales</div>
            <div class="h5 mb-0 font-weight-bold text-success">₹<?= number_format($s['scheme_sales'], 2) ?></div>
            <div class="text-muted small"><?= $s['scheme_invoices'] ?> invoices had a scheme applied</div>
        </div></div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2">
            <div class="text-muted small">Scheme Sales % of Total</div>
            <div class="h5 mb-0 font-weight-bold text-primary"><?= number_format($s['scheme_sales_pct'], 2) ?>%</div>
            <div class="text-muted small">Non-scheme: ₹<?= number_format($s['non_scheme_sales'], 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2">
            <div class="text-muted small">Discount Given / Free Units</div>
            <div class="h5 mb-0 font-weight-bold text-danger">₹<?= number_format($s['total_discount'], 2) ?></div>
            <div class="text-muted small"><?= number_format($s['total_free_units'], 0) ?> free units · <?= $s['applications'] ?> applications</div>
        </div></div>
    </div>
</div>
<p class="text-muted small no-print" style="margin-top:-8px;">
    "Scheme Sales" = total billed value of invoices that had at least one scheme applied (not just the discounted line) —
    schemes discount at the item level and aren't stored as a separately subtractable amount, so per-line revenue attribution isn't available.
</p>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Scheme-wise Breakdown</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Scheme</th>
                        <th>Type</th>
                        <th class="text-right">Invoices</th>
                        <th class="text-right">Customers</th>
                        <th class="text-right">Discount Given</th>
                        <th class="text-right">Free Units</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($breakdown)): foreach ($breakdown as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r->scheme_name) ?></td>
                        <td><span class="badge badge-light border text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $r->scheme_type)) ?></span></td>
                        <td class="text-right"><?= $r->invoices ?></td>
                        <td class="text-right"><?= $r->customers ?></td>
                        <td class="text-right">₹<?= number_format($r->discount_given, 2) ?></td>
                        <td class="text-right"><?= number_format($r->free_units, 0) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No schemes applied in this date range.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Impact Comparison: with-scheme period vs without-scheme period ── -->
<div class="card border-0 shadow-sm no-print">
    <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">
        Impact Comparison — Running a Scheme vs Not
    </div>
    <div class="card-body">
        <form method="get" class="form-row align-items-end mb-3">
            <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
            <input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">
            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold mb-0">Pick a scheme (auto-fills Period A)</label>
                <select id="schemePicker" class="form-control form-control-sm" onchange="applySchemeDates()">
                    <option value="">— choose a scheme —</option>
                    <?php foreach ($schemes_list as $sc): ?>
                        <option value="<?= $sc->id ?>" data-start="<?= $sc->start_date ?>" data-end="<?= $sc->end_date ?>">
                            <?= htmlspecialchars($sc->name) ?> (<?= date('d-M', strtotime($sc->start_date)) ?> to <?= date('d-M-y', strtotime($sc->end_date)) ?>)<?= $sc->is_active ? '' : ' — inactive' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto form-group mb-2">
                <label class="small font-weight-bold mb-0">Period A — with scheme: From</label>
                <input type="date" name="cmp_a_from" id="cmpAFrom" class="form-control form-control-sm" value="<?= htmlspecialchars($cmp_a_from) ?>">
            </div>
            <div class="col-auto form-group mb-2">
                <label class="small font-weight-bold mb-0">To</label>
                <input type="date" name="cmp_a_to" id="cmpATo" class="form-control form-control-sm" value="<?= htmlspecialchars($cmp_a_to) ?>">
            </div>
            <div class="col-auto form-group mb-2">
                <label class="small font-weight-bold mb-0">Period B — comparison: From</label>
                <input type="date" name="cmp_b_from" class="form-control form-control-sm" value="<?= htmlspecialchars($cmp_b_from) ?>">
            </div>
            <div class="col-auto form-group mb-2">
                <label class="small font-weight-bold mb-0">To</label>
                <input type="date" name="cmp_b_to" class="form-control form-control-sm" value="<?= htmlspecialchars($cmp_b_to) ?>">
            </div>
            <div class="col-auto form-group mb-2">
                <button class="btn btn-outline-dark btn-sm">Compare</button>
            </div>
        </form>

        <?php
        $pa = $period_a; $pb = $period_b;
        $delta_sales = $pa['total_sales'] - $pb['total_sales'];
        $delta_sales_pct = $pb['total_sales'] > 0 ? round($delta_sales / $pb['total_sales'] * 100, 1) : null;
        $avg_a = $pa['total_invoices'] > 0 ? $pa['total_sales'] / $pa['total_invoices'] : 0;
        $avg_b = $pb['total_invoices'] > 0 ? $pb['total_sales'] / $pb['total_invoices'] : 0;
        ?>
        <div class="row">
            <div class="col-md-5">
                <div class="card border-success h-100">
                    <div class="card-header bg-success text-white py-1 font-weight-bold" style="font-size:.8rem;">
                        Period A (<?= date('d M', strtotime($cmp_a_from)) ?> – <?= date('d M Y', strtotime($cmp_a_to)) ?>)
                    </div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0" style="font-size:.85rem;">
                            <tr><td>Total Sales</td><td class="text-right font-weight-bold">₹<?= number_format($pa['total_sales'], 2) ?></td></tr>
                            <tr><td>Invoices</td><td class="text-right"><?= $pa['total_invoices'] ?></td></tr>
                            <tr><td>Avg Bill Value</td><td class="text-right">₹<?= number_format($avg_a, 2) ?></td></tr>
                            <tr><td>Scheme-Touched Invoices</td><td class="text-right"><?= $pa['scheme_invoices'] ?></td></tr>
                            <tr><td>Scheme Sales</td><td class="text-right">₹<?= number_format($pa['scheme_sales'], 2) ?> (<?= number_format($pa['scheme_sales_pct'], 1) ?>%)</td></tr>
                            <tr><td>Discount Given</td><td class="text-right">₹<?= number_format($pa['total_discount'], 2) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-center justify-content-center">
                <div class="text-center">
                    <div class="text-muted small">Sales Δ</div>
                    <div class="h4 mb-0 font-weight-bold <?= $delta_sales >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= $delta_sales >= 0 ? '+' : '' ?>₹<?= number_format($delta_sales, 2) ?>
                    </div>
                    <?php if ($delta_sales_pct !== null): ?>
                    <div class="<?= $delta_sales >= 0 ? 'text-success' : 'text-danger' ?> font-weight-bold">
                        <?= $delta_sales >= 0 ? '+' : '' ?><?= $delta_sales_pct ?>%
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card border-secondary h-100">
                    <div class="card-header bg-secondary text-white py-1 font-weight-bold" style="font-size:.8rem;">
                        Period B (<?= date('d M', strtotime($cmp_b_from)) ?> – <?= date('d M Y', strtotime($cmp_b_to)) ?>)
                    </div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0" style="font-size:.85rem;">
                            <tr><td>Total Sales</td><td class="text-right font-weight-bold">₹<?= number_format($pb['total_sales'], 2) ?></td></tr>
                            <tr><td>Invoices</td><td class="text-right"><?= $pb['total_invoices'] ?></td></tr>
                            <tr><td>Avg Bill Value</td><td class="text-right">₹<?= number_format($avg_b, 2) ?></td></tr>
                            <tr><td>Scheme-Touched Invoices</td><td class="text-right"><?= $pb['scheme_invoices'] ?></td></tr>
                            <tr><td>Scheme Sales</td><td class="text-right">₹<?= number_format($pb['scheme_sales'], 2) ?> (<?= number_format($pb['scheme_sales_pct'], 1) ?>%)</td></tr>
                            <tr><td>Discount Given</td><td class="text-right">₹<?= number_format($pb['total_discount'], 2) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-muted small mt-2 mb-0">
            Pick a scheme to auto-fill Period A with its actual run dates and Period B with the same-length window right before it —
            or set any two custom ranges to compare directly.
        </p>
    </div>
</div>

<script>
function applySchemeDates() {
    const sel = document.getElementById('schemePicker');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    const start = opt.dataset.start, end = opt.dataset.end;
    document.getElementById('cmpAFrom').value = start;
    document.getElementById('cmpATo').value = end;
    const days = Math.round((new Date(end) - new Date(start)) / 86400000) + 1;
    const bTo = new Date(start); bTo.setDate(bTo.getDate() - 1);
    const bFrom = new Date(bTo); bFrom.setDate(bFrom.getDate() - (days - 1));
    document.querySelector('input[name="cmp_b_to"]').value = bTo.toISOString().slice(0, 10);
    document.querySelector('input[name="cmp_b_from"]').value = bFrom.toISOString().slice(0, 10);
}
</script>
