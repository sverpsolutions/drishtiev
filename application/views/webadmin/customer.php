<p><a href="<?= site_url('webadmin/customers') ?>">← Customers</a></p>
<div class="grid" style="grid-template-columns:minmax(0,2fr) minmax(260px,1fr);align-items:start">
  <div class="card">
    <h3>Web orders</h3>
    <?php if (!$orders): ?><p class="muted">No web orders.</p><?php else: ?>
    <table class="tbl"><tr><th>Order</th><th>Date</th><th>Status</th><th>Payment</th><th class="r">Total</th></tr>
      <?php foreach ($orders as $o): ?><tr><td><a href="<?= site_url('webadmin/order/' . $o->id) ?>"><?= html_escape($o->order_no) ?></a></td><td><?= date('d M Y', strtotime($o->created_at)) ?></td><td><?= order_badge($o->order_status) ?></td><td><?= order_badge($o->payment_status) ?></td><td class="r"><?= inr($o->grand_total, 2) ?></td></tr><?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3><?= html_escape($c->name) ?></h3>
    <p style="margin:0">Mobile: <a href="tel:<?= html_escape($c->phone) ?>"><?= html_escape($c->phone) ?></a><?= $c->email ? '<br>Email: ' . html_escape($c->email) : '' ?><?= $c->city ? '<br>' . html_escape($c->city) : '' ?></p>
    <p class="small muted">Customer since <?= date('d M Y', strtotime($c->created_at)) ?> · ERP customer #<?= (int)$c->id ?></p>
    <h3 style="margin-top:14px">Website login</h3>
    <?php if (!$c->web_password_hash): ?><p class="small muted">No website password (guest checkout only).</p><?php else: ?>
    <p><?= $c->web_active ? '<span class="badge b-green">Active</span>' : '<span class="badge b-red">Disabled</span>' ?></p>
    <?php endif; ?>
    <?= form_open('webadmin/customer_action/' . $c->id, ['data-confirm' => 'Change website access for this customer?']) ?><input type="hidden" name="action" value="toggle"><button class="btn <?= $c->web_active ? 'btn-danger' : 'btn-primary' ?>"><?= $c->web_active ? 'Disable website login' : 'Enable website login' ?></button></form>
    <details class="act" style="margin-top:10px"><summary>Set a temporary password</summary>
      <?= form_open('webadmin/customer_action/' . $c->id) ?><input type="hidden" name="action" value="reset"><input type="text" name="password" minlength="8" required placeholder="At least 8 characters" autocomplete="off" aria-label="Temporary password"><button class="btn" style="margin-top:6px">Set password</button></form>
    </details>
  </div>
</div>
