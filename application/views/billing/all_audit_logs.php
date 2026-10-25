<?php // FILE: application/views/billing/all_audit_logs.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-history text-warning"></i> Bill Edit Logs</h5>
        <small class="text-muted">All invoice modifications by all users</small>
    </div>
    <a href="<?= base_url('billing') ?>" class="btn btn-sm btn-outline-secondary">&larr; Back</a>
</div>

<div class="card shadow-sm">
<div class="card-body p-0">
<?php if (empty($logs)): ?>
<p class="text-muted text-center py-4">No bill edits recorded yet.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm table-hover table-bordered mb-0" style="font-size:.84rem;">
<thead class="thead-dark">
<tr>
    <th>#</th>
    <th>Invoice No</th>
    <th>Edited At</th>
    <th>Edited By</th>
    <th class="text-right">Old Total</th>
    <th class="text-right">New Total</th>
    <th class="text-right">Change</th>
    <th>Reason</th>
    <th>IP</th>
    <th>Details</th>
</tr>
</thead>
<tbody>
<?php foreach ($logs as $idx => $log): ?>
<?php $diff = $log->new_total - $log->old_total; ?>
<tr>
    <td><?= $idx + 1 ?></td>
    <td>
        <a href="<?= base_url('billing/audit_log/' . $log->invoice_id) ?>" class="font-weight-bold">
            <?= htmlspecialchars($log->invoice_no) ?>
        </a>
    </td>
    <td><?= date('d-M-Y H:i:s', strtotime($log->edited_at)) ?></td>
    <td><?= htmlspecialchars($log->edited_by_name) ?></td>
    <td class="text-right">&#8377;<?= number_format($log->old_total, 2) ?></td>
    <td class="text-right font-weight-bold">&#8377;<?= number_format($log->new_total, 2) ?></td>
    <td class="text-right font-weight-bold <?= $diff > 0 ? 'text-danger' : ($diff < 0 ? 'text-success' : '') ?>">
        <?= $diff > 0 ? '+' : '' ?>&#8377;<?= number_format($diff, 2) ?>
    </td>
    <td><?= htmlspecialchars($log->reason ?: '—') ?></td>
    <td><small class="text-muted"><?= $log->ip_address ?></small></td>
    <td>
        <button class="btn btn-xs btn-outline-info" data-toggle="modal" data-target="#logModal_<?= $log->id ?>">
            <i class="fas fa-eye"></i> View
        </button>
        <!-- Modal -->
        <div class="modal fade" id="logModal_<?= $log->id ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Detail — <?= htmlspecialchars($log->invoice_no) ?> @ <?= date('d-M-Y H:i', strtotime($log->edited_at)) ?></h5>
                <button class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                <div class="col-md-6">
                    <h6 class="text-danger">BEFORE (Old Items)</h6>
                    <pre class="bg-light p-2 rounded" style="font-size:11px; max-height:300px; overflow-y:auto;"><?php
                        $old = json_decode($log->old_items, true);
                        if (is_array($old)) {
                            foreach ($old as $oi) {
                                $name = $oi['product_name'] ?? ($oi['description'] ?? 'Item');
                                echo htmlspecialchars($name)
                                    . ' | Qty: ' . ($oi['qty'] ?? '')
                                    . ' | Rate: ₹' . number_format($oi['rate'] ?? 0, 2)
                                    . ' | Total: ₹' . number_format($oi['total'] ?? 0, 2) . "\n";
                            }
                        } else {
                            echo 'N/A';
                        }
                    ?></pre>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success">AFTER (New Items)</h6>
                    <pre class="bg-light p-2 rounded" style="font-size:11px; max-height:300px; overflow-y:auto;"><?php
                        $new = json_decode($log->new_items, true);
                        if (is_array($new)) {
                            foreach ($new as $ni) {
                                $name = $ni['product_name'] ?? ('PID:' . ($ni['product_id'] ?? '?'));
                                echo htmlspecialchars($name)
                                    . ' | Qty: ' . ($ni['qty'] ?? '')
                                    . ' | Rate: ₹' . number_format($ni['rate'] ?? 0, 2)
                                    . ' | Total: ₹' . number_format($ni['total'] ?? 0, 2) . "\n";
                            }
                        } else {
                            echo 'N/A';
                        }
                    ?></pre>
                </div>
                </div>
                <div class="mt-2 text-right">
                    <span class="badge badge-secondary">Old: &#8377;<?= number_format($log->old_total, 2) ?></span>
                    <span class="badge badge-<?= $diff >= 0 ? 'danger' : 'success' ?> ml-1">New: &#8377;<?= number_format($log->new_total, 2) ?></span>
                </div>
            </div>
        </div>
        </div>
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
