<?php // Counter-wise & Bill-wise Summary Report ?>
<?php $export_params = 'from=' . urlencode($from) . '&to=' . urlencode($to) . '&cashier_id=' . urlencode($cashier_id); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold">Counter-wise & Bill-wise Summary</h5>
        <small class="text-muted">Cashier performance, shift summaries, and bill details</small>
    </div>
    <div class="d-flex align-items-center" style="gap:5px;">
        <a href="<?= base_url('reports/counter_summary?' . $export_params . '&export=excel') ?>" class="btn btn-success btn-sm" title="Download Excel">
            <i class="bi bi-file-excel mr-1"></i>Excel
        </a>
        <a href="<?= base_url('reports/counter_summary?' . $export_params . '&export=csv') ?>" class="btn btn-outline-success btn-sm" title="Download CSV">
            <i class="bi bi-filetype-csv mr-1"></i>CSV
        </a>
        <a href="<?= base_url('reports') ?>" class="btn btn-outline-secondary btn-sm">← Reports</a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end g-2">
            <div class="col-md-2"><label class="small">From</label><input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>"></div>
            <div class="col-md-2"><label class="small">To</label><input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>"></div>
            <div class="col-md-3">
                <label class="small">Cashier / Counter</label>
                <select name="cashier_id" class="form-control form-control-sm">
                    <option value="">All Cashiers</option>
                    <?php foreach ($cashiers as $u): ?>
                    <option value="<?= $u->id ?>" <?= $cashier_id==$u->id?'selected':'' ?>><?= htmlspecialchars($u->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm btn-block mt-3">Filter</button></div>
        </form>
    </div>
</div>

<!-- Counter Performance Summary -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white font-weight-bold">Cashier-wise Performance Summary</div>
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0" style="font-size:.87rem;">
            <thead class="thead-light">
                <tr>
                    <th>Cashier</th>
                    <th>Shift</th>
                    <th>Opened At</th>
                    <th>Closed At</th>
                    <th class="text-right">Bills</th>
                    <th class="text-right">Sales (₹)</th>
                    <th class="text-right">Refund (₹)</th>
                    <th class="text-right">Net Sales (₹)</th>
                    <th class="text-right">Collected (₹)</th>
                    <th class="text-right">Due (₹)</th>
                    <th class="text-right">Discount (₹)</th>
                    <th class="text-right">Avg Bill (₹)</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($counter_summary)): ?>
                <tr><td colspan="12" class="text-center text-muted py-3">No shifts found for selected period</td></tr>
            <?php else: ?>
            <?php foreach ($counter_summary as $cs): ?>
            <tr>
                <td class="font-weight-bold"><?= htmlspecialchars($cs->cashier_name ?? 'Unknown') ?></td>
                <td><span class="badge badge-secondary">#<?= $cs->shift_id ?></span></td>
                <td><?= $cs->opened_at ? date('d-M H:i', strtotime($cs->opened_at)) : '—' ?></td>
                <td><?= $cs->closed_at ? date('d-M H:i', strtotime($cs->closed_at)) : '<span class="text-warning">Open</span>' ?></td>
                <td class="text-right"><?= number_format($cs->bill_count ?? 0) ?></td>
                <td class="text-right">₹<?= number_format($cs->total_sales ?? 0, 2) ?></td>
                <td class="text-right <?= ($cs->total_refunds??0)>0?'text-danger':'text-muted' ?>">₹<?= number_format($cs->total_refunds ?? 0, 2) ?></td>
                <td class="text-right font-weight-bold">₹<?= number_format($cs->net_sales ?? 0, 2) ?></td>
                <td class="text-right text-success">₹<?= number_format($cs->total_collected ?? 0, 2) ?></td>
                <td class="text-right <?= ($cs->total_due??0)>0?'text-danger':'' ?>">₹<?= number_format($cs->total_due ?? 0, 2) ?></td>
                <td class="text-right text-muted">₹<?= number_format($cs->total_discount ?? 0, 2) ?></td>
                <td class="text-right">₹<?= number_format($cs->avg_bill_value ?? 0, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($counter_summary)): ?>
            <tfoot class="font-weight-bold bg-light">
                <tr>
                    <td colspan="4">Total</td>
                    <td class="text-right"><?= array_sum(array_column($counter_summary,'bill_count')) ?></td>
                    <td class="text-right">₹<?= number_format(array_sum(array_column($counter_summary,'total_sales')),2) ?></td>
                    <td class="text-right text-danger">₹<?= number_format(array_sum(array_column($counter_summary,'total_refunds')),2) ?></td>
                    <td class="text-right">₹<?= number_format(array_sum(array_column($counter_summary,'net_sales')),2) ?></td>
                    <td class="text-right text-success">₹<?= number_format(array_sum(array_column($counter_summary,'total_collected')),2) ?></td>
                    <td class="text-right text-danger">₹<?= number_format(array_sum(array_column($counter_summary,'total_due')),2) ?></td>
                    <td class="text-right">₹<?= number_format(array_sum(array_column($counter_summary,'total_discount')),2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- Bill-wise Details -->
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between font-weight-bold">
        <span>Bill-wise Details (<?= count($bills) ?> bills<?= !empty($refunds) ? ', ' . count($refunds) . ' refunds' : '' ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:500px;overflow-y:auto;">
            <table class="table table-hover table-bordered mb-0" id="billTable" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th><th>Invoice No</th><th>Date</th><th>Cashier</th>
                        <th>Customer</th><th>Mode</th>
                        <th class="text-right">Total (₹)</th>
                        <th class="text-right">Paid (₹)</th>
                        <th class="text-right">Due (₹)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bill_rows as $i => $b): ?>
                <?php $is_refund = $b->row_type === 'refund'; ?>
                <tr class="<?= $is_refund ? 'text-danger' : '' ?>">
                    <td><?= $i+1 ?></td>
                    <td>
                        <?php if ($is_refund): ?>
                        <a class="text-danger" href="<?= base_url('refunds/view/'.$b->id) ?>" target="_blank"><?= htmlspecialchars($b->ref_no) ?></a>
                        <?php else: ?>
                        <a href="<?= base_url('billing/invoice/'.$b->id) ?>" target="_blank"><?= htmlspecialchars($b->ref_no) ?></a>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d-M-Y H:i', strtotime($b->ts)) ?></td>
                    <td><?= htmlspecialchars($b->cashier_name ?? '—') ?></td>
                    <td><?= htmlspecialchars($b->customer_name ?? '—') ?></td>
                    <td><span class="badge <?= $is_refund ? 'badge-danger' : 'badge-light' ?>"><?= ucfirst($b->mode) ?></span></td>
                    <td class="text-right <?= $is_refund ? 'text-danger font-weight-bold' : '' ?>">₹<?= number_format($b->amount,2) ?></td>
                    <td class="text-right <?= $is_refund ? 'text-danger' : 'text-success' ?>">₹<?= $b->paid !== null ? number_format($b->paid,2) : '—' ?></td>
                    <td class="text-right <?= ($b->due??0)>0?'text-danger':'' ?>">₹<?= $b->due !== null ? number_format($b->due,2) : '—' ?></td>
                    <td><span class="badge badge-<?= $is_refund?'danger':($b->status==='paid'?'success':($b->status==='partial'?'warning':'secondary')) ?>"><?= ucfirst($b->status) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($bill_rows)): ?>
                <tr><td colspan="10" class="text-center text-muted py-3">No bills found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
