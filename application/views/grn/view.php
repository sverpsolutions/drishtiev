<?php
$is_admin = $this->session->userdata('role') === 'admin';
$flash_s  = $this->session->flashdata('success');
$flash_e  = $this->session->flashdata('error');

$statusColors = ['accepted'=>'success','pending'=>'warning','qc_pending'=>'info','partial'=>'primary','rejected'=>'danger'];
$sc = $statusColors[$grn->status] ?? 'secondary';

// Compute totals from items
$subTotal = 0; $gstSum = 0;
foreach ($items as $it) {
    $taxable  = $it->accepted_qty > 0 ? $it->accepted_qty * $it->rate : $it->received_qty * $it->rate;
    $subTotal += $taxable;
    $gstSum   += $taxable * ($it->gst_pct ?? 0) / 100;
}
$grandTotal = $grn->net_total ?? ($subTotal + $gstSum);
?>
<style>
.grn-view { font-size:.82rem; }
.grn-view-hdr { background:#1e3a8a; color:#fff; padding:8px 14px; border-radius:4px 4px 0 0; font-weight:700; font-size:.85rem; display:flex; align-items:center; justify-content:space-between; }
.grn-view-card { border:1px solid #c7d4ea; border-radius:4px; margin-bottom:12px; }
.grn-view-body { padding:12px 14px; }
.info-table td { padding:3px 8px; }
.info-table td:first-child { color:#6b7280; font-weight:600; width:130px; }
.info-table td:last-child { font-weight:500; }
#viewItemsTable { font-size:.76rem; min-width:900px; border-collapse:collapse; width:100%; }
#viewItemsTable thead tr { background:#1e3a8a; color:#fff; }
#viewItemsTable thead th { padding:5px 8px; border:1px solid #2d4faa; white-space:nowrap; }
#viewItemsTable tbody td { padding:4px 8px; border:1px solid #e2e8f0; vertical-align:middle; }
#viewItemsTable tbody tr:hover { background:#eff6ff; }
.badge-status { font-size:.75rem; padding:3px 8px; border-radius:10px; }
.tot-summary td { padding:3px 8px; }
.tot-summary td:first-child { color:#6b7280; }
.tot-summary td:last-child { text-align:right; font-weight:700; }
.grand-box { background:#1e3a8a; color:#fff; font-size:1rem; font-weight:700; padding:8px 14px; border-radius:4px; text-align:right; }
</style>

<div class="grn-view">

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-2">
    <div>
        <span style="font-size:.95rem;font-weight:700;color:#1e3a8a;">
            <i class="fas fa-truck-loading mr-1"></i><?= htmlspecialchars($grn->grn_number) ?>
        </span>
        <span class="badge badge-<?= $sc ?> ml-2 badge-status"><?= ucfirst(str_replace('_',' ',$grn->status)) ?></span>
    </div>
    <div>
        <a href="<?= base_url('grn/edit/'.$grn->id) ?>" class="btn btn-sm btn-outline-warning" style="font-size:.78rem;">
            <i class="fas fa-edit mr-1"></i>Edit / Modify
        </a>
        <a href="<?= base_url('grn/print_grn/'.$grn->id) ?>" target="_blank" class="btn btn-sm btn-outline-primary ml-1" style="font-size:.78rem;">
            <i class="fas fa-print mr-1"></i>Print GRN
        </a>
        <a href="<?= base_url('grn') ?>" class="btn btn-sm btn-outline-secondary ml-1" style="font-size:.78rem;">← Back</a>
    </div>
</div>

<?php if ($flash_s): ?><div class="alert alert-success alert-dismissible fade show py-1" style="font-size:.8rem;"><?= $flash_s ?><button class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
<?php if ($flash_e): ?><div class="alert alert-danger  alert-dismissible fade show py-1" style="font-size:.8rem;"><?= $flash_e ?><button class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

<!-- Header info cards -->
<div class="row">
    <div class="col-md-6">
        <div class="grn-view-card">
            <div class="grn-view-hdr"><span>GRN Details</span></div>
            <div class="grn-view-body">
                <table class="info-table w-100">
                    <tr><td>GRN Number</td><td><?= htmlspecialchars($grn->grn_number) ?></td></tr>
                    <tr><td>PO Number</td><td><?= htmlspecialchars($grn->po_number ?? '-') ?></td></tr>
                    <tr><td>Received Date</td><td><?= date('d M Y', strtotime($grn->received_date)) ?></td></tr>
                    <tr><td>Invoice No</td><td><?= htmlspecialchars($grn->supplier_invoice_no ?? '-') ?></td></tr>
                    <tr><td>Invoice Date</td><td><?= $grn->supplier_invoice_date ? date('d M Y', strtotime($grn->supplier_invoice_date)) : '-' ?></td></tr>
                    <tr><td>E-Way Bill</td><td><?= htmlspecialchars($grn->eway_bill ?? '-') ?></td></tr>
                    <tr><td>LR Number</td><td><?= htmlspecialchars($grn->lr_number ?? '-') ?></td></tr>
                    <tr><td>Vehicle / Driver</td><td><?= htmlspecialchars(($grn->vehicle_no ?? '-') . ' / ' . ($grn->driver_name ?? '-')) ?></td></tr>
                    <tr><td>Received By</td><td><?= htmlspecialchars($grn->received_by_name ?? '-') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="grn-view-card">
            <div class="grn-view-hdr"><span>Supplier &amp; Amounts</span></div>
            <div class="grn-view-body">
                <table class="info-table w-100">
                    <tr><td>Supplier</td><td><strong><?= htmlspecialchars($grn->supplier_name) ?></strong></td></tr>
                    <tr><td>Sub Total</td><td>₹<?= number_format($subTotal, 2) ?></td></tr>
                    <tr><td>GST Amount</td><td>₹<?= number_format($gstSum, 2) ?></td></tr>
                    <tr><td>Grand Total</td><td class="text-success" style="font-size:.9rem;">₹<?= number_format($grandTotal, 2) ?></td></tr>
                    <tr><td>Status</td><td><span class="badge badge-<?= $sc ?>"><?= ucfirst(str_replace('_',' ',$grn->status)) ?></span></td></tr>
                    <?php if ($grn->qc_notes): ?>
                    <tr><td>QC Notes</td><td><?= htmlspecialchars($grn->qc_notes) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Items table -->
<div class="grn-view-card">
    <div class="grn-view-hdr"><span>Received Items</span>
        <span style="font-size:.75rem;font-weight:400;"><?= count($items) ?> item(s)</span>
    </div>
    <div style="overflow-x:auto;">
        <table id="viewItemsTable">
            <thead>
                <tr>
                    <th>#</th><th>Code</th><th>Item Name</th><th>Ordered</th>
                    <th>Rec.Qty</th><th>Acc.Qty</th><th>Rej.Qty</th>
                    <th>Rate</th><th>Disc%</th><th>Basic Amt</th>
                    <th>GST%</th><th>GST Amt</th><th>Total Amt</th>
                    <th>Batch No</th><th>Expiry</th>
                </tr>
            </thead>
            <tbody>
            <?php $i=1; $totQty=0; $totAmt=0; foreach ($items as $it): ?>
            <?php
                $baseAmt = $it->accepted_qty > 0 ? $it->accepted_qty * $it->rate : $it->received_qty * $it->rate;
                $gstAmt  = $baseAmt * ($it->gst_pct ?? 0) / 100;
                $totItem = $baseAmt + $gstAmt;
                $totQty += $it->received_qty;
                $totAmt += $totItem;
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($it->item_code ?? '') ?></td>
                <td><strong><?= htmlspecialchars($it->product_name) ?></strong>
                    <?php if ($it->hsn_code): ?><br><small class="text-muted">HSN: <?= $it->hsn_code ?></small><?php endif; ?>
                </td>
                <td><?= number_format($it->ordered_qty ?? 0, 2) ?></td>
                <td><?= number_format($it->received_qty, 2) ?></td>
                <td class="text-success font-weight-bold"><?= number_format($it->accepted_qty, 2) ?></td>
                <td class="<?= $it->rejected_qty > 0 ? 'text-danger' : '' ?>"><?= number_format($it->rejected_qty, 2) ?></td>
                <td>₹<?= number_format($it->rate, 2) ?></td>
                <td>0</td>
                <td>₹<?= number_format($baseAmt, 2) ?></td>
                <td><?= number_format($it->gst_pct ?? 0, 2) ?>%</td>
                <td>₹<?= number_format($gstAmt, 2) ?></td>
                <td class="font-weight-bold">₹<?= number_format($totItem, 2) ?></td>
                <td><?= htmlspecialchars($it->batch_no ?? '-') ?></td>
                <td><?= $it->expiry_date && $it->expiry_date !== '0000-00-00' ? date('d M Y', strtotime($it->expiry_date)) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#eff6ff;font-weight:700;">
                    <td colspan="4">TOTALS</td>
                    <td><?= number_format($totQty, 2) ?></td>
                    <td colspan="8"></td>
                    <td>₹<?= number_format($totAmt, 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Grand total box -->
<div class="row justify-content-end mb-3">
    <div class="col-md-4">
        <table class="tot-summary w-100" style="font-size:.82rem;border:1px solid #c7d4ea;border-radius:4px;overflow:hidden;">
            <tr style="background:#f8faff;"><td>Sub Total (Before GST)</td><td>₹<?= number_format($subTotal,2) ?></td></tr>
            <tr><td>GST Tax Amount</td><td>₹<?= number_format($gstSum,2) ?></td></tr>
            <tr><td>Freight</td><td>₹0.00</td></tr>
            <tr><td>Other Charges</td><td>₹0.00</td></tr>
        </table>
        <div class="grand-box mt-1">Grand Total: ₹<?= number_format($grandTotal,2) ?></div>
    </div>
</div>

<!-- QC / Accept section -->
<?php if ($grn->status === 'pending' && $is_admin): ?>
<div class="grn-view-card">
    <div class="grn-view-hdr"><span><i class="fas fa-clipboard-check mr-1"></i>Quality Check &amp; Accept</span></div>
    <div class="grn-view-body">
        <?= form_open('grn/accept/' . $grn->id) ?>
        <div class="form-group">
            <label style="font-weight:600;font-size:.82rem;">QC Notes</label>
            <textarea name="qc_notes" class="form-control" rows="2" style="font-size:.82rem;" placeholder="Quality check observations..."></textarea>
        </div>
        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Accept this GRN? Stock will be updated immediately.')">
            <i class="fas fa-check mr-1"></i>Accept GRN &amp; Update Stock
        </button>
        <?= form_close() ?>
    </div>
</div>
<?php endif; ?>

<!-- B2B pass-through: bill the whole GRN out to a customer at basic_cost + markup% -->
<?php if ($grn->status === 'accepted'): ?>
<div class="grn-view-card">
    <div class="grn-view-hdr"><span><i class="fas fa-file-invoice mr-1"></i>Convert to Sales Invoice</span></div>
    <div class="grn-view-body">
        <?php if (!empty($grn->converted_invoice_id)): ?>
            <div class="alert alert-info py-2 mb-0" style="font-size:.82rem;">
                Already converted to
                <a href="<?= base_url('billing/edit/' . (int)$grn->converted_invoice_id) ?>"><strong>Invoice #<?= (int)$grn->converted_invoice_id ?></strong></a>.
            </div>
        <?php else: ?>
            <?= form_open('grn/convert_to_invoice/' . $grn->id) ?>
            <div class="form-row align-items-end">
                <div class="col-md-3">
                    <label style="font-weight:600;font-size:.82rem;">Markup % on Basic Cost</label>
                    <input type="number" name="markup_percent" class="form-control form-control-sm" step="0.01" min="0" required placeholder="e.g. 2 or 3">
                </div>
                <div class="col-md-5">
                    <small class="text-muted">Bills every accepted item in this GRN to a customer of your choice, at basic cost + this markup, GST added on top. You'll pick the customer and review before it's saved as an invoice.</small>
                </div>
                <div class="col-md-4 text-right">
                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Convert this GRN to a sales invoice? You will review and pick the customer on the next screen before it is saved.')">
                        <i class="fas fa-exchange-alt mr-1"></i>Convert to Sales Invoice
                    </button>
                </div>
            </div>
            <?= form_close() ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</div><!-- /grn-view -->
