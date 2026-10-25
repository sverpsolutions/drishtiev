
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($messages)): ?><div class="text-center text-muted py-5">No messages yet.</div><?php endif; ?>
        <?php
        $current_po = null;
        foreach ($messages as $msg):
            if ($current_po !== $msg->po_number):
                $current_po = $msg->po_number;
        ?>
        <div class="px-3 py-2 bg-light border-bottom">
            <i class="fas fa-file-invoice mr-1 text-muted"></i>
            <strong><?= htmlspecialchars($msg->po_number??'General') ?></strong>
        </div>
        <?php endif; ?>
        <div class="px-3 py-2 border-bottom d-flex <?= $msg->sender_type==='vendor'?'justify-content-end':'' ?>" style="font-size:.82rem;">
            <div class="<?= $msg->sender_type==='vendor'?'text-right':'' ?>" style="max-width:70%;">
                <div class="rounded p-2 <?= $msg->sender_type==='vendor'?'bg-success text-white':'bg-light border' ?>">
                    <?= htmlspecialchars($msg->message) ?>
                </div>
                <small class="text-muted"><?= date('d M H:i',strtotime($msg->created_at)) ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
