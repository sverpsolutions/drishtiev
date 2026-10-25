<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI-only self-check for the web order lifecycle. Everything runs inside one
 * transaction that is rolled back, so live data is untouched.
 *   php index.php WebstoreSelfTest
 */
class WebstoreSelfTest extends CI_Controller {

    private $fails = 0;

    public function index() {
        if (!is_cli()) show_404();
        $this->load->model('Webstore_model', 'ws');
        $this->load->model('Garment_model');
        $loc = $this->ws->location_id();

        $v = $this->db->query('SELECT gv.id, gv.product_id, ls.stock_qty FROM garment_variants gv JOIN garment_variant_location_stock ls ON ls.variant_id = gv.id AND ls.location_id = ?
            JOIN products p ON p.id = gv.product_id AND p.status = 1 AND p.web_visible = 1 WHERE gv.status = 1 AND ls.stock_qty >= 3 ORDER BY ls.stock_qty DESC LIMIT 1', [$loc])->row();
        if (!$v) { echo "SKIP: needs one web-visible variant with stock >= 3\n"; return; }
        $stock = function () use ($v, $loc) { return (float)$this->db->query('SELECT stock_qty FROM garment_variant_location_stock WHERE variant_id = ? AND location_id = ?', [$v->id, $loc])->row()->stock_qty; };

        $this->db->trans_begin();
        $this->ws->save_setting('web_cod_enabled', '1');
        $this->ws->save_setting('web_min_order', '0');
        $start = $stock();
        $cart = [$v->product_id . ':' . $v->id => ['p' => $v->product_id, 'v' => $v->id, 'q' => 2]];
        $form = ['name' => 'Selftest', 'phone' => '9000000001', 'email' => '', 'address_line' => 'Test address 1', 'landmark' => '', 'city' => 'Mathura',
                 'state_code' => '09', 'pincode' => '281001', 'notes' => '', 'coupon' => '', 'payment_method' => 'cod'];

        // 1. placement deducts stock; same key is idempotent
        $r = $this->ws->place_order($cart, $form, null, 'selftest-key-1');
        $this->check('order placed', $r['ok']);
        $o = $r['order'];
        $this->check('stock deducted by 2', $stock() == $start - 2);
        $r2 = $this->ws->place_order($cart, $form, null, 'selftest-key-1');
        $this->check('duplicate key returns same order', $r2['ok'] && $r2['order']->id == $o->id && !empty($r2['duplicate']));
        $this->check('no double deduction', $stock() == $start - 2);

        // 2. GST: intra-state → CGST+SGST, totals consistent
        $this->check('intra-state uses CGST/SGST', $o->igst == 0 && $o->cgst > 0 && abs($o->cgst + $o->sgst + $o->taxable_total - $o->grand_total) < 0.02);
        $pr = $this->ws->price_cart($cart, '', '07');
        $this->check('inter-state uses IGST', $pr['igst'] > 0 && $pr['cgst'] == 0);

        // 3. overselling is refused
        $big = [$v->product_id . ':' . $v->id => ['p' => $v->product_id, 'v' => $v->id, 'q' => (int)$stock() + 1]];
        $r3 = $this->ws->place_order($big, $form, null, 'selftest-key-2');
        $this->check('oversell refused', !$r3['ok'] && $stock() == $start - 2);

        // 4. cancel restores once
        $this->check('cancel needs reason', $this->ws->change_status($o->id, 'cancelled', '') !== true);
        $this->check('cancel ok', $this->ws->change_status($o->id, 'cancelled', 'selftest') === true);
        $this->check('stock restored', $stock() == $start);
        $this->check('second cancel refused', $this->ws->change_status($o->id, 'cancelled', 'again') !== true && $stock() == $start);

        // 5. full lifecycle + return with restock + payment idempotency + refund cap
        $o2 = $this->ws->place_order($cart, $form, null, 'selftest-key-3')['order'];
        foreach (['confirmed', 'packed', 'shipped', 'delivered'] as $s) $this->check("→ {$s}", $this->ws->change_status($o2->id, $s) === true);
        $this->check('invoice number issued', (bool)$this->ws->get_order($o2->id)->invoice_no);
        $this->check('skipping back refused', $this->ws->change_status($o2->id, 'new') !== true);
        $this->check('mark paid', $this->ws->mark_paid($o2->id, $o2->grand_total, 'COD', null, 'selftest') === true);
        $this->check('repeat payment callback is a no-op', $this->ws->mark_paid($o2->id, $o2->grand_total, 'COD', null, 'selftest') === 'already');
        $item = $this->ws->order_items($o2->id)[0];
        $this->check('return 1 with restock', $this->ws->return_items($o2->id, [$item->id => 1], true, 'selftest') === true && $stock() == $start - 1);
        $this->check('over-return refused', $this->ws->return_items($o2->id, [$item->id => 2], true, 'selftest') !== true);
        $this->check('refund above paid refused', $this->ws->add_refund($o2->id, $o2->grand_total + 1, 'UPI', '', 'processed', '') !== true);
        $this->check('partial refund ok', $this->ws->add_refund($o2->id, 10, 'UPI', 'r1', 'processed', '') === true && $this->ws->get_order($o2->id)->payment_status === 'partially_refunded');

        // 6. coupon maths
        $this->db->insert('web_coupons', ['code' => 'SELFTEST10', 'discount_type' => 'percent', 'discount_value' => 10, 'is_active' => 1]);
        $pc = $this->ws->price_cart($cart, 'SELFTEST10', '09');
        $this->check('10% coupon applied', abs($pc['discount'] - round($pc['subtotal'] * 0.10, 2)) < 0.01 && abs($pc['grand_total'] - ($pc['subtotal'] - $pc['discount'] + $pc['shipping'])) < 0.01);

        $this->db->trans_rollback();
        echo $this->fails ? "\n{$this->fails} CHECK(S) FAILED\n" : "\nALL CHECKS PASSED (changes rolled back)\n";
    }

    private function check($label, $ok) {
        echo ($ok ? '  ok   ' : '  FAIL ') . $label . "\n";
        if (!$ok) $this->fails++;
    }
}
