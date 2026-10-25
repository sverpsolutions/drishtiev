<?php // FILE: application/views/cash_management/index.php ?>

<style>
.cm-card{border:0;border-radius:.5rem;box-shadow:0 1px 4px rgba(0,0,0,.08);}
.cm-card .card-body{padding:.8rem 1rem;}
.cm-card .cm-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#888;margin-bottom:2px;}
.cm-card .cm-value{font-size:1.25rem;font-weight:700;}
.cm-card.cm-green .cm-value{color:#28a745;}
.cm-card.cm-red .cm-value{color:#dc3545;}
.cm-card.cm-blue .cm-value{color:#007bff;}
.cm-card.cm-orange .cm-value{color:#e67e22;}
.cm-card.cm-purple .cm-value{color:#6f42c1;}
.ledger-table td,.ledger-table th{font-size:.82rem;padding:.4rem .5rem;vertical-align:middle;}
.ledger-in{color:#28a745;font-weight:600;}
.ledger-out{color:#dc3545;font-weight:600;}
.badge-payin{background:#d4edda;color:#155724;}
.badge-payout{background:#f8d7da;color:#721c24;}
.badge-handover{background:#fff3cd;color:#856404;}
.badge-opening{background:#d1ecf1;color:#0c5460;}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-cash-register mr-1"></i> Cash Management</h5>
        <small class="text-muted">Daily cash ledger &amp; handover</small>
    </div>
    <div class="d-flex align-items-center">
        <a href="<?= base_url('cashManagement/categories') ?>" class="btn btn-sm btn-outline-secondary mr-2"><i class="fas fa-tags"></i> Categories</a>
        <a href="<?= base_url('cashManagement/ledger') ?>" class="btn btn-sm btn-outline-primary mr-2"><i class="fas fa-book"></i> Ledger Report</a>
        <a href="<?= base_url('cashManagement/handover?date=' . $business_date) ?>" class="btn btn-sm btn-warning"><i class="fas fa-hand-holding-usd"></i> Cash Handover</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<!-- Filters -->
<div class="card cm-card mb-3">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end">
            <div class="col-md-3">
                <label class="cm-label mb-0">Location</label>
                <select name="location_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <?php foreach ($locations as $l): ?>
                        <option value="<?= $l->id ?>" <?= $l->id == $current_loc ? 'selected' : '' ?>><?= htmlspecialchars($l->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="cm-label mb-0">Business Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= $business_date ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
                <label class="cm-label mb-0">Shift</label>
                <select name="shift_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Shifts</option>
                    <?php foreach ($shifts as $s): ?>
                        <option value="<?= $s->id ?>" <?= $shift_id == $s->id ? 'selected' : '' ?>><?= htmlspecialchars($s->shift_name) ?> — <?= htmlspecialchars($s->cashier_name ?? 'Unknown') ?> (<?= $s->status ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 text-right">
                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#payinModal"><i class="fas fa-plus"></i> Pay-In</button>
                <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#payoutModal"><i class="fas fa-minus"></i> Pay-Out</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-2 col-6 mb-2">
        <div class="card cm-card cm-blue">
            <div class="card-body">
                <div class="cm-label">Opening Cash</div>
                <div class="cm-value">₹ <?= number_format($summary['opening_cash'], 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-2">
        <div class="card cm-card cm-green">
            <div class="card-body">
                <div class="cm-label">Cash Sales</div>
                <div class="cm-value">₹ <?= number_format($summary['cash_sales'], 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-2">
        <div class="card cm-card cm-green">
            <div class="card-body">
                <div class="cm-label">Pay-In</div>
                <div class="cm-value">₹ <?= number_format($summary['pay_in'], 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-2">
        <div class="card cm-card cm-red">
            <div class="card-body">
                <div class="cm-label">Pay-Out</div>
                <div class="cm-value">₹ <?= number_format($summary['pay_out'], 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-2">
        <div class="card cm-card cm-orange">
            <div class="card-body">
                <div class="cm-label">Handovers</div>
                <div class="cm-value">₹ <?= number_format($summary['handovers'], 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-2">
        <div class="card cm-card cm-purple">
            <div class="card-body">
                <div class="cm-label">Expected Cash</div>
                <div class="cm-value">₹ <?= number_format($summary['expected_cash'], 2) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Sales Breakdown -->
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card cm-card">
            <div class="card-body">
                <h6 class="font-weight-bold mb-2" style="font-size:.85rem;"><i class="fas fa-chart-pie mr-1"></i> Sales Breakdown</h6>
                <table class="table table-sm table-borderless mb-0" style="font-size:.82rem;">
                    <tr><td>Total Sales</td><td class="text-right font-weight-bold">₹ <?= number_format($sales['total_sales'], 2) ?></td></tr>
                    <tr><td><span class="badge badge-success">Cash</span></td><td class="text-right">₹ <?= number_format($sales['cash_sales'], 2) ?></td></tr>
                    <tr><td><span class="badge badge-info">UPI</span></td><td class="text-right">₹ <?= number_format($sales['upi_sales'], 2) ?></td></tr>
                    <tr><td><span class="badge badge-primary">Card</span></td><td class="text-right">₹ <?= number_format($sales['card_sales'], 2) ?></td></tr>
                    <tr><td><span class="badge badge-secondary">Bank/NEFT</span></td><td class="text-right">₹ <?= number_format($sales['bank_sales'], 2) ?></td></tr>
                    <tr><td><span class="badge badge-warning text-dark">Credit</span></td><td class="text-right">₹ <?= number_format($sales['credit_sales'], 2) ?></td></tr>
                    <tr><td class="text-muted" style="font-size:.75rem;">Total Bills</td><td class="text-right text-muted" style="font-size:.75rem;"><?= $sales['total_bills'] ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card cm-card">
            <div class="card-body">
                <h6 class="font-weight-bold mb-2" style="font-size:.85rem;"><i class="fas fa-calculator mr-1"></i> Cash Formula</h6>
                <table class="table table-sm table-borderless mb-0" style="font-size:.82rem;">
                    <tr><td>Opening Cash</td><td class="text-right">₹ <?= number_format($summary['opening_cash'], 2) ?></td></tr>
                    <tr class="text-success"><td>+ Cash Sales</td><td class="text-right">+ ₹ <?= number_format($summary['cash_sales'], 2) ?></td></tr>
                    <?php if ($summary['cash_received'] > 0): ?>
                    <tr class="text-success"><td>+ Cash Received (payments)</td><td class="text-right">+ ₹ <?= number_format($summary['cash_received'], 2) ?></td></tr>
                    <?php endif; ?>
                    <tr class="text-success"><td>+ Pay-In</td><td class="text-right">+ ₹ <?= number_format($summary['pay_in'], 2) ?></td></tr>
                    <tr class="text-danger"><td>- Cash Refunds</td><td class="text-right">- ₹ <?= number_format($summary['cash_refunds'], 2) ?></td></tr>
                    <tr class="text-danger"><td>- Pay-Out</td><td class="text-right">- ₹ <?= number_format($summary['pay_out'], 2) ?></td></tr>
                    <tr style="border-top:2px solid #333;"><td class="font-weight-bold">= Expected Cash</td><td class="text-right font-weight-bold">₹ <?= number_format($summary['expected_cash'], 2) ?></td></tr>
                    <?php if ($summary['handovers'] > 0): ?>
                    <tr class="text-warning"><td>- Handovers</td><td class="text-right">- ₹ <?= number_format($summary['handovers'], 2) ?></td></tr>
                    <tr><td class="font-weight-bold">= Carry Forward</td><td class="text-right font-weight-bold">₹ <?= number_format($summary['carry_forward'], 2) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Cash Ledger -->
<div class="card cm-card">
    <div class="card-body">
        <h6 class="font-weight-bold mb-2" style="font-size:.85rem;"><i class="fas fa-list mr-1"></i> Cash Ledger — <?= date('d M Y', strtotime($business_date)) ?></h6>
        <div class="table-responsive">
            <table class="table table-hover table-sm ledger-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-right">In (+)</th>
                        <th class="text-right">Out (-)</th>
                        <th class="text-right">Balance</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $balance = 0;
                    if (!empty($ledger)):
                    foreach ($ledger as $entry):
                        $balance += $entry->in_amount - $entry->out_amount;
                        $type_badge = 'badge-secondary';
                        if ($entry->type === 'opening') $type_badge = 'badge-opening';
                        elseif ($entry->type === 'pay_in') $type_badge = 'badge-payin';
                        elseif ($entry->type === 'pay_out') $type_badge = 'badge-payout';
                        elseif ($entry->type === 'handover') $type_badge = 'badge-handover';
                ?>
                    <tr>
                        <td><?= date('h:i A', strtotime($entry->time)) ?></td>
                        <td><span class="badge <?= $type_badge ?>"><?= $entry->label ?></span></td>
                        <td><?= htmlspecialchars($entry->description ?? '') ?></td>
                        <td class="text-right"><?= $entry->in_amount > 0 ? '<span class="ledger-in">+ ₹ ' . number_format($entry->in_amount, 2) . '</span>' : '—' ?></td>
                        <td class="text-right"><?= $entry->out_amount > 0 ? '<span class="ledger-out">- ₹ ' . number_format($entry->out_amount, 2) . '</span>' : '—' ?></td>
                        <td class="text-right font-weight-bold">₹ <?= number_format($balance, 2) ?></td>
                        <td class="text-center">
                            <?php if (isset($entry->id) && in_array($entry->type, ['pay_in','pay_out'])): ?>
                                <button class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size:.7rem;" onclick="voidTxn(<?= $entry->id ?>, '<?= addslashes($entry->label) ?>')">Void</button>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">No cash entries for this date.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pay-In Modal -->
<div class="modal fade" id="payinModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2 bg-success text-white">
                <h6 class="modal-title font-weight-bold mb-0"><i class="fas fa-plus-circle mr-1"></i> Cash Pay-In</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <?= form_open('cashManagement/save_payin') ?>
            <input type="hidden" name="location_id" value="<?= $current_loc ?>">
            <input type="hidden" name="business_date" value="<?= $business_date ?>">
            <input type="hidden" name="shift_id" value="<?= $shift_id ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label style="font-size:.85rem;">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-control form-control-sm" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($payin_cats as $c): ?>
                            <option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required autofocus>
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Description / Remarks</label>
                    <input type="text" name="description" class="form-control form-control-sm" placeholder="e.g. Cash received from Manager">
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Reference No.</label>
                    <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="Optional">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-save mr-1"></i> Save Pay-In</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<!-- Pay-Out Modal -->
<div class="modal fade" id="payoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2 bg-danger text-white">
                <h6 class="modal-title font-weight-bold mb-0"><i class="fas fa-minus-circle mr-1"></i> Cash Pay-Out</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <?= form_open('cashManagement/save_payout') ?>
            <input type="hidden" name="location_id" value="<?= $current_loc ?>">
            <input type="hidden" name="business_date" value="<?= $business_date ?>">
            <input type="hidden" name="shift_id" value="<?= $shift_id ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label style="font-size:.85rem;">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-control form-control-sm" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($payout_cats as $c): ?>
                            <option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Paid To</label>
                    <input type="text" name="paid_to" class="form-control form-control-sm" placeholder="e.g. Tea vendor">
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Description / Remarks</label>
                    <input type="text" name="description" class="form-control form-control-sm" placeholder="e.g. Staff evening tea">
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Reference No.</label>
                    <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="Optional">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-save mr-1"></i> Save Pay-Out</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<!-- Void Modal -->
<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title font-weight-bold mb-0">Void Transaction</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <?= form_open('cashManagement/void_transaction') ?>
            <input type="hidden" name="txn_id" id="void_txn_id">
            <div class="modal-body">
                <p id="void_txn_label" class="font-weight-bold mb-2"></p>
                <div class="form-group">
                    <label style="font-size:.85rem;">Reason for voiding <span class="text-danger">*</span></label>
                    <input type="text" name="void_reason" class="form-control form-control-sm" required>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="submit" class="btn btn-sm btn-danger">Void</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
function voidTxn(id, label) {
    document.getElementById('void_txn_id').value = id;
    document.getElementById('void_txn_label').textContent = label;
    $('#voidModal').modal('show');
}
</script>
