<?php // FILE: application/views/inventory/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Inventory</h5>
        <small class="text-muted">Real-time stock status</small>
    </div>
    <a href="<?= base_url('inventory/report') ?>" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-table"></i> Location-Wise Report
    </a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1" style="font-size:.8rem;">Total Stock Value</p>
                <h5 class="font-weight-bold">₹ <?= number_format($total_value, 2) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1" style="font-size:.8rem;">Out of Stock</p>
                <h5 class="font-weight-bold text-danger"><?= $out_of_stock ?> items</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1" style="font-size:.8rem;">Low Stock (&le;threshold)</p>
                <h5 class="font-weight-bold text-warning"><?= $low_stock ?> items</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1" style="font-size:.8rem;">Expiring in 30 days</p>
                <h5 class="font-weight-bold text-warning"><?= $expiring_soon ?> items</h5>
            </div>
        </div>
    </div>
</div>

<!-- Filter bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row">
            <div class="col-md-4">
                <input type="text" id="invSearch" class="form-control form-control-sm" placeholder="Search by name, item code, barcode, HSN, category…" autofocus>
            </div>
            <div class="col-md-2">
                <select id="stockFilter" class="form-control form-control-sm">
                    <option value="">All Stock</option>
                    <option value="out">Out of Stock</option>
                    <option value="low">Low Stock</option>
                    <option value="ok">In Stock</option>
                </select>
            </div>
            <div class="col-md-3">
                <small id="invResultInfo" class="text-muted" style="line-height:31px;">Showing first 500 items</small>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="invTable" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Item Code</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>HSN</th>
                        <th class="text-center">Stock</th>
                        <th class="text-right">Purchase ₹</th>
                        <th class="text-right">Selling ₹</th>
                        <th class="text-right">Stock Value</th>
                        <th>Expiry</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="invTableBody">
                <?php $data['is_admin'] = ($this->session->userdata('role') === 'admin'); $this->load->view('inventory/_rows', ['products' => $products, 'is_admin' => $data['is_admin']]); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Stock Adjust Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title font-weight-bold mb-0">Adjust Stock</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <?= form_open('', ['id'=>'adjustForm']) ?>
            <div class="modal-body">
                <p id="adjust_product_name" class="font-weight-bold mb-2"></p>
                <p class="mb-2 text-muted" style="font-size:.85rem;">Current: <span id="adjust_current"></span></p>
                <div class="form-group">
                    <label style="font-size:.85rem;">Adjustment Qty <small class="text-muted">(+/- number)</small></label>
                    <input type="number" name="adjust_qty" id="adjust_qty" class="form-control" placeholder="e.g. +5 or -3" required>
                </div>
                <div class="form-group">
                    <label style="font-size:.85rem;">Reason</label>
                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Damage, correction..." required>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="submit" class="btn btn-warning btn-sm">Apply Adjustment</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
var _invTimer = null;
document.getElementById('invSearch').addEventListener('input', function() {
    clearTimeout(_invTimer);
    _invTimer = setTimeout(doInvSearch, 300);
});
document.getElementById('stockFilter').addEventListener('change', doInvSearch);

function doInvSearch() {
    var q = document.getElementById('invSearch').value.trim();
    var s = document.getElementById('stockFilter').value;
    var info = document.getElementById('invResultInfo');
    info.textContent = 'Searching…';

    var url = '<?= base_url("inventory/list_ajax") ?>?q=' + encodeURIComponent(q) + '&stock=' + encodeURIComponent(s);
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            document.getElementById('invTableBody').innerHTML = html;
            var rows = document.querySelectorAll('#invTableBody tr');
            var count = rows.length;
            if (count === 1 && rows[0].querySelector('td[colspan]')) count = 0;
            info.textContent = (q || s) ? (count + ' result(s)') : ('Showing first 500 items');
        })
        .catch(function() { info.textContent = 'Search failed'; });
}

function openAdjust(id, name, qty) {
    document.getElementById('adjust_product_name').textContent = name;
    document.getElementById('adjust_current').textContent = qty + ' units';
    document.getElementById('adjustForm').action = '<?= base_url("inventory/adjust/") ?>' + id;
    document.getElementById('adjust_qty').value = '';
    $('#adjustModal').modal('show');
}
</script>
