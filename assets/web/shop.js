/* Suvasan Clothes storefront — no dependencies */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var cfg = window.SHOP || {};

  // ── Toast ──
  var toastEl, toastT;
  function toast(msg, link) {
    if (!toastEl) { toastEl = document.createElement('div'); toastEl.className = 'toast'; toastEl.setAttribute('role', 'status'); document.body.appendChild(toastEl); }
    toastEl.textContent = msg;
    if (link) { var a = document.createElement('a'); a.href = link[1]; a.textContent = link[0]; toastEl.appendChild(a); }
    toastEl.classList.add('show'); clearTimeout(toastT);
    toastT = setTimeout(function () { toastEl.classList.remove('show'); }, 3200);
  }
  window.shopToast = toast;

  function post(url, data) {
    var fd = new FormData();
    fd.append(cfg.csrfName, cfg.csrfHash);
    Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); });
  }
  function setCount(n) { $$('[data-cart-count]').forEach(function (el) { el.textContent = n; el.setAttribute('data-n', n); }); }

  // ── Mobile drawers ──
  var backdrop = $('.drawer-backdrop');
  function closeDrawers() { $$('.is-open').forEach(function (e) { e.classList.remove('is-open'); }); }
  $$('[data-open]').forEach(function (b) {
    b.addEventListener('click', function () { var t = $(b.getAttribute('data-open')); if (t) { t.classList.add('is-open'); backdrop && backdrop.classList.add('is-open'); } });
  });
  backdrop && backdrop.addEventListener('click', closeDrawers);
  $$('[data-close]').forEach(function (b) { b.addEventListener('click', closeDrawers); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawers(); });

  // ── Hero slider: autoplay, arrows, dots, swipe ──
  var hero = $('.hero');
  if (hero) {
    var track = $('.slides', hero), slides = $$('.slide', hero), dots = $$('.dots button', hero), i = 0, timer;
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    var go = function (n) {
      i = (n + slides.length) % slides.length;
      track.style.transform = 'translateX(' + (-100 * i) + '%)';
      dots.forEach(function (d, k) { d.setAttribute('aria-current', k === i ? 'true' : 'false'); });
      slides.forEach(function (s, k) { s.setAttribute('aria-hidden', k === i ? 'false' : 'true'); $$('a', s).forEach(function (a) { a.tabIndex = k === i ? 0 : -1; }); });
    };
    var play = function () { if (!reduce && slides.length > 1) { clearInterval(timer); timer = setInterval(function () { go(i + 1); }, 6000); } };
    var stop = function () { clearInterval(timer); };
    $('.hero-nav.prev', hero) && $('.hero-nav.prev', hero).addEventListener('click', function () { go(i - 1); play(); });
    $('.hero-nav.next', hero) && $('.hero-nav.next', hero).addEventListener('click', function () { go(i + 1); play(); });
    dots.forEach(function (d, k) { d.addEventListener('click', function () { go(k); play(); }); });
    hero.addEventListener('mouseenter', stop); hero.addEventListener('mouseleave', play);
    hero.addEventListener('focusin', stop);
    var x0 = null;
    track.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; stop(); }, { passive: true });
    track.addEventListener('touchend', function (e) {
      if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 40) go(i + (dx < 0 ? 1 : -1)); play();
    });
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : play(); });
    go(0); play();
  }

  // ── Wishlist (per-browser, localStorage) ──
  var WKEY = 'svc_wishlist';
  function wl() { try { return JSON.parse(localStorage.getItem(WKEY) || '[]'); } catch (e) { return []; } }
  function wlSave(a) { try { localStorage.setItem(WKEY, JSON.stringify(a)); } catch (e) {} paintWish(); }
  function paintWish() {
    var a = wl();
    $$('[data-wish]').forEach(function (b) {
      var on = a.indexOf(+b.getAttribute('data-wish')) > -1;
      b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    $$('[data-wish-count]').forEach(function (el) { el.textContent = a.length; el.setAttribute('data-n', a.length); });
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-wish]'); if (!b) return;
    e.preventDefault(); e.stopPropagation();
    var id = +b.getAttribute('data-wish'), a = wl(), k = a.indexOf(id);
    if (k > -1) { a.splice(k, 1); toast('Removed from wishlist'); } else { a.unshift(id); toast('Saved to wishlist', ['View', cfg.base + 'wishlist']); }
    wlSave(a.slice(0, 60));
    var box = $('#wishlist-grid'); if (box && k > -1) { var card = b.closest('.pcard'); card && card.remove(); if (!wl().length) location.reload(); }
  });
  paintWish();
  var wbox = $('#wishlist-grid');
  if (wbox) {
    var ids = wl();
    if (!ids.length) { $('#wishlist-empty').hidden = false; wbox.hidden = true; }
    else fetch(cfg.base + 'shop/products_html?ids=' + ids.join(','), { credentials: 'same-origin' }).then(function (r) { return r.json(); })
      .then(function (d) { wbox.innerHTML = d.html; if (!d.html) $('#wishlist-empty').hidden = false; paintWish(); });
  }

  // ── Search suggestions ──
  var sIn = $('#q'), sBox = $('#suggest'), sT;
  if (sIn && sBox) {
    sIn.addEventListener('input', function () {
      clearTimeout(sT); var q = sIn.value.trim();
      if (q.length < 2) { sBox.style.display = 'none'; return; }
      sT = setTimeout(function () {
        fetch(cfg.base + 'shop/suggest?q=' + encodeURIComponent(q), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
          sBox.innerHTML = '';
          d.items.forEach(function (it) {
            var a = document.createElement('a'); a.href = it.url;
            var im = it.img ? document.createElement('img') : document.createElement('span');
            if (it.img) { im.src = it.img; im.alt = ''; } else im.className = 'ph-mini';
            var t = document.createElement('span'); t.textContent = it.name;
            var p = document.createElement('b'); p.textContent = it.price; p.style.marginLeft = 'auto';
            a.appendChild(im); a.appendChild(t); a.appendChild(p); sBox.appendChild(a);
          });
          sBox.style.display = d.items.length ? 'block' : 'none';
        });
      }, 220);
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('.search')) sBox.style.display = 'none'; });
  }

  // ── Add to cart (forms with data-add-to-cart) ──
  document.addEventListener('submit', function (e) {
    var f = e.target.closest('[data-add-to-cart]'); if (!f) return;
    e.preventDefault();
    var btn = e.submitter || $('button[type=submit]', f), buyNow = btn && btn.name === 'buy_now';
    if (f.variant_id && f.hasAttribute('data-needs-variant') && !f.variant_id.value) { toast('Please select a size and colour'); return; }
    btn && btn.classList.add('is-loading');
    post(cfg.base + 'cart/add', { product_id: f.product_id.value, variant_id: f.variant_id ? f.variant_id.value : 0, qty: f.qty ? f.qty.value : 1 })
      .then(function (d) {
        if (!d.ok) { toast(d.msg || 'Could not add to cart'); return; }
        setCount(d.count);
        if (buyNow) location.href = cfg.base + 'checkout'; else toast(d.msg, ['View cart', cfg.base + 'cart']);
      })
      .catch(function () { toast('Network error — please try again'); })
      .then(function () { btn && btn.classList.remove('is-loading'); });
  });

  // ── Quantity steppers ──
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-step]'); if (!b) return;
    var inp = b.parentNode.querySelector('input'); var max = +(inp.max || 20);
    inp.value = Math.max(1, Math.min(max, (+inp.value || 1) + (+b.getAttribute('data-step'))));
  });

  // ── Auto-submit selects (sort, cart qty) ──
  $$('[data-autosubmit]').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });

  // ── Prevent double submit on plain forms ──
  $$('form[data-once]').forEach(function (f) {
    f.addEventListener('submit', function () { var b = $('button[type=submit]', f); if (b) { b.classList.add('is-loading'); b.textContent = b.getAttribute('data-loading') || 'Please wait…'; } });
  });
})();
