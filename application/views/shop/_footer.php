<?php
$store_disp = ucwords(strtolower($company['company_name'] ?? 'Suvasan Clothes'));
$socials = array_filter(['Facebook' => $company['web_social_facebook'] ?? '', 'Instagram' => $company['web_social_instagram'] ?? '', 'YouTube' => $company['web_social_youtube'] ?? '']);
$wa = preg_replace('/\D/', '', $company['web_whatsapp'] ?? '');
?>
</main>
<footer class="site-footer">
  <div class="wrap fgrid">
    <div>
      <h4><?= html_escape($store_disp) ?></h4>
      <p style="margin:0 0 8px;color:var(--gold-l);font-family:var(--serif);font-size:18px;font-style:italic">Grace in Every Thread</p>
      <p style="margin:0">Ethnic and western wear for the whole family, from our store in Mathura.</p>
      <?php if ($socials || $wa): ?>
      <div class="social">
        <?php foreach ($socials as $name => $url): ?>
        <a href="<?= html_escape($url) ?>" target="_blank" rel="noopener" aria-label="<?= $name ?>"><svg viewBox="0 0 24 24" fill="currentColor"><?php if ($name === 'Facebook'): ?><path d="M14 8h3V4h-3c-2.8 0-4 1.8-4 4.3V10H7v4h3v7h4v-7h3l1-4h-4V8.5c0-.4.3-.5.6-.5z"/><?php elseif ($name === 'Instagram'): ?><path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm5 5a4 4 0 1 0 0 8 4 4 0 0 0 0-8zm5.5-1.5a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/><?php else: ?><path d="M22 8.2c-.2-1.6-1-2.6-2.6-2.8C17 5 12 5 12 5s-5 0-7.4.4C3 5.6 2.2 6.6 2 8.2 1.8 10 1.8 12 1.8 12s0 2 .2 3.8c.2 1.6 1 2.6 2.6 2.8C7 19 12 19 12 19s5 0 7.4-.4c1.6-.2 2.4-1.2 2.6-2.8.2-1.8.2-3.8.2-3.8s0-2-.2-3.8zM10 15V9l5 3z"/><?php endif; ?></svg></a>
        <?php endforeach; ?>
        <?php if ($wa): ?><a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.1-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.6-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3z"/></svg></a><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <div>
      <h4>Shop</h4>
      <ul>
        <li><a href="<?= site_url('shop?sort=newest') ?>">New Arrivals</a></li>
        <?php foreach (array_slice($nav['categories'] ?? [], 0, 5) as $c): ?><li><a href="<?= category_url($c) ?>"><?= html_escape($c->name) ?></a></li><?php endforeach; ?>
        <li><a href="<?= site_url('shop?sale=1') ?>">Offers</a></li>
      </ul>
    </div>
    <div>
      <h4>Help</h4>
      <ul>
        <li><a href="<?= site_url('track') ?>">Track Order</a></li>
        <li><a href="<?= site_url('page/shipping-policy') ?>">Shipping Policy</a></li>
        <li><a href="<?= site_url('page/return-policy') ?>">Returns &amp; Refunds</a></li>
        <li><a href="<?= site_url('page/size-guide') ?>">Size Guide</a></li>
        <li><a href="<?= site_url('page/faq') ?>">FAQ</a></li>
        <li><a href="<?= site_url('page/privacy-policy') ?>">Privacy Policy</a></li>
        <li><a href="<?= site_url('page/terms') ?>">Terms &amp; Conditions</a></li>
      </ul>
    </div>
    <div>
      <h4>Visit Us</h4>
      <address style="font-style:normal">
        <?= nl2br(html_escape(str_replace('\n', "\n", $company['company_address'] ?? ''))) ?><br>
        <?php if (!empty($company['company_phone'])): ?>Phone: <a href="tel:<?= html_escape(preg_replace('/[^\d+]/', '', $company['company_phone'])) ?>"><?= html_escape($company['company_phone']) ?></a><br><?php endif; ?>
        <?php if (!empty($company['company_email'])): ?>Email: <a href="mailto:<?= html_escape($company['company_email']) ?>"><?= html_escape($company['company_email']) ?></a><br><?php endif; ?>
      </address>
      <?php if (!empty($company['company_gstin'])): ?><p style="margin:10px 0 0;font-size:13px">GSTIN: <?= html_escape($company['company_gstin']) ?></p><?php endif; ?>
    </div>
  </div>
  <div class="wrap legal">
    <span>© <?= date('Y') ?> <?= html_escape($store_disp) ?>. All rights reserved.</span>
    <span>All prices in ₹ INR, inclusive of GST.</span>
  </div>
</footer>
<script src="<?= base_url('assets/web/shop.js?v=20261010') ?>" defer></script>
</body>
</html>
