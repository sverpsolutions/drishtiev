<?php // FILE: application/views/reports/transfers_report.php ?>
<div class="mb-3">
    <h5 class="font-weight-bold mb-0">Stock Transfers Report</h5>
</div>

<form method="GET" class="mb-3">
    <div class="form-row align-items-end">
        <div class="form-group col-md-2 mb-2">
            <label class="small">Status</label>
            <select name="status" class="form-control form-control-sm">
                <option value="">All</option>
                <?php foreach (['pending','in_transit','approved','rejected','completed'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s === 'in_transit' ? 'In Transit' : ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">TRO No.</label>
            <input type="text" name="tro_no" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['tro_no'] ?? '') ?>" placeholder="TRO-WH-0001">
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">TRI No.</label>
            <input type="text" name="tri_no" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['tri_no'] ?? '') ?>" placeholder="TRI-ANA-0001">
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">Item / Barcode</label>
            <input type="text" name="item" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['item'] ?? '') ?>">
        </div>
        <?php if (count($locations ?? []) > 1): ?>
        <div class="form-group col-md-2 mb-2">
            <label class="small">From Location</label>
            <select name="from_location_id" class="form-control form-control-sm">
                <option value="">All</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= ($filters['from_location_id'] ?? '') == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">To Location</label>
            <select name="to_location_id" class="form-control form-control-sm">
                <option value="">All</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= ($filters['to_location_id'] ?? '') == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="form-group col-md-2 mb-2">
            <label class="small">From Date</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $filters['date_from'] ?? '' ?>">
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">To Date</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $filters['date_to'] ?? '' ?>">
        </div>
        <div class="form-group col-auto mb-2">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <a href="<?= base_url('reports/transfers_report') ?>" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
        </div>
    </div>
</form>

<?php
$total = count($transfers);
$completed = array_filter($transfers, fn($t) => $t->status === 'completed');
$pending   = array_filter($transfers, fn($t) => $t->status === 'pending');
$rejected  = array_filter($transfers, fn($t) => $t->status === 'rejected');
$in_transit = array_filter($transfers, fn($t) => $t->status === 'in_transit');
?>
<div class="row mb-3">
    <div class="col-md-3 mb-2"><div class="card border-0 shadow-sm text-center py-3"><div class="h5 mb-0"><?= $total ?></div><small class="text-muted">Total</small></div></div>
    <div class="col-md-3 mb-2"><div class="card border-0 shadow-sm text-center py-3"><div class="h5 mb-0 text-success"><?= count($completed) ?></div><small class="text-muted">Completed</small></div></div>
    <div class="col-md-3 mb-2"><div class="card border-0 shadow-sm text-center py-3"><div class="h5 mb-0 text-warning"><?= count($pending) ?> <small class="text-primary">/ <?= count($in_transit) ?></small></div><small class="text-muted">Pending / In Transit</small></div></div>
    <div class="col-md-3 mb-2"><div class="card border-0 shadow-sm text-center py-3"><div class="h5 mb-0 text-danger"><?= count($rejected) ?></div><small class="text-muted">Rejected</small></div></div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>TRO No.</th>
                    <th>TRI No.</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Status</th>
                    <th>Requested By</th>
                    <th>Date</th>
                    <th>Approved By</th>
                    <th>Completed</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($transfers)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No transfers found.</td></tr>
            <?php else: ?>
            <?php
            $badges = ['draft'=>'secondary','pending'=>'warning','approved'=>'info','rejected'=>'danger','completed'=>'success','in_transit'=>'primary'];
            foreach ($transfers as $t): ?>
                <tr>
                    <td><a href="<?= base_url('stock_transfers/view/' . $t->id) ?>"><?= htmlspecialchars($t->transfer_no) ?></a></td>
                    <td><?php if (!empty($t->tri_id)): ?><a href="<?= base_url('stock_transfers/transfer_in_view/' . $t->tri_id) ?>"><?= htmlspecialchars($t->tri_no) ?></a><?php else: ?>—<?php endif; ?></td>
                    <td><?= htmlspecialchars($t->from_location_name) ?></td>
                    <td><?= htmlspecialchars($t->to_location_name) ?></td>
                    <td><span class="badge badge-<?= $badges[$t->status] ?? 'secondary' ?>"><?= $t->status === 'in_transit' ? 'In Transit' : ucfirst($t->status) ?></span></td>
                    <td><?= htmlspecialchars($t->requested_by_name) ?></td>
                    <td><?= date('d M Y', strtotime($t->requested_at)) ?></td>
                    <td><?= $t->approved_by_name ? htmlspecialchars($t->approved_by_name) : '—' ?></td>
                    <td><?= $t->completed_at ? date('d M Y', strtotime($t->completed_at)) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
