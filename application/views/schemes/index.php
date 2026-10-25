<?php // Schemes Index ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-tags-fill text-primary mr-2"></i>Scheme Management</h4>
        <small class="text-muted">Schemes: Qty Slab, Free Items, Mix &amp; Match, Target, Cash Discount, Credit Note</small>
    </div>
    <div class="d-flex" style="gap:.5rem">
        <a href="<?= base_url('schemes/simulate') ?>" class="btn btn-outline-info btn-sm"><i class="bi bi-play-circle mr-1"></i>Simulate</a>
        <a href="<?= base_url('schemes/item_list') ?>" class="btn btn-outline-dark btn-sm"><i class="bi bi-list-check mr-1"></i>Item List</a>
        <a href="<?= base_url('schemes/report') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-bar-chart mr-1"></i>Report</a>
        <a href="<?= base_url('schemes/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>New Scheme</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div><?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end">
            <div class="col-md-3">
                <select name="active" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="1" <?= ($filters['is_active']??'')==='1'?'selected':'' ?>>Active</option>
                    <option value="0" <?= ($filters['is_active']??'')==='0'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="type_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t->id ?>" <?= ($filters['type_id']??'')==$t->id?'selected':'' ?>><?= htmlspecialchars($t->label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="q" class="form-control" placeholder="Search scheme..." value="<?= htmlspecialchars($filters['search']??'') ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-2"><a href="<?= base_url('schemes') ?>" class="btn btn-outline-secondary btn-sm w-100">Reset</a></div>
        </form>
    </div>
</div>

<?php
$today         = date('Y-m-d');
$active_count  = count(array_filter($schemes, fn($s) => $s->is_active && $s->end_date >= $today));
$expired_count = count(array_filter($schemes, fn($s) => $s->end_date < $today));
?>
<div class="row mb-3">
    <div class="col-md-3"><div class="card bg-success text-white shadow-sm"><div class="card-body py-2 text-center"><div class="small">Active Now</div><div class="h4 font-weight-bold"><?= $active_count ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-warning text-dark shadow-sm"><div class="card-body py-2 text-center"><div class="small">Expired</div><div class="h4 font-weight-bold"><?= $expired_count ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-primary text-white shadow-sm"><div class="card-body py-2 text-center"><div class="small">Total</div><div class="h4 font-weight-bold"><?= count($schemes) ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-info text-white shadow-sm"><div class="card-body py-2 text-center"><div class="small">Scheme Types</div><div class="h4 font-weight-bold"><?= count($types) ?></div></div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Scheme Name</th><th>Type</th><th>Apply On</th><th class="text-center">Priority</th>
                        <th>Period</th><th>Trigger</th><th>Benefit</th><th>Parties</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($schemes)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-5">
                        <i class="bi bi-tags fs-1 d-block mb-2 opacity-25"></i>No schemes yet.
                        <a href="<?= base_url('schemes/create') ?>">Create your first scheme &rarr;</a>
                    </td></tr>
                <?php else: ?>
                <?php foreach ($schemes as $s):
                    $expired  = $s->end_date < $today;
                    $upcoming = $s->start_date > $today;
                ?>
                <tr class="<?= !$s->is_active ? 'table-secondary' : ($expired ? 'table-warning' : '') ?>">
                    <td>
                        <a href="<?= base_url('schemes/view/'.$s->id) ?>" class="font-weight-bold text-decoration-none"><?= htmlspecialchars($s->name) ?></a>
                        <?php if ($upcoming): ?><span class="badge badge-info ml-1">Upcoming</span><?php endif; ?>
                        <?php if ($expired && $s->is_active): ?><span class="badge badge-warning text-dark ml-1">Expired</span><?php endif; ?>
                        <?php if ($s->is_recursive): ?><span class="badge badge-success ml-1 small">Recursive</span><?php endif; ?>
                        <?php if (!$s->is_combination_allowed): ?><span class="badge badge-danger ml-1 small">Exclusive</span><?php endif; ?>
                        <?php if ($s->notes): ?><div class="text-muted small"><?= htmlspecialchars(substr($s->notes,0,50)) ?></div><?php endif; ?>
                    </td>
                    <td><span class="badge badge-primary"><?= htmlspecialchars($s->type_label) ?></span></td>
                    <td><span class="badge badge-secondary"><?= ucfirst($s->apply_on) ?></span></td>
                    <td class="text-center">
                        <span class="badge <?= $s->priority<=3?'bg-danger':($s->priority<=6?'bg-warning text-dark':'bg-light text-dark border') ?>"><?= $s->priority ?></span>
                    </td>
                    <td class="small">
                        <div><?= date('d/m/Y',strtotime($s->start_date)) ?></div>
                        <div class="text-muted">&rarr; <?= date('d/m/Y',strtotime($s->end_date)) ?></div>
                    </td>
                    <td class="small">
                        <?php if ($s->min_qty > 0): ?><div>Min Qty: <strong><?= rtrim(rtrim($s->min_qty,'0'),'.') ?> <?= strtoupper($s->min_qty_uom) ?></strong></div><?php endif; ?>
                        <?php if ($s->min_value > 0): ?><div>Min Val: <strong>&#8377;<?= number_format($s->min_value,0) ?></strong></div><?php endif; ?>
                        <?php if ($s->target_amount > 0): ?><div>Target: <strong>&#8377;<?= number_format($s->target_amount,0) ?></strong></div><?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if ($s->discount_percent > 0): ?><span class="badge badge-success"><?= $s->discount_percent ?>% OFF</span> <?php endif; ?>
                        <?php if ($s->discount_value > 0): ?><span class="badge badge-success">&#8377;<?= $s->discount_value ?> OFF</span> <?php endif; ?>
                        <?php if ($s->free_qty > 0): ?><span class="badge badge-primary"><?= rtrim(rtrim($s->free_qty,'0'),'.') ?> Free</span> <?php endif; ?>
                        <?php if ($s->credit_note_percent > 0): ?><span class="badge badge-warning text-dark">CN <?= $s->credit_note_percent ?>%</span><?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?= $s->applicable_parties==='all' ? '<span class="badge badge-success">All</span>' : '<span class="badge badge-info">Selected</span>' ?>
                    </td>
                    <td>
                        <?php if ($s->is_active && !$expired): ?>
                            <span class="badge badge-success">&#10003; Active</span>
                        <?php elseif ($expired): ?>
                            <span class="badge badge-warning text-dark">Expired</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= base_url('schemes/view/'.$s->id) ?>" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                            <a href="<?= base_url('schemes/edit/'.$s->id) ?>" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="<?= base_url('schemes/toggle/'.$s->id) ?>" class="btn btn-outline-<?= $s->is_active?'secondary':'success' ?>" title="<?= $s->is_active?'Deactivate':'Activate' ?>">
                                <i class="bi bi-<?= $s->is_active?'pause':'play' ?>-circle"></i>
                            </a>
                            <a href="<?= base_url('schemes/clone_scheme/'.$s->id) ?>" class="btn btn-outline-secondary" title="Clone"><i class="bi bi-copy"></i></a>
                            <a href="#" onclick="confirmDel(<?= $s->id ?>,'<?= htmlspecialchars($s->name) ?>')" class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function confirmDel(id, name) {
    if (confirm('Delete scheme "'+name+'"?\nAll slabs, item maps, and logs will be deleted.')) window.location='<?= base_url("schemes/delete/") ?>'+id;
}
</script>
