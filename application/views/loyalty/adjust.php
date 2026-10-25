<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-sliders-h text-primary mr-1"></i> Manual Point Adjustment</h5>
    <a href="<?= base_url('loyalty') ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
</div>

<div class="row"><div class="col-md-6">
<div class="card border-0 shadow-sm"><div class="card-body">
    <div class="alert alert-warning py-2 px-3" style="font-size:.85rem;">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Manual adjustments change a customer's balance directly. Every one is written to the
        ledger and the audit log with your name and the reason you give.
    </div>
    <?= form_open('loyalty/adjust') ?>
    <div class="form-group">
        <label class="font-weight-bold">Customer <span class="text-danger">*</span></label>
        <input type="text" name="customer" class="form-control" required autofocus
               placeholder="Mobile number, loyalty card number or customer ID">
    </div>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label class="font-weight-bold">Direction</label>
            <select name="direction" class="form-control">
                <option value="credit">Add points (credit)</option>
                <option value="debit">Deduct points (debit)</option>
            </select>
        </div>
        <div class="form-group col-md-6">
            <label class="font-weight-bold">Points <span class="text-danger">*</span></label>
            <input type="number" name="points" class="form-control" min="1" step="1" required placeholder="e.g. 100">
        </div>
    </div>
    <div class="form-group">
        <label class="font-weight-bold">Reason <span class="text-danger">*</span></label>
        <input type="text" name="reason" class="form-control" required maxlength="255"
               placeholder="e.g. Goodwill credit for delayed delivery">
        <small class="text-muted">Shown on the customer statement and the adjustment report.</small>
    </div>
    <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Apply Adjustment</button>
    <?= form_close() ?>
</div></div>
</div></div>
