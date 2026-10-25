
<div class="container-fluid mt-3">
<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('warning')): ?>
<div class="alert alert-warning"><?= $this->session->flashdata('warning') ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-file-alt text-primary"></i> <?= $estimate->estimate_no ?></h4>
    <div>
        <a href="<?= base_url('estimates') ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
        <a href="<?= base_url('estimates/print_estimate/'.$estimate->id) ?>" class="btn btn-sm btn-outline-dark" target="_blank"><i class="fas fa-print"></i> Print</a>
        <?php if ($estimate->status === 'pending'): ?>
        <a href="<?= base_url('estimates/edit/'.$estimate->id) ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
        <a href="<?= base_url('estimates/convert_to_invoice/'.$estimate->id) ?>" class="btn btn-sm btn-info" onclick="return confirm('Convert to Sales Invoice?')"><i class="fas fa-file-invoice"></i> Convert to Bill</a>
        <button class="btn btn-sm btn-success" onclick="confirmCash()"><i class="fas fa-money-bill-wave"></i> Cash Sale (No Bill)</button>
        <a href="<?= base_url('estimates/cancel/'.$estimate->id) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this estimate?')"><i class="fas fa-times"></i> Cancel</a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
<div class="col-md-8">
<div class="card shadow-sm">
<div class="card-header d-flex justify-content-between align-items-center">
    <span><b><?= $company['company_name'] ?? 'SV ERP Solutions' ?></b></span>
    <?php
    $badges = ['pending'=>'warning','converted'=>'info','closed_cash'=>'success','cancelled'=>'danger'];
    $labels = ['pending'=>'Pending','converted'=>'Converted to Bill','closed_cash'=>'Closed - Cash Sale','cancelled'=>'Cancelled'];
    ?>
    <span class="badge badge-<?= $badges[$estimate->status]??'secondary' ?> badge-pill px-3 py-2">
        <?= $labels[$estimate->status]??$estimate->status ?>
    </span>
</div>
<div class="card-body">
<div class="row mb-3">
    <div class="col-md-6">
        <p class="mb-1"><b>Customer:</b> <?= htmlspecialchars($estimate->customer_name) ?></p>
        <p class="mb-1"><b>Mobile:</b> <?= $estimate->customer_mobile ?: 'N/A' ?></p>
    </div>
    <div class="col-md-6 text-right">
        <p class="mb-1"><b>Estimate No:</b> <?= $estimate->estimate_no ?></p>
        <p class="mb-1"><b>Date:</b> <?= date('d-M-Y', strtotime($estimate->estimate_date)) ?></p>
        <p class="mb-1"><b>Valid Until:</b> <?= $estimate->valid_until ? date('d-M-Y', strtotime($estimate->valid_until)) : 'N/A' ?></p>
    </div>
</div>

<table class="table table-bordered table-sm">
<thead class="thead-light">
<tr>
    <th>#</th><th>Code</th><th>Description</th><th>Qty</th><th>Unit</th>
    <th class="text-right">Rate</th><th class="text-right">Disc%</th>
    <th class="text-right">Tax%</th><th class="text-right">Tax Amt</th>
    <th class="text-right">Total</th>
</tr>
</thead>
<tbody>
<?php foreach ($items as $idx => $item): ?>
<tr>
    <td><?= $idx+1 ?></td>
    <td><?= $item->item_code ?></td>
    <td><?= htmlspecialchars($item->description) ?></td>
    <td><?= $item->qty ?></td>
    <td><?= $item->unit ?></td>
    <td class="text-right">₹<?= number_format($item->rate, 2) ?></td>
    <td class="text-right"><?= $item->discount ?>%</td>
    <td class="text-right"><?= $item->tax_percent ?>%</td>
    <td class="text-right">₹<?= number_format($item->tax_amount, 2) ?></td>
    <td class="text-right font-weight-bold">₹<?= number_format($item->total, 2) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr class="table-light">
    <td colspan="9" class="text-right"><b>Subtotal:</b></td>
    <td class="text-right">₹<?= number_format($estimate->subtotal, 2) ?></td>
</tr>
<?php if ($estimate->total_discount > 0): ?>
<tr class="table-light">
    <td colspan="9" class="text-right text-danger"><b>Discount:</b></td>
    <td class="text-right text-danger">-₹<?= number_format($estimate->total_discount, 2) ?></td>
</tr>
<?php endif; ?>
<?php if ($estimate->total_tax > 0): ?>
<tr class="table-light">
    <td colspan="9" class="text-right"><b>Tax:</b></td>
    <td class="text-right">₹<?= number_format($estimate->total_tax, 2) ?></td>
</tr>
<?php endif; ?>
<tr class="table-warning">
    <td colspan="9" class="text-right h5"><b>Grand Total:</b></td>
    <td class="text-right h5 font-weight-bold text-danger">₹<?= number_format($estimate->total_amount, 2) ?></td>
</tr>
</tfoot>
</table>

<?php if ($estimate->notes): ?>
<div class="alert alert-light border"><b>Notes:</b> <?= nl2br(htmlspecialchars($estimate->notes)) ?></div>
<?php endif; ?>

<?php if ($estimate->status === 'closed_cash'): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <b>Closed as Cash Sale</b> — Stock deducted. No GST bill generated. Closed at: <?= $estimate->closed_at ?></div>
<?php elseif ($estimate->status === 'converted'): ?>
<div class="alert alert-info"><i class="fas fa-file-invoice"></i> <b>Converted to Invoice ID: <?= $estimate->converted_to ?></b> — <a href="<?= base_url('billing/invoice/'.$estimate->converted_to) ?>">View Invoice →</a></div>
<?php endif; ?>
</div>
</div>
</div>

<div class="col-md-4">
<!-- Profit Summary for closed estimates -->
<?php if (in_array($estimate->status, ['closed_cash', 'converted'])): ?>
<div class="card shadow-sm mb-3 border-success">
<div class="card-header bg-success text-white"><b>Profit Summary</b></div>
<div class="card-body">
<?php
$total_cost   = array_sum(array_map(fn($i) => $i->cost_price * $i->qty, $items));
$total_sale   = $estimate->total_amount;
$profit       = $total_sale - $total_cost;
$profit_pct   = $total_cost > 0 ? round(($profit/$total_cost)*100, 1) : 0;
?>
<table class="table table-sm table-borderless mb-0">
<tr><td>Sales Value:</td><td class="text-right font-weight-bold">₹<?= number_format($total_sale, 2) ?></td></tr>
<tr><td>Cost:</td><td class="text-right">₹<?= number_format($total_cost, 2) ?></td></tr>
<tr class="text-success font-weight-bold"><td>Profit:</td><td class="text-right">₹<?= number_format($profit, 2) ?> (<?= $profit_pct ?>%)</td></tr>
</table>
</div>
</div>
<?php endif; ?>

<!-- Item Stock Status -->
<div class="card shadow-sm">
<div class="card-header"><b>Stock Status</b></div>
<div class="card-body p-0">
<table class="table table-sm mb-0">
<thead class="thead-light"><tr><th>Item</th><th>Needed</th><th>Available</th></tr></thead>
<tbody>
<?php foreach ($items as $item): ?>
<tr>
    <td><?= htmlspecialchars(substr($item->description, 0, 20)) ?></td>
    <td><?= $item->qty ?></td>
    <td class="<?= ($item->stock_qty >= $item->qty) ? 'text-success' : 'text-danger' ?>">
        <?= $item->stock_qty ?>
        <?= ($item->stock_qty < $item->qty) ? '<i class="fas fa-exclamation-triangle"></i>' : '' ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>

<!-- Cash Close Modal -->
<div class="modal fade" id="cashModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header bg-success text-white"><h5 class="modal-title">Close as Cash Sale</h5></div>
<div class="modal-body">
    <p>This will:</p>
    <ul>
        <li>Mark estimate as <b>Closed — Cash Sale (No Bill)</b></li>
        <li>Deduct stock for all items</li>
        <li>Track sales value & profit</li>
        <li><b>No GST invoice</b> will be generated</li>
    </ul>
    <p class="text-danger"><b>Total: ₹<?= number_format($estimate->total_amount, 2) ?></b></p>
</div>
<div class="modal-footer">
    <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
    <a href="<?= base_url('estimates/close_cash/'.$estimate->id) ?>" class="btn btn-success">Confirm Cash Sale</a>
</div>
</div>
</div>
</div>

<script>
function confirmCash() { $('#cashModal').modal('show'); }
</script>

