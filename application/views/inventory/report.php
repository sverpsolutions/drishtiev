<?php defined('BASEPATH') OR exit('No direct script access allowed');
$co_name    = $company['company_name']    ?? 'Company';
$co_address = $company['company_address'] ?? '';
$co_phone   = $company['company_phone']   ?? '';
$co_gstin   = $company['company_gstin']   ?? '';
?>

<style>
#inv-report-table th, #inv-report-table td {
    white-space: nowrap; font-size: .78rem; padding: 4px 8px; vertical-align: middle;
}
#inv-report-table thead tr:first-child th {
    background: #1e3a8a; color: #fff; position: sticky; top: 0; z-index: 2;
}
#inv-report-table thead tr:last-child th {
    background: #1e40af; color: #dbeafe; position: sticky; top: 33px; z-index: 2;
}
#inv-report-table tbody tr:hover { background: #eff6ff; }
.loc-qty-zero { color: #ef4444; font-weight: 700; }
.loc-qty-pos  { color: #16a34a; font-weight: 700; }
.total-col    { background: #f0fdf4; font-weight: 700; color: #15803d; }
.tfoot-row td { background: #1e3a8a !important; color: #fff !important; font-weight: 700; font-size: .8rem; }

@media print {
    .no-print, nav, .navbar, .sidebar, aside, header, .topbar { display: none !important; }
    body, html { margin: 0 !important; padding: 0 !important; }
    .content-wrapper, .main-content, .container-fluid,
    .card, .card-body { margin: 0 !important; padding: 0 !important;
        border: none !important; box-shadow: none !important; }
    .table-responsive { overflow: visible !important; max-height: none !important; }
    #printHeader { display: block !important; }
    #inv-report-table { width: 100%; border-collapse: collapse; font-size: 7pt; }
    #inv-report-table th, #inv-report-table td { padding: 2px 4px; font-size: 7pt; border: 1px solid #999; }
    #inv-report-table thead tr:first-child th, #inv-report-table thead tr:last-child th {
        background: #1e3a8a !important; color: #fff !important; position: static !important;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .category-header td {
        background: #dbeafe !important; color: #1e40af !important;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .tfoot-row td {
        background: #1e3a8a !important; color: #fff !important;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .total-col {
        background: #dcfce7 !important;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    tr { page-break-inside: avoid; }
    @page { margin: 10mm 8mm 12mm 8mm; size: A4 landscape; }
}
.category-header td {
    background: #e0e7ff !important;
    color: #1e3a8a;
    font-weight: 700;
    font-size: .75rem;
    padding: 3px 8px !important;
    border-top: 2px solid #c7d2fe;
}
</style>

<!-- Print Header (hidden on screen) -->
<div id="printHeader" style="display:none; font-family: Arial, sans-serif; margin-bottom: 8px;">
    <table style="width:100%; border-bottom:2px solid #1e3a8a; padding-bottom:6px; margin-bottom:6px;">
        <tr>
            <td style="width:70%;">
                <div style="font-size:13pt;font-weight:700;color:#1e3a8a;"><?= htmlspecialchars($co_name) ?></div>
                <?php if ($co_address): ?><div style="font-size:8pt;color:#444;"><?= htmlspecialchars($co_address) ?></div><?php endif; ?>
                <?php if ($co_phone): ?><div style="font-size:8pt;color:#444;">Ph: <?= htmlspecialchars($co_phone) ?><?= $co_gstin?' &nbsp;|&nbsp; GSTIN: '.$co_gstin:'' ?></div><?php endif; ?>
            </td>
            <td style="width:30%;text-align:right;vertical-align:top;">
                <div style="font-size:11pt;font-weight:700;">LOCATION-WISE INVENTORY REPORT</div>
                <div style="font-size:8pt;color:#444;">Date: <?= date('d M Y') ?> &nbsp;|&nbsp; Time: <?= date('h:i A') ?></div>
            </td>
        </tr>
    </table>
    <div id="printKpi" style="display:flex;border:1px solid #ccc;padding:4px 8px;margin-bottom:6px;font-size:8pt;gap:20px;">
        <span><b>Items:</b> <span id="pkTotal">—</span></span>
        <span><b>Out of Stock:</b> <span id="pkOut" style="color:#ef4444;">—</span></span>
        <span><b>Total Units:</b> <span id="pkQty">—</span></span>
        <span><b>Stock Value:</b> <span id="pkVal">—</span></span>
        <span><b>MRP Value:</b> <span id="pkMrp">—</span></span>
    </div>
</div>

<!-- Page header -->
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h5 class="mb-0 font-weight-bold">Inventory Report — Location Wise</h5>
        <small class="text-muted">Stock quantity across all locations</small>
    </div>
    <div class="d-flex" style="gap:8px;">
        <button class="btn btn-sm btn-outline-secondary" onclick="printAll()">
            <i class="fas fa-print"></i> Print All
        </button>
        <button class="btn btn-sm btn-outline-info" onclick="printFiltered()">
            <i class="fas fa-filter"></i> Print Filtered
        </button>
        <button class="btn btn-sm btn-outline-success" id="btnExcel">
            <i class="fas fa-file-excel"></i> Export Excel
        </button>
        <a href="<?= base_url('inventory') ?>" class="btn btn-sm btn-outline-dark">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>


<!-- Filters -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <div class="row align-items-center" style="gap:0;">
            <div class="col-md-3">
                <input type="text" id="rptSearch" class="form-control form-control-sm"
                       placeholder="Search code, name, brand, category…" autofocus>
            </div>
            <div class="col-md-2">
                <select id="rptCategory" class="form-control form-control-sm">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $c): ?>
                    <option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="rptBrand" class="form-control form-control-sm">
                    <option value="">All Brands</option>
                    <?php foreach($brands as $b): ?>
                    <option value="<?= $b->id ?>"><?= htmlspecialchars($b->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="rptStock" class="form-control form-control-sm">
                    <option value="">All Stock</option>
                    <option value="out">Out of Stock</option>
                    <option value="low">Low Stock</option>
                    <option value="in">In Stock</option>
                </select>
            </div>
            <div class="col-md-3 text-right">
                <span id="rptCount" class="badge badge-primary" style="font-size:.82rem;"></span>
                <button class="btn btn-xs btn-outline-secondary ml-2" onclick="clearFilters()">Clear</button>
            </div>
        </div>
    </div>
</div>

<!-- KPI strip -->
<div class="row mb-3 no-print" id="kpiStrip">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2">
            <div style="font-size:.75rem;color:#6b7280;">Total Products</div>
            <div style="font-size:1.2rem;font-weight:700;" id="kpiTotal">—</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2">
            <div style="font-size:.75rem;color:#6b7280;">Out of Stock</div>
            <div style="font-size:1.2rem;font-weight:700;color:#ef4444;" id="kpiOut">—</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2">
            <div style="font-size:.75rem;color:#6b7280;">Total Stock Value</div>
            <div style="font-size:1.2rem;font-weight:700;color:#1e40af;" id="kpiValue">—</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2">
            <div style="font-size:.75rem;color:#6b7280;">Total MRP Value</div>
            <div style="font-size:1.2rem;font-weight:700;color:#16a34a;" id="kpiMrp">—</div>
        </div>
    </div>
</div>

<!-- Report table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:68vh;overflow:auto;">
            <table class="table table-bordered mb-0" id="inv-report-table">
                <thead>
                    <!-- Location group header row -->
                    <tr>
                        <th rowspan="2" style="vertical-align:middle;">#</th>
                        <th rowspan="2" style="vertical-align:middle;">Item Code</th>
                        <th rowspan="2" style="vertical-align:middle;">Product Name</th>
                        <th rowspan="2" style="vertical-align:middle;">Brand</th>
                        <th rowspan="2" style="vertical-align:middle;">Category</th>
                        <th rowspan="2" style="vertical-align:middle;text-align:center;">Unit</th>
                        <th rowspan="2" style="vertical-align:middle;text-align:right;">Basic ₹</th>
                        <th rowspan="2" style="vertical-align:middle;text-align:right;">MRP ₹</th>
                        <th rowspan="2" style="vertical-align:middle;text-align:right;">SP ₹</th>
                        <?php foreach($locations as $loc): ?>
                        <th colspan="1" style="text-align:center;background:#1e3a8a;border-left:2px solid #3b82f6;">
                            <?= htmlspecialchars($loc->name) ?>
                        </th>
                        <?php endforeach; ?>
                        <th rowspan="2" style="vertical-align:middle;text-align:right;background:#14532d;color:#bbf7d0;border-left:2px solid #22c55e;">
                            Total Qty
                        </th>
                        <th rowspan="2" style="vertical-align:middle;text-align:right;background:#14532d;color:#bbf7d0;">
                            Stock Value
                        </th>
                    </tr>
                    <tr>
                        <?php foreach($locations as $loc): ?>
                        <th style="text-align:center;background:#1e40af;border-left:2px solid #3b82f6;">Qty</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody id="rptBody">
                <?php
                $prev_cat = null;
                $row_num  = 0;
                foreach ($products as $p):
                    $row_num++;
                    $total_qty = (float)$p->total_qty;
                    $stock_value = $total_qty * (float)$p->purchase_price;

                    // Category separator row
                    if ($p->category_name !== $prev_cat):
                        $prev_cat = $p->category_name;
                        $col_count = 10 + count($locations) + 2;
                ?>
                <tr class="category-header" data-cat-header="1">
                    <td colspan="<?= $col_count ?>">
                        <i class="fas fa-tag"></i> <?= htmlspecialchars($p->category_name ?: 'Uncategorized') ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr data-name="<?= strtolower($p->name) ?>"
                    data-code="<?= strtolower($p->item_code ?? '') ?>"
                    data-brand="<?= strtolower($p->brand_name) ?>"
                    data-cat="<?= strtolower($p->category_name) ?>"
                    data-cat-id="<?= $p->category_id ?? '' ?>"
                    data-brand-id="<?= $p->brand_id ?? '' ?>"
                    data-total="<?= $total_qty ?>"
                    data-value="<?= $stock_value ?>"
                    data-mrp-value="<?= $total_qty * (float)$p->mrp ?>">
                    <td><?= $row_num ?></td>
                    <td><code style="font-size:.72rem;"><?= htmlspecialchars($p->item_code ?? '—') ?></code></td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($p->name) ?>">
                        <strong><?= htmlspecialchars($p->name) ?></strong>
                        <?php if ($p->barcode): ?>
                        <br><small class="text-muted" style="font-size:.68rem;"><?= htmlspecialchars($p->barcode) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p->brand_name ?: '—') ?></td>
                    <td><?= htmlspecialchars($p->category_name ?: '—') ?></td>
                    <td class="text-center"><?= htmlspecialchars($p->unit) ?></td>
                    <td class="text-right">₹<?= number_format($p->purchase_price, 2) ?></td>
                    <td class="text-right">₹<?= number_format($p->mrp, 2) ?></td>
                    <td class="text-right">₹<?= number_format($p->selling_price, 2) ?></td>
                    <?php foreach($locations as $loc):
                        $loc_qty = (float)($p->{'loc_'.$loc->id} ?? 0);
                    ?>
                    <td class="text-center <?= $loc_qty <= 0 ? 'loc-qty-zero' : 'loc-qty-pos' ?>"
                        style="border-left:2px solid #dbeafe;">
                        <?= $loc_qty > 0 ? number_format($loc_qty, 0) : ($loc_qty < 0 ? '<span style="color:#dc2626">'.number_format($loc_qty,0).'</span>' : '—') ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="text-right total-col" style="border-left:2px solid #22c55e;">
                        <?php
                        $cls = $total_qty <= 0 ? 'color:#ef4444;' : 'color:#16a34a;';
                        echo '<span style="'.$cls.'">' . number_format($total_qty, 0) . '</span>';
                        ?>
                    </td>
                    <td class="text-right total-col">
                        ₹<?= number_format($stock_value, 2) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="tfoot-row" id="rptFooter">
                        <td colspan="9" class="text-right">TOTAL</td>
                        <?php foreach($locations as $loc): ?>
                        <td class="text-center" id="foot_loc_<?= $loc->id ?>">—</td>
                        <?php endforeach; ?>
                        <td class="text-right" id="foot_total_qty">—</td>
                        <td class="text-right" id="foot_total_val">—</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php
$total_rows   = $total_rows ?? 0;
$current_page = $current_page ?? 1;
$per_page     = $per_page ?? 500;
$total_pages  = $total_rows > 0 ? (int)ceil($total_rows / $per_page) : 1;
$row_offset   = ($current_page - 1) * $per_page;
if ($total_pages > 1):
?>
<nav class="d-flex justify-content-between align-items-center mt-3 mb-3 no-print">
    <small class="text-muted">
        Showing <?= number_format($row_offset + 1) ?>–<?= number_format(min($row_offset + $per_page, $total_rows)) ?>
        of <?= number_format($total_rows) ?> products
    </small>
    <ul class="pagination pagination-sm mb-0">
        <?php if ($current_page > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('inventory/report?page=' . ($current_page - 1)) ?>">&laquo; Prev</a></li>
        <?php endif; ?>
        <?php
        $start_p = max(1, $current_page - 3);
        $end_p   = min($total_pages, $current_page + 3);
        if ($start_p > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('inventory/report?page=1') ?>">1</a></li>
        <?php if ($start_p > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif;
        for ($pg = $start_p; $pg <= $end_p; $pg++): ?>
        <li class="page-item <?= $pg == $current_page ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('inventory/report?page=' . $pg) ?>"><?= $pg ?></a></li>
        <?php endfor;
        if ($end_p < $total_pages): ?>
        <?php if ($end_p < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('inventory/report?page=' . $total_pages) ?>"><?= $total_pages ?></a></li>
        <?php endif; ?>
        <?php if ($current_page < $total_pages): ?>
        <li class="page-item"><a class="page-link" href="<?= base_url('inventory/report?page=' . ($current_page + 1)) ?>">Next &raquo;</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<script>
// ── Locations data for footer totals
const LOCATIONS = <?= json_encode(array_map(function($l){ return ['id'=>$l->id,'name'=>$l->name]; }, $locations)) ?>;

// ── Filter & search
function applyFilters() {
// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
    const q       = document.getElementById('rptSearch').value.toLowerCase().trim();
    const catId   = document.getElementById('rptCategory').value;
    const brandId = document.getElementById('rptBrand').value;
    const stock   = document.getElementById('rptStock').value;

    const rows = document.querySelectorAll('#rptBody tr:not([data-cat-header])');
    let visible = 0;
    let prevCatEl = null;
    let prevCatItems = 0;

    // hide all category headers first
    document.querySelectorAll('#rptBody tr[data-cat-header]').forEach(r => r.style.display = 'none');

    // track category→header mapping
    const catMap = {};
    let lastCatHeader = null;
    document.querySelectorAll('#rptBody tr').forEach(r => {
        if (r.dataset.catHeader) { lastCatHeader = r; }
        else if (lastCatHeader)  { catMap[r] = lastCatHeader; }
    });

    const shownCats = new Set();
    let totalQty = 0, totalVal = 0, outCount = 0;
    const locTotals = {};
    LOCATIONS.forEach(l => locTotals[l.id] = 0);

    rows.forEach(r => {
        const nameMatch  = !q || pmatch(q, r.dataset.name, r.dataset.code, r.dataset.brand, r.dataset.cat);
        const catMatch   = !catId  || r.dataset.catId  === catId;
        const brandMatch = !brandId || r.dataset.brandId === brandId;
        const qty        = parseFloat(r.dataset.total || 0);
        const stockMatch = !stock ||
            (stock === 'out' && qty <= 0) ||
            (stock === 'low' && qty > 0 && qty <= 10) ||
            (stock === 'in'  && qty > 10);

        const show = nameMatch && catMatch && brandMatch && stockMatch;
        r.style.display = show ? '' : 'none';

        if (show) {
            visible++;
            totalQty += qty;
            totalVal += parseFloat(r.dataset.value || 0);
            if (qty <= 0) outCount++;
            LOCATIONS.forEach(l => {
                const cell = r.querySelector('td[data-loc="' + l.id + '"]');
                const locQty = cell ? parseFloat(cell.dataset.qty || 0) : 0;
                locTotals[l.id] += locQty;
            });
            // show this row's category header
            if (catMap[r]) shownCats.add(catMap[r]);
        }
    });

    shownCats.forEach(el => el.style.display = '');

    // Update KPIs
    document.getElementById('rptCount').textContent = visible + ' items';
    document.getElementById('kpiTotal').textContent  = visible;
    document.getElementById('kpiOut').textContent    = outCount;
    document.getElementById('kpiValue').textContent  = '₹' + totalVal.toLocaleString('en-IN', {minimumFractionDigits:2});

    // MRP value — iterate visible rows
    let mrpVal = 0;
    rows.forEach(r => { if (r.style.display !== 'none') mrpVal += parseFloat(r.dataset.mrpValue || 0); });
    document.getElementById('kpiMrp').textContent = '₹' + mrpVal.toLocaleString('en-IN', {minimumFractionDigits:2});

    // Footer
    document.getElementById('foot_total_qty').textContent = totalQty.toLocaleString('en-IN');
    document.getElementById('foot_total_val').textContent = '₹' + totalVal.toLocaleString('en-IN', {minimumFractionDigits:2});
    LOCATIONS.forEach(l => {
        const el = document.getElementById('foot_loc_' + l.id);
        if (el) el.textContent = locTotals[l.id].toLocaleString('en-IN');
    });
}

// Attach location data attributes to cells for footer calc
document.querySelectorAll('#rptBody tr:not([data-cat-header])').forEach(row => {
    const cells = row.querySelectorAll('td');
    // location cells start at index 9 (after #,code,name,brand,cat,unit,basic,mrp,sp)
    LOCATIONS.forEach((l, i) => {
        const cell = cells[9 + i];
        if (cell) {
            cell.dataset.loc = l.id;
            const txt = cell.textContent.replace(/[^0-9.\-]/g, '');
            cell.dataset.qty = txt || '0';
        }
    });
});

let _debounce;
document.getElementById('rptSearch').addEventListener('input', function() {
    clearTimeout(_debounce); _debounce = setTimeout(applyFilters, 200);
});
document.getElementById('rptCategory').addEventListener('change', applyFilters);
document.getElementById('rptBrand').addEventListener('change', applyFilters);
document.getElementById('rptStock').addEventListener('change', applyFilters);

function clearFilters() {
    document.getElementById('rptSearch').value = '';
    document.getElementById('rptCategory').value = '';
    document.getElementById('rptBrand').value = '';
    document.getElementById('rptStock').value = '';
    applyFilters();
}

// ── Excel export
document.getElementById('btnExcel').addEventListener('click', function() {
    const rows = [['#','Item Code','Product Name','Brand','Category','Unit','Basic ₹','MRP ₹','SP ₹']
                  .concat(LOCATIONS.map(l => l.name))
                  .concat(['Total Qty','Stock Value'])];

    document.querySelectorAll('#rptBody tr:not([data-cat-header])').forEach(r => {
        if (r.style.display === 'none') return;
        const cells = r.querySelectorAll('td');
        const row = [];
        cells.forEach((c, i) => row.push(c.textContent.trim().replace(/\s+/g,' ')));
        rows.push(row);
    });

    // Add totals row
    const foot = document.querySelector('#rptFooter');
    if (foot) {
        const ftRow = ['','','','','','','','','Total'];
        LOCATIONS.forEach(l => ftRow.push(document.getElementById('foot_loc_'+l.id)?.textContent || ''));
        ftRow.push(document.getElementById('foot_total_qty')?.textContent || '');
        ftRow.push(document.getElementById('foot_total_val')?.textContent || '');
        rows.push(ftRow);
    }

    let csv = rows.map(r => r.map(c => '"' + String(c).replace(/"/g,'""') + '"').join(',')).join('\n');
    const blob = new Blob(['﻿' + csv], {type:'text/csv;charset=utf-8'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'Inventory_Report_<?= date('Ymd') ?>.csv';
    a.click();
});

// ── Print helpers
function _syncPrintKpi() {
    let total=0, outC=0, qty=0, val=0, mrp=0;
    document.querySelectorAll('#rptBody tr:not([data-cat-header])').forEach(r => {
        if (r.style.display === 'none') return;
        total++;
        const q = parseFloat(r.dataset.total||0);
        if (q <= 0) outC++;
        qty += q;
        val += parseFloat(r.dataset.value||0);
        mrp += parseFloat(r.dataset.mrpValue||0);
    });
    document.getElementById('pkTotal').textContent = total;
    document.getElementById('pkOut').textContent   = outC;
    document.getElementById('pkQty').textContent   = qty.toLocaleString('en-IN');
    document.getElementById('pkVal').textContent   = '₹'+val.toLocaleString('en-IN',{minimumFractionDigits:2});
    document.getElementById('pkMrp').textContent   = '₹'+mrp.toLocaleString('en-IN',{minimumFractionDigits:2});
}

function printFiltered() {
    _syncPrintKpi();
    window.print();
}

function printAll() {
    const rows = document.querySelectorAll('#rptBody tr');
    const wasHidden = [];
    rows.forEach(r => { if (r.style.display==='none') { wasHidden.push(r); r.style.display=''; } });
    _syncPrintKpi();
    window.print();
    wasHidden.forEach(r => r.style.display = 'none');
    applyFilters();
}

// Init
applyFilters();
</script>
