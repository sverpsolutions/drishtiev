<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<style>
#scanInput { font-size:1.2rem; font-weight:600; letter-spacing:.05em; }
#itemCard  { display:none; }
.scan-log-row { animation: fadeIn .25s ease; }
@keyframes fadeIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }
.kpi-strip { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:10px; }
.kpi-s { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:6px 14px; font-size:.8rem; }
.kpi-s strong { display:block; font-size:1.1rem; color:var(--ds-primary,#1e40af); }
.var-pos { color:#16a34a; font-weight:700; }
.var-neg { color:#dc2626; font-weight:700; }
.var-zero{ color:#6b7280; }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h5 class="mb-0 font-weight-bold">
            <i class="fas fa-barcode mr-1 text-primary"></i> Stock Count
            <span class="badge badge-warning ml-1"><?= htmlspecialchars($take->take_no) ?></span>
        </h5>
        <small class="text-muted"><i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($take->location_name) ?></small>
    </div>
    <div class="d-flex" style="gap:6px;">
        <a href="<?= base_url('stock_take') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> All Stock Takes</a>
        <button class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#importExcelModal">
            <i class="fas fa-file-excel mr-1"></i> Import Excel
        </button>
        <button class="btn btn-success btn-sm" onclick="openFinalize()"><i class="fas fa-check-circle mr-1"></i> Scanning Complete</button>
    </div>
</div>

<!-- Import Excel Modal -->
<div class="modal fade" id="importExcelModal" tabindex="-1" role="dialog" aria-labelledby="importExcelModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importExcelModalLabel"><i class="fas fa-file-excel text-success mr-1"></i> Import Stock Count from Excel</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="post" action="<?= base_url('stock_take/import_excel/'.$take->id) ?>" enctype="multipart/form-data">
        <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_hash ?>">
        <div class="modal-body">
          <p class="text-muted mb-3">Upload an Excel (.xlsx / .xls) or CSV file with the following columns:</p>
          <table class="table table-bordered table-sm mb-3">
            <thead class="thead-light">
              <tr><th>item_code</th><th>mrp</th><th>qty</th></tr>
            </thead>
            <tbody>
              <tr><td>ITEM001</td><td>50.00</td><td>10</td></tr>
            </tbody>
          </table>
          <div class="alert alert-info py-2 px-3" style="font-size:.85rem;">
            <i class="fas fa-info-circle mr-1"></i>
            The <strong>qty</strong> column sets the counted quantity. Rows are matched on
            <strong>item_code</strong> first, then as a <strong>barcode/EAN</strong>, then on item_code
            with leading zeros ignored (Excel drops them from codes like <code>0012345</code>).
            Any row that still doesn't match is listed back to you, not silently skipped —
            and you can download an <strong>issues report</strong> naming the Excel row number
            and the reason each one failed.
          </div>
          <div class="form-group mb-2">
            <label class="font-weight-bold">Select File</label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" name="import_file" id="importFileInput" accept=".xlsx,.xls,.csv" required>
              <label class="custom-file-label" for="importFileInput">Choose file…</label>
            </div>
          </div>
          <a href="<?= base_url('stock_take/download_template') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-download mr-1"></i> Download blank template
          </a>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="button" id="importSubmitBtn" onclick="doImportExcel()" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> Import</button>
        </div>
        <div id="importResult" class="px-3 pb-2" style="display:none;"></div>
      </form>
    </div>
  </div>
</div>

<script>
// Show filename in custom file input label
document.getElementById('importFileInput') && document.getElementById('importFileInput').addEventListener('change', function(){
    var name = this.files[0] ? this.files[0].name : 'Choose file…';
    this.nextElementSibling.textContent = name;
});

// Renders one "here are the rows you need to look at" panel. Long lists are
// capped on screen but the full set stays selectable in the scroll box.
function codeBlock(level, icon, codes, label) {
    codes = codes || [];
    if (!codes.length) return '';
    var esc = function (s) {
        return String(s).replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
    };
    return '<div class="alert alert-' + level + ' py-2 mb-2" style="font-size:.83rem;">'
         + '<i class="fas fa-' + icon + ' mr-1"></i><strong>' + codes.length + '</strong> ' + label + ':'
         + '<div style="max-height:120px;overflow:auto;margin-top:5px;font-family:monospace;font-size:.8rem;">'
         + codes.map(esc).join(', ')
         + '</div></div>';
}

function doImportExcel() {
    var fileInput = document.getElementById('importFileInput');
    if (!fileInput.files.length) { alert('Please select a file first.'); return; }

    var btn = document.getElementById('importSubmitBtn');
    var resultDiv = document.getElementById('importResult');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Importing...';
    resultDiv.style.display = 'none';

    var fd = new FormData();
    fd.append('import_file', fileInput.files[0]);

    fetch('<?= base_url('stock_take/import_excel/'.$take->id) ?>', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-upload mr-1"></i> Import';
        resultDiv.style.display = 'block';
        if (data.success) {
            var html = '<div class="alert alert-success py-2 mb-2"><i class="fas fa-check-circle mr-1"></i> ' + data.message + '</div>';
            if (data.issue_url) {
                html += '<a href="' + data.issue_url + '" class="btn btn-sm btn-success btn-block mb-2">'
                      + '<i class="fas fa-file-excel mr-1"></i> Download issues report ('
                      + data.issue_count + ' row' + (data.issue_count === 1 ? '' : 's')
                      + ') — shows why each row failed</a>';
            }
            html += codeBlock('warning', 'exclamation-triangle', data.not_found_codes,
                        'code(s) did not match any item in this stock take');
            html += codeBlock('danger', 'times-circle', data.invalid_codes,
                        'row(s) had a blank or non-numeric qty and were left uncounted');
            html += codeBlock('info', 'copy', data.duplicate_codes,
                        'code(s) appeared more than once — the last qty in the file was used');
            resultDiv.innerHTML = html;
            // Only auto-reload on a clean import; when there's something to read,
            // leave it on screen so the codes can be copied out.
            var clean = !(data.not_found_codes || []).length && !(data.invalid_codes || []).length
                     && !(data.duplicate_codes || []).length;
            if (clean) setTimeout(function(){ location.reload(); }, 2000);
            else resultDiv.innerHTML += '<button type="button" class="btn btn-sm btn-outline-dark" onclick="location.reload()">Done — refresh counts</button>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="fas fa-exclamation-circle mr-1"></i> ' + data.message + '</div>';
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-upload mr-1"></i> Import';
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div class="alert alert-danger py-2 mb-0">Network error: ' + err.message + '</div>';
    });
}
</script>

<!-- KPI strip -->
<div class="kpi-strip">
    <div class="kpi-s"><strong id="kpiScanned"><?= $take->counted_items ?></strong>Items Scanned</div>
    <div class="kpi-s"><strong><?= $take->total_items ?></strong>Total Items</div>
    <div class="kpi-s"><strong id="kpiUnscanned"><?= $take->total_items - $take->counted_items ?></strong>Not Yet Scanned</div>
    <div class="kpi-s"><strong id="kpiSessions">0</strong>Scans This Session</div>
</div>

<!-- ══ SCAN BOX ══ -->
<div class="card shadow-sm mb-2" style="border-left:4px solid #1e40af;">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center" style="gap:10px;">
            <i class="fas fa-barcode fa-2x text-primary"></i>
            <div class="flex-grow-1">
                <label class="font-weight-bold mb-0" style="font-size:.78rem;color:#555;">Scan Barcode / Item Code / Name</label>
                <input type="text" id="scanInput" class="form-control" placeholder="Scan or type here…" autocomplete="off" autofocus>
            </div>
            <div id="scanStatus" style="font-size:.8rem;min-width:120px;text-align:right;"></div>
        </div>
    </div>
</div>

<!-- ══ ITEM DETAIL CARD (shown after scan) ══ -->
<div id="itemCard" class="card shadow mb-2" style="border-left:4px solid #16a34a;">
    <div class="card-body py-2 px-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div id="itemName" class="font-weight-bold" style="font-size:1rem;"></div>
                <div style="font-size:.8rem;color:#555;">
                    <span id="itemCode" class="mr-2"></span>
                    <span id="itemBarcode" class="text-muted"></span>
                </div>
                <div class="mt-1 d-flex" style="gap:8px;flex-wrap:wrap;">
                    <span class="badge badge-light border">MRP: <strong id="itemMrp"></strong></span>
                    <span class="badge badge-light border">SP: <strong id="itemSp"></strong></span>
                    <span class="badge badge-info">QOH: <strong id="itemQoh"></strong></span>
                    <span class="badge badge-secondary">Already Counted: <strong id="itemCounted">—</strong></span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="font-weight-bold mb-1" style="font-size:.78rem;">Enter Qty Counted</label>
                <div class="input-group input-group-lg">
                    <input type="number" id="qtyInput" class="form-control text-center font-weight-bold" value="1" min="0" step="any" style="font-size:1.4rem;">
                    <div class="input-group-append">
                        <button class="btn btn-success font-weight-bold" onclick="saveCurrentItem()" title="Save (Enter)">
                            <i class="fas fa-check"></i> Save
                        </button>
                    </div>
                </div>
                <small class="text-muted" style="font-size:.72rem;">Press <kbd>Enter</kbd> to save &amp; scan next</small>
            </div>
            <div class="col-md-3 text-center">
                <div style="font-size:.75rem;color:#888;">Variance after save</div>
                <div id="previewVariance" style="font-size:1.5rem;font-weight:800;">—</div>
            </div>
        </div>
    </div>
</div>

<!-- Multiple results picker (shown when search returns >1 result) -->
<div id="searchResults" style="display:none;" class="card shadow-sm mb-2">
    <div class="card-header py-1 px-3" style="font-size:.8rem;font-weight:700;">Multiple matches — select product:</div>
    <div id="searchResultsList" class="list-group list-group-flush"></div>
</div>

<!-- ══ SCAN LOG ══ -->
<div class="card shadow-sm">
    <div class="card-header py-1 px-3 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold" style="font-size:.82rem;"><i class="fas fa-history mr-1"></i>Scan Log <span class="badge badge-secondary" id="logCount">0</span></span>
        <span class="text-muted" style="font-size:.72rem;">Most recent first — all saved to server</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:320px;overflow-y:auto;">
            <table class="table table-sm mb-0" style="font-size:.82rem;">
                <thead class="thead-dark" style="position:sticky;top:0;">
                    <tr>
                        <th>#</th>
                        <th>Item Code</th>
                        <th>Product Name</th>
                        <th class="text-center">QOH</th>
                        <th class="text-center">Scanned Qty</th>
                        <th class="text-center">Total Counted</th>
                        <th class="text-center">Variance</th>
                        <th class="text-right">Time</th>
                    </tr>
                </thead>
                <tbody id="scanLog"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══ FINALIZE MODAL ══ -->
<div class="modal fade" id="finalizeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-check-circle mr-1"></i> Complete Stock Count</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3" style="font-size:.85rem;">
                    <strong>Session Summary:</strong>
                    <span id="modScanned"></span> items scanned, <span id="modUnscanned"></span> not yet counted.
                </div>
                <p class="font-weight-bold">What should happen to items you did NOT scan?</p>
                <div class="custom-control custom-radio mb-2">
                    <input type="radio" id="optZero" name="unscanned_opt" class="custom-control-input" value="zero">
                    <label class="custom-control-label" for="optZero">
                        <strong>Set all un-scanned items to ZERO</strong>
                        <div class="text-muted small">Treats missing items as out-of-stock — sets their counted qty to 0</div>
                    </label>
                </div>
                <div class="custom-control custom-radio mb-3">
                    <input type="radio" id="optSkip" name="unscanned_opt" class="custom-control-input" value="skip" checked>
                    <label class="custom-control-label" for="optSkip">
                        <strong>Leave un-scanned items as not counted</strong>
                        <div class="text-muted small">Only adjusts stock for items you actually scanned</div>
                    </label>
                </div>
                <hr>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" id="chkAdjust" class="custom-control-input" value="1" checked>
                    <label class="custom-control-label font-weight-bold" for="chkAdjust">
                        Apply stock adjustment (update actual inventory to counted quantities)
                    </label>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-secondary btn-sm" data-dismiss="modal">Continue Scanning</button>
                <button class="btn btn-success" onclick="submitFinalize()"><i class="fas fa-check-circle mr-1"></i> Finalize Stock Take</button>
            </div>
        </div>
    </div>
</div>

<script>
const TAKE_ID      = <?= (int)$take->id ?>;
const LOOKUP_URL   = '<?= base_url("stock_take/lookup_ajax") ?>';
const SAVE_URL     = '<?= base_url("stock_take/scan_save_ajax") ?>';
const ZERO_URL     = '<?= base_url("stock_take/zero_uncounted_ajax") ?>';
const FINALIZE_URL = '<?= base_url("stock_take/finalize/" . $take->id) ?>';
const VIEW_URL     = '<?= base_url("stock_take/view/" . $take->id) ?>';

let currentProduct = null;
let sessionScans   = 0;
let logEntries     = [];
let kpiScanned     = <?= (int)$take->counted_items ?>;
let kpiTotal       = <?= (int)$take->total_items ?>;

const scanInput   = document.getElementById('scanInput');
const qtyInput    = document.getElementById('qtyInput');
const itemCard    = document.getElementById('itemCard');
const scanStatus  = document.getElementById('scanStatus');
const searchResults = document.getElementById('searchResults');

let selectedPickerIdx = -1;
let searchDebounce    = null;

// ── Auto-focus scan box always
function focusScan() { setTimeout(() => { scanInput.focus(); scanInput.value = ''; }, 80); }
focusScan();

// ── Live search as you type (≥3 chars)
scanInput.addEventListener('input', function() {
    const val = this.value.trim();
    clearTimeout(searchDebounce);
    selectedPickerIdx = -1;

    if (val.length < 3) {
        searchResults.style.display = 'none';
        return;
    }
    // Debounce 300 ms — barcode scanner fires Enter before this triggers
    searchDebounce = setTimeout(() => doLookup(val, true), 300);
});

// ── Keyboard navigation in scan box
scanInput.addEventListener('keydown', function(e) {
    const pickerOpen = searchResults.style.display !== 'none';
    const items = pickerOpen ? searchResults.querySelectorAll('.picker-item') : [];

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!pickerOpen) return;
        selectedPickerIdx = Math.min(selectedPickerIdx + 1, items.length - 1);
        highlightPicker(items); return;
    }
    if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (!pickerOpen) return;
        selectedPickerIdx = Math.max(selectedPickerIdx - 1, 0);
        highlightPicker(items); return;
    }
    if (e.key === 'Escape') {
        searchResults.style.display = 'none'; selectedPickerIdx = -1; return;
    }
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchDebounce);
        if (pickerOpen && items.length > 0) {
            // Select highlighted item or first item
            const idx = selectedPickerIdx >= 0 ? selectedPickerIdx : 0;
            items[idx].click();
        } else {
            doLookup(this.value.trim(), false);
        }
    }
});

function highlightPicker(items) {
    items.forEach((el, i) => {
        if (i === selectedPickerIdx) {
            el.style.setProperty('background-color', '#1e40af', 'important');
            el.style.setProperty('color', '#ffffff', 'important');
            el.scrollIntoView({ block: 'nearest' });
        } else {
            el.style.removeProperty('background-color');
            el.style.removeProperty('color');
        }
    });
}

// ── Handle Enter in qty box → save
qtyInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); saveCurrentItem(); }
    if (e.key === 'Escape') { hideItemCard(); focusScan(); }
});

// ── Live variance preview
qtyInput.addEventListener('input', function() {
    if (!currentProduct) return;
    const qty = parseFloat(this.value) || 0;
    const alreadyCounted = parseFloat(currentProduct.counted_qty || 0);
    const newTotal = alreadyCounted + qty;
    const variance = newTotal - parseFloat(currentProduct.system_qty);
    const el = document.getElementById('previewVariance');
    el.textContent = (variance >= 0 ? '+' : '') + variance.toFixed(2);
    el.className = variance > 0 ? 'var-pos' : variance < 0 ? 'var-neg' : 'var-zero';
});

function doLookup(q, fromTyping) {
    if (!q) return;
    selectedPickerIdx = -1;
    scanStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    searchResults.style.display = 'none';
    if (!fromTyping) itemCard.style.display = 'none';

    fetch(LOOKUP_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'stock_take_id=' + TAKE_ID + '&q=' + encodeURIComponent(q)
    })
    .then(r => r.text())
    .then(raw => {
        let data;
        try { data = JSON.parse(raw); }
        catch(e) {
            scanStatus.innerHTML = '<span class="text-danger">Server error — see console</span>';
            console.error('Lookup raw response:', raw);
            return;
        }
        if (!data.success || !data.products || !data.products.length) {
            const msg = data.message || ('Not found: ' + q);
            scanStatus.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> ' + msg + '</span>';
            focusScan(); return;
        }
        if (data.products.length === 1) {
            selectProduct(data.products[0]);
        } else {
            showPicker(data.products);
        }
        scanStatus.innerHTML = '';
    })
    .catch(err => {
        scanStatus.innerHTML = '<span class="text-danger">Network error: ' + err.message + '</span>';
    });
}

function selectProduct(p) {
    currentProduct = p;
    searchResults.style.display = 'none';

    document.getElementById('itemName').textContent    = p.name;
    document.getElementById('itemCode').textContent    = p.item_code || '';
    document.getElementById('itemBarcode').textContent = p.barcode ? ('EAN: ' + p.barcode) : '';
    document.getElementById('itemMrp').textContent     = '₹' + parseFloat(p.mrp || 0).toFixed(2);
    document.getElementById('itemSp').textContent      = '₹' + parseFloat(p.selling_price || 0).toFixed(2);
    document.getElementById('itemQoh').textContent     = parseFloat(p.system_qty || 0).toFixed(2);
    document.getElementById('itemCounted').textContent = p.counted_qty !== null ? parseFloat(p.counted_qty).toFixed(2) : '—';

    qtyInput.value = 1;
    itemCard.style.display = 'block';

    // Trigger preview update
    qtyInput.dispatchEvent(new Event('input'));

    // Focus qty
    setTimeout(() => { qtyInput.select(); qtyInput.focus(); }, 50);
}

function showPicker(products) {
    const list = document.getElementById('searchResultsList');
    selectedPickerIdx = -1;
    list.innerHTML = products.map((p, i) => {
        const pd = JSON.stringify(p).replace(/"/g, '&quot;');
        return `<a href="#" class="list-group-item list-group-item-action py-1 px-3 picker-item" style="font-size:.82rem;"
               onclick="selectProduct(${pd}); searchResults.style.display='none'; return false;">
            <strong>${p.name}</strong> <span class="text-muted small ml-1">${p.item_code || ''}</span>
            <span class="float-right">
                <span class="badge badge-info">QOH: ${parseFloat(p.system_qty||0).toFixed(0)}</span>
                <span class="ml-1">₹${parseFloat(p.selling_price||0).toFixed(2)}</span>
            </span>
        </a>`;
    }).join('');
    searchResults.style.display = 'block';
}

function hideItemCard() {
    itemCard.style.display = 'none';
    currentProduct = null;
    scanInput.value = '';
}

function saveCurrentItem() {
    if (!currentProduct) return;
    const qty = parseFloat(qtyInput.value);
    if (isNaN(qty) || qty < 0) { alert('Enter a valid quantity'); qtyInput.focus(); return; }

    const p = currentProduct;
    saveStatus('Saving…', '#6b7280');

    fetch(SAVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'stock_take_id=' + TAKE_ID + '&product_id=' + p.id + '&qty=' + qty
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) { saveStatus('Save failed — retry', '#dc2626'); return; }

        sessionScans++;
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-IN', { hour:'2-digit', minute:'2-digit', second:'2-digit' });

        // Update KPI
        const wasNew = (p.counted_qty === null);
        if (wasNew) { kpiScanned++; document.getElementById('kpiScanned').textContent = kpiScanned; document.getElementById('kpiUnscanned').textContent = kpiTotal - kpiScanned; }
        document.getElementById('kpiSessions').textContent = sessionScans;
        document.getElementById('logCount').textContent = sessionScans;

        // Add to log
        const varClass = res.variance > 0 ? 'var-pos' : res.variance < 0 ? 'var-neg' : 'var-zero';
        const varText  = (res.variance >= 0 ? '+' : '') + parseFloat(res.variance).toFixed(2);
        const row = document.createElement('tr');
        row.className = 'scan-log-row';
        row.innerHTML = `<td>${sessionScans}</td>
            <td>${p.item_code || '—'}</td>
            <td><strong>${p.name}</strong></td>
            <td class="text-center">${parseFloat(res.system_qty||0).toFixed(2)}</td>
            <td class="text-center text-primary font-weight-bold">+${qty.toFixed(2)}</td>
            <td class="text-center font-weight-bold">${parseFloat(res.new_qty).toFixed(2)}</td>
            <td class="text-center ${varClass}">${varText}</td>
            <td class="text-right text-muted" style="font-size:.72rem;">${timeStr}</td>`;
        const tbody = document.getElementById('scanLog');
        tbody.insertBefore(row, tbody.firstChild);

        saveStatus('<i class="fas fa-check-circle" style="color:#16a34a;"></i> Saved ✓', '#16a34a');

        hideItemCard();
        focusScan();
    })
    .catch(() => saveStatus('Network error — saved locally, retry', '#dc2626'));
}

function saveStatus(msg, color) {
    scanStatus.innerHTML = '<span style="color:' + color + ';font-weight:600;">' + msg + '</span>';
    setTimeout(() => { scanStatus.innerHTML = ''; }, 2000);
}

function openFinalize() {
    document.getElementById('modScanned').textContent   = kpiScanned;
    document.getElementById('modUnscanned').textContent = kpiTotal - kpiScanned;
    $('#finalizeModal').modal('show');
}

function submitFinalize() {
    const zeroUncounted = document.querySelector('input[name="unscanned_opt"]:checked').value === 'zero';
    const adjustStock   = document.getElementById('chkAdjust').checked;

    const doFinalize = () => {
        const form = document.createElement('form');
        form.method = 'POST'; form.action = FINALIZE_URL;
        const csrf = document.createElement('input'); csrf.type='hidden'; csrf.name='<?= $csrf_name ?>'; csrf.value='<?= $csrf_hash ?>';
        const adj  = document.createElement('input'); adj.type='hidden';  adj.name='adjust_stock';         adj.value = adjustStock ? '1' : '0';
        form.appendChild(csrf); form.appendChild(adj);
        document.body.appendChild(form); form.submit();
    };

    if (zeroUncounted) {
        fetch(ZERO_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'stock_take_id=' + TAKE_ID
        }).then(() => doFinalize());
    } else {
        doFinalize();
    }
}
</script>
