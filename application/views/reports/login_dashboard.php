<?php
$total_users   = count($users);
$active_users  = count(array_filter($users, fn($u) => $u->status == 1));
$pending_devs  = $device_counts['pending'] ?? 0;
$approved_devs = $device_counts['approved'] ?? 0;
$rejected_devs = $device_counts['rejected'] ?? 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Login Dashboard</h5>
        <small class="text-muted">User logins, devices &amp; access control</small>
    </div>
    <a href="<?= base_url('reports') ?>" class="btn btn-outline-secondary btn-sm">&larr; Reports</a>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm text-center" style="border-radius:10px;">
            <div class="card-body py-3">
                <div class="text-muted" style="font-size:.75rem;">Logins Today</div>
                <div class="font-weight-bold text-primary" style="font-size:1.8rem;"><?= $logins_today ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm text-center" style="border-radius:10px;">
            <div class="card-body py-3">
                <div class="text-muted" style="font-size:.75rem;">Active Users</div>
                <div class="font-weight-bold text-success" style="font-size:1.8rem;"><?= $active_users ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm text-center" style="border-radius:10px;">
            <div class="card-body py-3">
                <div class="text-muted" style="font-size:.75rem;">Approved Devices</div>
                <div class="font-weight-bold text-info" style="font-size:1.8rem;"><?= $approved_devs ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm text-center" style="border-radius:10px;">
            <div class="card-body py-3">
                <div class="text-muted" style="font-size:.75rem;">Pending Devices</div>
                <div class="font-weight-bold <?= $pending_devs > 0 ? 'text-danger' : 'text-muted' ?>" style="font-size:1.8rem;"><?= $pending_devs ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:10px;">
    <div class="card-header bg-dark text-white py-2 font-weight-bold" style="border-radius:10px 10px 0 0; font-size:.9rem;">Users</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Location</th>
                        <th>Last Login</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="font-weight-bold"><?= htmlspecialchars($u->name) ?></td>
                        <td><code><?= htmlspecialchars($u->username) ?></code></td>
                        <td><span class="badge badge-<?= $u->role === 'superadmin' ? 'dark' : ($u->role === 'owner' ? 'primary' : ($u->role === 'admin' ? 'info' : 'secondary')) ?>"><?= ucfirst($u->role) ?></span></td>
                        <td><?= $u->location_id ? htmlspecialchars($locations[$u->location_id] ?? 'Loc #'.$u->location_id) : '<span class="text-muted">All</span>' ?></td>
                        <td><?= $u->last_login ? date('d M Y, h:i A', strtotime($u->last_login)) : '<span class="text-muted">Never</span>' ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?= $u->status == 1 ? 'success' : 'danger' ?>"><?= $u->status == 1 ? 'Active' : 'Inactive' ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Registered Devices -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:10px;">
    <div class="card-header bg-dark text-white py-2 font-weight-bold" style="border-radius:10px 10px 0 0; font-size:.9rem;">Registered Devices</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Browser / OS</th>
                        <th>Screen</th>
                        <th>IP Address</th>
                        <th>Label</th>
                        <th class="text-center">Status</th>
                        <th>Requested</th>
                        <th>Last Login</th>
                        <th class="text-right">Logins</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($devices as $i => $d): ?>
                    <tr id="dev-row-<?= $d->id ?>">
                        <td><?= $i + 1 ?></td>
                        <td class="font-weight-bold"><?= htmlspecialchars($d->user_name ?? '?') ?><br><small class="text-muted"><?= htmlspecialchars($d->username ?? '') ?></small></td>
                        <td><?= htmlspecialchars($d->browser ?? '?') ?><br><small class="text-muted"><?= htmlspecialchars($d->os ?? '') ?></small></td>
                        <td><small><?= htmlspecialchars($d->screen_info ?: '-') ?></small></td>
                        <td><code style="font-size:.8rem;"><?= htmlspecialchars($d->ip_address ?: '-') ?></code></td>
                        <td><?= htmlspecialchars($d->device_label ?: '-') ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?= $d->status === 'approved' ? 'success' : ($d->status === 'pending' ? 'warning' : 'danger') ?>" id="dev-status-<?= $d->id ?>">
                                <?= ucfirst($d->status) ?>
                            </span>
                        </td>
                        <td><small><?= $d->requested_at ? date('d M, h:i A', strtotime($d->requested_at)) : '-' ?></small></td>
                        <td><small><?= $d->last_login ? date('d M, h:i A', strtotime($d->last_login)) : '-' ?></small></td>
                        <td class="text-right font-weight-bold"><?= (int)$d->login_count ?></td>
                        <td class="text-center text-nowrap" id="dev-actions-<?= $d->id ?>">
                            <?php if ($d->status === 'pending'): ?>
                                <button class="btn btn-success btn-sm py-0 px-2" onclick="deviceAction(<?= $d->id ?>, 'approved')">Approve</button>
                                <button class="btn btn-danger btn-sm py-0 px-2" onclick="deviceAction(<?= $d->id ?>, 'rejected')">Reject</button>
                            <?php elseif ($d->status === 'approved'): ?>
                                <button class="btn btn-outline-danger btn-sm py-0 px-2" onclick="deviceAction(<?= $d->id ?>, 'rejected')">Revoke</button>
                            <?php else: ?>
                                <button class="btn btn-outline-success btn-sm py-0 px-2" onclick="deviceAction(<?= $d->id ?>, 'approved')">Re-approve</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($devices)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No devices registered yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Login History -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:10px;">
    <div class="card-header bg-dark text-white py-2 font-weight-bold" style="border-radius:10px 10px 0 0; font-size:.9rem;">Recent Login History (Last 50)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>IP Address</th>
                        <th>Login Time</th>
                        <th>Ago</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent_logins as $i => $l): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="font-weight-bold"><?= htmlspecialchars($l->user_name) ?></td>
                        <td><code style="font-size:.8rem;"><?= htmlspecialchars($l->ip_address ?: '-') ?></code></td>
                        <td><?= date('d M Y, h:i:s A', strtotime($l->created_at)) ?></td>
                        <td><small class="text-muted"><?php
                            $diff = time() - strtotime($l->created_at);
                            if ($diff < 60) echo $diff . 's ago';
                            elseif ($diff < 3600) echo floor($diff/60) . 'm ago';
                            elseif ($diff < 86400) echo floor($diff/3600) . 'h ago';
                            else echo floor($diff/86400) . 'd ago';
                        ?></small></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_logins)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No login records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function deviceAction(id, action) {
    var label = action === 'approved' ? 'approve' : (action === 'rejected' ? 'reject/revoke' : action);
    if (!confirm('Are you sure you want to ' + label + ' this device?')) return;

    fetch('<?= base_url('reports/device_action_ajax') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&action=' + encodeURIComponent(action)
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            var badge = document.getElementById('dev-status-' + id);
            badge.className = 'badge badge-' + (action === 'approved' ? 'success' : 'danger');
            badge.textContent = action === 'approved' ? 'Approved' : 'Rejected';

            var acts = document.getElementById('dev-actions-' + id);
            if (action === 'approved') {
                acts.innerHTML = '<button class="btn btn-outline-danger btn-sm py-0 px-2" onclick="deviceAction(' + id + ', \'rejected\')">Revoke</button>';
            } else {
                acts.innerHTML = '<button class="btn btn-outline-success btn-sm py-0 px-2" onclick="deviceAction(' + id + ', \'approved\')">Re-approve</button>';
            }
        } else {
            alert(d.message || 'Failed');
        }
    })
    .catch(function() { alert('Network error'); });
}
</script>
