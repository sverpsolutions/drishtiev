<?php
$return = urlencode(http_build_query(array_filter($filters) + ['page' => $page]));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-bug-fill text-danger mr-2"></i>Error Logs</h4>
        <small class="text-muted">PHP errors, warnings, and exceptions captured across the app — <?= $unresolved_count ?> unresolved</small>
    </div>
    <div class="d-flex" style="gap:.5rem">
        <?php if (!empty($logs)): ?>
        <a href="<?= base_url('error_logs/clear_resolved') ?>" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Delete all resolved entries? This cannot be undone.')">
            <i class="bi bi-trash mr-1"></i>Clear Resolved
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div><?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end">
            <div class="col-md-2">
                <label class="small text-muted mb-1">Severity</label>
                <select name="severity" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($severities as $s): ?>
                        <option value="<?= htmlspecialchars($s->severity) ?>" <?= ($filters['severity'] ?? '') === $s->severity ? 'selected' : '' ?>><?= htmlspecialchars($s->severity) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted mb-1">Status</label>
                <select name="is_resolved" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="0" <?= ($filters['is_resolved'] ?? '') === '0' ? 'selected' : '' ?>>Unresolved</option>
                    <option value="1" <?= ($filters['is_resolved'] ?? '') === '1' ? 'selected' : '' ?>>Resolved</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted mb-1">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small text-muted mb-1">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['to'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="small text-muted mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Message, file, or URL..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    <div class="input-group-append"><button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button></div>
                </div>
            </div>
            <div class="col-md-1">
                <a href="<?= base_url('error_logs') ?>" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul mr-1"></i><?= $total ?> Entries</span>
        <span class="text-muted small">Page <?= $page ?> of <?= $total_pages ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>Severity</th>
                        <th>Message</th>
                        <th>File : Line</th>
                        <th>URL</th>
                        <th>User</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="8" class="text-center text-muted small py-4">No error log entries found</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <?php
                        $sev_badge = 'secondary';
                        if (stripos($log->severity, 'error') !== false || stripos($log->severity, 'exception') !== false) $sev_badge = 'danger';
                        elseif (stripos($log->severity, 'warning') !== false) $sev_badge = 'warning';
                    ?>
                    <tr class="<?= $log->is_resolved ? 'text-muted' : '' ?>">
                        <td style="white-space:nowrap;font-size:.8rem;"><?= date('d/m/Y h:i A', strtotime($log->created_at)) ?></td>
                        <td><span class="badge badge-<?= $sev_badge ?>"><?= htmlspecialchars($log->severity) ?></span></td>
                        <td style="max-width:420px;word-break:break-word;font-size:.82rem;"><?= htmlspecialchars($log->message) ?></td>
                        <td style="font-size:.75rem;"><code><?= htmlspecialchars(basename((string)$log->file)) ?><?= $log->line ? ' : ' . $log->line : '' ?></code></td>
                        <td style="font-size:.75rem;max-width:200px;word-break:break-all;"><?= htmlspecialchars($log->url) ?></td>
                        <td style="font-size:.8rem;"><?= htmlspecialchars($log->user_name ?: '—') ?></td>
                        <td><?= $log->is_resolved ? '<span class="badge badge-success">Resolved</span>' : '<span class="badge badge-danger">Open</span>' ?></td>
                        <td style="white-space:nowrap;">
                            <?php if ($log->is_resolved): ?>
                                <a href="<?= base_url('error_logs/unresolve/' . $log->id . '?return=' . $return) ?>" class="btn btn-sm btn-outline-secondary py-0" title="Mark unresolved"><i class="bi bi-arrow-counterclockwise"></i></a>
                            <?php else: ?>
                                <a href="<?= base_url('error_logs/resolve/' . $log->id . '?return=' . $return) ?>" class="btn btn-sm btn-outline-success py-0" title="Mark resolved"><i class="bi bi-check-lg"></i></a>
                            <?php endif; ?>
                            <a href="<?= base_url('error_logs/delete/' . $log->id . '?return=' . $return) ?>" class="btn btn-sm btn-outline-danger py-0" title="Delete" onclick="return confirm('Delete this log entry?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center py-2">
        <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= base_url('error_logs?' . http_build_query(array_filter($filters) + ['page' => max(1, $page - 1)])) ?>">&laquo; Prev</a>
        <span class="small text-muted">Page <?= $page ?> of <?= $total_pages ?></span>
        <a class="btn btn-sm btn-outline-secondary <?= $page >= $total_pages ? 'disabled' : '' ?>" href="<?= base_url('error_logs?' . http_build_query(array_filter($filters) + ['page' => min($total_pages, $page + 1)])) ?>">Next &raquo;</a>
    </div>
    <?php endif; ?>
</div>
