<?php $editing = !empty($army_company); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><?= $editing ? 'Edit Company' : 'Add Company' ?></h5>
        <small class="text-muted"><a href="<?= base_url('army_company_master') ?>">Company Master</a> / <?= $editing ? 'Edit' : 'Add' ?></small>
    </div>
    <a href="<?= base_url('army_company_master') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<?= form_open_multipart($editing ? 'army_company_master/edit/'.$army_company->id : 'army_company_master/create') ?>
<div class="row">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 font-weight-bold">Company Details</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="small font-weight-bold">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" required
                           value="<?= htmlspecialchars($army_company->company_name ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Address <small class="text-muted">(line breaks are preserved exactly on the printed quotation)</small></label>
                    <textarea name="address" class="form-control" rows="5"><?= htmlspecialchars($army_company->address ?? '') ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold">GST Number</label>
                        <input type="text" name="gst_number" class="form-control" value="<?= htmlspecialchars($army_company->gst_number ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold">PAN Number</label>
                        <input type="text" name="pan_number" class="form-control" value="<?= htmlspecialchars($army_company->pan_number ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($army_company->phone ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold">Mobile</label>
                        <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($army_company->mobile ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($army_company->email ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold">Website</label>
                        <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($army_company->website ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 font-weight-bold">Bank Details</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($army_company->bank_name ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold">Account Number</label>
                        <input type="text" name="account_number" class="form-control" value="<?= htmlspecialchars($army_company->account_number ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold">IFSC Code</label>
                        <input type="text" name="ifsc_code" class="form-control" value="<?= htmlspecialchars($army_company->ifsc_code ?? '') ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold">Branch</label>
                        <input type="text" name="branch" class="form-control" value="<?= htmlspecialchars($army_company->branch ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 font-weight-bold">Signatory & Images</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="small font-weight-bold">Authorized Signatory Name</label>
                    <input type="text" name="signatory_name" class="form-control" value="<?= htmlspecialchars($army_company->signatory_name ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Company Logo</label>
                    <input type="file" name="logo" class="form-control-file" accept="image/*">
                    <?php if (!empty($army_company->logo_path)): ?>
                        <div class="mt-2"><img src="<?= base_url($army_company->logo_path) ?>" style="max-height:60px;"></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Signature Image</label>
                    <input type="file" name="signature" class="form-control-file" accept="image/*">
                    <?php if (!empty($army_company->signature_path)): ?>
                        <div class="mt-2"><img src="<?= base_url($army_company->signature_path) ?>" style="max-height:60px;"></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Round Company Stamp</label>
                    <input type="file" name="stamp" class="form-control-file" accept="image/*">
                    <?php if (!empty($army_company->stamp_path)): ?>
                        <div class="mt-2"><img src="<?= base_url($army_company->stamp_path) ?>" style="max-height:60px;"></div>
                    <?php endif; ?>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                           <?= (!$editing || $army_company->is_active) ? 'checked' : '' ?>>
                    <label class="form-check-label small font-weight-bold" for="is_active">Active</label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-dark btn-block font-weight-bold"><?= $editing ? 'Update Company' : 'Save Company' ?></button>
    </div>
</div>
<?= form_close() ?>
