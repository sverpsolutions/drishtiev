<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-laptop text-primary mr-2"></i>Device Access Management</h5>
        <small class="text-muted">Approve or reject computers requesting access to the system</small>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-4 col-6 mb-2">
        <a href="<?= base_url('device_access?status=pending') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm text-center py-3 <?= $status_filter==='pending'?'bg-warning text-dark':'' ?>">
                <div class="h2 font-weight-bold mb-0"><?= $count_pending ?></div>
                <div class="small font-weight-bold">⏳ Pending Approval</div>
            </div>
        </a>
    </div>
    <div class="col-md-4 col-6 mb-2">
        <a href="<?= base_url('device_access?status=approved') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm text-center py-3 <?= $status_filter==='approved'?'bg-success text-white':'' ?>">
                <div class="h2 font-weight-bold mb-0"><?= $count_approved ?></div>
                <div class="small font-weight-bold">✅ Approved Devices</div>
            </div>
        </a>
    </div>
    <div class="col-md-4 col-6 mb-2">
        <a href="<?= base_url('device_access?status=rejected') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm text-center py-3 <?= $status_filter==='rejected'?'bg-danger text-white':'' ?>">
                <div class="h2 font-weight-bold mb-0"><?= $count_rejected ?></div>
                <div class="small font-weight-bold">❌ Rejected</div>
            </div>
        </a>
    </div>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-0">
                <select name="status" class="form-control form-control-sm">
                    <option value="all" <?= $status_filter==='all'?'selected':'' ?>>All Statuses</option>
                    <option value="pending" <?= $status_filter==='pending'?'selected':'' ?>>Pending</option>
                    <option value="approved" <?= $status_filter==='approved'?'selected':'' ?>>Approved</option>
                    <option value="rejected" <?= $status_filter==='rejected'?'selected':'' ?>>Rejected</option>
                </select>
            </div>
            <div class="form-group col-md-4 mb-0">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by user, IP, browser..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group col-md-2 mb-0">
                <button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Devices Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($devices)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-check-circle fa-3x mb-3 text-success"></i><br>
                No devices in this category.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th class="pl-3">User</th>
                        <th>Device / Label</th>
                        <th>Browser & OS</th>
                        <th>IP Address</th>
                        <th>Requested</th>
                        <th>Last Login</th>
                        <th>Logins</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($devices as $d): ?>
                <?php
                    $row_bg = $d->status==='pending' ? 'table-warning' : ($d->status==='rejected' ? 'table-danger' : '');
                ?>
                <tr class="<?= $row_bg ?>">
                    <td class="pl-3">
                        <div class="font-weight-bold"><?= htmlspecialchars($d->user_name ?? '?') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($d->username ?? '') ?> · <?= ucfirst($d->role ?? '') ?></div>
                    </td>
                    <td>
                        <div class="small text-muted font-weight-bold" style="font-size:.72rem;">ID: <?= substr($d->device_id, 0, 16) ?>...</div>
                        <div class="input-group input-group-sm mt-1" style="max-width:160px;">
                            <input type="text" class="form-control form-control-sm label-input" style="font-size:.75rem;"
                                   placeholder="Name this device" value="<?= htmlspecialchars($d->device_label ?? '') ?>" data-id="<?= $d->id ?>">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary btn-sm save-label" data-id="<?= $d->id ?>" style="font-size:.7rem; padding:0 6px;">Save</button>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-secondary"><?= htmlspecialchars($d->browser ?? '?') ?></span><br>
                        <small class="text-muted"><?= htmlspecialchars($d->os ?? '') ?></small>
                    </td>
                    <td class="font-weight-bold"><?= htmlspecialchars($d->ip_address ?? '—') ?></td>
                    <td style="white-space:nowrap;">
                        <?= date('d M Y', strtotime($d->requested_at)) ?><br>
                        <small class="text-muted"><?= date('h:i A', strtotime($d->requested_at)) ?></small>
                    </td>
                    <td>
                        <?= $d->last_login ? date('d M, h:i A', strtotime($d->last_login)) : '<span class="text-muted small">Never</span>' ?>
                    </td>
                    <td class="text-center font-weight-bold"><?= $d->login_count ?></td>
                    <td>
                        <?php if ($d->status === 'pending'): ?>
                            <span class="badge badge-warning text-dark">⏳ Pending</span>
                        <?php elseif ($d->status === 'approved'): ?>
                            <span class="badge badge-success">✅ Approved</span>
                            <?php if ($d->reviewed_by): ?>
                            <div class="small text-muted"><?= htmlspecialchars($d->reviewed_by_name ?? '') ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-danger">❌ Rejected</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center" style="white-space:nowrap;">
                        <?php if ($d->status === 'pending'): ?>
                            <a href="<?= base_url('device_access/approve/'.$d->id) ?>" class="btn btn-success btn-sm"
                               onclick="return confirm('Approve this device for <?= htmlspecialchars(addslashes($d->user_name)) ?>?')">
                               ✅ Approve
                            </a>
                            <a href="<?= base_url('device_access/reject/'.$d->id) ?>" class="btn btn-danger btn-sm ml-1"
                               onclick="return confirm('Reject this device?')">
                               ❌ Reject
                            </a>
                        <?php elseif ($d->status === 'approved'): ?>
                            <a href="<?= base_url('device_access/revoke/'.$d->id) ?>" class="btn btn-warning btn-sm"
                               onclick="return confirm('Revoke access for this device?')">
                               🚫 Revoke
                            </a>
                        <?php else: ?>
                            <a href="<?= base_url('device_access/approve/'.$d->id) ?>" class="btn btn-success btn-sm"
                               onclick="return confirm('Re-approve this device?')">
                               ✅ Re-approve
                            </a>
                        <?php endif; ?>
                        <a href="<?= base_url('device_access/delete/'.$d->id) ?>" class="btn btn-outline-danger btn-sm ml-1"
                           onclick="return confirm('Delete this record? User will need to request again.')" title="Delete">
                           🗑
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Save device label via AJAX
document.querySelectorAll('.save-label').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.id;
        var inp = document.querySelector('.label-input[data-id="'+id+'"]');
        fetch('<?= base_url('device_access/set_label/') ?>'+id, {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
            body:'label='+encodeURIComponent(inp.value)
        }).then(r=>r.json()).then(d=>{ if(d.ok) btn.textContent='✓'; });
    });
});
</script>
