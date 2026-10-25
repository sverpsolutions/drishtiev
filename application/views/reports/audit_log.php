<?php
$action_colors = [
    'login'=>'info','logout'=>'secondary','create'=>'success','create_bill'=>'success','create_user'=>'success',
    'edit'=>'warning','edit_user'=>'warning','price_change'=>'danger','update'=>'warning',
    'delete'=>'danger','cancel'=>'danger','cancel_bill'=>'danger',
    'wallet_adjust'=>'warning','toggle_status'=>'warning',
    'open'=>'primary','close'=>'dark',
    'accept'=>'success','finalize'=>'success',
    'payment_receipt'=>'info',
];
function action_badge($action, $action_colors) {
    $lc = strtolower($action);
    $color = $action_colors[$lc] ?? (
        str_contains($lc,'delete')||str_contains($lc,'cancel') ? 'danger' : (
        str_contains($lc,'edit')||str_contains($lc,'update')||str_contains($lc,'change') ? 'warning' : (
        str_contains($lc,'create')||str_contains($lc,'add') ? 'success' : 'secondary'
    )));
    return '<span class="badge badge-'.$color.'">'.strtoupper(str_replace('_',' ',$action)).'</span>';
}
?>
<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-shield-alt text-danger mr-2"></i>Audit Log</h5>
        <small class="text-muted"><?= date('d M Y', strtotime($from)) ?> → <?= date('d M Y', strtotime($to)) ?> &nbsp;·&nbsp; <?= number_format($total_count) ?> events</small>
    </div>
    <div>
        <?php
        $qp = http_build_query(['from'=>$from,'to'=>$to,'user_id'=>$user_id,'module'=>$sel_module,'action_f'=>$sel_action,'search'=>$search,'location_id'=>$location_id??0,'export'=>'csv']);
        ?>
        <a href="<?= base_url('reports/audit_log?'.$qp) ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-file-csv mr-1"></i>Export CSV</a>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm ml-1"><i class="fas fa-print mr-1"></i>Print</button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-3 d-print-none">
    <div class="card-body py-2">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-1 mb-0">
                <label class="small font-weight-bold mb-1">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
            </div>
            <div class="form-group col-md-1 mb-0">
                <label class="small font-weight-bold mb-1">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">User</label>
                <select name="user_id" class="form-control form-control-sm">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u->id ?>" <?= $user_id == $u->id ? 'selected':'' ?>><?= htmlspecialchars($u->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">Module</label>
                <select name="module" class="form-control form-control-sm">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $m): ?>
                    <option value="<?= htmlspecialchars($m->module) ?>" <?= $sel_module==$m->module?'selected':'' ?>><?= htmlspecialchars(ucfirst(str_replace('_',' ',$m->module))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">Action</label>
                <select name="action_f" class="form-control form-control-sm">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $a): ?>
                    <option value="<?= htmlspecialchars($a->action) ?>" <?= $sel_action==$a->action?'selected':'' ?>><?= htmlspecialchars(strtoupper(str_replace('_',' ',$a->action))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">Search Description</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($search) ?>" placeholder="Invoice#, name, note...">
            </div>
            <div class="form-group col-md-2 mb-0 d-flex" style="gap:4px;">
                <button type="submit" class="btn btn-danger btn-sm flex-fill">Filter</button>
                <a href="<?= base_url('reports/audit_log') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-0 bg-dark text-white text-center py-2 shadow-sm">
            <div class="small text-muted">Total Events</div>
            <div class="font-weight-bold h4 mb-0"><?= number_format($total_count) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 bg-danger text-white text-center py-2 shadow-sm">
            <div class="small">Sensitive Actions</div>
            <div class="font-weight-bold h4 mb-0"><?= count($sensitive_logs) ?></div>
            <div class="small" style="font-size:.7rem;">deletes · cancels · price changes</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 bg-primary text-white text-center py-2 shadow-sm">
            <div class="small">Active Users</div>
            <div class="font-weight-bold h4 mb-0"><?= count($by_user) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 bg-success text-white text-center py-2 shadow-sm">
            <div class="small">Modules Tracked</div>
            <div class="font-weight-bold h4 mb-0"><?= count($modules) ?></div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <!-- Activity by Module/Action breakdown -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-dark text-white py-2 font-weight-bold" style="font-size:.84rem;">Activity Breakdown</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.8rem;">
                    <thead class="thead-light"><tr><th class="pl-3">Module</th><th>Action</th><th class="text-right pr-3">Count</th></tr></thead>
                    <tbody>
                    <?php foreach ($stats as $s): ?>
                    <tr>
                        <td class="pl-3"><?= htmlspecialchars(ucfirst(str_replace('_',' ',$s->module))) ?></td>
                        <td><?= action_badge($s->action, $action_colors) ?></td>
                        <td class="text-right pr-3 font-weight-bold"><?= $s->cnt ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Users -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-dark text-white py-2 font-weight-bold" style="font-size:.84rem;">Most Active Users</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.8rem;">
                    <thead class="thead-light"><tr><th class="pl-3">User</th><th class="text-right pr-3">Actions</th></tr></thead>
                    <tbody>
                    <?php $max_u = max(array_column($by_user,'cnt') ?: [1]); foreach ($by_user as $u): ?>
                    <tr>
                        <td class="pl-3">
                            <?= htmlspecialchars($u->user_name ?? 'Unknown') ?>
                            <div class="progress mt-1" style="height:3px;"><div class="progress-bar bg-primary" style="width:<?= round($u->cnt/$max_u*100) ?>%"></div></div>
                        </td>
                        <td class="text-right pr-3 font-weight-bold"><?= $u->cnt ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sensitive Actions Panel -->
    <div class="col-md-4">
        <div class="card border-danger shadow-sm h-100" style="border-width:2px!important;">
            <div class="card-header bg-danger text-white py-2 font-weight-bold" style="font-size:.84rem;">
                <i class="fas fa-exclamation-triangle mr-1"></i> Sensitive / Critical Actions
            </div>
            <div class="card-body p-0" style="max-height:220px;overflow-y:auto;">
                <?php if (empty($sensitive_logs)): ?>
                    <div class="text-center text-muted py-3 small">No sensitive actions in this period</div>
                <?php else: ?>
                <table class="table table-sm mb-0" style="font-size:.76rem;">
                    <tbody>
                    <?php foreach ($sensitive_logs as $sl): ?>
                    <tr class="table-danger">
                        <td class="pl-2 py-1">
                            <div class="font-weight-bold"><?= htmlspecialchars($sl->user_name ?? '?') ?></div>
                            <div class="text-muted"><?= date('d-M H:i', strtotime($sl->created_at)) ?></div>
                        </td>
                        <td class="py-1"><?= action_badge($sl->action, $action_colors) ?><br><span class="text-muted"><?= htmlspecialchars(ucfirst($sl->module)) ?></span></td>
                        <td class="py-1 pr-2" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($sl->description) ?>">
                            <?= htmlspecialchars(mb_substr($sl->description, 0, 60)) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Main Log Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold" style="font-size:.85rem;">Activity Log — Page <?= $page ?> of <?= $total_pages ?: 1 ?></span>
        <span class="small text-muted"><?= number_format($total_count) ?> total records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.8rem;">
                <thead class="thead-dark">
                    <tr>
                        <th class="pl-3">#</th>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Location</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No audit log entries found.</td></tr>
                <?php else: $start = ($page-1)*$per_page + 1; foreach ($logs as $i => $log):
                    $al = strtolower($log->action ?? '');
                    $row_class = str_contains($al,'delete')||str_contains($al,'cancel') ? 'table-danger' :
                                (str_contains($al,'price_change')||str_contains($al,'wallet_adjust')||str_contains($al,'toggle') ? 'table-warning' :
                                (str_contains($al,'create')||str_contains($al,'add')||str_contains($al,'accept') ? 'table-success' : ''));
                ?>
                <tr class="<?= $row_class ?>">
                    <td class="pl-3 text-muted"><?= $start + $i ?></td>
                    <td style="white-space:nowrap;"><?= date('d-M-Y', strtotime($log->created_at)) ?><br><small class="text-muted"><?= date('H:i:s', strtotime($log->created_at)) ?></small></td>
                    <td class="font-weight-bold"><?= htmlspecialchars($log->user_name ?? 'System') ?></td>
                    <td><?= htmlspecialchars($log->location_name ?? '—') ?></td>
                    <td><?= htmlspecialchars(ucfirst(str_replace('_',' ',$log->module ?? ''))) ?></td>
                    <td><?= action_badge($log->action ?? '', $action_colors) ?></td>
                    <td class="font-weight-bold small"><?= htmlspecialchars($log->record_no ?? '—') ?></td>
                    <td style="max-width:300px;"><?= htmlspecialchars($log->description ?? '') ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($log->ip_address ?? '') ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1):
$base_url_pag = base_url('reports/audit_log?').http_build_query(['from'=>$from,'to'=>$to,'user_id'=>$user_id,'module'=>$sel_module,'action_f'=>$sel_action,'search'=>$search,'location_id'=>$location_id??0]);
?>
<nav class="mt-3 d-print-none">
    <ul class="pagination pagination-sm justify-content-center flex-wrap">
        <li class="page-item <?= $page<=1?'disabled':'' ?>">
            <a class="page-link" href="<?= $base_url_pag ?>&page=<?= $page-1 ?>">‹ Prev</a>
        </li>
        <?php for ($p = max(1,$page-3); $p <= min($total_pages,$page+3); $p++): ?>
        <li class="page-item <?= $p==$page?'active':'' ?>">
            <a class="page-link" href="<?= $base_url_pag ?>&page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
            <a class="page-link" href="<?= $base_url_pag ?>&page=<?= $page+1 ?>">Next ›</a>
        </li>
    </ul>
    <div class="text-center text-muted small">Showing <?= number_format(($page-1)*$per_page+1) ?>–<?= number_format(min($total_count,$page*$per_page)) ?> of <?= number_format($total_count) ?> events</div>
</nav>
<?php endif; ?>

<style>
@media print {
    .d-print-none { display:none!important; }
    .card { border:1px solid #dee2e6!important; box-shadow:none!important; }
    body { font-size:10px; }
}
</style>
