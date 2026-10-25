<?php // FILE: application/views/billing/create.php ?>
<style>
/* ── Single-page billing layout ── */
.billing-page-wrap {
    display: flex;
    flex-direction: column;
    flex: 1 1 0;
    min-height: 0;
    overflow: hidden;
}
.billing-body {
    display: flex;
    flex: 1;
    gap: 8px;
    overflow: hidden;
    margin-top: 6px;
    min-height: 0;
}
.billing-left {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-width: 0;
}
.billing-right {
    width: 300px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    overflow-y: auto;          /* scroll if content taller than viewport */
    overflow-x: hidden;
    border-left: 2px solid #e9ecef;
    padding-left: 10px;
    padding-right: 4px;
    padding-bottom: 8px;
}
.billing-right .sum-table { width: 100%; font-size:.88rem; }
.billing-right .sum-table td { padding: 5px 8px; vertical-align: middle; }
.billing-right .sum-table td:last-child { text-align: right; font-weight: 600; }
.billing-right #cd_percent { width: 70px; }
.billing-right #cd_amount  { width: 90px; }
/* All direct children of billing-left that are NOT the items wrap must not grow */
.billing-left > .card,
.billing-left > form > .card,
.billing-left > .d-flex { flex-shrink: 0; }

.billing-items-wrap {
    flex: 1 1 0;         /* grow, shrink, start from 0 */
    overflow-y: auto;    /* items scroll here */
    overflow-x: hidden;
    min-height: 0;       /* critical: allows flex child to shrink below content */
}
/* Right panel beautification */
.sum-row-label { font-size:.85rem; color:#555; padding:6px 10px; }
.sum-row-val   { font-size:.85rem; font-weight:700; padding:6px 10px; text-align:right; }
.sum-grand     { font-size:1.2rem; font-weight:800; background:#1a1a2e; color:#fff; }
.sum-due-row   { font-size:1.1rem; font-weight:800; }
.sum-paid-input{ width:100%; border:none; background:transparent; font-weight:700; font-size:.9rem; text-align:right; color:#198754; }
/* Row number pill */
.row-num { display:inline-block; width:22px; height:22px; border-radius:50%; background:#6c757d; color:#fff; font-size:.7rem; font-weight:700; line-height:22px; text-align:center; }
/* Latest-scanned-row auto-scroll: small breathing room at the bottom of the items
   grid's own scroll box (see .billing-items-wrap) so the newest row isn't flush
   against its edge. */
#itemsBody tr { scroll-margin-bottom: 12px; }
#itemsBody tr.row-just-added { animation: rowJustAdded 1.1s ease-out; }
@keyframes rowJustAdded {
    0%   { background-color: #bbf7d0; }
    100% { background-color: transparent; }
}
/* EAN badge */
.ean-code { font-family:monospace; font-size:.72rem; color:#888; }
/* Hide navbar in fullscreen billing mode */
body.billing-fs nav,
body.billing-fs .navbar,
:-webkit-full-screen body nav,
:fullscreen body nav { display: none !important; }

/* Make body a flex column so container-fluid fills exactly the remaining space below the navbar */
body.billing-open {
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    height: 100vh !important;
}
body.billing-open > nav,
body.billing-open > .navbar,
body.billing-open > header { flex-shrink: 0; }
body.billing-open .container-fluid {
    flex: 1 1 0 !important;
    overflow: hidden !important;
    padding: 4px 8px 0 8px !important;
    margin: 0 !important;
    min-height: 0 !important;
    display: flex !important;
    flex-direction: column !important;
}
/* Form must be a flex column too so billing-body fills it */
#invoiceForm { display: contents; }

/* ── v2: POS-style header ── */
.v2-topbar {
    background: #0f172a; color: #e2e8f0; border-radius: 10px;
    padding: 6px 16px; display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0; margin-bottom: 5px; gap: 10px; flex-wrap: wrap;
}
.v2-topbar .v2-brand { font-weight: 800; font-size: 1rem; color: #fff; display:flex; align-items:center; gap:8px; }
.v2-topbar .v2-brand .v2-badge { background:#2563eb; color:#fff; font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:20px; letter-spacing:.4px; }
.v2-topbar .v2-meta { display:flex; align-items:center; gap:18px; font-size:.78rem; color:#94a3b8; }
.v2-topbar .v2-meta strong { color:#e2e8f0; display:block; font-size:.85rem; }
.v2-topbar .v2-actions { display:flex; align-items:center; gap:10px; }
.v2-topbar .v2-actions a, .v2-topbar .v2-actions button { color:#94a3b8; background:none; border:none; font-size:1rem; }
.v2-topbar .v2-actions a:hover, .v2-topbar .v2-actions button:hover { color:#fff; }

/* Quick payment-mode buttons */
.pay-mode-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:6px; margin-bottom:6px; }
.pay-mode-btn {
    border:2px solid #e2e8f0; background:#fff; border-radius:10px; padding:7px 4px;
    display:flex; flex-direction:column; align-items:center; gap:3px; font-size:.72rem; font-weight:700; color:#475569;
    cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,.04); user-select:none;
    transition:border-color .12s ease, background-color .12s ease, transform .12s ease, box-shadow .12s ease;
}
.pay-mode-btn i { font-size:1.05rem; }
.pay-mode-btn.active { border-color:#16a34a; background:#f0fdf4; color:#15803d; }
.pay-mode-btn:not(.active):hover { border-color:#94a3b8; transform: translateY(-1px); box-shadow:0 4px 10px rgba(15,23,42,.08); }
.pay-mode-btn:active { transform: translateY(0) scale(.96); }

/* Bottom function-key bar — fixed to the viewport, always visible without scrolling.
   Solid single-color (blue) filled buttons, white text, no border — classic flat
   Windows-button look rather than white cards with a colored accent chip. */
.v2-fkey-bar-fixed {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 1030;
    background: #ffffff; border-top: 1px solid #e5e7eb; padding: 10px 16px;
    box-shadow: 0 -4px 16px rgba(15,23,42,.08);
}
.v2-fkey-bar {
    display:flex; gap:10px; flex-shrink:0; overflow-x:auto; max-width: 1400px; margin: 0 auto;
}
.v2-fkey-btn {
    flex:1; min-width:100px; background:#0d5ea6; border:none; border-radius:10px;
    padding:9px 6px; text-align:center; cursor:pointer; font-size:.76rem; font-weight:700; color:#fff;
    box-shadow:0 1px 3px rgba(15,23,42,.2); user-select:none;
    transition: transform .12s ease, box-shadow .12s ease, background-color .12s ease;
}
.v2-fkey-btn kbd {
    display:block; background:rgba(255,255,255,.28); color:#fff; border-radius:6px; padding:3px 0;
    font-size:.72rem; margin-bottom:5px; font-weight:400; letter-spacing:.3px;
    transition: background-color .12s ease;
}
.v2-fkey-btn i { color:#fff; margin-right:4px; font-size:.85rem; }
.v2-fkey-btn.disabled { opacity:.45; cursor:not-allowed; background:#94a3b8; }
.v2-fkey-btn.disabled kbd { background:rgba(255,255,255,.3); color:#f1f5f9; }
.v2-fkey-btn.disabled i { color:#f1f5f9; }
.v2-fkey-btn:not(.disabled):hover {
    background:#0a4a86; transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(13,94,166,.4);
}
.v2-fkey-btn:not(.disabled):hover kbd { background:rgba(255,255,255,.4); }
.v2-fkey-btn:not(.disabled):active { transform: translateY(0) scale(.96); box-shadow:0 1px 2px rgba(15,23,42,.15); }

/* Consistent rounded corners across the left column's cards, matching the right
   panel's cards (10px) — was Bootstrap's default 4px, a small but visible mismatch. */
.billing-left .card { border-radius: 10px; }
.billing-left .card .thead-dark th:first-child { border-top-left-radius: 8px; }
.billing-left .card .thead-dark th:last-child { border-top-right-radius: 8px; }

/* Softer card shadows to match the mockup's elevated-card look.
   flex-shrink:0 is critical here: .billing-right is a flex column, and without it,
   once max-height constrains the column (see fitBillingRightPanel), flex's default
   shrink behavior COMPRESSES these children to fit instead of letting the column
   scroll — squishing the totals/payment cards instead of showing a scrollbar. */
.billing-right > div { box-shadow: 0 1px 3px rgba(0,0,0,.05); flex-shrink: 0; }

/* Compact single-row customer/invoice header (was two stacked rows) */
.v2-header-row { display:flex; align-items:flex-start; gap:8px; flex-wrap:wrap; }
.v2-header-row .v2-cust-block { position:relative; flex:1.3 1 170px; min-width:170px; }
.v2-header-row .v2-cust-select-block { flex:1.6 1 190px; min-width:190px; }
.v2-header-row .v2-delivery-block { flex:0 0 auto; white-space:nowrap; padding-top:4px; }
.v2-header-row .v2-type-block { flex:0 0 115px; }
.v2-header-row .v2-date-block { flex:0 0 148px; }
.v2-header-row .v2-items-block {
    flex:0 0 auto; white-space:nowrap; font-weight:800; color:#334155; font-size:.85rem;
    display:flex; align-items:center; gap:5px; background:#f1f5f9; border-radius:6px;
    padding:0 10px; height:31px;
}

/* v2: the classic screen locks to exactly the viewport height (a "POS terminal" full-
   screen frame, useful once a cart has many rows) — with just a couple of items that
   left a large dead gap below the columns before the footer, since the columns still
   stretched to fill the locked height. v2 instead sizes to its content, like a normal
   page, matching the mockup (which is naturally content-sized, not viewport-locked). */
body.billing-v2 { overflow: auto !important; height: auto !important; display: block !important; }
body.billing-v2 .container-fluid { flex: none !important; overflow: visible !important; height: auto !important; display: block !important; }
.billing-v2 .billing-page-wrap { flex: none !important; overflow: visible !important; }
.billing-v2 .billing-body { flex: none !important; overflow: visible !important; align-items: flex-start; }
/* Items grid scrolls internally instead of growing the whole page — otherwise a
   50-item cart pushes the search bar and customer header off-screen, which is what
   made the "auto-scroll to newest item" feature confusing (it was scrolling the
   whole page, not just the cart). Bounded height + its own scrollbar keeps the
   scan box, customer picker, and totals/payment panel always in view.
   max-height here is just a pre-JS fallback — fitItemsWrapHeight() (below) sizes it
   exactly to the space actually available above the fixed F-key bar, the same way
   fitBillingRightPanel() already does for the right column. A static vh guess left
   a large dead gap under a short cart, which is exactly what v2 was designed to avoid. */
.billing-v2 .billing-items-wrap { flex: none !important; max-height: 46vh; overflow-y: auto; overflow-x: hidden; }
.billing-v2 .billing-items-wrap::-webkit-scrollbar { width: 10px; }
.billing-v2 .billing-items-wrap::-webkit-scrollbar-track { background: #eef1f6; border-radius: 6px; }
.billing-v2 .billing-items-wrap::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 6px; }
.billing-v2 .billing-items-wrap::-webkit-scrollbar-thumb:hover { background: #64748b; }
/* Height is now managed dynamically by fitBillingRightPanel() below — this just sets a
   sane default before that JS runs. A visible, colored scrollbar (rather than the
   easy-to-miss default thin one) makes it obvious there's more content below when the
   panel doesn't fully fit the screen. */
.billing-v2 .billing-right { overflow-y: auto; scrollbar-width: auto; scrollbar-color: #94a3b8 #eef1f6; }
.billing-v2 .billing-right::-webkit-scrollbar { width: 10px; }
.billing-v2 .billing-right::-webkit-scrollbar-track { background: #eef1f6; border-radius: 6px; }
.billing-v2 .billing-right::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 6px; }
.billing-v2 .billing-right::-webkit-scrollbar-thumb:hover { background: #64748b; }
.billing-v2 .billing-page-wrap { padding-bottom: 100px; }
/* Narrow 300px column forced a lot of vertical stacking to fit everything — widen it
   for v2 so pairs of fields (Amount Payable / Amount Paid, etc.) can sit side by side. */
.billing-v2 .billing-right { width: 380px; }
</style>
<script>document.body.classList.add('billing-open', 'billing-v2');</script>
<script>
// Fit the right panel exactly between its own top position and the fixed F-key bar,
// so it never overflows off-screen (scrolls internally instead) and never leaves a
// blank gap either — this adapts to any screen resolution/zoom level automatically.
function fitBillingRightPanel() {
    var right = document.querySelector('.billing-right');
    var bar = document.querySelector('.v2-fkey-bar-fixed');
    if (!right || !bar) return;
    var rightTop = right.getBoundingClientRect().top;
    var barTop = bar.getBoundingClientRect().top;
    var available = barTop - rightTop - 10;
    if (available > 150) right.style.maxHeight = available + 'px';
}
window.addEventListener('load', fitBillingRightPanel);
window.addEventListener('resize', fitBillingRightPanel);

// Same idea as fitBillingRightPanel() above, applied to the items grid: fills exactly
// the space between its own top and the fixed F-key bar, so a short cart doesn't leave
// a dead gap (a static vh guess did) and a long cart scrolls inside the box instead of
// growing the page.
function fitItemsWrapHeight() {
    var wrap = document.querySelector('.billing-items-wrap');
    var bar = document.querySelector('.v2-fkey-bar-fixed');
    if (!wrap || !bar) return;
    var wrapTop = wrap.getBoundingClientRect().top;
    var barTop = bar.getBoundingClientRect().top;
    var available = barTop - wrapTop - 10;
    if (available > 150) wrap.style.maxHeight = available + 'px';
}
window.addEventListener('load', fitItemsWrapHeight);
window.addEventListener('resize', fitItemsWrapHeight);
</script>

<div class="billing-page-wrap">

<!-- ── TOP BAR (v2) ── -->
<div class="v2-topbar">
    <div class="v2-brand">
        <i class="fas fa-shopping-cart" style="color:#38bdf8;"></i>
        <?= htmlspecialchars($company['company_name'] ?? 'POS') ?>
        <span class="v2-badge">BILLING</span>
    </div>
    <div class="v2-meta">
        <div><span style="text-transform:uppercase;letter-spacing:.5px;">Invoice</span><strong>New (unsaved)</strong></div>
        <div><span style="text-transform:uppercase;letter-spacing:.5px;">Cashier</span><strong><?= htmlspecialchars($this->session->userdata('name') ?: 'Staff') ?></strong></div>
        <div><span style="text-transform:uppercase;letter-spacing:.5px;">Time</span><strong id="v2Clock"><?= date('h:i A') ?></strong></div>
    </div>
    <div class="v2-actions">
        <a href="<?= base_url('billing') ?>" title="Back to invoice list"><i class="fas fa-arrow-left"></i></a>
        <button type="button" onclick="billingIsFS() ? billingExitFS() : billingEnterFS();" title="Fullscreen (F11)"><i class="fas fa-expand"></i></button>
    </div>
</div>
<script>setInterval(function(){ var el=document.getElementById('v2Clock'); if(el) el.textContent = new Date().toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:true}); }, 30000);</script>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<?php
// Settings > Billing > "Default Payment Mode" — pre-selects this mode on every fresh
// invoice (and after Save & Print reloads a blank one). Falls back to 'cash' if unset,
// and to 'cash' if set to 'credit' but this user isn't allowed to raise due bills
// (the Credit option isn't even rendered in that case, so selecting it would be a no-op).
$canCredit = $this->session->userdata('role') === 'admin' || $this->session->userdata('role') === 'superadmin' || !empty($allow_due_bills);
$defaultPayMode = $company['default_payment_mode'] ?? 'cash';
if ($defaultPayMode === 'credit' && !$canCredit) $defaultPayMode = 'cash';
?>

<?= form_open('billing/create', ['id' => 'invoiceForm']) ?>
<input type="hidden" name="device_label" id="hDeviceLabel" value="">
<input type="hidden" name="convert_order_id" id="convert_order_id" value="">
<input type="hidden" name="submit_token" value="<?= $submit_token ?? '' ?>">

<!-- ── SPLIT BODY ── -->
<div class="billing-body">
<div class="billing-left">

<!-- Invoice Header — one compact row: customer search+select, delivery, type, date, items -->
<div class="card border-0 shadow-sm mb-1" style="flex-shrink:0;">
    <div class="card-body">
        <div class="v2-header-row">
            <div class="v2-cust-block">
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend"><span class="input-group-text" title="Phone / Name search"><i class="fas fa-mobile-alt text-info"></i></span></div>
                    <input type="text" id="cust_phone_search" class="form-control border-info" placeholder="Phone or name search..." autocomplete="off">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-info btn-sm" onclick="searchCustomerByPhone()" title="Search"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div id="custSearchDropdown" class="list-group position-absolute" style="z-index:99999;max-height:200px;overflow-y:auto;display:none;width:95%;box-shadow:0 4px 6px rgba(0,0,0,.1);"></div>
                <small id="phone_search_status" class="text-success font-weight-bold" style="font-size:.7rem;position:absolute;top:100%;left:0;z-index:20;background:#fff;padding:1px 4px;border-radius:4px;box-shadow:0 2px 6px rgba(0,0,0,.12);white-space:nowrap;"></small>
            </div>
            <div class="v2-cust-select-block">
                <select name="customer_id" id="customer_id" class="form-control form-control-sm" onchange="loadCustomer(this.value)" title="Customer (optional — Walk-in auto-assigned)">
                    <option value="">👤 Walk-in Customer (Default)</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c->id ?>" data-balance="<?= $c->balance ?>" data-wallet="<?= $c->wallet_balance ?? 0 ?>">
                        <?= htmlspecialchars($c->name) ?> (<?= htmlspecialchars($c->phone ?? '') ?>)
                        <?php if (($c->wallet_balance ?? 0) > 0): ?> — Wallet: ₹<?= number_format($c->wallet_balance, 0) ?><?php endif; ?>
                        <?php if ($c->balance > 0): ?> — Due: ₹<?= number_format($c->balance, 0) ?><?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="v2-delivery-block">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="is_delivery" id="is_delivery" class="custom-control-input" value="1">
                    <label class="custom-control-label font-weight-bold" style="font-size:.75rem;" for="is_delivery" title="Delivery order">🚚</label>
                </div>
            </div>
            <div class="v2-type-block">
                <select name="invoice_type" id="invoice_type" class="form-control form-control-sm" onchange="loadCustomer(document.getElementById('customer_id').value)" title="Invoice Type (required)">
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                </select>
            </div>
            <div class="v2-date-block">
                <input type="date" name="invoice_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required title="Invoice Date (required)">
            </div>
            <div class="v2-items-block" title="Items in this bill">
                <i class="fas fa-boxes text-muted"></i> <span id="v2ItemsBadge">0</span>
            </div>
        </div>
        <div class="d-flex align-items-start" style="gap:8px;margin-top:22px;">
            <!-- margin-top:22px above clears #phone_search_status, which floats absolutely
                 below the phone input and was overlapping this row before. -->
            <!-- Customer info panel — compact single-line strip -->
            <div id="customer_info" class="rounded px-2 py-1 border" style="flex:1;min-width:0;font-size:.75rem;display:none;border-left:3px solid #17a2b8 !important;background:#f0f9ff;">
                <div id="cust_detail"></div>
            </div>
            <!-- Last Bill panel — most recent invoice for the selected customer -->
            <div id="last_bill_info" class="rounded px-2 py-1 border" style="flex:0 0 auto;font-size:.75rem;display:none;border-left:3px solid #f59e0b !important;background:#fffbeb;white-space:nowrap;">
                <i class="fas fa-receipt text-warning mr-1"></i>
                <span class="text-muted">Last Bill:</span> <strong id="last_bill_no">—</strong>
                &nbsp;|&nbsp;
                <span class="text-muted">Amount:</span> <strong id="last_bill_amount">₹0.00</strong>
            </div>
        </div>
            <!-- Payment mode / paid amount now live in the always-visible payment panel on the right;
                 these remain the real submitted fields, kept in sync from there. -->
            <div style="display:none;">
                <select name="payment_mode" class="form-control form-control-sm">
                    <?php $is_admin_role = in_array($this->session->userdata('role'), ['admin','superadmin']); ?>
                    <?php foreach ($payment_modes as $pm): ?>
                        <?php if ($pm->is_credit && !$is_admin_role && empty($allow_due_bills)) continue; ?>
                        <option value="<?= $pm->code ?>"<?= $pm->code === 'cash' ? ' selected' : '' ?>><?= htmlspecialchars($pm->name) ?></option>
                    <?php endforeach; ?>
                    <option value="split">Split (Multi-mode)</option>
                </select>
                <input type="number" step="0.01" name="paid_amount" id="paid_amount" value="0" min="0" oninput="recalcTotals()">
            </div>
    </div>
</div>

<!-- ══ Unified Smart Search & Scan ══ -->
<div class="card border-0 shadow-sm mb-1" style="border-left:4px solid #28a745 !important;flex-shrink:0;">
    <div class="card-body py-1 px-3">
        <div class="d-flex align-items-center" style="gap:8px;">
            <label class="font-weight-bold mb-0 text-success text-nowrap" style="font-size:.78rem;"><i class="fas fa-barcode mr-1"></i>Search &amp; Scan</label>
            <div class="input-group input-group-sm flex-grow-1 position-relative">
                <input type="text" id="smartSearch" class="form-control border-success" placeholder="Type product name, item code, or scan barcode & press Enter..." autocomplete="off" style="font-size:.88rem;font-weight:500;">
                <div class="input-group-append">
                    <button type="button" class="btn btn-success btn-sm" onclick="triggerSmartSearch()"><i class="fas fa-search"></i></button>
                </div>
                <div id="searchDropdown" class="list-group position-absolute" style="z-index:9999;max-height:260px;overflow-y:auto;display:none;width:100%;top:100%;left:0;"></div>
            </div>
            <div class="d-flex" style="gap:4px;flex-shrink:0;">
                <button type="button" class="btn btn-sm btn-success font-weight-bold text-white shadow-sm" onclick="openEvScooterModal()" title="Allocate EV Scooter, Battery & Charger"><i class="fas fa-motorcycle mr-1"></i> EV Scooter</button>
                <button type="button" class="btn btn-sm btn-outline-dark" onclick="addRow()" title="Add Row Manually"><i class="fas fa-plus"></i> Row</button>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="openQuickAddProduct()" title="New Item"><i class="fas fa-box-open"></i> New Item</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openQuickAddCustomer()" title="New Customer"><i class="fas fa-user-plus"></i> New Customer</button>
            </div>
        </div>
        <small id="barcodeStatus" class="form-text text-muted font-weight-bold" style="font-size:.72rem;min-height:13px;margin-top:1px;"></small>
    </div>
</div>

<!-- Product Line Items -->
<div class="billing-items-wrap">
<div class="card border-0 shadow-sm mb-2" style="min-height:0;">
    <div class="card-header bg-white py-1 px-3 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold" style="font-size:.82rem;">Items <span class="badge badge-secondary" id="rowCount">0</span></span>
        <span class="text-muted" style="font-size:.72rem;"><kbd>F1</kbd> Qty &bull; <kbd>F2</kbd> Price &bull; <kbd>F4</kbd> Pay &amp; Save &bull; <kbd>Enter</kbd> on scan box to add</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" id="itemsTable" style="font-size:.83rem;">
                <thead class="thead-dark">
                    <tr>
                        <th style="width:2%">#</th>
                        <th style="width:26%">Product</th>
                        <th style="width:6%">Stock</th>
                        <th style="width:6%">Qty</th>
                        <th style="width:9%">MRP (₹)</th>
                        <th style="width:9%">Disc%</th>
                        <th style="width:9%">Rate (₹)</th>
                        <th style="width:12%">EAN / Item Code</th>
                        <?php if (!empty($settings['enable_gst'])): ?>
                        <th style="width:6%">GST%</th>
                        <?php endif; ?>
                        <th style="width:10%">Total</th>
                        <th style="width:2%"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <td colspan="<?= !empty($settings['enable_gst']) ? '8' : '7' ?>" class="text-right">Totals:</td>
                        <td id="foot_total" class="font-weight-bold">₹ 0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>


</div><!-- /billing-items-wrap -->
</div><!-- /billing-left -->

<!-- ── RIGHT PANEL ── -->
<div class="billing-right" style="justify-content:flex-start;">

    <!-- Grand Total — shown up top too, mirrors #s_total below -->
    <div class="d-flex justify-content-between align-items-center" style="background:#1a1a2e;border-radius:10px;padding:9px 16px;margin-bottom:6px;flex-shrink:0;">
        <span style="font-size:.9rem;font-weight:800;color:#cbd5e1;letter-spacing:.5px;">GRAND TOTAL</span>
        <span id="s_total_top" style="font-size:1.4rem;font-weight:900;color:#4ade80;">₹ 0.00</span>
    </div>

    <!-- Product image preview lives here invisibly (updated on scan) — the standalone
         preview box + separate Items/Qty counter card were cut to reclaim vertical
         space, since the panel was taller than the screen on laptop-sized monitors. -->
    <div style="display:none;">
        <div id="rp_img_placeholder"></div>
        <img id="rp_img" src="" alt="">
    </div>

    <!-- Items + Qty counter — compact single-line strip -->
    <div style="background:#1e293b;border-radius:8px;padding:5px 14px;margin-bottom:6px;flex-shrink:0;display:flex;justify-content:space-around;align-items:center;">
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Items</span>
            <span id="rp_item_count" style="font-size:1.05rem;font-weight:900;color:#f8fafc;">0</span>
        </div>
        <div style="width:1px;height:16px;background:#334155;"></div>
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">Qty</span>
            <span id="rp_total_qty" style="font-size:1.05rem;font-weight:900;color:#38bdf8;">0</span>
        </div>
    </div>

    <!-- 3. Notes -->
    <textarea name="notes" class="form-control form-control-sm mb-2" rows="1" placeholder="Notes / payment terms..." style="flex-shrink:0;"></textarea>

    <!-- 4. Totals card — natural height, no stretching -->
    <div style="border-radius:10px;border:1px solid #e0e0e0;overflow:hidden;">
        <div style="background:#f8f9fa;padding:6px 14px;">
            <div class="d-flex justify-content-between mb-1">
                <span style="font-size:.78rem;color:#555;font-weight:600;">MRP Total</span>
                <span id="s_subtotal" style="font-size:.82rem;font-weight:800;">₹ 0.00</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span style="font-size:.78rem;color:#16a34a;font-weight:600;">Item Discount (MRP→SP)</span>
                <span id="s_disc" style="font-size:.82rem;font-weight:800;color:#16a34a;">₹ 0.00</span>
            </div>
            <div id="row_cgst" class="d-flex justify-content-between mb-1" style="<?= empty($settings['enable_gst']) ? 'display:none!important;' : '' ?>">
                <span style="font-size:.78rem;color:#555;font-weight:600;">CGST</span>
                <span id="s_cgst" style="font-size:.82rem;font-weight:800;">₹ 0.00</span>
            </div>
            <div id="row_sgst" class="d-flex justify-content-between mb-1" style="<?= empty($settings['enable_gst']) ? 'display:none!important;' : '' ?>">
                <span style="font-size:.78rem;color:#555;font-weight:600;">SGST</span>
                <span id="s_sgst" style="font-size:.82rem;font-weight:800;">₹ 0.00</span>
            </div>
            <div id="row_igst" class="d-flex justify-content-between mb-1" style="display:none;">
                <span style="font-size:.78rem;color:#555;font-weight:600;">IGST</span>
                <span id="s_igst" style="font-size:.82rem;font-weight:800;">₹ 0.00</span>
            </div>
            <div id="row_bill_disc" class="d-flex justify-content-between mb-1" style="display:none;">
                <span style="font-size:.78rem;color:#dc3545;font-weight:700;">Store Discount</span>
                <span id="s_bill_disc" style="font-size:.82rem;font-weight:800;color:#dc3545;">- ₹ 0.00</span>
            </div>
            <!-- Total Saving -->
            <div class="saving-row d-flex justify-content-between align-items-center pt-1" style="border-top:1px dashed #16a34a;">
                <span style="font-size:.78rem;color:#15803d;font-weight:700;">🎉 Total Saving</span>
                <span id="s_saving" style="font-size:.85rem;font-weight:900;color:#15803d;">₹ 0.00</span>
            </div>
        </div>
        <!-- CD Discount -->
        <div style="background:#fff8e1;padding:5px 14px;border-top:1px solid #ffe082;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span style="font-size:.78rem;font-weight:700;color:#856404;">CD Discount</span>
                <span id="s_cd" style="font-size:.84rem;font-weight:800;color:#c0392b;">- ₹ 0.00</span>
            </div>
            <div class="d-flex align-items-center" style="gap:5px;">
                <input type="number" step="0.01" min="0" max="100" name="cd_percent" id="cd_percent" class="form-control form-control-sm" style="width:65px;" value="0" oninput="onCdPercentChange()" placeholder="%">
                <small style="color:#856404;">%</small>
                <input type="number" step="0.01" min="0" name="cd_amount" id="cd_amount" class="form-control form-control-sm" style="flex:1;" value="0" oninput="onCdAmountChange()" placeholder="₹ Amt">
            </div>
        </div>
        <!-- Round Off -->
        <div style="background:#e0f2fe;padding:5px 14px;border-top:1px solid #bae6fd;display:flex;justify-content:space-between;align-items:center;">
            <label style="font-size:.78rem;font-weight:700;color:#075985;margin:0;display:flex;align-items:center;gap:6px;cursor:pointer;">
                <input type="checkbox" id="round_off_enabled" name="round_off_enabled" value="1" onchange="recalcTotals()"> Round Off (nearest ₹1)
            </label>
            <span id="s_round_off" style="font-size:.84rem;font-weight:800;color:#075985;">₹ 0.00</span>
        </div>
        <!-- Grand Total + Amount Due — already shown big at the very top of the panel,
             so these repeat compactly here (kept in the DOM: #s_total is read directly
             by several calc functions with no null-guard, so it can't be removed). -->
        <div class="d-flex justify-content-between align-items-center" style="background:#1a1a2e;padding:6px 16px;flex-shrink:0;">
            <span style="font-size:.78rem;font-weight:700;color:#cbd5e1;letter-spacing:.5px;">GRAND TOTAL</span>
            <span id="s_total" style="font-size:.92rem;font-weight:900;color:#4ade80;">₹ 0.00</span>
        </div>
        <!-- Amount Due (live, pre-payment-entry) -->
        <div class="d-flex justify-content-between align-items-center" style="background:#fef2f2;padding:5px 16px;border-top:1px solid #fca5a5;flex-shrink:0;">
            <span style="font-size:.72rem;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:.5px;">Amount Due</span>
            <span id="s_due" style="font-size:.85rem;font-weight:900;color:#dc2626;">₹ 0.00</span>
        </div>
        <span id="s_taxable" style="display:none;">₹ 0.00</span>
        <span id="pmGrandTotal" style="display:none;">₹ 0.00</span>
    </div>

    <!-- ═══ Inline Payment Panel (was a modal; now always visible) ═══ -->
    <div style="border-radius:10px;border:1px solid #e0e0e0;overflow:hidden;margin-top:8px;">
        <div style="background:#1e293b;color:#fff;padding:8px 14px;font-weight:800;font-size:.85rem;letter-spacing:.4px;">
            <i class="fas fa-wallet mr-1"></i> PAYMENT SECTION
        </div>
        <div style="padding:8px 14px;">
            <div class="pay-mode-grid">
                <?php foreach ($payment_modes as $pm): if ($pm->show_in_quick): ?>
                <div class="pay-mode-btn<?= $pm->code === 'cash' ? ' active' : '' ?>" data-mode="<?= $pm->code ?>" onclick="selectPayModeBtn('<?= $pm->code ?>')"><i class="<?= htmlspecialchars($pm->icon) ?>"></i><span><?= htmlspecialchars($pm->name) ?></span></div>
                <?php endif; endforeach; ?>
                <div class="pay-mode-btn" id="btnSplitPayQuick" onclick="switchPayMode('split')"><i class="fas fa-random"></i><span>Split</span></div>
            </div>
            <div class="form-group mb-1">
                <?php
                    $other_modes = array_filter($payment_modes, function($m) use ($is_admin_role, $allow_due_bills) {
                        if (!$m->show_in_other) return false;
                        if ($m->is_credit && !$is_admin_role && empty($allow_due_bills)) return false;
                        return true;
                    });
                ?>
                <?php if (!empty($other_modes)): ?>
                <label class="font-weight-bold small mb-0 text-muted" style="font-size:.66rem;">Other mode</label>
                <select id="pmOtherMode" class="form-control form-control-sm" onchange="selectOtherPayMode(this.value)">
                    <option value="" selected disabled>Select...</option>
                    <?php foreach ($other_modes as $pm): ?>
                    <option value="<?= $pm->code ?>"><?= htmlspecialchars($pm->name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                <select id="pmPaymentMode" style="display:none;" onchange="onPaymentModeChange(); syncPayModeButtons();">
                    <?php foreach ($payment_modes as $pm): ?>
                        <?php if ($pm->is_credit && !$is_admin_role && empty($allow_due_bills)) continue; ?>
                        <option value="<?= $pm->code ?>"<?= $pm->code === 'cash' ? ' selected' : '' ?>><?= htmlspecialchars($pm->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <form id="paymentModalForm" onsubmit="submitInvoiceForm(event)">
                <!-- Store-Level Discount (admin/manager with discount permission only) -->
                <?php if ($is_admin && !empty($can_discount)): ?>
                <div class="border rounded p-1 mb-1" style="background:#fff8e1;">
                    <div class="d-flex justify-content-between align-items-center" style="min-height:0;">
                        <label class="font-weight-bold small mb-0 text-warning" style="font-size:.72rem;"><i class="fas fa-tag mr-1"></i> Store Discount <span class="badge badge-warning" style="font-size:.6rem;">ADMIN</span></label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enableStoreDisc" onchange="toggleStoreDisc()">
                            <label class="custom-control-label small" for="enableStoreDisc">Enable</label>
                        </div>
                    </div>
                    <div id="storeDiscFields" style="display:none;">
                        <div class="form-row mb-1">
                            <div class="col-5">
                                <select id="pmBillDiscType" class="form-control form-control-sm" onchange="calcBillDiscount()">
                                    <option value="percent">Percent (%)</option>
                                    <option value="fixed">Fixed Amount (₹)</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <input type="number" step="0.01" min="0" id="pmBillDiscValue" class="form-control form-control-sm" placeholder="Value" oninput="calcBillDiscount()">
                            </div>
                            <div class="col-3 d-flex align-items-center">
                                <span class="text-danger font-weight-bold small" id="pmBillDiscAmt">- ₹ 0.00</span>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <input type="text" id="pmBillDiscReason" class="form-control form-control-sm" placeholder="Reason for discount (required)">
                        </div>
                    </div>
                </div>
                <!-- Hidden mirrors: kept in sync so existing discount-calc JS (which reads these ids) needs no changes -->
                <input type="hidden" id="pmDiscountPercent"><input type="hidden" id="pmDiscountAmount"><span id="pmNetAfterCd" style="display:none;">₹ 0.00</span>
                <?php else: ?>
                <span id="pmNetAfterCd" style="display:none;">₹ 0.00</span><input type="hidden" id="pmDiscountPercent"><input type="hidden" id="pmDiscountAmount">
                <?php endif; ?>

                <!-- Amount Payable Now + Amount Paid, side by side to save vertical space -->
                <div class="form-row mb-1">
                    <div class="col-6">
                        <div class="text-center rounded p-1 h-100 border-success" style="background:#e8f5e9; border:1px solid #a5d6a7;">
                            <div class="small text-muted font-weight-bold text-uppercase" style="font-size:.64rem;">Payable Now</div>
                            <div class="font-weight-bold text-success mb-0" id="pmNetPayable" style="font-size:1.15rem;">₹ 0.00</div>
                        </div>
                    </div>
                    <div class="col-6" id="singlePayAmountCol">
                        <label class="font-weight-bold small mb-0" style="font-size:.68rem;">Amount Paid (₹)</label>
                        <input type="number" step="0.01" id="pmAmountPaid" class="form-control form-control-sm" style="font-size: .95rem; font-weight: bold;" oninput="markAmountPaidEdited(); calcPaymentChange()" onblur="formatAmountPaidInput()" onkeydown="if(event.key==='Enter'){ event.preventDefault(); document.getElementById('pmSubmitBtn').focus(); }">
                    </div>
                </div>

                <!-- Single Payment Mode -->
                <div id="singlePaySection">
                    <!-- Card details (single mode) -->
                    <div id="singleCardDetails" style="display:none;" class="border rounded p-2 mb-2" style="background:#e3f2fd;">
                        <div class="small font-weight-bold text-primary mb-1"><i class="fas fa-credit-card mr-1"></i> Card Details (Optional)</div>
                        <div class="form-row">
                            <div class="col-4">
                                <select id="singleCardType" class="form-control form-control-sm">
                                    <option value="">Type</option>
                                    <option value="debit">Debit</option>
                                    <option value="credit">Credit</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <input type="text" id="singleCardLast4" class="form-control form-control-sm" maxlength="4" placeholder="Last 4 digits">
                            </div>
                            <div class="col-4">
                                <input type="text" id="singleBankName" class="form-control form-control-sm" placeholder="Bank Name">
                            </div>
                        </div>
                        <div class="form-row mt-1">
                            <div class="col-12">
                                <input type="text" id="singleCardRef" class="form-control form-control-sm" placeholder="Transaction Reference No.">
                            </div>
                        </div>
                    </div>
                    <!-- Credit due date -->
                    <div id="singleCreditDue" style="display:none;" class="mb-2">
                        <label class="font-weight-bold small text-danger"><i class="fas fa-calendar mr-1"></i> Credit Due Date (optional)</label>
                        <input type="date" id="pmCreditDueDate" class="form-control form-control-sm">
                    </div>
                </div>

                <!-- Split Payment Mode -->
                <div id="splitPaySection" style="display:none;">
                    <table class="table table-sm mb-1" id="splitPayTable" style="font-size:.82rem;">
                        <thead class="thead-light">
                            <tr><th>Mode</th><th>Amount (₹)</th><th>Card Details</th><th></th></tr>
                        </thead>
                        <tbody id="splitPayBody"></tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-success mb-2" onclick="addSplitRow()"><i class="fas fa-plus mr-1"></i> Add Payment Row</button>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#e8f5e9; font-size:.84rem;">
                        <span class="font-weight-bold">Total Splits:</span>
                        <span class="font-weight-bold text-success" id="splitTotal">₹ 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded mt-1" style="background:#ffebee; font-size:.84rem;" id="splitRemainBox">
                        <span class="font-weight-bold">Remaining / Credit:</span>
                        <span class="font-weight-bold text-danger" id="splitRemain">₹ 0.00</span>
                    </div>
                    <!-- Credit due date (split mode) -->
                    <div id="splitCreditDue" style="display:none;" class="mt-2">
                        <label class="font-weight-bold small text-danger"><i class="fas fa-calendar mr-1"></i> Credit Due Date (optional)</label>
                        <input type="date" id="pmCreditDueDateSplit" class="form-control form-control-sm">
                    </div>
                </div>

                <!-- Customer Wallet Adjustment Section -->
                <div id="pmWalletContainer" class="rounded p-2 mb-2 border" style="background:#e8f4fd; border-color:#b3d7f7 !important; display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small font-weight-bold text-primary"><i class="fas fa-wallet mr-1"></i> Available Wallet Balance:</span>
                        <span class="font-weight-bold text-primary" id="pmWalletAvailable">₹ 0.00</span>
                    </div>
                    <div class="form-row align-items-center">
                        <div class="col-md-7">
                            <span class="small text-muted font-weight-bold">Apply from Wallet:</span>
                        </div>
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" step="0.01" min="0" id="pmWalletUsed" class="form-control text-right" placeholder="0.00" oninput="onWalletUsedChange()" onblur="formatWalletInput()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change return display for Cashier -->
                <div id="pmChangeContainer" class="text-center rounded p-1 mb-1 border-success" style="display: block; background-color: #e2f0d9; border: 1px solid #c5e0b4;">
                    <div class="small text-success font-weight-bold text-uppercase" style="font-size: .68rem;">Change to Return</div>
                    <div class="font-weight-bold text-success mb-0" id="pmChangeDue" style="font-size:1.15rem;">₹ 0.00</div>
                </div>

                <!-- Excess Payment Wallet options -->
                <div id="pmExcessWalletOption" class="rounded p-2 mb-2 border" style="display:none; background-color: #fff8e1; border-color: #ffe082 !important;">
                    <div class="small text-warning font-weight-bold text-uppercase" style="font-size: .75rem; color:#f57f17 !important;">Excess Payment Options</div>
                    <div class="d-flex justify-content-around mt-1">
                        <div class="custom-control custom-radio">
                            <input type="radio" id="pmExcessCash" name="pm_excess_action" class="custom-control-input" value="cash" checked onchange="calcPaymentChange()">
                            <label class="custom-control-label small font-weight-bold" for="pmExcessCash">Return cash change</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="pmExcessWallet" name="pm_excess_action" class="custom-control-input" value="wallet" onchange="calcPaymentChange()">
                            <label class="custom-control-label small text-success font-weight-bold" for="pmExcessWallet">Add to customer wallet</label>
                        </div>
                    </div>
                </div>

                <button type="submit" id="pmSubmitBtn" class="btn btn-dark btn-block font-weight-bold py-2 shadow-sm" style="border-radius:8px; font-size: .95rem;">
                    <i class="fas fa-check-circle mr-2"></i>Save Invoice <kbd style="background:rgba(255,255,255,.25);color:#fff;border:none;font-size:.8rem;border-radius:4px;padding:2px 6px;">F4</kbd>
                </button>
            </form>
        </div>
    </div>

</div><!-- /billing-right -->
</div><!-- /billing-body -->

<!-- Bottom function-key bar (v2) — fixed to the viewport bottom so it's always
     reachable without scrolling, regardless of how tall the cart/payment panel gets
     or how small the screen is. -->
<div class="v2-fkey-bar-fixed">
    <div class="v2-fkey-bar">
        <div class="v2-fkey-btn" onclick="openQuickEdit('qty')"><kbd>F1</kbd><i class="fas fa-hashtag"></i> Qty</div>
        <div class="v2-fkey-btn" onclick="openQuickEdit('rate')"><kbd>F2</kbd><i class="fas fa-rupee-sign"></i> Price</div>
        <div class="v2-fkey-btn" onclick="openPriceCheck()"><kbd>F3</kbd><i class="fas fa-tag"></i> Price Check</div>
        <div class="v2-fkey-btn" onclick="if (typeof validateInvoiceBeforePayment==='function' && validateInvoiceBeforePayment()) submitInvoiceForm();"><kbd>F4</kbd><i class="fas fa-check-circle"></i> Save</div>
        <div class="v2-fkey-btn" onclick="holdCurrentBill()" style="position:relative;"><kbd>F5</kbd><i class="fas fa-pause-circle"></i> Hold Bill</div>
        <div class="v2-fkey-btn" onclick="openRecallBillModal()" style="position:relative;"><kbd>F6</kbd><i class="fas fa-history"></i> Recall Bill<span id="heldBillsBadge" style="display:none;position:absolute;top:-6px;right:-6px;background:#dc2626;color:#fff;border-radius:50%;min-width:18px;height:18px;font-size:.65rem;font-weight:800;line-height:18px;text-align:center;padding:0 3px;box-shadow:0 1px 3px rgba(0,0,0,.3);"></span></div>
        <div class="v2-fkey-btn" onclick="openReprintModal()"><kbd>F7</kbd><i class="fas fa-print"></i> Reprint</div>
        <div class="v2-fkey-btn" onclick="billingIsFS() ? billingExitFS() : billingEnterFS();"><kbd>F11</kbd><i class="fas fa-expand"></i> Fullscreen</div>
    </div>
</div>
</div><!-- /billing-page-wrap -->

<input type="hidden" name="wallet_amount_used" id="wallet_amount_used" value="0.00">
<input type="hidden" name="wallet_amount_credited" id="wallet_amount_credited" value="0.00">
<!-- New hidden inputs for POS enhancements -->
<input type="hidden" name="bill_discount_type"    id="hBillDiscType"    value="none">
<input type="hidden" name="bill_discount_percent" id="hBillDiscPercent" value="0">
<input type="hidden" name="bill_discount_amount"  id="hBillDiscAmount"  value="0">
<input type="hidden" name="bill_discount_reason"  id="hBillDiscReason"  value="">
<input type="hidden" name="credit_due_date"       id="hCreditDueDate"   value="">

<?= form_close() ?>

<!-- Price Check Modal (v2) — look up MRP/selling price/scheme net price for a
     customer's question WITHOUT adding anything to the bill. -->
<div class="modal fade" id="priceCheckModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow border-0" style="border-radius:12px;">
            <div class="modal-header py-2" style="background:#3730a3;color:#fff;border-top-left-radius:12px;border-top-right-radius:12px;">
                <h6 class="modal-title font-weight-bold mb-0"><i class="fas fa-tag mr-1"></i> Price Check</h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="input-group input-group-sm mb-2 position-relative">
                    <input type="text" id="pcSearchInput" class="form-control" placeholder="Scan barcode, or type product name / code..." autocomplete="off">
                    <div class="input-group-append"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                    <div id="pcDropdown" class="list-group position-absolute" style="z-index:9999;max-height:220px;overflow-y:auto;display:none;width:100%;top:100%;left:0;"></div>
                </div>
                <div id="pcStatus" class="small text-muted mb-2" style="min-height:16px;"></div>
                <div id="pcResult" style="display:none;">
                    <div class="text-center mb-2">
                        <div class="font-weight-bold" id="pcProductName" style="font-size:1rem;"></div>
                        <div class="text-muted small" id="pcProductCode"></div>
                    </div>
                    <div class="d-flex justify-content-around text-center mb-2">
                        <div>
                            <div class="text-muted small text-uppercase" style="font-size:.68rem;">MRP</div>
                            <div class="font-weight-bold" id="pcMrp" style="font-size:1.1rem;text-decoration:line-through;color:#94a3b8;">₹ 0.00</div>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase" style="font-size:.68rem;">Selling Price</div>
                            <div class="font-weight-bold text-success" id="pcSp" style="font-size:1.1rem;">₹ 0.00</div>
                        </div>
                    </div>
                    <div id="pcSchemeBox" class="rounded p-2 text-center" style="display:none;background:#f0fdf4;border:1px solid #86efac;">
                        <div class="font-weight-bold text-success small" id="pcSchemeName"></div>
                        <div class="text-muted small text-uppercase" style="font-size:.68rem;margin-top:4px;">Scheme Net Price</div>
                        <div class="font-weight-bold" id="pcNetPrice" style="font-size:1.3rem;color:#15803d;">₹ 0.00</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recall Bill Modal (v2) — lists held bills for this location, click to resume one -->
<div class="modal fade" id="recallBillModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow border-0" style="border-radius:12px;">
            <div class="modal-header py-2" style="background:#0d5ea6;color:#fff;border-top-left-radius:12px;border-top-right-radius:12px;">
                <h6 class="modal-title font-weight-bold mb-0"><i class="fas fa-history mr-1"></i> Recall Bill</h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div id="recallBillListBody" class="list-group list-group-flush" style="max-height:400px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Item Deletion Authorization Modal -->
<div class="modal fade" id="itemAuthModal" tabindex="-1" data-backdrop="static">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white py-2">
        <h6 class="modal-title"><i class="fas fa-lock mr-1"></i> Manager Authorization Required</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-2">Item: <strong id="authItemName"></strong></p>
        <p class="small mb-2">Enter manager PIN to remove this item from the bill.</p>
        <input type="password" id="authPin" class="form-control form-control-sm text-center" placeholder="Manager PIN" maxlength="20" autocomplete="new-password" onkeydown="if(event.key==='Enter')confirmItemRemoval()">
        <div class="text-danger small mt-1" id="authPinMsg"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-danger" onclick="confirmItemRemoval()">Authorize &amp; Remove</button>
      </div>
    </div>
  </div>
</div>

<!-- Quick Create Customer Modal -->
<div class="modal fade" id="quickCustomerModal" tabindex="-1" role="dialog" aria-labelledby="quickCustomerModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow border-0" style="border-radius:12px;">
            <div class="modal-header bg-info text-white py-2" style="border-top-left-radius:12px; border-top-right-radius:12px;">
                <h6 class="modal-title font-weight-bold" id="quickCustomerModalLabel"><i class="fas fa-user-plus mr-1"></i> B2C Quick Register Customer</h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="quickCustomerForm" onsubmit="saveQuickCustomer(event)">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="quick_phone" id="quick_phone" class="form-control form-control-sm bg-light" required readonly style="font-family:monospace; font-size:1rem; letter-spacing:1px; font-weight:bold;">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" name="quick_name" id="quick_name" class="form-control form-control-sm" required placeholder="Enter Full Name..." autocomplete="off">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Address (Optional - for delivery)</label>
                        <textarea name="quick_address" id="quick_address" class="form-control form-control-sm" rows="2" placeholder="Enter Delivery Address..."></textarea>
                    </div>
                    <div id="quickCustomerError" class="alert alert-danger py-2 d-none" style="font-size:.85rem;"></div>
                    <button type="submit" class="btn btn-info btn-block font-weight-bold shadow-sm py-2" style="border-radius:8px;">Save &amp; Open Billing Screen</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Quick Edit Modal -->
<div class="modal fade" id="quickEditModal" tabindex="-1" role="dialog" aria-labelledby="quickEditModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow border-0" style="border-radius:12px;">
            <div class="modal-header bg-warning text-dark py-2" style="border-top-left-radius:12px; border-top-right-radius:12px;">
                <h6 class="modal-title font-weight-bold" id="quickEditModalLabel"><i class="fas fa-edit mr-1"></i> Edit Last Item: <span id="qeProductName" class="font-weight-bold"></span></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="quickEditForm" onsubmit="applyQuickEdit(event)">
                    <input type="hidden" id="qeRowId">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Quantity</label>
                        <input type="number" id="qeQty" class="form-control form-control-sm" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Rate / Price (₹)</label>
                        <input type="number" id="qeRate" class="form-control form-control-sm" step="0.01" min="0" required>
                    </div>
                    <button type="submit" class="btn btn-warning btn-block font-weight-bold py-2" style="border-radius:8px;">Apply Changes (Enter)</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Garment Size x Colour Sales Matrix Modal for POS Billing -->
<div class="modal fade" id="garmentSalesMatrixModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:960px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header py-3 px-4 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #4A0E17 0%, #2A080C 100%); color:#fff;">
                <div class="d-flex align-items-center">
                    <span class="mr-3" style="font-size:1.8rem;">👗</span>
                    <div>
                        <h5 class="modal-title font-weight-bold mb-0 text-white" id="gsmProductTitle">Select Garment Size &amp; Colour</h5>
                        <small class="text-white-50" id="gsmProductSubtitle">Style: - | Category: -</small>
                    </div>
                </div>
                <button type="button" class="close text-white" onclick="closeGarmentSalesMatrix()" style="opacity:0.9; font-size:1.8rem; line-height:1; border:none; background:none; cursor:pointer;" aria-label="Close">&times;</button>
            </div>
            
            <div class="modal-body p-3 bg-light">
                <!-- Info strip with price & instructions -->
                <div class="card p-2 px-3 mb-3 bg-white border" style="border-radius:8px;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-primary px-2 py-1 mr-2" style="font-size:0.85rem; background:#4A0E17; border-color:#4A0E17;" id="gsmPriceBadge">Price: ₹0.00</span>
                            <span class="badge badge-secondary px-2 py-1 mr-2" style="font-size:0.85rem;" id="gsmMrpBadge">MRP: ₹0.00</span>
                            <span class="badge badge-info px-2 py-1 mr-2" style="font-size:0.85rem;" id="gsmHsnBadge">HSN: -</span>
                            <span class="badge badge-light border px-2 py-1" style="font-size:0.85rem;" id="gsmTotalStockBadge">Total Stock: 0</span>
                        </div>
                        <div class="small text-muted font-italic mt-1 mt-md-0">
                            💡 Click <strong>+1</strong> or enter qty in any cell. Press <strong>Enter</strong> to add to invoice.
                        </div>
                    </div>
                </div>

                <!-- Matrix Grid Container -->
                <div class="table-responsive bg-white border rounded shadow-xs" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-bordered table-sm mb-0 text-center" id="gsmMatrixTable" style="vertical-align: middle;">
                        <thead class="bg-dark text-white small" id="gsmThead">
                            <!-- Header with sizes -->
                        </thead>
                        <tbody id="gsmTbody">
                            <!-- Dynamic Colour rows x Size cells -->
                        </tbody>
                        <tfoot class="bg-light font-weight-bold small" id="gsmTfoot">
                            <!-- Totals -->
                        </tfoot>
                    </table>
                </div>

                <div id="gsmNoVariantsAlert" class="alert alert-warning my-2" style="display:none;">
                    <i class="fas fa-exclamation-triangle mr-1"></i> No size/colour variants found for this garment style.
                </div>
            </div>

            <div class="modal-footer bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="text-muted small mr-2">Total Pieces Selected:</span>
                    <span class="badge badge-success px-3 py-1 font-weight-bold" style="font-size:1.1rem;" id="gsmGrandTotalBadge">0 pcs</span>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm px-3 mr-2" onclick="closeGarmentSalesMatrix()">Cancel [Esc]</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 font-weight-bold shadow-sm" id="btnGsmAddItems" onclick="gsmSubmitMatrix()" disabled style="background:#4A0E17; border-color:#4A0E17;">
                        <i class="fas fa-shopping-bag mr-1"></i> Add to Invoice [Enter]
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ALLOW_NEGATIVE_STOCK = <?= ($company['allow_negative_stock'] ?? '1') === '1' ? 'true' : 'false' ?>;
const REQUIRE_PIN_TO_DELETE = <?= ($company['require_pin_to_delete_item'] ?? '1') === '1' ? 'true' : 'false' ?>;
const PRINT_HOLD_SLIP = <?= ($company['print_hold_slip'] ?? '0') === '1' ? 'true' : 'false' ?>;
const PRODUCTS = <?= json_encode(array_map(function($p) {
    return ['id'=>(int)$p->id,'name'=>(string)($p->name??''),'item_code'=>(string)($p->item_code??''),'selling_price'=>(float)$p->selling_price,'mrp'=>(float)($p->mrp??0),'gst_percent'=>(float)$p->gst_percent,'unit'=>(string)($p->unit??''),'stock_qty'=>(float)$p->stock_qty,'hsn_code'=>(string)($p->hsn_code??''),'image1'=>(string)($p->image1??''),'no_discount'=>(int)($p->no_discount??0),'brand_id'=>(int)($p->brand_id??0),'category_id'=>(int)($p->category_id??0),'carton_size'=>(int)($p->carton_size??1),'allow_name_edit'=>(int)($p->allow_name_edit??0),
    'is_garment'=>(int)($p->is_garment??0),'style_code'=>(string)($p->style_code??''),'design_no'=>(string)($p->design_no??''),'garment_category'=>(string)($p->garment_category??''),
    'product_type'=>(string)($p->product_type??'standard'),'model_name'=>(string)($p->model_name??''),'model_number'=>(string)($p->model_number??''),'colour'=>(string)($p->colour??''),
    'battery_type'=>(string)($p->battery_type??''),'battery_capacity'=>(string)($p->battery_capacity??''),'charger_details'=>(string)($p->charger_details??''),'motor_details'=>(string)($p->motor_details??''),
    'warranty_policy'=>(string)($p->warranty_policy??''),'maintain_serial'=>(int)($p->maintain_serial??0),'warranty_applicable'=>(int)($p->warranty_applicable??0),'warranty_days'=>(int)($p->warranty_days??0)];
}, $products), JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE) ?? '[]' ?>;

const CUSTOMERS = <?= json_encode(array_map(function($c) {
    return ['id'=>(int)$c->id,'name'=>(string)($c->name??''),'phone'=>(string)($c->phone??''),'address'=>(string)($c->address??''),'type'=>(string)($c->type??''),'balance'=>(float)$c->balance,'credit_limit'=>(float)$c->credit_limit,'gst_number'=>(string)($c->gst_number??''),'state'=>(string)($c->state??''),'wallet_balance'=>(float)($c->wallet_balance??0),'discount_percent'=>(float)($c->discount_percent??0)];
}, $customers), JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE) ?? '[]' ?>;

let rowCount = 0;
let isInterstate = false;
let customerDiscPct = 0;
const COMPANY_STATE = <?= json_encode(strtolower(trim($company['company_state'] ?? 'Punjab'))) ?>;
const enableGst = <?= !empty($settings['enable_gst']) ? 'true' : 'false' ?>;
const isCashier   = <?= ($this->session->userdata('role') === 'cashier') ? 'true' : 'false' ?>;
const RATE_LOCKED = <?= (isset($allow_price_override) && (int)$allow_price_override === 0) ? 'true' : 'false' ?>;
const canDiscount = <?= !empty($can_discount) ? 'true' : 'false' ?>;

function loadCustomer(cid) {
    if (!cid) {
        customerDiscPct = 0;
        document.getElementById('customer_info').style.display='none';
        const lb = document.getElementById('last_bill_info'); if (lb) lb.style.display = 'none';
        return;
    }
    const c = CUSTOMERS.find(x => x.id == cid);
    if (!c) return;
    fetchLastBillInfo(cid);
    const invoiceType = document.getElementById('invoice_type')?.value || 'retail';
    isInterstate = (invoiceType !== 'retail' && c.state && c.state.trim().toLowerCase() !== COMPANY_STATE);
    const bal = c.balance || 0;
    const lim = c.credit_limit || 0;
    customerDiscPct = c.discount_percent || 0;
    document.getElementById('cust_detail').innerHTML =
        `<strong>${c.name}</strong> &middot; ${c.type}<br>` +
        (c.address ? `Address: ${c.address}<br>` : '') +
        (c.gst_number ? `GSTIN: <code>${c.gst_number}</code><br>` : '') +
        (lim > 0 ? `Credit Limit: ₹${lim.toFixed(2)}<br>` : '') +
        (customerDiscPct > 0 ? `<span class="badge badge-info">Customer Discount: ${customerDiscPct}%</span><br>` : '') +
        (c.wallet_balance > 0 ? `<strong class="text-success">Wallet Balance: ₹${c.wallet_balance.toFixed(2)}</strong><br>` : '<span class="text-muted">Wallet Balance: ₹0.00</span><br>') +
        (bal > 0 ? `<strong class="text-danger">Outstanding Due: ₹${bal.toFixed(2)}</strong><br>` : '<span class="text-success">No Outstanding</span><br>') +
        (isInterstate ? '<span class="badge badge-warning">Interstate — IGST applies</span>' : '<span class="badge badge-success">Intrastate — CGST+SGST</span>');
    document.getElementById('customer_info').style.display = 'block';
    applyCustomerDiscountToAll();
    document.getElementById('row_cgst').style.display = (enableGst && !isInterstate) ? '' : 'none';
    document.getElementById('row_sgst').style.display = (enableGst && !isInterstate) ? '' : 'none';
    document.getElementById('row_igst').style.display = (enableGst && isInterstate) ? '' : 'none';
    // v2: payment panel is always visible, so refresh its wallet-balance display here
    // (previously this only happened when the payment modal was opened).
    const walletBalance = c.wallet_balance || 0;
    const pmWalletUsedEl = document.getElementById('pmWalletUsed');
    if (pmWalletUsedEl) pmWalletUsedEl.value = '';
    const pmWalletContainerEl = document.getElementById('pmWalletContainer');
    if (pmWalletContainerEl) {
        if (walletBalance > 0) {
            document.getElementById('pmWalletAvailable').textContent = '₹ ' + walletBalance.toFixed(2);
            pmWalletContainerEl.style.display = 'block';
        } else {
            pmWalletContainerEl.style.display = 'none';
        }
    }
    recalcTotals();
}

function applyCustomerDiscountToAll() {
    if (!canDiscount) return;
    for (let r = 1; r <= rowCount; r++) {
        const row = document.getElementById('row_' + r);
        if (!row) continue;
        const pctInput = document.getElementById('disc_pct_' + r);
        if (!pctInput || pctInput.readOnly) continue;
        if (pctInput.dataset.manualDisc) continue;
        if (customerDiscPct > 0) {
            pctInput.value = customerDiscPct;
        } else {
            pctInput.value = '';
            document.getElementById('disc_' + r).value = '0';
        }
        calcRowPct(r);
    }
}

// Shows the customer's most recent invoice (no./amount) so the cashier can quickly
// verify their last purchase. Read-only GET lookup — no CSRF token needed.
let _lastBillReqId = 0;
function fetchLastBillInfo(cid) {
    const box = document.getElementById('last_bill_info');
    if (!box) return;
    const reqId = ++_lastBillReqId; // guard against an earlier, slower request overwriting a later one
    box.style.display = 'none';
    fetch('<?= base_url("billing/get_last_invoice_ajax/") ?>' + encodeURIComponent(cid), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (reqId !== _lastBillReqId) return; // a newer customer selection has since fired
        if (!data.success || !data.has_previous) { box.style.display = 'none'; return; }
        document.getElementById('last_bill_no').textContent = data.invoice_no;
        document.getElementById('last_bill_amount').textContent = '₹ ' + parseFloat(data.total_amount).toFixed(2);
        box.style.display = 'block';
    })
    .catch(() => { box.style.display = 'none'; });
}

// Built once at page load and cloned per row (see addRow below) — rebuilding and
// re-parsing an <option> per product (2000+ products live) on every single addRow()
// call was an O(rows x products) bottleneck that froze/crashed the tab past ~25 rows.
// cloneNode() reuses the already-parsed DOM subtree instead of re-parsing HTML text.
const _productSelectTemplate = document.createElement('select');
_productSelectTemplate.innerHTML = '<option value="">-- Product --</option>' +
    PRODUCTS.map(p => `<option value="${p.id}" data-price="${p.selling_price}" data-mrp="${p.mrp||0}" data-gst="${p.gst_percent}" data-unit="${p.unit}" data-stock="${p.stock_qty}" data-code="${p.item_code||''}" data-image="${p.image1||''}" data-no-discount="${p.no_discount||0}" data-name-edit="${p.allow_name_edit||0}">${p.name} (${p.stock_qty} ${p.unit})</option>`).join('');

// Always bring the newest row into view and flash it, regardless of which entry
// point added it (manual Row button, smart search, or a PLU barcode scan) — the
// items table itself never scrolls (CSS gives it overflow:visible so the whole
// page scrolls instead), so scrollIntoView here targets the page, not a wrapper div.
function scrollToRowAndHighlight(rid) {
    const row = document.getElementById('row_' + rid);
    if (!row) return;
    row.scrollIntoView({ block: 'end', behavior: 'smooth' });
    row.classList.remove('row-just-added');
    // Force reflow so re-adding the class restarts the animation on repeated scans.
    void row.offsetWidth;
    row.classList.add('row-just-added');
}

function addRow() {
    rowCount++;
    const html = `
    <tr id="row_${rowCount}">
        <td class="text-center align-middle"><span class="row-num">${rowCount}</span></td>
        <td>
            <div class="d-flex align-items-center" style="gap:4px;">
                <div style="width:34px;height:34px;flex-shrink:0;display:none;border:1px solid #ddd;border-radius:4px;overflow:hidden;" id="img_container_${rowCount}">
                    <img id="img_${rowCount}" src="" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <div class="flex-grow-1">
                    <span id="prod_select_slot_${rowCount}"></span>
                    <input type="hidden" name="custom_name[]" id="custom_name_${rowCount}" value="">
                    <input type="text" id="name_edit_${rowCount}" class="form-control form-control-sm mt-1 border-info" style="display:none;font-size:.8rem;" placeholder="Custom description..." oninput="document.getElementById('custom_name_${rowCount}').value=this.value">
                    <input type="hidden" name="plu_id[]" id="plu_id_${rowCount}" value="">
                    <input type="hidden" name="variant_id[]" id="variant_id_${rowCount}" value="">
                    <input type="hidden" name="variant_sku[]" id="variant_sku_${rowCount}" value="">
                    <input type="hidden" name="size_name[]" id="size_name_${rowCount}" value="">
                    <input type="hidden" name="colour_name[]" id="colour_name_${rowCount}" value="">
                    <!-- EV Dealership Serial & Vehicle Details -->
                    <input type="hidden" name="chassis_no[]" id="chassis_no_${rowCount}" value="">
                    <input type="hidden" name="motor_no[]" id="motor_no_${rowCount}" value="">
                    <input type="hidden" name="item_colour[]" id="item_colour_${rowCount}" value="">
                    <input type="hidden" name="battery_serial[]" id="battery_serial_${rowCount}" value="">
                    <input type="hidden" name="charger_serial[]" id="charger_serial_${rowCount}" value="">
                    <input type="hidden" name="warranty_terms[]" id="warranty_terms_${rowCount}" value="">
                    <input type="hidden" name="serial_json[]" id="serial_json_${rowCount}" value="">
                    <span id="variant_badge_${rowCount}" class="badge badge-info mr-1" style="font-size:0.75rem;display:none;"></span>
                    <span id="ev_badge_${rowCount}" class="badge badge-success mr-1" style="font-size:0.75rem;display:none;"></span>
                    <small id="plu_badge_${rowCount}" class="text-info" style="font-size:0.72rem;display:none;"></small>
                    <small id="scheme_badge_${rowCount}" class="text-success font-weight-bold" style="font-size:0.72rem;display:none;"><i class="fas fa-tag"></i> <span></span></small>
                    <div id="ev_detail_${rowCount}" class="mt-1" style="display:none;font-size:0.75rem;line-height:1.25;"></div>
                </div>
            </div>
        </td>
        <td class="align-middle"><span id="stock_${rowCount}" class="badge badge-secondary">—</span></td>
        <td><input type="number" name="qty[]" id="qty_${rowCount}" class="form-control form-control-sm text-center" value="1" min="1" oninput="calcRow(${rowCount})" onkeydown="if(event.key==='Enter'){event.preventDefault();calcRow(${rowCount});document.getElementById('smartSearch').focus();document.getElementById('smartSearch').select();}"></td>
        <td><input type="number" step="0.01" name="mrp[]" id="mrp_${rowCount}" class="form-control form-control-sm" value="0.00" oninput="calcRow(${rowCount})">
            <small id="sd_${rowCount}" class="text-success" style="font-size:0.68rem;font-weight:600;display:none;"></small>
        </td>
        <td>${canDiscount
            ? `<input type="number" step="0.01" min="0" max="100" id="disc_pct_${rowCount}" class="form-control form-control-sm" placeholder="%" oninput="onDiscPctInput(this,${rowCount})" onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('smartSearch').focus();}"><input type="hidden" name="item_discount[]" id="disc_${rowCount}" value="0">`
            : `<input type="hidden" name="item_discount[]" value="0"><span class="text-muted small"><i class="fas fa-lock"></i></span>`
        }</td>
        <td><input type="number" step="0.01" name="rate[]" id="rate_${rowCount}" class="form-control form-control-sm font-weight-bold" value="0.00" oninput="calcRow(${rowCount})" onkeydown="if(event.key==='Enter'){event.preventDefault();calcRow(${rowCount});document.getElementById('smartSearch').focus();document.getElementById('smartSearch').select();}" \${RATE_LOCKED ? 'readonly' : ''}></td>
        <td><span id="ean_${rowCount}" class="ean-code">—</span><input type="hidden" id="taxable_${rowCount}" value="0"><input type="hidden" id="gstamt_${rowCount}" value="0"></td>
        ${enableGst ? '<td><select name="gst_percent[]" id="gst_' + rowCount + '" class="form-control form-control-sm" onchange="calcRow(' + rowCount + ')">' + [0,3,5,12,18,40].map(function(r){return '<option value="'+r+'">'+r+'%</option>';}).join('') + '</select></td>'
        : '<td style="display:none;"><input type="hidden" name="gst_percent[]" id="gst_' + rowCount + '" value="0"></td>'
        }
        <td><input type="text" id="total_${rowCount}" class="form-control form-control-sm bg-light font-weight-bold text-right" readonly></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeRow(${rowCount})">✕</button></td>
    </tr>`;
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', html);

    // Swap in a clone of the pre-built product dropdown (see _productSelectTemplate above)
    const rid = rowCount;
    const sel = _productSelectTemplate.cloneNode(true);
    sel.name = 'product_id[]';
    sel.className = 'form-control form-control-sm';
    sel.addEventListener('change', function() {
        const pid = sel.value;
        if (pid) {
            const p = PRODUCTS.find(x => parseInt(x.id) === parseInt(pid));
            if (p && p.product_type === 'electric_scooter') {
                openEvScooterModal(pid, rid);
                return;
            }
            if (p && (parseInt(p.is_garment) === 1 || parseInt(p.variant_count) > 0)) {
                openGarmentSalesMatrix(pid, rid);
                return;
            }
        }
        fillProduct(sel, rid);
    });
    document.getElementById('prod_select_slot_' + rid).replaceWith(sel);

    updateRowCount();
    scrollToRowAndHighlight(rid);
}

function fillProduct(sel, rid) {
    const opt = sel.options[sel.selectedIndex];
    const imgContainer = document.getElementById('img_container_' + rid);
    const imgEl = document.getElementById('img_' + rid);
    if (!opt || !opt.value) {
        if (imgContainer) imgContainer.style.display = 'none';
        const pctInput = document.getElementById('disc_pct_' + rid);
        if (pctInput) {
            pctInput.value = '';
            pctInput.readOnly = false;
            pctInput.placeholder = '%';
            pctInput.style.backgroundColor = '';
        }
        const discInput = document.getElementById('disc_' + rid);
        if (discInput) {
            discInput.value = '0';
            discInput.readOnly = false;
            discInput.style.backgroundColor = '';
        }
        return;
    }
    const price   = parseFloat(opt.dataset.price);
    const gstPct  = parseFloat(opt.dataset.gst);
    const stock   = parseInt(opt.dataset.stock);
    const mrp     = parseFloat(opt.dataset.mrp) || 0;
    const image   = opt.dataset.image;
    const noDiscount = opt.dataset.noDiscount ? parseInt(opt.dataset.noDiscount) : 0;
    const nameEdit = parseInt(opt.dataset.nameEdit) || 0;

    const nameEditInput = document.getElementById('name_edit_' + rid);
    const customNameHid = document.getElementById('custom_name_' + rid);
    if (nameEditInput) {
        if (nameEdit) {
            nameEditInput.style.display = 'block';
            nameEditInput.value = opt.textContent.split('(')[0].trim();
            if (customNameHid) customNameHid.value = nameEditInput.value;
        } else {
            nameEditInput.style.display = 'none';
            nameEditInput.value = '';
            if (customNameHid) customNameHid.value = '';
        }
    }

    if (imgContainer && imgEl) {
        if (image) {
            imgEl.src = '<?= base_url("assets/uploads/products/") ?>' + image;
            imgContainer.style.display = 'block';
        } else {
            imgContainer.style.display = 'none';
        }
    }
    const rateInputEl = document.getElementById('rate_' + rid);
    rateInputEl.value = price.toFixed(2);
    rateInputEl.dataset.baseRate = price.toFixed(2); // store original for disc% calc
    document.getElementById('mrp_'  + rid).value = mrp.toFixed(2);
    const eanEl = document.getElementById('ean_' + rid);
    if (eanEl) eanEl.textContent = opt.dataset.code || '—';
    // Update right-panel product image
    const rpImg = document.getElementById('rp_img');
    const rpPlaceholder = document.getElementById('rp_img_placeholder');
    if (rpImg && image) {
        rpImg.src = '<?= base_url("assets/uploads/products/") ?>' + image;
        rpImg.style.display = 'block';
        if (rpPlaceholder) rpPlaceholder.style.display = 'none';
    }
    document.getElementById('stock_' + rid).textContent = stock;
    
    const pctInput = document.getElementById('disc_pct_' + rid);
    const discInput = document.getElementById('disc_' + rid);
    
    if (noDiscount === 1) {
        if (pctInput) {
            pctInput.value = '';
            pctInput.readOnly = true;
            pctInput.placeholder = '🚫';
            pctInput.style.backgroundColor = '#f8d7da';
        }
        if (discInput) {
            discInput.value = '0';
            discInput.readOnly = true;
            discInput.style.backgroundColor = '#f8d7da';
        }
    } else {
        if (pctInput) {
            pctInput.readOnly = false;
            pctInput.placeholder = '%';
            pctInput.style.backgroundColor = '';
            if (customerDiscPct > 0) {
                pctInput.value = customerDiscPct;
            } else {
                pctInput.value = '';
            }
        }
        if (discInput) {
            discInput.value = '0';
            discInput.readOnly = false;
            discInput.style.backgroundColor = '';
        }
    }

    document.getElementById('stock_' + rid).className = 'badge badge-' + (stock > 10 ? 'success' : stock > 0 ? 'warning' : 'danger');
    const gstSel = document.getElementById('gst_' + rid);
    if (gstSel && gstSel.options) {
        for (let i=0; i<gstSel.options.length; i++) {
            if (parseFloat(gstSel.options[i].value) === gstPct) { gstSel.selectedIndex = i; break; }
        }
    } else if (gstSel) {
        gstSel.value = gstPct;
    }
    if (customerDiscPct > 0 && noDiscount !== 1 && pctInput) {
        calcRowPct(rid);
    } else {
        calcRow(rid);
    }
}

// Marks this row's Disc% as cashier-entered so scheme auto-discount won't override
// it. Clearing the field back to blank/0 hands control back to the auto-scheme logic.
// Also releases the Rate field's "schemeAuto" claim so a later scheme re-check can't
// clobber whatever rate calcRowPct is about to set from this manual entry.
function onDiscPctInput(el, rid) {
    const v = parseFloat(el.value);
    if (!v || v <= 0) { delete el.dataset.manualDisc; } else { el.dataset.manualDisc = '1'; }
    const rateEl = document.getElementById('rate_' + rid);
    if (rateEl) delete rateEl.dataset.schemeAuto;
    calcRowPct(rid);
}

function calcRowPct(rid) {
    const rateEl   = document.getElementById('rate_' + rid);
    const discInput = document.getElementById('disc_' + rid);
    const pct      = parseFloat(document.getElementById('disc_pct_' + rid)?.value) || 0;
    const qty      = parseFloat(document.getElementById('qty_' + rid)?.value) || 0;

    // Use stored base rate so repeated disc% edits don't compound
    const baseRate = parseFloat(rateEl?.dataset.baseRate) || parseFloat(rateEl?.value) || 0;
    const netRate  = parseFloat((baseRate * (1 - pct / 100)).toFixed(2));

    // NOTE: Rate must stay at the original (undiscounted) price. calcRow()/recalcTotals()
    // and the server both compute total = rate*qty - disc — if Rate were also overwritten
    // to the discounted price here, the same discount would be subtracted twice (a real bug
    // that used to live here: a manual 10% disc on a ₹110 item produced a ₹88 total instead
    // of the correct ₹99). Only the discount AMOUNT carries the reduction.
    const discAmt = parseFloat(((baseRate - netRate) * qty).toFixed(2));
    if (discInput) discInput.value = discAmt > 0 ? discAmt.toFixed(2) : 0;

    calcRow(rid);
}

function calcRowAmt(rid) {
    const qty = parseFloat(document.getElementById('qty_' + rid)?.value) || 0;
    const rate = parseFloat(document.getElementById('rate_' + rid)?.value) || 0;
    const discAmt = parseFloat(document.getElementById('disc_' + rid)?.value) || 0;
    const pct = (rate * qty) > 0 ? parseFloat(((discAmt / (rate * qty)) * 100).toFixed(2)) : 0;
    const pctInput = document.getElementById('disc_pct_' + rid);
    if (pctInput) {
        pctInput.value = pct > 0 ? pct.toFixed(2) : '';
    }
    calcRow(rid);
}

function calcRow(rid) {
    const qty      = parseFloat(document.getElementById('qty_'   + rid)?.value) || 0;
    const rate     = parseFloat(document.getElementById('rate_'  + rid)?.value) || 0;
    const mrpEl    = document.getElementById('mrp_' + rid);
    if (mrpEl && rate > (parseFloat(mrpEl.value) || 0)) mrpEl.value = rate.toFixed(2);
    const pctInput = document.getElementById('disc_pct_' + rid);
    const discInput = document.getElementById('disc_' + rid);

    const sel = document.querySelector(`#row_${rid} select[name="product_id[]"]`);
    const opt = sel ? sel.options[sel.selectedIndex] : null;
    const noDiscount = opt && opt.dataset.noDiscount ? parseInt(opt.dataset.noDiscount) : 0;

    if (noDiscount === 1) {
        if (pctInput) pctInput.value = '';
        if (discInput) discInput.value = '0';
    }

    // rate is GST-inclusive: (rate*qty - disc) IS the final amount charged.
    // Taxable value + GST are extracted backwards out of that amount.
    const disc     = parseFloat(discInput?.value) || 0;
    const gst      = parseFloat(document.getElementById('gst_'   + rid)?.value) || 0;
    const total    = parseFloat(((rate * qty) - disc).toFixed(2));
    const taxable  = parseFloat((total / (1 + gst / 100)).toFixed(2));
    const gstAmt   = parseFloat((total - taxable).toFixed(2));
    document.getElementById('taxable_' + rid).value = taxable.toFixed(2);
    document.getElementById('gstamt_'  + rid).value = gstAmt.toFixed(2);
    document.getElementById('total_'   + rid).value = '₹ ' + total.toFixed(2);

    // Per-line "S.D" (savings) display: MRP vs Rate, so the cashier sees the
    // discount on this exact item, not just the aggregate in the sidebar.
    const mrp   = parseFloat(document.getElementById('mrp_' + rid)?.value) || 0;
    const sdVal = parseFloat(((mrp - rate) * qty).toFixed(2));
    const sdEl  = document.getElementById('sd_' + rid);
    if (sdEl) {
        if (sdVal > 0) { sdEl.textContent = 'S.D ₹' + sdVal.toFixed(2); sdEl.style.display = ''; }
        else { sdEl.style.display = 'none'; }
    }

    recalcTotals();
}

let _pendingRemoveRid = null;
let _pendingRemoveItem = null;

function removeRow(rid) {
    const r = document.getElementById('row_' + rid);
    if (!r) return;
    if (!REQUIRE_PIN_TO_DELETE) {
        r.remove(); recalcTotals(); updateRowCount();
        return;
    }
    const sel = r.querySelector('select[name="product_id[]"]');
    const opt = sel && sel.value ? sel.options[sel.selectedIndex] : null;
    const productName = opt ? opt.text.split('(')[0].trim() : 'this item';
    _pendingRemoveRid = rid;
    _pendingRemoveItem = {
        product_id: opt ? opt.value : '',
        product_name: productName,
        item_code: document.getElementById('ean_' + rid)?.textContent?.trim() || '',
        qty: document.getElementById('qty_' + rid)?.value || '0',
        rate: document.getElementById('rate_' + rid)?.value || '0',
    };
    document.getElementById('authItemName').textContent = productName;
    document.getElementById('authPin').value = '';
    document.getElementById('authPinMsg').textContent = '';
    $('#itemAuthModal').modal('show');
}

// Every removal — whichever PIN path authorizes it — gets logged server-side for the
// Reports > Removed Items audit (see verify_manager_pin_ajax()), so item/qty/rate/
// cashier/customer context travels with the auth request rather than just the PIN.
function confirmItemRemoval() {
    const pin = document.getElementById('authPin').value.trim();
    if (!pin) { document.getElementById('authPinMsg').textContent = 'Enter authorization PIN.'; return; }
    const csrfBody = (window._csrfName && window._csrfHash)
        ? '&' + encodeURIComponent(window._csrfName) + '=' + encodeURIComponent(window._csrfHash) : '';
    const item = _pendingRemoveItem || {};
    const custSel = document.getElementById('customer_id');
    const custId = custSel ? custSel.value : '';
    const custName = (custId && custSel.options[custSel.selectedIndex]) ? custSel.options[custSel.selectedIndex].textContent.split('(')[0].trim() : '';
    const body = 'pin=' + encodeURIComponent(pin) + csrfBody
        + '&product_id=' + encodeURIComponent(item.product_id || '')
        + '&product_name=' + encodeURIComponent(item.product_name || '')
        + '&item_code=' + encodeURIComponent(item.item_code || '')
        + '&qty=' + encodeURIComponent(item.qty || '0')
        + '&rate=' + encodeURIComponent(item.rate || '0')
        + '&customer_id=' + encodeURIComponent(custId || '')
        + '&customer_name=' + encodeURIComponent(custName || '')
        + '&device_label=' + encodeURIComponent(document.getElementById('hDeviceLabel')?.value || '');
    fetch('<?= base_url("billing/verify_manager_pin_ajax") ?>', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
        body: body
    }).then(r=>r.json()).then(d=>{
        if (d.success) {
            $('#itemAuthModal').modal('hide');
            const r = document.getElementById('row_' + _pendingRemoveRid);
            if (r) { r.remove(); recalcTotals(); updateRowCount(); }
            _pendingRemoveRid = null;
            _pendingRemoveItem = null;
        } else {
            document.getElementById('authPinMsg').textContent = d.error || 'Invalid PIN. Authorization denied.';
        }
    }).catch(()=>{
        document.getElementById('authPinMsg').textContent = 'Server error. Try again.';
    });
}

function recalcTotals() {
    let mrpTotal = 0, spTotal = 0, itemDisc = 0, taxable = 0, totalGst = 0, totalQty = 0;
    const rows = document.querySelectorAll('#itemsBody tr');
    const rpItemCount = document.getElementById('rp_item_count');
    const rpTotalQty  = document.getElementById('rp_total_qty');
    let filledRows = 0;
    rows.forEach(function(row) {
        const rid  = row.id.replace('row_','');
        const qty  = parseFloat(document.getElementById('qty_' + rid)?.value) || 0;
        const rate = parseFloat(document.getElementById('rate_'+ rid)?.value) || 0; // list rate; manual Disc% is carried separately in `disc`, scheme auto-discount reduces this directly
        const mrp  = parseFloat(document.getElementById('mrp_' + rid)?.value) || rate;
        const disc = parseFloat(document.getElementById('disc_'+ rid)?.value) || 0;
        const gst  = parseFloat(document.getElementById('gst_' + rid)?.value) || 0;
        const sel  = row.querySelector('select[name="product_id[]"]');
        if (sel && sel.value) { filledRows++; totalQty += qty; }
        mrpTotal  += mrp * qty;
        spTotal   += rate * qty;
        itemDisc  += (mrp - rate) * qty + disc; // MRP→SP saving + any extra disc
        // Rate is GST-inclusive: (rate*qty - disc) IS the final line amount.
        // Taxable value + GST are extracted backwards out of that amount.
        const lineTotal   = (rate * qty) - disc;
        const lineTaxable = gst > 0 ? lineTotal / (1 + gst / 100) : lineTotal;
        taxable   += lineTaxable;
        totalGst  += (lineTotal - lineTaxable);
    });
    // alias for downstream use
    const subtotal = mrpTotal;
    const totalDisc = itemDisc;
    if (rpItemCount) rpItemCount.textContent = filledRows;
    if (rpTotalQty)  rpTotalQty.textContent  = totalQty % 1 === 0 ? totalQty : totalQty.toFixed(2);
    totalGst = parseFloat(totalGst.toFixed(2));
    const preCdTotal    = parseFloat((taxable + totalGst).toFixed(2));
    const cdAmountInput = document.getElementById('cd_amount');
    const cdPctEl2      = document.getElementById('cd_percent');
    let cdAmt;
    if (_cdMode === 'amt') {
        // user typed a rupee amount — use it exactly
        cdAmt = parseFloat(cdAmountInput?.value) || 0;
    } else {
        // user typed a percentage — derive amount from it
        const cdPct = cdPctEl2 ? (parseFloat(cdPctEl2.value) || 0) : 0;
        cdAmt = parseFloat(((preCdTotal * cdPct) / 100).toFixed(2));
        if (cdAmountInput && document.activeElement !== cdAmountInput)
            cdAmountInput.value = cdAmt > 0 ? cdAmt.toFixed(2) : 0;
    }
    
    const grandTotal = parseFloat((preCdTotal - cdAmt).toFixed(2));

    // Recalculate taxable/GST after CD — CD reduces the GST-inclusive total,
    // so taxable and GST must be re-extracted proportionally.
    if (cdAmt > 0 && preCdTotal > 0) {
        const ratio = grandTotal / preCdTotal;
        taxable  = parseFloat((taxable * ratio).toFixed(2));
        totalGst = parseFloat((grandTotal - taxable).toFixed(2));
    }

    // Round Off (optional) — round to nearest ₹1: <50 paise rounds down, >=50 paise rounds up
    let roundOff = 0;
    const roundOffEnabled = document.getElementById('round_off_enabled') && document.getElementById('round_off_enabled').checked;
    if (roundOffEnabled) {
        const paise = Math.round((grandTotal - Math.floor(grandTotal)) * 100);
        if (paise > 0) roundOff = paise < 50 ? -(paise / 100) : ((100 - paise) / 100);
        roundOff = parseFloat(roundOff.toFixed(2));
    }
    const finalTotal = parseFloat((grandTotal + roundOff).toFixed(2));

    const paid       = parseFloat(document.getElementById('paid_amount').value) || 0;
    const walletUsed = parseFloat(document.getElementById('wallet_amount_used').value) || 0;
    const due        = parseFloat((finalTotal - paid - walletUsed).toFixed(2));
    const cgst       = isInterstate ? 0 : parseFloat((totalGst / 2).toFixed(2));
    const sgst       = isInterstate ? 0 : parseFloat((totalGst - cgst).toFixed(2));
    const igst       = isInterstate ? totalGst : 0;

    const footTax = document.getElementById('foot_taxable'); if (footTax) footTax.textContent = '₹ ' + taxable.toFixed(2);
    const footGst = document.getElementById('foot_gst'); if (footGst) footGst.textContent = '₹ ' + totalGst.toFixed(2);
    document.getElementById('foot_total').textContent   = '₹ ' + finalTotal.toFixed(2);
    const totalSaving = parseFloat((mrpTotal - finalTotal).toFixed(2));
    document.getElementById('s_subtotal').textContent   = '₹ ' + mrpTotal.toFixed(2);
    document.getElementById('s_disc').textContent       = itemDisc > 0 ? '- ₹ ' + itemDisc.toFixed(2) : '₹ 0.00';
    const sSaving = document.getElementById('s_saving');
    if (sSaving) { sSaving.textContent = '₹ ' + (totalSaving > 0 ? totalSaving.toFixed(2) : '0.00'); sSaving.closest('.saving-row').style.display = totalSaving > 0 ? '' : 'none'; }
    document.getElementById('s_taxable').textContent    = '₹ ' + taxable.toFixed(2);
    document.getElementById('s_cgst').textContent       = '₹ ' + cgst.toFixed(2);
    document.getElementById('s_sgst').textContent       = '₹ ' + sgst.toFixed(2);
    document.getElementById('s_igst').textContent       = '₹ ' + igst.toFixed(2);
    document.getElementById('s_cd').textContent         = cdAmt > 0 ? '- ₹ ' + cdAmt.toFixed(2) : '- ₹ 0.00';
    const sRoundOff = document.getElementById('s_round_off');
    if (sRoundOff) sRoundOff.textContent = (roundOff >= 0 ? '+ ₹ ' : '- ₹ ') + Math.abs(roundOff).toFixed(2);
    document.getElementById('s_total').textContent      = '₹ ' + finalTotal.toFixed(2);
    const sTotalTop = document.getElementById('s_total_top'); if (sTotalTop) sTotalTop.textContent = '₹ ' + finalTotal.toFixed(2);
    // sync right-panel paid input → header hidden input
    var rightPaid = document.getElementById('paid_amount_right');
    if (rightPaid && document.activeElement !== rightPaid) rightPaid.value = paid.toFixed(2);
    document.getElementById('s_due').textContent        = '₹ ' + due.toFixed(2);
    document.getElementById('s_due').style.color        = due > 0 ? '#dc3545' : '#28a745';
    const v2Badge = document.getElementById('v2ItemsBadge'); if (v2Badge) v2Badge.textContent = filledRows;
    evaluateSchemes();
    // Keep the always-visible payment panel's totals live without resetting user input.
    // Guarded because updateNetPayable()/calcPaymentChange() call back into recalcTotals().
    if (!_paySyncing) { _paySyncing = true; refreshPaymentDisplay(); _paySyncing = false; }
}

document.getElementById('paid_amount').addEventListener('input', recalcTotals);
addRow();

// Settings > Billing > "Default Payment Mode" drives the payment panel's starting
// selection — applied here on first load, and again from resetBillingScreen() so a
// Held bill's payment mode never leaks into the next sale (see that function below).
// Deliberately does NOT call onPaymentModeChange()/recalcTotals() — this runs before
// the cart has any items, and routing through that whole recalc/payment cascade this
// early (rather than the usual trigger, a user click) isn't needed just to pre-select
// a dropdown value, so it's skipped rather than risking it on a cold, item-less form.
const DEFAULT_PAYMENT_MODE = <?= json_encode($defaultPayMode) ?>;
function applyDefaultPaymentMode() {
    const pmSel = document.getElementById('pmPaymentMode');
    if (pmSel) pmSel.value = DEFAULT_PAYMENT_MODE;
    const hiddenSel = document.querySelector('select[name="payment_mode"]');
    if (hiddenSel) hiddenSel.value = DEFAULT_PAYMENT_MODE;
    const otherSel = document.getElementById('pmOtherMode');
    if (otherSel) otherSel.value = ['neft', 'cheque', 'credit'].includes(DEFAULT_PAYMENT_MODE) ? DEFAULT_PAYMENT_MODE : '';
    const cardDetails = document.getElementById('singleCardDetails');
    if (cardDetails) cardDetails.style.display = (DEFAULT_PAYMENT_MODE === 'card') ? '' : 'none';
    const creditDue = document.getElementById('singleCreditDue');
    if (creditDue) creditDue.style.display = (DEFAULT_PAYMENT_MODE === 'credit') ? '' : 'none';
    if (typeof syncPayModeButtons === 'function') syncPayModeButtons();
}
applyDefaultPaymentMode();

/* ══════════════════════════════════════════════
   UNIFIED SMART PRODUCT SEARCH & SCAN
   ══════════════════════════════════════════════ */
const smartSearchInput = document.getElementById('smartSearch');
const searchDropdown   = document.getElementById('searchDropdown');
const barcodeStatus    = document.getElementById('barcodeStatus');

let scanChain = Promise.resolve(); // serialises barcode lookups (rapid scans)
smartSearchInput.addEventListener('input', function() {
    const q = this.value.trim();
    // Clearing innerHTML (not just hiding) matters here: the Enter-key handler below
    // falls back to "first .search-item-link on the page" when a scan finds nothing,
    // and querySelectorAll finds hidden elements just fine — a stale match left over
    // from an earlier, shorter keystroke would otherwise get silently billed as if it
    // were the scanned item.
    if (q.length < 1) { searchDropdown.style.display = 'none'; searchDropdown.innerHTML = ''; return; }
    const results = PRODUCTS.filter(p =>
        p.name.toLowerCase().includes(q.toLowerCase()) ||
        (p.item_code && p.item_code.toLowerCase().includes(q.toLowerCase()))
    ).slice(0, 12);
    if (!results.length) { searchDropdown.style.display = 'none'; searchDropdown.innerHTML = ''; return; }
    selectedSearchIdx = -1;
    searchDropdown.innerHTML = results.map((p, idx) => {
        const imgUrl = p.image1 ? '<?= base_url("assets/uploads/products/") ?>' + p.image1 : '';
        const imgHtml = imgUrl ? `<img src="${imgUrl}" style="width:28px; height:28px; object-fit:cover; border-radius:4px; margin-right:8px; border:1px solid #ddd;">` : `<div style="width:28px; height:28px; border-radius:4px; margin-right:8px; border:1px dashed #ccc; background:#f8f9fa; display:inline-block;"></div>`;
        const sp  = parseFloat(p.selling_price);
        const mrp = parseFloat(p.mrp) || 0;
        const saving = mrp > sp ? (mrp - sp).toFixed(2) : 0;
        const mrpHtml = mrp > sp
            ? `<span style="text-decoration:line-through;opacity:.65;font-size:.75rem;">₹${mrp.toFixed(2)}</span> `
            : '';
        const saveHtml = saving > 0
            ? `<span style="font-size:.7rem;background:rgba(255,255,255,.25);border-radius:3px;padding:0 3px;margin-left:3px;">Save ₹${saving}</span>`
            : '';
        const isGarment = (parseInt(p.is_garment) === 1 || parseInt(p.variant_count) > 0);
        const garmentBadge = isGarment 
            ? `<span class="badge badge-pill badge-danger ml-1" style="font-size:0.65rem; background:#4A0E17; border:1px solid #731322;" title="Garment Size/Colour Matrix"><i class="fas fa-tshirt mr-1"></i>Matrix</span>` 
            : '';
        return `<a href="#" class="list-group-item list-group-item-action py-1 px-2 search-item-link" style="font-size:.82rem;"
            data-id="${p.id}" onclick="addProductFromSearch(${p.id}); return false;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center" style="min-width:0;flex:1;">
                    ${imgHtml}
                    <div style="min-width:0;">
                        <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${p.name}${garmentBadge}</div>
                        <div style="font-size:.72rem;opacity:.7;">${p.item_code||''} ${p.style_code ? '· ' + p.style_code : ''}</div>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;margin-left:8px;">
                    <div>
                        <span class="badge badge-${p.stock_qty>10?'success':p.stock_qty>0?'warning':'danger'}">${p.stock_qty} ${p.unit}</span>
                    </div>
                    <div style="margin-top:2px;">
                        ${mrpHtml}<strong>₹${sp.toFixed(2)}</strong>${saveHtml}
                    </div>
                </div>
            </div>
         </a>`;
    }).join('');
    searchDropdown.style.display = 'block';
});

function learnProduct(p) {
    const existing = PRODUCTS.find(x => x.id === p.id);
    if (existing) {
        if (p.is_garment !== undefined) existing.is_garment = p.is_garment;
        if (p.variant_count !== undefined) existing.variant_count = p.variant_count;
        if (p.style_code !== undefined) existing.style_code = p.style_code;
        return;
    }
    PRODUCTS.push(p);
    const optHtml = `<option value="${p.id}" data-price="${p.selling_price}" data-mrp="${p.mrp||0}" data-gst="${p.gst_percent}" data-unit="${p.unit}" data-stock="${p.stock_qty}" data-code="${p.item_code||''}" data-image="${p.image1||''}" data-no-discount="${p.no_discount||0}" data-name-edit="${p.allow_name_edit||0}">${p.name} (${p.stock_qty} ${p.unit})</option>`;
    _productSelectTemplate.insertAdjacentHTML('beforeend', optHtml);
    document.querySelectorAll('#itemsBody select[name="product_id[]"]').forEach(function(sel) {
        sel.insertAdjacentHTML('beforeend', optHtml);
    });
}

// Support keyboard navigation in dropdown list
let selectedSearchIdx = -1;
smartSearchInput.addEventListener('keydown', function(e) {
    const links = document.querySelectorAll('.search-item-link');
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (links.length > 0) {
            selectedSearchIdx = (selectedSearchIdx + 1) % links.length;
            highlightSearchItem(links);
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (links.length > 0) {
            selectedSearchIdx = (selectedSearchIdx - 1 + links.length) % links.length;
            highlightSearchItem(links);
        }
    } else if (e.key === 'Escape') {
        searchDropdown.style.display = 'none';
        selectedSearchIdx = -1;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const activeLink = document.querySelector('.search-item-link.active-search-item');
        // If a dropdown item is highlighted, pick it immediately — skip barcode AJAX
        if (activeLink) {
            const pid = activeLink.getAttribute('data-id');
            addProductFromSearch(pid);
            return;
        }
        const q = this.value.trim();
        if (!q) return;

        // 1. First try barcode lookup via AJAX
        barcodeStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking barcode/code...';
        barcodeStatus.className = 'form-text text-muted';

        this.value = '';
        scanChain = scanChain.then(() => fetch('<?= base_url("billing/get_by_barcode_ajax") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'barcode=' + encodeURIComponent(q)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success && data.ambiguous) {
                barcodeStatus.innerHTML = '✗ ' + data.message;
                barcodeStatus.className = 'form-text text-danger font-weight-bold';
                return;
            }
            if (data.success && data.mode === 'serial_ev') {
                if (data.item_type === 'scooter') {
                    openEvScooterModal(data.product.id, null, data.chassis_no || data.scanned_sn);
                    barcodeStatus.innerHTML = '✓ EV Chassis Detected: <strong>' + (data.chassis_no || data.scanned_sn) + '</strong> (' + data.product.name + ')';
                    barcodeStatus.className = 'form-text text-success font-weight-bold';
                } else {
                    learnProduct({
                        id: parseInt(data.product.id), name: data.product.name,
                        item_code: data.product.item_code || '', selling_price: parseFloat(data.product.selling_price) || 0,
                        mrp: parseFloat(data.product.mrp) || 0, gst_percent: parseFloat(data.product.gst_percent) || 0,
                        unit: data.product.unit || 'PCS', stock_qty: parseFloat(data.product.stock_qty) || 0,
                        hsn_code: data.product.hsn_code || '', image1: data.product.image1 || '',
                        no_discount: parseInt(data.product.no_discount) || 0,
                        allow_name_edit: parseInt(data.product.allow_name_edit) || 0,
                        maintain_serial: parseInt(data.product.maintain_serial) || 0,
                        warranty_applicable: parseInt(data.product.warranty_applicable) || 0,
                        warranty_days: parseInt(data.product.warranty_days) || 0,
                        carton_size: parseInt(data.product.carton_size) || 1
                    });
                    let rid = findEmptyRow();
                    if (rid === null) { addRow(); rid = rowCount; }
                    const sel = document.querySelector(`#row_${rid} select[name="product_id[]"]`);
                    if (sel) { sel.value = data.product.id; fillProduct(sel, rid); }
                    if (data.item_type === 'battery') document.getElementById('battery_serial_' + rid).value = data.scanned_sn;
                    if (data.item_type === 'charger') document.getElementById('charger_serial_' + rid).value = data.scanned_sn;
                    document.getElementById('serial_json_' + rid).value = JSON.stringify([data.scanned_sn]);
                    const evDet = document.getElementById('ev_detail_' + rid);
                    if (evDet) {
                        evDet.innerHTML = '<span class="badge badge-info"><i class="fas fa-barcode mr-1"></i>S/N: ' + data.scanned_sn + '</span>';
                        evDet.style.display = 'block';
                    }
                    barcodeStatus.innerHTML = '✓ Component S/N Found: <strong>' + data.scanned_sn + '</strong> (' + data.product.name + ')';
                    barcodeStatus.className = 'form-text text-success font-weight-bold';
                }
                return;
            }
            if (data.success && data.mode === 'garment' && data.variant) {
                // Garment variant scanned — auto-add exact size & colour
                addGarmentToRow(data.variant, data.product);
                barcodeStatus.innerHTML = '✓ Garment Scanned: <strong>' + (data.product ? data.product.name : data.variant.product_name) + '</strong> (' + (data.variant.colour_name || '') + ' / ' + (data.variant.size_name || '') + ') ₹' + (data.variant.selling_price || 0);
                barcodeStatus.className = 'form-text text-success font-weight-bold';
            } else if (data.success && data.mode === 'single') {
                // One PLU with stock — auto-add
                addPluToRow(data.plu);
                barcodeStatus.innerHTML = '✓ PLU Found & Added: <strong>' + data.plu.name + '</strong> [' + data.plu.plu_code + '] ₹' + data.plu.selling_rate;
                barcodeStatus.className = 'form-text text-success font-weight-bold';
            } else if (data.success && data.mode === 'multiple') {
                // Multiple PLUs — show popup to pick MRP
                openPluSelectModal(data.plus);
                barcodeStatus.innerHTML = '<i class="fas fa-list"></i> Multiple MRPs found — select one';
                barcodeStatus.className = 'form-text text-warning font-weight-bold';
            } else if (data.success && data.mode === 'legacy' && data.product) {
                // Legacy product (no PLU) — inject into dropdown first
                learnProduct({
                    id: parseInt(data.product.id), name: data.product.name,
                    item_code: data.product.item_code || '', selling_price: parseFloat(data.product.selling_price) || 0,
                    mrp: parseFloat(data.product.mrp) || 0, gst_percent: parseFloat(data.product.gst_percent) || 0,
                    unit: data.product.unit || 'PCS', stock_qty: parseFloat(data.product.stock_qty) || 0,
                    hsn_code: data.product.hsn_code || '', image1: data.product.image1 || '',
                    no_discount: parseInt(data.product.no_discount) || 0,
                    allow_name_edit: parseInt(data.product.allow_name_edit) || 0,
                    maintain_serial: parseInt(data.product.maintain_serial) || 0,
                    warranty_applicable: parseInt(data.product.warranty_applicable) || 0,
                    warranty_days: parseInt(data.product.warranty_days) || 0,
                    carton_size: parseInt(data.product.carton_size) || 1
                });
                addProductFromSearch(data.product.id);
                barcodeStatus.innerHTML = '✓ Barcode Found & Added: <strong>' + data.product.name + '</strong>';
                barcodeStatus.className = 'form-text text-success font-weight-bold';
            } else if (data.success && data.product) {
                learnProduct({
                    id: parseInt(data.product.id), name: data.product.name,
                    item_code: data.product.item_code || '', selling_price: parseFloat(data.product.selling_price) || 0,
                    mrp: parseFloat(data.product.mrp) || 0, gst_percent: parseFloat(data.product.gst_percent) || 0,
                    unit: data.product.unit || 'PCS', stock_qty: parseFloat(data.product.stock_qty) || 0,
                    hsn_code: data.product.hsn_code || '', image1: data.product.image1 || '',
                    no_discount: parseInt(data.product.no_discount) || 0,
                    allow_name_edit: parseInt(data.product.allow_name_edit) || 0,
                    maintain_serial: parseInt(data.product.maintain_serial) || 0,
                    warranty_applicable: parseInt(data.product.warranty_applicable) || 0,
                    warranty_days: parseInt(data.product.warranty_days) || 0,
                    carton_size: parseInt(data.product.carton_size) || 1
                });
                addProductFromSearch(data.product.id);
                barcodeStatus.innerHTML = '✓ Barcode Found & Added: <strong>' + data.product.name + '</strong>';
                barcodeStatus.className = 'form-text text-success font-weight-bold';
            } else {
                // Barcode not found, check for exact SKU/item_code match locally
                const localMatch = PRODUCTS.find(x => x.item_code && x.item_code.toLowerCase() === q.toLowerCase());
                if (localMatch) {
                    addProductFromSearch(localMatch.id);
                    barcodeStatus.innerHTML = '✓ Code Found & Added: <strong>' + localMatch.name + '</strong>';
                    barcodeStatus.className = 'form-text text-success font-weight-bold';
                } else if (activeLink) {
                    // Use currently highlighted dropdown item
                    const pid = activeLink.getAttribute('data-id');
                    addProductFromSearch(pid);
                    barcodeStatus.innerHTML = '✓ Selected: <strong>' + activeLink.querySelector('strong').textContent + '</strong>';
                    barcodeStatus.className = 'form-text text-success font-weight-bold';
                } else if (links.length > 0) {
                    // Fallback to first search result if any
                    const pid = links[0].getAttribute('data-id');
                    addProductFromSearch(pid);
                    barcodeStatus.innerHTML = '✓ Selected: <strong>' + links[0].querySelector('strong').textContent + '</strong>';
                    barcodeStatus.className = 'form-text text-success font-weight-bold';
                } else {
                    barcodeStatus.innerHTML = '✗ Barcode not found. Please check the barcode or search for the product. (' + q + ')';
                    barcodeStatus.className = 'form-text text-danger font-weight-bold';
                }
            }
        })
        .catch(() => {
            barcodeStatus.innerHTML = '✗ Error during lookup.';
            barcodeStatus.className = 'form-text text-danger font-weight-bold';
        })).then(() => new Promise(r => setTimeout(r, 120))); // let the row finish filling before the next scan
    }
});

function highlightSearchItem(links) {
    links.forEach((link, idx) => {
        if (idx === selectedSearchIdx) {
            link.style.setProperty('background-color', '#1e40af', 'important');
            link.style.setProperty('color', '#ffffff', 'important');
            link.style.setProperty('border-left', '4px solid #93c5fd', 'important');
            link.style.setProperty('font-weight', '700', 'important');
            link.classList.add('active-search-item');
            link.scrollIntoView({ block: 'nearest' });
        } else {
            link.style.removeProperty('background-color');
            link.style.removeProperty('color');
            link.style.removeProperty('border-left');
            link.style.removeProperty('font-weight');
            link.classList.remove('active-search-item');
        }
    });
}

function addProductFromSearch(productId) {
    searchDropdown.style.display = 'none';
    smartSearchInput.value = '';
    selectedSearchIdx = -1;

    // Check if product is an EV Scooter
    const p = PRODUCTS.find(x => parseInt(x.id) === parseInt(productId));
    if (p && p.product_type === 'electric_scooter') {
        openEvScooterModal(productId);
        return;
    }

    // Check if product is a garment with size/colour variants
    if (p && (parseInt(p.is_garment) === 1 || parseInt(p.variant_count) > 0)) {
        openGarmentSalesMatrix(productId);
        return;
    }

    // Quick check with server matrix if not marked locally as garment
    fetch('<?= base_url("billing/get_variants_matrix_ajax") ?>?product_id=' + productId)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.count > 0) {
                if (p) { p.is_garment = 1; p.variant_count = res.count; }
                gsmCurrentData = res;
                $('#garmentSalesMatrixModal').modal('show');
                renderGarmentSalesMatrix(res);
            } else {
                addRowWithProduct(productId);
                smartSearchInput.focus();
            }
        })
        .catch(() => {
            addRowWithProduct(productId);
            smartSearchInput.focus();
        });
}

document.addEventListener('click', function(e) {
    if (!searchDropdown.contains(e.target) && e.target !== smartSearchInput) {
        searchDropdown.style.display = 'none';
    }
});

function triggerSmartSearch() {
    smartSearchInput.dispatchEvent(new Event('input'));
}

function findEmptyRow() {
    let emptyRid = null;
    document.querySelectorAll('#itemsBody tr').forEach(function(row) {
        const rid  = row.id.replace('row_','');
        const sel  = row.querySelector('select[name="product_id[]"]');
        if (sel && !sel.value) {
            emptyRid = rid;
        }
    });
    return emptyRid;
}

function addRowWithProduct(productId) {
    // Check if product already exists in any row — if so, just increment qty
    const existingSelects = document.querySelectorAll('#itemsBody select[name="product_id[]"]');
    for (const sel of existingSelects) {
        if (parseInt(sel.value) === parseInt(productId)) {
            const rowEl = sel.closest('tr');
            const rid = rowEl.id.replace('row_', '');
            const qtyEl = document.getElementById('qty_' + rid);
            if (qtyEl) {
                qtyEl.value = parseInt(qtyEl.value || 1) + 1;
                calcRow(parseInt(rid));
                scrollToRowAndHighlight(rid);
            }
            return;
        }
    }
    // Product not in list — add new row
    let rid = findEmptyRow();
    if (rid === null) {
        addRow();
        rid = rowCount;
    }
    const sel = document.querySelector(`#row_${rid} select[name="product_id[]"]`);
    if (sel) {
        sel.value = productId;
        fillProduct(sel, rid);
    }
    updateRowCount();
    scrollToRowAndHighlight(rid);
}

function updateRowCount() {
    const cnt = document.querySelectorAll('#itemsBody tr').length;
    const el = document.getElementById('rowCount');
    if (el) el.textContent = cnt;
}

// Fullscreen handler — real browser Fullscreen API, so the address bar/tabs are
// actually hidden (not just the in-page nav). Trade-off: browsers force Escape to
// exit fullscreen as a non-cancelable security behavior no page JS can prevent, so
// if Price Check is open when that happens, the fullscreenchange listener below
// closes it too — that makes Escape feel like "one action" even though it's really
// the browser's own exit-fullscreen reaction plus our follow-up cleanup.
function billingEnterFS() {
    const el = document.documentElement;
    const p = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
    if (p) p.call(el).catch(() => {});
}
function billingExitFS() {
    if (document.exitFullscreen)            document.exitFullscreen();
    else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
}
function billingIsFS() {
    return !!document.fullscreenElement;
}
// Toggle on F11
document.addEventListener('keydown', function(e) {
    if (e.key === 'F11') { e.preventDefault(); billingIsFS() ? billingExitFS() : billingEnterFS(); }
});
document.addEventListener('fullscreenchange', function() {
    document.body.classList.toggle('billing-fs', billingIsFS());
    // Real fullscreen changes the viewport dramatically — the right panel's fitted
    // height needs recalculating either way (entering or exiting).
    setTimeout(fitBillingRightPanel, 0);
    // See comment above: Escape exiting fullscreen is unavoidable, so close Price
    // Check in that same action instead of requiring a second Escape.
    if (!billingIsFS()) {
        const pcModalEl = document.getElementById('priceCheckModal');
        if (pcModalEl && pcModalEl.classList.contains('show')) {
            $('#priceCheckModal').modal('hide');
        }
    }
});

// Browsers block a page from entering fullscreen on its own the instant it loads —
// it's only allowed as the direct result of a user gesture (security rule, not a bug
// here). This gets as close to "always fullscreen" as that allows: the cashier's very
// first click/tap/keystroke on the billing screen triggers it automatically, so they
// never have to find F11 or the fullscreen button themselves. Fires once, then gets
// out of the way — it must not keep re-triggering on every later click.
(function () {
    function autoEnterFSOnce() {
        document.removeEventListener('click', autoEnterFSOnce);
        document.removeEventListener('keydown', autoEnterFSOnce);
        if (!billingIsFS()) billingEnterFS();
    }
    document.addEventListener('click', autoEnterFSOnce);
    document.addEventListener('keydown', autoEnterFSOnce);
})();

// Focus B2C customer phone input on page load for quick billing flow
setTimeout(() => {
    const custPhoneSearch = document.getElementById('cust_phone_search');
    if (custPhoneSearch) custPhoneSearch.focus();
}, 200);

/* ══════════════════════════════════════════════
   LIVE SCHEME EVALUATION
══════════════════════════════════════════════ */
let schemeTimer = null;
function evaluateSchemes() {
    clearTimeout(schemeTimer);
    schemeTimer = setTimeout(_doEvalSchemes, 700);
}

function _doEvalSchemes() {
    // customer_id is blank for Walk-in (the default, most common POS sale) — that's
    // still a valid customer for "all-party" schemes, so don't bail out here. Only
    // customer-specific ("selected") schemes won't match with no customer chosen,
    // which SchemeEngine already handles correctly server-side.
    const custId = parseInt(document.getElementById('customer_id').value) || 0;

    const items = [];
    window._schemeRowMap = {}; // product_id -> [row ids currently on the bill]
    document.querySelectorAll('#itemsBody tr').forEach(function(row) {
        const rid  = row.id.replace('row_', '');
        const sel  = row.querySelector('select[name="product_id[]"]');
        const pid  = parseInt(sel?.value) || 0;
        const qty  = parseFloat(document.getElementById('qty_'  + rid)?.value) || 0;
        const disc = parseFloat(document.getElementById('disc_' + rid)?.value) || 0;
        const rate = parseFloat(document.getElementById('rate_' + rid)?.value) || 0;
        const gst  = parseFloat(document.getElementById('gst_'  + rid)?.value) || 0;
        const tax  = (rate * qty) - disc;
        const prod = PRODUCTS.find(p => p.id === pid);
        if (pid && qty > 0) {
            items.push({
                product_id: pid, qty: qty, uom: 'pcs', taxable_amt: tax, gst_percent: gst,
                mrp: prod ? prod.mrp : 0,
                carton_size: prod ? prod.carton_size : 1,
                brand_id: prod ? prod.brand_id : 0,
                category_id: prod ? prod.category_id : 0
            });
            (window._schemeRowMap[pid] = window._schemeRowMap[pid] || []).push(rid);
        }
    });

    if (!items.length) { _hideSchemePanel(); return; }

    const billTotal   = items.reduce((s, i) => s + i.taxable_amt, 0);
    const paymentMode = document.querySelector('select[name="payment_mode"]')?.value || 'credit';
    const params      = new URLSearchParams({
        customer_id:  custId,
        bill_total:   billTotal,
        payment_mode: paymentMode,
        items:        JSON.stringify(items)
    });

    fetch('<?= base_url("schemes/evaluate_ajax") ?>', {
        method:  'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body:    params.toString()
    })
    .then(r => r.json())
    .then(data => { if (data.success) _renderSchemes(data.schemes); else _hideSchemePanel(); })
    .catch(() => _hideSchemePanel());
}

function _renderSchemes(s) {
    // No separate summary panel — the per-row badge + the auto-discounted Rate/Disc%
    // on each line is all the cashier needs to see.
    _updateSchemeRowBadges(s.discount_breakup || []);
}

function _hideSchemePanel() {
    _updateSchemeRowBadges([]);
}

// Show a per-row "scheme applied" tag on each line item, and auto-discount that
// row's Rate/Disc% by the scheme's amount — so the discount is baked into the
// price the cashier sees per line, and the Grand Total already reflects it
// without a separate bill-level deduction (avoids double-counting the saving).
function _updateSchemeRowBadges(discountBreakup) {
    document.querySelectorAll('[id^="scheme_badge_"]').forEach(function(el) {
        el.style.display = 'none';
        el.querySelector('span').textContent = '';
    });

    const map = window._schemeRowMap || {};
    const rowInfo = {};
    document.querySelectorAll('#itemsBody tr').forEach(function(row) {
        const rid = row.id.replace('row_', '');
        rowInfo[rid] = {
            mrp: parseFloat(document.getElementById('mrp_' + rid)?.value) || 0,
            qty: parseFloat(document.getElementById('qty_' + rid)?.value) || 0,
        };
    });

    // rid -> total ₹ discount attributed to that row this round
    const rowDiscount = {};
    (discountBreakup || []).forEach(function(d) {
        const rids = [];
        (d.product_ids || []).forEach(function(pid) { (map[pid] || []).forEach(function(rid) { rids.push(rid); }); });
        if (!rids.length) return;

        rids.forEach(function(rid) {
            const el = document.getElementById('scheme_badge_' + rid);
            if (el) { el.style.display = ''; el.querySelector('span').textContent = d.scheme_name; }
        });

        // Split the scheme's discount across matching rows by MRP value share
        // (mirrors how SchemeEngine computes the discount off MRP internally).
        const totalVal = rids.reduce((sum, rid) => sum + (rowInfo[rid] ? rowInfo[rid].mrp * rowInfo[rid].qty : 0), 0);
        if (totalVal <= 0) return;
        rids.forEach(function(rid) {
            const val = rowInfo[rid] ? rowInfo[rid].mrp * rowInfo[rid].qty : 0;
            rowDiscount[rid] = (rowDiscount[rid] || 0) + (val / totalVal) * d.discount;
        });
    });

    Object.keys(rowInfo).forEach(function(rid) {
        if (rowDiscount[rid] > 0) {
            _applySchemeDiscount(rid, rowDiscount[rid]);
        } else {
            _clearSchemeDiscount(rid);
        }
    });
}

// Bake a scheme's ₹ discount straight into the row's Rate field (the same field the
// cashier sees and that gets submitted) — NOT the Disc-amount field too, since calcRow()
// already computes total = rate*qty - disc; touching both would subtract the same
// discount twice. Skips rows where the cashier has already typed a manual Disc% (their
// entry wins). Idempotent — checks the already-applied value first — so this can't
// retrigger calcRow -> recalcTotals -> evaluateSchemes in an endless loop.
// IMPORTANT: the discounted rate is computed off MRP, not off the current Rate/SP.
// A scheme percentage is defined against MRP (SchemeEngine computes it that way too —
// see _item_mrp_value()), so if SP has already been marked down below MRP for unrelated
// reasons, subtracting the scheme amount from SP again would stack an extra discount on
// top of that markdown. Always: netRate = MRP - schemeDiscountPerUnit.
function _applySchemeDiscount(rid, discAmt) {
    const rateEl   = document.getElementById('rate_' + rid);
    const mrpEl    = document.getElementById('mrp_' + rid);
    const pctInput = document.getElementById('disc_pct_' + rid);
    const qty      = parseFloat(document.getElementById('qty_' + rid)?.value) || 0;
    if (!rateEl || qty <= 0) return;
    if (pctInput && pctInput.dataset.manualDisc === '1') return;

    const mrp     = parseFloat(mrpEl?.value) || parseFloat(rateEl.dataset.baseRate) || parseFloat(rateEl.value) || 0;
    const netRate = Math.max(0, parseFloat((mrp - discAmt / qty).toFixed(2)));
    if (rateEl.dataset.schemeAuto === '1' && Math.abs(parseFloat(rateEl.value) - netRate) < 0.01) return;

    rateEl.value = netRate.toFixed(2);
    rateEl.dataset.schemeAuto = '1';
    calcRow(rid);
}

function _clearSchemeDiscount(rid) {
    const rateEl = document.getElementById('rate_' + rid);
    if (!rateEl || rateEl.dataset.schemeAuto !== '1') return;

    const baseRate = parseFloat(rateEl.dataset.baseRate) || 0;
    rateEl.value = baseRate.toFixed(2);
    delete rateEl.dataset.schemeAuto;
    calcRow(rid);
}

<?php if (!empty($convert_order)): ?>
// Pre-fill from converted order request
(function() {
    // Set customer
    var custSel = document.getElementById('customer_id');
    if (custSel) {
        custSel.value = '<?= (int)$convert_order['customer_id'] ?>';
        if (typeof loadCustomer === 'function') loadCustomer(custSel.value);
    }
    var convertOrderIdEl = document.getElementById('convert_order_id');
    if (convertOrderIdEl) convertOrderIdEl.value = '<?= (int)($convert_order['order_id'] ?? 0) ?>';

    // Portal orders are always delivery orders — tick by default
    var deliveryChk = document.getElementById('is_delivery');
    if (deliveryChk) { deliveryChk.checked = true; deliveryChk.dispatchEvent(new Event('change')); }

    // Add items from order
    var orderItems = <?= json_encode($convert_order['items']) ?>;
    // Clear the default empty row first
    document.querySelectorAll('#itemsBody tr').forEach(function(r) { r.remove(); });
    rowCount = 0;

    orderItems.forEach(function(item) {
        addRow();
        var rid = rowCount;
        var sel = document.querySelector('#row_' + rid + ' select[name="product_id[]"]');
        if (sel) {
            sel.value = item.product_id;
            fillProduct(sel, rid);
            setTimeout(function() {
                var qtyEl = document.getElementById('qty_' + rid);
                if (qtyEl) { qtyEl.value = item.qty; recalcTotals(); }
                // Use the price the customer was charged (mrp at order time), not current selling_price
                var rateEl = document.getElementById('rate_' + rid);
                if (rateEl && item.mrp) { rateEl.value = parseFloat(item.mrp).toFixed(2); recalcTotals(); }
            }, 150);
        }
    });
    updateRowCount();

    // Show notice
    var notice = document.createElement('div');
    notice.className = 'alert alert-info alert-dismissible mb-3';
    notice.innerHTML = '<strong>Converting Order <?= htmlspecialchars($convert_order['request_no']) ?></strong> — Review items and submit to create invoice. <button class="close" data-dismiss="alert">&times;</button>';
    document.querySelector('form').insertBefore(notice, document.querySelector('form').firstChild);
})();
<?php endif; ?>

/* ══════════════════════════════════════════════
   B2C QUICK CUSTOMER PHONE LOOKUP & REGISTER
   ══════════════════════════════════════════════ */
function searchCustomerByPhone() {
    const input = document.getElementById('cust_phone_search');
    const phone = input.value.trim();
    const statusEl = document.getElementById('phone_search_status');
    if (!phone) return;

    // Normalize phone number (digits only, last 10 digits)
    const digits10 = phone.replace(/\D/g, '').slice(-10);
    if (digits10.length < 10) {
        statusEl.textContent = '✗ Enter a valid 10-digit mobile number.';
        statusEl.className = 'form-text text-danger font-weight-bold';
        return;
    }

    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
    statusEl.className = 'form-text text-muted';

    // 1. Try local cache check first
    const c = CUSTOMERS.find(x => {
        if (!x.phone) return false;
        const d10 = String(x.phone).replace(/\D/g, '').slice(-10);
        return d10 === digits10;
    });

    if (c) {
        selectAndLoadCustomer(c.id, c.name);
    } else {
        // 2. Query the server just in case it was created in another window/session
        fetch('<?= base_url("billing/get_customer_by_phone_ajax") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'phone=' + encodeURIComponent(digits10)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.customer) {
                const s = data.customer;
                // Add to local array
                CUSTOMERS.push({
                    id: parseInt(s.id),
                    name: s.name,
                    phone: s.phone ?? '',
                    address: s.address ?? '',
                    type: s.type,
                    balance: parseFloat(s.balance || 0),
                    credit_limit: parseFloat(s.credit_limit || 0),
                    gst_number: s.gst_number,
                    state: s.state,
                    wallet_balance: parseFloat(s.wallet_balance || 0)
                });
                // Append to option
                const sel = document.getElementById('customer_id');
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name + ' (' + (s.phone ?? '') + ')';
                sel.appendChild(opt);

                selectAndLoadCustomer(s.id, s.name);
            } else {
                // 3. Open quick customer registration modal
                document.getElementById('quick_phone').value = digits10;
                document.getElementById('quick_name').value = '';
                document.getElementById('quick_address').value = '';
                document.getElementById('quickCustomerError').classList.add('d-none');
                statusEl.textContent = '✗ Mobile not registered. Opening form...';
                statusEl.className = 'form-text text-warning font-weight-bold';
                $('#quickCustomerModal').modal('show');
            }
        })
        .catch(() => {
            // Fallback to direct modal opening on network error
            document.getElementById('quick_phone').value = digits10;
            document.getElementById('quick_name').value = '';
            document.getElementById('quick_address').value = '';
            document.getElementById('quickCustomerError').classList.add('d-none');
            $('#quickCustomerModal').modal('show');
        });
    }
}

function selectAndLoadCustomer(id, name) {
    const $sel = $('#customer_id');
    if ($sel.length) {
        $sel.val(id).trigger('change');
    } else {
        const sel = document.getElementById('customer_id');
        if (sel) {
            sel.value = String(id);
            sel.dispatchEvent(new Event('change'));
        }
    }

    const statusEl = document.getElementById('phone_search_status');
    statusEl.textContent = '✓ Active: ' + name;
    statusEl.className = 'form-text text-success font-weight-bold';

    // Clear search and close dropdown
    document.getElementById('cust_phone_search').value = '';
    const custDropdown = document.getElementById('custSearchDropdown');
    if (custDropdown) custDropdown.style.display = 'none';
    selectedCustIdx = -1;

    // Auto-focus the unified product search field to keep Cashier in flow
    setTimeout(() => {
        const smartSearch = document.getElementById('smartSearch');
        if (smartSearch) smartSearch.focus();
    }, 120);
}

function saveQuickCustomer(e) {
    e.preventDefault();
    const name = document.getElementById('quick_name').value.trim();
    const phone = document.getElementById('quick_phone').value.trim();
    const address = document.getElementById('quick_address').value.trim();
    const errEl = document.getElementById('quickCustomerError');

    if (!name || !phone) {
        errEl.textContent = 'Name and Phone are required.';
        errEl.classList.remove('d-none');
        return;
    }

    fetch('<?= base_url("billing/create_customer_ajax") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'name=' + encodeURIComponent(name) + '&phone=' + encodeURIComponent(phone) + '&address=' + encodeURIComponent(address)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.customer) {
            const newCust = data.customer;
            // Add to local array
            CUSTOMERS.push({
                id: parseInt(newCust.id),
                name: newCust.name,
                phone: newCust.phone ?? '',
                address: newCust.address ?? '',
                type: newCust.type,
                balance: parseFloat(newCust.balance || 0),
                credit_limit: parseFloat(newCust.credit_limit || 0),
                gst_number: newCust.gst_number,
                state: newCust.state,
                wallet_balance: parseFloat(newCust.wallet_balance || 0)
            });

            // Add Option dynamically
            const sel = document.getElementById('customer_id');
            const opt = document.createElement('option');
            opt.value = newCust.id;
            opt.textContent = newCust.name + ' (' + (newCust.phone ?? '') + ')';
            sel.appendChild(opt);

            // Select & load
            selectAndLoadCustomer(newCust.id, newCust.name);

            // Hide modal
            $('#quickCustomerModal').modal('hide');
        } else {
            errEl.textContent = data.message || 'Failed to create customer.';
            errEl.classList.remove('d-none');
        }
    })
    .catch(() => {
        errEl.textContent = 'Server/connection error. Please try again.';
        errEl.classList.remove('d-none');
    });
}

/* ══════════════════════════════════════════════
   B2C CUSTOMER AUTOCOMPLETE SEARCH & SELECTION
   ══════════════════════════════════════════════ */
const custPhoneInput = document.getElementById('cust_phone_search');
const custDropdown   = document.getElementById('custSearchDropdown');
let selectedCustIdx  = -1;

custPhoneInput.addEventListener('input', function() {
    const q = this.value.trim();
    if (q.length < 3) { custDropdown.style.display = 'none'; return; }

    const results = CUSTOMERS.filter(c => {
        const nameStr = c.name ? String(c.name).toLowerCase() : '';
        const phoneClean = c.phone ? String(c.phone).replace(/\D/g, '') : '';
        const qClean = q.replace(/\D/g, '');
        return (qClean.length > 0 && phoneClean.includes(qClean)) ||
               nameStr.includes(q.toLowerCase());
    }).slice(0, 8);

    if (results.length === 0) { custDropdown.style.display = 'none'; return; }

    selectedCustIdx = -1;
    custDropdown.innerHTML = results.map((c) => {
        let suffix = '';
        if (c.wallet_balance > 0) suffix += ` <span class="badge badge-success">Wallet: ₹${c.wallet_balance.toFixed(0)}</span>`;
        if (c.balance > 0) suffix += ` <span class="badge badge-danger">Due: ₹${c.balance.toFixed(0)}</span>`;
        return `<a href="#" class="list-group-item list-group-item-action py-1 px-2 cust-search-item"
            data-id="${c.id}" style="font-size:.82rem;"
            onclick="selectCustomerFromSearch(${c.id}); return false;">
            <strong>${c.name}</strong> <span class="text-muted small">(${c.phone ?? ''})</span> ${suffix}
         </a>`;
    }).join('');
    custDropdown.style.display = 'block';
});

custPhoneInput.addEventListener('keydown', function(e) {
    const links = document.querySelectorAll('.cust-search-item');

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (links.length > 0) {
            selectedCustIdx = (selectedCustIdx + 1) % links.length;
            highlightCustItem(links);
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (links.length > 0) {
            selectedCustIdx = (selectedCustIdx - 1 + links.length) % links.length;
            highlightCustItem(links);
        }
    } else if (e.key === 'Escape') {
        custDropdown.style.display = 'none';
        selectedCustIdx = -1;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const activeLink = document.querySelector('.active-cust-item');
        if (activeLink && custDropdown.style.display !== 'none') {
            const cid = activeLink.getAttribute('data-id');
            selectCustomerFromSearch(cid);
        } else {
            searchCustomerByPhone();
        }
    }
});

function highlightCustItem(links) {
    links.forEach((link, idx) => {
        if (idx === selectedCustIdx) {
            link.style.setProperty('background-color', '#1e40af', 'important');
            link.style.setProperty('color', '#ffffff', 'important');
            link.style.setProperty('border-left', '4px solid #93c5fd', 'important');
            link.style.setProperty('font-weight', '700', 'important');
            link.classList.add('active-cust-item');
            link.scrollIntoView({ block: 'nearest' });
        } else {
            link.style.removeProperty('background-color');
            link.style.removeProperty('color');
            link.style.removeProperty('border-left');
            link.style.removeProperty('font-weight');
            link.classList.remove('active-cust-item');
        }
    });
}

function selectCustomerFromSearch(id) {
    const c = CUSTOMERS.find(x => x.id == id);
    if (!c) return;
    custDropdown.style.display = 'none';
    selectedCustIdx = -1;
    selectAndLoadCustomer(c.id, c.name);
}

document.addEventListener('click', function(e) {
    if (!custDropdown.contains(e.target) && e.target !== custPhoneInput) {
        custDropdown.style.display = 'none';
    }
});

// Auto focus Customer Name inside modal when opened
// Bootstrap modal events are attached after jQuery loads via window.load fallback
window.addEventListener('load', function() {
    var qcModal = document.getElementById('quickCustomerModal');
    if (qcModal) qcModal.addEventListener('shown.bs.modal', function () {
        var qName = document.getElementById('quick_name');
        if (qName) qName.focus();
    });
    var qeModal = document.getElementById('quickEditModal');
    if (qeModal) qeModal.addEventListener('hidden.bs.modal', function () {
        var smartSearch = document.getElementById('smartSearch');
        if (smartSearch) smartSearch.focus();
    });
    // Bootstrap fires these as namespaced jQuery events (via $el.trigger()), which a
    // plain addEventListener does NOT receive — must bind with jQuery's .on() (this
    // mirrors how the original payment modal's focus handling was wired).
    var pcModal = $('#priceCheckModal');
    if (pcModal.length) {
        pcModal.on('shown.bs.modal', function () {
            var pcInput = document.getElementById('pcSearchInput');
            if (pcInput) pcInput.focus();
        });
        pcModal.on('hidden.bs.modal', function () {
            var smartSearch = document.getElementById('smartSearch');
            if (smartSearch) smartSearch.focus();
        });
    }
});

/* ══════════════════════════════════════════════
   RAPID BILLING KEYBOARD SHORTCUTS (F1, F2, F4, F5, F6, F7)
   — payment panel is always visible in v2, so F4 submits directly
   (F11 fullscreen toggle is handled by its own listener above)
   ══════════════════════════════════════════════ */
document.addEventListener('keydown', function(e) {
    if (e.key === 'F1') {
        e.preventDefault();
        openQuickEdit('qty');
    } else if (e.key === 'F2') {
        e.preventDefault();
        openQuickEdit('rate');
    } else if (e.key === 'F3') {
        e.preventDefault();
        openPriceCheck();
    } else if (e.key === 'F4') {
        e.preventDefault();
        if (validateInvoiceBeforePayment()) submitInvoiceForm();
    } else if (e.key === 'F5') {
        e.preventDefault();
        holdCurrentBill();
    } else if (e.key === 'F6') {
        e.preventDefault();
        openRecallBillModal();
    } else if (e.key === 'F7') {
        e.preventDefault();
        openReprintModal();
    }
});

/* Safety net: Enter inside any invoice-form text/number field must never
   implicitly submit the bill. Fields that want their own Enter behaviour
   (qty, rate, discount %, scan box) handle it in their own onkeydown and
   still run first — this only kills the default form submission. */
document.getElementById('invoiceForm').addEventListener('keydown', function(e) {
    if (e.key !== 'Enter') return;
    const t = e.target;
    if (!t || t.tagName !== 'INPUT') return;
    if (t.type === 'submit' || t.type === 'button') return;
    e.preventDefault();
});

function openQuickEdit(focusField) {
    if (RATE_LOCKED && focusField === 'rate') {
        alert('Access denied: You do not have permission to change rates.');
        return;
    }
    const rows = document.querySelectorAll('#itemsBody tr[id^=row_]');
    if (!rows.length) return;
    
    let lastRow = null;
    for (let i = rows.length - 1; i >= 0; i--) {
        const sel = rows[i].querySelector('select[name="product_id[]"]');
        if (sel && sel.value) {
            lastRow = rows[i];
            break;
        }
    }
    
    if (!lastRow) return;
    
    const rid = lastRow.id.replace('row_', '');
    const sel = lastRow.querySelector('select[name="product_id[]"]');
    const opt = sel.options[sel.selectedIndex];
    const productName = opt.textContent.split('(')[0].trim();
    
    const qtyVal = parseFloat(document.getElementById('qty_' + rid).value) || 1;
    const rateVal = parseFloat(document.getElementById('rate_' + rid).value) || 0;
    
    document.getElementById('qeRowId').value = rid;
    document.getElementById('qeProductName').textContent = productName;
    document.getElementById('qeQty').value = qtyVal;
    document.getElementById('qeRate').value = rateVal;
    
    $('#quickEditModal').modal('show');
    
    setTimeout(() => {
        if (focusField === 'qty') {
            const qInput = document.getElementById('qeQty');
            if (qInput) { qInput.focus(); qInput.select(); }
        } else {
            const rInput = document.getElementById('qeRate');
            if (rInput) { rInput.focus(); rInput.select(); }
        }
    }, 150);
}

function applyQuickEdit(e) {
    if (e) e.preventDefault();
    const rid = document.getElementById('qeRowId').value;
    const newQty = parseFloat(document.getElementById('qeQty').value) || 1;
    const newRate = parseFloat(document.getElementById('qeRate').value) || 0;
    
    const qtyInput = document.getElementById('qty_' + rid);
    const rateInput = document.getElementById('rate_' + rid);
    
    if (qtyInput) qtyInput.value = newQty;
    if (rateInput) rateInput.value = newRate.toFixed(2);
    
    calcRow(rid);

    $('#quickEditModal').modal('hide');
    setTimeout(function() {
        var smartSearch = document.getElementById('smartSearch');
        if (smartSearch) { smartSearch.focus(); smartSearch.select(); }
    }, 300);
}

function getPreCdTotal() {
    let taxable = 0, totalGst = 0;
    document.querySelectorAll('#itemsBody tr').forEach(function(row) {
        const rid  = row.id.replace('row_','');
        const qty  = parseFloat(document.getElementById('qty_' + rid)?.value) || 0;
        const rate = parseFloat(document.getElementById('rate_'+ rid)?.value) || 0;
        const disc = parseFloat(document.getElementById('disc_'+ rid)?.value) || 0;
        const gst  = parseFloat(document.getElementById('gst_' + rid)?.value) || 0;
        const tax  = (rate * qty) - disc;
        
        const sel  = row.querySelector('select[name="product_id[]"]');
        const opt  = sel ? sel.options[sel.selectedIndex] : null;
        const noDiscount = opt && opt.dataset.noDiscount ? parseInt(opt.dataset.noDiscount) : 0;
        
        if (noDiscount !== 1) {
            taxable   += tax;
            totalGst  += (tax * gst) / 100;
        }
    });
    totalGst = parseFloat(totalGst.toFixed(2));
    return parseFloat((taxable + totalGst).toFixed(2));
}

let _cdMode = 'pct'; // 'pct' or 'amt' — tracks which field user last edited

function onCdPercentChange() {
    _cdMode = 'pct';
    const cdPct = parseFloat(document.getElementById('cd_percent').value) || 0;
    const preCdTotal = getPreCdTotal();
    const cdAmt = parseFloat(((preCdTotal * cdPct) / 100).toFixed(2));
    const cdAmtInput = document.getElementById('cd_amount');
    if (cdAmtInput) cdAmtInput.value = cdAmt > 0 ? cdAmt.toFixed(2) : 0;
    recalcTotals();
}

function onCdAmountChange() {
    _cdMode = 'amt';
    const cdAmt = parseFloat(document.getElementById('cd_amount').value) || 0;
    const preCdTotal = getPreCdTotal();
    const cdPct = preCdTotal > 0 ? parseFloat(((cdAmt / preCdTotal) * 100).toFixed(4)) : 0;
    const cdPctInput = document.getElementById('cd_percent');
    if (cdPctInput) cdPctInput.value = cdPct > 0 ? cdPct.toFixed(2) : 0;
    recalcTotals();
}

function validateInvoiceBeforePayment() {
    // 1. Customer is optional — Walk-in Customer auto-assigned if blank

    // 2. Check if at least one product has been selected/entered with qty > 0
    const rows = document.querySelectorAll('#itemsBody tr[id^=row_]');
    let hasItems = false;
    for (let i = 0; i < rows.length; i++) {
        const sel = rows[i].querySelector('select[name="product_id[]"]');
        const qtyInput = rows[i].querySelector('input[name="qty[]"]');
        if (sel && sel.value && qtyInput && parseFloat(qtyInput.value) > 0) {
            hasItems = true;
            break;
        }
    }

    if (!hasItems) {
        alert('Please add at least one product with a valid quantity.');
        return false;
    }

    // 3. Negative stock check (client-side warning)
    if (typeof ALLOW_NEGATIVE_STOCK !== 'undefined' && !ALLOW_NEGATIVE_STOCK) {
        let stockErrors = [];
        for (let i = 0; i < rows.length; i++) {
            const sel = rows[i].querySelector('select[name="product_id[]"]');
            const qtyInput = rows[i].querySelector('input[name="qty[]"]');
            if (!sel || !sel.value || !qtyInput) continue;
            const qty = parseFloat(qtyInput.value) || 0;
            if (qty <= 0) continue;
            const opt = sel.selectedOptions[0];
            const stock = parseFloat(opt?.dataset?.stock) || 0;
            const mult = parseFloat(rows[i].querySelector('input[name="multiplier[]"]')?.value) || 1;
            if (stock < qty * mult) {
                stockErrors.push((opt?.textContent?.split('(')[0]?.trim() || 'Item') + ': need ' + (qty * mult) + ', available ' + stock);
            }
        }
        if (stockErrors.length > 0) {
            alert('Insufficient stock (negative billing disabled):\n' + stockErrors.join('\n'));
            return false;
        }
    }

    return true;
}

// ─── Payment modes from DB ───
window._splitModes = <?= json_encode(array_values(array_map(function($m) { return $m->code; }, array_filter($payment_modes, function($m) { return $m->show_in_split; })))) ?>;
window._splitLabels = <?= json_encode((object)array_column(array_filter($payment_modes, function($m) { return $m->show_in_split; }), 'name', 'code')) ?>;
window._refModes = <?= json_encode(array_values(array_map(function($m) { return $m->code; }, array_filter($payment_modes, function($m) { return $m->needs_ref; })))) ?>;
window._creditModes = <?= json_encode(array_values(array_map(function($m) { return $m->code; }, array_filter($payment_modes, function($m) { return $m->is_credit; })))) ?>;

// ─── Split payment row counter ───
let splitRowCount = 0;

function switchPayMode(mode) {
    const isSplit = mode === 'split';
    document.getElementById('singlePaySection').style.display = isSplit ? 'none' : '';
    document.getElementById('splitPaySection').style.display  = isSplit ? '' : 'none';
    // v2: Amount Paid was pulled out of #singlePaySection to sit next to "Payable Now" —
    // hide it in split mode too, since split payment uses the per-row amounts instead.
    const singlePayCol = document.getElementById('singlePayAmountCol');
    if (singlePayCol) singlePayCol.style.display = isSplit ? 'none' : '';
    // v2: the old Single/Split toggle buttons were replaced by the pay-mode quick buttons —
    // guard with ?. so this still works if either markup is present.
    document.getElementById('btnSinglePay')?.classList.toggle('active', !isSplit);
    document.getElementById('btnSplitPay')?.classList.toggle('active', isSplit);
    if (isSplit && document.getElementById('splitPayBody').children.length === 0) {
        addSplitRow(); addSplitRow();
    }
    calcSplitTotals();
    if (typeof syncPayModeButtons === 'function') syncPayModeButtons();
    const otherSel = document.getElementById('pmOtherMode');
    if (otherSel && isSplit) otherSel.selectedIndex = 0;
}

function addSplitRow() {
    const idx = splitRowCount++;
    const modes = window._splitModes || ['cash','upi','neft','cheque','card','credit'];
    const labels = window._splitLabels || {'cash':'Cash','upi':'UPI','neft':'NEFT/Bank','cheque':'Cheque','card':'Card','credit':'Credit'};
    const opts = modes.map(m => `<option value="${m}">${labels[m]}</option>`).join('');
    const html = `<tr id="spr_${idx}">
        <td style="width:35%;"><select class="form-control form-control-sm split-mode" onchange="onSplitModeChange(${idx})">${opts}</select></td>
        <td style="width:35%;"><input type="number" step="0.01" min="0" class="form-control form-control-sm split-amt" placeholder="₹" oninput="calcSplitTotals()"></td>
        <td style="width:25%;" id="spr_card_${idx}"></td>
        <td><button type="button" class="btn btn-xs btn-outline-danger py-0 px-1" onclick="removeSplitRow(${idx})">✕</button></td>
    </tr>`;
    document.getElementById('splitPayBody').insertAdjacentHTML('beforeend', html);
}

function removeSplitRow(idx) {
    const r = document.getElementById('spr_' + idx);
    if (r) { r.remove(); calcSplitTotals(); }
}

function onSplitModeChange(idx) {
    const sel = document.querySelector('#spr_' + idx + ' select.split-mode');
    const mode = sel ? sel.value : '';
    const cardCell = document.getElementById('spr_card_' + idx);
    if ((window._refModes || ['card']).includes(mode)) {
        cardCell.innerHTML = `<input type="text" class="form-control form-control-sm mb-1" id="sc_last4_${idx}" maxlength="4" placeholder="Last 4">
            <input type="text" class="form-control form-control-sm" id="sc_ref_${idx}" placeholder="Ref#">`;
    } else {
        cardCell.innerHTML = '';
    }
    const creditCodes = window._creditModes || ['credit'];
    const hasCreditSplit = Array.from(document.querySelectorAll('.split-mode')).some(s => creditCodes.includes(s.value));
    document.getElementById('splitCreditDue').style.display = hasCreditSplit ? '' : 'none';
    calcSplitTotals();
}

function calcSplitTotals() {
    let total = 0;
    document.querySelectorAll('.split-amt').forEach(inp => { total += parseFloat(inp.value) || 0; });
    document.getElementById('splitTotal').textContent = '₹ ' + total.toFixed(2);
    const netPay = parseFloat(document.getElementById('pmNetPayable').textContent.replace(/[^\d\.]/g, '')) || 0;
    const remain = netPay - total;
    document.getElementById('splitRemain').textContent = '₹ ' + Math.max(0, remain).toFixed(2);
    document.getElementById('splitRemainBox').style.background = remain > 0.01 ? '#ffebee' : '#e8f5e9';
}

function toggleStoreDisc() {
    const enabled = document.getElementById('enableStoreDisc').checked;
    document.getElementById('storeDiscFields').style.display = enabled ? '' : 'none';
    if (!enabled) {
        document.getElementById('pmBillDiscValue').value = '';
        document.getElementById('pmBillDiscAmt').textContent = '- ₹ 0.00';
        document.getElementById('pmBillDiscReason').value = '';
        updateNetPayable();
    }
}

function getNetAfterCd() {
    const el = document.getElementById('pmNetAfterCd');
    if (el) return parseFloat(el.textContent.replace(/[^\d\.]/g,'')) || 0;
    // Fallback when discount fields are hidden
    const tot = document.getElementById('s_total');
    return tot ? (parseFloat(tot.textContent.replace(/[^\d\.]/g,'')) || 0) : 0;
}

function calcBillDiscount() {
    const enabled = document.getElementById('enableStoreDisc') && document.getElementById('enableStoreDisc').checked;
    if (!enabled) return;
    const type = document.getElementById('pmBillDiscType').value;
    const val  = parseFloat(document.getElementById('pmBillDiscValue').value) || 0;
    const afterCd = getNetAfterCd();
    let discAmt = 0;
    if (type === 'percent') {
        discAmt = parseFloat((afterCd * val / 100).toFixed(2));
    } else {
        discAmt = val;
    }
    document.getElementById('pmBillDiscAmt').textContent = '- ₹ ' + discAmt.toFixed(2);
    updateNetPayable();
}

function updateNetPayable() {
    const afterCd = getNetAfterCd();
    const enabled = document.getElementById('enableStoreDisc') && document.getElementById('enableStoreDisc').checked;
    let discAmt = 0;
    if (enabled) {
        const type = document.getElementById('pmBillDiscType').value;
        const val  = parseFloat(document.getElementById('pmBillDiscValue').value) || 0;
        discAmt = type === 'percent' ? parseFloat((afterCd * val / 100).toFixed(2)) : val;
    }
    const netPay = Math.max(0, afterCd - discAmt);
    document.getElementById('pmNetPayable').textContent = '₹ ' + netPay.toFixed(2);

    // Also update bill disc summary in main form sidebar
    const billDiscRow = document.getElementById('row_bill_disc');
    const billDiscCell = document.getElementById('s_bill_disc');
    if (discAmt > 0) {
        if (billDiscRow) billDiscRow.style.display = '';
        if (billDiscCell) billDiscCell.textContent = '- ₹ ' + discAmt.toFixed(2);
    } else {
        if (billDiscRow) billDiscRow.style.display = 'none';
    }
    calcSplitTotals();

    // Update single mode paid amount default
    const isSingle = document.getElementById('singlePaySection').style.display !== 'none';
    if (isSingle) {
        const mode = document.getElementById('pmPaymentMode').value;
        const amountPaidEl = document.getElementById('pmAmountPaid');
        // Cash stays in sync with the live total too, same as UPI/Card — but only until
        // the cashier actually types a tendered amount themselves (see
        // markAmountPaidEdited()), so a deliberate "customer handed me a bigger note"
        // entry for change calculation is never silently overwritten mid-edit.
        if (mode !== 'credit' && !(mode === 'cash' && amountPaidEl.dataset.userEdited)) {
            const walletUsed = parseFloat(document.getElementById('pmWalletUsed').value) || 0;
            amountPaidEl.value = Math.max(0, netPay - walletUsed).toFixed(2);
        }
        calcPaymentChange();
    }
}

// v2: the payment panel is always visible (no modal open/close event), so instead of a
// one-shot "reset everything to fresh defaults" function, totals are kept live via
// refreshPaymentDisplay() (called from recalcTotals(), guarded against re-entrancy since
// updateNetPayable()/calcPaymentChange() call back into recalcTotals()). It deliberately
// does NOT touch pmAmountPaid/pmPaymentMode/store-discount state, since those are the
// cashier's live inputs and must not be clobbered on every cart keystroke.
let _paySyncing = false;
function refreshPaymentDisplay() {
    const totalVal = getInvoiceGrandTotal();
    const walletUsed = parseFloat(document.getElementById('pmWalletUsed')?.value) || 0;
    const netTotal = totalVal - walletUsed;
    const gt = document.getElementById('pmGrandTotal'); if (gt) gt.textContent = '₹ ' + netTotal.toFixed(2);
    const na = document.getElementById('pmNetAfterCd'); if (na) na.textContent = '₹ ' + netTotal.toFixed(2);
    updateNetPayable();
}

function selectPayModeBtn(mode) {
    switchPayMode('single');
    document.getElementById('pmPaymentMode').value = mode;
    onPaymentModeChange();
    syncPayModeButtons();
    // Clear the "other mode" dropdown so it doesn't keep showing a stale NEFT/Cheque/
    // Credit selection while a quick button (Cash/Card/UPI) is actually active.
    const otherSel = document.getElementById('pmOtherMode');
    if (otherSel) otherSel.selectedIndex = 0;
}

// Selecting NEFT/Cheque/Credit from the "Other mode" dropdown drives the real
// pmPaymentMode select exactly like a quick button click would.
function selectOtherPayMode(mode) {
    if (!mode) return;
    switchPayMode('single');
    document.getElementById('pmPaymentMode').value = mode;
    onPaymentModeChange();
    syncPayModeButtons();
}

function syncPayModeButtons() {
    const mode = document.getElementById('pmPaymentMode').value;
    const isSplit = document.getElementById('splitPaySection').style.display !== 'none';
    document.querySelectorAll('.pay-mode-btn[data-mode]').forEach(b => b.classList.toggle('active', !isSplit && b.dataset.mode === mode));
    document.getElementById('btnSplitPayQuick')?.classList.toggle('active', isSplit);
}

function getInvoiceGrandTotal() {
    const txt = document.getElementById('s_total')?.textContent || '0.00';
    return parseFloat(txt.replace(/[^\d\.]/g, '')) || 0;
}

// Cash used to be the one payment mode that never auto-filled "amount paid" — it was
// left for the cashier to type a tendered amount so change could be calculated. In
// practice cashiers very often don't type anything for an exact-cash sale (no change
// due), so the field silently stayed at 0/empty and the invoice posted as fully unpaid.
// Cash now defaults "amount paid" to the net payable exactly like UPI/Card already do,
// and only stops auto-syncing once the cashier has actually typed into the field
// (tracked via this flag, set only by a real 'input' event — JS-driven value
// assignments for the auto-fill itself never fire 'input', so the two can't collide).
function markAmountPaidEdited() {
    const el = document.getElementById('pmAmountPaid');
    if (el) el.dataset.userEdited = '1';
}
function resetAmountPaidEdited() {
    const el = document.getElementById('pmAmountPaid');
    if (el) delete el.dataset.userEdited;
}

function onPaymentModeChange() {
    const mode = document.getElementById('pmPaymentMode').value;

    recalcTotals();
    const billTotal = getInvoiceGrandTotal();
    const walletUsed = parseFloat(document.getElementById('pmWalletUsed').value) || 0;
    const netTotal = billTotal - walletUsed;

    document.querySelector('select[name="payment_mode"]').value = mode;

    // Show/hide card details
    document.getElementById('singleCardDetails').style.display = (mode === 'card') ? '' : 'none';
    // Show/hide credit due date
    document.getElementById('singleCreditDue').style.display = (mode === 'credit') ? '' : 'none';

    if (mode === 'cash') {
        document.getElementById('pmChangeContainer').style.display = 'block';
        if (!document.getElementById('pmAmountPaid').dataset.userEdited) {
            document.getElementById('pmAmountPaid').value = netTotal.toFixed(2);
        }
        calcPaymentChange();
    } else if (mode === 'credit') {
        document.getElementById('pmChangeContainer').style.display = 'none';
        document.getElementById('pmAmountPaid').value = '0.00';
        document.getElementById('paid_amount').value = '0.00';
        calcPaymentChange();
    } else {
        document.getElementById('pmChangeContainer').style.display = 'none';
        document.getElementById('pmAmountPaid').value = netTotal.toFixed(2);
        document.getElementById('paid_amount').value = netTotal.toFixed(2);
        calcPaymentChange();
    }
}

function onWalletUsedChange() {
    const cid = document.getElementById('customer_id').value;
    const c = CUSTOMERS.find(x => x.id == cid);
    const maxWallet = c ? parseFloat(c.wallet_balance || 0) : 0;
    
    recalcTotals();
    const billTotal = getInvoiceGrandTotal();
    
    let walletUsed = parseFloat(document.getElementById('pmWalletUsed').value) || 0;
    if (walletUsed < 0) {
        walletUsed = 0;
        document.getElementById('pmWalletUsed').value = '';
    }
    if (walletUsed > maxWallet) {
        walletUsed = maxWallet;
        document.getElementById('pmWalletUsed').value = walletUsed;
    }
    if (walletUsed > billTotal) {
        walletUsed = billTotal;
        document.getElementById('pmWalletUsed').value = walletUsed;
    }
    
    // Calculate net grand total to show on screen
    const netTotal = billTotal - walletUsed;
    document.getElementById('pmGrandTotal').textContent = '₹ ' + netTotal.toFixed(2);
    
    // Set paid amount defaults for non-credit modes
    const mode = document.getElementById('pmPaymentMode').value;
    if (mode !== 'credit') {
        const currentPaid = parseFloat(document.getElementById('pmAmountPaid').value) || 0;
        if (mode !== 'cash' || currentPaid === billTotal || currentPaid === 0 || document.activeElement !== document.getElementById('pmAmountPaid')) {
            document.getElementById('pmAmountPaid').value = netTotal.toFixed(2);
        }
    }
    
    calcPaymentChange();
}

function formatWalletInput() {
    const el = document.getElementById('pmWalletUsed');
    const val = parseFloat(el.value) || 0;
    el.value = val > 0 ? val.toFixed(2) : '';
}

function formatAmountPaidInput() {
    const el = document.getElementById('pmAmountPaid');
    const val = parseFloat(el.value) || 0;
    el.value = val > 0 ? val.toFixed(2) : '0.00';
}

function onPaymentDiscountPercentChange() {
    const pmDiscPct = parseFloat(document.getElementById('pmDiscountPercent').value) || 0;
    
    const cdPercentInput = document.getElementById('cd_percent');
    if (cdPercentInput) {
        cdPercentInput.value = pmDiscPct;
    }
    const preCdTotal = getPreCdTotal();
    const cdAmt = parseFloat(((preCdTotal * pmDiscPct) / 100).toFixed(2));
    const cdAmountInput = document.getElementById('cd_amount');
    if (cdAmountInput) {
        cdAmountInput.value = cdAmt > 0 ? cdAmt.toFixed(2) : 0;
    }
    
    document.getElementById('pmDiscountAmount').value = cdAmt > 0 ? cdAmt.toFixed(2) : '';

    recalcTotals();

    const billTotal = getInvoiceGrandTotal();
    const walletUsed = parseFloat(document.getElementById('pmWalletUsed').value) || 0;
    const netTotal = billTotal - walletUsed;
    document.getElementById('pmGrandTotal').textContent = '₹ ' + netTotal.toFixed(2);
    document.getElementById('pmNetAfterCd').textContent = '₹ ' + netTotal.toFixed(2);
    updateNetPayable();
    calcPaymentChange();
}

function onPaymentDiscountAmountChange() {
    const pmDiscAmt = parseFloat(document.getElementById('pmDiscountAmount').value) || 0;
    const preCdTotal = getPreCdTotal();
    const pmDiscPct = preCdTotal > 0 ? parseFloat(((pmDiscAmt / preCdTotal) * 100).toFixed(2)) : 0;

    const cdPercentInput = document.getElementById('cd_percent');
    if (cdPercentInput) {
        cdPercentInput.value = pmDiscPct > 0 ? pmDiscPct.toFixed(2) : 0;
    }
    const cdAmountInput = document.getElementById('cd_amount');
    if (cdAmountInput) {
        cdAmountInput.value = pmDiscAmt > 0 ? pmDiscAmt.toFixed(2) : 0;
    }

    document.getElementById('pmDiscountPercent').value = pmDiscPct > 0 ? pmDiscPct.toFixed(2) : '';

    recalcTotals();

    const billTotal = getInvoiceGrandTotal();
    const walletUsed = parseFloat(document.getElementById('pmWalletUsed').value) || 0;
    const netTotal = billTotal - walletUsed;
    document.getElementById('pmGrandTotal').textContent = '₹ ' + netTotal.toFixed(2);
    document.getElementById('pmNetAfterCd').textContent = '₹ ' + netTotal.toFixed(2);
    updateNetPayable();
    calcPaymentChange();
}

function calcPaymentChange() {
    const billTotal = getInvoiceGrandTotal();
    const walletUsed = parseFloat(document.getElementById('pmWalletUsed').value) || 0;
    const netTotal = billTotal - walletUsed;
    
    const paidVal = parseFloat(document.getElementById('pmAmountPaid').value) || 0;
    const mode = document.getElementById('pmPaymentMode').value;
    
    let change = paidVal - netTotal;
    
    const changeDueEl = document.getElementById('pmChangeDue');
    const excessOptionEl = document.getElementById('pmExcessWalletOption');
    
    if (mode === 'cash' && change > 0) {
        excessOptionEl.style.display = 'block';
        
        const isAddToWallet = document.getElementById('pmExcessWallet').checked;
        if (isAddToWallet) {
            if (changeDueEl) changeDueEl.textContent = '₹ 0.00';
            document.getElementById('wallet_amount_credited').value = change.toFixed(2);
        } else {
            if (changeDueEl) changeDueEl.textContent = '₹ ' + change.toFixed(2);
            document.getElementById('wallet_amount_credited').value = '0.00';
        }
    } else {
        excessOptionEl.style.display = 'none';
        document.getElementById('wallet_amount_credited').value = '0.00';
        if (changeDueEl) {
            changeDueEl.textContent = '₹ ' + (change > 0 ? change.toFixed(2) : '0.00');
        }
    }
    
    document.getElementById('paid_amount').value = paidVal.toFixed(2);
    document.getElementById('wallet_amount_used').value = walletUsed.toFixed(2);
    
    recalcTotals();
}

/* ══════════════════════════════════════════════
   OFFLINE-FIRST: Intercept invoice submission
   ══════════════════════════════════════════════ */
let _invoiceSubmitting = false;
async function submitInvoiceForm(e) {
    if (e) e.preventDefault();
    if (_invoiceSubmitting) return;

    if (!validateInvoiceBeforePayment()) return;

    _invoiceSubmitting = true;
    const allBtns = document.querySelectorAll('#pmSubmitBtn, .v2-fkey-btn');
    allBtns.forEach(b => { b.style.pointerEvents = 'none'; b.style.opacity = '.5'; });
    const pmBtn = document.getElementById('pmSubmitBtn');
    if (pmBtn) pmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving…';

    // ── If OFFLINE: save invoice locally and show offline receipt ──
    const isOffline = typeof OfflineSync !== 'undefined' && !OfflineSync.isOnline();
    if (isOffline) {
        await _submitOfflineInvoice();
        return;
    }

    // ── If ONLINE: proceed with normal server submission below ──

    const walletUsed = parseFloat(document.getElementById('pmWalletUsed').value) || 0;
    document.getElementById('wallet_amount_used').value = walletUsed.toFixed(2);

    const isSplit = document.getElementById('splitPaySection').style.display !== 'none';

    if (isSplit) {
        // Validate: total splits must cover net payable
        const netPay = parseFloat(document.getElementById('pmNetPayable').textContent.replace(/[^\d\.]/g,'')) || 0;
        let splitTotal = 0;
        document.querySelectorAll('.split-amt').forEach(inp => { splitTotal += parseFloat(inp.value) || 0; });
        const diff = netPay - splitTotal;
        if (diff > 0.5) {
            alert('Split payments (₹' + splitTotal.toFixed(2) + ') do not cover the net payable amount (₹' + netPay.toFixed(2) + '). Add a Credit split for the remaining ₹' + diff.toFixed(2) + ' or adjust amounts.');
            return;
        }
        // Inject split fields into form
        const form = document.getElementById('invoiceForm');
        // Remove old split inputs
        form.querySelectorAll('.dyn-split').forEach(el => el.remove());
        const rows = document.querySelectorAll('#splitPayBody tr');
        rows.forEach((row, idx) => {
            const mode = row.querySelector('select.split-mode')?.value;
            const amt  = parseFloat(row.querySelector('.split-amt')?.value) || 0;
            if (!mode || amt <= 0) return;
            const addHidden = (name, val) => {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = name; inp.value = val; inp.className = 'dyn-split';
                form.appendChild(inp);
            };
            addHidden('pay_mode[]', mode);
            addHidden('pay_amount[]', amt.toFixed(2));
            if (mode === 'card') {
                addHidden('card_last4_' + idx, document.getElementById('sc_last4_' + idx)?.value || '');
                addHidden('card_ref_'   + idx, document.getElementById('sc_ref_'   + idx)?.value || '');
            }
        });
        // credit due date (split)
        const creditDueSplit = document.getElementById('pmCreditDueDateSplit')?.value;
        if (creditDueSplit) document.getElementById('hCreditDueDate').value = creditDueSplit;
    } else {
        const mode = document.getElementById('pmPaymentMode').value;
        const isAddToWallet = document.getElementById('pmExcessWallet').checked;

        // The "amount tendered" field is only there for change calculation on a cash
        // sale — if the cashier never typed anything into it (exact cash handed over,
        // the common case), it's still 0/empty at this point. Without this, the bill
        // silently posts with paid_amount=0 and the whole amount becomes "due" even
        // though cash was actually collected. Default it to the full net payable.
        if (mode === 'cash' && (parseFloat(document.getElementById('pmAmountPaid').value) || 0) <= 0) {
            const netPay = parseFloat(document.getElementById('pmNetPayable').textContent.replace(/[^\d\.]/g, '')) || 0;
            document.getElementById('paid_amount').value = netPay.toFixed(2);
        }

        if (mode === 'cash' && isAddToWallet) {
            const totalText = document.getElementById('pmGrandTotal').textContent;
            const totalVal  = parseFloat(totalText.replace(/[^\d\.]/g, '')) || 0;
            const paidVal   = parseFloat(document.getElementById('pmAmountPaid').value) || 0;
            const excess    = paidVal - totalVal;
            if (excess > 0) {
                document.getElementById('wallet_amount_credited').value = excess.toFixed(2);
                document.getElementById('paid_amount').value = totalVal.toFixed(2);
            } else {
                document.getElementById('wallet_amount_credited').value = '0.00';
            }
        } else {
            document.getElementById('wallet_amount_credited').value = '0.00';
        }

        // Card details (single mode)
        if (mode === 'card') {
            const form = document.getElementById('invoiceForm');
            form.querySelectorAll('.dyn-split').forEach(el => el.remove());
            const addH = (name, val) => {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = name; inp.value = val; inp.className = 'dyn-split';
                form.appendChild(inp);
            };
            addH('pay_mode[]',   'card');
            addH('pay_amount[]', (parseFloat(document.getElementById('pmAmountPaid').value)||0).toFixed(2));
            addH('card_type_0',  document.getElementById('singleCardType')?.value || '');
            addH('card_last4_0', document.getElementById('singleCardLast4')?.value || '');
            addH('bank_name_0',  document.getElementById('singleBankName')?.value || '');
            addH('card_ref_0',   document.getElementById('singleCardRef')?.value || '');
        }

        // Credit due date (single mode)
        const creditDueSingle = document.getElementById('pmCreditDueDate')?.value;
        if (creditDueSingle) document.getElementById('hCreditDueDate').value = creditDueSingle;
    }

    // Inject store discount into hidden fields
    const storeDiscEnabled = document.getElementById('enableStoreDisc')?.checked;
    if (storeDiscEnabled) {
        const type = document.getElementById('pmBillDiscType').value;
        const val  = parseFloat(document.getElementById('pmBillDiscValue').value) || 0;
        const afterCd = parseFloat(document.getElementById('pmNetAfterCd').textContent.replace(/[^\d\.]/g,'')) || 0;
        const amt  = type === 'percent' ? parseFloat((afterCd * val / 100).toFixed(2)) : val;
        const pct  = type === 'percent' ? val : (afterCd > 0 ? (val / afterCd * 100) : 0);
        const reason = document.getElementById('pmBillDiscReason').value.trim();
        if (amt > 0 && reason) {
            document.getElementById('hBillDiscType').value    = type;
            document.getElementById('hBillDiscPercent').value = pct.toFixed(2);
            document.getElementById('hBillDiscAmount').value  = amt.toFixed(2);
            document.getElementById('hBillDiscReason').value  = reason;
        }
    }

    const form = document.getElementById('invoiceForm');
    if (form) form.submit();
}

/* ══════════════════════════════════════════════
   OFFLINE INVOICE BUILDER
   ══════════════════════════════════════════════ */
async function _submitOfflineInvoice() {
    // Collect form state
    const customerId = parseInt(document.getElementById('customer_id').value) || 0;
    const customer   = CUSTOMERS.find(c => c.id === customerId) || { name: 'Walk-in', phone: '' };
    const invoiceDate = document.querySelector('input[name="invoice_date"]').value || new Date().toISOString().slice(0,10);
    const invoiceType = document.getElementById('invoice_type').value || 'retail';
    const notes       = document.querySelector('textarea[name="notes"]').value || '';

    // Payment info from modal
    const isSplit = document.getElementById('splitPaySection').style.display !== 'none';
    let paymentMode = document.getElementById('pmPaymentMode').value;
    let paidAmount  = parseFloat(document.getElementById('pmAmountPaid').value) || 0;
    const walletUsed = parseFloat(document.getElementById('pmWalletUsed').value) || 0;
    const walletCredited = parseFloat(document.getElementById('wallet_amount_credited').value) || 0;

    // Collect items
    const items = [];
    document.querySelectorAll('#itemsBody tr[id^=row_]').forEach(function(row) {
        const rid     = row.id.replace('row_','');
        const sel     = row.querySelector('select[name="product_id[]"]');
        const pid     = parseInt(sel?.value) || 0;
        if (!pid) return;
        const opt     = sel.options[sel.selectedIndex];
        const qty     = parseFloat(document.getElementById('qty_'  + rid)?.value) || 0;
        const rate    = parseFloat(document.getElementById('rate_' + rid)?.value) || 0;
        const disc    = parseFloat(document.getElementById('disc_' + rid)?.value) || 0;
        const gstPct  = parseFloat(document.getElementById('gst_'  + rid)?.value) || 0;
        const mrp     = parseFloat(document.getElementById('mrp_'  + rid)?.value) || 0;
        const pluId   = document.getElementById('plu_id_' + rid)?.value || null;
        const prod    = PRODUCTS.find(p => p.id === pid) || {};

        const taxable = (rate * qty) - disc;
        const gstAmt  = parseFloat((taxable * gstPct / 100).toFixed(2));

        if (qty > 0) {
            const customName = document.getElementById('custom_name_' + rid)?.value || '';
            items.push({
                product_id:    pid,
                product_name:  customName || (opt ? opt.textContent.split('(')[0].trim() : (prod.name || '')),
                qty:           qty,
                rate:          rate,
                mrp:           mrp,
                unit:          prod.unit || 'pcs',
                item_discount: disc,
                gst_percent:   gstPct,
                gst_amount:    gstAmt,
                taxable_amt:   taxable,
                total:         taxable + gstAmt,
                plu_id:        pluId || null,
                hsn_code:      prod.hsn_code || '',
            });
        }
    });

    if (!items.length) { alert('No items to save.'); return; }

    // CD discount
    const cdAmount  = parseFloat(document.getElementById('cd_amount')?.value)  || 0;
    const cdPercent = parseFloat(document.getElementById('cd_percent')?.value) || 0;

    // Bill discount
    const billDiscAmount = parseFloat(document.getElementById('hBillDiscAmount')?.value) || 0;
    const billDiscType   = document.getElementById('hBillDiscType')?.value || 'none';
    const billDiscReason = document.getElementById('hBillDiscReason')?.value || '';

    // Totals
    let subtotal = 0, totalDisc = 0, taxable = 0, totalGst = 0;
    items.forEach(it => {
        subtotal  += it.rate * it.qty;
        totalDisc += it.item_discount;
        taxable   += it.taxable_amt;
        totalGst  += it.gst_amount;
    });
    totalGst = parseFloat(totalGst.toFixed(2));
    const preCdTotal  = parseFloat((taxable + totalGst).toFixed(2));
    const grandTotal  = parseFloat((preCdTotal - cdAmount - billDiscAmount).toFixed(2));

    // Split payments
    let splitPayments = null;
    if (isSplit) {
        splitPayments = [];
        paidAmount = 0;
        document.querySelectorAll('#splitPayBody tr').forEach((row, idx) => {
            const mode = row.querySelector('select.split-mode')?.value;
            const amt  = parseFloat(row.querySelector('.split-amt')?.value) || 0;
            if (mode && amt > 0) {
                splitPayments.push({
                    mode: mode, amount: amt,
                    card_last4: document.getElementById('sc_last4_' + idx)?.value || '',
                    reference_no: document.getElementById('sc_ref_' + idx)?.value || '',
                });
                paidAmount += amt;
            }
        });
        paymentMode = 'split';
    }

    const dueAmount = Math.max(0, grandTotal - paidAmount - walletUsed);

    // Generate offline invoice number
    const offlineRef = await OfflineSync.generateOfflineRef();

    const invoicePayload = {
        offline_ref:           offlineRef,
        device_id:             _getDeviceId(),
        invoice_date:          invoiceDate,
        invoice_type:          invoiceType,
        customer_id:           customerId,
        customer_name:         customer.name,
        customer_phone:        customer.phone || '',
        is_interstate:         isInterstate,
        payment_mode:          paymentMode,
        paid_amount:           parseFloat(paidAmount.toFixed(2)),
        due_amount:            parseFloat(dueAmount.toFixed(2)),
        wallet_amount_used:    walletUsed,
        wallet_amount_credited: walletCredited,
        cd_percent:            cdPercent,
        cd_amount:             cdAmount,
        bill_discount_type:    billDiscType,
        bill_discount_amount:  billDiscAmount,
        bill_discount_reason:  billDiscReason,
        subtotal:              parseFloat(subtotal.toFixed(2)),
        total_discount:        parseFloat(totalDisc.toFixed(2)),
        taxable_amount:        parseFloat(taxable.toFixed(2)),
        total_gst:             totalGst,
        grand_total:           grandTotal,
        notes:                 notes,
        items:                 items,
        split_payments:        splitPayments,
        location_id:           <?= (int)($this->session->userdata('location_id') ?: 1) ?>,
        user_id:               <?= (int)$this->session->userdata('user_id') ?>,
        created_at_local:      new Date().toISOString().replace('T',' ').slice(0,19),
    };

    // Save to local IDB queue
    await OfflineSync.queueInvoice(invoicePayload);

    // Show offline receipt
    _showOfflineReceipt(invoicePayload);
}

function _getDeviceId() {
    let did = localStorage.getItem('pos_device_id');
    if (!did) {
        did = 'DEV-' + Math.random().toString(36).slice(2,10).toUpperCase()
            + '-' + Date.now().toString(36).toUpperCase();
        localStorage.setItem('pos_device_id', did);
    }
    return did;
}

function _showOfflineReceipt(inv) {
    const company = <?= json_encode($company['company_name'] ?? 'Sandal House') ?>;
    const address = <?= json_encode(str_replace(["\r\n","\r","\n"], ' ', $company['company_address'] ?? '')) ?>;
    const phone   = <?= json_encode($company['company_phone'] ?? '') ?>;
    const gstNo   = <?= json_encode($company['gst_number'] ?? '') ?>;

    let itemRows = '';
    inv.items.forEach((it, i) => {
        itemRows += `<tr>
            <td>${i+1}</td>
            <td>${it.product_name}</td>
            <td style="text-align:center">${it.qty}</td>
            <td style="text-align:right">₹${parseFloat(it.rate).toFixed(2)}</td>
            <td style="text-align:right">₹${parseFloat(it.item_discount||0).toFixed(2)}</td>
            <td style="text-align:right">₹${parseFloat(it.total).toFixed(2)}</td>
        </tr>`;
    });

    let splitRows = '';
    if (inv.split_payments) {
        inv.split_payments.forEach(sp => {
            splitRows += `<tr><td>${sp.mode.toUpperCase()}</td><td style="text-align:right">₹${parseFloat(sp.amount).toFixed(2)}</td></tr>`;
        });
    }

    const html = `<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>Offline Invoice — ${inv.offline_ref}</title>
<style>
body{font-family:monospace;font-size:12px;max-width:80mm;margin:0 auto;padding:8px;}
h3{text-align:center;margin:4px 0;font-size:14px;}
.center{text-align:center;}
.bold{font-weight:bold;}
hr{border:none;border-top:1px dashed #000;margin:4px 0;}
table{width:100%;border-collapse:collapse;font-size:11px;}
th{background:#eee;padding:2px 4px;text-align:left;font-size:10px;}
td{padding:2px 4px;}
.total-row{font-weight:bold;font-size:13px;}
.offline-badge{background:#dc3545;color:#fff;text-align:center;padding:4px;font-size:11px;border-radius:4px;margin:6px 0;}
.cloud-note{background:#fff3cd;border:1px solid #ffc107;text-align:center;padding:4px;font-size:10px;border-radius:4px;margin:6px 0;}
@media print{.no-print{display:none;}}
</style>
</head><body>
<h3>${company}</h3>
<div class="center">${address}</div>
<div class="center">Tel: ${phone}</div>
${gstNo ? `<div class="center">GSTIN: ${gstNo}</div>` : ''}
<hr>
<div class="offline-badge">🔴 OFFLINE INVOICE — WORKING LOCALLY</div>
<div class="cloud-note">⚠ This is a temporary bill. Final invoice will be generated when internet is restored.</div>
<hr>
<div class="bold">Ref: ${inv.offline_ref}</div>
<div>Date: ${inv.invoice_date} ${inv.created_at_local.slice(11,16)}</div>
<div>Customer: ${inv.customer_name} ${inv.customer_phone ? '('+inv.customer_phone+')' : ''}</div>
<div>Type: ${inv.invoice_type.toUpperCase()} | Mode: ${inv.payment_mode.toUpperCase()}</div>
<hr>
<table>
<thead><tr><th>#</th><th>Item</th><th>Qty</th><th>Rate</th><th>Disc</th><th>Total</th></tr></thead>
<tbody>${itemRows}</tbody>
</table>
<hr>
<table>
<tr><td>MRP Total</td><td style="text-align:right">₹${inv.subtotal.toFixed(2)}</td></tr>
${inv.total_discount>0?`<tr style="color:#16a34a"><td>Item Discount</td><td style="text-align:right">- ₹${inv.total_discount.toFixed(2)}</td></tr>`:''}
${inv.cd_amount>0?`<tr><td>CD Discount</td><td style="text-align:right">- ₹${inv.cd_amount.toFixed(2)}</td></tr>`:''}
${inv.bill_discount_amount>0?`<tr><td>Store Discount</td><td style="text-align:right">- ₹${inv.bill_discount_amount.toFixed(2)}</td></tr>`:''}
<tr class="total-row"><td>GRAND TOTAL</td><td style="text-align:right">₹${inv.grand_total.toFixed(2)}</td></tr>
${(inv.subtotal-inv.grand_total)>0?`<tr style="color:#15803d;font-weight:bold;border-top:1px dashed #000"><td>🎉 YOU SAVED</td><td style="text-align:right">₹${(inv.subtotal-inv.grand_total).toFixed(2)}</td></tr>`:''}
${inv.wallet_amount_used>0?`<tr><td>Wallet Used</td><td style="text-align:right">₹${inv.wallet_amount_used.toFixed(2)}</td></tr>`:''}
<tr><td>Paid</td><td style="text-align:right">₹${inv.paid_amount.toFixed(2)}</td></tr>
${inv.due_amount>0?`<tr style="color:#dc3545"><td>Due</td><td style="text-align:right">₹${inv.due_amount.toFixed(2)}</td></tr>`:''}
</table>
${splitRows ? `<hr><div class="bold" style="font-size:10px;">Payment Split:</div><table>${splitRows}</table>` : ''}
<hr>
<div class="center" style="font-size:10px;">Thank you for your purchase!</div>
<div class="center" style="font-size:9px;color:#666;">This offline bill will auto-sync when internet returns.</div>
<hr>
<div class="no-print" style="text-align:center;margin-top:10px;">
  <button onclick="window.print()" style="padding:8px 20px;background:#333;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;">🖨 Print Bill</button>
  <button onclick="window.close()" style="padding:8px 20px;background:#666;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;margin-left:8px;">Close</button>
</div>
<script>window.onload=function(){window.print();}<\/script>
</body></html>`;

    const win = window.open('', '_blank', 'width=400,height=700,scrollbars=yes');
    if (win) {
        win.document.write(html);
        win.document.close();
    }

    // Show success notification in billing page
    const notice = document.createElement('div');
    notice.className = 'alert alert-warning alert-dismissible shadow-sm mt-3';
    notice.style.cssText = 'border-left:4px solid #ffc107; font-size:.88rem;';
    notice.innerHTML = `
        <strong>🔴 Offline Invoice Saved: ${inv.offline_ref}</strong><br>
        Bill saved locally. It will automatically sync to cloud when internet is restored.
        <a href="<?= base_url('offline_sync/dashboard') ?>" class="btn btn-sm btn-warning ml-2" target="_blank">
            <i class="fas fa-sync mr-1"></i>Sync Dashboard
        </a>
        <button type="button" class="close" data-dismiss="alert">&times;</button>`;

    const form = document.getElementById('invoiceForm');
    if (form && form.parentNode) {
        form.parentNode.insertBefore(notice, form);
    }

    // Reset billing form for next invoice
    setTimeout(function() {
        document.querySelectorAll('#itemsBody tr').forEach(r => r.remove());
        rowCount = 0;
        document.getElementById('customer_id').value = '';
        document.getElementById('customer_info').style.display = 'none';
        document.getElementById('cust_phone_search').value = '';
        document.getElementById('phone_search_status').textContent = '';
        document.querySelector('input[name="paid_amount"]').value = '0';
        addRow();
        recalcTotals();
        updateRowCount();
        document.getElementById('cust_phone_search').focus();
    }, 500);
}

// On billing page load: seed IDB with current page's data for offline use
window.addEventListener('load', async function() {
    if (typeof PosDB === 'undefined') return;
    await PosDB.open();

    // Seed products if they are not yet in IDB
    const prodCount = await PosDB.count('products');
    if (prodCount === 0 && typeof PRODUCTS !== 'undefined' && PRODUCTS.length) {
        await PosDB.putBulk('products', PRODUCTS);
        console.info('[BillingPage] Seeded', PRODUCTS.length, 'products to IDB from page data.');
    }

    // Seed customers
    const custCount = await PosDB.count('customers');
    if (custCount === 0 && typeof CUSTOMERS !== 'undefined' && CUSTOMERS.length) {
        await PosDB.putBulk('customers', CUSTOMERS);
        console.info('[BillingPage] Seeded', CUSTOMERS.length, 'customers to IDB.');
    }
});

/* ── PLU Selection (multi-MRP popup) ─────────────────────────── */
let _pluList = [], _pluIdx = 0;

function addPluToRow(plu) {
    let rid = findEmptyRow();
    if (rid === null) { addRow(); rid = rowCount; }
    const sel = document.querySelector(`#row_${rid} select[name="product_id[]"]`);
    if (sel) { sel.value = plu.product_id; fillProduct(sel, rid); }
    setTimeout(() => {
        const rateEl  = document.getElementById('rate_' + rid);
        const mrpEl   = document.getElementById('mrp_' + rid);
        const pluIdEl = document.getElementById('plu_id_' + rid);
        const badgeEl = document.getElementById('plu_badge_' + rid);
        const stockEl = document.getElementById('stock_' + rid);
        if (rateEl)  rateEl.value  = parseFloat(plu.selling_rate || plu.mrp || 0).toFixed(2);
        if (mrpEl)   mrpEl.value   = parseFloat(plu.mrp || 0).toFixed(2);
        if (pluIdEl) pluIdEl.value = plu.id;
        if (badgeEl) { badgeEl.textContent = plu.plu_code + ' | MRP ₹' + plu.mrp; badgeEl.style.display = ''; }
        if (stockEl) { stockEl.textContent = plu.stock_qty; stockEl.className = parseFloat(plu.stock_qty) > 5 ? 'badge badge-success' : 'badge badge-warning'; }
        calcRow(rid);
        updateRowCount();
        scrollToRowAndHighlight(rid);
    }, 80);
    smartSearchInput.value = '';
    searchDropdown.style.display = 'none';
    smartSearchInput.focus();
}

let gsmCurrentData = null;
let gsmTargetRowId = null;

function openGarmentSalesMatrix(productId, targetRowId = null) {
    gsmTargetRowId = targetRowId;
    gsmCurrentData = null;
    
    document.getElementById('gsmThead').innerHTML = '';
    document.getElementById('gsmTbody').innerHTML = '<tr><td colspan="10" class="py-4 text-center text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><br><span class="small mt-2 d-inline-block">Loading garment sizes &amp; colours...</span></td></tr>';
    document.getElementById('gsmTfoot').innerHTML = '';
    document.getElementById('btnGsmAddItems').disabled = true;
    document.getElementById('gsmGrandTotalBadge').textContent = '0 pcs';
    document.getElementById('gsmNoVariantsAlert').style.display = 'none';

    $('#garmentSalesMatrixModal').modal('show');

    fetch('<?= base_url("billing/get_variants_matrix_ajax") ?>?product_id=' + productId)
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status + ' ' + r.statusText);
            return r.json();
        })
        .then(res => {
            if (!res.success) {
                alert(res.message || 'Error loading garment matrix.');
                closeGarmentSalesMatrix();
                return;
            }
            if (res.count === 0) {
                // No variants exist for this product, fallback to standard row addition
                $('#garmentSalesMatrixModal').modal('hide');
                if (gsmTargetRowId) {
                    const sel = document.querySelector(`#row_${gsmTargetRowId} select[name="product_id[]"]`);
                    if (sel) { sel.value = productId; fillProduct(sel, gsmTargetRowId); }
                } else {
                    addRowWithProduct(productId);
                }
                return;
            }
            gsmCurrentData = res;
            renderGarmentSalesMatrix(res);
        })
        .catch(err => {
            console.error('Matrix load error:', err);
            $('#garmentSalesMatrixModal').modal('hide');
            if (gsmTargetRowId) {
                const sel = document.querySelector(`#row_${gsmTargetRowId} select[name="product_id[]"]`);
                if (sel) { sel.value = productId; fillProduct(sel, gsmTargetRowId); }
            } else {
                addRowWithProduct(productId);
            }
        });
}

function closeGarmentSalesMatrix() {
    $('#garmentSalesMatrixModal').modal('hide');
    gsmTargetRowId = null;
    gsmCurrentData = null;
    if (typeof smartSearchInput !== 'undefined' && smartSearchInput) {
        setTimeout(() => smartSearchInput.focus(), 150);
    }
}

function renderGarmentSalesMatrix(data) {
    const p = data.product;
    const sizes = data.sizes;
    const colours = data.colours;
    const matrix = data.matrix;

    document.getElementById('gsmProductTitle').textContent = p.name;
    document.getElementById('gsmProductSubtitle').textContent = 
        'Style: ' + (p.style_code || p.item_code) + 
        (p.garment_category ? ' | Category: ' + p.garment_category : '') + 
        (p.gender ? ' | ' + p.gender : '');

    document.getElementById('gsmPriceBadge').textContent = 'Selling Price: ₹' + parseFloat(p.selling_price || 0).toFixed(2);
    document.getElementById('gsmMrpBadge').textContent = 'MRP: ₹' + parseFloat(p.mrp || 0).toFixed(2);
    document.getElementById('gsmHsnBadge').textContent = 'HSN: ' + (p.hsn_code || '6204') + ' (' + p.gst_percent + '%)';

    let totalStockSum = 0;
    Object.values(matrix).forEach(colorSizes => {
        Object.values(colorSizes).forEach(v => {
            totalStockSum += (parseFloat(v.stock_qty) || 0);
        });
    });
    document.getElementById('gsmTotalStockBadge').innerHTML = 'Total Available Stock: <strong class="' + (totalStockSum > 0 ? 'text-success' : 'text-danger') + '">' + totalStockSum + ' pcs</strong>';

    if (!sizes.length || !colours.length || !data.count) {
        document.getElementById('gsmThead').innerHTML = '';
        document.getElementById('gsmTbody').innerHTML = '';
        document.getElementById('gsmTfoot').innerHTML = '';
        document.getElementById('gsmNoVariantsAlert').style.display = 'block';
        return;
    }
    document.getElementById('gsmNoVariantsAlert').style.display = 'none';

    // 1. Build Header
    let theadHtml = '<tr><th style="width: 140px; text-align: left; padding-left: 12px; background:#212529;">Colour / Shade</th>';
    sizes.forEach(s => {
        theadHtml += `<th style="min-width: 95px; background:#212529;" class="text-center">${s.name} ${s.measurement ? `<br><small class="text-white-50" style="font-size:9px;">${s.measurement}</small>` : ''}</th>`;
    });
    theadHtml += '<th style="width: 90px; text-align: center; background:#343a40;">Total</th></tr>';
    document.getElementById('gsmThead').innerHTML = theadHtml;

    // 2. Build Rows
    let tbodyHtml = '';
    colours.forEach(c => {
        tbodyHtml += `<tr data-colour="${c.name}">
            <td style="text-align: left; vertical-align: middle; padding-left: 12px; font-weight: 600;">
                <span class="d-inline-block rounded-circle mr-2 border shadow-xs" style="width: 14px; height: 14px; background: ${c.hex || '#808080'}; vertical-align: middle;"></span>
                ${c.name}
            </td>`;
        
        sizes.forEach(s => {
            const v = matrix[c.name] && matrix[c.name][s.name];
            if (v) {
                const isOutOfStock = (parseFloat(v.stock_qty) <= 0);
                const sp = parseFloat(v.selling_price || p.selling_price || 0).toFixed(2);
                tbodyHtml += `<td class="p-1 text-center align-middle ${isOutOfStock ? 'bg-light' : ''}" style="vertical-align: middle;">
                    <div style="font-size: 11px; font-weight: 600; color: #4A0E17;">₹${sp}</div>
                    <div class="input-group input-group-sm justify-content-center my-1" style="max-width: 95px; margin: 0 auto;">
                        <input type="number" min="0" step="1" 
                               class="form-control form-control-sm text-center font-weight-bold gsm-qty-input ${isOutOfStock ? 'border-warning' : 'border-secondary'}"
                               data-col="${c.name}" data-siz="${s.name}" data-var-id="${v.id}"
                               data-sku="${v.variant_sku}" data-barcode="${v.barcode}" data-stock="${v.stock_qty}"
                               data-sp="${v.selling_price}" data-mrp="${v.mrp}"
                               placeholder="0" style="height: 30px; font-size: 14px; padding: 2px;">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-1 py-0 gsm-plus-btn" title="Add 1" onclick="gsmQuickAdd(this)">+1</button>
                        </div>
                    </div>
                    <div class="small" style="font-size: 10px;">
                        <span class="badge ${v.stock_qty > 0 ? 'badge-success' : 'badge-danger'} py-0 px-1">
                            Stk: ${v.stock_qty}
                        </span>
                    </div>
                </td>`;
            } else {
                tbodyHtml += `<td class="bg-light text-muted" style="vertical-align: middle; font-size: 11px;">—</td>`;
            }
        });

        tbodyHtml += `<td style="vertical-align: middle;" class="text-center bg-light font-weight-bold text-primary gsm-row-total">0 pcs</td></tr>`;
    });
    document.getElementById('gsmTbody').innerHTML = tbodyHtml;

    // 3. Build Footer (Column Totals)
    let tfootHtml = '<tr><td style="text-align: left; padding-left: 12px;">Size Totals:</td>';
    sizes.forEach(s => {
        tfootHtml += `<td class="text-center gsm-col-total" data-siz="${s.name}">0 pcs</td>`;
    });
    tfootHtml += '<td class="text-center font-weight-bold text-success bg-white" id="gsmFooterGrandTotal">0 pcs</td></tr>';
    document.getElementById('gsmTfoot').innerHTML = tfootHtml;

    // Event listeners on inputs
    document.querySelectorAll('.gsm-qty-input').forEach(inp => {
        inp.addEventListener('input', gsmRecalcTotals);
        inp.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const total = parseFloat(document.getElementById('gsmGrandTotalBadge').textContent) || 0;
                if (total > 0) {
                    gsmSubmitMatrix();
                } else {
                    const val = parseFloat(this.value) || 0;
                    if (val > 0) {
                        gsmRecalcTotals();
                        gsmSubmitMatrix();
                    } else {
                        const all = Array.from(document.querySelectorAll('.gsm-qty-input'));
                        const idx = all.indexOf(this);
                        if (idx >= 0 && idx < all.length - 1) {
                            all[idx + 1].focus();
                            all[idx + 1].select();
                        }
                    }
                }
            }
        });
    });

    gsmRecalcTotals();

    // Auto-focus the first in-stock variant or first available input
    setTimeout(() => {
        let targetInp = null;
        const allInps = document.querySelectorAll('.gsm-qty-input');
        for (const inp of allInps) {
            const stk = parseFloat(inp.getAttribute('data-stock')) || 0;
            if (stk > 0) { targetInp = inp; break; }
        }
        if (!targetInp && allInps.length > 0) targetInp = allInps[0];
        if (targetInp) { targetInp.focus(); targetInp.select(); }
    }, 250);
}

function gsmQuickAdd(btn) {
    const inp = btn.closest('.input-group').querySelector('.gsm-qty-input');
    if (inp) {
        inp.value = (parseFloat(inp.value) || 0) + 1;
        gsmRecalcTotals();
        inp.focus();
    }
}

function gsmRecalcTotals() {
    let grandTotal = 0;

    // Calculate row totals
    document.querySelectorAll('#gsmTbody tr').forEach(tr => {
        let rowSum = 0;
        tr.querySelectorAll('.gsm-qty-input').forEach(inp => {
            const val = parseFloat(inp.value) || 0;
            if (val > 0) rowSum += val;
        });
        const rTot = tr.querySelector('.gsm-row-total');
        if (rTot) rTot.textContent = rowSum + ' pcs';
    });

    // Calculate column totals
    if (gsmCurrentData && gmmSizesSafe(gsmCurrentData)) {
        gsmCurrentData.sizes.forEach(s => {
            let colSum = 0;
            document.querySelectorAll(`.gsm-qty-input[data-siz="${s.name}"]`).forEach(inp => {
                const val = parseFloat(inp.value) || 0;
                if (val > 0) colSum += val;
            });
            const cTot = document.querySelector(`.gsm-col-total[data-siz="${s.name}"]`);
            if (cTot) cTot.textContent = colSum + ' pcs';
            grandTotal += colSum;
        });
    }

    const ft = document.getElementById('gsmFooterGrandTotal');
    if (ft) ft.textContent = grandTotal + ' pcs';

    const bd = document.getElementById('gsmGrandTotalBadge');
    if (bd) bd.textContent = grandTotal + ' pcs';

    const btn = document.getElementById('btnGsmAddItems');
    if (btn) btn.disabled = grandTotal <= 0;
}

function gmmSizesSafe(d) {
    return d && Array.isArray(d.sizes);
}

function gsmSubmitMatrix() {
    if (!gsmCurrentData || !gsmCurrentData.product) return;

    const p = gsmCurrentData.product;
    const inputsWithQty = Array.from(document.querySelectorAll('.gsm-qty-input')).filter(inp => (parseFloat(inp.value) || 0) > 0);

    if (inputsWithQty.length === 0) {
        alert('Please enter a quantity or click +1 for at least one size & colour.');
        return;
    }

    let isFirst = true;
    inputsWithQty.forEach(inp => {
        const qty = parseFloat(inp.value) || 0;
        const vid = inp.getAttribute('data-var-id');
        const sku = inp.getAttribute('data-sku');
        const barcode = inp.getAttribute('data-barcode');
        const col = inp.getAttribute('data-col');
        const siz = inp.getAttribute('data-siz');
        const stk = inp.getAttribute('data-stock');
        const sp = inp.getAttribute('data-sp') || p.selling_price;
        const mrp = inp.getAttribute('data-mrp') || p.mrp;

        const variantObj = {
            id: parseInt(vid),
            product_id: parseInt(p.id),
            product_name: p.name,
            variant_sku: sku,
            barcode: barcode,
            colour_name: col,
            size_name: siz,
            selling_price: parseFloat(sp),
            mrp: parseFloat(mrp),
            stock_qty: parseFloat(stk),
            loc_stock_qty: parseFloat(stk),
            gst_percent: parseFloat(p.gst_percent || 5.0),
            hsn_code: p.hsn_code || '6204',
            unit: p.unit || 'PCS'
        };

        if (isFirst && gsmTargetRowId) {
            fillTargetRowWithGarment(gsmTargetRowId, variantObj, p, qty);
            isFirst = false;
        } else {
            addGarmentToRow(variantObj, p, qty);
        }
    });

    closeGarmentSalesMatrix();
}

function fillTargetRowWithGarment(rid, v, p, qty = 1) {
    learnProduct({
        id: parseInt(v.product_id),
        name: p ? p.name : v.product_name,
        item_code: p ? p.item_code : (v.parent_item_code || ''),
        selling_price: parseFloat(v.selling_price) || 0,
        mrp: parseFloat(v.mrp) || 0,
        gst_percent: parseFloat(p ? p.gst_percent : (v.gst_percent || 5.0)),
        unit: (p && p.unit) ? p.unit : 'PCS',
        stock_qty: parseFloat(v.stock_qty || v.loc_stock_qty || 0),
        hsn_code: (p && p.hsn_code) ? p.hsn_code : (v.hsn_code || '6204'),
        image1: (p && p.image1) ? p.image1 : (v.image1 || ''),
        no_discount: 0,
        allow_name_edit: 0,
        maintain_serial: 0,
        warranty_applicable: 0,
        warranty_days: 0,
        carton_size: 1,
        is_garment: 1
    });

    const sel = document.querySelector(`#row_${rid} select[name="product_id[]"]`);
    if (sel) { sel.value = v.product_id; fillProduct(sel, rid); }
    setTimeout(() => {
        const rateEl   = document.getElementById('rate_' + rid);
        const mrpEl    = document.getElementById('mrp_' + rid);
        const qtyEl    = document.getElementById('qty_' + rid);
        const varIdEl  = document.getElementById('variant_id_' + rid);
        const skuEl    = document.getElementById('variant_sku_' + rid);
        const sizeEl   = document.getElementById('size_name_' + rid);
        const colEl    = document.getElementById('colour_name_' + rid);
        const badgeEl  = document.getElementById('variant_badge_' + rid);
        const eanEl    = document.getElementById('ean_' + rid);
        const stockEl  = document.getElementById('stock_' + rid);

        if (rateEl)  rateEl.value  = parseFloat(v.selling_price || v.mrp || 0).toFixed(2);
        if (mrpEl)   mrpEl.value   = parseFloat(v.mrp || 0).toFixed(2);
        if (qtyEl)   qtyEl.value   = parseInt(qty) || 1;
        if (varIdEl) varIdEl.value = v.id;
        if (skuEl)   skuEl.value   = v.variant_sku || '';
        if (sizeEl)  sizeEl.value  = v.size_name || '';
        if (colEl)   colEl.value   = v.colour_name || '';
        if (eanEl && v.barcode) eanEl.textContent = v.barcode;

        if (badgeEl) {
            badgeEl.innerHTML = '<i class="fas fa-tshirt mr-1"></i>' + (v.colour_name || '') + ' / ' + (v.size_name || '');
            badgeEl.style.display = 'inline-block';
            badgeEl.style.cursor = 'pointer';
            badgeEl.title = 'Click to change size/colour';
            badgeEl.onclick = function() { openGarmentSalesMatrix(v.product_id, rid); };
        }
        if (stockEl) {
            const sq = parseFloat(v.loc_stock_qty !== undefined ? v.loc_stock_qty : v.stock_qty);
            stockEl.textContent = sq + ' PCS';
            stockEl.className = sq > 0 ? 'badge badge-success' : 'badge badge-danger';
        }
        calcRow(rid);
        updateRowCount();
        scrollToRowAndHighlight(rid);
    }, 80);
}

function addGarmentToRow(v, p, customQty = 1) {
    if (!v) return;
    const addQty = parseInt(customQty) || 1;
    learnProduct({
        id: parseInt(v.product_id),
        name: p ? p.name : v.product_name,
        item_code: p ? p.item_code : (v.parent_item_code || ''),
        selling_price: parseFloat(v.selling_price) || 0,
        mrp: parseFloat(v.mrp) || 0,
        gst_percent: parseFloat(p ? p.gst_percent : (v.gst_percent || 5.0)),
        unit: (p && p.unit) ? p.unit : 'PCS',
        stock_qty: parseFloat(v.stock_qty || v.loc_stock_qty || 0),
        hsn_code: (p && p.hsn_code) ? p.hsn_code : (v.hsn_code || '6204'),
        image1: (p && p.image1) ? p.image1 : (v.image1 || ''),
        no_discount: 0,
        allow_name_edit: 0,
        maintain_serial: 0,
        warranty_applicable: 0,
        warranty_days: 0,
        carton_size: 1,
        is_garment: 1
    });

    const existingVariants = document.querySelectorAll('#itemsBody input[name="variant_id[]"]');
    for (const vInput of existingVariants) {
        if (parseInt(vInput.value) === parseInt(v.id)) {
            const rowEl = vInput.closest('tr');
            const rid = rowEl.id.replace('row_', '');
            const qtyEl = document.getElementById('qty_' + rid);
            if (qtyEl) {
                qtyEl.value = parseInt(qtyEl.value || 0) + addQty;
                calcRow(parseInt(rid));
                scrollToRowAndHighlight(rid);
            }
            if (typeof smartSearchInput !== 'undefined' && smartSearchInput) smartSearchInput.value = '';
            if (typeof searchDropdown !== 'undefined' && searchDropdown) searchDropdown.style.display = 'none';
            return;
        }
    }

    let rid = findEmptyRow();
    if (rid === null) { addRow(); rid = rowCount; }
    const sel = document.querySelector(`#row_${rid} select[name="product_id[]"]`);
    if (sel) { sel.value = v.product_id; fillProduct(sel, rid); }
    setTimeout(() => {
        const rateEl   = document.getElementById('rate_' + rid);
        const mrpEl    = document.getElementById('mrp_' + rid);
        const qtyEl    = document.getElementById('qty_' + rid);
        const varIdEl  = document.getElementById('variant_id_' + rid);
        const skuEl    = document.getElementById('variant_sku_' + rid);
        const sizeEl   = document.getElementById('size_name_' + rid);
        const colEl    = document.getElementById('colour_name_' + rid);
        const badgeEl  = document.getElementById('variant_badge_' + rid);
        const eanEl    = document.getElementById('ean_' + rid);
        const stockEl  = document.getElementById('stock_' + rid);

        if (rateEl)  rateEl.value  = parseFloat(v.selling_price || v.mrp || 0).toFixed(2);
        if (mrpEl)   mrpEl.value   = parseFloat(v.mrp || 0).toFixed(2);
        if (qtyEl)   qtyEl.value   = addQty;
        if (varIdEl) varIdEl.value = v.id;
        if (skuEl)   skuEl.value   = v.variant_sku || '';
        if (sizeEl)  sizeEl.value  = v.size_name || '';
        if (colEl)   colEl.value   = v.colour_name || '';
        if (eanEl && v.barcode) eanEl.textContent = v.barcode;

        if (badgeEl) {
            badgeEl.innerHTML = '<i class="fas fa-tshirt mr-1"></i>' + (v.colour_name || '') + ' / ' + (v.size_name || '');
            badgeEl.style.display = 'inline-block';
            badgeEl.style.cursor = 'pointer';
            badgeEl.title = 'Click to change size/colour';
            badgeEl.onclick = function() { openGarmentSalesMatrix(v.product_id, rid); };
        }
        if (stockEl) {
            const sq = parseFloat(v.loc_stock_qty !== undefined ? v.loc_stock_qty : v.stock_qty);
            stockEl.textContent = sq + ' PCS';
            stockEl.className = sq > 0 ? 'badge badge-success' : 'badge badge-danger';
        }
        calcRow(rid);
        updateRowCount();
        scrollToRowAndHighlight(rid);
    }, 80);
    if (typeof smartSearchInput !== 'undefined' && smartSearchInput) smartSearchInput.value = '';
    if (typeof searchDropdown !== 'undefined' && searchDropdown) searchDropdown.style.display = 'none';
    if (typeof smartSearchInput !== 'undefined' && smartSearchInput) smartSearchInput.focus();
}

function openPluSelectModal(plus) {
    _pluList = plus; _pluIdx = 0;
    renderPluModal();
    $('#pluPickModal').modal('show');
    setTimeout(() => document.getElementById('pluPickList').focus(), 300);
}

function renderPluModal() {
    const el = document.getElementById('pluPickList');
    el.innerHTML = _pluList.map((p, i) => {
        const inStock = parseFloat(p.stock_qty) > 0;
        const cls = i === _pluIdx ? 'plu-pick-row plu-pick-active' : 'plu-pick-row';
        const stockBadge = inStock
            ? `<span class="badge badge-success">${p.stock_qty} in stock</span>`
            : `<span class="badge badge-danger">Out of stock</span>`;
        return `<div class="${cls}" data-idx="${i}" onclick="pluPickClick(${i})">
            <span class="font-weight-bold text-primary">${p.plu_code}</span>
            <span class="mx-2">${p.item_name || p.name || ''}</span>
            <span class="font-weight-bold text-dark">MRP ₹${p.mrp}</span>
            <span class="mx-2">| Sell ₹${p.selling_rate}</span>
            ${stockBadge}
        </div>`;
    }).join('');
}

function pluPickClick(idx) { _pluIdx = idx; confirmPluPick(); }

function confirmPluPick() {
    $('#pluPickModal').modal('hide');
    addPluToRow(_pluList[_pluIdx]);
}

document.addEventListener('keydown', function(e) {
    if (!document.getElementById('pluPickModal') || !$('#pluPickModal').hasClass('show')) return;
    if (e.key === 'ArrowDown') { _pluIdx = Math.min(_pluIdx + 1, _pluList.length - 1); renderPluModal(); e.preventDefault(); }
    else if (e.key === 'ArrowUp') { _pluIdx = Math.max(_pluIdx - 1, 0); renderPluModal(); e.preventDefault(); }
    else if (e.key === 'Enter') { confirmPluPick(); e.preventDefault(); }
    else if (e.key === 'Escape') { $('#pluPickModal').modal('hide'); }
});
</script>

<!-- PLU Pick Modal -->
<div class="modal fade" id="pluPickModal" tabindex="-1" data-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2" style="background:#4f46e5;">
        <h6 class="modal-title text-white mb-0"><i class="fas fa-tags mr-1"></i> Select MRP / PLU</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-2">
        <p class="text-muted small mb-2"><i class="fas fa-keyboard mr-1"></i> Use <kbd>↑</kbd><kbd>↓</kbd> then <kbd>Enter</kbd> to select, or click a row</p>
        <div id="pluPickList" tabindex="0" style="outline:none;"></div>
      </div>
    </div>
  </div>
</div>
<style>
.plu-pick-row { padding:10px 12px; border-radius:6px; margin-bottom:6px; cursor:pointer; border:1px solid #e2e8f0; transition:background .15s; }
.plu-pick-row:hover { background:#eff6ff; border-color:#93c5fd; }
.plu-pick-active { background:#dbeafe !important; border-color:#3b82f6 !important; }
</style>

<!-- ══ QUICK ADD PRODUCT MODAL ══ -->
<div class="modal fade" id="quickAddProductModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white py-2">
        <h6 class="modal-title mb-0"><i class="fas fa-box-open mr-1"></i> Quick Add New Product</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body py-3">
        <div id="quickAddProductAlert" class="d-none"></div>
        <div class="form-row">
          <div class="col-md-6 form-group mb-2">
            <label class="small font-weight-bold">Product Name <span class="text-danger">*</span></label>
            <input type="text" id="qp_name" class="form-control form-control-sm" placeholder="Product name">
          </div>
          <div class="col-md-3 form-group mb-2">
            <label class="small font-weight-bold">Item Code</label>
            <input type="text" id="qp_item_code" class="form-control form-control-sm" placeholder="Auto if blank">
          </div>
          <div class="col-md-3 form-group mb-2">
            <label class="small font-weight-bold">Unit</label>
            <select id="qp_unit" class="form-control form-control-sm">
              <option>PCS</option><option>KG</option><option>LTR</option><option>BOX</option><option>PKT</option><option>MTR</option>
            </select>
          </div>
          <div class="col-md-3 form-group mb-2">
            <label class="small font-weight-bold">Purchase Price</label>
            <input type="number" id="qp_purchase_price" class="form-control form-control-sm" placeholder="0.00" step="0.01" min="0">
          </div>
          <div class="col-md-3 form-group mb-2">
            <label class="small font-weight-bold">Selling Price <span class="text-danger">*</span></label>
            <input type="number" id="qp_selling_price" class="form-control form-control-sm" placeholder="0.00" step="0.01" min="0">
          </div>
          <div class="col-md-3 form-group mb-2">
            <label class="small font-weight-bold">MRP</label>
            <input type="number" id="qp_mrp" class="form-control form-control-sm" placeholder="0.00" step="0.01" min="0">
          </div>
          <div class="col-md-3 form-group mb-2">
            <label class="small font-weight-bold">GST %</label>
            <select id="qp_gst" class="form-control form-control-sm">
              <option value="0">0%</option><option value="5">5%</option><option value="12">12%</option>
              <option value="18" selected>18%</option><option value="40">40%</option>
            </select>
          </div>
          <div class="col-md-3 form-group mb-2">
            <label class="small font-weight-bold">Opening Stock</label>
            <input type="number" id="qp_stock" class="form-control form-control-sm" placeholder="0" min="0" value="0">
          </div>
          <div class="col-md-3 form-group mb-2">
            <label class="small font-weight-bold">Category</label>
            <input type="text" id="qp_category" class="form-control form-control-sm" placeholder="Optional">
          </div>
          <div class="col-md-3 form-group mb-2">
            <label class="small font-weight-bold">Brand</label>
            <input type="text" id="qp_brand" class="form-control form-control-sm" placeholder="Optional">
          </div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <small class="text-muted mr-auto"><i class="fas fa-info-circle"></i> Product will be saved & auto-added to this bill</small>
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success btn-sm" id="qpSaveBtn" onclick="saveQuickProduct()">
          <i class="fas fa-save"></i> Save & Add to Bill
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══ QUICK ADD CUSTOMER MODAL ══ -->
<div class="modal fade" id="quickAddCustomerModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2">
        <h6 class="modal-title mb-0"><i class="fas fa-user-plus mr-1"></i> Quick Add New Customer</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body py-3">
        <div id="quickAddCustomerAlert" class="d-none"></div>
        <div class="form-row">
          <div class="col-md-6 form-group mb-2">
            <label class="small font-weight-bold">Customer Name <span class="text-danger">*</span></label>
            <input type="text" id="qc_name" class="form-control form-control-sm" placeholder="Full name">
          </div>
          <div class="col-md-6 form-group mb-2">
            <label class="small font-weight-bold">Mobile</label>
            <input type="text" id="qc_phone" class="form-control form-control-sm" placeholder="10-digit mobile">
          </div>
          <div class="col-md-12 form-group mb-2">
            <label class="small font-weight-bold">Address</label>
            <input type="text" id="qc_address" class="form-control form-control-sm" placeholder="Optional">
          </div>
          <div class="col-md-6 form-group mb-0">
            <label class="small font-weight-bold">GSTIN</label>
            <input type="text" id="qc_gstin" class="form-control form-control-sm" placeholder="Optional" style="text-transform:uppercase;">
          </div>
          <div class="col-md-6 form-group mb-0">
            <label class="small font-weight-bold">Opening Balance (₹)</label>
            <input type="number" id="qc_balance" class="form-control form-control-sm" placeholder="0" value="0" min="0" step="0.01">
          </div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <small class="text-muted mr-auto"><i class="fas fa-info-circle"></i> Customer will be saved & selected on this bill</small>
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="qcSaveBtn" onclick="saveNewCustomerQuick()">
          <i class="fas fa-save"></i> Save & Select
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// ── Quick Add Product ──
function openQuickAddProduct() {
    ['qp_name','qp_item_code','qp_category','qp_brand'].forEach(id => document.getElementById(id).value = '');
    ['qp_purchase_price','qp_selling_price','qp_mrp'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('qp_stock').value = '0';
    document.getElementById('qp_gst').value = '18';
    document.getElementById('qp_unit').value = 'PCS';
    document.getElementById('quickAddProductAlert').className = 'd-none';
    $('#quickAddProductModal').modal('show');
    setTimeout(() => document.getElementById('qp_name').focus(), 400);
}

function saveQuickProduct() {
    var name = document.getElementById('qp_name').value.trim();
    if (!name) { showQPAlert('danger','Product name is required.'); return; }
    var sp = parseFloat(document.getElementById('qp_selling_price').value) || 0;
    if (sp <= 0) { showQPAlert('danger','Selling price is required.'); return; }

    var btn = document.getElementById('qpSaveBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    var csrf = '<?= $this->security->get_csrf_token_name() ?>';
    var csrfVal = '<?= $this->security->get_csrf_hash() ?>';
    var fd = new FormData();
    fd.append(csrf, csrfVal);
    fd.append('name',           name);
    fd.append('item_code',      document.getElementById('qp_item_code').value.trim());
    fd.append('unit',           document.getElementById('qp_unit').value);
    fd.append('basic_cost',     document.getElementById('qp_purchase_price').value || 0);
    fd.append('selling_price',  sp);
    fd.append('mrp',            document.getElementById('qp_mrp').value || sp);
    fd.append('gst_percent',    document.getElementById('qp_gst').value);
    fd.append('stock_qty',      document.getElementById('qp_stock').value || 0);
    fd.append('category',       document.getElementById('qp_category').value.trim());
    fd.append('brand',          document.getElementById('qp_brand').value.trim());

    fetch('<?= base_url('products/quick_add_ajax') ?>', { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Save & Add to Bill';
        if (data.success) {
            // Push new product into local PRODUCTS array
            PRODUCTS.push(data.product);
            // Add <option> to all product selects in the bill rows
            var p = data.product;
            document.querySelectorAll('select[name="product_id[]"]').forEach(function(sel) {
                var opt = document.createElement('option');
                opt.value = p.id;
                opt.text  = p.name + ' [' + p.item_code + ']';
                opt.dataset.price     = p.selling_price;
                opt.dataset.gst       = p.gst_percent;
                opt.dataset.stock     = p.stock_qty;
                opt.dataset.mrp       = p.mrp;
                opt.dataset.image     = '';
                opt.dataset.noDiscount = 0;
                opt.dataset.nameEdit  = 0;
                sel.add(opt);
            });
            $('#quickAddProductModal').modal('hide');
            showQPAlert('success','');
            // Add directly to bill
            addRowWithProduct(data.product.id);
            showBillingToast('✅ ' + name + ' added to products & bill!', 'success');
        } else {
            showQPAlert('danger', data.error || 'Failed to save product.');
        }
    })
    .catch(() => {
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Save & Add to Bill';
        showQPAlert('danger','Server error. Please try again.');
    });
}

function showQPAlert(type, msg) {
    var el = document.getElementById('quickAddProductAlert');
    if (!msg) { el.className = 'd-none'; return; }
    el.className = 'alert alert-' + type + ' py-1 mb-2'; el.textContent = msg;
}

// ── Quick Add Customer (New Customer button, separate from phone-lookup flow) ──
function openQuickAddCustomer() {
    ['qc_name','qc_phone','qc_address','qc_gstin'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('qc_balance').value = '0';
    document.getElementById('quickAddCustomerAlert').className = 'd-none';
    $('#quickAddCustomerModal').modal('show');
    setTimeout(() => document.getElementById('qc_name').focus(), 400);
}

function saveNewCustomerQuick() {
    var name = document.getElementById('qc_name').value.trim();
    if (!name) { showQCAlert('danger','Customer name is required.'); return; }

    var btn = document.getElementById('qcSaveBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    var csrf = '<?= $this->security->get_csrf_token_name() ?>';
    var csrfVal = '<?= $this->security->get_csrf_hash() ?>';
    var fd = new FormData();
    fd.append(csrf, csrfVal);
    fd.append('name',            name);
    fd.append('phone',           document.getElementById('qc_phone').value.trim());
    fd.append('address',         document.getElementById('qc_address').value.trim());
    fd.append('gstin',           document.getElementById('qc_gstin').value.trim().toUpperCase());
    fd.append('opening_balance', document.getElementById('qc_balance').value || 0);

    fetch('<?= base_url('customers/quick_add_ajax') ?>', { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Save & Select';
        if (data.success) {
            $('#quickAddCustomerModal').modal('hide');
            // Add to customer dropdown and select
            var sel = document.getElementById('customer_id');
            if (sel) {
                var opt = new Option(data.customer.name + ' (' + (data.customer.phone||'No Phone') + ')', data.customer.id, true, true);
                sel.add(opt);
                $(sel).trigger('change');
            }
            showBillingToast('✅ Customer ' + name + ' added & selected!', 'success');
        } else {
            showQCAlert('danger', data.error || 'Failed to save customer.');
        }
    })
    .catch(() => {
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Save & Select';
        showQCAlert('danger','Server error. Please try again.');
    });
}

function showQCAlert(type, msg) {
    var el = document.getElementById('quickAddCustomerAlert');
    if (!msg) { el.className = 'd-none'; return; }
    el.className = 'alert alert-' + type + ' py-1 mb-2'; el.textContent = msg;
}

function showBillingToast(msg, type) {
    var t = document.createElement('div');
    t.style.cssText = 'position:fixed;top:70px;right:20px;z-index:99999;min-width:260px;';
    t.innerHTML = '<div class="alert alert-' + (type==='success'?'success':'danger') + ' shadow py-2 px-3 mb-0" style="font-size:.85rem;">' + msg + '</div>';
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// ── Device Label (Counter Name) ──────────────────────────────────
(function() {
    var STORAGE_KEY = 'ncg_device_label';
    var label = localStorage.getItem(STORAGE_KEY) || '';

    function applyLabel(l) {
        document.getElementById('hDeviceLabel').value = l;
        var badge = document.getElementById('deviceLabelBadge');
        if (badge) badge.textContent = '🖥️ ' + l;
    }

    function showSetupModal() {
        var overlay = document.createElement('div');
        overlay.id = 'deviceSetupOverlay';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99999;display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML = `
            <div style="background:#fff;border-radius:10px;padding:28px 32px;width:340px;box-shadow:0 8px 32px rgba(0,0,0,.3);text-align:center;">
                <div style="font-size:2rem;margin-bottom:8px;">🖥️</div>
                <h5 style="margin-bottom:6px;font-weight:700;">Set Counter / Machine Name</h5>
                <p style="font-size:13px;color:#666;margin-bottom:16px;">Enter a name for this billing machine (e.g. Counter-1, POS-2). This is saved in this browser only.</p>
                <input id="deviceLabelInput" type="text" maxlength="40" placeholder="e.g. Counter-1" style="width:100%;padding:8px 12px;border:2px solid #d1d5db;border-radius:6px;font-size:15px;margin-bottom:14px;outline:none;text-align:center;">
                <button onclick="saveDeviceLabel()" style="background:#1e293b;color:#fff;border:none;border-radius:6px;padding:9px 28px;font-size:14px;font-weight:600;cursor:pointer;width:100%;">Save & Continue</button>
            </div>`;
        document.body.appendChild(overlay);
        setTimeout(function(){ document.getElementById('deviceLabelInput').focus(); }, 100);
        document.getElementById('deviceLabelInput').addEventListener('keydown', function(e){ if(e.key==='Enter') saveDeviceLabel(); });
    }

    window.saveDeviceLabel = function() {
        var inp = document.getElementById('deviceLabelInput');
        var val = (inp ? inp.value.trim() : '') || 'Counter-1';
        localStorage.setItem(STORAGE_KEY, val);
        var ov = document.getElementById('deviceSetupOverlay');
        if (ov) ov.remove();
        applyLabel(val);
        showBillingToast('Counter set to: ' + val, 'success');
    };

    window.changeDeviceLabel = function() {
        var cur = localStorage.getItem(STORAGE_KEY) || '';
        var val = prompt('Enter new counter/machine name:', cur);
        if (val && val.trim()) {
            localStorage.setItem(STORAGE_KEY, val.trim());
            applyLabel(val.trim());
        }
    };

    if (!label) {
        showSetupModal();
    } else {
        applyLabel(label);
    }
})();
</script>

<!-- Device label badge (top of billing page) -->
<div style="position:fixed;bottom:10px;left:10px;z-index:9000;cursor:pointer;opacity:.85;" onclick="changeDeviceLabel()" title="Click to change counter name">
    <span id="deviceLabelBadge" style="background:#1e293b;color:#fff;font-size:11px;padding:3px 10px;border-radius:20px;font-weight:600;box-shadow:0 2px 6px rgba(0,0,0,.25);"></span>
</div>

<!-- F7 Reprint Modal -->
<div class="modal fade" id="reprintModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#4f46e5;">
                <h6 class="modal-title text-white mb-0"><i class="fas fa-print mr-1"></i> Reprint Bill (F7)</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-3">
                <div class="row mb-3">
                    <div class="col-md-5">
                        <label class="small font-weight-bold">Bill Date</label>
                        <input type="date" id="reprintDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" onchange="fetchBillsForReprint()">
                    </div>
                    <div class="col-md-5">
                        <label class="small font-weight-bold">Search Bill No</label>
                        <input type="text" id="reprintBillNo" class="form-control form-control-sm" placeholder="Enter Bill No" onkeydown="if(event.key==='Enter') printSpecificBill()">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-sm btn-primary w-100" onclick="printSpecificBill()">Print</button>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
                    <table class="table table-sm table-bordered table-hover mb-0" style="font-size:0.85rem;">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th>Bill No</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th class="text-center">Total Qty</th>
                                <th class="text-right">Grand Total</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="reprintBillsTableBody">
                            <tr><td colspan="6" class="text-center py-3">Loading bills...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* ══════════════════════════════════════════════
   PRICE CHECK (F3) — look up MRP / selling price / scheme net price for a
   customer's question, without adding anything to the bill.
   ══════════════════════════════════════════════ */
function openPriceCheck() {
    $('#priceCheckModal').modal('show');
    // Focus is handled by the shown.bs.modal listener (near the other modal
    // focus-management code) so it works regardless of how the modal was opened.
    document.getElementById('pcSearchInput').value = '';
    document.getElementById('pcDropdown').style.display = 'none';
    document.getElementById('pcDropdown').innerHTML = '';
    document.getElementById('pcResult').style.display = 'none';
    document.getElementById('pcStatus').textContent = '';
}

(function() {
    const pcInput = document.getElementById('pcSearchInput');
    if (!pcInput) return;
    const pcDropdown = document.getElementById('pcDropdown');

    let pcSelectedIdx = -1;

    function pcHighlight(links) {
        links.forEach((link, idx) => {
            if (idx === pcSelectedIdx) {
                link.style.setProperty('background-color', '#1e40af', 'important');
                link.style.setProperty('color', '#ffffff', 'important');
                link.classList.add('active-pc-item');
                link.scrollIntoView({ block: 'nearest' });
            } else {
                link.style.removeProperty('background-color');
                link.style.removeProperty('color');
                link.classList.remove('active-pc-item');
            }
        });
    }

    pcInput.addEventListener('input', function() {
        const q = this.value.trim();
        pcSelectedIdx = -1;
        if (q.length < 1) { pcDropdown.style.display = 'none'; pcDropdown.innerHTML = ''; return; }
        const results = PRODUCTS.filter(p =>
            p.name.toLowerCase().includes(q.toLowerCase()) ||
            (p.item_code && p.item_code.toLowerCase().includes(q.toLowerCase()))
        ).slice(0, 10);
        if (!results.length) { pcDropdown.style.display = 'none'; pcDropdown.innerHTML = ''; return; }
        pcDropdown.innerHTML = results.map(p => `
            <a href="#" class="list-group-item list-group-item-action py-1 px-2 pc-item-link" data-id="${p.id}" style="font-size:.82rem;" onclick="pcSelectProductById(${p.id}); return false;">
                <div class="d-flex justify-content-between">
                    <span>${p.name}</span>
                    <strong>₹${parseFloat(p.selling_price).toFixed(2)}</strong>
                </div>
            </a>`).join('');
        pcDropdown.style.display = 'block';
    });

    pcInput.addEventListener('keydown', function(e) {
        const links = document.querySelectorAll('.pc-item-link');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (links.length > 0) {
                pcSelectedIdx = (pcSelectedIdx + 1) % links.length;
                pcHighlight(links);
            }
            return;
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (links.length > 0) {
                pcSelectedIdx = (pcSelectedIdx - 1 + links.length) % links.length;
                pcHighlight(links);
            }
            return;
        } else if (e.key === 'Escape') {
            e.preventDefault();
            if (pcDropdown.style.display !== 'none' && links.length > 0) {
                // First Escape just closes the suggestion list, so a half-typed
                // search isn't lost immediately.
                pcDropdown.style.display = 'none';
                pcSelectedIdx = -1;
            } else {
                // Second Escape (or dropdown already closed) exits Price Check
                // entirely — hidden.bs.modal then returns focus to the main scan box.
                $('#priceCheckModal').modal('hide');
            }
            return;
        } else if (e.key !== 'Enter') {
            return;
        }

        e.preventDefault();

        // If a dropdown item is highlighted via arrow keys, pick it directly
        const activeLink = document.querySelector('.pc-item-link.active-pc-item');
        if (activeLink) { pcSelectProductById(parseInt(activeLink.dataset.id)); return; }

        const q = this.value.trim();
        if (!q) return;

        const localMatch = PRODUCTS.find(x => x.item_code && x.item_code.toLowerCase() === q.toLowerCase());
        if (localMatch) { pcSelectProductById(localMatch.id); return; }

        document.getElementById('pcStatus').textContent = 'Checking barcode...';
        fetch('<?= base_url("billing/get_by_barcode_ajax") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'barcode=' + encodeURIComponent(q)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.mode === 'single' && data.plu) {
                pcShowPlu(data.plu);
            } else if (data.success && (data.mode === 'legacy' || data.product)) {
                pcSelectProductById(data.product.id);
            } else {
                const first = PRODUCTS.find(p => p.name.toLowerCase().includes(q.toLowerCase()));
                if (first) pcSelectProductById(first.id);
                else document.getElementById('pcStatus').textContent = '✗ Not found: ' + q;
            }
        })
        .catch(() => { document.getElementById('pcStatus').textContent = '✗ Lookup error.'; });
    });
})();

function pcSelectProductById(productId) {
    const p = PRODUCTS.find(x => x.id === productId);
    if (!p) return;
    pcShowResult({
        name: p.name, code: p.item_code, mrp: p.mrp || p.selling_price, sp: p.selling_price,
        gst: p.gst_percent, schemeProductId: p.id, brand_id: p.brand_id, category_id: p.category_id,
        carton_size: p.carton_size,
    });
}

// PLU (weighing-scale) items live in a separate table from the main product catalog —
// display their price directly, and only attempt a scheme check if the PLU links back
// to a real product_id that also exists in PRODUCTS (needed for brand/category matching).
function pcShowPlu(plu) {
    const linked = PRODUCTS.find(x => x.id === plu.product_id);
    pcShowResult({
        name: plu.name, code: plu.item_code, mrp: plu.mrp, sp: plu.selling_rate, gst: plu.gst_percent,
        schemeProductId: linked ? linked.id : null,
        brand_id: linked ? linked.brand_id : 0, category_id: linked ? linked.category_id : 0,
        carton_size: linked ? linked.carton_size : 1,
    });
}

function pcShowResult(info) {
    document.getElementById('pcDropdown').style.display = 'none';
    document.getElementById('pcStatus').textContent = '';
    document.getElementById('pcResult').style.display = 'block';
    document.getElementById('pcProductName').textContent = info.name;
    document.getElementById('pcProductCode').textContent = info.code ? ('Code: ' + info.code) : '';
    document.getElementById('pcMrp').textContent = '₹ ' + parseFloat(info.mrp || info.sp || 0).toFixed(2);
    document.getElementById('pcSp').textContent = '₹ ' + parseFloat(info.sp || 0).toFixed(2);
    document.getElementById('pcSchemeBox').style.display = 'none';
    if (info.schemeProductId) pcCheckScheme(info);
}

function pcCheckScheme(info) {
    const custId = parseInt(document.getElementById('customer_id')?.value) || 0;
    const paymentMode = document.querySelector('select[name="payment_mode"]')?.value || 'cash';
    const rate = parseFloat(info.sp) || 0;
    const gst  = parseFloat(info.gst) || 0;
    const taxable = gst > 0 ? rate / (1 + gst / 100) : rate;
    const items = [{
        product_id: info.schemeProductId, qty: 1, uom: 'pcs', taxable_amt: taxable, gst_percent: gst,
        mrp: info.mrp || 0, carton_size: info.carton_size || 1, brand_id: info.brand_id || 0, category_id: info.category_id || 0,
    }];
    const params = new URLSearchParams({
        customer_id: custId, bill_total: taxable, payment_mode: paymentMode, items: JSON.stringify(items),
    });
    fetch('<?= base_url("schemes/evaluate_ajax") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: params.toString(),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const breakup = (data.schemes && data.schemes.discount_breakup) || [];
        const match = breakup.find(d => (d.product_ids || []).includes(info.schemeProductId));
        if (match && match.discount > 0) {
            const netPrice = Math.max(0, rate - match.discount);
            document.getElementById('pcSchemeName').textContent = match.scheme_name;
            document.getElementById('pcNetPrice').textContent = '₹ ' + netPrice.toFixed(2);
            document.getElementById('pcSchemeBox').style.display = 'block';
        }
    })
    .catch(() => {});
}

/* ══════════════════════════════════════════════
   HOLD BILL / RECALL BILL (F5 / F6) — park the current in-progress sale server-side
   and resume it later. Cart state round-trips as an opaque JSON payload built and
   read entirely here; the server just stores/returns it.
   ══════════════════════════════════════════════ */
function holdCurrentBill() {
    if (!validateInvoiceBeforePayment()) return;

    const items = [];
    document.querySelectorAll('#itemsBody tr[id^=row_]').forEach(function(row) {
        const rid = row.id.replace('row_', '');
        const sel = row.querySelector('select[name="product_id[]"]');
        const pid = parseInt(sel?.value) || 0;
        const qty = parseFloat(document.getElementById('qty_' + rid)?.value) || 0;
        if (!pid || qty <= 0) return;
        const optEl = sel.options[sel.selectedIndex];
        items.push({
            product_id: pid,
            product_name: optEl ? optEl.textContent.trim() : ('Product #' + pid),
            qty: qty,
            rate: parseFloat(document.getElementById('rate_' + rid)?.value) || 0,
            mrp: parseFloat(document.getElementById('mrp_' + rid)?.value) || 0,
            item_discount: parseFloat(document.getElementById('disc_' + rid)?.value) || 0,
            disc_pct: document.getElementById('disc_pct_' + rid)?.value || '',
            gst_percent: parseFloat(document.getElementById('gst_' + rid)?.value) || 0,
            plu_id: document.getElementById('plu_id_' + rid)?.value || '',
        });
    });
    if (!items.length) { alert('No items to hold.'); return; }

    const custSel = document.getElementById('customer_id');
    const custId = custSel.value;
    const customerName = custId ? custSel.options[custSel.selectedIndex].textContent.split('(')[0].trim() : 'Walk-in Customer';
    const totalVal = getInvoiceGrandTotal();

    const cartState = {
        customer_id: custId || '',
        invoice_type: document.getElementById('invoice_type').value,
        notes: document.querySelector('textarea[name="notes"]')?.value || '',
        cd_percent: document.getElementById('cd_percent')?.value || '0',
        cd_amount: document.getElementById('cd_amount')?.value || '0',
        is_delivery: !!document.getElementById('is_delivery')?.checked,
        items: items,
    };

    const body = new URLSearchParams({
        payload: JSON.stringify(cartState),
        customer_id: custId || '',
        customer_name: customerName,
        item_count: String(items.length),
        total_amount: String(totalVal),
    });

    fetch('<?= base_url("billing/hold_bill_ajax") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: body.toString(),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { alert('Could not hold bill: ' + (data.message || 'unknown error')); return; }
        if (PRINT_HOLD_SLIP) {
            printHoldSlip({
                hold_number: data.hold_number || ('H-' + data.id),
                hold_date: data.hold_date || new Date().toLocaleString('en-IN'),
                held_by: data.held_by || 'Staff',
                customer_name: customerName,
                items: items,
                total: totalVal,
            });
        }
        showBillingToast('Bill held for ' + customerName + '.', 'success');
        resetBillingScreenForHold();
        refreshHeldBillsBadge();
    })
    .catch(() => alert('Network error while holding the bill.'));
}

// Full manual reset since (unlike a normal save) there's no page redirect to do this
// for us — the cashier stays on this same screen, ready for the next sale.
// Full manual reset since (unlike a normal Save & Print, which does a real page
// redirect and so gets a clean slate for free from the server-rendered defaults)
// Hold Bill keeps the cashier on this same screen — any field left un-reset here
// leaks into whatever sale they ring up next.
function resetBillingScreen() {
    document.querySelectorAll('#itemsBody tr').forEach(r => r.remove());
    rowCount = 0;
    document.getElementById('customer_id').value = '';
    document.getElementById('customer_info').style.display = 'none';
    const lastBillBox = document.getElementById('last_bill_info');
    if (lastBillBox) lastBillBox.style.display = 'none';
    document.getElementById('cust_phone_search').value = '';
    document.getElementById('phone_search_status').textContent = '';
    document.getElementById('invoice_type').value = 'retail';
    const notesEl = document.querySelector('textarea[name="notes"]');
    if (notesEl) notesEl.value = '';
    const cdPct = document.getElementById('cd_percent'); if (cdPct) cdPct.value = '0';
    const cdAmt = document.getElementById('cd_amount'); if (cdAmt) cdAmt.value = '0';
    const delivery = document.getElementById('is_delivery'); if (delivery) delivery.checked = false;
    document.querySelector('input[name="paid_amount"]').value = '0';
    const pmPaid = document.getElementById('pmAmountPaid'); if (pmPaid) pmPaid.value = '';
    resetAmountPaidEdited();

    // Payment panel — mode back to the configured default, single (not split) mode,
    // store discount + wallet + card + credit-due fields all cleared.
    document.getElementById('splitPayBody').innerHTML = '';
    splitRowCount = 0;
    document.getElementById('singlePaySection').style.display = '';
    document.getElementById('splitPaySection').style.display = 'none';
    const storeDisc = document.getElementById('enableStoreDisc');
    if (storeDisc) { storeDisc.checked = false; if (typeof toggleStoreDisc === 'function') toggleStoreDisc(); }
    const bdVal = document.getElementById('pmBillDiscValue'); if (bdVal) bdVal.value = '';
    const bdReason = document.getElementById('pmBillDiscReason'); if (bdReason) bdReason.value = '';
    const walletUsed = document.getElementById('pmWalletUsed'); if (walletUsed) walletUsed.value = '';
    const cardType = document.getElementById('singleCardType'); if (cardType) cardType.value = '';
    const cardLast4 = document.getElementById('singleCardLast4'); if (cardLast4) cardLast4.value = '';
    const bankName = document.getElementById('singleBankName'); if (bankName) bankName.value = '';
    const cardRef = document.getElementById('singleCardRef'); if (cardRef) cardRef.value = '';
    const creditDue = document.getElementById('pmCreditDueDate'); if (creditDue) creditDue.value = '';
    const creditDueSplit = document.getElementById('pmCreditDueDateSplit'); if (creditDueSplit) creditDueSplit.value = '';
    ['wallet_amount_used', 'wallet_amount_credited'].forEach(id => { const el = document.getElementById(id); if (el) el.value = '0.00'; });
    ['hBillDiscType'].forEach(id => { const el = document.getElementById(id); if (el) el.value = 'none'; });
    ['hBillDiscPercent', 'hBillDiscAmount'].forEach(id => { const el = document.getElementById(id); if (el) el.value = '0'; });
    ['hBillDiscReason', 'hCreditDueDate'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    document.querySelectorAll('.dyn-split').forEach(el => el.remove());

    applyDefaultPaymentMode();
    addRow();
    recalcTotals();
    updateRowCount();
    document.getElementById('smartSearch')?.focus();
}
// Old name kept as an alias — resetBillingScreen() now covers everything Hold needed plus payment-panel state.
function resetBillingScreenForHold() { resetBillingScreen(); }

function refreshHeldBillsBadge() {
    fetch('<?= base_url("billing/get_held_bills_ajax") ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('heldBillsBadge');
            if (!badge) return;
            const count = (data.bills || []).length;
            if (count > 0) { badge.textContent = count; badge.style.display = 'block'; }
            else { badge.style.display = 'none'; }
        })
        .catch(() => {});
}
document.addEventListener('DOMContentLoaded', refreshHeldBillsBadge);

function printHoldSlip(info) {
    const companyName = <?= json_encode($company['company_name'] ?? 'Store') ?>;
    const companyAddr = <?= json_encode(str_replace(["\r\n","\r","\n"], ', ', trim($company['company_address'] ?? ''))) ?>;
    const companyPhone = <?= json_encode($company['company_phone'] ?? '') ?>;
    const companyGst  = <?= json_encode($company['gst_number'] ?? '') ?>;

    let itemRows = '';
    let subTotal = 0, totalTax = 0;
    (info.items || []).forEach(function(it, i) {
        const lineAmt = it.qty * it.rate;
        const disc = parseFloat(it.item_discount) || 0;
        const taxable = lineAmt - disc;
        const gst = it.gst_percent || 0;
        const tax = taxable * gst / (100 + gst);
        subTotal += taxable;
        totalTax += tax;
        const name = (it.product_name || '').replace(/\s*\(.*?\)\s*$/, '');
        itemRows += '<tr><td>'+(i+1)+'</td><td style="text-align:left;">'+name+'</td><td>'+it.qty+'</td><td>'+parseFloat(it.rate).toFixed(2)+'</td><td>'+(disc > 0 ? disc.toFixed(2) : '-')+'</td><td>'+taxable.toFixed(2)+'</td></tr>';
    });

    const html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hold Slip '+info.hold_number+'</title>'
    +'<style>*{margin:0;padding:0;box-sizing:border-box;}body{font-family:Arial,sans-serif;font-size:12px;width:80mm;margin:0 auto;padding:3mm 4mm;}'
    +'table{width:100%;border-collapse:collapse;}th,td{padding:2px 3px;font-size:11px;}th{border-bottom:1px solid #333;text-align:right;}td{text-align:right;border-bottom:1px dotted #ddd;}'
    +'.center{text-align:center;}.left{text-align:left;}.bold{font-weight:bold;}.line{border-top:1px dashed #333;margin:4px 0;}'
    +'@media print{body{width:100%;padding:0 2mm;}@page{margin:2mm;}}</style></head><body>'
    +'<div class="center bold" style="font-size:14px;">'+companyName+'</div>'
    +(companyAddr ? '<div class="center" style="font-size:10px;margin:2px 0;">'+companyAddr+'</div>' : '')
    +(companyPhone ? '<div class="center" style="font-size:10px;">Ph: '+companyPhone+'</div>' : '')
    +(companyGst ? '<div class="center" style="font-size:10px;">GSTIN: '+companyGst+'</div>' : '')
    +'<div class="line"></div>'
    +'<div class="center bold" style="font-size:13px;margin:4px 0;">--- HOLD SLIP ---</div>'
    +'<div class="line"></div>'
    +'<table style="margin:4px 0;"><tr><td class="left bold">Hold #:</td><td class="left">'+info.hold_number+'</td></tr>'
    +'<tr><td class="left bold">Date:</td><td class="left">'+info.hold_date+'</td></tr>'
    +'<tr><td class="left bold">Held By:</td><td class="left">'+info.held_by+'</td></tr>'
    +'<tr><td class="left bold">Customer:</td><td class="left">'+info.customer_name+'</td></tr></table>'
    +'<div class="line"></div>'
    +'<table><thead><tr><th class="left">#</th><th class="left">Item</th><th>Qty</th><th>Rate</th><th>Disc</th><th>Amt</th></tr></thead>'
    +'<tbody>'+itemRows+'</tbody></table>'
    +'<div class="line"></div>'
    +'<table><tr><td class="left bold" style="font-size:13px;">Grand Total:</td><td class="bold" style="font-size:13px;">Rs. '+parseFloat(info.total).toFixed(2)+'</td></tr></table>'
    +'<div class="line"></div>'
    +'<div class="center" style="font-size:10px;margin:6px 0;">Items: '+(info.items||[]).length+' | This is a HOLD slip, not an invoice.</div>'
    +'<div class="center" style="font-size:9px;color:#888;">Present this slip to recall your order.</div>'
    +'</body></html>';

    const printWin = window.open('', '_blank', 'width=350,height=600');
    if (!printWin) { alert('Pop-up blocked. Please allow pop-ups for this site.'); return; }
    printWin.document.write(html);
    printWin.document.close();
    printWin.focus();
    setTimeout(function() { printWin.print(); printWin.close(); }, 400);
}

function openRecallBillModal() {
    $('#recallBillModal').modal('show');
    const body = document.getElementById('recallBillListBody');
    body.innerHTML = '<div class="text-center text-muted py-3">Loading...</div>';
    fetch('<?= base_url("billing/get_held_bills_ajax") ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            const bills = data.bills || [];
            if (!bills.length) {
                body.innerHTML = '<div class="text-center text-muted py-3">No held bills.</div>';
                return;
            }
            body.innerHTML = bills.map(b => `
                <a href="#" class="list-group-item list-group-item-action" onclick="recallHeldBill(${b.id}); return false;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${b.customer_name || 'Walk-in Customer'}</strong>
                            <div class="text-muted small">${b.item_count} item(s) &middot; ${b.created_at}</div>
                        </div>
                        <div class="font-weight-bold text-success">₹ ${parseFloat(b.total_amount).toFixed(2)}</div>
                    </div>
                </a>`).join('');
        })
        .catch(() => { body.innerHTML = '<div class="text-center text-danger py-3">Could not load held bills.</div>'; });
}

function recallHeldBill(id) {
    const hasCurrentItems = Array.from(document.querySelectorAll('#itemsBody tr[id^=row_]'))
        .some(row => row.querySelector('select[name="product_id[]"]')?.value);
    if (hasCurrentItems && !confirm('This will replace the items currently on screen (they have not been saved or held). Continue?')) {
        return;
    }

    fetch('<?= base_url("billing/recall_bill_ajax/") ?>' + encodeURIComponent(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert('Could not recall bill: ' + (data.message || 'not found')); return; }
            const cartState = JSON.parse(data.payload);
            applyRecalledCart(cartState);
            $('#recallBillModal').modal('hide');
            showBillingToast('Bill recalled.', 'success');
            refreshHeldBillsBadge();
        })
        .catch(() => alert('Network error while recalling the bill.'));
}

function applyRecalledCart(cartState) {
    document.querySelectorAll('#itemsBody tr').forEach(r => r.remove());
    rowCount = 0;

    // Inject any held products missing from the capped product list into the
    // template BEFORE cloning rows — guptabros loads only 500 of 56k products,
    // so a held bill's items may not have an <option> in the dropdown.
    (cartState.items || []).forEach(function(item) {
        if (!item.product_id) return;
        var existing = _productSelectTemplate.querySelector('option[value="' + item.product_id + '"]');
        if (!existing) {
            var opt = document.createElement('option');
            opt.value = item.product_id;
            opt.textContent = (item.product_name || ('Product #' + item.product_id)) + ' (held)';
            opt.dataset.price = item.rate || 0;
            opt.dataset.mrp   = item.mrp || 0;
            opt.dataset.gst   = item.gst_percent || 0;
            opt.dataset.unit  = '';
            opt.dataset.stock = 0;
            opt.dataset.code  = '';
            opt.dataset.image = '';
            opt.dataset.noDiscount = '0';
            opt.dataset.nameEdit  = '0';
            _productSelectTemplate.appendChild(opt);
        }
    });

    (cartState.items || []).forEach(function(item) {
        addRow();
        const rid = rowCount;
        const sel = document.querySelector('#row_' + rid + ' select[name="product_id[]"]');
        if (!sel) return;
        sel.value = item.product_id;
        fillProduct(sel, rid);
        // fillProduct() sets defaults from the product's CURRENT master data — override
        // with the values the bill actually had when it was held.
        const qtyEl = document.getElementById('qty_' + rid); if (qtyEl) qtyEl.value = item.qty;
        const rateEl = document.getElementById('rate_' + rid); if (rateEl) rateEl.value = item.rate;
        const discEl = document.getElementById('disc_' + rid); if (discEl) discEl.value = item.item_discount || 0;
        const discPctEl = document.getElementById('disc_pct_' + rid); if (discPctEl && item.disc_pct) discPctEl.value = item.disc_pct;
        calcRow(rid);
    });
    if (!(cartState.items || []).length) addRow();

    document.getElementById('invoice_type').value = cartState.invoice_type || 'retail';
    const notesEl = document.querySelector('textarea[name="notes"]');
    if (notesEl) notesEl.value = cartState.notes || '';
    const cdPct = document.getElementById('cd_percent'); if (cdPct) cdPct.value = cartState.cd_percent || '0';
    const cdAmt = document.getElementById('cd_amount'); if (cdAmt) cdAmt.value = cartState.cd_amount || '0';
    const delivery = document.getElementById('is_delivery'); if (delivery) delivery.checked = !!cartState.is_delivery;

    if (cartState.customer_id) {
        document.getElementById('customer_id').value = cartState.customer_id;
        loadCustomer(cartState.customer_id);
    }

    updateRowCount();
    recalcTotals();
}

function openReprintModal() {
    $('#reprintModal').modal('show');
    document.getElementById('reprintDate').value = new Date().toISOString().split('T')[0];
    fetchBillsForReprint();
    setTimeout(() => document.getElementById('reprintBillNo').focus(), 300);
}

function fetchBillsForReprint() {
    const date = document.getElementById('reprintDate').value;
    const tbody = document.getElementById('reprintBillsTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3">Loading bills...</td></tr>';
    
    fetch('<?= base_url("billing/get_recent_bills_ajax") ?>?date=' + date, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            tbody.innerHTML = '';
            if (data.bills.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">No bills found for this date.</td></tr>';
                return;
            }
            data.bills.forEach(bill => {
                const tr = document.createElement('tr');
                tr.style.cursor = 'pointer';
                tr.onclick = function(e) {
                    if(e.target.tagName !== 'BUTTON' && e.target.tagName !== 'I') {
                        reprintBillById(bill.id);
                    }
                };
                tr.innerHTML = `
                    <td class="font-weight-bold">${bill.bill_no}</td>
                    <td>${bill.bill_date.substring(0, 10)}</td>
                    <td>${bill.customer_name || 'Walk-in Customer'}</td>
                    <td class="text-center">${bill.total_qty || 0}</td>
                    <td class="text-right font-weight-bold">₹ ${parseFloat(bill.grand_total).toFixed(2)}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-outline-primary" onclick="reprintBillById(${bill.id}); event.stopPropagation();"><i class="fas fa-print"></i> Print</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    })
    .catch(err => {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-danger">Failed to load bills.</td></tr>';
    });
}

function printSpecificBill() {
    const billNo = document.getElementById('reprintBillNo').value.trim();
    if (!billNo) {
        alert('Please enter a bill number');
        return;
    }
    const tbody = document.getElementById('reprintBillsTableBody');
    const rows = tbody.querySelectorAll('tr');
    let foundId = null;
    rows.forEach(row => {
        const noCell = row.cells[0];
        if (noCell && noCell.innerText.trim() === billNo) {
            const btn = row.querySelector('button');
            if (btn) {
                const match = btn.getAttribute('onclick').match(/reprintBillById\((\d+)\)/);
                if (match) foundId = match[1];
            }
        }
    });
    
    if (foundId) {
        reprintBillById(foundId);
    } else {
        alert('Bill number not found for the selected date. Please check the date or bill number.');
    }
}

function reprintBillById(id) {
    const printUrl = '<?= base_url("billing/print_invoice/") ?>' + id;
    const printWindow = window.open(printUrl, '_blank', 'width=400,height=600');
    if (printWindow) {
        printWindow.focus();
        $('#reprintModal').modal('hide');
    } else {
        alert('Please allow popups to print bills.');
    }
}

<?php if ($this->session->flashdata('auto_print_id')): ?>
window.addEventListener('load', function() {
    setTimeout(function() {
        var printIframe = document.createElement('iframe');
        printIframe.style.display = 'none';
        printIframe.src = '<?= base_url("billing/print_invoice/" . $this->session->flashdata('auto_print_id')) ?>';
        document.body.appendChild(printIframe);
        
        printIframe.onload = function() {
            setTimeout(function() {
                printIframe.contentWindow.focus();
                printIframe.contentWindow.print();
                
                // Restore focus to the main window and customer search box
                window.focus();
                var custSearch = document.getElementById('cust_phone_search');
                if (custSearch) {
                    custSearch.focus();
                }
                
                // Remove iframe after print dialog resolves/times out
                setTimeout(function() {
                    if (document.body.contains(printIframe)) {
                        document.body.removeChild(printIframe);
                    }
                }, 5000);
            }, 500);
        };
    }, 500);
});
<?php endif; ?>

<?php if ($this->session->flashdata('print_confirm_id')): ?>
<div class="modal fade" id="printConfirmModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#2e7d32;">
                <h6 class="modal-title text-white mb-0"><i class="fas fa-print mr-1"></i> Print Bill?</h6>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-check-circle text-success mb-2" style="font-size:2.5rem;"></i>
                <p class="mb-0 font-weight-bold">Bill saved successfully!</p>
                <p class="text-muted small mb-0">Do you want to print the bill?</p>
            </div>
            <div class="modal-footer justify-content-center py-2">
                <button type="button" class="btn btn-success px-4" id="btnPrintYes"><i class="fas fa-print mr-1"></i> Yes, Print</button>
                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal"><i class="fas fa-times mr-1"></i> No</button>
            </div>
        </div>
    </div>
</div>
<script>
window.addEventListener('DOMContentLoaded', function(){
    var printUrl = '<?= base_url("billing/print_invoice/" . $this->session->flashdata('print_confirm_id')) ?>';
    var modal = document.getElementById('printConfirmModal');

    function autoPrintViaIframe() {
        var iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = printUrl;
        document.body.appendChild(iframe);
        iframe.onload = function() {
            setTimeout(function() {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                window.focus();
                var cs = document.getElementById('cust_phone_search');
                if (cs) { cs.focus(); }
                setTimeout(function() {
                    if (document.body.contains(iframe)) document.body.removeChild(iframe);
                }, 5000);
            }, 500);
        };
    }

    function closeModal() {
        if (typeof $ !== 'undefined' && modal) $(modal).modal('hide');
        else if (modal) modal.style.display = 'none';
    }

    if (modal && typeof $ !== 'undefined') {
        $(modal).modal('show');
        document.getElementById('btnPrintYes').addEventListener('click', function(){
            closeModal();
            autoPrintViaIframe();
        });
        $(modal).on('hidden.bs.modal', function(){
            var cs = document.getElementById('cust_phone_search');
            if (cs) cs.focus();
        });
    } else if (modal) {
        modal.classList.add('show');
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        document.getElementById('btnPrintYes').addEventListener('click', function(){
            closeModal();
            autoPrintViaIframe();
        });
    }
});
</script>
<?php endif; ?>
</script>

<!-- EV Scooter Dealership Allocation Modal & Integration -->
<?php $this->load->view('billing/_ev_modal'); ?>

<script>
window.onEvScooterAllocated = function(payload, targetRowId) {
    if (!payload || !payload.productId) return;

    let rid = targetRowId;
    if (!rid) {
        rid = findEmptyRow();
        if (rid === null) {
            addRow();
            rid = rowCount;
        }
    }

    const sel = document.querySelector(`#row_${rid} select[name="product_id[]"]`);
    if (sel) {
        sel.value = payload.productId;
        fillProduct(sel, rid);
    }

    // Lock quantity to 1 for unique serialized vehicle line
    const qtyEl = document.getElementById('qty_' + rid);
    if (qtyEl) {
        qtyEl.value = 1;
        qtyEl.readOnly = true;
    }

    const mrpEl = document.getElementById('mrp_' + rid);
    if (mrpEl && payload.mrp) {
        mrpEl.value = parseFloat(payload.mrp).toFixed(2);
    }

    const rateEl = document.getElementById('rate_' + rid);
    if (rateEl && payload.rate) {
        rateEl.value = parseFloat(payload.rate).toFixed(2);
    }

    const gstEl = document.getElementById('gst_' + rid);
    if (gstEl && typeof payload.gstPercent !== 'undefined') {
        gstEl.value = payload.gstPercent;
    }

    // Set EV dealership hidden form fields
    if (document.getElementById('chassis_no_' + rid)) {
        document.getElementById('chassis_no_' + rid).value = payload.chassisNo || '';
    }
    if (document.getElementById('motor_no_' + rid)) {
        document.getElementById('motor_no_' + rid).value = payload.motorNo || '';
    }
    if (document.getElementById('item_colour_' + rid)) {
        document.getElementById('item_colour_' + rid).value = payload.colour || '';
    }
    if (document.getElementById('battery_serial_' + rid)) {
        document.getElementById('battery_serial_' + rid).value = payload.batterySerial || '';
    }
    if (document.getElementById('charger_serial_' + rid)) {
        document.getElementById('charger_serial_' + rid).value = payload.chargerSerial || '';
    }
    if (document.getElementById('warranty_terms_' + rid)) {
        document.getElementById('warranty_terms_' + rid).value = payload.warrantyTerms || '';
    }
    if (document.getElementById('serial_json_' + rid)) {
        document.getElementById('serial_json_' + rid).value = JSON.stringify(payload.serials || [payload.chassisNo]);
    }

    // Update Visual Badge and Vehicle Details
    const evBadge = document.getElementById('ev_badge_' + rid);
    if (evBadge) {
        evBadge.innerHTML = '<i class="fas fa-motorcycle mr-1"></i> EV Scooter';
        evBadge.style.display = 'inline-block';
    }

    const evDetail = document.getElementById('ev_detail_' + rid);
    if (evDetail) {
        let detHtml = '<div class="alert alert-light border p-1 mb-0 mt-1" style="font-size:0.75rem; background:#f0fdf4; border-color:#86efac !important;">';
        detHtml += '<div><strong class="text-success"><i class="fas fa-id-card mr-1"></i>VIN:</strong> <span class="badge badge-dark font-weight-bold" style="letter-spacing:0.04em;">' + (payload.chassisNo || 'N/A') + '</span>';
        if (payload.motorNo) detHtml += ' &bull; <strong>Motor:</strong> ' + payload.motorNo;
        if (payload.colour) detHtml += ' &bull; <strong>Col:</strong> ' + payload.colour;
        detHtml += '</div>';

        if (payload.batterySerial || payload.chargerSerial) {
            detHtml += '<div class="mt-1 text-muted">';
            if (payload.batterySerial) detHtml += '<span class="mr-2"><i class="fas fa-car-battery text-info mr-1"></i>Batt S/N: <strong class="text-dark">' + payload.batterySerial + '</strong></span>';
            if (payload.chargerSerial) detHtml += '<span><i class="fas fa-charging-station text-warning mr-1"></i>Chg S/N: <strong class="text-dark">' + payload.chargerSerial + '</strong></span>';
            detHtml += '</div>';
        }
        if (payload.warrantyTerms) {
            detHtml += '<div class="mt-1 text-secondary" style="font-size:0.7rem;"><i class="fas fa-shield-alt text-success mr-1"></i>' + payload.warrantyTerms + '</div>';
        }
        detHtml += '</div>';

        evDetail.innerHTML = detHtml;
        evDetail.style.display = 'block';
    }

    calcRow(rid);
    updateRowCount();
    scrollToRowAndHighlight(rid);

    const barcodeStatus = document.getElementById('barcodeStatus');
    if (barcodeStatus) {
        barcodeStatus.innerHTML = '✓ EV Scooter Allocated: <strong>' + (payload.chassisNo || '') + '</strong> (' + (payload.productName || '') + ')';
        barcodeStatus.className = 'form-text text-success font-weight-bold';
    }
};
</script>
