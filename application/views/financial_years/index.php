<?php // FILE: application/views/financial_years/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Financial Years</h5>
        <small class="text-muted">Document numbering (Location/FY/Serial) resets to 000001 for each new financial year, per location.</small>
    </div>
    <a href="<?= base_url('financial_years/add') ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> Add Financial Year
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

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Label</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($financial_years)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No financial years yet — one is created automatically the first time a document is numbered.</td></tr>
            <?php else: foreach ($financial_years as $fy): ?>
                <tr class="<?= $fy->label === $current_label ? 'table-active' : '' ?>">
                    <td class="font-weight-bold">
                        FY <?= htmlspecialchars($fy->label) ?>
                        <?php if ($fy->label === $current_label): ?>
                            <span class="badge badge-info ml-1" style="font-size:.65rem;">CURRENT</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($fy->start_date) ?></td>
                    <td><?= htmlspecialchars($fy->end_date) ?></td>
                    <td>
                        <span class="badge badge-<?= $fy->status === 'open' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($fy->status) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($fy->status === 'open'): ?>
                            <form method="POST" action="<?= base_url('financial_years/close/' . $fy->id) ?>" class="d-inline" onsubmit="return confirm('Close FY <?= htmlspecialchars($fy->label) ?>? No new documents can be posted into it afterward.');">
                                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                                <button type="submit" class="btn btn-xs btn-outline-danger btn-sm"><i class="fas fa-lock mr-1"></i>Close</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="<?= base_url('financial_years/reopen/' . $fy->id) ?>" class="d-inline">
                                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                                <button type="submit" class="btn btn-xs btn-outline-success btn-sm"><i class="fas fa-lock-open mr-1"></i>Reopen</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
