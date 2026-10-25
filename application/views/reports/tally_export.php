<?php $mode = ($mode ?? 'transactions') === 'masters' ? 'masters' : 'transactions'; ?>
<div class="container-fluid mt-3">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-file-export text-primary"></i> Tally Export</h4>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="alert alert-secondary small">
    <i class="fas fa-info-circle mr-1"></i>
    Produces a native Tally XML file — import it via <strong>Gateway of Tally → Import → Data</strong>. Two export
    types, matching Tally's own import menu: <strong>Masters</strong> (ledgers, stock items, units) and
    <strong>Transactions</strong> (vouchers). Import Masters first, then Transactions.
    <ul class="mb-0 mt-1">
        <li>Item-wise vouchers: every product becomes a Tally Stock Item, so Tally's own stock reports keep working after import.</li>
        <li>Walk-in customers with no GSTIN are all posted to one shared <code>Cash Sales</code> ledger; customers with a GSTIN get their own ledger.</li>
        <li>GST posts to three flat ledgers — <code>CGST</code> / <code>SGST</code> / <code>IGST</code> — not split by rate slab.</li>
        <li>This app doesn't record interstate/intrastate on purchases directly, so for Purchase and Purchase Return vouchers it's <strong>inferred</strong> by comparing the supplier's state to your company state (Settings → Company). That's the same test GST law itself uses, but it depends on both states being filled in correctly.</li>
        <li>Purchase Return line items don't carry their own GST rate in this system — the rate is <strong>inferred from the matching line on the original purchase</strong> for the same product. If a returned product wasn't on that original purchase, this can be wrong.</li>
        <li><strong>Import a small test batch (e.g. one day) into a test Tally company first</strong> before bulk-importing — this export has been checked for well-formed XML and correct debit/credit signs, but hasn't been verified against a real Tally import.</li>
    </ul>
</div>

<div class="card shadow-sm">
<div class="card-body">
<form method="get" action="<?= base_url('reports/export_tally_xml') ?>">
    <input type="hidden" name="mode" id="tallyMode" value="<?= $mode ?>">

    <div class="form-group">
        <label class="font-weight-bold small mb-1 d-block">Export Type</label>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn <?= $mode === 'masters' ? 'btn-primary' : 'btn-outline-primary' ?>" data-mode="masters">
                <i class="fas fa-address-book mr-1"></i> Masters
            </button>
            <button type="button" class="btn <?= $mode === 'transactions' ? 'btn-primary' : 'btn-outline-primary' ?>" data-mode="transactions">
                <i class="fas fa-file-invoice mr-1"></i> Transactions
            </button>
        </div>
    </div>

    <div class="form-row">
        <div class="col-md-4 form-group">
            <label class="font-weight-bold small mb-0">Tally Company Name *</label>
            <input type="text" name="tally_company" class="form-control form-control-sm" required placeholder="exact name as in Tally">
        </div>
    </div>

    <!-- ── MASTERS ────────────────────────────────────────────────── -->
    <div id="paneMasters" class="border-top pt-3" style="<?= $mode === 'masters' ? '' : 'display:none' ?>">
        <p class="small text-muted mb-2">Master data only — no date range, no vouchers. This is the file you import first into a fresh Tally company.</p>
        <div class="form-group">
            <label class="font-weight-bold small mb-0 d-block">Include</label>
            <div class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input" id="mAccounts" name="m_accounts" value="1" checked>
                <label class="custom-control-label" for="mAccounts">Accounting ledgers (Sales, Purchase, CGST/SGST/IGST, Cash Sales)</label>
            </div>
            <div class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input" id="mSuppliers" name="m_suppliers" value="1" checked>
                <label class="custom-control-label" for="mSuppliers">Suppliers</label>
            </div>
            <div class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input" id="mCustomers" name="m_customers" value="1" checked>
                <label class="custom-control-label" for="mCustomers">Customers</label>
            </div>
            <div class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input" id="mProducts" name="m_products" value="1" checked>
                <label class="custom-control-label" for="mProducts">Products (stock items + units)</label>
            </div>
        </div>
        <div class="form-group mb-0">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="mCustomersB2c" name="m_customers_b2c" value="1">
                <label class="custom-control-label" for="mCustomersB2c">Also export walk-in customers with no GSTIN</label>
                <small class="d-block text-muted">Off by default: vouchers post those customers to the shared <code>Cash Sales</code> ledger, so their individual ledgers would stay empty.</small>
            </div>
            <div class="custom-control custom-checkbox mt-2">
                <input type="checkbox" class="custom-control-input" id="mOpeningStock" name="m_opening_stock" value="1">
                <label class="custom-control-label" for="mOpeningStock">Set opening stock from current quantities</label>
                <small class="d-block text-muted">Off by default. Only tick this for a fresh Tally company you will <em>not</em> load historical purchase vouchers into — otherwise the same stock gets counted twice. Valued at purchase price.</small>
            </div>
        </div>
    </div>

    <!-- ── TRANSACTIONS ───────────────────────────────────────────── -->
    <div id="paneTransactions" class="border-top pt-3" style="<?= $mode === 'transactions' ? '' : 'display:none' ?>">
        <div class="form-row">
            <div class="col-md-3 form-group">
                <label class="font-weight-bold small mb-0">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
            </div>
            <div class="col-md-3 form-group">
                <label class="font-weight-bold small mb-0">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
            </div>
            <div class="col-md-4 form-group">
                <label class="font-weight-bold small mb-0">Location</label>
                <select name="location_id" class="form-control form-control-sm" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
                    <?php if (count($locations ?? []) > 1): ?>
                    <option value="">All Outlets</option>
                    <?php endif; ?>
                    <?php foreach ($locations ?? [] as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= ($location_id ?? 0) == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Sales &amp; Purchase only — returns aren't tracked per-location in this system.</small>
            </div>
        </div>

        <div class="form-group">
            <label class="font-weight-bold small mb-0 d-block">Voucher Types</label>
            <div class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input" id="incSales" name="inc_sales" value="1" checked>
                <label class="custom-control-label" for="incSales">Sales</label>
            </div>
            <div class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input" id="incSalesReturn" name="inc_sales_return" value="1" checked>
                <label class="custom-control-label" for="incSalesReturn">Sales Return</label>
            </div>
            <div class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input" id="incPurchase" name="inc_purchase" value="1" checked>
                <label class="custom-control-label" for="incPurchase">Purchase</label>
            </div>
            <div class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input" id="incPurchaseReturn" name="inc_purchase_return" value="1" checked>
                <label class="custom-control-label" for="incPurchaseReturn">Purchase Return</label>
            </div>
        </div>

        <div class="form-group mb-0">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="incMasters" name="inc_masters" value="1" checked>
                <label class="custom-control-label" for="incMasters">Also include the masters these vouchers reference</label>
                <small class="d-block text-muted">Leave on unless you've already imported the Masters file into the same Tally company. Only the parties, stock items and ledgers actually used in the date range are included.</small>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-sm mt-3"><i class="fas fa-file-export mr-1"></i> <span id="exportBtnLabel"><?= $mode === 'masters' ? 'Export Masters XML' : 'Export Transactions XML' ?></span></button>
</form>
</div>
</div>
</div>

<script>
(function () {
    var hidden = document.getElementById('tallyMode');
    var panes  = { masters: document.getElementById('paneMasters'), transactions: document.getElementById('paneTransactions') };
    var label  = document.getElementById('exportBtnLabel');

    document.querySelectorAll('[data-mode]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var mode = btn.getAttribute('data-mode');
            hidden.value = mode;
            document.querySelectorAll('[data-mode]').forEach(function (b) {
                var on = b.getAttribute('data-mode') === mode;
                b.classList.toggle('btn-primary', on);
                b.classList.toggle('btn-outline-primary', !on);
            });
            // Hidden panes must also be disabled — a hidden checkbox still
            // submits its value on a GET form, which would leak the other
            // mode's options into the query string.
            Object.keys(panes).forEach(function (k) {
                var on = (k === mode);
                panes[k].style.display = on ? '' : 'none';
                panes[k].querySelectorAll('input, select').forEach(function (f) { f.disabled = !on; });
            });
            label.textContent = mode === 'masters' ? 'Export Masters XML' : 'Export Transactions XML';
        });
    });

    // Apply the disabled state once on load for whichever pane starts hidden.
    Object.keys(panes).forEach(function (k) {
        if (panes[k].style.display === 'none') {
            panes[k].querySelectorAll('input, select').forEach(function (f) { f.disabled = true; });
        }
    });
})();
</script>
