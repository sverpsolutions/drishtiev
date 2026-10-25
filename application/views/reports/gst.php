<?php // FILE: application/views/reports/gst.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">GST Report (GSTR-1 Summary)</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / GST</small>
    </div>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm no-print">&#128438; Print</button>
</div>

<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="form-inline flex-wrap">
            <label class="mr-2" style="font-size:.85rem;">Month</label>
            <input type="month" name="month" class="form-control form-control-sm mr-2" value="<?= $month ?>">
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

<!-- GST Summary by Rate -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">
        GSTR-1 Tax Summary — <?= date('F Y', strtotime($month.'-01')) ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>GST Rate</th>
                        <th class="text-right">Invoices</th>
                        <th class="text-right">Taxable Value</th>
                        <th class="text-right">CGST</th>
                        <th class="text-right">SGST</th>
                        <th class="text-right">IGST</th>
                        <th class="text-right">Total Tax</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $tot_taxable = $tot_cgst = $tot_sgst = $tot_igst = $tot_tax = 0;
                if (!empty($gst_summary)): foreach ($gst_summary as $g):
                    $tot_taxable += $g->taxable_value;
                    $tot_cgst    += $g->cgst;
                    $tot_sgst    += $g->sgst;
                    $tot_igst    += $g->igst;
                    $tot_tax     += $g->total_tax;
                ?>
                <tr>
                    <td><?= $g->gst_percent ?>%</td>
                    <td class="text-right"><?= $g->invoice_count ?></td>
                    <td class="text-right">₹ <?= number_format($g->taxable_value,2) ?></td>
                    <td class="text-right">₹ <?= number_format($g->cgst,2) ?></td>
                    <td class="text-right">₹ <?= number_format($g->sgst,2) ?></td>
                    <td class="text-right">₹ <?= number_format($g->igst,2) ?></td>
                    <td class="text-right font-weight-bold">₹ <?= number_format($g->total_tax,2) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <td>Total</td>
                        <td></td>
                        <td class="text-right">₹ <?= number_format($tot_taxable,2) ?></td>
                        <td class="text-right">₹ <?= number_format($tot_cgst,2) ?></td>
                        <td class="text-right">₹ <?= number_format($tot_sgst,2) ?></td>
                        <td class="text-right">₹ <?= number_format($tot_igst,2) ?></td>
                        <td class="text-right">₹ <?= number_format($tot_tax,2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- B2B Invoices -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">B2B Invoices (with GSTIN)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.83rem;">
                <thead class="thead-light">
                    <tr><th>Invoice #</th><th>Date</th><th>Customer</th><th>GSTIN</th><th class="text-right">Taxable</th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Total</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($b2b_invoices)): foreach ($b2b_invoices as $inv): ?>
                <tr>
                    <td><?= $inv->invoice_no ?></td>
                    <td><?= date('d M', strtotime($inv->invoice_date)) ?></td>
                    <td><?= htmlspecialchars($inv->customer_name) ?></td>
                    <td><code><?= $inv->customer_gstin ?></code></td>
                    <td class="text-right">₹ <?= number_format($inv->taxable_amount,2) ?></td>
                    <td class="text-right">₹ <?= number_format($inv->cgst_amount,2) ?></td>
                    <td class="text-right">₹ <?= number_format($inv->sgst_amount,2) ?></td>
                    <td class="text-right">₹ <?= number_format($inv->igst_amount,2) ?></td>
                    <td class="text-right font-weight-bold">₹ <?= number_format($inv->total_amount,2) ?></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="9" class="text-center text-muted py-3">No B2B invoices this month.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- B2C Invoices -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">B2C Invoices (retail / no GSTIN)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.83rem;">
                <thead class="thead-light">
                    <tr><th>Invoice #</th><th>Date</th><th>Customer</th><th class="text-right">Taxable</th><th class="text-right">Total GST</th><th class="text-right">Total</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($b2c_invoices)): foreach ($b2c_invoices as $inv): ?>
                <tr>
                    <td><?= $inv->invoice_no ?></td>
                    <td><?= date('d M', strtotime($inv->invoice_date)) ?></td>
                    <td><?= htmlspecialchars($inv->customer_name) ?></td>
                    <td class="text-right">₹ <?= number_format($inv->taxable_amount,2) ?></td>
                    <td class="text-right">₹ <?= number_format($inv->total_gst,2) ?></td>
                    <td class="text-right font-weight-bold">₹ <?= number_format($inv->total_amount,2) ?></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">No B2C invoices this month.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>@media print { .no-print { display:none; } }</style>
