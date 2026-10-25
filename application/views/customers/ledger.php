<?php // FILE: application/views/customers/ledger.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Customer Ledger</h5>
        <small class="text-muted"><a href="<?= base_url('customers') ?>">Customers</a> / Ledger / <?= htmlspecialchars($customer->name) ?></small>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm no-print">&#128438; Print Statement</button>
        <a href="<?= base_url('customers') ?>" class="btn btn-outline-secondary btn-sm ml-1 no-print">&larr; Back</a>
    </div>
</div>

<!-- Customer Info + Summary -->
<div class="row mb-3">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body" style="font-size:.87rem;">
                <p class="font-weight-bold mb-1" style="font-size:1.05rem;"><?= htmlspecialchars($customer->name) ?></p>
                <p class="mb-1 text-muted"><?= ucfirst($customer->type) ?> Customer</p>
                <p class="mb-1">&#128222; <?= htmlspecialchars($customer->phone) ?></p>
                <?php if ($customer->address): ?>
                <p class="mb-1">&#128205; <?= htmlspecialchars($customer->address) ?><?= $customer->city ? ', '.htmlspecialchars($customer->city) : '' ?></p>
                <?php endif; ?>
                <?php if ($customer->gst_number): ?>
                <p class="mb-1">GSTIN: <code><?= htmlspecialchars($customer->gst_number) ?></code></p>
                <?php endif; ?>
                <?php if (!empty($customer->pan)): ?>
                <p class="mb-1"><i class="fas fa-id-card text-muted mr-1"></i>PAN: <code><?= htmlspecialchars($customer->pan) ?></code></p>
                <?php endif; ?>
                <?php if (!empty($customer->aadhaar_number)): ?>
                <?php
                $raw_adh = $customer->aadhaar_number;
                $masked_adh = strlen($raw_adh) >= 4 ? ('XXXXXXXX' . substr($raw_adh, -4)) : $raw_adh;
                ?>
                <p class="mb-1"><i class="fas fa-fingerprint text-muted mr-1"></i>Aadhaar: <code><?= htmlspecialchars($masked_adh) ?></code></p>
                <?php endif; ?>
                <?php if (!empty($customer->id_proof_type)): ?>
                <p class="mb-0 text-muted" style="font-size:0.8rem;"><i class="fas fa-file-alt mr-1"></i>ID: <?= ucwords(str_replace('_', ' ', $customer->id_proof_type)) ?><?= !empty($customer->id_proof_doc) ? (' - ' . htmlspecialchars($customer->id_proof_doc)) : '' ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="row">
            <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-2">
                        <p class="text-muted mb-1" style="font-size:.75rem;">Total Invoiced</p>
                        <p class="font-weight-bold mb-0">&#8377; <?= number_format($summary['total_invoiced'], 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-2">
                        <p class="text-muted mb-1" style="font-size:.75rem;">Total Paid</p>
                        <p class="font-weight-bold text-success mb-0">&#8377; <?= number_format($summary['total_paid'], 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm text-center" style="border:<?= $summary['balance'] > 0 ? '1px solid #f5c6cb' : '1px solid #c3e6cb' ?> !important;">
                    <div class="card-body py-2">
                        <p class="text-muted mb-1" style="font-size:.75rem;">Outstanding Balance</p>
                        <p class="font-weight-bold mb-0 <?= $summary['balance'] > 0 ? 'text-danger' : 'text-success' ?>" style="font-size:1.1rem;">
                            &#8377; <?= number_format($summary['balance'], 2) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php if ($customer->credit_limit > 0): ?>
            <div class="col-12 mt-1">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.82rem;">
                            <span>Credit utilisation</span>
                            <span class="font-weight-bold">
                                &#8377; <?= number_format($summary['balance'], 2) ?> of
                                &#8377; <?= number_format($customer->credit_limit, 2) ?>
                            </span>
                        </div>
                        <?php
                        $pct = min(100, $customer->credit_limit > 0 ? ($summary['balance'] / $customer->credit_limit) * 100 : 0);
                        $bar_cls = $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success');
                        ?>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar bg-<?= $bar_cls ?>" style="width:<?= round($pct) ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($vehicles) || !empty($warranties)): ?>
<div class="card border-0 shadow-sm mb-3" style="border-left: 4px solid #16a34a !important;">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <h6 class="font-weight-bold mb-0 text-success">
            <i class="fas fa-motorcycle mr-1"></i> EV Dealership Vehicles &amp; Warranties (<?= count($vehicles) ?> Vehicle<?= count($vehicles) > 1 ? 's' : '' ?>)
        </h6>
        <span class="badge badge-success"><?= count($warranties) ?> Warranty Policy Records</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($vehicles)): ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:0.85rem;">
                <thead class="bg-light">
                    <tr>
                        <th>Vehicle Model</th>
                        <th>Chassis / VIN</th>
                        <th>Motor No</th>
                        <th>Colour</th>
                        <th>Sale Date</th>
                        <th>Invoice No</th>
                        <th>Warranty Expiry</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $v): ?>
                    <?php
                    $veh_warr = null;
                    if (!empty($warranties)) {
                        foreach ($warranties as $w) {
                            if ($w->serial_id == $v->id || $w->serial_no == $v->serial_no) {
                                $veh_warr = $w;
                                break;
                            }
                        }
                    }
                    $is_expired = $veh_warr ? (strtotime($veh_warr->expiry_date) < time()) : false;
                    ?>
                    <tr>
                        <td class="font-weight-bold">
                            <?= htmlspecialchars($v->product_name ?: $v->model_name ?: 'EV Scooter') ?>
                            <?php if ($v->model_number): ?><br><small class="text-muted">Model: <?= htmlspecialchars($v->model_number) ?></small><?php endif; ?>
                        </td>
                        <td><code class="font-weight-bold text-dark" style="font-size:0.9rem;"><?= htmlspecialchars($v->serial_no ?: $v->chassis_no) ?></code></td>
                        <td><?= htmlspecialchars($v->motor_no ?: '—') ?></td>
                        <td><?= htmlspecialchars($v->colour ?: $v->default_colour ?: '—') ?></td>
                        <td><?= !empty($v->sale_date) ? date('d-M-Y', strtotime($v->sale_date)) : (!empty($v->invoice_date) ? date('d-M-Y', strtotime($v->invoice_date)) : '—') ?></td>
                        <td>
                            <?php if ($v->invoice_id): ?>
                            <a href="<?= base_url('billing/print_invoice/' . $v->invoice_id) ?>" target="_blank" class="badge badge-info">
                                <i class="fas fa-file-invoice mr-1"></i><?= htmlspecialchars($v->invoice_no ?: ('#' . $v->invoice_id)) ?>
                            </a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php if ($veh_warr && $veh_warr->expiry_date): ?>
                            <span class="<?= $is_expired ? 'text-danger font-weight-bold' : 'text-success font-weight-bold' ?>">
                                <?= date('d-M-Y', strtotime($veh_warr->expiry_date)) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">Standard Policy</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_expired): ?>
                            <span class="badge badge-secondary">Expired</span>
                            <?php elseif ($veh_warr): ?>
                            <span class="badge badge-success">Active Warranty</span>
                            <?php else: ?>
                            <span class="badge badge-info">Registered</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Ledger Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 font-weight-bold d-flex justify-content-between" style="font-size:.85rem;">
        <span>Account Statement</span>
        <span class="text-muted font-weight-normal">All transactions in chronological order</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th class="text-right">Debit (Dr)</th>
                        <th class="text-right">Credit (Cr)</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($ledger)): ?>

                    <!-- Opening -->
                    <tr class="bg-light">
                        <td colspan="6" class="text-muted font-weight-bold" style="font-size:.8rem;">Opening Balance</td>
                        <td class="text-right font-weight-bold">&#8377; <?= number_format($opening_balance, 2) ?></td>
                    </tr>

                    <?php foreach ($ledger as $entry): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($entry['date'])) ?></td>
                        <td>
                            <?php if ($entry['type'] === 'invoice'): ?>
                                <span class="badge badge-warning">Invoice</span>
                            <?php else: ?>
                                <span class="badge badge-success">Payment</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($entry['type'] === 'invoice'): ?>
                                <a href="<?= base_url('billing/invoice/'.$entry['id']) ?>">
                                    <?= htmlspecialchars($entry['reference']) ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($entry['reference']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($entry['note']) ?></td>
                        <td class="text-right <?= $entry['debit'] > 0 ? 'text-danger' : 'text-muted' ?>">
                            <?= $entry['debit'] > 0 ? '&#8377; '.number_format($entry['debit'],2) : '—' ?>
                        </td>
                        <td class="text-right <?= $entry['credit'] > 0 ? 'text-success' : 'text-muted' ?>">
                            <?= $entry['credit'] > 0 ? '&#8377; '.number_format($entry['credit'],2) : '—' ?>
                        </td>
                        <td class="text-right font-weight-bold <?= $entry['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                            &#8377; <?= number_format(abs($entry['balance']),2) ?>
                            <small class="text-muted"><?= $entry['balance'] > 0 ? 'Dr' : 'Cr' ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Closing -->
                    <tr class="font-weight-bold bg-light">
                        <td colspan="4" class="text-right">Closing Balance:</td>
                        <td class="text-right text-danger">&#8377; <?= number_format($summary['total_invoiced'],2) ?></td>
                        <td class="text-right text-success">&#8377; <?= number_format($summary['total_paid'],2) ?></td>
                        <td class="text-right <?= $summary['balance'] > 0 ? 'text-danger' : 'text-success' ?>" style="font-size:.95rem;">
                            &#8377; <?= number_format(abs($summary['balance']),2) ?>
                            <small><?= $summary['balance'] > 0 ? 'Dr' : 'Cr' ?></small>
                        </td>
                    </tr>

                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No transactions found for this customer.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Payment Button -->
<?php if ($summary['balance'] > 0): ?>
<div class="mt-3 no-print">
    <a href="<?= base_url('payments/receive') ?>" class="btn btn-success btn-sm">
        + Record Payment from <?= htmlspecialchars($customer->name) ?>
    </a>
</div>
<?php endif; ?>

<?php if (!empty($show_wa_activity)): ?>
<div class="card border-0 shadow-sm mt-3 no-print">
    <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;"><i class="fab fa-whatsapp text-success mr-1"></i> WhatsApp Marketing Activity</div>
    <div class="card-body p-0">
        <?php if (empty($wa_activity)): ?>
            <p class="text-muted text-center py-3 mb-0 small">No WhatsApp campaign sends recorded for this customer.</p>
        <?php else: ?>
            <?php
            $wa_badge = ['pending' => 'secondary', 'queued' => 'info', 'sending' => 'info', 'sent' => 'primary', 'delivered' => 'success', 'read' => 'success', 'failed' => 'danger', 'replied' => 'warning', 'opted_out' => 'dark'];
            ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                    <thead class="thead-light"><tr><th>Campaign</th><th>Type</th><th>Status</th><th>Sent</th><th>Converted</th></tr></thead>
                    <tbody>
                    <?php foreach ($wa_activity as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a->campaign_name) ?></td>
                            <td class="text-capitalize"><?= str_replace('_', ' ', $a->campaign_type) ?></td>
                            <td><span class="badge badge-<?= $wa_badge[$a->status] ?? 'light' ?> text-capitalize"><?= $a->status ?></span></td>
                            <td><?= $a->sent_at ? date('d M Y', strtotime($a->sent_at)) : '—' ?></td>
                            <td><?= $a->converted_invoice_id ? '<span class="text-success">✓ Yes</span>' : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<style>
@media print {
    .no-print { display: none !important; }
    .card { box-shadow: none !important; }
    .navbar { display: none !important; }
}
</style>
