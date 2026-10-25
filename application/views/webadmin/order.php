<?php
$labels = ['confirmed' => 'Confirm order', 'processing' => 'Mark processing', 'packed' => 'Mark packed', 'shipped' => 'Mark shipped',
           'delivered' => 'Mark delivered', 'cancelled' => 'Cancel order', 'rejected' => 'Reject order'];
$paid_refunded = array_sum(array_map(function ($r) { return $r->status === 'processed' ? $r->amount : 0; }, $refunds));
$can_return = in_array($o->order_status, ['shipped', 'delivered'], true);
$ship_rate = 0; foreach ($items as $it) $ship_rate = max($ship_rate, (float)$it->gst_rate);
?>
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:12px">
  <div>
    <h1 style="margin:0"><?= html_escape($o->order_no) ?></h1>
    <div class="muted"><?= date('l, d M Y · h:i A', strtotime($o->created_at)) ?> · <?= order_badge($o->order_status) ?> <?= order_badge($o->payment_status) ?><?= $o->invoice_no ? ' · Invoice ' . html_escape($o->invoice_no) : '' ?></div>
  </div>
  <div style="display:flex;gap:6px;flex-wrap:wrap">
    <a class="btn" href="<?= site_url('webadmin/invoice/' . $o->id) ?>" target="_blank">Print invoice</a>
    <a class="btn" href="<?= site_url('webadmin/packing_slip/' . $o->id) ?>" target="_blank">Packing slip</a>
    <a class="btn" href="tel:<?= html_escape($o->phone) ?>">Call</a>
    <a class="btn" href="https://wa.me/91<?= html_escape(substr(preg_replace('/\D/', '', $o->phone), -10)) ?>?text=<?= rawurlencode('Hello ' . $o->customer_name . ', regarding your ' . ($company['company_name'] ?? '') . ' order ' . $o->order_no) ?>" target="_blank" rel="noopener">WhatsApp</a>
    <?php if ($o->email): ?><a class="btn" href="mailto:<?= html_escape($o->email) ?>?subject=<?= rawurlencode('Your order ' . $o->order_no) ?>">Email</a><?php endif; ?>
  </div>
</div>
<?php if ($o->status_reason): ?><div class="alert alert-info">Reason: <?= html_escape($o->status_reason) ?></div><?php endif; ?>

<div class="grid" style="grid-template-columns:minmax(0,2fr) minmax(280px,1fr);align-items:start">
  <div>
    <div class="card">
      <h3>Items</h3>
      <div class="tbl-wrap"><table class="tbl">
        <tr><th>Product</th><th>SKU / Barcode</th><th class="r">Qty</th><th class="r">MRP</th><th class="r">Price</th><th class="r">Disc.</th><th class="r">Taxable</th><th class="r">GST</th><th class="r">Total</th></tr>
        <?php foreach ($items as $it): ?>
        <tr>
          <td><a href="<?= site_url('garments/edit/' . $it->product_id) ?>"><?= html_escape($it->product_name) ?></a><?php if ($it->size_name): ?><div class="small">Size <b><?= html_escape($it->size_name) ?></b> · Colour <b><?= html_escape($it->colour_name) ?></b></div><?php endif; ?><?php if ($it->returned_qty): ?><span class="badge b-red">Returned <?= (int)$it->returned_qty ?></span><?php endif; ?></td>
          <td class="small"><?= html_escape($it->sku) ?><br><?= html_escape($it->barcode) ?><br>HSN <?= html_escape($it->hsn_code) ?></td>
          <td class="r"><?= (int)$it->qty ?></td><td class="r"><?= inr($it->mrp, 2) ?></td><td class="r"><?= inr($it->unit_price, 2) ?></td>
          <td class="r"><?= $it->discount > 0 ? inr($it->discount, 2) : '—' ?></td><td class="r"><?= inr($it->taxable, 2) ?></td>
          <td class="r small"><?= (float)$it->gst_rate ?>%<br><?= $o->is_interstate ? 'IGST ' . inr($it->igst, 2) : 'C ' . inr($it->cgst, 2) . '<br>S ' . inr($it->sgst, 2) ?></td>
          <td class="r"><strong><?= inr($it->line_total, 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </table></div>
      <table class="tbl" style="max-width:380px;margin:10px 0 0 auto">
        <tr><td>Items total (MRP-inclusive selling price)</td><td class="r"><?= inr($o->subtotal, 2) ?></td></tr>
        <?php if ($o->discount > 0): ?><tr><td>Coupon <?= html_escape($o->coupon_code) ?></td><td class="r">−<?= inr($o->discount, 2) ?></td></tr><?php endif; ?>
        <tr><td>Shipping<?= $o->shipping > 0 ? ' (GST ' . $ship_rate . '% incl.)' : '' ?></td><td class="r"><?= inr($o->shipping, 2) ?></td></tr>
        <tr><td>Taxable value</td><td class="r"><?= inr($o->taxable_total, 2) ?></td></tr>
        <?php if ($o->is_interstate): ?><tr><td>IGST</td><td class="r"><?= inr($o->igst, 2) ?></td></tr>
        <?php else: ?><tr><td>CGST</td><td class="r"><?= inr($o->cgst, 2) ?></td></tr><tr><td>SGST</td><td class="r"><?= inr($o->sgst, 2) ?></td></tr><?php endif; ?>
        <tr><td><strong>Grand total</strong></td><td class="r"><strong><?= inr($o->grand_total, 2) ?></strong></td></tr>
        <?php if ($o->paid_amount > 0): ?><tr><td>Paid</td><td class="r"><?= inr($o->paid_amount, 2) ?></td></tr><?php endif; ?>
        <?php if ($paid_refunded > 0): ?><tr><td>Refunded</td><td class="r">−<?= inr($paid_refunded, 2) ?></td></tr><?php endif; ?>
      </table>
    </div>

    <div class="card">
      <h3>Actions</h3>
      <?php if (!$next && !$can_return): ?><p class="muted">This order is closed (<?= Webstore_model::status_label($o->order_status) ?>). No further status changes.</p><?php endif; ?>
      <?php foreach ($next as $to): if ($to === 'cancelled' || $to === 'rejected') continue; ?>
        <?php if ($to === 'shipped'): ?>
        <details class="act" <?= in_array($o->order_status, ['packed', 'processing'], true) ? 'open' : '' ?>><summary>Mark shipped (with courier details)</summary>
          <?= form_open('webadmin/order_status/' . $o->id) ?><input type="hidden" name="to" value="shipped">
          <div class="row" style="margin-top:8px"><div><label>Courier</label><input name="courier" value="<?= html_escape($o->courier) ?>" maxlength="80"></div><div><label>Tracking no.</label><input name="tracking_no" value="<?= html_escape($o->tracking_no) ?>" maxlength="80"></div></div>
          <div class="f" style="margin-top:8px"><label>Tracking URL (optional)</label><input name="tracking_url" type="url" value="<?= html_escape($o->tracking_url) ?>"></div>
          <button class="btn btn-primary">Mark shipped</button></form>
        </details>
        <?php else: ?>
        <?= form_open('webadmin/order_status/' . $o->id, ['style' => 'display:inline-block;margin:0 6px 10px 0', 'data-confirm' => $labels[$to] . '?']) ?><input type="hidden" name="to" value="<?= $to ?>"><button class="btn <?= $to === 'confirmed' ? 'btn-primary' : 'btn-gold' ?>"><?= $labels[$to] ?></button></form>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php foreach (['rejected', 'cancelled'] as $to): if (!in_array($to, $next, true)) continue; ?>
      <details class="act"><summary style="color:var(--danger)"><?= $labels[$to] ?> (stock is returned to inventory)</summary>
        <?= form_open('webadmin/order_status/' . $o->id, ['data-confirm' => $labels[$to] . '? Stock will be restored.']) ?><input type="hidden" name="to" value="<?= $to ?>">
        <div class="f" style="margin-top:8px"><label>Reason (required, shown to customer)</label><input name="reason" required maxlength="255"></div>
        <button class="btn btn-danger"><?= $labels[$to] ?></button></form>
      </details>
      <?php endforeach; ?>

      <?php if ($o->payment_status !== 'paid' && !in_array($o->payment_status, ['refunded', 'partially_refunded'], true) && !in_array($o->order_status, ['cancelled', 'rejected', 'pending_payment'], true)): ?>
      <details class="act" <?= ($o->payment_method === 'upi' || ($o->payment_method === 'cod' && $o->order_status === 'delivered')) ? 'open' : '' ?>>
        <summary><?= $o->payment_method === 'cod' ? 'Record COD collection' : 'Mark payment received' ?></summary>
        <?php if ($o->payment_method === 'cod' && $o->order_status !== 'delivered'): ?><p class="small muted">Available once the order is delivered.</p><?php else: ?>
        <?= form_open('webadmin/order_payment/' . $o->id) ?>
        <?php if ($o->payment_ref): ?><p class="small">Customer reference: <b><?= html_escape($o->payment_ref) ?></b></p><?php endif; ?>
        <div class="row" style="margin-top:8px"><div><label>Amount received</label><input name="amount" type="number" step="0.01" value="<?= number_format($o->grand_total, 2, '.', '') ?>" readonly></div><div><label>Reference (UTR / receipt no.)</label><input name="reference" maxlength="100" value="<?= html_escape($o->payment_ref) ?>"></div></div>
        <button class="btn btn-primary" style="margin-top:8px">Confirm payment received</button></form>
        <?php endif; ?>
      </details>
      <?php endif; ?>

      <?php if ($o->courier || in_array($o->order_status, ['shipped', 'delivered'], true)): ?>
      <details class="act"><summary>Edit courier / tracking</summary>
        <?= form_open('webadmin/order_tracking/' . $o->id) ?>
        <div class="row" style="margin-top:8px"><div><label>Courier</label><input name="courier" value="<?= html_escape($o->courier) ?>" maxlength="80"></div><div><label>Tracking no.</label><input name="tracking_no" value="<?= html_escape($o->tracking_no) ?>" maxlength="80"></div></div>
        <div class="f" style="margin-top:8px"><label>Tracking URL</label><input name="tracking_url" type="url" value="<?= html_escape($o->tracking_url) ?>"></div>
        <button class="btn">Save</button></form>
      </details>
      <?php endif; ?>

      <?php if ($can_return): ?>
      <details class="act"><summary>Record return</summary>
        <?= form_open('webadmin/order_return/' . $o->id, ['data-confirm' => 'Record this return?']) ?>
        <table class="tbl" style="margin-top:8px"><?php foreach ($items as $it): $max = $it->qty - $it->returned_qty; if ($max <= 0) continue; ?>
          <tr><td><?= html_escape($it->product_name) ?> <span class="small muted"><?= html_escape($it->size_name . ' / ' . $it->colour_name) ?></span></td><td style="width:110px"><input type="number" name="qty[<?= $it->id ?>]" min="0" max="<?= $max ?>" value="0" aria-label="Return quantity"></td><td class="small muted">of <?= $max ?></td></tr>
        <?php endforeach; ?></table>
        <div class="f"><label>Reason</label><input name="reason" required maxlength="255"></div>
        <label class="check"><input type="checkbox" name="restock" value="1" checked> Put returned items back into sellable stock</label>
        <button class="btn btn-gold" style="margin-top:8px">Record return</button></form>
      </details>
      <?php endif; ?>

      <?php if ($o->paid_amount > 0): ?>
      <details class="act"><summary>Record refund</summary>
        <?= form_open('webadmin/order_refund/' . $o->id) ?>
        <div class="row" style="margin-top:8px">
          <div><label>Amount</label><input name="amount" type="number" step="0.01" min="0.01" max="<?= number_format($o->paid_amount - $paid_refunded, 2, '.', '') ?>" required></div>
          <div><label>Method</label><select name="method"><option>UPI</option><option>Bank transfer</option><option>Cash</option><option>Razorpay</option><option>Store credit</option></select></div>
          <div><label>Status</label><select name="status"><option value="pending">Pending</option><option value="processed">Processed</option></select></div>
        </div>
        <div class="row" style="margin-top:8px"><div><label>Reference</label><input name="reference" maxlength="100"></div><div><label>Note</label><input name="note" maxlength="255"></div></div>
        <button class="btn btn-gold" style="margin-top:8px">Save refund</button></form>
      </details>
      <?php endif; ?>
      <?php if ($refunds): ?>
      <h3 style="margin-top:14px">Refunds</h3>
      <table class="tbl"><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Reference</th><th></th></tr>
        <?php foreach ($refunds as $r): ?><tr><td class="small"><?= date('d M Y', strtotime($r->created_at)) ?><br><?= html_escape($r->user_name) ?></td><td><?= inr($r->amount, 2) ?></td><td><?= html_escape($r->method) ?></td><td><?= html_escape(ucfirst($r->status)) ?></td>
          <td colspan="2"><?= form_open('webadmin/refund_update/' . $r->id, ['class' => 'row']) ?><input name="reference" value="<?= html_escape($r->reference) ?>" maxlength="100" aria-label="Reference"><select name="status" aria-label="Status"><?php foreach (['pending', 'processed', 'failed'] as $s): ?><option value="<?= $s ?>" <?= $r->status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select><button class="btn btn-sm" style="flex:0">Update</button></form></td></tr><?php endforeach; ?>
      </table>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>History &amp; audit trail</h3>
      <ul class="timeline">
        <?php foreach ($history as $h): ?>
        <li><strong><?= html_escape(ucfirst($h->action)) ?></strong><?= $h->to_status && $h->from_status !== $h->to_status ? ' → ' . html_escape(Webstore_model::status_label($h->to_status)) : '' ?>
          <span class="small muted"> · <?= date('d M Y, h:i A', strtotime($h->created_at)) ?> · <?= html_escape($h->user_name) ?></span>
          <?php if ($h->note): ?><div class="small"><?= html_escape($h->note) ?></div><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div>
    <div class="card">
      <h3>Customer</h3>
      <p style="margin:0"><strong><?= html_escape($o->customer_name) ?></strong><br>
      <a href="tel:<?= html_escape($o->phone) ?>"><?= html_escape($o->phone) ?></a><?= $o->email ? '<br>' . html_escape($o->email) : '' ?></p>
      <?php if ($o->customer_id): ?><p class="small"><a href="<?= site_url('webadmin/customer/' . $o->customer_id) ?>"><?= (int)$cust_orders ?> web order(s) by this customer</a></p><?php endif; ?>
      <h3 style="margin-top:14px">Delivery address</h3>
      <p style="margin:0"><?= html_escape($o->address_line) ?><?= $o->landmark ? '<br>Landmark: ' . html_escape($o->landmark) : '' ?><br><?= html_escape($o->city) ?>, <?= html_escape($o->state) ?> (<?= html_escape($o->state_code) ?>)<br>PIN <?= html_escape($o->pincode) ?></p>
      <p class="small muted"><?= $o->is_interstate ? 'Inter-state supply → IGST' : 'Intra-state supply → CGST + SGST' ?></p>
      <?php if ($o->customer_notes): ?><h3 style="margin-top:14px">Customer note</h3><p style="margin:0"><?= nl2br(html_escape($o->customer_notes)) ?></p><?php endif; ?>
    </div>
    <div class="card">
      <h3>Payment</h3>
      <p style="margin:0"><?= html_escape(['cod' => 'Cash on Delivery', 'upi' => 'UPI (manual verification)', 'razorpay' => 'Razorpay'][$o->payment_method]) ?><br><?= order_badge($o->payment_status) ?></p>
      <?php if ($o->payment_ref): ?><p class="small">Ref: <?= html_escape($o->payment_ref) ?></p><?php endif; ?>
      <?php if ($o->gateway_order_id): ?><p class="small">Gateway order: <?= html_escape($o->gateway_order_id) ?><br>Payment ID: <?= html_escape($o->gateway_payment_id ?: '—') ?></p><?php endif; ?>
      <?php if ($o->courier || $o->tracking_no): ?><h3 style="margin-top:14px">Shipment</h3><p style="margin:0"><?= html_escape($o->courier) ?> · <?= html_escape($o->tracking_no) ?><?php if ($o->tracking_url): ?><br><a href="<?= html_escape($o->tracking_url) ?>" target="_blank" rel="noopener">Tracking link</a><?php endif; ?></p><?php endif; ?>
      <p class="small muted">Stock: <?= html_escape($o->stock_state) ?> at location #<?= (int)$o->location_id ?></p>
    </div>
    <div class="card">
      <h3>Admin notes</h3>
      <?php if ($o->admin_notes): ?><pre style="white-space:pre-wrap;font:inherit;font-size:13px;margin:0 0 10px"><?= html_escape($o->admin_notes) ?></pre><?php endif; ?>
      <?= form_open('webadmin/order_note/' . $o->id) ?><textarea name="note" rows="2" maxlength="500" aria-label="New note" placeholder="Add an internal note"></textarea><button class="btn" style="margin-top:6px">Add note</button></form>
    </div>
  </div>
</div>
