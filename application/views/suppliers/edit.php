<?php $s = $supplier; ?>
<?= form_open('suppliers/edit/' . $s->id) ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-edit text-primary mr-2"></i>Edit Supplier — <?= htmlspecialchars($s->name) ?></h5>
    <a href="<?= base_url('suppliers/view/' . $s->id) ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>
<?php $this->load->view('suppliers/_form', ['s' => $s, 'is_edit' => true]); ?>
<?= form_close() ?>
