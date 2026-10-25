<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
            <h4 class="mb-0 font-weight-bold">Shift Summary Report</h4>
            <div>
                <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fas fa-print"></i> Print Full Report</button>
                <a href="<?= base_url('shifts/shift_thermal/'.$shift->id.'?autoprint=1') ?>" target="_blank" class="btn btn-sm btn-outline-primary ml-1"><i class="fas fa-receipt"></i> Print 3-inch Receipt</a>
                <a href="<?= base_url('shifts/shift_report/'.$shift->id.'?pdf=1') ?>" class="btn btn-sm btn-outline-danger ml-1"><i class="fas fa-file-pdf"></i> Download PDF</a>
                <a href="<?= base_url('dashboard') ?>" class="btn btn-sm btn-outline-secondary ml-1">Dashboard</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-4">
            <!-- Header Header -->
            <div class="text-center mb-4">
                <h3 class="font-weight-bold text-dark mb-0"><?= htmlspecialchars($company['company_name'] ?? 'View & Chew') ?></h3>
                <small class="text-muted d-block"><?= htmlspecialchars($company['company_address'] ?? 'ERP Solutions') ?></small>
                <small class="text-muted">GSTIN: <code><?= htmlspecialchars($company['company_gstin'] ?? 'N/A') ?></code></small>
                <hr class="my-3">
                <h5 class="font-weight-bold text-uppercase text-secondary">Shift Closing Summary</h5>
                <h6 class="font-weight-bold">Shift ID: #<?= $shift->id ?> &middot; Status: <span class="badge badge-success"><?= strtoupper($shift->status) ?></span></h6>
            </div>

            <!-- Details Block -->
            <div class="row mb-4" style="font-size: .9rem;">
                <div class="col-sm-6 mb-2">
                    <span class="text-muted d-block">Cashier Name:</span>
                    <strong><?= htmlspecialchars($shift->cashier_name) ?></strong>
                </div>
                <div class="col-sm-6 mb-2 text-sm-right">
                    <span class="text-muted d-block">Business Date:</span>
                    <strong><?= date('d-M-Y', strtotime($shift->business_date)) ?></strong>
                </div>
                <div class="col-sm-6 mb-2">
                    <span class="text-muted d-block">Shift Start Time:</span>
                    <strong><?= date('d-M-Y H:i:s', strtotime($shift->opened_at)) ?></strong>
                </div>
                <div class="col-sm-6 mb-2 text-sm-right">
                    <span class="text-muted d-block">Shift End Time:</span>
                    <strong><?= $shift->closed_at ? date('d-M-Y H:i:s', strtotime($shift->closed_at)) : 'N/A' ?></strong>
                </div>
            </div>

            <!-- Reconciliation Matrix Table -->
            <h6 class="font-weight-bold mb-2 text-uppercase text-secondary" style="font-size: .8rem; letter-spacing: 0.5px;">Collection Reconciliation</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm" style="font-size: 1rem;">
                    <thead class="thead-dark text-center">
                        <tr>
                            <th>Payment Mode</th>
                            <th>System (₹)</th>
                            <th>Actual (₹)</th>
                            <th>Difference (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sys_cash_adj = !empty($cash_summary) ? $cash_summary['carry_forward'] : $shift->system_cash; $adj_diff = (float)$shift->actual_cash - $sys_cash_adj; ?>
                        <tr>
                            <td class="font-weight-bold pl-2">Cash</td>
                            <td class="text-right pr-2">₹<?= number_format($sys_cash_adj, 2) ?></td>
                            <td class="text-right pr-2">₹<?= number_format($shift->actual_cash, 2) ?></td>
                            <td class="text-right pr-2 <?= $adj_diff < -0.01 ? 'text-danger' : ($adj_diff > 0.01 ? 'text-success' : 'text-muted') ?>">
                                <?= abs($adj_diff) < 0.01 ? '—' : (($adj_diff > 0 ? '+' : '-') . ' ₹' . number_format(abs($adj_diff), 2)) ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2">UPI</td>
                            <td class="text-right pr-2">₹<?= number_format($shift->system_upi, 2) ?></td>
                            <td class="text-right pr-2">₹<?= number_format($shift->actual_upi, 2) ?></td>
                            <td class="text-right pr-2 <?= $shift->diff_upi < 0 ? 'text-danger' : ($shift->diff_upi > 0 ? 'text-success' : 'text-muted') ?>">
                                <?= $shift->diff_upi < 0 ? '-' : ($shift->diff_upi > 0 ? '+' : '') ?> ₹<?= number_format(abs($shift->diff_upi), 2) ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2">Card</td>
                            <td class="text-right pr-2">₹<?= number_format($shift->system_card, 2) ?></td>
                            <td class="text-right pr-2">₹<?= number_format($shift->actual_card, 2) ?></td>
                            <td class="text-right pr-2 <?= $shift->diff_card < 0 ? 'text-danger' : ($shift->diff_card > 0 ? 'text-success' : 'text-muted') ?>">
                                <?= $shift->diff_card < 0 ? '-' : ($shift->diff_card > 0 ? '+' : '') ?> ₹<?= number_format(abs($shift->diff_card), 2) ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2">Bank Transfer</td>
                            <td class="text-right pr-2">₹<?= number_format($shift->system_bank, 2) ?></td>
                            <td class="text-right pr-2">₹<?= number_format($shift->actual_bank, 2) ?></td>
                            <td class="text-right pr-2 text-muted">—</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2">Wallet</td>
                            <td class="text-right pr-2">₹<?= number_format($shift->system_wallet, 2) ?></td>
                            <td class="text-right pr-2">₹<?= number_format($shift->actual_wallet, 2) ?></td>
                            <td class="text-right pr-2 text-muted">—</td>
                        </tr>
                        <tr class="bg-light text-muted">
                            <td class="pl-2">Credit Sales (Bills Due) <small>— not included in cash</small></td>
                            <td class="text-right pr-2">₹<?= number_format($shift->system_credit, 2) ?></td>
                            <td class="text-center small">—</td>
                            <td class="text-center small">—</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Credit Bills (due bills raised in this shift) -->
            <?php if (!empty($credit_bills)): ?>
            <h6 class="font-weight-bold mb-2 text-uppercase text-secondary" style="font-size: .8rem; letter-spacing: 0.5px;">Credit Bills (<?= count($credit_bills) ?>)</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm" style="font-size: .9rem;">
                    <thead class="thead-light">
                        <tr>
                            <th>Bill No</th>
                            <th>Customer</th>
                            <th class="text-right">Bill Amount</th>
                            <th class="text-right">Paid at Billing</th>
                            <th class="text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $cb_bill = 0; $cb_paid = 0; $cb_credit = 0; foreach ($credit_bills as $cb): $cb_bill += $cb->total_amount; $cb_paid += $cb->paid_at_bill; $cb_credit += $cb->credit_amount; ?>
                        <tr>
                            <td class="pl-2"><a href="<?= base_url('billing/invoice/' . $cb->id) ?>" class="d-print-none"><?= htmlspecialchars($cb->invoice_no) ?></a><span class="d-none d-print-inline"><?= htmlspecialchars($cb->invoice_no) ?></span></td>
                            <td>
                                <?= htmlspecialchars($cb->customer_name ?: 'Walk-in') ?>
                                <?php if (!empty($cb->customer_phone)): ?><small class="text-muted d-block"><?= htmlspecialchars($cb->customer_phone) ?></small><?php endif; ?>
                            </td>
                            <td class="text-right pr-2">₹<?= number_format($cb->total_amount, 2) ?></td>
                            <td class="text-right pr-2">₹<?= number_format($cb->paid_at_bill, 2) ?></td>
                            <td class="text-right pr-2 font-weight-bold text-danger">
                                ₹<?= number_format($cb->credit_amount, 2) ?>
                                <?php if ($cb->collected > 0): ?><small class="text-success d-block">₹<?= number_format($cb->collected, 2) ?> collected later</small><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-light font-weight-bold">
                            <td colspan="2" class="pl-2">Total</td>
                            <td class="text-right pr-2">₹<?= number_format($cb_bill, 2) ?></td>
                            <td class="text-right pr-2">₹<?= number_format($cb_paid, 2) ?></td>
                            <td class="text-right pr-2 text-danger">₹<?= number_format($cb_credit, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>

            <!-- Cash Flow Summary (from Cash Management module) -->
            <?php if (!empty($cash_summary)): ?>
            <h6 class="font-weight-bold mb-2 text-uppercase text-secondary" style="font-size: .8rem; letter-spacing: 0.5px;">Cash Flow Summary</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm" style="font-size: .95rem;">
                    <tbody>
                        <tr class="bg-light">
                            <td class="font-weight-bold pl-2">Opening Cash</td>
                            <td class="text-right pr-2 text-success font-weight-bold" style="width:150px;">+ ₹<?= number_format($cash_summary['opening_cash'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2">Cash Sales</td>
                            <td class="text-right pr-2 text-success font-weight-bold">+ ₹<?= number_format($cash_summary['cash_sales'], 2) ?></td>
                        </tr>
                        <?php if ($cash_summary['cash_received'] > 0): ?>
                        <tr>
                            <td class="font-weight-bold pl-2">Cash Received (Payments)</td>
                            <td class="text-right pr-2 text-success font-weight-bold">+ ₹<?= number_format($cash_summary['cash_received'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="pl-2">
                                <span class="font-weight-bold">Pay-In</span>
                                <?php if (!empty($cash_payins)): ?>
                                <small class="text-muted ml-1">(<?= count($cash_payins) ?> entry)</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-right pr-2 text-success font-weight-bold">+ ₹<?= number_format($cash_summary['pay_in'], 2) ?></td>
                        </tr>
                        <?php if (!empty($cash_payins)): foreach ($cash_payins as $pi): ?>
                        <tr class="bg-light" style="font-size:.85rem;">
                            <td class="pl-4 text-muted"><i class="fas fa-arrow-right fa-xs mr-1"></i><?= htmlspecialchars($pi->txn_no) ?> — <?= htmlspecialchars($pi->category_name ?? '') ?> <?= $pi->description ? '(' . htmlspecialchars($pi->description) . ')' : '' ?></td>
                            <td class="text-right pr-2 text-muted">₹<?= number_format($pi->amount, 2) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        <tr>
                            <td class="font-weight-bold pl-2">Cash Refunds</td>
                            <td class="text-right pr-2 text-danger font-weight-bold">- ₹<?= number_format($cash_summary['cash_refunds'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="pl-2">
                                <span class="font-weight-bold">Pay-Out</span>
                                <?php if (!empty($cash_payouts)): ?>
                                <small class="text-muted ml-1">(<?= count($cash_payouts) ?> entry)</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-right pr-2 text-danger font-weight-bold">- ₹<?= number_format($cash_summary['pay_out'], 2) ?></td>
                        </tr>
                        <?php if (!empty($cash_payouts)): foreach ($cash_payouts as $po): ?>
                        <tr class="bg-light" style="font-size:.85rem;">
                            <td class="pl-4 text-muted"><i class="fas fa-arrow-right fa-xs mr-1"></i><?= htmlspecialchars($po->txn_no) ?> — <?= htmlspecialchars($po->category_name ?? '') ?> <?= $po->description ? '(' . htmlspecialchars($po->description) . ')' : '' ?></td>
                            <td class="text-right pr-2 text-muted">₹<?= number_format($po->amount, 2) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        <tr class="border-top" style="border-top: 2px solid #333 !important;">
                            <td class="font-weight-bold pl-2 bg-light">Expected Cash in Drawer</td>
                            <td class="text-right pr-2 font-weight-bold bg-light">₹<?= number_format($cash_summary['expected_cash'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="pl-2">
                                <span class="font-weight-bold text-danger">Cash Handover to Owner</span>
                                <?php if (!empty($cash_handovers)): ?>
                                <small class="text-muted ml-1">(<?= count($cash_handovers) ?> entry)</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-right pr-2 text-danger font-weight-bold">- ₹<?= number_format($cash_summary['handovers'], 2) ?></td>
                        </tr>
                        <?php if (!empty($cash_handovers)): foreach ($cash_handovers as $ho): ?>
                        <tr class="bg-light" style="font-size:.85rem;">
                            <td class="pl-4 text-muted"><i class="fas fa-arrow-right fa-xs mr-1"></i><?= htmlspecialchars($ho->handover_no) ?> — <?= htmlspecialchars($ho->from_user_name ?? '?') ?> → <?= htmlspecialchars($ho->to_user_name ?? '?') ?> <?= !empty($ho->remarks) ? '(' . htmlspecialchars($ho->remarks) . ')' : '' ?></td>
                            <td class="text-right pr-2 text-muted">₹<?= number_format($ho->handover_amount, 2) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        <tr class="table-warning" style="border-top: 2px solid #333 !important;">
                            <td class="font-weight-bold pl-2">Expected Closing Cash</td>
                            <td class="text-right pr-2 font-weight-bold" style="font-size:1.05rem;">₹<?= number_format($cash_summary['carry_forward'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2">Cash Counted (Actual)</td>
                            <td class="text-right pr-2 font-weight-bold" style="font-size:1.05rem;">₹<?= number_format($shift->actual_cash, 2) ?></td>
                        </tr>
                        <?php
                        $cf_diff = (float)$shift->actual_cash - (float)$cash_summary['carry_forward'];
                        if (abs($cf_diff) >= 0.01):
                            $cf_label = $cf_diff > 0 ? 'Excess' : 'Shortage';
                            $cf_class = $cf_diff > 0 ? 'text-success' : 'text-danger';
                            $cf_sign  = $cf_diff > 0 ? '+' : '';
                        ?>
                        <tr>
                            <td class="font-weight-bold pl-2 <?= $cf_class ?>"><?= $cf_label ?></td>
                            <td class="text-right pr-2 font-weight-bold <?= $cf_class ?>"><?= $cf_sign ?>₹<?= number_format($cf_diff, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr style="background: #2d5f2d; color: #fff;">
                            <td class="font-weight-bold pl-2" style="font-size:1rem;">NEXT DAY OPENING</td>
                            <td class="text-right pr-2 font-weight-bold" style="font-size:1.1rem;">₹<?= number_format($shift->actual_cash, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Summary Table -->
            <div class="row">
                <div class="col-md-6 offset-md-6">
                    <table class="table table-bordered table-sm" style="font-size: .88rem;">
                        <tr>
                            <td class="font-weight-bold pl-2 bg-light">Opening Cash Float:</td>
                            <td class="text-right pr-2 font-weight-bold">₹<?= number_format($shift->opening_cash, 2) ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2 bg-light">Total Sales Value:</td>
                            <td class="text-right pr-2 font-weight-bold">₹<?= number_format($total_sales, 2) ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2 bg-light">Refund Amount:</td>
                            <td class="text-right pr-2 font-weight-bold">₹<?= number_format($total_refunds ?? 0, 2) ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2 bg-light">Overall Discount:</td>
                            <td class="text-right pr-2 font-weight-bold">₹<?= number_format($total_disc ?? 0, 2) ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2 bg-light">No. of Bills:</td>
                            <td class="text-right pr-2 font-weight-bold"><?= $bill_count ?? 0 ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2 bg-light">Sales Receipts No(s):</td>
                            <td class="text-right pr-2 font-weight-bold"><?= ($first_receipt ?? '') . ' - ' . ($last_receipt ?? '') ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2 bg-light">No. of Items Sold:</td>
                            <td class="text-right pr-2 font-weight-bold"><?= $items_sold ?? 0 ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2 bg-light">No. of Refunds:</td>
                            <td class="text-right pr-2 font-weight-bold"><?= $refund_count ?? 0 ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2 bg-light">Bill Payment Mode Changes:</td>
                            <td class="text-right pr-2 font-weight-bold"><?= $payment_mode_changes ?? 0 ?></td>
                        </tr>
                        <?php
                        $rpt_exp = $shift->opening_cash + $shift->system_cash;
                        if (!empty($cash_summary)) $rpt_exp = $cash_summary['carry_forward'];
                        $rpt_diff = (float)$shift->actual_cash - $rpt_exp;
                        ?>
                        <tr>
                            <td class="font-weight-bold pl-2 bg-light text-danger">Total Shortage Amount:</td>
                            <td class="text-right pr-2 font-weight-bold text-danger">₹<?= number_format($rpt_diff < -0.01 ? abs($rpt_diff) : 0, 2) ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold pl-2 bg-light text-success">Total Excess Amount:</td>
                            <td class="text-right pr-2 font-weight-bold text-success">₹<?= number_format($rpt_diff > 0.01 ? $rpt_diff : 0, 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Footer Remarks -->
            <?php if (!empty($shift->remarks)): ?>
                <hr>
                <h6 class="font-weight-bold text-muted mb-1" style="font-size: .8rem;">Remarks:</h6>
                <p class="text-dark bg-light p-2 rounded mb-0" style="font-size: .85rem; font-style: italic;"><?= htmlspecialchars($shift->remarks) ?></p>
            <?php endif; ?>
            
            <hr class="mt-4 mb-2">
            <div class="row" style="font-size: .8rem; margin-top: 40px;">
                <div class="col text-center">
                    <div style="border-top: 1px dashed #777; width: 150px; margin: 0 auto 5px auto;"></div>
                    Cashier Signature
                </div>
                <div class="col text-center">
                    <div style="border-top: 1px dashed #777; width: 150px; margin: 0 auto 5px auto;"></div>
                    Manager Signature
                </div>
            </div>
        </div>
    </div>
</div>
