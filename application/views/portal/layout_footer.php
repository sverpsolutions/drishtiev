</div><!-- /bb-page-content -->

<!-- ══ BOTTOM NAVIGATION ════════════════════════════════════ -->
<?php
$cur = uri_string();
$_footer_logged_in = (bool)$this->session->userdata('portal_customer_id');
$fn_cart = $this->session->userdata('portal_cart') ?? [];
$fn_count = 0; foreach($fn_cart as $fi) { $fn_count += $fi['qty']; }
?>
<nav class="bb-bottom-nav">
  <?php if ($_footer_logged_in): ?>
  <a href="<?= base_url('portal/dashboard') ?>" class="bb-nav-item <?= strpos($cur,'portal/dashboard')!==false?'active':'' ?>">
    <i class="fa fa-home"></i><span>Home</span>
  </a>
  <?php else: ?>
  <a href="<?= base_url('portal/products') ?>" class="bb-nav-item <?= (strpos($cur,'portal/products')!==false || $cur==='portal')?'active':'' ?>">
    <i class="fa fa-home"></i><span>Home</span>
  </a>
  <?php endif; ?>

  <a href="<?= base_url('portal/products') ?>" class="bb-nav-item <?= strpos($cur,'portal/products')!==false?'active':'' ?>">
    <i class="fa fa-th-large"></i><span>Shop</span>
  </a>

  <a href="<?= base_url('portal/order') ?>" class="bb-nav-item <?= (strpos($cur,'portal/order')!==false&&strpos($cur,'my_orders')===false)?'active':'' ?>" style="position:relative;">
    <i class="fa fa-shopping-cart"></i><span>Cart</span>
    <span class="bb-nav-cart-badge" id="bottomNavCartBadge" <?= $fn_count > 0 ? '' : 'style="display:none"' ?>><?= $fn_count ?></span>
  </a>

  <?php if ($_footer_logged_in): ?>
  <a href="<?= base_url('portal/my_orders') ?>" class="bb-nav-item <?= strpos($cur,'portal/my_orders')!==false?'active':'' ?>">
    <i class="fa fa-clipboard-list"></i><span>Orders</span>
  </a>
  <a href="<?= base_url('portal/profile') ?>" class="bb-nav-item <?= strpos($cur,'portal/profile')!==false?'active':'' ?>">
    <i class="fa fa-user"></i><span>Profile</span>
  </a>
  <?php else: ?>
  <a href="<?= base_url('portal/login') ?>" class="bb-nav-item <?= strpos($cur,'portal/login')!==false?'active':'' ?>">
    <i class="fa fa-sign-in"></i><span>Login</span>
  </a>
  <a href="<?= base_url('portal/register') ?>" class="bb-nav-item <?= strpos($cur,'portal/register')!==false?'active':'' ?>">
    <i class="fa fa-user-plus"></i><span>Sign Up</span>
  </a>
  <?php endif; ?>
</nav>

<script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script>
(function() {
    var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
    var csrfHash = '<?= $this->security->get_csrf_hash() ?>';
    document.querySelectorAll('form').forEach(function(form) {
        if (!form.querySelector('[name="' + csrfName + '"]')) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = csrfName; inp.value = csrfHash;
            form.appendChild(inp);
        }
    });
    window._csrfName = csrfName; window._csrfHash = csrfHash;
})();
/* Shared cart badge update function (called by products/cart pages) */
function bbUpdateCartBadge(count) {
  var topBadge    = document.getElementById('topCartBadge');
  var bottomBadge = document.getElementById('bottomNavCartBadge');
  if (topBadge) {
    topBadge.textContent = count;
    topBadge.style.display = count > 0 ? 'flex' : 'none';
  }
  if (bottomBadge) {
    bottomBadge.textContent = count;
    bottomBadge.style.display = count > 0 ? 'flex' : 'none';
  }
}
</script>
</body>
</html>
