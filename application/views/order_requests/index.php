<?php // FILE: application/views/order_requests/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="font-weight-bold mb-0">
        Order Requests
        <?php if ($pending_count > 0): ?>
        <span class="badge badge-warning ml-1" style="font-size:.75rem;"><?= $pending_count ?> Pending</span>
        <?php endif; ?>
    </h4>
</div>

<!-- Filter tabs -->
<ul class="nav nav-pills mb-3" style="font-size:.88rem;">
    <li class="nav-item"><a class="nav-link <?= !$status_filter ? 'active' : '' ?>" href="<?= base_url('order_requests') ?>">All</a></li>
    <li class="nav-item"><a class="nav-link <?= $status_filter==='pending' ? 'active' : '' ?>" href="<?= base_url('order_requests?status=pending') ?>">Pending</a></li>
    <li class="nav-item"><a class="nav-link <?= $status_filter==='confirmed' ? 'active' : '' ?>" href="<?= base_url('order_requests?status=confirmed') ?>">Confirmed</a></li>
    <li class="nav-item"><a class="nav-link <?= $status_filter==='converted' ? 'active' : '' ?>" href="<?= base_url('order_requests?status=converted') ?>">Converted</a></li>
    <li class="nav-item"><a class="nav-link <?= $status_filter==='cancelled' ? 'active' : '' ?>" href="<?= base_url('order_requests?status=cancelled') ?>">Cancelled</a></li>
</ul>

<?php if (empty($orders)): ?>
<div class="card shadow-sm">
    <div class="card-body text-center text-muted py-5">
        <i class="fas fa-clipboard-list fa-3x mb-3 d-block" style="opacity:.3;"></i>
        No order requests found.
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.88rem;">
            <thead class="thead-light">
                <tr>
                    <th class="pl-3">Order #</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Date</th>
                    <th>Delivery Info</th>
                    <th class="text-right">Del. Charge</th>
                    <th class="text-right">Total Amount</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td class="pl-3"><a href="<?= base_url('order_requests/view/'.$o->id) ?>" class="font-weight-bold text-primary"><?= htmlspecialchars($o->request_no) ?></a></td>
                <td><?= htmlspecialchars($o->customer_name) ?></td>
                <td><?= htmlspecialchars($o->customer_phone) ?></td>
                <td><?= date('d M Y', strtotime($o->created_at)) ?></td>
                <td>
                    <?php if (!empty($o->delivery_slot) || !empty($o->delivery_distance)): ?>
                        <small class="d-block text-dark font-weight-bold"><?= htmlspecialchars($o->delivery_slot) ?></small>
                        <small class="text-muted"><?= $o->delivery_distance ?> KM</small>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td class="text-right font-weight-bold text-dark">
                    <?= $o->delivery_charge > 0 ? '₹' . number_format($o->delivery_charge, 2) : '<span class="text-success">FREE</span>' ?>
                </td>
                <td class="text-right font-weight-bold text-success">
                    ₹<?= number_format($o->total_amount, 2) ?>
                </td>
                <td>
                    <?php
                    $cls = [
                        'pending'   => 'warning',
                        'confirmed' => 'info',
                        'converted' => 'success',
                        'cancelled' => 'secondary',
                    ];
                    ?>
                    <span class="badge badge-<?= $cls[$o->status] ?? 'secondary' ?>"><?= ucfirst($o->status) ?></span>
                </td>
                <td class="text-muted" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($o->notes ?? '') ?></td>
                <td><a href="<?= base_url('order_requests/view/'.$o->id) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">View</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php endif; ?>
