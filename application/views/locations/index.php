<?php // FILE: application/views/locations/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Outlet & Location Master</h5>
        <small class="text-muted">Manage warehouse &amp; outlet locations</small>
    </div>
    <a href="<?= base_url('locations/add') ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> Add Location
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

<?php
$warehouses = array_filter($locations, fn($l) => $l->type === 'warehouse');
$outlets    = array_filter($locations, fn($l) => $l->type === 'outlet');
?>

<!-- Warehouses -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
        <strong><i class="fas fa-warehouse mr-2"></i>Warehouses</strong>
        <span class="badge badge-light text-primary"><?= count($warehouses) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($warehouses)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No warehouses found.</td></tr>
            <?php else: foreach ($warehouses as $loc): ?>
                <tr>
                    <td class="font-weight-bold"><?= htmlspecialchars($loc->name) ?></td>
                    <td><span class="badge badge-primary"><?= htmlspecialchars($loc->code) ?></span></td>
                    <td><?= htmlspecialchars($loc->phone ?: '—') ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($loc->address ?: '—') ?></td>
                    <td>
                        <span class="badge badge-<?= $loc->status ? 'success' : 'secondary' ?>">
                            <?= $loc->status ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= base_url('locations/stock/' . $loc->id) ?>" class="btn btn-xs btn-outline-secondary btn-sm mr-1">
                            <i class="fas fa-boxes"></i> Stock
                        </a>
                        <a href="<?= base_url('locations/edit/' . $loc->id) ?>" class="btn btn-xs btn-outline-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Outlets -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
        <strong><i class="fas fa-store mr-2"></i>Outlets</strong>
        <div class="d-flex align-items-center gap-2">
            <span class="badge badge-light text-success"><?= count($outlets) ?></span>
            <a href="<?= base_url('locations/add?type=outlet') ?>" class="btn btn-sm btn-light text-success ml-2 py-0">
                <i class="fas fa-plus mr-1"></i> Add Outlet
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Outlet Name</th>
                    <th>Code</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($outlets)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No outlets found. <a href="<?= base_url('locations/add?type=outlet') ?>">Add one</a>.</td></tr>
            <?php else:
            $i = 1;
            foreach ($outlets as $loc): ?>
                <tr>
                    <td class="text-muted"><?= $i++ ?></td>
                    <td class="font-weight-bold"><?= htmlspecialchars($loc->name) ?></td>
                    <td><span class="badge badge-success"><?= htmlspecialchars($loc->code) ?></span></td>
                    <td><?= htmlspecialchars($loc->phone ?: '—') ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($loc->address ?: '—') ?></td>
                    <td>
                        <span class="badge badge-<?= $loc->status ? 'success' : 'secondary' ?>">
                            <?= $loc->status ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= base_url('locations/stock/' . $loc->id) ?>" class="btn btn-xs btn-outline-secondary btn-sm mr-1">
                            <i class="fas fa-boxes"></i> Stock
                        </a>
                        <a href="<?= base_url('locations/edit/' . $loc->id) ?>" class="btn btn-xs btn-outline-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
