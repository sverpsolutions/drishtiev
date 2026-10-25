<?php
/**
 * Shared export toolbar for all reports.
 * Required variables passed in $data:
 *   $export_route  — e.g. 'reports/sales'
 *   $export_params — query string params for from/to/etc (no leading ?)
 *   $export_title  — human-readable report name
 * Optional:
 *   $export_no_pdf — true to hide PDF button
 */
$__fp    = $this->session->userdata('form_perms') ?: [];
$__role  = $this->session->userdata('role');
$__is_sa = ($__role === 'superadmin');
$__full  = $__is_sa || in_array($__role, ['owner','admin']);
$can_export = $__full || !empty($__fp['frm_rpt_export']);
$can_email  = $__full || !empty($__fp['frm_rpt_email']);
$can_print  = $__full || !empty($__fp['frm_rpt_print']);
$ep = $export_params ?? '';
$route = $export_route ?? '';
$modal_id = 'emailModal_'.preg_replace('/\W/','_', $route);
?>
<div class="d-flex align-items-center d-print-none" style="gap:5px;">
    <?php if ($can_export): ?>
    <a href="<?= base_url($route.'?'.$ep.'&export=excel') ?>" class="btn btn-success btn-sm" title="Download Excel/CSV">
        <i class="fas fa-file-excel mr-1"></i><span class="d-none d-md-inline">Excel</span>
    </a>
    <?php if (empty($export_no_pdf)): ?>
    <a href="<?= base_url($route.'?'.$ep.'&export=pdf') ?>" class="btn btn-danger btn-sm" title="Download PDF">
        <i class="fas fa-file-pdf mr-1"></i><span class="d-none d-md-inline">PDF</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>
    <?php if ($can_email): ?>
    <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#<?= $modal_id ?>" title="Email Report">
        <i class="fas fa-envelope mr-1"></i><span class="d-none d-md-inline">Email</span>
    </button>
    <?php endif; ?>
    <?php if ($can_print): ?>
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()" title="Print">
        <i class="fas fa-print mr-1"></i><span class="d-none d-md-inline">Print</span>
    </button>
    <?php endif; ?>
</div>

<?php if ($can_email): ?>
<!-- Email Modal -->
<div class="modal fade" id="<?= $modal_id ?>" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h6 class="modal-title mb-0"><i class="fas fa-envelope mr-1"></i> Email Report</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <?= form_open($route.'?'.$ep.'&export=email', ['method'=>'post']) ?>
            <div class="modal-body">
                <div class="form-group mb-1">
                    <label class="small font-weight-bold">Report</label>
                    <div class="text-muted small"><?= htmlspecialchars($export_title ?? $route) ?></div>
                </div>
                <div class="form-group mb-2">
                    <label class="small font-weight-bold">Send to Email <span class="text-danger">*</span></label>
                    <input type="email" name="email_to" class="form-control form-control-sm" placeholder="name@example.com" required>
                </div>
                <small class="text-muted">Sent as CSV attachment.</small>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-paper-plane mr-1"></i> Send</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>
<?php endif; ?>
