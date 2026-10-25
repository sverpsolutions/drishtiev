<?php
$statuses = [
    'draft'            => ['Grey',   'secondary'],
    'submitted'        => ['Submitted','primary'],
    'pending_approval' => ['Pending Approval','warning'],
    'approved'         => ['Approved','success'],
    'vendor_confirmed' => ['Vendor Confirmed','info'],
    'dispatched'       => ['Dispatched','warning'],
    'in_transit'       => ['In Transit','warning'],
    'received'         => ['Received','success'],
    'invoiced'         => ['Invoiced','primary'],
    'paid'             => ['Paid','success'],
    'rejected'         => ['Rejected','danger'],
    'cancelled'        => ['Cancelled','dark'],
];
$flash_s = $this->session->flashdata('success');
$flash_e = $this->session->flashdata('error');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-invoice text-success mr-2"></i>Purchase Orders</h5>
        <small class="text-muted">Create, approve and track purchase orders</small>
    </div>
    <div>
        <a href="<?= base_url('purchase_suggestions') ?>" class="btn btn-outline-info btn-sm mr-2"><i class="fas fa-magic"></i> Smart Suggestions</a>
        <a href="<?= base_url('rfq') ?>" class="btn btn-outline-secondary btn-sm mr-2"><i class="fas fa-quote-right"></i> RFQ</a>
        <a href="<?= base_url('purchase_orders/create') ?>" class="btn btn-dark btn-sm"><i class="fas fa-plus"></i> New PO</a>
    </div>
</div>

<?php if ($flash_s): ?><div class="alert alert-success alert-dismissible fade show"><?= $flash_s ?><button class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
<?php if ($flash_e): ?><div class="alert alert-danger alert-dismissible fade show"><?= $flash_e ?><button class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

<!-- Stats -->
<div class="row mb-3">
    <?php $stat_map = ['draft'=>['Draft','secondary'],'pending_approval'=>['Pending Approval','warning'],'approved'=>['Approved','success'],'vendor_confirmed'=>['Confirmed','info'],'dispatched'=>['In Transit','warning'],'received'=>['Received','primary']]; ?>
    <?php foreach ($stat_map as $st => [$label,$cls]): ?>
    <div class="col-6 col-md-2 mb-2">
        <a href="<?= base_url('purchase_orders?status=' . $st) ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm text-center py-2">
                <div class="h4 mb-0 text-<?= $cls ?>"><?= $stats[$st] ?? 0 ?></div>
                <div style="font-size:.65rem;color:#6c757d"><?= $label ?></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="form-inline flex-wrap gap-2">
            <select name="status" class="form-control form-control-sm mr-2 mb-1">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $k => [$l,$c]): ?>
                <option value="<?= $k ?>" <?= ($filters['status']??'')===$k?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
            <select name="supplier_id" class="form-control form-control-sm mr-2 mb-1">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $sid => $sname): ?>
                <option value="<?= $sid ?>" <?= ($filters['supplier_id']??'')==$sid?'selected':'' ?>><?= htmlspecialchars($sname) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="from_date" class="form-control form-control-sm mr-2 mb-1" value="<?= $filters['from_date']??'' ?>">
            <input type="date" name="to_date"   class="form-control form-control-sm mr-2 mb-1" value="<?= $filters['to_date']??'' ?>">
            <button type="submit" class="btn btn-sm btn-dark mb-1">Filter</button>
            <a href="<?= base_url('purchase_orders') ?>" class="btn btn-sm btn-outline-secondary mb-1 ml-1">Clear</a>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr><th>PO #</th><th>Supplier</th><th>Date</th><th>Expected Delivery</th><th>Buyer</th><th>Items</th><th>Net Total</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($pos)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No purchase orders found.</td></tr>
                <?php endif; ?>
                <?php foreach ($pos as $po): ?>
                <?php [$sl,$sc] = $statuses[$po->status] ?? ['Unknown','secondary']; ?>
                <tr>
                    <td><a href="<?= base_url('purchase_orders/view/' . $po->id) ?>" class="font-weight-bold"><?= htmlspecialchars($po->po_number) ?></a></td>
                    <td><?= htmlspecialchars($po->supplier_name ?? '-') ?></td>
                    <td><?= date('d M Y', strtotime($po->created_at)) ?></td>
                    <td><?= $po->expected_delivery ? date('d M Y', strtotime($po->expected_delivery)) : '-' ?></td>
                    <td><?= htmlspecialchars($po->buyer_name ?? '-') ?></td>
                    <td class="text-center">
                        <?php $cnt = $this->db->where('po_id',$po->id)->get('purchase_order_items')->num_rows(); echo $cnt; ?>
                    </td>
                    <td class="font-weight-bold">₹<?= number_format($po->net_total, 2) ?></td>
                    <td><span class="badge badge-<?= $sc ?>"><?= $sl ?></span></td>
                    <td>
                        <a href="<?= base_url('purchase_orders/view/' . $po->id) ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i></a>
                        <?php if (in_array($po->status,['draft','submitted'])): ?>
                        <a href="<?= base_url('purchase_orders/edit/' . $po->id) ?>" class="btn btn-xs btn-outline-secondary"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                        <?php if ($po->status === 'pending_approval' && $is_admin): ?>
                        <a href="<?= base_url('purchase_orders/approve/' . $po->id) ?>" class="btn btn-xs btn-outline-warning"><i class="fas fa-check"></i></a>
                        <?php endif; ?>
                        <?php if ($po->status === 'approved'): ?>
                        <a href="<?= base_url('grn/create/' . $po->id) ?>" class="btn btn-xs btn-outline-success" title="Create GRN"><i class="fas fa-truck-loading"></i></a>
                        <?php endif; ?>
                        <a href="<?= base_url('purchase_orders/print_po/' . $po->id) ?>" class="btn btn-xs btn-outline-dark" target="_blank"><i class="fas fa-print"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>.btn-xs{padding:2px 6px;font-size:.72rem;}</style>
