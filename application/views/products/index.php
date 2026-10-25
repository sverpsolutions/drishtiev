<?php
// ============================================================
// FILE: application/views/products/index.php
// ============================================================
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Products</h5>
        <?php if (!empty($view_loc_id)): ?>
            <small class="text-muted">Stock shown for: <strong class="text-primary"><?= htmlspecialchars($this->session->userdata('active_location_name') ?: $this->session->userdata('location_name') ?: 'Your Location') ?></strong></small>
        <?php else: ?>
            <small class="text-muted">Manage your FMCG product catalogue &mdash; <span class="text-muted">Global stock (all locations)</span></small>
        <?php endif; ?>
    </div>
    <div>
        <a href="<?= base_url('products/import') ?>" class="btn btn-success btn-sm mr-1">
            <i class="fas fa-file-excel"></i> Import from Excel
        </a>
        <a href="<?= base_url('products/add') ?>" class="btn btn-dark btn-sm">+ Add Product</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $this->session->flashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $this->session->flashdata('error') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<!-- Search / Filter bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <input type="text" id="productSearch" class="form-control form-control-sm" placeholder="Search by name, item code, category, brand, barcode...">
        <?php if (!empty($products) && count($products) >= ($products_page_limit ?? 100)): ?>
            <small class="text-muted">Showing first <?= (int)($products_page_limit ?? 100) ?> products — use search to find any product in the full catalogue.</small>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="productsTable" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>HSN Code</th>
                        <th class="text-right">Basic Cost</th>
                        <th class="text-right">CP (incl.GST)</th>
                        <th class="text-right">MRP</th>
                        <th class="text-right">SP (Selling)</th>
                        <th class="text-right">WSP</th>
                        <th class="text-center">GST %</th>
                        <th class="text-center"><?= !empty($view_loc_id) ? 'Location Stock' : 'Stock (All)' ?></th>
                        <th>Barcode (PCS)</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $this->load->view('products/_rows', ['products' => $products, 'view_loc_id' => $view_loc_id]); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Server-side search — the table only ever holds the first <?= (int)($products_page_limit ?? 100) ?>
// products (or the current search's matches), never the whole catalogue, so this stays
// fast and light even with tens of thousands of products in the DB.
let productSearchTimer = null;
document.getElementById('productSearch').addEventListener('input', function() {
    clearTimeout(productSearchTimer);
    const q = this.value;
    productSearchTimer = setTimeout(function() {
        fetch('<?= base_url('products/list_ajax') ?>?q=' + encodeURIComponent(q))
            .then(function(res) { return res.text(); })
            .then(function(html) {
                const tbody = document.querySelector('#productsTable tbody');
                tbody.innerHTML = html;
                bindProductRowEvents();
            });
    }, 300);
});

// Double click to edit — (re)bound after every AJAX search result swap
function bindProductRowEvents() {
    document.querySelectorAll('.product-row').forEach(function(row) {
        row.addEventListener('dblclick', function(e) {
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('a') || e.target.closest('button')) {
                return;
            }
            const url = this.getAttribute('data-href');
            if (url) {
                window.location.href = url;
            }
        });
    });
}
bindProductRowEvents();
</script>
