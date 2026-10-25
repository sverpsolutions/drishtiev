<?php // Store-wise Media Report ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold">Store-wise Media Report</h5>
        <small class="text-muted">Payment media (Cash/UPI/Card) breakdown per store/location</small>
    </div>
    <div>
        <?php $export_qs = "?from={$from}&to={$to}&location_id=".(isset($_GET['location_id']) ? $_GET['location_id'] : ''); ?>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm shadow-sm"><i class="fas fa-print"></i> Print</button>
        <a href="<?= base_url("reports/media_report{$export_qs}&export=pdf") ?>" class="btn btn-outline-danger btn-sm shadow-sm"><i class="fas fa-file-pdf"></i> PDF</a>
        <a href="<?= base_url("reports/media_report{$export_qs}&export=excel") ?>" class="btn btn-outline-success btn-sm shadow-sm"><i class="fas fa-file-excel"></i> Excel</a>
        <button data-toggle="modal" data-target="#emailModal" class="btn btn-outline-info btn-sm shadow-sm"><i class="fas fa-envelope"></i> Email</button>
        <a href="<?= base_url('reports') ?>" class="btn btn-outline-secondary btn-sm ml-2">← Reports</a>
    </div>
</div>

<!-- Filter -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end g-2">
            <div class="col-md-3">
                <label class="small">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
            </div>
            <div class="col-md-3">
                <label class="small">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
            </div>
            <?php if (!empty($locations)): ?>
            <div class="col-md-3">
                <label class="small">Location</label>
                <select name="location_id" class="form-control form-control-sm" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
                    <?php if (count($locations) > 1): ?>
                    <option value="">All Locations</option>
                    <?php endif; ?>
                    <?php foreach ($locations as $l): ?>
                    <option value="<?= $l->id ?>" <?= (isset($_GET['location_id']) && $_GET['location_id']==$l->id)?'selected':'' ?>><?= htmlspecialchars($l->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm btn-block">Filter</button>
            </div>
        </form>
    </div>
</div>

<?php
// Group by location
$by_location = [];
foreach ($media_summary as $row) {
    $loc = $row->location_name ?: 'Main Store';
    if (!isset($by_location[$loc])) $by_location[$loc] = [];
    $by_location[$loc][] = $row;
}
$modes = ['cash'=>'💵 Cash','upi'=>'⚡ UPI','card'=>'💳 Card','neft'=>'🏛️ NEFT','cheque'=>'📄 Cheque','wallet'=>'📱 Wallet','credit'=>'📋 Credit'];
?>

<?php foreach ($by_location as $locName => $rows): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-dark text-white font-weight-bold">
        <i class="fas fa-store mr-2"></i><?= htmlspecialchars($locName) ?>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr><th>Payment Mode</th><th class="text-right">Bills</th><th class="text-right">Amount (₹)</th><th class="text-right">% Share</th></tr>
            </thead>
            <tbody>
            <?php
            $loc_total = array_sum(array_column($rows, 'total_amount'));
            foreach ($rows as $r):
                $pct = $loc_total > 0 ? round($r->total_amount/$loc_total*100,1) : 0;
                $mode_label = $modes[$r->payment_mode] ?? ucfirst($r->payment_mode);
            ?>
            <tr>
                <td><?= $mode_label ?></td>
                <td class="text-right"><?= number_format($r->bill_count) ?></td>
                <td class="text-right font-weight-bold">₹<?= number_format($r->total_amount, 2) ?></td>
                <td class="text-right">
                    <div class="progress" style="height:14px;">
                        <div class="progress-bar bg-success" style="width:<?= $pct ?>%"><?= $pct ?>%</div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="font-weight-bold bg-light">
                <tr>
                    <td>Total</td>
                    <td class="text-right"><?= array_sum(array_column($rows,'bill_count')) ?></td>
                    <td class="text-right">₹<?= number_format($loc_total,2) ?></td>
                    <td class="text-right">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endforeach; ?>

<!-- Grand Total -->
<?php if (!empty($totals)): ?>
<div class="card shadow-sm border-primary">
    <div class="card-header bg-primary text-white font-weight-bold">Grand Total — All Locations</div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0">
            <thead class="thead-light"><tr><th>Mode</th><th class="text-right">Bills</th><th class="text-right">Amount</th></tr></thead>
            <tbody>
            <?php $grand = array_sum(array_column($totals, 'total_amount')); ?>
            <?php foreach ($totals as $t): ?>
            <tr>
                <td><?= $modes[$t->payment_mode] ?? ucfirst($t->payment_mode) ?></td>
                <td class="text-right"><?= number_format($t->bill_count) ?></td>
                <td class="text-right font-weight-bold">₹<?= number_format($t->total_amount,2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="font-weight-bold bg-light">
                <tr><td>Grand Total</td><td class="text-right"><?= array_sum(array_column($totals,'bill_count')) ?></td><td class="text-right text-primary">₹<?= number_format($grand,2) ?></td></tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= base_url("reports/email_media_report") ?>" method="post" class="modal-content">
            <input type="hidden" name="from" value="<?= $from ?>">
            <input type="hidden" name="to" value="<?= $to ?>">
            <input type="hidden" name="location_id" value="<?= isset($_GET['location_id']) ? $_GET['location_id'] : '' ?>">
            
            <div class="modal-header">
                <h5 class="modal-title">Email Store-wise Media Report</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>To Email <span class="text-danger">*</span></label>
                    <input type="email" name="to_email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>CC Email</label>
                    <input type="email" name="cc_email" class="form-control">
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="email_subject" class="form-control" value="Store-wise Media Report (<?= $from ?> to <?= $to ?>)">
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="email_message" class="form-control" rows="3">Please find attached the Store-wise Media Report.</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Email</button>
            </div>
        </form>
    </div>
</div>

