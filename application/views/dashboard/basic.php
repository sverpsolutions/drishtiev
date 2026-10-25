<?php
// Quick-link definitions: form_key => [label, icon, url]
$quick_links = [
    'frm_billing'              => ['Create Invoice',        'fas fa-file-invoice',        'billing/create'],
    'frm_billing'              => ['Billing List',          'fas fa-list',                'billing'],
    'frm_estimates'            => ['Estimates',             'fas fa-file-alt',            'estimates'],
    'frm_sales_returns'        => ['Sales Returns',         'fas fa-undo',                'sales_returns'],
    'frm_payments_receive'     => ['Receive Payment',       'fas fa-hand-holding-usd',    'payments/receive'],
    'frm_order_requests'       => ['Order Requests',        'fas fa-shopping-bag',        'order_requests'],
    'frm_products'             => ['Products',              'fas fa-boxes',               'products'],
    'frm_products_add'         => ['Add Product',           'fas fa-plus-square',         'products/add'],
    'frm_plu'                  => ['PLU Master',            'fas fa-barcode',             'plu'],
    'frm_customers'            => ['Customers',             'fas fa-users',               'customers'],
    'frm_suppliers'            => ['Suppliers',             'fas fa-truck',               'suppliers'],
    'frm_purchases'            => ['Purchases',             'fas fa-shopping-cart',       'purchases'],
    'frm_purchase_orders'      => ['Purchase Orders',       'fas fa-file-purchase-order', 'purchase_orders'],
    'frm_grn'                  => ['GRN',                   'fas fa-clipboard-check',     'grn'],
    'frm_rfq'                  => ['RFQ',                   'fas fa-envelope-open-text',  'rfq'],
    'frm_purchase_returns'     => ['Purchase Returns',      'fas fa-undo-alt',            'purchase_returns'],
    'frm_payments_supplier'    => ['Pay Supplier',          'fas fa-money-bill-wave',     'payments/pay_supplier'],
    'frm_purchase_suggestions' => ['Purchase Suggestions',  'fas fa-lightbulb',           'purchase_suggestions'],
    'frm_inventory'            => ['Inventory',             'fas fa-warehouse',           'inventory'],
    'frm_stock_transfers'      => ['Stock Transfers',       'fas fa-exchange-alt',        'stock_transfers'],
    'frm_stock_take'           => ['Stock Take',            'fas fa-clipboard-list',      'stock_take'],
    'frm_reports'              => ['Reports',               'fas fa-chart-bar',           'reports'],
    'frm_hsn'                  => ['HSN Master',            'fas fa-tags',                'hsn'],
    'frm_masters'              => ['Masters',               'fas fa-cogs',                'masters/sub_categories'],
    'frm_schemes'              => ['Schemes',               'fas fa-percentage',          'schemes'],
    'frm_settings'             => ['Settings',              'fas fa-sliders-h',           'settings'],
    'frm_locations'            => ['Locations',             'fas fa-map-marker-alt',      'locations'],
    'frm_user_management'      => ['User Management',       'fas fa-user-cog',            'user_management'],
];

// Deduplicate by URL, filter to only permitted forms
$allowed_links = [];
foreach ($quick_links as $fkey => $link) {
    if (!isset($form_perms[$fkey]) || !empty($form_perms[$fkey])) {
        $url = $link[2];
        if (!isset($allowed_links[$url])) {
            $allowed_links[$url] = $link;
        }
    }
}
?>

<div class="container-fluid py-4">

    <!-- Welcome Banner -->
    <div class="card border-0 mb-4" style="background: linear-gradient(135deg, #3B2314, #6D3D1E); color:#FFF9E6; border-radius:12px;">
        <div class="card-body py-4 px-4">
            <div class="d-flex align-items-center">
                <div style="width:56px;height:56px;background:rgba(246,205,60,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-right:20px;flex-shrink:0;">
                    <i class="fas fa-user" style="font-size:24px;color:#F6CD3C;"></i>
                </div>
                <div>
                    <h4 class="mb-0 font-weight-bold" style="color:#F6CD3C;">Welcome, <?= htmlspecialchars($name) ?>!</h4>
                    <p class="mb-0" style="color:#E6DCBF;font-size:0.9rem;">
                        <?= ucfirst(htmlspecialchars($role)) ?> &mdash; <?= date('l, d F Y') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access Links -->
    <?php if (!empty($allowed_links)): ?>
    <h6 class="font-weight-bold text-muted mb-3" style="letter-spacing:0.05em; text-transform:uppercase; font-size:0.78rem;">
        <i class="fas fa-th mr-1"></i> Quick Access
    </h6>
    <div class="row">
        <?php foreach ($allowed_links as $url => $link): ?>
        <div class="col-6 col-md-3 col-lg-2 mb-3">
            <a href="<?= base_url($url) ?>" class="card border-0 shadow-sm text-center text-decoration-none h-100"
               style="border-radius:10px; transition:transform 0.15s, box-shadow 0.15s;"
               onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div class="card-body py-3 px-2">
                    <div style="width:44px;height:44px;background:#FFF2CC;border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                        <i class="<?= $link[1] ?>" style="font-size:18px;color:#3B2314;"></i>
                    </div>
                    <p class="mb-0 font-weight-bold" style="font-size:0.78rem;color:#3B2314;line-height:1.2;">
                        <?= htmlspecialchars($link[0]) ?>
                    </p>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        You have no pages assigned yet. Please contact your administrator.
    </div>
    <?php endif; ?>

</div>
