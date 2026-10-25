<?php // FILE: application/views/plu/create.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-plus-circle text-success mr-1"></i> Create New PLU</h5>
        <small class="text-muted"><a href="<?= base_url('plu') ?>">PLU Master</a> / Create</small>
    </div>
    <a href="<?= base_url('plu') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<?php if (!empty($existing_plus)): ?>
<div class="alert alert-info">
    <strong><i class="fas fa-info-circle"></i> Existing PLUs for this product:</strong>
    <div class="mt-2">
        <?php foreach ($existing_plus as $ep): ?>
        <span class="badge badge-primary mr-1 p-2">
            <?= htmlspecialchars($ep->plu_code) ?> — MRP ₹<?= number_format($ep->mrp, 2) ?>
            (Stock: <?= number_format($ep->stock_qty, 0) ?>)
        </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?= form_open('plu/create', ['id' => 'pluForm']) ?>
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-dark text-white py-2" style="font-size:.85rem;">
                <i class="fas fa-tag mr-1"></i> PLU Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="font-weight-bold">Product / Item Master <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="text" id="productSearch" class="form-control border-success" placeholder="Type product name or item code..." autocomplete="off"
                                value="<?= $selected_product ? htmlspecialchars($selected_product->name . ' (' . ($selected_product->item_code ?? '') . ')') : '' ?>">
                            <input type="hidden" name="product_id" id="product_id" value="<?= $selected_product ? (int)$selected_product->id : '' ?>">
                            <div id="productSearchDropdown" class="list-group position-absolute" style="z-index:9999;max-height:280px;overflow-y:auto;display:none;width:100%;top:100%;left:0;"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Barcode</label>
                            <input type="text" name="barcode" id="barcode" class="form-control" placeholder="Scan or type barcode" autocomplete="off">
                            <small class="text-muted">Barcode for this PLU (can be same as other PLUs of the same product)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>EAN Code</label>
                            <input type="text" id="ean_code" class="form-control" placeholder="-" readonly style="background:#f4f4f4;">
                            <small class="text-muted">Product's master EAN code (reference only)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Batch No</label>
                            <input type="text" name="batch_no" class="form-control" placeholder="e.g. BATCH-A, LOT-2024">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="font-weight-bold text-danger">MRP <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" name="mrp" id="mrp" class="form-control font-weight-bold" step="0.01" min="0" required onchange="checkMrpDuplicate()">
                            </div>
                            <div id="mrp_warning" class="text-warning small mt-1" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Purchase Rate <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" name="purchase_rate" id="purchase_rate" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Selling Rate <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" name="selling_rate" id="selling_rate" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Landing Cost</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" name="landing_cost" id="landing_cost" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>GST %</label>
                            <input type="number" name="gst_percent" id="gst_percent" class="form-control" step="0.01" min="0" max="100" value="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Mfg Date</label>
                            <input type="date" name="mfg_date" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Supplier</label>
                            <select name="supplier_id" class="form-control">
                                <option value="">-- None --</option>
                                <?php foreach ($suppliers as $sid => $sname): ?>
                                <option value="<?= $sid ?>"><?= htmlspecialchars($sname) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Location / Warehouse</label>
                            <select name="location_id" class="form-control">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $loc): ?>
                                <option value="<?= $loc->id ?>"><?= htmlspecialchars($loc->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success btn-lg px-5">
            <i class="fas fa-save mr-1"></i> Create PLU
        </button>
        <a href="<?= base_url('plu') ?>" class="btn btn-outline-secondary btn-lg ml-2">Cancel</a>
    </div>

    <div class="col-md-4">
        <div class="card border-warning shadow-sm">
            <div class="card-header bg-warning py-2 font-weight-bold" style="font-size:.85rem;">
                <i class="fas fa-lightbulb mr-1"></i> PLU Guide
            </div>
            <div class="card-body small">
                <p><strong>When to create a new PLU?</strong></p>
                <ul class="pl-3">
                    <li>Same product, <strong>different MRP</strong></li>
                    <li>New supplier batch with different purchase rate</li>
                    <li>Different expiry date batch</li>
                </ul>
                <hr>
                <p><strong>During Billing:</strong></p>
                <ul class="pl-3">
                    <li>If 1 PLU → auto-added to bill</li>
                    <li>If multiple PLUs → popup to choose price</li>
                    <li>Stock deducts from <em>selected PLU only</em></li>
                </ul>
                <hr>
                <p class="text-danger font-weight-bold">DO NOT create a new product for a different MRP!</p>
                <p>One product = one item master. MRP differences = PLUs.</p>
            </div>
        </div>

        <div id="product_info_card" class="card border-0 shadow-sm mt-3" style="display:none;">
            <div class="card-header bg-info text-white py-2" style="font-size:.85rem;">Product Info</div>
            <div class="card-body small" id="product_info_body"></div>
        </div>
    </div>
</div>
<?= form_close() ?>

<script>
const PLU_PRODUCTS = <?= json_encode(array_map(function($p) {
    return [
        'id'             => (int)$p->id,
        'name'           => (string)($p->name ?? ''),
        'item_code'      => (string)($p->item_code ?? ''),
        'gst_percent'    => (float)($p->gst_percent ?? 0),
        'purchase_price' => (float)($p->purchase_price ?? 0),
        'selling_price'  => (float)($p->selling_price ?? 0),
        'mrp'            => (float)($p->mrp ?? 0),
        'ean'            => (string)($p->pcs_barcode ?? ''),
    ];
}, $products), JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE) ?? '[]' ?>;

function loadProductInfo(pid) {
    pid = parseInt(pid);
    var p = PLU_PRODUCTS.find(function(x) { return x.id === pid; });
    if (!p) { $('#product_info_card').hide(); return; }

    document.getElementById('gst_percent').value   = p.gst_percent;
    document.getElementById('purchase_rate').value = p.purchase_price;
    document.getElementById('selling_rate').value  = p.selling_price;
    document.getElementById('mrp').value           = p.mrp;
    document.getElementById('landing_cost').value  = p.purchase_price;
    document.getElementById('ean_code').value      = p.ean || '-';

    document.getElementById('product_info_body').innerHTML =
        '<b>Default GST:</b> ' + p.gst_percent + '%<br>' +
        '<b>Default Purchase:</b> ₹' + p.purchase_price.toFixed(2) + '<br>' +
        '<b>Default Selling:</b> ₹' + p.selling_price.toFixed(2) + '<br>' +
        '<b>Default MRP:</b> ₹' + p.mrp.toFixed(2) + '<br>' +
        '<b>EAN Code:</b> ' + (p.ean || '-');
    document.getElementById('product_info_card').style.display = 'block';

    checkMrpDuplicate();
}

/* ── Smart product search (same pattern as Billing item search) ── */
const productSearchInput    = document.getElementById('productSearch');
const productSearchDropdown = document.getElementById('productSearchDropdown');
let pluSelectedIdx = -1;

productSearchInput.addEventListener('input', function() {
    var q = this.value.trim().toLowerCase();
    document.getElementById('product_id').value = '';
    document.getElementById('product_info_card').style.display = 'none';
    if (q.length < 1) { productSearchDropdown.style.display = 'none'; productSearchDropdown.innerHTML = ''; return; }

// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
    var results = PLU_PRODUCTS.filter(function(p) {
        return pmatch(q, p.name, p.item_code);
    }).slice(0, 15);

    if (!results.length) { productSearchDropdown.style.display = 'none'; productSearchDropdown.innerHTML = ''; return; }

    pluSelectedIdx = -1;
    productSearchDropdown.innerHTML = results.map(function(p) {
        return '<a href="#" class="list-group-item list-group-item-action py-1 px-2 plu-search-item" style="font-size:.85rem;" data-id="' + p.id + '" onclick="selectPluProduct(' + p.id + '); return false;">'
            + '<div class="d-flex align-items-center justify-content-between">'
            +   '<div style="min-width:0;">'
            +     '<div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + p.name + '</div>'
            +     '<div style="font-size:.72rem;opacity:.7;">' + (p.item_code || '') + '</div>'
            +   '</div>'
            +   '<div class="text-right flex-shrink-0 ml-2"><strong>₹' + p.mrp.toFixed(2) + '</strong></div>'
            + '</div></a>';
    }).join('');
    productSearchDropdown.style.display = 'block';
});

productSearchInput.addEventListener('keydown', function(e) {
    var items = document.querySelectorAll('.plu-search-item');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (items.length) { pluSelectedIdx = (pluSelectedIdx + 1) % items.length; highlightPluItem(items); }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (items.length) { pluSelectedIdx = (pluSelectedIdx - 1 + items.length) % items.length; highlightPluItem(items); }
    } else if (e.key === 'Escape') {
        productSearchDropdown.style.display = 'none';
        pluSelectedIdx = -1;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        var active = document.querySelector('.plu-search-item.active-plu-item');
        if (active) { selectPluProduct(parseInt(active.getAttribute('data-id'))); }
        else if (items.length) { selectPluProduct(parseInt(items[0].getAttribute('data-id'))); }
    }
});

function highlightPluItem(items) {
    items.forEach(function(el, idx) {
        if (idx === pluSelectedIdx) {
            el.style.setProperty('background-color', '#1e40af', 'important');
            el.style.setProperty('color', '#ffffff', 'important');
            el.classList.add('active-plu-item');
            el.scrollIntoView({ block: 'nearest' });
        } else {
            el.style.removeProperty('background-color');
            el.style.removeProperty('color');
            el.classList.remove('active-plu-item');
        }
    });
}

function selectPluProduct(pid) {
    var p = PLU_PRODUCTS.find(function(x) { return x.id === pid; });
    if (!p) return;
    document.getElementById('product_id').value = pid;
    productSearchInput.value = p.name + (p.item_code ? ' (' + p.item_code + ')' : '');
    productSearchDropdown.style.display = 'none';
    productSearchDropdown.innerHTML = '';
    pluSelectedIdx = -1;
    loadProductInfo(pid);
}

document.addEventListener('click', function(e) {
    if (!productSearchDropdown.contains(e.target) && e.target !== productSearchInput) {
        productSearchDropdown.style.display = 'none';
    }
});

document.getElementById('pluForm').addEventListener('submit', function(e) {
    if (!document.getElementById('product_id').value) {
        e.preventDefault();
        alert('Please select a product from the search dropdown.');
        productSearchInput.focus();
    }
});

// csrf_regenerate is ON — every AJAX POST rotates the CSRF cookie server-side,
// which leaves the hidden token baked into #pluForm at page-load stale. Resync
// it from the cookie after each AJAX call so the real "Create PLU" submit
// doesn't get rejected with "The action you have requested is not allowed."
function getCookie(name) {
    var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : null;
}
function syncCsrfToken() {
    var val = getCookie('<?= $this->config->item('csrf_cookie_name') ?>');
    if (val) {
        var el = document.querySelector('input[name="<?= $this->security->get_csrf_token_name() ?>"]');
        if (el) el.value = val;
    }
}

function checkMrpDuplicate() {
    var pid = document.getElementById('product_id').value;
    var mrp = document.getElementById('mrp').value;
    if (!pid || !mrp) return;
    $.ajax({
        url: '<?= base_url('plu/check_mrp_ajax') ?>',
        method: 'POST',
        data: { product_id: pid, mrp: mrp, <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>' },
        complete: syncCsrfToken,
        success: function(r) {
            if (r.exists) {
                document.getElementById('mrp_warning').innerHTML =
                    '<i class="fas fa-exclamation-triangle"></i> PLU <strong>' + r.plu_code + '</strong> already has MRP ₹' + mrp + '. Edit that PLU instead.';
                document.getElementById('mrp_warning').style.display = 'block';
            } else {
                document.getElementById('mrp_warning').style.display = 'none';
            }
        }
    });
}

<?php if ($selected_product): ?>
document.addEventListener('DOMContentLoaded', function() {
    loadProductInfo(<?= (int)$selected_product->id ?>);
});
<?php endif; ?>
</script>
