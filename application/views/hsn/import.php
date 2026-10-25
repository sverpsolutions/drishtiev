<?php // FILE: application/views/hsn/import.php ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Import HSN Codes</h5>
        <small class="text-muted">Bulk import from Excel (.xlsx) or CSV</small>
    </div>
    <a href="<?= base_url('hsn') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to HSN Master</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<div class="row">

    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white py-2"><strong>Upload File</strong></div>
            <div class="card-body">
                <?= form_open_multipart('hsn/import_preview') ?>
                <div class="form-group">
                    <label>Select File <span class="text-danger">*</span></label>
                    <input type="file" name="import_file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                    <small class="text-muted">Supported: .xlsx, .xls, .csv — max 8MB</small>
                </div>
                <button type="submit" class="btn btn-info btn-block" id="importBtn">
                    <i class="fas fa-eye"></i> Preview Before Import
                </button>
                <small class="text-muted d-block mt-1">You will see a preview before data is saved.</small>
                <?= form_close() ?>
                <hr>
                <a href="<?= base_url('hsn/download_template') ?>" class="btn btn-outline-success btn-sm btn-block">
                    <i class="fas fa-file-csv"></i> Download CSV Template
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2"><strong>Column Reference</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0" style="font-size:.83rem;">
                    <thead class="thead-light">
                        <tr><th>Column</th><th>Required?</th><th>Notes</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>hsn_code</code></td><td><span class="badge badge-danger">Yes</span></td><td>Unique HSN / SAC code, max 20 chars</td></tr>
                        <tr><td><code>description</code></td><td>No</td><td>Short description of goods</td></tr>
                        <tr><td><code>igst_percent</code></td><td><span class="badge badge-danger">Yes</span></td><td>0, 5, 12, 18, 40 etc. CGST/SGST auto-split.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="alert alert-info mt-3" style="font-size:.83rem;">
            <strong>Tip:</strong> Column headers are case-insensitive. Accepted aliases:<br>
            <code>hsn code / hsn_code / hsn</code><br>
            <code>igst% / igst_percent / gst% / gst</code><br>
            <code>description / desc / name</code>
        </div>
    </div>

</div>

<script>
document.querySelector('form').addEventListener('submit', function() {
    var btn = document.getElementById('importBtn');
    btn.textContent = 'Parsing... Please wait';
    btn.disabled = true;
});
</script>
