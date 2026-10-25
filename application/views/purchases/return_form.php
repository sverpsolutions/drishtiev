<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Purchase Return (PRN)</h5>
        <small class="text-muted"><a href="<?= base_url('purchases') ?>">Purchases</a> / <?= $purchase->purchase_no ?> / Return</small>
    </div>
    <a href="<?= base_url('purchases/view/'.$purchase->id) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-warning text-dark py-2 font-weight-bold" style="font-size:.85rem;">
        PRN against: <?= $purchase->purchase_no ?> &mdash; <?= htmlspecialchars($purchase->supplier_name) ?>
    </div>
</div>
<?= form_open('purchases/return_create/'.$purchase->id) ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:.85rem;">
                <thead class="thead-light">
                    <tr><th>Product</th><th class="text-center">Purchased Qty</th><th class="text-right">Rate</th><th style="width:140px" class="text-center">Return Qty</th><th class="text-right">Amount</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item->product_name) ?></td>
                    <td class="text-center"><?= $item->qty ?> <?= $item->unit ?></td>
                    <td class="text-right">&#8377; <?= number_format($item->price,2) ?></td>
                    <td>
                        <input type="number" name="return_qty[<?= $item->id ?>]" class="form-control form-control-sm text-center return-qty" data-price="<?= $item->price ?>" value="0" min="0" max="<?= $item->qty ?>" data-row="<?= $item->id ?>">
                        <?php if (!empty($item->maintain_serial)): ?>
                        <input type="hidden" name="prn_serial_json[<?= $item->id ?>]" id="prnsj_<?= $item->id ?>" value="">
                        <input type="hidden" id="prnpid_<?= $item->id ?>" value="<?= (int)$item->product_id ?>">
                        <input type="hidden" id="prnnm_<?= $item->id ?>" value="<?= htmlspecialchars($item->product_name) ?>">
                        <button type="button" class="btn btn-warning btn-sm py-0 px-2 mt-1 w-100" style="font-size:.72rem;"
                                onclick="openPrnSerials(<?= $item->id ?>)">
                            <i class="fas fa-barcode"></i> Serials <span id="prnsc_<?= $item->id ?>">0/0</span>
                        </button>
                        <?php endif; ?>
                    </td>
                    <td class="text-right" id="ret_<?= $item->id ?>">&#8377; 0.00</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr class="bg-light font-weight-bold"><td colspan="4" class="text-right">Total:</td><td class="text-right" id="prn_total">&#8377; 0.00</td></tr></tfoot>
            </table>
        </div>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="form-group"><label>Reason for Return</label><textarea name="reason" class="form-control" rows="2"></textarea></div>
        <button type="submit" class="btn btn-warning">Create PRN</button>
        <a href="<?= base_url('purchases/view/'.$purchase->id) ?>" class="btn btn-outline-secondary ml-2">Cancel</a>
    </div>
</div>
<?= form_close() ?>
<script>
document.querySelectorAll('.return-qty').forEach(function(inp){
    inp.addEventListener('input', function(){
        const price=parseFloat(this.dataset.price)||0, qty=parseInt(this.value)||0;
        const id=this.closest('tr').querySelector('[id^=ret_]').id.replace('ret_','');
        document.getElementById('ret_'+id).textContent='&#8377; '+(price*qty).toFixed(2);
        let t=0; document.querySelectorAll('.return-qty').forEach(function(i){t+=(parseFloat(i.dataset.price)||0)*(parseInt(i.value)||0);});
        document.getElementById('prn_total').textContent='&#8377; '+t.toFixed(2);
    });
});
</script>

<script>
// Serial picker for the return. Only rows whose item is serialized have a
// button; every other row behaves exactly as before.
var prnSerials = {};
document.querySelectorAll('[id^=prnsj_]').forEach(function (f) {
    var id = f.id.replace('prnsj_', '');
    var btn = document.getElementById('prnsc_' + id);
    prnSerials[id] = {
        productId: document.getElementById('prnpid_' + id).value,
        productName: document.getElementById('prnnm_' + id).value,
        serials: []
    };
});

function prnQty(id) {
    var i = document.querySelector('[name="return_qty[' + id + ']"]');
    return Math.floor(parseFloat(i && i.value) || 0);
}

function syncPrn(id) {
    var st = prnSerials[id]; if (!st) return;
    var qty = prnQty(id);
    if (st.serials.length > qty) st.serials = st.serials.slice(0, qty);
    var f = document.getElementById('prnsj_' + id);
    if (f) f.value = JSON.stringify(st.serials);
    var c = document.getElementById('prnsc_' + id);
    if (c) {
        c.textContent = st.serials.length + '/' + qty;
        var b = c.closest('button');
        b.classList.remove('btn-warning', 'btn-success');
        b.classList.add(st.serials.length === qty && qty > 0 ? 'btn-success' : 'btn-warning');
    }
}

function openPrnSerials(id) {
    var st = prnSerials[id]; if (!st) return;
    var qty = prnQty(id);
    if (qty < 1) { alert('Enter the return quantity for this item first.'); return; }
    SerialCapture.open({
        mode: 'outbound',
        productId: st.productId,
        productName: st.productName,
        qty: qty,
        serials: st.serials,
        enforceLocation: false,   // the unit may sit at any branch; the server checks it came from THIS purchase
        onSave: function (list) { st.serials = list; syncPrn(id); }
    });
}

document.querySelectorAll('.return-qty').forEach(function (i) {
    i.addEventListener('input', function () { if (prnSerials[this.dataset.row]) syncPrn(this.dataset.row); });
});

document.querySelector('form').addEventListener('submit', function (e) {
    var bad = [];
    Object.keys(prnSerials).forEach(function (id) {
        var qty = prnQty(id);
        if (qty > 0 && prnSerials[id].serials.length !== qty) {
            bad.push(document.getElementById('prnnm_' + id).value + ' — ' + prnSerials[id].serials.length + ' of ' + qty + ' selected');
        }
    });
    if (bad.length) {
        e.preventDefault();
        alert('Select the serial numbers being returned:\n\n\u2022 ' + bad.join('\n\u2022 '));
    }
});
</script>

<?php $this->load->view('serials/_capture_modal'); ?>
