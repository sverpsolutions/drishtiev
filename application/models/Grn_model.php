<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Grn_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        if (!$this->db->field_exists('mrp', 'grn_items')) {
            $this->db->query("ALTER TABLE grn_items ADD COLUMN mrp decimal(12,2) DEFAULT 0.00");
        }
        if (!$this->db->field_exists('selling_price', 'grn_items')) {
            $this->db->query("ALTER TABLE grn_items ADD COLUMN selling_price decimal(12,2) DEFAULT 0.00");
        }
        if (!$this->db->field_exists('converted_invoice_id', 'grn')) {
            $this->db->query("ALTER TABLE grn ADD COLUMN converted_invoice_id INT(11) DEFAULT NULL COMMENT 'Sales invoice this GRN was converted into, if any' AFTER qc_notes");
        }
        $this->load->model('Location_model');
    }

    public function get_all($filters = []) {
        $this->db->select('grn.*, suppliers.name as supplier_name, purchase_orders.po_number, users.name as received_by_name')
            ->join('suppliers',       'suppliers.id = grn.supplier_id',           'left')
            ->join('purchase_orders', 'purchase_orders.id = grn.po_id',           'left')
            ->join('users',           'users.id = grn.received_by',               'left')
            ->order_by('grn.id', 'DESC');
        if (!empty($filters['status']))      $this->db->where('grn.status', $filters['status']);
        if (!empty($filters['supplier_id'])) $this->db->where('grn.supplier_id', $filters['supplier_id']);
        return $this->db->get('grn')->result();
    }

    public function get_by_id($id) {
        return $this->db
            ->select('grn.*, suppliers.name as supplier_name, purchase_orders.po_number, users.name as received_by_name')
            ->join('suppliers',       'suppliers.id = grn.supplier_id',     'left')
            ->join('purchase_orders', 'purchase_orders.id = grn.po_id',     'left')
            ->join('users',           'users.id = grn.received_by',         'left')
            ->where('grn.id', $id)->get('grn')->row();
    }

    public function get_items($grn_id) {
        return $this->db
            ->select('grn_items.*, products.name as product_name, products.item_code, products.unit, products.hsn_code')
            ->join('products', 'products.id = grn_items.product_id', 'left')
            ->where('grn_items.grn_id', $grn_id)
            ->get('grn_items')->result();
    }

    // Only the lines actually accepted into stock — those are what a pass-through
    // sales invoice can legitimately bill out.
    public function get_accepted_items($grn_id) {
        return $this->db
            ->select('grn_items.*, products.name as product_name, products.item_code, products.unit, products.hsn_code, products.mrp as product_mrp')
            ->join('products', 'products.id = grn_items.product_id', 'left')
            ->where('grn_items.grn_id', $grn_id)
            ->where('grn_items.accepted_qty >', 0)
            ->get('grn_items')->result();
    }

    public function mark_converted($grn_id, $invoice_id) {
        $this->db->where('id', $grn_id)->update('grn', ['converted_invoice_id' => $invoice_id]);
    }

    public function create($data, $items) {
        $data['grn_number'] = $this->_next_grn_number();
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('grn', $data);
        $grn_id = $this->db->insert_id();
        foreach ($items as $item) {
            $item['grn_id'] = $grn_id;
            $this->db->insert('grn_items', $item);
        }
        return $grn_id;
    }

    public function accept($grn_id, $received_by, $qc_notes = '') {
        $this->db->where('id', $grn_id)->update('grn', [
            'status'      => 'accepted',
            'received_by' => $received_by,
            'qc_notes'    => $qc_notes,
            'accepted_by' => $received_by,
            'accepted_at' => date('Y-m-d H:i:s'),
        ]);
        // Update stock for each accepted item
        $items = $this->get_items($grn_id);
        $grn   = $this->get_by_id($grn_id);
        foreach ($items as $item) {
            $qty = $item->accepted_qty > 0 ? $item->accepted_qty : $item->received_qty;
            if ($qty > 0) {
                if (!empty($item->variant_id)) {
                    $this->load->model('Garment_model');
                    $loc_id = !empty($grn->location_id) ? $grn->location_id : 1;
                    $this->Garment_model->increase_variant_stock($item->variant_id, $item->product_id, $loc_id, $qty);
                    
                    $v_upd = [];
                    if (isset($item->rate) && $item->rate > 0) $v_upd['cost_price'] = $item->rate;
                    if (isset($item->mrp) && $item->mrp > 0) $v_upd['mrp'] = $item->mrp;
                    if (isset($item->selling_price) && $item->selling_price > 0) $v_upd['selling_price'] = $item->selling_price;
                    if (!empty($v_upd)) {
                        $this->db->where('id', $item->variant_id)->update('garment_variants', $v_upd);
                    }
                } else if (!empty($grn->location_id)) {
                    // Credits the location this GRN was received at, so it's available to
                    // Stock Transfer / location-wise inventory immediately
                    $this->Location_model->increase_stock($item->product_id, $grn->location_id, $qty);
                } else {
                    // No location on this GRN (e.g. accepted while on "All Locations") —
                    // fall back to the old global-only behaviour since we can't attribute it
                    $this->db->set('stock_qty', "stock_qty + $qty", false)
                        ->where('id', $item->product_id)->update('products');
                }
                // Update last purchase cost in supplier_products
                $this->db->where('supplier_id', $grn->supplier_id)
                    ->where('product_id', $item->product_id)
                    ->update('supplier_products', ['cost_price' => $item->rate]);
                
                // Calculate cost_price (tax inclusive)
                $cost_price = $item->rate + ($item->rate * ($item->gst_pct / 100));

                $update_data = [
                    'basic_cost' => $item->rate,
                    'cost_price' => $cost_price
                ];

                if (isset($item->mrp) && $item->mrp > 0) {
                    $update_data['mrp'] = $item->mrp;
                }
                if (isset($item->selling_price) && $item->selling_price > 0) {
                    $update_data['selling_price'] = $item->selling_price;
                }

                // Also update product's basic cost, cost price, mrp, and sp
                $this->db->where('id', $item->product_id)
                    ->update('products', $update_data);

                // Inbound EV & component serials into item_serials on GRN acceptance
                if (!empty($item->serials_json) || !empty($item->serial_no) || !empty($item->chassis_no)) {
                    $this->load->model('Serial_model');
                    $serials_to_add = [];
                    if (!empty($item->serials_json)) {
                        $parsed = json_decode($item->serials_json, true);
                        if (is_array($parsed)) $serials_to_add = $parsed;
                    }
                    if (empty($serials_to_add) && !empty($item->chassis_no)) {
                        $serials_to_add[] = [
                            'serial_no'  => $item->chassis_no,
                            'chassis_no' => $item->chassis_no,
                            'motor_no'   => $item->motor_no ?? null,
                            'colour'     => $item->colour ?? null,
                        ];
                    } elseif (empty($serials_to_add) && !empty($item->serial_no)) {
                        $serials_to_add[] = $item->serial_no;
                    }

                    if (!empty($serials_to_add)) {
                        $this->Serial_model->create_inbound($serials_to_add, [
                            'product_id'    => $item->product_id,
                            'supplier_id'   => $grn->supplier_id ?? null,
                            'location_id'   => !empty($grn->location_id) ? $grn->location_id : 1,
                            'purchase_date' => $grn->received_date ?: date('Y-m-d'),
                            'mrp'           => $item->mrp ?: null,
                            'rate'          => $item->rate,
                            'txn_type'      => 'GRN_ACCEPTED',
                            'reference_id'  => $grn_id,
                            'reference_no'  => $grn->grn_number ?? ('GRN-' . $grn_id),
                        ]);
                    }
                }
            }
        }
        // Mark PO as received (only for PO-based GRNs; direct GRNs have no PO)
        if (!empty($grn->po_id)) {
            $this->db->where('id', $grn->po_id)->update('purchase_orders', [
                'status'      => 'received',
                'received_at' => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }
        return true;
    }

    // Reverses the stock added by a previously-accepted GRN, e.g. before re-editing it
    private function _reverse_stock($grn_id) {
        $grn   = $this->get_by_id($grn_id);
        $items = $this->get_items($grn_id);
        foreach ($items as $item) {
            $qty = $item->accepted_qty > 0 ? $item->accepted_qty : $item->received_qty;
            if ($qty > 0) {
                if (!empty($item->variant_id)) {
                    $this->load->model('Garment_model');
                    $loc_id = !empty($grn->location_id) ? $grn->location_id : 1;
                    $this->Garment_model->decrease_variant_stock($item->variant_id, $item->product_id, $loc_id, $qty);
                } else if (!empty($grn->location_id)) {
                    $this->Location_model->decrease_stock($item->product_id, $grn->location_id, $qty);
                } else {
                    $this->db->set('stock_qty', "GREATEST(stock_qty - $qty, 0)", false)
                        ->where('id', $item->product_id)->update('products');
                }
            }
        }
    }

    // Updates a GRN's header + line items. If it was already accepted, its stock
    // effect is reversed first and the GRN reverts to 'pending' for re-authorization.
    public function update($grn_id, $header, $items) {
        $grn = $this->get_by_id($grn_id);
        if (!$grn) return false;

        if ($grn->status === 'accepted') {
            $this->_reverse_stock($grn_id);
            $header['status']      = 'pending';
            $header['accepted_by'] = null;
            $header['accepted_at'] = null;
        }

        $this->db->where('id', $grn_id)->update('grn', $header);

        $this->db->where('grn_id', $grn_id)->delete('grn_items');
        foreach ($items as $item) {
            $item['grn_id'] = $grn_id;
            $this->db->insert('grn_items', $item);
        }
        return true;
    }

    // Accepted GRNs received at a given location — used by Stock Transfer's
    // "Load from GRN" so only stock actually attributed to that location can be pulled in
    public function search_accepted_by_location($location_id, $q = '') {
        $this->db->select('grn.id, grn.grn_number, grn.received_date, suppliers.name as supplier_name,
                            (SELECT COUNT(*) FROM grn_items WHERE grn_items.grn_id = grn.id) as item_count')
            ->join('suppliers', 'suppliers.id = grn.supplier_id', 'left')
            ->where('grn.status', 'accepted');
        if ($location_id) {
            $this->db->where('grn.location_id', $location_id);
        } else {
            $this->db->where('grn.location_id IS NOT NULL', null, false);
        }
        if ($q !== '') {
            $this->db->group_start()
                ->like('grn.grn_number', $q)
                ->or_like('suppliers.name', $q)
                ->group_end();
        }
        return $this->db->order_by('grn.id', 'DESC')->limit(20)->get('grn')->result();
    }

    private function _next_grn_number() {
        $row = $this->db->query("SELECT MAX(CAST(SUBSTRING(grn_number,5) AS UNSIGNED)) as mx FROM grn WHERE grn_number LIKE 'GRN-'")->row();
        // safer fallback
        $row2 = $this->db->select_max('id')->get('grn')->row();
        $n = ($row2->id ?? 0) + 1;
        return 'GRN-' . str_pad($n, 5, '0', STR_PAD_LEFT);
    }
}
