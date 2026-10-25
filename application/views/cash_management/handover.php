<?php // FILE: application/views/cash_management/handover.php ?>

<style>
.ho-card{border:0;border-radius:.5rem;box-shadow:0 1px 4px rgba(0,0,0,.08);}
.ho-card .card-body{padding:1rem 1.2rem;}
.ho-label{font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;color:#888;margin-bottom:2px;}
.ho-val{font-size:1.4rem;font-weight:700;}
.diff-short{color:#dc3545;font-weight:700;}
.diff-excess{color:#28a745;font-weight:700;}
.diff-ok{color:#28a745;font-weight:700;}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-hand-holding-usd mr-1"></i> Cash Handover</h5>
        <small class="text-muted"><?= date('d M Y', strtotime($business_date)) ?></small>
    </div>
    <a href="<?= base_url('cashManagement?date=' . $business_date) ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="row">
    <!-- Cash Summary -->
    <div class="col-md-5 mb-3">
        <div class="card ho-card">
            <div class="card-body">
                <h6 class="font-weight-bold mb-3"><i class="fas fa-calculator mr-1"></i> Cash Summary</h6>
                <table class="table table-sm table-borderless mb-0" style="font-size:.85rem;">
                    <tr><td>Opening Cash</td><td class="text-right">₹ <?= number_format($summary['opening_cash'], 2) ?></td></tr>
                    <tr class="text-success"><td>+ Cash Sales</td><td class="text-right">+ ₹ <?= number_format($summary['cash_sales'], 2) ?></td></tr>
                    <?php if ($summary['cash_received'] > 0): ?>
                    <tr class="text-success"><td>+ Cash Received</td><td class="text-right">+ ₹ <?= number_format($summary['cash_received'], 2) ?></td></tr>
                    <?php endif; ?>
                    <tr class="text-success"><td>+ Pay-In</td><td class="text-right">+ ₹ <?= number_format($summary['pay_in'], 2) ?></td></tr>
                    <tr class="text-danger"><td>- Cash Refunds</td><td class="text-right">- ₹ <?= number_format($summary['cash_refunds'], 2) ?></td></tr>
                    <tr class="text-danger"><td>- Pay-Out</td><td class="text-right">- ₹ <?= number_format($summary['pay_out'], 2) ?></td></tr>
                    <tr style="border-top:2px solid #333;"><td class="font-weight-bold" style="font-size:1rem;">Expected Cash</td><td class="text-right font-weight-bold" style="font-size:1.1rem;">₹ <?= number_format($summary['expected_cash'], 2) ?></td></tr>
                    <?php if ($summary['handovers'] > 0): ?>
                    <tr class="text-warning"><td>- Previous Handovers</td><td class="text-right">- ₹ <?= number_format($summary['handovers'], 2) ?></td></tr>
                    <tr><td class="font-weight-bold">Net Available</td><td class="text-right font-weight-bold">₹ <?= number_format($summary['carry_forward'], 2) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <?php if (!empty($handovers)): ?>
        <div class="card ho-card mt-3">
            <div class="card-body">
                <h6 class="font-weight-bold mb-2" style="font-size:.85rem;">Previous Handovers Today</h6>
                <table class="table table-sm mb-0" style="font-size:.8rem;">
                    <thead><tr><th>#</th><th>From → To</th><th class="text-right">Amount</th><th>Time</th></tr></thead>
                    <tbody>
                    <?php foreach ($handovers as $h): ?>
                        <tr>
                            <td><?= $h->handover_no ?></td>
                            <td><?= htmlspecialchars($h->from_user_name ?? '?') ?> → <?= htmlspecialchars($h->to_user_name ?? '?') ?></td>
                            <td class="text-right font-weight-bold">₹ <?= number_format($h->handover_amount, 2) ?></td>
                            <td><?= date('h:i A', strtotime($h->created_at)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Handover Form -->
    <div class="col-md-7 mb-3">
        <div class="card ho-card">
            <div class="card-body">
                <h6 class="font-weight-bold mb-3"><i class="fas fa-exchange-alt mr-1"></i> New Cash Handover</h6>
                <?= form_open('cashManagement/save_handover') ?>
                <input type="hidden" name="location_id" value="<?= $current_loc ?>">
                <input type="hidden" name="business_date" value="<?= $business_date ?>">
                <input type="hidden" name="shift_id" value="<?= $shift_id ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label style="font-size:.85rem;">Expected Cash</label>
                            <input type="text" class="form-control form-control-sm bg-light" value="₹ <?= number_format($summary['expected_cash'] - $summary['handovers'], 2) ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label style="font-size:.85rem;">Actual Physical Cash <span class="text-danger">*</span></label>
                            <input type="number" name="actual_cash" id="actual_cash" class="form-control" step="0.01" min="0" required oninput="calcDiff()">
                        </div>
                    </div>
                </div>

                <div id="diffRow" style="display:none;" class="alert py-2 mb-3">
                    <span id="diffLabel"></span>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label style="font-size:.85rem;">Handover Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="handover_amount" id="handover_amount" class="form-control" step="0.01" min="0.01" required oninput="calcRemaining()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label style="font-size:.85rem;">Handover To <span class="text-danger">*</span></label>
                            <select name="to_user_id" class="form-control form-control-sm" required>
                                <option value="">-- Select Person --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u->id ?>"><?= htmlspecialchars($u->name) ?> (<?= $u->role ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="remainingRow" style="display:none;" class="mb-3">
                    <div class="alert alert-info py-2 mb-0">
                        Remaining after handover: <strong id="remainingVal">₹ 0.00</strong>
                        <br><small class="text-muted">This amount carries forward to the next shift opening.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label style="font-size:.85rem;">Remarks</label>
                    <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Any notes about this handover..."></textarea>
                </div>

                <div id="diffRemarksGroup" style="display:none;" class="form-group">
                    <label style="font-size:.85rem;">Reason for Cash Difference <span class="text-danger">*</span></label>
                    <input type="text" name="difference_remarks" class="form-control form-control-sm" placeholder="Explain the shortage or excess">
                </div>

                <button type="submit" class="btn btn-warning btn-block"><i class="fas fa-hand-holding-usd mr-1"></i> Complete Handover</button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<script>
var expectedCash = <?= $summary['expected_cash'] - $summary['handovers'] ?>;

function calcDiff() {
    var actual = parseFloat(document.getElementById('actual_cash').value) || 0;
    var diff = actual - expectedCash;
    var row = document.getElementById('diffRow');
    var label = document.getElementById('diffLabel');
    var diffRemarks = document.getElementById('diffRemarksGroup');

    if (actual > 0) {
        row.style.display = 'block';
        if (Math.abs(diff) < 0.01) {
            row.className = 'alert alert-success py-2 mb-3';
            label.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Cash matches expected amount. <span class="diff-ok">₹ 0.00</span>';
            diffRemarks.style.display = 'none';
        } else if (diff < 0) {
            row.className = 'alert alert-danger py-2 mb-3';
            label.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Cash Short: <span class="diff-short">₹ ' + Math.abs(diff).toFixed(2) + '</span>';
            diffRemarks.style.display = 'block';
        } else {
            row.className = 'alert alert-warning py-2 mb-3';
            label.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Cash Excess: <span class="diff-excess">₹ ' + diff.toFixed(2) + '</span>';
            diffRemarks.style.display = 'block';
        }
    } else {
        row.style.display = 'none';
        diffRemarks.style.display = 'none';
    }
    calcRemaining();
}

function calcRemaining() {
    var actual = parseFloat(document.getElementById('actual_cash').value) || 0;
    var handover = parseFloat(document.getElementById('handover_amount').value) || 0;
    var remaining = actual - handover;
    var row = document.getElementById('remainingRow');
    if (handover > 0) {
        row.style.display = 'block';
        document.getElementById('remainingVal').textContent = '₹ ' + remaining.toFixed(2);
    } else {
        row.style.display = 'none';
    }
}
</script>
