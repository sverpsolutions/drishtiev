<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0 font-weight-bold text-dark"><i class="fas fa-stop-circle text-danger mr-2"></i> Cashier Shift Close</h4>
                <small class="text-muted">
                    Shift #<strong><?= $shift->id ?></strong>
                    <?php if (!empty($shift->shift_name)): ?>&middot; <strong><?= htmlspecialchars($shift->shift_name) ?></strong><?php endif; ?>
                    <?php if (!empty($shift->system_name)): ?>&middot; System: <strong><?= htmlspecialchars($shift->system_name) ?></strong><?php endif; ?>
                    &middot; Opened: <strong><?= date('d-M-Y H:i', strtotime($shift->opened_at)) ?></strong>
                </small>
            </div>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
        </div>

        <!-- Banner — bill count only, no amounts visible to cashier -->
        <div class="row mb-3">
            <div class="col-md-4 offset-md-4">
                <div class="card border-0 bg-dark text-white text-center py-3">
                    <div class="small text-muted">Total Bills This Shift</div>
                    <div class="font-weight-bold h3 mb-0"><?= $total_bills ?></div>
                </div>
            </div>
        </div>

        <div class="alert alert-info py-2 mb-3" style="font-size:.88rem;">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Blind Declaration Mode:</strong> Enter the actual cash you have counted in the till and all other payment totals. System figures will be revealed <strong>after</strong> you close the shift.
        </div>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger mb-3"><?= $this->session->flashdata('error') ?></div>
        <?php endif; ?>

        <?= form_open('shifts/shift_close', ['id' => 'shiftCloseForm']) ?>
        <div class="row">
            <!-- Left: Actual Collection inputs only — system column locked -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-dark text-white font-weight-bold py-2">
                        Declare Actual Collection
                        <span class="badge badge-warning ml-2" style="font-size:.7rem;">🔒 System totals hidden until after close</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-striped mb-0" id="reconcileTable" style="font-size:.88rem;">
                            <thead class="thead-dark text-center">
                                <tr>
                                    <th style="width:35%">Payment Mode</th>
                                    <th style="width:30%">System Collection</th>
                                    <th style="width:35%">Actual Collection (₹) <span class="text-warning">← Enter Here</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Hidden sys values — used only for form submission calc, not displayed -->
                                <input type="hidden" id="sys_cash_val"   value="<?= number_format($sys_cash,         2, '.', '') ?>">
                                <input type="hidden" id="sys_upi_val"    value="<?= number_format($sys_upi,          2, '.', '') ?>">
                                <input type="hidden" id="sys_card_val"   value="<?= number_format($sys_card,         2, '.', '') ?>">
                                <input type="hidden" id="sys_bank_val"   value="<?= number_format($sys_bank,         2, '.', '') ?>">
                                <input type="hidden" id="sys_wallet_val" value="<?= number_format($sys_wallet,       2, '.', '') ?>">
                                <input type="hidden" id="sys_gift_val"   value="<?= number_format($sys_gift_voucher, 2, '.', '') ?>">

                                <tr>
                                    <td class="align-middle font-weight-bold">💵 Cash</td>
                                    <td class="align-middle text-center text-muted"><i class="fas fa-lock"></i> Hidden</td>
                                    <td class="align-middle p-1">
                                        <input type="number" step="0.01" min="0" name="actual_cash" id="act_cash"
                                               class="form-control form-control-sm text-right font-weight-bold border-primary"
                                               value="0.00" oninput="onActualChange()">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle font-weight-bold">⚡ UPI</td>
                                    <td class="align-middle text-center text-muted"><i class="fas fa-lock"></i> Hidden</td>
                                    <td class="align-middle p-1">
                                        <input type="number" step="0.01" min="0" name="actual_upi" id="act_upi"
                                               class="form-control form-control-sm text-right font-weight-bold border-primary"
                                               value="0.00" oninput="onActualChange()">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle font-weight-bold">💳 Card</td>
                                    <td class="align-middle text-center text-muted"><i class="fas fa-lock"></i> Hidden</td>
                                    <td class="align-middle p-1">
                                        <input type="number" step="0.01" min="0" name="actual_card" id="act_card"
                                               class="form-control form-control-sm text-right font-weight-bold border-primary"
                                               value="0.00" oninput="onActualChange()">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle font-weight-bold">🏛️ Bank / NEFT</td>
                                    <td class="align-middle text-center text-muted"><i class="fas fa-lock"></i> Hidden</td>
                                    <td class="align-middle p-1">
                                        <input type="number" step="0.01" min="0" name="actual_bank" id="act_bank"
                                               class="form-control form-control-sm text-right font-weight-bold border-primary"
                                               value="0.00" oninput="onActualChange()">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle font-weight-bold">📱 Wallet</td>
                                    <td class="align-middle text-center text-muted"><i class="fas fa-lock"></i> Hidden</td>
                                    <td class="align-middle p-1">
                                        <input type="number" step="0.01" min="0" name="actual_wallet" id="act_wallet"
                                               class="form-control form-control-sm text-right font-weight-bold border-primary"
                                               value="0.00" oninput="onActualChange()">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle font-weight-bold">🎁 Gift Voucher</td>
                                    <td class="align-middle text-center text-muted"><i class="fas fa-lock"></i> Hidden</td>
                                    <td class="align-middle p-1">
                                        <input type="number" step="0.01" min="0" name="actual_gift_voucher" id="act_gift"
                                               class="form-control form-control-sm text-right font-weight-bold border-primary"
                                               value="0.00" oninput="onActualChange()">
                                    </td>
                                </tr>
                                <tr class="bg-light text-muted">
                                    <td class="align-middle font-weight-bold">📄 Credit Sales (Bills Due)</td>
                                    <td class="align-middle text-center text-muted"><i class="fas fa-lock"></i> Hidden</td>
                                    <td class="align-middle text-center small text-muted">Not required</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-dark text-white font-weight-bold">
                                    <td>Total Declared</td>
                                    <td class="text-center">—</td>
                                    <td class="text-right pr-3" id="act_total_td">₹ 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Cash Denomination Entry -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-warning text-dark py-2 font-weight-bold d-flex justify-content-between align-items-center">
                        <span>💵 Cash Denomination Count</span>
                        <span class="font-weight-bold" id="denomTotal">Total: ₹ 0.00</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm mb-0" style="font-size:1rem;">
                            <thead class="thead-dark text-center">
                                <tr>
                                    <th style="width:30%">Denomination</th>
                                    <th style="width:35%">No. of Notes / Coins</th>
                                    <th style="width:35%">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $denoms = [
                                    500 => '₹ 500', 200 => '₹ 200', 100 => '₹ 100',
                                    50  => '₹ 50',  20  => '₹ 20',  10  => '₹ 10',
                                    5   => '₹ 5',   2   => '₹ 2',   1   => '₹ 1',
                                ];
                                foreach ($denoms as $val => $label): ?>
                                <tr>
                                    <td class="align-middle text-center font-weight-bold" style="background:#fffdf0;"><?= $label ?></td>
                                    <td class="align-middle p-1">
                                        <input type="number" min="0" class="form-control form-control-sm text-center font-weight-bold"
                                               name="denom_count_<?= $val ?>" id="dc_<?= $val ?>"
                                               value="0" data-val="<?= $val ?>" oninput="calcDenom()">
                                    </td>
                                    <td class="align-middle text-right font-weight-bold pr-3 text-dark" id="ds_<?= $val ?>">₹ 0</td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td class="align-middle text-center font-weight-bold" style="background:#fffdf0;">Coins (₹)</td>
                                    <td class="align-middle p-1">
                                        <input type="number" min="0" step="0.01" class="form-control form-control-sm text-center font-weight-bold"
                                               name="denom_coins" id="dc_coins" value="0" oninput="calcDenom()">
                                    </td>
                                    <td class="align-middle text-right font-weight-bold pr-3 text-dark" id="ds_coins">₹ 0</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-dark text-white font-weight-bold">
                                    <td class="text-center py-2">TOTAL</td>
                                    <td></td>
                                    <td class="text-right pr-3 py-2" id="denomTotalRow">₹ 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="px-3 py-2 small text-muted">
                            💡 Denomination total auto-fills <strong>Actual Cash</strong> field above.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right panel -->
            <div class="col-md-4">
                <!-- Opening cash only — no expected, no comparison -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-dark text-white py-2 font-weight-bold">Cash in Till</div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0" style="font-size:.85rem;">
                            <tr>
                                <td class="text-muted">Opening Cash (Float)</td>
                                <td class="text-right font-weight-bold">₹<?= number_format($shift->opening_cash, 2) ?></td>
                            </tr>
                            <tr class="border-top">
                                <td class="font-weight-bold">Declared Cash (Counted)</td>
                                <td class="text-right font-weight-bold text-primary" id="summary_declared">₹ 0.00</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Declared summary — no system amounts shown -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header py-2 font-weight-bold" style="background:#e8f4fd;">Your Declared Totals</div>
                    <div class="card-body py-2" style="font-size:.83rem;">
                        <div class="d-flex justify-content-between mb-1"><span>💵 Cash</span><span class="font-weight-bold" id="disp_cash">₹ 0.00</span></div>
                        <div class="d-flex justify-content-between mb-1"><span>⚡ UPI</span><span class="font-weight-bold" id="disp_upi">₹ 0.00</span></div>
                        <div class="d-flex justify-content-between mb-1"><span>💳 Card</span><span class="font-weight-bold" id="disp_card">₹ 0.00</span></div>
                        <div class="d-flex justify-content-between mb-1"><span>🏛️ Bank</span><span class="font-weight-bold" id="disp_bank">₹ 0.00</span></div>
                        <div class="d-flex justify-content-between mb-1"><span>📱 Wallet</span><span class="font-weight-bold" id="disp_wallet">₹ 0.00</span></div>
                        <div class="d-flex justify-content-between mb-1"><span>🎁 Gift Voucher</span><span class="font-weight-bold" id="disp_gift">₹ 0.00</span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between font-weight-bold">
                            <span>Total Declared</span>
                            <span class="text-primary" id="disp_total">₹ 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Closing Remarks -->
                <div class="form-group mb-3">
                    <label class="font-weight-bold small">Closing Remarks <span class="text-muted font-weight-normal">(optional)</span></label>
                    <textarea name="close_remarks" class="form-control" rows="2"
                              placeholder="Any notes for this shift..."></textarea>
                </div>

                <div class="alert alert-warning py-2 small mb-3">
                    ⚠️ Once you click <strong>Calculate & Close</strong>, system totals will be revealed and the shift will be permanently closed.
                </div>

                <button type="button" class="btn btn-danger btn-block btn-lg font-weight-bold shadow-sm" onclick="openConfirmModal()">
                    <i class="fas fa-calculator mr-1"></i> Calculate &amp; Close Shift
                </button>
            </div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmCloseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle mr-2"></i>Confirm Shift Close</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">You are about to close this shift. The following amounts you declared will be locked and compared against system totals.</p>
                <table class="table table-sm table-bordered mb-0" style="font-size:.88rem;">
                    <thead class="thead-light">
                        <tr><th>Payment Mode</th><th class="text-right">You Declared</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>💵 Cash</td><td class="text-right font-weight-bold" id="cfm_cash">₹ 0.00</td></tr>
                        <tr><td>⚡ UPI</td><td class="text-right font-weight-bold" id="cfm_upi">₹ 0.00</td></tr>
                        <tr><td>💳 Card</td><td class="text-right font-weight-bold" id="cfm_card">₹ 0.00</td></tr>
                        <tr><td>🏛️ Bank / NEFT</td><td class="text-right font-weight-bold" id="cfm_bank">₹ 0.00</td></tr>
                        <tr><td>📱 Wallet</td><td class="text-right font-weight-bold" id="cfm_wallet">₹ 0.00</td></tr>
                        <tr><td>🎁 Gift Voucher</td><td class="text-right font-weight-bold" id="cfm_gift">₹ 0.00</td></tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-dark text-white font-weight-bold">
                            <td>Total Declared</td>
                            <td class="text-right" id="cfm_total">₹ 0.00</td>
                        </tr>
                    </tfoot>
                </table>
                <div class="alert alert-danger mt-3 mb-0 py-2 small font-weight-bold text-center">
                    ⚠️ This action is <u>irreversible</u>. System totals will be revealed after closing.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-arrow-left mr-1"></i> Go Back &amp; Recount
                </button>
                <button type="button" class="btn btn-danger font-weight-bold" onclick="submitClose()">
                    <i class="fas fa-lock mr-1"></i> Yes, Close My Shift
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function pf(val) { return parseFloat(val) || 0; }

function fmt(n) {
    return '₹ ' + n.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function onActualChange() {
    const cash   = pf(document.getElementById('act_cash')?.value);
    const upi    = pf(document.getElementById('act_upi')?.value);
    const card   = pf(document.getElementById('act_card')?.value);
    const bank   = pf(document.getElementById('act_bank')?.value);
    const wallet = pf(document.getElementById('act_wallet')?.value);
    const gift   = pf(document.getElementById('act_gift')?.value);
    const total  = cash + upi + card + bank + wallet + gift;

    document.getElementById('summary_declared').textContent = fmt(cash);
    document.getElementById('disp_cash').textContent   = fmt(cash);
    document.getElementById('disp_upi').textContent    = fmt(upi);
    document.getElementById('disp_card').textContent   = fmt(card);
    document.getElementById('disp_bank').textContent   = fmt(bank);
    document.getElementById('disp_wallet').textContent = fmt(wallet);
    document.getElementById('disp_gift').textContent   = fmt(gift);
    document.getElementById('disp_total').textContent  = fmt(total);
    document.getElementById('act_total_td').textContent = fmt(total);
}

function calcDenom() {
    const vals = [500, 200, 100, 50, 20, 10, 5, 2, 1];
    let total = 0;
    vals.forEach(v => {
        const inp = document.getElementById('dc_' + v);
        if (!inp) return;
        const count = pf(inp.value);
        const sub = count * v;
        total += sub;
        const ds = document.getElementById('ds_' + v);
        if (ds) ds.textContent = '₹ ' + sub.toLocaleString('en-IN');
    });
    const coins = pf(document.getElementById('dc_coins')?.value);
    total += coins;
    const dsCoins = document.getElementById('ds_coins');
    if (dsCoins) dsCoins.textContent = '₹ ' + coins.toFixed(2);
    const fmtStr = fmt(total);
    document.getElementById('denomTotal').textContent = 'Total: ' + fmtStr;
    const dr = document.getElementById('denomTotalRow');
    if (dr) dr.textContent = fmtStr;
    const actCash = document.getElementById('act_cash');
    if (actCash) { actCash.value = total.toFixed(2); onActualChange(); }
}

function openConfirmModal() {
    const cash   = pf(document.getElementById('act_cash')?.value);
    const upi    = pf(document.getElementById('act_upi')?.value);
    const card   = pf(document.getElementById('act_card')?.value);
    const bank   = pf(document.getElementById('act_bank')?.value);
    const wallet = pf(document.getElementById('act_wallet')?.value);
    const gift   = pf(document.getElementById('act_gift')?.value);
    const total  = cash + upi + card + bank + wallet + gift;

    document.getElementById('cfm_cash').textContent   = fmt(cash);
    document.getElementById('cfm_upi').textContent    = fmt(upi);
    document.getElementById('cfm_card').textContent   = fmt(card);
    document.getElementById('cfm_bank').textContent   = fmt(bank);
    document.getElementById('cfm_wallet').textContent = fmt(wallet);
    document.getElementById('cfm_gift').textContent   = fmt(gift);
    document.getElementById('cfm_total').textContent  = fmt(total);

    $('#confirmCloseModal').modal('show');
}

function submitClose() {
    $('#confirmCloseModal').modal('hide');
    document.getElementById('shiftCloseForm').submit();
}

document.addEventListener('DOMContentLoaded', onActualChange);
</script>
