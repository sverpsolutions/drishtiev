<?php // FILE: application/views/reports/index.php ?>
<div class="mb-4">
    <h5 class="font-weight-bold mb-0">Reports</h5>
    <small class="text-muted">Business intelligence for <?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?></small>
</div>

<style>
.exec-summary-banner, .exec-summary-banner:hover { text-decoration: none !important; display: block; }
.exec-summary-banner .exec-summary-card {
    background: #1B3A6B !important;
    background: linear-gradient(135deg,#1B3A6B,#2c5aa0) !important;
    border: 0 !important;
    border-radius: .5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.15) !important;
}
.exec-summary-banner .exec-summary-title,
.exec-summary-banner .exec-summary-desc { color: #ffffff !important; }
.exec-summary-banner .exec-summary-desc { opacity: .85; }
</style>
<div class="row mb-4">
    <div class="col-md-6 mb-3 mb-md-0">
        <a href="<?= base_url('dashboard') ?>" class="exec-summary-banner">
            <div class="exec-summary-card" style="background: linear-gradient(135deg,#0d6efd,#3d8bfd) !important;">
                <div class="py-3 px-4 d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <div class="exec-summary-title font-weight-bold" style="font-size:1.15rem;"><i class="fas fa-tachometer-alt mr-2"></i>Dashboard</div>
                        <div class="exec-summary-desc" style="font-size:.85rem;">Today's sales, monthly sales, stock alerts, daily &amp; monthly charts — your home screen at a glance.</div>
                    </div>
                    <span class="btn btn-light btn-sm mt-2 mt-md-0">Open &rarr;</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="<?= base_url('reports/executive_summary') ?>" class="exec-summary-banner">
            <div class="exec-summary-card">
                <div class="py-3 px-4 d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <div class="exec-summary-title font-weight-bold" style="font-size:1.15rem;"><i class="fas fa-chart-line mr-2"></i>Executive Summary</div>
                        <div class="exec-summary-desc" style="font-size:.85rem;">Sales, purchases, stock, gross profit, branch performance &amp; alerts with month-over-month comparison.</div>
                    </div>
                    <span class="btn btn-light btn-sm mt-2 mt-md-0">Open &rarr;</span>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="mb-3">
    <h6 class="font-weight-bold text-uppercase text-secondary" style="letter-spacing:1px; font-size:0.75rem;">Standard Financial Reports</h6>
</div>

<div class="row mb-4">
    <?php
    $report_cards = [
        ['url'=>'reports/sales',        'icon'=>'📈', 'title'=>'Sales Report',      'desc'=>'Invoices, GST collected, payments',     'color'=>'success'],
        ['url'=>'reports/purchases',     'icon'=>'🛒', 'title'=>'Purchase Report',   'desc'=>'All purchases, GST input, dues',        'color'=>'primary'],
        ['url'=>'reports/stock',         'icon'=>'📦', 'title'=>'Stock Report',      'desc'=>'Current inventory valuation',           'color'=>'secondary'],
        ['url'=>'reports/profit_loss',   'icon'=>'💰', 'title'=>'Profit & Loss',     'desc'=>'Revenue, COGS, gross margin',           'color'=>'dark'],
        ['url'=>'reports/gst',           'icon'=>'🧾', 'title'=>'GST Report',        'desc'=>'GSTR-1 summary: B2B / B2C',            'color'=>'warning'],
        ['url'=>'reports/outstanding',      'icon'=>'⚠️', 'title'=>'Outstanding Dues',    'desc'=>'Customer & supplier pending amounts',    'color'=>'danger'],
        ['url'=>'reports/credit_sales',     'icon'=>'📋', 'title'=>'Credit Sales',        'desc'=>'Pending credit bills & recovery status',  'color'=>'danger'],
        ['url'=>'reports/refunds_report',   'icon'=>'↩️',  'title'=>'Refunds & Returns',   'desc'=>'All refunds, returns & stock restorations', 'color'=>'warning'],
        ['url'=>'reports/location_stock',   'icon'=>'🏪', 'title'=>'Location Stock',       'desc'=>'Per-location inventory breakdown',          'color'=>'info'],
        ['url'=>'reports/stock_location_grid', 'icon'=>'🔳', 'title'=>'Stock by Location (Grid)', 'desc'=>'Compare qty/CP/MRP/SP across all locations, side by side', 'color'=>'secondary'],
        ['url'=>'reports/stock_movement',     'icon'=>'🔄', 'title'=>'Stock Movement',          'desc'=>'All stock in/out movements with balance',        'color'=>'secondary'],
        ['url'=>'Reports/negative_stock_audit', 'icon'=>'⚠️', 'title'=>'Negative Stock (Audit)',  'desc'=>'Real balances below zero (screens show 0)',       'color'=>'danger'],
        ['url'=>'reports/transfers_report',   'icon'=>'🚚', 'title'=>'Transfer Report',         'desc'=>'Inter-location transfer history & status',       'color'=>'primary'],
        ['url'=>'reports/sales_requisition_report', 'icon'=>'📊', 'title'=>'Sales Requisition',  'desc'=>'Prev-day sales vs suggested vs final transfer qty', 'color'=>'info'],
        ['url'=>'reports/wallet_ledger',        'icon'=>'👛', 'title'=>'Wallet Ledger',           'desc'=>'Customer wallet credit/debit statement',          'color'=>'info'],
        ['url'=>'reports/portal_order_funnel',  'icon'=>'🔻', 'title'=>'Portal Order Funnel',     'desc'=>'B2B/B2C order lifecycle & conversion rate',       'color'=>'success'],
        ['url'=>'reports/po_grn_variance',      'icon'=>'📐', 'title'=>'PO vs GRN Variance',      'desc'=>'Ordered vs received/rejected qty by PO line',     'color'=>'warning'],
    ];
    foreach ($report_cards as $rc):
    ?>
    <div class="col-md-4 mb-3">
        <a href="<?= base_url($rc['url']) ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-start gap-3">
                    <span style="font-size:2rem;line-height:1;"><?= $rc['icon'] ?></span>
                    <div>
                        <p class="font-weight-bold mb-1 text-dark"><?= $rc['title'] ?></p>
                        <small class="text-muted"><?= $rc['desc'] ?></small>
                    </div>
                    <span class="ml-auto text-<?= $rc['color'] ?>" style="font-size:1.2rem;">›</span>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
    <div class="col-md-4 mb-3">
        <a href="<?= base_url('reports/fast_slow_report') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-start gap-3">
                    <span style="font-size:2rem;line-height:1;">&#9889;</span>
                    <div>
                        <p class="font-weight-bold mb-1 text-dark">Fast &amp; Slow Movement</p>
                        <small class="text-muted">Fast-moving, slow-moving &amp; dead stock analysis</small>
                    </div>
                    <span class="ml-auto text-warning" style="font-size:1.2rem;">&#8250;</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="<?= base_url('reports/inventory_analytics') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-start gap-3">
                    <span style="font-size:2rem;line-height:1;">&#128202;</span>
                    <div>
                        <p class="font-weight-bold mb-1 text-dark">Inventory Analytics</p>
                        <small class="text-muted">Category, brand &amp; supplier stock insights</small>
                    </div>
                    <span class="ml-auto text-info" style="font-size:1.2rem;">&#8250;</span>
                </div>
            </div>
        </a>
    </div>
</div>

<hr class="my-4" style="border-top: 1px solid #eef2f3;">

<div class="mb-3">
    <h6 class="font-weight-bold text-uppercase text-secondary" style="letter-spacing:1px; font-size:0.75rem;">Daily Reports</h6>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <a href="<?= base_url('reports/closing_stock_summary') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-start gap-3">
                    <span style="font-size:2rem;line-height:1;">📅</span>
                    <div>
                        <p class="font-weight-bold mb-1 text-dark">Closing Stock Summary</p>
                        <small class="text-muted">Date-wise stock ledger summary & valuation</small>
                    </div>
                    <span class="ml-auto text-primary" style="font-size:1.2rem;">›</span>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-md-4 mb-3">
        <a href="<?= base_url('reports/margin_report') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-start gap-3">
                    <span style="font-size:2rem;line-height:1;">📈</span>
                    <div>
                        <p class="font-weight-bold mb-1 text-dark">All Item Margin Report</p>
                        <small class="text-muted">Basic cost, CP, MRP, SP & margin for every item</small>
                    </div>
                    <span class="ml-auto text-primary" style="font-size:1.2rem;">›</span>
                </div>
            </div>
        </a>
    </div>

    <?php if (in_array($this->session->userdata('role'), ['admin', 'superadmin'])): ?>
    <div class="col-md-4 mb-3">
        <a href="<?= base_url('stock_snapshots/generate') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-start gap-3">
                    <span style="font-size:2rem;line-height:1;">⚡</span>
                    <div>
                        <p class="font-weight-bold mb-1 text-dark">Generate Stock Snapshot</p>
                        <small class="text-muted">High-performance utility to save month-end opening stock</small>
                    </div>
                    <span class="ml-auto text-primary" style="font-size:1.2rem;">›</span>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
</div>

<hr class="my-4" style="border-top: 1px solid #eef2f3;">

<div class="mb-3">
    <h6 class="font-weight-bold text-uppercase text-secondary" style="letter-spacing:1px; font-size:0.75rem;">POS Operations Reports</h6>
</div>

<div class="row mb-4">
    <?php
    $pos_cards = [
        ['url'=>'reports/media_report',         'icon'=>'💳', 'title'=>'Store-wise Media Report',      'desc'=>'Cash/UPI/Card breakdown per store location',        'color'=>'success'],
        ['url'=>'reports/counter_media_report',  'icon'=>'🏧', 'title'=>'Counter-wise Media Report',    'desc'=>'Payment media summary per cashier/counter',         'color'=>'info'],
        ['url'=>'reports/day_end_metrics',       'icon'=>'📊', 'title'=>'Day End Metrics Dashboard',    'desc'=>'Sales KPIs, basket value, payment modes for a day', 'color'=>'primary'],
        ['url'=>'reports/counter_summary',       'icon'=>'🖨️', 'title'=>'Counter & Bill-wise Summary',  'desc'=>'Cashier performance, shift details, bill list',     'color'=>'dark'],
    ];
    foreach ($pos_cards as $rc):
    ?>
    <div class="col-md-4 mb-3">
        <a href="<?= base_url($rc['url']) ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-start gap-3">
                    <span style="font-size:2rem;line-height:1;"><?= $rc['icon'] ?></span>
                    <div>
                        <p class="font-weight-bold mb-1 text-dark"><?= $rc['title'] ?></p>
                        <small class="text-muted"><?= $rc['desc'] ?></small>
                    </div>
                    <span class="ml-auto text-<?= $rc['color'] ?>" style="font-size:1.2rem;">›</span>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<hr class="my-4" style="border-top: 1px solid #eef2f3;">

<div class="mb-3">
    <h6 class="font-weight-bold text-uppercase text-secondary" style="letter-spacing:1px; font-size:0.75rem;">Operations & Shift Control Reports</h6>
</div>

<div class="row">
    <?php
    $operation_cards = [
        ['url'=>'reports/run_report/day_open',            'icon'=>'☀️', 'title'=>'Day Open Report',        'desc'=>'History of opened business days',        'color'=>'warning'],
        ['url'=>'reports/run_report/day_close',           'icon'=>'🌙', 'title'=>'Day Close Report',       'desc'=>'Reconciliation of closed business days', 'color'=>'dark'],
        ['url'=>'reports/run_report/shift_open',          'icon'=>'🔑', 'title'=>'Shift Open Report',      'desc'=>'Cashier shift start status & floats',    'color'=>'success'],
        ['url'=>'reports/run_report/shift_close',         'icon'=>'🔒', 'title'=>'Shift Close Report',     'desc'=>'Shift closing logs & actual entries',    'color'=>'danger'],
        ['url'=>'reports/run_report/cashier_collection',   'icon'=>'💵', 'title'=>'Cashier Collection',     'desc'=>'Total cash, card, & UPI by cashier',     'color'=>'primary'],
        ['url'=>'reports/run_report/short_excess',        'icon'=>'⚖️', 'title'=>'Short/Excess Report',     'desc'=>'Detailed cashier variance & diff logs', 'color'=>'danger'],
        ['url'=>'reports/run_report/payment_mode_summary', 'icon'=>'💳', 'title'=>'Payment Mode Summary',   'desc'=>'Breakdown of sales by cash, card, UPI',  'color'=>'info'],
        ['url'=>'reports/run_report/user_activity_log',   'icon'=>'🕵️‍♂️', 'title'=>'User Activity Log',      'desc'=>'System audit trail and security logs',   'color'=>'secondary'],
        ['url'=>'reports/run_report/billing_summary',      'icon'=>'🧾', 'title'=>'Billing Summary',        'desc'=>'Overview of all active invoice totals',  'color'=>'warning'],
        ['url'=>'reports/run_report/collection_summary',   'icon'=>'📈', 'title'=>'Collection Summary',     'desc'=>'Daily financial collections over time',  'color'=>'success'],
        ['url'=>'reports/run_report/cash_register',        'icon'=>'🏧', 'title'=>'Cash Register Flow',     'desc'=>'Detailed cashier cash flow register',    'color'=>'primary'],
        ['url'=>'reports/run_report/wallet_transactions',  'icon'=>'👛', 'title'=>'Wallet Transactions',    'desc'=>'Wallet credits, debits and adjustments', 'color'=>'info'],
        ['url'=>'reports/stock_take_variance',    'icon'=>'⚖️', 'title'=>'Stock-Take Variance',    'desc'=>'Shortage/surplus value across all stock-takes', 'color'=>'danger'],
        ['url'=>'reports/run_report/discount_report',      'icon'=>'🏷️', 'title'=>'Discount Report',        'desc'=>'All line-item discounts given, location-wise', 'color'=>'warning'],
        ['url'=>'reports/run_report/price_override_report', 'icon'=>'💲', 'title'=>'Price Override Report',  'desc'=>'Items billed at a rate different from selling price', 'color'=>'danger'],
    ];
    foreach ($operation_cards as $oc):
    ?>
    <div class="col-md-4 mb-3">
        <a href="<?= base_url($oc['url']) ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-start gap-3">
                    <span style="font-size:2rem;line-height:1;"><?= $oc['icon'] ?></span>
                    <div>
                        <p class="font-weight-bold mb-1 text-dark"><?= $oc['title'] ?></p>
                        <small class="text-muted"><?= $oc['desc'] ?></small>
                    </div>
                    <span class="ml-auto text-<?= $oc['color'] ?>" style="font-size:1.2rem;">›</span>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<hr class="my-4" style="border-top: 1px solid #eef2f3;">

<div class="mb-3">
    <h6 class="font-weight-bold text-uppercase text-secondary" style="letter-spacing:1px; font-size:0.75rem;">Outlet / Location Reports</h6>
</div>

<div class="row mb-4">
    <?php
    $outlet_cards = [
        ['url'=>'reports/outlet_sales',    'icon'=>'🏪', 'title'=>'Outlet-wise Sales',     'desc'=>'Compare sales across all outlets side-by-side', 'color'=>'success'],
        ['url'=>'reports/location_stock',  'icon'=>'📦', 'title'=>'Location Stock',         'desc'=>'Per-location inventory breakdown',               'color'=>'info'],
        ['url'=>'reports/transfers_report','icon'=>'🚚', 'title'=>'Transfer Report',        'desc'=>'Inter-location transfers history',               'color'=>'primary'],
        ['url'=>'reports/sales_requisition_report','icon'=>'📊', 'title'=>'Sales Requisition', 'desc'=>'Prev-day sales vs suggested vs final qty',       'color'=>'info'],
    ];
    foreach ($outlet_cards as $rc):
    ?>
    <div class="col-md-4 mb-3">
        <a href="<?= base_url($rc['url']) ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-start gap-3">
                    <span style="font-size:2rem;line-height:1;"><?= $rc['icon'] ?></span>
                    <div>
                        <p class="font-weight-bold mb-1 text-dark"><?= $rc['title'] ?></p>
                        <small class="text-muted"><?= $rc['desc'] ?></small>
                    </div>
                    <span class="ml-auto text-<?= $rc['color'] ?>" style="font-size:1.2rem;">›</span>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<hr class="my-4" style="border-top: 1px solid #eef2f3;">

<div class="mb-3">
    <h6 class="font-weight-bold text-uppercase text-secondary" style="letter-spacing:1px; font-size:0.75rem;">Audit & Compliance</h6>
</div>

<div class="row mb-4">
    <?php
    $audit_cards = [
        ['url'=>'reports/login_dashboard',              'icon'=>'🔐', 'title'=>'Login Dashboard',         'desc'=>'User logins, device access & session history', 'color'=>'primary'],
        ['url'=>'reports/audit_log',                    'icon'=>'🕵️', 'title'=>'Audit Log (Full)',       'desc'=>'Complete system activity trail with filters',  'color'=>'dark'],
        ['url'=>'reports/run_report/user_activity_log', 'icon'=>'👤', 'title'=>'User Activity Log',       'desc'=>'Per-user action history',                     'color'=>'secondary'],
        ['url'=>'reports/gstr1',                        'icon'=>'📋', 'title'=>'GSTR-1',                  'desc'=>'Output tax — B2B, B2C, HSN summary',          'color'=>'warning'],
        ['url'=>'reports/gstr2',                        'icon'=>'📋', 'title'=>'GSTR-2',                  'desc'=>'Input tax credit from purchases',              'color'=>'info'],
        ['url'=>'reports/gstr3b',                       'icon'=>'📋', 'title'=>'GSTR-3B',                 'desc'=>'Net GST payable summary',                     'color'=>'danger'],
        ['url'=>'reports/tax_breakup',                  'icon'=>'🧾', 'title'=>'Tax Breakup Report',      'desc'=>'Date-wise sales split by GST slab (0/5/18/40%)', 'color'=>'primary'],
    ];
    foreach ($audit_cards as $rc):
    ?>
    <div class="col-md-4 mb-3">
        <a href="<?= base_url($rc['url']) ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-start gap-3">
                    <span style="font-size:2rem;line-height:1;"><?= $rc['icon'] ?></span>
                    <div>
                        <p class="font-weight-bold mb-1 text-dark"><?= $rc['title'] ?></p>
                        <small class="text-muted"><?= $rc['desc'] ?></small>
                    </div>
                    <span class="ml-auto text-<?= $rc['color'] ?>" style="font-size:1.2rem;">›</span>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<style>
    .card-hover:hover { box-shadow: 0 6px 18px rgba(0,0,0,.08) !important; transform: translateY(-2px); transition: all .2s ease-in-out; }
    .card-hover { transition: all .2s ease-in-out; border-radius: 10px; }
    .gap-3 { gap: 1rem; }
</style>
