
<div class="row mb-4">
    <?php $perf = $performance ?? (object)['total_orders'=>0,'delivered_ontime'=>0,'quality_score'=>0,'avg_price_variance'=>0,'tier'=>'bronze','vendor_score'=>0]; ?>
    <div class="col-md-3 mb-3"><div class="card border-0 shadow-sm text-center py-3">
        <div class="h3 mb-0"><?= $perf->total_orders ?></div><div class="small text-muted">Total Orders</div>
    </div></div>
    <div class="col-md-3 mb-3"><div class="card border-0 shadow-sm text-center py-3">
        <?php $ot = $perf->total_orders>0 ? round($perf->delivered_ontime/$perf->total_orders*100) : 0; ?>
        <div class="h3 mb-0 <?= $ot>=80?'text-success':($ot>=60?'text-warning':'text-danger') ?>"><?= $ot ?>%</div>
        <div class="small text-muted">On-Time Delivery</div>
    </div></div>
    <div class="col-md-3 mb-3"><div class="card border-0 shadow-sm text-center py-3">
        <div class="h3 mb-0 text-info"><?= number_format($perf->quality_score,1) ?>/10</div>
        <div class="small text-muted">Quality Score</div>
    </div></div>
    <div class="col-md-3 mb-3"><div class="card border-0 shadow-sm text-center py-3">
        <?php $tc=['gold'=>'warning','silver'=>'secondary','bronze'=>'dark'][$perf->tier]??'secondary'; ?>
        <div class="h3 mb-0"><span class="badge badge-<?= $tc ?> px-3 py-2"><?= ucfirst($perf->tier) ?></span></div>
        <div class="small text-muted">Vendor Tier</div>
    </div></div>
</div>

<?php if (!empty($order_history)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header py-2 font-weight-bold">Order History</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:.8rem;">
            <thead class="thead-light"><tr><th>PO #</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($order_history as $o): ?>
            <?php $sc=['received'=>'success','dispatched'=>'warning','vendor_confirmed'=>'info','rejected'=>'danger'][$o->status]??'secondary'; ?>
            <tr>
                <td><?= htmlspecialchars($o->po_number) ?></td>
                <td><?= date('d M Y',strtotime($o->created_at)) ?></td>
                <td>₹<?= number_format($o->net_total,0) ?></td>
                <td><span class="badge badge-<?= $sc ?>"><?= ucfirst(str_replace('_',' ',$o->status)) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
