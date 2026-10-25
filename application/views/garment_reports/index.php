<?php
// ============================================================
// FILE: application/views/garment_reports/index.php
// Garment Reports Dashboard & Analytics Hub
// ============================================================
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-chart-line text-primary mr-2"></i>Garment & Apparel Reports</h5>
        <small class="text-muted">Size-wise sales matrices, colour movement, stock valuation, and reorder intelligence</small>
    </div>
</div>

<div class="row">
    <!-- Card 1: Size-wise Sales Matrix -->
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-3 bg-primary text-white rounded mr-3">
                        <i class="fas fa-th-large fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="font-weight-bold text-dark mb-1">Size-wise Sales Matrix</h6>
                        <small class="text-muted">Product × Size grid</small>
                    </div>
                </div>
                <p class="text-muted small">
                    Compare sales quantities and available stock across all sizes (XS, S, M, L, XL, XXL) in an interactive cross-tab matrix. Spot best-selling sizes and stock imbalances.
                </p>
                <a href="<?= base_url('garmentreports/size_matrix') ?>" class="btn btn-outline-primary btn-sm btn-block font-weight-bold">
                    View Size Matrix <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Card 2: Colour-wise Analytics -->
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-3 bg-info text-white rounded mr-3">
                        <i class="fas fa-palette fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="font-weight-bold text-dark mb-1">Colour-wise Analytics</h6>
                        <small class="text-muted">Sales & Stock by Colour</small>
                    </div>
                </div>
                <p class="text-muted small">
                    Track demand for specific fabric colours and shades across all garments. Understand which colour variations drive retail revenue and which sit unsold.
                </p>
                <a href="<?= base_url('garmentreports/colour_matrix') ?>" class="btn btn-outline-info btn-sm btn-block font-weight-bold">
                    View Colour Report <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Card 3: Garment Stock & Inventory Valuation Report -->
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-3 bg-success text-white rounded mr-3">
                        <i class="fas fa-boxes fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="font-weight-bold text-dark mb-1">Garment Stock &amp; Valuation</h6>
                        <small class="text-muted">Size, Colour &amp; Article Breakdown</small>
                    </div>
                </div>
                <p class="text-muted small">
                    Live inventory balances across all sizes (XS, S, M, L, XL), colours, style/article codes, and categories. Features 1-click Quick Size Explorer, Cost &amp; MRP valuation, and low stock alerts.
                </p>
                <a href="<?= base_url('garmentreports/stock_report') ?>" class="btn btn-outline-success btn-sm btn-block font-weight-bold">
                    View Garment Stock Report <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>
