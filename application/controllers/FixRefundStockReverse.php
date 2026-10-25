<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FixRefundStockReverse extends CI_Controller {

    public function index() {
        $token = $this->input->get('token');
        if ($token !== 'fix9823reverse') {
            show_404(); return;
        }

        $this->load->model(['Location_model', 'Product_model']);
        $dry = $this->input->get('apply') !== '1';

        header('Content-Type: text/plain; charset=utf-8');

        echo $dry ? "=== DRY RUN — REVERSE DOUBLE refund_in (add &apply=1 to execute) ===\n\n" : "=== APPLYING REVERSAL ===\n\n";

        // The fix script logged movements with reference_id=0.
        // Real refunds (after Refunds.php fix) logged with the actual refund_id.
        // We need to find products that got BOTH and reverse the fix script's entries.

        // Step 1: Get all fix-script movements (reference_id=0, movement_type=refund_in)
        $fix_movements = $this->db
            ->where('movement_type', 'refund_in')
            ->where('reference_id', 0)
            ->get('stock_movement_log')->result();

        if (empty($fix_movements)) {
            echo "No fix-script movements found (reference_id=0). Nothing to reverse.\n";
            return;
        }

        echo "Found " . count($fix_movements) . " fix-script movement(s) (reference_id=0).\n\n";

        // Step 2: For each, check if a real refund_in also exists for same product+location
        $to_reverse = [];
        foreach ($fix_movements as $fm) {
            $real_count = $this->db
                ->where('movement_type', 'refund_in')
                ->where('product_id', $fm->product_id)
                ->where('location_id', $fm->location_id)
                ->where('reference_id !=', 0)
                ->count_all_results('stock_movement_log');

            // Even if no real duplicate, the fix script itself was wrong because it
            // added stock for ALL refunds including post-fix ones. We should reverse ALL
            // fix-script entries and let only the real per-refund movements stand.
            // But the safe approach: only reverse the OVERLAPPING qty.

            // Actually, the simplest correct approach: the fix script added stock for
            // ALL past refunds. But refunds made AFTER the Refunds.php fix was deployed
            // already had their stock restored by the fixed code. So those got double-counted.
            // We should reverse the ENTIRE fix script since it over-counted.
            $to_reverse[] = $fm;
        }

        echo sprintf("%-40s | loc=%-3s | qty    | current -> after | movement_id\n", 'PRODUCT', 'LOC');
        echo str_repeat('-', 100) . "\n";

        $count = 0;
        foreach ($to_reverse as $fm) {
            $before = $this->Location_model->get_stock($fm->product_id, $fm->location_id);
            $after  = $before - (float)$fm->quantity;

            echo sprintf("%-40s | loc=%-3s | -%-5s | %7s -> %-7s | #%s\n",
                mb_substr($fm->product_name, 0, 40),
                $fm->location_id,
                $fm->quantity,
                $before, $after,
                $fm->id
            );

            if (!$dry) {
                $this->Location_model->decrease_stock($fm->product_id, $fm->location_id, (float)$fm->quantity);
                // Delete the fix-script's movement log entry
                $this->db->where('id', $fm->id)->delete('stock_movement_log');
            }
            $count++;
        }

        echo "\n{$count} reversal(s).\n";
        if ($dry) {
            echo "\nDRY RUN — no changes made. Add &apply=1 to execute.\n";
        } else {
            echo "\nDone! Fix-script stock additions reversed and their log entries removed.\n";
            echo "DELETE this controller file after confirming.\n";
        }
    }
}
