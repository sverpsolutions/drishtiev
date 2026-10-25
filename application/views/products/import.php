<?php // FILE: application/views/products/import.php ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Import Products</h5>
        <small class="text-muted">Bulk import from Excel (.xlsx) or CSV file</small>
    </div>
    <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Products</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<div class="row">

    <!-- Left: Upload form -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white py-2">
                <strong>Upload File</strong>
            </div>
            <div class="card-body">
                <?= form_open_multipart('products/import_preview') ?>
                <div class="form-group">
                    <label>Select File <span class="text-danger">*</span></label>
                    <input type="file" name="import_file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                    <small class="text-muted">Supported: .xlsx, .xls, .csv — max 8MB</small>
                </div>
                <button type="submit" class="btn btn-info btn-block" id="importBtn">
                    <i class="fas fa-eye"></i> Preview Before Import
                </button>
                <small class="text-muted d-block mt-1">You will see a preview and error report before data is imported.</small>
                <?= form_close() ?>
                <hr>
                <a href="<?= base_url('products/download_excel_template') ?>" class="btn btn-outline-success btn-sm btn-block">
                    <i class="fas fa-file-excel"></i> Download Excel Template (.xlsx)
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header py-2">
                <strong>Download Template</strong>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2" style="font-size:.87rem;">
                    Download the blank CSV template, fill in your products, and upload it above.
                    You can also open it in Excel and save as .xlsx.
                </p>
                <a href="<?= base_url('products/download_template') ?>" class="btn btn-outline-success btn-sm">
                    &#8681; Download CSV Template
                </a>
            </div>
        </div>
    </div>

    <!-- Right: Column reference -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2">
                <strong>Column Reference</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0" style="font-size:.82rem;">
                    <thead class="thead-light">
                        <tr><th>Column Header</th><th>Required?</th><th>Notes</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>name</code></td><td><span class="badge badge-danger">Yes</span></td><td>Product name</td></tr>
                        <tr><td><code>item_code</code></td><td>No</td><td>Auto-generated if blank</td></tr>
                        <tr><td><code>hsn_code</code></td><td>Recommended</td><td>Must exist in HSN Master</td></tr>
                        <tr><td><code>brand</code></td><td>No</td><td>Text (not linked to master)</td></tr>
                        <tr><td><code>category</code></td><td>No</td><td>Text (not linked to master)</td></tr>
                        <tr><td><code>mrp</code></td><td>No</td><td>Max Retail Price</td></tr>
                        <tr><td><code>purchase_price</code></td><td>No</td><td>Cost price</td></tr>
                        <tr><td><code>selling_price</code></td><td>No</td><td>Default sale rate</td></tr>
                        <tr><td><code>gst_percent</code></td><td>No</td><td>Overridden by HSN if hsn_code given</td></tr>
                        <tr><td><code>stock_qty</code></td><td>No</td><td>Opening stock, default 0</td></tr>
                        <tr><td><code>unit</code></td><td>No</td><td>PCS / KG / LTR / BOX</td></tr>
                        <tr><td><code>carton_size</code></td><td>No</td><td>No. of PCS in 1 CRT</td></tr>
                        <tr><td><code>low_stock_threshold</code></td><td>No</td><td>Alert level, default 10</td></tr>
                        <tr><td><code>expiry_date</code></td><td>No</td><td>Any readable date format</td></tr>
                        <tr><td><code>pcs_barcode</code></td><td>No</td><td>Single piece barcode</td></tr>
                        <tr><td><code>crt_barcode</code></td><td>No</td><td>Carton barcode</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="alert alert-warning mt-3" style="font-size:.83rem;">
            <strong>Note on HSN codes:</strong> If you provide a <code>hsn_code</code> column, the system will look it up in the HSN Master and auto-assign IGST/CGST/SGST rates.
            If the code doesn't exist yet, <a href="<?= base_url('hsn/add') ?>" target="_blank">add it to HSN Master first</a>.
        </div>
    </div>

</div>

<script>
document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('importBtn').textContent = 'Importing... Please wait';
    document.getElementById('importBtn').disabled = true;
});
</script>
