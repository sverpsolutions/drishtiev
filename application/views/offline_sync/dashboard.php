<?php // FILE: application/views/offline_sync/dashboard.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-sync-alt mr-2 text-primary"></i>Offline Sync Dashboard</h5>
        <small class="text-muted">Monitor offline billing synchronization status</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span id="pos-sync-badge" class="sync-status-badge mr-3">🟢 Online</span>
        <a href="<?= base_url('offline_sync/report') ?>" class="btn btn-sm btn-outline-info">
            <i class="fas fa-file-alt mr-1"></i>Report
        </a>
        <button class="btn btn-sm btn-warning" onclick="retryFailed()">
            <i class="fas fa-redo mr-1"></i>Retry Failed
        </button>
        <button class="btn btn-sm btn-success" onclick="forceSync()">
            <i class="fas fa-cloud-upload-alt mr-1"></i>Sync Now
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="display-4 font-weight-bold text-warning"><?= $stats['pending'] ?></div>
                <div class="text-muted small font-weight-bold">Pending Bills</div>
                <?php if ($stats['pending_amount'] > 0): ?>
                <div class="mt-1 small text-warning">₹<?= number_format($stats['pending_amount'],2) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="display-4 font-weight-bold text-success"><?= $stats['synced'] ?></div>
                <div class="text-muted small font-weight-bold">Synced Bills</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="display-4 font-weight-bold text-danger"><?= $stats['failed'] ?></div>
                <div class="text-muted small font-weight-bold">Failed Bills</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="display-4 font-weight-bold text-info"><?= $stats['total'] ?></div>
                <div class="text-muted small font-weight-bold">Total Offline Bills</div>
                <?php if ($stats['last_log']): ?>
                <div class="mt-1 small text-muted">
                    Last sync: <?= date('d M H:i', strtotime($stats['last_log']['created_at'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Network Status Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 d-flex align-items-center justify-content-between" style="font-size:.9rem;">
        <div>
            <span class="font-weight-bold">Network Status:</span>
            <span id="dash-network-status" class="ml-2">Checking...</span>
        </div>
        <div>
            <span class="font-weight-bold mr-2">Sync Status:</span>
            <span id="dash-sync-status" class="badge badge-secondary">Idle</span>
        </div>
        <div>
            <span class="font-weight-bold mr-2">Local DB:</span>
            <span id="dash-db-stats" class="text-muted small">Loading...</span>
        </div>
        <div>
            <span class="font-weight-bold mr-2">Last Data Sync:</span>
            <span id="dash-last-sync" class="text-muted small">—</span>
        </div>
    </div>
</div>

<!-- Invoice Mapping Table (synced) -->
<?php if (!empty($mapping)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-success text-white py-2" style="font-size:.85rem;">
        <i class="fas fa-check-circle mr-1"></i> Synced Invoice Mappings (<?= count($mapping) ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:300px; overflow-y:auto;">
            <table class="table table-sm mb-0" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Offline Reference</th>
                        <th>Cloud Invoice No</th>
                        <th>Cloud ID</th>
                        <th>Device</th>
                        <th>Synced At</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mapping as $m): ?>
                    <tr>
                        <td><code class="text-warning"><?= htmlspecialchars($m['offline_ref']) ?></code></td>
                        <td><strong><?= htmlspecialchars($m['cloud_invoice_no']) ?></strong></td>
                        <td><?= $m['cloud_invoice_id'] ?></td>
                        <td><small class="text-muted"><?= substr($m['device_id'], 0, 12) ?>...</small></td>
                        <td><?= date('d M H:i', strtotime($m['synced_at'])) ?></td>
                        <td>
                            <a href="<?= base_url('billing/invoice/' . $m['cloud_invoice_id']) ?>"
                               class="btn btn-xs btn-outline-success" target="_blank">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Sync Queue Table -->
<?php if (!empty($queue)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center" style="font-size:.85rem;">
        <span><i class="fas fa-list mr-1"></i> Sync Queue (<?= count($queue) ?>)</span>
        <button class="btn btn-xs btn-outline-light" onclick="refreshPage()">
            <i class="fas fa-sync fa-spin" id="refreshIcon" style="display:none;"></i>
            <i class="fas fa-sync" id="refreshIcon2"></i> Refresh
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
            <table class="table table-sm mb-0" style="font-size:.81rem;">
                <thead class="thead-light">
                    <tr>
                        <th>Offline Ref</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Cloud Ref</th>
                        <th>Retries</th>
                        <th>Error</th>
                        <th>Queued At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queue as $q): ?>
                    <?php
                    $statusBadge = [
                        'pending'  => 'badge-warning',
                        'syncing'  => 'badge-info',
                        'synced'   => 'badge-success',
                        'failed'   => 'badge-danger',
                    ][$q['status']] ?? 'badge-secondary';
                    ?>
                    <tr>
                        <td><code><?= htmlspecialchars($q['offline_ref']) ?></code></td>
                        <td><?= ucfirst($q['queue_type']) ?></td>
                        <td><span class="badge <?= $statusBadge ?>"><?= $q['status'] ?></span></td>
                        <td><?= $q['cloud_ref'] ? '<strong>'.htmlspecialchars($q['cloud_ref']).'</strong>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="<?= $q['retry_count'] >= 3 ? 'text-danger' : '' ?>"><?= $q['retry_count'] ?></td>
                        <td><small class="text-danger"><?= $q['last_error'] ? htmlspecialchars(substr($q['last_error'],0,60)) : '' ?></small></td>
                        <td><?= date('d M H:i', strtotime($q['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle mr-2"></i>
    No items in sync queue. All offline invoices are up to date.
</div>
<?php endif; ?>

<!-- Local DB Stats Panel -->
<div class="card border-0 shadow-sm mb-4" id="localDbPanel" style="display:none;">
    <div class="card-header bg-info text-white py-2" style="font-size:.85rem;">
        <i class="fas fa-database mr-1"></i> Local Offline Cache Statistics
    </div>
    <div class="card-body">
        <div class="row" id="localDbStatsBody">
            <div class="col-12 text-center text-muted py-3">
                <i class="fas fa-spinner fa-spin mr-2"></i>Loading local cache stats...
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= base_url() ?>';

async function retryFailed() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Retrying...';
    try {
        const res = await fetch(BASE_URL + 'offline_sync/retry_failed', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (json.success) {
            alert(`Reset ${json.reset} failed items to pending. Sync will start automatically.`);
            if (typeof OfflineSync !== 'undefined') await OfflineSync.syncNow();
        }
    } catch (e) { alert('Error: ' + e.message); }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-redo mr-1"></i>Retry Failed';
}

async function forceSync() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Syncing...';
    document.getElementById('dash-sync-status').textContent = 'Running...';
    document.getElementById('dash-sync-status').className = 'badge badge-info';
    try {
        if (typeof OfflineSync !== 'undefined') {
            await OfflineSync.syncNow();
            document.getElementById('dash-sync-status').textContent = 'Complete';
            document.getElementById('dash-sync-status').className = 'badge badge-success';
            setTimeout(() => location.reload(), 1500);
        }
    } catch (e) { alert('Sync error: ' + e.message); }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-cloud-upload-alt mr-1"></i>Sync Now';
}

function refreshPage() {
    document.getElementById('refreshIcon').style.display = '';
    document.getElementById('refreshIcon2').style.display = 'none';
    setTimeout(() => location.reload(), 800);
}

// Load local DB stats
async function loadLocalStats() {
    if (typeof PosDB === 'undefined') return;
    await PosDB.open();

    const [prodCount, custCount, pluCount, pendingInvs, lastSync] = await Promise.all([
        PosDB.count('products'),
        PosDB.count('customers'),
        PosDB.count('plu_list'),
        PosDB.getPendingInvoices(),
        PosDB.getMeta('last_sync_ts'),
    ]);

    document.getElementById('localDbPanel').style.display = '';
    document.getElementById('localDbStatsBody').innerHTML = `
        <div class="col-md-2 text-center mb-3">
            <div class="font-weight-bold h4 text-primary">${prodCount}</div>
            <div class="small text-muted">Products Cached</div>
        </div>
        <div class="col-md-2 text-center mb-3">
            <div class="font-weight-bold h4 text-info">${custCount}</div>
            <div class="small text-muted">Customers Cached</div>
        </div>
        <div class="col-md-2 text-center mb-3">
            <div class="font-weight-bold h4 text-success">${pluCount}</div>
            <div class="small text-muted">PLU / Barcodes</div>
        </div>
        <div class="col-md-2 text-center mb-3">
            <div class="font-weight-bold h4 ${pendingInvs.length > 0 ? 'text-warning' : 'text-muted'}">${pendingInvs.length}</div>
            <div class="small text-muted">Pending Invoices (IDB)</div>
        </div>
        <div class="col-md-4 text-center mb-3">
            <div class="font-weight-bold small text-muted">Last Data Sync</div>
            <div class="small">${lastSync ? new Date(parseInt(lastSync)*1000).toLocaleString() : 'Not yet synced'}</div>
        </div>
    `;
    document.getElementById('dash-db-stats').textContent =
        `${prodCount} products, ${custCount} customers, ${pluCount} PLU`;
    if (lastSync) {
        document.getElementById('dash-last-sync').textContent = new Date(parseInt(lastSync)*1000).toLocaleString();
    }
}

// Network status
async function checkNetwork() {
    const el = document.getElementById('dash-network-status');
    const statusEl = document.getElementById('dash-sync-status');
    try {
        const res = await fetch(BASE_URL + 'offline_sync/status_json?_=' + Date.now(), {
            signal: AbortSignal.timeout(4000)
        });
        if (res.ok) {
            el.innerHTML = '<span class="text-success font-weight-bold">🟢 Online</span>';
        } else {
            el.innerHTML = '<span class="text-danger font-weight-bold">🔴 Offline</span>';
        }
    } catch {
        el.innerHTML = '<span class="text-danger font-weight-bold">🔴 Offline</span>';
    }

    // Update sync queue status
    const pending = <?= $stats['pending'] ?>;
    const failed  = <?= $stats['failed'] ?>;
    if (pending > 0) {
        statusEl.textContent = `${pending} Pending`;
        statusEl.className = 'badge badge-warning';
    } else if (failed > 0) {
        statusEl.textContent = `${failed} Failed`;
        statusEl.className = 'badge badge-danger';
    } else {
        statusEl.textContent = 'All Synced';
        statusEl.className = 'badge badge-success';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    checkNetwork();
    loadLocalStats();
    setInterval(checkNetwork, 15000);
});

// Listen to sync events from OfflineSync module
document.addEventListener('pos:sync_complete', function(e) {
    const { synced, failed, pending } = e.detail;
    if (synced > 0 || failed > 0) {
        const msg = `Sync update: ${synced} synced, ${failed} failed, ${pending} pending`;
        console.info(msg);
        if (synced > 0) setTimeout(() => location.reload(), 2000);
    }
});
</script>
