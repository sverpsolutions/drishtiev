
<div class="row mb-3">
    <div class="col-md-8">
        <h5 class="font-weight-bold"><?= htmlspecialchars($po->po_number) ?></h5>
        <?php $sc=['approved'=>'success','vendor_confirmed'=>'info','dispatched'=>'warning','received'=>'dark','rejected'=>'danger'][$po->status]??'secondary'; ?>
        <span class="badge badge-<?= $sc ?>"><?= ucfirst(str_replace('_',' ',$po->status)) ?></span>
    </div>
    <div class="col-md-4 text-right">
        <?php if ($po->status==='approved'): ?>
        <a href="<?= base_url('vendor-portal/accept_po/'.$po->id) ?>" class="btn btn-success btn-sm mr-2" onclick="return confirm('Accept this Purchase Order?')"><i class="fas fa-check mr-1"></i> Accept</a>
        <a href="<?= base_url('vendor-portal/reject_po/'.$po->id) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject this order?')"><i class="fas fa-times mr-1"></i> Reject</a>
        <?php endif; ?>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6"><div class="card border-0 shadow-sm p-3" style="font-size:.85rem;">
        <table class="table table-sm mb-0">
            <tr><td class="text-muted">PO Date</td><td><?= date('d M Y',strtotime($po->created_at)) ?></td></tr>
            <tr><td class="text-muted">Expected Delivery</td><td><?= $po->expected_delivery ? date('d M Y',strtotime($po->expected_delivery)) : '-' ?></td></tr>
            <tr><td class="text-muted">Payment Terms</td><td><?= htmlspecialchars($po->payment_terms??'-') ?></td></tr>
            <tr><td class="text-muted">Net Total</td><td class="font-weight-bold text-success">₹<?= number_format($po->net_total,2) ?></td></tr>
            <?php if ($po->remarks): ?><tr><td class="text-muted">Remarks</td><td><?= htmlspecialchars($po->remarks) ?></td></tr><?php endif; ?>
        </table>
    </div></div>
    <div class="col-md-6"><div class="card border-0 shadow-sm p-3" style="font-size:.85rem;">
        <div class="font-weight-bold mb-2">Deliver To:</div>
        <?= htmlspecialchars($po->location_name ?? 'Main Store') ?><br>
        <?php if ($po->delivery_address): ?><?= htmlspecialchars($po->delivery_address) ?><?php endif; ?>
    </div></div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 font-weight-bold">Items</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:.8rem;">
            <thead class="thead-light"><tr><th>#</th><th>Product</th><th>Qty</th><th>Unit</th><th>Rate</th><th class="text-right">Amount</th></tr></thead>
            <tbody>
            <?php $i=1; foreach ($items as $item): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($item->product_name) ?></strong></td>
                <td><?= number_format($item->ordered_qty,2) ?></td>
                <td><?= $item->unit ?></td>
                <td>₹<?= number_format($item->rate,2) ?></td>
                <td class="text-right">₹<?= number_format($item->amount,2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Messaging -->
<div class="card border-0 shadow-sm">
    <div class="card-header py-2 font-weight-bold"><i class="fas fa-comments mr-1"></i> Message Buyer</div>
    <div class="card-body p-2" style="max-height:220px;overflow-y:auto;" id="chatBox">
        <?php foreach ($messages as $msg): ?>
        <div class="d-flex mb-2 <?= $msg->sender_type==='vendor'?'justify-content-end':'' ?>">
            <div class="rounded p-2 <?= $msg->sender_type==='vendor'?'bg-success text-white':'bg-light border' ?>" style="max-width:80%;font-size:.8rem;">
                <?= htmlspecialchars($msg->message) ?>
                <div style="font-size:.65rem;opacity:.7" class="text-right"><?= date('d M H:i',strtotime($msg->created_at)) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="card-footer p-2">
        <form onsubmit="sendVendorMsg(event)">
            <div class="input-group input-group-sm">
                <input type="text" id="msgInput" class="form-control" placeholder="Type message...">
                <div class="input-group-append"><button class="btn btn-dark" type="submit"><i class="fas fa-paper-plane"></i></button></div>
            </div>
        </form>
    </div>
</div>

<script>
var chatBox = document.getElementById('chatBox');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
function sendVendorMsg(e) {
    e.preventDefault();
    var msg = document.getElementById('msgInput').value.trim();
    if (!msg) return;
    var fd = new FormData();
    fd.append('po_id', <?= $po->id ?>);
    fd.append('supplier_id', <?= $po->supplier_id ?>);
    fd.append('message', msg);
    fetch('<?= base_url('vendor-portal/send_message') ?>', {method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){return r.json();}).then(function(d){
        if(d.success){
            var div=document.createElement('div'); div.className='d-flex mb-2 justify-content-end';
            div.innerHTML='<div class="rounded p-2 bg-success text-white" style="max-width:80%;font-size:.8rem;">'+msg+'<div style="font-size:.65rem;opacity:.7" class="text-right">Just now</div></div>';
            chatBox.appendChild(div); chatBox.scrollTop=chatBox.scrollHeight;
            document.getElementById('msgInput').value='';
        }
    });
}
</script>
