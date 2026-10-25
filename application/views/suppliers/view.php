<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$tier_colors = ['gold'=>'warning','silver'=>'secondary','bronze'=>'dark'];
$tc = $tier_colors[$supplier->tier??'bronze']??'dark';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><?= htmlspecialchars($supplier->name) ?>
            <small class="text-muted"><?= htmlspecialchars($supplier->supplier_code??'') ?></small>
            <?php if ($supplier->tier ?? null): ?><span class="badge badge-<?= $tc ?> ml-2"><?= ucfirst($supplier->tier) ?></span><?php endif; ?>
        </h5>
        <?php if ($supplier->company_name ?? null): ?><small class="text-muted"><?= htmlspecialchars($supplier->company_name) ?></small><?php endif; ?>
    </div>
    <div>
        <a href="<?= base_url('purchase_orders/create?supplier_id='.$supplier->id) ?>" class="btn btn-sm btn-success mr-2"><i class="fas fa-plus"></i> New PO</a>
        <a href="<?= base_url('suppliers/edit/'.$supplier->id) ?>" class="btn btn-sm btn-outline-secondary mr-2"><i class="fas fa-edit"></i> Edit</a>
        <a href="<?= base_url('suppliers') ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 h-100" style="font-size:.84rem;">
            <div class="font-weight-bold mb-2 text-uppercase text-muted small">Contact</div>
            <div><i class="fas fa-mobile-alt mr-1 text-muted"></i><?= htmlspecialchars($supplier->mobile??$supplier->phone??'-') ?></div>
            <?php if ($supplier->whatsapp??null): ?><div><i class="fab fa-whatsapp mr-1 text-success"></i><?= htmlspecialchars($supplier->whatsapp) ?></div><?php endif; ?>
            <?php if ($supplier->email??null): ?><div><i class="fas fa-envelope mr-1 text-muted"></i><?= htmlspecialchars($supplier->email) ?></div><?php endif; ?>
            <?php if ($supplier->city??null): ?><div class="mt-2"><i class="fas fa-map-marker-alt mr-1 text-muted"></i><?= htmlspecialchars(($supplier->city??'').', '.($supplier->state??'')) ?></div><?php endif; ?>
            <div class="mt-2"><span class="badge badge-light border"><?= htmlspecialchars($supplier->gstin??$supplier->gst_number??'No GSTIN') ?></span></div>
            <?php if ($supplier->pan??$supplier->pan_number??null): ?><div class="text-muted">PAN: <?= htmlspecialchars($supplier->pan??$supplier->pan_number) ?></div><?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 h-100" style="font-size:.84rem;">
            <div class="font-weight-bold mb-2 text-uppercase text-muted small">Purchase Terms</div>
            <table class="table table-sm mb-0">
                <tr><td class="text-muted">Credit Days</td><td><?= $supplier->credit_days ?? 0 ?></td></tr>
                <tr><td class="text-muted">Credit Limit</td><td>₹<?= number_format($supplier->credit_limit??0,0) ?></td></tr>
                <tr><td class="text-muted">MOQ</td><td><?= $supplier->moq ?? 1 ?></td></tr>
                <tr><td class="text-muted">Lead Time</td><td><?= $supplier->lead_time_days ?? 0 ?> days</td></tr>
                <tr><td class="text-muted">Vendor Score</td><td><?= $supplier->vendor_score ?? 0 ?></td></tr>
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <?php if ($schedule??null): ?>
        <div class="card border-0 shadow-sm p-3 h-100" style="font-size:.84rem;">
            <div class="font-weight-bold mb-2 text-uppercase text-muted small">Visit Schedule</div>
            <div>Type: <span class="badge badge-info"><?= ucfirst($schedule->schedule_type) ?></span></div>
            <div class="mt-1">Next Visit: <?php
            if ($schedule->next_visit_date) {
                $diff = (new DateTime())->diff(new DateTime($schedule->next_visit_date))->days;
                $future = (new DateTime($schedule->next_visit_date)) >= new DateTime();
                $cls = !$future ? 'text-danger' : ($diff==0 ? 'text-danger' : ($diff<=2?'text-warning':'text-success'));
                echo '<span class="font-weight-bold '.$cls.'">'.date('d M Y',strtotime($schedule->next_visit_date)).'</span>';
                if ($diff==0 && $future) echo ' <span class="badge badge-danger">TODAY</span>';
            } else echo '<span class="text-muted">Not set</span>';
            ?></div>
            <div class="mt-2"><a href="<?= base_url('suppliers/visit_schedule/'.$supplier->id) ?>" class="btn btn-xs btn-outline-info"><i class="fas fa-calendar-alt"></i> Manage Schedule</a></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Purchase History -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 font-weight-bold d-flex justify-content-between">
        <span><i class="fas fa-shopping-cart mr-1"></i>Purchase Orders</span>
        <a href="<?= base_url('purchase_orders/create?supplier_id='.$supplier->id) ?>" class="btn btn-xs btn-outline-success"><i class="fas fa-plus"></i></a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.78rem;">
                <thead class="thead-light"><tr><th>PO #</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($pos??[])): ?><tr><td colspan="5" class="text-center text-muted py-3">No POs yet.</td></tr><?php endif; ?>
                <?php foreach ($pos??[] as $po): ?>
                <?php $sc=['approved'=>'success','vendor_confirmed'=>'info','received'=>'dark','rejected'=>'danger'][$po->status]??'secondary'; ?>
                <tr>
                    <td><a href="<?= base_url('purchase_orders/view/'.$po->id) ?>"><?= htmlspecialchars($po->po_number) ?></a></td>
                    <td><?= date('d M Y',strtotime($po->created_at)) ?></td>
                    <td>₹<?= number_format($po->net_total,0) ?></td>
                    <td><span class="badge badge-<?= $sc ?>"><?= ucfirst(str_replace('_',' ',$po->status)) ?></span></td>
                    <td><a href="<?= base_url('purchase_orders/view/'.$po->id) ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Visit Log -->
<?php if (!empty($visit_logs??[])): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header py-2 font-weight-bold"><i class="fas fa-calendar-check mr-1"></i>Visit Log</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:.78rem;">
            <thead class="thead-light"><tr><th>Date</th><th>Buyer</th><th>PO Value</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach ($visit_logs as $vl): ?>
            <tr>
                <td><?= date('d M Y',strtotime($vl->visit_date)) ?></td>
                <td><?= htmlspecialchars($vl->buyer_name??'-') ?></td>
                <td><?= $vl->po_value ? '₹'.number_format($vl->po_value,0) : '-' ?></td>
                <td><?= htmlspecialchars($vl->notes??'-') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<style>.btn-xs{padding:2px 6px;font-size:.72rem;}</style>
