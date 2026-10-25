<?php $flash_s = $this->session->flashdata('success'); $flash_e = $this->session->flashdata('error'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-truck-loading text-success mr-2"></i>Goods Receipt Notes (GRN)</h5>
        <small class="text-muted">Record received goods and update stock</small>
    </div>
    <div>
        <a href="<?= base_url('grn/drafts') ?>" class="btn btn-outline-secondary btn-sm mr-2">Drafts</a>
        <a href="<?= base_url('grn/create_direct') ?>" class="btn btn-primary btn-sm mr-2"><i class="fas fa-bolt"></i> New GRN (Without PO)</a>
        <a href="<?= base_url('grn/create') ?>" class="btn btn-dark btn-sm"><i class="fas fa-plus"></i> New GRN (From PO)</a>
    </div>
</div>
<?php if ($flash_s): ?><div class="alert alert-success alert-dismissible fade show"><?= $flash_s ?><button class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
<?php if ($flash_e): ?><div class="alert alert-danger alert-dismissible fade show"><?= $flash_e ?><button class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr><th>GRN #</th><th>PO #</th><th>Supplier</th><th>Received Date</th><th>Invoice No</th><th>Net Total</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($grns)): ?><tr><td colspan="8" class="text-center text-muted py-4">No GRNs yet.</td></tr><?php endif; ?>
                <?php foreach ($grns as $g): ?>
                <?php $sc=['pending'=>'warning','qc_pending'=>'info','accepted'=>'success','partial'=>'primary','rejected'=>'danger'][$g->status]??'secondary'; ?>
                <tr>
                    <td><a href="<?= base_url('grn/view/'.$g->id) ?>" class="font-weight-bold"><?= htmlspecialchars($g->grn_number) ?></a></td>
                    <td><?= htmlspecialchars($g->po_number??'-') ?></td>
                    <td><?= htmlspecialchars($g->supplier_name) ?></td>
                    <td><?= date('d M Y', strtotime($g->received_date)) ?></td>
                    <td><?= htmlspecialchars($g->supplier_invoice_no??'-') ?></td>
                    <td>₹<?= number_format($g->net_total,2) ?></td>
                    <td><span class="badge badge-<?= $sc ?>"><?= ucfirst(str_replace('_',' ',$g->status)) ?></span></td>
                    <td>
                        <a href="<?= base_url('grn/view/'.$g->id) ?>" class="btn btn-xs btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                        <a href="<?= base_url('grn/edit/'.$g->id) ?>" class="btn btn-xs btn-outline-warning ml-1" title="Edit / Modify"><i class="fas fa-edit"></i></a>
                        <a href="<?= base_url('grn/print_grn/'.$g->id) ?>" target="_blank" class="btn btn-xs btn-outline-secondary ml-1" title="Print"><i class="fas fa-print"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>.btn-xs{padding:2px 6px;font-size:.72rem;}</style>
