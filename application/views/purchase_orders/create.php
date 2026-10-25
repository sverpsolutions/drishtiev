<?php $is_edit = false; $po = null; $items = []; ?>
<?= form_open('purchase_orders/create', ['id'=>'poForm']) ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-invoice text-success mr-2"></i>New Purchase Order</h5>
    <a href="<?= base_url('purchase_orders') ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>
<?php $this->load->view('purchase_orders/_form', compact('is_edit','po','items','suppliers','products','locations','suggestions')); ?>
<?= form_close() ?>
