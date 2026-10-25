/**
 * ProductSearch — universal product search modal
 * Usage: ProductSearch.open({ title, callback, onSelect })
 *   callback(product) is called when user selects a product
 */
(function(window) {
'use strict';

const SEARCH_URL = window.BASE_URL + 'products/search_ajax';

let _callback  = null;
let _selIdx    = -1;
let _results   = [];
let _debounce  = null;
let _cats      = [];
let _brands    = [];

// ── Build modal DOM once
function _buildModal() {
    if (document.getElementById('ps-modal')) return;
    const html = `
<div id="ps-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);">
  <div id="ps-box" style="position:absolute;top:5vh;left:50%;transform:translateX(-50%);
       width:96vw;max-width:1100px;background:#fff;border-radius:10px;
       box-shadow:0 8px 40px rgba(0,0,0,.35);display:flex;flex-direction:column;max-height:88vh;">

    <!-- Header -->
    <div style="padding:10px 14px;background:#1e40af;border-radius:10px 10px 0 0;
                display:flex;align-items:center;gap:10px;">
      <i class="fas fa-search" style="color:#93c5fd;font-size:1rem;"></i>
      <span id="ps-title" style="color:#fff;font-weight:700;font-size:.95rem;flex:1;">Product Search</span>
      <button id="ps-close" style="background:none;border:none;color:#93c5fd;font-size:1.2rem;cursor:pointer;line-height:1;">&times;</button>
    </div>

    <!-- Search + Filters -->
    <div style="padding:8px 12px;border-bottom:1px solid #e2e8f0;background:#f8fafc;">
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <input id="ps-q" type="text" placeholder="Search by name, item code, barcode, brand, category, HSN…"
               autocomplete="off"
               style="flex:1;min-width:200px;padding:7px 12px;border:2px solid #1e40af;
                      border-radius:6px;font-size:.88rem;outline:none;">
        <select id="ps-cat" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:.82rem;">
          <option value="">All Categories</option>
        </select>
        <select id="ps-brand" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:.82rem;">
          <option value="">All Brands</option>
        </select>
        <span id="ps-count" style="font-size:.75rem;color:#6b7280;white-space:nowrap;"></span>
      </div>
    </div>

    <!-- Results table -->
    <div style="overflow:auto;flex:1;">
      <table style="width:100%;border-collapse:collapse;font-size:.8rem;">
        <thead>
          <tr style="background:#1e3a8a;color:#fff;position:sticky;top:0;z-index:1;">
            <th style="padding:6px 8px;text-align:left;white-space:nowrap;">Item Code</th>
            <th style="padding:6px 8px;text-align:left;white-space:nowrap;">Barcode</th>
            <th style="padding:6px 8px;text-align:left;">Product Name</th>
            <th style="padding:6px 8px;text-align:left;white-space:nowrap;">Brand</th>
            <th style="padding:6px 8px;text-align:left;white-space:nowrap;">Category</th>
            <th style="padding:6px 8px;text-align:center;white-space:nowrap;">Unit</th>
            <th style="padding:6px 8px;text-align:right;white-space:nowrap;">MRP</th>
            <th style="padding:6px 8px;text-align:right;white-space:nowrap;">SP</th>
            <th style="padding:6px 8px;text-align:right;white-space:nowrap;">Purchase ₹</th>
            <th style="padding:6px 8px;text-align:right;white-space:nowrap;">Stock</th>
            <th style="padding:6px 8px;text-align:center;white-space:nowrap;">GST%</th>
            <th style="padding:6px 8px;text-align:left;white-space:nowrap;">HSN</th>
          </tr>
        </thead>
        <tbody id="ps-tbody"></tbody>
      </table>
      <div id="ps-empty" style="display:none;padding:40px;text-align:center;color:#9ca3af;">
        <i class="fas fa-search" style="font-size:2rem;margin-bottom:8px;display:block;"></i>
        No products found. Try a different search term.
      </div>
      <div id="ps-loading" style="display:none;padding:30px;text-align:center;color:#6b7280;">
        <i class="fas fa-spinner fa-spin"></i> Searching…
      </div>
    </div>

    <!-- Footer -->
    <div style="padding:7px 14px;background:#f8fafc;border-top:1px solid #e2e8f0;border-radius:0 0 10px 10px;
                display:flex;justify-content:space-between;align-items:center;font-size:.75rem;color:#6b7280;">
      <span><kbd>↑</kbd><kbd>↓</kbd> Navigate &nbsp; <kbd>Enter</kbd> Select &nbsp; <kbd>Esc</kbd> Close &nbsp; <kbd>F3</kbd> Open</span>
      <button id="ps-close2" style="padding:4px 14px;background:#6b7280;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:.78rem;">Close</button>
    </div>
  </div>
</div>`;
    document.body.insertAdjacentHTML('beforeend', html);

    document.getElementById('ps-close').onclick  = close;
    document.getElementById('ps-close2').onclick = close;
    document.getElementById('ps-modal').addEventListener('click', function(e) {
        if (e.target === this) close();
    });
    document.getElementById('ps-q').addEventListener('input', _onInput);
    document.getElementById('ps-q').addEventListener('keydown', _onKey);
    document.getElementById('ps-cat').addEventListener('change', _doSearch);
    document.getElementById('ps-brand').addEventListener('change', _doSearch);
}

function _onInput() {
    clearTimeout(_debounce);
    _debounce = setTimeout(_doSearch, 250);
}

function _doSearch() {
    const q     = document.getElementById('ps-q').value.trim();
    const cat   = document.getElementById('ps-cat').value;
    const brand = document.getElementById('ps-brand').value;

    document.getElementById('ps-loading').style.display = 'block';
    document.getElementById('ps-empty').style.display   = 'none';
    document.getElementById('ps-tbody').innerHTML        = '';
    document.getElementById('ps-count').textContent      = '';
    _selIdx  = -1;
    _results = [];

    fetch(SEARCH_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'q=' + encodeURIComponent(q) +
              '&category_id=' + encodeURIComponent(cat) +
              '&brand_id='    + encodeURIComponent(brand) +
              '&limit=200'
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('ps-loading').style.display = 'none';
        if (!data.success) return;

        // Populate filters on first load
        if (data.categories && _cats.length === 0) {
            _cats   = data.categories;
            _brands = data.brands || [];
            const catSel   = document.getElementById('ps-cat');
            const brandSel = document.getElementById('ps-brand');
            _cats.forEach(c => {
                const o = document.createElement('option'); o.value = c.id; o.textContent = c.name;
                catSel.appendChild(o);
            });
            _brands.forEach(b => {
                const o = document.createElement('option'); o.value = b.id; o.textContent = b.name;
                brandSel.appendChild(o);
            });
        }

        _results = data.products || [];
        document.getElementById('ps-count').textContent = _results.length + ' items';

        if (_results.length === 0) {
            document.getElementById('ps-empty').style.display = 'block'; return;
        }

        const q_lower = q.toLowerCase();
        const tbody = document.getElementById('ps-tbody');
        tbody.innerHTML = _results.map((p, i) => {
            const stockColor = p.stock_status === 'out' ? '#dc2626' : p.stock_status === 'low' ? '#d97706' : '#16a34a';
            const stockBg    = p.stock_status === 'out' ? '#fee2e2' : p.stock_status === 'low' ? '#fef3c7' : '#dcfce7';
            return `<tr class="ps-row" data-idx="${i}"
                        style="cursor:pointer;border-bottom:1px solid #f1f5f9;"
                        onmouseenter="this.style.background='#dbeafe'"
                        onmouseleave="ProductSearch._unhover(this,${i})"
                        onclick="ProductSearch._pick(${i})">
                <td style="padding:5px 8px;font-family:monospace;font-size:.78rem;">${_hl(p.item_code||'',q_lower)}</td>
                <td style="padding:5px 8px;color:#6b7280;font-size:.75rem;">${p.barcode||'—'}</td>
                <td style="padding:5px 8px;font-weight:600;">${_hl(p.name,q_lower)}</td>
                <td style="padding:5px 8px;color:#555;">${p.brand_name||'—'}</td>
                <td style="padding:5px 8px;color:#555;">${p.category_name||'—'}</td>
                <td style="padding:5px 8px;text-align:center;">${p.unit}</td>
                <td style="padding:5px 8px;text-align:right;">₹${parseFloat(p.mrp).toFixed(2)}</td>
                <td style="padding:5px 8px;text-align:right;font-weight:700;color:#1e40af;">₹${parseFloat(p.selling_price).toFixed(2)}</td>
                <td style="padding:5px 8px;text-align:right;color:#6b7280;">₹${parseFloat(p.purchase_price).toFixed(2)}</td>
                <td style="padding:5px 8px;text-align:right;">
                  <span style="background:${stockBg};color:${stockColor};padding:2px 7px;border-radius:10px;font-weight:700;font-size:.75rem;">
                    ${parseFloat(p.stock_qty).toFixed(0)}
                  </span>
                </td>
                <td style="padding:5px 8px;text-align:center;">${p.gst_percent||0}%</td>
                <td style="padding:5px 8px;color:#6b7280;font-size:.75rem;">${p.hsn_code||'—'}</td>
            </tr>`;
        }).join('');
    })
    .catch(() => {
        document.getElementById('ps-loading').style.display = 'none';
    });
}

function _hl(text, q) {
    if (!q || !text) return text || '';
    const idx = text.toLowerCase().indexOf(q);
    if (idx < 0) return text;
    return text.slice(0, idx) +
           '<mark style="background:#fef08a;padding:0;">' + text.slice(idx, idx + q.length) + '</mark>' +
           text.slice(idx + q.length);
}

function _onKey(e) {
    const rows = document.querySelectorAll('.ps-row');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        _selIdx = Math.min(_selIdx + 1, rows.length - 1);
        _highlightRow(rows);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        _selIdx = Math.max(_selIdx - 1, 0);
        _highlightRow(rows);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (_selIdx >= 0 && _results[_selIdx]) _pick(_selIdx);
        else if (_results.length === 1)          _pick(0);
    } else if (e.key === 'Escape') {
        e.preventDefault(); close();
    }
}

function _highlightRow(rows) {
    rows.forEach((r, i) => {
        r.style.background = (i === _selIdx) ? '#1e40af' : '';
        if (i === _selIdx) {
            r.style.color = '#fff';
            r.scrollIntoView({ block: 'nearest' });
        } else {
            r.style.color = '';
        }
    });
}

function _pick(idx) {
    const p = _results[idx];
    if (!p) return;
    close();
    if (typeof _callback === 'function') _callback(p);
}

function _unhover(el, idx) {
    el.style.background = (idx === _selIdx) ? '#1e40af' : (idx % 2 === 0 ? '#fff' : '#f8fafc');
    el.style.color = (idx === _selIdx) ? '#fff' : '';
}

function open(opts) {
    _buildModal();
    const options = opts || {};
    _callback = options.callback || options.onSelect || null;
    _selIdx   = -1;
    _results  = [];

    document.getElementById('ps-title').textContent = options.title || 'Search Product';
    document.getElementById('ps-q').value    = '';
    document.getElementById('ps-tbody').innerHTML = '';
    document.getElementById('ps-count').textContent = '';
    document.getElementById('ps-empty').style.display   = 'none';
    document.getElementById('ps-loading').style.display = 'none';
    document.getElementById('ps-modal').style.display   = 'block';

    setTimeout(() => {
        document.getElementById('ps-q').focus();
        _doSearch(); // load all products initially
    }, 60);
}

function close() {
    const m = document.getElementById('ps-modal');
    if (m) m.style.display = 'none';
    _callback = null;
}

// F3 global shortcut
document.addEventListener('keydown', function(e) {
    if (e.key === 'F3') {
        e.preventDefault();
        if (document.getElementById('ps-modal') &&
            document.getElementById('ps-modal').style.display !== 'none') {
            close();
        } else {
            open();
        }
    }
});

// Public API
window.ProductSearch = { open, close, _pick, _unhover };

})(window);
