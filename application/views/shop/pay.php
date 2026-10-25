<div class="wrap auth-box" style="max-width:520px">
  <div class="card center">
    <h1 style="font-size:30px">Complete your payment</h1>
    <p>Order <strong><?= html_escape($o->order_no) ?></strong> · Amount <strong><?= inr($o->grand_total, 2) ?></strong></p>
    <p class="small muted">Your items are reserved for a limited time. Unpaid orders are cancelled automatically.</p>
    <?php if ($err): ?><div class="alert alert-error"><?= html_escape($err) ?></div>
      <a class="btn btn-primary" href="<?= current_url() ?>">Try again</a>
    <?php else: ?>
      <button class="btn btn-primary btn-block" id="rzp-btn">Pay <?= inr($o->grand_total, 2) ?> securely</button>
      <form id="rzp-form" method="post" action="<?= site_url('checkout/razorpay_verify') ?>" hidden>
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
        <input type="hidden" name="razorpay_order_id"><input type="hidden" name="razorpay_payment_id"><input type="hidden" name="razorpay_signature">
      </form>
      <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
      <script>
      (function () {
        var f = document.getElementById('rzp-form');
        var rzp = new Razorpay({
          key: <?= json_encode($key_id) ?>, amount: <?= (int)round($o->grand_total * 100) ?>, currency: 'INR',
          name: <?= json_encode(ucwords(strtolower($company['company_name'] ?? ''))) ?>, description: <?= json_encode('Order ' . $o->order_no) ?>,
          image: <?= json_encode(base_url('assets/web/icon-192.png')) ?>, order_id: <?= json_encode($o->gateway_order_id) ?>,
          prefill: {name: <?= json_encode($o->customer_name) ?>, contact: <?= json_encode($o->phone) ?>, email: <?= json_encode((string)$o->email) ?>},
          theme: {color: '#7a1420'},
          handler: function (r) { f.razorpay_order_id.value = r.razorpay_order_id; f.razorpay_payment_id.value = r.razorpay_payment_id; f.razorpay_signature.value = r.razorpay_signature; f.submit(); }
        });
        document.getElementById('rzp-btn').onclick = function (e) { e.preventDefault(); rzp.open(); };
        rzp.open();
      })();
      </script>
    <?php endif; ?>
  </div>
</div>
