<?php // FILE: application/views/reports/gst2b_reconciliation.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-balance-scale text-danger mr-1"></i> GSTR-2B Reconciliation</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / GSTR-2B Reconciliation</small>
    </div>
    <?php if (!empty($upload)): ?>
    <div class="d-flex no-print" style="gap:5px;">
        <a href="<?= base_url('reports/gst2b_reconciliation?period='.$period.'&export=excel') ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel mr-1"></i> Excel</a>
        <a href="<?= base_url('reports/gst2b_reconciliation?period='.$period.'&export=pdf') ?>" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf mr-1"></i> PDF</a>
    </div>
    <?php endif; ?>
</div>

<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?= $this->session->flashdata('success') ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div><?php endif; ?>

<div class="alert alert-info py-2 mb-3" style="font-size:.85rem;">
    Matches your Purchases against the vendor-filed GSTR-2B (downloaded from the GST portal) by supplier GSTIN + invoice number.
    An invoice you recorded that the vendor never filed is flagged <strong>Not Filed</strong> — the supplier is also flagged on the Supplier Master until you clear it.
</div>

<!-- Upload -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <?= form_open_multipart('reports/gst2b_reconciliation') ?>
        <div class="form-row align-items-end">
            <div class="col-md-6">
                <label class="small font-weight-bold mb-1">Upload GSTR-2B JSON (from the GST portal)</label>
                <input type="file" name="gst2b_file" accept=".json,application/json" class="form-control-file" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-dark btn-sm"><i class="fas fa-upload mr-1"></i> Upload</button>
            </div>
            <div class="col-md-3 ml-auto">
                <label class="small font-weight-bold mb-1">View Period</label>
                <select name="period" class="form-control form-control-sm" onchange="window.location='<?= base_url('reports/gst2b_reconciliation') ?>?period='+this.value">
                    <?php if (empty($uploads)): ?>
                    <option value="">No uploads yet</option>
                    <?php else: foreach ($uploads as $u): ?>
                    <option value="<?= $u->period ?>" <?= $u->period===$period?'selected':'' ?>><?= date('F Y', strtotime($u->period.'-01')) ?> (<?= $u->record_count ?> invoices)</option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<?php if (empty($upload)): ?>
<div class="alert alert-light border text-muted text-center py-4">
    <i class="fas fa-file-upload mr-1"></i> No GSTR-2B uploaded for <?= date('F Y', strtotime($period.'-01')) ?> yet. Upload the JSON file downloaded from the GST portal above.
</div>
<?php else: ?>

<!-- Summary -->
<div class="row mb-3">
    <?php $cards = [
        ['label'=>'Matched',      'val'=>$summary['matched'],      'cls'=>'text-success'],
        ['label'=>'Mismatch',     'val'=>$summary['mismatch'],     'cls'=>'text-warning'],
        ['label'=>'Not Filed',    'val'=>$summary['not_filed'],    'cls'=>'text-danger font-weight-bold'],
        ['label'=>'Extra in 2B',  'val'=>$summary['extra_in_2b'],  'cls'=>'text-muted'],
    ]; foreach ($cards as $c): ?>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <p class="text-muted mb-0" style="font-size:.75rem;"><?= $c['label'] ?></p>
                <p class="mb-0 <?= $c['cls'] ?>" style="font-size:1.2rem;font-weight:600;"><?= $c['val'] ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php if ($summary['amount_at_risk'] > 0): ?>
<div class="alert alert-danger py-2 mb-3" style="font-size:.9rem;">
    <i class="fas fa-exclamation-triangle mr-1"></i> <strong>₹<?= number_format($summary['amount_at_risk'],2) ?></strong> in purchase invoices this period are not confirmed as filed by the vendor.
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Status</th><th>Supplier</th><th>GSTIN</th>
                        <th>Our Invoice</th><th>Date</th><th class="text-right">Our Total</th>
                        <th>Filed Invoice</th><th class="text-right">Filed Taxable+GST</th><th>Filed On</th>
                        <th class="no-print"></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $badge_map = ['matched'=>'success','mismatch'=>'warning','not_filed'=>'danger','extra_in_2b'=>'secondary'];
                $row_cls_map = ['matched'=>'', 'mismatch'=>'table-warning', 'not_filed'=>'table-danger', 'extra_in_2b'=>''];
                ?>
                <?php if (!empty($rows)): foreach ($rows as $r): ?>
                <tr class="<?= $row_cls_map[$r['status']] ?>">
                    <td><span class="badge badge-<?= $badge_map[$r['status']] ?>"><?= strtoupper(str_replace('_',' ',$r['status'])) ?></span></td>
                    <td><?= htmlspecialchars($r['supplier_name']) ?></td>
                    <td><small><?= htmlspecialchars($r['gstin']) ?></small></td>
                    <td><?= htmlspecialchars($r['our_invoice_no']) ?></td>
                    <td><?= $r['our_date'] ? date('d-M-Y', strtotime($r['our_date'])) : '—' ?></td>
                    <td class="text-right"><?= $r['our_total']!==null ? '₹'.number_format($r['our_total'],2) : '—' ?></td>
                    <td><?= htmlspecialchars($r['filed_invoice_no']) ?: '—' ?></td>
                    <td class="text-right"><?= $r['filed_taxable']!==null ? '₹'.number_format($r['filed_taxable']+$r['filed_gst'],2) : '—' ?></td>
                    <td><?= $r['supfildt'] ? date('d-M-Y', strtotime($r['supfildt'])) : '—' ?></td>
                    <td class="no-print">
                        <?php if ($r['supplier_id'] && in_array($r['status'], ['mismatch','not_filed'])): ?>
                        <a href="<?= base_url('reports/gst2b_clear_flag/'.$r['supplier_id']) ?>" class="btn btn-xs btn-outline-secondary" onclick="return confirm('Clear the GST non-compliance flag for '+<?= json_encode($r['supplier_name']) ?>+'? Do this only once the vendor has actually filed or you\'ve otherwise resolved it.')">Clear Flag</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No purchase invoices with a GSTIN'd supplier in this period.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<style>@media print { .no-print { display:none; } }</style>
