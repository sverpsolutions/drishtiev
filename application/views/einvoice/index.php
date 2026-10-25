<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-file-invoice mr-1 text-primary"></i> e-Invoice (IRN) Export</h5>
        <small class="text-muted">Generate government-schema JSON for B2B invoices &mdash; upload manually on <code>einvoice1.gst.gov.in</code>, then record the IRN back here.</small>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?= $this->session->flashdata('success') ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= htmlspecialchars($this->session->flashdata('error')) ?></div><?php endif; ?>

<?php
$missing_company = empty($company['company_gstin']) || empty($company['company_pincode']) || empty($company['company_state_code']);
if ($missing_company):
?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    Company GSTIN / Pincode / GST State Code isn't fully set up yet &mdash; e-Invoice export will be blocked until these are filled in
    <a href="<?= base_url('settings') ?>" class="alert-link">Settings &gt; Company Info</a>.
</div>
<?php endif; ?>

<div class="alert <?= $einvoice_mandatory ? 'alert-danger' : 'alert-info' ?> py-2">
    <i class="fas fa-balance-scale mr-1"></i>
    <?php if ($turnover <= 0): ?>
        <strong>Annual turnover not set.</strong> Set it in <a href="<?= base_url('settings') ?>" class="alert-link">Settings &gt; Company Info</a> to know whether e-Invoice is legally mandatory for you and how many HSN digits are required.
    <?php elseif ($einvoice_mandatory): ?>
        <strong>e-Invoice is MANDATORY</strong> &mdash; turnover (&#8377;<?= number_format($turnover, 0) ?>) is above the &#8377;5 Cr threshold. Every B2B invoice needs an IRN, and HSN codes must be at least <strong>6 digits</strong>.
    <?php else: ?>
        e-Invoice is <strong>not yet mandatory</strong> &mdash; turnover (&#8377;<?= number_format($turnover, 0) ?>) is below the &#8377;5 Cr threshold. HSN codes must still be at least <strong>4 digits</strong> for B2B invoices. You can still generate e-Invoices voluntarily below.
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <?= form_open('einvoice', ['method' => 'GET', 'id' => 'filterForm']) ?>
        <div class="form-row align-items-end">
            <div class="col-md-2">
                <label class="small font-weight-bold">From Date</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col-md-2">
                <label class="small font-weight-bold">To Date</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col-md-3">
                <label class="small font-weight-bold">Status</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option value="not_applicable" <?= $status_filter === 'not_applicable' ? 'selected' : '' ?>>Not Exported Yet</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Exported &mdash; Awaiting IRN</option>
                    <option value="generated" <?= $status_filter === 'generated' ? 'selected' : '' ?>>IRN Generated</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="custom-control custom-checkbox mt-4">
                    <input type="checkbox" class="custom-control-input" id="required_only" name="required_only" value="1" <?= $required_only ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="required_only">Only "e-Invoice Required" customers</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-search mr-1"></i> Filter</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<?= form_open('einvoice/export', ['id' => 'exportForm']) ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.84rem;">
                <thead class="thead-dark">
                    <tr>
                        <th><input type="checkbox" id="checkAll"></th>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>GSTIN</th>
                        <th class="text-right">Total</th>
                        <th>Status</th>
                        <th>IRN</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($invoices)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No B2B invoices (customer with GSTIN) found for this filter.</td></tr>
                <?php else: ?>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td><input type="checkbox" name="invoice_ids[]" value="<?= $inv->id ?>" class="rowCheck"></td>
                    <td><a href="<?= base_url('billing/invoice/'.$inv->id) ?>"><?= htmlspecialchars($inv->invoice_no) ?></a></td>
                    <td><?= date('d M Y', strtotime($inv->invoice_date)) ?></td>
                    <td>
                        <?= htmlspecialchars($inv->customer_name) ?>
                        <?php if ($inv->einvoice_required): ?><span class="badge badge-info ml-1">Required</span><?php endif; ?>
                    </td>
                    <td><code><?= htmlspecialchars($inv->gst_number) ?></code></td>
                    <td class="text-right">&#8377; <?= number_format($inv->total_amount, 2) ?></td>
                    <td>
                        <?php if ($inv->einvoice_status === 'generated'): ?>
                            <span class="badge badge-success">IRN Generated</span>
                        <?php elseif ($inv->einvoice_status === 'pending'): ?>
                            <span class="badge badge-warning">Awaiting IRN</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Not Exported</span>
                        <?php endif; ?>
                    </td>
                    <td><?php if ($inv->einvoice_irn): ?><small class="text-monospace"><?= htmlspecialchars(substr($inv->einvoice_irn, 0, 12)) ?>&hellip;</small><?php endif; ?></td>
                    <td>
                        <?php if ($inv->einvoice_status !== 'generated'): ?>
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" onclick="openRecordModal(<?= $inv->id ?>, '<?= htmlspecialchars($inv->invoice_no, ENT_QUOTES) ?>')">Record IRN</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($invoices)): ?>
<div class="mb-4">
    <button type="submit" class="btn btn-primary" <?= $missing_company ? 'disabled' : '' ?>><i class="fas fa-download mr-1"></i> Export Selected as e-Invoice JSON</button>
    <small class="text-muted ml-2">Downloads a single JSON file (works for one invoice or many) to upload on the government e-Invoice portal.</small>
</div>
<?php endif; ?>
<?= form_close() ?>

<!-- Bulk import of the portal's IRN response -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold"><i class="fas fa-upload mr-1"></i> Import IRN Response (optional, bulk)</div>
    <div class="card-body">
        <p class="text-muted small mb-2">If the portal's bulk tool gave you back a JSON response file, upload it here &mdash; rows are matched to invoices by invoice number automatically. Unmatched rows can be entered manually with "Record IRN" above.</p>
        <?= form_open_multipart('einvoice/import_response') ?>
        <div class="form-row align-items-end">
            <div class="col-md-6">
                <input type="file" name="response_file" accept="application/json,.json" class="form-control-file" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-secondary btn-sm">Import Response JSON</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<!-- Record IRN modal -->
<div class="modal fade" id="recordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?= form_open('einvoice/save_response') ?>
            <div class="modal-header">
                <h6 class="modal-title font-weight-bold">Record e-Invoice IRN &mdash; <span id="recordInvNo"></span></h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="small font-weight-bold">IRN <span class="text-danger">*</span></label>
                    <input type="text" name="irn" class="form-control form-control-sm" required placeholder="64-character Invoice Reference Number from the portal">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Ack No</label>
                    <input type="text" name="ack_no" class="form-control form-control-sm">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Ack Date</label>
                    <input type="datetime-local" name="ack_date" class="form-control form-control-sm">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold">Signed QR Code Data</label>
                    <textarea name="qr_data" class="form-control form-control-sm" rows="3" placeholder="Paste the SignedQRCode string from the portal response (printed on the invoice as a QR code)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Save</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
document.getElementById('checkAll')?.addEventListener('change', function() {
    document.querySelectorAll('.rowCheck').forEach(cb => cb.checked = this.checked);
});
function openRecordModal(id, invNo) {
    document.getElementById('recordModal').querySelector('form').action = '<?= base_url('einvoice/save_response') ?>/' + id;
    document.getElementById('recordInvNo').textContent = invNo;
    $('#recordModal').modal('show');
}
</script>
