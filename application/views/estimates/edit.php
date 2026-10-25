
<div class="container-fluid mt-3">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-edit text-warning"></i> Edit Estimate: <?= $estimate->estimate_no ?></h4>
    <a href="<?= base_url('estimates/view/'.$estimate->id) ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<form method="post" action="<?= base_url('estimates/edit/'.$estimate->id) ?>" id="estimateForm">

<!-- Customer Info -->
<div class="card shadow-sm mb-3">
<div class="card-header bg-warning text-dark py-2"><b>Customer Information</b></div>
<div class="card-body">
<div class="form-row">
    <div class="col-md-3">
        <label>Select Customer</label>
        <select name="customer_id" id="customer_id" class="form-control form-control-sm" onchange="fillCustomer(this)">
            <option value="">-- Walk-in --</option>
            <?php foreach ($customers as $c): ?>
            <option value="<?= $c->id ?>" data-name="<?= htmlspecialchars($c->name) ?>" data-phone="<?= $c->phone ?>" <?= ($estimate->customer_id == $c->id)?'selected':'' ?>><?= htmlspecialchars($c->name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label>Customer Name *</label>
        <input type="text" name="customer_name" id="customer_name" class="form-control form-control-sm" value="<?= htmlspecialchars($estimate->customer_name) ?>" required>
    </div>
    <div class="col-md-2">
        <label>Mobile</label>
        <input type="text" name="customer_mobile" id="customer_mobile" class="form-control form-control-sm" value="<?= $estimate->customer_mobile ?>">
    </div>
    <div class="col-md-2">
        <label>Estimate Date *</label>
        <input type="date" name="estimate_date" class="form-control form-control-sm" value="<?= $estimate->estimate_date ?>" required>
    </div>
    <div class="col-md-2">
        <label>Valid Until</label>
        <input type="date" name="valid_until" class="form-control form-control-sm" value="<?= $estimate->valid_until ?>">
    </div>
</div>
</div>
</div>

<!-- Items -->
<div class="card shadow-sm mb-3">
<div class="card-header bg-warning text-dark py-2 d-flex justify-content-between">
    <b>Items</b>
    <small>Tip: Type item code or name to add items quickly</small>
</div>
<div class="card-body p-2">

<div class="form-row mb-2">
    <div class="col-md-5">
        <div class="input-group input-group-sm">
            <input type="text" id="smartSearch" class="form-control" placeholder="🔍 Type item code or name..." autocomplete="off">
            <div class="input-group-append">
                <button type="button" class="btn btn-warning btn-sm" onclick="triggerSearch()">Search</button>
            </div>
        </div>
        <div id="searchDropdown" class="list-group position-absolute w-50" style="z-index:9999; max-height:250px; overflow-y:auto; display:none;"></div>
    </div>
</div>

<div class="table-responsive">
<table class="table table-bordered table-sm" id="itemsTable">
<thead class="thead-light">
<tr>
    <th>#</th><th>Code</th><th>Description</th><th>Qty</th><th>Unit</th>
    <th>Rate</th><th>Disc%</th>
    <?php if (!empty($settings['enable_gst'])): ?>
    <th>Tax%</th><th>Tax Amt</th>
    <?php endif; ?>
    <th>Total</th><th>Stock</th><th>Del</th>
</tr>
</thead>
<tbody id="itemsBody"></tbody>
<tfoot>
<tr class="table-warning">
    <td colspan="<?= !empty($settings['enable_gst']) ? 9 : 7 ?>" class="text-right"><b>Grand Total:</b></td>
    <td class="text-right font-weight-bold text-danger h5" id="footTotal">₹0.00</td>
    <td colspan="2"></td>
</tr>
</tfoot>
</table>
</div>
<button type="button" class="btn btn-sm btn-outline-warning" onclick="addRow()"><i class="fas fa-plus"></i> Add Row</button>
</div>
</div>

<div class="card shadow-sm mb-3">
<div class="card-body">
<div class="form-row">
    <div class="col-md-8">
        <label>Notes</label>
        <textarea name="notes" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($estimate->notes) ?></textarea>
    </div>
    <div class="col-md-4 text-right pt-3">
        <div class="h4 text-danger">Total: ₹<span id="grandTotalDisplay">0.00</span></div>
        <button type="submit" class="btn btn-warning btn-lg mt-2"><i class="fas fa-save"></i> Update Estimate</button>
    </div>
</div>
</div>
</div>

</form>
</div>

<script>
var enableGst = <?= !empty($settings['enable_gst']) ? 'true' : 'false' ?>;
var enableStockCheck = <?= !empty($settings['enable_estimate_stock_check']) ? 'true' : 'false' ?>;
var rowCount = 0;

// Pre-populate existing items
var existingItems = <?= json_encode(array_map(function($i){ return [
    'id'           => $i->product_id,
    'item_code'    => $i->item_code,
    'product_name' => $i->description,
    'rate'         => $i->rate,
    'unit'         => $i->unit,
    'gst_percent'  => $i->tax_percent,
    'stock_qty'    => $i->stock_qty,
    'qty'          => $i->qty,
    'discount'     => $i->discount,
]; }, $items)) ?>;

function fillCustomer(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('customer_name').value   = opt.dataset.name  || '';
    document.getElementById('customer_mobile').value = opt.dataset.phone || '';
}

function addRow(product, existDisc) {
    rowCount++;
    var i = rowCount;
    var p = product || {};
    var disc = existDisc !== undefined ? existDisc : 0;
    var taxCols = enableGst ? `
        <td><input type="number" name="tax_percent[]" class="form-control form-control-sm tax-pct" value="${p.gst_percent||0}" min="0" max="40" step="0.01" onchange="calcRow(${i})"></td>
        <td><input type="text" class="form-control form-control-sm bg-light" id="tax_amt_${i}" readonly value="0.00"></td>` : '';

    var row = `<tr id="row_${i}" data-row="${i}">
        <td>${i}</td>
        <td><input type="text" id="code_${i}" class="form-control form-control-sm" value="${p.item_code||''}" placeholder="Code" onblur="searchByCode(${i})"></td>
        <td>
            <input type="hidden" name="product_id[]" id="pid_${i}" value="${p.id||''}">
            <input type="text" name="description[]" id="desc_${i}" class="form-control form-control-sm" value="${p.product_name||''}" required>
        </td>
        <td><input type="number" name="qty[]" id="qty_${i}" class="form-control form-control-sm" value="${p.qty||1}" min="0.001" step="0.001" onchange="calcRow(${i})"></td>
        <td><input type="text" name="unit[]" id="unit_${i}" class="form-control form-control-sm" value="${p.unit||'PCS'}" readonly></td>
        <td><input type="number" name="rate[]" id="rate_${i}" class="form-control form-control-sm" value="${p.rate||0}" min="0" step="0.01" onchange="calcRow(${i})"></td>
        <td><input type="number" name="item_discount[]" class="form-control form-control-sm disc-pct" value="${disc}" min="0" max="100" step="0.01" onchange="calcRow(${i})"></td>
        ${taxCols}
        <td><input type="text" id="total_${i}" name="line_total[]" class="form-control form-control-sm bg-light font-weight-bold" readonly value="0.00"></td>
        <td><span class="badge badge-secondary" id="stock_badge_${i}">${p.stock_qty||'-'}</span></td>
        <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(${i})"><i class="fas fa-trash"></i></button></td>
    </tr>`;

    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', row);
    calcRow(i);
}

function removeRow(i) { document.getElementById('row_'+i)?.remove(); calcFooter(); }

function calcRow(i) {
    var qty  = parseFloat(document.getElementById('qty_'+i)?.value) || 0;
    var rate = parseFloat(document.getElementById('rate_'+i)?.value) || 0;
    var disc = parseFloat(document.querySelector('#row_'+i+' .disc-pct')?.value) || 0;
    var tax  = enableGst ? (parseFloat(document.querySelector('#row_'+i+' .tax-pct')?.value)||0) : 0;
    // Rate is GST-inclusive: (gross - discAmt) IS the final quoted amount.
    var gross = qty*rate, discAmt = gross*(disc/100), total = gross-discAmt;
    var taxable = tax > 0 ? total / (1 + tax/100) : total, taxAmt = total - taxable;
    if (enableGst) document.getElementById('tax_amt_'+i).value = taxAmt.toFixed(2);
    document.getElementById('total_'+i).value = total.toFixed(2);
    calcFooter();
}

function calcFooter() {
    var sum = 0;
    document.querySelectorAll('[id^="total_"]').forEach(t => sum += parseFloat(t.value)||0);
    document.getElementById('footTotal').textContent = '₹'+sum.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = sum.toFixed(2);
}

function searchByCode(i) {
    var code = document.getElementById('code_'+i)?.value.trim(); if(!code) return;
    fetch('<?= base_url("estimates/ajax_search_product") ?>?q='+encodeURIComponent(code))
    .then(r=>r.json()).then(items=>{
        if(items.length>0){var p=items[0];
            document.getElementById('pid_'+i).value=p.id;
            document.getElementById('desc_'+i).value=p.product_name;
            document.getElementById('rate_'+i).value=p.rate;
            document.getElementById('unit_'+i).value=p.unit;
            if(enableGst){var t=document.querySelector('#row_'+i+' .tax-pct');if(t)t.value=p.gst_percent||0;}
            calcRow(i);}
    });
}

var searchTimeout;
document.getElementById('smartSearch').addEventListener('input', function(){
    clearTimeout(searchTimeout);
    var q=this.value.trim(); if(q.length<1){document.getElementById('searchDropdown').style.display='none';return;}
    searchTimeout=setTimeout(()=>doSearch(q),200);
});
function triggerSearch(){var q=document.getElementById('smartSearch').value.trim();if(q)doSearch(q,true);}
function doSearch(q,autoSelect){
    fetch('<?= base_url("estimates/ajax_search_product") ?>?q='+encodeURIComponent(q))
    .then(r=>r.json()).then(items=>{
        if(autoSelect&&items.length===1){addRow(items[0]);document.getElementById('smartSearch').value='';document.getElementById('searchDropdown').style.display='none';return;}
        var dd=document.getElementById('searchDropdown');dd.innerHTML='';
        if(!items.length){dd.style.display='none';return;}
        items.forEach(p=>{var a=document.createElement('a');a.href='#';a.className='list-group-item list-group-item-action py-1 px-2 small';
            a.innerHTML=`<b>[${p.item_code||'?'}]</b> ${p.product_name} &mdash; ₹${p.rate} &mdash; Stock: ${p.stock_qty}`;
            a.onclick=function(e){e.preventDefault();addRow(p);document.getElementById('smartSearch').value='';dd.style.display='none';};
            dd.appendChild(a);});dd.style.display='block';
    });
}
document.addEventListener('click',function(e){if(!e.target.closest('#smartSearch')&&!e.target.closest('#searchDropdown'))document.getElementById('searchDropdown').style.display='none';});

// Load existing items
window.onload = function() { existingItems.forEach(p => addRow(p, p.discount||0)); };
</script>

