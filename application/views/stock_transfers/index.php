<?php // FILE: application/views/stock_transfers/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0">Stock Transfers — Transfer Out</h5>
        <small class="text-muted">Inter-location stock movement requests</small>
    </div>
    <div>
        <a href="<?= base_url('stock_transfers/transfer_in') ?>" class="btn btn-outline-success btn-sm mr-1">
            <i class="fas fa-arrow-left mr-1"></i> Transfer In
            <?php if (!empty($in_transit_count)): ?><span class="badge badge-primary ml-1"><?= (int)$in_transit_count ?> in transit</span><?php endif; ?>
        </a>
        <a href="<?= base_url('stock_transfers/create') ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> New Transfer
        </a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $this->session->flashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $this->session->flashdata('error') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<!-- Filter Bar -->
<form method="GET" class="mb-3">
    <div class="form-row align-items-end">
        <div class="form-group col-md-3 mb-2">
            <label class="small">Search</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="TRO / TRI no., item, barcode">
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">Status</label>
            <select name="status" class="form-control form-control-sm">
                <option value="">All Statuses</option>
                <?php foreach (['draft','pending','in_transit','approved','rejected','completed'] as $st): ?>
                    <option value="<?= $st ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st === 'in_transit' ? 'In Transit' : ucfirst($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($is_owner): ?>
        <div class="form-group col-md-2 mb-2">
            <label class="small">From</label>
            <select name="from_location_id" class="form-control form-control-sm">
                <option value="">Any Location</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= ($filters['from_location_id'] ?? '') == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">To</label>
            <select name="to_location_id" class="form-control form-control-sm">
                <option value="">Any Location</option>
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
        <div class="form-group col-md-1 mb-2">
            <button type="submit" class="btn btn-secondary btn-sm w-100">Filter</button>
        </div>
        <div class="form-group col-md-1 mb-2">
            <a href="<?= base_url('stock_transfers') ?>" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
        </div>
    </div>
</form>

<?php
$status_badges = [
    'draft'     => 'secondary',
    'pending'   => 'warning',
    'approved'  => 'info',
    'rejected'  => 'danger',
    'completed' => 'success',
    'in_transit'=> 'primary',
];
?>
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Transfer Out No.</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Transfer In No.</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Requested By</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($transfers)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No transfers found.</td></tr>
            <?php else: ?>
            <?php foreach ($transfers as $t): ?>
                <tr>
                    <td><a href="<?= base_url('stock_transfers/view/' . $t->id) ?>" class="font-weight-bold"><?= htmlspecialchars($t->transfer_no) ?></a></td>
                    <td><?= htmlspecialchars($t->from_location_name) ?></td>
                    <td><?= htmlspecialchars($t->to_location_name) ?></td>
                    <td><?php if (!empty($t->tri_id)): ?><a href="<?= base_url('stock_transfers/transfer_in_view/' . $t->tri_id) ?>"><?= htmlspecialchars($t->tri_no) ?></a><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
                    <td><small class="text-muted"><?= str_replace('_', ' ', $t->transfer_type) ?></small></td>
                    <td><span class="badge badge-<?= $status_badges[$t->status] ?? 'secondary' ?>"><?= $t->status === 'in_transit' ? 'In Transit' : ucfirst($t->status) ?></span></td>
                    <td><?= htmlspecialchars($t->requested_by_name) ?></td>
                    <td><?= date('d M Y', strtotime($t->requested_at)) ?></td>
                    <td>
                        <a href="<?= base_url('stock_transfers/view/' . $t->id) ?>" class="btn btn-xs btn-outline-primary">View</a>
                        <?php
                            $__fp = $this->session->userdata('form_perms') ?: [];
                            $__edit_ok = !isset($__fp['frm_stock_transfers_edit']) || !empty($__fp['frm_stock_transfers_edit']);
                            $can_edit_row = in_array($t->status, ['draft','pending'])
                                && ($is_owner || $this->session->userdata('role') === 'admin'
                                    || ($__edit_ok && (int)$t->requested_by === (int)$this->session->userdata('user_id')));
                        ?>
                        <?php if ($can_edit_row): ?>
                            <a href="<?= base_url('stock_transfers/edit/' . $t->id) ?>" class="btn btn-xs btn-outline-info">Edit</a>
                        <?php endif; ?>
                        <?php if ($t->status === 'draft' && ($is_owner || $this->session->userdata('role') === 'admin')): ?>
                            <a href="<?= base_url('stock_transfers/delete/' . $t->id) ?>"
                               onclick="return confirm('Delete this draft?')"
                               class="btn btn-xs btn-outline-danger">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
