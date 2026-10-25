<?php // Counter-wise Media Report — pivot: rows=counter, columns=payment mode ?>
<!-- Print-only company header, shown only when printing (window.print()) -->
<div class="print-header text-center" style="display:none;">
    <?php $__logo = $company['company_logo'] ?? ''; if (!empty($__logo) && file_exists('./' . $__logo)): ?>
        <img src="<?= './' . $__logo ?>" style="max-height:50px;max-width:150px;object-fit:contain;margin-bottom:6px;">
    <?php endif; ?>
    <h4 class="font-weight-bold mb-0"><?= htmlspecialchars($company['company_name'] ?? '') ?></h4>
    <div style="font-size:11px;color:#555;"><?= htmlspecialchars($company['company_address'] ?? '') ?></div>
    <div style="font-size:11px;color:#555;">GSTIN: <?= htmlspecialchars($company['company_gstin'] ?? 'N/A') ?> | Phone: <?= htmlspecialchars($company['company_phone'] ?? '') ?></div>
    <hr>
    <h5 class="font-weight-bold text-uppercase">Counter-wise Media Report</h5>
    <div style="font-size:12px;font-weight:bold;margin-bottom:10px;">Period: <?= date('d M Y', strtotime($from)) ?> to <?= date('d M Y', strtotime($to)) ?></div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h5 class="mb-0 font-weight-bold">Counter-wise Media Report</h5>
        <small class="text-muted">Payment mode summary per billing counter / cashier</small>
    </div>
    <div class="d-flex align-items-center" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/counter_media_report','export_params'=>$export_params??'','export_title'=>$export_title??'Counter-wise Media Report']); ?>
        <a href="<?= base_url('reports') ?>" class="btn btn-outline-secondary btn-sm">← Reports</a>
    </div>
</div>

<div class="card shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end g-2">
            <div class="col-md-2"><label class="small">From</label><input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>"></div>
            <div class="col-md-2"><label class="small">To</label><input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>"></div>
            <div class="col-md-2">
                <label class="small">Cashier</label>
                <select name="cashier_id" class="form-control form-control-sm">
                    <option value="">All Cashiers</option>
                    <?php foreach ($cashiers as $u): ?>
                    <option value="<?= $u->id ?>" <?= $cashier_id==$u->id?'selected':'' ?>><?= htmlspecialchars($u->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small">Payment Mode</label>
                <?php $__pm_options = ['cash'=>'Cash','upi'=>'UPI','card'=>'Card','neft'=>'NEFT/Bank','cheque'=>'Cheque','wallet'=>'Wallet','credit'=>'Credit','other'=>'Other']; ?>
                <select name="payment_mode" class="form-control form-control-sm">
                    <option value="">All Modes</option>
                    <?php foreach ($__pm_options as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= ($mode_filter ?? '')===$val?'selected':'' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1"><button class="btn btn-dark btn-sm btn-block mt-3">Filter</button></div>
        </form>
    </div>
</div>

<?php
// Build pivot: $pivot[counter_name][mode] = ['bills'=>N, 'amount'=>X]
// Also track cashier names per counter
// Note: "Split" is decomposed by the controller into its real component modes
// (cash/upi/card/etc), so it never appears here as its own column. Per-mode
// bill counts can therefore sum to more than the counter's actual bill count
// when a single split bill touches 2+ modes — that's expected, not a bug.
$pivot        = [];
$pivot_cashiers = [];
$modes_found  = [];
foreach ($counter_media as $row) {
    $counter = $row->counter_name ?: 'Counter';
    $cashier = $row->cashier_name ?: '—';
    $mode    = strtolower($row->payment_mode ?: 'other');
    if (!isset($pivot[$counter])) { $pivot[$counter] = []; $pivot_cashiers[$counter] = []; }
    if (!isset($pivot[$counter][$mode])) { $pivot[$counter][$mode] = ['bills' => 0, 'amount' => 0]; }
    $pivot[$counter][$mode]['bills']  += (int)$row->bill_count;
    $pivot[$counter][$mode]['amount'] += (float)$row->total_amount;
    $pivot_cashiers[$counter][$cashier] = true;
    $modes_found[$mode] = true;
}

// Real per-counter totals — always the actual bill count / amount collected,
// independent of how many modes a split bill was decomposed into.
$row_totals = [];
foreach ($counter_totals as $row) {
    $counter = $row->counter_name ?: 'Counter';
    if (!isset($row_totals[$counter])) { $row_totals[$counter] = ['bills' => 0, 'amount' => 0]; }
    $row_totals[$counter]['bills']  += (int)$row->bill_count;
    $row_totals[$counter]['amount'] += (float)$row->total_amount;
}

// Mode display labels + icons
$mode_labels = [
    'cash'   => ['icon'=>'💵','label'=>'Cash'],
    'upi'    => ['icon'=>'⚡','label'=>'UPI'],
    'card'   => ['icon'=>'💳','label'=>'Card'],
    'neft'   => ['icon'=>'🏛️','label'=>'NEFT'],
    'cheque' => ['icon'=>'📄','label'=>'Cheque'],
    'wallet' => ['icon'=>'📱','label'=>'Wallet'],
    'credit' => ['icon'=>'📋','label'=>'Credit'],
    'other'  => ['icon'=>'💰','label'=>'Other'],
];
// Show only modes that have data, in preferred order
$ordered_modes = array_filter(
    ['cash','upi','card','neft','cheque','wallet','credit','other'],
    fn($m) => isset($modes_found[$m])
);
// Any extra modes not in our list
foreach (array_keys($modes_found) as $m) {
    if (!in_array($m, $ordered_modes)) $ordered_modes[] = $m;
}

// Column totals
$col_totals = [];
foreach ($ordered_modes as $m) $col_totals[$m] = ['bills'=>0,'amount'=>0];
$grand_bills  = 0;
$grand_amount = 0;
?>

<?php if (empty($pivot)): ?>
<div class="alert alert-info">No data found for selected period/cashier.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-bordered table-sm mb-0" style="font-size:13px;">
    <thead>
        <!-- Header row 1: Counter + payment modes (each spans 2 sub-cols) + Total -->
        <tr style="background:#3d2b1f;color:#fff;text-align:center;">
            <th rowspan="2" style="vertical-align:middle;min-width:140px;text-align:left;padding-left:10px;">Counter / Cashier</th>
            <?php foreach ($ordered_modes as $m):
                $lbl = $mode_labels[$m] ?? ['icon'=>'💰','label'=>ucfirst($m)];
            ?>
            <th colspan="2" style="border-left:2px solid #6b4f3a;">
                <?= $lbl['icon'] ?> <?= $lbl['label'] ?>
            </th>
            <?php endforeach; ?>
            <th colspan="2" style="border-left:2px solid #6b4f3a;background:#5c3a1e;">Total</th>
        </tr>
        <!-- Header row 2: Bills / Amount for each mode -->
        <tr style="background:#5c3a1e;color:#fde68a;text-align:center;font-size:11px;">
            <?php foreach ($ordered_modes as $m): ?>
            <th style="border-left:2px solid #6b4f3a;">Bills</th>
            <th>Amount (₹)</th>
            <?php endforeach; ?>
            <th style="border-left:2px solid #6b4f3a;">Bills</th>
            <th>Amount (₹)</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($pivot as $counterName => $modeData):
        $row_bills  = $row_totals[$counterName]['bills']  ?? 0;
        $row_amount = $row_totals[$counterName]['amount'] ?? 0;
        $cashier_list = implode(', ', array_keys($pivot_cashiers[$counterName]));
    ?>
    <tr>
        <td style="padding-left:10px;">
            <div style="font-weight:700;font-size:13px;"><i class="fas fa-desktop text-muted mr-1"></i><?= htmlspecialchars($counterName) ?></div>
            <div style="font-size:11px;color:#6b7280;margin-top:1px;"><i class="fas fa-user fa-xs mr-1"></i><?= htmlspecialchars($cashier_list) ?></div>
        </td>
        <?php foreach ($ordered_modes as $m):
            $cell = $modeData[$m] ?? ['bills'=>0,'amount'=>0];
            $col_totals[$m]['bills']  += $cell['bills'];
            $col_totals[$m]['amount'] += $cell['amount'];
        ?>
        <td class="text-center" style="border-left:2px solid #e5e7eb;">
            <?= $cell['bills'] > 0 ? '<span class="badge badge-secondary">'.$cell['bills'].'</span>' : '<span class="text-muted">—</span>' ?>
        </td>
        <td class="text-right" style="<?= $cell['amount']>0?'font-weight:600;color:#1d4ed8;':'' ?>">
            <?= $cell['amount'] > 0 ? '₹'.number_format($cell['amount'],2) : '<span class="text-muted">—</span>' ?>
        </td>
        <?php endforeach; ?>
        <?php $grand_bills += $row_bills; $grand_amount += $row_amount; ?>
        <td class="text-center font-weight-bold" style="border-left:2px solid #d1d5db;background:#f9fafb;">
            <span class="badge badge-dark"><?= $row_bills ?></span>
        </td>
        <td class="text-right font-weight-bold" style="background:#f9fafb;color:#065f46;">
            ₹<?= number_format($row_amount,2) ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="background:#fef3c7;font-weight:700;font-size:13px;">
            <td style="padding-left:10px;">TOTAL</td>
            <?php foreach ($ordered_modes as $m): $ct = $col_totals[$m]; ?>
            <td class="text-center" style="border-left:2px solid #e5e7eb;">
                <span class="badge badge-warning text-dark"><?= $ct['bills'] ?></span>
            </td>
            <td class="text-right" style="color:#1d4ed8;">₹<?= number_format($ct['amount'],2) ?></td>
            <?php endforeach; ?>
            <td class="text-center" style="border-left:2px solid #d1d5db;background:#fde68a;">
                <span class="badge badge-dark"><?= $grand_bills ?></span>
            </td>
            <td class="text-right" style="background:#fde68a;color:#065f46;">₹<?= number_format($grand_amount,2) ?></td>
        </tr>
    </tfoot>
</table>
</div>
<?php endif; ?>

<style>
@media print {
    .no-print { display: none !important; }
    .print-header { display: block !important; }
}
</style>
