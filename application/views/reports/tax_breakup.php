<?php
// ============================================================
// FILE: application/views/reports/tax_breakup.php
// ============================================================
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Tax Breakup Report</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Tax Breakup</small>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/tax_breakup','export_params'=>$export_params??'','export_title'=>$export_title??'Tax Breakup Report']); ?>
    </div>
</div>

<!-- Date filter -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="form-inline flex-wrap">
            <label class="mr-2" style="font-size:.85rem;">From</label>
            <input type="date" name="from" class="form-control form-control-sm mr-2" value="<?= $from ?>">
            <label class="mr-2" style="font-size:.85rem;">To</label>
            <input type="date" name="to" class="form-control form-control-sm mr-2" value="<?= $to ?>">
            <select name="location_id" class="form-control form-control-sm mr-2" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
                <?php if (count($locations ?? []) > 1): ?>
                <option value="">All Outlets</option>
                <?php endif; ?>
                <?php foreach ($locations ?? [] as $loc): ?>
                <option value="<?= $loc->id ?>" <?= ($location_id ?? 0) == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-dark btn-sm">Apply</button>
        </form>
    </div>
</div>

<?php if (!empty($other_txbl) || !empty($other_tax)): ?>
<div class="alert alert-warning py-2 mb-3 no-print" style="font-size:.83rem;">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    &#8377; <?= number_format($other_txbl,2) ?> taxable (&#8377; <?= number_format($other_tax,2) ?> tax) is billed at GST rates other than 0/5/18/40% — it's included in <strong>Total Sales</strong> and <strong>Basic Cost</strong> below, but not broken into its own column.
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 tax-breakup-table" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Date</th>
                        <th class="text-right">Total Sales</th>
                        <th class="text-right">GST 0%</th>
                        <th class="text-right">Taxable 5%</th>
                        <th class="text-right">Tax Amount 5%</th>
                        <th class="text-right">Taxable 18%</th>
                        <th class="text-right">Tax Amount 18%</th>
                        <th class="text-right">Taxable 40%</th>
                        <th class="text-right">Tax Amount 40%</th>
                        <th class="text-right">Basic Cost</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($rows)): foreach ($rows as $row): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($row['date'])) ?></td>
                        <td class="text-right font-weight-bold">&#8377; <?= number_format($row['total_sales'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($row['gst0'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($row['txbl5'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($row['tax5'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($row['txbl18'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($row['tax18'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($row['txbl40'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($row['tax40'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($row['basic_cost'],2) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No sales in this period.</td></tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($rows)): ?>
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td>TOTAL</td>
                        <td class="text-right">&#8377; <?= number_format($totals['total_sales'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($totals['gst0'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($totals['txbl5'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($totals['tax5'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($totals['txbl18'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($totals['tax18'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($totals['txbl40'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($totals['tax40'],2) ?></td>
                        <td class="text-right">&#8377; <?= number_format($totals['basic_cost'],2) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<div class="text-muted mt-2 no-print" style="font-size:.78rem;">
    Basic Cost = GST 0% + Taxable 5% + Taxable 18% + Taxable 40% (total taxable value before tax, for the day). Figures are net of refunds/returns — a refund reduces the total on the date it was processed, not the original invoice's date.
</div>

<style>
    .tax-breakup-table, .tax-breakup-table th, .tax-breakup-table td {
        border: 1px solid #e3e6ea !important;
        border-collapse: collapse;
    }
    .tax-breakup-table thead th {
        border-color: #4a4a4a !important;
    }
</style>
