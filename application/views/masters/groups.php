<?php // FILE: application/views/masters/groups.php ?>
<div class="row">
<div class="col-md-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white py-2 font-weight-bold">Add Group</div>
        <div class="card-body">
            <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success py-1"><?= $this->session->flashdata('success') ?></div><?php endif; ?>
            <?= form_open('masters/groups') ?>
            <div class="form-group"><label>Group Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
            <div class="form-group"><label>Code</label><input type="text" name="code" class="form-control"></div>
            <button type="submit" class="btn btn-success btn-block">Add Group</button>
            <?= form_close() ?>
        </div>
    </div>
</div>
<div class="col-md-8">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white py-2 font-weight-bold"><i class="fas fa-layer-group mr-1"></i> Groups (<?= count($groups) ?>)</div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($groups as $g): ?>
                <tr>
                    <td class="font-weight-bold"><?= htmlspecialchars($g->name) ?></td>
                    <td><?= htmlspecialchars($g->code ?? '-') ?></td>
                    <td><span class="badge badge-<?= $g->status ? 'success' : 'secondary' ?>"><?= $g->status ? 'Active' : 'Inactive' ?></span></td>
                    <td><a href="<?= base_url('masters/delete_group/' . $g->id) ?>" class="btn btn-xs btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($groups)): ?><tr><td colspan="4" class="text-center text-muted py-3">No groups yet</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
