<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Party Ledger — <?= htmlspecialchars($party->name ?? '') ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size: 11px; color: #222; background:#fff; }
  .header { text-align:center; border-bottom:2px solid #333; padding-bottom:8px; margin-bottom:10px; }
  .header h2 { font-size:16px; font-weight:bold; }
  .header h3 { font-size:13px; color:#555; }
  .meta-row { display:flex; justify-content:space-between; margin-bottom:10px; font-size:10.5px; }
  .meta-row div { flex:1; }
  table { width:100%; border-collapse:collapse; margin-top:8px; }
  th, td { border:1px solid #ccc; padding:4px 6px; }
  thead th { background:#333; color:#fff; font-weight:bold; }
  .opening-row td { background:#f5f5f5; font-weight:bold; }
  .dr { color:#c0392b; font-weight:bold; }
  .cr { color:#27ae60; font-weight:bold; }
  tfoot th { background:#333; color:#fff; }
  .summary-box { margin-top:12px; border:1px solid #ccc; padding:8px; display:flex; gap:20px; }
  .summary-box div { flex:1; text-align:center; }
  .summary-box .val { font-size:14px; font-weight:bold; }
  @media print {
    body { font-size:10px; }
    .no-print { display:none !important; }
  }
</style>
</head>
<body>
<div class="no-print" style="padding:8px;background:#f8f9fa;border-bottom:1px solid #ddd;margin-bottom:10px;">
    <button onclick="window.print()" style="padding:6px 16px;background:#333;color:#fff;border:none;border-radius:4px;cursor:pointer;">🖨 Print / Save PDF</button>
    <button onclick="window.close()" style="padding:6px 12px;border:1px solid #ccc;background:#fff;border-radius:4px;cursor:pointer;margin-left:8px;">✕ Close</button>
</div>

<div class="header">
    <h2><?= htmlspecialchars($company['company_name'] ?? 'SV ERP Solutions') ?></h2>
    <div style="font-size:10px;color:#555;">
        <?= htmlspecialchars($company['company_address'] ?? '') ?> | Ph: <?= htmlspecialchars($company['company_phone'] ?? '') ?> | GSTIN: <?= htmlspecialchars($company['company_gstin'] ?? '') ?>
    </div>
    <h3 style="margin-top:6px;">Party Ledger Statement — <?= ucfirst($party_type) ?></h3>
</div>

<div class="meta-row">
    <div>
        <strong>Party Name:</strong> <?= htmlspecialchars($party->name) ?><br>
        <?php if (!empty($party->phone)): ?><strong>Phone:</strong> <?= $party->phone ?><br><?php endif; ?>
        <?php if (!empty($party->address)): ?><strong>Address:</strong> <?= htmlspecialchars($party->address) ?><?php endif; ?>
    </div>
    <div style="text-align:right;">
        <strong>Period:</strong> <?= date('d M Y',strtotime($from)) ?> to <?= date('d M Y',strtotime($to)) ?><br>
        <strong>Printed On:</strong> <?= date('d M Y H:i') ?><br>
        <?php if (!empty($party->gst_number)): ?><strong>GSTIN:</strong> <?= $party->gst_number ?><?php endif; ?>
    </div>
</div>

<?php
$total_debit  = array_sum(array_column($ledger, 'debit'));
$total_credit = array_sum(array_column($ledger, 'credit'));
$closing_bal  = $opening_balance + $total_debit - $total_credit;
?>
<div class="summary-box">
    <div>
        <div class="text-muted" style="font-size:10px;">Opening Balance</div>
        <div class="val <?= $opening_balance > 0 ? 'dr' : 'cr' ?>">₹<?= number_format(abs($opening_balance),2) ?> <?= $opening_balance >= 0 ? 'Dr' : 'Cr' ?></div>
    </div>
    <div>
        <div style="font-size:10px;">Total Debit</div>
        <div class="val dr">₹<?= number_format($total_debit,2) ?></div>
    </div>
    <div>
        <div style="font-size:10px;">Total Credit</div>
        <div class="val cr">₹<?= number_format($total_credit,2) ?></div>
    </div>
    <div>
        <div style="font-size:10px;">Closing Balance</div>
        <div class="val <?= $closing_bal > 0 ? 'dr' : 'cr' ?>" style="font-size:16px;">₹<?= number_format(abs($closing_bal),2) ?> <?= $closing_bal >= 0 ? 'Dr' : 'Cr' ?></div>
    </div>
</div>

<table>
<thead>
<tr>
    <th>#</th><th>Date</th><th>Reference No.</th><th>Particulars</th>
    <th style="text-align:right;">Debit (Dr)</th>
    <th style="text-align:right;">Credit (Cr)</th>
    <th style="text-align:right;">Balance</th>
</tr>
</thead>
<tbody>
<tr class="opening-row">
    <td colspan="4">Opening Balance (before <?= date('d M Y',strtotime($from)) ?>)</td>
    <td style="text-align:right;"><?= $opening_balance > 0 ? '₹'.number_format($opening_balance,2).' Dr' : '—' ?></td>
    <td style="text-align:right;"><?= $opening_balance < 0 ? '₹'.number_format(abs($opening_balance),2).' Cr' : '—' ?></td>
    <td style="text-align:right;" class="<?= $opening_balance >= 0 ? 'dr' : 'cr' ?>">₹<?= number_format(abs($opening_balance),2) ?> <?= $opening_balance >= 0 ? 'Dr' : 'Cr' ?></td>
</tr>
<?php
$running = $opening_balance;
$sno = 0;
foreach ($ledger as $row):
    $sno++;
    $dr = (float)$row['debit'];
    $cr = (float)$row['credit'];
    $running += $dr - $cr;
?>
<tr>
    <td><?= $sno ?></td>
    <td><?= date('d M Y', strtotime($row['txn_date'])) ?></td>
    <td><?= htmlspecialchars($row['ref_no'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['txn_type']) ?></td>
    <td style="text-align:right;" class="<?= $dr > 0 ? 'dr' : '' ?>"><?= $dr > 0 ? '₹'.number_format($dr,2) : '—' ?></td>
    <td style="text-align:right;" class="<?= $cr > 0 ? 'cr' : '' ?>"><?= $cr > 0 ? '₹'.number_format($cr,2) : '—' ?></td>
    <td style="text-align:right;" class="<?= $running >= 0 ? 'dr' : 'cr' ?>">₹<?= number_format(abs($running),2) ?> <?= $running >= 0 ? 'Dr' : 'Cr' ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($ledger)): ?>
<tr><td colspan="7" style="text-align:center;padding:20px;color:#888;">No transactions in this period.</td></tr>
<?php endif; ?>
</tbody>
<tfoot>
<tr>
    <th colspan="4" style="text-align:right;">CLOSING BALANCE</th>
    <th style="text-align:right;">₹<?= number_format($total_debit,2) ?></th>
    <th style="text-align:right;">₹<?= number_format($total_credit,2) ?></th>
    <th style="text-align:right;">₹<?= number_format(abs($closing_bal),2) ?> <?= $closing_bal >= 0 ? 'Dr' : 'Cr' ?></th>
</tr>
</tfoot>
</table>

<div style="margin-top:30px;display:flex;justify-content:space-between;font-size:10.5px;">
    <div>
        <div style="margin-top:30px;border-top:1px solid #333;width:150px;padding-top:4px;">Authorised Signatory</div>
    </div>
    <div style="text-align:right;color:#888;font-size:9px;">
        Generated by SV ERP Solutions | <?= date('d M Y H:i:s') ?>
    </div>
</div>
</body>
</html>
