<?php
// FILE: application/views/customers/add.php  (also used for edit.php)
$is_edit   = isset($customer);
$form_url  = $is_edit ? 'customers/edit/'.$customer->id : 'customers/add';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><?= $is_edit ? 'Edit Customer' : 'Add Customer' ?></h5>
        <small class="text-muted"><a href="<?= base_url('customers') ?>">Customers</a> / <?= $is_edit ? 'Edit' : 'Add' ?></small>
    </div>
    <a href="<?= base_url('customers') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if (validation_errors()): ?>
    <div class="alert alert-danger"><?= validation_errors('<div>','</div>') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?= form_open($form_url) ?>

        <div class="row">
            <div class="col-md-5">
                <div class="form-group">
                    <label>Customer Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control <?= form_error('name') ? 'is-invalid':'' ?>"
                           value="<?= set_value('name', $customer->name ?? '') ?>">
                    <?php if(form_error('name')): ?><div class="invalid-feedback"><?= form_error('name') ?></div><?php endif; ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control <?= form_error('phone') ? 'is-invalid':'' ?>"
                           value="<?= set_value('phone', $customer->phone ?? '') ?>" placeholder="+91-XXXXXXXXXX">
                    <?php if(form_error('phone')): ?><div class="invalid-feedback"><?= form_error('phone') ?></div><?php endif; ?>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-control" id="custType">
                        <?php
                        $cur_type = set_value('type', $customer->type ?? 'retail');
                        foreach(['retail','wholesale'] as $t):
                        ?>
                        <option value="<?= $t ?>" <?= $cur_type === $t ? 'selected':'' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= set_value('email', $customer->email ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= set_value('address', $customer->address ?? '') ?></textarea>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" class="form-control"
                           value="<?= set_value('city', $customer->city ?? '') ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="state" class="form-control"
                           value="<?= set_value('state', $customer->state ?? 'Punjab') ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Pincode</label>
                    <input type="text" name="pincode" class="form-control" maxlength="6"
                           value="<?= set_value('pincode', $customer->pincode ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- EV Dealership KYC & Government ID (Subsidy / RTO Registration / Finance) -->
        <div class="card border-0 mb-3" style="background:#f0fdf4; border-left:4px solid #16a34a !important; border-radius:8px;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="font-weight-bold text-success mb-0 d-flex align-items-center">
                        <i class="fas fa-id-card mr-2"></i> EV Dealership KYC &amp; Identity Details (RTO / Finance / Subsidy)
                    </h6>
                    <small class="text-muted"><i class="fas fa-lock mr-1"></i>Aadhaar masked for legal privacy</small>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size:0.84rem;">PAN Number</label>
                            <input type="text" name="pan" class="form-control form-control-sm text-uppercase" maxlength="10"
                                   placeholder="ABCDE1234F" style="letter-spacing:0.04em;"
                                   value="<?= set_value('pan', $customer->pan ?? '') ?>">
                            <small class="text-muted">High-value vehicle billing / finance</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size:0.84rem;">Aadhaar / Virtual ID</label>
                            <?php
                            $raw_aadhaar = $customer->aadhaar_number ?? '';
                            $display_aadhaar = (!empty($raw_aadhaar) && strlen($raw_aadhaar) >= 4)
                                ? ('XXXXXXXX' . substr($raw_aadhaar, -4))
                                : $raw_aadhaar;
                            ?>
                            <input type="text" name="aadhaar_number" class="form-control form-control-sm" maxlength="14"
                                   placeholder="12-digit Aadhaar"
                                   value="<?= set_value('aadhaar_number', $display_aadhaar) ?>">
                            <small class="text-muted">Last 4 digits kept visible</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size:0.84rem;">Secondary ID Proof</label>
                            <select name="id_proof_type" class="form-control form-control-sm">
                                <?php
                                $cur_id = set_value('id_proof_type', $customer->id_proof_type ?? '');
                                $id_options = [
                                    '' => '-- None / Optional --',
                                    'driving_licence' => 'Driving Licence (RTO)',
                                    'voter_id' => 'Voter ID Card',
                                    'passport' => 'Passport',
                                    'electricity_bill' => 'Electricity Bill (Address)'
                                ];
                                foreach ($id_options as $val => $lbl):
                                ?>
                                <option value="<?= $val ?>" <?= $cur_id === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">For RTO registration</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size:0.84rem;">Document / File Reference</label>
                            <input type="text" name="id_proof_doc" class="form-control form-control-sm"
                                   placeholder="DL / Doc Number"
                                   value="<?= set_value('id_proof_doc', $customer->id_proof_doc ?? '') ?>">
                            <small class="text-muted">Reference identifier</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr>
        <div class="row">
            <!-- GSTIN — shown mainly for wholesale -->
            <div class="col-md-4" id="gstinField">
                <div class="form-group">
                    <label>GSTIN <small class="text-muted">(for B2B/wholesale)</small></label>
                    <input type="text" name="gst_number" class="form-control" maxlength="15"
                           style="text-transform:uppercase"
                           value="<?= set_value('gst_number', $customer->gst_number ?? '') ?>"
                           placeholder="07XXXXX0000X1ZX">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Credit Limit (₹)</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                        <input type="number" step="0.01" name="credit_limit" class="form-control"
                               value="<?= set_value('credit_limit', $customer->credit_limit ?? 0) ?>">
                    </div>
                    <small class="form-text text-muted">0 = no credit (COD only)</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Discount %</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" max="100" name="discount_percent" class="form-control"
                               value="<?= set_value('discount_percent', $customer->discount_percent ?? 0) ?>">
                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                    </div>
                    <small class="form-text text-muted">Auto-apply in billing</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Opening Balance (₹) <small class="text-muted">old-system due</small></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                        <input type="number" step="0.01" name="opening_balance" class="form-control <?= form_error('opening_balance') ? 'is-invalid':'' ?>"
                               value="<?= set_value('opening_balance', $customer->opening_balance ?? 0) ?>">
                    </div>
                    <small class="form-text text-muted">Amount already owed as of go-live (1 Aug 2026)</small>
                    <?php if(form_error('opening_balance')): ?><div class="invalid-feedback d-block"><?= form_error('opening_balance') ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <hr>
        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="show_outstanding_in_print"
                       name="show_outstanding_in_print" value="1"
                       <?= ($customer->show_outstanding_in_print ?? 1) ? 'checked' : '' ?>>
                <label class="custom-control-label" for="show_outstanding_in_print">
                    Show Outstanding Balance in Bill Print
                    <small class="text-muted">(displays total due amount on invoice printout)</small>
                </label>
            </div>
        </div>
        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="einvoice_required"
                       name="einvoice_required" value="1"
                       <?= ($customer->einvoice_required ?? 0) ? 'checked' : '' ?>>
                <label class="custom-control-label" for="einvoice_required">
                    e-Invoice (IRN) Required
                    <small class="text-muted">(flags this customer's B2B invoices on the e-Invoice export dashboard)</small>
                </label>
            </div>
        </div>
        <?php
        // Loyalty is allowed by default; untick to close it for this customer.
        // Hidden entirely on an install where sql/45_loyalty.sql has not run.
        $loy_col   = $this->db->field_exists('loyalty_member', 'customers');
        $loy_on    = isset($customer->loyalty_member) ? (int)$customer->loyalty_member === 1 : true;
        $loy_bal   = (float)($customer->loyalty_points ?? 0);
        $walkin_id = (int)($company['walkin_customer_id'] ?? 0);
        $is_walkin = $walkin_id && isset($customer->id) && (int)$customer->id === $walkin_id;
        ?>
        <?php if ($loy_col): ?>
        <div class="form-group">
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="loyalty_member"
                       name="loyalty_member" value="1" <?= $loy_on ? 'checked' : '' ?>>
                <label class="custom-control-label" for="loyalty_member">
                    <strong>Allow Loyalty Points</strong>
                    <small class="text-muted d-block">
                        On by default. Untick to close this customer's loyalty account — they stop
                        earning and redeeming, but the balance and history are kept, so it can be
                        re-opened later.
                        <?php if (isset($customer->id) && $loy_bal > 0): ?>
                            <span class="text-primary font-weight-bold d-block mt-1">
                                Current balance: <?= number_format($loy_bal) ?> point(s).
                            </span>
                        <?php endif; ?>
                    </small>
                </label>
            </div>
            <?php if ($is_walkin): ?>
            <div class="alert alert-warning py-2 px-3 mt-2 mb-0" style="font-size:.83rem;">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                This is the <strong>Walk-in Customer</strong> used for bills with no named buyer.
                Leaving loyalty on here would pool every anonymous sale's points into one account.
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="portal_active"
                       name="portal_active" value="1"
                       <?= ($customer->portal_active ?? 1) ? 'checked' : '' ?>>
                <label class="custom-control-label" for="portal_active">
                    <strong>Enable Customer Portal Access</strong>
                    <small class="text-muted d-block">Customer can login at <code>/portal/login</code> using mobile number + last 4 digits as password.</small>
                </label>
            </div>
        </div>
        <hr>
        <button type="submit" class="btn btn-dark mr-2"><?= $is_edit ? 'Update Customer' : 'Save Customer' ?></button>
        <a href="<?= base_url('customers') ?>" class="btn btn-outline-secondary">Cancel</a>
        <?= form_close() ?>
    </div>
</div>

<script>
// Show/hide GSTIN hint based on type
document.getElementById('custType').addEventListener('change', function() {
    document.getElementById('gstinField').style.opacity = this.value === 'wholesale' ? '1' : '0.5';
});
document.getElementById('custType').dispatchEvent(new Event('change'));
</script>
