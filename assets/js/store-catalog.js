/**
 * store-catalog.js — live search-as-you-type for /store/products and category pages.
 */
(function () {
    'use strict';
    var input = document.getElementById('stCatalogSearch');
    var grid = document.getElementById('stCatalogGrid');
    if (!input || !grid) return;

    var timer = null;
    var categoryId = grid.getAttribute('data-category') || '';

    function pcardHtml(p) {
        var img = p.image ? '<img src="' + stBase('assets/uploads/products/' + p.image) + '" alt="">' : '<div class="st-pcard-placeholder">🛒</div>';
        var mrpHtml = p.has_disc ? '<span class="st-pcard-mrp">₹' + p.mrp.toFixed(2) + '</span>' : '';
        var discBadge = p.has_disc ? '<span class="st-pcard-discount">' + p.save_pct + '% OFF</span>' : '';
        var vegClass = p.is_veg ? '' : 'nonveg';
        var oos = p.stock <= 0 ? '<div class="st-pcard-oos">Out of Stock</div>' : '';
        return '' +
            '<div class="st-pcard" data-pid="' + p.id + '" data-qty="' + p.in_cart + '">' +
              discBadge +
              '<div class="st-pcard-veg ' + vegClass + '"></div>' +
              '<a href="' + stBase('store/product/' + p.id) + '" class="st-pcard-img-wrap">' + img + oos + '</a>' +
              '<div class="st-pcard-body">' +
                '<a href="' + stBase('store/product/' + p.id) + '"><div class="st-pcard-name">' + p.name + '</div></a>' +
                '<div class="st-pcard-unit">' + (p.unit || '') + '</div>' +
                '<div class="st-pcard-price-row"><span class="st-pcard-price">₹' + p.sp.toFixed(2) + '</span>' + mrpHtml + '</div>' +
                '<div class="st-pcard-addbar"></div>' +
              '</div>' +
            '</div>';
    }

    function render(products) {
        if (!products.length) {
            grid.innerHTML = '<div class="st-empty" style="grid-column:1/-1;"><i class="fa fa-search"></i><div>No products found</div></div>';
            return;
        }
        grid.innerHTML = products.map(pcardHtml).join('');
        grid.querySelectorAll('.st-pcard[data-pid]').forEach(stWireProductCard);
    }

    function search(q) {
        grid.innerHTML = '<div class="st-skeleton" style="grid-column:1/-1;height:180px;"></div>';
        var url = stBase('store/search_ajax') + '?q=' + encodeURIComponent(q) + (categoryId ? '&category=' + categoryId : '');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) { render(res.products || []); })
            .catch(function () { grid.innerHTML = '<div class="st-empty">Search failed — try again.</div>'; });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        var q = this.value.trim();
        timer = setTimeout(function () { search(q); }, 300);
    });
    // Note: the initially server-rendered grid is already wired by store-main.js's
    // own DOMContentLoaded listener — only re-rendered search results need wiring here.
})();
