
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-calculator text-danger mr-1"></i> GSTR-3B Summary</h5>
        <small class="text-muted">Net Tax Payable = Output Tax – Input Tax Credit</small>
    </div>
    <div>
        <a href="<?= base_url('reports/export_gst_excel?type=gstr3b&month='.$month) ?>" class="btn btn-sm btn-success mr-1">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark no-print">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<!-- Month Filter -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="form-inline">
            <label class="mr-2 font-weight-bold" style="font-size:.85rem;">Month:</label>
            <input type="month" name="month" class="form-control form-control-sm mr-2" value="<?= $month ?>">
            <button type="submit" class="btn btn-primary btn-sm mr-2">Apply</button>
            <a href="<?= base_url('reports/gstr1?month='.$month) ?>" class="btn btn-outline-secondary btn-sm mr-1">← GSTR-1</a>
            <a href="<?= base_url('reports/gstr2?month='.$month) ?>" class="btn btn-outline-secondary btn-sm">← GSTR-2</a>
        </form>
    </div>
</div>

<?php
$net_cgst  = ($output['cgst']  ?? 0) - ($input['cgst']  ?? 0) - (float)($credit_note_adj->cgst_amount ?? 0);
$net_sgst  = ($output['sgst']  ?? 0) - ($input['sgst']  ?? 0) - (float)($credit_note_adj->sgst_amount ?? 0);
$net_igst  = ($output['igst']  ?? 0) - ($input['igst']  ?? 0) - (float)($credit_note_adj->igst_amount ?? 0);
$net_total = ($output['total'] ?? 0) - ($input['total'] ?? 0) - (float)($credit_note_adj->total_amount ?? 0);
$is_payable = $net_total > 0;
?>

<h6 class="text-muted mb-3">GSTR-3B Summary for: <strong class="text-dark"><?= date('F Y', strtotime($from)) ?></strong></h6>

<div class="row">

    <!-- Left: Tax Table -->
    <div class="col-lg-8 col-md-7 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white py-2"><i class="fas fa-table mr-1"></i> Tax Summary</div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0" style="font-size:.9rem;">
                    <thead class="thead-light">
                        <tr>
                            <th>Description</th>
                            <th class="text-right">CGST (₹)</th>
                            <th class="text-right">SGST (₹)</th>
                            <th class="text-right">IGST (₹)</th>
                            <th class="text-right">Total (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-danger">
                            <td>
                                <strong>3.1 — Output Tax Liability</strong><br>
                                <small class="text-muted">Tax collected on sales (GSTR-1)</small>
                            </td>
                            <td class="text-right font-weight-bold"><?= number_format($output['cgst'] ?? 0, 2) ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($output['sgst'] ?? 0, 2) ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($output['igst'] ?? 0, 2) ?></td>
                            <td class="text-right font-weight-bold text-danger"><?= number_format($output['total'] ?? 0, 2) ?></td>
                        </tr>
                        <?php if (!empty($credit_note_adj) && (float)($credit_note_adj->total_amount ?? 0) > 0): ?>
                        <tr class="table-light">
                            <td>
                                <strong>4(B) — Credit Notes Adjustment</strong><br>
                                <small class="text-muted">Sales returns reduce output tax (Table 4B)</small>
                            </td>
                            <td class="text-right text-secondary">(<?= number_format($credit_note_adj->cgst_amount ?? 0, 2) ?>)</td>
                            <td class="text-right text-secondary">(<?= number_format($credit_note_adj->sgst_amount ?? 0, 2) ?>)</td>
                            <td class="text-right text-secondary">(<?= number_format($credit_note_adj->igst_amount ?? 0, 2) ?>)</td>
                            <td class="text-right text-secondary">(<?= number_format($credit_note_adj->total_amount ?? 0, 2) ?>)</td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-success">
                            <td>
                                <strong>4 — Input Tax Credit (ITC) Available</strong><br>
                                <small class="text-muted">Eligible tax paid on purchases (GSTR-2)</small>
                                <?php if (!empty($input['ineligible_itc']) && (float)$input['ineligible_itc'] > 0): ?>
                                    <br><span class="badge badge-warning">Ineligible ITC: ₹<?= number_format($input['ineligible_itc'], 2) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right font-weight-bold"><?= number_format($input['cgst'] ?? 0, 2) ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($input['sgst'] ?? 0, 2) ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($input['igst'] ?? 0, 2) ?></td>
                            <td class="text-right font-weight-bold text-success"><?= number_format(($input['eligible_itc'] ?? $input['total']) ?? 0, 2) ?></td>
                        </tr>
                        <tr class="<?= $is_payable ? 'table-danger' : 'table-success' ?>">
                            <td>
                                <strong>5 — Net Tax Payable</strong><br>
                                <small class="text-muted">Output – Credit Notes – Input Tax Credit</small>
                            </td>
                            <td class="text-right font-weight-bold <?= $net_cgst < 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($net_cgst, 2) ?>
                            </td>
                            <td class="text-right font-weight-bold <?= $net_sgst < 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($net_sgst, 2) ?>
                            </td>
                            <td class="text-right font-weight-bold <?= $net_igst < 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($net_igst, 2) ?>
                            </td>
                            <td class="text-right font-weight-bold h5 mb-0 <?= $is_payable ? 'text-danger' : 'text-success' ?>">
                                <?= number_format($net_total, 2) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Breakup by head -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header py-2"><i class="fas fa-list mr-1"></i> Payable Breakup by Tax Head</div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0" style="font-size:.85rem;">
                    <thead class="thead-light">
                        <tr>
                            <th>Tax Head</th>
                            <th class="text-right">Output (₹)</th>
                            <th class="text-right">ITC (₹)</th>
                            <th class="text-right">Net Payable (₹)</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ([
                            ['CGST', $output['cgst']??0, $input['cgst']??0, $net_cgst],
                            ['SGST', $output['sgst']??0, $input['sgst']??0, $net_sgst],
                            ['IGST', $output['igst']??0, $input['igst']??0, $net_igst],
                        ] as [$head, $out, $inp, $net]): ?>
                        <tr>
                            <td class="font-weight-bold"><?= $head ?></td>
                            <td class="text-right"><?= number_format($out, 2) ?></td>
                            <td class="text-right"><?= number_format($inp, 2) ?></td>
                            <td class="text-right font-weight-bold <?= $net > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= number_format(abs($net), 2) ?>
                            </td>
                            <td class="text-center">
                                <?php if ($net > 0): ?>
                                    <span class="badge badge-danger">Pay ₹<?= number_format($net, 2) ?></span>
                                <?php elseif ($net < 0): ?>
                                    <span class="badge badge-success">Surplus</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Nil</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right: Summary Cards -->
    <div class="col-lg-4 col-md-5 mb-3">

        <!-- Net Payable / Surplus Card -->
        <div class="card border-0 shadow-sm mb-3 <?= $is_payable ? 'border-danger' : 'border-success' ?>" style="border-left: 4px solid <?= $is_payable ? '#dc3545' : '#28a745' ?> !important;">
            <div class="card-header <?= $is_payable ? 'bg-danger' : 'bg-success' ?> text-white py-2">
                <strong><?= $is_payable ? '⚠ Tax Payable to Govt' : '✓ ITC Surplus (Carry Forward)' ?></strong>
            </div>
            <div class="card-body text-center py-3">
                <div class="font-weight-bold <?= $is_payable ? 'text-danger' : 'text-success' ?>" style="font-size: 2rem; line-height:1.2;">
                    ₹<?= number_format(abs($net_total), 2) ?>
                </div>
                <small class="text-muted">
                    <?= $is_payable ? 'Amount to be deposited to GST portal' : 'ITC credit to carry forward next month' ?>
                </small>
                <hr class="my-2">
                <div style="font-size:.85rem;">
                    <?php foreach ([
                        ['CGST', $net_cgst],
                        ['SGST', $net_sgst],
                        ['IGST', $net_igst],
                    ] as [$h, $n]): ?>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="text-muted"><?= $h ?> <?= $n < 0 ? 'Surplus' : 'Payable' ?>:</span>
                        <span class="font-weight-bold <?= $n < 0 ? 'text-success' : 'text-danger' ?>">
                            ₹<?= number_format(abs($n), 2) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2"><i class="fas fa-info-circle mr-1"></i> Quick Stats</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.85rem;">
                    <tr class="table-light">
                        <td>Total Output Tax</td>
                        <td class="text-right font-weight-bold text-danger">₹<?= number_format($output['total'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Total ITC Available</td>
                        <td class="text-right font-weight-bold text-success">₹<?= number_format($input['total'] ?? 0, 2) ?></td>
                    </tr>
                    <?php if ((float)($credit_note_adj->total_amount ?? 0) > 0): ?>
                    <tr>
                        <td>Credit Note Adj.</td>
                        <td class="text-right text-secondary">₹<?= number_format($credit_note_adj->total_amount ?? 0, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="<?= $is_payable ? 'table-danger' : 'table-success' ?>">
                        <td><strong>Net <?= $is_payable ? 'Payable' : 'Surplus' ?></strong></td>
                        <td class="text-right font-weight-bold <?= $is_payable ? 'text-danger' : 'text-success' ?>">
                            ₹<?= number_format(abs($net_total), 2) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Other GST Reports -->
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2"><i class="fas fa-link mr-1"></i> Other GST Reports</div>
            <div class="card-body">
                <a href="<?= base_url('reports/gstr1?month='.$month) ?>" class="btn btn-outline-primary btn-sm btn-block mb-2">
                    <i class="fas fa-file-invoice mr-1"></i> GSTR-1 (Sales)
                </a>
                <a href="<?= base_url('reports/gstr2?month='.$month) ?>" class="btn btn-outline-warning btn-sm btn-block mb-2">
                    <i class="fas fa-shopping-cart mr-1"></i> GSTR-2 (Purchases)
                </a>
                <a href="<?= base_url('reports/gst?month='.$month) ?>" class="btn btn-outline-secondary btn-sm btn-block">
                    <i class="fas fa-percent mr-1"></i> GST Summary
                </a>
            </div>
        </div>

    </div>

</div><!-- /.row -->

<style>
@media print {
    .no-print { display: none !important; }
    .card { box-shadow: none !important; }
}
</style>
