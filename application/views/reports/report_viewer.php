<?php // FILE: application/views/reports/report_viewer.php ?>
<?php
// ─── Dynamic Column Total Calculation ───
$numeric_cols = [];
$col_sums = [];
// Scan headers to see which ones are numeric/monetary. Per-unit values (Selling
// Price, MRP, Cost Price, Rate) and percentages (GST %, Margin %) are deliberately
// excluded — summing those across many rows produces a meaningless number (e.g.
// "Total GST %: 1,885" isn't a real percentage). Only genuine ₹ totals and
// quantity/count columns are aggregated into the summary cards.
foreach ($headers as $col_idx => $header) {
    $header_lower = strtolower($header);
    if (strpos($header_lower, '%') !== false) continue;
    if (
        strpos($header_lower, 'qty') !== false ||
        strpos($header_lower, 'count') !== false ||
        strpos($header_lower, 'bills') !== false ||
        strpos($header_lower, 'items') !== false ||
        strpos($header_lower, 'total') !== false ||
        strpos($header_lower, 'subtotal') !== false ||
        strpos($header_lower, 'net') !== false ||
        strpos($header_lower, 'discount') !== false ||
        strpos($header_lower, 'tax') !== false ||
        strpos($header_lower, 'gst') !== false ||
        strpos($header_lower, 'paid') !== false ||
        strpos($header_lower, 'refund') !== false ||
        strpos($header_lower, 'due') !== false ||
        strpos($header_lower, 'value') !== false ||
        strpos($header_lower, 'debit') !== false ||
        strpos($header_lower, 'credit') !== false
    ) {
        $numeric_cols[$col_idx] = [
            'name' => $header,
            'is_currency' => (
                strpos($header_lower, 'qty') === false && 
                strpos($header_lower, 'count') === false && 
                strpos($header_lower, 'bills') === false && 
                strpos($header_lower, 'items') === false
            )
        ];
        $col_sums[$col_idx] = 0.0;
    }
}

// Calculate sums — skip any row that is itself a grand-total row (marked with
// 'TOTAL' or the '__GT__' prefix), otherwise the summary cards would double-count it.
if (!empty($rows)) {
    foreach ($rows as $row_data) {
        $row_label = isset($row_data[0]) ? strtoupper(trim((string)$row_data[0])) : '';
        if ($row_label === 'TOTAL' || strpos($row_label, '__GT__') === 0) continue;
        foreach ($numeric_cols as $col_idx => $meta) {
            if (isset($row_data[$col_idx])) {
                // Strip currency symbol, spaces, commas to parse float
                $val = str_replace(['₹', ',', ' ', '%'], '', $row_data[$col_idx]);
                $col_sums[$col_idx] += (float)$val;
            }
        }
    }
}

// Resolve export route/params dynamically
$export_route = !empty($is_fmcg) ? "reports/run_fmcg_report/{$type}" : "reports/run_report/{$type}";
$export_params = "from={$from}&to={$to}";
if (!empty($cashier_id)) {
    $export_params .= "&cashier_id={$cashier_id}";
}
if (!empty($location_id)) $export_params .= '&location_id=' . (int)$location_id;
foreach ($_GET as $gk => $gv) {
    if (!in_array($gk, ['from', 'to', 'cashier_id', 'location_id', 'export'])) {
        $export_params .= "&" . urlencode($gk) . "=" . urlencode($gv);
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h4 class="font-weight-bold mb-0 text-dark"><?= htmlspecialchars($report_title) ?></h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent p-0 mb-0" style="font-size: 0.85rem;">
                <li class="breadcrumb-item"><a href="<?= base_url('reports') ?>" class="text-muted text-decoration-none">Reports</a></li>
                <li class="breadcrumb-item active text-primary font-weight-bold" aria-current="page"><?= htmlspecialchars($report_title) ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1 shadow-sm px-3">
            <span>🖨️</span> Print
        </button>
        <a href="<?= base_url("{$export_route}?{$export_params}&export=pdf") ?>" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 shadow-sm px-3">
            <span>📄</span> PDF
        </a>
        <a href="<?= base_url("{$export_route}?{$export_params}&export=excel") ?>" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 shadow-sm px-3">
            <span>📊</span> Excel CSV
        </a>
    </div>
</div>

<!-- Print-Only Header -->
<div class="d-none d-print-block mb-4">
    <div class="text-center border-bottom pb-3">
        <?php 
        $company_logo = $company['company_logo'] ?? '';
        if (!empty($company_logo) && file_exists('./' . $company_logo)): 
        ?>
            <div style="margin-bottom: 8px;">
                <img src="<?= base_url($company_logo) ?>" style="max-height: 50px; max-width: 150px; object-fit: contain;">
            </div>
        <?php endif; ?>
        <h3 class="mb-1 font-weight-bold"><?= htmlspecialchars($company['company_name'] ?? 'View & Chew') ?></h3>
        <p class="mb-1 text-muted" style="font-size: 0.9rem;"><?= htmlspecialchars($company['company_address'] ?? '') ?></p>
        <p class="mb-0 text-muted" style="font-size: 0.9rem;">GSTIN: <?= htmlspecialchars($company['company_gstin'] ?? 'N/A') ?> | Phone: <?= htmlspecialchars($company['company_phone'] ?? '') ?></p>
        <?php if (!empty($location_id) && !empty($locations)):
            $rpt_locs = array_filter((array)$locations, fn($l) => $l->id == $location_id);
            $rpt_loc = reset($rpt_locs); ?>
        <p class="mb-0 text-info" style="font-size:0.9rem;">Outlet: <strong><?= htmlspecialchars($rpt_loc->name ?? '') ?></strong></p>
        <?php endif; ?>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <h5 class="font-weight-bold mb-0"><?= htmlspecialchars($report_title) ?></h5>
        <span class="text-muted" style="font-size: 0.85rem;">Period: <?= date('d M Y', strtotime($from)) ?> to <?= date('d M Y', strtotime($to)) ?></span>
    </div>
</div>

<!-- Date, Cashier & Toggle Filters -->
<div class="card border-0 shadow-sm mb-4 no-print" style="border-radius: 12px; background: linear-gradient(145deg, #ffffff, #f8f9fa);">
    <div class="card-body py-3">
        <form method="get" class="form-inline d-flex flex-wrap gap-3 align-items-center">
            <div class="form-group mb-0">
                <label class="mr-2 font-weight-bold text-muted" style="font-size: 0.85rem;">From Date</label>
                <input type="date" name="from" class="form-control form-control-sm border-0 shadow-sm px-3 py-2" style="border-radius: 8px; font-size: 0.85rem;" value="<?= $from ?>">
            </div>
            
            <div class="form-group mb-0">
                <label class="mr-2 font-weight-bold text-muted" style="font-size: 0.85rem;">To Date</label>
                <input type="date" name="to" class="form-control form-control-sm border-0 shadow-sm px-3 py-2" style="border-radius: 8px; font-size: 0.85rem;" value="<?= $to ?>">
            </div>

            <?php if (in_array($type, ['shift_open', 'shift_close', 'cashier_collection', 'short_excess'])): ?>
            <div class="form-group mb-0">
                <label class="mr-2 font-weight-bold text-muted" style="font-size: 0.85rem;">Cashier</label>
                <select name="cashier_id" class="form-control form-control-sm border-0 shadow-sm px-3 py-2" style="border-radius: 8px; font-size: 0.85rem; min-width: 160px;">
                    <option value="">All Cashiers</option>
                    <?php foreach ($cashiers as $c): ?>
                        <option value="<?= $c->id ?>" <?= $cashier_id == $c->id ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($locations)): ?>
            <?php $this->load->view('reports/_location_filter') ?>
            <?php endif; ?>

            <!-- Dynamic Summary vs Details Mode toggler -->
            <?php 
            $toggle_reports = [
                'sales_summary' => ['label' => 'Sales Summary', 'opposite' => 'sales_detail'],
                'sales_detail' => ['label' => 'Item Sales Detail', 'opposite' => 'sales_summary'],
                'purchase_summary' => ['label' => 'Purchase GRN Summary', 'opposite' => 'purchase_detail'],
                'purchase_detail' => ['label' => 'GRN Item Details', 'opposite' => 'purchase_summary']
            ];
            if (isset($toggle_reports[$type])): 
            ?>
            <div class="form-group mb-0">
                <label class="mr-2 font-weight-bold text-muted" style="font-size: 0.85rem;">Report Mode</label>
                <select id="reportModeToggle" class="form-control form-control-sm border-0 shadow-sm px-3 py-2 font-weight-bold" style="border-radius: 8px; font-size: 0.85rem; min-width: 170px; background: #e0f2fe; color: #0369a1;">
                    <option value="<?= $type ?>" selected><?= $toggle_reports[$type]['label'] ?></option>
                    <option value="<?= $toggle_reports[$type]['opposite'] ?>">
                        <?= $toggle_reports[$toggle_reports[$type]['opposite']]['label'] ?>
                    </option>
                </select>
                <script>
                document.getElementById('reportModeToggle').addEventListener('change', function() {
                    const nextKey = this.value;
                    const url = new URL(window.location.href);
                    url.pathname = url.pathname.replace(/run_fmcg_report\/[^\/]+/, 'run_fmcg_report/' + nextKey);
                    window.location.href = url.toString();
                });
                </script>
            </div>
            <?php endif; ?>

            <!-- Preserve other GET params as hidden fields in filter form -->
            <?php foreach ($_GET as $gk => $gv): ?>
                <?php if (!in_array($gk, ['from', 'to', 'cashier_id', 'location_id', 'export'])): ?>
                    <input type="hidden" name="<?= htmlspecialchars($gk) ?>" value="<?= htmlspecialchars($gv) ?>">
                <?php endif; ?>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm font-weight-bold ml-auto-md" style="border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                Apply Filter
            </button>
        </form>
    </div>
</div>

<!-- Premium Aggregated Summary Cards -->
<?php if (!empty($numeric_cols) && !empty($rows)): ?>
<div class="row mb-4 no-print fmcg-summary-cards">
    <?php foreach ($numeric_cols as $col_idx => $meta): 
        if ($col_sums[$col_idx] == 0 && ($meta['name'] == 'Rank' || $meta['name'] == 'ID')) continue;
        
        $card_class = '';
        if (strpos(strtolower($meta['name']), 'total') !== false || strpos(strtolower($meta['name']), 'net') !== false) {
            $card_class = 'border-primary text-primary';
        } elseif (strpos(strtolower($meta['name']), 'due') !== false || strpos(strtolower($meta['name']), 'shortage') !== false || strpos(strtolower($meta['name']), 'refund') !== false) {
            $card_class = 'border-danger text-danger';
        } elseif (strpos(strtolower($meta['name']), 'paid') !== false || strpos(strtolower($meta['name']), 'excess') !== false || strpos(strtolower($meta['name']), 'collected') !== false) {
            $card_class = 'border-success text-success';
        }
    ?>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-left-3 <?= $card_class ?> shadow-sm h-100" style="border-radius: 8px;">
            <div class="card-body py-2 px-3">
                <small class="text-muted font-weight-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total <?= htmlspecialchars($meta['name']) ?></small>
                <h5 class="mb-0 font-weight-bold mt-1">
                    <?= $meta['is_currency'] ? '₹ ' . number_format($col_sums[$col_idx], 2) : number_format($col_sums[$col_idx]) ?>
                </h5>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Item-wise Summary — aggregated across the whole period, one row per item
     (no Bill No/Date, unlike the line-by-line detail table below) -->
<?php if (!empty($item_summary_rows)): ?>
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-white border-bottom py-2 px-4">
        <span class="font-weight-bold text-muted" style="font-size: 0.8rem; letter-spacing: 0.5px;">ITEM-WISE SUMMARY</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 hairline-table" style="font-size: 0.88rem; vertical-align: middle;">
                <thead class="bg-light text-secondary border-bottom">
                    <tr>
                        <?php foreach ($item_summary_headers as $header): ?>
                            <th class="font-weight-bold py-3 px-4 border-0"><?= htmlspecialchars($header) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($item_summary_rows as $row_data): ?>
                        <tr class="align-middle">
                            <?php foreach ($row_data as $cell): ?>
                                <td class="py-3 px-4 text-dark border-bottom-light"><?= htmlspecialchars((string)$cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Display Options Mode Switcher (Both / Summary Only / Details Only) -->
<?php if (!empty($numeric_cols) && !empty($rows)): ?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <span class="font-weight-bold text-muted" style="font-size: 0.8rem; letter-spacing: 0.5px;">DISPLAY OPTIONS</span>
    </div>
    <div class="btn-group btn-group-toggle shadow-sm" data-toggle="buttons" style="border-radius: 8px; overflow: hidden;">
        <label class="btn btn-xs btn-outline-primary px-3 active" id="btn-show-both" style="font-weight: 600;">
            <input type="radio" name="options" checked> Both
        </label>
        <label class="btn btn-xs btn-outline-primary px-3" id="btn-show-summary" style="font-weight: 600;">
            <input type="radio" name="options"> Summary Cards
        </label>
        <label class="btn btn-xs btn-outline-primary px-3" id="btn-show-details" style="font-weight: 600;">
            <input type="radio" name="options"> Detailed Table
        </label>
    </div>
</div>
<?php endif; ?>

<!-- Details Table Card -->
<div class="card border-0 shadow-sm mb-4" id="detailsTableCard" style="border-radius: 12px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 hairline-table" style="font-size: 0.88rem; vertical-align: middle;">
                <thead class="bg-light text-secondary border-bottom">
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <th class="font-weight-bold py-3 px-4 border-0"><?= htmlspecialchars($header) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $row_data):
                            // Detect grand total marker (either the '__GT__' prefix or a plain 'TOTAL' label —
                            // the latter is the same convention _export_excel()/_export_pdf() already recognize)
                            $row_label_check = isset($row_data[0]) ? strtoupper(trim((string)$row_data[0])) : '';
                            $is_grand = strpos($row_label_check, '__GT__') === 0 || $row_label_check === 'TOTAL';
                            $row_class = $is_grand ? 'table-active font-weight-bold' : 'align-middle';
                        ?>
                            <tr class="<?= $row_class ?>">
                                <?php foreach ($row_data as $index => $cell):
                                    $cell_str = trim((string)$cell);
                                    // Strip grand total marker from display
                                    if ($index === 0 && $is_grand) $cell_str = str_replace('__GT__', '', $cell_str);
                                ?>
                                    <td class="py-3 px-4 <?= $is_grand ? 'text-dark border-top-2' : 'text-dark border-bottom-light' ?> font-weight-<?= $is_grand ? 'bold' : 'medium' ?>">
                                        <?php
                                        if ($is_grand) {
                                            echo '<span style="' . ($index === 0 ? 'color:var(--ds-primary,#1e40af)' : '') . '">' . htmlspecialchars($cell_str) . '</span>';
                                        } elseif (strtoupper($cell_str) === 'OPEN') {
                                            echo '<span class="badge badge-success px-3 py-1 font-weight-bold text-uppercase" style="border-radius: 30px; font-size: 0.75rem;">Open</span>';
                                        } elseif (strtoupper($cell_str) === 'CLOSED') {
                                            echo '<span class="badge badge-secondary px-3 py-1 font-weight-bold text-uppercase" style="border-radius: 30px; font-size: 0.75rem;">Closed</span>';
                                        } elseif (strpos($cell_str, 'Shift #') === 0) {
                                            echo '<span class="font-weight-bold text-primary">' . htmlspecialchars($cell) . '</span>';
                                        } elseif (stripos($headers[$index] ?? '', 'refund') !== false && (float)str_replace(',', '', $cell_str) > 0) {
                                            echo '<span class="font-weight-bold text-danger">' . htmlspecialchars($cell_str) . '</span>';
                                        } else {
                                            echo htmlspecialchars($cell_str);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= count($headers) ?>" class="text-center text-muted py-5" style="background-color: #fafafa;">
                                <div class="py-4">
                                    <span style="font-size: 3rem; display: block;" class="mb-3">📭</span>
                                    <span class="font-weight-bold d-block text-muted">No records found for this period</span>
                                    <small class="text-muted">Adjust filters or select a different date range.</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .gap-2 { gap: 0.5rem; }
    .gap-3 { gap: 1rem; }
    .align-middle td { vertical-align: middle; }
    .border-bottom-light { border-bottom: 1px solid #f1f2f6; }
    .border-top-2 { border-top: 2px solid #1e40af !important; }
    .bg-light { background-color: #f8f9fa !important; }
    .border-left-3 { border-left: 3px solid #6c757d !important; }
    .border-left-3.border-primary { border-left-color: #007bff !important; }
    .border-left-3.border-danger { border-left-color: #dc3545 !important; }
    .border-left-3.border-success { border-left-color: #28a745 !important; }
    .hairline-table, .hairline-table th, .hairline-table td {
        border: 1px solid #e3e6ea !important;
        border-collapse: collapse;
    }
    .hairline-table thead th { border-color: #d7dbe0 !important; }
    @media (min-width: 768px) {
        .ml-auto-md { margin-left: auto !important; }
    }
    @media print {
        .no-print { display: none !important; }
        body { background: white !important; color: black !important; }
        .card { border: none !important; box-shadow: none !important; }
        .table { width: 100% !important; }
        .table th, .table td { padding: 8px !important; border-bottom: 1px solid #ccc !important; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const summaryCards = document.querySelector('.fmcg-summary-cards');
    const detailsTable = document.getElementById('detailsTableCard');
    
    const btnBoth = document.getElementById('btn-show-both');
    const btnSummary = document.getElementById('btn-show-summary');
    const btnDetails = document.getElementById('btn-show-details');
    
    if (btnBoth && btnSummary && btnDetails) {
        btnBoth.addEventListener('click', function() {
            if (summaryCards) summaryCards.classList.remove('d-none');
            if (detailsTable) detailsTable.classList.remove('d-none');
        });
        btnSummary.addEventListener('click', function() {
            if (summaryCards) summaryCards.classList.remove('d-none');
            if (detailsTable) detailsTable.classList.add('d-none');
        });
        btnDetails.addEventListener('click', function() {
            if (summaryCards) summaryCards.classList.add('d-none');
            if (detailsTable) detailsTable.classList.remove('d-none');
        });
    }
});
</script>
