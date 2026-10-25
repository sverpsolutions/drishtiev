
<div class="container-fluid mt-3">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-file-invoice text-primary"></i> GSTR-1 Report</h4>
    <div>
        <a href="<?= base_url('reports/export_gst_excel?type=gstr1&month='.$month) ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export Excel</a>
        <a href="<?= base_url('reports/gstr1_json?month='.$month) ?>" class="btn btn-sm btn-warning font-weight-bold">
            <i class="fas fa-download mr-1"></i>Download JSON <small style="font-size:.72rem;">(GST Portal)</small>
        </a>
        <a href="<?= base_url('reports/export_gstr1_offline?month='.$month) ?>" class="btn btn-sm btn-info font-weight-bold">
            <i class="fas fa-file-excel mr-1"></i>Offline Tool Excel
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark no-print"><i class="fas fa-print"></i> Print</button>
    </div>
</div>

<!-- Filter -->
<div class="card shadow-sm mb-3 no-print">
<div class="card-body py-2">
<form method="get" class="form-inline flex-wrap">
    <label class="mr-2">Month:</label>
    <input type="month" name="month" class="form-control form-control-sm mr-2" value="<?= $month ?>">
    <select name="location_id" class="form-control form-control-sm mr-2" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
        <?php if (count($locations ?? []) > 1): ?>
        <option value="">All Outlets</option>
        <?php endif; ?>
        <?php foreach ($locations ?? [] as $loc): ?>
        <option value="<?= $loc->id ?>" <?= ($location_id ?? 0) == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    <a href="<?= base_url('reports/gstr2?month='.$month) ?>" class="btn btn-outline-secondary btn-sm ml-2">GSTR-2 →</a>
    <a href="<?= base_url('reports/gstr3b?month='.$month) ?>" class="btn btn-outline-secondary btn-sm ml-1">GSTR-3B →</a>
</form>
</div>
</div>

<h5 class="mb-3">Period: <?= date('F Y', strtotime($from)) ?></h5>

<!-- Nav tabs -->
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab_b2b">B2B Invoices</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_b2c">B2C Invoices</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_hsn">HSN Summary</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_tax">Tax Summary</a></li>
    <li class="nav-item">
        <a class="nav-link text-danger" data-toggle="tab" href="#tab_cn">
            <i class="fas fa-undo fa-xs mr-1"></i>Section 9B — Credit Notes
            <?php if (!empty($credit_notes)): ?>
                <span class="badge badge-danger"><?= count($credit_notes) ?></span>
            <?php endif; ?>
        </a>
    </li>
</ul>

<div class="tab-content">

<!-- B2B -->
<div class="tab-pane fade show active" id="tab_b2b">
<div class="card shadow-sm">
<div class="card-header"><b>B2B — Business Invoices (Registered Customers)</b>
<span class="badge badge-primary ml-2"><?= count($b2b) ?></span></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-bordered mb-0">
<thead class="thead-dark"><tr>
    <th>#</th><th>Invoice No</th><th>Date</th><th>Customer</th><th>GSTIN</th>
    <th class="text-right">Taxable</th><th class="text-right">CGST</th>
    <th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Total</th>
</tr></thead>
<tbody>
<?php $b2b_taxable=0; $b2b_cgst=0; $b2b_sgst=0; $b2b_igst=0; $b2b_total=0; ?>
<?php foreach ($b2b as $i => $row): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= $row->invoice_no ?></td>
    <td><?= date('d-M-Y', strtotime($row->invoice_date)) ?></td>
    <td><?= htmlspecialchars($row->customer_name ?? '') ?></td>
    <td><small><?= $row->customer_gstin ?? '-' ?></small></td>
    <td class="text-right">₹<?= number_format($row->taxable_amount, 2) ?></td>
    <td class="text-right">₹<?= number_format($row->cgst_amount ?? 0, 2) ?></td>
    <td class="text-right">₹<?= number_format($row->sgst_amount ?? 0, 2) ?></td>
    <td class="text-right">₹<?= number_format($row->igst_amount ?? 0, 2) ?></td>
    <td class="text-right font-weight-bold">₹<?= number_format($row->total_amount, 2) ?></td>
</tr>
<?php $b2b_taxable+=$row->taxable_amount; $b2b_cgst+=($row->cgst_amount??0); $b2b_sgst+=($row->sgst_amount??0); $b2b_igst+=($row->igst_amount??0); $b2b_total+=$row->total_amount; ?>
<?php endforeach; ?>
<?php if (empty($b2b)): ?><tr><td colspan="10" class="text-center text-muted">No B2B invoices.</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-light font-weight-bold">
<tr><td colspan="5">TOTAL</td>
<td class="text-right">₹<?= number_format($b2b_taxable, 2) ?></td>
<td class="text-right">₹<?= number_format($b2b_cgst, 2) ?></td>
<td class="text-right">₹<?= number_format($b2b_sgst, 2) ?></td>
<td class="text-right">₹<?= number_format($b2b_igst, 2) ?></td>
<td class="text-right">₹<?= number_format($b2b_total, 2) ?></td></tr>
</tfoot>
</table>
</div>
</div>
</div>
</div>

<!-- B2C -->
<div class="tab-pane fade" id="tab_b2c">
<div class="card shadow-sm">
<div class="card-header"><b>B2C — Consumer Invoices (Unregistered Customers)</b>
<span class="badge badge-secondary ml-2"><?= count($b2c) ?></span></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-bordered mb-0">
<thead class="thead-dark"><tr>
    <th>#</th><th>Invoice No</th><th>Date</th><th>Customer</th>
    <th class="text-right">Taxable</th><th class="text-right">GST</th><th class="text-right">Total</th>
</tr></thead>
<tbody>
<?php $b2c_taxable=0; $b2c_gst=0; $b2c_total=0; ?>
<?php foreach ($b2c as $i => $row): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= $row->invoice_no ?></td>
    <td><?= date('d-M-Y', strtotime($row->invoice_date)) ?></td>
    <td><?= htmlspecialchars($row->customer_name ?? '') ?></td>
    <td class="text-right">₹<?= number_format($row->taxable_amount, 2) ?></td>
    <td class="text-right">₹<?= number_format($row->total_gst, 2) ?></td>
    <td class="text-right font-weight-bold">₹<?= number_format($row->total_amount, 2) ?></td>
</tr>
<?php $b2c_taxable+=$row->taxable_amount; $b2c_gst+=$row->total_gst; $b2c_total+=$row->total_amount; ?>
<?php endforeach; ?>
<?php if (empty($b2c)): ?><tr><td colspan="7" class="text-center text-muted">No B2C invoices.</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-light font-weight-bold"><tr><td colspan="4">TOTAL</td>
<td class="text-right">₹<?= number_format($b2c_taxable, 2) ?></td>
<td class="text-right">₹<?= number_format($b2c_gst, 2) ?></td>
<td class="text-right">₹<?= number_format($b2c_total, 2) ?></td></tr></tfoot>
</table>
</div>
</div>
</div>
</div>

<!-- HSN Summary -->
<div class="tab-pane fade" id="tab_hsn">
<div class="card shadow-sm">
<div class="card-header"><b>HSN-wise Summary</b></div>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="thead-dark"><tr>
    <th>HSN Code</th><th>Description</th><th class="text-right">Total Qty</th>
    <th class="text-right">Taxable Value</th><th class="text-right">GST%</th>
    <th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Total Tax</th>
</tr></thead>
<tbody>
<?php foreach ($hsn_summary as $row): ?>
<tr>
    <td><?= $row->hsn_code ?? '-' ?></td>
    <td><?= htmlspecialchars($row->hsn_desc ?? '') ?></td>
    <td class="text-right"><?= number_format($row->total_qty, 2) ?></td>
    <td class="text-right">₹<?= number_format($row->taxable_value, 2) ?></td>
    <td class="text-right"><?= $row->gst_percent ?>%</td>
    <td class="text-right">₹<?= number_format($row->cgst, 2) ?></td>
    <td class="text-right">₹<?= number_format($row->sgst, 2) ?></td>
    <td class="text-right">₹<?= number_format($row->igst, 2) ?></td>
    <td class="text-right font-weight-bold">₹<?= number_format($row->total_tax, 2) ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($hsn_summary)): ?><tr><td colspan="9" class="text-center text-muted">No HSN data.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Tax Summary -->
<div class="tab-pane fade" id="tab_tax">
<div class="card shadow-sm">
<div class="card-header"><b>Tax Summary by Rate</b></div>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="thead-dark"><tr>
    <th>GST Rate</th><th class="text-right">Invoices</th><th class="text-right">Taxable Value</th>
    <th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Total Tax</th>
</tr></thead>
<tbody>
<?php $ttax=0; ?>
<?php foreach ($tax_summary as $row): ?>
<tr>
    <td><?= $row->gst_percent ?>%</td>
    <td class="text-right"><?= $row->invoice_count ?></td>
    <td class="text-right">₹<?= number_format($row->taxable_value, 2) ?></td>
    <td class="text-right">₹<?= number_format($row->cgst, 2) ?></td>
    <td class="text-right">₹<?= number_format($row->sgst, 2) ?></td>
    <td class="text-right">₹<?= number_format($row->igst, 2) ?></td>
    <td class="text-right font-weight-bold">₹<?= number_format($row->total_tax, 2) ?></td>
</tr>
<?php $ttax += $row->total_tax; ?>
<?php endforeach; ?>
<?php if (empty($tax_summary)): ?><tr><td colspan="7" class="text-center text-muted">No tax data.</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-light font-weight-bold"><tr>
<td colspan="6" class="text-right">Total GST Collected:</td>
<td class="text-right text-danger">₹<?= number_format($ttax, 2) ?></td>
</tr></tfoot>
</table>
</div>
</div>
</div>

<!-- Section 9B — Credit Notes -->
<div class="tab-pane fade" id="tab_cn">
<div class="card shadow-sm border-danger">
<div class="card-header bg-danger text-white">
    <b><i class="fas fa-undo mr-1"></i>Section 9B — Credit / Debit Notes (Sales Returns from Customers)</b>
    <span class="badge badge-light ml-2"><?= count($credit_notes ?? []) ?></span>
    <a href="<?= base_url('sales_returns/create') ?>" class="btn btn-sm btn-light ml-2 float-right"><i class="fas fa-plus"></i> New Credit Note</a>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-bordered mb-0">
<thead class="thead-dark"><tr>
    <th>#</th><th>Credit Note No</th><th>Date</th><th>Customer</th><th>GSTIN</th>
    <th>Against Invoice</th><th class="text-right">Taxable</th>
    <th class="text-right">CGST</th><th class="text-right">SGST</th>
    <th class="text-right">IGST</th><th class="text-right">Total</th>
</tr></thead>
<tbody>
<?php if (empty($credit_notes)): ?>
    <tr><td colspan="11" class="text-center text-muted py-3">No credit notes for this period</td></tr>
<?php else: ?>
<?php
    $cn_taxable = $cn_cgst = $cn_sgst = $cn_igst = $cn_total = 0;
    foreach ($credit_notes as $i => $cn):
        $cn_taxable += $cn->taxable_amount;
        $cn_cgst    += $cn->cgst_amount;
        $cn_sgst    += $cn->sgst_amount;
        $cn_igst    += $cn->igst_amount;
        $cn_total   += $cn->total_amount;
?>
<tr>
    <td><?= $i+1 ?></td>
    <td><a href="<?= base_url('sales_returns/view/'.$cn->id) ?>"><?= htmlspecialchars($cn->return_number) ?></a></td>
    <td><?= date('d/m/Y', strtotime($cn->return_date)) ?></td>
    <td><?= htmlspecialchars($cn->customer_name) ?></td>
    <td><small><?= htmlspecialchars($cn->customer_gst ?: '—') ?></small></td>
    <td><small><?= htmlspecialchars($cn->original_invoice_no ?: '—') ?></small></td>
    <td class="text-right">₹<?= number_format($cn->taxable_amount,2) ?></td>
    <td class="text-right">₹<?= number_format($cn->cgst_amount,2) ?></td>
    <td class="text-right">₹<?= number_format($cn->sgst_amount,2) ?></td>
    <td class="text-right">₹<?= number_format($cn->igst_amount,2) ?></td>
    <td class="text-right text-danger font-weight-bold">₹<?= number_format($cn->total_amount,2) ?></td>
</tr>
<?php endforeach; ?>
<tr class="table-danger font-weight-bold">
    <td colspan="6" class="text-right">Total Adjustments (reduces output tax):</td>
    <td class="text-right">₹<?= number_format($cn_taxable,2) ?></td>
    <td class="text-right">₹<?= number_format($cn_cgst,2) ?></td>
    <td class="text-right">₹<?= number_format($cn_sgst,2) ?></td>
    <td class="text-right">₹<?= number_format($cn_igst,2) ?></td>
    <td class="text-right">₹<?= number_format($cn_total,2) ?></td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="card-footer text-muted small">
    <i class="fas fa-info-circle text-info mr-1"></i>
    These credit notes reduce your output GST liability in GSTR-3B Table 4(B)(2). Upload to GST Portal → GSTR-1 → Table 9B.
    <a href="<?= base_url('reports/export_gst_excel?type=gstr1&month='.$month) ?>" class="float-right"><i class="fas fa-file-excel"></i> Export to Excel</a>
</div>
</div>
</div>
</div>

</div> <!-- tab-content -->
</div>

