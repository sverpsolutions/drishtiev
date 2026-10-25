
<div class="container-fluid mt-3">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-history text-info"></i> Audit Log: <?= $invoice->invoice_no ?></h4>
    <a href="<?= base_url('billing/invoice/'.$invoice->id) ?>" class="btn btn-sm btn-outline-secondary">← Back to Invoice</a>
</div>

<!-- Edit History -->
<div class="card shadow-sm mb-3">
<div class="card-header bg-info text-white"><b>Edit History</b></div>
<div class="card-body p-0">
<?php if (empty($logs)): ?>
<p class="text-muted text-center py-3">No edit history found.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm table-bordered mb-0">
<thead class="thead-dark">
<tr><th>Edit #</th><th>Date/Time</th><th>Editor</th><th>Old Total</th><th>New Total</th><th>Change</th><th>Reason</th><th>IP</th><th>Details</th></tr>
</thead>
<tbody>
<?php foreach ($logs as $idx => $log): ?>
<tr>
    <td><?= $idx+1 ?></td>
    <td><?= date('d-M-Y H:i:s', strtotime($log->edited_at)) ?></td>
    <td><?= htmlspecialchars($log->edited_by_name) ?></td>
    <td class="text-right">₹<?= number_format($log->old_total, 2) ?></td>
    <td class="text-right font-weight-bold">₹<?= number_format($log->new_total, 2) ?></td>
    <td class="text-right <?= ($log->new_total > $log->old_total) ? 'text-danger' : 'text-success' ?>">
        <?= $log->new_total > $log->old_total ? '+' : '' ?>₹<?= number_format($log->new_total - $log->old_total, 2) ?>
    </td>
    <td><?= htmlspecialchars($log->reason ?? 'N/A') ?></td>
    <td><small><?= $log->ip_address ?></small></td>
    <td>
        <button class="btn btn-xs btn-outline-info" data-toggle="modal" data-target="#logModal_<?= $log->id ?>">View</button>
        <!-- Modal -->
        <div class="modal fade" id="logModal_<?= $log->id ?>" tabindex="-1">
        <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit Detail — <?= date('d-M-Y H:i', strtotime($log->edited_at)) ?></h5><button class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <div class="row">
            <div class="col-md-6">
                <h6 class="text-danger">OLD Items</h6>
                <pre class="bg-light p-2" style="font-size:11px; max-height:300px; overflow-y:auto;"><?php
                    $old = json_decode($log->old_items, true);
                    if (is_array($old)) foreach($old as $oi) {
                        echo ($oi['product_name'] ?? $oi['description'] ?? 'Item') . ' | Qty: '.($oi['qty']??'') . ' | Rate: '.($oi['rate']??'') . ' | Total: '.($oi['total']??'')."\n";
                    }
                ?></pre>
            </div>
            <div class="col-md-6">
                <h6 class="text-success">NEW Items</h6>
                <pre class="bg-light p-2" style="font-size:11px; max-height:300px; overflow-y:auto;"><?php
                    $new = json_decode($log->new_items, true);
                    if (is_array($new)) foreach($new as $ni) {
                        echo 'PID:'.($ni['product_id']??'') . ' | Qty: '.($ni['qty']??'') . ' | Rate: '.($ni['rate']??'') . ' | Total: '.($ni['total']??'')."\n";
                    }
                ?></pre>
            </div>
            </div>
        </div>
        </div></div>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>

<!-- Audit Events -->
<div class="card shadow-sm">
<div class="card-header bg-secondary text-white"><b>All Audit Events</b></div>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="thead-dark"><tr><th>Time</th><th>Action</th><th>User</th><th>Old Amt</th><th>New Amt</th><th>Description</th></tr></thead>
<tbody>
<?php foreach ($audit_logs as $al): ?>
<tr>
    <td><?= date('d-M-Y H:i', strtotime($al->created_at)) ?></td>
    <td><span class="badge badge-<?= $al->action==='edit'?'warning':($al->action==='create'?'success':'danger') ?>"><?= ucfirst($al->action) ?></span></td>
    <td><?= htmlspecialchars($al->user_name) ?></td>
    <td>₹<?= number_format($al->old_amount??0, 2) ?></td>
    <td>₹<?= number_format($al->new_amount??0, 2) ?></td>
    <td><?= htmlspecialchars($al->description) ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($audit_logs)): ?>
<tr><td colspan="6" class="text-center text-muted">No audit events.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

