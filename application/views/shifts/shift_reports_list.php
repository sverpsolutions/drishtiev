<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 font-weight-bold"><i class="fas fa-receipt text-primary mr-2"></i>Shift Close Reports</h4>
        <small class="text-muted">View, download or reprint any cashier's shift closing receipt</small>
    </div>
    <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">From Date</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">To Date</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">Cashier</label>
                <select name="cashier" class="form-control form-control-sm">
                    <option value="">All Cashiers</option>
                    <?php foreach ($cashiers as $c): ?>
                    <option value="<?= $c->id ?>" <?= $filter_uid == $c->id ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2 mb-0">
                <label class="small font-weight-bold mb-1">Status</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="closed"  <?= $filter_status === 'closed'  ? 'selected' : '' ?>>Closed</option>
                    <option value="open"    <?= $filter_status === 'open'    ? 'selected' : '' ?>>Open</option>
                    <option value=""        <?= $filter_status === ''        ? 'selected' : '' ?>>All</option>
                </select>
            </div>
            <div class="form-group col-md-2 mb-0">
                <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-search mr-1"></i>Filter</button>
            </div>
            <div class="form-group col-md-2 mb-0">
                <a href="<?= base_url('shifts/shift_reports_list') ?>" class="btn btn-outline-secondary btn-sm btn-block">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary cards -->
<?php
$total_bills_sum = array_sum(array_column($shifts, 'bill_count'));
$total_sales_sum = array_sum(array_column($shifts, 'total_sales'));
$total_short     = array_sum(array_column($shifts, 'short_amount'));
$total_excess    = array_sum(array_column($shifts, 'excess_amount'));
$total_handover  = array_sum(array_column($shifts, 'handover_total'));
?>
<div class="row mb-3">
    <div class="col"><div class="card border-0 bg-dark text-white text-center py-2"><div class="small text-muted">Shifts</div><div class="font-weight-bold h5 mb-0"><?= count($shifts) ?></div></div></div>
    <div class="col"><div class="card border-0 bg-success text-white text-center py-2"><div class="small">Total Sales</div><div class="font-weight-bold h5 mb-0">₹<?= number_format($total_sales_sum, 2) ?></div></div></div>
    <div class="col"><div class="card border-0 bg-info text-white text-center py-2"><div class="small">Total Handover</div><div class="font-weight-bold h5 mb-0">₹<?= number_format($total_handover, 2) ?></div></div></div>
    <div class="col"><div class="card border-0 bg-danger text-white text-center py-2"><div class="small">Total Shortage</div><div class="font-weight-bold h5 mb-0">₹<?= number_format($total_short, 2) ?></div></div></div>
    <div class="col"><div class="card border-0 bg-warning text-dark text-center py-2"><div class="small">Total Excess</div><div class="font-weight-bold h5 mb-0">₹<?= number_format($total_excess, 2) ?></div></div></div>
</div>

<!-- Shifts Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($shifts)): ?>
            <div class="text-center py-5 text-muted">No shifts found for selected filters.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:.84rem;">
                <thead class="thead-dark">
                    <tr>
                        <th class="pl-3">Shift #</th>
                        <th>Date</th>
                        <th>Cashier</th>
                        <th>Counter/System</th>
                        <th>Shift</th>
                        <th>Open</th>
                        <th>Close</th>
                        <th class="text-right">Opening Cash</th>
                        <th class="text-center">Bills</th>
                        <th class="text-right">Gross Sales</th>
                        <th class="text-right">Cash Decl.</th>
                        <th class="text-right text-info">Handover</th>
                        <th class="text-right text-danger">Short</th>
                        <th class="text-right text-success">Excess</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($shifts as $s): ?>
                    <tr>
                        <td class="pl-3 align-middle font-weight-bold">#<?= $s->id ?></td>
                        <td class="align-middle"><?= date('d-M-Y', strtotime($s->opened_at)) ?></td>
                        <td class="align-middle font-weight-bold"><?= htmlspecialchars($s->cashier_name) ?></td>
                        <td class="align-middle"><?= htmlspecialchars($s->system_name ?? '—') ?></td>
                        <td class="align-middle"><?= htmlspecialchars($s->shift_name ?? '—') ?></td>
                        <td class="align-middle"><?= date('H:i', strtotime($s->opened_at)) ?></td>
                        <td class="align-middle"><?= $s->closed_at ? date('H:i', strtotime($s->closed_at)) : '<span class="badge badge-warning">Open</span>' ?></td>
                        <td class="text-right align-middle">₹<?= number_format($s->opening_cash, 2) ?></td>
                        <td class="text-center align-middle"><?= $s->bill_count ?></td>
                        <td class="text-right align-middle font-weight-bold">₹<?= number_format($s->total_sales, 2) ?></td>
                        <td class="text-right align-middle">₹<?= number_format($s->actual_cash, 2) ?></td>
                        <td class="text-right align-middle <?= $s->handover_total > 0 ? 'text-info font-weight-bold' : 'text-muted' ?>">
                            <?= $s->handover_total > 0 ? '₹'.number_format($s->handover_total, 2) : '—' ?>
                        </td>
                        <td class="text-right align-middle <?= $s->short_amount > 0 ? 'text-danger font-weight-bold' : 'text-muted' ?>">
                            <?= $s->short_amount > 0 ? '₹'.number_format($s->short_amount, 2) : '—' ?>
                        </td>
                        <td class="text-right align-middle <?= $s->excess_amount > 0 ? 'text-success font-weight-bold' : 'text-muted' ?>">
                            <?= $s->excess_amount > 0 ? '₹'.number_format($s->excess_amount, 2) : '—' ?>
                        </td>
                        <td class="align-middle">
                            <span class="badge badge-<?= $s->status === 'open' ? 'warning' : 'success' ?>">
                                <?= strtoupper($s->status) ?>
                            </span>
                        </td>
                        <td class="text-center align-middle" style="white-space:nowrap;">
                            <a href="<?= base_url('shifts/shift_report/'.$s->id) ?>" class="btn btn-xs btn-outline-secondary" title="Full Report">
                                <i class="fas fa-file-alt"></i>
                            </a>
                            <a href="<?= base_url('shifts/shift_thermal/'.$s->id) ?>" target="_blank" class="btn btn-xs btn-outline-primary" title="Print 3-inch Receipt">
                                <i class="fas fa-receipt"></i>
                            </a>
                            <a href="<?= base_url('shifts/shift_thermal/'.$s->id.'?autoprint=1') ?>" target="_blank" class="btn btn-xs btn-outline-dark" title="Auto-print 3-inch">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light font-weight-bold" style="font-size:.84rem;">
                    <tr>
                        <td colspan="8" class="pl-3 text-right">TOTALS →</td>
                        <td class="text-center"><?= $total_bills_sum ?></td>
                        <td class="text-right">₹<?= number_format($total_sales_sum, 2) ?></td>
                        <td></td>
                        <td class="text-right text-info">₹<?= number_format($total_handover, 2) ?></td>
                        <td class="text-right text-danger">₹<?= number_format($total_short, 2) ?></td>
                        <td class="text-right text-success">₹<?= number_format($total_excess, 2) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
