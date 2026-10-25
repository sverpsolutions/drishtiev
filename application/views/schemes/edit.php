<?php
// Pre-compute existing maps for JS pre-selection
$existing_product_ids = array_column(array_map('get_object_vars', $scheme->item_map ?? []), 'product_id');
$existing_product_ids = array_filter(array_map('intval', $existing_product_ids));
$existing_brand_id    = null;
$existing_category_id = null;
foreach (($scheme->item_map ?? []) as $m) {
    if ($m->brand_id)    $existing_brand_id    = (int)$m->brand_id;
    if ($m->category_id) $existing_category_id = (int)$m->category_id;
}
$existing_customer_ids = array_column(array_map('get_object_vars', $scheme->party_map ?? []), 'customer_id');
$existing_customer_ids = array_filter(array_map('intval', $existing_customer_ids));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-pencil-square text-warning mr-2"></i>Edit Scheme</h4>
        <small class="text-muted"><?= htmlspecialchars($scheme->name) ?> &nbsp;·&nbsp; ID #<?= $scheme->id ?></small>
    </div>
    <div class="d-flex" style="gap:.5rem">
        <a href="<?= base_url('schemes/view/'.$scheme->id) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-eye mr-1"></i>View</a>
        <a href="<?= base_url('schemes') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left mr-1"></i>Back</a>
    </div>
</div>

<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div><?php endif; ?>

<form method="post" action="<?= base_url('schemes/update/'.$scheme->id) ?>" id="schemeForm">

<!-- Step 1 -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white"><i class="bi bi-info-circle mr-2"></i>Step 1 — Scheme Identity</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-5">
                <label class="form-label font-weight-bold">Scheme Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($scheme->name) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Scheme Type <span class="text-danger">*</span></label>
                <select name="scheme_type_id" id="scheme_type_id" class="form-control" required onchange="showTypeFields()">
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t->id ?>" data-code="<?= $t->code ?>" <?= $t->id==$scheme->scheme_type_id?'selected':'' ?>><?= htmlspecialchars($t->label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label font-weight-bold">Priority</label>
                <input type="number" name="priority" class="form-control" value="<?= $scheme->priority ?>" min="1" max="10">
            </div>
            <div class="col-md-2">
                <label class="form-label font-weight-bold">Status</label>
                <select name="is_active" class="form-control">
                    <option value="1" <?= $scheme->is_active?'selected':'' ?>>Active</option>
                    <option value="0" <?= !$scheme->is_active?'selected':'' ?>>Inactive (Draft)</option>
                </select>
            </div>
            <div class="col-md-3 mt-2">
                <label class="form-label font-weight-bold">Apply On</label>
                <select name="apply_on" id="apply_on" class="form-control" onchange="showApplyFields()">
                    <?php foreach (['item'=>'Specific Items','brand'=>'Brand','category'=>'Category','brand_category'=>'Brand & Category','invoice'=>'Entire Invoice','mix_match'=>'Mix & Match Group'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $scheme->apply_on===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mt-2">
                <label class="form-label font-weight-bold">Start Date <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" value="<?= $scheme->start_date ?>" required>
            </div>
            <div class="col-md-3 mt-2">
                <label class="form-label font-weight-bold">End Date <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control" value="<?= $scheme->end_date ?>" required>
            </div>
            <div class="col-md-3 mt-2">
                <label class="form-label font-weight-bold">Applicable Parties</label>
                <select name="applicable_parties" id="applicable_parties" class="form-control" onchange="togglePartySelect()">
                    <option value="all" <?= $scheme->applicable_parties==='all'?'selected':'' ?>>All Customers</option>
                    <option value="selected" <?= $scheme->applicable_parties==='selected'?'selected':'' ?>>Selected Customers</option>
                </select>
            </div>
            <div class="col-md-3 mt-2">
                <label class="form-label font-weight-bold">Start Time <small class="text-muted">(optional)</small></label>
                <input type="time" name="start_time" class="form-control" value="<?= $scheme->start_time ?? '' ?>">
            </div>
            <div class="col-md-3 mt-2">
                <label class="form-label font-weight-bold">End Time <small class="text-muted">(optional)</small></label>
                <input type="time" name="end_time" class="form-control" value="<?= $scheme->end_time ?? '' ?>">
            </div>
            <div class="col-md-3 mt-2">
                <label class="form-label font-weight-bold">Expiry Days Trigger</label>
                <input type="number" name="expiry_days_trigger" class="form-control" value="<?= $scheme->expiry_days_trigger ?? '' ?>" min="0">
            </div>
            <div class="col-12 mt-2">
                <label class="form-label font-weight-bold">Notes</label>
                <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($scheme->notes ?? '') ?>">
            </div>
        </div>
    </div>
</div>

<!-- Step 2 -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-warning text-dark"><i class="bi bi-lightning mr-2"></i>Step 2 — Trigger Conditions</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Minimum Quantity</label>
                <div class="input-group">
                    <input type="number" name="min_qty" class="form-control" value="<?= $scheme->min_qty+0 ?>" min="0" step="0.001">
                    <select name="min_qty_uom" class="form-control" style="max-width:80px">
                        <option value="pcs" <?= $scheme->min_qty_uom==='pcs'?'selected':'' ?>>PCS</option>
                        <option value="crt" <?= $scheme->min_qty_uom==='crt'?'selected':'' ?>>CRT</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Min Bill Value (₹)</label>
                <input type="number" name="min_value" class="form-control" value="<?= $scheme->min_value+0 ?>" min="0" step="0.01">
            </div>
            <div class="col-md-3" id="carton_size_wrap">
                <label class="form-label font-weight-bold">Carton Size</label>
                <input type="number" name="carton_size" class="form-control" value="<?= $scheme->carton_size ?: 12 ?>" min="1">
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Max Benefit / Invoice (₹)</label>
                <input type="number" name="max_benefit_per_invoice" class="form-control" value="<?= $scheme->max_benefit_per_invoice+0 ?>" min="0">
            </div>
            <div class="col-md-3 mt-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_recursive" value="1" id="is_recursive" <?= $scheme->is_recursive?'checked':'' ?>>
                    <label class="form-check-label" for="is_recursive">Recursive</label>
                </div>
            </div>
            <div class="col-md-3 mt-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_combination_allowed" value="1" id="is_combo" <?= $scheme->is_combination_allowed?'checked':'' ?>>
                    <label class="form-check-label" for="is_combo">Allow Combination</label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Step 3 -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-success text-white"><i class="bi bi-gift mr-2"></i>Step 3 — Benefit / Reward</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Discount %</label>
                <input type="number" name="discount_percent" class="form-control" value="<?= $scheme->discount_percent+0 ?>" min="0" max="100" step="0.01">
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Flat Discount (₹)</label>
                <input type="number" name="discount_value" class="form-control" value="<?= $scheme->discount_value+0 ?>" min="0" step="0.01">
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Free Qty (PCS)</label>
                <input type="number" name="free_qty" class="form-control" value="<?= $scheme->free_qty+0 ?>" min="0" step="0.001">
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Free Item (blank = same)</label>
                <select name="free_item_id" class="form-control">
                    <option value="">Same Item</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p->id ?>" <?= $scheme->free_item_id==$p->id?'selected':'' ?>><?= htmlspecialchars($p->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div id="target_fields" class="mt-3 border-top pt-3">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Target Amount (₹)</label>
                    <input type="number" name="target_amount" class="form-control" value="<?= $scheme->target_amount+0 ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Period</label>
                    <select name="target_period" class="form-control">
                        <?php foreach (['monthly'=>'Monthly','quarterly'=>'Quarterly','yearly'=>'Yearly','custom'=>'Custom'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $scheme->target_period===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div id="cn_fields" class="mt-3 border-top pt-3">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Credit Note %</label>
                    <input type="number" name="credit_note_percent" class="form-control" value="<?= $scheme->credit_note_percent+0 ?>" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Issue Timing</label>
                    <select name="credit_note_timing" class="form-control">
                        <?php foreach (['immediate'=>'Immediate','end_of_month'=>'End of Month','end_of_quarter'=>'End of Quarter'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $scheme->credit_note_timing===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Step 4: Slabs -->
<div class="card shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-layers mr-2"></i>Step 4 — Quantity / Value Slabs</span>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSlab()"><i class="bi bi-plus"></i> Add Slab</button>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0 table-sm">
            <thead class="table-light">
                <tr><th>Min Qty</th><th>Max Qty</th><th>Min Value</th><th>Max Value</th><th>Free Qty</th><th>Disc %</th><th>Disc ₹</th><th>Free Item</th><th></th></tr>
            </thead>
            <tbody id="slabsBody">
            <?php foreach (($scheme->slabs ?? []) as $i => $slab): ?>
            <tr id="slab_ex_<?= $i ?>">
                <td><input type="number" name="slabs[ex_<?= $i ?>][min_qty]" class="form-control form-control-sm" value="<?= $slab->min_qty+0 ?>" step="0.001"></td>
                <td><input type="number" name="slabs[ex_<?= $i ?>][max_qty]" class="form-control form-control-sm" value="<?= $slab->max_qty+0 ?>" step="0.001"></td>
                <td><input type="number" name="slabs[ex_<?= $i ?>][min_value]" class="form-control form-control-sm" value="<?= $slab->min_value+0 ?>" step="0.01"></td>
                <td><input type="number" name="slabs[ex_<?= $i ?>][max_value]" class="form-control form-control-sm" value="<?= $slab->max_value+0 ?>" step="0.01"></td>
                <td><input type="number" name="slabs[ex_<?= $i ?>][free_qty]" class="form-control form-control-sm" value="<?= $slab->free_qty+0 ?>" step="0.001"></td>
                <td><input type="number" name="slabs[ex_<?= $i ?>][disc_pct]" class="form-control form-control-sm" value="<?= $slab->discount_percent+0 ?>" step="0.01"></td>
                <td><input type="number" name="slabs[ex_<?= $i ?>][disc_val]" class="form-control form-control-sm" value="<?= $slab->discount_value+0 ?>" step="0.01"></td>
                <td>
                    <select name="slabs[ex_<?= $i ?>][free_item_id]" class="form-control form-control-sm">
                        <option value="">Same</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p->id ?>" <?= $slab->free_item_id==$p->id?'selected':'' ?>><?= htmlspecialchars($p->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('slab_ex_<?= $i ?>').remove()"><i class="bi bi-trash"></i></button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Step 5: Item Mapping -->
<div class="card shadow-sm mb-3">
    <div class="card-header"><i class="bi bi-box-seam mr-2"></i>Step 5 — Item / Brand / Category Mapping</div>
    <div class="card-body">
        <div class="row">
            <div id="product_select_wrap" class="col-md-6">
                <label class="form-label font-weight-bold">Products</label>
                <div class="mb-2 p-2 border rounded bg-light">
                    <input type="text" id="product_search" class="form-control form-control-sm mb-2" placeholder="🔍 Search products...">
                    <div class="d-flex" style="gap:8px">
                        <button type="button" class="btn btn-xs btn-primary py-1 px-2 flex-fill" style="font-size:.75rem" onclick="selectAllFiltered()">☑ Select All Filtered</button>
                        <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2 flex-fill" style="font-size:.75rem" onclick="clearProductSelections()">✕ Clear All</button>
                    </div>
                </div>
                <select name="product_ids[]" id="product_ids" class="form-control" multiple size="8">
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p->id ?>" <?= in_array($p->id, $existing_product_ids)?'selected':'' ?>>[<?= $p->item_code ?>] <?= htmlspecialchars($p->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="brand_select_wrap" class="col-md-3">
                <label class="form-label font-weight-bold">Brand</label>
                <select name="brand_id" class="form-control">
                    <option value="">-- Select Brand --</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b->id ?>" <?= $existing_brand_id==$b->id?'selected':'' ?>><?= htmlspecialchars($b->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="category_select_wrap" class="col-md-3">
                <label class="form-label font-weight-bold">Category</label>
                <select name="category_id" class="form-control">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c->id ?>" <?= $existing_category_id==$c->id?'selected':'' ?>><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Step 6: Free Items -->
<div class="card shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-gift mr-2"></i>Step 6 — Free Items List</span>
        <button type="button" class="btn btn-sm btn-outline-success" onclick="addFreeItem()"><i class="bi bi-plus"></i> Add</button>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0 table-sm">
            <thead class="table-light"><tr><th>Free Product</th><th>Qty</th><th>UOM</th><th></th></tr></thead>
            <tbody id="freeItemsBody">
            <?php foreach (($scheme->free_items ?? []) as $i => $fi): ?>
            <tr id="fi_ex_<?= $i ?>">
                <td>
                    <select name="fi_product_id[]" class="form-control form-control-sm">
                        <option value="">-- Select --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p->id ?>" <?= $fi->free_product_id==$p->id?'selected':'' ?>><?= htmlspecialchars($p->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" name="fi_qty[]" class="form-control form-control-sm" value="<?= $fi->free_qty+0 ?>" min="0.001" step="0.001"></td>
                <td>
                    <select name="fi_uom[]" class="form-control form-control-sm">
                        <option value="pcs" <?= $fi->free_uom==='pcs'?'selected':'' ?>>PCS</option>
                        <option value="crt" <?= $fi->free_uom==='crt'?'selected':'' ?>>CRT</option>
                    </select>
                </td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('fi_ex_<?= $i ?>').remove()"><i class="bi bi-trash"></i></button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Step 7: Parties -->
<div class="card shadow-sm mb-3" id="party_section" style="<?= $scheme->applicable_parties==='selected'?'':'display:none' ?>">
    <div class="card-header"><i class="bi bi-people mr-2"></i>Step 7 — Applicable Customers</div>
    <div class="card-body">
        <select name="customer_ids[]" class="form-control" multiple size="6">
            <?php foreach ($customers as $c): ?>
                <option value="<?= $c->id ?>" <?= in_array($c->id, $existing_customer_ids)?'selected':'' ?>><?= htmlspecialchars($c->name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body d-flex" style="gap:.5rem">
        <button type="submit" class="btn btn-warning btn-lg flex-fill"><i class="bi bi-check-lg mr-2"></i>Update Scheme</button>
        <a href="<?= base_url('schemes/view/'.$scheme->id) ?>" class="btn btn-outline-secondary btn-lg">Cancel</a>
    </div>
</div>

</form>

<script>
const PRODUCTS = <?= json_encode(array_map(function($p){return['id'=>(int)$p->id,'name'=>$p->name,'item_code'=>$p->item_code??''];}, $products)) ?>;

function showTypeFields() {
    const code = document.getElementById('scheme_type_id').options[document.getElementById('scheme_type_id').selectedIndex]?.dataset.code ?? '';
    document.getElementById('target_fields').style.display = code === 'TARGET' ? '' : 'none';
    document.getElementById('cn_fields').style.display     = code === 'CREDIT_NOTE' ? '' : 'none';
    document.getElementById('carton_size_wrap').style.display = code === 'CARTON' ? '' : 'none';
}

function showApplyFields() {
    const v = document.getElementById('apply_on').value;
    document.getElementById('product_select_wrap').style.display  = ['item','mix_match','combo'].includes(v) ? '' : 'none';
    document.getElementById('brand_select_wrap').style.display    = ['brand','brand_category'].includes(v) ? '' : 'none';
    document.getElementById('category_select_wrap').style.display = ['category','brand_category'].includes(v) ? '' : 'none';
}

// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
document.getElementById('product_search').addEventListener('input', function() {
    const f = this.value.toLowerCase();
    Array.from(document.getElementById('product_ids').options).forEach(o => {
        o.style.display = pmatch(f, o.text) ? '' : 'none';
    });
});

function selectAllFiltered() {
    Array.from(document.getElementById('product_ids').options).forEach(o => { if (o.style.display !== 'none') o.selected = true; });
}
function clearProductSelections() {
    Array.from(document.getElementById('product_ids').options).forEach(o => o.selected = false);
}

function togglePartySelect() {
    document.getElementById('party_section').style.display = document.getElementById('applicable_parties').value === 'selected' ? '' : 'none';
}

let slabCount = 0;
function addSlab() {
    slabCount++;
    const prodOpts = PRODUCTS.map(p=>`<option value="${p.id}">[${p.item_code}] ${p.name}</option>`).join('');
    document.getElementById('slabsBody').insertAdjacentHTML('beforeend', `<tr id="slab_${slabCount}">
        <td><input type="number" name="slabs[${slabCount}][min_qty]" class="form-control form-control-sm" value="0" step="0.001"></td>
        <td><input type="number" name="slabs[${slabCount}][max_qty]" class="form-control form-control-sm" value="0" step="0.001"></td>
        <td><input type="number" name="slabs[${slabCount}][min_value]" class="form-control form-control-sm" value="0" step="0.01"></td>
        <td><input type="number" name="slabs[${slabCount}][max_value]" class="form-control form-control-sm" value="0" step="0.01"></td>
        <td><input type="number" name="slabs[${slabCount}][free_qty]" class="form-control form-control-sm" value="0" step="0.001"></td>
        <td><input type="number" name="slabs[${slabCount}][disc_pct]" class="form-control form-control-sm" value="0" step="0.01"></td>
        <td><input type="number" name="slabs[${slabCount}][disc_val]" class="form-control form-control-sm" value="0" step="0.01"></td>
        <td><select name="slabs[${slabCount}][free_item_id]" class="form-control form-control-sm"><option value="">Same</option>${prodOpts}</select></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('slab_${slabCount}').remove()"><i class="bi bi-trash"></i></button></td>
    </tr>`);
}

let fiCount = 0;
function addFreeItem() {
    fiCount++;
    const prodOpts = PRODUCTS.map(p=>`<option value="${p.id}">[${p.item_code}] ${p.name}</option>`).join('');
    document.getElementById('freeItemsBody').insertAdjacentHTML('beforeend', `<tr id="fi_${fiCount}">
        <td><select name="fi_product_id[]" class="form-control form-control-sm"><option value="">-- Select --</option>${prodOpts}</select></td>
        <td><input type="number" name="fi_qty[]" class="form-control form-control-sm" value="1" step="0.001"></td>
        <td><select name="fi_uom[]" class="form-control form-control-sm"><option value="pcs">PCS</option><option value="crt">CRT</option></select></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('fi_${fiCount}').remove()"><i class="bi bi-trash"></i></button></td>
    </tr>`);
}

showTypeFields();
showApplyFields();
</script>
