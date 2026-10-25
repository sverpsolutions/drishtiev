<?php // FILE: application/views/payments/cheque_print.php ?>
<!DOCTYPE html>
<?php
if (!function_exists('cheque_num_to_words')) {
    function cheque_num_to_words(float $num): string {
        $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                 'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                 'Seventeen','Eighteen','Nineteen'];
        $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        $hw = null;
        $hw = function(int $n) use (&$hw, $ones, $tens): string {
            if ($n < 20) return $ones[$n];
            if ($n < 100) return $tens[(int)($n/10)] . ($n%10 ? ' '.$ones[$n%10] : '');
            return $ones[(int)($n/100)] . ' Hundred' . ($n%100 ? ' ' . $hw($n%100) : '');
        };
        $num    = round($num, 2);
        $rupees = (int)$num;
        $paise  = (int)round(($num - $rupees) * 100);
        $parts  = [];
        if ($rupees >= 10000000) { $parts[] = $hw((int)($rupees/10000000)).' Crore'; $rupees %= 10000000; }
        if ($rupees >= 100000)   { $parts[] = $hw((int)($rupees/100000)).' Lakh';   $rupees %= 100000; }
        if ($rupees >= 1000)     { $parts[] = $hw((int)($rupees/1000)).' Thousand'; $rupees %= 1000; }
        if ($rupees > 0)           $parts[] = $hw($rupees);
        $result = implode(' ', $parts) ?: 'Zero';
        $result .= ' Rupees';
        if ($paise > 0) $result .= ' and ' . $hw($paise) . ' Paise';
        return $result . ' Only/-';
    }
}

define('CHQ_W_MM', 215.9); // 8.5 inches
define('CHQ_H_MM', 88.9);  // 3.5 inches

$chequeDate    = $payment->payment_date;
$dateFormatted = $chequeDate ? date('d / m / Y', strtotime($chequeDate)) : '';
$amtFormatted  = '₹ ' . number_format((float)$payment->amount, 2);
$amtWords      = cheque_num_to_words((float)$payment->amount);
$partyName     = $payment->party_name;
$csrfName      = $this->security->get_csrf_token_name();
$csrfHash      = $this->security->get_csrf_hash();
?>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Cheque — <?= htmlspecialchars($payment->payment_no) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
  --bg:#0f1117; --card:#161c2d; --border:#1f2842; --fg:#e2e8f0;
  --mut:#5a6490; --mut2:#8892b8; --acc:#00d4a0; --acc2:#4db8ff;
  --warn:#f59e0b; --red:#f43f5e; --green:#10b981;
  --mono:'Courier New',monospace;
}
body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);color:var(--fg);min-height:100vh;padding:0}
.shell{display:grid;grid-template-rows:auto 1fr;height:100vh}
.topbar{background:var(--card);border-bottom:1px solid var(--border);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;flex-shrink:0}
.content{display:grid;grid-template-columns:1fr 320px;overflow:hidden}
.preview-pane{overflow:auto;padding:24px;background:var(--bg);display:flex;flex-direction:column;align-items:center}
.control-pane{background:var(--card);border-left:1px solid var(--border);overflow-y:auto;padding:16px}
.btn{padding:8px 16px;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:.15s;white-space:nowrap}
.btn:hover{filter:brightness(1.1)}
.btn:disabled{opacity:.4;cursor:not-allowed;filter:none}
.btn-primary{background:var(--acc);color:#000}
.btn-blue{background:var(--acc2);color:#000}
.btn-ghost{background:rgba(255,255,255,.07);color:var(--fg);border:1px solid var(--border)}
.btn-sm{padding:6px 12px;font-size:12px}
.info-strip{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:18px;width:100%;max-width:800px}
.ic{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:8px 14px;min-width:110px}
.il{font-size:9px;color:var(--mut);text-transform:uppercase;letter-spacing:.8px;margin-bottom:3px;font-family:var(--mono)}
.iv{font-size:13px;font-weight:700;color:var(--fg)}
.cheque-container{width:215.9mm;height:88.9mm;position:relative;background:#fffef5;border:2px dashed #94a3b8;border-radius:3px;overflow:hidden;flex-shrink:0;cursor:crosshair}
.cheque-container.no-border{border-color:transparent}
.cf{position:absolute;cursor:move;user-select:none;padding:2px 4px;border:1.5px dashed transparent;border-radius:3px;line-height:1.25;color:#000;transition:border-color .12s, background .12s;white-space:nowrap}
.cf:hover{border-color:rgba(59,130,246,.5);background:rgba(59,130,246,.06)}
.cf.active{border-color:#3b82f6!important;background:rgba(59,130,246,.1)!important;box-shadow:0 0 0 2px rgba(59,130,246,.25)}
#cf-words{white-space:normal;max-width:170mm}
#cf-acpayee{font-weight:800;transform:rotate(-20deg);transform-origin:left center}
.sec-title{font-size:9px;font-weight:800;color:var(--mut);text-transform:uppercase;letter-spacing:1.2px;font-family:var(--mono);margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid var(--border)}
.field-pill{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;cursor:pointer;border:1.5px solid var(--border);background:var(--bg);margin-bottom:6px;transition:.15s}
.field-pill:hover{border-color:var(--acc2);background:rgba(77,184,255,.05)}
.field-pill.active{border-color:var(--acc2)!important;background:rgba(77,184,255,.1)!important}
.fp-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.fp-label{font-size:12px;font-weight:600;flex:1}
.fp-pos{font-size:10px;color:var(--mut);font-family:var(--mono)}
.nudge-wrap{margin-top:14px}
.axis-row{margin-bottom:12px}
.axis-label{font-size:10px;color:var(--mut);font-weight:700;margin-bottom:5px;font-family:var(--mono)}
.nudge-btns{display:flex;gap:4px;align-items:center}
.nb{width:30px;height:30px;border-radius:6px;border:1px solid var(--border);background:rgba(255,255,255,.05);color:var(--fg);font-size:14px;cursor:pointer;display:grid;place-items:center;transition:.12s;flex-shrink:0}
.nb:hover{background:var(--acc2);color:#000;border-color:var(--acc2)}
.nb:active{transform:scale(.9)}
.nb.large{font-size:11px;font-weight:700}
.pos-input{width:58px;padding:5px 7px;border:1px solid var(--border);border-radius:6px;background:rgba(255,255,255,.05);color:var(--fg);font-size:12px;font-family:var(--mono);text-align:center;outline:none}
.pos-input:focus{border-color:var(--acc2)}
.size-row{display:flex;gap:6px;align-items:center;margin-top:10px}
.size-label{font-size:10px;color:var(--mut);font-weight:700;width:40px;font-family:var(--mono)}
.kbd{background:rgba(255,255,255,.08);border:1px solid var(--border);border-radius:4px;padding:1px 6px;font-size:10px;font-family:var(--mono)}
.warn-box{background:rgba(245,158,11,.1);border:1px solid var(--warn);color:var(--warn);border-radius:8px;padding:8px 10px;font-size:11px;margin-bottom:14px}
@media print{
  body{background:transparent!important}
  .shell{display:block!important;height:auto!important}
  .topbar,.control-pane,.info-strip,.preview-pane > *:not(.cheque-container){display:none!important}
  .preview-pane{padding:0!important;display:block!important;overflow:visible!important}
  .content{display:block!important}
  .cheque-container{position:absolute!important;top:0!important;left:0!important;width:215.9mm!important;height:88.9mm!important;border:none!important;border-radius:0!important;overflow:visible!important;background:transparent!important}
  .cf{border:none!important;background:transparent!important;box-shadow:none!important}
  @page{size:215.9mm 88.9mm;margin:0}
}
</style>
</head>
<body>
<div class="shell">

<div class="topbar">
  <div>
    <div style="font-size:15px;font-weight:800">Cheque Print</div>
    <div style="font-size:11px;color:var(--mut);margin-top:1px">
      <?= htmlspecialchars($payment->payment_no) ?> &nbsp;·&nbsp;
      <?= $payment->bank ? htmlspecialchars($payment->bank->account_name) : 'No bank account on this payment' ?> &nbsp;·&nbsp;
      <?= $payment->reference_no ? 'Cheque #' . htmlspecialchars($payment->reference_no) : 'No cheque no.' ?>
    </div>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--mut);cursor:pointer">
      <input type="checkbox" id="toggle-border" checked onchange="toggleBorder(this.checked)" style="accent-color:var(--acc2)">
      Show border
    </label>
    <button class="btn btn-blue" id="btnSave" onclick="saveLayout()" <?= $payment->bank_account_id ? '' : 'disabled' ?>>Save Layout</button>
    <button class="btn btn-primary" onclick="saveAndPrint()">Save &amp; Print</button>
    <button class="btn btn-ghost btn-sm" onclick="window.print()">Print without saving</button>
    <button class="btn btn-ghost btn-sm" onclick="window.close()">✕</button>
  </div>
</div>

<div class="content">

<div class="preview-pane">

  <?php if (!$payment->bank_account_id): ?>
  <div class="warn-box" style="max-width:800px;width:100%">
    This payment has no bank account set, so the layout can't be saved for reuse. You can still position fields and print — go back and edit the payment to attach a bank account if you want the layout remembered.
  </div>
  <?php endif; ?>

  <div class="info-strip">
    <div class="ic"><div class="il">Party</div><div class="iv" style="font-size:12px"><?= htmlspecialchars($partyName) ?></div></div>
    <div class="ic"><div class="il">Amount</div><div class="iv" style="color:var(--green)">₹<?= number_format((float)$payment->amount, 2) ?></div></div>
    <div class="ic"><div class="il">Cheque Date</div><div class="iv"><?= $dateFormatted ?></div></div>
    <div class="ic"><div class="il">Cheque No</div><div class="iv" style="font-family:var(--mono)"><?= htmlspecialchars($payment->reference_no ?? '—') ?></div></div>
    <?php if ($payment->bank): ?>
    <div class="ic"><div class="il">Bank</div><div class="iv" style="font-size:11px"><?= htmlspecialchars($payment->bank->account_name) ?><?= $payment->bank->bank_name ? ' / ' . htmlspecialchars($payment->bank->bank_name) : '' ?></div></div>
    <?php endif; ?>
  </div>

  <div style="background:#fff;border-radius:4px;box-shadow:0 4px 24px rgba(0,0,0,.5);display:inline-block">
    <div class="cheque-container" id="cheque-area">

      <?php if ($L['f_acpayee_show']): ?>
      <div class="cf" id="cf-acpayee" data-field="acpayee"
           style="top:<?= $L['f_acpayee_top'] ?>mm;left:<?= $L['f_acpayee_left'] ?>mm;font-size:<?= $L['f_acpayee_size'] ?>pt">
        A/C Payee Only
      </div>
      <?php endif; ?>

      <div class="cf" id="cf-party" data-field="party"
           style="top:<?= $L['f_party_top'] ?>mm;left:<?= $L['f_party_left'] ?>mm;font-size:<?= $L['f_party_size'] ?>pt">
        <?= htmlspecialchars($partyName) ?>
      </div>

      <div class="cf" id="cf-amount" data-field="amount"
           style="top:<?= $L['f_amount_top'] ?>mm;left:<?= $L['f_amount_left'] ?>mm;font-size:<?= $L['f_amount_size'] ?>pt;font-weight:700;font-family:var(--mono)">
        <?= $amtFormatted ?>/-
      </div>

      <div class="cf" id="cf-words" data-field="words"
           style="top:<?= $L['f_words_top'] ?>mm;left:<?= $L['f_words_left'] ?>mm;font-size:<?= $L['f_words_size'] ?>pt">
        <?= $amtWords ?>
      </div>

      <div class="cf" id="cf-date" data-field="date"
           style="top:<?= $L['f_date_top'] ?>mm;left:<?= $L['f_date_left'] ?>mm;font-size:<?= $L['f_date_size'] ?>pt;font-family:var(--mono);letter-spacing:2px">
        <?= $dateFormatted ?>
      </div>

    </div>
  </div>

  <div style="margin-top:14px;font-size:10px;color:var(--mut);text-align:center">
    8.5" × 3.5" (215.9mm × 88.9mm) — Indian Standard Cheque
    &nbsp;·&nbsp; Drag fields to move &nbsp;·&nbsp; Use arrow buttons or <span class="kbd">←↑→↓</span> keys to nudge
  </div>

</div>

<div class="control-pane">

  <div class="sec-title">Select Field</div>
  <?php
  $fields = [
    'party'   => ['Party Name',       '#f59e0b'],
    'amount'  => ['Amount (₹)',        '#10b981'],
    'words'   => ['Amount in Words',   '#3b82f6'],
    'date'    => ['Date',              '#8b5cf6'],
  ];
  if ($L['f_acpayee_show']) $fields['acpayee'] = ['A/C Payee Only', '#ef4444'];
  foreach ($fields as $fk => [$fl, $fc]): ?>
  <div class="field-pill" data-field="<?= $fk ?>" onclick="selectField('<?= $fk ?>')">
    <div class="fp-dot" style="background:<?= $fc ?>"></div>
    <span class="fp-label"><?= $fl ?></span>
    <span class="fp-pos" id="pos-<?= $fk ?>">T:<?= $L["f_{$fk}_top"] ?> L:<?= $L["f_{$fk}_left"] ?></span>
  </div>
  <?php endforeach; ?>

  <div class="nudge-wrap" id="nudge-section" style="opacity:.35;pointer-events:none">
    <div style="height:1px;background:var(--border);margin:14px 0 14px"></div>
    <div class="sec-title" id="nudge-title">Position</div>

    <div class="axis-row">
      <div class="axis-label">TOP (mm)</div>
      <div class="nudge-btns">
        <button class="nb large" onclick="nudge('top',-5)" title="-5mm">↑↑</button>
        <button class="nb" onclick="nudge('top',-1)" title="-1mm">↑</button>
        <button class="nb" onclick="nudge('top',-.5)" title="-0.5mm" style="font-size:11px">↑½</button>
        <input class="pos-input" id="inp-top" type="number" step="0.5" onchange="setAxis('top',+this.value)"/>
        <button class="nb" onclick="nudge('top',.5)" title="+0.5mm" style="font-size:11px">↓½</button>
        <button class="nb" onclick="nudge('top',1)" title="+1mm">↓</button>
        <button class="nb large" onclick="nudge('top',5)" title="+5mm">↓↓</button>
      </div>
    </div>

    <div class="axis-row">
      <div class="axis-label">LEFT (mm)</div>
      <div class="nudge-btns">
        <button class="nb large" onclick="nudge('left',-5)" title="-5mm">←←</button>
        <button class="nb" onclick="nudge('left',-1)" title="-1mm">←</button>
        <button class="nb" onclick="nudge('left',-.5)" title="-0.5mm" style="font-size:11px">←½</button>
        <input class="pos-input" id="inp-left" type="number" step="0.5" onchange="setAxis('left',+this.value)"/>
        <button class="nb" onclick="nudge('left',.5)" title="+0.5mm" style="font-size:11px">→½</button>
        <button class="nb" onclick="nudge('left',1)" title="+1mm">→</button>
        <button class="nb large" onclick="nudge('left',5)" title="+5mm">→→</button>
      </div>
    </div>

    <div class="axis-row">
      <div class="axis-label">FONT SIZE (pt)</div>
      <div class="size-row">
        <button class="nb" onclick="nudgeSize(-1)" title="-1pt" style="font-size:11px;font-weight:700">A-</button>
        <button class="nb" onclick="nudgeSize(-.5)" title="-0.5pt" style="font-size:10px">A½-</button>
        <input class="pos-input" id="inp-size" type="number" step="0.5" min="6" max="22" onchange="setSize(+this.value)"/>
        <button class="nb" onclick="nudgeSize(.5)" title="+0.5pt" style="font-size:10px">A½+</button>
        <button class="nb" onclick="nudgeSize(1)" title="+1pt" style="font-size:11px;font-weight:700">A+</button>
      </div>
    </div>

    <div style="font-size:10px;color:var(--mut);line-height:1.7;margin-top:8px">
      <span class="kbd">↑↓←→</span> ±1mm &nbsp;
      <span class="kbd">Shift+Arrow</span> ±0.5mm<br>
      Drag the field on the cheque to reposition
    </div>
  </div>

  <div style="margin-top:18px;border-top:1px solid var(--border);padding-top:14px">
    <div class="sec-title">Reset</div>
    <button class="btn btn-ghost btn-sm" style="width:100%;justify-content:center" onclick="resetAll()">↺ Reset all fields to default</button>
  </div>

  <div style="margin-top:16px;border-top:1px solid var(--border);padding-top:14px">
    <div class="sec-title">Payment Info</div>
    <div style="font-size:11px;color:var(--mut);line-height:2">
      <div>No: <span style="color:var(--fg);font-family:var(--mono)"><?= htmlspecialchars($payment->payment_no) ?></span></div>
      <div>Words: <span style="color:var(--fg);font-size:10px"><?= $amtWords ?></span></div>
    </div>
  </div>

</div>
</div>
</div>

<script>
const POS = {
  party:   {top:<?= $L['f_party_top'] ?>,   left:<?= $L['f_party_left'] ?>,   size:<?= $L['f_party_size'] ?>},
  amount:  {top:<?= $L['f_amount_top'] ?>,  left:<?= $L['f_amount_left'] ?>,  size:<?= $L['f_amount_size'] ?>},
  words:   {top:<?= $L['f_words_top'] ?>,   left:<?= $L['f_words_left'] ?>,   size:<?= $L['f_words_size'] ?>},
  date:    {top:<?= $L['f_date_top'] ?>,    left:<?= $L['f_date_left'] ?>,    size:<?= $L['f_date_size'] ?>},
  acpayee: {top:<?= $L['f_acpayee_top'] ?>, left:<?= $L['f_acpayee_left'] ?>, size:<?= $L['f_acpayee_size'] ?>},
};
const DEFAULTS = {
  party:   {top:<?= $defaults['f_party_top'] ?>,   left:<?= $defaults['f_party_left'] ?>,   size:<?= $defaults['f_party_size'] ?>},
  amount:  {top:<?= $defaults['f_amount_top'] ?>,  left:<?= $defaults['f_amount_left'] ?>,  size:<?= $defaults['f_amount_size'] ?>},
  words:   {top:<?= $defaults['f_words_top'] ?>,   left:<?= $defaults['f_words_left'] ?>,   size:<?= $defaults['f_words_size'] ?>},
  date:    {top:<?= $defaults['f_date_top'] ?>,    left:<?= $defaults['f_date_left'] ?>,    size:<?= $defaults['f_date_size'] ?>},
  acpayee: {top:<?= $defaults['f_acpayee_top'] ?>, left:<?= $defaults['f_acpayee_left'] ?>, size:<?= $defaults['f_acpayee_size'] ?>},
};

const CHQ_W = <?= CHQ_W_MM ?>;
const CHQ_H = <?= CHQ_H_MM ?>;
const BANK_ACCOUNT_ID = <?= (int)$payment->bank_account_id ?>;
const CSRF_NAME = '<?= $csrfName ?>';
let CSRF_HASH = '<?= $csrfHash ?>';
let active = null;

function selectField(fk) {
  document.querySelectorAll('.cf').forEach(e => e.classList.remove('active'));
  document.querySelectorAll('.field-pill').forEach(e => e.classList.remove('active'));
  const el   = document.getElementById('cf-' + fk);
  const pill = document.querySelector(`.field-pill[data-field="${fk}"]`);
  if (!el) return;
  el.classList.add('active');
  if (pill) pill.classList.add('active');
  active = fk;
  const ns = document.getElementById('nudge-section');
  ns.style.opacity = '1'; ns.style.pointerEvents = 'auto';
  document.getElementById('nudge-title').textContent = `Position — ${pill?.querySelector('.fp-label')?.textContent || fk}`;
  syncInputs();
}

function syncInputs() {
  if (!active) return;
  document.getElementById('inp-top').value  = round(POS[active].top);
  document.getElementById('inp-left').value = round(POS[active].left);
  document.getElementById('inp-size').value = POS[active].size;
  document.querySelector(`#pos-${active}`).textContent = `T:${round(POS[active].top)} L:${round(POS[active].left)}`;
}

function round(v){ return Math.round(v * 10) / 10; }

function applyPos(fk) {
  const el = document.getElementById('cf-' + fk);
  if (!el) return;
  el.style.top      = POS[fk].top  + 'mm';
  el.style.left     = POS[fk].left + 'mm';
  el.style.fontSize = POS[fk].size + 'pt';
  const posEl = document.querySelector(`#pos-${fk}`);
  if (posEl) posEl.textContent = `T:${round(POS[fk].top)} L:${round(POS[fk].left)}`;
  if (active === fk) syncInputs();
}

function nudge(axis, delta) {
  if (!active) return;
  POS[active][axis] = round(POS[active][axis] + delta);
  applyPos(active);
}
function nudgeSize(delta) {
  if (!active) return;
  POS[active].size = Math.max(6, Math.min(22, round(POS[active].size + delta)));
  applyPos(active);
}
function setAxis(axis, val) {
  if (!active) return;
  POS[active][axis] = round(val || 0);
  applyPos(active);
}
function setSize(val) {
  if (!active) return;
  POS[active].size = Math.max(6, Math.min(22, round(val || 10)));
  applyPos(active);
}
function resetAll() {
  if (!confirm('Reset all fields to default positions?')) return;
  Object.keys(DEFAULTS).forEach(fk => {
    POS[fk].top  = DEFAULTS[fk].top;
    POS[fk].left = DEFAULTS[fk].left;
    POS[fk].size = DEFAULTS[fk].size;
    applyPos(fk);
  });
  if (active) syncInputs();
}

document.addEventListener('keydown', e => {
  if (!active) return;
  if (['INPUT','TEXTAREA','SELECT'].includes(e.target.tagName)) return;
  const d = e.shiftKey ? 0.5 : 1;
  if (e.key === 'ArrowUp')    { nudge('top',  -d); e.preventDefault(); }
  if (e.key === 'ArrowDown')  { nudge('top',   d); e.preventDefault(); }
  if (e.key === 'ArrowLeft')  { nudge('left', -d); e.preventDefault(); }
  if (e.key === 'ArrowRight') { nudge('left',  d); e.preventDefault(); }
});

document.querySelectorAll('.cf').forEach(el => {
  const fk = el.dataset.field;
  let dragging = false, startX = 0, startY = 0, origLeft = 0, origTop = 0;

  el.addEventListener('mousedown', e => {
    e.preventDefault();
    selectField(fk);
    dragging = true;
    startX = e.clientX;
    startY = e.clientY;
    origLeft = POS[fk].left;
    origTop  = POS[fk].top;
  });

  document.addEventListener('mousemove', e => {
    if (!dragging) return;
    const area = document.getElementById('cheque-area');
    const rect = area.getBoundingClientRect();
    const pxPerMm_x = rect.width  / CHQ_W;
    const pxPerMm_y = rect.height / CHQ_H;
    const dxMm = (e.clientX - startX) / pxPerMm_x;
    const dyMm = (e.clientY - startY) / pxPerMm_y;
    POS[fk].left = round(Math.max(0, Math.min(CHQ_W - 10, origLeft + dxMm)));
    POS[fk].top  = round(Math.max(0, Math.min(CHQ_H - 5,  origTop  + dyMm)));
    applyPos(fk);
  });

  document.addEventListener('mouseup', () => { dragging = false; });
});

function toggleBorder(show) {
  const a = document.getElementById('cheque-area');
  a.classList.toggle('no-border', !show);
}

function buildPayload() {
  return {
    bank_account_id: BANK_ACCOUNT_ID,
    f_party_top:    POS.party.top,   f_party_left:   POS.party.left,   f_party_size:   POS.party.size,
    f_amount_top:   POS.amount.top,  f_amount_left:  POS.amount.left,  f_amount_size:  POS.amount.size,
    f_words_top:    POS.words.top,   f_words_left:   POS.words.left,   f_words_size:   POS.words.size,
    f_date_top:     POS.date.top,    f_date_left:    POS.date.left,    f_date_size:    POS.date.size,
    f_acpayee_top:  POS.acpayee.top, f_acpayee_left: POS.acpayee.left, f_acpayee_size: POS.acpayee.size,
    f_acpayee_show: 1,
  };
}

function saveLayout() {
  if (!BANK_ACCOUNT_ID) {
    toast('⚠ No bank account on this payment', 'var(--red)');
    return Promise.resolve(false);
  }
  const fd = new FormData();
  const pl = buildPayload();
  Object.entries(pl).forEach(([k,v]) => fd.append(k, v));
  fd.append(CSRF_NAME, CSRF_HASH);
  return fetch('<?= base_url('payments/save_cheque_layout') ?>', {
      method: 'POST', body: fd, headers: {'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(d => {
      if (d.ok) { toast('✓ Layout saved for this bank!', 'var(--acc)'); }
      else      { toast('⚠ Save failed: ' + d.err, 'var(--red)'); }
      return d.ok;
    }).catch(() => { toast('⚠ Network error', 'var(--red)'); return false; });
}

async function saveAndPrint() {
  const ok = await saveLayout();
  setTimeout(() => window.print(), ok ? 500 : 200);
}

function toast(msg, color) {
  const d = document.createElement('div');
  d.style.cssText = `position:fixed;top:70px;right:20px;background:${color};color:#000;
    padding:10px 18px;border-radius:8px;font-size:13px;font-weight:700;z-index:9999;
    box-shadow:0 4px 16px rgba(0,0,0,.3);pointer-events:none`;
  d.textContent = msg;
  document.body.appendChild(d);
  setTimeout(() => d.remove(), 3000);
}

window.addEventListener('DOMContentLoaded', () => selectField('party'));
</script>
</body>
</html>
