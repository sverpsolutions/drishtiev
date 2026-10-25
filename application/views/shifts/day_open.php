<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-3">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-sun mr-2"></i> Start Business Day (Day Open)</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
                <?php endif; ?>

                <?php if (!empty($today_closed_day)): ?>
                <div class="alert alert-warning">
                    <strong>Today (<?= date('d-M-Y', strtotime($today_closed_day->business_date)) ?>) was already closed</strong>
                    at <?= date('h:i A', strtotime($today_closed_day->closed_at)) ?>.
                    Since it's still the same date, you can't open a new Day Open for it —
                    reopen the same business day instead.
                    <?= form_open('shifts/reopen_business_day', ['class' => 'mt-2']) ?>
                        <button type="submit" class="btn btn-warning btn-sm font-weight-bold">
                            <i class="fas fa-undo mr-1"></i> Reopen Today's Business Day
                        </button>
                    <?= form_close() ?>
                </div>
                <?php endif; ?>

                <?= form_open('shifts/day_open') ?>
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark mb-2">Select Business Date <span class="text-danger">*</span></label>
                        <input type="date" name="business_date" class="form-control form-control-lg text-center font-weight-bold" 
                               value="<?= $suggested_date ?>" required style="font-size:1.2rem; letter-spacing:1px;">
                        <small class="text-muted mt-2 d-block">
                            💡 Once opened, this date will be linked to all shift billings and settlements.
                            Only one Day Open allowed per business date.
                        </small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-2">Opening Remarks <span class="text-muted font-weight-normal">(optional)</span></label>
                        <textarea name="open_remarks" class="form-control" rows="2" placeholder="e.g. Regular business day, Public holiday, etc."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg btn-block font-weight-bold mt-4 shadow-sm py-2">
                        <i class="fas fa-play-circle mr-1"></i> Perform Day Open
                    </button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>
