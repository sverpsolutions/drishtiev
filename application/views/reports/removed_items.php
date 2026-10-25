<?php // FILE: application/views/reports/removed_items.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Removed Items (Deleted After Scan)</h5>
        <small class="text-muted">Items scanned into a bill and then deleted with manager authorization — before the sale was saved</small>
    </div>
    <div>
        <?php $qp = http_build_query(['from'=>$from,'to'=>$to,'user_id'=>$user_id,'location_id'=>$location_id??0,'export'=>'excel']); ?>
        <a href="<?= base_url('reports/removed_items?'.$qp) ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Export Excel</a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2">
                <small class="text-muted text-uppercase font-weight-bold" style="font-size:.68rem;">Items Removed</small>
                <div class="font-weight-bold" style="font-size:1.3rem;"><?= (int)$total_count ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2">
                <small class="text-muted text-uppercase font-weight-bold" style="font-size:.68rem;">Total Value</small>
                <div class="font-weight-bold text-danger" style="font-size:1.3rem;">₹ <?= number_format($total_value, 2) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="get" action="<?= base_url('reports/removed_items') ?>" class="row align-items-end">
            <div class="col-md-2">
                <label class="small text-muted mb-0">From Date</label>
                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="small text-muted mb-0">To Date</label>
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="small text-muted mb-0">Cashier</label>
                <select name="user_id" class="form-control form-control-sm">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u->id ?>" <?= $user_id == $u->id ? 'selected' : '' ?>><?= htmlspecialchars($u->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($locations)): ?>
            <div class="col-md-3">
                <label class="small text-muted mb-0">Location</label>
                <select name="location_id" class="form-control form-control-sm" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
                    <?php if (count($locations) > 1): ?>
                    <option value="">All Locations</option>
                    <?php endif; ?>
                    <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= ($location_id ?? 0) == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
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
                        <th>Date &amp; Time</th>
                        <th>Item</th>
                        <th>Item Code</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">Value</th>
                        <th>Removed By</th>
                        <th>Authorized By</th>
                        <th>Customer</th>
                        <th>Terminal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="align-middle">
                                <?= date('d M Y', strtotime($log->removed_at)) ?><br>
                                <small class="text-muted"><?= date('h:i A', strtotime($log->removed_at)) ?></small>
                            </td>
                            <td class="align-middle font-weight-bold"><?= htmlspecialchars($log->product_name) ?></td>
                            <td class="align-middle small text-muted"><?= htmlspecialchars($log->item_code ?? '') ?></td>
                            <td class="align-middle text-right"><?= rtrim(rtrim(number_format($log->qty, 2), '0'), '.') ?></td>
                            <td class="align-middle text-right">₹ <?= number_format($log->rate, 2) ?></td>
                            <td class="align-middle text-right font-weight-bold text-danger">₹ <?= number_format($log->line_value, 2) ?></td>
                            <td class="align-middle"><?= htmlspecialchars($log->user_name) ?></td>
                            <td class="align-middle"><span class="badge badge-warning"><?= htmlspecialchars($log->authorized_by ?? '') ?></span></td>
                            <td class="align-middle"><?= htmlspecialchars($log->customer_name ?? '—') ?></td>
                            <td class="align-middle small text-muted"><?= htmlspecialchars($log->device_label ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No removed items found for the selected period.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
