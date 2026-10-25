<?php // FILE: application/views/reports/supplier_report.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-truck text-warning mr-1"></i> Supplier Report</h5>
        <small class="text-muted">Purchases, Payments & Outstanding by Supplier — <?= $from ?> to <?= $to ?></small>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/supplier_report','export_params'=>$export_params??'','export_title'=>$export_title??'Supplier Report']); ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="form-inline">
            <input type="date" name="from" class="form-control form-control-sm mr-2" value="<?= $from ?>">
            <span class="mr-2">to</span>
            <input type="date" name="to" class="form-control form-control-sm mr-2" value="<?= $to ?>">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
        </form>
    </div>
</div>

<?php
$total_purchase  = array_sum(array_column((array)$suppliers, 'total_bills'));
$total_paid      = array_sum(array_column((array)$suppliers, 'total_paid'));
$total_outstanding = array_sum(array_column((array)$suppliers, 'outstanding'));
?>

<div class="row mb-3">
    <div class="col-md-4"><div class="card bg-dark text-white text-center p-3"><h5>₹<?= number_format($total_purchase, 0) ?></h5><small>Total Purchases</small></div></div>
    <div class="col-md-4"><div class="card bg-success text-white text-center p-3"><h5>₹<?= number_format($total_paid, 0) ?></h5><small>Total Paid</small></div></div>
    <div class="col-md-4"><div class="card bg-danger text-white text-center p-3"><h5>₹<?= number_format($total_outstanding, 0) ?></h5><small>Total Outstanding</small></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Supplier</th>
                        <th class="text-right">Bills</th>
                        <th class="text-right">Qty Purchased</th>
                        <th class="text-right">Purchase Value</th>
                        <th class="text-right">Total Bills</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Outstanding</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($suppliers as $r): ?>
                    <tr class="<?= $r->outstanding > 0 ? 'table-warning' : '' ?>">
                        <td><?= $i++ ?></td>
                        <td class="font-weight-bold"><?= htmlspecialchars($r->supplier_name) ?></td>
                        <td class="text-right"><?= $r->purchase_count ?></td>
                        <td class="text-right"><?= number_format($r->total_qty_purchased, 0) ?></td>
                        <td class="text-right">₹<?= number_format($r->total_purchase_value, 0) ?></td>
                        <td class="text-right font-weight-bold">₹<?= number_format($r->total_bills, 0) ?></td>
                        <td class="text-right text-success">₹<?= number_format($r->total_paid, 0) ?></td>
                        <td class="text-right <?= $r->outstanding > 0 ? 'text-danger font-weight-bold' : 'text-success' ?>">
                            ₹<?= number_format($r->outstanding, 0) ?>
                        </td>
                        <td>
                            <?php if ($r->supplier_id): ?>
                            <a href="<?= base_url('reports/party_ledger?party_type=supplier&party_id=' . $r->supplier_id) ?>" class="btn btn-xs btn-outline-primary">Ledger</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($suppliers)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No purchase data in this period</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($suppliers)): ?>
                <tfoot class="font-weight-bold bg-light">
                    <tr>
                        <td colspan="4">TOTAL</td>
                        <td class="text-right">₹<?= number_format($total_purchase, 0) ?></td>
                        <td class="text-right">₹<?= number_format($total_purchase, 0) ?></td>
                        <td class="text-right text-success">₹<?= number_format($total_paid, 0) ?></td>
                        <td class="text-right text-danger">₹<?= number_format($total_outstanding, 0) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
