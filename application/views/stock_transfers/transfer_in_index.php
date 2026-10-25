<?php // FILE: application/views/stock_transfers/transfer_in_index.php
$tri_badges = ['in_transit' => 'primary', 'received' => 'success', 'cancelled' => 'danger'];
$tri_labels = ['in_transit' => 'In Transit', 'received' => 'Received', 'cancelled' => 'Cancelled'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0">Stock Transfers — Transfer In</h5>
        <small class="text-muted">Created automatically when a Transfer Out is approved. Receive to add stock at the destination.</small>
    </div>
    <a href="<?= base_url('stock_transfers') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-right mr-1"></i> Transfer Out
    </a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<form method="GET" class="mb-3">
    <div class="form-row align-items-end">
        <div class="form-group col-md-3 mb-2">
            <label class="small">Search</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="TRI / TRO no., item, barcode">
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">Status</label>
            <select name="status" class="form-control form-control-sm">
                <option value="">All Statuses</option>
                <?php foreach ($tri_labels as $k => $lbl): ?>
                    <option value="<?= $k ?>" <?= ($filters['status'] ?? '') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
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
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">To Date</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
        </div>
        <div class="form-group col-auto mb-2">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <a href="<?= base_url('stock_transfers/transfer_in') ?>" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Transfer In No.</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Transfer Out No.</th>
                    <th>Status</th>
                    <th>Received By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($transfer_ins)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No Transfer Ins found.</td></tr>
            <?php else: foreach ($transfer_ins as $r): ?>
                <tr>
                    <td><a href="<?= base_url('stock_transfers/transfer_in_view/' . $r->id) ?>" class="font-weight-bold"><?= htmlspecialchars($r->tri_no) ?></a></td>
                    <td><?= date('d M Y', strtotime($r->transfer_date)) ?></td>
                    <td><?= htmlspecialchars($r->from_location_name) ?></td>
                    <td><?= htmlspecialchars($r->to_location_name) ?></td>
                    <td><a href="<?= base_url('stock_transfers/view/' . $r->transfer_id) ?>"><?= htmlspecialchars($r->transfer_no) ?></a></td>
                    <td><span class="badge badge-<?= $tri_badges[$r->status] ?? 'secondary' ?>"><?= $tri_labels[$r->status] ?? ucfirst($r->status) ?></span></td>
                    <td>
                        <?php if ($r->received_by_name): ?>
                            <?= htmlspecialchars($r->received_by_name) ?><br><small class="text-muted"><?= date('d M Y H:i', strtotime($r->received_at)) ?></small>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= base_url('stock_transfers/transfer_in_view/' . $r->id) ?>" class="btn btn-xs btn-outline-<?= $r->status === 'in_transit' ? 'success' : 'primary' ?>">
                            <?= $r->status === 'in_transit' ? 'Receive' : 'View' ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
