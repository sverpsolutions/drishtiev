</div><!-- /st-page-content -->

<!-- ══ BOTTOM NAVIGATION ════════════════════════════════════ -->
<?php
$cur = uri_string();
$_footer_logged_in = (bool)$this->session->userdata('store_customer_id');
$fn_cart = $this->session->userdata('store_cart') ?? [];
$fn_count = 0; foreach ($fn_cart as $fi) $fn_count += $fi['qty'];
?>
<nav class="st-bottom-nav">
  <a href="<?= base_url('store') ?>" class="st-nav-item <?= ($cur === 'store' || strpos($cur, 'store/home') !== false) ? 'active' : '' ?>">
    <i class="fa fa-home"></i><span>Home</span>
  </a>
  <a href="<?= base_url('store/products') ?>" class="st-nav-item <?= strpos($cur, 'store/products') !== false ? 'active' : '' ?>">
    <i class="fa fa-th-large"></i><span>Categories</span>
  </a>
  <a href="<?= base_url('store/cart') ?>" class="st-nav-item st-nav-cart <?= strpos($cur, 'store/cart') !== false ? 'active' : '' ?>">
    <i class="fa fa-shopping-bag"></i><span>Cart</span>
    <span class="st-nav-cart-badge" id="stBottomCartBadge" <?= $fn_count > 0 ? '' : 'style="display:none"' ?>><?= $fn_count ?></span>
  </a>
  <?php if ($_footer_logged_in): ?>
  <a href="<?= base_url('store/my_orders') ?>" class="st-nav-item <?= strpos($cur, 'store/my_orders') !== false ? 'active' : '' ?>">
    <i class="fa fa-clipboard-list"></i><span>Orders</span>
  </a>
  <a href="<?= base_url('store/profile') ?>" class="st-nav-item <?= strpos($cur, 'store/profile') !== false ? 'active' : '' ?>">
    <i class="fa fa-user"></i><span>Account</span>
  </a>
  <?php else: ?>
  <a href="<?= base_url('store/login') ?>" class="st-nav-item <?= strpos($cur, 'store/login') !== false ? 'active' : '' ?>">
    <i class="fa fa-sign-in"></i><span>Login</span>
  </a>
  <?php endif; ?>
</nav>

<script>
(function () {
    var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
    var csrfHash = '<?= $this->security->get_csrf_hash() ?>';
    document.querySelectorAll('form').forEach(function (form) {
        if (!form.querySelector('[name="' + csrfName + '"]')) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = csrfName; inp.value = csrfHash;
            form.appendChild(inp);
        }
    });
    window._stCsrfName = csrfName;
    window._stCsrfHash = csrfHash;
})();
function stUpdateCartBadge(count) {
    ['stTopCartBadge', 'stBottomCartBadge'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = count;
        el.style.display = count > 0 ? 'flex' : 'none';
    });
}
</script>
<script src="<?= base_url('assets/js/store-main.js') ?>"></script>
</body>
</html>
