<?php // FILE: application/views/reports/party_ledger.php ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-book text-primary"></i> Party Ledger</h5>
        <small class="text-muted">Customer / Supplier transaction history with running balance</small>
    </div>
    <?php if ($party_id && !empty($ledger)): ?>
    <div>
        <a href="<?= base_url('reports/party_ledger_print?party_type='.$party_type.'&party_id='.$party_id.'&from='.$from.'&to='.$to) ?>"
           target="_blank" class="btn btn-outline-dark btn-sm"><i class="fas fa-print"></i> Print / PDF</a>
        <button onclick="exportTableToExcel('ledgerTable', 'Party_Ledger_<?= $party_id ?>_<?= $from ?>_<?= $to ?>')"
                class="btn btn-success btn-sm ml-1"><i class="fas fa-file-excel"></i> Export Excel</button>
        <?php if (!empty($party->phone)): ?>
        <?php
        $wa_phone = preg_replace('/\D/', '', $party->phone);
        if (strlen($wa_phone) == 10) $wa_phone = '91' . $wa_phone;
        $wa_text = urlencode("Dear ".$party->name.", your account statement from ".date('d M Y',strtotime($from))." to ".date('d M Y',strtotime($to))." is ready. Please contact us for details.");
        ?>
        <a href="https://wa.me/<?= $wa_phone ?>?text=<?= $wa_text ?>" target="_blank"
           class="btn btn-sm ml-1" style="background:#25D366;color:#fff;"><i class="fab fa-whatsapp"></i> WhatsApp</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ══ Filter Form ══ -->
<div class="card border-0 shadow-sm mb-3">
<div class="card-body py-3">
<form method="get" action="<?= base_url('reports/party_ledger') ?>" class="form-row align-items-end">
    <div class="col-md-2">
        <label class="small font-weight-bold">Party Type</label>
        <select name="party_type" id="partyTypeSelect" class="form-control form-control-sm" onchange="this.form.submit()">
            <option value="customer" <?= $party_type=='customer'?'selected':'' ?>>Customer</option>
            <option value="supplier" <?= $party_type=='supplier'?'selected':'' ?>>Supplier</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="small font-weight-bold">
            <?= $party_type === 'customer' ? 'Select Customer' : 'Select Supplier' ?>
        </label>
        <select name="party_id" class="form-control form-control-sm" id="partySelect">
            <option value="">-- Select --</option>
            <?php if ($party_type === 'customer'): ?>
                <?php foreach ($customers as $c): ?>
                <option value="<?= $c->id ?>" <?= $party_id==$c->id?'selected':'' ?>>
                    <?= htmlspecialchars($c->name) ?>
                    <?php if ($c->balance > 0): ?>(Due: ₹<?= number_format($c->balance,0) ?>)<?php endif; ?>
                </option>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s->id ?>" <?= $party_id==$s->id?'selected':'' ?>><?= htmlspecialchars($s->name) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="small font-weight-bold">From Date</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
    </div>
    <div class="col-md-2">
        <label class="small font-weight-bold">To Date</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search"></i> View Ledger</button>
        <a href="<?= base_url('reports/party_ledger') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
    </div>
</form>
</div>
</div>

<?php if ($party_id && $party): ?>

<!-- ══ Party Info Card ══ -->
<div class="card border-0 shadow-sm mb-3">
<div class="card-body py-3">
<div class="row align-items-center">
    <div class="col-md-6">
        <h5 class="font-weight-bold mb-1 text-primary"><?= htmlspecialchars($party->name) ?></h5>
        <?php if (!empty($party->phone)): ?><span class="text-muted mr-3"><i class="fas fa-phone fa-xs"></i> <?= $party->phone ?></span><?php endif; ?>
        <?php if (!empty($party->email)): ?><span class="text-muted mr-3"><i class="fas fa-envelope fa-xs"></i> <?= $party->email ?></span><?php endif; ?>
        <?php if (!empty($party->gst_number)): ?><span class="badge badge-light border">GSTIN: <?= $party->gst_number ?></span><?php endif; ?>
        <div class="mt-1 text-muted small"><?= htmlspecialchars($party->address ?? '') ?><?= !empty($party->city) ? ', '.$party->city : '' ?></div>
    </div>
    <div class="col-md-6 text-right">
        <?php
        // Calculate closing balance = opening + sum of debits - sum of credits
        $total_debit  = array_sum(array_column($ledger, 'debit'));
        $total_credit = array_sum(array_column($ledger, 'credit'));
        $closing_bal  = $opening_balance + $total_debit - $total_credit;
        ?>
        <div class="row text-center">
            <div class="col-4">
                <div class="text-muted small">Opening Balance</div>
                <div class="font-weight-bold <?= $opening_balance > 0 ? 'text-danger' : ($opening_balance < 0 ? 'text-success' : '') ?>">
                    ₹<?= number_format(abs($opening_balance), 2) ?>
                    <?php if ($opening_balance > 0): ?><small class="text-danger">Dr</small>
                    <?php elseif ($opening_balance < 0): ?><small class="text-success">Cr</small><?php endif; ?>
                </div>
            </div>
            <div class="col-4">
                <div class="text-muted small">Period <?= $party_type==='customer' ? 'Billed' : 'Purchased' ?></div>
                <div class="font-weight-bold text-danger">₹<?= number_format($total_debit, 2) ?></div>
            </div>
            <div class="col-4">
                <div class="text-muted small">Closing Balance</div>
                <div class="font-weight-bold h5 <?= $closing_bal > 0 ? 'text-danger' : 'text-success' ?>">
                    ₹<?= number_format(abs($closing_bal), 2) ?>
                    <small><?= $closing_bal > 0 ? 'Dr' : 'Cr' ?></small>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<!-- ══ Ledger Table ══ -->
<div class="card border-0 shadow-sm">
<div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
    <span class="font-weight-bold">Statement: <?= date('d M Y',strtotime($from)) ?> — <?= date('d M Y',strtotime($to)) ?></span>
    <small class="text-white-50"><?= count($ledger) ?> transactions</small>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover table-bordered mb-0" id="ledgerTable" style="font-size:.85rem;">
<thead class="thead-dark">
<tr>
    <th style="width:5%">#</th>
    <th style="width:12%">Date</th>
    <th style="width:15%">Reference No.</th>
    <th>Particulars</th>
    <th style="width:13%;text-align:right;">Debit (Dr)</th>
    <th style="width:13%;text-align:right;">Credit (Cr)</th>
    <th style="width:14%;text-align:right;">Balance</th>
</tr>
</thead>
<tbody>
<!-- Opening Balance Row -->
<tr class="table-light font-weight-bold">
    <td colspan="4">Opening Balance (before <?= date('d M Y',strtotime($from)) ?>)</td>
    <td class="text-right">
        <?php if ($opening_balance > 0): ?>₹<?= number_format($opening_balance,2) ?> <small class="text-danger">Dr</small><?php else: ?>—<?php endif; ?>
    </td>
    <td class="text-right">
        <?php if ($opening_balance < 0): ?>₹<?= number_format(abs($opening_balance),2) ?> <small class="text-success">Cr</small><?php else: ?>—<?php endif; ?>
    </td>
    <td class="text-right font-weight-bold <?= $opening_balance >= 0 ? 'text-danger' : 'text-success' ?>">
        ₹<?= number_format(abs($opening_balance),2) ?> <?= $opening_balance >= 0 ? '<small>Dr</small>' : '<small>Cr</small>' ?>
    </td>
</tr>
<?php
$running = $opening_balance;
$sno = 0;
foreach ($ledger as $row):
    $sno++;
    $dr = (float)$row['debit'];
    $cr = (float)$row['credit'];
    $running += $dr - $cr;
    $is_dr = $running >= 0;
?>
<tr>
    <td><?= $sno ?></td>
    <td><?= date('d M Y', strtotime($row['txn_date'])) ?></td>
    <td>
        <?php if ($row['ref_module'] === 'invoice'): ?>
        <a href="<?= base_url('billing/invoice/'.$row['ref_id']) ?>" class="text-primary font-weight-bold"><?= htmlspecialchars($row['ref_no']) ?></a>
        <?php elseif ($row['ref_module'] === 'purchase'): ?>
        <a href="<?= base_url('purchases/view/'.$row['ref_id']) ?>" class="text-info font-weight-bold"><?= htmlspecialchars($row['ref_no']) ?></a>
        <?php else: ?>
        <span class="text-success"><?= htmlspecialchars($row['ref_no'] ?? '—') ?></span>
        <?php endif; ?>
    </td>
    <td>
        <span class="badge badge-<?= ($row['txn_type']=='Sales Invoice'||$row['txn_type']=='Purchase Bill') ? 'secondary' : 'success' ?> mr-1" style="font-size:.7rem;">
            <?= $row['txn_type'] ?>
        </span>
    </td>
    <td class="text-right <?= $dr > 0 ? 'text-danger font-weight-bold' : 'text-muted' ?>">
        <?= $dr > 0 ? '₹'.number_format($dr,2) : '—' ?>
    </td>
    <td class="text-right <?= $cr > 0 ? 'text-success font-weight-bold' : 'text-muted' ?>">
        <?= $cr > 0 ? '₹'.number_format($cr,2) : '—' ?>
    </td>
    <td class="text-right font-weight-bold <?= $is_dr ? 'text-danger' : 'text-success' ?>">
        ₹<?= number_format(abs($running),2) ?> <small><?= $is_dr ? 'Dr' : 'Cr' ?></small>
    </td>
</tr>
<?php endforeach; ?>

<?php if (empty($ledger)): ?>
<tr><td colspan="7" class="text-center text-muted py-4">No transactions found in this period.</td></tr>
<?php endif; ?>
</tbody>
<tfoot class="table-dark">
<tr>
    <th colspan="4" class="text-right">Totals</th>
    <th class="text-right">₹<?= number_format($total_debit, 2) ?></th>
    <th class="text-right">₹<?= number_format($total_credit, 2) ?></th>
    <th class="text-right <?= $closing_bal > 0 ? 'text-warning' : 'text-success' ?>">
        ₹<?= number_format(abs($closing_bal), 2) ?> <?= $closing_bal > 0 ? 'Dr' : 'Cr' ?>
    </th>
</tr>
</tfoot>
</table>
</div>
</div>
</div>

<?php elseif ($party_id && !$party): ?>
<div class="alert alert-warning">Party not found.</div>
<?php else: ?>
<div class="card border-0 shadow-sm py-5 text-center text-muted">
    <i class="fas fa-book fa-3x mb-3 text-primary"></i>
    <h5>Select a party above to view their ledger</h5>
    <p class="mb-0">Choose customer or supplier, set date range, and click "View Ledger"</p>
</div>
<?php endif; ?>

<script>
/* ── Export table to Excel ── */
function exportTableToExcel(tableId, filename) {
    var tbl  = document.getElementById(tableId);
    var html = tbl.outerHTML;
    var url  = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    var a    = document.createElement('a');
    a.href   = url;
    a.download = filename + '.xls';
    a.click();
}
</script>
