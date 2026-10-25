<?php // FILE: application/views/support/index.php — Help & Support (SV ERP helpdesk) ?>
<style>
.hs-banner{display:flex;align-items:center;gap:16px;padding:16px 20px;border-radius:10px;margin-bottom:18px;border:1px solid #e3e6f0;background:#fff;flex-wrap:wrap}
.hs-banner .ico{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;font-size:20px;flex-shrink:0;background:#f1f3f9;color:#858796}
.hs-banner.active .ico{background:rgba(16,185,129,.14);color:#059669}
.hs-banner.expired{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.35)} .hs-banner.expired .ico{background:rgba(245,158,11,.18);color:#b45309}
.hs-banner.suspended{background:rgba(220,38,38,.07);border-color:rgba(220,38,38,.35)} .hs-banner.suspended .ico{background:rgba(220,38,38,.15);color:#b91c1c}
.hs-banner h5{margin:0;font-weight:700}
.hs-banner p{margin:2px 0 0;font-size:.85rem;color:#6c757d}
.hs-days{margin-left:auto;text-align:right}
.hs-days b{display:block;font-size:1.7rem;line-height:1}
.hs-days small{color:#6c757d}
.hs-lic{font-family:monospace;letter-spacing:.5px}
.hs-prio{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.hs-prio label{border:1.5px solid #dee2e6;border-radius:10px;padding:8px 6px;text-align:center;font-size:.85rem;font-weight:600;cursor:pointer;margin:0}
.hs-prio input{display:none}
.hs-prio label.low:has(input:checked){background:#16a34a;border-color:#16a34a;color:#fff}
.hs-prio label.medium:has(input:checked){background:#ea580c;border-color:#ea580c;color:#fff}
.hs-prio label.high:has(input:checked){background:#dc2626;border-color:#dc2626;color:#fff}
.hs-drop{border:2px dashed #dee2e6;border-radius:12px;padding:14px;text-align:center;color:#6c757d;font-size:.85rem;cursor:pointer;display:block;margin:0}
.hs-drop:hover{border-color:#4e73df}
.hs-prev{display:flex;align-items:center;gap:10px;margin-top:8px}
.hs-prev[hidden]{display:none}
.hs-prev img{width:64px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #dee2e6}
.hs-err{color:#e74a3b;font-size:.78rem;margin-top:4px;display:none}
.hs-tab{border:1px solid #dee2e6;background:#f8f9fc;border-radius:999px;padding:4px 12px;font-size:.8rem;font-weight:600;cursor:pointer}
.hs-tab.on{background:#4e73df;border-color:#4e73df;color:#fff}
.hs-ticket{display:flex;gap:14px;align-items:flex-start;padding:12px 18px;border-bottom:1px solid #eef0f5;cursor:pointer}
.hs-ticket:hover{background:#f4f7ff}
.hs-ticket .no{font-family:monospace;font-weight:700;font-size:.82rem;color:#4e73df}
.hs-ticket .sub{font-weight:600;margin:2px 0}
.hs-ticket .last{font-size:.8rem;color:#6c757d;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:520px}
.hs-pill{display:inline-block;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:999px;text-transform:uppercase;letter-spacing:.3px}
.st-open{background:#ccfbf1;color:#0f766e}.st-in_progress{background:#fef3c7;color:#92400e}.st-resolved{background:#d1fae5;color:#065f46}
.pr-high{background:#fee2e2;color:#b91c1c}.pr-medium{background:#ffedd5;color:#c2410c}.pr-low{background:#dcfce7;color:#15803d}
.hs-empty{padding:46px 20px;text-align:center;color:#6c757d}
.hs-thread{display:flex;flex-direction:column;gap:12px;max-height:46vh;overflow:auto;padding:4px 2px}
.hs-msg{max-width:82%;padding:10px 14px;border-radius:14px;font-size:.88rem;line-height:1.5;white-space:pre-wrap;word-break:break-word}
.hs-msg.them{background:#f1f3f9;border:1px solid #e3e6f0;align-self:flex-start;border-bottom-left-radius:4px}
.hs-msg.me{background:#eaf1ff;border:1px solid #c9d8ff;align-self:flex-end;border-bottom-right-radius:4px}
.hs-msg .who{font-size:.7rem;font-weight:700;color:#6c757d;margin-bottom:4px;white-space:normal}
.hs-msg img{max-width:240px;border-radius:8px;margin-top:8px;display:block}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="mb-0"><i class="fas fa-headset text-primary"></i> Help &amp; Support</h4>
        <small class="text-muted">Report a software issue to SV ERP Solutions and track the replies here.</small></div>
</div>

<div id="hs-banner" class="hs-banner"><div class="ico"><i class="fas fa-spinner fa-spin"></i></div><div><h5>Checking your support license…</h5></div></div>

<div class="row">
    <div class="col-lg-5 mb-3">
        <div class="card shadow-sm">
            <div class="card-header bg-white font-weight-bold"><i class="fas fa-headset text-primary"></i> Report a Software Issue to SV ERP</div>
            <form class="card-body" id="hs-form" novalidate>
                <div class="form-group"><label class="small font-weight-bold">Subject *</label><input class="form-control form-control-sm" id="f-subject" maxlength="150" placeholder="e.g. Thermal bill print is cut on the right"><div class="hs-err" id="e-subject"></div></div>
                <div class="form-group"><label class="small font-weight-bold">Issue type *</label>
                    <select class="form-control form-control-sm" id="f-category">
                        <option value="">Choose…</option>
                        <option>Billing / POS</option><option>Printing</option><option>Purchase / GRN</option><option>Inventory / Stock</option>
                        <option>Customers / Loyalty</option><option>Reports / GST</option><option>Users / Login / Access</option><option>WhatsApp / SMS</option>
                        <option>Customer Portal / Online Orders</option><option>Data Correction</option><option>New Feature Request</option><option>Other</option>
                    </select><div class="hs-err" id="e-category"></div></div>
                <div class="form-group"><label class="small font-weight-bold">Priority *</label>
                    <div class="hs-prio">
                        <label class="low"><input type="radio" name="prio" value="low"><span>Low</span></label>
                        <label class="medium"><input type="radio" name="prio" value="medium" checked><span>Medium</span></label>
                        <label class="high"><input type="radio" name="prio" value="high"><span>High</span></label>
                    </div>
                    <small class="text-muted">High = billing stopped or data wrong. Medium = something not working. Low = question or suggestion.</small></div>
                <div class="form-group"><label class="small font-weight-bold">Describe the issue *</label><textarea class="form-control form-control-sm" id="f-message" rows="6" maxlength="5000" placeholder="What were you doing, what happened, and what did you expect? Include bill number / item / customer if relevant."></textarea><div class="hs-err" id="e-message"></div></div>
                <div class="form-group"><label class="small font-weight-bold">Screenshot (optional)</label>
                    <label class="hs-drop"><i class="fas fa-image"></i> Click to attach a screenshot<br><small>JPG, PNG or WEBP · max 5 MB</small><input type="file" id="f-image" accept="image/jpeg,image/png,image/webp" hidden></label>
                    <div class="hs-prev" id="f-prev" hidden><img alt="Screenshot preview"><span class="small" style="flex:1"></span><button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearImg('f')">Remove</button></div>
                    <div class="hs-err" id="e-image"></div></div>
                <button class="btn btn-primary btn-block" id="f-submit" type="submit"><i class="fas fa-paper-plane"></i> Submit Ticket</button>
                <div id="f-blocked" class="hs-err" style="font-size:.85rem"></div>
            </form>
        </div>
    </div>
    <div class="col-lg-7 mb-3">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                <strong>My Tickets</strong>
                <div id="hs-tabs" class="d-flex flex-wrap" style="gap:6px;">
                    <button class="hs-tab on" data-s="all">All</button><button class="hs-tab" data-s="open">Open</button>
                    <button class="hs-tab" data-s="in_progress">In Progress</button><button class="hs-tab" data-s="resolved">Resolved</button>
                    <button class="hs-tab" onclick="loadTickets()" title="Refresh"><i class="fas fa-sync"></i></button>
                </div>
            </div>
            <div id="hs-list"><div class="hs-empty"><i class="fas fa-spinner fa-spin"></i> Loading tickets…</div></div>
            <div id="hs-pager" class="d-flex justify-content-end align-items-center px-3 py-2 small" style="gap:8px;"></div>
        </div>
    </div>
</div>

<?php if ($is_owner): ?>
<div class="card shadow-sm mb-4">
    <a class="card-header bg-white font-weight-bold text-dark" data-toggle="collapse" href="#hs-config" style="text-decoration:none;"><i class="fas fa-plug"></i> Support connection settings (owner)</a>
    <div class="collapse" id="hs-config"><div class="card-body">
        <div class="form-row align-items-end">
            <div class="col-md-4 form-group"><label class="small font-weight-bold">CRM API URL</label><input class="form-control form-control-sm" id="c-base" value="<?= htmlspecialchars($cfg['base']) ?>" placeholder="https://crm.sverpsolutions.com/api"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">CRM API Key</label><input class="form-control form-control-sm" id="c-key" type="password" autocomplete="new-password" placeholder="<?= $cfg['has_key'] ? '•••••••• saved — leave blank to keep' : 'Paste the CRM API key' ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">SV ERP License Key</label><input class="form-control form-control-sm hs-lic" id="c-license" value="<?= htmlspecialchars($cfg['license']) ?>" placeholder="SVERP-XXXX-XXXX-XXXX-XXXX" style="text-transform:uppercase"></div>
            <div class="col-md-2 form-group"><button type="button" class="btn btn-primary btn-sm btn-block" onclick="saveConfig()"><i class="fas fa-save"></i> Save &amp; Test</button></div>
        </div>
    </div></div>
</div>
<?php endif; ?>

<div class="modal fade" id="hsModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content" id="hsModalBody"></div></div></div>

<script>
var SAPI = <?= json_encode(base_url('support/api')) ?> + '?action=';
var IS_OWNER_HS = <?= $is_owner ? 'true' : 'false' ?>;
var hsEsc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); };
var ST_LABEL = { open: 'Open', in_progress: 'In Progress', resolved: 'Resolved' };
var stPill = function (s) { return '<span class="hs-pill st-' + hsEsc(s) + '">' + (ST_LABEL[s] || hsEsc(s)) + '</span>'; };
var prPill = function (p) { return '<span class="hs-pill pr-' + hsEsc(p) + '">' + hsEsc(p) + '</span>'; };
var fmtDT = function (s) { return s ? new Date(String(s).replace(' ', 'T')).toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''; };
var hsStatus = 'all', hsPage = 1, canRaise = true;
function toast(msg, type) { if (window.toastr) { toastr[type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info')](msg); } else alert(msg); }
function showModal(html) { document.getElementById('hsModalBody').innerHTML = html; $('#hsModal').modal('show'); }
function closeModal() { $('#hsModal').modal('hide'); }
// jQuery so the footer adds / refreshes the CSRF token on every POST
function api(action, method, data) {
    return new Promise(function (res) {
        $.ajax({ url: SAPI + action, type: method || 'GET', data: data || null, processData: !(data instanceof FormData), contentType: data instanceof FormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8', dataType: 'json' })
            .done(res).fail(function (x) { res(x.responseJSON || { success: false, error: 'Network error — please try again.' }); });
    });
}

// ── License banner ──
function loadLicense() {
    var b = document.getElementById('hs-banner');
    api('license').then(function (d) {
        var lic = d.data || {};
        if (d.not_configured) {
            b.className = 'hs-banner';
            b.innerHTML = '<div class="ico"><i class="fas fa-plug"></i></div><div><h5>Support not connected</h5><p>' + (IS_OWNER_HS ? 'Enter your SV ERP license key and CRM API key in "Support connection settings" below.' : 'Please ask the owner to connect SV ERP support.') + '</p></div>';
            if (IS_OWNER_HS) $('#hs-config').collapse('show');
            setCanRaise(false, 'Support is not connected yet.'); return;
        }
        if (!lic.status) {
            b.className = 'hs-banner';
            b.innerHTML = '<div class="ico"><i class="fas fa-exclamation-triangle"></i></div><div><h5>Could not verify your license</h5><p>' + hsEsc(d.error || d.message) + '</p></div>'; return;
        }
        var days = parseInt(lic.days_remaining, 10) || 0;
        var till = lic.support_valid_till ? new Date(lic.support_valid_till + 'T00:00').toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' }) : '—';
        var name = hsEsc(lic.company_name), sw = hsEsc(lic.software_name || ''), key = '<span class="hs-lic">' + hsEsc(lic.license_key || '') + '</span>';
        if (lic.status === 'suspended') {
            b.className = 'hs-banner suspended';
            b.innerHTML = '<div class="ico"><i class="fas fa-ban"></i></div><div><h5>' + name + ' — License Suspended</h5><p>License ' + key + ' is suspended. Please contact SV ERP support.</p></div>';
            setCanRaise(false, 'Your support license is suspended. Please contact SV ERP support.');
        } else if (lic.status === 'expired') {
            var since = lic.support_valid_till ? Math.max(0, Math.round((Date.now() - new Date(lic.support_valid_till + 'T00:00')) / 864e5)) : 0;
            b.className = 'hs-banner expired';
            b.innerHTML = '<div class="ico"><i class="fas fa-hourglass-end"></i></div><div><h5>' + name + ' — AMC Support Expired</h5><p>' + sw + ' · License ' + key + ' · Free support ended on ' + till + '. Renew your AMC for priority help; you can still raise tickets and see history.</p></div>'
                + '<div class="hs-days"><b style="color:#b45309">' + since + '</b><small>days since expiry</small></div>';
        } else {
            b.className = 'hs-banner active';
            b.innerHTML = '<div class="ico"><i class="fas fa-shield-alt"></i></div><div><h5>' + name + ' — AMC Support Active</h5><p>' + sw + ' · License ' + key + ' · Support valid till ' + till + '</p></div>'
                + '<div class="hs-days"><b style="color:' + (days <= 30 ? '#d97706' : '#059669') + '">' + days + '</b><small>days of support left</small></div>';
        }
    });
}
function setCanRaise(ok, msg) {
    canRaise = ok; document.getElementById('f-submit').disabled = !ok;
    var e = document.getElementById('f-blocked'); e.textContent = ok ? '' : msg; e.style.display = ok ? 'none' : 'block';
}

// ── Raise ticket ──
function showErr(id, msg) { var e = document.getElementById('e-' + id); e.textContent = msg || ''; e.style.display = msg ? 'block' : 'none'; }
function validImage(f) { if (!f) return ''; if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) < 0) return 'Only JPG, PNG or WEBP images.'; if (f.size > 5 * 1024 * 1024) return 'Screenshot must be 5 MB or smaller.'; return ''; }
function bindPreview(inputId, prevId, errId) {
    document.getElementById(inputId).addEventListener('change', function (e) {
        var f = e.target.files[0], err = validImage(f), prev = document.getElementById(prevId);
        if (errId) showErr(errId, err); else if (err) toast(err, 'error');
        if (!f || err) { e.target.value = ''; prev.hidden = true; return; }
        prev.querySelector('img').src = URL.createObjectURL(f);
        prev.querySelector('span').textContent = f.name + ' (' + (f.size / 1024).toFixed(0) + ' KB)';
        prev.hidden = false;
    });
}
function clearImg(p) { var ids = p === 'f' ? ['f-image', 'f-prev'] : ['r-image', 'r-prev']; document.getElementById(ids[0]).value = ''; document.getElementById(ids[1]).hidden = true; }
bindPreview('f-image', 'f-prev', 'image');
['subject', 'category', 'message'].forEach(function (k) { document.getElementById('f-' + k).addEventListener('input', function () { showErr(k, ''); }); });

document.getElementById('hs-form').addEventListener('submit', function (e) {
    e.preventDefault(); if (!canRaise) return;
    var subject = document.getElementById('f-subject').value.trim(), category = document.getElementById('f-category').value,
        message = document.getElementById('f-message').value.trim(), img = document.getElementById('f-image').files[0], bad = false;
    if (subject.length < 5) { showErr('subject', 'Subject must be at least 5 characters.'); bad = true; }
    if (!category) { showErr('category', 'Choose the issue type.'); bad = true; }
    if (message.length < 15) { showErr('message', 'Please describe the issue (at least 15 characters).'); bad = true; }
    var ie = validImage(img); if (ie) { showErr('image', ie); bad = true; }
    if (bad) return;
    var btn = document.getElementById('f-submit'); btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';
    var fd = new FormData();
    fd.append('subject', subject); fd.append('category', category); fd.append('message', message);
    fd.append('priority', document.querySelector('input[name=prio]:checked').value);
    if (img) fd.append('image', img);
    api('raise', 'POST', fd).then(function (d) {
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Ticket';
        if (!d.success) return toast(d.error || 'Could not raise ticket', 'error');
        showModal('<div class="modal-header"><h5 class="modal-title">✅ Ticket Raised</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>'
            + '<div class="modal-body text-center py-4"><div class="small text-muted">Your ticket number</div><div style="font-size:1.9rem;font-weight:800;font-family:monospace;color:#4e73df;margin:6px 0 10px">' + hsEsc(d.ticket_no || '') + '</div>'
            + '<div class="small text-muted">SV ERP support will reply here. You will see replies under My Tickets.</div></div><div class="modal-footer"><button class="btn btn-primary" data-dismiss="modal">OK</button></div>');
        document.getElementById('hs-form').reset(); clearImg('f'); hsStatus = 'all'; hsPage = 1; syncTabs(); loadTickets();
    });
});

// ── Ticket list ──
function syncTabs() { document.querySelectorAll('#hs-tabs [data-s]').forEach(function (t) { t.classList.toggle('on', t.dataset.s === hsStatus); }); }
document.querySelectorAll('#hs-tabs [data-s]').forEach(function (t) { t.addEventListener('click', function () { hsStatus = t.dataset.s; hsPage = 1; syncTabs(); loadTickets(); }); });
function loadTickets() {
    var list = document.getElementById('hs-list');
    list.innerHTML = '<div class="hs-empty"><i class="fas fa-spinner fa-spin"></i> Loading tickets…</div>';
    api('list&status=' + hsStatus + '&page=' + hsPage).then(function (d) {
        if (!d.success) { list.innerHTML = '<div class="hs-empty"><i class="fas fa-plug"></i> ' + hsEsc(d.error) + '</div>'; document.getElementById('hs-pager').innerHTML = ''; return; }
        var tickets = d.data.tickets || [], pg = d.data.pagination || {};
        list.innerHTML = tickets.map(function (t) {
            return '<div class="hs-ticket" onclick="openTicket(' + (+t.id) + ')"><div style="flex:1;min-width:0"><span class="no">' + hsEsc(t.ticket_no) + '</span> ' + stPill(t.status) + ' ' + prPill(t.priority)
                + '<div class="sub">' + hsEsc(t.subject) + '</div><div class="last">' + (t.latest_reply ? (t.latest_reply_from === 'admin' ? '💬 Support: ' : '↩️ You: ') + hsEsc(t.latest_reply) : hsEsc(t.message)) + '</div></div>'
                + '<div class="text-right small text-muted text-nowrap">' + fmtDT(t.created_at) + '<br>' + (+t.reply_count ? '<i class="far fa-comments"></i> ' + (+t.reply_count) : '') + '</div></div>';
        }).join('') || '<div class="hs-empty"><i class="fas fa-life-ring fa-2x d-block mb-2" style="opacity:.35"></i>No tickets found. Need help? Raise a ticket!</div>';
        document.getElementById('hs-pager').innerHTML = (pg.total_pages || 1) > 1
            ? 'Page ' + pg.current_page + ' of ' + pg.total_pages + ' <button class="btn btn-outline-secondary btn-sm" ' + (pg.current_page <= 1 ? 'disabled' : '') + ' onclick="hsPage--;loadTickets()">‹</button> <button class="btn btn-outline-secondary btn-sm" ' + (pg.current_page >= pg.total_pages ? 'disabled' : '') + ' onclick="hsPage++;loadTickets()">›</button>' : '';
    });
}

// ── Ticket detail + replies ──
function openTicket(id) {
    showModal('<div class="modal-body hs-empty"><i class="fas fa-spinner fa-spin"></i> Loading ticket…</div>');
    api('view&ticket_id=' + id).then(function (d) {
        if (!d.success) { closeModal(); return toast(d.error || 'Could not load ticket', 'error'); }
        var t = d.data;
        var img = function (u) { return u ? '<a href="' + hsEsc(u) + '" target="_blank" rel="noopener"><img src="' + hsEsc(u) + '" alt="Attachment"></a>' : ''; };
        var thread = ['<div class="hs-msg me"><div class="who">You · ' + fmtDT(t.created_at) + '</div>' + hsEsc(t.message) + img(t.image_url) + '</div>']
            .concat((t.replies || []).map(function (r) { return '<div class="hs-msg ' + (r.sender_type === 'admin' ? 'them' : 'me') + '"><div class="who">' + (r.sender_type === 'admin' ? '🎧 ' + hsEsc(r.sender_name || 'SV ERP Support') : hsEsc(r.sender_name || 'You')) + ' · ' + fmtDT(r.created_at) + '</div>' + hsEsc(r.message) + img(r.image_url) + '</div>'; })).join('');
        showModal('<div class="modal-header"><h5 class="modal-title">' + hsEsc(t.ticket_no) + ' ' + stPill(t.status) + ' ' + prPill(t.priority) + '</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>'
            + '<div class="modal-body"><div class="font-weight-bold mb-2">' + hsEsc(t.subject) + '</div><div class="hs-thread" id="hs-thread">' + thread + '</div>'
            + (t.status === 'resolved' ? '<div class="small text-muted mt-2"><i class="fas fa-check-circle text-success"></i> This ticket is resolved. Replying will notify support if the issue is back.</div>' : '')
            + '<textarea class="form-control mt-3" id="r-message" rows="3" maxlength="5000" placeholder="Write a reply to SV ERP support…"></textarea>'
            + '<div class="d-flex align-items-center flex-wrap mt-2" style="gap:8px;"><label class="btn btn-outline-secondary btn-sm mb-0" style="cursor:pointer"><i class="fas fa-paperclip"></i> Attach image<input type="file" id="r-image" accept="image/jpeg,image/png,image/webp" hidden></label>'
            + '<span class="hs-prev" id="r-prev" hidden style="margin:0"><img alt=""><span class="small"></span><button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearImg(\'r\')">✕</button></span>'
            + '<button class="btn btn-primary btn-sm ml-auto" id="r-send" onclick="sendReply(' + (+t.id) + ')"><i class="fas fa-reply"></i> Post Reply</button></div></div>');
        bindPreview('r-image', 'r-prev', null);
        var th = document.getElementById('hs-thread'); th.scrollTop = th.scrollHeight;
    });
}
function sendReply(id) {
    var msg = document.getElementById('r-message').value.trim(); if (!msg) return toast('Write a reply first', 'info');
    var btn = document.getElementById('r-send'); btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
    var fd = new FormData(); fd.append('ticket_id', id); fd.append('message', msg);
    var img = document.getElementById('r-image').files[0]; if (img) fd.append('image', img);
    api('reply', 'POST', fd).then(function (d) {
        if (!d.success) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-reply"></i> Post Reply'; return toast(d.error || 'Reply failed', 'error'); }
        toast('Reply sent to SV ERP support', 'success'); openTicket(id); loadTickets();
    });
}

// ── Owner: connection settings ──
function saveConfig() {
    var fd = new FormData();
    fd.append('crm_api_base_url', document.getElementById('c-base').value);
    fd.append('crm_api_key', document.getElementById('c-key').value);
    fd.append('sverp_license_key', document.getElementById('c-license').value);
    api('save_config', 'POST', fd).then(function (d) {
        if (!d.success) return toast(d.error || 'Save failed', 'error');
        document.getElementById('c-key').value = ''; document.getElementById('c-key').placeholder = '•••••••• saved — leave blank to keep';
        toast('Saved — checking connection…', 'success'); setCanRaise(true, ''); loadLicense(); loadTickets();
    });
}

// jQuery (and the footer's CSRF helpers) load after this view, so start once the page is parsed
document.addEventListener('DOMContentLoaded', function () {
    loadLicense(); loadTickets();
    var _openId = parseInt(new URLSearchParams(location.search).get('ticket'), 10);
    if (_openId > 0) openTicket(_openId);
});
</script>
