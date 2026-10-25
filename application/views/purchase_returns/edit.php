<?php // Purchase Returns / Debit Note - Edit ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-pencil-square text-warning mr-2"></i>Edit Debit Note: <?= htmlspecialchars($return->return_number) ?></h4>
        <small class="text-muted">Modify items, quantities or details before finalising</small>
    </div>
    <a href="<?= base_url('purchase_returns/view/'.$return->id) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left mr-1"></i>Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle mr-2"></i><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<form method="post" action="<?= base_url('purchase_returns/update/'.$return->id) ?>" id="dnForm">

<!-- Header -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-warning text-dark"><i class="bi bi-file-earmark-pencil mr-2"></i>Debit Note Details</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Debit Note No</label>
                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($return->return_number) ?>" readonly>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Return Date <span class="text-danger">*</span></label>
                <input type="date" name="return_date" class="form-control" value="<?= $return->return_date ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Supplier <span class="text-danger">*</span></label>
                <select name="supplier_id" id="supplier_id" class="form-control" required onchange="loadSupplierPurchases(this.value)">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?= $sup->id ?>" <?= $return->supplier_id == $sup->id ? 'selected':'' ?>><?= htmlspecialchars($sup->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Against Purchase Bill (Optional)</label>
                <select name="purchase_id" id="purchase_id" class="form-control">
                    <option value="<?= $return->purchase_id ?>">Current: <?= $return->purchase_id ? '#'.$return->purchase_id : 'None' ?></option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label font-weight-bold">Reason for Return</label>
                <input type="text" name="reason" class="form-control" value="<?= htmlspecialchars($return->reason) ?>" placeholder="e.g. Damaged, Wrong item...">
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Supply Type</label>
                <select name="is_interstate" id="is_interstate" class="form-control" onchange="recalcAll()">
                    <option value="0" <?= !$return->is_interstate ? 'selected':'' ?>>Intrastate (CGST + SGST)</option>
                    <option value="1" <?= $return->is_interstate ? 'selected':'' ?>>Interstate (IGST)</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label font-weight-bold">Notes</label>
                <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($return->notes) ?>" placeholder="Additional notes...">
            </div>
        </div>
    </div>
</div>

<!-- Items Table -->
<div class="card shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-list-ul mr-1"></i>Return Items</span>
        <button type="button" class="btn btn-sm btn-outline-warning" onclick="addRow()"><i class="bi bi-plus"></i> Add Row</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:220px">Product</th>
                        <th style="width:100px">HSN</th>
                        <th style="width:70px">Qty</th>
                        <th style="width:60px">Unit</th>
                        <th style="width:90px">Rate (₹)</th>
                        <th style="width:70px">Disc%</th>
                        <th style="width:90px">Taxable</th>
                        <th style="width:60px">GST%</th>
                        <th style="width:80px">GST Amt</th>
                        <th style="width:90px">Total</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
                <tfoot>
                    <tr class="table-light font-weight-bold">
                        <td colspan="6" class="text-right">Totals:</td>
                        <td id="ft_taxable" class="text-right">0.00</td>
                        <td></td>
                        <td id="ft_gst" class="text-right">0.00</td>
                        <td id="ft_total" class="text-right text-warning">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Totals + Submit -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-percent mr-1"></i>GST Breakup</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Rate</th><th>Taxable</th><th>CGST</th><th>SGST</th><th>IGST</th></tr></thead>
                    <tbody id="gstBreakupBody"><tr><td colspan="5" class="text-muted text-center small">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark"><i class="bi bi-receipt mr-1"></i>Debit Note Summary</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Subtotal</td><td class="text-right font-weight-bold" id="s_subtotal">₹0.00</td></tr>
                    <tr><td>Discount</td><td class="text-right" id="s_discount">₹0.00</td></tr>
                    <tr><td>Taxable Amount</td><td class="text-right" id="s_taxable">₹0.00</td></tr>
                    <tr><td>CGST</td><td class="text-right" id="s_cgst">₹0.00</td></tr>
                    <tr><td>SGST</td><td class="text-right" id="s_sgst">₹0.00</td></tr>
                    <tr><td>IGST</td><td class="text-right" id="s_igst">₹0.00</td></tr>
                    <tr class="table-warning font-weight-bold"><td>Debit Note Total</td><td class="text-right fs-5" id="s_total">₹0.00</td></tr>
                </table>
                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-warning btn-lg text-dark"><i class="bi bi-check-lg mr-2"></i>Update Debit Note</button>
                </div>
            </div>
        </div>
    </div>
</div>

</form>

<script>
const PRODUCTS = <?= json_encode(array_map(function($p) {
    return ['id'=>(int)$p->id,'name'=>$p->name,'item_code'=>$p->item_code??'','hsn_code'=>$p->hsn_code??'','unit'=>$p->unit,'selling_price'=>(float)$p->selling_price,'gst_percent'=>(float)$p->gst_percent,'purchase_price'=>(float)($p->purchase_price??0),'basic_cost'=>(float)($p->basic_cost??0)];
}, $products)) ?>;

const EXISTING_ITEMS = <?= json_encode(array_map(function($it) {
    return ['product_id'=>(int)$it->product_id,'product_name'=>$it->product_name,'hsn_code'=>$it->hsn_code??'','qty'=>(float)$it->qty,'unit'=>$it->unit??'PCS','rate'=>(float)$it->rate,'discount_percent'=>(float)($it->discount_percent??0),'gst_percent'=>(float)$it->gst_percent,'taxable_amt'=>(float)$it->taxable_amt,'gst_amount'=>(float)$it->gst_amount,'total'=>(float)$it->total];
}, $items)) ?>;

let rowCount = 0;

function loadSupplierPurchases(sid) {
    const sel = document.getElementById('purchase_id');
    sel.innerHTML = '<option value="">-- Select Purchase --</option>';
    if (!sid) return;
    fetch('<?= base_url("purchase_returns/get_purchases_by_supplier/") ?>' + sid, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r=>r.json()).then(rows => {
            rows.forEach(p => {
                const o = document.createElement('option');
                o.value = p.id;
                o.text = (p.purchase_no||p.invoice_no||'#'+p.id) + ' — ' + p.invoice_date + ' — ₹' + parseFloat(p.total_amount).toFixed(2);
                sel.appendChild(o);
            });
        });
}

function addRow(item) {
    rowCount++;
    const rid = rowCount;
    const prodOpts = PRODUCTS.map(p =>
        `<option value="${p.id}" data-price="${p.basic_cost||p.purchase_price}" data-gst="${p.gst_percent}" data-hsn="${p.hsn_code}" data-unit="${p.unit}">${p.name}</option>`
    ).join('');
    const html = `<tr id="row_${rid}">
        <td>
            <select name="items[${rid}][product_id]" class="form-control form-control-sm" onchange="fillProduct(this,${rid})">
                <option value="">-- Select --</option>${prodOpts}
            </select>
            <input type="hidden" name="items[${rid}][product_name]" id="pname_${rid}">
        </td>
        <td><input type="text"   name="items[${rid}][hsn_code]"         class="form-control form-control-sm" id="hsn_${rid}"></td>
        <td><input type="number" name="items[${rid}][qty]"              class="form-control form-control-sm" id="qty_${rid}" value="1" min="0.001" step="0.001" oninput="calcRow(${rid})"></td>
        <td><input type="text"   name="items[${rid}][unit]"             class="form-control form-control-sm" id="unit_${rid}" value="PCS" style="width:55px"></td>
        <td><input type="number" name="items[${rid}][rate]"             class="form-control form-control-sm" id="rate_${rid}" value="0" step="0.01" oninput="calcRow(${rid})"></td>
        <td><input type="number" name="items[${rid}][discount_percent]" class="form-control form-control-sm" id="disc_${rid}" value="0" step="0.01" oninput="calcRow(${rid})"></td>
        <td><input type="number" name="items[${rid}][taxable_amt]"      class="form-control form-control-sm" id="taxable_${rid}" value="0" readonly></td>
        <td><input type="number" name="items[${rid}][gst_percent]"      class="form-control form-control-sm" id="gst_${rid}" value="0" step="0.01" oninput="calcRow(${rid})"></td>
        <td><input type="number" name="items[${rid}][gst_amount]"       class="form-control form-control-sm" id="gst_amt_${rid}" value="0" readonly></td>
        <td><input type="number" name="items[${rid}][total]"            class="form-control form-control-sm" id="total_${rid}" value="0" readonly></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(${rid})"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', html);
    if (item) {
        const sel = document.querySelector(`#row_${rid} select[name$="[product_id]"]`);
        for (let o of sel.options) { if (parseInt(o.value)===item.product_id){o.selected=true;break;} }
        document.getElementById('pname_'+rid).value   = item.product_name;
        document.getElementById('hsn_'+rid).value     = item.hsn_code;
        document.getElementById('qty_'+rid).value     = item.qty;
        document.getElementById('unit_'+rid).value    = item.unit;
        document.getElementById('rate_'+rid).value    = item.rate.toFixed(2);
        document.getElementById('disc_'+rid).value    = item.discount_percent.toFixed(2);
        document.getElementById('gst_'+rid).value     = item.gst_percent.toFixed(2);
        calcRow(rid);
    }
}

function fillProduct(sel, rid) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) return;
    document.getElementById('pname_'+rid).value = opt.text;
    document.getElementById('hsn_'+rid).value   = opt.dataset.hsn||'';
    document.getElementById('unit_'+rid).value  = opt.dataset.unit||'PCS';
    document.getElementById('rate_'+rid).value  = parseFloat(opt.dataset.price||0).toFixed(2);
    document.getElementById('gst_'+rid).value   = parseFloat(opt.dataset.gst||0).toFixed(2);
    calcRow(rid);
}

function calcRow(rid) {
    const qty=parseFloat(document.getElementById('qty_'+rid)?.value||0);
    const rate=parseFloat(document.getElementById('rate_'+rid)?.value||0);
    const disc=parseFloat(document.getElementById('disc_'+rid)?.value||0);
    const gst_pct=parseFloat(document.getElementById('gst_'+rid)?.value||0);
    const taxable=parseFloat((qty*rate*(1-disc/100)).toFixed(2));
    const gst_amt=parseFloat((taxable*gst_pct/100).toFixed(2));
    document.getElementById('taxable_'+rid).value=taxable.toFixed(2);
    document.getElementById('gst_amt_'+rid).value=gst_amt.toFixed(2);
    document.getElementById('total_'+rid).value=(taxable+gst_amt).toFixed(2);
    recalcAll();
}

function removeRow(rid) { const r=document.getElementById('row_'+rid); if(r){r.remove();recalcAll();} }

function recalcAll() {
    let subtotal=0,taxable=0,cgst=0,sgst=0,igst=0,gstAmt=0;
    const inter=document.getElementById('is_interstate').value==='1';
    const gstMap={};
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const rid=row.id.replace('row_','');
        const qty=parseFloat(document.getElementById('qty_'+rid)?.value||0);
        const rate=parseFloat(document.getElementById('rate_'+rid)?.value||0);
        const disc=parseFloat(document.getElementById('disc_'+rid)?.value||0);
        const gp=parseFloat(document.getElementById('gst_'+rid)?.value||0);
        const tx=parseFloat((qty*rate*(1-disc/100)).toFixed(2));
        const ga=parseFloat((tx*gp/100).toFixed(2));
        subtotal+=qty*rate; taxable+=tx;
        if(inter)igst+=ga; else {cgst+=ga/2;sgst+=ga/2;}
        gstAmt+=ga;
        if(gp>0){if(!gstMap[gp])gstMap[gp]={taxable:0,cgst:0,sgst:0,igst:0};gstMap[gp].taxable+=tx;if(inter)gstMap[gp].igst+=ga;else{gstMap[gp].cgst+=ga/2;gstMap[gp].sgst+=ga/2;}}
    });
    const discount=subtotal-taxable; const total=taxable+gstAmt;
    document.getElementById('ft_taxable').textContent=taxable.toFixed(2);
    document.getElementById('ft_gst').textContent=gstAmt.toFixed(2);
    document.getElementById('ft_total').textContent=total.toFixed(2);
    document.getElementById('s_subtotal').textContent='₹'+subtotal.toFixed(2);
    document.getElementById('s_discount').textContent='₹'+discount.toFixed(2);
    document.getElementById('s_taxable').textContent='₹'+taxable.toFixed(2);
    document.getElementById('s_cgst').textContent='₹'+cgst.toFixed(2);
    document.getElementById('s_sgst').textContent='₹'+sgst.toFixed(2);
    document.getElementById('s_igst').textContent='₹'+igst.toFixed(2);
    document.getElementById('s_total').textContent='₹'+total.toFixed(2);
    let bhtml='';
    Object.entries(gstMap).sort().forEach(([r,v])=>{bhtml+=`<tr><td>${r}%</td><td>₹${v.taxable.toFixed(2)}</td><td>₹${v.cgst.toFixed(2)}</td><td>₹${v.sgst.toFixed(2)}</td><td>₹${v.igst.toFixed(2)}</td></tr>`;});
    document.getElementById('gstBreakupBody').innerHTML=bhtml||'<tr><td colspan="5" class="text-muted text-center small">No items</td></tr>';
}

// Pre-load existing items
EXISTING_ITEMS.forEach(item => addRow(item));
recalcAll();
</script>
