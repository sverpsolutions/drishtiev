
<div class="container-fluid mt-3">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-file-alt text-primary"></i> Estimates / Quotations</h4>
    <a href="<?= base_url('estimates/create') ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> New Estimate
    </a>
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

<!-- Filters -->
<div class="card shadow-sm mb-3">
<div class="card-body py-2">
<form method="get" class="form-row align-items-end">
    <div class="col-md-2">
        <label class="small mb-1">Status</label>
        <select name="status" class="form-control form-control-sm">
            <option value="">All</option>
            <?php foreach (['pending','converted','closed_cash','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= ($filters['status']==$s)?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="small mb-1">From Date</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $filters['date_from'] ?>">
    </div>
    <div class="col-md-2">
        <label class="small mb-1">To Date</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $filters['date_to'] ?>">
    </div>
    <div class="col-md-3">
        <label class="small mb-1">Customer Name</label>
        <input type="text" name="customer_name" class="form-control form-control-sm" placeholder="Search..." value="<?= htmlspecialchars($filters['customer_name']) ?>">
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-sm btn-primary mr-1">Filter</button>
        <a href="<?= base_url('estimates') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>
</div>
</div>

<!-- Stats Bar -->
<?php
$pending   = array_filter($estimates, fn($e) => $e->status === 'pending');
$cash      = array_filter($estimates, fn($e) => $e->status === 'closed_cash');
$converted = array_filter($estimates, fn($e) => $e->status === 'converted');
?>
<div class="row mb-3">
    <div class="col-md-3"><div class="card border-warning text-center py-2">
        <div class="font-weight-bold text-warning"><?= count($pending) ?></div><small>Pending</small></div></div>
    <div class="col-md-3"><div class="card border-success text-center py-2">
        <div class="font-weight-bold text-success"><?= count($cash) ?></div><small>Cash Closed (No Bill)</small></div></div>
    <div class="col-md-3"><div class="card border-info text-center py-2">
        <div class="font-weight-bold text-info"><?= count($converted) ?></div><small>Converted to Bill</small></div></div>
    <div class="col-md-3"><div class="card border-secondary text-center py-2">
        <div class="font-weight-bold">&#8377; <?= number_format(array_sum(array_column($estimates,'total_amount')),2) ?></div><small>Total Value</small></div></div>
</div>

<!-- Table -->
<div class="card shadow-sm">
<div class="card-body p-0">
<table class="table table-sm table-hover table-bordered mb-0">
<thead class="thead-dark">
<tr>
    <th>#</th>
    <th>Estimate No</th>
    <th>Customer</th>
    <th>Mobile</th>
    <th>Date</th>
    <th>Valid Till</th>
    <th class="text-right">Amount</th>
    <th>Status</th>
    <th>Created By</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php if (empty($estimates)): ?>
<tr><td colspan="10" class="text-center text-muted py-4">No estimates found.</td></tr>
<?php endif; ?>
<?php foreach ($estimates as $e): ?>
<tr>
    <td><?= $e->id ?></td>
    <td><a href="<?= base_url('estimates/view/'.$e->id) ?>" class="font-weight-bold"><?= $e->estimate_no ?></a></td>
    <td><?= htmlspecialchars($e->customer_name) ?></td>
    <td><?= $e->customer_mobile ?></td>
    <td><?= date('d-M-Y', strtotime($e->estimate_date)) ?></td>
    <td><?= $e->valid_until ? date('d-M-Y', strtotime($e->valid_until)) : '-' ?></td>
    <td class="text-right">&#8377; <?= number_format($e->total_amount, 2) ?></td>
    <td>
        <?php
        $badges = ['pending'=>'warning','converted'=>'info','closed_cash'=>'success','cancelled'=>'danger'];
        $labels = ['pending'=>'Pending','converted'=>'Converted','closed_cash'=>'Cash Closed','cancelled'=>'Cancelled'];
        ?>
        <span class="badge badge-<?= $badges[$e->status]??'secondary' ?>"><?= $labels[$e->status]??$e->status ?></span>
    </td>
    <td><?= $e->created_by_name ?></td>
    <td class="text-nowrap">
        <a href="<?= base_url('estimates/view/'.$e->id) ?>" class="btn btn-xs btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
        <a href="<?= base_url('estimates/print_estimate/'.$e->id) ?>" class="btn btn-xs btn-outline-secondary" target="_blank" title="Print"><i class="fas fa-print"></i></a>
        <?php if ($e->status === 'pending'): ?>
        <a href="<?= base_url('estimates/edit/'.$e->id) ?>" class="btn btn-xs btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
        <a href="<?= base_url('estimates/convert_to_invoice/'.$e->id) ?>" class="btn btn-xs btn-outline-info" title="Convert to Invoice" onclick="return confirm('Convert to Sales Invoice?')"><i class="fas fa-file-invoice"></i></a>
        <a href="<?= base_url('estimates/close_cash/'.$e->id) ?>" class="btn btn-xs btn-outline-success" title="Close as Cash Sale" onclick="return confirm('Mark as Cash Sale (No GST Bill)?')"><i class="fas fa-money-bill-wave"></i></a>
        <a href="<?= base_url('estimates/cancel/'.$e->id) ?>" class="btn btn-xs btn-outline-danger" title="Cancel" onclick="return confirm('Cancel this estimate?')"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

