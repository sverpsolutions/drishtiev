<?php // FILE: application/views/user_management/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-users-cog mr-1 text-warning"></i> User Management</h5>
        <small class="text-muted">Manage ERP users and module access permissions</small>
    </div>
    <a href="<?= base_url('user_management/add') ?>" class="btn btn-warning btn-sm">
        <i class="fas fa-plus mr-1"></i> Add User
    </a>
</div>

<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-1"></i><?= $this->session->flashdata('success') ?><button class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
    <table class="table table-hover mb-0" style="font-size:.88rem;">
        <thead class="thead-dark">
            <tr>
                <th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th>
                <th>Status</th><th>Last Login</th><th>Module Access</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u->id ?></td>
            <td class="font-weight-bold"><?= htmlspecialchars($u->name) ?></td>
            <td><code><?= htmlspecialchars($u->username) ?></code></td>
            <td><?= htmlspecialchars($u->email) ?></td>
            <td>
                <?php if ($u->role === 'owner'): ?>
                <span class="badge" style="background:linear-gradient(135deg,#f7971e,#ffd200);color:#333;">👑 Owner</span>
                <?php elseif ($u->role === 'superadmin'): ?>
                <span class="badge" style="background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;">⚡ SuperAdmin</span>
                <?php elseif ($u->role === 'manager'): ?>
                <span class="badge badge-warning text-dark">Manager</span>
                <?php elseif ($u->role === 'admin'): ?>
                <span class="badge badge-danger">Admin</span>
                <?php else: ?>
                <span class="badge badge-info">Cashier</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($u->role !== 'superadmin'): ?>
                <a href="<?= base_url('user_management/toggle_status/'.$u->id) ?>"
                   onclick="return confirm('Toggle status?')"
                   class="badge badge-<?= $u->status ? 'success' : 'secondary' ?>" style="cursor:pointer;">
                    <?= $u->status ? 'Active' : 'Inactive' ?>
                </a>
                <?php else: ?>
                <span class="badge badge-success">Active</span>
                <?php endif; ?>
            </td>
            <td class="text-muted" style="font-size:.8rem;"><?= $u->last_login ? date('d M y H:i', strtotime($u->last_login)) : '—' ?></td>
            <td>
                <?php if ($u->role === 'superadmin' || $u->role === 'owner'): ?>
                <span class="text-muted small">All Modules</span>
                <?php else:
                    $cnt = $this->db->where('user_id',$u->id)->where('is_enabled',1)->count_all_results('module_permissions');
                    $tot = count(ERP_MODULES);
                ?>
                <span class="badge badge-<?= $cnt == $tot ? 'success' : ($cnt > 0 ? 'warning' : 'danger') ?>">
                    <?= $cnt ?>/<?= $tot ?> modules
                </span>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?= base_url('user_management/edit/'.$u->id) ?>" class="btn btn-warning btn-sm py-0 px-2">Edit</a>
                <?php if ($u->role !== 'superadmin'): ?>
                <a href="<?= base_url('user_management/delete/'.$u->id) ?>"
                   onclick="return confirm('Delete user <?= htmlspecialchars(addslashes($u->name)) ?>?')"
                   class="btn btn-outline-danger btn-sm py-0 px-2">Del</a>
                <?php else: ?>
                <span class="text-muted small ml-1" title="Super Admin accounts can't be deleted or deactivated from here">Del: Protected</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
