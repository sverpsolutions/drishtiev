<?php
$flash_e = $this->session->flashdata('error');
$flash_s = $this->session->flashdata('success');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-excel text-success mr-2"></i>Import Customers</h5>
        <small class="text-muted">Bulk add customers from an Excel / CSV file, including old-system dues as an opening balance</small>
    </div>
    <a href="<?= base_url('customers') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Customers</a>
</div>

<?php if ($flash_e): ?><div class="alert alert-danger alert-dismissible fade show"><?= $flash_e ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
<?php if ($flash_s): ?><div class="alert alert-success alert-dismissible fade show"><?= $flash_s ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header"><i class="fas fa-upload mr-1"></i> Upload File</div>
            <div class="card-body">
                <form method="post" action="<?= base_url('customers/import') ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="font-weight-bold small">Select Excel (.xlsx) or CSV file</label>
                        <input type="file" name="import_file" accept=".xlsx,.xls,.csv" class="form-control-file" required>
                        <small class="form-text text-muted">First row must be the header row.</small>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Import Customers</button>
                    <a href="<?= base_url('customers/import_template') ?>" class="btn btn-outline-primary btn-sm ml-2"><i class="fas fa-download"></i> Download Template</a>
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
                        <tr><td><code>phone</code></td><td><span class="badge badge-secondary">Optional</span></td></tr>
                        <tr><td><code>email</code></td><td><span class="badge badge-secondary">Optional</span></td></tr>
                        <tr><td><code>type</code> (retail / wholesale)</td><td><span class="badge badge-secondary">Optional</span></td></tr>
                        <tr><td><code>gst_number</code></td><td><span class="badge badge-secondary">Optional</span></td></tr>
                        <tr><td><code>address</code>, <code>city</code>, <code>state</code>, <code>pincode</code></td><td><span class="badge badge-secondary">Optional</span></td></tr>
                        <tr><td><code>credit_limit</code></td><td><span class="badge badge-secondary">Optional</span></td></tr>
                        <tr><td><code>opening_balance</code></td><td><span class="badge badge-warning text-dark">Old dues</span></td></tr>
                    </tbody>
                </table>
                <ul class="small text-muted mb-0 pl-3">
                    <li>Only the customer <strong>name</strong> is mandatory.</li>
                    <li><strong>opening_balance</strong> = amount this customer already owed in your old system as of go-live (1 Aug 2026). It becomes their starting outstanding balance here, so nothing gets lost in the switch.</li>
                    <li>Rows whose <strong>phone</strong> (or name, if no phone) matches an existing active customer are skipped — safe to re-run.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
