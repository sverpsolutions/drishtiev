<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-tools text-primary mr-1"></i> New Warranty Claim</h5>
    <a href="<?= base_url('warranty/claims') ?>" class="btn btn-outline-secondary btn-sm">All Claims</a>
</div>

<div class="row">
  <div class="col-md-7">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <?= form_open('warranty/claim_create') ?>
        <div class="form-group">
            <label class="font-weight-bold">Serial Number <span class="text-danger">*</span></label>
            <input type="text" name="serial_no" id="claimSerial" class="form-control form-control-lg"
                   style="font-family:monospace;font-weight:600;" required autofocus autocomplete="off"
                   value="<?= htmlspecialchars($prefill ?? '') ?>"
                   placeholder="Scan the serial number on the unit">
            <small class="text-muted">Scan it and the customer, invoice and warranty are filled in automatically.</small>
        </div>
        <div id="claimLookup"></div>

        <div class="form-group">
            <label class="font-weight-bold">Claim Date</label>
            <input type="date" name="claim_date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
            <label class="font-weight-bold">Customer Complaint</label>
            <textarea name="customer_complaint" class="form-control" rows="2" placeholder="What the customer reported"></textarea>
        </div>
        <div class="form-group">
            <label class="font-weight-bold">Problem Description</label>
            <textarea name="problem_description" class="form-control" rows="3" placeholder="Observed fault"></textarea>
        </div>
        <div class="form-group">
            <label class="font-weight-bold">Remarks</label>
            <input type="text" name="remarks" class="form-control">
        </div>
        <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Create Claim</button>
        <?= form_close() ?>
      </div>
    </div>
  </div>
</div>

<script>
// Look the unit up as soon as it is scanned so the operator can confirm they
// have the right motor before opening a claim.
(function () {
    var box = document.getElementById('claimSerial'), out = document.getElementById('claimLookup'), last = '';
    function esc(s){return String(s).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];});}
    function lookup() {
        var sn = (box.value || '').trim();
        if (!sn || sn === last) return;
        last = sn;
        var fd = new FormData(); fd.append('serial', sn);
        fetch('<?= base_url('serials/lookup_ajax') ?>', {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){return r.json();})
        .then(function(res){
            var s = res && res.serial;
            if (!s) { out.innerHTML = '<div class="alert alert-danger py-2 px-3">'+esc((res&&res.error)||'Serial not found.')+'</div>'; return; }
            var active = s.warranty_status === 'ACTIVE';
            out.innerHTML =
              '<div class="alert alert-'+(active?'success':'warning')+' py-2 px-3" style="font-size:.87rem;">'
            + '<strong>'+(active?'✓ WARRANTY ACTIVE':'⚠ WARRANTY EXPIRED')+'</strong><br>'
            + '<strong>Item:</strong> '+esc(s.product_name||'—')+'<br>'
            + '<strong>Customer:</strong> '+esc(s.customer_name||'—')+'<br>'
            + '<strong>Invoice:</strong> '+esc(s.invoice_no||'—')+'  <strong>Sold:</strong> '+esc(s.sale_date||'—')+'<br>'
            + '<strong>Warranty Expiry:</strong> '+esc(s.warranty_expiry||'—')+'<br>'
            + esc(s.warranty_text||'') + '</div>';
        })
        .catch(function(){ out.innerHTML = '<div class="alert alert-danger py-2 px-3">Could not reach the server.</div>'; });
    }
    box.addEventListener('change', lookup);
    box.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); lookup(); } });
    if (box.value) lookup();
})();
</script>
