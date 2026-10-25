<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 font-weight-bold text-dark"><i class="fas fa-moon text-danger mr-2"></i> Close Business Day</h4>
                <small class="text-muted">Consolidated summary for active business date: <strong><?= date('d-M-Y', strtotime($open_day->business_date)) ?></strong></small>
            </div>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
        </div>

        <?php if ($validation_failed): ?>
            <!-- WARNINGS BLOCK -->
            <div class="card border-danger mb-4 shadow-sm">
                <div class="card-header bg-danger text-white py-2 font-weight-bold">
                    ⚠️ Validation Warnings (Day Close Blocked)
                </div>
                <div class="card-body">
                    <p class="text-danger font-weight-bold">Please resolve the following issues before closing the day:</p>
                    <ul class="mb-0 pl-4">
                        <?php if (!empty($open_shifts)): ?>
                            <li class="mb-2">
                                <strong>Active shifts are still open:</strong>
                                <div class="mt-1">
                                    <?php foreach ($open_shifts as $os): ?>
                                        <span class="badge badge-warning font-weight-bold px-2 py-1 mr-1">
                                            <?= htmlspecialchars($os->cashier_name) ?> (Shift #<?= $os->id ?>)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($draft_invoices)): ?>
                            <li>
                                <strong>Draft / Pending invoices exist (<?= count($draft_invoices) ?> items):</strong>
                                <div class="mt-1">
                                    <?php foreach ($draft_invoices as $di): ?>
                                        <a href="<?= base_url('billing/create/'.$di->id) ?>" class="badge badge-info px-2 py-1 mr-1">
                                            Invoice #<?= $di->invoice_no ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left: Shift summary -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-dark text-white py-2 font-weight-bold">Shifts Summary for Today</div>
                    <div class="card-body p-0">
                        <?php if (empty($shifts)): ?>
                            <div class="text-center py-4 text-muted small">No shifts recorded for this business date.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0" style="font-size: .80rem;">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Cashier</th>
                                            <th>System</th>
                                            <th>Shift</th>
                                            <th>Open</th>
                                            <th>Close</th>
                                            <th>Opening Cash</th>
                                            <th>Expected Cash</th>
                                            <th>Declared</th>
                                            <th>Short</th>
                                            <th>Excess</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($shifts as $s):
                                            $exp_cash = $s->opening_cash + $s->system_cash;
                                        ?>
                                            <tr>
                                                <td class="align-middle"><?= $s->id ?></td>
                                                <td class="align-middle font-weight-bold"><?= htmlspecialchars($s->cashier_name) ?></td>
                                                <td class="align-middle"><?= htmlspecialchars($s->system_name ?? '—') ?></td>
                                                <td class="align-middle"><?= htmlspecialchars($s->shift_name ?? '—') ?></td>
                                                <td class="align-middle"><?= date('H:i', strtotime($s->opened_at)) ?></td>
                                                <td class="align-middle"><?= $s->closed_at ? date('H:i', strtotime($s->closed_at)) : '—' ?></td>
                                                <td class="align-middle">₹<?= number_format($s->opening_cash, 2) ?></td>
                                                <td class="align-middle text-primary font-weight-bold">₹<?= number_format($exp_cash, 2) ?></td>
                                                <td class="align-middle">₹<?= number_format($s->actual_cash, 2) ?></td>
                                                <td class="align-middle text-danger font-weight-bold"><?= $s->short_amount > 0 ? '₹'.number_format($s->short_amount,2) : '—' ?></td>
                                                <td class="align-middle text-success font-weight-bold"><?= $s->excess_amount > 0 ? '₹'.number_format($s->excess_amount,2) : '—' ?></td>
                                                <td class="align-middle">
                                                    <span class="badge badge-<?= $s->status === 'open' ? 'warning' : 'success' ?>">
                                                        <?= strtoupper($s->status) ?>
                                                    </span>
                                                </td>
                                                <td class="text-right align-middle">
                                                    <a href="<?= base_url('shifts/shift_report/'.$s->id) ?>" class="btn btn-xs btn-outline-info">Report</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Consolidated totals -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-dark text-white py-2 font-weight-bold">Consolidated Collection Summary</div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0" style="font-size: .88rem;">
                            <tbody>
                                <tr><td class="pl-3 py-2">Total Bills</td><td class="text-right pr-3 font-weight-bold"><?= $total_bills ?? 0 ?></td></tr>
                                <tr><td class="pl-3 py-2">Cancelled Bills</td><td class="text-right pr-3 text-danger"><?= $cancelled_bills ?? 0 ?></td></tr>
                                <tr class="table-dark"><td class="pl-3 py-2 font-weight-bold">Gross Sales</td><td class="text-right pr-3 font-weight-bold">₹<?= number_format($total_subtotal, 2) ?></td></tr>
                                <tr><td class="pl-3 py-2">Discounts Given</td><td class="text-right pr-3 text-danger">- ₹<?= number_format($total_discounts, 2) ?></td></tr>
                                <tr><td class="pl-3 py-2">GST Collected</td><td class="text-right pr-3">₹<?= number_format($total_taxes, 2) ?></td></tr>
                                <tr><td class="pl-3 py-2">Refunds Processed</td><td class="text-right pr-3 text-danger">- ₹<?= number_format($total_refunds ?? 0, 2) ?></td></tr>
                                <tr class="font-weight-bold"><td class="pl-3 py-2 text-primary">Net Sales</td><td class="text-right pr-3 text-primary">₹<?= number_format($total_sales, 2) ?></td></tr>
                                <tr><td colspan="2" class="pl-3 py-1 small font-weight-bold text-muted bg-light">PAYMENT MODE BREAKDOWN</td></tr>
                                <tr><td class="pl-3 py-1">💵 Cash</td><td class="text-right pr-3 text-success font-weight-bold">₹<?= number_format($total_cash, 2) ?></td></tr>
                                <tr><td class="pl-3 py-1">⚡ UPI</td><td class="text-right pr-3 text-success font-weight-bold">₹<?= number_format($total_upi, 2) ?></td></tr>
                                <tr><td class="pl-3 py-1">💳 Card</td><td class="text-right pr-3 text-success font-weight-bold">₹<?= number_format($total_card, 2) ?></td></tr>
                                <tr><td class="pl-3 py-1">🏛️ Bank/NEFT</td><td class="text-right pr-3 text-success font-weight-bold">₹<?= number_format($total_bank, 2) ?></td></tr>
                                <tr><td class="pl-3 py-1">📱 Wallet</td><td class="text-right pr-3 text-success font-weight-bold">₹<?= number_format($total_wallet, 2) ?></td></tr>
                                <tr><td class="pl-3 py-1">🎁 Gift Voucher</td><td class="text-right pr-3 text-success font-weight-bold">₹<?= number_format($total_gift_voucher ?? 0, 2) ?></td></tr>
                                <tr class="table-warning">
                                    <td class="pl-3 py-2 font-weight-bold">Cash Shortage</td>
                                    <td class="text-right pr-3 text-danger font-weight-bold">₹<?= number_format($total_shortage, 2) ?></td>
                                </tr>
                                <tr class="table-warning">
                                    <td class="pl-3 py-2 font-weight-bold">Cash Excess</td>
                                    <td class="text-right pr-3 text-success font-weight-bold">₹<?= number_format($total_excess, 2) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?= form_open('shifts/day_close') ?>
                    <button type="submit" class="btn btn-danger btn-block btn-lg font-weight-bold shadow" 
                            <?= $validation_failed ? 'disabled' : '' ?> 
                            onclick="return confirm('Confirm Day Close? This action is irreversible.')">
                        <i class="fas fa-power-off mr-1"></i> Confirm &amp; Close Business Day
                    </button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>
