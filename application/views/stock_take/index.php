<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-clipboard-check mr-2"></i> Stock Takes</h4>
    <?php if ($is_owner || !$pending): ?>
    <a href="<?= base_url('stock_take/create') ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> New Stock Take
    </a>
    <?php endif; ?>
</div>

<?php if ($pending): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    There is an <strong>in-progress</strong> stock take for your location:
    <strong><?= htmlspecialchars($pending->take_no) ?></strong>
    (started <?= date('d M Y H:i', strtotime($pending->started_at)) ?>).
    <a href="<?= base_url('stock_take/count/' . $pending->id) ?>" class="btn btn-warning btn-sm ml-2">
        <i class="fas fa-play mr-1"></i> Continue Counting
    </a>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header font-weight-bold">
        <i class="fas fa-list mr-1"></i> All Stock Takes
    </div>
    <div class="card-body p-0">
        <?php if (empty($takes)): ?>
        <p class="text-muted p-3 mb-0">No stock takes found. <a href="<?= base_url('stock_take/create') ?>">Start one now</a>.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Take #</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Started By</th>
                        <th>Started At</th>
                        <th>Finalized By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($takes as $t): ?>
                    <?php
                        $pct = $t->total_items > 0 ? round(($t->counted_items / $t->total_items) * 100) : 0;
                        switch ($t->status) {
                            case 'in_progress': $badge = 'warning'; $label = 'In Progress'; break;
                            case 'completed':   $badge = 'success'; $label = 'Completed'; break;
                            case 'cancelled':   $badge = 'secondary'; $label = 'Cancelled'; break;
                            default:            $badge = 'info'; $label = $t->status;
                        }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($t->take_no) ?></strong></td>
                        <td><?= htmlspecialchars($t->location_name) ?></td>
                        <td><span class="badge badge-<?= $badge ?>"><?= $label ?></span></td>
                        <td style="min-width:120px;">
                            <div class="progress" style="height:16px;">
                                <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                            </div>
                            <small><?= $t->counted_items ?> / <?= $t->total_items ?></small>
                        </td>
                        <td><?= htmlspecialchars($t->started_by_name) ?></td>
                        <td><?= date('d M Y H:i', strtotime($t->started_at)) ?></td>
                        <td><?= $t->finalized_by_name ? htmlspecialchars($t->finalized_by_name) : '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <?php if ($t->status === 'in_progress'): ?>
                                <a href="<?= base_url('stock_take/count/' . $t->id) ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-play mr-1"></i> Continue
                                </a>
                                <a href="<?= base_url('stock_take/view/' . $t->id) ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                                <?php if ($is_admin): ?>
                                <a href="<?= base_url('stock_take/cancel/' . $t->id) ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Cancel this stock take?')">
                                    <i class="fas fa-times mr-1"></i> Cancel
                                </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?= base_url('stock_take/view/' . $t->id) ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
