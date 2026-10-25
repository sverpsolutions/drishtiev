<?php // FILE: application/views/offline_sync/report.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-alt mr-2 text-info"></i>Offline Sync Report</h5>
        <small class="text-muted">Summary of all offline billing activity</small>
    </div>
    <a href="<?= base_url('offline_sync/dashboard') ?>" class="btn btn-sm btn-outline-secondary">
        &larr; Dashboard
    </a>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h4 font-weight-bold text-primary mb-0"><?= $stats['total'] ?></div>
            <small class="text-muted">Total Offline Bills</small>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h4 font-weight-bold text-success mb-0"><?= $stats['synced'] ?></div>
            <small class="text-muted">Synced</small>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h4 font-weight-bold text-warning mb-0"><?= $stats['pending'] ?></div>
            <small class="text-muted">Pending</small>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h4 font-weight-bold text-danger mb-0"><?= $stats['failed'] ?></div>
            <small class="text-muted">Failed</small>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h4 font-weight-bold text-dark mb-0">₹<?= number_format($stats['pending_amount'], 2) ?></div>
            <small class="text-muted">Pending Sales Value</small>
        </div>
    </div>
</div>

<!-- Synced Invoices -->
<?php if (!empty($mapping)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-success text-white py-2 d-flex justify-content-between" style="font-size:.85rem;">
        <span><i class="fas fa-check-double mr-1"></i> Successfully Synced Bills (<?= count($mapping) ?>)</span>
        <button class="btn btn-xs btn-light" onclick="window.print()"><i class="fas fa-print mr-1"></i>Print</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Offline Reference</th>
                        <th>Cloud Invoice No.</th>
                        <th>Cloud ID</th>
                        <th>Synced At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; foreach ($mapping as $m): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><code class="text-warning" style="font-size:.78rem;"><?= htmlspecialchars($m['offline_ref']) ?></code></td>
                        <td><strong class="text-success"><?= htmlspecialchars($m['cloud_invoice_no']) ?></strong></td>
                        <td>#<?= $m['cloud_invoice_id'] ?></td>
                        <td><?= date('d M Y H:i', strtotime($m['synced_at'])) ?></td>
                        <td>
                            <a href="<?= base_url('billing/invoice/'.$m['cloud_invoice_id']) ?>"
                               class="btn btn-xs btn-outline-success" target="_blank">View Invoice</a>
                            <a href="<?= base_url('billing/print_invoice/'.$m['cloud_invoice_id']) ?>"
                               class="btn btn-xs btn-outline-secondary" target="_blank">Print</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Failed Items -->
<?php if (!empty($failed)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-danger text-white py-2" style="font-size:.85rem;">
        <i class="fas fa-exclamation-triangle mr-1"></i> Failed Sync Items (<?= count($failed) ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.81rem;">
                <thead class="thead-light">
                    <tr>
                        <th>Offline Ref</th>
                        <th>Type</th>
                        <th>Retries</th>
                        <th>Last Error</th>
                        <th>Queued At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($failed as $f): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($f['offline_ref']) ?></code></td>
                        <td><?= ucfirst($f['queue_type']) ?></td>
                        <td class="text-danger font-weight-bold"><?= $f['retry_count'] ?></td>
                        <td><small class="text-danger"><?= htmlspecialchars(substr($f['last_error'] ?? '', 0, 80)) ?></small></td>
                        <td><?= date('d M H:i', strtotime($f['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-3">
            <a href="<?= base_url('offline_sync/retry_failed') ?>" class="btn btn-sm btn-danger"
               onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Retrying...'; return true;">
                <i class="fas fa-redo mr-1"></i>Retry All Failed
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($mapping) && empty($failed)): ?>
<div class="alert alert-info text-center py-4">
    <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
    No offline billing history found. All billing has been done while connected to the internet.
</div>
<?php endif; ?>
