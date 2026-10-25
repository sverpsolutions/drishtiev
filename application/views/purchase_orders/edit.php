<?php $is_edit = true; $suggestions = $suggestions ?? []; ?>
<?= form_open('purchase_orders/edit/' . $po->id, ['id'=>'poForm']) ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-edit text-primary mr-2"></i>Edit PO — <?= htmlspecialchars($po->po_number) ?></h5>
    <a href="<?= base_url('purchase_orders/view/' . $po->id) ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>
<?php $this->load->view('purchase_orders/_form', compact('is_edit','po','items','suppliers','products','locations','suggestions')); ?>
<?= form_close() ?>
