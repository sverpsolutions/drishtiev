<?php
$st = function ($k) use ($by_status) { return (int)($by_status[$k] ?? 0); };
$trend_map = []; foreach ($trend as $t) $trend_map[$t->d] = $t;
$max = 1; foreach ($trend as $t) $max = max($max, (float)$t->amt);
?>
<div class="grid g4" style="margin-bottom:16px">
  <a class="kpi" href="<?= site_url('webadmin/orders?status=today') ?>"><div class="l">Today's sales</div><div class="v"><?= inr($today->amt) ?></div><div class="small muted"><?= (int)$today->n ?> order(s)</div></a>
  <a class="kpi alert" href="<?= site_url('webadmin/orders?status=new') ?>"><div class="l">New — to process</div><div class="v"><?= $st('new') ?></div><div class="small muted"><?= $awaiting_pay ?> awaiting payment check</div></a>
  <a class="kpi" href="<?= site_url('webadmin/orders?status=open') ?>"><div class="l">Open orders</div><div class="v"><?= $st('confirmed') + $st('processing') + $st('packed') ?></div><div class="small muted"><?= $st('shipped') ?> shipped · <?= $st('pending_payment') ?> pending payment</div></a>
  <a class="kpi" href="<?= site_url('webadmin/orders?status=delivered') ?>"><div class="l">This month</div><div class="v"><?= inr($month->amt) ?></div><div class="small muted"><?= (int)$month->n ?> orders · <?= $st('delivered') ?> delivered all-time</div></a>
</div>

<div class="grid g2">
  <div class="card">
    <h3>Sales — last 14 days</h3>
    <div class="bars" role="img" aria-label="Daily web sales for the last 14 days">
      <?php for ($i = 13; $i >= 0; $i--): $d = date('Y-m-d', strtotime("-{$i} day")); $amt = (float)($trend_map[$d]->amt ?? 0); ?>
      <div title="<?= date('d M', strtotime($d)) ?>: <?= inr($amt) ?> (<?= (int)($trend_map[$d]->n ?? 0) ?> orders)"><span style="height:<?= max(1, round($amt / $max * 100)) ?>%"></span><?= date('d', strtotime($d)) ?></div>
      <?php endfor; ?>
    </div>
  </div>
  <div class="card">
    <h3>Orders by status</h3>
    <div class="tabs">
      <?php foreach (['pending_payment', 'new', 'confirmed', 'processing', 'packed', 'shipped', 'delivered', 'cancelled', 'rejected', 'returned'] as $s): ?>
      <a href="<?= site_url('webadmin/orders?status=' . $s) ?>"><?= Webstore_model::status_label($s) ?><span class="n"><?= $st($s) ?></span></a>
      <?php endforeach; ?>
    </div>
    <h3 style="margin-top:12px">Sales by category (30 days)</h3>
    <?php if (!$by_cat): ?><p class="muted">No sales yet.</p><?php else: ?>
    <table class="tbl"><?php foreach ($by_cat as $c): ?><tr><td><?= html_escape($c->name) ?></td><td class="r"><?= (int)$c->q ?> pcs</td><td class="r"><?= inr($c->amt) ?></td></tr><?php endforeach; ?></table>
    <?php endif; ?>
  </div>
</div>

<div class="grid g2">
  <div class="card">
    <h3>Recent orders</h3>
    <?php if (!$recent): ?><p class="muted">No web orders yet. They appear here automatically as soon as customers check out.</p><?php else: ?>
    <div class="tbl-wrap"><table class="tbl">
      <tr><th>Order</th><th>Customer</th><th>Status</th><th class="r">Total</th></tr>
      <?php foreach ($recent as $o): ?><tr><td><a href="<?= site_url('webadmin/order/' . $o->id) ?>"><?= html_escape($o->order_no) ?></a><div class="small muted"><?= date('d M, h:i A', strtotime($o->created_at)) ?></div></td><td><?= html_escape($o->customer_name) ?></td><td><?= order_badge($o->order_status) ?></td><td class="r"><?= inr($o->grand_total, 2) ?></td></tr><?php endforeach; ?>
    </table></div>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>Top sellers (30 days)</h3>
    <?php if (!$top): ?><p class="muted">No sales yet.</p><?php else: ?>
    <table class="tbl"><?php foreach ($top as $t): ?><tr><td><?= html_escape($t->product_name) ?></td><td class="r"><?= (int)$t->q ?> pcs</td><td class="r"><?= inr($t->amt) ?></td></tr><?php endforeach; ?></table>
    <?php endif; ?>
    <h3 style="margin-top:16px">Low-stock variants <a class="small" href="<?= site_url('webadmin/reports/lowstock') ?>">(all)</a></h3>
    <?php if (!$low): ?><p class="muted">All variants are above their minimum stock.</p><?php else: ?>
    <table class="tbl"><?php foreach ($low as $l): ?><tr><td><?= html_escape($l->name) ?><div class="small muted"><?= html_escape($l->size_name . ' / ' . $l->colour_name . ' · ' . $l->variant_sku) ?></div></td><td class="r"><span class="badge <?= $l->stock <= 0 ? 'b-red' : 'b-amber' ?>"><?= (float)$l->stock ?> left</span></td></tr><?php endforeach; ?></table>
    <?php endif; ?>
  </div>
</div>
