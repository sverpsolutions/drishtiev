<?php // FILE: application/views/hsn/index.php ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">HSN Master</h5>
        <small class="text-muted">Manage HSN codes and GST tax rates</small>
    </div>
    <div>
        <a href="<?= base_url('hsn/import') ?>" class="btn btn-outline-success btn-sm mr-2">
            <i class="fas fa-file-excel"></i> Import from Excel
        </a>
        <a href="<?= base_url('hsn/add') ?>" class="btn btn-dark btn-sm">+ Add HSN Code</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:.87rem;">
            <thead class="thead-dark">
                <tr>
                    <th>#</th>
                    <th>HSN Code</th>
                    <th>Description</th>
                    <th class="text-center">IGST %</th>
                    <th class="text-center">CGST %</th>
                    <th class="text-center">SGST %</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($hsn_list)): ?>
                <?php foreach ($hsn_list as $i => $h): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><code class="font-weight-bold"><?= htmlspecialchars($h->hsn_code) ?></code></td>
                    <td><?= htmlspecialchars($h->description) ?></td>
                    <td class="text-center">
                        <span class="badge badge-<?= $h->igst_percent == 0 ? 'secondary' : ($h->igst_percent <= 5 ? 'success' : ($h->igst_percent <= 12 ? 'info' : ($h->igst_percent <= 18 ? 'warning' : 'danger'))) ?>">
                            <?= number_format($h->igst_percent, 2) ?>%
                        </span>
                    </td>
                    <td class="text-center"><?= number_format($h->cgst_percent, 2) ?>%</td>
                    <td class="text-center"><?= number_format($h->sgst_percent, 2) ?>%</td>
                    <td class="text-center">
                        <a href="<?= base_url('hsn/edit/'.$h->id) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Edit</a>
                        <?php if ($this->session->userdata('role') === 'admin'): ?>
                        <a href="<?= base_url('hsn/delete/'.$h->id) ?>"
                           class="btn btn-sm btn-outline-danger py-0 px-2"
                           onclick="return confirm('Delete HSN <?= htmlspecialchars($h->hsn_code) ?>? Linked products will lose HSN.')">Del</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No HSN codes yet. <a href="<?= base_url('hsn/add') ?>">Add one</a>.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info mt-3" style="font-size:.83rem;">
    <strong>Rule:</strong> CGST % = IGST % ÷ 2 &nbsp;|&nbsp; SGST % = IGST % ÷ 2 (auto-calculated on save).
    <br>For <strong>inter-state billing</strong> → IGST applies. For <strong>same-state billing</strong> → CGST + SGST applies.
</div>
