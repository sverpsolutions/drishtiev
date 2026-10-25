<?php // FILE: application/views/products/reviews.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="font-weight-bold mb-0 text-dark">Customer Reviews</h4>
        <small class="text-muted">Moderate and view customer product ratings and feedback</small>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size: 0.88rem; vertical-align: middle;">
                <thead class="bg-light text-secondary border-bottom">
                    <tr>
                        <th class="py-3 px-4 border-0" style="width: 15%;">Product</th>
                        <th class="py-3 px-4 border-0" style="width: 15%;">Customer</th>
                        <th class="py-3 px-4 border-0" style="width: 12%;">Rating</th>
                        <th class="py-3 px-4 border-0">Review Summary & Details</th>
                        <th class="py-3 px-4 border-0" style="width: 12%;">Date Submitted</th>
                        <th class="py-3 px-4 border-0" style="width: 10%;">Status</th>
                        <th class="py-3 px-4 border-0 text-right" style="width: 18%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $rev): ?>
                            <tr class="align-middle">
                                <td class="py-3 px-4 border-bottom-light">
                                    <span class="font-weight-bold text-primary"><?= htmlspecialchars($rev->product_name) ?></span>
                                    <?php if (!empty($rev->item_code)): ?>
                                        <small class="text-muted d-block" style="font-size: 0.72rem;">Code: <?= htmlspecialchars($rev->item_code) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 border-bottom-light">
                                    <span class="font-weight-bold text-dark"><?= htmlspecialchars($rev->customer_name) ?></span>
                                </td>
                                <td class="py-3 px-4 border-bottom-light">
                                    <span class="text-warning font-weight-bold" style="font-size: 1.1rem; line-height: 1;">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rev->rating) echo '★';
                                            else echo '☆';
                                        }
                                        ?>
                                    </span>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;"><?= $rev->rating ?> / 5 Stars</small>
                                </td>
                                <td class="py-3 px-4 border-bottom-light">
                                    <?php if ($rev->is_verified_purchase): ?>
                                        <span class="badge badge-success px-2 py-1 font-weight-bold mb-1" style="font-size: 0.65rem; border-radius: 4px;">Verified Purchase</span>
                                    <?php endif; ?>
                                    <div class="font-weight-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($rev->review_title) ?></div>
                                    <p class="text-muted mb-0 mt-1" style="font-size: 0.84rem; line-height: 1.4;"><?= htmlspecialchars($rev->review_text) ?></p>
                                </td>
                                <td class="py-3 px-4 border-bottom-light">
                                    <span class="text-secondary"><?= date('d M Y', strtotime($rev->created_at)) ?></span>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;"><?= date('H:i', strtotime($rev->created_at)) ?></small>
                                </td>
                                <td class="py-3 px-4 border-bottom-light">
                                    <?php if ($rev->status === 'approved'): ?>
                                        <span class="badge badge-success px-3 py-1 font-weight-bold text-uppercase" style="border-radius: 30px; font-size: 0.72rem;">Approved</span>
                                    <?php elseif ($rev->status === 'pending'): ?>
                                        <span class="badge badge-warning px-3 py-1 font-weight-bold text-uppercase" style="border-radius: 30px; font-size: 0.72rem;">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger px-3 py-1 font-weight-bold text-uppercase" style="border-radius: 30px; font-size: 0.72rem;">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 border-bottom-light text-right">
                                    <div class="d-flex justify-content-end gap-1">
                                        <?php if ($rev->status !== 'approved'): ?>
                                            <form method="post" action="<?= base_url('products/update_review_status') ?>" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $rev->id ?>">
                                                <input type="hidden" name="action" value="approved">
                                                <button type="submit" class="btn btn-xs btn-outline-success font-weight-bold" style="border-radius: 4px;">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($rev->status !== 'rejected'): ?>
                                            <form method="post" action="<?= base_url('products/update_review_status') ?>" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $rev->id ?>">
                                                <input type="hidden" name="action" value="rejected">
                                                <button type="submit" class="btn btn-xs btn-outline-danger font-weight-bold" style="border-radius: 4px;">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="<?= base_url('products/update_review_status') ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                            <input type="hidden" name="id" value="<?= $rev->id ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-xs btn-danger font-weight-bold" style="border-radius: 4px;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5" style="background-color: #fafafa;">
                                <div class="py-4">
                                    <span style="font-size: 3rem; display: block;" class="mb-3">💬</span>
                                    <span class="font-weight-bold d-block text-muted">No customer reviews found</span>
                                    <small class="text-muted">When customers write reviews on portal products, they will appear here.</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .gap-1 { gap: 0.25rem; }
    .border-bottom-light { border-bottom: 1px solid #f1f2f6; }
    .align-middle td { vertical-align: middle; }
    .bg-light { background-color: #f8f9fa !important; }
    
    /* Dark mode overrides manually (just in case) */
    body.dark-mode .border-bottom-light { border-bottom-color: #333; }
    body.dark-mode .bg-light { background-color: #0f3460 !important; }
</style>
