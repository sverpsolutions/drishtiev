<?php // Scheme Simulator ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-play-circle text-info mr-2"></i>Scheme Simulator</h4>
        <small class="text-muted">Test scheme calculation before billing — add items and see what schemes apply</small>
    </div>
    <a href="<?= base_url('schemes') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list mr-1"></i>Schemes</a>
</div>

<form method="post" action="<?= base_url('schemes/simulate') ?>">
<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-info text-white"><i class="bi bi-person mr-1"></i>Customer &amp; Payment</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">Customer</label>
                        <select name="customer_id" class="form-control" required>
                            <option value="">-- Select Customer --</option>
                            <?php foreach ($customers as $cid => $cname): ?>
                                <option value="<?= $cid ?>" <?= ($customer_id??0)==$cid?'selected':'' ?>><?= htmlspecialchars($cname) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Payment Mode</label>
                        <select name="payment_mode" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="credit" selected>Credit</option>
                            <option value="upi">UPI</option>
                            <option value="neft">NEFT/Bank</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Scan -->
        <div class="card border-0 shadow-sm mb-2" style="border-left:4px solid #28a745 !important;">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center" style="gap:8px;">
                    <label class="font-weight-bold mb-0 text-success text-nowrap" style="font-size:.78rem;"><i class="bi bi-upc-scan mr-1"></i>Search &amp; Scan</label>
                    <div class="input-group input-group-sm flex-grow-1 position-relative">
                        <input type="text" id="simScan" class="form-control border-success" placeholder="Type product name, item code, or scan barcode & press Enter..." autocomplete="off" style="font-size:.88rem;font-weight:500;">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-success btn-sm" onclick="triggerSimSearch()"><i class="bi bi-search"></i></button>
                        </div>
                        <div id="simSearchDropdown" class="list-group position-absolute" style="z-index:9999;max-height:260px;overflow-y:auto;display:none;width:100%;top:100%;left:0;"></div>
                    </div>
                </div>
                <small id="simScanStatus" class="form-text text-muted font-weight-bold" style="font-size:.72rem;min-height:13px;margin-top:1px;"></small>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-list-ul mr-1"></i>Items</span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()"><i class="bi bi-plus"></i> Add Item</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0 table-sm">
                    <thead class="table-light"><tr><th>Product</th><th>Qty</th><th>UOM</th><th>Rate</th><th>Taxable</th><th></th></tr></thead>
                    <tbody id="simBody"></tbody>
                </table>
            </div>
        </div>

        <button type="submit" class="btn btn-info btn-lg text-white w-100"><i class="bi bi-calculator mr-2"></i>Calculate Schemes</button>
    </div>

    <!-- Result Panel -->
    <div class="col-md-4">
        <?php if ($result !== null): ?>
        <div class="card shadow-sm mb-3 border-<?= $result['total_discount'] > 0 || !empty($result['free_items']) ? 'success' : 'secondary' ?>">
            <div class="card-header bg-<?= $result['total_discount'] > 0 || !empty($result['free_items']) ? 'success' : 'secondary' ?> text-white">
                <i class="bi bi-gift mr-1"></i>Scheme Result
            </div>
            <div class="card-body">
                <?php if (empty($result['schemes_applied']) && empty($result['free_items'])): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-x-circle fs-2 d-block mb-2"></i>No schemes applicable for this cart
                    </div>
                <?php else: ?>
                    <?php
                    // Selling prices are GST-inclusive, so "Bill Total" is the gross
                    // the customer pays. The taxable value is shown underneath because
                    // that is what schemes are actually calculated on — the same figure
                    // Billing::create() feeds the engine.
                    $sim_gross = isset($gross_total) && $gross_total > 0 ? $gross_total : $bill_total;
                    $sim_net   = $sim_gross - $result['total_discount'];
                    ?>
                    <div class="mb-3 p-2 bg-light rounded">
                        <div class="text-muted small">Bill Total <span class="text-muted">(incl. GST)</span></div>
                        <div class="h5 font-weight-bold">&#8377;<?= number_format($sim_gross, 2) ?></div>
                        <div class="text-success font-weight-bold">- &#8377;<?= number_format($result['total_discount'], 2) ?> discount</div>
                        <div class="h6 font-weight-bold border-top mt-2 pt-2">Net: &#8377;<?= number_format($sim_net, 2) ?></div>
                        <div class="text-muted border-top mt-2 pt-2" style="font-size:.76rem;">
                            Taxable value &#8377;<?= number_format($bill_total, 2) ?>
                            &middot; GST &#8377;<?= number_format($sim_gross - $bill_total, 2) ?><br>
                            Schemes with a minimum bill value are checked against the
                            <strong>taxable</strong> figure, the same as at the till.
                        </div>
                    </div>

                    <?php if (!empty($result['discount_breakup'])): ?>
                    <div class="mb-3">
                        <div class="font-weight-bold mb-2">Discounts Applied:</div>
                        <?php foreach ($result['discount_breakup'] as $d): ?>
                            <div class="d-flex justify-content-between align-items-start mb-1 p-2 bg-light rounded">
                                <div>
                                    <div class="small font-weight-bold"><?= htmlspecialchars($d['scheme_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($d['note']) ?></div>
                                </div>
                                <span class="badge badge-success ml-2">&#8377;<?= number_format($d['discount'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($result['free_items'])): ?>
                    <div class="mb-3">
                        <div class="font-weight-bold mb-2">Free Items:</div>
                        <?php foreach ($result['free_items'] as $fi): ?>
                            <div class="p-2 bg-light rounded mb-1">
                                <div class="font-weight-bold small"><?= htmlspecialchars($fi['product_name']) ?></div>
                                <div class="small text-muted">Qty: <?= $fi['qty'] ?> <?= strtoupper($fi['uom']??'pcs') ?> | <?= htmlspecialchars($fi['scheme_name']) ?></div>
                                <?php if ($fi['note']??''): ?><div class="small text-primary"><?= htmlspecialchars($fi['note']) ?></div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($result['credit_notes'])): ?>
                    <div class="mb-2">
                        <div class="font-weight-bold mb-2">Credit Notes (pending):</div>
                        <?php foreach ($result['credit_notes'] as $cn): ?>
                            <div class="p-2 bg-light rounded mb-1 small">
                                <i class="bi bi-receipt mr-1"></i><?= htmlspecialchars($cn['note']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card shadow-sm border-dashed">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-calculator fs-1 d-block mb-2 opacity-25"></i>
                Add items and click <strong>Calculate Schemes</strong> to see applicable benefits
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</form>

<script>
const PRODUCTS = <?= json_encode(array_map(function($p){return['id'=>(int)$p->id,'name'=>$p->name,'item_code'=>$p->item_code??'','selling_price'=>(float)$p->selling_price,'mrp'=>(float)($p->mrp??0),'stock_qty'=>(float)($p->stock_qty??0),'unit'=>$p->unit??'PCS','gst_percent'=>(float)$p->gst_percent,'carton_size'=>(int)($p->carton_size??1)];}, $products)) ?>;

let rowCount = 0;
function addRow() {
    rowCount++;
    const rid = rowCount;
    const opts = PRODUCTS.map(p=>`<option value="${p.id}" data-price="${p.selling_price}" data-gst="${p.gst_percent}" data-crt="${p.carton_size}">${p.name}</option>`).join('');
    const html = `<tr id="simrow_${rid}">
        <td><select name="items[${rid}][product_id]" class="form-control form-control-sm" onchange="fillRow(this,${rid})" required><option value="">--Select--</option>${opts}</select></td>
        <td><input type="number" name="items[${rid}][qty]" id="qty_${rid}" class="form-control form-control-sm" value="1" min="0.001" step="0.001" style="width:70px"></td>
        <td><select name="items[${rid}][uom]" class="form-control form-control-sm" style="width:70px"><option value="pcs">PCS</option><option value="crt">CRT</option></select></td>
        <td><input type="number" name="items[${rid}][rate]" id="rate_${rid}" class="form-control form-control-sm" step="0.01" style="width:90px" readonly></td>
        <td><input type="number" name="items[${rid}][taxable]" id="tax_${rid}" class="form-control form-control-sm" step="0.01" style="width:90px" readonly></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('simrow_${rid}').remove()"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    document.getElementById('simBody').insertAdjacentHTML('beforeend', html);
}

function fillRow(sel, rid) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) return;
    document.getElementById('rate_'+rid).value = parseFloat(opt.dataset.price||0).toFixed(2);
    document.getElementById('tax_'+rid).value  = parseFloat((parseFloat(document.getElementById('qty_'+rid).value) * parseFloat(opt.dataset.price||0) / (1 + parseFloat(opt.dataset.gst||0)/100)).toFixed(2));
}

/* ══ Search & Scan — add an item by barcode scan or name/code search ══ */
function addRowWithProduct(productId) {
    addRow();
    const sel = document.querySelector('#simrow_' + rowCount + ' select[name$="[product_id]"]');
    if (!sel) return;
    for (const o of sel.options) { if (parseInt(o.value) === parseInt(productId)) { o.selected = true; break; } }
    fillRow(sel, rowCount);
}

const simScanInput      = document.getElementById('simScan');
const simSearchDropdown = document.getElementById('simSearchDropdown');
const simScanStatus     = document.getElementById('simScanStatus');
let simSelectedIdx = -1;

// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
simScanInput.addEventListener('input', function() {
    const q = this.value.trim();
    if (q.length < 1) { simSearchDropdown.style.display = 'none'; return; }
    const results = PRODUCTS.filter(p =>
        pmatch(q, p.name, p.item_code)
    ).slice(0, 12);
    if (!results.length) { simSearchDropdown.style.display = 'none'; return; }
    simSelectedIdx = -1;
    simSearchDropdown.innerHTML = results.map(p => {
        const sp  = parseFloat(p.selling_price);
        const mrp = parseFloat(p.mrp) || 0;
        const mrpHtml = mrp > sp ? `<span style="text-decoration:line-through;opacity:.65;font-size:.75rem;">₹${mrp.toFixed(2)}</span> ` : '';
        return `<a href="#" class="list-group-item list-group-item-action py-1 px-2 sim-search-item" style="font-size:.82rem;"
            data-id="${p.id}" onclick="addProductFromSimSearch(${p.id}); return false;">
            <div class="d-flex align-items-center justify-content-between">
                <div style="min-width:0;">
                    <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${p.name}</div>
                    <div style="font-size:.72rem;opacity:.7;">${p.item_code||''}</div>
                </div>
                <div style="text-align:right;flex-shrink:0;margin-left:8px;">
                    <span class="badge badge-${p.stock_qty>10?'success':p.stock_qty>0?'warning':'danger'}">${p.stock_qty} ${p.unit}</span>
                    <div style="margin-top:2px;">${mrpHtml}<strong>₹${sp.toFixed(2)}</strong></div>
                </div>
            </div>
        </a>`;
    }).join('');
    simSearchDropdown.style.display = 'block';
});

simScanInput.addEventListener('keydown', function(e) {
    const links = document.querySelectorAll('.sim-search-item');

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (links.length > 0) { simSelectedIdx = (simSelectedIdx + 1) % links.length; highlightSimItem(links); }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (links.length > 0) { simSelectedIdx = (simSelectedIdx - 1 + links.length) % links.length; highlightSimItem(links); }
    } else if (e.key === 'Escape') {
        simSearchDropdown.style.display = 'none';
        simSelectedIdx = -1;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const activeLink = document.querySelector('.sim-search-item.active-sim-item');
        if (activeLink) { addProductFromSimSearch(activeLink.getAttribute('data-id')); return; }

        const q = this.value.trim();
        if (!q) return;

        simScanStatus.innerHTML = '<i class="bi bi-arrow-repeat"></i> Checking barcode/code...';
        simScanStatus.className = 'form-text text-muted';

        // Reuse the billing barcode/PLU lookup endpoint so a real scanner works here too
        fetch('<?= base_url("billing/get_by_barcode_ajax") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'barcode=' + encodeURIComponent(q)
        })
        .then(r => r.json())
        .then(data => {
            const findByCode = (code) => PRODUCTS.find(x => x.item_code && code && x.item_code.toLowerCase() === code.toLowerCase());

            if (data.success && data.mode === 'single' && data.plu) {
                const match = findByCode(data.plu.item_code) || PRODUCTS.find(x => x.name === data.plu.name);
                if (match) {
                    addProductFromSimSearch(match.id);
                    simScanStatus.innerHTML = '✓ Found & Added: <strong>' + data.plu.name + '</strong>';
                    simScanStatus.className = 'form-text text-success font-weight-bold';
                } else {
                    simScanStatus.innerHTML = '✗ Matched a batch but not in scheme product list: ' + data.plu.name;
                    simScanStatus.className = 'form-text text-danger font-weight-bold';
                }
            } else if (data.success && data.mode === 'multiple' && data.plus && data.plus.length) {
                const first = data.plus[0];
                const match = findByCode(first.item_code) || PRODUCTS.find(x => x.name === data.item_name);
                if (match) {
                    addProductFromSimSearch(match.id);
                    simScanStatus.innerHTML = '✓ Multiple batches found — added: <strong>' + match.name + '</strong>';
                    simScanStatus.className = 'form-text text-warning font-weight-bold';
                } else {
                    simScanStatus.innerHTML = '✗ Matched batches but not in scheme product list: ' + (data.item_name || q);
                    simScanStatus.className = 'form-text text-danger font-weight-bold';
                }
            } else if (data.success && (data.product || (data.mode === 'legacy' && data.product))) {
                addProductFromSimSearch(data.product.id);
                simScanStatus.innerHTML = '✓ Found & Added: <strong>' + data.product.name + '</strong>';
                simScanStatus.className = 'form-text text-success font-weight-bold';
            } else {
                const localMatch = findByCode(q);
                if (localMatch) {
                    addProductFromSimSearch(localMatch.id);
                    simScanStatus.innerHTML = '✓ Code Found & Added: <strong>' + localMatch.name + '</strong>';
                    simScanStatus.className = 'form-text text-success font-weight-bold';
                } else if (activeLink) {
                    addProductFromSimSearch(activeLink.getAttribute('data-id'));
                } else if (links.length > 0) {
                    addProductFromSimSearch(links[0].getAttribute('data-id'));
                } else {
                    simScanStatus.innerHTML = '✗ Product / Barcode not found: ' + q;
                    simScanStatus.className = 'form-text text-danger font-weight-bold';
                }
            }
        })
        .catch(() => {
            simScanStatus.innerHTML = '✗ Error during lookup.';
            simScanStatus.className = 'form-text text-danger font-weight-bold';
        });
    }
});

function highlightSimItem(links) {
    links.forEach((link, idx) => {
        if (idx === simSelectedIdx) {
            link.style.setProperty('background-color', '#1e40af', 'important');
            link.style.setProperty('color', '#ffffff', 'important');
            link.classList.add('active-sim-item');
            link.scrollIntoView({ block: 'nearest' });
        } else {
            link.style.removeProperty('background-color');
            link.style.removeProperty('color');
            link.classList.remove('active-sim-item');
        }
    });
}

function addProductFromSimSearch(productId) {
    simSearchDropdown.style.display = 'none';
    simScanInput.value = '';
    simSelectedIdx = -1;
    addRowWithProduct(productId);
    simScanInput.focus();
}

document.addEventListener('click', function(e) {
    if (!simSearchDropdown.contains(e.target) && e.target !== simScanInput) {
        simSearchDropdown.style.display = 'none';
    }
});

function triggerSimSearch() {
    simScanInput.dispatchEvent(new Event('input'));
}

// Re-populate if POST result
<?php if (!empty($sim_items)): ?>
<?php foreach ($sim_items as $i => $item): ?>
addRow();
(function() {
    const sel = document.querySelector('#simrow_'+rowCount+' select[name$="[product_id]"]');
    for(let o of sel.options) { if(parseInt(o.value)===<?= (int)$item['product_id'] ?>){o.selected=true;break;} }
    document.getElementById('qty_'+rowCount).value  = <?= (float)$item['qty'] ?>;
    document.getElementById('rate_'+rowCount).value = <?= (float)$item['rate'] ?>;
    document.getElementById('tax_'+rowCount).value  = <?= (float)$item['taxable_amt'] ?>;
})();
<?php endforeach; ?>
<?php else: ?>
addRow();
<?php endif; ?>
</script>
