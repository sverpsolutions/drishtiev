<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Customer Wallet Ledger</h5>
        <small class="text-muted"><a href="<?= base_url('customers') ?>">Customers</a> / <?= htmlspecialchars($customer->name) ?> / Wallet History</small>
    </div>
    <a href="<?= base_url('customers') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Customers</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-1"></i> <?= $this->session->flashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle mr-1"></i> <?= $this->session->flashdata('error') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <!-- Wallet Balance Card (Left) -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm text-white h-100" style="background: linear-gradient(135deg, #11998e, #38ef7d); border-radius: 12px; overflow: hidden; min-height: 180px;">
            <div class="card-body d-flex flex-column justify-content-between p-4">
                <div>
                    <h6 class="text-uppercase tracking-wider opacity-75 mb-1" style="font-size: 0.8rem; font-weight: 600; letter-spacing: 1px;">Customer Wallet</h6>
                    <h4 class="font-weight-bold mb-0"><?= htmlspecialchars($customer->name) ?></h4>
                    <p class="small opacity-75 mb-0"><i class="fas fa-phone-alt mr-1"></i> <?= htmlspecialchars($customer->phone) ?></p>
                </div>
                <div class="mt-4">
                    <span class="opacity-75 d-block small">Available Balance</span>
                    <h2 class="font-weight-bold mb-0" style="font-size: 2.5rem; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        ₹ <?= number_format($customer->wallet_balance ?? 0, 2) ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Adjustment Form (Right) -->
    <div class="col-md-7">
        <?php if (in_array($this->session->userdata('role'), ['superadmin', 'admin', 'manager'])): ?>
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-sliders-h mr-2 text-primary"></i> Manual Wallet Adjustment</h6>
                </div>
                <div class="card-body pt-0">
                    <?= form_open('customers/adjust_wallet/' . $customer->id, ['id' => 'walletAdjustForm']) ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small font-weight-bold">Adjustment Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-control form-control-sm" required>
                                <option value="credit">Add Funds (+)</option>
                                <option value="debit">Deduct Funds (-)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small font-weight-bold">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small font-weight-bold">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-save mr-1"></i> Save Adjustment</button>
                        </div>
                        <div class="col-12 mt-2">
                            <label class="form-label small font-weight-bold">Remarks / Reason <span class="text-danger">*</span></label>
                            <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Enter reason for adjustment..." required>
                        </div>
                    </div>
                    <?= form_close() ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; min-height: 180px;">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                    <div class="rounded-circle bg-light p-3 mb-2">
                        <i class="fas fa-lock text-muted fa-2x"></i>
                    </div>
                    <h6 class="font-weight-bold mb-1">Administrative Adjustment Restricted</h6>
                    <p class="text-muted small mb-0">Only administrators and managers are permitted to manually adjust wallet balances.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Transaction Ledger Card (Bottom) -->
<div class="card border-0 shadow-sm" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-list-alt mr-2 text-success"></i> Wallet Transaction Ledger</h6>
        <span class="badge badge-pill badge-light font-weight-bold py-1 px-3" style="font-size: 0.8rem;"><?= count($transactions) ?> Entries</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size: 0.85rem; vertical-align: middle;">
                <thead class="thead-light">
                    <tr>
                        <th style="padding-left: 20px;">Date &amp; Time</th>
                        <th>Type</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Opening Balance</th>
                        <th class="text-right">Closing Balance</th>
                        <th>Reference</th>
                        <th>Remarks / Description</th>
                        <th style="padding-right: 20px;">Processed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td style="padding-left: 20px;" class="text-nowrap"><?= date('d M Y, h:i A', strtotime($t->created_at)) ?></td>
                                <td>
                                    <?php if ($t->transaction_type === 'credit'): ?>
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-plus mr-1"></i> Credit</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger px-2 py-1"><i class="fas fa-minus mr-1"></i> Debit</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-weight-bold <?= $t->transaction_type === 'credit' ? 'text-success' : 'text-danger' ?>">
                                    ₹ <?= number_format($t->amount, 2) ?>
                                </td>
                                <td class="text-right text-muted">₹ <?= number_format($t->opening_balance, 2) ?></td>
                                <td class="text-right font-weight-bold text-dark">₹ <?= number_format($t->closing_balance, 2) ?></td>
                                <td>
                                    <?php if ($t->reference_type === 'sale_payment' && $t->reference_id): ?>
                                        <a href="<?= base_url('billing/invoice/' . $t->reference_id) ?>" class="font-weight-bold text-decoration-none">
                                            <i class="fas fa-receipt mr-1"></i> <?= htmlspecialchars($t->reference_no ?: '#' . $t->reference_id) ?>
                                        </a>
                                    <?php elseif ($t->reference_type === 'sale_excess' && $t->reference_id): ?>
                                        <a href="<?= base_url('billing/invoice/' . $t->reference_id) ?>" class="font-weight-bold text-decoration-none text-success">
                                            <i class="fas fa-coins mr-1"></i> Change (<?= htmlspecialchars($t->reference_no ?: '#' . $t->reference_id) ?>)
                                        </a>
                                    <?php elseif ($t->reference_type === 'refund' && $t->reference_id): ?>
                                        <a href="<?= base_url('sales_returns/view/' . $t->reference_id) ?>" class="font-weight-bold text-decoration-none text-warning">
                                            <i class="fas fa-undo mr-1"></i> CN <?= htmlspecialchars($t->reference_no ?: '#' . $t->reference_id) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><i class="fas fa-user-cog mr-1"></i> Adjustment</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($t->remarks) ?></td>
                                <td style="padding-right: 20px;" class="font-weight-bold text-dark"><?= htmlspecialchars($t->user_name ?: 'System') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <div class="mb-2"><i class="fas fa-wallet fa-2x text-light"></i></div>
                                <span>No wallet transactions recorded for this customer.</span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
