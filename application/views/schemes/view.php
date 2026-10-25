<?php $s = $scheme; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-tag-fill text-primary mr-2"></i><?= htmlspecialchars($s->name) ?></h4>
        <small class="text-muted"><?= htmlspecialchars($s->type_label) ?> | <?= date('d/m/Y',strtotime($s->start_date)) ?> &rarr; <?= date('d/m/Y',strtotime($s->end_date)) ?></small>
    </div>
    <div class="d-flex" style="gap:.5rem">
        <a href="<?= base_url('schemes/toggle/'.$s->id) ?>" class="btn btn-sm btn-outline-<?= $s->is_active?'warning':'success' ?>">
            <i class="bi bi-<?= $s->is_active?'pause':'play' ?>-circle mr-1"></i><?= $s->is_active?'Deactivate':'Activate' ?>
        </a>
        <a href="<?= base_url('schemes/clone_scheme/'.$s->id) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-copy mr-1"></i>Clone</a>
        <a href="<?= base_url('schemes/simulate') ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-play-circle mr-1"></i>Simulate</a>
        <a href="<?= base_url('schemes') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list mr-1"></i>All</a>
    </div>
</div>

<?php
$today   = date('Y-m-d');
$expired = $s->end_date < $today;
$active  = $s->is_active && !$expired;
?>
<?php if ($active): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle mr-2"></i>Scheme is <strong>ACTIVE</strong> and will auto-apply during billing.</div>
<?php elseif ($expired): ?>
    <div class="alert alert-warning"><i class="bi bi-clock mr-2"></i>Scheme has <strong>EXPIRED</strong> on <?= date('d/m/Y',strtotime($s->end_date)) ?>.</div>
<?php else: ?>
    <div class="alert alert-secondary"><i class="bi bi-pause-circle mr-2"></i>Scheme is <strong>INACTIVE</strong>.</div>
<?php endif; ?>

<div class="row mb-3">
    <!-- Header Details -->
    <div class="col-md-7">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-info-circle mr-1"></i>Scheme Details</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6"><span class="text-muted small">Type:</span><div class="font-weight-bold"><?= htmlspecialchars($s->type_label) ?></div></div>
                    <div class="col-6"><span class="text-muted small">Apply On:</span><div class="font-weight-bold"><?= $s->apply_on === 'brand_category' ? 'Brand & Category' : ucfirst($s->apply_on) ?></div></div>
                    <?php if (in_array($s->apply_on, ['brand', 'brand_category'])): 
                        $brand_row = $this->db->select('b.name')
                            ->from('scheme_item_map sim')
                            ->join('brands b', 'b.id = sim.brand_id')
                            ->where('sim.scheme_id', $s->id)
                            ->where('sim.brand_id IS NOT NULL')
                            ->get()->row();
                        if ($brand_row):
                    ?>
                        <div class="col-6"><span class="text-muted small">Brand:</span><div class="font-weight-bold"><?= htmlspecialchars($brand_row->name) ?></div></div>
                    <?php endif; endif; ?>

                    <?php if (in_array($s->apply_on, ['category', 'brand_category'])): 
                        $cat_row = $this->db->select('c.name')
                            ->from('scheme_item_map sim')
                            ->join('categories c', 'c.id = sim.category_id')
                            ->where('sim.scheme_id', $s->id)
                            ->where('sim.category_id IS NOT NULL')
                            ->get()->row();
                        if ($cat_row):
                    ?>
                        <div class="col-6"><span class="text-muted small">Category:</span><div class="font-weight-bold"><?= htmlspecialchars($cat_row->name) ?></div></div>
                    <?php endif; endif; ?>
                    <div class="col-6"><span class="text-muted small">Priority:</span><div class="font-weight-bold"><?= $s->priority ?> (lower = higher priority)</div></div>
                    <div class="col-6"><span class="text-muted small">Parties:</span><div class="font-weight-bold"><?= ucfirst($s->applicable_parties) ?></div></div>
                    <div class="col-6"><span class="text-muted small">Recursive:</span><div><?= $s->is_recursive ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' ?></div></div>
                    <div class="col-6"><span class="text-muted small">Combination:</span><div><?= $s->is_combination_allowed ? '<span class="badge badge-success">Allowed</span>' : '<span class="badge badge-danger">Exclusive</span>' ?></div></div>
                    <?php if (!empty($s->start_time) && !empty($s->end_time) && $s->start_time != '00:00:00' && $s->end_time != '00:00:00'): ?>
                        <div class="col-6"><span class="text-muted small">Time Window:</span><div class="font-weight-bold"><?= date('h:i A', strtotime($s->start_time)) ?> &rarr; <?= date('h:i A', strtotime($s->end_time)) ?></div></div>
                    <?php endif; ?>
                    <?php if (isset($s->expiry_days_trigger) && $s->expiry_days_trigger !== null && $s->expiry_days_trigger !== ''): ?>
                        <div class="col-6"><span class="text-muted small">Expiry Trigger:</span><div class="font-weight-bold">Expires in &le; <?= htmlspecialchars($s->expiry_days_trigger) ?> Day(s)</div></div>
                    <?php endif; ?>
                    <?php if ($s->notes): ?><div class="col-12"><span class="text-muted small">Notes:</span><div><?= htmlspecialchars($s->notes) ?></div></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Benefit Summary -->
    <div class="col-md-5">
        <div class="card shadow-sm h-100 border-success">
            <div class="card-header bg-success text-white"><i class="bi bi-gift mr-1"></i>Benefit Summary</div>
            <div class="card-body">
                <?php if ($s->min_qty > 0): ?><div class="mb-2"><span class="text-muted">Min Qty:</span> <strong><?= rtrim(rtrim($s->min_qty,'0'),'.') ?> <?= strtoupper($s->min_qty_uom) ?></strong></div><?php endif; ?>
                <?php if ($s->min_value > 0): ?><div class="mb-2"><span class="text-muted">Min Value:</span> <strong>&#8377;<?= number_format($s->min_value,2) ?></strong></div><?php endif; ?>
                <?php if ($s->discount_percent > 0): ?><div class="mb-2 h6 text-success font-weight-bold"><i class="bi bi-percent mr-1"></i><?= $s->discount_percent ?>% Discount</div><?php endif; ?>
                <?php if ($s->discount_value > 0): ?><div class="mb-2 h6 text-success font-weight-bold"><i class="bi bi-currency-rupee mr-1"></i>&#8377;<?= number_format($s->discount_value,2) ?> Flat Off</div><?php endif; ?>
                <?php if ($s->free_qty > 0): ?><div class="mb-2 h6 text-primary font-weight-bold"><i class="bi bi-gift mr-1"></i><?= rtrim(rtrim($s->free_qty,'0'),'.') ?> Free Units</div><?php endif; ?>
                <?php if ($s->target_amount > 0): ?><div class="mb-2"><span class="text-muted">Target:</span> <strong>&#8377;<?= number_format($s->target_amount,0) ?></strong> (<?= $s->target_period ?>)</div><?php endif; ?>
                <?php if ($s->credit_note_percent > 0): ?><div class="mb-2 text-warning font-weight-bold"><i class="bi bi-receipt mr-1"></i>Credit Note: <?= $s->credit_note_percent ?>%</div><?php endif; ?>
                <?php if ($s->max_benefit_per_invoice > 0): ?><div class="small text-muted">Max benefit/invoice: &#8377;<?= number_format($s->max_benefit_per_invoice,2) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Slabs -->
<?php if (!empty($s->slabs)): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header"><i class="bi bi-layers mr-1"></i>Slabs (<?= count($s->slabs) ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light"><tr><th>#</th><th>Min Qty</th><th>Max Qty</th><th>Min Value</th><th>Max Value</th><th>Free Qty</th><th>Disc %</th><th>Disc &#8377;</th></tr></thead>
                <tbody>
                <?php foreach ($s->slabs as $i => $sl): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= $sl->min_qty > 0 ? rtrim(rtrim($sl->min_qty,'0'),'.') : '—' ?></td>
                    <td><?= $sl->max_qty > 0 ? rtrim(rtrim($sl->max_qty,'0'),'.') : '&#8734;' ?></td>
                    <td><?= $sl->min_value > 0 ? '&#8377;'.number_format($sl->min_value,2) : '—' ?></td>
                    <td><?= $sl->max_value > 0 ? '&#8377;'.number_format($sl->max_value,2) : '&#8734;' ?></td>
                    <td><?= $sl->free_qty > 0 ? rtrim(rtrim($sl->free_qty,'0'),'.') : '—' ?></td>
                    <td><?= $sl->discount_percent > 0 ? $sl->discount_percent.'%' : '—' ?></td>
                    <td><?= $sl->discount_value > 0 ? '&#8377;'.number_format($sl->discount_value,2) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Item Map -->
<?php if (!empty($s->item_map)): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header"><i class="bi bi-box-seam mr-1"></i>Applicable Products (<?= count($s->item_map) ?>)</div>
    <div class="card-body">
        <?php foreach ($s->item_map as $im): ?>
            <span class="badge badge-light text-dark border mr-1 mb-1"><?= htmlspecialchars($im->product_name ?? 'Product #'.$im->product_id) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Free Items -->
<?php if (!empty($s->free_items)): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header"><i class="bi bi-gift mr-1"></i>Free Items Given</div>
    <div class="card-body">
        <?php foreach ($s->free_items as $fi): ?>
            <span class="badge badge-primary mr-1"><?= htmlspecialchars($fi->product_name) ?> &times; <?= rtrim(rtrim($fi->free_qty,'0'),'.') ?> <?= strtoupper($fi->free_uom) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Party Map -->
<?php if (!empty($s->party_map)): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header"><i class="bi bi-people mr-1"></i>Applicable Customers (<?= count($s->party_map) ?>)</div>
    <div class="card-body">
        <?php foreach ($s->party_map as $pm): ?>
            <span class="badge badge-info text-dark mr-1 mb-1"><?= htmlspecialchars($pm->customer_name) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Application Log -->
<div class="card shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-clock-history mr-1"></i>Recent Applications</span>
        <a href="<?= base_url('schemes/report') ?>" class="btn btn-outline-success btn-sm">Full Report</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Invoice</th><th>Customer</th><th>Benefit</th><th>Discount</th><th>Free Qty</th></tr></thead>
                <tbody>
                <?php if (empty($log)): ?>
                    <tr><td colspan="6" class="text-center text-muted small py-3">No applications yet</td></tr>
                <?php else: ?>
                <?php foreach ($log as $l): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($l->applied_at)) ?></td>
                        <td><?= htmlspecialchars($l->invoice_no) ?></td>
                        <td><?= htmlspecialchars($l->customer_name) ?></td>
                        <td><span class="badge badge-secondary"><?= $l->benefit_type ?></span></td>
                        <td><?= $l->discount_given > 0 ? '&#8377;'.number_format($l->discount_given,2) : '—' ?></td>
                        <td><?= $l->free_qty > 0 ? rtrim(rtrim($l->free_qty,'0'),'.') : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
