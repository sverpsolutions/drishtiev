/**
 * store-cart.js — quantity steppers + live totals on the /store/cart page.
 * Cart rows here use a different (full-width row) markup than product-card
 * steppers, so this has its own small wiring rather than reusing stWireProductCard.
 * Updates happen in place via get_cart_ajax — no full page reload.
 */
(function () {
    'use strict';
    var list = document.getElementById('stCartList');
    var subtotalEl = document.getElementById('stCartSubtotal');
    var checkoutTotalEl = document.getElementById('stCheckoutTotal');
    var checkoutBar = document.getElementById('stCheckoutBar');
    if (!list) return;

    function rowHtml(item) {
        var img = item.image ? '<img src="' + stBase('assets/uploads/products/' + item.image) + '" alt="">' : '<div class="st-pcard-placeholder">🛒</div>';
        return '' +
            '<div class="st-cart-row" data-pid="' + item.id + '">' +
              '<div class="st-cart-thumb">' + img + '</div>' +
              '<div class="st-cart-info">' +
                '<div class="st-cart-name">' + item.name + '</div>' +
                '<div class="st-cart-unit">' + (item.unit || '') + '</div>' +
                '<div class="st-cart-price">₹' + item.price.toFixed(2) + '</div>' +
              '</div>' +
              '<div class="st-pcard-stepper" style="width:96px;">' +
                '<button type="button" data-action="dec">−</button>' +
                '<span class="st-qty-val">' + item.qty + '</span>' +
                '<button type="button" data-action="inc">+</button>' +
              '</div>' +
              '<button type="button" data-action="remove" class="st-icon-btn" style="color:#c62828;"><i class="fa fa-trash"></i></button>' +
            '</div>';
    }

    function loadCart() {
        fetch(stBase('store/get_cart_ajax'), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                stUpdateCartBadge(res.cart_count);
                if (!res.items.length) {
                    list.innerHTML = '<div class="st-empty"><i class="fa fa-shopping-bag"></i><div>Your cart is empty</div></div>';
                    if (checkoutBar) checkoutBar.style.display = 'none';
                    if (subtotalEl) subtotalEl.textContent = '₹0.00';
                    if (checkoutTotalEl) checkoutTotalEl.textContent = '₹0.00';
                    return;
                }
                list.innerHTML = res.items.map(rowHtml).join('');
                if (subtotalEl) subtotalEl.textContent = '₹' + res.total.toFixed(2);
                if (checkoutTotalEl) checkoutTotalEl.textContent = '₹' + res.total.toFixed(2);
                if (checkoutBar) checkoutBar.style.display = 'flex';
            });
    }

    list.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-action]');
        if (!btn) return;
        var row = btn.closest('.st-cart-row');
        var pid = row.getAttribute('data-pid');
        var action = btn.getAttribute('data-action');

        if (action === 'remove') {
            stPostForm(stBase('store/remove_from_cart_ajax'), { product_id: pid }).then(loadCart);
            return;
        }
        var qtyEl = row.querySelector('.st-qty-val');
        var qty = parseInt(qtyEl.textContent, 10) || 1;
        qty = action === 'inc' ? qty + 1 : Math.max(0, qty - 1);
        stUpdateCartQty(pid, qty, loadCart);
    });

    document.addEventListener('DOMContentLoaded', loadCart);
})();
