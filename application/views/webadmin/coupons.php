<p><a class="btn btn-primary" href="<?= site_url('webadmin/coupon') ?>">+ New coupon</a></p>
<div class="card">
  <?php if (!$rows): ?><p class="muted">No coupons yet.</p><?php else: ?>
  <div class="tbl-wrap"><table class="tbl">
    <tr><th>Code</th><th>Discount</th><th>Min order</th><th>Valid</th><th>Used / limit</th><th>Applies to</th><th>Status</th><th></th></tr>
    <?php foreach ($rows as $c): $expired = $c->ends_at && $c->ends_at < date('Y-m-d H:i:s'); ?>
    <tr>
      <td><strong><?= html_escape($c->code) ?></strong><div class="small muted"><?= html_escape($c->description) ?></div></td>
      <td><?= $c->discount_type === 'percent' ? (float)$c->discount_value . '%' . ($c->max_discount ? ' (max ' . inr($c->max_discount) . ')' : '') : inr($c->discount_value) ?></td>
      <td><?= $c->min_order > 0 ? inr($c->min_order) : '—' ?></td>
      <td class="small"><?= $c->starts_at ? date('d M Y', strtotime($c->starts_at)) : 'Now' ?> → <?= $c->ends_at ? date('d M Y', strtotime($c->ends_at)) : 'No expiry' ?></td>
      <td><?= (int)$c->used ?> / <?= $c->usage_limit ?? '∞' ?><?= $c->per_customer_limit ? '<div class="small muted">' . (int)$c->per_customer_limit . ' per customer</div>' : '' ?></td>
      <td class="small"><?= $c->category_ids || $c->product_ids ? 'Selected categories/products' : 'All products' ?></td>
      <td><?= !$c->is_active ? '<span class="badge b-grey">Inactive</span>' : ($expired ? '<span class="badge b-red">Expired</span>' : '<span class="badge b-green">Active</span>') ?></td>
      <td><a class="btn btn-sm" href="<?= site_url('webadmin/coupon/' . $c->id) ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>
