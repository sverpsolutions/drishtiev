<?php
// ============================================================
// FILE: application/views/reports/sales.php
// ============================================================
?>
<?php $view = $view ?? 'summary'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Sales Report<?= $view==='details' ? ' — Item Details' : '' ?></h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Sales</small>
        <?php
        $badges = $filter_summary ?? [];
        if (!empty($payment_mode) && $view === 'summary') $badges[] = 'Payment: '.ucfirst($payment_mode);
        if (!empty($badges)): ?>
        <div class="mt-1"><span class="badge badge-dark" style="font-size:.75rem;"><?= htmlspecialchars(implode(' | ', $badges)) ?></span></div>
        <?php endif; ?>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/sales','export_params'=>$export_params??'','export_title'=>$export_title??'Sales Report']); ?>
    </div>
</div>

<!-- Summary / Details toggle -->
<?php $pm = $payment_mode ?? ''; ?>
<div class="btn-group mb-3 no-print" role="group">
    <a href="<?= base_url('reports/sales?from='.$from.'&to='.$to.'&location_id='.($location_id??'').'&payment_mode='.$pm.'&view=summary') ?>"
       class="btn btn-sm <?= $view==='summary' ? 'btn-dark' : 'btn-outline-dark' ?>">Summary</a>
    <a href="<?= base_url('reports/sales?from='.$from.'&to='.$to.'&location_id='.($location_id??'').'&payment_mode='.$pm.'&view=details') ?>"
       class="btn btn-sm <?= $view==='details' ? 'btn-dark' : 'btn-outline-dark' ?>">Details</a>
</div>

<!-- Date filter -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="form-inline flex-wrap">
            <input type="hidden" name="view" value="<?= $view ?>">
            <label class="mr-2" style="font-size:.85rem;">From</label>
            <input type="date" name="from" class="form-control form-control-sm mr-2" value="<?= $from ?>">
            <label class="mr-2" style="font-size:.85rem;">To</label>
            <input type="date" name="to" class="form-control form-control-sm mr-2" value="<?= $to ?>">
            <select name="location_id" class="form-control form-control-sm mr-2" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
                <?php if (count($locations ?? []) > 1): ?>
                <option value="">All Outlets</option>
                <?php endif; ?>
                <?php foreach ($locations ?? [] as $loc): ?>
                <option value="<?= $loc->id ?>" <?= ($location_id ?? 0) == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                <?php endforeach; ?>
            </select>
            <?php $pay_modes = [''=>'All Payment Modes','cash'=>'Cash','upi'=>'UPI','card'=>'Card','neft'=>'NEFT','cheque'=>'Cheque','wallet'=>'Wallet','credit'=>'Credit','split'=>'Split']; ?>
            <select name="payment_mode" class="form-control form-control-sm mr-2" style="min-width:140px; max-width:180px; font-size:.8rem;">
                <?php foreach ($pay_modes as $k=>$v): ?>
                <option value="<?= $k ?>" <?= ($payment_mode ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-dark btn-sm">Apply</button>
        </form>
    </div>
</div>

<?php if (!empty($location_id)): ?>
<div class="alert alert-info py-2 mb-3 no-print" style="font-size:.85rem;">
    <i class="fas fa-store mr-1"></i> Showing data for outlet: <strong><?= htmlspecialchars(array_reduce((array)($locations??[]), fn($c,$l) => $l->id==$location_id ? $l->name : $c, 'Unknown')) ?></strong>
</div>
<?php endif; ?>

<?php if ($view === 'details'): ?>
    <?php include APPPATH . 'views/reports/_sales_item_details.php'; ?>
<?php else: ?>

<!-- Summary -->
<?php if ($summary): ?>
<div class="row mb-3">
    <?php $cards = [
        ['label'=>'Total Invoices',    'val'=>$summary->total_invoices,             'fmt'=>false, 'cls'=>''],
        ['label'=>'Gross Sales',       'val'=>$summary->gross_sales,                'fmt'=>true,  'cls'=>''],
        ['label'=>'Total Discount',    'val'=>$summary->total_discount,             'fmt'=>true,  'cls'=>'text-warning'],
        ['label'=>'Taxable Amount',    'val'=>$summary->taxable_amount,             'fmt'=>true,  'cls'=>''],
        ['label'=>'Total GST',         'val'=>$summary->total_gst,                  'fmt'=>true,  'cls'=>'text-info'],
        ['label'=>'Refunds',           'val'=>$summary->total_refunds ?? 0,         'fmt'=>true,  'cls'=>'text-danger'],
        ['label'=>'Net Sales',         'val'=>$summary->net_sales,                  'fmt'=>true,  'cls'=>'text-success font-weight-bold'],
        ['label'=>'Collected',         'val'=>$summary->total_collected,            'fmt'=>true,  'cls'=>'text-success'],
        ['label'=>'Outstanding',       'val'=>$summary->total_outstanding,          'fmt'=>true,  'cls'=>'text-danger'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <p class="text-muted mb-0" style="font-size:.75rem;"><?= $c['label'] ?></p>
                <p class="mb-0 <?= $c['cls'] ?>" style="font-size:1rem;font-weight:500;">
                    <?= $c['fmt'] ? '₹ '.number_format($c['val'],2) : $c['val'] ?>
                </p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Details table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.83rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th class="text-right">Taxable</th>
                        <th class="text-right">CGST</th>
                        <th class="text-right">SGST</th>
                        <th class="text-right">IGST</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $gt_taxable=0; $gt_cgst=0; $gt_sgst=0; $gt_igst=0; $gt_total=0; $gt_paid=0; $gt_due=0;
                if (!empty($invoices)): foreach ($invoices as $inv): $is_refund = !empty($inv->is_refund);
                    $gt_taxable += (float)$inv->taxable_amount;
                    $gt_cgst    += (float)$inv->cgst_amount;
                    $gt_sgst    += (float)$inv->sgst_amount;
                    $gt_igst    += (float)$inv->igst_amount;
                    $gt_total   += (float)$inv->total_amount;
                    if (!$is_refund) { $gt_paid += (float)$inv->paid_amount; $gt_due += (float)$inv->due_amount; }
                ?>
                    <tr class="<?= $is_refund ? 'table-danger' : '' ?>">
                        <?php if ($is_refund): ?>
                        <td class="text-danger">
                            <a href="<?= base_url('refunds/view/'.$inv->refund_id) ?>" class="text-danger"><i class="fas fa-undo mr-1"></i><?= $inv->invoice_no ?></a>
                            <div class="text-muted" style="font-size:.72rem;">Refund of <?= htmlspecialchars($inv->orig_invoice_no) ?></div>
                        </td>
                        <td class="text-danger"><?= htmlspecialchars($inv->customer_name) ?></td>
                        <td class="text-danger"><?= htmlspecialchars($inv->location_name ?? '') ?></td>
                        <td class="text-danger">Refund</td>
                        <td class="text-danger"><?= date('d M Y', strtotime($inv->invoice_date)) ?></td>
                        <td class="text-right text-danger">₹ <?= number_format($inv->taxable_amount,2) ?></td>
                        <td class="text-right text-danger">₹ <?= number_format($inv->cgst_amount,2) ?></td>
                        <td class="text-right text-danger">₹ <?= number_format($inv->sgst_amount,2) ?></td>
                        <td class="text-right text-danger">₹ <?= number_format($inv->igst_amount,2) ?></td>
                        <td class="text-right font-weight-bold text-danger">₹ <?= number_format($inv->total_amount,2) ?></td>
                        <td class="text-right text-danger">—</td>
                        <td class="text-right text-danger">—</td>
                        <td><span class="badge badge-danger">Refund</span></td>
                        <?php else: ?>
                        <td><a href="<?= base_url('billing/invoice/'.$inv->id) ?>"><?= $inv->invoice_no ?></a></td>
                        <td><?= htmlspecialchars($inv->customer_name) ?></td>
                        <td><?= htmlspecialchars($inv->location_name ?? '') ?></td>
                        <td><?= ucfirst($inv->customer_type) ?></td>
                        <td><?= date('d M Y', strtotime($inv->invoice_date)) ?></td>
                        <td class="text-right">₹ <?= number_format($inv->taxable_amount,2) ?></td>
                        <td class="text-right">₹ <?= number_format($inv->cgst_amount,2) ?></td>
                        <td class="text-right">₹ <?= number_format($inv->sgst_amount,2) ?></td>
                        <td class="text-right">₹ <?= number_format($inv->igst_amount,2) ?></td>
                        <td class="text-right font-weight-bold">₹ <?= number_format($inv->total_amount,2) ?></td>
                        <td class="text-right text-success">₹ <?= number_format($inv->paid_amount,2) ?></td>
                        <td class="text-right <?= $inv->due_amount > 0 ? 'text-danger':'' ?>">₹ <?= number_format($inv->due_amount,2) ?></td>
                        <td><span class="badge badge-<?= ['paid'=>'success','partial'=>'warning','unpaid'=>'danger','cancelled'=>'secondary'][$inv->status]??'secondary' ?>"><?= ucfirst($inv->status) ?></span></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="13" class="text-center text-muted py-4">No sales in this period.</td></tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($invoices)): ?>
                <tfoot>
                    <tr class="bg-dark text-white" style="font-size:.85rem;font-weight:600;">
                        <td colspan="5" class="text-right">Grand Total</td>
                        <td class="text-right">₹ <?= number_format($gt_taxable,2) ?></td>
                        <td class="text-right">₹ <?= number_format($gt_cgst,2) ?></td>
                        <td class="text-right">₹ <?= number_format($gt_sgst,2) ?></td>
                        <td class="text-right">₹ <?= number_format($gt_igst,2) ?></td>
                        <td class="text-right">₹ <?= number_format($gt_total,2) ?></td>
                        <td class="text-right">₹ <?= number_format($gt_paid,2) ?></td>
                        <td class="text-right">₹ <?= number_format($gt_due,2) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<style>@media print { .no-print { display:none; } }</style>
