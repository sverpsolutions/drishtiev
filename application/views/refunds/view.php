<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-undo mr-1 text-warning"></i> Refund: <?= $refund->refund_no ?></h5>
        <small class="text-muted"><a href="<?= base_url('refunds') ?>">Refunds</a> / <?= $refund->refund_no ?></small>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark mr-1"><i class="fas fa-print mr-1"></i> Print</button>
        <a href="<?= base_url('refunds') ?>" class="btn btn-sm btn-outline-secondary">&larr; Back</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?= $this->session->flashdata('success') ?></div><?php endif; ?>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body" style="font-size:.86rem;">
            <p class="font-weight-bold mb-1" style="font-size:1rem;"><?= htmlspecialchars($refund->customer_name ?? '—') ?></p>
            <p class="mb-1">Ph: <?= htmlspecialchars($refund->customer_phone ?? '—') ?></p>
            <?php if ($refund->customer_address): ?><p class="mb-0"><?= htmlspecialchars($refund->customer_address) ?></p><?php endif; ?>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body" style="font-size:.86rem;">
            <p class="mb-1"><strong>Refund No:</strong> <code><?= $refund->refund_no ?></code></p>
            <p class="mb-1"><strong>Invoice No:</strong> <a href="<?= base_url('billing/invoice/'.$refund->invoice_id) ?>"><code><?= $refund->invoice_no ?></code></a></p>
            <p class="mb-1"><strong>Date:</strong> <?= date('d M Y H:i', strtotime($refund->created_at)) ?></p>
            <p class="mb-0"><strong>Type:</strong> <span class="badge badge-<?= $refund->refund_type === 'full' ? 'danger' : 'warning' ?>"><?= ucfirst($refund->refund_type) ?></span></p>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body" style="font-size:.86rem;">
            <p class="mb-1"><strong>Refund Amount:</strong> <span class="font-weight-bold text-danger" style="font-size:1.1rem;">&#8377; <?= number_format($refund->refund_amount, 2) ?></span></p>
            <p class="mb-1"><strong>Mode:</strong> <span class="badge badge-secondary"><?= strtoupper($refund->refund_mode) ?></span></p>
            <p class="mb-1"><strong>Status:</strong> <span class="badge badge-<?= $refund->status === 'processed' ? 'success' : 'warning' ?>"><?= strtoupper($refund->status) ?></span></p>
            <p class="mb-1"><strong>By:</strong> <?= htmlspecialchars($refund->created_by_name) ?></p>
            <p class="mb-0"><strong>Reason:</strong> <?= htmlspecialchars($refund->reason) ?></p>
        </div></div>
    </div>
</div>

<!-- Company Header (for print) -->
<div class="d-none d-print-block text-center mb-4">
    <h4 class="font-weight-bold"><?= htmlspecialchars($company['company_name'] ?? '') ?></h4>
    <p class="mb-1"><?= htmlspecialchars($company['company_address'] ?? '') ?></p>
    <p class="mb-0">Ph: <?= htmlspecialchars($company['company_phone'] ?? '') ?></p>
    <hr>
    <h5><strong>REFUND / RETURN NOTE</strong></h5>
    <p>Refund No: <strong><?= $refund->refund_no ?></strong> | Invoice: <strong><?= $refund->invoice_no ?></strong> | Date: <strong><?= date('d M Y', strtotime($refund->created_at)) ?></strong></p>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Returned Items</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:.84rem;">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Taxable</th>
                    <th class="text-right">GST %</th>
                    <th class="text-right">GST Amt</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($item->product_name) ?></td>
                <td class="text-center"><?= $item->qty ?> <?= $item->unit ?></td>
                <td class="text-right">&#8377; <?= number_format($item->rate, 2) ?></td>
                <td class="text-right">&#8377; <?= number_format($item->taxable_amt, 2) ?></td>
                <td class="text-right"><?= $item->gst_percent ?>%</td>
                <td class="text-right">&#8377; <?= number_format($item->gst_amount, 2) ?></td>
                <td class="text-right font-weight-bold">&#8377; <?= number_format($item->total, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="font-weight-bold bg-light">
                    <td colspan="7" class="text-right pr-3">Total Refund Amount:</td>
                    <td class="text-right text-danger">&#8377; <?= number_format($refund->refund_amount, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php if (!empty($refund->notes)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body py-2" style="font-size:.84rem;"><strong>Notes:</strong> <?= htmlspecialchars($refund->notes) ?></div>
</div>
<?php endif; ?>

<div class="mt-3 text-right d-print-none">
    <a href="<?= base_url('billing/invoice/'.$refund->invoice_id) ?>" class="btn btn-sm btn-outline-primary">View Original Invoice</a>
    <a href="<?= base_url('refunds/create?invoice_no='.$refund->invoice_no) ?>" class="btn btn-sm btn-outline-warning ml-1">Create Another Refund</a>
</div>
