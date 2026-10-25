<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Update_cp_temp extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function index() {
        echo "<h3>Starting Cost Price (CP) retroactive update...</h3>";

        // Get all products
        $products = $this->db->select('id, name')->get('products')->result();
        $updated_count = 0;

        foreach ($products as $p) {
            // Find the most recent authorized purchase item for this product
            $latest_item = $this->db->select('pi.qty, pi.total')
                                  ->from('purchase_items pi')
                                  ->join('purchases p', 'p.id = pi.purchase_id')
                                  ->where('pi.product_id', $p->id)
                                  ->where_in('p.status', ['received', 'partial'])
                                  ->order_by('p.invoice_date', 'DESC')
                                  ->order_by('p.id', 'DESC')
                                  ->limit(1)
                                  ->get()
                                  ->row();

            if ($latest_item && $latest_item->qty > 0) {
                $new_cp = round($latest_item->total / $latest_item->qty, 2);
                
                $this->db->set('cost_price', $new_cp)
                       ->where('id', $p->id)
                       ->update('products');
                       
                echo "Updated '{$p->name}' CP to {$new_cp}<br>";
                $updated_count++;
            }
        }

        echo "<br><h3>Done! Successfully updated CP for {$updated_count} products.</h3>";
        echo "<p style='color:red;'><strong>YOU CAN NOW DELETE THIS FILE FOR SECURITY.</strong></p>";
    }
}
