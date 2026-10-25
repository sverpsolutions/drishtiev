<?php
// ============================================================
// FILE: application/views/garments/import.php
// Import Garment Styles & Variants (CSV)
// ============================================================
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-file-csv text-success mr-2"></i>Import Garment Styles & Variants</h5>
        <small class="text-muted">Bulk import parent styles with size-wise, colour-wise, barcode-wise inventory from CSV</small>
    </div>
    <div>
        <a href="<?= base_url('garments') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Styles
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-upload text-primary mr-1"></i> Upload CSV File</h6>
                <a href="<?= base_url('garments/download_template') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-download mr-1"></i> Download Sample CSV Template
                </a>
            </div>
            <div class="card-body p-4">
                <form method="post" action="<?= base_url('garments/import') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold">Select CSV File (.csv) <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control-file p-2 border rounded" accept=".csv" required>
                    </div>

                    <div class="alert alert-info small mb-4">
                        <strong><i class="fas fa-info-circle mr-1"></i> CSV Column Structure:</strong><br>
                        <code>Product Name, Style Code, Garment Category, Gender, Brand, Size, Colour, Barcode, MRP, Selling Price, Cost Price, Opening Stock</code>
                        <ul class="mb-0 mt-2 pl-3">
                            <li><strong>Product Name:</strong> Required parent product name (e.g. Printed Kali Palazzo). Multiple rows with the same product name will attach variants to that single parent style.</li>
                            <li><strong>Barcode:</strong> If left empty, a unique GS1/EAN-13 barcode will be automatically generated.</li>
                            <li><strong>Opening Stock:</strong> Quantities will be credited to your active location automatically.</li>
                        </ul>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-success px-4 font-weight-bold">
                            <i class="fas fa-file-import mr-1"></i> Upload and Import Garments
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
