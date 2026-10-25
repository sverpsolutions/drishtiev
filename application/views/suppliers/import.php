<?php
$flash_e = $this->session->flashdata('error');
$flash_s = $this->session->flashdata('success');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-excel text-success mr-2"></i>Import Suppliers</h5>
        <small class="text-muted">Bulk add suppliers from an Excel / CSV file</small>
    </div>
    <a href="<?= base_url('suppliers') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Suppliers</a>
</div>

<?php if ($flash_e): ?><div class="alert alert-danger alert-dismissible fade show"><?= $flash_e ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
<?php if ($flash_s): ?><div class="alert alert-success alert-dismissible fade show"><?= $flash_s ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header"><i class="fas fa-upload mr-1"></i> Upload File</div>
            <div class="card-body">
                <form method="post" action="<?= base_url('suppliers/import') ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="font-weight-bold small">Select Excel (.xlsx) or CSV file</label>
                        <input type="file" name="import_file" accept=".xlsx,.xls,.csv" class="form-control-file" required>
                        <small class="form-text text-muted">First row must be the header row.</small>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Import Suppliers</button>
                    <a href="<?= base_url('suppliers/import_template') ?>" class="btn btn-outline-primary btn-sm ml-2"><i class="fas fa-download"></i> Download Template</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header"><i class="fas fa-info-circle mr-1"></i> File Format</div>
            <div class="card-body">
                <p class="small mb-2">Use these column headers (any order):</p>
                <table class="table table-sm table-bordered mb-2">
                    <thead class="thead-light"><tr><th>Column</th><th>Required?</th></tr></thead>
                    <tbody>
                        <tr><td><code>name</code></td><td><span class="badge badge-danger">Required</span></td></tr>
                        <tr><td><code>gst_number</code></td><td><span class="badge badge-secondary">Optional</span></td></tr>
                        <tr><td><code>phone</code></td><td><span class="badge badge-secondary">Optional</span></td></tr>
                    </tbody>
                </table>
                <ul class="small text-muted mb-0 pl-3">
                    <li>Only the supplier <strong>name</strong> is mandatory.</li>
                    <li>GST and phone may be left blank.</li>
                    <li>Rows with a name that already exists are skipped.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
