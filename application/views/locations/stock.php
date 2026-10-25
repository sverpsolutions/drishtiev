<?php defined('BASEPATH') OR exit('No direct script access allowed');
$co_name    = $company['company_name']    ?? 'Company';
$co_address = $company['company_address'] ?? '';
$co_phone   = $company['company_phone']   ?? '';
$co_gstin   = $company['company_gstin']   ?? '';
$k = $kpis ?? (object)['total'=>0,'out_count'=>0,'low_count'=>0,'ok_count'=>0,'total_qty'=>0,'total_val'=>0];
$total_pages = $per_page > 0 ? (int)ceil($total_rows / $per_page) : 1;
$base_qs = ['q'=>$search,'sf'=>$stock_filter,'cat'=>$category_filter];
?>

<style>
#locStockTable th, #locStockTable td {
    font-size: .8rem; padding: 4px 8px; vertical-align: middle; white-space: nowrap;
}
#locStockTable thead th {
    background: #1e3a8a; color: #fff; position: sticky; top: 0; z-index: 2;
}
#locStockTable tbody tr:hover { background: #eff6ff; }
.cat-header td {
    background: #e0e7ff !important; color: #1e3a8a;
    font-weight: 700; font-size: .74rem; padding: 3px 8px !important;
    border-top: 2px solid #c7d2fe;
}
.tfoot-row td { background: #1e3a8a !important; color: #fff !important; font-weight: 700; }
.page-link { font-size: .82rem; padding: 4px 10px; }

@media print {
    .no-print, nav, .navbar, .sidebar, #sidebar,
    aside, header, .main-header, .topbar,
    .breadcrumb, .alert, ul.nav { display: none !important; }
    body { margin: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
    #locStockTable th { background: #333 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    #printHeader { display: block !important; }
    tr { page-break-inside: avoid; }
    .cat-header { page-break-before: auto; }
    @page { margin: 12mm 10mm 15mm 10mm; size: A4 landscape; }
}
</style>

<!-- Print Header -->
<div id="printHeader" style="display:none; font-family: Arial, sans-serif; margin-bottom: 8px;">
    <table style="width:100%; border-bottom: 2px solid #1e3a8a; padding-bottom: 6px; margin-bottom: 6px;">
        <tr>
            <td style="width:70%;">
                <div style="font-size:13pt; font-weight:700; color:#1e3a8a;"><?= htmlspecialchars($co_name) ?></div>
                <?php if ($co_address): ?><div style="font-size:8pt; color:#444;"><?= htmlspecialchars($co_address) ?></div><?php endif; ?>
                <?php if ($co_phone): ?><div style="font-size:8pt; color:#444;">Ph: <?= htmlspecialchars($co_phone) ?><?= $co_gstin ? ' &nbsp;|&nbsp; GSTIN: '.$co_gstin : '' ?></div><?php endif; ?>
            </td>
            <td style="width:30%; text-align:right; vertical-align:top;">
                <div style="font-size:11pt; font-weight:700;">STOCK REPORT</div>
                <div style="font-size:8pt; color:#444;"><?= htmlspecialchars($current_location->name) ?></div>
                <div style="font-size:8pt; color:#444;">Date: <?= date('d M Y') ?> &nbsp;|&nbsp; Time: <?= date('h:i A') ?></div>
            </td>
        </tr>
    </table>
</div>

<!-- Screen Header -->
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h5 class="font-weight-bold mb-0">Location Stock</h5>
        <small class="text-muted">Current inventory per location</small>
    </div>
    <div class="d-flex" style="gap:8px;">
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
        <button class="btn btn-sm btn-outline-success" id="btnExcel">
            <i class="fas fa-file-excel"></i> Export
        </button>
        <a href="<?= base_url('stock_transfers/create?from=' . $current_location->id) ?>"
           class="btn btn-sm btn-outline-primary">
            <i class="fas fa-exchange-alt"></i> Transfer Stock
        </a>
    </div>
</div>

<!-- Location Tabs -->
<ul class="nav nav-pills mb-3 flex-wrap no-print">
<?php foreach ($locations as $loc): ?>
    <li class="nav-item mr-1 mb-1">
        <a class="nav-link py-1 px-3 <?= $loc->id == $current_location->id ? 'active' : '' ?>"
           href="<?= base_url('locations/stock/' . $loc->id) ?>" style="font-size:.83rem;">
            <span class="badge badge-<?= $loc->type === 'warehouse' ? 'primary' : 'success' ?> mr-1" style="font-size:.72rem;">
                <?= htmlspecialchars($loc->code) ?>
            </span>
            <?= htmlspecialchars($loc->name) ?>
        </a>
    </li>
<?php endforeach; ?>
</ul>

<!-- KPI Cards -->
<div class="row mb-3 no-print">
    <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="h5 font-weight-bold text-primary mb-0"><?= number_format($k->total) ?></div>
            <div class="text-muted" style="font-size:.72rem;">Total Products</div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="h5 font-weight-bold text-danger mb-0"><?= number_format($k->out_count) ?></div>
            <div class="text-muted" style="font-size:.72rem;">Out of Stock</div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="h5 font-weight-bold text-warning mb-0"><?= number_format($k->low_count) ?></div>
            <div class="text-muted" style="font-size:.72rem;">Low Stock</div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="h5 font-weight-bold text-success mb-0"><?= number_format($k->ok_count) ?></div>
            <div class="text-muted" style="font-size:.72rem;">In Stock OK</div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="h5 font-weight-bold mb-0"><?= number_format($k->total_qty, 0) ?></div>
            <div class="text-muted" style="font-size:.72rem;">Total Units</div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="h5 font-weight-bold text-success mb-0">₹<?= number_format($k->total_val, 0) ?></div>
            <div class="text-muted" style="font-size:.72rem;">Stock Value</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" action="<?= base_url('locations/stock/' . $current_location->id) ?>" id="filterForm">
        <div class="row align-items-center">
            <div class="col-md-4 mb-1">
                <input type="text" name="q" id="locSearch" class="form-control form-control-sm"
                       placeholder="Search name, item code, barcode…" value="<?= htmlspecialchars($search) ?>" autofocus>
            </div>
            <div class="col-md-2 mb-1">
                <select name="sf" id="locStockFilter" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Stock</option>
                    <option value="out" <?= $stock_filter==='out'?'selected':'' ?>>Out of Stock</option>
                    <option value="low" <?= $stock_filter==='low'?'selected':'' ?>>Low Stock</option>
                    <option value="ok" <?= $stock_filter==='ok'?'selected':'' ?>>In Stock</option>
                </select>
            </div>
            <div class="col-md-3 mb-1">
                <select name="cat" id="locCatFilter" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): if ($c->category_name === '') continue; ?>
                    <option value="<?= htmlspecialchars($c->category_name) ?>" <?= $category_filter===$c->category_name?'selected':'' ?>><?= htmlspecialchars($c->category_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 text-right mb-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Search</button>
                <span class="badge badge-primary ml-2" style="font-size:.82rem;"><?= number_format($total_rows) ?> items</span>
                <?php if ($search || $stock_filter || $category_filter): ?>
                <a href="<?= base_url('locations/stock/' . $current_location->id) ?>" class="btn btn-xs btn-outline-secondary ml-2">Clear</a>
                <?php endif; ?>
            </div>
        </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 no-print d-flex justify-content-between align-items-center">
        <strong style="font-size:.85rem;"><?= htmlspecialchars($current_location->name) ?> — Stock List</strong>
        <span class="text-muted" style="font-size:.78rem;">
            Page <?= $current_page ?> of <?= $total_pages ?> (<?= number_format($total_rows) ?> items)
        </span>
    </div>
    <div class="table-responsive" style="max-height:62vh;overflow:auto;">
        <table class="table table-bordered mb-0" id="locStockTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Code</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th class="text-center">Unit</th>
                    <th class="text-right">Basic ₹</th>
                    <th class="text-right">MRP ₹</th>
                    <th class="text-right">SP ₹</th>
                    <th class="text-right">In Stock</th>
                    <th class="text-right">Min</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody id="locBody">
            <?php
            $prev_cat = null; $row_num = ($current_page - 1) * $per_page;
            foreach ($stock as $p):
                $qty = (float)$p->stock_qty; $min = (float)$p->min_stock;
                if ($qty <= 0)        $status = 'out';
                elseif ($qty <= $min) $status = 'low';
                else                  $status = 'ok';

                if ($p->category_name !== $prev_cat):
                    $prev_cat = $p->category_name;
            ?>
            <tr class="cat-header">
                <td colspan="11"><i class="fas fa-tag mr-1"></i><?= htmlspecialchars($p->category_name ?: 'Uncategorized') ?></td>
            </tr>
            <?php endif; $row_num++; ?>
            <tr>
                <td><?= $row_num ?></td>
                <td><code style="font-size:.72rem;"><?= htmlspecialchars($p->item_code ?? '—') ?></code></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($p->name) ?>">
                    <?= htmlspecialchars($p->name) ?>
                    <?php if ($p->barcode): ?>
                    <br><small class="text-muted" style="font-size:.67rem;"><?= htmlspecialchars($p->barcode) ?></small>
                    <?php endif; ?>
                </td>
                <td style="font-size:.76rem;"><?= htmlspecialchars($p->category_name ?: '—') ?></td>
                <td class="text-center"><?= htmlspecialchars($p->unit) ?></td>
                <td class="text-right">₹<?= number_format($p->purchase_price, 2) ?></td>
                <td class="text-right">₹<?= number_format($p->mrp, 2) ?></td>
                <td class="text-right">₹<?= number_format($p->selling_price, 2) ?></td>
                <td class="text-right font-weight-bold <?= $qty<=0?'text-danger':($qty<=$min?'text-warning':'text-success') ?>">
                    <?= number_format($qty, 0) ?>
                </td>
                <td class="text-right text-muted"><?= number_format($min, 0) ?></td>
                <td class="text-center">
                    <?php if ($qty<=0): ?>
                        <span class="badge badge-danger" style="font-size:.7rem;">Out</span>
                    <?php elseif ($qty<=$min): ?>
                        <span class="badge badge-warning" style="font-size:.7rem;">Low</span>
                    <?php else: ?>
                        <span class="badge badge-success" style="font-size:.7rem;">OK</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav class="mt-3 no-print">
    <ul class="pagination justify-content-center flex-wrap">
        <?php
        $make_url = function($p) use ($current_location, $base_qs) {
            $params = array_filter($base_qs, fn($v) => $v !== '');
            if ($p > 1) $params['page'] = $p;
            $qs = $params ? '?' . http_build_query($params) : '';
            return base_url('locations/stock/' . $current_location->id) . $qs;
        };
        ?>
        <li class="page-item <?= $current_page<=1?'disabled':'' ?>">
            <a class="page-link" href="<?= $make_url($current_page-1) ?>">«</a>
        </li>
        <?php
        $start = max(1, $current_page - 3);
        $end   = min($total_pages, $current_page + 3);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= $make_url(1) ?>">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif;
        for ($pg = $start; $pg <= $end; $pg++): ?>
            <li class="page-item <?= $pg==$current_page?'active':'' ?>">
                <a class="page-link" href="<?= $make_url($pg) ?>"><?= $pg ?></a>
            </li>
        <?php endfor;
        if ($end < $total_pages): ?>
            <?php if ($end < $total_pages-1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= $make_url($total_pages) ?>"><?= $total_pages ?></a></li>
        <?php endif; ?>
        <li class="page-item <?= $current_page>=$total_pages?'disabled':'' ?>">
            <a class="page-link" href="<?= $make_url($current_page+1) ?>">»</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<script>
document.getElementById('locSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') document.getElementById('filterForm').submit();
});

document.getElementById('btnExcel').addEventListener('click', function() {
    const header = ['#','Item Code','Product Name','Category','Unit','Basic ₹','MRP ₹','SP ₹','In Stock','Min','Status'];
    const rows = [header];
    document.querySelectorAll('#locBody tr:not(.cat-header)').forEach(r => {
        rows.push(Array.from(r.querySelectorAll('td')).map(c => c.textContent.trim().replace(/\s+/g,' ')));
    });
    const csv = rows.map(r => r.map(c => '"'+String(c).replace(/"/g,'""')+'"').join(',')).join('\n');
    const blob = new Blob(['﻿' + csv], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'LocationStock_<?= date('Ymd') ?>.csv';
    a.click();
});
</script>
