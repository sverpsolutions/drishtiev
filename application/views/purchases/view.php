<?php // FILE: application/views/purchases/view.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">
            <?= $purchase->purchase_no ?>
            <?php
            $badges = ['received'=>'success','partial'=>'warning','pending'=>'danger','returned'=>'secondary','draft'=>'secondary'];
            $b = $badges[$purchase->status] ?? 'secondary';
            ?>
            <span class="badge badge-<?= $b ?> ml-2" style="font-size: 0.6em; vertical-align: middle;"><?= ucfirst($purchase->status) ?></span>
        </h5>
        <small class="text-muted"><a href="<?= base_url('purchases') ?>">Purchases</a> / View</small>
    </div>
    <div>
        <?php if ($purchase->status === 'draft'): ?>
        <a href="<?= base_url('purchases/edit/'.$purchase->id) ?>" class="btn btn-outline-primary btn-sm">Edit</a>
        <?php endif; ?>
        <?php if ($purchase->status !== 'draft' && ($this->session->userdata('username') === 'sverpadmin' || $this->session->userdata('role') === 'superadmin' || $this->session->userdata('role') === 'admin')): ?>
        <a href="<?= base_url('purchases/revert_to_draft/'.$purchase->id) ?>" class="btn btn-outline-danger btn-sm ml-1" onclick="return confirm('ADMIN ACTION:\nAre you sure you want to Reopen this GRN to edit it?\n\nThis will temporarily subtract the stock quantities so you can make your changes and save again.')">Reopen to Edit (Admin)</a>
        <?php endif; ?>
        <a href="<?= base_url('purchases/return_create/'.$purchase->id) ?>" class="btn btn-outline-warning btn-sm ml-1">Create PRN</a>
        <a href="<?= base_url('purchases/print_po/'.$purchase->id) ?>" class="btn btn-outline-info btn-sm ml-1" target="_blank">Print</a>
        <a href="<?= base_url('purchases') ?>" class="btn btn-outline-secondary btn-sm ml-1">&larr; Back</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body" style="font-size:.87rem;">
                <p class="mb-1"><strong>Supplier:</strong> <?= htmlspecialchars($purchase->supplier_name) ?></p>
                <p class="mb-1"><strong>GSTIN:</strong> <code><?= htmlspecialchars($purchase->supplier_gstin ?? '—') ?></code></p>
                <p class="mb-1"><strong>Supplier Invoice:</strong> <?= htmlspecialchars($purchase->invoice_no ?? '—') ?></p>
                <p class="mb-1"><strong>Invoice Date:</strong> <?= date('d M Y', strtotime($purchase->invoice_date)) ?></p>
                <p class="mb-0"><strong>Recorded by:</strong> <?= htmlspecialchars($purchase->created_by_name) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body" style="font-size:.87rem;">
                <p class="mb-1"><strong>Subtotal:</strong> ₹ <?= number_format($purchase->subtotal, 2) ?></p>
                <p class="mb-1"><strong>Total GST:</strong> ₹ <?= number_format($purchase->total_gst, 2) ?></p>
                <p class="mb-1"><strong>Discount:</strong> ₹ <?= number_format($purchase->discount, 2) ?></p>
                <p class="mb-1 font-weight-bold"><strong>Grand Total:</strong> ₹ <?= number_format($purchase->total_amount, 2) ?></p>
                <p class="mb-1 text-success"><strong>Paid:</strong> ₹ <?= number_format($purchase->paid_amount, 2) ?></p>
                <p class="mb-0 text-danger font-weight-bold"><strong>Due:</strong> ₹ <?= number_format($purchase->due_amount, 2) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Items Received</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>HSN</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Rate</th>
                        <th class="text-center">GST %</th>
                        <th class="text-right">GST Amt</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <div class="font-weight-bold text-dark"><?= htmlspecialchars($item->product_name) ?></div>
                            <?php if (!empty($item->size_name) || !empty($item->colour_name) || !empty($item->variant_sku)): ?>
                                <div class="mt-1 d-flex align-items-center flex-wrap" style="gap:4px;">
                                    <?php if (!empty($item->colour_name)): ?>
                                        <span class="badge badge-secondary px-2 py-1" style="font-size:11px; font-weight:600; background:#475569; color:#fff;">
                                            <?php if (!empty($item->colour_hex)): ?>
                                                <span class="d-inline-block rounded-circle mr-1 border border-light" style="width:8px; height:8px; background:<?= htmlspecialchars($item->colour_hex) ?>; vertical-align:middle;"></span>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($item->colour_name) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($item->size_name)): ?>
                                        <span class="badge badge-primary px-2 py-1" style="font-size:11px; font-weight:600;">
                                            Size: <?= htmlspecialchars($item->size_name) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($item->variant_sku)): ?>
                                        <span class="badge badge-light border text-muted px-2 py-1" style="font-size:10px;">
                                            SKU: <?= htmlspecialchars($item->variant_sku) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($item->barcode)): ?>
                                <div class="small text-muted mt-1 font-monospace" style="font-size:11px;">
                                    <i class="fas fa-barcode mr-1"></i><?= htmlspecialchars($item->barcode) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><code><?= htmlspecialchars($item->hsn_code ?? '—') ?></code></td>
                        <td class="text-center font-weight-bold"><?= $item->qty ?> <?= $item->unit ?></td>
                        <td class="text-right">₹ <?= number_format($item->price, 2) ?></td>
                        <td class="text-center"><?= $item->gst_percent ?>%</td>
                        <td class="text-right">₹ <?= number_format($item->gst_amount, 2) ?></td>
                        <td class="text-right font-weight-bold">₹ <?= number_format($item->total, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($purchase->status !== 'draft'): ?>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Convert to Sales Invoice</div>
    <div class="card-body">
        <?php if (!empty($purchase->converted_invoice_id)): ?>
            <div class="alert alert-info py-2 mb-0" style="font-size:.85rem;">
                Already converted to
                <a href="<?= base_url('billing/edit/' . (int)$purchase->converted_invoice_id) ?>"><strong>Invoice #<?= (int)$purchase->converted_invoice_id ?></strong></a>.
            </div>
        <?php else: ?>
            <?= form_open('purchases/convert_to_invoice/' . $purchase->id) ?>
            <div class="form-row align-items-end">
                <div class="col-md-3">
                    <label style="font-weight:600;font-size:.85rem;">Markup % on Basic Cost</label>
                    <input type="number" name="markup_percent" class="form-control form-control-sm" step="0.01" min="0" required placeholder="e.g. 2 or 3">
                </div>
                <div class="col-md-5">
                    <small class="text-muted">Bills every item in this purchase to a customer of your choice, at basic cost + this markup, GST added on top. You'll pick the customer and review before it's saved as an invoice.</small>
                </div>
                <div class="col-md-4 text-right">
                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Convert this purchase to a sales invoice? You will review and pick the customer on the next screen before it is saved.')">
                        <i class="fas fa-exchange-alt mr-1"></i>Convert to Sales Invoice
                    </button>
                </div>
            </div>
            <?= form_close() ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
