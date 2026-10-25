<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h5 class="font-weight-bold mb-0">Purchase Report</h5><small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Purchases</small></div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/purchases','export_params'=>$export_params??'','export_title'=>$export_title??'Purchase Report']); ?>
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
        <button type="submit" class="btn btn-dark btn-sm">Apply</button>
    </form>
</div></div>

<?php if (!empty($location_id)): ?>
<div class="alert alert-info py-2 mb-3 no-print" style="font-size:.85rem;">
    <i class="fas fa-store mr-1"></i> Showing data for outlet: <strong><?= htmlspecialchars(array_reduce((array)($locations??[]), fn($c,$l) => $l->id==$location_id ? $l->name : $c, 'Unknown')) ?></strong>
</div>
<?php endif; ?>
<?php if ($summary): ?>
<div class="row mb-3">
    <?php foreach([['Total Orders',$summary->total_orders,false],['Gross Purchases',$summary->gross_purchases,true],['Total GST',$summary->total_gst,true],['Net Purchases',$summary->net_purchases,true],['Total Paid',$summary->total_paid,true],['Outstanding',$summary->total_outstanding,true]] as [$l,$v,$f]): ?>
    <div class="col-md-2 mb-2"><div class="card border-0 shadow-sm"><div class="card-body py-2 px-3">
        <p class="text-muted mb-0" style="font-size:.75rem;"><?= $l ?></p>
        <p class="mb-0 font-weight-bold"><?= $f ? '&#8377; '.number_format($v,2) : $v ?></p>
    </div></div></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="card border-0 shadow-sm"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-sm table-hover mb-0" style="font-size:.83rem;">
    <thead class="thead-dark"><tr><th>Purchase #</th><th>Supplier</th><th>Brands</th><th>Date</th><th class="text-right">Subtotal</th><th class="text-right">GST</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Due</th><th>Status</th></tr></thead>
    <tbody>
    <?php if (!empty($purchases)): foreach ($purchases as $p): ?>
    <tr>
        <td><a href="<?= base_url('purchases/view/'.$p->id) ?>"><?= $p->purchase_no ?></a></td>
        <td><?= htmlspecialchars($p->supplier_name) ?></td>
        <td style="max-width:220px;"><small><?= htmlspecialchars($p->brands ?? '—') ?></small></td>
        <td><?= date('d M Y', strtotime($p->invoice_date)) ?></td>
        <td class="text-right">&#8377; <?= number_format($p->subtotal,2) ?></td>
        <td class="text-right">&#8377; <?= number_format($p->total_gst,2) ?></td>
        <td class="text-right font-weight-bold">&#8377; <?= number_format($p->total_amount,2) ?></td>
        <td class="text-right text-success">&#8377; <?= number_format($p->paid_amount,2) ?></td>
        <td class="text-right <?= $p->due_amount > 0 ? 'text-danger':'' ?>">&#8377; <?= number_format($p->due_amount,2) ?></td>
        <td><span class="badge badge-<?= ['received'=>'success','partial'=>'warning','pending'=>'danger','returned'=>'secondary'][$p->status]??'secondary' ?>"><?= ucfirst($p->status) ?></span></td>
    </tr>
    <?php endforeach; else: ?>
        <tr><td colspan="10" class="text-center text-muted py-4">No purchases in this period.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div></div></div>
<style>@media print{.no-print{display:none}}</style>
