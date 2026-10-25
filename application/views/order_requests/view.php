<?php // FILE: application/views/order_requests/view.php ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= base_url('order_requests') ?>" class="btn btn-sm btn-outline-secondary mr-2">
            <i class="fas fa-arrow-left mr-1"></i>Back
        </a>
        <strong><?= htmlspecialchars($order->request_no) ?></strong>
        <?php
        $cls = ['pending'=>'warning','confirmed'=>'info','converted'=>'success','cancelled'=>'secondary'];
        ?>
        <span class="badge badge-<?= $cls[$order->status] ?? 'secondary' ?> ml-2"><?= ucfirst($order->status) ?></span>
    </div>
    <div>
        <?php if ($order->status === 'pending' || $order->status === 'confirmed'): ?>
        <a href="<?= base_url('order_requests/convert/'.$order->id) ?>"
           class="btn btn-sm btn-success mr-1"
           onclick="return confirm('Convert this order to invoice?')">
            <i class="fas fa-file-invoice mr-1"></i>Convert to Invoice
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white py-2 d-flex justify-content-between">
                <strong>Order Items</strong>
                <span class="text-muted" style="font-size:.83rem;"><?= count($items) ?> item(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.87rem;">
                    <thead class="thead-light">
                        <tr>
                            <th class="pl-3">#</th>
                            <th>Product</th>
                            <th>Code</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">MRP</th>
                            <th class="text-right">SP</th>
                            <th class="text-right">Stock</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td class="pl-3"><?= $i + 1 ?></td>
                        <td class="font-weight-bold"><?= htmlspecialchars($item->product_name) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($item->item_code ?? '') ?></td>
                        <td class="text-center"><?= $item->qty ?> <?= htmlspecialchars($item->unit ?? '') ?></td>
                        <td class="text-right">
                            <span class="text-muted" style="text-decoration:line-through;">₹<?= number_format($item->mrp, 2) ?></span>
                        </td>
                        <td class="text-right font-weight-bold text-success">
                            ₹<?= number_format($item->selling_price ?? $item->mrp, 2) ?>
                        </td>
                        <td class="text-right <?= ($item->stock_qty ?? 0) < $item->qty ? 'text-danger font-weight-bold' : 'text-success' ?>">
                            <?= $item->stock_qty ?? 0 ?>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($item->notes ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <?php
                        $subtotal = 0;
                        foreach ($items as $item) {
                            $sp = isset($item->selling_price) && $item->selling_price > 0 ? $item->selling_price : $item->mrp;
                            $subtotal += $sp * $item->qty;
                        }
                        ?>
                        <tr class="border-top">
                            <td colspan="6"></td>
                            <td class="text-right font-weight-bold text-muted">Subtotal:</td>
                            <td class="text-right font-weight-bold text-dark">₹<?= number_format($subtotal, 2) ?></td>
                        </tr>
                        <tr>
                            <td colspan="6"></td>
                            <td class="text-right font-weight-bold text-muted">Delivery:</td>
                            <td class="text-right font-weight-bold text-dark">
                                <?= $order->delivery_charge > 0 ? '₹' . number_format($order->delivery_charge, 2) : '<span class="text-success">FREE</span>' ?>
                            </td>
                        </tr>
                        <tr class="bg-light">
                            <td colspan="6"></td>
                            <td class="text-right font-weight-bold text-success" style="font-size:1rem;">Grand Total:</td>
                            <td class="text-right font-weight-bold text-success" style="font-size:1rem;">₹<?= number_format($order->total_amount, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Customer Info -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2 px-3" style="font-size:.86rem;">
                <p class="mb-2 font-weight-bold">Customer</p>
                <p class="mb-1"><i class="fas fa-user text-muted mr-1"></i><?= htmlspecialchars($order->customer_name) ?></p>
                <p class="mb-1"><i class="fas fa-phone text-muted mr-1"></i><?= htmlspecialchars($order->customer_phone) ?></p>
                <?php if (!empty($order->customer_address)): ?>
                <p class="mb-1 text-muted"><i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($order->customer_address) ?></p>
                <?php endif; ?>
                <?php if (!empty($order->gst_number)): ?>
                <p class="mb-0 text-muted">GST: <?= htmlspecialchars($order->gst_number) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Info -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2 px-3" style="font-size:.86rem;">
                <p class="mb-2 font-weight-bold">Order Info</p>
                <p class="mb-1 text-muted">Placed: <?= date('d M Y, h:i A', strtotime($order->created_at)) ?></p>
                
                <?php if (!empty($order->delivery_slot) || !empty($order->delivery_distance)): ?>
                <div class="mt-2 p-2 bg-light rounded">
                    <strong>Delivery Details:</strong>
                    <div class="mt-1">
                        <?php if (!empty($order->delivery_distance)): ?>
                        <span class="d-block">📍 Distance: <strong><?= $order->delivery_distance ?> KM</strong></span>
                        <?php endif; ?>
                        <?php if (!empty($order->delivery_slot)): ?>
                        <span class="d-block">🕒 Slot: <strong><?= htmlspecialchars($order->delivery_slot) ?></strong></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($order->notes)): ?>
                <div class="mt-2 p-2 bg-light rounded">
                    <strong>Notes:</strong>
                    <p class="mb-0 text-muted mt-1"><?= nl2br(htmlspecialchars($order->notes)) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status update -->
        <?php if ($order->status !== 'converted' && $order->status !== 'cancelled'): ?>
        <div class="card shadow-sm">
            <div class="card-body py-2 px-3" style="font-size:.86rem;">
                <p class="mb-2 font-weight-bold">Update Status</p>
                <form method="post" action="<?= base_url('order_requests/update_status/'.$order->id) ?>">
                    <div class="form-group mb-2">
                        <select name="status" class="form-control form-control-sm">
                            <option value="pending"   <?= $order->status==='pending'  ?'selected':'' ?>>Pending</option>
                            <option value="confirmed" <?= $order->status==='confirmed'?'selected':'' ?>>Confirmed</option>
                            <option value="cancelled" <?= $order->status==='cancelled'?'selected':'' ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Update Status</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
