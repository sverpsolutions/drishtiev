<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-history text-danger mr-2"></i>Emergency Day Close History</h5>
        <small class="text-muted">Permanent record of every emergency day close performed on this system.</small>
    </div>
    <a href="<?= base_url('emergency_day_close') ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Business Date</th>
                        <th>Closed By</th>
                        <th>Reason</th>
                        <th class="text-center">Active Counters</th>
                        <th>Emergency Time</th>
                        <th class="text-right">Sales Amount</th>
                        <th class="text-center">Details</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No emergency day closes have been performed.</td></tr>
                <?php else: foreach ($logs as $l): ?>
                    <tr>
                        <td class="font-weight-bold"><?= date('d M Y', strtotime($l->business_date)) ?></td>
                        <td><?= htmlspecialchars($l->closed_by_name) ?></td>
                        <td><span class="badge badge-warning"><?= htmlspecialchars($l->reason) ?></span></td>
                        <td class="text-center"><?= (int)$l->active_counters_count ?></td>
                        <td><?= date('d M Y H:i', strtotime($l->closed_at)) ?></td>
                        <td class="text-right font-weight-bold">₹<?= number_format($l->sales_amount, 2) ?></td>
                        <td class="text-center">
                            <a href="<?= base_url('emergency_day_close/view/'.$l->id) ?>" class="btn btn-xs btn-outline-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>.btn-xs{padding:2px 6px;font-size:.72rem;}</style>
