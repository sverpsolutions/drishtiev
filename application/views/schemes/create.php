<?php // Scheme Create — Full Scheme Builder ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-plus-circle text-primary mr-2"></i>Create New Scheme</h4>
        <small class="text-muted">Configure any scheme type — Qty Slab, Free Item, Mix &amp; Match, Target, etc.</small>
    </div>
    <a href="<?= base_url('schemes') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left mr-1"></i>Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div><?php endif; ?>

<form method="post" action="<?= base_url('schemes/save') ?>" id="schemeForm">

<!-- ── Step 1: Basic Info ────────────────────────────────── -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white"><i class="bi bi-info-circle mr-2"></i>Step 1 — Scheme Identity</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-5">
                <label class="form-label font-weight-bold">Scheme Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="e.g. HUL Surf Excel Q3 2026 Scheme" required>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Scheme Type <span class="text-danger">*</span></label>
                <select name="scheme_type_id" id="scheme_type_id" class="form-control" required onchange="showTypeFields()">
                    <option value="">-- Select Type --</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t->id ?>" data-code="<?= $t->code ?>"><?= htmlspecialchars($t->label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12" id="cheapest_free_help" style="display:none;">
                <div class="alert alert-info py-2 px-3 mt-2 mb-0" style="font-size:.85rem;">
                    <strong>Buy N &mdash; Cheapest Free.</strong>
                    Set <strong>Minimum Quantity</strong> to how many items the customer must pick up
                    (<strong>3</strong> for a 2+1 offer &mdash; it is the group size, not the number paid for)
                    and <strong>Free Qty</strong> to how many of the cheapest are free (<strong>1</strong>).
                    Tick <strong>Recursive</strong> so 6 items give 2 free.
                    Choose <strong>Apply On &rarr; Brand</strong> to limit it to one brand.
                    The lowest-MRP item in each group is the one given free.
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label font-weight-bold">Priority <span class="text-muted small">(1=High)</span></label>
                <input type="number" name="priority" class="form-control" value="5" min="1" max="10">
            </div>
            <div class="col-md-2">
                <label class="form-label font-weight-bold">Status</label>
                <select name="is_active" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">Inactive (Draft)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Apply On</label>
                <select name="apply_on" id="apply_on" class="form-control" onchange="showApplyFields()">
                    <option value="item">Specific Items</option>
                    <option value="brand">Brand</option>
                    <option value="category">Category</option>
                    <option value="brand_category">Brand &amp; Category</option>
                    <option value="invoice">Entire Invoice</option>
                    <option value="mix_match">Mix &amp; Match Group</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Start Date <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">End Date <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-t') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Applicable Parties</label>
                <select name="applicable_parties" id="applicable_parties" class="form-control" onchange="togglePartySelect()">
                    <option value="all">All Customers</option>
                    <option value="selected">Selected Customers</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Start Time <small class="text-muted">(optional)</small></label>
                <input type="time" name="start_time" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">End Time <small class="text-muted">(optional)</small></label>
                <input type="time" name="end_time" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Expiry Days Trigger <small class="text-muted">(optional)</small></label>
                <input type="number" name="expiry_days_trigger" class="form-control" placeholder="e.g. 1" min="0">
            </div>
            <div class="col-12 mt-2">
                <label class="form-label font-weight-bold">Notes / Internal Memo</label>
                <input type="text" name="notes" class="form-control" placeholder="Internal notes about this scheme...">
            </div>
        </div>
    </div>
</div>

<!-- ── Step 2: Triggers (Min Qty / Value) ───────────────── -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-warning text-dark"><i class="bi bi-lightning mr-2"></i>Step 2 — Trigger Conditions</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Minimum Quantity</label>
                <div class="input-group">
                    <input type="number" name="min_qty" class="form-control" value="0" min="0" step="0.001">
                    <select name="min_qty_uom" class="form-control" style="max-width:80px">
                        <option value="pcs">PCS</option>
                        <option value="crt">CRT</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Minimum Bill Value (&#8377;)</label>
                <input type="number" name="min_value" class="form-control" value="0" min="0" step="0.01">
            </div>
            <div class="col-md-3" id="carton_size_wrap" style="display:none">
                <label class="form-label font-weight-bold">Carton Size (pcs/crt)</label>
                <input type="number" name="carton_size" class="form-control" value="12" min="1">
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Max Benefit / Invoice (&#8377;)</label>
                <input type="number" name="max_benefit_per_invoice" class="form-control" value="0" min="0" placeholder="0 = unlimited">
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_recursive" value="1" id="is_recursive">
                    <label class="form-check-label font-weight-bold" for="is_recursive">
                        Recursive <small class="text-muted">(e.g. Buy 10 Get 1 → Buy 20 Get 2)</small>
                    </label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_combination_allowed" value="1" id="is_combo" checked>
                    <label class="form-check-label font-weight-bold" for="is_combo">
                        Allow Combination <small class="text-muted">(uncheck = exclusive)</small>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Step 3: Benefit ──────────────────────────────────── -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-success text-white"><i class="bi bi-gift mr-2"></i>Step 3 — Benefit / Reward</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-2">
                <label class="form-label font-weight-bold">Discount %</label>
                <input type="number" name="discount_percent" id="disc_pct1" class="form-control"
                       value="0" min="0" max="100" step="0.01" oninput="previewCascade()">
            </div>
            <div class="col-md-2">
                <label class="form-label font-weight-bold">+ Extra %
                    <small class="text-muted">(optional)</small></label>
                <input type="number" name="discount_percent2" id="disc_pct2" class="form-control"
                       value="0" min="0" max="100" step="0.01" oninput="previewCascade()">
                <small class="text-muted">Applied on the balance</small>
            </div>
            <div class="col-md-2">
                <label class="form-label font-weight-bold">Flat Discount (&#8377;)</label>
                <input type="number" name="discount_value" class="form-control" value="0" min="0" step="0.01">
            </div>
            <div class="col-12" id="cascade_preview_wrap" style="display:none;">
                <div class="alert alert-success py-2 px-3 mt-1 mb-2" style="font-size:.85rem;">
                    <i class="fas fa-calculator mr-1"></i> <span id="cascade_preview"></span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Free Qty (PCS)</label>
                <input type="number" name="free_qty" class="form-control" value="0" min="0" step="0.001">
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Free Item (leave blank = same item)</label>
                <select name="free_item_id" class="form-control">
                    <option value="">Same Item</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p->id ?>"><?= htmlspecialchars($p->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Target scheme fields -->
        <div id="target_fields" style="display:none" class="mt-3 border-top pt-3">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Target Amount (&#8377;)</label>
                    <input type="number" name="target_amount" class="form-control" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Period</label>
                    <select name="target_period" class="form-control">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                        <option value="custom">Custom (scheme dates)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Credit note scheme -->
        <div id="cn_fields" style="display:none" class="mt-3 border-top pt-3">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Credit Note %</label>
                    <input type="number" name="credit_note_percent" class="form-control" value="0" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Issue Timing</label>
                    <select name="credit_note_timing" class="form-control">
                        <option value="immediate">Immediate (on billing)</option>
                        <option value="end_of_month">End of Month</option>
                        <option value="end_of_quarter">End of Quarter</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Step 4: Slabs (dynamic rows) ─────────────────────── -->
<div class="card shadow-sm mb-3" id="slabs_section">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-layers mr-2"></i>Step 4 — Quantity / Value Slabs <small class="text-muted">(optional)</small></span>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSlab()"><i class="bi bi-plus"></i> Add Slab</button>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0 table-sm">
            <thead class="table-light">
                <tr>
                    <th>Min Qty</th><th>Max Qty</th><th>Min Value</th><th>Max Value</th>
                    <th>Free Qty</th><th>Disc %</th><th>Disc &#8377;</th><th>Free Item</th><th></th>
                </tr>
            </thead>
            <tbody id="slabsBody"></tbody>
        </table>
    </div>
</div>

<!-- ── Step 5: Item Mapping ──────────────────────────────── -->
<div class="card shadow-sm mb-3" id="item_section">
    <div class="card-header"><i class="bi bi-box-seam mr-2"></i>Step 5 — Item / Brand / Category Mapping</div>
    <div class="card-body">
        <div class="row">
            <div id="product_select_wrap" class="col-md-6">
                <label class="form-label font-weight-bold">Products (Ctrl+Click to select multiple)</label>
                
                <!-- Premium Search & Quick Filters -->
                <div class="mb-2 p-2 border rounded bg-light shadow-xs" style="border-radius: 8px;">
                    <input type="text" id="product_search" class="form-control form-control-sm mb-2" placeholder="🔍 Search or filter products (e.g. 400gm, 1kg, cake)..." style="border-radius: 6px;">
                    
                    <div class="d-flex flex-wrap align-items-center mb-2" style="gap: 4px; font-size: 0.8rem;">
                        <span class="text-secondary font-weight-bold mr-1">Quick Tags:</span>
                        <span class="badge badge-info cursor-pointer px-2 py-1" style="cursor: pointer;" onclick="setQuickFilter('400gm')">400gm</span>
                        <span class="badge badge-info cursor-pointer px-2 py-1" style="cursor: pointer;" onclick="setQuickFilter('1kg')">1kg</span>
                        <span class="badge badge-info cursor-pointer px-2 py-1" style="cursor: pointer;" onclick="setQuickFilter('500gm')">500gm</span>
                        <span class="badge badge-info cursor-pointer px-2 py-1" style="cursor: pointer;" onclick="setQuickFilter('bread')">Bread</span>
                        <span class="badge badge-info cursor-pointer px-2 py-1" style="cursor: pointer;" onclick="setQuickFilter('cake')">Cake</span>
                        <span class="badge badge-info cursor-pointer px-2 py-1" style="cursor: pointer;" onclick="setQuickFilter('bun')">Bun</span>
                        <span class="badge badge-secondary cursor-pointer px-2 py-1" style="cursor: pointer;" onclick="setQuickFilter('')">Clear</span>
                    </div>

                    <div class="d-flex" style="gap: 8px;">
                        <button type="button" class="btn btn-xs btn-primary font-weight-bold py-1 px-2 flex-fill" style="font-size: 0.75rem; border-radius: 4px;" onclick="selectAllFiltered()">
                            ☑️ Select All Filtered
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2 flex-fill" style="font-size: 0.75rem; border-radius: 4px;" onclick="clearProductSelections()">
                            ❌ Clear All Selections
                        </button>
                    </div>
                </div>

                <select name="product_ids[]" id="product_ids" class="form-control" multiple size="8" style="border-radius: 8px;">
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p->id ?>">[<?= $p->item_code ?>] <?= htmlspecialchars($p->name) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Leave empty if applying to brand/category/whole invoice</small>
            </div>
            <div id="brand_select_wrap" class="col-md-3" style="display:none">
                <label class="form-label font-weight-bold">Brand</label>
                <select name="brand_id" class="form-control">
                    <option value="">-- Select Brand --</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b->id ?>"><?= htmlspecialchars($b->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="category_select_wrap" class="col-md-3" style="display:none">
                <label class="form-label font-weight-bold">Category</label>
                <select name="category_id" class="form-control">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- ── Step 6: Free Items List ───────────────────────────── -->
<div class="card shadow-sm mb-3" id="free_items_section">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-gift mr-2"></i>Step 6 — Free Items List <small class="text-muted">(for complex free-item schemes)</small></span>
        <button type="button" class="btn btn-sm btn-outline-success" onclick="addFreeItem()"><i class="bi bi-plus"></i> Add Free Item</button>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0 table-sm">
            <thead class="table-light"><tr><th>Free Product</th><th>Qty</th><th>UOM</th><th></th></tr></thead>
            <tbody id="freeItemsBody"></tbody>
        </table>
    </div>
</div>

<!-- ── Step 7: Selected Parties ──────────────────────────── -->
<div class="card shadow-sm mb-3" id="party_section" style="display:none">
    <div class="card-header"><i class="bi bi-people mr-2"></i>Step 7 — Applicable Customers</div>
    <div class="card-body">
        <select name="customer_ids[]" class="form-control" multiple size="6">
            <?php foreach ($customers as $c): ?>
                <option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">Ctrl+Click to select multiple customers</small>
    </div>
</div>

<!-- Submit -->
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex">
        <button type="submit" class="btn btn-primary btn-lg flex-fill"><i class="bi bi-check-lg mr-2"></i>Save Scheme</button>
        <a href="<?= base_url('schemes') ?>" class="btn btn-outline-secondary btn-lg">Cancel</a>
    </div>
</div>

</form>

<script>
const PRODUCTS = <?= json_encode(array_map(function($p){return['id'=>(int)$p->id,'name'=>$p->name,'item_code'=>$p->item_code??''];}, $products)) ?>;

function showTypeFields() {
    const sel = document.getElementById('scheme_type_id');
    const code = sel.options[sel.selectedIndex]?.dataset.code ?? '';

    document.getElementById('target_fields').style.display   = code === 'TARGET' ? '' : 'none';
    document.getElementById('cn_fields').style.display       = code === 'CREDIT_NOTE' ? '' : 'none';
    document.getElementById('carton_size_wrap').style.display = code === 'CARTON' ? '' : 'none';

    // "Cheapest free" reuses Minimum Quantity as the GROUP SIZE, which is not
    // obvious from the label — spell it out and pre-fill a sane 2+1 so the
    // scheme cannot be saved doing nothing.
    var cf = document.getElementById('cheapest_free_help');
    if (cf) {
        var on = (code === 'CHEAPEST_FREE');
        cf.style.display = on ? '' : 'none';
        if (on) {
            var mq = document.querySelector('[name=min_qty]');
            var fq = document.querySelector('[name=free_qty]');
            if (mq && (!parseInt(mq.value) || parseInt(mq.value) < 2)) mq.value = 3;
            if (fq && !parseInt(fq.value)) fq.value = 1;
        }
    }
}

function showApplyFields() {
    const v = document.getElementById('apply_on').value;
    document.getElementById('product_select_wrap').style.display  = ['item','mix_match','combo'].includes(v) ? '' : 'none';
    document.getElementById('brand_select_wrap').style.display    = ['brand', 'brand_category'].includes(v) ? '' : 'none';
    document.getElementById('category_select_wrap').style.display = ['category', 'brand_category'].includes(v) ? '' : 'none';
}

// Product search filtering logic
document.getElementById('product_search').addEventListener('input', function() {
    const filterText = this.value.toLowerCase().trim();
    const select = document.getElementById('product_ids');
    const options = select.options;
    
    for (let i = 0; i < options.length; i++) {
        const optionText = options[i].text.toLowerCase();
        if (optionText.includes(filterText)) {
            options[i].style.display = '';
        } else {
            options[i].style.display = 'none';
        }
    }
});

// Select all options currently visible/filtered
function selectAllFiltered() {
    const select = document.getElementById('product_ids');
    const options = select.options;
    let count = 0;
    
    for (let i = 0; i < options.length; i++) {
        if (options[i].style.display !== 'none') {
            options[i].selected = true;
            count++;
        }
    }
}

// Clear all product selections
function clearProductSelections() {
    const select = document.getElementById('product_ids');
    const options = select.options;
    
    for (let i = 0; i < options.length; i++) {
        options[i].selected = false;
    }
}

// Set search box input from quick filter tag click
function setQuickFilter(text) {
    const searchInput = document.getElementById('product_search');
    searchInput.value = text;
    searchInput.dispatchEvent(new Event('input'));
}

function togglePartySelect() {
    const v = document.getElementById('applicable_parties').value;
    document.getElementById('party_section').style.display = v === 'selected' ? '' : 'none';
}

let slabCount = 0;
function addSlab() {
    slabCount++;
    const prodOpts = PRODUCTS.map(p=>`<option value="${p.id}">[${p.item_code}] ${p.name}</option>`).join('');
    const html = `<tr id="slab_${slabCount}">
        <td><input type="number" name="slabs[${slabCount}][min_qty]" class="form-control form-control-sm" value="0" step="0.001"></td>
        <td><input type="number" name="slabs[${slabCount}][max_qty]" class="form-control form-control-sm" value="0" step="0.001" placeholder="0=unlimited"></td>
        <td><input type="number" name="slabs[${slabCount}][min_value]" class="form-control form-control-sm" value="0" step="0.01"></td>
        <td><input type="number" name="slabs[${slabCount}][max_value]" class="form-control form-control-sm" value="0" step="0.01"></td>
        <td><input type="number" name="slabs[${slabCount}][free_qty]" class="form-control form-control-sm" value="0" step="0.001"></td>
        <td><input type="number" name="slabs[${slabCount}][disc_pct]" class="form-control form-control-sm" value="0" step="0.01"></td>
        <td><input type="number" name="slabs[${slabCount}][disc_val]" class="form-control form-control-sm" value="0" step="0.01"></td>
        <td><select name="slabs[${slabCount}][free_item_id]" class="form-control form-control-sm"><option value="">Same</option>${prodOpts}</select></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('slab_${slabCount}').remove()"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    document.getElementById('slabsBody').insertAdjacentHTML('beforeend', html);
}

let fiCount = 0;
function addFreeItem() {
    fiCount++;
    const prodOpts = PRODUCTS.map(p=>`<option value="${p.id}">[${p.item_code}] ${p.name}</option>`).join('');
    const html = `<tr id="fi_${fiCount}">
        <td><select name="fi_product_id[]" class="form-control form-control-sm"><option value="">-- Select --</option>${prodOpts}</select></td>
        <td><input type="number" name="fi_qty[]" class="form-control form-control-sm" value="1" min="0.001" step="0.001"></td>
        <td><select name="fi_uom[]" class="form-control form-control-sm"><option value="pcs">PCS</option><option value="crt">CRT</option></select></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('fi_${fiCount}').remove()"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    document.getElementById('freeItemsBody').insertAdjacentHTML('beforeend', html);
}

// Init
showTypeFields();
showApplyFields();
// Auto-add first slab
addSlab();
</script>

<script>
// Shows the operator exactly how "20% + 10% extra" resolves on a ₹100 item,
// because the natural assumption is 30% and the real answer is 28%.
function previewCascade() {
    var p1 = parseFloat(document.getElementById('disc_pct1').value) || 0;
    var p2 = parseFloat(document.getElementById('disc_pct2').value) || 0;
    var wrap = document.getElementById('cascade_preview_wrap');
    if (p2 <= 0 || p1 <= 0) { wrap.style.display = 'none'; return; }

    var base = 100;
    var d1 = base * p1 / 100;
    var rem = base - d1;
    var d2 = rem * p2 / 100;
    var total = d1 + d2;

    wrap.style.display = '';
    document.getElementById('cascade_preview').innerHTML =
        '<strong>On an item of &#8377;100:</strong> ' +
        '&#8377;100 &minus; ' + p1 + '% (&#8377;' + d1.toFixed(2) + ') = &#8377;' + rem.toFixed(2) +
        ' &nbsp;&rarr;&nbsp; &#8377;' + rem.toFixed(2) + ' &minus; ' + p2 + '% (&#8377;' + d2.toFixed(2) + ')' +
        ' = <strong>&#8377;' + (base - total).toFixed(2) + ' net</strong>' +
        ' &nbsp;|&nbsp; total discount <strong>&#8377;' + total.toFixed(2) + '</strong>' +
        ' (effective <strong>' + total.toFixed(2) + '%</strong>, not ' + (p1 + p2) + '%)';
}
document.addEventListener('DOMContentLoaded', previewCascade);
</script>
