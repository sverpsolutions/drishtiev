<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FixRefundStock2 extends CI_Controller {

    public function index() {
        if ($this->input->get('token') !== 'fix9823refund2') { show_404(); return; }

        $dry = $this->input->get('apply') !== '1';

        header('Content-Type: text/plain; charset=utf-8');
        echo $dry ? "=== DRY RUN (add &apply=1 to execute) ===\n\n" : "=== APPLYING FIXES ===\n\n";

        // Find all processed refunds missing movement log entries
        $sql = "
            SELECT r.id AS refund_id, r.refund_no, r.created_at,
                   ri.product_id, ri.product_name, ri.qty,
                   i.location_id, COALESCE(l.name, '') AS location_name
            FROM refund_items ri
            JOIN refunds r ON r.id = ri.refund_id
            JOIN invoices i ON i.id = r.invoice_id
            LEFT JOIN locations l ON l.id = i.location_id
            WHERE r.status = 'processed'
              AND NOT EXISTS (
                  SELECT 1 FROM stock_movement_log sml
                  WHERE sml.reference_type = 'refund'
                    AND sml.reference_id = r.id
                    AND sml.movement_type = 'refund_in'
                    AND sml.product_id = ri.product_id
              )
            ORDER BY r.id, ri.id
        ";

        $missing = $this->db->query($sql)->result();
        echo "Found " . count($missing) . " refund item(s) without movement entries.\n\n";

        if (empty($missing)) {
            echo "Nothing to fix!\n";
            return;
        }

        $fixed = 0;
        foreach ($missing as $m) {
            $pid = (int)$m->product_id;
            $lid = (int)$m->location_id;
            $qty = (float)$m->qty;

            // Get current location stock
            $row = $this->db->select('stock_qty')
                ->where('product_id', $pid)
                ->where('location_id', $lid)
                ->get('product_location_stock')->row();
            $before = $row ? (float)$row->stock_qty : 0;
            $after  = $before + $qty;

            echo sprintf("[%s] %s (pid=%d) loc=%d(%s) qty=+%.1f  stock: %.1f -> %.1f\n",
                $m->refund_no, mb_substr($m->product_name, 0, 30), $pid, $lid, $m->location_name, $qty, $before, $after);

            if (!$dry) {
                // 1. Update location stock
                if ($row) {
                    $this->db->where('product_id', $pid)
                        ->where('location_id', $lid)
                        ->set('stock_qty', "stock_qty + {$qty}", false)
                        ->update('product_location_stock');
                } else {
                    $this->db->insert('product_location_stock', [
                        'product_id' => $pid, 'location_id' => $lid, 'stock_qty' => $qty
                    ]);
                }
                echo "   -> stock updated: " . ($this->db->affected_rows() ? "OK" : "FAIL") . "\n";

                // 2. Sync global stock
                $total = $this->db->select_sum('stock_qty')
                    ->where('product_id', $pid)
                    ->get('product_location_stock')->row();
                $global = $total ? (float)$total->stock_qty : 0;
                $this->db->where('id', $pid)->update('products', ['stock_qty' => $global]);
                echo "   -> global stock synced to {$global}: " . ($this->db->affected_rows() >= 0 ? "OK" : "FAIL") . "\n";

                // 3. Get updated stock for balance_after
                $new_row = $this->db->select('stock_qty')
                    ->where('product_id', $pid)
                    ->where('location_id', $lid)
                    ->get('product_location_stock')->row();
                $actual_after = $new_row ? (float)$new_row->stock_qty : $after;

                // 4. Insert movement log
                $this->db->insert('stock_movement_log', [
                    'product_id'     => $pid,
                    'product_name'   => $m->product_name,
                    'location_id'    => $lid,
                    'location_name'  => $m->location_name,
                    'movement_type'  => 'refund_in',
                    'quantity'       => $qty,
                    'balance_before' => $before,
                    'balance_after'  => $actual_after,
                    'reference_type' => 'refund',
                    'reference_id'   => $m->refund_id,
                    'reference_no'   => $m->refund_no,
                    'user_id'        => 0,
                    'user_name'      => 'System Fix',
                    'created_at'     => $m->created_at,
                ]);
                echo "   -> movement log: " . ($this->db->affected_rows() ? "OK (id=" . $this->db->insert_id() . ")" : "FAIL") . "\n";

                $fixed++;
            }
        }

        echo "\n" . ($dry ? "DRY RUN — {$fixed} items would be fixed. Add &apply=1 to execute." : "DONE — {$fixed} items fixed.") . "\n";
    }
}
