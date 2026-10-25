<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 font-weight-bold text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Emergency Day Close Record</h5>
                <small class="text-muted">Business date: <strong><?= date('d-M-Y', strtotime($log->business_date)) ?></strong></small>
            </div>
            <a href="<?= base_url('emergency_day_close/history') ?>" class="btn btn-outline-secondary btn-sm">← Back to History</a>
        </div>

        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
        <?php endif; ?>

        <div class="alert alert-danger font-weight-bold text-center py-2">
            EMERGENCY DAY CLOSE
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-dark text-white py-2 font-weight-bold">Audit Details (Immutable)</div>
            <div class="card-body">
                <table class="table table-sm mb-0" style="font-size:.87rem;">
                    <tr><td class="text-muted" style="width:220px;">Business Date</td><td class="font-weight-bold"><?= date('d M Y', strtotime($log->business_date)) ?></td></tr>
                    <tr><td class="text-muted">Closed By</td><td><?= htmlspecialchars($log->closed_by_name) ?> (User ID <?= $log->closed_by ?>)</td></tr>
                    <tr><td class="text-muted">Date &amp; Time</td><td><?= date('d M Y H:i:s', strtotime($log->closed_at)) ?></td></tr>
                    <tr><td class="text-muted">IP Address</td><td><code><?= htmlspecialchars($log->ip_address ?? '-') ?></code></td></tr>
                    <tr><td class="text-muted">Machine</td><td><?= htmlspecialchars($log->machine_name ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Active Counters Force-Closed</td><td class="font-weight-bold"><?= (int)$log->active_counters_count ?></td></tr>
                    <tr><td class="text-muted">Counter Names</td><td><?= htmlspecialchars($log->counter_names ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Reason</td><td><span class="badge badge-warning"><?= htmlspecialchars($log->reason) ?></span></td></tr>
                    <tr><td class="text-muted">Remarks</td><td><?= nl2br(htmlspecialchars($log->remarks ?? '-')) ?></td></tr>
                    <tr><td class="text-muted">Backup Status</td><td><?= htmlspecialchars($log->backup_status ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Sales Amount</td><td class="font-weight-bold">₹<?= number_format($log->sales_amount, 2) ?></td></tr>
                </table>
            </div>
        </div>

        <?php if ($business_day): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-dark text-white py-2 font-weight-bold">Day Close Summary</div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0" style="font-size:.87rem;">
                    <tr><td class="pl-3 py-2">Total Sales</td><td class="text-right pr-3 font-weight-bold">₹<?= number_format($business_day->total_sales, 2) ?></td></tr>
                    <tr><td class="pl-3 py-2">Discounts Given</td><td class="text-right pr-3 text-danger">- ₹<?= number_format($business_day->total_discounts, 2) ?></td></tr>
                    <tr><td class="pl-3 py-2">GST / Tax Collected</td><td class="text-right pr-3">₹<?= number_format($business_day->total_taxes, 2) ?></td></tr>
                    <tr><td class="pl-3 py-2">Refunds Processed</td><td class="text-right pr-3 text-danger">- ₹<?= number_format($business_day->total_refunds ?? 0, 2) ?></td></tr>
                    <tr><td colspan="2" class="pl-3 py-1 small font-weight-bold text-muted bg-light">PAYMENT MODE BREAKDOWN</td></tr>
                    <tr><td class="pl-3 py-1">💵 Cash</td><td class="text-right pr-3">₹<?= number_format($business_day->total_cash, 2) ?></td></tr>
                    <tr><td class="pl-3 py-1">⚡ UPI</td><td class="text-right pr-3">₹<?= number_format($business_day->total_upi, 2) ?></td></tr>
                    <tr><td class="pl-3 py-1">💳 Card</td><td class="text-right pr-3">₹<?= number_format($business_day->total_card, 2) ?></td></tr>
                    <tr><td class="pl-3 py-1">🏛️ Bank/NEFT</td><td class="text-right pr-3">₹<?= number_format($business_day->total_bank, 2) ?></td></tr>
                    <tr><td class="pl-3 py-1">📱 Wallet</td><td class="text-right pr-3">₹<?= number_format($business_day->total_wallet, 2) ?></td></tr>
                    <tr><td class="pl-3 py-1">🎁 Gift Voucher</td><td class="text-right pr-3">₹<?= number_format($business_day->total_gift_voucher, 2) ?></td></tr>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
