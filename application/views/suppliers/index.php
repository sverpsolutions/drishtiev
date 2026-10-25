<?php
$flash_s = $this->session->flashdata('success');
$flash_e = $this->session->flashdata('error');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-truck text-primary mr-2"></i>Supplier Master</h5>
        <small class="text-muted">Manage suppliers, visit schedules & performance</small>
    </div>
    <div>
        <a href="<?= base_url('suppliers/visit_schedule/0') ?>" class="btn btn-outline-info btn-sm mr-2"><i class="fas fa-calendar-check"></i> Visit Planner</a>
        <a href="<?= base_url('suppliers/import') ?>" class="btn btn-outline-success btn-sm mr-2"><i class="fas fa-file-excel"></i> Import Excel</a>
        <a href="<?= base_url('suppliers/add') ?>" class="btn btn-dark btn-sm"><i class="fas fa-plus"></i> Add Supplier</a>
    </div>
</div>

<?php if ($flash_s): ?><div class="alert alert-success alert-dismissible fade show"><?= $flash_s ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
<?php if ($flash_e): ?><div class="alert alert-danger alert-dismissible fade show"><?= $flash_e ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

<?php if (!empty($visits_today)): ?>
<div class="alert alert-warning border-left border-warning py-2 mb-3" style="border-left-width:4px!important">
    <strong><i class="fas fa-calendar-day mr-1"></i> <?= count($visits_today) ?> Supplier Visit(s) Today:</strong>
    <?php foreach ($visits_today as $v): ?>
        <span class="badge badge-warning ml-1"><?= htmlspecialchars($v->name) ?></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <input type="text" id="suppSearch" class="form-control form-control-sm" placeholder="Search suppliers by name, code, city...">
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="suppTable" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Code</th><th>Name</th><th>Contact</th><th>City</th><th>GSTIN</th>
                        <th>Credit Days</th><th>Visit Cycle</th><th>Next Visit</th>
                        <th>Score</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><span class="badge badge-secondary"><?= htmlspecialchars($s->supplier_code ?? 'SUP') ?></span></td>
                    <td>
                        <strong><?= htmlspecialchars($s->name) ?></strong>
                        <?php if ($s->is_preferred): ?><span class="badge badge-success ml-1" title="Preferred">★</span><?php endif; ?>
                        <?php if (($s->gst_compliance_status ?? 'ok') === 'flagged'): ?>
                        <span class="badge badge-danger ml-1" title="<?= htmlspecialchars($s->gst_compliance_note ?? '') ?>"><i class="fas fa-exclamation-triangle"></i> GST Non-Compliant</span>
                        <?php endif; ?>
                        <?php if (!empty($s->company_name)): ?><br><small class="text-muted"><?= htmlspecialchars($s->company_name) ?></small><?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($s->phone ?? '') ?><br><small class="text-muted"><?= htmlspecialchars($s->email ?? '') ?></small></td>
                    <td><?= htmlspecialchars($s->city ?? '') ?></td>
                    <td><small><?= htmlspecialchars($s->gst_number ?? '-') ?></small></td>
                    <td><?= $s->credit_days ?? 0 ?> days</td>
                    <td><?php
                        if ($s->schedule_type === 'weekly') echo 'Weekly';
                        elseif ($s->schedule_type === 'daily') echo 'Daily';
                        elseif ($s->schedule_type === 'monthly_dates') echo 'Monthly';
                        elseif ($s->interval_days) echo 'Every ' . $s->interval_days . 'd';
                        else echo '<span class="text-muted">Not set</span>';
                    ?></td>
                    <td><?php if (!empty($s->next_visit_date)) {
                        $diff = (strtotime($s->next_visit_date) - strtotime(date('Y-m-d'))) / 86400;
                        $cls  = $diff <= 0 ? 'danger' : ($diff <= 1 ? 'warning' : 'info');
                        echo '<span class="badge badge-' . $cls . '">' . date('d M', strtotime($s->next_visit_date)) . '</span>';
                        if ($diff <= 0) { echo ' <small class="text-danger">TODAY</small>'; }
                    } else { echo '<span class="text-muted">-</span>'; } ?></td>
                    <td><?php
                        $score = $s->overall_score ?? 0;
                        $tier  = $s->tier ?? 'bronze';
                        $tc    = $tier === 'gold' ? 'warning' : ($tier === 'silver' ? 'secondary' : 'dark');
                        echo '<span class="badge badge-' . $tc . '">' . ($tier ? ucfirst($tier) : 'New') . ' ' . ($score ? number_format($score,1) : '') . '</span>';
                    ?></td>
                    <td>
                        <a href="<?= base_url('suppliers/view/' . $s->id) ?>" class="btn btn-xs btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                        <a href="<?= base_url('suppliers/edit/' . $s->id) ?>" class="btn btn-xs btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="<?= base_url('purchase_orders/create?supplier_id=' . $s->id) ?>" class="btn btn-xs btn-outline-success" title="New PO"><i class="fas fa-file-invoice"></i></a>
                        <a href="<?= base_url('suppliers/products/' . $s->id) ?>" class="btn btn-xs btn-outline-info" title="Products"><i class="fas fa-boxes"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.getElementById('suppSearch').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#suppTable tbody tr').forEach(function(r) {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
<style>.btn-xs{padding:2px 6px;font-size:.72rem;}</style>
