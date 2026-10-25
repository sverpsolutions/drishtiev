<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Stock Movements: <?= htmlspecialchars($product->name) ?></h5>
        <small class="text-muted"><a href="<?= base_url('inventory') ?>">Inventory</a> / Movements</small>
    </div>
    <div>
        <?php $export_qs = ""; ?>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm shadow-sm"><i class="fas fa-print"></i> Print</button>
        <a href="<?= base_url("inventory/movements/{$product->id}?export=pdf") ?>" class="btn btn-outline-danger btn-sm shadow-sm"><i class="fas fa-file-pdf"></i> PDF</a>
        <a href="<?= base_url("inventory/movements/{$product->id}?export=excel") ?>" class="btn btn-outline-success btn-sm shadow-sm"><i class="fas fa-file-excel"></i> Excel</a>
        <button data-toggle="modal" data-target="#emailModal" class="btn btn-outline-info btn-sm shadow-sm"><i class="fas fa-envelope"></i> Email</button>
        <a href="<?= base_url('inventory') ?>" class="btn btn-outline-secondary btn-sm ml-2">&larr; Back</a>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 d-flex" style="font-size:.87rem;gap:20px;">
        <?php if (!empty($location_id) && isset($location_stock)): ?>
        <span><strong>Stock (<?= htmlspecialchars($location_name) ?>):</strong> <span class="badge badge-<?= $location_stock>10?'success':($location_stock>0?'warning':'danger') ?>"><?= $location_stock ?> <?= $product->unit ?></span></span>
        <?php else: ?>
        <span><strong>Stock:</strong> <span class="badge badge-<?= $product->stock_qty>10?'success':($product->stock_qty>0?'warning':'danger') ?>"><?= $product->stock_qty ?> <?= $product->unit ?></span></span>
        <?php endif; ?>
        <span><strong>Category:</strong> <?= $product->category ?></span>
        <span><strong>Purchase Price:</strong> &#8377; <?= number_format($product->purchase_price,2) ?></span>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark"><tr><th>Date</th><th>Time</th><th>Type</th><th>Reference</th><th>Party</th><th class="text-center text-success">In (+)</th><th class="text-center text-danger">Out (-)</th><th class="text-right">Balance</th></tr></thead>
                <tbody>
                <?php if (!empty($movements)):
                    $current_stock = isset($location_stock) ? $location_stock : (float)$product->stock_qty;
                    $total_in  = array_sum(array_column($movements, 'qty_in'));
                    $total_out = array_sum(array_column($movements, 'qty_out'));
                    $net_movement = $total_in - $total_out;
                    $ob = $current_stock - $net_movement;
                    $running_bal = $ob;
                ?>
                <tr class="bg-light font-weight-bold">
                    <td colspan="5">Opening Balance (OB)</td>
                    <td class="text-center">—</td>
                    <td class="text-center">—</td>
                    <td class="text-right <?= $ob < 0 ? 'text-danger' : 'text-dark' ?>"><?= $ob ?> <?= $product->unit ?></td>
                </tr>
                <?php foreach ($movements as $m):
                        $running_bal += ($m->qty_in - $m->qty_out);
                ?>
                <tr>
                    <td><?= date('d M Y', strtotime($m->date)) ?></td>
                    <td class="text-muted"><?= date('h:i A', strtotime($m->datetime)) ?></td>
                    <?php
                        $badge = 'secondary';
                        if (strpos($m->type,'Purchase')!==false || strpos($m->type,'Transfer In')!==false || strpos($m->type,'(+)')!==false) $badge='success';
                        elseif (strpos($m->type,'Sale')!==false || strpos($m->type,'Transfer Out')!==false || strpos($m->type,'(-)')!==false) $badge='warning';
                    ?>
                    <td><span class="badge badge-<?= $badge ?>"><?= htmlspecialchars($m->type) ?></span></td>
                    <td><?= htmlspecialchars($m->reference) ?></td>
                    <td><?= htmlspecialchars($m->party) ?></td>
                    <td class="text-center text-success font-weight-bold"><?= $m->qty_in > 0 ? '+'.$m->qty_in : '—' ?></td>
                    <td class="text-center text-danger font-weight-bold"><?= $m->qty_out > 0 ? '-'.$m->qty_out : '—' ?></td>
                    <td class="text-right font-weight-bold <?= $running_bal < 0 ? 'text-danger' : 'text-dark' ?>"><?= $running_bal ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="bg-dark text-white font-weight-bold">
                    <td colspan="5" class="text-right">Closing Stock &rarr;</td>
                    <td class="text-center"><?= $total_in ?></td>
                    <td class="text-center"><?= $total_out ?></td>
                    <td class="text-right"><?= $current_stock ?> <?= $product->unit ?></td>
                </tr>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No movements found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= base_url("inventory/email_movements/".$product->id) ?>" method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Stock Movements</h5>
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
                    <input type="text" name="email_subject" class="form-control" value="Stock Movements: <?= htmlspecialchars($product->name) ?>">
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="email_message" class="form-control" rows="3">Please find attached the stock movements report for <?= htmlspecialchars($product->name) ?>.</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Email</button>
            </div>
        </form>
    </div>
</div>

