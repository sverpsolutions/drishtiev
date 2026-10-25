<?php
$s = null; // no existing data
?>
<?= form_open('suppliers/add') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-plus-circle text-success mr-2"></i>Add Supplier</h5>
    <a href="<?= base_url('suppliers') ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>
<?php $this->load->view('suppliers/_form', ['s' => $s, 'is_edit' => false]); ?>
<?= form_close() ?>
