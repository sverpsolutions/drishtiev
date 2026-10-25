<?php // FILE: application/views/plu/import.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-file-excel text-success mr-1"></i> Import PLU Master</h5>
        <small class="text-muted"><a href="<?= base_url('plu') ?>">PLU Master</a> / Import from Excel or CSV</small>
    </div>
    <a href="<?= base_url('plu') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $cls): ?>
    <?php if ($this->session->flashdata($key)): ?>
        <div class="alert alert-<?= $cls ?> alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= $this->session->flashdata($key) ?>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<div class="row">
    <div class="col-lg-5 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold">Upload your file</div>
            <div class="card-body">
                <form method="post" action="<?= base_url('plu/import_preview') ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="small font-weight-bold">Excel (.xlsx / .xls) or CSV file</label>
                        <input type="file" name="import_file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                        <small class="form-text text-muted">Maximum 8 MB. The first row must be the column headings.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-eye mr-1"></i> Preview Import
                    </button>
                    <a href="<?= base_url('plu/import_template') ?>" class="btn btn-outline-success ml-1">
                        <i class="fas fa-download mr-1"></i> Download Template
                    </a>
                </form>

                <hr>
                <div class="alert alert-info py-2 px-3 mb-0" style="font-size:.85rem;">
                    <i class="fas fa-info-circle mr-1"></i>
                    Nothing is saved yet. The next screen shows exactly which rows will be
                    <strong>created</strong>, which will be <strong>updated</strong> and which
                    have <strong>errors</strong> — you confirm from there.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold">Columns the importer understands</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:.82rem;">
                        <thead class="thead-light">
                            <tr><th style="width:26%;">Column</th><th>Also accepted as</th></tr>
                        </thead>
                        <tbody>
                        <?php
                        $required = ['mrp'];
                        $labels = [
                            'plu_code' => 'PLU Code', 'item_code' => 'Item Code', 'name' => 'Name',
                            'barcode' => 'Barcode', 'batch_no' => 'Batch No', 'mrp' => 'MRP',
                            'selling_rate' => 'Selling Rate', 'purchase_rate' => 'Purchase Rate',
                            'landing_cost' => 'Landing Cost', 'gst_percent' => 'GST %',
                            'mfg_date' => 'Mfg Date', 'expiry_date' => 'Expiry Date',
                            'supplier' => 'Supplier', 'location' => 'Location',
                            'stock_qty' => 'Stock Qty', 'status' => 'Status',
                        ];
                        foreach ($col_map as $field => $aliases): ?>
                            <tr>
                                <td class="font-weight-bold">
                                    <?= htmlspecialchars($labels[$field] ?? $field) ?>
                                    <?php if (in_array($field, $required, true)): ?>
                                        <span class="badge badge-danger">required</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars(implode(', ', $aliases)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="font-weight-bold mb-2">How rows are matched</h6>
        <ol class="mb-3" style="font-size:.87rem;">
            <li>If a <strong>PLU Code</strong> is given, that exact PLU is updated.</li>
            <li>Otherwise the product is found by <strong>Item Code</strong>, then <strong>Barcode</strong>, then exact <strong>Name</strong>.</li>
            <li>If that product already has a PLU at the same <strong>MRP</strong>, it is updated. If not, a new PLU is created.</li>
        </ol>
        <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:.85rem;">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>MRP is what separates one PLU from another.</strong> Two rows for the same
            product with the same MRP is treated as an error, not two PLUs. If a product name
            matches more than one item in the master, the row is rejected rather than guessed.
        </div>
    </div>
</div>
