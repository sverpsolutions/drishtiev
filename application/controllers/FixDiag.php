<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FixDiag extends CI_Controller {

    public function index() {
        if ($this->input->get('token') !== 'fix9823diag') { show_404(); return; }

        header('Content-Type: text/plain; charset=utf-8');

        echo "=== REFUND STOCK DIAGNOSTIC ===\n\n";

        // 1. All processed refunds
        $refunds = $this->db->select('r.id, r.refund_no, r.created_at, r.invoice_id, i.invoice_no, i.location_id')
            ->from('refunds r')
            ->join('invoices i', 'i.id = r.invoice_id', 'left')
            ->where('r.status', 'processed')
            ->order_by('r.id', 'DESC')
            ->get()->result();

        echo "1. TOTAL PROCESSED REFUNDS: " . count($refunds) . "\n";
        foreach ($refunds as $r) {
            $items = $this->db->where('refund_id', $r->id)->get('refund_items')->result();
            echo sprintf("   %s (id=%d) | inv=%s | loc=%s | %d items | %s\n",
                $r->refund_no, $r->id, $r->invoice_no, $r->location_id, count($items), $r->created_at);
        }

        // 2. All refund_in movement entries
        echo "\n2. ALL refund_in MOVEMENT LOG ENTRIES:\n";
        $movements = $this->db->where('movement_type', 'refund_in')
            ->order_by('id', 'DESC')
            ->get('stock_movement_log')->result();

        echo "   Count: " . count($movements) . "\n";
        foreach ($movements as $m) {
            echo sprintf("   id=%d | prod=%s | loc=%d | qty=%.1f | ref_type=%s | ref_id=%s | ref_no=%s | %s\n",
                $m->id, mb_substr($m->product_name, 0, 25), $m->location_id, $m->quantity,
                $m->reference_type ?? 'NULL', $m->reference_id ?? 'NULL', $m->reference_no ?? 'NULL', $m->created_at);
        }

        // 3. Check what FixRefundStock would skip
        echo "\n3. SKIP CHECK - refunds with existing refund_in entries:\n";
        $existing = $this->db->select('reference_id, COUNT(*) as cnt')
            ->where('movement_type', 'refund_in')
            ->where('reference_type', 'refund')
            ->group_by('reference_id')
            ->get('stock_movement_log')->result();

        if (empty($existing)) {
            echo "   NONE — no refund_in entries exist at all\n";
        } else {
            foreach ($existing as $e) {
                echo sprintf("   reference_id=%s has %d entries\n", $e->reference_id, $e->cnt);
            }
        }

        // 4. Check if FixRefundStock controller exists
        echo "\n4. FILES CHECK:\n";
        $fix_path = APPPATH . 'controllers/FixRefundStock.php';
        echo "   FixRefundStock.php: " . (file_exists($fix_path) ? 'EXISTS' : 'MISSING') . "\n";
        $rev_path = APPPATH . 'controllers/FixRefundStockReverse.php';
        echo "   FixRefundStockReverse.php: " . (file_exists($rev_path) ? 'EXISTS' : 'MISSING') . "\n";

        echo "\nDone.\n";
    }
}
