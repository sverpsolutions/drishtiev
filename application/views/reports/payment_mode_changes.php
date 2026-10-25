<?php // FILE: application/views/reports/payment_mode_changes.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Payment Mode Changes Audit</h5>
        <small class="text-muted">Track all post-billing payment mode modifications</small>
    </div>
    <div>
        <a href="<?= base_url('reports/payment_mode_changes?from='.$from.'&to='.$to.'&export=excel') ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Export Excel</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="get" action="<?= base_url('reports/payment_mode_changes') ?>" class="row align-items-center">
            <div class="col-md-3">
                <label class="small text-muted mb-0">From Date</label>
                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="small text-muted mb-0">To Date</label>
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 d-flex align-items-end h-100 mt-4 mt-md-0">
                <button type="submit" class="btn btn-dark btn-sm btn-block">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Date & Time</th>
                        <th>Invoice #</th>
                        <th>Changed By</th>
                        <th>Old Mode</th>
                        <th>New Mode</th>
                        <th>Reason</th>
                        <th>Remarks</th>
                        <th>Terminal IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="align-middle">
                                <?= date('d M Y', strtotime($log->created_at)) ?><br>
                                <small class="text-muted"><?= date('h:i A', strtotime($log->created_at)) ?></small>
                            </td>
                            <td class="align-middle font-weight-bold"><?= htmlspecialchars($log->bill_no) ?></td>
                            <td class="align-middle"><?= htmlspecialchars($log->user_name) ?></td>
                            <td class="align-middle"><span class="badge badge-secondary"><?= strtoupper($log->old_mode) ?></span></td>
                            <td class="align-middle"><span class="badge badge-primary"><?= strtoupper($log->new_mode) ?></span></td>
                            <td class="align-middle" style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($log->reason) ?>"><?= htmlspecialchars($log->reason) ?></td>
                            <td class="align-middle" style="max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($log->remarks) ?>"><?= htmlspecialchars($log->remarks) ?></td>
                            <td class="align-middle small text-muted"><?= htmlspecialchars($log->terminal) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No payment mode changes found for the selected period.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
