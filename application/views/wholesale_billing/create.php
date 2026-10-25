<style>
.ws-item-row td { vertical-align: middle !important; padding: .3rem .4rem !important; }
.ws-item-row input, .ws-item-row select { font-size: .85rem; }
#wsItemsBody .form-control { min-width: 60px; }
.ws-totals th { text-align: right; font-weight: 600; }
.ws-totals td { text-align: right; }
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-file-invoice-dollar"></i> Create Wholesale Invoice</h4>
        <a href="<?= base_url('wholesale_billing') ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form id="wsInvoiceForm">
        <div class="row">
            <!-- Left: Customer & Invoice Details -->
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header py-2"><strong>Invoice Details</strong></div>
                    <div class="card-body py-2">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Customer <span class="text-danger">*</span></label>
                                <select name="customer_id" id="wsCustomer" class="form-control form-control-sm" required>
                                    <option value="">-- Select Wholesale Customer --</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c->id ?>" data-gstin="<?= htmlspecialchars($c->gst_number) ?>" data-state="<?= htmlspecialchars($c->state ?? '') ?>" data-address="<?= htmlspecialchars($c->address ?? '') ?>" data-pincode="<?= htmlspecialchars($c->pincode ?? '') ?>" data-city="<?= htmlspecialchars($c->city ?? '') ?>" data-name="<?= htmlspecialchars($c->name) ?>">
                                            <?= htmlspecialchars($c->name) ?> (<?= $c->gst_number ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block" id="custGstin"></small>
                                <small class="d-block" id="custAddrInfo"></small>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Invoice Date</label>
                                <input type="date" name="invoice_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Location</label>
                                <select name="location_id" id="wsLocation" class="form-control form-control-sm" onchange="checkAddresses()">
                                    <?php foreach ($locations as $l): ?>
                                        <option value="<?= $l->id ?>" data-pincode="<?= htmlspecialchars($l->pincode ?? '') ?>" data-address="<?= htmlspecialchars($l->address ?? '') ?>" data-city="<?= htmlspecialchars($l->city ?? '') ?>" <?= (isset($this->session->active_location_id) && $this->session->active_location_id == $l->id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($l->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="d-block" id="locAddrInfo"></small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Payment Mode</label>
                                <select name="payment_mode" class="form-control form-control-sm">
                                    <option value="credit">Credit</option>
                                    <option value="cash">Cash</option>
                                    <option value="upi">UPI</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="form-group col-md-8">
                                <label>Notes</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional notes">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <strong><i class="fas fa-map-marker-alt"></i> Delivery (Ship-To) Address</strong>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="shipDiff" name="ship_to_different" value="1" onchange="toggleShip()">
                            <label class="custom-control-label" for="shipDiff" style="font-size:.85rem;">Ship to a different address</label>
                        </div>
                    </div>
                    <div class="card-body py-2" id="shipNote">
                        <small class="text-muted">Goods go to the customer's billing address.</small>
                    </div>
                    <div class="card-body py-2" id="shipFields" style="display:none;">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Ship-To Name <span class="text-danger">*</span></label>
                                <input type="text" name="ship_name" id="shipName" class="form-control form-control-sm" maxlength="100">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Ship-To GSTIN <small class="text-muted">(if registered)</small></label>
                                <input type="text" name="ship_gstin" id="shipGstin" class="form-control form-control-sm" maxlength="15" style="text-transform:uppercase;" oninput="shipGstinChanged()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Address <span class="text-danger">*</span></label>
                            <input type="text" name="ship_address" id="shipAddress" class="form-control form-control-sm" maxlength="200" placeholder="Building, street, area">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>City / Place <span class="text-danger">*</span></label>
                                <input type="text" name="ship_place" id="shipPlace" class="form-control form-control-sm" maxlength="100">
                            </div>
                            <div class="form-group col-md-5">
                                <label>State <span class="text-danger">*</span></label>
                                <select name="ship_state_code" id="shipState" class="form-control form-control-sm" onchange="checkAddresses(); recalcAll();">
                                    <option value="">-- Select State --</option>
                                    <?php foreach ($states as $code => $st): ?>
                                        <option value="<?= $code ?>"><?= $code ?> - <?= htmlspecialchars($st) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>PIN Code <span class="text-danger">*</span></label>
                                <input type="text" name="ship_pincode" id="shipPin" class="form-control form-control-sm" maxlength="6" inputmode="numeric" oninput="checkAddresses()">
                            </div>
                        </div>
                        <small id="shipInfo"></small>
                    </div>
                </div>
            </div>

            <!-- Right: Transport Details -->
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header py-2"><strong>Transport Details</strong> <small class="text-muted">(optional here — you can fill / change them when you click Generate E-Way Bill)</small></div>
                    <div class="card-body py-2">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Transport Mode</label>
                                <select name="transport_mode" class="form-control form-control-sm">
                                    <option value="1">Road</option>
                                    <option value="2">Rail</option>
                                    <option value="3">Air</option>
                                    <option value="4">Ship</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Vehicle No</label>
                                <input type="text" name="vehicle_no" class="form-control form-control-sm" placeholder="e.g. DL01AB1234" style="text-transform:uppercase;">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Vehicle Type</label>
                                <select name="vehicle_type" class="form-control form-control-sm">
                                    <option value="R">Regular</option>
                                    <option value="O">Over Dimensional Cargo</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Transporter ID</label>
                                <input type="text" name="transporter_id" class="form-control form-control-sm" placeholder="GSTIN" maxlength="15">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Transporter Name</label>
                                <input type="text" name="transporter_name" class="form-control form-control-sm">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Distance (KM)</label>
                                <input type="number" name="transport_distance" class="form-control form-control-sm" min="0" max="4000">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Transport Doc No</label>
                                <input type="text" name="transport_doc_no" class="form-control form-control-sm">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Transport Doc Date</label>
                                <input type="date" name="transport_doc_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Entry -->
        <div class="card mb-3">
            <div class="card-header py-2">
                <strong>Items</strong>
                <div class="float-right" style="width:300px;">
                    <input type="text" id="wsProductSearch" class="form-control form-control-sm" placeholder="Search product by name / code / barcode...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" id="wsItemsTable">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:30px;">#</th>
                                <th>Product</th>
                                <th>HSN</th>
                                <th style="width:70px;">Qty</th>
                                <th style="width:90px;">Rate</th>
                                <th style="width:90px;">Discount</th>
                                <th style="width:70px;">GST %</th>
                                <th class="text-right" style="width:90px;">Taxable</th>
                                <th class="text-right" style="width:90px;">GST Amt</th>
                                <th class="text-right" style="width:100px;">Total</th>
                                <th style="width:30px;"></th>
                            </tr>
                        </thead>
                        <tbody id="wsItemsBody"></tbody>
                        <tfoot class="ws-totals">
                            <tr>
                                <th colspan="7" class="text-right">Subtotal:</th>
                                <td id="wsTaxable">0.00</td>
                                <td id="wsGstAmt">0.00</td>
                                <td id="wsGrandTotal">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Summary & Submit -->
        <div class="row">
            <div class="col-md-4 offset-md-8">
                <table class="table table-sm">
                    <tr><td>Taxable Amount</td><td class="text-right" id="summTaxable">0.00</td></tr>
                    <tr id="rowCgst"><td>CGST</td><td class="text-right" id="summCgst">0.00</td></tr>
                    <tr id="rowSgst"><td>SGST</td><td class="text-right" id="summSgst">0.00</td></tr>
                    <tr id="rowIgst" style="display:none;"><td>IGST</td><td class="text-right" id="summIgst">0.00</td></tr>
                    <tr><td>Round Off</td><td class="text-right" id="summRound">0.00</td></tr>
                    <tr class="font-weight-bold"><td>Grand Total</td><td class="text-right" id="summTotal">0.00</td></tr>
                </table>
                <button type="submit" class="btn btn-success btn-block" id="btnSubmit">
                    <i class="fas fa-save"></i> Create Wholesale Invoice
                </button>
            </div>
        </div>
    </form>
</div>

<script>
var wsProducts = <?= json_encode(array_map(function($p) {
    return [
        'id' => $p->id,
        'name' => $p->name,
        'item_code' => $p->item_code ?? '',
        'barcode' => $p->barcode ?? '',
        'hsn_code' => $p->hsn_code ?? '',
        'selling_price' => (float)$p->selling_price,
        'mrp' => (float)$p->mrp,
        'gst_percent' => (float)($p->gst_percent ?? 0),
        'unit' => $p->unit ?? 'PCS',
        'stock_qty' => (float)($p->stock_qty ?? 0),
    ];
}, $products)) ?>;

var companyState = <?= json_encode(strtolower(trim($company['company_state'] ?? ''))) ?>;
var sellerStateCode = <?= json_encode(substr(strtoupper(trim($company['company_gstin'] ?? '')), 0, 2)) ?>;
var STATE_NAMES = <?= json_encode($states) ?>;
// First two PIN digits per GST state code (same table as GstAddress on the server)
var PIN_PREFIX = <?= json_encode(GstAddress::PIN_PREFIX) ?>;
var wsRowIdx = 0;

document.getElementById('wsCustomer').addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    document.getElementById('custGstin').textContent = opt.dataset.gstin ? 'GSTIN: ' + opt.dataset.gstin : '';
    checkAddresses();
    recalcAll();
});

// Problem text for a PIN, or '' when it is fine for the state
function pinProblem(pin, stateCode, what) {
    pin = (pin || '').trim();
    if (!pin) return what + ' is missing (compulsory)';
    if (!/^[1-9][0-9]{5}$/.test(pin)) return what + ' "' + pin + '" is not a 6-digit PIN';
    var allowed = PIN_PREFIX[stateCode];
    if (allowed && allowed.indexOf(pin.substr(0, 2)) === -1)
        return what + ' ' + pin + ' is not in ' + (STATE_NAMES[stateCode] || stateCode) + ' (starts ' + allowed.join('/') + ')';
    return '';
}
function setInfo(id, problems, okText) {
    var el = document.getElementById(id);
    if (problems.length) { el.className = 'd-block text-danger'; el.textContent = '\u26A0 ' + problems.join('; '); }
    else { el.className = 'd-block text-success'; el.textContent = okText ? '\u2714 ' + okText : ''; }
}
function custOpt() { var s = document.getElementById('wsCustomer'); return s.options[s.selectedIndex]; }
function checkAddresses() {
    var p = [], l = document.getElementById('wsLocation'), lo = l ? l.options[l.selectedIndex] : null;
    if (lo) {
        var lp = pinProblem(lo.dataset.pincode, sellerStateCode, 'Location PIN');
        if (lp) p.push(lp + ' — set it in Locations master');
        if (!(lo.dataset.address || '').trim()) p.push('Location address missing');
        if (!(lo.dataset.city || '').trim()) p.push('Location city missing');
        setInfo('locAddrInfo', p, 'Dispatch from PIN ' + lo.dataset.pincode);
    }
    var c = custOpt(); p = [];
    if (c && c.value) {
        var cs = (c.dataset.gstin || '').substr(0, 2);
        var cp = pinProblem(c.dataset.pincode, cs, 'Customer PIN');
        if (cp) p.push(cp + ' — update the customer');
        if (!(c.dataset.address || '').trim()) p.push('Customer address missing');
        if (!(c.dataset.city || '').trim()) p.push('Customer city missing');
        setInfo('custAddrInfo', p, (c.dataset.city || '') + ' ' + (c.dataset.pincode || ''));
    } else { setInfo('custAddrInfo', [], ''); }
    if (document.getElementById('shipDiff').checked) {
        var st = document.getElementById('shipState').value, sp = [];
        if (!st) sp.push('Select the ship-to state');
        else { var pp = pinProblem(document.getElementById('shipPin').value, st, 'Ship-to PIN'); if (pp) sp.push(pp); }
        setInfo('shipInfo', sp, st ? 'Place of supply: ' + (STATE_NAMES[st] || st) : '');
    }
}
function toggleShip() {
    var on = document.getElementById('shipDiff').checked;
    document.getElementById('shipFields').style.display = on ? '' : 'none';
    document.getElementById('shipNote').style.display = on ? 'none' : '';
    checkAddresses(); recalcAll();
}
function shipGstinChanged() {
    var g = document.getElementById('shipGstin').value.trim().toUpperCase();
    if (g.length >= 2 && STATE_NAMES[g.substr(0, 2)]) { document.getElementById('shipState').value = g.substr(0, 2); checkAddresses(); recalcAll(); }
}
// Place of supply: ship-to state when shipping elsewhere, else the buyer GSTIN state
function placeOfSupply() {
    if (document.getElementById('shipDiff').checked && document.getElementById('shipState').value) return document.getElementById('shipState').value;
    var c = custOpt(); return c && c.dataset.gstin ? c.dataset.gstin.substr(0, 2) : '';
}
function isInterstate() {
    var pos = placeOfSupply();
    if (pos && sellerStateCode) return pos !== sellerStateCode;
    var custState = (custOpt().dataset.state || '').toLowerCase().trim();
    return custState && custState !== companyState;
}
checkAddresses();

function addItem(product) {
    wsRowIdx++;
    var tr = document.createElement('tr');
    tr.className = 'ws-item-row';
    tr.id = 'wsRow' + wsRowIdx;
    tr.innerHTML = '<td>' + wsRowIdx + '</td>' +
        '<td>' + escHtml(product.name) + '<br><small class="text-muted">' + escHtml(product.item_code) + '</small>' +
        '<input type="hidden" name="product_id[]" value="' + product.id + '"></td>' +
        '<td><input type="text" name="hsn_code[]" class="form-control form-control-sm" value="' + escHtml(product.hsn_code) + '"></td>' +
        '<td><input type="number" name="qty[]" class="form-control form-control-sm ws-calc" value="1" min="0.01" step="any"></td>' +
        '<td><input type="number" name="rate[]" class="form-control form-control-sm ws-calc" value="' + product.selling_price.toFixed(2) + '" step="any"></td>' +
        '<td><input type="number" name="item_discount[]" class="form-control form-control-sm ws-calc" value="0" min="0" step="any"></td>' +
        '<td><input type="number" name="gst_percent[]" class="form-control form-control-sm ws-calc" value="' + product.gst_percent.toFixed(2) + '" step="any"></td>' +
        '<td class="text-right line-taxable">0.00</td>' +
        '<td class="text-right line-gst">0.00</td>' +
        '<td class="text-right line-total font-weight-bold">0.00</td>' +
        '<td><button type="button" class="btn btn-xs btn-danger" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>';
    document.getElementById('wsItemsBody').appendChild(tr);
    tr.querySelectorAll('.ws-calc').forEach(function(el) {
        el.addEventListener('input', function() { calcRow(tr); recalcAll(); });
    });
    calcRow(tr);
    recalcAll();
}

function removeRow(btn) {
    btn.closest('tr').remove();
    recalcAll();
}

function calcRow(tr) {
    var qty = parseFloat(tr.querySelector('[name="qty[]"]').value) || 0;
    var rate = parseFloat(tr.querySelector('[name="rate[]"]').value) || 0;
    var disc = parseFloat(tr.querySelector('[name="item_discount[]"]').value) || 0;
    var gst = parseFloat(tr.querySelector('[name="gst_percent[]"]').value) || 0;
    var taxable = Math.round((qty * rate - disc) * 100) / 100;
    if (taxable < 0) taxable = 0;
    var gstAmt = Math.round(taxable * gst / 100 * 100) / 100;
    var total = Math.round((taxable + gstAmt) * 100) / 100;
    tr.querySelector('.line-taxable').textContent = taxable.toFixed(2);
    tr.querySelector('.line-gst').textContent = gstAmt.toFixed(2);
    tr.querySelector('.line-total').textContent = total.toFixed(2);
}

function recalcAll() {
    var taxable = 0, gst = 0, total = 0;
    document.querySelectorAll('#wsItemsBody tr').forEach(function(tr) {
        taxable += parseFloat(tr.querySelector('.line-taxable').textContent) || 0;
        gst += parseFloat(tr.querySelector('.line-gst').textContent) || 0;
        total += parseFloat(tr.querySelector('.line-total').textContent) || 0;
    });
    document.getElementById('wsTaxable').textContent = taxable.toFixed(2);
    document.getElementById('wsGstAmt').textContent = gst.toFixed(2);
    document.getElementById('wsGrandTotal').textContent = total.toFixed(2);

    var inter = isInterstate();
    document.getElementById('rowCgst').style.display = inter ? 'none' : '';
    document.getElementById('rowSgst').style.display = inter ? 'none' : '';
    document.getElementById('rowIgst').style.display = inter ? '' : 'none';

    document.getElementById('summTaxable').textContent = taxable.toFixed(2);
    if (inter) {
        document.getElementById('summIgst').textContent = gst.toFixed(2);
        document.getElementById('summCgst').textContent = '0.00';
        document.getElementById('summSgst').textContent = '0.00';
    } else {
        var cgst = Math.round(gst / 2 * 100) / 100;
        var sgst = Math.round((gst - cgst) * 100) / 100;
        document.getElementById('summCgst').textContent = cgst.toFixed(2);
        document.getElementById('summSgst').textContent = sgst.toFixed(2);
        document.getElementById('summIgst').textContent = '0.00';
    }
    var roundOff = Math.round(total) - total;
    var grand = Math.round(total + roundOff);
    document.getElementById('summRound').textContent = roundOff.toFixed(2);
    document.getElementById('summTotal').textContent = grand.toFixed(2);
}

// Product search
var searchBox = document.getElementById('wsProductSearch');
var searchDropdown = null;

searchBox.addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    if (q.length < 2) { closeDropdown(); return; }
    var matches = wsProducts.filter(function(p) {
        return p.name.toLowerCase().indexOf(q) >= 0 ||
               (p.item_code && p.item_code.toLowerCase().indexOf(q) >= 0) ||
               (p.barcode && p.barcode.indexOf(q) >= 0);
    }).slice(0, 15);
    showDropdown(matches);
});

searchBox.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        var q = this.value.trim();
        var exact = wsProducts.find(function(p) {
            return (p.barcode && p.barcode === q) || (p.item_code && p.item_code.toLowerCase() === q.toLowerCase());
        });
        if (exact) { addItem(exact); this.value = ''; closeDropdown(); }
    }
});

function showDropdown(items) {
    closeDropdown();
    if (!items.length) return;
    searchDropdown = document.createElement('div');
    searchDropdown.style.cssText = 'position:absolute;z-index:1000;background:#fff;border:1px solid #ccc;max-height:250px;overflow-y:auto;width:300px;box-shadow:0 2px 6px rgba(0,0,0,.15);';
    items.forEach(function(p) {
        var d = document.createElement('div');
        d.style.cssText = 'padding:6px 10px;cursor:pointer;font-size:.85rem;border-bottom:1px solid #eee;';
        d.innerHTML = '<strong>' + escHtml(p.name) + '</strong><br><small>' + escHtml(p.item_code) + ' | &#8377;' + p.selling_price.toFixed(2) + ' | Stock: ' + p.stock_qty + '</small>';
        d.addEventListener('click', function() { addItem(p); searchBox.value = ''; closeDropdown(); searchBox.focus(); });
        d.addEventListener('mouseenter', function() { this.style.background = '#f0f0f0'; });
        d.addEventListener('mouseleave', function() { this.style.background = '#fff'; });
        searchDropdown.appendChild(d);
    });
    searchBox.parentNode.style.position = 'relative';
    searchBox.parentNode.appendChild(searchDropdown);
}

function closeDropdown() {
    if (searchDropdown) { searchDropdown.remove(); searchDropdown = null; }
}

document.addEventListener('click', function(e) { if (!searchBox.contains(e.target) && !(searchDropdown && searchDropdown.contains(e.target))) closeDropdown(); });

function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// Submit
document.getElementById('wsInvoiceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var custId = document.getElementById('wsCustomer').value;
    if (!custId) { alert('Please select a customer'); return; }
    var rows = document.querySelectorAll('#wsItemsBody tr');
    if (!rows.length) { alert('Please add at least one item'); return; }
    checkAddresses();
    var probs = ['locAddrInfo', 'custAddrInfo'].concat(document.getElementById('shipDiff').checked ? ['shipInfo'] : [])
        .map(function (id) { var el = document.getElementById(id); return el.className.indexOf('text-danger') >= 0 ? el.textContent : ''; })
        .filter(Boolean);
    if (probs.length) { alert('Please fix before saving:\n' + probs.join('\n')); return; }

    var btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

    var formData = new FormData(this);

    fetch('<?= base_url('wholesale_billing/create_ajax') ?>', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            window.location.href = '<?= base_url('wholesale_billing/view/') ?>' + d.invoice_id;
        } else {
            alert(d.message || 'Error creating invoice');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Create Wholesale Invoice';
        }
    })
    .catch(function(e) {
        alert('Network error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Create Wholesale Invoice';
    });
});
</script>
