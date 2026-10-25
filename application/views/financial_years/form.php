<?php // FILE: application/views/financial_years/form.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-calendar-plus mr-2"></i>Add Financial Year</h5>
        <small class="text-muted"><a href="<?= base_url('financial_years') ?>">Financial Years</a> / New</small>
    </div>
    <a href="<?= base_url('financial_years') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

<div class="row">
<div class="col-md-6">
<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="<?= base_url('financial_years/add') ?>">
            <div class="form-group">
                <label class="font-weight-bold small">Label <span class="text-danger">*</span></label>
                <input type="text" name="label" class="form-control" required maxlength="8" pattern="\d{2}-\d{2}"
                       value="<?= set_value('label') ?>" placeholder="e.g. 25-26">
                <small class="form-text text-muted">Two-digit start year, dash, two-digit end year — e.g. "25-26" for Apr 2025–Mar 2026.</small>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Start Date <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" required value="<?= set_value('start_date') ?>">
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">End Date <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control" required value="<?= set_value('end_date') ?>">
            </div>
            <div class="alert alert-info small mb-3">
                <i class="fas fa-info-circle mr-1"></i> Most years never need to be added manually — one is created automatically (status "open") the first time a document is dated into it. Use this only to pre-create a year or to set custom start/end dates.
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create Financial Year</button>
        </form>
    </div>
</div>
</div>
</div>
