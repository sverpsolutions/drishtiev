<?php // FILE: application/views/reports/fmcg_suite.php ?>
<style>
/* ── Sidebar & Layout Styles ── */
.fmcg-layout {
    display: flex;
    gap: 1.5rem;
    margin-top: 1rem;
}
.fmcg-sidebar {
    width: 280px;
    flex-shrink: 0;
    position: sticky;
    top: 20px;
    height: calc(100vh - 120px);
    overflow-y: auto;
}
.fmcg-main {
    flex-grow: 1;
}

/* ── Premium Sidebar Navigation ── */
.fmcg-nav-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    color: #4b5563;
    text-decoration: none;
    font-weight: 500;
    margin-bottom: 0.4rem;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.fmcg-nav-item:hover {
    background: #f3f4f6;
    color: #1f2937;
    text-decoration: none;
    transform: translateX(3px);
}
.fmcg-nav-item.active {
    background: #e0f2fe;
    color: #0369a1;
    font-weight: 600;
    border-color: #bae6fd;
}
.fmcg-nav-item i {
    width: 20px;
    margin-right: 10px;
    font-size: 1.1rem;
}
.fmcg-badge-count {
    margin-left: auto;
    font-size: 0.75rem;
    background: #e5e7eb;
    color: #4b5563;
    padding: 2px 7px;
    border-radius: 9999px;
    font-weight: 600;
}
.fmcg-nav-item.active .fmcg-badge-count {
    background: #bae6fd;
    color: #0369a1;
}

/* ── Cards Grid ── */
.fmcg-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.25rem;
}
.fmcg-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}
.fmcg-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}
.fmcg-card-sales::before { background: #10b981; }
.fmcg-card-customer::before { background: #06b6d4; }
.fmcg-card-purchase::before { background: #3b82f6; }
.fmcg-card-inventory::before { background: #f59e0b; }
.fmcg-card-analytics::before { background: #8b5cf6; }
.fmcg-card-gst::before { background: #ef4444; }

.fmcg-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    border-color: #cbd5e1;
}
.fmcg-card-icon {
    font-size: 1.25rem;
    margin-right: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 8px;
}
.fmcg-card-sales .fmcg-card-icon { background: #ecfdf5; color: #10b981; }
.fmcg-card-customer .fmcg-card-icon { background: #ecfeff; color: #06b6d4; }
.fmcg-card-purchase .fmcg-card-icon { background: #eff6ff; color: #3b82f6; }
.fmcg-card-inventory .fmcg-card-icon { background: #fffbeb; color: #f59e0b; }
.fmcg-card-analytics .fmcg-card-icon { background: #f5f3ff; color: #8b5cf6; }
.fmcg-card-gst .fmcg-card-icon { background: #fef2f2; color: #ef4444; }

.fmcg-card-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.35rem;
}
.fmcg-card-desc {
    font-size: 0.82rem;
    color: #6b7280;
    line-height: 1.4;
    margin-bottom: 1.25rem;
    flex-grow: 1;
}
.fmcg-card-actions {
    display: flex;
    gap: 0.5rem;
    border-top: 1px solid #f3f4f6;
    padding-top: 0.75rem;
}

/* ── Search Bar & Inputs ── */
.fmcg-search-wrapper {
    position: relative;
}
.fmcg-search-wrapper i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}
.fmcg-search-input {
    padding-left: 36px !important;
    border-radius: 8px;
    height: 38px;
    border: 1px solid #d1d5db;
}

/* ── Fullscreen Modal Iframe Layout ── */
.modal-iframe-dialog {
    max-width: 95vw;
    width: 95vw;
    margin: 1.5rem auto;
}
.modal-iframe-content {
    height: calc(100vh - 100px);
    border-radius: 12px;
    border: none;
    overflow: hidden;
}
.modal-iframe-body {
    padding: 0;
    height: calc(100% - 56px);
    position: relative;
}
.report-iframe {
    width: 100%;
    height: 100%;
    border: none;
}
.iframe-loader {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    flex-direction: column;
}

/* ── Dark Mode Adaptive Overrides ── */
body.dark-mode .fmcg-nav-item {
    color: #9ca3af;
}
body.dark-mode .fmcg-nav-item:hover {
    background: #1e293b;
    color: #e2e8f0;
}
body.dark-mode .fmcg-nav-item.active {
    background: #0f172a;
    color: #38bdf8;
    border-color: #0369a1;
}
body.dark-mode .fmcg-badge-count {
    background: #334155;
    color: #9ca3af;
}
body.dark-mode .fmcg-nav-item.active .fmcg-badge-count {
    background: #0369a1;
    color: #38bdf8;
}
body.dark-mode .fmcg-card {
    background: #16213e;
    border-color: #0f3460;
    color: #e2e8f0;
}
body.dark-mode .fmcg-card:hover {
    border-color: #1e293b;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
}
body.dark-mode .fmcg-card-title {
    color: #f1f5f9;
}
body.dark-mode .fmcg-card-desc {
    color: #94a3b8;
}
body.dark-mode .fmcg-card-actions {
    border-top-color: #1e293b;
}
body.dark-mode .fmcg-card-sales .fmcg-card-icon { background: #064e3b; color: #34d399; }
body.dark-mode .fmcg-card-customer .fmcg-card-icon { background: #164e63; color: #22d3ee; }
body.dark-mode .fmcg-card-purchase .fmcg-card-icon { background: #1e3a8a; color: #60a5fa; }
body.dark-mode .fmcg-card-inventory .fmcg-card-icon { background: #78350f; color: #fbbf24; }
body.dark-mode .fmcg-card-analytics .fmcg-card-icon { background: #4c1d95; color: #a78bfa; }
body.dark-mode .fmcg-card-gst .fmcg-card-icon { background: #7f1d1d; color: #f87171; }
body.dark-mode .iframe-loader {
    background: rgba(22, 33, 62, 0.9);
}
body.dark-mode .modal-iframe-content {
    background: #16213e;
}
body.dark-mode .modal-header {
    border-bottom-color: #0f3460;
    background: #0f3460;
}
</style>

<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold mb-1 text-dark">
            🚀 Reports Suite
        </h4>
        <small class="text-muted">Natively powered by <?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?> local MySQL database</small>
    </div>
    <div class="d-flex align-items-center mt-2 mt-md-0">
        <span class="badge badge-success px-3 py-2 font-weight-bold shadow-sm" style="font-size:0.82rem; border-radius:30px;">
            <i class="fas fa-database mr-1"></i> Local DB Sync (Online)
        </span>
    </div>
</div>

<!-- Configuration & Filter Form Panel -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 px-3 bg-light text-dark">
        <div class="row align-items-center">
            <!-- Global Month Selection -->
            <div class="col-lg-3 col-md-4 mb-2 mb-md-0">
                <div class="input-group input-group-sm align-items-center">
                    <div class="input-group-prepend"><span class="input-group-text bg-white border-0 font-weight-bold" style="font-size:0.8rem;">Period</span></div>
                    <select id="global-year" class="form-control form-control-sm border-0 ml-1 rounded-sm shadow-sm" style="font-size:0.82rem;font-weight:600;">
                        <?php 
                        $cur_year = (int)date('Y');
                        for ($y = $cur_year - 2; $y <= $cur_year + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= $y === $cur_year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="global-month" class="form-control form-control-sm border-0 ml-1 rounded-sm shadow-sm" style="font-size:0.82rem;font-weight:600;">
                        <?php 
                        $cur_month = (int)date('m');
                        $months = [
                            1=>'January', 2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June',
                            7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December'
                        ];
                        foreach ($months as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $num === $cur_month ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Global Department Code -->
            <div class="col-lg-3 col-md-4 mb-2 mb-md-0 border-left-lg">
                <div class="input-group input-group-sm align-items-center">
                    <div class="input-group-prepend"><span class="input-group-text bg-white border-0 font-weight-bold" style="font-size:0.8rem;">Dept</span></div>
                    <input type="text" id="global-dept" class="form-control form-control-sm border-0 ml-1 rounded-sm shadow-sm px-2 text-dark" style="font-size:0.82rem;font-weight:600;" value="" placeholder="e.g. STORE">
                </div>
            </div>

            <!-- Global Search Box -->
            <div class="col-lg-6 col-md-4 ml-auto">
                <div class="fmcg-search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="report-search" class="form-control form-control-sm fmcg-search-input bg-white border-0 shadow-sm text-dark" placeholder="Search report name or description (e.g. GSTR, Stock, Margin)...">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Layout Structure -->
<div class="fmcg-layout">
    
    <!-- Sidebar Navigation Left Column -->
    <div class="fmcg-sidebar">
        <div class="list-group shadow-sm border-0 bg-transparent">
            <?php 
            $categories = [
                'all'       => ['label' => 'All Reports',       'icon' => 'fas fa-th-large',          'color' => 'dark'],
                'sales'     => ['label' => 'Sales & Returns',   'icon' => 'fas fa-chart-line',        'color' => 'success'],
                'customer'  => ['label' => 'Customer Analytics', 'icon' => 'fas fa-users-cog',         'color' => 'info'],
                'purchase'  => ['label' => 'Purchase GRNs',      'icon' => 'fas fa-shopping-basket',   'color' => 'primary'],
                'inventory' => ['label' => 'Inventory Status',   'icon' => 'fas fa-boxes',             'color' => 'warning'],
                'analytics' => ['label' => 'Advanced Analytics', 'icon' => 'fas fa-chart-pie',         'color' => 'purple'],
                'gst'       => ['label' => 'GST & Compliance',  'icon' => 'fas fa-file-invoice-dollar','color' => 'danger'],
            ];
            $first = true;
            foreach ($categories as $key => $cat):
            ?>
            <a href="#" class="fmcg-nav-item <?= $first ? 'active' : '' ?>" data-category="<?= $key ?>">
                <i class="<?= $cat['icon'] ?> text-<?= $cat['color'] ?>"></i>
                <span><?= $cat['label'] ?></span>
                <span class="fmcg-badge-count" id="count-<?= $key ?>">0</span>
            </a>
            <?php 
            $first = false;
            endforeach; 
            ?>
        </div>
        
        <!-- Offline tips block -->
        <div class="card border-0 shadow-sm mt-3 text-dark">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:0.75rem; letter-spacing:0.5px;">
                💡 TIPS &amp; UTILITIES
            </div>
            <div class="card-body py-2 px-3" style="font-size:0.75rem; line-height:1.4;">
                <p class="mb-1 text-muted"><strong>GST Offline Tool:</strong> Use GSTR-1 Excel templates to export directly into official spreadsheets matching portal requirements.</p>
                <p class="mb-0 text-muted"><strong>Live Sync:</strong> Reports pull live transactional views from local MySQL tables automatically on load.</p>
            </div>
        </div>
    </div>

    <!-- Main Reports Panel Right Column -->
    <div class="fmcg-main">
        <div class="fmcg-grid" id="reports-grid">
            <?php 
            $reports_data = [
                // Sales
                [
                    'key' => 'sales_summary',
                    'category' => 'sales',
                    'title' => 'Sales Summary Report',
                    'desc' => 'Daily sales totals, billing volumes, taxable value, and tax splits (CGST/SGST/IGST).',
                    'icon' => 'fas fa-file-invoice-dollar',
                    'has_period' => true
                ],
                [
                    'key' => 'sales_detail',
                    'category' => 'sales',
                    'title' => 'Item Sales Detail',
                    'desc' => 'Line-item wise sales log including specific item codes, quantities sold, and cost margins.',
                    'icon' => 'fas fa-list-ol',
                    'has_period' => true
                ],
                [
                    'key' => 'sales_payment',
                    'category' => 'sales',
                    'title' => 'Payment Mode Breakdown',
                    'desc' => 'Detailed collection logs of Cash, Card, UPI, and Credit payments by cashier and shift.',
                    'icon' => 'fas fa-credit-card',
                    'has_period' => true
                ],
                [
                    'key' => 'sales_returns',
                    'category' => 'sales',
                    'title' => 'Sales Returns logs',
                    'desc' => 'Returns logs, items refunded, credit notes issued, and negative sales audit trail.',
                    'icon' => 'fas fa-undo-alt',
                    'has_period' => true
                ],
                [
                    'key' => 'sales_smart',
                    'category' => 'sales',
                    'title' => 'Smart Interactive Sales',
                    'desc' => 'Multi-filter sales search engine filtering by product category, brand, and customer mobile.',
                    'icon' => 'fas fa-search-dollar',
                    'has_period' => true,
                    'is_smart' => true
                ],
                [
                    'key' => 'scheme_roi',
                    'category' => 'sales',
                    'title' => 'Scheme ROI',
                    'desc' => 'Discount cost vs revenue influenced, per promotional scheme, over a date range.',
                    'icon' => 'fas fa-percentage',
                    'has_period' => true
                ],

                // Customer
                [
                    'key' => 'customer_list',
                    'category' => 'customer',
                    'title' => 'Customer Master List',
                    'desc' => 'Directory of registered customers, contact phone numbers, registration dates, and GSTINs.',
                    'icon' => 'fas fa-address-book',
                    'has_period' => false
                ],
                [
                    'key' => 'customer_ledger',
                    'category' => 'customer',
                    'title' => 'Customer Transaction Ledger',
                    'desc' => 'Detailed transaction history and statement ledger for specific customer entities.',
                    'icon' => 'fas fa-book',
                    'has_period' => false
                ],
                [
                    'key' => 'customer_loyalty',
                    'category' => 'customer',
                    'title' => 'Loyalty Points Ledger',
                    'desc' => 'Loyalty points accrued, points redeemed, and current balances across billing events.',
                    'icon' => 'fas fa-award',
                    'has_period' => false
                ],
                [
                    'key' => 'customer_segments',
                    'category' => 'customer',
                    'title' => 'RFM Customer Segments',
                    'desc' => 'Segmentation of customers by Recency, Frequency, and Monetary parameters for campaign targeting.',
                    'icon' => 'fas fa-user-tag',
                    'has_period' => false
                ],
                [
                    'key' => 'customer_trend',
                    'category' => 'customer',
                    'title' => 'Monthly Purchase Trends',
                    'desc' => 'Purchase frequency analysis, average transaction value, and monthly peaks per client.',
                    'icon' => 'fas fa-chart-area',
                    'has_period' => true
                ],
                [
                    'key' => 'customer_promotions',
                    'category' => 'customer',
                    'title' => 'Smart Promotions Generator',
                    'desc' => 'Next-product suggestions and affinity promotions generated using database statistics.',
                    'icon' => 'fas fa-percentage',
                    'has_period' => false
                ],

                // Purchase
                [
                    'key' => 'purchase_summary',
                    'category' => 'purchase',
                    'title' => 'Purchase GRN Summary',
                    'desc' => 'Summary of Goods Receipt Notes by date, supplier vendor, and invoice parameters.',
                    'icon' => 'fas fa-receipt',
                    'has_period' => true
                ],
                [
                    'key' => 'purchase_detail',
                    'category' => 'purchase',
                    'title' => 'GRN Item Details',
                    'desc' => 'Detailed raw item logs for incoming stock shipments, cost structures, and tax codes.',
                    'icon' => 'fas fa-boxes',
                    'has_period' => true
                ],
                [
                    'key' => 'purchase_returns',
                    'category' => 'purchase',
                    'title' => 'Purchase Returns (PRN)',
                    'desc' => 'Outward supplier returns, itemized debit notes, and credit returns statements.',
                    'icon' => 'fas fa-reply',
                    'has_period' => true
                ],
                [
                    'key' => 'vendor_scorecard',
                    'category' => 'purchase',
                    'title' => 'Vendor Scorecard',
                    'desc' => 'On-time delivery, fill rate, quality rating, and RFQ responsiveness by supplier.',
                    'icon' => 'fas fa-star-half-alt',
                    'has_period' => false
                ],

                // Inventory
                [
                    'key' => 'inventory_stock',
                    'category' => 'inventory',
                    'title' => 'Stock Status & Warnings',
                    'desc' => 'Current inventory listings, out-of-stock items, and threshold replenishment suggestions.',
                    'icon' => 'fas fa-exclamation-triangle',
                    'has_period' => false
                ],
                [
                    'key' => 'inventory_transfer',
                    'category' => 'inventory',
                    'title' => 'Stock Transfers (MTN)',
                    'desc' => 'Stock transfer records, logs of transport, and inter-department stock audits.',
                    'icon' => 'fas fa-dolly',
                    'has_period' => true
                ],
                [
                    'key' => 'inventory_valuation',
                    'category' => 'inventory',
                    'title' => 'Real-time Stock Valuation',
                    'desc' => 'Asset valuation of stock calculated using cost prices and taxes.',
                    'icon' => 'fas fa-calculator',
                    'has_period' => false
                ],
                [
                    'key' => 'expiry_alert',
                    'category' => 'inventory',
                    'title' => 'Near-Expiry Alert',
                    'desc' => 'Products approaching expiry (default 30 days) with quantity at risk per location.',
                    'icon' => 'fas fa-hourglass-end',
                    'has_period' => false
                ],

                // Analytics
                [
                    'key' => 'analytics_dashboard',
                    'category' => 'analytics',
                    'title' => 'Live Business Dashboard',
                    'desc' => 'Real-time business sales graphs, active registers, shift statuses, and KPI panels.',
                    'icon' => 'fas fa-tachometer-alt',
                    'has_period' => false
                ],
                [
                    'key' => 'analytics_trend',
                    'category' => 'analytics',
                    'title' => 'Sales Trend Charts',
                    'desc' => 'Graphical dashboards of monthly, daily, and seasonal sales trends.',
                    'icon' => 'fas fa-chart-line',
                    'has_period' => true
                ],
                [
                    'key' => 'analytics_top_items',
                    'category' => 'analytics',
                    'title' => 'Top Selling SKU Ranking',
                    'desc' => 'Fastest moving items, ranking by gross quantity sold, and profitability index.',
                    'icon' => 'fas fa-fire',
                    'has_period' => true
                ],
                [
                    'key' => 'analytics_category',
                    'category' => 'analytics',
                    'title' => 'Category Sales Contribution',
                    'desc' => 'Revenue shares and percentage margins of sales grouped by item categories.',
                    'icon' => 'fas fa-sitemap',
                    'has_period' => true
                ],
                [
                    'key' => 'analytics_supplier',
                    'category' => 'analytics',
                    'title' => 'Supplier Delivery Lead times',
                    'desc' => 'Vendor rating, lead-time compliance, and fulfillment ratios.',
                    'icon' => 'fas fa-truck-loading',
                    'has_period' => true
                ],
                [
                    'key' => 'analytics_velocity',
                    'category' => 'analytics',
                    'title' => 'Inventory Turnover Velocity',
                    'desc' => 'Stock-turns calculations, days-of-coverage, and identification of slow-moving stocks.',
                    'icon' => 'fas fa-shipping-fast',
                    'has_period' => true
                ],
                [
                    'key' => 'analytics_profitability',
                    'category' => 'analytics',
                    'title' => 'Profitability & Margin Audit',
                    'desc' => 'Gross profit margins, cost vs sell price structures, and profit multipliers.',
                    'icon' => 'fas fa-coins',
                    'has_period' => true
                ],
                [
                    'key' => 'analytics_sales_vs_purchase',
                    'category' => 'analytics',
                    'title' => 'Sales vs Purchase Match',
                    'desc' => 'Reconciliation of sales volume matching against procurement purchases logs.',
                    'icon' => 'fas fa-exchange-alt',
                    'has_period' => true
                ],
                [
                    'key' => 'analytics_exceptions',
                    'category' => 'analytics',
                    'title' => 'Operational Exception Reports',
                    'desc' => 'Logs of negative stock corrections, high manual discounts, and voided receipts.',
                    'icon' => 'fas fa-exclamation-circle',
                    'has_period' => true
                ],
                [
                    'key' => 'offline_sync_health',
                    'category' => 'analytics',
                    'title' => 'Offline Sync Health',
                    'desc' => 'Failed and stuck offline POS syncs by device, with retry counts and last error.',
                    'icon' => 'fas fa-plug',
                    'has_period' => true
                ],

                // GST Compliance
                [
                    'key' => 'gst_hsn_sales',
                    'category' => 'gst',
                    'title' => 'HSN Outward Sales Summary',
                    'desc' => 'HSN wise sales summary (GSTR-1 Table 12 format) with CGST/SGST/IGST splits.',
                    'icon' => 'fas fa-tags',
                    'has_period' => true
                ],
                [
                    'key' => 'gst_hsn_purchase',
                    'category' => 'gst',
                    'title' => 'HSN Inward Purchase Summary',
                    'desc' => 'HSN wise purchase logs mapping input tax credits for inward inventories.',
                    'icon' => 'fas fa-tag',
                    'has_period' => true
                ],
                [
                    'key' => 'gst_hsn_comparison',
                    'category' => 'gst',
                    'title' => 'HSN Sales vs Purchase Audit',
                    'desc' => 'Verification audit matching outward HSN tax rates against purchase HSN rates.',
                    'icon' => 'fas fa-greater-than-equal',
                    'has_period' => true
                ],
                [
                    'key' => 'gst_gstr1',
                    'category' => 'gst',
                    'title' => 'GSTR-1 Compliance Assistant',
                    'desc' => 'Comprehensive GSTR-1 transaction logs with built-in government offline schema exports.',
                    'icon' => 'fas fa-file-invoice',
                    'has_period' => true,
                    'is_gstr1' => true
                ],
                [
                    'key' => 'gst_gstr3b',
                    'category' => 'gst',
                    'title' => 'GSTR-3B monthly assessment',
                    'desc' => 'Monthly self-assessment summary covering Table 3.1 liability and Table 4 ITC claims.',
                    'icon' => 'fas fa-balance-scale-right',
                    'has_period' => true
                ],
                [
                    'key' => 'gst_itc',
                    'category' => 'gst',
                    'title' => 'Input Tax Credit (ITC) Tracker',
                    'desc' => 'Comprehensive eligibility statements tracking tax inputs on purchases.',
                    'icon' => 'fas fa-percent',
                    'has_period' => true
                ],
                [
                    'key' => 'gst_cdnr',
                    'category' => 'gst',
                    'title' => 'Credit Notes (CDNR) Report',
                    'desc' => 'Credit/Debit note reporting for registered return transactions.',
                    'icon' => 'fas fa-minus-square',
                    'has_period' => true
                ]
            ];

            foreach ($reports_data as $rep):
            ?>
            <div class="fmcg-card fmcg-card-<?= $rep['category'] ?>" 
                 data-category="<?= $rep['category'] ?>" 
                 data-title="<?= htmlspecialchars(strtolower($rep['title'])) ?>" 
                 data-desc="<?= htmlspecialchars(strtolower($rep['desc'])) ?>"
                 data-key="<?= $rep['key'] ?>"
                 data-period="<?= $rep['has_period'] ? '1' : '0' ?>"
                 data-smart="<?= !empty($rep['is_smart']) ? '1' : '0' ?>"
                 data-gstr1="<?= !empty($rep['is_gstr1']) ? '1' : '0' ?>">
                
                <div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="fmcg-card-icon">
                            <i class="<?= $rep['icon'] ?>"></i>
                        </div>
                        <h6 class="fmcg-card-title mb-0"><?= htmlspecialchars($rep['title']) ?></h6>
                    </div>
                    <p class="fmcg-card-desc"><?= htmlspecialchars($rep['desc']) ?></p>
                </div>
                
                <div class="fmcg-card-actions">
                    <button class="btn btn-outline-primary btn-sm flex-grow-1 btn-quick-view" style="font-size:0.75rem;font-weight:600;">
                        <i class="fas fa-eye"></i> Quick View
                    </button>
                    <a href="#" target="_blank" class="btn btn-dark btn-sm btn-launch" style="font-size:0.75rem;font-weight:600;" title="Launch in New Tab">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
                
                <!-- GSTR-1 Compliance Extras Panel -->
                <?php if (!empty($rep['is_gstr1'])): ?>
                <div class="border-top mt-2 pt-2 d-flex justify-content-between align-items-center gap-1 flex-wrap">
                    <small class="font-weight-bold text-muted w-100 mb-1" style="font-size:0.7rem;text-transform:uppercase;">Offline Compliance Exports:</small>
                    <a href="#" id="gstr1-export-json" class="btn btn-outline-danger btn-xs flex-grow-1" style="font-weight:600;" title="Download Official JSON Upload Schema">
                        <i class="fas fa-file-code"></i> JSON Export
                    </a>
                    <a href="#" id="gstr1-export-excel" class="btn btn-outline-success btn-xs flex-grow-1" style="font-weight:600;" title="Download Pre-formatted Offline Excel Template">
                        <i class="fas fa-file-excel"></i> Official Excel
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Fullscreen Report Viewer Modal -->
<div class="modal fade" id="reportViewerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-iframe-dialog" role="document">
        <div class="modal-content modal-iframe-content shadow-lg border-0">
            <div class="modal-header py-2 px-3 align-items-center bg-dark text-white">
                <h5 class="modal-title font-weight-bold" id="modal-report-title" style="font-size:1rem;">Report Viewer</h5>
                <div class="ml-auto d-flex align-items-center">
                    <button id="modal-refresh" class="btn btn-sm btn-outline-light mr-2" title="Reload Report">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <a id="modal-external-link" href="#" target="_blank" class="btn btn-sm btn-light mr-3" title="Open in New Tab">
                        <i class="fas fa-external-link-alt"></i> Launch
                    </a>
                    <button type="button" class="close text-white ml-2 py-2" data-dismiss="modal" aria-label="Close" style="opacity:0.85; outline:none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body modal-iframe-body">
                <!-- Loading indicator -->
                <div class="iframe-loader" id="modal-loader">
                    <div class="spinner-border text-primary" role="status" style="width:2.5rem; height:2.5rem;"></div>
                    <span class="mt-2 font-weight-bold text-muted" style="font-size:0.85rem;">Retrieving live query rows...</span>
                </div>
                <!-- Interactive iframe -->
                <iframe id="modal-iframe" class="report-iframe" src=""></iframe>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const SERVER_BASE = '<?= base_url() ?>';
    
    // Elements
    const searchInput = document.getElementById('report-search');
    const navItems = document.querySelectorAll('.fmcg-nav-item');
    const cards = document.querySelectorAll('.fmcg-grid .fmcg-card');
    const globalYear = document.getElementById('global-year');
    const globalMonth = document.getElementById('global-month');
    const globalDept = document.getElementById('global-dept');
    
    // Modal Elements
    const modal = $('#reportViewerModal');
    const modalIframe = document.getElementById('modal-iframe');
    const modalTitle = document.getElementById('modal-report-title');
    const modalLoader = document.getElementById('modal-loader');
    const modalRefresh = document.getElementById('modal-refresh');
    const modalExternal = document.getElementById('modal-external-link');
    
    let currentCategory = 'all';
    
    // Helper to calculate full URL based on selected settings
    function buildReportUrl(key, isPeriod, isSmart, isGstr1ExportType) {
        const year = globalYear.value;
        const month = globalMonth.value;
        const dept = globalDept.value;
        
        let url = `${SERVER_BASE}`;
        
        // Ensure index.php mapping if required, else keep clean base_url
        if (!url.endsWith('/')) {
            url += '/';
        }
        
        if (isGstr1ExportType === 'json') {
            url += `reports/export_gstr1_json?year=${year}&month=${month}`;
            if (dept) url += `&dept=${dept}`;
            return url;
        }
        
        if (isGstr1ExportType === 'excel') {
            url += `reports/export_gstr1_excel?year=${year}&month=${month}`;
            if (dept) url += `&dept=${dept}`;
            return url;
        }
        
        url += `reports/run_fmcg_report/${key}`;
        
        const params = [];
        
        if (isSmart) {
            // Smart Sales Report parameters (from_date / to_date format)
            const padMonth = String(month).padStart(2, '0');
            const fromDate = `${year}-${padMonth}-01`;
            // Get last day of month
            const lastDay = new Date(year, month, 0).getDate();
            const toDate = `${year}-${padMonth}-${lastDay}`;
            params.push(`from=${fromDate}`);
            params.push(`to=${toDate}`);
        } else if (isPeriod) {
            // Convert month/year to standard from/to dates for local php backend reports
            const padMonth = String(month).padStart(2, '0');
            const fromDate = `${year}-${padMonth}-01`;
            const lastDay = new Date(year, month, 0).getDate();
            const toDate = `${year}-${padMonth}-${lastDay}`;
            params.push(`from=${fromDate}`);
            params.push(`to=${toDate}`);
        }
        
        if (dept) {
            params.push(`dept=${dept}`);
        }
        
        if (params.length > 0) {
            url += `?${params.join('&')}`;
        }
        
        return url;
    }
    
    // Update card buttons links and actions
    function updateCardLinks() {
        cards.forEach(card => {
            const key = card.getAttribute('data-key');
            const isPeriod = card.getAttribute('data-period') === '1';
            const isSmart = card.getAttribute('data-smart') === '1';
            const isGstr1 = card.getAttribute('data-gstr1') === '1';
            
            const launchBtn = card.querySelector('.btn-launch');
            const targetUrl = buildReportUrl(key, isPeriod, isSmart);
            launchBtn.setAttribute('href', targetUrl);
            
            if (isGstr1) {
                const jsonBtn = card.querySelector('#gstr1-export-json');
                const excelBtn = card.querySelector('#gstr1-export-excel');
                if (jsonBtn) jsonBtn.setAttribute('href', buildReportUrl(key, isPeriod, isSmart, 'json'));
                if (excelBtn) excelBtn.setAttribute('href', buildReportUrl(key, isPeriod, isSmart, 'excel'));
            }
        });
    }
    
    // Update links immediately and on period/dept selectors change
    updateCardLinks();
    globalYear.addEventListener('change', updateCardLinks);
    globalMonth.addEventListener('change', updateCardLinks);
    globalDept.addEventListener('change', updateCardLinks);
    globalDept.addEventListener('input', updateCardLinks);
    
    // Category Counts calculation
    function updateCategoryCounts() {
        const counts = { all: 0, sales: 0, customer: 0, purchase: 0, inventory: 0, analytics: 0, gst: 0 };
        cards.forEach(card => {
            const cat = card.getAttribute('data-category');
            if (counts[cat] !== undefined) {
                counts[cat]++;
                counts['all']++;
            }
        });
        
        for (const cat in counts) {
            const countBadge = document.getElementById(`count-${cat}`);
            if (countBadge) countBadge.textContent = counts[cat];
        }
    }
    updateCategoryCounts();
    
    // Filter & Search Implementation
    function filterReports() {
        const query = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        
        cards.forEach(card => {
            const cat = card.getAttribute('data-category');
            const title = card.getAttribute('data-title');
            const desc = card.getAttribute('data-desc');
            
            const matchesCategory = (currentCategory === 'all' || cat === currentCategory);
            const matchesSearch = (!query || title.includes(query) || desc.includes(query));
            
            if (matchesCategory && matchesSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show count badge updates under search
        const activeBadge = document.querySelector(`.fmcg-nav-item.active .fmcg-badge-count`);
        if (activeBadge && query) {
            activeBadge.textContent = `${visibleCount}`;
        } else {
            updateCategoryCounts();
        }
    }
    
    // Search listener
    searchInput.addEventListener('input', filterReports);
    
    // Category click handler
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.getAttribute('data-category');
            searchInput.value = ''; // clear search when switching categories
            filterReports();
        });
    });
    
    // Quick View inline iframe loading handler
    cards.forEach(card => {
        const qvBtn = card.querySelector('.btn-quick-view');
        qvBtn.addEventListener('click', function() {
            const key = card.getAttribute('data-key');
            const isPeriod = card.getAttribute('data-period') === '1';
            const isSmart = card.getAttribute('data-smart') === '1';
            const title = card.querySelector('.fmcg-card-title').textContent;
            
            const url = buildReportUrl(key, isPeriod, isSmart);
            
            // Set up modal contents
            modalTitle.innerHTML = `<i class="${card.querySelector('.fmcg-card-icon i').className} mr-2"></i> ${title}`;
            modalLoader.style.display = 'flex';
            modalIframe.src = url;
            modalExternal.setAttribute('href', url);
            
            // Show modal
            modal.modal('show');
        });
    });
    
    // Iframe load listener to hide spinner
    modalIframe.addEventListener('load', function() {
        modalLoader.style.display = 'none';
    });
    
    // Refresh modal iframe content
    modalRefresh.addEventListener('click', function() {
        modalLoader.style.display = 'flex';
        modalIframe.contentWindow.location.reload();
    });
    
    // Reset modal source when hidden to release memory/requests
    modal.on('hidden.bs.modal', function() {
        modalIframe.src = '';
    });
});
</script>
