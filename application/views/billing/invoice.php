<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0"><?= $invoice->invoice_no ?></h5>
        <small class="text-muted"><a href="<?= base_url('billing') ?>">Billing</a> / Invoice</small>
    </div>
    <div>
        <a href="<?= base_url('billing/pdf/'.$invoice->id) ?>" class="btn btn-dark btn-sm">&#8659; PDF</a>
        <a href="<?= base_url('billing/print_invoice/'.$invoice->id) ?>" class="btn btn-outline-dark btn-sm ml-1" target="_blank">&#128438; Print</a>
        <button class="btn btn-outline-primary btn-sm ml-1" data-toggle="modal" data-target="#emailModal">&#128231; Email</button>
        <?php
        // WhatsApp button — dropdown: text message + PDF download
        $wa_phone = preg_replace('/\D/', '', $invoice->customer_phone ?? '');
        if (strlen($wa_phone) === 10) $wa_phone = '91' . $wa_phone;
        $company = $this->Invoice_model->get_company_settings();
        $public_link = base_url('portal/public_invoice/' . $invoice->id);
        $wa_msg  = urlencode(
            "Dear " . $invoice->customer_name . ",\n" .
            "Please find your Invoice *" . $invoice->invoice_no . "* dated " . date('d M Y', strtotime($invoice->invoice_date)) . ".\n" .
            "Amount: *₹" . number_format($invoice->total_amount, 2) . "*\n" .
            ($invoice->due_amount > 0 ? "Amount Due: *₹" . number_format($invoice->due_amount, 2) . "*\n" : "Status: *PAID ✓*\n") .
            "View / Download Invoice: " . $public_link . "\n\n" .
            "From: " . ($company['company_name'] ?? 'SV ERP Solutions')
        );
        if (!empty($wa_phone) && strlen($wa_phone) >= 10):
        ?>
        <div class="btn-group ml-1" style="display:inline-flex;">
            <a href="https://wa.me/<?= $wa_phone ?>?text=<?= $wa_msg ?>" target="_blank"
               class="btn btn-sm" style="background:#25D366;color:#fff;border:none;padding:.2rem .5rem;border-radius:4px 0 0 4px;">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
            <button type="button" class="btn btn-sm dropdown-toggle dropdown-toggle-split"
                    style="background:#1ead55;color:#fff;border:none;border-radius:0 4px 4px 0;padding:.2rem .35rem;"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            </button>
            <div class="dropdown-menu dropdown-menu-right" style="min-width:200px;">
                <a class="dropdown-item" href="https://wa.me/<?= $wa_phone ?>?text=<?= $wa_msg ?>" target="_blank">
                    <i class="fab fa-whatsapp text-success mr-1"></i> Send Text Message
                </a>
                <a class="dropdown-item" href="#" onclick="copyPublicLink('<?= $public_link ?>'); return false;">
                    <i class="fas fa-link text-info mr-1"></i> Copy Public Link
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="<?= base_url('billing/pdf/'.$invoice->id) ?>"
                   onclick="setTimeout(function(){ window.open('https://wa.me/<?= $wa_phone ?>?text=<?= $wa_msg ?>','_blank'); }, 1500); return true;">
                    <i class="fas fa-file-pdf text-danger mr-1"></i> Download PDF &amp; Open WhatsApp
                </a>
                <small class="dropdown-item text-muted" style="font-size:.75rem;cursor:default;white-space:normal;">
                    PDF downloads first, then attach manually in WhatsApp
                </small>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($invoice->status !== 'cancelled'): ?>
        <a href="<?= base_url('billing/edit/'.$invoice->id) ?>" class="btn btn-warning btn-sm ml-1"><i class="fas fa-edit"></i> Edit</a>
        <?php if ($this->session->userdata('role') === 'admin' || $this->session->userdata('role') === 'superadmin' || !empty($allow_payment_mode_change)): ?>
        <a href="#" class="btn btn-info btn-sm ml-1" onclick="openPmModal(<?= $invoice->id ?>, '<?= $invoice->invoice_no ?>', '<?= $invoice->payment_mode ?>'); return false;"><i class="fas fa-exchange-alt"></i> Pay Mode</a>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($invoice->status !== 'cancelled'): ?>
        <a href="<?= base_url('refunds/create?invoice_no='.$invoice->invoice_no) ?>" class="btn btn-outline-warning btn-sm ml-1"><i class="fas fa-undo"></i> Refund/Return</a>
        <?php endif; ?>
        <?php if ($this->session->userdata('role')==='admin' && $invoice->status!=='cancelled'): ?>
        <a href="<?= base_url('billing/cancel/'.$invoice->id) ?>" class="btn btn-outline-danger btn-sm ml-1" onclick="return confirm('Cancel invoice? Stock will be restored.')">Cancel</a>
        <?php endif; ?>
        <a href="<?= base_url('billing/audit_log/'.$invoice->id) ?>" class="btn btn-outline-secondary btn-sm ml-1"><i class="fas fa-history"></i></a>
        <a href="<?= base_url('billing') ?>" class="btn btn-outline-secondary btn-sm ml-1">&larr; Back</a>
    </div>
</div>

<?php if (!empty($invoice->customer_gstin) && $invoice->status !== 'cancelled'): ?>
<div class="alert <?= ($invoice->einvoice_status ?? 'not_applicable') === 'generated' ? 'alert-success' : 'alert-light border' ?> py-2 mb-3 d-flex justify-content-between align-items-center">
    <div>
        <i class="fas fa-file-contract mr-1"></i>
        <strong>e-Invoice (IRN):</strong>
        <?php if (($invoice->einvoice_status ?? '') === 'generated'): ?>
            <span class="badge badge-success">Generated</span>
            <span class="text-monospace small ml-1"><?= htmlspecialchars($invoice->einvoice_irn) ?></span>
        <?php elseif (($invoice->einvoice_status ?? '') === 'pending'): ?>
            <span class="badge badge-warning">Exported — Awaiting IRN</span>
        <?php else: ?>
            <span class="badge badge-secondary">Not Exported</span>
        <?php endif; ?>
    </div>
    <a href="<?= base_url('einvoice?from='.date('Y-m-d',strtotime($invoice->invoice_date)).'&to='.date('Y-m-d',strtotime($invoice->invoice_date))) ?>" class="btn btn-outline-primary btn-xs py-0">Manage in e-Invoice Dashboard</a>
</div>
<?php endif; ?>

<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?= $this->session->flashdata('success') ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div><?php endif; ?>
<?php if ($invoice->status==='cancelled'): ?><div class="alert alert-danger"><strong>This invoice has been cancelled.</strong> Stock restored.</div><?php endif; ?>

<div class="row mb-3">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100"><div class="card-body" style="font-size:.87rem;">
            <p class="font-weight-bold mb-1" style="font-size:1rem;"><?= htmlspecialchars($invoice->customer_name) ?></p>
            <p class="mb-1"><?= htmlspecialchars($invoice->customer_address??'') ?><?= $invoice->customer_city?', '.htmlspecialchars($invoice->customer_city):'' ?></p>
            <p class="mb-1">Ph: <?= htmlspecialchars($invoice->customer_phone??'—') ?></p>
            <?php if($invoice->customer_gstin): ?><p class="mb-0">GSTIN: <code><?= htmlspecialchars($invoice->customer_gstin) ?></code></p><?php endif; ?>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body" style="font-size:.87rem;">
            <p class="mb-1"><strong>Invoice No:</strong> <?= $invoice->invoice_no ?></p>
            <p class="mb-1"><strong>Date:</strong> <?= date('d M Y', strtotime($invoice->invoice_date)) ?></p>
            <p class="mb-1"><strong>Type:</strong> <?= ucfirst($invoice->invoice_type) ?></p>
            <p class="mb-1"><strong>Tax:</strong> <?= $invoice->is_interstate ? 'Interstate — IGST' : 'Intrastate — CGST+SGST' ?></p>
            <p class="mb-0"><strong>Payment:</strong> <?= ucfirst($invoice->payment_mode) ?></p>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body" style="font-size:.87rem;">
            <p class="mb-1"><strong>Subtotal:</strong> &#8377; <?= number_format($invoice->subtotal,2) ?></p>
            <p class="mb-1"><strong>Discount:</strong> &#8377; <?= number_format($invoice->discount,2) ?></p>
            <?php if (!empty($company['enable_gst']) || $invoice->total_gst > 0): ?>
            <p class="mb-1"><strong>Taxable:</strong> &#8377; <?= number_format($invoice->taxable_amount,2) ?></p>
            <?php if($invoice->is_interstate): ?>
            <p class="mb-1"><strong>IGST:</strong> &#8377; <?= number_format($invoice->igst_amount,2) ?></p>
            <?php else: ?>
            <p class="mb-1"><strong>CGST:</strong> &#8377; <?= number_format($invoice->cgst_amount,2) ?></p>
            <p class="mb-1"><strong>SGST:</strong> &#8377; <?= number_format($invoice->sgst_amount,2) ?></p>
            <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($invoice->cd_percent) && $invoice->cd_percent > 0): ?>
            <p class="mb-1 text-danger">CD (<?= $invoice->cd_percent ?>%): - &#8377; <?= number_format($invoice->cd_amount,2) ?></p>
            <?php endif; ?>
            <?php if (!empty($invoice->bill_discount_amount) && $invoice->bill_discount_amount > 0): ?>
            <p class="mb-1 text-danger font-weight-bold">
                Store Discount
                <?php if ($invoice->bill_discount_percent > 0): ?>(<?= $invoice->bill_discount_percent ?>%)<?php endif; ?>:
                - &#8377; <?= number_format($invoice->bill_discount_amount,2) ?>
                <?php if (!empty($invoice->bill_discount_reason)): ?><br><small class="text-muted"><?= htmlspecialchars($invoice->bill_discount_reason) ?></small><?php endif; ?>
            </p>
            <?php endif; ?>
            <?php if (!empty($invoice->round_off) && $invoice->round_off != 0): ?>
            <p class="mb-1 text-muted">Round Off: <?= $invoice->round_off > 0 ? '+' : '-' ?> &#8377; <?= number_format(abs($invoice->round_off),2) ?></p>
            <?php endif; ?>
            <p class="mb-1 font-weight-bold">Total: &#8377; <?= number_format($invoice->total_amount,2) ?></p>
            <?php if (isset($invoice->wallet_amount_used) && $invoice->wallet_amount_used > 0): ?>
            <p class="mb-1 text-info">Wallet Used: &#8377; <?= number_format($invoice->wallet_amount_used,2) ?></p>
            <?php endif; ?>
            <?php if (isset($invoice->wallet_amount_credited) && $invoice->wallet_amount_credited > 0): ?>
            <p class="mb-1 text-info">Wallet Credited: &#8377; <?= number_format($invoice->wallet_amount_credited,2) ?></p>
            <?php endif; ?>
            <p class="mb-1 text-success">Paid: &#8377; <?= number_format($invoice->paid_amount,2) ?></p>
            <?php if ($invoice->due_amount > 0): ?>
            <p class="mb-0 text-danger font-weight-bold">Due: &#8377; <?= number_format($invoice->due_amount,2) ?></p>
            <?php else: ?>
            <p class="mb-0 text-success font-weight-bold">Due: &#8377; 0.00</p>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Items</div>
    <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.85rem;">
            <thead class="thead-light">
                <tr><th>#</th><th>Product</th>
                <?php if (!empty($company['enable_gst']) || $invoice->total_gst > 0): ?>
                <th>HSN</th>
                <?php endif; ?>
                <th class="text-center">Qty</th><th class="text-right">MRP</th><th class="text-right">Rate</th><th class="text-right">Discount</th>
                <?php if (!empty($company['enable_gst']) || $invoice->total_gst > 0): ?>
                <th class="text-right">Taxable</th><th class="text-center">GST%</th>
                <?php if($invoice->is_interstate): ?><th class="text-right">IGST</th><?php else: ?><th class="text-right">CGST</th><th class="text-right">SGST</th><?php endif; ?>
                <?php endif; ?>
                <th class="text-right">Total</th></tr>
            </thead>
            <tbody>
            <?php
            $items_total = array_sum(array_map(fn($it) => (float)$it->total, $items));
            $cd_ratio = ($invoice->cd_amount > 0 && $items_total > 0) ? ($invoice->total_amount / $items_total) : 1;
            ?>
            <?php foreach($items as $i=>$item):
                $disp_taxable = round($item->taxable_amt * $cd_ratio, 2);
                $disp_gst     = round($item->gst_amount * $cd_ratio, 2);
                $disp_total   = round($item->total * $cd_ratio, 2);
                $half = round($disp_gst / 2, 2);
            ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="font-weight-bold"><?= htmlspecialchars($item->product_name) ?></div>
                    <?php if(!empty($item->description)): ?><div class="text-muted" style="font-size:0.75rem;line-height:1;margin-top:1px;"><?= htmlspecialchars($item->description) ?></div><?php endif; ?>
                    <?php $this->load->view('billing/_serial_line', [
                        'item' => $item, 'sl_size' => '0.75rem',
                        'serials' => $serials ?? [], 'item_warranty' => $item_warranty ?? [],
                    ]); ?>
                </td>
                <?php if (!empty($company['enable_gst']) || $invoice->total_gst > 0): ?>
                <td><code><?= htmlspecialchars($item->hsn_code??'—') ?></code></td>
                <?php endif; ?>
                <td class="text-center"><?= $item->qty ?> <?= $item->unit ?></td>
                <td class="text-right text-muted">&#8377; <?= number_format(($item->mrp > 0) ? $item->mrp : ($item->product_mrp ?? 0), 2) ?></td>
                <td class="text-right">&#8377; <?= number_format($item->rate,2) ?></td>
                <td class="text-right">&#8377; <?= number_format($item->discount,2) ?></td>
                <?php if (!empty($company['enable_gst']) || $invoice->total_gst > 0): ?>
                <td class="text-right">&#8377; <?= number_format($disp_taxable,2) ?></td>
                <td class="text-center"><?= $item->gst_percent ?>%</td>
                <?php if($invoice->is_interstate): ?>
                <td class="text-right">&#8377; <?= number_format($disp_gst,2) ?></td>
                <?php else: ?>
                <td class="text-right">&#8377; <?= number_format($half,2) ?></td>
                <td class="text-right">&#8377; <?= number_format($disp_gst-$half,2) ?></td>
                <?php endif; ?>
                <?php endif; ?>
                <td class="text-right font-weight-bold">&#8377; <?= number_format($disp_total,2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<!-- Scheme Applied -->
<?php if (!empty($schemes_applied)): ?>
<div class="mt-2" style="font-size:.75rem;color:#6c757d;">
    <?php foreach ($schemes_applied as $sa): ?>
    <span class="badge badge-light border mr-1">
        <i class="fas fa-tag text-success"></i> <?= htmlspecialchars($sa->scheme_name) ?><?= $sa->discount_given > 0 ? ' (-₹'.number_format($sa->discount_given,2).')' : ' (free item)' ?>
    </span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Payment Splits (multi-payment) -->
<?php if (!empty($invoice->multi_payment)): ?>
<?php $splits = $this->db->where('invoice_id', $invoice->id)->get('invoice_payment_splits')->result(); ?>
<?php if (!empty($splits)): ?>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;"><i class="fas fa-money-bill-wave mr-1 text-success"></i> Payment Breakdown (Split)</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:.84rem;">
            <thead class="thead-light"><tr><th>Mode</th><th>Amount</th><th>Details</th></tr></thead>
            <tbody>
            <?php foreach ($splits as $sp): ?>
            <tr>
                <td><span class="badge badge-<?= $sp->payment_mode === 'cash' ? 'success' : ($sp->payment_mode === 'card' ? 'primary' : ($sp->payment_mode === 'credit' ? 'danger' : 'info')) ?>">
                    <?= strtoupper($sp->payment_mode) ?>
                    <?php if ($sp->payment_mode === 'card' && $sp->card_type): ?> — <?= ucfirst($sp->card_type) ?><?php endif; ?>
                </span></td>
                <td class="font-weight-bold">&#8377; <?= number_format($sp->amount, 2) ?></td>
                <td class="text-muted small">
                    <?php if ($sp->card_last4): ?>**** <?= $sp->card_last4 ?><?php endif; ?>
                    <?php if ($sp->bank_name): ?> | <?= htmlspecialchars($sp->bank_name) ?><?php endif; ?>
                    <?php if ($sp->card_ref): ?> | Ref: <?= htmlspecialchars($sp->card_ref) ?><?php endif; ?>
                    <?php if ($sp->reference_no): ?> | Ref: <?= htmlspecialchars($sp->reference_no) ?><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Credit Due Date -->
<?php if (!empty($invoice->credit_due_date)): ?>
<div class="alert alert-warning mt-3 py-2" style="font-size:.84rem;">
    <i class="fas fa-calendar-alt mr-1"></i>
    <strong>Credit Due Date:</strong> <?= date('d M Y', strtotime($invoice->credit_due_date)) ?>
    <?php $days_left = (strtotime($invoice->credit_due_date) - time()) / 86400; ?>
    <?php if ($days_left < 0): ?>
    <span class="badge badge-danger ml-1">Overdue by <?= abs((int)$days_left) ?> days</span>
    <?php elseif ($days_left <= 7): ?>
    <span class="badge badge-warning ml-1">Due in <?= (int)$days_left ?> days</span>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Refund History -->
<?php
$refunds_on_inv = $this->db->where('invoice_id', $invoice->id)->order_by('id','DESC')->get('refunds')->result();
if (!empty($refunds_on_inv)):
?>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;"><i class="fas fa-undo mr-1 text-warning"></i> Refund History</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:.84rem;">
            <thead class="thead-light"><tr><th>Refund #</th><th>Type</th><th>Amount</th><th>Mode</th><th>Reason</th><th>By</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($refunds_on_inv as $ref): ?>
            <tr>
                <td><code><?= $ref->refund_no ?></code></td>
                <td><?= ucfirst($ref->refund_type) ?></td>
                <td class="font-weight-bold text-danger">&#8377; <?= number_format($ref->refund_amount, 2) ?></td>
                <td><?= strtoupper($ref->refund_mode) ?></td>
                <td><?= htmlspecialchars($ref->reason) ?></td>
                <td><?= htmlspecialchars($ref->created_by_name) ?></td>
                <td><?= date('d M Y H:i', strtotime($ref->created_at)) ?></td>
                <td><a href="<?= base_url('refunds/view/'.$ref->id) ?>" class="btn btn-xs btn-outline-secondary py-0 px-1" style="font-size:.75rem;">View</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Customer Portal QR Code -->
<?php
$cust_phone = preg_replace('/\D/', '', $invoice->customer_phone ?? '');
if ($cust_phone):
    $portal_url = base_url('portal/login') . '?phone=' . urlencode($cust_phone);
?>
<div class="card border-0 shadow-sm mt-3 mb-3">
    <div class="card-body py-2 d-flex align-items-center" style="font-size:.83rem;">
        <div id="invoice-qr-code" class="mr-3"></div>
        <div>
            <p class="mb-0 font-weight-bold">Customer Portal QR Code</p>
            <p class="mb-0 text-muted">Customer scans to login &amp; view this invoice.</p>
            <p class="mb-0 text-muted">Login: <code><?= htmlspecialchars($cust_phone) ?></code> / Password: <code><?= substr($cust_phone, -4) ?></code></p>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
<script>
new QRCode(document.getElementById("invoice-qr-code"), {
    text: "<?= addslashes($portal_url) ?>",
    width: 80,
    height: 80,
    colorDark : "#000000",
    colorLight : "#ffffff"
});
</script>
<?php endif; ?>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header py-2">
            <h6 class="modal-title font-weight-bold mb-0"><i class="fas fa-paper-plane mr-1"></i> Email Invoice</h6>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <?= form_open('billing/email_invoice/'.$invoice->id) ?>
        <div class="modal-body">
            <div class="form-group mb-2">
                <label style="font-size:.85rem;">Recipient Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($invoice->customer_email??'') ?>" placeholder="customer@email.com" required>
            </div>
            <div class="alert alert-success py-2 mb-0" style="font-size:.82rem;">
                <i class="fas fa-paperclip mr-1"></i>
                <strong>Invoice PDF will be attached automatically</strong> to this email.<br>
                <span class="text-muted">Invoice No: <?= $invoice->invoice_no ?> &middot; Amount: ₹<?= number_format($invoice->total_amount,2) ?></span>
            </div>
        </div>
        <div class="modal-footer py-2">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane mr-1"></i> Send with PDF</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
        </div>
        <?= form_close() ?>
    </div></div>
</div>

<script>
function copyPublicLink(link) {
    navigator.clipboard.writeText(link).then(function() {
        alert('Public Invoice Link copied to clipboard!');
    }, function(err) {
        const el = document.createElement('textarea');
        el.value = link;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        alert('Public Invoice Link copied to clipboard!');
    });
}
</script>

<!-- Change Payment Mode Modal -->
<div class="modal fade" id="pmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="pmForm" method="post" action="">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Change Payment Mode - <span id="pmInvoiceNo"></span></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Only the payment mode changes. Items, totals, and amounts stay exactly the same.</p>
                    <div class="form-group mb-2">
                        <label class="font-weight-bold small">Payment Mode</label>
                        <select name="payment_mode" id="pmSelect" class="form-control form-control-sm">
                            <?php if (!empty($payment_modes)): foreach ($payment_modes as $pm): ?>
                            <option value="<?= $pm->code ?>"><?= htmlspecialchars($pm->name) ?></option>
                            <?php endforeach; else: ?>
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="card">Card</option>
                            <option value="neft">Bank / NEFT</option>
                            <option value="cheque">Cheque</option>
                            <option value="credit">Credit</option>
                            <?php endif; ?>
                            <option value="split">Split (Multiple)</option>
                        </select>
                    </div>
                    
                    <div id="pmSplitDiv" style="display:none;" class="mb-2 p-2 border rounded bg-light">
                        <label class="font-weight-bold small mb-1">Enter Split Amounts</label>
                        <?php
                        $split_modes = !empty($payment_modes) ? array_filter($payment_modes, function($m) { return $m->show_in_split && !$m->is_credit; }) : [];
                        for ($si = 0; $si < min(3, max(2, count($split_modes))); $si++):
                        ?>
                        <div class="row mb-1">
                            <div class="col-6">
                                <select name="split_mode[]" class="form-control form-control-sm">
                                    <?php $sj = 0; foreach ($split_modes as $sm): ?>
                                    <option value="<?= $sm->code ?>"<?= $sj === $si ? ' selected' : '' ?>><?= htmlspecialchars($sm->name) ?></option>
                                    <?php $sj++; endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6"><input type="number" step="0.01" name="split_amount[]" class="form-control form-control-sm" placeholder="Amount"></div>
                        </div>
                        <?php endfor; ?>
                        <small class="text-muted d-block mt-1">Leave amount blank for unused modes. Total must match bill amount.</small>
                    </div>
                    <div class="form-group mb-2">
                        <label class="font-weight-bold small">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control form-control-sm" rows="2" placeholder="Mandatory reason for changing payment mode" required></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Remarks (Optional)</label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="1" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openPmModal(id, invoice_no, current_mode) {
    $('#pmForm').attr('action', '<?= base_url("billing/update_payment_mode/") ?>' + id);
    $('#pmInvoiceNo').text(invoice_no);
    $('#pmSelect').val(current_mode);
    togglePmSplit();
    $('#pmModal').modal('show');
}

function togglePmSplit() {
    if ($('#pmSelect').val() === 'split') {
        $('#pmSplitDiv').slideDown('fast');
    } else {
        $('#pmSplitDiv').slideUp('fast');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    $('#pmSelect').on('change', togglePmSplit);

    <?php if ($this->session->flashdata('prompt_silent_print')): ?>
    setTimeout(function() {
        if (confirm("Do you want to print this bill?")) {
            // Create a hidden iframe for Kiosk Printing
            var printIframe = document.createElement('iframe');
            printIframe.style.display = 'none';
            printIframe.src = '<?= base_url("billing/print_invoice/" . $invoice->id) ?>';
            document.body.appendChild(printIframe);

            printIframe.onload = function() {
                setTimeout(function() {
                    printIframe.contentWindow.focus();
                    printIframe.contentWindow.print();
                    
                    // Note: In Chrome Kiosk Mode (--kiosk-printing), this prints silently to the default OS printer.
                    // Remove the iframe after some time to avoid DOM clutter
                    setTimeout(function() {
                        document.body.removeChild(printIframe);
                    }, 5000);
                }, 500);
            };
        }
    }, 500);
    <?php endif; ?>
});
</script>
