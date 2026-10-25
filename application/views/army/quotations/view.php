<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Quotation #<?= htmlspecialchars($quotation->quotation_no) ?></h5>
        <small class="text-muted"><a href="<?= base_url('army_quotations') ?>">Army Quotations</a> / View</small>
    </div>
    <div class="d-flex" style="gap:5px;">
        <a href="<?= base_url('army_quotations/edit/'.$quotation->id) ?>" class="btn btn-outline-primary btn-sm">Edit</a>
        <a href="<?= base_url('army_quotations/print/'.$quotation->id) ?>" class="btn btn-outline-secondary btn-sm" target="_blank">Print</a>
        <a href="<?= base_url('army_quotations/pdf/'.$quotation->id) ?>" class="btn btn-danger btn-sm">Download PDF</a>
        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#emailModal">Email</button>
        <a href="<?= base_url('army_quotations/duplicate/'.$quotation->id) ?>" class="btn btn-outline-dark btn-sm">Duplicate</a>
        <a href="<?= base_url('army_quotations/delete/'.$quotation->id) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this quotation?')">Delete</a>
        <a href="<?= base_url('army_quotations') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold">Company</div>
            <div class="card-body">
                <?php if (!empty($quotation->logo_path)): ?>
                    <img src="<?= base_url($quotation->logo_path) ?>" style="max-height:50px;margin-bottom:8px;"><br>
                <?php endif; ?>
                <div class="font-weight-bold"><?= htmlspecialchars($quotation->company_name) ?></div>
                <div style="white-space:pre-line;"><?= htmlspecialchars($quotation->company_address ?? '') ?></div>
                <div class="small text-muted mt-1">
                    <?php if ($quotation->company_gst_number): ?>GSTIN: <?= htmlspecialchars($quotation->company_gst_number) ?><br><?php endif; ?>
                    <?php if ($quotation->company_phone): ?>Ph: <?= htmlspecialchars($quotation->company_phone) ?><br><?php endif; ?>
                    <?php if ($quotation->company_email): ?>Email: <?= htmlspecialchars($quotation->company_email) ?><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold">Quotation To</div>
            <div class="card-body">
                <div class="font-weight-bold"><?= htmlspecialchars($quotation->customer_name) ?></div>
                <?php if ($quotation->contact_person): ?><div>Attn: <?= htmlspecialchars($quotation->contact_person) ?></div><?php endif; ?>
                <div style="white-space:pre-line;"><?= htmlspecialchars($quotation->address ?? '') ?></div>
                <div class="small text-muted mt-1">
                    <?php if ($quotation->mobile): ?>Mobile: <?= htmlspecialchars($quotation->mobile) ?><br><?php endif; ?>
                    <?php if ($quotation->email): ?>Email: <?= htmlspecialchars($quotation->email) ?><?php endif; ?>
                </div>
                <hr class="my-2">
                <div class="row small">
                    <div class="col-6"><strong>Date:</strong> <?= date('d M Y', strtotime($quotation->quotation_date)) ?></div>
                    <div class="col-6"><strong>Valid Till:</strong> <?= $quotation->valid_till ? date('d M Y', strtotime($quotation->valid_till)) : '—' ?></div>
                    <div class="col-6"><strong>Subject:</strong> <?= htmlspecialchars($quotation->subject ?? '—') ?></div>
                    <div class="col-6"><strong>Reference:</strong> <?= htmlspecialchars($quotation->reference ?? '—') ?></div>
                    <div class="col-6"><strong>Status:</strong> <span class="badge badge-secondary"><?= ucfirst($quotation->status) ?></span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="thead-dark">
                <tr>
                    <th>Sr.No</th>
                    <th>Particulars</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= $it->sr_no ?></td>
                    <td><?= htmlspecialchars($it->particulars) ?></td>
                    <td class="text-right"><?= number_format($it->qty, 2) ?> <?= htmlspecialchars($it->unit ?? 'Nos') ?></td>
                    <td class="text-right">₹ <?= number_format($it->rate, 2) ?></td>
                    <td class="text-right">₹ <?= number_format($it->amount, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="4" class="text-right">Sub Total</td><td class="text-right">₹ <?= number_format($quotation->sub_total, 2) ?></td></tr>
                <tr><td colspan="4" class="text-right">GST (<?= number_format($quotation->gst_percent, 2) ?>%)</td><td class="text-right">₹ <?= number_format($quotation->gst_amount, 2) ?></td></tr>
                <tr class="font-weight-bold" style="font-size:1.05rem;"><td colspan="4" class="text-right">Grand Total</td><td class="text-right">₹ <?= number_format($quotation->grand_total, 2) ?></td></tr>
            </tfoot>
        </table>
    </div>
</div>

<?php if (!empty($quotation->terms_text)): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold">Terms & Conditions</div>
    <div class="card-body" style="white-space:pre-line;font-size:.9rem;"><?= htmlspecialchars($quotation->terms_text) ?></div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body text-right">
        <?php if (!empty($quotation->signature_path)): ?>
            <img src="<?= base_url($quotation->signature_path) ?>" style="max-height:50px;"><br>
        <?php endif; ?>
        <?php if (!empty($quotation->stamp_path)): ?>
            <img src="<?= base_url($quotation->stamp_path) ?>" style="max-height:70px;"><br>
        <?php endif; ?>
        <div class="font-weight-bold mt-1">For <?= htmlspecialchars($quotation->company_name) ?></div>
        <div><?= htmlspecialchars($quotation->signatory_name ?? '') ?></div>
        <div class="small text-muted">Authorized Signatory</div>
    </div>
</div>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <?= form_open('army_quotations/send_email/'.$quotation->id) ?>
            <div class="modal-header">
                <h6 class="modal-title font-weight-bold">Send Quotation — <?= htmlspecialchars($quotation->quotation_no) ?></h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="small font-weight-bold">To <span class="text-danger">*</span></label>
                    <input type="email" name="to_email" class="form-control form-control-sm" required value="<?= htmlspecialchars($quotation->email ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">CC</label>
                    <input type="text" name="cc_email" class="form-control form-control-sm">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">BCC</label>
                    <input type="text" name="bcc_email" class="form-control form-control-sm">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Subject</label>
                    <input type="text" name="email_subject" class="form-control form-control-sm"
                           value="Quotation <?= htmlspecialchars($quotation->quotation_no) ?> from <?= htmlspecialchars($quotation->company_name) ?>">
                </div>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold">Message</label>
                    <textarea name="email_message" class="form-control form-control-sm" rows="5">Dear <?= htmlspecialchars($quotation->customer_name) ?>,

Please find attached our quotation <?= htmlspecialchars($quotation->quotation_no) ?> dated <?= date('d M Y', strtotime($quotation->quotation_date)) ?>.

Regards,
<?= htmlspecialchars($quotation->company_name) ?></textarea>
                </div>
                <small class="text-muted d-block mt-2">A PDF copy of this quotation will be attached automatically.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark btn-sm">Send Quotation</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<?php if ($this->input->get('open_email', TRUE)): ?>
<script>$(function() { $('#emailModal').modal('show'); });</script>
<?php endif; ?>
