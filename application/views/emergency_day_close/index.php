<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 font-weight-bold text-danger"><i class="fas fa-exclamation-triangle mr-2"></i> Emergency Day Management</h4>
                <small class="text-muted">Business date: <strong><?= date('d-M-Y', strtotime($open_day->business_date)) ?></strong> — Super Admin recovery process</small>
            </div>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
        </div>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
        <?php endif; ?>

        <div class="card border-danger mb-4 shadow-sm">
            <div class="card-header bg-danger text-white py-2 font-weight-bold">
                ⚠️ Read before proceeding
            </div>
            <div class="card-body">
                <ul class="mb-0 pl-4">
                    <li>Use Emergency Day Close <strong>only</strong> if the system cannot be recovered normally (crashed terminal, dead machine, etc).</li>
                    <li>Any active counters below will be <strong>forcefully closed</strong> using system-calculated totals — no cashier will get to declare their own cash count for those shifts.</li>
                    <li>Unsaved work on a crashed terminal may be lost.</li>
                    <li>This action <strong>cannot be undone</strong>.</li>
                    <li>Every emergency close is permanently logged with your name, IP, reason, and the counters affected.</li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-dark text-white py-2 font-weight-bold d-flex justify-content-between">
                        <span>Billing Counters</span>
                        <span><?= $closed_counters ?>/<?= $total_counters ?> closed</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($shifts)): ?>
                            <div class="text-center py-4 text-muted small">No shifts recorded for this business date.</div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size:.8rem;">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Cashier</th>
                                        <th>Machine</th>
                                        <th>Login</th>
                                        <th class="text-right">Bills</th>
                                        <th>Last Bill</th>
                                        <th class="text-right">Cash</th>
                                        <th class="text-right">UPI</th>
                                        <th class="text-right">Card</th>
                                        <th class="text-right">Credit</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($shifts as $s): $sys = $s->sys_totals; $is_open = $s->status === 'open'; ?>
                                    <tr class="<?= $is_open ? 'table-danger' : '' ?>">
                                        <td class="align-middle font-weight-bold"><?= htmlspecialchars($s->cashier_name) ?></td>
                                        <td class="align-middle"><?= htmlspecialchars($s->system_name ?: '—') ?><?= $s->terminal_no ? ' / '.htmlspecialchars($s->terminal_no) : '' ?></td>
                                        <td class="align-middle"><?= date('H:i', strtotime($s->opened_at)) ?></td>
                                        <td class="align-middle text-right"><?= $sys['bills'] ?></td>
                                        <td class="align-middle"><?= $sys['last_bill_no'] ? htmlspecialchars($sys['last_bill_no']) . ' @ ' . date('H:i', strtotime($sys['last_bill_time'])) : '—' ?></td>
                                        <td class="align-middle text-right">₹<?= number_format($sys['cash'], 2) ?></td>
                                        <td class="align-middle text-right">₹<?= number_format($sys['upi'], 2) ?></td>
                                        <td class="align-middle text-right">₹<?= number_format($sys['card'], 2) ?></td>
                                        <td class="align-middle text-right">₹<?= number_format($sys['credit'], 2) ?></td>
                                        <td class="align-middle">
                                            <span class="badge badge-<?= $is_open ? 'danger' : 'success' ?> font-weight-bold">
                                                <?= $is_open ? 'ACTIVE' : 'CLOSED' ?>
                                            </span>
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

            <div class="col-md-5">
                <div class="card border-danger shadow-sm mb-4">
                    <div class="card-header bg-danger text-white py-2 font-weight-bold">Emergency Close Confirmation</div>
                    <div class="card-body">
                        <?= form_open('emergency_day_close/execute', ['id' => 'emergencyForm']) ?>
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="machine_name" id="machineNameField" value="">

                            <div class="form-group">
                                <label class="font-weight-bold small">Reason <span class="text-danger">*</span></label>
                                <select name="reason" id="reasonSelect" class="form-control form-control-sm" required>
                                    <option value="">-- Select Reason --</option>
                                    <?php foreach ($reasons as $r): ?>
                                    <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold small">Remarks <span id="remarksRequiredHint" class="text-danger" style="display:none;">(required for "Other")</span></label>
                                <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Additional detail (optional unless reason is Other)"></textarea>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold small text-danger">Type <code>EMERGENCY CLOSE</code> to confirm <span class="text-danger">*</span></label>
                                <input type="text" name="confirm_phrase" id="confirmPhrase" class="form-control form-control-sm" autocomplete="off" placeholder="Type here exactly">
                            </div>

                            <button type="submit" id="executeBtn" class="btn btn-danger btn-block font-weight-bold" disabled>
                                <i class="fas fa-power-off mr-1"></i> Execute Emergency Day Close
                            </button>
                            <a href="<?= base_url('emergency_day_close/history') ?>" class="btn btn-outline-secondary btn-block btn-sm mt-2">
                                <i class="fas fa-history mr-1"></i> View History
                            </a>
                        <?= form_close() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var phraseInput = document.getElementById('confirmPhrase');
    var execBtn     = document.getElementById('executeBtn');
    var reasonSelect= document.getElementById('reasonSelect');
    var hint        = document.getElementById('remarksRequiredHint');
    var machineField= document.getElementById('machineNameField');
    var form        = document.getElementById('emergencyForm');

    machineField.value = navigator.platform + ' — ' + navigator.userAgent.substring(0, 150);

    function checkPhrase() {
        execBtn.disabled = phraseInput.value !== 'EMERGENCY CLOSE';
    }
    phraseInput.addEventListener('input', checkPhrase);

    reasonSelect.addEventListener('change', function() {
        hint.style.display = (this.value === 'Other') ? 'inline' : 'none';
    });

    form.addEventListener('submit', function(e) {
        if (phraseInput.value !== 'EMERGENCY CLOSE') {
            e.preventDefault();
            return;
        }
        if (!confirm('This will forcefully close all active counters and permanently close this business day. This cannot be undone. Continue?')) {
            e.preventDefault();
        }
    });
})();
</script>
