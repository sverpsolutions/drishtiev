<?php // FILE: application/views/reports/portal_order_funnel.php ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-filter text-primary"></i> Portal Order Funnel</h5>
        <small class="text-muted">B2B/B2C portal order lifecycle: pending &rarr; confirmed &rarr; converted/cancelled</small>
    </div>
</div>

<!-- Filter Form -->
<div class="card border-0 shadow-sm mb-3">
<div class="card-body py-3">
<form method="get" action="<?= base_url('reports/portal_order_funnel') ?>" class="form-row align-items-end">
    <div class="col-md-3">
        <label class="small font-weight-bold">From Date</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
    </div>
    <div class="col-md-3">
        <label class="small font-weight-bold">To Date</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Apply</button>
    </div>
</form>
</div>
</div>

<!-- Stat Cards -->
<div class="row mb-3">
    <?php
    $status_colors = ['pending' => 'warning', 'confirmed' => 'info', 'converted' => 'success', 'cancelled' => 'danger'];
    $by_status = [];
    foreach ($summary as $s) { $by_status[$s['status']] = $s; }
    ?>
    <div class="col-md-3 mb-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 text-center">
                <div class="text-muted small">Total Orders</div>
                <div class="h4 font-weight-bold mb-0"><?= $total_count ?></div>
            </div>
        </div>
    </div>
    <?php foreach (['pending', 'confirmed', 'converted', 'cancelled'] as $st): $s = $by_status[$st] ?? ['cnt' => 0, 'total_value' => 0]; ?>
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 text-center">
                <div class="text-muted small text-capitalize"><?= $st ?></div>
                <div class="h5 font-weight-bold mb-0 text-<?= $status_colors[$st] ?>"><?= $s['cnt'] ?></div>
                <small class="text-muted">₹<?= number_format($s['total_value'] ?? 0, 0) ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm h-100 bg-light">
            <div class="card-body py-3 text-center">
                <div class="text-muted small">Conversion Rate</div>
                <div class="h5 font-weight-bold mb-0 text-primary"><?= number_format($conversion_rate, 1) ?>%</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Order Detail Table -->
    <div class="col-lg-8 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-dark text-white py-2"><span class="font-weight-bold">Orders in Period</span></div>
            <div class="card-body p-0">
            <div class="table-responsive" style="max-height:500px;">
            <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
            <thead class="thead-light" style="position:sticky;top:0;">
            <tr>
                <th>Request No</th>
                <th>Customer</th>
                <th>Status</th>
                <th class="text-right">Amount</th>
                <th>Created</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= htmlspecialchars($o['request_no']) ?></td>
                <td><?= htmlspecialchars($o['customer_name'] ?? 'N/A') ?><br><small class="text-muted"><?= $o['customer_phone'] ?? '' ?></small></td>
                <td><span class="badge badge-<?= $status_colors[$o['status']] ?? 'secondary' ?>"><?= ucfirst($o['status']) ?></span></td>
                <td class="text-right">₹<?= number_format($o['total_amount'], 2) ?></td>
                <td><?= date('d-m-Y', strtotime($o['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No portal orders found in this period.</td></tr>
            <?php endif; ?>
            </tbody>
            </table>
            </div>
            </div>
        </div>
    </div>

    <!-- Top Pending Customers -->
    <div class="col-lg-4 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-warning py-2"><span class="font-weight-bold">Top Pending Customers</span></div>
            <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
            <thead>
            <tr><th>Customer</th><th class="text-right">Orders</th><th class="text-right">Value</th></tr>
            </thead>
            <tbody>
            <?php foreach ($top_pending as $tp): ?>
            <tr>
                <td><?= htmlspecialchars($tp['customer_name'] ?? 'N/A') ?></td>
                <td class="text-right"><?= $tp['pending_orders'] ?></td>
                <td class="text-right">₹<?= number_format($tp['pending_value'], 0) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($top_pending)): ?>
            <tr><td colspan="3" class="text-center text-muted py-4">No pending orders.</td></tr>
            <?php endif; ?>
            </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
