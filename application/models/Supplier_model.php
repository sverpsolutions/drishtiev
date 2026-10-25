<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Supplier_model extends CI_Model {

    private $table = 'suppliers';

    public function get_all() {
        return $this->db->where('status', 1)->order_by('name', 'ASC')->get($this->table)->result();
    }

    public function get_all_for_select() {
        $rows = $this->db->select('id, name, supplier_code')->where('status', 1)->order_by('name')->get($this->table)->result();
        $out = [];
        foreach ($rows as $r) $out[$r->id] = $r->name;
        return $out;
    }

    // Like get_all_for_select() but carries the GST compliance flag so payment
    // screens can warn before money goes out to a supplier who hasn't filed.
    public function get_all_for_select_with_gst_flag() {
        return $this->db->select('id, name, gst_compliance_status, gst_compliance_note')
            ->where('status', 1)->order_by('name')->get($this->table)->result();
    }

    public function get_all_with_performance() {
        return $this->db
            ->select('suppliers.*, vp.overall_score, vp.tier, vp.ontime_delivery_pct, svs.next_visit_date, svs.schedule_type, svs.interval_days')
            ->join('vendor_performance vp', 'vp.supplier_id = suppliers.id', 'left')
            ->join('supplier_visit_schedules svs', 'svs.supplier_id = suppliers.id', 'left')
            ->where('suppliers.status', 1)
            ->order_by('suppliers.name', 'ASC')
            ->get($this->table)
            ->result();
    }

    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row();
    }

    public function get_with_schedule($id) {
        $s = $this->get_by_id($id);
        if ($s) {
            $s->schedule = $this->db->where('supplier_id', $id)->get('supplier_visit_schedules')->row();
            $s->performance = $this->db->where('supplier_id', $id)->get('vendor_performance')->row();
            $s->documents = $this->db->where('supplier_id', $id)->order_by('doc_type')->get('supplier_documents')->result();
        }
        return $s;
    }

    public function get_by_portal_username($username) {
        return $this->db
            ->where('portal_username', $username)
            ->where('portal_active', 1)
            ->where('status', 1)
            ->get($this->table)->row();
    }

    public function create($data) {
        if (empty($data['supplier_code'])) {
            $data['supplier_code'] = $this->_next_code();
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id) {
        return $this->db->where('id', $id)->update($this->table, ['status' => 0]);
    }

    // ── Visit schedule ────────────────────────────────────────────────────────
    public function save_visit_schedule($supplier_id, $data) {
        $data['next_visit_date'] = $this->compute_next_visit($data);
        $existing = $this->db->where('supplier_id', $supplier_id)->get('supplier_visit_schedules')->row();
        if ($existing) {
            $data['supplier_id'] = $supplier_id;
            $this->db->where('supplier_id', $supplier_id)->update('supplier_visit_schedules', $data);
        } else {
            $data['supplier_id'] = $supplier_id;
            $data['created_at']  = date('Y-m-d H:i:s');
            $this->db->insert('supplier_visit_schedules', $data);
        }
    }

    public function compute_next_visit($sched, $from = null) {
        $from = $from ? new DateTime($from) : new DateTime();
        $type = $sched['schedule_type'] ?? 'interval';
        if ($type === 'daily') {
            $next = clone $from; $next->modify('+1 day');
            return $next->format('Y-m-d');
        }
        if ($type === 'interval') {
            $days = max(1, (int)($sched['interval_days'] ?? 7));
            $next = clone $from; $next->modify("+{$days} days");
            return $next->format('Y-m-d');
        }
        if ($type === 'weekly') {
            $wdays = json_decode($sched['weekdays'] ?? '[1]', true);
            sort($wdays);
            $dow = (int)$from->format('N'); // 1=Mon 7=Sun
            foreach ($wdays as $wd) {
                if ($wd > $dow) { $diff = $wd - $dow; $next = clone $from; $next->modify("+{$diff} days"); return $next->format('Y-m-d'); }
            }
            $diff = 7 - $dow + $wdays[0];
            $next = clone $from; $next->modify("+{$diff} days");
            return $next->format('Y-m-d');
        }
        if ($type === 'monthly_dates') {
            $dates = json_decode($sched['month_dates'] ?? '[1]', true);
            sort($dates);
            $day = (int)$from->format('j'); $month = (int)$from->format('n'); $year = (int)$from->format('Y');
            foreach ($dates as $d) {
                if ($d > $day) return sprintf('%04d-%02d-%02d', $year, $month, $d);
            }
            $month++; if ($month > 12) { $month = 1; $year++; }
            return sprintf('%04d-%02d-%02d', $year, $month, $dates[0]);
        }
        return $from->modify('+7 days')->format('Y-m-d');
    }

    public function get_visits_today() {
        return $this->db
            ->select('suppliers.id, suppliers.name, suppliers.phone as mobile, svs.last_visit_date, svs.next_visit_date, svs.assigned_buyer')
            ->join('supplier_visit_schedules svs', 'svs.supplier_id = suppliers.id')
            ->where('svs.next_visit_date', date('Y-m-d'))
            ->where('suppliers.status', 1)
            ->get('suppliers')
            ->result();
    }

    public function get_visits_upcoming($days = 7) {
        return $this->db
            ->select('suppliers.id, suppliers.name, suppliers.phone as mobile, svs.next_visit_date, svs.schedule_type, svs.interval_days')
            ->join('supplier_visit_schedules svs', 'svs.supplier_id = suppliers.id')
            ->where('svs.next_visit_date >=', date('Y-m-d'))
            ->where('svs.next_visit_date <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('suppliers.status', 1)
            ->order_by('svs.next_visit_date')
            ->get('suppliers')
            ->result();
    }

    public function log_visit($supplier_id, $buyer_id, $notes = '', $po_value = 0) {
        $sched = $this->db->where('supplier_id', $supplier_id)->get('supplier_visit_schedules')->row();
        $today = date('Y-m-d');
        $this->db->insert('supplier_visit_logs', [
            'supplier_id' => $supplier_id,
            'visit_date'  => $today,
            'buyer_id'    => $buyer_id,
            'po_value'    => $po_value,
            'notes'       => $notes,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        if ($sched) {
            $schedArr = (array)$sched;
            $schedArr['last_visit_date'] = $today;
            $next = $this->compute_next_visit($schedArr, $today);
            $this->db->where('supplier_id', $supplier_id)->update('supplier_visit_schedules', [
                'last_visit_date' => $today,
                'next_visit_date' => $next,
            ]);
        }
    }

    // ── Products linked to supplier ────────────────────────────────────────
    public function get_supplier_products($supplier_id) {
        return $this->db
            ->select('supplier_products.*, products.name as product_name, products.item_code, products.unit, products.selling_price, products.gst_percent, GREATEST(products.stock_qty, 0) AS stock_qty, products.mrp')
            ->join('products', 'products.id = supplier_products.product_id')
            ->where('supplier_products.supplier_id', $supplier_id)
            ->where('products.status', 1)
            ->order_by('products.name')
            ->get('supplier_products')
            ->result();
    }

    public function link_product($supplier_id, $product_id, $data = []) {
        $exists = $this->db->where('supplier_id', $supplier_id)->where('product_id', $product_id)->get('supplier_products')->row();
        if ($exists) {
            if ($data) $this->db->where('id', $exists->id)->update('supplier_products', $data);
        } else {
            $data['supplier_id'] = $supplier_id; $data['product_id'] = $product_id; $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('supplier_products', $data);
        }
    }

    public function unlink_product($supplier_id, $product_id) {
        $this->db->where('supplier_id', $supplier_id)->where('product_id', $product_id)->delete('supplier_products');
    }

    public function get_outstanding($supplier_id) {
        $r = $this->db->select_sum('due_amount')->where('supplier_id', $supplier_id)->where('status !=', 'returned')->get('purchases')->row();
        return $r->due_amount ?? 0;
    }

    private function _next_code() {
        $row = $this->db->select_max('id')->get($this->table)->row();
        $n = ($row->id ?? 0) + 1;
        return 'SUP' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }
}
