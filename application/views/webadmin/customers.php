<form class="card row" method="get" action="<?= site_url('webadmin/customers') ?>">
  <div><label for="q">Search name / mobile / email (searches all customers)</label><input id="q" name="q" value="<?= html_escape($q) ?>"></div>
  <div style="flex:0 0 auto"><button class="btn btn-primary">Search</button></div>
</form>
<div class="card">
  <p class="small muted"><?= $q === '' ? 'Showing customers who have ordered online or have a website login.' : 'Search results.' ?> Customer details are personal data — use them only to fulfil orders.</p>
  <div class="tbl-wrap"><table class="tbl">
    <tr><th>Name</th><th>Mobile</th><th>Email</th><th>City</th><th class="r">Web orders</th><th class="r">Spent</th><th>Last order</th><th>Website login</th></tr>
    <?php foreach ($rows as $c): ?>
    <tr><td><a href="<?= site_url('webadmin/customer/' . $c->id) ?>"><?= html_escape($c->name) ?></a></td><td><?= html_escape($c->phone) ?></td><td><?= html_escape($c->email) ?></td><td><?= html_escape($c->city) ?></td>
      <td class="r"><?= (int)$c->orders ?></td><td class="r"><?= inr($c->spent) ?></td><td class="small"><?= $c->last_order ? date('d M Y', strtotime($c->last_order)) : '—' ?></td>
      <td><?= !$c->has_login ? '<span class="badge b-grey">Guest</span>' : ($c->web_active ? '<span class="badge b-green">Active</span>' : '<span class="badge b-red">Disabled</span>') ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
