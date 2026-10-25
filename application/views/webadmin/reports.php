<?php
$money_cols = ['Subtotal', 'Discount', 'Shipping', 'Net sales', 'Net amount', 'Amount', 'Taxable value', 'CGST', 'SGST', 'IGST', 'Invoice value', 'Refunded (processed)', 'Refund pending', 'MRP', 'Selling price'];
$totals = [];
?>
<div class="tabs"><?php foreach ($reports as $k => $r): ?><a class="<?= $type === $k ? 'on' : '' ?>" href="<?= site_url('webadmin/reports/' . $k) . '?from=' . $from . '&to=' . $to ?>"><?= html_escape($r[0]) ?></a><?php endforeach; ?></div>
<form class="card row" method="get" action="<?= site_url('webadmin/reports/' . $type) ?>">
  <?php if ($dated): ?>
  <div><label for="from">From</label><input id="from" type="date" name="from" value="<?= $from ?>"></div>
  <div><label for="to">To</label><input id="to" type="date" name="to" value="<?= $to ?>"></div>
  <div style="flex:0 0 auto"><button class="btn btn-primary">Show</button></div>
  <?php else: ?><div><span class="muted">Current stock at the website's fulfilment location.</span></div><?php endif; ?>
  <div style="flex:0 0 auto"><a class="btn" href="<?= site_url('webadmin/reports/' . $type) . '?from=' . $from . '&to=' . $to . '&export=1' ?>">Export CSV (Excel)</a></div>
</form>
<div class="card">
  <h3><?= html_escape($label) ?></h3>
  <?php if (!$rows): ?><p class="muted">No data for this period.</p><?php else: ?>
  <div class="tbl-wrap"><table class="tbl">
    <tr><?php foreach ($head as $h): ?><th class="<?= in_array($h, $money_cols, true) || preg_match('/Qty|Orders|Items|Stock|Returned/', $h) ? 'r' : '' ?>"><?= html_escape($h) ?></th><?php endforeach; ?></tr>
    <?php foreach ($rows as $r): ?><tr>
      <?php foreach ($r as $i => $cell): $h = $head[$i]; $num = in_array($h, $money_cols, true); if ($num || preg_match('/Qty|Orders|Items|Returned/', $h)) $totals[$i] = ($totals[$i] ?? 0) + (float)$cell; ?>
      <td class="<?= $num || is_numeric($cell) ? 'r' : '' ?>"><?= $num ? inr($cell, 2) : html_escape((string)$cell) ?></td>
      <?php endforeach; ?>
    </tr><?php endforeach; ?>
    <?php if ($totals): ?><tr style="font-weight:600;background:#fbf8f4"><?php foreach ($head as $i => $h): ?><td class="r"><?= $i === 0 ? 'Total' : (isset($totals[$i]) ? (in_array($h, $money_cols, true) ? inr($totals[$i], 2) : (float)$totals[$i]) : '') ?></td><?php endforeach; ?></tr><?php endif; ?>
  </table></div>
  <?php endif; ?>
</div>
