<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FixRefundStock extends CI_Controller {

    public function index() {
        $token = $this->input->get('token');
        if ($token !== 'fix9823refund') {
            show_404(); return;
        }

        $this->load->model(['Location_model', 'Product_model']);
        $dry = $this->input->get('apply') !== '1';

        header('Content-Type: text/plain; charset=utf-8');

        echo $dry ? "=== DRY RUN (add &apply=1 to execute) ===\n\n" : "=== APPLYING FIXES ===\n\n";

        $refunds = $this->db->select('r.id, r.refund_no, r.created_at, i.invoice_no, i.location_id')
            ->from('refunds r')
            ->join('invoices i', 'i.id = r.invoice_id')
            ->where('r.status', 'processed')
            ->get()->result();

        // Skip refunds that already have movement entries
        $already_fixed = [];
        $existing = $this->db->select('DISTINCT reference_id')
            ->where('movement_type', 'refund_in')
            ->where('reference_type', 'refund')
            ->get('stock_movement_log')->result();
        foreach ($existing as $e) {
            $already_fixed[(int)$e->reference_id] = true;
        }

        $loc_cache = [];
        $count = 0;
        $skipped = 0;

        foreach ($refunds as $r) {
            if (isset($already_fixed[(int)$r->id])) {
                $skipped++;
                continue;
            }

            $loc_id = (int)$r->location_id;
            if (!isset($loc_cache[$loc_id])) {
                $loc_obj = $this->Location_model->get_by_id($loc_id);
                $loc_cache[$loc_id] = $loc_obj ? $loc_obj->name : '';
            }

            $items = $this->db->where('refund_id', $r->id)->get('refund_items')->result();
            foreach ($items as $ri) {
                $before = $this->Location_model->get_stock($ri->product_id, $loc_id);
                $after  = $before + (float)$ri->qty;

                echo sprintf("%s | %-30s | loc=%d | +%.0f | %.0f -> %.0f | %s\n",
                    $r->refund_no,
                    mb_substr($ri->product_name, 0, 30),
                    $loc_id,
                    $ri->qty,
                    $before, $after,
                    $r->invoice_no
                );

                if (!$dry) {
                    $this->Location_model->increase_stock($ri->product_id, $loc_id, (float)$ri->qty);
                    $new_stock = $this->Location_model->get_stock($ri->product_id, $loc_id);
                    $this->Location_model->log_movement([
                        'product_id'     => $ri->product_id,
                        'product_name'   => $ri->product_name,
                        'location_id'    => $loc_id,
                        'location_name'  => $loc_cache[$loc_id],
                        'movement_type'  => 'refund_in',
                        'quantity'       => $ri->qty,
                        'balance_before' => $before,
                        'balance_after'  => $new_stock,
                        'reference_type' => 'refund',
                        'reference_id'   => $r->id,
                        'reference_no'   => $r->refund_no,
                    ]);
                }
                $count++;
            }
        }

        echo "\n{$count} item(s) to restore. {$skipped} refund(s) already had movements (skipped).\n";
        if ($dry) {
            echo "\nDRY RUN — no changes made. Add &apply=1 to execute.\n";
        } else {
            echo "\nDone! Location stocks restored and movement log updated.\n";
            echo "DELETE this controller file after confirming.\n";
        }
    }
}
