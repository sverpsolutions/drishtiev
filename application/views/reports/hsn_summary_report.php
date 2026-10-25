<div class="container-fluid mt-3">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-file-invoice text-primary"></i> HSN Summary Report</h4>
    <div>
        <a href="<?= base_url('reports/export_hsn_summary_report?from='.$from.'&to='.$to.'&gst_type='.$gst_type.'&location_id='.$location_id) ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export Excel</a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark no-print"><i class="fas fa-print"></i> Print</button>
    </div>
</div>

<!-- Filter -->
<div class="card shadow-sm mb-3 no-print">
<div class="card-body py-2">
<form method="get" class="form-inline flex-wrap">
    <label class="mr-2">From:</label>
    <input type="date" name="from" class="form-control form-control-sm mr-2" value="<?= $from ?>">
    <label class="mr-2">To:</label>
    <input type="date" name="to" class="form-control form-control-sm mr-2" value="<?= $to ?>">
    <select name="location_id" class="form-control form-control-sm mr-2" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
        <?php if (count($locations ?? []) > 1): ?>
        <option value="">All Outlets</option>
        <?php endif; ?>
        <?php foreach ($locations ?? [] as $loc): ?>
        <option value="<?= $loc->id ?>" <?= ($location_id ?? 0) == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="gst_type" class="form-control form-control-sm mr-2" style="min-width:120px;">
        <option value="all" <?= $gst_type === 'all' ? 'selected' : '' ?>>All Sales</option>
        <option value="b2b" <?= $gst_type === 'b2b' ? 'selected' : '' ?>>B2B Only</option>
        <option value="b2c" <?= $gst_type === 'b2c' ? 'selected' : '' ?>>B2C Only</option>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    <a href="<?= base_url('reports/gstr1') ?>" class="btn btn-outline-secondary btn-sm ml-2">GSTR-1 →</a>
</form>
</div>
</div>

<div class="alert alert-secondary small no-print mb-3">
    <i class="fas fa-info-circle mr-1"></i> Gross outward sales only — not netted against sales returns/credit notes (matching how Table 12 is filed in GSTR-1; credit notes are reported separately). Cess is always ₹0.00 — this system doesn't track cess anywhere, so there's nothing to report. HSN descriptions fall back to the HSN code itself when it isn't registered in HSN Master.
</div>

<div class="card shadow-sm">
<div class="card-body">
    <h5 class="mb-1">Summary For HSN(12) - <?= ['b2b' => 'B2B Sales', 'b2c' => 'B2C Sales', 'all' => 'All Sales'][$gst_type] ?? 'All Sales' ?></h5>
    <p class="text-muted small mb-3"><?= date('d M Y', strtotime($from)) ?> to <?= date('d M Y', strtotime($to)) ?></p>

    <div class="table-responsive mb-3">
        <table class="table table-bordered table-sm mb-0" style="font-size:.82rem;">
            <thead class="thead-light">
                <tr>
                    <th>No. of HSN</th><th></th><th class="text-right">Total Value</th><th></th>
                    <th class="text-right">Total Taxable Value</th><th class="text-right">Total Integrated Tax</th>
                    <th class="text-right">Total Central Tax</th><th class="text-right">Total State/UT Tax</th>
                    <th class="text-right">Total Cess</th>
                </tr>
            </thead>
            <tbody>
                <tr class="font-weight-bold">
                    <td><?= count($rows) ?></td><td></td>
                    <td class="text-right">₹<?= number_format($totals['value'], 2) ?></td><td></td>
                    <td class="text-right">₹<?= number_format($totals['taxable'], 2) ?></td>
                    <td class="text-right">₹<?= number_format($totals['igst'], 2) ?></td>
                    <td class="text-right">₹<?= number_format($totals['cgst'], 2) ?></td>
                    <td class="text-right">₹<?= number_format($totals['sgst'], 2) ?></td>
                    <td class="text-right">₹0.00</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0" style="font-size:.82rem;">
            <thead class="thead-dark">
                <tr>
                    <th>HSN</th><th>Description</th><th>UQC</th><th class="text-right">Total Quantity</th>
                    <th class="text-right">Total Value</th><th class="text-right">Rate</th><th class="text-right">Taxable Value</th>
                    <th class="text-right">Integrated Tax Amount</th><th class="text-right">Central Tax Amount</th>
                    <th class="text-right">State/UT Tax Amount</th><th class="text-right">Cess Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($rows)): foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row->hsn_code) ?></td>
                    <td><?= htmlspecialchars($row->hsn_desc) ?></td>
                    <td><?= htmlspecialchars($row->uqc) ?></td>
                    <td class="text-right"><?= number_format((float)$row->qty, 2) ?></td>
                    <td class="text-right"><?= number_format((float)$row->total_value, 2) ?></td>
                    <td class="text-right"><?= number_format((float)$row->rate, 2) ?></td>
                    <td class="text-right"><?= number_format((float)$row->taxable_value, 2) ?></td>
                    <td class="text-right"><?= number_format((float)$row->igst, 2) ?></td>
                    <td class="text-right"><?= number_format((float)$row->cgst, 2) ?></td>
                    <td class="text-right"><?= number_format((float)$row->sgst, 2) ?></td>
                    <td class="text-right">0.00</td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="11" class="text-center text-muted py-3">No sales in this period.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    .card { box-shadow: none !important; }
}
</style>
