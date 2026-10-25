/**
 * store-main.js — shared cart/toast helpers for the Store storefront.
 * Loaded on every /store page (from layout_footer.php). Vanilla JS, no jQuery.
 */
(function (global) {
    'use strict';

    function base(path) {
        // Resolve relative to the site root the page itself was served from —
        // works both locally (/ncg/store/...) and in production (/store/...).
        var scriptBase = window.location.origin + window.location.pathname.split('/store')[0] + '/';
        return scriptBase + path;
    }

    function toast(msg) {
        var el = document.getElementById('stToastEl');
        if (!el) {
            el = document.createElement('div');
            el.id = 'stToastEl';
            el.className = 'st-toast';
            document.body.appendChild(el);
        }
        el.textContent = msg;
        el.classList.add('show');
        clearTimeout(el._t);
        el._t = setTimeout(function () { el.classList.remove('show'); }, 1800);
    }

    // These cart AJAX endpoints are listed in csrf_exclude_uris (config.php),
    // matching every other tokenless AJAX endpoint in this app (including the
    // old portal's identical cart calls) — no CSRF token needed here.
    function postForm(url, data) {
        var body = Object.keys(data).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
        }).join('&');
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: body,
        }).then(function (r) { return r.json(); });
    }

    function addToCart(productId, onDone) {
        postForm(base('store/add_to_cart_ajax'), { product_id: productId }).then(function (res) {
            if (res.status === 'success') {
                stUpdateCartBadge(res.cart_count);
                toast('Added to cart');
            } else {
                toast(res.message || 'Could not add item');
            }
            if (onDone) onDone(res);
        }).catch(function () { toast('Network error — try again'); });
    }

    function updateCartQty(productId, qty, onDone) {
        postForm(base('store/update_cart_ajax'), { product_id: productId, qty: qty }).then(function (res) {
            if (res.status === 'success') stUpdateCartBadge(res.cart_count);
            if (onDone) onDone(res);
        }).catch(function () { toast('Network error — try again'); });
    }

    // ── Product card stepper wiring — cards render a "+ ADD" button; once
    // clicked it becomes a qty stepper. Works for any .st-pcard on the page.
    function wireProductCard(card) {
        var pid = card.getAttribute('data-pid');
        var addBar = card.querySelector('.st-pcard-addbar');
        if (!pid || !addBar) return;

        function renderStepper(qty) {
            if (qty <= 0) {
                addBar.innerHTML = '<button type="button" class="st-pcard-add-btn">ADD</button>';
                addBar.querySelector('button').addEventListener('click', function () {
                    addToCart(pid, function (res) { renderStepper(res.qty || 1); });
                });
            } else {
                addBar.innerHTML = '<div class="st-pcard-stepper"><button type="button" data-d="-1">−</button><span>' + qty + '</span><button type="button" data-d="1">+</button></div>';
                addBar.querySelectorAll('button').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var delta = parseInt(btn.getAttribute('data-d'), 10);
                        var newQty = Math.max(0, qty + delta);
                        updateCartQty(pid, newQty, function () { renderStepper(newQty); });
                    });
                });
            }
        }
        var initialQty = parseInt(card.getAttribute('data-qty') || '0', 10);
        renderStepper(initialQty);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.st-pcard[data-pid]').forEach(wireProductCard);
    });

    global.stToast = toast;
    global.stAddToCart = addToCart;
    global.stUpdateCartQty = updateCartQty;
    global.stBase = base;
    global.stPostForm = postForm;
    global.stWireProductCard = wireProductCard;
})(window);
