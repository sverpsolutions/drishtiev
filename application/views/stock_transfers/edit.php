<?php // FILE: application/views/stock_transfers/edit.php
$t = $transfer;
$active_loc_id   = isset($active_loc_id)   ? (int)$active_loc_id   : 0;
$active_loc_name = isset($active_loc_name) ? $active_loc_name       : 'Unknown Location';
$is_out = ((int)$t->from_location_id === $active_loc_id);
$auto_load_sales = !empty($auto_load_sales);

// Resolve the TO location name for sales label
$to_loc_name = '';
foreach ($locations as $_loc) {
    if ((int)$_loc->id === (int)$t->to_location_id) { $to_loc_name = $_loc->name; break; }
}

// Check if any existing items have suggestion data — if so, show sales columns on load
$has_suggestions = false;
if (!empty($transfer_items)) {
    foreach ($transfer_items as $_ti) {
        if (!empty($_ti->prev_day_sales_qty) || !empty($_ti->suggested_qty)) {
            $has_suggestions = true;
            break;
        }
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0">Edit Stock Transfer</h5>
        <small class="text-muted"><a href="<?= base_url('stock_transfers') ?>">Stock Transfers</a> / <a href="<?= base_url('stock_transfers/view/'.$t->id) ?>"><?= htmlspecialchars($t->transfer_no) ?></a> / Edit</small>
    </div>
    <a href="<?= base_url('stock_transfers/view/'.$t->id) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<form method="POST" action="<?= base_url('stock_transfers/edit/'.$t->id) ?>" id="transferForm">
<input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
<input type="hidden" name="save_action" id="saveAction" value="draft">
<input type="hidden" name="from_location_id" id="fromLocationHidden" value="<?= (int)$t->from_location_id ?>">
<input type="hidden" name="to_location_id"   id="toLocationHidden"   value="<?= (int)$t->to_location_id ?>">

<div class="row">
<div class="col-md-8">

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2">
            <strong><i class="fas fa-edit text-info mr-1"></i> Editing <?= htmlspecialchars($t->transfer_no) ?></strong>
            <span class="badge badge-<?= $t->status === 'draft' ? 'secondary' : 'warning' ?> ml-2"><?= ucfirst($t->status) ?></span>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <?php $__from_locked = strpos($t->transfer_no, 'TRO-') === 0; ?>
                    <label class="small font-weight-bold">From Location<?php if ($__from_locked): ?> <small class="text-muted font-weight-normal">(fixed — <?= htmlspecialchars($t->transfer_no) ?> belongs to this location)</small><?php endif; ?></label>
                    <select name="_from_loc" class="form-control form-control-sm" <?= $__from_locked ? 'disabled' : '' ?> onchange="document.getElementById('fromLocationHidden').value=this.value">
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc->id ?>" <?= (int)$loc->id === (int)$t->from_location_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc->name) ?> (<?= ucfirst($loc->type) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label class="small font-weight-bold">To Location</label>
                    <select name="_to_loc" class="form-control form-control-sm" onchange="document.getElementById('toLocationHidden').value=this.value">
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc->id ?>" <?= (int)$loc->id === (int)$t->to_location_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc->name) ?> (<?= ucfirst($loc->type) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Requisition Date</label>
                    <input type="date" id="requisitionDate" name="requisition_date" class="form-control form-control-sm"
                           value="<?= date('Y-m-d', strtotime($t->created_at)) ?>">
                </div>
                <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Reason</label>
                    <input type="text" name="reason" class="form-control form-control-sm" value="<?= htmlspecialchars($t->reason ?? '') ?>">
                </div>
                <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Notes</label>
                    <input type="text" name="notes" class="form-control form-control-sm" value="<?= htmlspecialchars($t->notes ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Load Last Day Sales -->
    <div class="card shadow-sm border-0 mb-2" id="salesLoadCard">
        <div class="card-body py-2">
            <label class="small font-weight-bold mb-1"><i class="fas fa-chart-bar text-info mr-1"></i> Load Previous Day Sales</label>
            <div class="small text-muted mb-2">Auto-fill items sold at <strong id="salesLocLabel"><?= htmlspecialchars($to_loc_name ?: $active_loc_name) ?></strong> on the day before the requisition date as suggested quantities. Existing quantities will NOT be overwritten.</div>
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
            <strong>Items to Transfer <span class="badge badge-secondary ml-1" id="itemCount"><?= count($transfer_items) ?></span></strong>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0" onclick="addEmptyRow()">
                <i class="fas fa-plus mr-1"></i> Add Row
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="itemsTable">
                <thead class="thead-dark">
                    <tr>
                        <th style="width:22%">Product</th>
                        <th style="width:8%">EAN Code</th>
                        <th style="width:8%" class="sales-col <?= $has_suggestions ? '' : 'd-none' ?>">Prev Day Sales</th>
                        <th style="width:8%" class="sales-col <?= $has_suggestions ? '' : 'd-none' ?>">Suggested</th>
                        <th style="width:12%">Final Qty</th>
                        <th style="width:6%">Unit</th>
                        <th style="width:22%">Remarks</th>
                        <th style="width:6%"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                <?php if (empty($transfer_items)): ?>
                    <tr id="noItemsRow">
                        <td colspan="8" class="text-center text-muted py-3">No items. Search a product above or click "Add Row"</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transfer_items as $idx => $item): $rn = $idx + 1; ?>
                    <tr id="row_<?= $rn ?>">
                        <input type="hidden" name="items[<?= $rn ?>][product_id]" value="<?= (int)$item->product_id ?>">
                        <input type="hidden" name="items[<?= $rn ?>][prev_day_sales_qty]" value="<?= isset($item->prev_day_sales_qty) ? (float)$item->prev_day_sales_qty : '' ?>">
                        <input type="hidden" name="items[<?= $rn ?>][suggested_qty]" value="<?= isset($item->suggested_qty) ? (float)$item->suggested_qty : '' ?>">
                        <input type="hidden" name="items[<?= $rn ?>][suggestion_source]" value="<?= htmlspecialchars($item->suggestion_source ?? '') ?>">
                        <td>
                            <div class="font-weight-bold" style="font-size:.85rem;"><?= htmlspecialchars($item->product_name) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($item->item_code ?? '') ?></small>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($item->ean_code ?? '') ?></small></td>
                        <td class="sales-col <?= $has_suggestions ? '' : 'd-none' ?> text-center">
                            <span class="prev-sales-val text-info font-weight-bold"><?= !empty($item->prev_day_sales_qty) ? (float)$item->prev_day_sales_qty : '-' ?></span>
                        </td>
                        <td class="sales-col <?= $has_suggestions ? '' : 'd-none' ?> text-center">
                            <span class="suggested-val text-muted"><?= !empty($item->suggested_qty) ? (float)$item->suggested_qty : '-' ?></span>
                        </td>
                        <td>
                            <input type="number" name="items[<?= $rn ?>][requested_qty]"
                                   class="form-control form-control-sm qty-input"
                                   min="0.001" step="0.001" value="<?= (float)$item->requested_qty ?>" required>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($item->unit) ?></small></td>
                        <td>
                            <input type="text" name="items[<?= $rn ?>][remarks]" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($item->remarks ?? '') ?>" placeholder="Remarks">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeRow(<?= $rn ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /col-md-8 -->

<div class="col-md-4">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2"><strong>Save Changes</strong></div>
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Total Items:</span>
                <strong id="summaryCount"><?= count($transfer_items) ?></strong>
            </div>
            <hr>
            <button type="button" class="btn btn-outline-secondary btn-block mb-2" onclick="submitAs('draft')">
                <i class="fas fa-save mr-1"></i> Save as Draft
            </button>
            <button type="button" class="btn btn-warning btn-block" onclick="submitAs('pending')">
                <i class="fas fa-paper-plane mr-1"></i> Submit for Authorization
            </button>
            <hr>
            <a href="<?= base_url('stock_transfers/view/'.$t->id) ?>" class="btn btn-light btn-block btn-sm">Cancel</a>
        </div>
    </div>
</div>

</div><!-- /row -->
</form>

<script>
const allProducts    = <?= json_encode(array_values($products)) ?>;
const salesAjaxUrl   = '<?= base_url('stock_transfers/get_prev_day_sales_ajax') ?>';
const activeLocId    = <?= (int)$active_loc_id ?>;
const activeLocName  = <?= json_encode($active_loc_name) ?>;
let rowCount = <?= count($transfer_items) ?>;
let salesColumnsVisible = <?= $has_suggestions ? 'true' : 'false' ?>;

/* ── Product Search ──────────────────────────── */
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

function learnProduct(p) {
    if (allProducts.some(x => String(x.id) === String(p.id))) return;
    allProducts.push(p);
}

searchInput.addEventListener('input', function() {
    const q = this.value.trim();
    const ql = q.toLowerCase();
    clearTimeout(searchAjaxTimer);
    if (q.length < 1) { searchDropdown.style.display = 'none'; return; }
// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
    const localResults = allProducts.filter(p =>
        pmatch(ql, p.name, p.item_code, p.pcs_barcode)
    ).slice(0, 15);
    renderDropdown(localResults, searchDropdown, function(p) {
        addProductRow(p);
        searchInput.value = '';
        searchDropdown.style.display = 'none';
        searchInput.focus();
    });
    searchAjaxTimer = setTimeout(function() {
        fetch('<?= base_url("products/search_ajax") ?>?q=' + encodeURIComponent(q) + '&limit=15', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (searchInput.value.trim() !== q) return;
                if (!data.success || !data.products || !data.products.length) return;
                const srv = data.products.map(row => ({
                    id: parseInt(row.id), name: row.name, item_code: row.item_code || '',
                    unit: row.unit || 'PCS', stock_qty: parseFloat(row.stock_qty) || 0,
                    pcs_barcode: row.barcode || ''
                }));
                srv.forEach(learnProduct);
                renderDropdown(srv, searchDropdown, function(p) {
                    addProductRow(p);
                    searchInput.value = '';
                    searchDropdown.style.display = 'none';
                    searchInput.focus();
                });
            }).catch(() => {});
    }, 200);
});

searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); triggerSearch(); }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('#productSearch') && !e.target.closest('#searchDropdown')) {
        searchDropdown.style.display = 'none';
    }
});

function triggerSearch() {
    const q = searchInput.value.trim();
    if (!q) return;
    const firstLink = searchDropdown.querySelector('.search-item-link');
    if (firstLink && searchDropdown.style.display !== 'none') {
        firstLink.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
        return;
    }
    fetch('<?= base_url("stock_transfers/get_by_barcode_ajax") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'barcode=' + encodeURIComponent(q)
    }).then(r => r.json()).then(data => {
        if (data.success && data.product) {
            learnProduct(data.product);
            addProductRow(data.product);
            searchInput.value = '';
        }
    }).catch(() => {});
}

/* ── Add / Remove Rows ───────────────────────── */
function addProductRow(p, qty, salesData) {
    qty = (qty === undefined || qty === null || qty === '') ? 1 : (parseFloat(qty) || 0);
    const prevSales  = salesData ? (parseFloat(salesData.prev_day_sales_qty) || 0) : 0;
    const suggestedQ = salesData ? (parseFloat(salesData.suggested_qty) || 0)      : 0;
    const source     = salesData ? (salesData.suggestion_source || 'prev_day_sales') : '';

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
            const qi = row.querySelector('.qty-input');
            if (qi) qi.value = (parseFloat(qi.value) || 0) + qty;
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
        <td><small class="text-muted">${escHtml(p.pcs_barcode || '')}</small></td>
        <td class="sales-col ${showSales ? '' : 'd-none'} text-center">
            <span class="prev-sales-val text-info font-weight-bold">${prevSales || '-'}</span>
        </td>
        <td class="sales-col ${showSales ? '' : 'd-none'} text-center">
            <span class="suggested-val text-muted">${suggestedQ || '-'}</span>
        </td>
        <td>
            <input type="number" name="items[${rowCount}][requested_qty]"
                   class="form-control form-control-sm qty-input"
                   min="0.001" step="0.001" value="${qty}" required>
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
    updateCounts();
}

function addEmptyRow() {
    rowCount++;
    removeNoItemsRow();
    const tr = document.createElement('tr');
    tr.id = 'row_' + rowCount;
    tr.innerHTML = `
        <td colspan="8">
            <div class="input-group input-group-sm">
                <input type="text" class="form-control border-primary"
                       placeholder="Type product name or code..." autocomplete="off"
                       oninput="inlineSearch(this, ${rowCount})"
                       onkeydown="if(event.key==='Escape'){removeRow(${rowCount})}">
                <div id="inlineDrop_${rowCount}" class="list-group position-absolute" style="z-index:9999;max-height:200px;overflow-y:auto;display:none;min-width:350px;top:100%;left:0;"></div>
            </div>
        </td>`;
    document.getElementById('itemsBody').appendChild(tr);
}

function inlineSearch(input, rn) {
    const q = input.value.trim().toLowerCase();
    const drop = document.getElementById('inlineDrop_' + rn);
    clearTimeout(input._t);
    if (q.length < 1) { drop.style.display = 'none'; return; }
    const onPick = function(p) {
        document.getElementById('row_' + rn)?.remove();
        addProductRow(p);
    };
    const results = allProducts.filter(p =>
        pmatch(q, p.name, p.item_code)
    ).slice(0, 10);
    renderDropdown(results, drop, onPick);
    input._t = setTimeout(function() {
        fetch('<?= base_url("products/search_ajax") ?>?q=' + encodeURIComponent(q) + '&limit=10', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.products) return;
                const srv = data.products.map(row => ({
                    id: parseInt(row.id), name: row.name, item_code: row.item_code || '',
                    unit: row.unit || 'PCS', stock_qty: parseFloat(row.stock_qty) || 0
                }));
                srv.forEach(learnProduct);
                renderDropdown(srv, drop, onPick);
            }).catch(() => {});
    }, 200);
}

function removeRow(n) {
    document.getElementById('row_' + n)?.remove();
    updateCounts();
    if (!document.querySelectorAll('#itemsBody tr[id^="row_"]').length) showNoItemsRow();
}

function removeNoItemsRow() { document.getElementById('noItemsRow')?.remove(); }

function showNoItemsRow() {
    document.getElementById('itemsBody').innerHTML =
        '<tr id="noItemsRow"><td colspan="8" class="text-center text-muted py-3">No items.</td></tr>';
}

function updateCounts() {
    const n = document.querySelectorAll('#itemsBody tr[id^="row_"]').length;
    document.getElementById('itemCount').textContent    = n;
    document.getElementById('summaryCount').textContent = n;
}

function submitAs(action) {
    const fromId = document.getElementById('fromLocationHidden').value;
    const toId   = document.getElementById('toLocationHidden').value;
    if (!fromId || !toId) { alert('Please select both locations.'); return; }
    if (fromId === toId) { alert('From and To locations cannot be the same.'); return; }
    if (!document.querySelectorAll('#itemsBody tr[id^="row_"]').length) {
        alert('Please add at least one item.'); return;
    }
    document.getElementById('saveAction').value = action;
    document.getElementById('transferForm').submit();
}

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
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

    const salesLocId = document.getElementById('toLocationHidden').value;
    if (!salesLocId) { alert('Please select a destination location.'); return; }
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
    document.querySelectorAll('#itemsBody tr[id^="row_"]').forEach(tr => {
        const psHid  = tr.querySelector('input[name*="[prev_day_sales_qty]"]');
        const sgHid  = tr.querySelector('input[name*="[suggested_qty]"]');
        const srcHid = tr.querySelector('input[name*="[suggestion_source]"]');
        if (psHid)  psHid.value  = '';
        if (sgHid)  sgHid.value  = '';
        if (srcHid) srcHid.value = '';
    });
}
</script>

<style>
#searchDropdown .list-group-item:hover { background-color: #e8f0fe; color: #000; }
</style>
