<?php // FILE: application/views/reports/outstanding.php
// Outlet-wise summary (customer receivables + supplier payables per outlet)
$by_outlet = [];
foreach ((array)$customer_dues as $c) {
    $k = $c->location_name ?: '—';
    $by_outlet[$k]['recv'] = ($by_outlet[$k]['recv'] ?? 0) + (float)$c->outstanding;
}
foreach ((array)$supplier_dues as $s) {
    $k = $s->location_name ?: '—';
    $by_outlet[$k]['pay'] = ($by_outlet[$k]['pay'] ?? 0) + (float)$s->total_due;
}
$cust_total = array_sum(array_map(function ($c) { return (float)$c->outstanding; }, (array)$customer_dues));
$supp_total = array_sum(array_map(function ($s) { return (float)$s->total_due; }, (array)$supplier_dues));
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <div>
        <h5 class="font-weight-bold mb-0">Outstanding Dues</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Outstanding</small>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/outstanding','export_params'=>$export_params??'','export_title'=>$export_title??'Outstanding Dues']); ?>
    </div>
</div>

<?php if (count($locations ?? []) > 1 || !empty($fixed_loc)): ?>
<form method="GET" class="form-inline mb-3 no-print" style="gap:8px;">
    <label class="mr-1 font-weight-bold text-muted" style="font-size:.82rem;"><i class="fas fa-store mr-1"></i>Outlet</label>
    <select name="location_id" class="form-control form-control-sm" style="min-width:200px;" onchange="this.form.submit()" <?= !empty($fixed_loc) ? 'disabled' : '' ?>>
        <?php if (count($locations ?? []) > 1): ?>
        <option value="">All Outlets</option>
        <?php endif; ?>
        <?php foreach ($locations ?? [] as $loc): ?>
        <option value="<?= $loc->id ?>" <?= (int)$location_id === (int)$loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?> [<?= htmlspecialchars($loc->code) ?>]</option>
        <?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="btn btn-sm btn-secondary">Go</button></noscript>
</form>
<?php endif; ?>

<?php if (empty($location_id) && count($by_outlet) > 1): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Outlet-wise Summary</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:.84rem;">
            <thead class="thead-light"><tr><th>Outlet</th><th class="text-right">Customer Outstanding</th><th class="text-right">Supplier Payable</th></tr></thead>
            <tbody>
            <?php foreach ($by_outlet as $name => $v): ?>
                <tr>
                    <td><?= htmlspecialchars($name) ?></td>
                    <td class="text-right text-danger">₹ <?= number_format($v['recv'] ?? 0, 2) ?></td>
                    <td class="text-right text-warning">₹ <?= number_format($v['pay'] ?? 0, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="font-weight-bold"><td>Total</td><td class="text-right">₹ <?= number_format($cust_total, 2) ?></td><td class="text-right">₹ <?= number_format($supp_total, 2) ?></td></tr></tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Customer Dues -->
    <div class="col-lg-7 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-danger text-white py-2 font-weight-bold" style="font-size:.85rem;">
                Customer Outstanding (₹ <?= number_format($cust_total, 2) ?> total)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size:.84rem;">
                        <thead class="thead-light">
                            <tr><th>Customer</th><th>Phone</th><th>Outlet</th><th>Type</th><th class="text-right">Credit Limit</th><th class="text-right">Outstanding</th><th>Open Inv.</th></tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($customer_dues)): foreach ($customer_dues as $c): ?>
                        <tr>
                            <td><a href="<?= base_url('customers/ledger/'.$c->id) ?>"><?= htmlspecialchars($c->name) ?></a></td>
                            <td><?= htmlspecialchars((string)$c->phone) ?></td>
                            <td><?php if ((int)$c->location_id > 0): ?><?= htmlspecialchars((string)$c->location_name) ?><?php else: ?><small class="text-muted font-italic"><?= htmlspecialchars((string)$c->location_name) ?></small><?php endif; ?></td>
                            <td><?= ucfirst((string)$c->type) ?></td>
                            <td class="text-right">₹ <?= number_format((float)$c->credit_limit,2) ?></td>
                            <td class="text-right font-weight-bold text-danger">₹ <?= number_format((float)$c->outstanding,2) ?></td>
                            <td class="text-center"><?php if ($c->open_invoices): ?><span class="badge badge-warning"><?= $c->open_invoices ?></span><?php endif; ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">No outstanding customer dues.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Supplier Dues -->
    <div class="col-lg-5 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark py-2 font-weight-bold" style="font-size:.85rem;">
                Supplier Payables (₹ <?= number_format($supp_total, 2) ?> total)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size:.84rem;">
                        <thead class="thead-light">
                            <tr><th>Supplier</th><th>Outlet</th><th>Open Orders</th><th class="text-right">Total Due</th></tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($supplier_dues)): foreach ($supplier_dues as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s->name) ?></td>
                            <td><?= htmlspecialchars((string)$s->location_name) ?></td>
                            <td class="text-center"><span class="badge badge-secondary"><?= $s->open_orders ?></span></td>
                            <td class="text-right font-weight-bold text-warning">₹ <?= number_format((float)$s->total_due,2) ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No supplier dues.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<style>@media print { .no-print { display:none; } }</style>
