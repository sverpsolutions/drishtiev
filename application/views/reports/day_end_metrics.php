<?php // Day End Metrics Dashboard ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold">Day End Metrics Dashboard</h5>
        <small class="text-muted"><?= date('d-M-Y', strtotime($date)) ?></small>
    </div>
    <div>
        <?php $export_qs = "?date={$date}"; ?>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm shadow-sm"><i class="fas fa-print"></i> Print</button>
        <a href="<?= base_url("reports/day_end_metrics{$export_qs}&export=pdf") ?>" class="btn btn-outline-danger btn-sm shadow-sm"><i class="fas fa-file-pdf"></i> PDF</a>
        <a href="<?= base_url("reports/day_end_metrics{$export_qs}&export=excel") ?>" class="btn btn-outline-success btn-sm shadow-sm"><i class="fas fa-file-excel"></i> Excel</a>
        <button data-toggle="modal" data-target="#emailModal" class="btn btn-outline-info btn-sm shadow-sm"><i class="fas fa-envelope"></i> Email</button>
        <a href="<?= base_url('reports') ?>" class="btn btn-outline-secondary btn-sm ml-2">← Reports</a>
    </div>
</div>

<!-- Date Picker -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end g-2">
            <div class="col-md-3"><label class="small">Date</label><input type="date" name="date" class="form-control form-control-sm" value="<?= $date ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm btn-block mt-3">Load</button></div>
        </form>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card bg-success text-white shadow-sm text-center">
            <div class="card-body py-3">
                <div class="small mb-1">Total Sales</div>
                <div class="h5 font-weight-bold">₹<?= number_format($total_sales, 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-primary text-white shadow-sm text-center">
            <div class="card-body py-3">
                <div class="small mb-1">Bills Generated</div>
                <div class="h5 font-weight-bold"><?= number_format($bill_count) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white shadow-sm text-center">
            <div class="card-body py-3">
                <div class="small mb-1">Avg Bill Value</div>
                <div class="h5 font-weight-bold">₹<?= number_format($avg_bill, 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-dark shadow-sm text-center">
            <div class="card-body py-3">
                <div class="small mb-1">Customers</div>
                <div class="h5 font-weight-bold"><?= number_format($customer_count) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white shadow-sm text-center">
            <div class="card-body py-3">
                <div class="small mb-1">Returns Value</div>
                <div class="h5 font-weight-bold">₹<?= number_format($total_returns, 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-dark text-white shadow-sm text-center">
            <div class="card-body py-3">
                <div class="small mb-1">Cancellations</div>
                <div class="h5 font-weight-bold"><?= number_format($cancelled_count) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Payment Mode Summary -->
    <div class="col-md-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header font-weight-bold bg-dark text-white">Payment Mode Summary</div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="thead-light"><tr><th>Mode</th><th class="text-right">Bills</th><th class="text-right">Amount</th><th class="text-right">%</th></tr></thead>
                    <tbody>
                    <?php
                    $modes_label = ['cash'=>'💵 Cash','upi'=>'⚡ UPI','card'=>'💳 Card','neft'=>'🏛️ NEFT','cheque'=>'📄 Cheque','wallet'=>'📱 Wallet','credit'=>'📋 Credit','split'=>'🔀 Split'];
                    $total_collected = array_sum(array_column($payment_modes, 'amount'));
                    foreach ($payment_modes as $mode => $vals):
                        $pct = $total_collected > 0 ? round($vals['amount']/$total_collected*100,1) : 0;
                    ?>
                    <tr>
                        <td><?= $modes_label[$mode] ?? ucfirst($mode) ?></td>
                        <td class="text-right"><?= $vals['count'] ?></td>
                        <td class="text-right font-weight-bold">₹<?= number_format($vals['amount'],2) ?></td>
                        <td class="text-right text-muted"><?= $pct ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="font-weight-bold bg-light">
                        <tr><td>Total</td><td class="text-right"><?= $bill_count ?></td><td class="text-right">₹<?= number_format($total_collected,2) ?></td><td class="text-right">100%</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Returns & Cancellations -->
    <div class="col-md-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header font-weight-bold bg-danger text-white">Returns & Cancellations</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Sales Returns (Credit Notes)</td><td class="text-right font-weight-bold"><?= $returns_count ?> returns</td><td class="text-right text-danger font-weight-bold">₹<?= number_format($total_returns,2) ?></td></tr>
                    <tr><td>Cancelled Bills</td><td class="text-right"><?= $cancelled_count ?></td><td class="text-right text-muted">—</td></tr>
                    <tr class="font-weight-bold bg-light"><td>Net Sales (after returns &amp; refunds)</td><td></td><td class="text-right text-success">₹<?= number_format($total_sales, 2) ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Hour-wise summary -->
        <div class="card shadow-sm">
            <div class="card-header font-weight-bold">Hour-wise Bill Volume</div>
            <div class="card-body p-2">
                <?php
                $hourly = [];
                foreach ($invoices as $inv) {
                    $h = date('H', strtotime($inv->created_at ?? $inv->invoice_date));
                    if (!isset($hourly[$h])) $hourly[$h] = 0;
                    $hourly[$h]++;
                }
                ksort($hourly);
                $max_h = max($hourly ?: [1]);
                ?>
                <div class="d-flex align-items-end" style="height:80px;gap:4px;">
                    <?php foreach ($hourly as $h => $cnt): ?>
                    <div class="flex-fill text-center" title="<?= $h ?>:00 — <?= $cnt ?> bills">
                        <div class="bg-primary rounded-top" style="height:<?= round($cnt/$max_h*70) ?>px; min-height:4px;"></div>
                        <div style="font-size:.65rem;color:#666;"><?= $h ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($hourly)): ?>
                    <div class="text-muted small w-100 text-center">No bills on this date</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bill-wise list -->
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between font-weight-bold">
        <span>All Bills (<?= $bill_count ?>)</span>
        <button class="btn btn-outline-success btn-sm" onclick="exportBills()"><i class="bi bi-file-excel mr-1"></i>Export</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
            <table class="table table-hover table-bordered mb-0" id="billsTable" style="font-size:.83rem;">
                <thead class="thead-dark">
                    <tr><th>#</th><th>Invoice No</th><th>Customer</th><th>Mode</th><th class="text-right">Amount</th><th class="text-right">Paid</th><th class="text-right">Due</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach ($invoices as $i => $inv): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><a href="<?= base_url('billing/invoice/'.$inv->id) ?>"><?= $inv->invoice_no ?></a></td>
                    <td><?= htmlspecialchars($inv->customer_name ?? '—') ?></td>
                    <td><span class="badge badge-secondary"><?= ucfirst($inv->payment_mode) ?></span></td>
                    <td class="text-right">₹<?= number_format($inv->total_amount,2) ?></td>
                    <td class="text-right text-success">₹<?= number_format($inv->paid_amount,2) ?></td>
                    <td class="text-right <?= $inv->due_amount>0?'text-danger':'' ?>">₹<?= number_format($inv->due_amount,2) ?></td>
                    <td><span class="badge badge-<?= $inv->status==='paid'?'success':($inv->status==='partial'?'warning':'secondary') ?>"><?= ucfirst($inv->status) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($invoices)): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">No bills on this date</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportBills() {
    const rows = document.querySelectorAll('#billsTable tr');
    let csv = [];
    rows.forEach(r => {
        csv.push(Array.from(r.querySelectorAll('th,td')).map(c=>'"'+c.innerText.trim().replace(/"/g,'""')+'"').join(','));
    });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv.join('\n')],{type:'text/csv'}));
    a.download = 'day_end_<?= $date ?>.csv';
    a.click();
}
</script>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= base_url("reports/email_day_end_metrics") ?>" method="post" class="modal-content">
            <input type="hidden" name="date" value="<?= $date ?>">
            
            <div class="modal-header">
                <h5 class="modal-title">Email Day End Metrics</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>To Email <span class="text-danger">*</span></label>
                    <input type="email" name="to_email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>CC Email</label>
                    <input type="email" name="cc_email" class="form-control">
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="email_subject" class="form-control" value="Day End Metrics (<?= $date ?>)">
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="email_message" class="form-control" rows="3">Please find attached the Day End Metrics Report.</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Email</button>
            </div>
        </form>
    </div>
</div>

