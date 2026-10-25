<div class="row justify-content-center mt-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-play mr-2"></i> Open Cashier Shift Session</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
                <?php endif; ?>

                <?= form_open('shifts/shift_open') ?>

                <!-- Read-only info -->
                <div class="form-row mb-3">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Business Date</label>
                        <input type="text" class="form-control bg-light font-weight-bold" value="<?= date('d-M-Y', strtotime($business_date)) ?>" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Cashier Name</label>
                        <input type="text" class="form-control bg-light font-weight-bold" value="<?= htmlspecialchars($cashier_name) ?>" readonly>
                    </div>
                </div>

                <!-- Shift Name -->
                <div class="form-group mb-3">
                    <label class="font-weight-bold small">Shift <span class="text-danger">*</span></label>
                    <select name="shift_name" class="form-control font-weight-bold" required>
                        <option value="General">General</option>
                        <option value="Morning">Morning</option>
                        <option value="Afternoon">Afternoon</option>
                        <option value="Evening">Evening</option>
                        <option value="Night">Night</option>
                    </select>
                </div>

                <!-- System / Terminal -->
                <div class="form-row mb-3">
                    <div class="form-group col-md-7">
                        <label class="font-weight-bold small">System / Counter Name <span class="text-danger">*</span></label>
                        <input type="text" name="system_name" class="form-control font-weight-bold"
                               placeholder="e.g. Counter 1, Billing PC, Main POS"
                               value="<?= $system_name_prefill ?? '' ?>" required>
                    </div>
                    <div class="form-group col-md-5">
                        <label class="font-weight-bold small">Terminal No.</label>
                        <input type="text" name="terminal_no" class="form-control"
                               placeholder="e.g. T01, POS-2"
                               value="<?= $terminal_prefill ?? '' ?>">
                    </div>
                </div>

                <!-- Opening Cash -->
                <div class="form-group mb-3">
                    <label class="font-weight-bold small">Opening Cash Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="opening_cash"
                           class="form-control form-control-lg font-weight-bold text-right"
                           value="<?= number_format($suggested_opening ?? 0, 2, '.', '') ?>" required autofocus style="font-size:1.3rem;">
                    <?php if (!empty($suggested_opening) && $suggested_opening > 0): ?>
                    <small class="text-success font-weight-bold"><i class="fas fa-info-circle mr-1"></i>Auto-filled from last shift closing (Next Day Opening)</small>
                    <?php else: ?>
                    <small class="text-muted">Enter the cash float in the drawer before billing starts.</small>
                    <?php endif; ?>
                </div>

                <!-- Remarks -->
                <div class="form-group mb-4">
                    <label class="font-weight-bold small">Remarks <span class="text-muted font-weight-normal">(optional)</span></label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Any notes for this shift..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block font-weight-bold shadow-sm py-2">
                    <i class="fas fa-sign-in-alt mr-1"></i> Start Shift Session
                </button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>
