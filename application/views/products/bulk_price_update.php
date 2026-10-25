<?php // FILE: application/views/products/bulk_price_update.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-tags mr-2 text-primary"></i>Bulk Price Update</h5>
        <small class="text-muted"><a href="<?= base_url('products') ?>">Products</a> / Bulk Price Update</small>
    </div>
    <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-2">
                <i class="fas fa-file-excel mr-1"></i> Upload Excel / CSV
            </div>
            <div class="card-body">
                <p class="text-muted mb-3" style="font-size:.88rem;">
                    Upload a file with the following 4 columns. <strong>item_code</strong> is used to find the product.
                    Leave <strong>mrp</strong> or <strong>sp</strong> blank to skip updating that field.
                </p>

                <!-- Column format table -->
                <table class="table table-bordered table-sm mb-3" style="font-size:.85rem;">
                    <thead class="thead-dark">
                        <tr><th>item_code</th><th>item_name</th><th>mrp</th><th>sp</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>110112</td><td>Surf Excel 1kg</td><td>120.00</td><td>115.00</td></tr>
                        <tr><td>110113</td><td>Ariel 500g</td><td>85.00</td><td>80.00</td></tr>
                        <tr class="text-muted"><td colspan="4" class="text-center" style="font-size:.78rem;">Leave mrp or sp blank to skip that column</td></tr>
                    </tbody>
                </table>

                <div class="alert alert-warning py-2" style="font-size:.83rem;">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <strong>item_name</strong> in the file will also update the product name. Leave blank to keep existing name.
                </div>

                <!-- File input -->
                <div class="form-group">
                    <label class="font-weight-bold">Select File <span class="text-danger">*</span></label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="priceFile" accept=".xlsx,.xls,.csv">
                        <label class="custom-file-label" for="priceFile">Choose .xlsx / .xls / .csv</label>
                    </div>
                </div>

                <button class="btn btn-primary btn-block font-weight-bold" id="uploadBtn" onclick="doBulkPriceUpdate()">
                    <i class="fas fa-upload mr-1"></i> Update Prices
                </button>

                <!-- Result area -->
                <div id="uploadResult" class="mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white py-2">
                <i class="fas fa-download mr-1"></i> Download Template
            </div>
            <div class="card-body text-center">
                <p class="text-muted mb-3" style="font-size:.85rem;">Download a ready-made template with all your current products pre-filled. Just update the MRP and SP columns.</p>
                <a href="<?= base_url('products/bulk_price_template') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-file-excel mr-1 text-success"></i> Download Template (CSV)
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-light py-2 font-weight-bold" style="font-size:.85rem;">
                <i class="fas fa-info-circle mr-1 text-info"></i> Instructions
            </div>
            <div class="card-body" style="font-size:.83rem;">
                <ol class="mb-0 pl-3">
                    <li>Download the template or create your own Excel/CSV.</li>
                    <li>Fill in <strong>item_code</strong> (required) and prices.</li>
                    <li>Column headers must be exactly: <code>item_code</code>, <code>item_name</code>, <code>mrp</code>, <code>sp</code></li>
                    <li>Upload and click <strong>Update Prices</strong>.</li>
                    <li>Products not found by item_code will be skipped.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('priceFile').addEventListener('change', function() {
    this.nextElementSibling.textContent = this.files[0] ? this.files[0].name : 'Choose .xlsx / .xls / .csv';
});

function doBulkPriceUpdate() {
    var fileInput = document.getElementById('priceFile');
    if (!fileInput.files.length) { alert('Please select a file first.'); return; }

    var btn = document.getElementById('uploadBtn');
    var resultDiv = document.getElementById('uploadResult');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Updating...';
    resultDiv.style.display = 'none';

    var fd = new FormData();
    fd.append('price_file', fileInput.files[0]);

    fetch('<?= base_url('products/bulk_price_update_ajax') ?>', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-upload mr-1"></i> Update Prices';
        resultDiv.style.display = 'block';

        if (data.success) {
            var html = '<div class="alert alert-success">'
                + '<h6 class="font-weight-bold"><i class="fas fa-check-circle mr-1"></i> ' + data.message + '</h6>';
            if (data.preview && data.preview.length) {
                html += '<hr class="my-2"><small><strong>Sample updates:</strong><br>' + data.preview.join('<br>') + '</small>';
            }
            html += '</div>';
            resultDiv.innerHTML = html;
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> ' + data.message + '</div>';
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-upload mr-1"></i> Update Prices';
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div class="alert alert-danger">Network error: ' + err.message + '</div>';
    });
}
</script>
