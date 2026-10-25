<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-boxes text-primary mr-2"></i>Linked Products — <?= htmlspecialchars($supplier->name) ?></h5>
    <a href="<?= base_url('suppliers/view/'.$supplier->id) ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 font-weight-bold">Products from this Supplier</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.8rem;">
                    <thead class="thead-light"><tr><th>Product</th><th>Code</th><th>Unit</th><th>Last Purchase Price</th><th>MOQ</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($linked_products)): ?><tr><td colspan="6" class="text-center text-muted py-3">No products linked.</td></tr><?php endif; ?>
                    <?php foreach ($linked_products as $lp): ?>
                    <tr>
                        <td><?= htmlspecialchars($lp->product_name) ?></td>
                        <td><?= htmlspecialchars($lp->item_code??'-') ?></td>
                        <td><?= htmlspecialchars($lp->unit??'PCS') ?></td>
                        <td>₹<?= number_format($lp->last_purchase_price??0,2) ?></td>
                        <td><?= $lp->supplier_moq??1 ?></td>
                        <td><button class="btn btn-xs btn-outline-danger" onclick="unlinkProduct(<?= $lp->product_id ?>)"><i class="fas fa-unlink"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3">
            <div class="font-weight-bold mb-2">Link a Product</div>
            <div class="form-group">
                <label class="small">Product</label>
                <select id="linkProductSel" class="form-control form-control-sm">
                    <option value="">-- Select --</option>
                    <?php foreach ($all_products as $p): ?>
                    <option value="<?= $p->id ?>"><?= htmlspecialchars($p->name) ?> [<?= $p->item_code ?>]</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="small">Last Purchase Price (₹)</label>
                <input type="number" step="0.01" id="linkPrice" class="form-control form-control-sm">
            </div>
            <div class="form-group">
                <label class="small">Supplier MOQ</label>
                <input type="number" id="linkMoq" class="form-control form-control-sm" value="1">
            </div>
            <button class="btn btn-sm btn-dark w-100" onclick="linkProduct()"><i class="fas fa-link mr-1"></i>Link Product</button>
        </div>
    </div>
</div>

<script>
function linkProduct() {
    var pid = document.getElementById('linkProductSel').value;
    var price = document.getElementById('linkPrice').value;
    var moq = document.getElementById('linkMoq').value;
    if (!pid) return alert('Select a product');
    var fd = new FormData();
    fd.append('supplier_id', <?= $supplier->id ?>);
    fd.append('product_id', pid);
    fd.append('last_purchase_price', price);
    fd.append('supplier_moq', moq);
    fd.append('action', 'link');
    fetch('<?= base_url('suppliers/link_product_ajax') ?>', {method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); else alert(d.error||'Error'); });
}
function unlinkProduct(pid) {
    if (!confirm('Unlink this product?')) return;
    var fd = new FormData();
    fd.append('supplier_id', <?= $supplier->id ?>);
    fd.append('product_id', pid);
    fd.append('action', 'unlink');
    fetch('<?= base_url('suppliers/link_product_ajax') ?>', {method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); else alert(d.error||'Error'); });
}
</script>
<style>.btn-xs{padding:2px 6px;font-size:.72rem;}</style>
