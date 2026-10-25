<?php // FILE: application/views/products/print_barcodes.php ?>
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h4 class="font-weight-bold mb-0 text-dark">Print Barcode Labels</h4>
        <small class="text-muted">Generate product barcode labels with custom ingredients lists or print directly to TSC printer</small>
    </div>
    <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Products</a>
</div>

<div class="row no-print">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 12px;">
            <h6 class="font-weight-bold text-dark mb-3">Label Settings</h6>
            
            <style>
            .psd-item:hover, .psd-item.active { background:#e6f0fa; }
            </style>
            <!-- Master Search Bar -->
            <div class="form-group mb-3" style="position:relative;">
                <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.85rem;">Search & Select Product</label>
                <input type="text" id="purSearch" class="form-control font-weight-bold" autocomplete="off" placeholder="Type product name, item code or scan barcode..." style="border-radius: 8px; border: 2px solid #4e73df; padding-right:30px;">
                <div id="purSearchDrop" style="z-index:9999; max-height:280px; overflow-y:auto; display:none; position:absolute; top:65px; left:0; width:100%; background:#fff; border:1px solid #ccc; box-shadow: 2px 2px 5px rgba(0,0,0,.2); border-radius:6px;"></div>
                <small id="purSearchStatus" style="position:absolute; right:10px; top:38px; font-size:10px; color:#666;"></small>
            </div>
            
            <!-- Tab Navigation for Print Mode -->
            <ul class="nav nav-tabs nav-justified mb-3" id="printModeTab" role="tablist" style="border-bottom: 2px solid #f1f3f9;">
                <li class="nav-item">
                    <a class="nav-link active font-weight-bold" id="tsc-direct-tab" data-toggle="tab" href="#tsc-direct" role="tab" style="font-size: 0.8rem; border: none; border-bottom: 2px solid transparent; color: #6c757d; padding: 8px 5px;">
                        🖨️ TSC Direct (PRN)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold" id="browser-html-tab" data-toggle="tab" href="#browser-html" role="tab" style="font-size: 0.8rem; border: none; border-bottom: 2px solid transparent; color: #6c757d; padding: 8px 5px;">
                        🌐 Browser HTML
                    </a>
                </li>
            </ul>

            <style>
                #printModeTab .nav-link.active {
                    color: #4e73df !important;
                    border-bottom: 2px solid #4e73df !important;
                    background: none;
                }
                #printModeTab .nav-link:hover {
                    border-bottom: 2px solid #ddd;
                }
            </style>

            <div class="tab-content" id="printModeTabContent">
                <!-- 1. Direct TSC Printer Tab -->
                <div class="tab-pane fade show active" id="tsc-direct" role="tabpanel">
                    <form id="tscDirectForm" onsubmit="return false;">
                        <!-- Replaced by master search -->
                        <input type="hidden" id="tsc_product_id">

                        <div class="form-group mb-2">
                            <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.8rem;">Select PRN Template</label>
                            <select id="tsc_template" class="form-control form-control-sm border-light shadow-xs" style="border-radius: 8px;" required>
                                <option value="">-- Choose Template --</option>
                                <?php foreach($prn_templates as $t): ?>
                                    <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.8rem;">Number of Labels</label>
                            <input type="number" id="tsc_qty" class="form-control form-control-sm border-light shadow-xs" style="border-radius: 8px;" value="2" min="1" max="500" onkeydown="if(event.key==='Enter') document.getElementById('btnTscPrint').focus();">
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.8rem;">Mfg Date (Packed On)</label>
                            <input type="date" id="tsc_mfg_date" class="form-control form-control-sm border-light shadow-xs" style="border-radius: 8px;" value="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Dynamic Editable Variables Form -->
                        <div id="tsc_variables_container" class="border rounded p-3 bg-light mb-3 d-none">
                            <h6 class="font-weight-bold text-dark mb-2" style="font-size: 0.85rem;"><i class="fas fa-edit text-primary mr-1"></i> Edit Variables to Print</h6>
                            
                            <div class="form-group mb-2">
                                <label class="font-weight-bold text-secondary mb-0" style="font-size: 0.7rem;">Product Name (@ITEM_NAME)</label>
                                <input type="text" id="tsc_var_name" class="form-control form-control-sm border-light" style="border-radius: 6px;">
                            </div>
                            
                            <div class="form-group mb-2">
                                <label class="font-weight-bold text-secondary mb-0" style="font-size: 0.7rem;">Barcode / PLU (@PLU_CODE1)</label>
                                <input type="text" id="tsc_var_barcode" class="form-control form-control-sm border-light" style="border-radius: 6px;">
                            </div>

                            <div class="row">
                                <div class="col-4 pr-1">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold text-secondary mb-0" style="font-size: 0.7rem;">MRP (@MRP1)</label>
                                        <input type="text" id="tsc_var_mrp" class="form-control form-control-sm border-light" style="border-radius: 6px;">
                                    </div>
                                </div>
                                <div class="col-4 px-1">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold text-secondary mb-0" style="font-size: 0.7rem;">SP (@SP)</label>
                                        <input type="text" id="tsc_var_sp" class="form-control form-control-sm border-light" style="border-radius: 6px;">
                                    </div>
                                </div>
                                <div class="col-4 pl-1">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold text-secondary mb-0" style="font-size: 0.7rem;">Expiry Days</label>
                                        <input type="number" id="tsc_expiry_days" class="form-control form-control-sm border-light" style="border-radius: 6px;" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="font-weight-bold text-secondary mb-0" style="font-size: 0.7rem;">Ingredients (@INGREDIENTS)</label>
                                <textarea id="tsc_var_ingredients" class="form-control form-control-sm border-light" rows="2" style="border-radius: 6px;" placeholder="e.g. Flour, Sugar..."></textarea>
                            </div>
                        </div>

                        <div class="form-group mb-3 p-2 bg-light rounded" style="font-size: 0.75rem;">
                            <span class="font-weight-bold text-dark d-block mb-1">Active Printer Setup:</span>
                            <code class="text-primary font-weight-bold" id="active_printer_display"><?= htmlspecialchars($tsc_printer_name) ?></code>
                            <a href="<?= base_url('settings') ?>" class="float-right text-muted" style="text-decoration: underline;">Change Settings</a>
                        </div>

                        <button type="button" id="btnTscPrint" onclick="printDirectTSC()" class="btn btn-primary btn-block font-weight-bold shadow-sm mb-2" style="border-radius: 8px; background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); border: none; padding: 10px;">
                            🚀 Send to TSC Printer
                        </button>
                        <button type="button" onclick="downloadPRN()" class="btn btn-outline-dark btn-block btn-sm font-weight-bold" style="border-radius: 8px;">
                            📥 Download PRN File
                        </button>
                    </form>
                </div>

                <!-- 2. Browser HTML Print Tab -->
                <div class="tab-pane fade" id="browser-html" role="tabpanel">
                    <!-- Replaced by master search -->
                    <input type="hidden" id="barcode_product_id">

                    <div class="form-group mb-2">
                        <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.8rem;">Number of Labels</label>
                        <input type="number" id="barcode_qty" class="form-control form-control-sm border-light shadow-xs" style="border-radius: 8px;" value="2" min="1" max="100" onkeydown="if(event.key==='Enter') document.getElementById('btnHtmlPrint').focus();">
                    </div>

                    <div class="form-group mb-2">
                        <label class="font-weight-bold text-secondary mb-1" style="font-size: 0.8rem;">Print Template Format</label>
                        <select id="barcode_format" class="form-control form-control-sm border-light shadow-xs" style="border-radius: 8px;">
                            <option value="roll">Single Label Roll (50mm x 25mm)</option>
                            <option value="roll_2up">2-Up Label Roll (100mm x 25mm) for 4-inch printers</option>
                            <option value="sheet">A4 Label Sheet (3 Columns x 8 Rows - 24 per page)</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary mb-2" style="font-size: 0.8rem;">Customize Elements</label>
                        <div class="custom-control custom-checkbox mb-1" style="font-size: 0.8rem;">
                            <input type="checkbox" class="custom-control-input" id="cfg_name" checked>
                            <label class="custom-control-label text-dark" for="cfg_name">Print Product Name</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-1" style="font-size: 0.8rem;">
                            <input type="checkbox" class="custom-control-input" id="cfg_mrp" checked>
                            <label class="custom-control-label text-dark" for="cfg_mrp">Print Price (MRP)</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-1" style="font-size: 0.8rem;">
                            <input type="checkbox" class="custom-control-input" id="cfg_ingredients" checked>
                            <label class="custom-control-label text-dark" for="cfg_ingredients">Print Ingredients</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-1" style="font-size: 0.8rem;">
                            <input type="checkbox" class="custom-control-input" id="cfg_expiry" checked>
                            <label class="custom-control-label text-dark" for="cfg_expiry">Print Expiry Date</label>
                        </div>
                    </div>

                    <button type="button" id="btnHtmlPrint" onclick="generateLabels()" class="btn btn-primary btn-block font-weight-bold shadow-sm" style="border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 10px;">
                        Generate Labels
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Preview & Output Console -->
    <div class="col-md-8 mb-4">
        <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 12px; min-height: 480px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="font-weight-bold text-dark mb-0">Label Preview Sheet / Console</h6>
                <button type="button" onclick="window.print()" id="btnPrint" class="btn btn-success btn-sm font-weight-bold shadow-sm px-4" style="border-radius: 8px; display: none;">
                    🖨️ Print Labels
                </button>
            </div>

            <!-- Printer Status Banner -->
            <div id="printerStatusBanner" class="alert d-none mb-3" role="alert" style="border-radius: 8px;"></div>
            
            <div id="previewContainer" class="d-flex flex-wrap justify-content-center p-3 border rounded bg-light mb-3" style="min-height: 280px; overflow-y: auto; max-height: 380px;">
                <div class="text-center text-muted my-auto">
                    <span style="font-size: 3rem; display: block;" class="mb-2">🏷️</span>
                    <span>Set options on the left and click **Generate Labels** or **Send to TSC Printer**.</span>
                </div>
            </div>

            <!-- TSPL Code Output Panel -->
            <div id="tsplCodeContainer" class="d-none">
                <h7 class="font-weight-bold text-dark mb-2 d-block" style="font-size: 0.85rem;">Generated TSPL Printer Commands</h7>
                <textarea id="tsplCodeArea" class="form-control bg-dark text-warning font-weight-bold p-3" rows="8" readonly style="font-family: monospace; font-size: 0.8rem; border-radius: 8px; resize: none;"></textarea>
            </div>
        </div>
    </div>
</div>

<!-- Printable Area (Always visible on print, formatted by print media queries) -->
<div id="printArea" class="d-none d-print-block"></div>

<!-- Load JsBarcode Library via CDN -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Master product variable
    window.selectedLabelProduct = null;

    var purSearchInput = document.getElementById('purSearch');
    var purSearchDrop = document.getElementById('purSearchDrop');
    var purSearchStatus = document.getElementById('purSearchStatus');
    var searchTimer = null;
    var searchIdx = -1;

    if (purSearchInput) {
        purSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            var q = this.value.trim();
            if (q.length < 1) { hideDrop(); return; }
            searchTimer = setTimeout(() => fetchProducts(q), 220);
        });

        purSearchInput.addEventListener('keydown', function(e) {
            var items = purSearchDrop.querySelectorAll('.psd-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault(); searchIdx = Math.min(searchIdx + 1, items.length - 1); highlightDrop(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault(); searchIdx = Math.max(searchIdx - 1, 0); highlightDrop(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                var active = purSearchDrop.querySelector('.psd-item.active');
                if (active) active.click();
                else if (items.length === 1) items[0].click();
            } else if (e.key === 'Escape') {
                hideDrop();
            }
        });

        document.addEventListener('click', function(e) {
            if (!purSearchDrop.contains(e.target) && e.target !== purSearchInput) hideDrop();
        });
    }

    function highlightDrop(items) {
        items.forEach((el, i) => el.classList.toggle('active', i === searchIdx));
        if (items[searchIdx]) items[searchIdx].scrollIntoView({ block:'nearest' });
    }

    function hideDrop() {
        if(purSearchDrop) purSearchDrop.style.display = 'none';
        searchIdx = -1;
    }

    function fetchProducts(q) {
        purSearchStatus.textContent = 'Searching…';
        fetch('<?= base_url("products/search_ajax") ?>?q=' + encodeURIComponent(q) + '&limit=12')
            .then(r => r.json())
            .then(data => {
                var products = data.products || data;
                if (!products.length) {
                    purSearchDrop.innerHTML = '<div class="psd-item text-muted" style="padding:10px;">No products found</div>';
                    purSearchDrop.style.display = 'block';
                    purSearchStatus.textContent = '';
                    return;
                }
                searchIdx = -1;
                purSearchDrop.innerHTML = products.map(p => {
                    var stock = parseFloat(p.stock_qty || 0);
                    var stockColor = stock <= 0 ? '#ef4444' : stock < 5 ? '#f59e0b' : '#16a34a';
                    return `<div class="psd-item" data-product='${JSON.stringify(p).replace(/'/g,"&#39;")}' style="padding:8px; cursor:pointer; border-bottom:1px solid #eee; display:flex; align-items:center; gap:10px;">
                        <div>
                            <div style="font-weight:bold; font-size:13px; color:#1e40af;">${p.name}</div>
                            <div style="font-size:11px; color:#666;">${p.item_code || ''} ${p.barcode ? '· ' + p.barcode : ''}</div>
                        </div>
                        <div style="font-size:11px; color:#333; margin-left:auto; text-align:right; white-space:nowrap;">
                            <span style="color:${stockColor};font-weight:600;">Stock: ${stock}</span><br>
                            SP: ₹${parseFloat(p.selling_price||0).toFixed(2)} &nbsp; MRP: ₹${parseFloat(p.mrp||0).toFixed(2)}
                        </div>
                    </div>`;
                }).join('');
                purSearchDrop.style.display = 'block';
                purSearchStatus.textContent = products.length + ' result(s)';
                purSearchDrop.querySelectorAll('.psd-item').forEach(el => {
                    el.addEventListener('click', function() {
                        var p = JSON.parse(this.dataset.product);
                        selectProductForLabel(p);
                    });
                });
            })
            .catch(() => { purSearchStatus.textContent = 'Search error'; });
    }

    function selectProductForLabel(p) {
        hideDrop();
        purSearchInput.value = p.name + ' (' + (p.barcode || p.item_code || '') + ')';
        purSearchStatus.textContent = '';
        
        window.selectedLabelProduct = p;
        document.getElementById('tsc_product_id').value = p.id;
        document.getElementById('barcode_product_id').value = p.id;

        // Populate TSC variables form
        var barcode = p.pcs_barcode || p.barcode || p.item_code || '';
        document.getElementById('tsc_var_name').value = p.name;
        document.getElementById('tsc_var_barcode').value = barcode;
        document.getElementById('tsc_var_mrp').value = parseFloat(p.mrp||0).toFixed(2);
        document.getElementById('tsc_var_sp').value = parseFloat(p.selling_price||0).toFixed(2);
        document.getElementById('tsc_expiry_days').value = p.expiry_days || 0;
        document.getElementById('tsc_var_ingredients').value = p.ingredients || '';
        
        var container = document.getElementById('tsc_variables_container');
        if (container) container.classList.remove('d-none');
        
        var activeTab = document.querySelector('#printModeTab .nav-link.active').getAttribute('href');
        if (activeTab === '#tsc-direct') {
            document.getElementById('tsc_qty').focus();
            document.getElementById('tsc_qty').select();
        } else {
            document.getElementById('barcode_qty').focus();
            document.getElementById('barcode_qty').select();
        }
    }

    // Reset UI when switching tabs
    var tabs = document.querySelectorAll('#printModeTab a[data-toggle="tab"]');
    tabs.forEach(function(tab) {
        $(tab).on('shown.bs.tab', function(e) {
            document.getElementById('printerStatusBanner').classList.add('d-none');
            document.getElementById('tsplCodeContainer').classList.add('d-none');
            document.getElementById('btnPrint').style.display = 'none';
            
            var container = document.getElementById('previewContainer');
            container.innerHTML = '<div class="text-center text-muted my-auto"><span style="font-size: 3rem; display: block;" class="mb-2">🏷️</span><span>Set options on the left and click command button.</span></div>';
        });
    });
});

function printDirectTSC() {
    var productId = document.getElementById('tsc_product_id').value;
    var template = document.getElementById('tsc_template').value;
    var qty = document.getElementById('tsc_qty').value;
    var mfgDate = document.getElementById('tsc_mfg_date').value;

    // Collect values from the variables editor form
    var name = document.getElementById('tsc_var_name').value;
    var barcode = document.getElementById('tsc_var_barcode').value;
    var mrp = document.getElementById('tsc_var_mrp').value;
    var sp = document.getElementById('tsc_var_sp').value;
    var expiryDays = document.getElementById('tsc_expiry_days').value;
    var ingredients = document.getElementById('tsc_var_ingredients').value;

    if (!productId) {
        alert('Please select a product.');
        return;
    }
    if (!template) {
        alert('Please select a PRN template.');
        return;
    }
    if (!qty || qty < 1) {
        alert('Please enter a valid quantity.');
        return;
    }

    var banner = document.getElementById('printerStatusBanner');
    banner.className = 'alert alert-info mb-3';
    banner.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sending print command to printer...';
    banner.classList.remove('d-none');

    // Perform AJAX call
    $.ajax({
        url: '<?= base_url("products/print_tsc_direct") ?>',
        type: 'POST',
        data: {
            product_id: productId,
            template: template,
            qty: qty,
            mfg_date: mfgDate,
            product_name: name,
            barcode: barcode,
            mrp: mrp,
            sp: sp,
            expiry_days: expiryDays,
            ingredients: ingredients
        },
        dataType: 'json',
        success: function(res) {
            // Show TSPL commands code
            document.getElementById('tsplCodeContainer').classList.remove('d-none');
            document.getElementById('tsplCodeArea').value = res.commands;

            // Hide web preview labels and show nice status
            var container = document.getElementById('previewContainer');
            document.getElementById('btnPrint').style.display = 'none';

            if (res.status === 'success') {
                container.innerHTML = '<div class="text-center text-success my-auto"><span style="font-size: 4rem; display: block;" class="mb-2">✓</span><strong>Success! Sent to Printer</strong><br><small class="text-muted">Direct print command sent to ' + escapeHtml(res.printer) + '.</small></div>';
                banner.className = 'alert alert-success mb-3';
                banner.innerHTML = '<strong>Success!</strong> Barcode commands sent to printer successfully! (Printer: <code>' + escapeHtml(res.printer) + '</code>)';
            } else {
                container.innerHTML = '<div class="text-center text-warning my-auto"><span style="font-size: 4rem; display: block;" class="mb-2">⚠</span><strong>Print Command Generated with Warning</strong><br><small class="text-muted">Printer connection could not be opened directly. See details below.</small></div>';
                banner.className = 'alert alert-warning mb-3';
                banner.innerHTML = '<strong>Warning!</strong> ' + res.message + '<br><small class="d-block mt-1">Error Details: ' + escapeHtml(res.error_detail) + '</small><br><small class="d-block mt-1">If printing fails, make sure your TSC printer is online, connected, and shared on the network as <code>' + escapeHtml(res.printer) + '</code>, or download the PRN file to print manually.</small>';
            }
        },
        error: function(xhr, status, err) {
            banner.className = 'alert alert-danger mb-3';
            banner.innerHTML = '<strong>Error!</strong> Failed to connect to server printer API. Check server connection.';
        }
    });
}

function downloadPRN() {
    var productId = document.getElementById('tsc_product_id').value;
    var template = document.getElementById('tsc_template').value;
    var qty = document.getElementById('tsc_qty').value;
    var mfgDate = document.getElementById('tsc_mfg_date').value;

    // Collect values from variables editor form
    var name = document.getElementById('tsc_var_name').value;
    var barcode = document.getElementById('tsc_var_barcode').value;
    var mrp = document.getElementById('tsc_var_mrp').value;
    var sp = document.getElementById('tsc_var_sp').value;
    var expiryDays = document.getElementById('tsc_expiry_days').value;
    var ingredients = document.getElementById('tsc_var_ingredients').value;

    if (!productId) {
        alert('Please select a product first.');
        return;
    }
    if (!template) {
        alert('Please select a template first.');
        return;
    }

    // Force a fresh generation by requesting the AJAX endpoint
    $.ajax({
        url: '<?= base_url("products/print_tsc_direct") ?>',
        type: 'POST',
        data: {
            product_id: productId,
            template: template,
            qty: qty,
            mfg_date: mfgDate,
            product_name: name,
            barcode: barcode,
            mrp: mrp,
            sp: sp,
            expiry_days: expiryDays,
            ingredients: ingredients
        },
        dataType: 'json',
        success: function(res) {
            document.getElementById('tsplCodeContainer').classList.remove('d-none');
            document.getElementById('tsplCodeArea').value = res.commands;
            triggerDownload(res.commands, template);
        },
        error: function() {
            alert('Failed to generate template for download.');
        }
    });
}

function triggerDownload(text, templateName) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    
    var cleanName = templateName.replace(/\.[^/.]+$/, ""); // strip extension
    element.setAttribute('download', cleanName + "_output.prn");

    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}

function generateLabels() {
    var p = window.selectedLabelProduct;
    
    if (!p) {
        alert('Please select a product first.');
        return;
    }

    var name = p.name;
    var barcode = p.pcs_barcode || p.barcode || p.item_code || '';
    var mrp = parseFloat(p.mrp||0).toFixed(2);
    var ingredients = p.ingredients || '';
    var expiryDays = parseInt(p.expiry_days) || 0;
    
    var qty = parseInt(document.getElementById('barcode_qty').value) || 1;
    var format = document.getElementById('barcode_format').value;
    
    var showName = document.getElementById('cfg_name').checked;
    var showMrp = document.getElementById('cfg_mrp').checked;
    var showIngredients = document.getElementById('cfg_ingredients').checked;
    var showExpiry = document.getElementById('cfg_expiry').checked;

    if (!barcode) {
        alert('This product does not have a barcode or item code set.');
        return;
    }

    var previewContainer = document.getElementById('previewContainer');
    var printArea = document.getElementById('printArea');
    
    previewContainer.innerHTML = '';
    printArea.innerHTML = '';
    
    // Set preview classes based on format
    previewContainer.className = 'd-flex flex-wrap justify-content-start p-3 border rounded bg-light ' + (format === 'sheet' ? 'preview-sheet' : 'preview-roll');

    // Calculate dynamic expiry date based on shelf life days
    var expDateText = '';
    if (expiryDays > 0) {
        var d = new Date();
        d.setDate(d.getDate() + expiryDays);
        var dd = String(d.getDate()).padStart(2, '0');
        var mm = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"][d.getMonth()];
        var yyyy = d.getFullYear();
        expDateText = dd + ' ' + mm + ' ' + yyyy;
    }

    // Build labels
    for (var i = 0; i < qty; i++) {
        // Label HTML structure
        var labelHtml = '<div class="barcode-label format-' + format + '">';
        
        if (showName) {
            labelHtml += '<div class="label-name">' + escapeHtml(name) + '</div>';
        }
        
        labelHtml += '<div class="label-barcode-container"><svg class="barcode-svg" id="barcode_svg_' + i + '"></svg></div>';
        
        if (showMrp) {
            labelHtml += '<div class="label-price">MRP: <strong>₹' + mrp + '</strong></div>';
        }
        
        if (showExpiry && expDateText) {
            labelHtml += '<div class="label-expiry">Exp: <strong>' + expDateText + '</strong></div>';
        }
        
        if (showIngredients && ingredients) {
            // Trim ingredients to fit the tag
            var maxLen = format === 'sheet' ? 85 : 65;
            var trimmedIngredients = ingredients.length > maxLen ? ingredients.substring(0, maxLen) + '...' : ingredients;
            labelHtml += '<div class="label-ingredients">Ingredients: ' + escapeHtml(trimmedIngredients) + '</div>';
        }
        
        labelHtml += '</div>';
        
        // Append to both preview and print area
        previewContainer.insertAdjacentHTML('beforeend', labelHtml);
        printArea.insertAdjacentHTML('beforeend', labelHtml);
    }
    
    // Render Barcodes in SVG
    for (var i = 0; i < qty; i++) {
        try {
            var bcWidth = format === 'sheet' ? 1.4 : 1.2;
            var bcHeight = format === 'sheet' ? 30 : 25;
            JsBarcode("#previewContainer #barcode_svg_" + i, barcode, {
                format: "CODE128",
                width: bcWidth,
                height: bcHeight,
                displayValue: true,
                fontSize: 10,
                margin: 0
            });
            JsBarcode("#printArea #barcode_svg_" + i, barcode, {
                format: "CODE128",
                width: bcWidth,
                height: bcHeight,
                displayValue: true,
                fontSize: 10,
                margin: 0
            });
        } catch (err) {
            console.error("Barcode generation error: ", err);
        }
    }
    
    document.getElementById('btnPrint').style.display = 'inline-block';
}

function escapeHtml(text) {
    if (!text) return '';
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>

<style>
/* CSS styles for Barcode labels */
.barcode-label {
    background: #fff;
    color: #000;
    font-family: Arial, sans-serif;
    border: 1px dashed #ccc;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    overflow: hidden;
    padding: 5px;
}

/* Roll Printer format (50mm x 25mm) */
.barcode-label.format-roll, .barcode-label.format-roll_2up {
    width: 190px; /* ~50mm */
    height: 95px; /* ~25mm */
    margin: 5px;
    border-radius: 4px;
}
.barcode-label.format-roll .label-name, .barcode-label.format-roll_2up .label-name {
    font-size: 8px;
    font-weight: bold;
    max-height: 20px;
    overflow: hidden;
    margin-bottom: 2px;
}
.barcode-label.format-roll .label-price, .barcode-label.format-roll_2up .label-price {
    font-size: 8px;
    margin-top: 1px;
}
.barcode-label.format-roll .label-expiry, .barcode-label.format-roll_2up .label-expiry {
    font-size: 7.5px;
    font-weight: bold;
    margin-top: 1px;
}
.barcode-label.format-roll .label-ingredients, .barcode-label.format-roll_2up .label-ingredients {
    font-size: 6px;
    color: #333;
    line-height: 1.1;
    margin-top: 2px;
    max-height: 16px;
    overflow: hidden;
}

/* A4 Sheet format (65mm x 38mm, 3x8 Grid) */
.barcode-label.format-sheet {
    width: 245px; /* ~65mm */
    height: 140px; /* ~38mm */
    margin: 8px;
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ddd;
}
.barcode-label.format-sheet .label-name {
    font-size: 10px;
    font-weight: bold;
    max-height: 26px;
    overflow: hidden;
    margin-bottom: 3px;
}
.barcode-label.format-sheet .label-price {
    font-size: 9px;
    margin-top: 2px;
}
.barcode-label.format-sheet .label-expiry {
    font-size: 8.5px;
    font-weight: bold;
    margin-top: 2px;
}
.barcode-label.format-sheet .label-ingredients {
    font-size: 7.5px;
    color: #444;
    line-height: 1.2;
    margin-top: 3px;
    max-height: 24px;
    overflow: hidden;
}

/* Preview layouts */
#previewContainer.preview-roll {
    justify-content: center;
}
#previewContainer.preview-sheet {
    justify-content: flex-start;
}

/* PRINT MEDIA QUERIES */
@media print {
    /* Hide all other items */
    nav, .no-print, .alert, header, footer, .navbar, .mb-4 {
        display: none !important;
    }
    body {
        background: #fff !important;
        color: #000 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    #printArea {
        display: block !important;
    }

    /* Single Roll Print Format */
    body:has(.format-roll) #printArea {
        display: block !important;
    }
    body:has(.format-roll) .barcode-label.format-roll {
        width: 50mm !important;
        height: 25mm !important;
        border: none !important;
        margin: 0 !important;
        padding: 2mm 1mm !important;
        page-break-after: always !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        align-items: center !important;
    }

    /* 2-Up Roll Print Format for 4-inch printers */
    body:has(.format-roll_2up) #printArea {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        width: 100mm !important;
        align-content: flex-start !important;
    }
    body:has(.format-roll_2up) .barcode-label.format-roll_2up {
        width: 50mm !important;
        height: 25mm !important;
        border: none !important;
        margin: 0 !important;
        padding: 2mm 1mm !important;
        box-sizing: border-box !important;
        page-break-after: auto !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        align-items: center !important;
    }
    /* Force page break every 2 labels (which is every row for a 100mm printer) */
    body:has(.format-roll_2up) .barcode-label.format-roll_2up:nth-child(even) {
        page-break-after: always !important;
    }
    
    /* A4 Grid Sheet Print Format */
    body:has(.format-sheet) #printArea {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 2mm !important;
        padding: 10mm 5mm !important;
        width: 210mm !important;
        box-sizing: border-box !important;
    }
    body:has(.format-sheet) .barcode-label.format-sheet {
        width: 65mm !important;
        height: 38mm !important;
        border: 1px dashed #bbb !important;
        margin: 0 !important;
        padding: 3mm !important;
        page-break-inside: avoid !important;
    }
}
</style>
