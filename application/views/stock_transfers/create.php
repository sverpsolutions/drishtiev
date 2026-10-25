<?php // FILE: application/views/stock_transfers/create.php
// Active location = the location the user is currently working in (switcher or fixed)
$active_loc_id   = isset($active_loc_id)   ? (int)$active_loc_id   : 0;
$active_loc_name = isset($active_loc_name) ? $active_loc_name       : 'Unknown Location';
$auto_load_sales = !empty($auto_load_sales);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0">Stock Transfer</h5>
        <small class="text-muted"><a href="<?= base_url('stock_transfers') ?>">Stock Transfers</a> / New Request</small>
    </div>
    <a href="<?= base_url('stock_transfers') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<!-- Transfer Direction Toggle -->
<div class="mb-3">
    <div class="btn-group btn-group-lg w-100" role="group">
        <button type="button" class="btn btn-danger active" id="btnOut" onclick="setDirection('out')">
            <i class="fas fa-arrow-right mr-2"></i><strong>Transfer OUT</strong>
            <div class="small font-weight-normal">Send stock from <em><?= htmlspecialchars($active_loc_name) ?></em> to another location</div>
        </button>
        <button type="button" class="btn btn-outline-success" id="btnIn" onclick="setDirection('in')">
            <i class="fas fa-arrow-left mr-2"></i><strong>Transfer IN</strong>
            <div class="small font-weight-normal">Receive stock into <em><?= htmlspecialchars($active_loc_name) ?></em> from another location</div>
        </button>
    </div>
</div>

<form method="POST" action="<?= base_url('stock_transfers/create') ?>" id="transferForm">
<input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
<input type="hidden" name="save_action" id="saveAction" value="draft">
<input type="hidden" name="from_location_id" id="fromLocationHidden" value="<?= $active_loc_id ?>">
<input type="hidden" name="to_location_id"   id="toLocationHidden"   value="">

<div class="row">
<div class="col-md-8">

    <!-- Transfer Details Card -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2">
            <strong id="directionLabel"><i class="fas fa-arrow-right text-danger mr-1"></i> Transfer OUT Details</strong>
        </div>
        <div class="card-body">
            <div class="form-row">

                <!-- FROM -->
                <div class="form-group col-md-6">
                    <label class="small font-weight-bold">From Location</label>
                    <!-- OUT: FROM is locked to active location -->
                    <div id="fromFixed" class="form-control bg-light font-weight-bold text-danger" style="cursor:default;">
                        <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($active_loc_name) ?>
                        <span class="badge badge-danger ml-1 small">Your Location</span>
                    </div>
                    <!-- IN: FROM is selectable (where stock comes FROM) -->
                    <select id="fromSelect" name="_from_select" class="form-control d-none" onchange="onFromSelectChange()">
                        <option value="">-- Select Source Location --</option>
                        <?php foreach ($locations as $loc): ?>
                            <?php if ((int)$loc->id === $active_loc_id) continue; // skip own location ?>
                            <option value="<?= $loc->id ?>" data-name="<?= htmlspecialchars($loc->name) ?>">
                                <?= htmlspecialchars($loc->name) ?> (<?= ucfirst($loc->type) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- TO -->
                <div class="form-group col-md-6">
                    <label class="small font-weight-bold">To Location</label>
                    <!-- OUT: TO is selectable (where stock goes TO) -->
                    <select id="toSelect" name="_to_select" class="form-control" onchange="onToSelectChange()">
                        <option value="">-- Select Destination --</option>
                        <?php foreach ($locations as $loc): ?>
                            <?php if ((int)$loc->id === $active_loc_id) continue; // skip own location ?>
                            <option value="<?= $loc->id ?>" data-name="<?= htmlspecialchars($loc->name) ?>">
                                <?= htmlspecialchars($loc->name) ?> (<?= ucfirst($loc->type) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- IN: TO is locked to active location -->
                    <div id="toFixed" class="form-control bg-light font-weight-bold text-success d-none" style="cursor:default;">
                        <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($active_loc_name) ?>
                        <span class="badge badge-success ml-1 small">Your Location</span>
                    </div>
                </div>

            </div>

            <div id="approvalNotice" class="alert alert-warning py-2 small mb-2 d-none">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                This transfer requires <strong>owner approval</strong> before stock moves.
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Requisition Date</label>
                    <input type="date" id="requisitionDate" name="requisition_date" class="form-control form-control-sm"
                           value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Reason</label>
                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Replenish outlet stock">
                </div>
                <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Notes</label>
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional notes">
                </div>
            </div>
        </div>
    </div>

    <!-- Load from GRN (Transfer OUT only) -->
    <div class="card shadow-sm border-0 mb-2" id="grnLoadCard">
        <div class="card-body py-2">
            <label class="small font-weight-bold mb-1"><i class="fas fa-file-invoice text-success mr-1"></i> Load Items from GRN</label>
            <div class="small text-muted mb-1">Received stock at <strong><?= htmlspecialchars($active_loc_name) ?></strong> that needs to move on — find the GRN and pull in every line at once.</div>
            <div class="input-group input-group-sm position-relative">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-success text-white"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" id="grnSearch" class="form-control border-success"
                       placeholder="Type GRN number or supplier name..." autocomplete="off">
                <div id="grnDropdown" class="list-group position-absolute" style="z-index:9999;max-height:220px;overflow-y:auto;display:none;min-width:100%;top:100%;left:0;"></div>
            </div>
            <div id="grnPreview" class="mt-2 d-none">
                <div class="alert alert-light border py-2 mb-2 small">
                    <strong id="grnPreviewNo"></strong> — <span id="grnPreviewSupplier"></span>
                    <span class="text-muted">(<span id="grnPreviewDate"></span>)</span>
                    <br><span id="grnPreviewCount" class="text-muted"></span>
                </div>
                <button type="button" class="btn btn-sm btn-success" onclick="loadAllGrnItems()">
                    <i class="fas fa-download mr-1"></i> Load All Items into Transfer
                </button>
            </div>
        </div>
    </div>

    <!-- Load Last Day Sales — loads sales from the destination (TO) location -->
    <div class="card shadow-sm border-0 mb-2" id="salesLoadCard">
        <div class="card-body py-2">
            <label class="small font-weight-bold mb-1"><i class="fas fa-chart-bar text-info mr-1"></i> Load Previous Day Sales</label>
            <div class="small text-muted mb-2">Auto-fill items sold at <strong id="salesLocLabel">destination</strong> on the day before the requisition date as suggested quantities.</div>
            <div id="salesSummary" class="d-none mb-2">
                <div class="alert alert-info border py-2 mb-0 small">
                    <i class="fas fa-info-circle mr-1"></i>
                    Sales Date: <strong id="salesSumDate"></strong> &middot;
                    Items: <strong id="salesSumItems">0</strong> &middot;
                    Units: <strong id="salesSumUnits">0</strong>
                </div>
            </div>
            <div class="d-flex">
                <button type="button" class="btn btn-sm btn-info mr-2" id="btnLoadSales" onclick="loadPrevDaySales()">
                    <i class="fas fa-download mr-1"></i> Load Last Day Sales
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btnClearSales" onclick="clearSalesSuggestions()">
                    <i class="fas fa-eraser mr-1"></i> Clear Suggestions
                </button>
                <span id="salesReportBtns" class="d-none">
                    <button type="button" class="btn btn-sm btn-outline-dark ml-2" onclick="openSalesReport('print')" title="Print last day sales, sorted by rack">
                        <i class="fas fa-print mr-1"></i> Print
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success ml-1" onclick="openSalesReport('excel')" title="Excel export, sorted by rack">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                </span>
            </div>
        </div>
    </div>

    <!-- Product Search -->
    <div class="card shadow-sm border-0 mb-2">
        <div class="card-body py-2">
            <label class="small font-weight-bold mb-1"><i class="fas fa-search text-primary mr-1"></i> Search &amp; Add Product</label>
            <div class="input-group input-group-sm">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-primary text-white"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" id="productSearch" class="form-control border-primary"
                       placeholder="Type product name or item code to search..." autocomplete="off">
                <div class="input-group-append">
                    <button type="button" class="btn btn-primary" onclick="triggerSearch()">Search</button>
                </div>
            </div>
            <div id="searchDropdown" class="list-group position-absolute" style="z-index:9999;max-height:220px;overflow-y:auto;display:none;min-width:400px;"></div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <strong>Items to Transfer <span class="badge badge-secondary ml-1" id="itemCount">0</span></strong>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0" onclick="addEmptyRow()">
                <i class="fas fa-plus mr-1"></i> Add Row
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="itemsTable">
                <thead class="thead-dark" style="white-space:normal; font-size:.78rem;">
                    <tr>
                        <th style="width:18%">Product</th>
                        <th style="width:7%" id="stockColHeader">Stock at <span id="stockColLoc"><?= htmlspecialchars($active_loc_name) ?></span></th>
                        <th style="width:7%" id="destStockColHeader">Stock at <span id="destStockColLoc">Dest</span></th>
                        <th style="width:7%" class="sales-col d-none">Prev Day Sales</th>
                        <th style="width:7%" class="sales-col d-none">Suggested</th>
                        <th style="width:10%">Final Qty</th>
                        <th style="width:5%">Unit</th>
                        <th style="width:18%">Remarks</th>
                        <th style="width:4%"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <tr id="noItemsRow">
                        <td colspan="9" class="text-center text-muted py-3">Search a product above, load last day sales, or click "Add Row"</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /col-md-8 -->

<div class="col-md-4">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2"><strong>Summary</strong></div>
        <div class="card-body">
            <div class="mb-2 p-2 rounded" id="summaryBox" style="background:#fff3cd;">
                <div class="small text-muted mb-1">Transfer Route:</div>
                <div class="font-weight-bold" id="summaryRoute">
                    <span class="text-danger"><?= htmlspecialchars($active_loc_name) ?></span>
                    <i class="fas fa-arrow-right mx-1"></i>
                    <span class="text-muted" id="summaryDest">— select destination —</span>
                </div>
                <span id="summaryBadge" class="badge badge-danger mt-1">Transfer OUT</span>
            </div>
            <div class="d-flex justify-content-between mb-2 mt-2">
                <span class="text-muted small">Total Items:</span>
                <strong id="summaryCount">0</strong>
            </div>
            <hr>
            <button type="button" class="btn btn-outline-secondary btn-block mb-2" onclick="submitAs('draft')">
                <i class="fas fa-save mr-1"></i> Save as Draft
                <div class="small font-weight-normal">Save without submitting</div>
            </button>
            <button type="button" class="btn btn-warning btn-block" onclick="submitAs('pending')">
                <i class="fas fa-paper-plane mr-1"></i> Submit for Authorization
                <div class="small font-weight-normal">Send to owner for approval</div>
            </button>
            <hr>
            <a href="<?= base_url('stock_transfers') ?>" class="btn btn-light btn-block btn-sm">Cancel</a>
        </div>
    </div>
</div>

</div><!-- /row -->
</form>

<script>
const allProducts    = <?= json_encode(array_values($products)) ?>;
const stockAjaxUrl   = '<?= base_url('stock_transfers/get_product_stock_ajax') ?>';
const salesAjaxUrl   = '<?= base_url('stock_transfers/get_prev_day_sales_ajax') ?>';
const activeLocId    = <?= (int)$active_loc_id ?>;
const activeLocName  = <?= json_encode($active_loc_name) ?>;
const autoLoadSales  = <?= $auto_load_sales ? 'true' : 'false' ?>;
let rowCount = 0;
let currentDirection = 'out';
let stockCheckLocId  = activeLocId;
let salesColumnsVisible = false;

/* ── Direction Toggle ─────────────────────────────────── */
function setDirection(dir) {
    currentDirection = dir;

    const fromFixed  = document.getElementById('fromFixed');
    const fromSelect = document.getElementById('fromSelect');
    const toFixed    = document.getElementById('toFixed');
    const toSelect   = document.getElementById('toSelect');

    if (dir === 'out') {
        // FROM = our location (locked), TO = select destination
        fromFixed.classList.remove('d-none');
        fromSelect.classList.add('d-none');
        toFixed.classList.add('d-none');
        toSelect.classList.remove('d-none');
        toSelect.value = '';

        document.getElementById('fromLocationHidden').value = activeLocId;
        document.getElementById('toLocationHidden').value   = '';

        stockCheckLocId = activeLocId; // show stock at OUR location (what we can send)
        document.getElementById('stockColLoc').textContent = activeLocName;
        document.getElementById('destStockColLoc').textContent = '— select —';

        // Summary
        document.getElementById('summaryBox').style.background = '#fff3cd';
        document.getElementById('summaryRoute').querySelector('.text-danger').textContent = activeLocName;
        document.getElementById('summaryBadge').className = 'badge badge-danger mt-1';
        document.getElementById('summaryBadge').textContent = 'Transfer OUT';
        document.getElementById('summaryDest').textContent = '— select destination —';
        document.getElementById('summaryDest').className = 'text-muted';

        // Label
        document.getElementById('directionLabel').innerHTML =
            '<i class="fas fa-arrow-right text-danger mr-1"></i> Transfer OUT — Stock leaving <strong>' + activeLocName + '</strong>';

        // Buttons
        document.getElementById('btnOut').classList.add('btn-danger');
        document.getElementById('btnOut').classList.remove('btn-outline-danger');
        document.getElementById('btnIn').classList.remove('btn-success');
        document.getElementById('btnIn').classList.add('btn-outline-success');

        // Load-from-GRN only makes sense when sending stock out of our own location
        document.getElementById('grnLoadCard').classList.remove('d-none');

    } else {
        // TO = our location (locked), FROM = select source
        fromFixed.classList.add('d-none');
        fromSelect.classList.remove('d-none');
        fromSelect.value = '';
        toFixed.classList.remove('d-none');
        toSelect.classList.add('d-none');

        document.getElementById('fromLocationHidden').value = '';
        document.getElementById('toLocationHidden').value   = activeLocId;

        stockCheckLocId = 0; // will update when source is selected
        document.getElementById('stockColLoc').textContent = '— source —';
        document.getElementById('destStockColLoc').textContent = activeLocName;

        // Summary
        document.getElementById('summaryBox').style.background = '#d4edda';
        document.getElementById('summaryRoute').querySelector('.text-danger').textContent = '— select source —';
        document.getElementById('summaryBadge').className = 'badge badge-success mt-1';
        document.getElementById('summaryBadge').textContent = 'Transfer IN';
        document.getElementById('summaryDest').textContent = activeLocName;
        document.getElementById('summaryDest').className = 'text-success font-weight-bold';

        // Label
        document.getElementById('directionLabel').innerHTML =
            '<i class="fas fa-arrow-left text-success mr-1"></i> Transfer IN — Stock arriving at <strong>' + activeLocName + '</strong>';

        // Buttons
        document.getElementById('btnIn').classList.add('btn-success');
        document.getElementById('btnIn').classList.remove('btn-outline-success');
        document.getElementById('btnOut').classList.remove('btn-danger');
        document.getElementById('btnOut').classList.add('btn-outline-danger');

        document.getElementById('grnLoadCard').classList.add('d-none');
    }

    resetGrnPicker();
    clearAllRows();
    updateSalesLocLabel();
}

function updateSalesLocLabel() {
    const toVal = document.getElementById('toLocationHidden').value;
    let locName = '';
    if (currentDirection === 'out') {
        const sel = document.getElementById('toSelect');
        const opt = sel.selectedOptions[0];
        locName = (opt && toVal) ? (opt.dataset.name || opt.textContent.trim()) : '';
    } else {
        locName = activeLocName;
    }
    document.getElementById('salesLocLabel').textContent = locName || 'destination';
}

function onToSelectChange() {
    const sel  = document.getElementById('toSelect');
    const opt  = sel.selectedOptions[0];
    const val  = sel.value;
    const name = opt ? opt.dataset.name || opt.textContent.trim() : '';
    document.getElementById('toLocationHidden').value = val;
    document.getElementById('summaryDest').textContent = name || '— select destination —';
    document.getElementById('destStockColLoc').textContent = name || 'Dest';
    updateSalesLocLabel();
    refreshStockInRows();
    refreshDestStockInRows();
}

function onFromSelectChange() {
    const sel  = document.getElementById('fromSelect');
    const opt  = sel.selectedOptions[0];
    const val  = sel.value;
    const name = opt ? opt.dataset.name || opt.textContent.trim() : '';
    document.getElementById('fromLocationHidden').value = val;
    // For Transfer IN, show stock at the SOURCE location
    stockCheckLocId = parseInt(val) || 0;
    document.getElementById('stockColLoc').textContent = name || '— source —';
    // Update summary FROM
    document.getElementById('summaryRoute').querySelector('.text-danger').textContent = name || '— select source —';
    refreshStockInRows();
}

/* ── Load from GRN ─────────────────────────────────────── */
const grnSearchInput = document.getElementById('grnSearch');
const grnDropdown    = document.getElementById('grnDropdown');
let grnItemsCache = null;
let grnSearchTimer = null;

grnSearchInput.addEventListener('input', function() {
    const q = this.value.trim();
    clearTimeout(grnSearchTimer);
    if (q.length < 1) { grnDropdown.style.display = 'none'; return; }
    grnSearchTimer = setTimeout(function() {
        fetch('<?= base_url("stock_transfers/search_grn_ajax") ?>?q=' + encodeURIComponent(q) + '&location_id=' + activeLocId,
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(rows => {
                grnDropdown.innerHTML = '';
                if (!rows.length) {
                    grnDropdown.innerHTML = '<div class="list-group-item py-1 px-2 text-muted small">No accepted GRNs found for this location.</div>';
                    grnDropdown.style.display = 'block';
                    return;
                }
                rows.forEach(g => {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className = 'list-group-item list-group-item-action py-1 px-2';
                    a.style.fontSize = '.82rem';
                    a.innerHTML = '<strong>' + escHtml(g.grn_number) + '</strong>'
                        + '<span class="text-muted ml-2">' + escHtml(g.supplier_name || '') + '</span>'
                        + '<span class="float-right text-muted small">' + escHtml(g.received_date) + ' · ' + g.item_count + ' item(s)</span>';
                    a.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        grnSearchInput.value = g.grn_number;
                        grnDropdown.style.display = 'none';
                        selectGrn(g.id);
                    });
                    grnDropdown.appendChild(a);
                });
                grnDropdown.style.display = 'block';
            });
    }, 250);
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('#grnSearch') && !e.target.closest('#grnDropdown')) {
        grnDropdown.style.display = 'none';
    }
});

function selectGrn(grnId) {
    fetch('<?= base_url("stock_transfers/get_grn_items_ajax/") ?>' + grnId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Could not load that GRN.');
                return;
            }
            grnItemsCache = data.items;
            document.getElementById('grnPreviewNo').textContent = data.grn.grn_number;
            document.getElementById('grnPreviewSupplier').textContent = data.grn.supplier_name || '—';
            document.getElementById('grnPreviewDate').textContent = data.grn.received_date;
            document.getElementById('grnPreviewCount').textContent = data.items.length + ' item(s) received';
            document.getElementById('grnPreview').classList.remove('d-none');
        });
}

function loadAllGrnItems() {
    if (!grnItemsCache || !grnItemsCache.length) return;
    grnItemsCache.forEach(it => {
        addProductRow({ id: it.product_id, name: it.name, item_code: it.item_code, unit: it.unit }, it.qty);
    });
}

function resetGrnPicker() {
    grnSearchInput.value = '';
    grnDropdown.style.display = 'none';
    grnItemsCache = null;
    document.getElementById('grnPreview').classList.add('d-none');
}

/* ── Product Search (billing-style: local + server AJAX) ── */
const searchInput    = document.getElementById('productSearch');
const searchDropdown = document.getElementById('searchDropdown');
let selectedSearchIdx = -1;
let searchAjaxTimer   = null;

function renderDropdown(results, container, onSelect) {
    container.innerHTML = '';
    selectedSearchIdx = -1;
    results.forEach((p, i) => {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action py-1 px-2 search-item-link';
        a.setAttribute('data-id', p.id);
        a.style.fontSize = '.82rem';
        a.innerHTML = '<strong>' + escHtml(p.name) + '</strong>'
            + (p.item_code ? '<small class="text-muted ml-2">' + escHtml(p.item_code) + '</small>' : '')
            + '<span class="float-right text-muted small">Stock: ' + (p.stock_qty || 0) + '</span>';
        a.addEventListener('mousedown', function(e) {
            e.preventDefault();
            onSelect(p);
        });
        container.appendChild(a);
    });
    container.style.display = results.length ? 'block' : 'none';
}

function highlightSearchItem(links) {
    links.forEach((l, i) => {
        l.classList.toggle('active', i === selectedSearchIdx);
    });
}

function learnProduct(p) {
    if (allProducts.some(x => String(x.id) === String(p.id))) return;
    allProducts.push(p);
}

searchInput.addEventListener('input', function() {
    const q = this.value.trim();
    const ql = q.toLowerCase();
    clearTimeout(searchAjaxTimer);
    if (q.length < 1) { searchDropdown.style.display = 'none'; searchDropdown.innerHTML = ''; return; }

// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
    // Instant local filter
    const localResults = allProducts.filter(p =>
        pmatch(ql, p.name, p.item_code, p.pcs_barcode)
    ).slice(0, 15);
    renderDropdown(localResults, searchDropdown, function(p) {
        addProductRow(p);
        searchInput.value = '';
        searchDropdown.style.display = 'none';
        searchInput.focus();
    });

    // Debounced server search — covers the full catalog beyond the embedded 2000
    searchAjaxTimer = setTimeout(function() {
        fetch('<?= base_url("products/search_ajax") ?>?q=' + encodeURIComponent(q) + '&limit=15', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (searchInput.value.trim() !== q) return;
                if (!data.success || !data.products || !data.products.length) return;
                const serverResults = data.products.map(function(row) {
                    return {
                        id: parseInt(row.id), name: row.name, item_code: row.item_code || '',
                        unit: row.unit || 'PCS', stock_qty: parseFloat(row.stock_qty) || 0,
                        pcs_barcode: row.barcode || '',
                        maintain_serial: parseInt(row.maintain_serial) || 0,
                    };
                });
                serverResults.forEach(learnProduct);
                renderDropdown(serverResults, searchDropdown, function(p) {
                    addProductRow(p);
                    searchInput.value = '';
                    searchDropdown.style.display = 'none';
                    searchInput.focus();
                });
            })
            .catch(() => {});
    }, 200);
});

searchInput.addEventListener('keydown', function(e) {
    const links = searchDropdown.querySelectorAll('.search-item-link');

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (links.length > 0) {
            selectedSearchIdx = (selectedSearchIdx + 1) % links.length;
            highlightSearchItem(links);
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (links.length > 0) {
            selectedSearchIdx = (selectedSearchIdx - 1 + links.length) % links.length;
            highlightSearchItem(links);
        }
    } else if (e.key === 'Escape') {
        searchDropdown.style.display = 'none';
        selectedSearchIdx = -1;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        triggerSearch();
    }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('#productSearch') && !e.target.closest('#searchDropdown')) {
        searchDropdown.style.display = 'none';
    }
});

function triggerSearch() {
    const q = searchInput.value.trim();
    if (!q) { searchInput.focus(); return; }

    // 1. If a dropdown item is highlighted (arrow keys), pick it
    const activeLink = searchDropdown.querySelector('.search-item-link.active');
    if (activeLink) {
        activeLink.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
        return;
    }

    // 2. If dropdown is showing with results, pick the first one
    const firstLink = searchDropdown.querySelector('.search-item-link');
    if (firstLink && searchDropdown.style.display !== 'none') {
        firstLink.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
        return;
    }

    // 3. No dropdown results — try barcode/item-code AJAX lookup
    searchDropdown.style.display = 'none';
    searchInput.placeholder = 'Searching barcode...';
    fetch('<?= base_url("stock_transfers/get_by_barcode_ajax") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'barcode=' + encodeURIComponent(q)
    })
    .then(r => r.json())
    .then(data => {
        searchInput.placeholder = 'Type product name or item code to search...';
        if (data.success && data.product) {
            learnProduct(data.product);
            addProductRow(data.product);
            searchInput.value = '';
            searchDropdown.style.display = 'none';
        } else {
            // 4. Barcode not found — try server search as last resort
            fetch('<?= base_url("products/search_ajax") ?>?q=' + encodeURIComponent(q) + '&limit=15', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(sdata => {
                    if (sdata.success && sdata.products && sdata.products.length) {
                        const serverResults = sdata.products.map(function(row) {
                            return { id: parseInt(row.id), name: row.name, item_code: row.item_code || '', unit: row.unit || 'PCS', stock_qty: parseFloat(row.stock_qty) || 0, pcs_barcode: row.barcode || '', maintain_serial: parseInt(row.maintain_serial) || 0 };
                        });
                        serverResults.forEach(learnProduct);
                        if (serverResults.length === 1) {
                            addProductRow(serverResults[0]);
                            searchInput.value = '';
                        } else {
                            renderDropdown(serverResults, searchDropdown, function(p) {
                                addProductRow(p);
                                searchInput.value = '';
                                searchDropdown.style.display = 'none';
                                searchInput.focus();
                            });
                        }
                    } else {
                        searchInput.select();
                    }
                }).catch(() => { searchInput.select(); });
        }
    })
    .catch(() => { searchInput.placeholder = 'Type product name or item code to search...'; searchInput.select(); });
}

/* ── Add Row ───────────────────────────────────────────── */
function addProductRow(p, qty, salesData) {
    qty = (qty === undefined || qty === null || qty === '') ? 1 : (parseFloat(qty) || 0);
    const prevSales  = salesData ? (parseFloat(salesData.prev_day_sales_qty) || 0) : 0;
    const suggestedQ = salesData ? (parseFloat(salesData.suggested_qty) || 0)      : 0;
    const source     = salesData ? (salesData.suggestion_source || 'prev_day_sales') : '';

    // Already in the table? Update suggestion info but don't overwrite user's final qty.
    const existing = document.querySelector('#itemsBody input[name*="[product_id]"][value="' + p.id + '"]');
    if (existing) {
        const row = existing.closest('tr');
        if (salesData) {
            const psCell = row.querySelector('.prev-sales-val');
            const sgCell = row.querySelector('.suggested-val');
            const psHid  = row.querySelector('input[name*="[prev_day_sales_qty]"]');
            const sgHid  = row.querySelector('input[name*="[suggested_qty]"]');
            const srcHid = row.querySelector('input[name*="[suggestion_source]"]');
            if (psCell)  psCell.textContent = prevSales;
            if (sgCell)  sgCell.textContent = suggestedQ;
            if (psHid)   psHid.value  = prevSales;
            if (sgHid)   sgHid.value  = suggestedQ;
            if (srcHid)  srcHid.value = source;
        } else {
            const qtyInput = row.querySelector('.qty-input');
            if (qtyInput) {
                qtyInput.value = (parseFloat(qtyInput.value) || 0) + qty;
            }
        }
        updateCounts();
        return;
    }

    rowCount++;
    removeNoItemsRow();
    const showSales = salesColumnsVisible;
    const tr = document.createElement('tr');
    tr.id = 'row_' + rowCount;
    tr.innerHTML = `
        <input type="hidden" name="items[${rowCount}][product_id]" value="${p.id}">
        <input type="hidden" name="items[${rowCount}][prev_day_sales_qty]" value="${prevSales || ''}">
        <input type="hidden" name="items[${rowCount}][suggested_qty]" value="${suggestedQ || ''}">
        <input type="hidden" name="items[${rowCount}][suggestion_source]" value="${source}">
        <td>
            <div class="font-weight-bold" style="font-size:.85rem;">${escHtml(p.name)}</div>
            <small class="text-muted">${escHtml(p.item_code || '')}</small>
        </td>
        <td>
            <span id="avail_${rowCount}" class="badge badge-secondary">...</span>
        </td>
        <td>
            <span id="dest_avail_${rowCount}" class="badge badge-secondary">...</span>
        </td>
        <td class="sales-col ${showSales ? '' : 'd-none'} text-center">
            <span class="prev-sales-val text-info font-weight-bold">${prevSales || '-'}</span>
        </td>
        <td class="sales-col ${showSales ? '' : 'd-none'} text-center">
            <span class="suggested-val text-muted">${suggestedQ || '-'}</span>
        </td>
        <td>
            <input type="number" name="items[${rowCount}][requested_qty]"
                   class="form-control form-control-sm qty-input"
                   min="0.001" step="0.001" value="${qty}" required onchange="updateCounts(); syncTxSerial(${rowCount});">
            ${String(p.maintain_serial || '0') === '1' ? `
            <input type="hidden" name="items[${rowCount}][serial_json]" id="txsj_${rowCount}" value="">
            <button type="button" class="btn btn-warning btn-sm py-0 px-2 mt-1 w-100" style="font-size:.72rem;"
                    onclick="openTxSerials(${rowCount})">
                <i class="fas fa-barcode"></i> Serials <span id="txsc_${rowCount}">0/${qty}</span>
            </button>` : ''}
        </td>
        <td><small class="text-muted">${escHtml(p.unit || 'PCS')}</small></td>
        <td>
            <input type="text" name="items[${rowCount}][remarks]" class="form-control form-control-sm" placeholder="Remarks">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeRow(${rowCount})">
                <i class="fas fa-times"></i>
            </button>
        </td>`;
    document.getElementById('itemsBody').appendChild(tr);
    if (String(p.maintain_serial || '0') === '1') {
        txSerials[rowCount] = { productId: p.id, productName: p.name, serials: [] };
    }
    updateCounts();
    loadStock(p.id, rowCount);

    // Defer focus so Enter keydown on searchInput doesn't steal it back
    const qtyInput = tr.querySelector('.qty-input');
    if (qtyInput) {
        qtyInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
        setTimeout(function() {
            qtyInput.focus();
            qtyInput.select();
        }, 60);
    }
}

function loadStock(productId, rn) {
    if (!stockCheckLocId) return;
    fetch(stockAjaxUrl + '?product_id=' + productId + '&location_id=' + stockCheckLocId)
        .then(r => r.json())
        .then(d => {
            const el = document.getElementById('avail_' + rn);
            if (el) {
                el.textContent = d.stock_qty;
                el.className   = d.stock_qty > 0 ? 'badge badge-success' : 'badge badge-danger';
            }
        });
    loadDestStock(productId, rn);
}

function loadDestStock(productId, rn) {
    const destLocId = document.getElementById('toLocationHidden').value;
    if (!destLocId) return;
    fetch(stockAjaxUrl + '?product_id=' + productId + '&location_id=' + destLocId)
        .then(r => r.json())
        .then(d => {
            const el = document.getElementById('dest_avail_' + rn);
            if (el) {
                el.textContent = d.stock_qty;
                el.className   = d.stock_qty > 0 ? 'badge badge-info' : 'badge badge-warning';
            }
        });
}

function refreshDestStockInRows() {
    document.querySelectorAll('#itemsBody tr[id^="row_"]').forEach(tr => {
        const rn  = tr.id.replace('row_', '');
        const pid = tr.querySelector('input[name*="[product_id]"]')?.value;
        if (pid) loadDestStock(pid, rn);
    });
}

function addEmptyRow() {
    rowCount++;
    removeNoItemsRow();
    const tr = document.createElement('tr');
    tr.id = 'row_' + rowCount;
    tr.innerHTML = `
        <td colspan="9">
            <div class="input-group input-group-sm">
                <input type="text" class="form-control border-primary inline-search"
                       placeholder="Type product name or code..." autocomplete="off"
                       oninput="inlineSearch(this, ${rowCount})"
                       onkeydown="if(event.key==='Escape'){removeRow(${rowCount})}">
                <div id="inlineDrop_${rowCount}" class="list-group position-absolute" style="z-index:9999;max-height:200px;overflow-y:auto;display:none;min-width:350px;top:100%;left:0;"></div>
            </div>
        </td>`;
    document.getElementById('itemsBody').appendChild(tr);
}

function inlineSearch(input, rn) {
    const q = input.value.trim();
    const ql = q.toLowerCase();
    const drop = document.getElementById('inlineDrop_' + rn);
    clearTimeout(input._ajaxTimer);
    if (q.length < 1) { drop.style.display = 'none'; return; }
    const onPick = function(p) {
        const oldRow = document.getElementById('row_' + rn);
        if (oldRow) oldRow.remove();
        addProductRow(p);
    };
    const results = allProducts.filter(p =>
        pmatch(ql, p.name, p.item_code, p.pcs_barcode)
    ).slice(0, 10);
    renderDropdown(results, drop, onPick);
    input._ajaxTimer = setTimeout(function() {
        fetch('<?= base_url("products/search_ajax") ?>?q=' + encodeURIComponent(q) + '&limit=10', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (input.value.trim() !== q) return;
                if (!data.success || !data.products || !data.products.length) return;
                const srv = data.products.map(function(row) {
                    return { id: parseInt(row.id), name: row.name, item_code: row.item_code || '', unit: row.unit || 'PCS', stock_qty: parseFloat(row.stock_qty) || 0, pcs_barcode: row.barcode || '', maintain_serial: parseInt(row.maintain_serial) || 0 };
                });
                srv.forEach(learnProduct);
                renderDropdown(srv, drop, onPick);
            }).catch(() => {});
    }, 200);
}

function removeRow(n) {
    const row = document.getElementById('row_' + n);
    if (row) row.remove();
    updateCounts();
    if (!document.querySelectorAll('#itemsBody tr[id^="row_"]').length) {
        showNoItemsRow();
    }
}

function clearAllRows() {
    document.getElementById('itemsBody').innerHTML =
        '<tr id="noItemsRow"><td colspan="9" class="text-center text-muted py-3">Search a product above, load last day sales, or click "Add Row"</td></tr>';
    rowCount = 0;
    updateCounts();
}

function removeNoItemsRow() {
    const nr = document.getElementById('noItemsRow');
    if (nr) nr.remove();
}

function showNoItemsRow() {
    document.getElementById('itemsBody').innerHTML =
        '<tr id="noItemsRow"><td colspan="9" class="text-center text-muted py-3">Search a product above, load last day sales, or click "Add Row"</td></tr>';
}

function refreshStockInRows() {
    document.querySelectorAll('#itemsBody tr[id^="row_"]').forEach(tr => {
        const rn  = tr.id.replace('row_', '');
        const pid = tr.querySelector('input[name*="[product_id]"]')?.value;
        if (pid) loadStock(pid, rn);
    });
}

function updateCounts() {
    const n = document.querySelectorAll('#itemsBody tr[id^="row_"]').length;
    document.getElementById('itemCount').textContent    = n;
    document.getElementById('summaryCount').textContent = n;
}

/* ── Submit ─────────────────────────────────────────────── */
function submitAs(action) {
    const fromId = document.getElementById('fromLocationHidden').value;
    const toId   = document.getElementById('toLocationHidden').value;

    if (!fromId || !toId) {
        alert(currentDirection === 'out'
            ? 'Please select a destination location before saving.'
            : 'Please select a source location before saving.');
        return;
    }

    const items = document.querySelectorAll('#itemsBody tr[id^="row_"]');
    if (!items.length) {
        alert('Please add at least one item before saving.');
        return;
    }

    document.getElementById('saveAction').value = action;
    document.getElementById('transferForm').submit();
}

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}

/* ── Load Last Day Sales ──────────────────────────────────── */
function showSalesColumns() {
    salesColumnsVisible = true;
    document.querySelectorAll('.sales-col').forEach(el => el.classList.remove('d-none'));
}

function hideSalesColumns() {
    salesColumnsVisible = false;
    document.querySelectorAll('.sales-col').forEach(el => el.classList.add('d-none'));
}

// Print / Excel of the loaded last-day sales — sorted by rack, then item name
function openSalesReport(fmt) {
    const reqDate = document.getElementById('requisitionDate').value;
    const toId    = document.getElementById('toLocationHidden').value;
    const fromId  = document.getElementById('fromLocationHidden').value;
    if (!reqDate || !toId) { alert('Select the destination location and requisition date first.'); return; }
    const url = '<?= base_url('stock_transfers/prev_day_sales_report') ?>'
        + '?location_id=' + encodeURIComponent(toId)
        + '&from_location_id=' + encodeURIComponent(fromId || '')
        + '&requisition_date=' + encodeURIComponent(reqDate)
        + '&format=' + fmt;
    if (fmt === 'excel') window.location.href = url; else window.open(url, '_blank');
}

function loadPrevDaySales() {
    const reqDate = document.getElementById('requisitionDate').value;
    if (!reqDate) { alert('Please select a requisition date.'); return; }

    // Always load sales from the TO location (the store that needs restocking)
    const salesLocId = document.getElementById('toLocationHidden').value;
    if (!salesLocId) {
        alert(currentDirection === 'out'
            ? 'Please select a destination location first.'
            : 'Destination location not set.');
        return;
    }

    const btn = document.getElementById('btnLoadSales');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Loading...';

    fetch(salesAjaxUrl + '?location_id=' + salesLocId + '&requisition_date=' + encodeURIComponent(reqDate),
        { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download mr-1"></i> Load Last Day Sales';

            if (!data.success) {
                alert(data.message || 'Failed to load sales data.');
                return;
            }

            // Update summary
            document.getElementById('salesSumDate').textContent = data.sales_date;
            document.getElementById('salesSumItems').textContent = data.total_items;
            document.getElementById('salesSumUnits').textContent = data.total_units;
            document.getElementById('salesSummary').classList.remove('d-none');
            document.getElementById('btnClearSales').classList.remove('d-none');
            document.getElementById('salesReportBtns').classList.remove('d-none');

            if (!data.items || !data.items.length) {
                document.getElementById('salesSummary').querySelector('.alert')
                    .innerHTML = '<i class="fas fa-info-circle mr-1"></i> No sales found for <strong>' + escHtml(data.sales_date) + '</strong> at this location.';
                return;
            }

            showSalesColumns();

            data.items.forEach(function(item) {
                const prodObj = {
                    id: item.product_id,
                    name: item.name,
                    item_code: item.item_code || '',
                    unit: item.unit || 'PCS',
                    stock_qty: item.current_stock || 0,
                    maintain_serial: 0,
                };
                learnProduct(prodObj);
                addProductRow(prodObj, item.suggested_qty, {
                    prev_day_sales_qty: item.prev_day_sales_qty,
                    suggested_qty: item.suggested_qty,
                    suggestion_source: 'prev_day_sales',
                });
            });
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download mr-1"></i> Load Last Day Sales';
            alert('Network error loading sales data.');
        });
}

function clearSalesSuggestions() {
    document.getElementById('salesSummary').classList.add('d-none');
    document.getElementById('btnClearSales').classList.add('d-none');
    document.getElementById('salesReportBtns').classList.add('d-none');
    hideSalesColumns();
    // Reset suggestion values in all rows
    document.querySelectorAll('#itemsBody tr[id^="row_"]').forEach(tr => {
        const psHid  = tr.querySelector('input[name*="[prev_day_sales_qty]"]');
        const sgHid  = tr.querySelector('input[name*="[suggested_qty]"]');
        const srcHid = tr.querySelector('input[name*="[suggestion_source]"]');
        if (psHid)  psHid.value  = '';
        if (sgHid)  sgHid.value  = '';
        if (srcHid) srcHid.value = '';
    });
}

/* ── Init ───────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    setDirection('out');
    if (autoLoadSales) {
        setTimeout(loadPrevDaySales, 300);
    }
});
</script>

<style>
.btn-group-lg .btn { border-radius: 8px !important; padding: 10px 15px; }
.btn-group-lg .btn:first-child { margin-right: 4px; }
.btn-group-lg .btn:last-child  { margin-left: 4px; }

/* Search dropdown highlight — light blue bg, dark text so everything is readable */
#searchDropdown .list-group-item.active,
#searchDropdown .list-group-item.search-first,
.list-group.position-absolute .list-group-item.active,
.list-group.position-absolute .list-group-item.search-first {
    background-color: #cfe2ff !important;
    border-color: #9ec5fe !important;
    color: #000 !important;
}
#searchDropdown .list-group-item.active *,
#searchDropdown .list-group-item.search-first *,
.list-group.position-absolute .list-group-item.active *,
.list-group.position-absolute .list-group-item.search-first * {
    color: #000 !important;
}
#searchDropdown .list-group-item:hover,
.list-group.position-absolute .list-group-item:hover {
    background-color: #e8f0fe;
    color: #000;
}
</style>

<script>
// Serial picker for transfers. Only rows whose item is serialized get a button.
var txSerials = {};

function txQty(rid) {
    var r = document.getElementById('row_' + rid);
    var i = r && r.querySelector('.qty-input');
    return Math.floor(parseFloat(i && i.value) || 0);
}

function syncTxSerial(rid) {
    var st = txSerials[rid]; if (!st) return;
    var qty = txQty(rid);
    if (st.serials.length > qty) st.serials = st.serials.slice(0, qty);
    var f = document.getElementById('txsj_' + rid);
    if (f) f.value = JSON.stringify(st.serials);
    var c = document.getElementById('txsc_' + rid);
    if (c) {
        c.textContent = st.serials.length + '/' + qty;
        var b = c.closest('button');
        b.classList.remove('btn-warning', 'btn-success');
        b.classList.add(st.serials.length === qty && qty > 0 ? 'btn-success' : 'btn-warning');
    }
}

function openTxSerials(rid) {
    var st = txSerials[rid]; if (!st) return;
    var qty = txQty(rid);
    if (qty < 1) { alert('Enter the transfer quantity first.'); return; }
    SerialCapture.open({
        mode: 'outbound',
        productId: st.productId,
        productName: st.productName,
        qty: qty,
        serials: st.serials,
        // The source branch may not be where this user is standing; the server
        // validates the units against the transfer's FROM location.
        enforceLocation: false,
        onSave: function (list) { st.serials = list; syncTxSerial(rid); }
    });
}

(function () {
    var f = document.getElementById('transferForm');
    if (!f) return;
    f.addEventListener('submit', function (e) {
        var bad = [];
        Object.keys(txSerials).forEach(function (rid) {
            if (!document.getElementById('row_' + rid)) return;   // row removed
            var qty = txQty(rid), have = txSerials[rid].serials.length;
            if (have !== qty) bad.push(txSerials[rid].productName + ' — ' + have + ' of ' + qty + ' selected');
        });
        if (bad.length) {
            e.preventDefault();
            e.stopImmediatePropagation();
            alert('Select which units are moving before saving:\n\n\u2022 ' + bad.join('\n\u2022 '));
        }
    }, true);
})();
</script>

<?php $this->load->view('serials/_capture_modal'); ?>
