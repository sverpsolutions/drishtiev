<?php
// $is_edit=bool, $po=object|null, $items=array, $suppliers, $products, $locations, $suggestions=[]
$suggestions = $suggestions ?? [];
$v = function($f,$d='') use ($po) { return $po ? htmlspecialchars($po->$f ?? $d) : $d; };
?>
<!-- Header -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-dark text-white py-2"><i class="fas fa-info-circle mr-1"></i> Order Header</div>
    <div class="card-body">
        <div class="form-row">
            <div class="col-md-3 form-group">
                <label class="small font-weight-bold">Supplier <span class="text-danger">*</span></label>
                <select name="supplier_id" id="supplierSel" class="form-control form-control-sm" required onchange="loadSupplierProducts()">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $sid => $sname): ?>
                    <option value="<?= $sid ?>" <?= $v('supplier_id')==$sid?'selected':'' ?>><?= htmlspecialchars($sname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 form-group">
                <label class="small font-weight-bold">Delivery Location</label>
                <select name="location_id" class="form-control form-control-sm">
                    <option value="">-- Select Location --</option>
                    <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= $v('location_id')==$loc->id?'selected':'' ?>><?= htmlspecialchars($loc->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 form-group">
                <label class="small font-weight-bold">Expected Delivery</label>
                <input name="expected_delivery" type="date" class="form-control form-control-sm" value="<?= $v('expected_delivery', date('Y-m-d', strtotime('+7 days'))) ?>">
            </div>
            <div class="col-md-3 form-group">
                <label class="small font-weight-bold">Payment Terms</label>
                <input name="payment_terms" class="form-control form-control-sm" value="<?= $v('payment_terms') ?>" placeholder="e.g. Net 30">
            </div>
            <div class="col-md-4 form-group">
                <label class="small font-weight-bold">Reference</label>
                <input name="reference" class="form-control form-control-sm" value="<?= $v('reference') ?>">
            </div>
            <div class="col-md-8 form-group">
                <label class="small font-weight-bold">Remarks</label>
                <input name="remarks" class="form-control form-control-sm" value="<?= $v('remarks') ?>">
            </div>
        </div>
    </div>
</div>

<!-- Global product search bar -->
<div class="card border-0 shadow-sm mb-2">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center">
            <i class="fas fa-search text-muted mr-2"></i>
            <div class="flex-grow-1 position-relative">
                <input type="text" id="globalProductSearch" class="form-control form-control-sm"
                    placeholder="Search product by name or code (type 3+ chars) → Arrow keys to navigate → Enter to add row"
                    autocomplete="off">
                <div id="globalSearchDrop" class="list-group shadow"
                    style="display:none;position:absolute;top:100%;left:0;right:0;z-index:9999;max-height:280px;overflow-y:auto;"></div>
            </div>
            <small class="text-muted ml-3 text-nowrap">or <a href="#" onclick="addRow();return false;">+ Add blank row</a></small>
        </div>
    </div>
</div>

<!-- Suggestion load bar -->
<div class="card border-0 shadow-sm mb-2" id="suggPanel" style="display:<?= !empty($suggestions)?'':'none' ?>">
    <div class="card-header py-2 bg-info text-white d-flex justify-content-between align-items-center">
        <span><i class="fas fa-magic mr-1"></i> Smart Suggestions — based on sales</span>
        <div class="d-flex align-items-center">
            <select id="salesPeriod" class="form-control form-control-sm mr-2" onchange="loadSupplierProducts()" style="width:auto">
                <option value="7">Last 7 Days</option>
                <option value="15">Last 15 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="45">Last 45 Days</option>
                <option value="60">Last 60 Days</option>
            </select>
            <button type="button" class="btn btn-sm btn-light" onclick="addAllSuggested()"><i class="fas fa-plus-circle"></i> Add All Suggested</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:240px;overflow-y:auto;">
            <table class="table table-sm mb-0" id="suggTable" style="font-size:.76rem;">
                <thead class="thead-light">
                    <tr><th>Product</th><th>Stock</th><th>Sales/Day</th><th>Visit Days</th><th>Suggested</th><th>Tag</th><th></th></tr>
                </thead>
                <tbody id="suggBody">
                    <?php foreach ($suggestions as $sg): ?>
                    <tr data-id="<?= $sg['product_id'] ?>" data-price="<?= $sg['last_purchase_price'] ?>" data-gst="<?= $sg['gst_percent'] ?>" data-unit="<?= htmlspecialchars($sg['unit']) ?>">
                        <td><?= htmlspecialchars($sg['name']) ?> <small class="text-muted"><?= $sg['item_code'] ?></small></td>
                        <td><?= $sg['current_stock'] ?></td>
                        <td><?= $sg['avg_daily_sales'] ?></td>
                        <td><?= $sg['visit_cycle_days'] ?></td>
                        <td><strong class="text-success"><?= $sg['suggested_qty'] ?></strong></td>
                        <td><?php $tc=['fast'=>'success','medium'=>'info','slow'=>'warning','dead'=>'danger']; ?>
                            <span class="badge badge-<?= $tc[$sg['tag']]??'secondary' ?>"><?= ucfirst($sg['tag']) ?></span></td>
                        <td><button type="button" class="btn btn-xs btn-outline-success" onclick="addSuggested(this, <?= $sg['suggested_qty'] ?>)">Add</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Items Grid -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold">Items <span class="badge badge-secondary" id="rowCnt">0</span></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.78rem;min-width:900px;">
                <thead class="thead-light">
                    <tr>
                        <th style="width:240px">Product</th><th style="width:80px">Stock</th>
                        <th style="width:70px">Sugg.Qty</th><th style="width:80px">Order Qty</th>
                        <th style="width:90px">Rate (₹)</th><th style="width:70px">Disc%</th>
                        <th style="width:70px">GST%</th><th style="width:90px">Amount</th><th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <tr id="emptyRow"><td colspan="9" class="text-center text-muted py-3" style="font-size:.8rem;"><i class="fas fa-search mr-1"></i>Search a product above or click "+ Add blank row" to begin</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Totals -->
<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3 mb-3">
            <div class="form-group mb-2">
                <label class="small font-weight-bold">Freight Charges (₹)</label>
                <input name="freight_charges" id="freight" type="number" step="0.01" class="form-control form-control-sm" value="<?= $v('freight_charges','0') ?>" oninput="calcTotals()">
            </div>
            <div class="form-group mb-0">
                <label class="small font-weight-bold">Delivery Address</label>
                <textarea name="delivery_address" class="form-control form-control-sm" rows="2"><?= $v('delivery_address') ?></textarea>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3 mb-3" style="font-size:.85rem;">
            <table class="table table-sm mb-0">
                <tr><td class="text-muted">Gross Amount</td><td class="text-right font-weight-bold" id="tGross">₹0.00</td></tr>
                <tr><td class="text-muted">Total Discount</td><td class="text-right text-danger" id="tDisc">-₹0.00</td></tr>
                <tr><td class="text-muted">Total Tax</td><td class="text-right" id="tTax">₹0.00</td></tr>
                <tr><td class="text-muted">Freight</td><td class="text-right" id="tFreight">₹0.00</td></tr>
                <tr class="font-weight-bold"><td>Net Total</td><td class="text-right text-success h5 mb-0" id="tNet">₹0.00</td></tr>
            </table>
        </div>
    </div>
</div>

<div class="form-group">
    <?php if ($is_edit): ?>
    <button type="submit" name="action" value="save" class="btn btn-secondary btn-sm"><i class="fas fa-save"></i> Save Draft</button>
    <button type="submit" name="action" value="submit" class="btn btn-dark btn-sm ml-2"><i class="fas fa-paper-plane"></i> Submit for Approval</button>
    <?php else: ?>
    <button type="submit" name="action" value="save" class="btn btn-secondary btn-sm"><i class="fas fa-save"></i> Save as Draft</button>
    <button type="submit" name="action" value="submit" class="btn btn-success btn-sm ml-2"><i class="fas fa-paper-plane"></i> Submit for Approval</button>
    <?php endif; ?>
    <a href="<?= base_url('purchase_orders') ?>" class="btn btn-outline-secondary btn-sm ml-2">Cancel</a>
</div>

<script>
var PRODUCTS = <?php
    $map = [];
    foreach ($products as $p) {
        $map[] = ['id'=>(int)$p->id,'name'=>$p->name,'item_code'=>$p->item_code??'','unit'=>$p->unit??'PCS',
                  'gst_percent'=>(float)($p->gst_percent??0),'stock_qty'=>(float)($p->stock_qty??0),
                  'basic_cost'=>(float)($p->basic_cost??0),'mrp'=>(float)($p->mrp??0)];
    }
    echo json_encode($map);
?>;
var rowCount = 0;

/* ─── Global product search ─── */
var gSearchIdx = -1;
var gSearchResults = [];
var gSearchInput  = document.getElementById('globalProductSearch');
var gSearchDrop   = document.getElementById('globalSearchDrop');

// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
gSearchInput.addEventListener('input', function() {
    var q = this.value.trim();
    if (q.length < 3) { gSearchDrop.style.display='none'; gSearchIdx=-1; return; }
    gSearchResults = PRODUCTS.filter(function(p) {
        return pmatch(q, p.name, p.item_code);
    }).slice(0, 15);
    renderGlobalDrop();
});

function renderGlobalDrop() {
    if (!gSearchResults.length) { gSearchDrop.style.display='none'; return; }
    gSearchDrop.innerHTML = gSearchResults.map(function(p, i) {
        var stockBadge = p.stock_qty > 10 ? 'success' : (p.stock_qty > 0 ? 'warning' : 'danger');
        var active = i === gSearchIdx ? ' active' : '';
        return '<a href="#" class="list-group-item list-group-item-action py-1 px-2 gs-item'+active+'" data-idx="'+i+'" style="font-size:.8rem;">' +
            '<div class="d-flex justify-content-between align-items-center">' +
            '<div><strong>'+escHtml(p.name)+'</strong> <span class="text-muted small">'+escHtml(p.item_code)+'</span></div>' +
            '<div><span class="badge badge-'+stockBadge+' mr-2">Stock: '+p.stock_qty+' '+escHtml(p.unit)+'</span>' +
            '<span class="text-dark font-weight-bold">₹'+p.basic_cost.toFixed(2)+'</span></div>' +
            '</div></a>';
    }).join('');
    gSearchDrop.style.display = 'block';
    // click events
    gSearchDrop.querySelectorAll('.gs-item').forEach(function(el) {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            var idx = parseInt(this.getAttribute('data-idx'));
            selectGlobalProduct(idx);
        });
    });
}

function selectGlobalProduct(idx) {
    var p = gSearchResults[idx];
    if (!p) return;
    gSearchInput.value = '';
    gSearchDrop.style.display = 'none';
    gSearchIdx = -1;
    addRow({product_id: p.id, ordered_qty: 1, rate: p.basic_cost, gst_pct: p.gst_percent,
            discount_pct: 0, suggested_qty: 0, current_stock: p.stock_qty, avg_daily_sales: 0, unit: p.unit});
    // focus the last Order Qty input
    var rows = document.querySelectorAll('#itemsBody tr[id^="row_"]');
    if (rows.length) {
        var lastRow = rows[rows.length - 1];
        var qtyInp = lastRow.querySelector('[name="ordered_qty[]"]');
        if (qtyInp) { qtyInp.select(); qtyInp.focus(); }
    }
}

gSearchInput.addEventListener('keydown', function(e) {
    var items = gSearchDrop.querySelectorAll('.gs-item');
    if (!items.length) return;
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        gSearchIdx = Math.min(gSearchIdx + 1, gSearchResults.length - 1);
        renderGlobalDrop();
        var active = gSearchDrop.querySelector('.gs-item.active');
        if (active) active.scrollIntoView({block:'nearest'});
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        gSearchIdx = Math.max(gSearchIdx - 1, 0);
        renderGlobalDrop();
        var active = gSearchDrop.querySelector('.gs-item.active');
        if (active) active.scrollIntoView({block:'nearest'});
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (gSearchIdx >= 0) {
            selectGlobalProduct(gSearchIdx);
        } else if (gSearchResults.length > 0) {
            selectGlobalProduct(0);
        }
    } else if (e.key === 'Escape') {
        gSearchDrop.style.display = 'none';
        gSearchIdx = -1;
    }
});

document.addEventListener('click', function(e) {
    if (!gSearchInput.contains(e.target) && !gSearchDrop.contains(e.target)) {
        gSearchDrop.style.display = 'none';
        gSearchIdx = -1;
    }
});

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ─── Row-level inline search (for existing rows) ─── */
var activeRowDrop = null;

function makeRowSearch(rid, pid) {
    var p = pid ? PRODUCTS.find(function(x){return x.id===pid;}) : null;
    var val = p ? (p.name + (p.item_code?' ['+p.item_code+']':'')) : '';
    return '<div class="position-relative row-search-wrap">' +
        '<input type="text" class="form-control form-control-sm row-search-inp" value="'+escHtml(val)+'" placeholder="Type to search..." autocomplete="off"' +
        ' data-rid="'+rid+'" oninput="onRowSearch(this,'+rid+')" onkeydown="onRowSearchKey(event,'+rid+')">' +
        '<input type="hidden" name="product_id[]" id="pid_'+rid+'" value="'+(pid||0)+'">' +
        '<div class="list-group shadow row-search-drop" id="rdrop_'+rid+'" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:9990;max-height:220px;overflow-y:auto;"></div>' +
        '</div>';
}

function onRowSearch(inp, rid) {
    var q = inp.value.trim();
    var drop = document.getElementById('rdrop_'+rid);
    if (q.length < 3) { drop.style.display='none'; return; }
    var res = PRODUCTS.filter(function(p) {
        return pmatch(q, p.name, p.item_code);
    }).slice(0,12);
    if (!res.length) { drop.style.display='none'; return; }
    drop.setAttribute('data-idx','-1');
    drop._results = res;
    drop.innerHTML = res.map(function(p,i){
        var sb = p.stock_qty>10?'success':(p.stock_qty>0?'warning':'danger');
        return '<a href="#" class="list-group-item list-group-item-action py-1 px-2 rdrop-item" data-i="'+i+'" style="font-size:.78rem;">' +
            '<div class="d-flex justify-content-between align-items-center">' +
            '<span><strong>'+escHtml(p.name)+'</strong> <small class="text-muted">'+escHtml(p.item_code)+'</small></span>' +
            '<span><span class="badge badge-'+sb+' mr-1">'+p.stock_qty+'</span>₹'+p.basic_cost.toFixed(2)+'</span></div></a>';
    }).join('');
    drop.style.display = 'block';
    drop.querySelectorAll('.rdrop-item').forEach(function(el){
        el.addEventListener('mousedown', function(e){
            e.preventDefault();
            var i = parseInt(this.getAttribute('data-i'));
            selectRowProduct(rid, drop._results[i]);
        });
    });
    activeRowDrop = drop;
}

function onRowSearchKey(e, rid) {
    var drop = document.getElementById('rdrop_'+rid);
    if (!drop || drop.style.display==='none') return;
    var items = drop.querySelectorAll('.rdrop-item');
    var idx = parseInt(drop.getAttribute('data-idx')||'-1');
    if (e.key==='ArrowDown') {
        e.preventDefault();
        idx = Math.min(idx+1, items.length-1);
        drop.setAttribute('data-idx', idx);
        highlightRowDrop(drop, items, idx);
    } else if (e.key==='ArrowUp') {
        e.preventDefault();
        idx = Math.max(idx-1, 0);
        drop.setAttribute('data-idx', idx);
        highlightRowDrop(drop, items, idx);
    } else if (e.key==='Enter') {
        e.preventDefault();
        idx = parseInt(drop.getAttribute('data-idx'));
        var target = idx >= 0 ? drop._results[idx] : (drop._results.length ? drop._results[0] : null);
        if (target) selectRowProduct(rid, target);
    } else if (e.key==='Escape') {
        drop.style.display='none';
    }
}

function highlightRowDrop(drop, items, idx) {
    items.forEach(function(el,i){ el.classList.toggle('active', i===idx); });
    if (items[idx]) items[idx].scrollIntoView({block:'nearest'});
}

function selectRowProduct(rid, p) {
    var drop = document.getElementById('rdrop_'+rid);
    var inp = document.querySelector('#row_'+rid+' .row-search-inp');
    if (inp) inp.value = p.name + (p.item_code?' ['+p.item_code+']':'');
    document.getElementById('pid_'+rid).value = p.id;
    if (drop) drop.style.display='none';
    // fill fields
    document.getElementById('cs_'+rid).value = p.stock_qty;
    document.querySelector('#row_'+rid+' [name="rate[]"]').value = p.basic_cost.toFixed(2);
    document.querySelector('#row_'+rid+' [name="gst_pct[]"]').value = p.gst_percent;
    document.getElementById('unit_'+rid).value = p.unit;
    // focus order qty
    var qtyInp = document.querySelector('#row_'+rid+' [name="ordered_qty[]"]');
    if (qtyInp) { qtyInp.select(); qtyInp.focus(); }
    calcRow(rid);
}

/* ─── Row management ─── */
<?php if ($is_edit && !empty($items)): ?>
window.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($items as $item): ?>
    addRow({
        product_id: <?= (int)$item->product_id ?>,
        current_stock: <?= (float)$item->current_stock ?>,
        avg_daily_sales: <?= (float)$item->avg_daily_sales ?>,
        suggested_qty: <?= (float)$item->suggested_qty ?>,
        ordered_qty: <?= (float)$item->ordered_qty ?>,
        rate: <?= (float)$item->rate ?>,
        discount_pct: <?= (float)$item->discount_pct ?>,
        gst_pct: <?= (float)$item->gst_pct ?>,
        unit: '<?= addslashes($item->unit??'PCS') ?>'
    });
    <?php endforeach; ?>
});
<?php endif; ?>

function addRow(data) {
    var emptyRow = document.getElementById('emptyRow');
    if (emptyRow) emptyRow.remove();
    rowCount++;
    var rid = rowCount;
    var tr = document.createElement('tr');
    tr.id = 'row_'+rid;
    tr.innerHTML =
        '<td>'+makeRowSearch(rid, data ? data.product_id : null)+'</td>'+
        '<td><input name="current_stock[]" id="cs_'+rid+'" type="number" class="form-control form-control-sm bg-light" readonly value="'+(data?data.current_stock:0)+'"></td>'+
        '<td><input name="suggested_qty[]" id="sq_'+rid+'" type="number" step="0.01" class="form-control form-control-sm bg-light text-success font-weight-bold" readonly value="'+(data?data.suggested_qty:0)+'"></td>'+
        '<td><input name="ordered_qty[]" type="number" step="0.01" class="form-control form-control-sm" value="'+(data?data.ordered_qty:0)+'" oninput="calcRow('+rid+')"></td>'+
        '<td><input name="rate[]" type="number" step="0.01" class="form-control form-control-sm" value="'+(data?data.rate:0)+'" oninput="calcRow('+rid+')"></td>'+
        '<td><input name="discount_pct[]" type="number" step="0.01" class="form-control form-control-sm" value="'+(data?data.discount_pct:0)+'" oninput="calcRow('+rid+')"></td>'+
        '<td><input name="gst_pct[]" type="number" step="0.01" class="form-control form-control-sm" value="'+(data?data.gst_pct:0)+'" oninput="calcRow('+rid+')"></td>'+
        '<td><input name="amount[]" id="amt_'+rid+'" type="number" step="0.01" class="form-control form-control-sm bg-light font-weight-bold" readonly value="0"></td>'+
        '<td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow('+rid+')"><i class="fas fa-times"></i></button></td>'+
        '<input type="hidden" name="avg_daily_sales[]" id="ads_'+rid+'" value="'+(data?data.avg_daily_sales:0)+'">'+
        '<input type="hidden" name="unit[]" id="unit_'+rid+'" value="'+(data?(data.unit||'PCS'):'PCS')+'">';
    document.getElementById('itemsBody').appendChild(tr);
    updateRowCount(); calcRow(rid);
    if (!data) {
        var inp = document.querySelector('#row_'+rid+' .row-search-inp');
        if (inp) inp.focus();
    }
    // Enter on ordered_qty → back to global search
    var qtyField = document.querySelector('#row_'+rid+' [name="ordered_qty[]"]');
    if (qtyField) {
        qtyField.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                gSearchInput.value = '';
                gSearchInput.focus();
                gSearchInput.select();
            }
        });
    }
}

function calcRow(rid) {
    var row  = document.getElementById('row_'+rid);
    if (!row) return;
    var qty  = parseFloat(row.querySelector('[name="ordered_qty[]"]').value)||0;
    var rate = parseFloat(row.querySelector('[name="rate[]"]').value)||0;
    var disc = parseFloat(row.querySelector('[name="discount_pct[]"]').value)||0;
    var gst  = parseFloat(row.querySelector('[name="gst_pct[]"]').value)||0;
    var line = qty * rate;
    var da   = line * disc / 100;
    var tax  = (line - da) * gst / 100;
    var amt  = line - da + tax;
    document.getElementById('amt_'+rid).value = amt.toFixed(2);
    calcTotals();
}

function calcTotals() {
    var gross=0,disc=0,tax=0;
    document.querySelectorAll('#itemsBody tr[id^="row_"]').forEach(function(tr){
        var rid = tr.id.replace('row_','');
        var qty  = parseFloat(tr.querySelector('[name="ordered_qty[]"]')?.value)||0;
        var rate = parseFloat(tr.querySelector('[name="rate[]"]')?.value)||0;
        var dp   = parseFloat(tr.querySelector('[name="discount_pct[]"]')?.value)||0;
        var gp   = parseFloat(tr.querySelector('[name="gst_pct[]"]')?.value)||0;
        var line = qty*rate; var da = line*dp/100; var ta = (line-da)*gp/100;
        gross+=line; disc+=da; tax+=ta;
    });
    var freight = parseFloat(document.getElementById('freight').value)||0;
    var net = gross - disc + tax + freight;
    document.getElementById('tGross').textContent   = '₹'+gross.toFixed(2);
    document.getElementById('tDisc').textContent    = '-₹'+disc.toFixed(2);
    document.getElementById('tTax').textContent     = '₹'+tax.toFixed(2);
    document.getElementById('tFreight').textContent = '₹'+freight.toFixed(2);
    document.getElementById('tNet').textContent     = '₹'+net.toFixed(2);
}

function removeRow(rid) {
    var r = document.getElementById('row_'+rid);
    if (r) r.remove();
    if (!document.querySelectorAll('#itemsBody tr[id^="row_"]').length) {
        var tbody = document.getElementById('itemsBody');
        tbody.innerHTML = '<tr id="emptyRow"><td colspan="9" class="text-center text-muted py-3" style="font-size:.8rem;"><i class="fas fa-search mr-1"></i>Search a product above or click &ldquo;+ Add blank row&rdquo; to begin</td></tr>';
    }
    updateRowCount(); calcTotals();
}

function updateRowCount() {
    document.getElementById('rowCnt').textContent = document.querySelectorAll('#itemsBody tr[id^="row_"]').length;
}

/* ─── Suggestions loading ─── */
function loadSupplierProducts() {
    var sid = document.getElementById('supplierSel').value;
    if (!sid) { document.getElementById('suggPanel').style.display='none'; return; }
    var days = document.getElementById('salesPeriod') ? document.getElementById('salesPeriod').value : 30;
    fetch('<?= base_url('purchase_orders/get_supplier_products_ajax') ?>?supplier_id='+sid+'&days='+days)
    .then(function(r){return r.json();}).then(function(data){
        document.getElementById('suggPanel').style.display = '';
        var tbody = document.getElementById('suggBody'); tbody.innerHTML = '';
        var tc = {fast:'success',medium:'info',slow:'warning',dead:'danger'};
        data.forEach(function(sg){
            if (sg.suggested_qty <= 0 && sg.tag === 'dead') return;
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', sg.product_id);
            tr.setAttribute('data-price', sg.last_purchase_price);
            tr.setAttribute('data-gst', sg.gst_percent);
            tr.setAttribute('data-unit', sg.unit);
            tr.innerHTML = '<td>'+escHtml(sg.name)+' <small class="text-muted">'+escHtml(sg.item_code)+'</small></td>'+
                '<td>'+sg.current_stock+'</td><td>'+sg.avg_daily_sales+'</td><td>'+sg.visit_cycle_days+'</td>'+
                '<td><strong class="text-success">'+sg.suggested_qty+'</strong></td>'+
                '<td><span class="badge badge-'+(tc[sg.tag]||'secondary')+'">'+sg.tag+'</span></td>'+
                '<td><button type="button" class="btn btn-xs btn-outline-success" onclick="addSuggested(this,'+sg.suggested_qty+')">Add</button></td>';
            tbody.appendChild(tr);
        });
    });
}

function addSuggested(btn, qty) {
    var tr = btn.closest('tr');
    var pid   = parseInt(tr.getAttribute('data-id'));
    var price = parseFloat(tr.getAttribute('data-price'));
    var gst   = parseFloat(tr.getAttribute('data-gst'));
    var unit  = tr.getAttribute('data-unit');
    addRow({product_id:pid, ordered_qty:qty, rate:price, gst_pct:gst, discount_pct:0, suggested_qty:qty, current_stock:0, avg_daily_sales:0, unit:unit});
    btn.textContent = '✓'; btn.disabled = true;
}

function addAllSuggested() {
    document.querySelectorAll('#suggBody tr').forEach(function(tr){
        var btn = tr.querySelector('button');
        if (btn && !btn.disabled) addSuggested(btn, parseFloat(tr.querySelector('strong').textContent)||0);
    });
}

// Auto-load suggestions if supplier pre-selected
var preSup = document.getElementById('supplierSel').value;
if (preSup) { setTimeout(loadSupplierProducts, 200); }

// Close row dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.row-search-wrap')) {
        document.querySelectorAll('.row-search-drop').forEach(function(d){d.style.display='none';});
    }
});

// Prevent accidental form submit on Enter (except save/submit buttons)
var poForm = document.getElementById('poForm');
if (poForm) {
    poForm.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            var tag = e.target.tagName;
            var type = (e.target.type||'').toLowerCase();
            // Allow Enter only on textareas and submit buttons
            if (tag === 'TEXTAREA') return;
            if (tag === 'BUTTON' && type === 'submit') return;
            if (tag === 'INPUT' && type === 'submit') return;
            // For everything else — block form submit
            e.preventDefault();
        }
    });
}
</script>

<style>
.btn-xs{padding:2px 6px;font-size:.72rem;}
.gs-item.active, .rdrop-item.active { background:#0069d9; color:#fff; }
.gs-item.active small, .rdrop-item.active small { color:#cce5ff !important; }
.gs-item.active .badge, .rdrop-item.active .badge { border: 1px solid #fff; }
#globalProductSearch { border: 2px solid #26a69a; border-radius:4px; }
#globalProductSearch:focus { border-color:#00796b; box-shadow:0 0 0 .2rem rgba(38,166,154,.25); }
</style>
