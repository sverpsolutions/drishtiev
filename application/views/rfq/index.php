<?php $flash = $this->session->flashdata('success'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-envelope-open-text text-primary mr-2"></i>Request for Quotations (RFQ)</h5>
        <small class="text-muted">Get competitive quotes from multiple suppliers</small>
    </div>
    <a href="<?= base_url('rfq/create') ?>" class="btn btn-dark btn-sm"><i class="fas fa-plus"></i> New RFQ</a>
</div>
<?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show"><?= $flash ?><button class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.82rem;">
                <thead class="thead-dark"><tr><th>RFQ #</th><th>Title</th><th>Suppliers</th><th>Items</th><th>Deadline</th><th>Status</th><th>Responses</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($rfqs)): ?><tr><td colspan="8" class="text-center text-muted py-4">No RFQs yet.</td></tr><?php endif; ?>
                <?php foreach ($rfqs as $r): ?>
                <?php $sc=['draft'=>'secondary','sent'=>'info','responded'=>'primary','compared'=>'warning','closed'=>'dark','cancelled'=>'danger'][$r->status]??'secondary'; ?>
                <tr>
                    <td><a href="<?= base_url('rfq/view/'.$r->id) ?>" class="font-weight-bold"><?= htmlspecialchars($r->rfq_number) ?></a></td>
                    <td><?= htmlspecialchars($r->title??'-') ?></td>
                    <td><?= $r->supplier_count ?></td>
                    <td><?= $r->item_count ?></td>
                    <td><?= $r->deadline ? date('d M Y', strtotime($r->deadline)) : '-' ?></td>
                    <td><span class="badge badge-<?= $sc ?>"><?= ucfirst($r->status) ?></span></td>
                    <td><?= $r->response_count ?>/<?= $r->supplier_count ?></td>
                    <td>
                        <a href="<?= base_url('rfq/view/'.$r->id) ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i></a>
                        <?php if ($r->response_count > 0): ?><a href="<?= base_url('rfq/compare/'.$r->id) ?>" class="btn btn-xs btn-outline-success ml-1"><i class="fas fa-balance-scale"></i></a><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>.btn-xs{padding:2px 6px;font-size:.72rem;}</style>
