<?php // Scheme Performance Report ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-bar-chart-fill text-success mr-2"></i>Scheme Performance Report</h4>
        <small class="text-muted">Analyse scheme effectiveness, discount given, and party-wise usage</small>
    </div>
    <a href="<?= base_url('schemes') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list mr-1"></i>Schemes</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end">
            <div class="col-md-3"><label class="form-label mb-1 small">From</label><input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>"></div>
            <div class="col-md-3"><label class="form-label mb-1 small">To</label><input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100 mt-3"><i class="bi bi-search mr-1"></i>Filter</button></div>
            <div class="col-md-2"><button class="btn btn-outline-success btn-sm w-100 mt-3" type="button" onclick="exportReport()"><i class="bi bi-file-excel mr-1"></i>Export</button></div>
        </form>
    </div>
</div>

<?php
$total_discount = array_sum(array_column(array_map('get_object_vars', $performance), 'total_discount'));
$total_invoices = array_sum(array_column(array_map('get_object_vars', $performance), 'invoices_count'));
$total_free     = array_sum(array_column(array_map('get_object_vars', $performance), 'total_free_qty'));
?>
<div class="row mb-3">
    <div class="col-md-4"><div class="card bg-danger text-white shadow-sm"><div class="card-body py-2 text-center"><div class="small">Total Discount Given</div><div class="h5 font-weight-bold">&#8377;<?= number_format($total_discount,2) ?></div></div></div></div>
    <div class="col-md-4"><div class="card bg-primary text-white shadow-sm"><div class="card-body py-2 text-center"><div class="small">Invoices with Schemes</div><div class="h5 font-weight-bold"><?= number_format($total_invoices) ?></div></div></div></div>
    <div class="col-md-4"><div class="card bg-success text-white shadow-sm"><div class="card-body py-2 text-center"><div class="small">Total Free Units Given</div><div class="h5 font-weight-bold"><?= number_format($total_free,0) ?></div></div></div></div>
</div>

<!-- Scheme Performance Table -->
<div class="card shadow-sm mb-3">
    <div class="card-header"><i class="bi bi-trophy mr-1"></i>Scheme Performance (<?= $from ?> to <?= $to ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0" id="perfTable">
                <thead class="table-success">
                    <tr>
                        <th>#</th><th>Scheme Name</th><th>Type</th>
                        <th class="text-right">Invoices</th><th class="text-right">Customers</th>
                        <th class="text-right">Eligible Value</th>
                        <th class="text-right">Discount Given</th>
                        <th class="text-right">Free Units</th>
                        <th class="text-right">Cost %</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($performance)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No scheme data for this period</td></tr>
                <?php else: ?>
                <?php foreach ($performance as $i => $p): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><a href="<?= base_url('schemes/view/'.$p->scheme_id) ?>" class="font-weight-bold"><?= htmlspecialchars($p->scheme_name) ?></a></td>
                        <td><span class="badge badge-primary"><?= htmlspecialchars($p->type_label) ?></span></td>
                        <td class="text-right"><?= number_format($p->invoices_count) ?></td>
                        <td class="text-right"><?= number_format($p->customers_count) ?></td>
                        <td class="text-right">&#8377;<?= number_format($p->total_eligible_value,2) ?></td>
                        <td class="text-right font-weight-bold text-danger">&#8377;<?= number_format($p->total_discount,2) ?></td>
                        <td class="text-right"><?= number_format($p->total_free_qty,0) ?></td>
                        <td class="text-right small">
                            <?= $p->total_eligible_value > 0 ? number_format(($p->total_discount/$p->total_eligible_value)*100, 2).'%' : '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Applications Log -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-clock-history mr-1"></i>Recent Applications Log</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Invoice</th><th>Customer</th><th>Scheme</th><th>Type</th><th class="text-right">Discount</th><th class="text-right">Free Qty</th><th>Free Item</th></tr></thead>
                <tbody>
                <?php if (empty($recent_log)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-3">No applications in this period</td></tr>
                <?php else: ?>
                <?php foreach ($recent_log as $l): ?>
                    <tr>
                        <td><?= date('d/m/Y',strtotime($l->applied_at)) ?></td>
                        <td><?= htmlspecialchars($l->invoice_no) ?></td>
                        <td><?= htmlspecialchars($l->customer_name) ?></td>
                        <td><?= htmlspecialchars($l->scheme_name) ?></td>
                        <td><span class="badge badge-secondary"><?= $l->scheme_type ?></span></td>
                        <td class="text-right"><?= $l->discount_given > 0 ? '&#8377;'.number_format($l->discount_given,2) : '—' ?></td>
                        <td class="text-right"><?= $l->free_qty > 0 ? rtrim(rtrim($l->free_qty,'0'),'.') : '—' ?></td>
                        <td><?= htmlspecialchars($l->free_product_name ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportReport() {
    const rows = document.querySelectorAll('#perfTable tr');
    let csv = [];
    rows.forEach(r => {
        let cols = Array.from(r.querySelectorAll('th,td'));
        csv.push(cols.map(c => '"'+c.innerText.trim().replace(/"/g,'""')+'"').join(','));
    });
    const blob = new Blob([csv.join('\n')], {type:'text/csv'});
    const a = document.createElement('a'); a.href=URL.createObjectURL(blob);
    a.download='scheme_report_<?= $from ?>_<?= $to ?>.csv'; a.click();
}
</script>
