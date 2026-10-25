<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Army Quotation — Company Master</h5>
        <small class="text-muted"><a href="<?= base_url('army_quotations') ?>">Army Quotations</a> / Company Master</small>
    </div>
    <a href="<?= base_url('army_company_master/create') ?>" class="btn btn-dark btn-sm">+ Add Company</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="vertical-align:middle;">
                <thead class="thead-dark">
                    <tr>
                        <th>Logo</th>
                        <th>Company Name</th>
                        <th>GSTIN</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th class="text-center">Active</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($companies)): foreach ($companies as $c): ?>
                    <tr>
                        <td>
                            <?php if (!empty($c->logo_path)): ?>
                                <img src="<?= base_url($c->logo_path) ?>" style="height:36px;max-width:70px;object-fit:contain;">
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="font-weight-bold"><?= htmlspecialchars($c->company_name) ?></td>
                        <td><?= htmlspecialchars($c->gst_number ?? '—') ?></td>
                        <td><?= htmlspecialchars($c->phone ?? '—') ?></td>
                        <td><?= htmlspecialchars($c->email ?? '—') ?></td>
                        <td class="text-center">
                            <?php if ($c->is_active): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= base_url('army_company_master/edit/'.$c->id) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="<?= base_url('army_company_master/delete/'.$c->id) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this company? This only works if no quotations reference it.')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No companies yet. <a href="<?= base_url('army_company_master/create') ?>">Add one.</a></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
