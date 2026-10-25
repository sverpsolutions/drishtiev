<?php // FILE: application/views/reports/wallet_ledger.php ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-wallet text-primary"></i> Wallet Ledger</h5>
        <small class="text-muted">Customer wallet credit/debit history with running balance</small>
    </div>
    <?php if ($customer_id && !empty($ledger)): ?>
    <div>
        <button onclick="exportTableToExcel('walletTable', 'Wallet_Ledger_<?= $customer_id ?>_<?= $from ?>_<?= $to ?>')"
                class="btn btn-success btn-sm ml-1"><i class="fas fa-file-excel"></i> Export Excel</button>
    </div>
    <?php endif; ?>
</div>

<!-- Filter Form -->
<div class="card border-0 shadow-sm mb-3">
<div class="card-body py-3">
<form method="get" action="<?= base_url('reports/wallet_ledger') ?>" class="form-row align-items-end">
    <div class="col-md-4">
        <label class="small font-weight-bold">Select Customer</label>
        <select name="customer_id" class="form-control form-control-sm" id="customerSelect">
            <option value="">-- Select --</option>
            <?php foreach ($customers as $c): ?>
            <option value="<?= $c->id ?>" <?= $customer_id == $c->id ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?><?= !empty($c->phone) ? ' ('.$c->phone.')' : '' ?></option>
            <?php endforeach; ?>
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
        <a href="<?= base_url('reports/wallet_ledger') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
    </div>
</form>
</div>
</div>

<?php if ($customer_id && $customer): ?>

<?php
$total_credit = array_sum(array_map(fn($r) => $r['transaction_type'] === 'credit' ? (float)$r['amount'] : 0, $ledger));
$total_debit  = array_sum(array_map(fn($r) => $r['transaction_type'] === 'debit'  ? (float)$r['amount'] : 0, $ledger));
$closing_bal  = !empty($ledger) ? (float)end($ledger)['closing_balance'] : (float)($customer->wallet_balance ?? 0);
$net_change   = $total_credit - $total_debit;
?>

<!-- Customer Info Card -->
<div class="card border-0 shadow-sm mb-3">
<div class="card-body py-3">
<div class="row align-items-center">
    <div class="col-md-6">
        <h5 class="font-weight-bold mb-1 text-primary"><?= htmlspecialchars($customer->name) ?></h5>
        <?php if (!empty($customer->phone)): ?><span class="text-muted mr-3"><i class="fas fa-phone fa-xs"></i> <?= $customer->phone ?></span><?php endif; ?>
    </div>
    <div class="col-md-6 text-right">
        <div class="row text-center">
            <div class="col-4">
                <div class="text-muted small">Total Credits</div>
                <div class="font-weight-bold text-success">₹<?= number_format($total_credit, 2) ?></div>
            </div>
            <div class="col-4">
                <div class="text-muted small">Total Debits</div>
                <div class="font-weight-bold text-danger">₹<?= number_format($total_debit, 2) ?></div>
            </div>
            <div class="col-4">
                <div class="text-muted small">Closing Balance</div>
                <div class="font-weight-bold h5 <?= $closing_bal >= 0 ? 'text-success' : 'text-danger' ?>">₹<?= number_format($closing_bal, 2) ?></div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<!-- Ledger Table -->
<div class="card border-0 shadow-sm">
<div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
    <span class="font-weight-bold">Statement: <?= date('d M Y', strtotime($from)) ?> — <?= date('d M Y', strtotime($to)) ?></span>
    <small class="text-white-50"><?= count($ledger) ?> transactions | Net change: ₹<?= number_format($net_change, 2) ?></small>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover table-bordered mb-0" id="walletTable" style="font-size:.85rem;">
<thead class="thead-dark">
<tr>
    <th style="width:5%">#</th>
    <th style="width:14%">Date</th>
    <th style="width:16%">Reference</th>
    <th>Remarks</th>
    <th style="width:12%;text-align:right;">Credit</th>
    <th style="width:12%;text-align:right;">Debit</th>
    <th style="width:14%;text-align:right;">Balance</th>
</tr>
</thead>
<tbody>
<?php $sno = 0; foreach ($ledger as $row): $sno++; ?>
<tr>
    <td><?= $sno ?></td>
    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
    <td>
        <span class="text-muted"><?= htmlspecialchars($row['reference_no'] ?? '—') ?></span>
        <?php if (!empty($row['reference_type'])): ?><br><small class="badge badge-light border"><?= htmlspecialchars($row['reference_type']) ?></small><?php endif; ?>
    </td>
    <td><?= htmlspecialchars($row['remarks'] ?? '') ?></td>
    <td class="text-right <?= $row['transaction_type'] === 'credit' ? 'text-success font-weight-bold' : 'text-muted' ?>">
        <?= $row['transaction_type'] === 'credit' ? '₹'.number_format($row['amount'], 2) : '—' ?>
    </td>
    <td class="text-right <?= $row['transaction_type'] === 'debit' ? 'text-danger font-weight-bold' : 'text-muted' ?>">
        <?= $row['transaction_type'] === 'debit' ? '₹'.number_format($row['amount'], 2) : '—' ?>
    </td>
    <td class="text-right font-weight-bold">₹<?= number_format($row['closing_balance'], 2) ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($ledger)): ?>
<tr><td colspan="7" class="text-center text-muted py-4">No wallet transactions found in this period.</td></tr>
<?php endif; ?>
</tbody>
<tfoot class="table-dark">
<tr>
    <th colspan="4" class="text-right">Totals</th>
    <th class="text-right">₹<?= number_format($total_credit, 2) ?></th>
    <th class="text-right">₹<?= number_format($total_debit, 2) ?></th>
    <th class="text-right">₹<?= number_format($closing_bal, 2) ?></th>
</tr>
</tfoot>
</table>
</div>
</div>
</div>

<?php elseif ($customer_id && !$customer): ?>
<div class="alert alert-warning">Customer not found.</div>
<?php else: ?>
<div class="card border-0 shadow-sm py-5 text-center text-muted">
    <i class="fas fa-wallet fa-3x mb-3 text-primary"></i>
    <h5>Select a customer above to view their wallet ledger</h5>
    <p class="mb-0">Choose customer, set date range, and click "View Ledger"</p>
</div>
<?php endif; ?>

<script>
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
