<?php // FILE: application/views/cash_management/ledger.php ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-book mr-1"></i> Cash Ledger Report</h5>
        <small class="text-muted">Pay-In / Pay-Out / Handover history</small>
    </div>
    <a href="<?= base_url('cashManagement') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end">
            <div class="col-md-2">
                <label style="font-size:.75rem;" class="mb-0">Location</label>
                <select name="location_id" class="form-control form-control-sm">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $l): ?>
                        <option value="<?= $l->id ?>" <?= $current_loc == $l->id ? 'selected' : '' ?>><?= htmlspecialchars($l->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label style="font-size:.75rem;" class="mb-0">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= $from_date ?>">
            </div>
            <div class="col-md-2">
                <label style="font-size:.75rem;" class="mb-0">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= $to_date ?>">
            </div>
            <div class="col-md-2">
                <label style="font-size:.75rem;" class="mb-0">Type</label>
                <select name="type" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option value="pay_in" <?= $filter_type === 'pay_in' ? 'selected' : '' ?>>Pay-In</option>
                    <option value="pay_out" <?= $filter_type === 'pay_out' ? 'selected' : '' ?>>Pay-Out</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Transactions -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2">
        <h6 class="mb-0 font-weight-bold" style="font-size:.85rem;">Pay-In / Pay-Out Transactions</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Txn No</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Paid To</th>
                        <th>Location</th>
                        <th>User</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $total_in = 0; $total_out = 0;
                if (!empty($transactions)): foreach ($transactions as $t):
                    if ($t->type === 'pay_in') $total_in += $t->amount; else $total_out += $t->amount;
                ?>
                    <tr <?= $t->voided ? 'class="text-muted" style="text-decoration:line-through;"' : '' ?>>
                        <td><code><?= $t->txn_no ?></code></td>
                        <td><?= date('d M Y', strtotime($t->business_date)) ?></td>
                        <td>
                            <?php if ($t->type === 'pay_in'): ?>
                                <span class="badge badge-success">Pay-In</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Pay-Out</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($t->category_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($t->description ?? '—') ?></td>
                        <td><?= htmlspecialchars($t->paid_to ?? '—') ?></td>
                        <td><?= htmlspecialchars($t->location_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($t->user_name ?? '—') ?></td>
                        <td class="text-right font-weight-bold <?= $t->type === 'pay_in' ? 'text-success' : 'text-danger' ?>">
                            <?= $t->type === 'pay_in' ? '+' : '-' ?> ₹ <?= number_format($t->amount, 2) ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="9" class="text-center text-muted py-3">No transactions found.</td></tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($transactions)): ?>
                <tfoot class="font-weight-bold" style="font-size:.85rem;">
                    <tr class="bg-light">
                        <td colspan="8" class="text-right">Total Pay-In:</td>
                        <td class="text-right text-success">+ ₹ <?= number_format($total_in, 2) ?></td>
                    </tr>
                    <tr class="bg-light">
                        <td colspan="8" class="text-right">Total Pay-Out:</td>
                        <td class="text-right text-danger">- ₹ <?= number_format($total_out, 2) ?></td>
                    </tr>
                    <tr class="bg-light">
                        <td colspan="8" class="text-right">Net:</td>
                        <td class="text-right <?= ($total_in - $total_out) >= 0 ? 'text-success' : 'text-danger' ?>">₹ <?= number_format($total_in - $total_out, 2) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Handovers -->
<div class="card border-0 shadow-sm">
    <div class="card-header py-2">
        <h6 class="mb-0 font-weight-bold" style="font-size:.85rem;">Cash Handovers</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Handover No</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>From</th>
                        <th>To</th>
                        <th class="text-right">Expected</th>
                        <th class="text-right">Actual</th>
                        <th class="text-right">Handed Over</th>
                        <th class="text-right">Remaining</th>
                        <th>Short/Excess</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $total_ho = 0;
                if (!empty($handovers)): foreach ($handovers as $h):
                    $total_ho += $h->handover_amount;
                ?>
                    <tr>
                        <td><code><?= $h->handover_no ?></code></td>
                        <td><?= date('d M Y', strtotime($h->business_date)) ?></td>
                        <td><?= htmlspecialchars($h->location_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($h->from_user_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($h->to_user_name ?? '—') ?></td>
                        <td class="text-right">₹ <?= number_format($h->expected_cash, 2) ?></td>
                        <td class="text-right">₹ <?= number_format($h->actual_cash, 2) ?></td>
                        <td class="text-right font-weight-bold">₹ <?= number_format($h->handover_amount, 2) ?></td>
                        <td class="text-right">₹ <?= number_format($h->remaining_cash, 2) ?></td>
                        <td>
                            <?php if ($h->short_amount > 0): ?>
                                <span class="badge badge-danger">Short ₹<?= number_format($h->short_amount, 2) ?></span>
                            <?php elseif ($h->excess_amount > 0): ?>
                                <span class="badge badge-warning">Excess ₹<?= number_format($h->excess_amount, 2) ?></span>
                            <?php else: ?>
                                <span class="badge badge-success">OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-3">No handovers found.</td></tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($handovers)): ?>
                <tfoot class="font-weight-bold bg-light" style="font-size:.85rem;">
                    <tr><td colspan="7" class="text-right">Total Handed Over:</td><td class="text-right">₹ <?= number_format($total_ho, 2) ?></td><td colspan="2"></td></tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
