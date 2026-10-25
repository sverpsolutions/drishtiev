<?php
$statuses = [
    'draft'=>['Draft','secondary'],'submitted'=>['Submitted','primary'],
    'pending_approval'=>['Pending Approval','warning'],'approved'=>['Approved','success'],
    'vendor_confirmed'=>['Vendor Confirmed','info'],'dispatched'=>['Dispatched','warning'],
    'in_transit'=>['In Transit','warning'],'received'=>['Received','success'],
    'invoiced'=>['Invoiced','primary'],'paid'=>['Paid','success'],
    'rejected'=>['Rejected','danger'],'cancelled'=>['Cancelled','dark'],
];
[$sl,$sc] = $statuses[$po->status] ?? ['Unknown','secondary'];
$flash_s = $this->session->flashdata('success');
$flash_e = $this->session->flashdata('error');
$print_url  = base_url('purchase_orders/print_po/'.$po->id);
$wa_text    = urlencode("PO {$po->po_number} | Supplier: {$po->supplier_name} | Amount: Rs.".number_format($po->net_total,2)." | Delivery: ".($po->expected_delivery ? date('d M Y',strtotime($po->expected_delivery)) : 'TBD'));
$email_subj = urlencode("Purchase Order {$po->po_number} from Sandal House");
$email_body = urlencode("Dear {$po->supplier_name},\n\nKindly note PO {$po->po_number} for Rs.".number_format($po->net_total,2).".\nExpected Delivery: ".($po->expected_delivery ? date('d M Y',strtotime($po->expected_delivery)) : 'TBD')."\n\nRegards,\nSandal House");
$sup_email  = $po->email ?? '';
$sup_phone  = $po->supplier_mobile ?? '';
?>

<?php if ($flash_s): ?><div class="alert alert-success alert-dismissible fade show py-2"><?= $flash_s ?><button class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
<?php if ($flash_e): ?><div class="alert alert-danger alert-dismissible fade show py-2"><?= $flash_e ?><button class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

<!-- Top header bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <h5 class="mb-0 font-weight-bold">
                    <i class="fas fa-file-alt text-success mr-1"></i>
                    <?= htmlspecialchars($po->po_number) ?>
                    <span class="badge badge-<?= $sc ?> ml-1" style="font-size:.7rem;vertical-align:middle;"><?= $sl ?></span>
                </h5>
                <small class="text-muted">
                    <?= date('d M Y H:i', strtotime($po->created_at)) ?>
                    &nbsp;&middot;&nbsp; Buyer: <strong><?= htmlspecialchars($po->buyer_name ?? '—') ?></strong>
                    &nbsp;&middot;&nbsp; Location: <strong><?= htmlspecialchars($po->location_name ?? 'Main') ?></strong>
                </small>
            </div>
            <!-- Action & Share Buttons -->
            <div class="d-flex flex-wrap mt-1" style="gap:5px;">
                <!-- Share -->
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown">
                        <i class="fas fa-share-alt"></i> Share
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="https://wa.me/<?= preg_replace('/\D/','',$sup_phone) ?>?text=<?= $wa_text ?>" target="_blank">
                            <i class="fab fa-whatsapp text-success mr-2"></i>WhatsApp Supplier
                        </a>
                        <a class="dropdown-item" href="https://wa.me/?text=<?= $wa_text ?>" target="_blank">
                            <i class="fab fa-whatsapp text-success mr-2"></i>WhatsApp (Any Number)
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="mailto:<?= htmlspecialchars($sup_email) ?>?subject=<?= $email_subj ?>&body=<?= $email_body ?>">
                            <i class="fas fa-envelope text-primary mr-2"></i>Email Supplier
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?= $print_url ?>" target="_blank">
                            <i class="fas fa-print text-dark mr-2"></i>Print / PDF
                        </a>
                        <a class="dropdown-item" href="#" onclick="window.open('<?= $print_url ?>','_blank');return false;">
                            <i class="fas fa-file-pdf text-danger mr-2"></i>Download as PDF
                        </a>
                    </div>
                </div>
                <!-- Actions -->
                <?php if (in_array($po->status, ['draft','submitted'])): ?>
                <a href="<?= base_url('purchase_orders/edit/'.$po->id) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <?php endif; ?>
                <?php if ($po->status === 'pending_approval' && $is_admin): ?>
                <a href="<?= base_url('purchase_orders/approve/'.$po->id) ?>" class="btn btn-sm btn-warning">
                    <i class="fas fa-check-circle"></i> Approve
                </a>
                <?php endif; ?>
                <?php if (in_array($po->status, ['approved','vendor_confirmed'])): ?>
                <a href="<?= base_url('grn/create/'.$po->id) ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-truck-loading"></i> Receive (GRN)
                </a>
                <?php endif; ?>
                <?php if (in_array($po->status, ['draft','submitted']) && $is_admin): ?>
                <a href="<?= base_url('purchase_orders/cancel/'.$po->id) ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Cancel this PO?')">
                    <i class="fas fa-ban"></i> Cancel
                </a>
                <?php endif; ?>
                <a href="<?= base_url('purchase_orders') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Info Row -->
<div class="row mb-3">
    <!-- Supplier -->
    <div class="col-md-4 mb-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 font-weight-bold" style="font-size:.8rem;background:#2c3e50;color:#fff;">
                <i class="fas fa-building mr-1"></i> Supplier Details
            </div>
            <div class="card-body py-2 px-3" style="font-size:.82rem;">
                <div class="font-weight-bold text-dark" style="font-size:.9rem;"><?= htmlspecialchars($po->supplier_name) ?></div>
                <?php if (!empty($po->supplier_address)): ?>
                <div class="text-muted mt-1"><?= htmlspecialchars($po->supplier_address) ?><?php if (!empty($po->supplier_city)): ?>, <?= htmlspecialchars($po->supplier_city) ?><?php endif; ?></div>
                <?php endif; ?>
                <?php if (!empty($po->supplier_mobile)): ?>
                <div class="mt-1"><i class="fas fa-phone fa-xs text-muted mr-1"></i><?= htmlspecialchars($po->supplier_mobile) ?>
                    <a href="https://wa.me/<?= preg_replace('/\D/','',$po->supplier_mobile) ?>?text=<?= $wa_text ?>" target="_blank" class="ml-1 text-success" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
                <?php endif; ?>
                <?php if (!empty($po->email)): ?>
                <div class="mt-1"><i class="fas fa-envelope fa-xs text-muted mr-1"></i><?= htmlspecialchars($po->email) ?></div>
                <?php endif; ?>
                <?php if (!empty($po->supplier_gstin)): ?>
                <div class="mt-1"><span class="badge badge-light border">GST: <?= htmlspecialchars($po->supplier_gstin) ?></span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Order Info -->
    <div class="col-md-4 mb-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 font-weight-bold" style="font-size:.8rem;background:#2c3e50;color:#fff;">
                <i class="fas fa-clipboard-list mr-1"></i> Order Info
            </div>
            <div class="card-body py-2 px-0">
                <table class="table table-sm mb-0" style="font-size:.82rem;">
                    <tr>
                        <td class="pl-3 text-muted border-0 py-1">Order Date</td>
                        <td class="border-0 py-1 font-weight-bold"><?= date('d M Y', strtotime($po->created_at)) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3 text-muted border-0 py-1">Exp. Delivery</td>
                        <td class="border-0 py-1 font-weight-bold <?= ($po->expected_delivery && strtotime($po->expected_delivery) < time() && !in_array($po->status,['received','cancelled'])) ? 'text-danger' : 'text-success' ?>">
                            <?= $po->expected_delivery ? date('d M Y', strtotime($po->expected_delivery)) : '—' ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="pl-3 text-muted border-0 py-1">Payment Terms</td>
                        <td class="border-0 py-1"><?= htmlspecialchars($po->payment_terms ?? '—') ?></td>
                    </tr>
                    <?php if (!empty($po->reference)): ?>
                    <tr><td class="pl-3 text-muted border-0 py-1">Reference</td><td class="border-0 py-1"><?= htmlspecialchars($po->reference) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($po->remarks)): ?>
                    <tr><td class="pl-3 text-muted border-0 py-1">Remarks</td><td class="border-0 py-1"><?= htmlspecialchars($po->remarks) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($po->delivery_address)): ?>
                    <tr><td class="pl-3 text-muted border-0 py-1">Deliver To</td><td class="border-0 py-1"><?= htmlspecialchars($po->delivery_address) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    <!-- Totals -->
    <div class="col-md-4 mb-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 font-weight-bold" style="font-size:.8rem;background:#2c3e50;color:#fff;">
                <i class="fas fa-calculator mr-1"></i> Amount Summary
            </div>
            <div class="card-body py-2 px-0">
                <table class="table table-sm mb-0" style="font-size:.82rem;">
                    <tr><td class="pl-3 text-muted border-0 py-1">Gross Amount</td><td class="pr-3 text-right border-0 py-1">&#8377;<?= number_format($po->gross_amount,2) ?></td></tr>
                    <?php if ($po->total_discount > 0): ?>
                    <tr><td class="pl-3 text-muted border-0 py-1">Discount</td><td class="pr-3 text-right text-danger border-0 py-1">- &#8377;<?= number_format($po->total_discount,2) ?></td></tr>
                    <?php endif; ?>
                    <tr><td class="pl-3 text-muted border-0 py-1">GST</td><td class="pr-3 text-right border-0 py-1">&#8377;<?= number_format($po->total_tax,2) ?></td></tr>
                    <?php if ($po->freight_charges > 0): ?>
                    <tr><td class="pl-3 text-muted border-0 py-1">Freight</td><td class="pr-3 text-right border-0 py-1">&#8377;<?= number_format($po->freight_charges,2) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($po->round_off != 0): ?>
                    <tr><td class="pl-3 text-muted border-0 py-1">Round Off</td><td class="pr-3 text-right border-0 py-1">&#8377;<?= number_format($po->round_off,2) ?></td></tr>
                    <?php endif; ?>
                    <tr class="border-top">
                        <td class="pl-3 font-weight-bold border-0 pt-2 pb-1">Net Total</td>
                        <td class="pr-3 text-right font-weight-bold text-success border-0 pt-2 pb-1" style="font-size:1.05rem;">&#8377;<?= number_format($po->net_total,2) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Approval Log -->
<?php if (!empty($approval_log)): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 bg-white font-weight-bold" style="font-size:.85rem;">
        <i class="fas fa-tasks text-warning mr-1"></i> Approval Workflow
    </div>
    <div class="card-body py-2">
        <div class="d-flex flex-wrap" style="gap:10px;">
        <?php
        $level_names = [1=>'L1 - Store Manager',2=>'L2 - Purchase Mgr',3=>'L3 - Finance',4=>'L4 - Director'];
        foreach ($approval_log as $al):
            $ac = $al->action==='approved'?'success':($al->action==='rejected'?'danger':($al->action==='pending'?'warning':'secondary'));
        ?>
        <div class="border rounded p-2 text-center" style="min-width:140px;font-size:.78rem;">
            <div class="font-weight-bold text-muted"><?= $level_names[$al->level] ?? 'Level '.$al->level ?></div>
            <span class="badge badge-<?= $ac ?> mt-1"><?= ucfirst($al->action) ?></span>
            <?php if (!empty($al->approver_name)): ?><div class="mt-1 font-weight-bold"><?= htmlspecialchars($al->approver_name) ?></div><?php endif; ?>
            <?php if (!empty($al->acted_at)): ?><div class="text-muted" style="font-size:.7rem;"><?= date('d M Y H:i',strtotime($al->acted_at)) ?></div><?php endif; ?>
            <?php if (!empty($al->remarks)): ?><div class="text-muted font-italic mt-1">"<?= htmlspecialchars($al->remarks) ?>"</div><?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Items Table -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
        <span class="font-weight-bold" style="font-size:.85rem;"><i class="fas fa-boxes mr-1 text-muted"></i> Order Items</span>
        <span class="text-muted small"><?= count($items) ?> item<?= count($items) != 1 ? 's' : '' ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:.79rem;">
                <thead style="background:#2c3e50;color:#fff;">
                    <tr>
                        <th class="pl-3">#</th>
                        <th>Product</th>
                        <th>HSN</th>
                        <th>Unit</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Sugg</th>
                        <th class="text-center">Order Qty</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">Disc%</th>
                        <th class="text-right">GST%</th>
                        <th class="text-right pr-3">Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php $sno = 1; foreach ($items as $item): ?>
                <tr>
                    <td class="pl-3 text-muted"><?= $sno++ ?></td>
                    <td>
                        <div class="font-weight-bold"><?= htmlspecialchars($item->product_name) ?></div>
                        <?php if (!empty($item->item_code)): ?><small class="text-muted"><?= htmlspecialchars($item->item_code) ?></small><?php endif; ?>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($item->hsn_code ?? '—') ?></td>
                    <td><?= htmlspecialchars($item->unit ?? 'PCS') ?></td>
                    <td class="text-center">
                        <?php $stk = $item->current_stock ?? 0; $sc2 = $stk <= 0 ? 'text-danger' : ($stk < 10 ? 'text-warning' : 'text-success'); ?>
                        <span class="<?= $sc2 ?> font-weight-bold"><?= number_format($stk,0) ?></span>
                    </td>
                    <td class="text-center text-info"><?= number_format($item->suggested_qty ?? 0,2) ?></td>
                    <td class="text-center font-weight-bold"><?= number_format($item->ordered_qty,2) ?></td>
                    <td class="text-right">&#8377;<?= number_format($item->rate,2) ?></td>
                    <td class="text-right"><?= $item->discount_pct ?>%</td>
                    <td class="text-right"><?= $item->gst_pct ?>%</td>
                    <td class="text-right pr-3 font-weight-bold">&#8377;<?= number_format($item->amount,2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <td colspan="8" class="pl-3 text-right font-weight-bold border-top py-2" style="font-size:.83rem;">
                            Gross: &#8377;<?= number_format($po->gross_amount,2) ?>
                            <?php if ($po->total_discount > 0): ?>&nbsp;| Disc: -&#8377;<?= number_format($po->total_discount,2) ?><?php endif; ?>
                            &nbsp;| GST: &#8377;<?= number_format($po->total_tax,2) ?>
                            <?php if ($po->freight_charges > 0): ?>&nbsp;| Freight: &#8377;<?= number_format($po->freight_charges,2) ?><?php endif; ?>
                        </td>
                        <td colspan="3" class="pr-3 text-right font-weight-bold border-top py-2 text-success" style="font-size:1rem;">
                            Net: &#8377;<?= number_format($po->net_total,2) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Supplier Chat -->
<div class="card border-0 shadow-sm">
    <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
        <span class="font-weight-bold" style="font-size:.85rem;"><i class="fas fa-comments text-primary mr-1"></i> Supplier Communication</span>
        <?php if (!empty($messages)): ?><span class="badge badge-primary"><?= count($messages) ?></span><?php endif; ?>
    </div>
    <div id="chatBox" class="card-body p-2" style="max-height:250px;overflow-y:auto;background:#f0f2f5;">
        <?php if (empty($messages)): ?>
        <div class="text-center text-muted py-3 small">No messages yet. Start a conversation below.</div>
        <?php endif; ?>
        <?php foreach ($messages as $msg): $isBuyer = $msg->sender_type === 'buyer'; ?>
        <div class="d-flex mb-2 <?= $isBuyer ? 'justify-content-end' : '' ?>">
            <div class="rounded px-3 py-2" style="max-width:72%;font-size:.81rem;box-shadow:0 1px 2px rgba(0,0,0,.1);background:<?= $isBuyer ? '#007bff' : '#fff' ?>;color:<?= $isBuyer ? '#fff' : '#333' ?>;">
                <div style="font-size:.64rem;opacity:.65;margin-bottom:2px;"><?= $isBuyer ? 'You' : 'Supplier' ?></div>
                <?= nl2br(htmlspecialchars($msg->message)) ?>
                <div class="text-right" style="font-size:.64rem;opacity:.6;margin-top:2px;"><?= date('d M, H:i', strtotime($msg->created_at)) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="card-footer p-2 bg-white">
        <form onsubmit="sendMsg(event)" class="d-flex" style="gap:6px;">
            <input type="text" id="msgInput" class="form-control form-control-sm" placeholder="Type a message to supplier..." autocomplete="off">
            <button type="submit" class="btn btn-sm btn-primary px-3"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>
</div>

<script>
var chatBox = document.getElementById('chatBox');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

function sendMsg(e) {
    e.preventDefault();
    var msg = document.getElementById('msgInput').value.trim();
    if (!msg) return;
    var fd = new FormData();
    fd.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');
    fd.append('po_id', <?= (int)$po->id ?>);
    fd.append('supplier_id', <?= (int)$po->supplier_id ?>);
    fd.append('message', msg);
    var btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true;
    fetch('<?= base_url('purchase_orders/send_message_ajax') ?>', {
        method: 'POST', body: fd,
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            var div = document.createElement('div');
            div.className = 'd-flex mb-2 justify-content-end';
            div.innerHTML = '<div class="rounded px-3 py-2" style="max-width:72%;font-size:.81rem;box-shadow:0 1px 2px rgba(0,0,0,.1);background:#007bff;color:#fff;">' +
                '<div style="font-size:.64rem;opacity:.65;margin-bottom:2px;">You</div>' +
                msg.replace(/</g,'&lt;') +
                '<div class="text-right" style="font-size:.64rem;opacity:.6;margin-top:2px;">Just now</div></div>';
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
            document.getElementById('msgInput').value = '';
        }
        btn.disabled = false;
    })
    .catch(function() { btn.disabled = false; });
}
</script>
