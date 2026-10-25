<?php // FILE: application/views/customers/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Customers</h5>
        <small class="text-muted">Retail &amp; wholesale customer master</small>
    </div>
    <div>
        <a href="<?= base_url('customers/add') ?>" class="btn btn-dark btn-sm">+ Add Customer</a>
        <a href="<?= base_url('customers/import') ?>" class="btn btn-outline-success btn-sm ml-1"><i class="fas fa-file-excel mr-1"></i>Import from Excel</a>
        <button class="btn btn-outline-info btn-sm ml-1" data-toggle="modal" data-target="#mobileUpdateModal"><i class="fas fa-mobile-alt mr-1"></i>Update Mobile</button>
    </div>
</div>

<!-- Modal: Update Customer Mobile Without Bill -->
<div class="modal fade" id="mobileUpdateModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="fas fa-mobile-alt mr-2"></i>Register / Update Customer Mobile</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="font-weight-bold">Search Customer</label>
          <input type="text" id="mobileSearchInput" class="form-control" placeholder="Type name or existing phone...">
        </div>
        <div id="mobileSearchResults" class="list-group mb-3" style="max-height:200px;overflow-y:auto;"></div>
        <div id="mobileUpdateForm" style="display:none;">
          <hr>
          <div class="alert alert-info py-2 small" id="mobileSelectedInfo"></div>
          <div class="form-group">
            <label class="font-weight-bold">New Mobile Number</label>
            <input type="tel" id="mobileNewPhone" class="form-control" placeholder="10-digit mobile number" maxlength="15">
          </div>
          <button class="btn btn-success btn-block" onclick="saveMobileUpdate()">Save Mobile Number</button>
        </div>
        <div id="mobileUpdateMsg"></div>
      </div>
    </div>
  </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<!-- Filter bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row">
            <div class="col-md-6">
                <input type="text" id="custSearch" class="form-control form-control-sm" placeholder="Search customers...">
            </div>
            <div class="col-md-3">
                <select id="typeFilter" class="form-control form-control-sm">
                    <option value="">All Types</option>
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="custTable" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>GSTIN</th>
                        <th>City</th>
                        <th class="text-right">Credit Limit</th>
                        <th class="text-center">Disc%</th>
                        <th class="text-right">Outstanding</th>
                        <th class="text-right">Wallet</th>
                        <th class="text-center">Portal</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $total_outstanding = array_sum(array_map(fn($c) => max(0, $c->balance), $customers));
                ?>
                <?php if (!empty($customers)): ?>
                    <?php foreach ($customers as $i => $c): ?>
                    <tr data-type="<?= $c->type ?>">
                        <td><?= $i + 1 ?></td>
                        <td class="font-weight-bold"><?= htmlspecialchars($c->name) ?></td>
                        <td><?= htmlspecialchars($c->phone) ?></td>
                        <td><span class="badge badge-<?= $c->type === 'wholesale' ? 'primary' : 'secondary' ?>"><?= ucfirst($c->type) ?></span></td>
                        <td><code><?= htmlspecialchars($c->gst_number ?? '—') ?></code></td>
                        <td><?= htmlspecialchars($c->city ?? '—') ?></td>
                        <td class="text-right">₹ <?= number_format($c->credit_limit, 2) ?></td>
                        <td class="text-center"><?= ($c->discount_percent ?? 0) > 0 ? '<span class="badge badge-info">' . $c->discount_percent . '%</span>' : '—' ?></td>
                        <td class="text-right font-weight-bold <?= $c->balance > 0 ? 'text-danger' : 'text-success' ?>">
                            ₹ <?= number_format($c->balance, 2) ?>
                        </td>
                        <td class="text-right font-weight-bold text-success">
                            ₹ <?= number_format($c->wallet_balance ?? 0, 2) ?>
                        </td>
                        <td class="text-center">
                            <?php if ($c->portal_active): ?>
                            <span class="badge badge-success" title="Portal enabled">Portal ✓</span>
                            <?php else: ?>
                            <span class="badge badge-light text-muted" title="Portal disabled">Off</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="<?= base_url('customers/wallet_history/'.$c->id) ?>" class="btn btn-sm btn-outline-success py-0 px-2" title="Wallet History"><i class="fas fa-wallet fa-xs"></i></a>
                            <a href="<?= base_url('customers/edit/'.$c->id) ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit"><i class="fas fa-edit fa-xs"></i></a>
                            <a href="<?= base_url('reports/party_ledger?party_type=customer&party_id='.$c->id) ?>" class="btn btn-sm btn-outline-info py-0 px-2" title="Party Ledger"><i class="fas fa-book fa-xs"></i></a>
                            <?php
                            $wa_ph = preg_replace('/\D/','',$c->phone);
                            if (strlen($wa_ph)==10) $wa_ph='91'.$wa_ph;
                            $wa_txt= urlencode('Dear '.$c->name.', your outstanding balance is ₹'.number_format($c->balance,2).'. Please contact us for details.');
                            if (strlen($wa_ph)>=10):
                            ?>
                            <a href="https://wa.me/<?=$wa_ph?>?text=<?=$wa_txt?>" target="_blank" class="btn btn-sm py-0 px-2" style="background:#25D366;color:#fff;" title="WhatsApp"><i class="fab fa-whatsapp fa-xs"></i></a>
                            <?php endif; ?>
                            <?php if ($this->session->userdata('role') === 'admin'): ?>
                            <a href="<?= base_url('customers/delete/'.$c->id) ?>" class="btn btn-sm btn-outline-danger py-0 px-2"
                               onclick="return confirm('Delete customer?')" title="Delete"><i class="fas fa-trash fa-xs"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No customers yet. <a href="<?= base_url('customers/add') ?>">Add one.</a></td></tr>
                <?php endif; ?>
                <?php if (!empty($customers) && $total_outstanding > 0): ?>
                    <tr class="table-danger font-weight-bold">
                        <td colspan="8" class="text-right text-dark">Total Outstanding Due:</td>
                        <td class="text-right text-danger">₹ <?= number_format($total_outstanding, 2) ?></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let mobileSelectedId = null;

document.getElementById('mobileSearchInput').addEventListener('input', function() {
    const q = this.value.trim();
    if (q.length < 2) { document.getElementById('mobileSearchResults').innerHTML = ''; return; }
    fetch('<?= base_url("customers/mobile_lookup_ajax") ?>', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'phone='+encodeURIComponent(q)
    }).then(r=>r.json()).then(d=>{
        const el = document.getElementById('mobileSearchResults');
        if (!d.success || !d.customers.length) { el.innerHTML = '<div class="list-group-item text-muted small">No customers found</div>'; return; }
        el.innerHTML = d.customers.map(c=>`<a href="#" class="list-group-item list-group-item-action" onclick="selectMobileCustomer(${c.id},'${c.name}','${c.phone}');return false;"><strong>${c.name}</strong> <span class="text-muted ml-2">${c.phone||'No phone'}</span></a>`).join('');
    });
});

function selectMobileCustomer(id, name, phone) {
    mobileSelectedId = id;
    document.getElementById('mobileSearchResults').innerHTML = '';
    document.getElementById('mobileSelectedInfo').textContent = 'Selected: ' + name + ' | Current: ' + (phone || 'None');
    document.getElementById('mobileNewPhone').value = '';
    document.getElementById('mobileUpdateForm').style.display = '';
}

function saveMobileUpdate() {
    const phone = document.getElementById('mobileNewPhone').value.trim();
    if (!phone || !mobileSelectedId) { alert('Please select a customer and enter a phone number.'); return; }
    fetch('<?= base_url("customers/update_mobile_ajax") ?>', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'customer_id='+mobileSelectedId+'&phone='+encodeURIComponent(phone)
    }).then(r=>r.json()).then(d=>{
        const el = document.getElementById('mobileUpdateMsg');
        if (d.success) {
            el.innerHTML = '<div class="alert alert-success mt-2">Mobile updated successfully!</div>';
            setTimeout(()=>{ $('#mobileUpdateModal').modal('hide'); location.reload(); }, 1200);
        } else {
            el.innerHTML = '<div class="alert alert-danger mt-2">'+(d.error||'Error')+'</div>';
        }
    });
}

document.getElementById('custSearch').addEventListener('input', filterTable);
document.getElementById('typeFilter').addEventListener('change', filterTable);
function filterTable() {
    const q    = document.getElementById('custSearch').value.toLowerCase();
    const type = document.getElementById('typeFilter').value;
    document.querySelectorAll('#custTable tbody tr').forEach(function(row) {
        const textMatch = row.textContent.toLowerCase().includes(q);
        const typeMatch = !type || row.dataset.type === type;
        row.style.display = (textMatch && typeMatch) ? '' : 'none';
    });
}
</script>
