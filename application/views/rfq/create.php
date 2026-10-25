<?= form_open('rfq/create') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-envelope-open-text text-primary mr-2"></i>Create New RFQ</h5>
    <a href="<?= base_url('rfq') ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-dark text-white py-2">RFQ Details</div>
    <div class="card-body">
        <div class="form-row">
            <div class="col-md-6 form-group"><label class="small font-weight-bold">Title</label>
                <input name="title" class="form-control form-control-sm" placeholder="e.g. Monthly Stock Replenishment"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Deadline</label>
                <input name="deadline" type="date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+7 days')) ?>"></div>
            <div class="col-md-12 form-group"><label class="small font-weight-bold">Notes to Suppliers</label>
                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Terms, quality requirements, delivery preference..."></textarea></div>
        </div>
    </div>
</div>

<!-- Search bar like billing -->
<div class="card border-0 shadow-sm mb-1" style="border-left:4px solid #007bff !important;">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center" style="gap:8px;">
            <label class="font-weight-bold mb-0 text-primary text-nowrap" style="font-size:.78rem;"><i class="fas fa-search mr-1"></i>Search &amp; Add Item</label>
            <div class="input-group input-group-sm flex-grow-1 position-relative">
                <input type="text" id="rfqMainSearch" class="form-control border-primary" placeholder="Type product name or item code & press Enter to add..." autocomplete="off" style="font-size:.88rem;font-weight:500;">
                <div class="input-group-append">
                    <button type="button" class="btn btn-primary btn-sm" onclick="triggerRfqSearch()"><i class="fas fa-search"></i></button>
                </div>
                <div id="rfqMainDrop" class="list-group position-absolute" style="z-index:9999;max-height:260px;overflow-y:auto;display:none;width:100%;top:100%;left:0;box-shadow:0 4px 12px rgba(0,0,0,.15);"></div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-dark flex-shrink-0" onclick="addBlankRow()"><i class="fas fa-plus"></i> Add Row</button>
        </div>
        <small id="rfqSearchStatus" class="form-text text-muted" style="font-size:.72rem;min-height:13px;margin-top:1px;"></small>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold">Items Required <span class="badge badge-secondary" id="rfqRowCount">0</span></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="overflow:visible;">
            <table class="table table-sm mb-0" style="font-size:.8rem;overflow:visible;">
                <thead class="thead-light">
                    <tr>
                        <th style="width:35%">Product</th>
                        <th style="width:80px">Qty</th>
                        <th style="width:60px">Unit</th>
                        <th style="width:120px">Target Rate (₹)</th>
                        <th>Notes</th>
                        <th style="width:30px"></th>
                    </tr>
                </thead>
                <tbody id="rfqItemsBody"></tbody>
            </table>
            <div id="rfqEmpty" class="text-center text-muted py-3" style="font-size:.82rem;">Search and add products above</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold">Select Suppliers to Send RFQ</div>
    <div class="card-body">
        <div class="form-row">
            <?php foreach ($suppliers as $sup): ?>
            <div class="col-md-3 mb-2">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="supplier_ids[]" value="<?= $sup->id ?>" id="sup_<?= $sup->id ?>">
                    <label class="custom-control-label" for="sup_<?= $sup->id ?>">
                        <?= htmlspecialchars($sup->name) ?>
                        <?php if ($sup->supplier_code): ?><small class="text-muted">(<?= htmlspecialchars($sup->supplier_code) ?>)</small><?php endif; ?>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane mr-1"></i> Create & Send RFQ</button>
<a href="<?= base_url('rfq') ?>" class="btn btn-outline-secondary btn-sm ml-2">Cancel</a>
<?= form_close() ?>

<style>
.rfq-drop-item { padding:6px 10px; cursor:pointer; font-size:.82rem; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; justify-content:space-between; }
.rfq-drop-item:hover, .rfq-drop-item.active { background:#e8f0fe; }
.rfq-drop-item .pname { font-weight:600; }
.rfq-drop-item .pcode { font-size:.72rem; color:#999; }
.btn-xs { padding:2px 6px; font-size:.72rem; }
</style>

<script>
var RFQ_PRODUCTS = <?php
    $map = [];
    foreach ($products as $p) {
        $map[] = [
            'id'             => (int)$p->id,
            'name'           => $p->name,
            'item_code'      => $p->item_code ?? '',
            'unit'           => $p->unit ?? 'PCS',
            'purchase_price' => (float)($p->purchase_price ?? 0),
        ];
    }
    echo json_encode($map, JSON_HEX_TAG);
?>;

var rfqRow = 0;
var rfqSelectedIdx = -1;

var mainSearch = document.getElementById('rfqMainSearch');
var mainDrop   = document.getElementById('rfqMainDrop');
var rfqStatus  = document.getElementById('rfqSearchStatus');

mainSearch.addEventListener('input', function() {
    var q = this.value.trim().toLowerCase();
    rfqSelectedIdx = -1;
    if (q.length < 1) { mainDrop.style.display = 'none'; return; }
// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
    var results = RFQ_PRODUCTS.filter(function(p) {
        return pmatch(q, p.name, p.item_code);
    }).slice(0, 15);
    if (!results.length) { mainDrop.style.display = 'none'; return; }
    mainDrop.innerHTML = results.map(function(p) {
        return '<a href="#" class="list-group-item list-group-item-action rfq-drop-item" data-id="'+p.id+'" '+
            'data-name="'+p.name.replace(/"/g,'&quot;')+'" data-code="'+(p.item_code||'')+'" '+
            'data-unit="'+p.unit+'" data-price="'+p.purchase_price+'" '+
            'onmousedown="rfqPickProduct(this);return false;">'+
            '<div><div class="pname">'+p.name+'</div><div class="pcode">'+(p.item_code||'')+'</div></div>'+
            '<div class="text-right flex-shrink-0 ml-2">'+
                (p.purchase_price > 0 ? '<span class="badge badge-light border">₹'+p.purchase_price.toFixed(2)+'</span>' : '')+
            '</div></a>';
    }).join('');
    mainDrop.style.display = 'block';
});

mainSearch.addEventListener('keydown', function(e) {
    var items = mainDrop.querySelectorAll('.rfq-drop-item');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        rfqSelectedIdx = Math.min(rfqSelectedIdx + 1, items.length - 1);
        highlightRfqDrop(items);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        rfqSelectedIdx = Math.max(rfqSelectedIdx - 1, 0);
        highlightRfqDrop(items);
    } else if (e.key === 'Escape') {
        mainDrop.style.display = 'none';
        rfqSelectedIdx = -1;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        var active = mainDrop.querySelector('.rfq-drop-item.active');
        if (active) {
            rfqPickProduct(active);
        } else if (items.length === 1) {
            rfqPickProduct(items[0]);
        } else if (items.length > 1) {
            // highlight first if nothing selected
            rfqSelectedIdx = 0;
            highlightRfqDrop(items);
        }
    }
});

function highlightRfqDrop(items) {
    items.forEach(function(el, i) {
        el.classList.toggle('active', i === rfqSelectedIdx);
    });
    if (rfqSelectedIdx >= 0 && items[rfqSelectedIdx]) {
        items[rfqSelectedIdx].scrollIntoView({block:'nearest'});
    }
}

function triggerRfqSearch() {
    mainSearch.dispatchEvent(new Event('input', {bubbles:true}));
    mainSearch.focus();
}

function rfqPickProduct(el) {
    var id    = el.getAttribute('data-id');
    var name  = el.getAttribute('data-name');
    var code  = el.getAttribute('data-code');
    var unit  = el.getAttribute('data-unit') || 'PCS';
    var price = parseFloat(el.getAttribute('data-price') || 0);
    addItemRow({id:id, name:name, code:code, unit:unit, purchase_price:price});
    mainDrop.style.display = 'none';
    mainSearch.value = '';
    rfqSelectedIdx = -1;
    rfqStatus.textContent = '✓ Added: ' + name;
    setTimeout(function(){ rfqStatus.textContent = ''; }, 2000);
    mainSearch.focus();
}

document.addEventListener('click', function(e) {
    if (!mainSearch.contains(e.target) && !mainDrop.contains(e.target)) {
        mainDrop.style.display = 'none';
    }
});

function addItemRow(prefill) {
    rfqRow++;
    var r = rfqRow;
    document.getElementById('rfqEmpty').style.display = 'none';
    var tr = document.createElement('tr');
    tr.id = 'rrow_' + r;
    var displayName = prefill ? (prefill.name + (prefill.code ? ' ['+prefill.code+']' : '')) : '';
    var unit  = prefill ? prefill.unit : 'PCS';
    var price = (prefill && prefill.purchase_price > 0) ? prefill.purchase_price.toFixed(2) : '';
    tr.innerHTML =
        '<td>'+
            '<input type="hidden" name="product_id[]" value="'+(prefill?prefill.id:'')+'">' +
            '<span class="font-weight-bold" style="font-size:.8rem;">'+displayName+'</span>' +
        '</td>' +
        '<td><input name="qty_required[]" type="number" step="0.01" min="0.01" class="form-control form-control-sm" value="1" id="rqty_'+r+'"></td>'+
        '<td><input name="unit[]" class="form-control form-control-sm" value="'+unit+'" readonly></td>'+
        '<td><input name="target_rate[]" type="number" step="0.01" class="form-control form-control-sm" value="'+price+'" placeholder="Optional"></td>'+
        '<td><input name="item_notes[]" class="form-control form-control-sm" placeholder="Spec, quality..."></td>'+
        '<td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRfqRow('+r+')"><i class="fas fa-times"></i></button></td>';
    document.getElementById('rfqItemsBody').appendChild(tr);
    updateRfqCount();
    // Focus qty so user can quickly change quantity then Tab forward
    var qty = document.getElementById('rqty_'+r);
    if (qty) { qty.focus(); qty.select(); }
}

function addBlankRow() {
    rfqRow++;
    var r = rfqRow;
    document.getElementById('rfqEmpty').style.display = 'none';
    // For blank rows add a small search input so user can still pick product
    var tr = document.createElement('tr');
    tr.id = 'rrow_' + r;
    tr.innerHTML =
        '<td>'+
            '<input type="hidden" name="product_id[]" id="rbpid_'+r+'" value="">' +
            '<div class="position-relative">' +
                '<input type="text" id="rbsearch_'+r+'" class="form-control form-control-sm" placeholder="Type to search..." autocomplete="off" ' +
                    'oninput="rbSearch(this,'+r+')" onkeydown="rbKeydown(event,'+r+')">' +
                '<div class="list-group position-absolute" id="rbdrop_'+r+'" style="z-index:9999;max-height:180px;overflow-y:auto;display:none;width:100%;top:100%;left:0;box-shadow:0 4px 12px rgba(0,0,0,.12);"></div>' +
            '</div>' +
        '</td>' +
        '<td><input name="qty_required[]" type="number" step="0.01" min="0.01" class="form-control form-control-sm" value="1"></td>'+
        '<td><input name="unit[]" id="rbunit_'+r+'" class="form-control form-control-sm" value="PCS" readonly></td>'+
        '<td><input name="target_rate[]" id="rbrate_'+r+'" type="number" step="0.01" class="form-control form-control-sm" placeholder="Optional"></td>'+
        '<td><input name="item_notes[]" class="form-control form-control-sm" placeholder="Spec, quality..."></td>'+
        '<td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRfqRow('+r+')"><i class="fas fa-times"></i></button></td>';
    document.getElementById('rfqItemsBody').appendChild(tr);
    updateRfqCount();
    document.getElementById('rbsearch_'+r).focus();
}

function rbSearch(input, r) {
    var q = input.value.trim().toLowerCase();
    var drop = document.getElementById('rbdrop_'+r);
    if (q.length < 1) { drop.style.display='none'; return; }
    var results = RFQ_PRODUCTS.filter(function(p){
        return pmatch(q, p.name, p.item_code);
    }).slice(0,12);
    if (!results.length) { drop.style.display='none'; return; }
    drop.innerHTML = results.map(function(p){
        return '<a href="#" class="list-group-item list-group-item-action rfq-drop-item" '+
            'data-name="'+p.name.replace(/"/g,'&quot;')+'" data-code="'+(p.item_code||'')+'" '+
            'data-unit="'+p.unit+'" data-price="'+p.purchase_price+'" data-id="'+p.id+'" '+
            'onmousedown="rbPick(event,'+r+',this);return false;">'+
            '<div class="pname">'+p.name+'</div><div class="pcode">'+(p.item_code||'')+'</div></a>';
    }).join('');
    drop.style.display='block';
}

function rbKeydown(e, r) {
    var drop = document.getElementById('rbdrop_'+r);
    var items = drop.querySelectorAll('.rfq-drop-item');
    var active = drop.querySelector('.active');
    var idx = Array.from(items).indexOf(active);
    if (e.key==='ArrowDown'){e.preventDefault();var n=items[idx+1]||items[0];if(active)active.classList.remove('active');if(n)n.classList.add('active');}
    else if(e.key==='ArrowUp'){e.preventDefault();var p=items[idx-1]||items[items.length-1];if(active)active.classList.remove('active');if(p)p.classList.add('active');}
    else if(e.key==='Enter'){e.preventDefault();if(active)active.dispatchEvent(new MouseEvent('mousedown',{bubbles:true}));else if(items.length===1)items[0].dispatchEvent(new MouseEvent('mousedown',{bubbles:true}));}
    else if(e.key==='Escape'||e.key==='Tab'){drop.style.display='none';}
}

function rbPick(e, r, el) {
    document.getElementById('rbpid_'+r).value   = el.getAttribute('data-id');
    document.getElementById('rbsearch_'+r).value = el.getAttribute('data-name')+(el.getAttribute('data-code')?' ['+el.getAttribute('data-code')+']':'');
    document.getElementById('rbunit_'+r).value   = el.getAttribute('data-unit')||'PCS';
    var price = parseFloat(el.getAttribute('data-price')||0);
    if(price>0) document.getElementById('rbrate_'+r).value = price.toFixed(2);
    document.getElementById('rbdrop_'+r).style.display='none';
}

function removeRfqRow(r) {
    var row = document.getElementById('rrow_'+r);
    if (row) row.remove();
    updateRfqCount();
    if (document.getElementById('rfqItemsBody').children.length === 0) {
        document.getElementById('rfqEmpty').style.display = '';
    }
}

function updateRfqCount() {
    document.getElementById('rfqRowCount').textContent = document.getElementById('rfqItemsBody').children.length;
}

// Focus search on load
mainSearch.focus();
</script>
<style>.btn-xs{padding:2px 6px;font-size:.72rem;}</style>
