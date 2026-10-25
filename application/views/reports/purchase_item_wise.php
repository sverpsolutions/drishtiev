<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h5 class="font-weight-bold mb-0">Purchase Report (Item-Wise)</h5><small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Purchase Item-Wise</small></div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/purchase_item_wise','export_params'=>$export_params??'','export_title'=>$export_title??'Purchase Report Item-Wise']); ?>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3 no-print"><div class="card-body py-2">
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
        <select name="product_id" class="form-control form-control-sm mr-2 select2" style="min-width:200px;">
            <option value="">-- All Items --</option>
            <?php foreach ($products ?? [] as $prod): ?>
            <option value="<?= $prod->id ?>" <?= ($product_id ?? '') == $prod->id ? 'selected' : '' ?>><?= htmlspecialchars($prod->name . ' (' . $prod->item_code . ')') ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-dark btn-sm">Apply</button>
    </form>
</div></div>

<?php if (!empty($location_id)): ?>
<div class="alert alert-info py-2 mb-3 no-print" style="font-size:.85rem;">
    <i class="fas fa-store mr-1"></i> Showing data for outlet: <strong><?= htmlspecialchars(array_reduce((array)($locations??[]), fn($c,$l) => $l->id==$location_id ? $l->name : $c, 'Unknown')) ?></strong>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-sm table-hover mb-0" style="font-size:.83rem;">
    <thead class="thead-dark">
        <tr>
            <th>Date</th>
            <th>GRN No</th>
            <th>Invoice No</th>
            <th>Supplier Name</th>
            <th>Item Code</th>
            <th>Item Name</th>
            <th class="text-right">Qty</th>
            <th class="text-right">Basic Cost</th>
            <th class="text-right">MRP</th>
            <th class="text-right">SP</th>
            <th class="text-right">Tax</th>
            <th class="text-right">Total Amount</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!empty($records)): foreach ($records as $r): ?>
    <tr>
        <td><?= date('d M Y', strtotime($r->invoice_date)) ?></td>
        <td><a href="<?= base_url('purchases/view/'.$r->purchase_no) ?>"><?= htmlspecialchars($r->purchase_no) ?></a></td>
        <td><?= htmlspecialchars($r->invoice_no ?? '-') ?></td>
        <td><?= htmlspecialchars($r->supplier_name) ?></td>
        <td><?= htmlspecialchars($r->item_code) ?></td>
        <td><?= htmlspecialchars($r->product_name) ?></td>
        <td class="text-right"><?= $r->qty ?></td>
        <td class="text-right">&#8377; <?= number_format($r->basic_cost,2) ?></td>
        <td class="text-right">&#8377; <?= number_format($r->mrp,2) ?></td>
        <td class="text-right">&#8377; <?= number_format($r->sp,2) ?></td>
        <td class="text-right">&#8377; <?= number_format($r->tax,2) ?></td>
        <td class="text-right font-weight-bold">&#8377; <?= number_format($r->total_amount,2) ?></td>
    </tr>
    <?php endforeach; else: ?>
        <tr><td colspan="12" class="text-center text-muted py-4">No purchases in this period for selected item(s).</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div></div></div>
<style>@media print{.no-print{display:none}}</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if($.fn.select2) {
        $('.select2').select2({
            placeholder: "-- All Items --",
            allowClear: true,
            width: '100%'
        });
    }
});
</script>
