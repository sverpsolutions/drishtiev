<?php
// ============================================================
// FILE: application/models/Scheme_model.php  (REBUILT)
// Full CRUD for scheme_master, slabs, item/party maps, reports
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Scheme_model extends CI_Model {

    // ── List all schemes with type label ──────────────────────
    public function get_all($filters = []) {
        $this->db->select('sm.*, st.label as type_label, st.code as type_code')
                 ->from('scheme_master sm')
                 ->join('scheme_types st', 'st.id = sm.scheme_type_id', 'left')
                 ->order_by('sm.priority', 'ASC')
                 ->order_by('sm.start_date', 'DESC');

        if (isset($filters['is_active'])) $this->db->where('sm.is_active', (int)$filters['is_active']);
        if (!empty($filters['type_id']))  $this->db->where('sm.scheme_type_id', $filters['type_id']);
        if (!empty($filters['search']))   $this->db->like('sm.name', $filters['search']);

        return $this->db->get()->result();
    }

    // ── Get all scheme types ──────────────────────────────────
    public function get_types() {
        return $this->db->order_by('id')->get('scheme_types')->result();
    }

    // ── Get single scheme with all relations ──────────────────
    public function get_by_id($id) {
        $scheme = $this->db->select('sm.*, st.label as type_label, st.code as type_code')
                           ->from('scheme_master sm')
                           ->join('scheme_types st', 'st.id = sm.scheme_type_id', 'left')
                           ->where('sm.id', $id)
                           ->get()->row();
        if (!$scheme) return null;

        $scheme->slabs        = $this->get_slabs($id);
        $scheme->item_map     = $this->get_item_map($id);
        $scheme->party_map    = $this->get_party_map($id);
        $scheme->free_items   = $this->get_free_items($id);
        $scheme->mix_groups   = $this->get_mix_groups($id);
        return $scheme;
    }

    // ── Create scheme (header only first) ─────────────────────
    public function create($data) {
        $this->db->insert('scheme_master', $data);
        return $this->db->insert_id();
    }

    // ── Update scheme header ──────────────────────────────────
    public function update($id, $data) {
        return $this->db->where('id', $id)->update('scheme_master', $data);
    }

    // ── Toggle active status ──────────────────────────────────
    public function toggle_active($id) {
        $current = $this->db->select('is_active')->where('id', $id)->get('scheme_master')->row();
        if (!$current) return false;
        return $this->db->where('id', $id)->update('scheme_master', ['is_active' => $current->is_active ? 0 : 1]);
    }

    // ── Delete scheme (cascades to slabs, maps, etc.) ─────────
    public function delete($id) {
        return $this->db->where('id', $id)->delete('scheme_master');
    }

    // ── Clone scheme ──────────────────────────────────────────
    public function clone_scheme($id) {
        $orig = $this->get_by_id($id);
        if (!$orig) return false;

        $this->db->trans_start();

        $header = (array)$orig;
        unset($header['id'], $header['slabs'], $header['item_map'], $header['party_map'], $header['free_items'], $header['mix_groups'], $header['type_label'], $header['type_code']);
        $header['name']       = $orig->name . ' (Copy)';
        $header['is_active']  = 0;
        $header['created_at'] = date('Y-m-d H:i:s');
        $header['updated_at'] = date('Y-m-d H:i:s');

        $this->db->insert('scheme_master', $header);
        $new_id = $this->db->insert_id();

        foreach ($orig->slabs as $s) {
            $r = (array)$s; unset($r['id']); $r['scheme_id'] = $new_id;
            $this->db->insert('scheme_slabs', $r);
        }
        foreach ($orig->item_map as $s) {
            $r = (array)$s; unset($r['id']); $r['scheme_id'] = $new_id;
            $this->db->insert('scheme_item_map', $r);
        }
        foreach ($orig->party_map as $s) {
            $r = (array)$s; unset($r['id']); $r['scheme_id'] = $new_id;
            $this->db->insert('scheme_party_map', $r);
        }
        foreach ($orig->free_items as $s) {
            $r = (array)$s; unset($r['id']); $r['scheme_id'] = $new_id;
            $this->db->insert('scheme_free_items', $r);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $new_id : false;
    }

    // ── Slabs ─────────────────────────────────────────────────
    public function get_slabs($scheme_id) {
        return $this->db->where('scheme_id', $scheme_id)->order_by('slab_order')->get('scheme_slabs')->result();
    }

    public function save_slabs($scheme_id, $slabs) {
        $this->db->where('scheme_id', $scheme_id)->delete('scheme_slabs');
        foreach ($slabs as $i => $s) {
            $this->db->insert('scheme_slabs', array_merge($s, ['scheme_id' => $scheme_id, 'slab_order' => $i + 1]));
        }
    }

    // ── Item Map ──────────────────────────────────────────────
    public function get_item_map($scheme_id) {
        return $this->db->select('sim.*, p.name as product_name')
                        ->from('scheme_item_map sim')
                        ->join('products p', 'p.id = sim.product_id', 'left')
                        ->where('sim.scheme_id', $scheme_id)
                        ->get()->result();
    }

    public function save_item_map($scheme_id, $product_ids, $brand_id = null, $category_id = null) {
        $this->db->where('scheme_id', $scheme_id)->delete('scheme_item_map');
        foreach ($product_ids as $pid) {
            $this->db->insert('scheme_item_map', ['scheme_id' => $scheme_id, 'product_id' => $pid, 'map_type' => 'trigger']);
        }
        if ($brand_id) {
            $this->db->insert('scheme_item_map', ['scheme_id' => $scheme_id, 'brand_id' => $brand_id, 'map_type' => 'trigger']);
        }
        if ($category_id) {
            $this->db->insert('scheme_item_map', ['scheme_id' => $scheme_id, 'category_id' => $category_id, 'map_type' => 'trigger']);
        }
    }

    // ── Party Map ─────────────────────────────────────────────
    public function get_party_map($scheme_id) {
        return $this->db->select('spm.*, c.name as customer_name')
                        ->from('scheme_party_map spm')
                        ->join('customers c', 'c.id = spm.customer_id', 'left')
                        ->where('spm.scheme_id', $scheme_id)
                        ->get()->result();
    }

    public function save_party_map($scheme_id, $customer_ids) {
        $this->db->where('scheme_id', $scheme_id)->delete('scheme_party_map');
        foreach ($customer_ids as $cid) {
            $this->db->insert('scheme_party_map', ['scheme_id' => $scheme_id, 'customer_id' => $cid]);
        }
    }

    // ── Free Items ────────────────────────────────────────────
    public function get_free_items($scheme_id) {
        return $this->db->select('sfi.*, p.name as product_name')
                        ->from('scheme_free_items sfi')
                        ->join('products p', 'p.id = sfi.free_product_id', 'left')
                        ->where('sfi.scheme_id', $scheme_id)
                        ->get()->result();
    }

    public function save_free_items($scheme_id, $free_items) {
        $this->db->where('scheme_id', $scheme_id)->delete('scheme_free_items');
        foreach ($free_items as $fi) {
            $this->db->insert('scheme_free_items', array_merge($fi, ['scheme_id' => $scheme_id]));
        }
    }

    // ── Mix & Match Groups ────────────────────────────────────
    public function get_mix_groups($scheme_id) {
        $groups = $this->db->where('scheme_id', $scheme_id)->order_by('group_order')->get('scheme_mixmatch_groups')->result();
        foreach ($groups as $g) {
            $g->items = $this->db->select('smi.*, p.name as product_name')
                                 ->from('scheme_mixmatch_items smi')
                                 ->join('products p', 'p.id = smi.product_id', 'left')
                                 ->where('smi.group_id', $g->id)
                                 ->get()->result();
        }
        return $groups;
    }

    // ── Scheme Application Log queries ────────────────────────
    public function get_log($filters = []) {
        $this->db->select('sal.*, sm.name as scheme_name, c.name as customer_name, p.name as free_product_name')
                 ->from('scheme_application_log sal')
                 ->join('scheme_master sm', 'sm.id = sal.scheme_id', 'left')
                 ->join('customers c', 'c.id = sal.customer_id', 'left')
                 ->join('products p', 'p.id = sal.free_product_id', 'left')
                 ->order_by('sal.applied_at', 'DESC');

        if (!empty($filters['scheme_id']))   $this->db->where('sal.scheme_id', $filters['scheme_id']);
        if (!empty($filters['customer_id'])) $this->db->where('sal.customer_id', $filters['customer_id']);
        if (!empty($filters['from']))        $this->db->where('DATE(sal.applied_at) >=', $filters['from']);
        if (!empty($filters['to']))          $this->db->where('DATE(sal.applied_at) <=', $filters['to']);
        if (!empty($filters['limit']))       $this->db->limit($filters['limit']);

        return $this->db->get()->result();
    }

    // ── Scheme Performance Report ─────────────────────────────
    public function get_performance($from, $to) {
        return $this->db->select('sal.scheme_id, sm.name as scheme_name, st.label as type_label,
                                  COUNT(DISTINCT sal.invoice_id) as invoices_count,
                                  COUNT(DISTINCT sal.customer_id) as customers_count,
                                  SUM(sal.discount_given) as total_discount,
                                  SUM(sal.free_qty) as total_free_qty,
                                  SUM(sal.applied_on_value) as total_eligible_value')
                        ->from('scheme_application_log sal')
                        ->join('scheme_master sm', 'sm.id = sal.scheme_id', 'left')
                        ->join('scheme_types st', 'st.id = sm.scheme_type_id', 'left')
                        ->where('DATE(sal.applied_at) >=', $from)
                        ->where('DATE(sal.applied_at) <=', $to)
                        ->group_by('sal.scheme_id')
                        ->order_by('total_discount', 'DESC')
                        ->get()->result();
    }

    // ── Scheme → Applicable Items report (for item_list view/export) ──
    // Returns a flat list of rows, one per (scheme, product) pair, resolving
    // apply_on ('item'/'brand'/'category'/'brand_category'/'invoice'/'mix_match'/'combo')
    // down to the actual product list so a cashier/manager can see exactly which
    // items a scheme covers.
    public function get_scheme_items($scheme_id = null) {
        $schemes_q = $this->db->select('sm.*, st.label as type_label')
                              ->from('scheme_master sm')
                              ->join('scheme_types st', 'st.id = sm.scheme_type_id', 'left')
                              ->order_by('sm.name');
        if (!empty($scheme_id)) $schemes_q->where('sm.id', $scheme_id);
        $schemes = $schemes_q->get()->result();

        $rows = [];
        foreach ($schemes as $s) {
            if ($s->apply_on === 'invoice') {
                $rows[] = $this->_scheme_row($s, null, 'ALL ITEMS (applies to whole invoice)', '', '', '');
                continue;
            }

            if ($s->apply_on === 'mix_match') {
                $groups = $this->get_mix_groups($s->id);
                $found = false;
                foreach ($groups as $g) {
                    foreach ($g->items as $it) {
                        $found = true;
                        $rows[] = $this->_scheme_row($s, $it->product_id, $it->product_name ?: ('Product #' . $it->product_id), '', '', 'Group ' . $g->group_order);
                    }
                }
                if (!$found) $rows[] = $this->_scheme_row($s, null, 'No mix & match items configured', '', '', '');
                continue;
            }

            $map = $this->db->where('scheme_id', $s->id)->get('scheme_item_map')->result();
            if (empty($map)) {
                $rows[] = $this->_scheme_row($s, null, 'No items mapped — check scheme setup', '', '', '');
                continue;
            }

            $product_ids = $brand_ids = $category_ids = [];
            foreach ($map as $m) {
                if (!empty($m->product_id))  $product_ids[]  = $m->product_id;
                if (!empty($m->brand_id))    $brand_ids[]    = $m->brand_id;
                if (!empty($m->category_id)) $category_ids[] = $m->category_id;
            }

            $q = $this->db->select('p.id, p.name, p.item_code, b.name as brand_name, c.name as category_name')
                          ->from('products p')
                          ->join('brands b', 'b.id = p.brand_id', 'left')
                          ->join('categories c', 'c.id = p.category_id', 'left');

            if ($s->apply_on === 'brand_category' && !empty($brand_ids) && !empty($category_ids)) {
                $q->where_in('p.brand_id', $brand_ids)->where_in('p.category_id', $category_ids);
            } elseif (!empty($product_ids)) {
                $q->where_in('p.id', $product_ids);
            } elseif (!empty($brand_ids)) {
                $q->where_in('p.brand_id', $brand_ids);
            } elseif (!empty($category_ids)) {
                $q->where_in('p.category_id', $category_ids);
            } else {
                $rows[] = $this->_scheme_row($s, null, 'No items mapped — check scheme setup', '', '', '');
                continue;
            }
            $products = $q->order_by('p.name')->get()->result();

            if (empty($products)) {
                $rows[] = $this->_scheme_row($s, null, 'Mapped brand/category has no matching products', '', '', '');
                continue;
            }
            foreach ($products as $p) {
                $rows[] = $this->_scheme_row($s, $p->id, $p->name, $p->item_code, $p->brand_name, $p->category_name);
            }
        }
        return $rows;
    }

    private function _scheme_row($s, $product_id, $product_name, $item_code, $brand_name, $category_name) {
        return (object)[
            'scheme_id'     => $s->id,
            'scheme_name'   => $s->name,
            'type_label'    => $s->type_label,
            'apply_on'      => $s->apply_on,
            'is_active'     => $s->is_active,
            'start_date'    => $s->start_date,
            'end_date'      => $s->end_date,
            'product_id'    => $product_id,
            'product_name'  => $product_name,
            'item_code'     => $item_code,
            'brand_name'    => $brand_name,
            'category_name' => $category_name,
        ];
    }

    // ── Legacy compatibility ──────────────────────────────────
    public function evaluate_invoice_schemes($customer_id, $invoice_date, $items) {
        // Delegate to SchemeEngine library
        $this->load->library('SchemeEngine');
        $enriched = array_map(fn($i) => array_merge($i, ['uom'=>'pcs','carton_size'=>1,'brand_id'=>0,'category_id'=>0]), $items);
        return $this->schemeengine->evaluate($customer_id, $enriched, array_sum(array_column($items,'taxable_amt')));
    }

    public function log_scheme_transactions($customer_id, $invoice_id, $invoice_date, $schemes_applied) {
        foreach ($schemes_applied as $app) {
            $this->db->insert('scheme_transactions', [
                'scheme_id'                => $app['scheme_id'] ?? 0,
                'customer_id'              => $customer_id,
                'invoice_id'               => $invoice_id,
                'invoice_date'             => $invoice_date,
                'eligible_purchase_amount' => $app['eligible_purchase_amount'] ?? 0,
                'bonus_discount_applied'   => $app['bonus_discount_applied'] ?? 0,
            ]);
        }
    }
}
