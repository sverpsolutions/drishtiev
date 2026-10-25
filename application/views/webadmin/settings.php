<?php
$v = function ($k, $d = '') use ($s) { return html_escape($s[$k] ?? $d); };
$chk = function ($k) use ($s) { return ($s[$k] ?? '0') === '1' ? 'checked' : ''; };
$secret_set = function ($k) use ($s) { return !empty($s[$k]) ? 'Saved — leave blank to keep' : 'Not set'; };
?>
<?= form_open('webadmin/settings') ?>
<div class="grid g2">
  <div class="card">
    <h3>Business &amp; contact</h3>
    <p class="small muted">Registered name, address and GSTIN come from the GST certificate and are edited in <a href="<?= site_url('settings') ?>">ERP Settings</a>.</p>
    <table class="tbl small"><tr><td>Trade name</td><td><?= $v('company_name') ?></td></tr><tr><td>GSTIN</td><td><?= $v('company_gstin') ?></td></tr><tr><td>State</td><td><?= $v('company_state') ?> (<?= $v('company_state_code') ?>)</td></tr></table>
    <div class="row" style="margin-top:10px"><div><label>Phone shown on website</label><input name="company_phone" maxlength="30" value="<?= $v('company_phone') ?>"></div><div><label>Email shown on website</label><input name="company_email" maxlength="120" value="<?= $v('company_email') ?>"></div></div>
    <div class="row" style="margin-top:10px"><div><label>WhatsApp number (with country code, e.g. 919876543210)</label><input name="web_whatsapp" maxlength="15" value="<?= $v('web_whatsapp') ?>"></div></div>
    <div class="row" style="margin-top:10px"><div><label>Facebook URL</label><input name="web_social_facebook" value="<?= $v('web_social_facebook') ?>"></div><div><label>Instagram URL</label><input name="web_social_instagram" value="<?= $v('web_social_instagram') ?>"></div><div><label>YouTube URL</label><input name="web_social_youtube" value="<?= $v('web_social_youtube') ?>"></div></div>
  </div>
  <div class="card">
    <h3>Website</h3>
    <div class="f"><label>Announcement bar text</label><input name="web_announcement" maxlength="255" value="<?= $v('web_announcement') ?>"></div>
    <div class="f"><label>SEO title (home page)</label><input name="web_meta_title" maxlength="120" value="<?= $v('web_meta_title') ?>"></div>
    <div class="f"><label>SEO description</label><input name="web_meta_description" maxlength="255" value="<?= $v('web_meta_description') ?>"></div>
    <label class="check"><input type="checkbox" name="web_show_stock_count" value="1" <?= $chk('web_show_stock_count') ?>> Show "only N left" on product pages</label>
    <label class="check" style="margin-top:8px"><input type="checkbox" name="web_maintenance" value="1" <?= $chk('web_maintenance') ?>> <strong>Maintenance mode</strong> (store closed to the public; staff logged in to the ERP still see it)</label>
  </div>
  <div class="card">
    <h3>Orders, shipping &amp; stock</h3>
    <div class="row"><div><label>Order number prefix</label><input name="web_order_prefix" maxlength="10" value="<?= $v('web_order_prefix', 'SVC') ?>"></div><div><label>Invoice number prefix</label><input name="web_invoice_prefix" maxlength="10" value="<?= $v('web_invoice_prefix', 'SVW') ?>"></div></div>
    <p class="small muted">Orders: PREFIX + date + id (e.g. SVC261010-12). Invoices: PREFIX/FY/sequence (e.g. SVW/26-27/0001), issued when an order is confirmed.</p>
    <div class="row"><div><label>Shipping charge ₹ (GST-inclusive, 0 = free)</label><input type="number" step="0.01" min="0" name="web_shipping_charge" value="<?= $v('web_shipping_charge', 0) ?>"></div><div><label>Free shipping above ₹ (0 = never)</label><input type="number" step="0.01" min="0" name="web_free_shipping_above" value="<?= $v('web_free_shipping_above', 0) ?>"></div></div>
    <div class="row" style="margin-top:10px"><div><label>Minimum order value ₹</label><input type="number" step="0.01" min="0" name="web_min_order" value="<?= $v('web_min_order', 0) ?>"></div><div><label>Return window (days, 0 = see policy)</label><input type="number" min="0" name="web_return_days" value="<?= $v('web_return_days', 0) ?>"></div></div>
    <div class="row" style="margin-top:10px"><div><label>Sell stock from location</label><select name="web_location_id"><?php foreach ($locations as $l): ?><option value="<?= $l->id ?>" <?= (int)($s['web_location_id'] ?? 1) === (int)$l->id ? 'selected' : '' ?>><?= html_escape($l->name) ?></option><?php endforeach; ?></select></div>
      <div><label>Cancel unpaid online-payment orders after (minutes)</label><input type="number" min="5" name="web_pending_payment_minutes" value="<?= $v('web_pending_payment_minutes', 30) ?>"></div></div>
    <div class="f" style="margin-top:10px"><label>New-order notification email (uses the ERP SMTP settings)</label><input type="email" name="web_notify_email" maxlength="120" value="<?= $v('web_notify_email') ?>"></div>
    <p class="small muted">SMTP is <?= empty($s['smtp_user']) || empty($s['smtp_pass']) ? '<strong>not configured</strong> — emails are skipped; new orders still appear instantly in the admin with a badge.' : 'configured.' ?></p>
  </div>
  <div class="card">
    <h3>GST</h3>
    <p class="small muted">Each product's GST rate comes from the product master (HSN). Selling prices are GST-inclusive. Intra-state orders (delivery in <?= $v('company_state') ?>) get CGST+SGST; others get IGST.</p>
    <label class="check"><input type="checkbox" name="web_gst_slab_enabled" value="1" <?= $chk('web_gst_slab_enabled') ?>> Apply value-based apparel slab to garment products</label>
    <div class="row" style="margin-top:8px"><div><label>Per-piece value up to ₹ (excl. GST)</label><input type="number" step="0.01" name="web_gst_slab_threshold" value="<?= $v('web_gst_slab_threshold', 2500) ?>"></div><div><label>Rate at or below (%)</label><input type="number" step="0.01" name="web_gst_slab_low" value="<?= $v('web_gst_slab_low', 5) ?>"></div><div><label>Rate above (%)</label><input type="number" step="0.01" name="web_gst_slab_high" value="<?= $v('web_gst_slab_high', 18) ?>"></div></div>
    <p class="small muted">Verify these values with your tax advisor whenever GST rates change.</p>
  </div>
  <div class="card">
    <h3>Payment methods</h3>
    <label class="check"><input type="checkbox" name="web_cod_enabled" value="1" <?= $chk('web_cod_enabled') ?>> Cash on Delivery</label>
    <div class="f" style="margin:6px 0 12px"><label>COD maximum order value ₹ (0 = no limit)</label><input type="number" step="0.01" min="0" name="web_cod_max" value="<?= $v('web_cod_max', 0) ?>"></div>
    <label class="check"><input type="checkbox" name="web_upi_enabled" value="1" <?= $chk('web_upi_enabled') ?>> UPI to store UPI ID (staff verifies each payment manually)</label>
    <div class="f" style="margin:6px 0 12px"><label>Store UPI ID</label><input name="web_upi_id" maxlength="80" value="<?= $v('web_upi_id') ?>" placeholder="name@bank"></div>
    <label class="check"><input type="checkbox" name="web_razorpay_enabled" value="1" <?= $chk('web_razorpay_enabled') ?>> Razorpay (cards / UPI / netbanking, auto-verified)</label>
    <div class="row" style="margin-top:6px"><div><label>Key ID</label><input name="web_razorpay_key_id" maxlength="80" value="<?= $v('web_razorpay_key_id') ?>"></div><div><label>Key secret (<?= $secret_set('web_razorpay_key_secret') ?>)</label><input type="password" name="web_razorpay_key_secret" autocomplete="new-password"></div></div>
    <div class="f" style="margin-top:6px"><label>Webhook secret (<?= $secret_set('web_razorpay_webhook_secret') ?>)</label><input type="password" name="web_razorpay_webhook_secret" autocomplete="new-password"></div>
    <p class="small muted">Webhook URL for the Razorpay dashboard: <code><?= site_url('webhook/razorpay') ?></code> (events: payment.captured, order.paid, payment.failed). A method only shows at checkout when it is enabled and fully configured.</p>
  </div>
</div>
<button class="btn btn-primary" style="padding:12px 28px">Save settings</button>
</form>
