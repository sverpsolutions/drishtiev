
<div class="container-fluid mt-3">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-file-invoice text-warning"></i> GSTR-2 Report (Purchase / Input Tax Credit)</h4>
    <div>
        <a href="<?= base_url('reports/export_gst_excel?type=gstr2&month='.$month) ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export Excel</a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark no-print"><i class="fas fa-print"></i> Print</button>
    </div>
</div>

<div class="card shadow-sm mb-3 no-print">
<div class="card-body py-2">
<form method="get" class="form-inline">
    <label class="mr-2">Month:</label>
    <input type="month" name="month" class="form-control form-control-sm mr-2" value="<?= $month ?>">
    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    <a href="<?= base_url('reports/gstr1?month='.$month) ?>" class="btn btn-outline-secondary btn-sm ml-2">← GSTR-1</a>
    <a href="<?= base_url('reports/gstr3b?month='.$month) ?>" class="btn btn-outline-secondary btn-sm ml-1">GSTR-3B →</a>
</form>
</div>
</div>

<h5 class="mb-3">Period: <?= date('F Y', strtotime($from)) ?></h5>

<!-- Summary Card -->
<?php if ($summary): ?>
<div class="row mb-3">
    <div class="col-md-3"><div class="card border-primary text-center py-2">
        <div class="h4 font-weight-bold"><?= $summary->total_invoices ?></div><small>Purchase Invoices</small></div></div>
    <div class="col-md-3"><div class="card border-secondary text-center py-2">
        <div class="h4 font-weight-bold">₹<?= number_format($summary->total_taxable, 2) ?></div><small>Taxable Value</small></div></div>
    <div class="col-md-3"><div class="card border-warning text-center py-2">
        <div class="h4 font-weight-bold text-warning">₹<?= number_format($summary->total_gst, 2) ?></div><small>Input Tax (ITC)</small></div></div>
    <div class="col-md-3"><div class="card border-success text-center py-2">
        <div class="h4 font-weight-bold text-success">₹<?= number_format($summary->total_taxable + $summary->total_gst, 2) ?></div><small>Total Purchase</small></div></div>
</div>
<?php endif; ?>

<!-- Supplier-wise Purchases -->
<div class="card shadow-sm">
<div class="card-header"><b>Supplier-wise Purchase Detail</b></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-bordered mb-0">
<thead class="thead-dark"><tr>
    <th>#</th><th>Purchase No</th><th>Date</th><th>Supplier</th><th>GSTIN</th>
    <th class="text-right">Taxable Amt</th><th class="text-right">Input Tax</th><th class="text-right">Total</th>
</tr></thead>
<tbody>
<?php $gt=0; $gg=0; $gtotal=0; ?>
<?php foreach ($purchases as $i => $row): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><a href="<?= base_url('purchases/view/'.$row->id??'') ?>"><?= $row->purchase_no ?></a></td>
    <td><?= date('d-M-Y', strtotime($row->invoice_date)) ?></td>
    <td><?= htmlspecialchars($row->supplier_name ?? '') ?></td>
    <td><small><?= $row->gst_number ?? '-' ?></small></td>
    <td class="text-right">₹<?= number_format($row->total_amount - $row->total_gst, 2) ?></td>
    <td class="text-right font-weight-bold text-warning">₹<?= number_format($row->total_gst, 2) ?></td>
    <td class="text-right">₹<?= number_format($row->total_amount, 2) ?></td>
</tr>
<?php $gt+=($row->total_amount-$row->total_gst); $gg+=$row->total_gst; $gtotal+=$row->total_amount; ?>
<?php endforeach; ?>
<?php if (empty($purchases)): ?><tr><td colspan="8" class="text-center text-muted">No purchase invoices for this period.</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-light font-weight-bold"><tr>
<td colspan="5">TOTAL</td>
<td class="text-right">₹<?= number_format($gt, 2) ?></td>
<td class="text-right text-warning">₹<?= number_format($gg, 2) ?></td>
<td class="text-right">₹<?= number_format($gtotal, 2) ?></td>
</tr></tfoot>
</table>
</div>
</div>
</div>

</div>

