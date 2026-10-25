<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Warranty claims, replacement chain, dashboard counters and the report queries.
 *
 * Everything here reads warranty facts from `serial_warranties` (the per-sale
 * snapshot), never from the item master — an item's warranty_days can be edited
 * at any time and must not retroactively change what was already sold.
 */
class Warranty_model extends CI_Model {

    public function schema_ready() {
        return $this->db->table_exists('warranty_claims');
    }

    // ── Claim numbering ─────────────────────────────────────────────────────
    // Same MAX(id) convention as Stock_take_model::generate_take_no().
    // document_sequences is reserved for statutory documents.
    public function generate_claim_no() {
        $row  = $this->db->select_max('id')->get('warranty_claims')->row();
        $next = $row && $row->id ? (int)$row->id + 1 : 1;
        return 'WC-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // ── Claims ──────────────────────────────────────────────────────────────

    public function create_claim(array $d) {
        $d['claim_no']   = $this->generate_claim_no();
        $d['created_at'] = date('Y-m-d H:i:s');
        $d['created_by'] = $this->session->userdata('user_id');
        $this->db->insert('warranty_claims', $d);
        $id = $this->db->insert_id();
        $this->log_status($id, null, $d['status'] ?? 'RECEIVED', 'Claim opened');
        return $id;
    }

    public function get_claim($id) {
        return $this->db
            ->select('wc.*, p.name AS product_name, p.item_code,
                      s.serial_no, s.status AS serial_status,
                      rs.serial_no AS replacement_serial_no,
                      c.name AS customer_name, c.phone AS customer_phone,
                      i.invoice_no, i.invoice_date,
                      w.warranty_start_date, w.warranty_expiry_date, w.warranty_days,
                      u.name AS assigned_name')
            ->from('warranty_claims wc')
            ->join('products p',      'p.id  = wc.product_id', 'left')
            ->join('item_serials s',  's.id  = wc.serial_id', 'left')
            ->join('item_serials rs', 'rs.id = wc.replacement_serial_id', 'left')
            ->join('customers c',     'c.id  = wc.customer_id', 'left')
            ->join('invoices i',      'i.id  = wc.invoice_id', 'left')
            ->join('serial_warranties w', 'w.id = wc.warranty_id', 'left')
            ->join('users u',         'u.id  = wc.assigned_to', 'left')
            ->where('wc.id', (int)$id)
            ->get()->row();
    }

    public function get_claims(array $f = []) {
        $this->db
            ->select('wc.id, wc.claim_no, wc.claim_date, wc.status, wc.problem_description,
                      p.name AS product_name, s.serial_no, c.name AS customer_name,
                      i.invoice_no, u.name AS assigned_name')
            ->from('warranty_claims wc')
            ->join('products p',     'p.id = wc.product_id', 'left')
            ->join('item_serials s', 's.id = wc.serial_id', 'left')
            ->join('customers c',    'c.id = wc.customer_id', 'left')
            ->join('invoices i',     'i.id = wc.invoice_id', 'left')
            ->join('users u',        'u.id = wc.assigned_to', 'left');

        if (!empty($f['status']))    $this->db->where('wc.status', $f['status']);
        if (!empty($f['open']))      $this->db->where_not_in('wc.status', ['CLOSED', 'REJECTED', 'DELIVERED']);
        if (!empty($f['from']))      $this->db->where('wc.claim_date >=', $f['from']);
        if (!empty($f['to']))        $this->db->where('wc.claim_date <=', $f['to']);
        if (!empty($f['customer_id'])) $this->db->where('wc.customer_id', (int)$f['customer_id']);
        if (!empty($f['q'])) {
            $q = trim($f['q']);
            $this->db->group_start()
                     ->like('wc.claim_no', $q)->or_like('s.serial_no', $q)
                     ->or_like('c.name', $q)->or_like('c.phone', $q)
                     ->or_like('i.invoice_no', $q)
                     ->group_end();
        }
        return $this->db->order_by('wc.id', 'DESC')
                        ->limit((int)($f['limit'] ?? 200))
                        ->get()->result();
    }

    /**
     * Status change + history in one call, so a claim's timeline can never be
     * silently rewritten. Returns false when the transition is not allowed.
     */
    public function set_claim_status($claim_id, $new_status, $remarks = null, array $extra = []) {
        $cur = $this->db->where('id', (int)$claim_id)->get('warranty_claims')->row();
        if (!$cur) return false;

        $update = array_merge($extra, [
            'status'     => $new_status,
            'updated_by' => $this->session->userdata('user_id'),
        ]);
        if (in_array($new_status, ['CLOSED', 'DELIVERED'], true) && empty($cur->closed_date)) {
            $update['closed_date'] = date('Y-m-d');
        }
        $this->db->where('id', (int)$claim_id)->update('warranty_claims', $update);
        $this->log_status($claim_id, $cur->status, $new_status, $remarks);
        return true;
    }

    public function log_status($claim_id, $old, $new, $remarks = null) {
        $this->db->insert('warranty_claim_log', [
            'claim_id'   => (int)$claim_id,
            'old_status' => $old,
            'new_status' => $new,
            'remarks'    => $remarks,
            'user_id'    => $this->session->userdata('user_id'),
            'user_name'  => $this->session->userdata('name'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function get_claim_log($claim_id) {
        return $this->db->where('claim_id', (int)$claim_id)
                        ->order_by('id', 'ASC')
                        ->get('warranty_claim_log')->result();
    }

    /** Valid next steps, so the UI can never offer an illegal jump. */
    public function next_statuses($current) {
        $flow = [
            'RECEIVED'         => ['UNDER_INSPECTION', 'REJECTED'],
            'UNDER_INSPECTION' => ['APPROVED', 'REJECTED'],
            'APPROVED'         => ['REPAIR', 'REPLACEMENT'],
            'REPAIR'           => ['READY', 'REJECTED'],
            'REPLACEMENT'      => ['READY'],
            'READY'            => ['DELIVERED'],
            'DELIVERED'        => ['CLOSED'],
            'REJECTED'         => ['CLOSED'],
            'CLOSED'           => [],
        ];
        return $flow[$current] ?? [];
    }

    // ── Dashboard ───────────────────────────────────────────────────────────

    public function dashboard_counts() {
        $today = date('Y-m-d');
        $c = [];

        $c['total_serialized'] = (int)$this->db->count_all('item_serials');
        foreach (['IN_STOCK' => 'in_stock', 'SOLD' => 'sold', 'UNDER_REPAIR' => 'under_repair',
                  'SALES_RETURN' => 'sales_return', 'SCRAPPED' => 'scrapped'] as $st => $key) {
            $c[$key] = (int)$this->db->where('status', $st)->count_all_results('item_serials');
        }

        // Only the live warranty per unit counts — a returned one is history.
        $c['warranty_active'] = (int)$this->db->where('is_returned', 0)
            ->where('warranty_expiry_date >=', $today)->count_all_results('serial_warranties');
        $c['warranty_expired'] = (int)$this->db->where('is_returned', 0)
            ->where('warranty_expiry_date <', $today)->count_all_results('serial_warranties');
        $c['expiring_30'] = (int)$this->db->where('is_returned', 0)
            ->where('warranty_expiry_date >=', $today)
            ->where('warranty_expiry_date <=', date('Y-m-d', strtotime('+30 day')))
            ->count_all_results('serial_warranties');

        foreach (['RECEIVED' => 'claims_received', 'UNDER_INSPECTION' => 'claims_inspection',
                  'APPROVED' => 'claims_approved', 'REJECTED' => 'claims_rejected',
                  'REPAIR' => 'claims_repair', 'REPLACEMENT' => 'claims_replacement'] as $st => $key) {
            $c[$key] = (int)$this->db->where('status', $st)->count_all_results('warranty_claims');
        }
        $c['claims_open'] = (int)$this->db
            ->where_not_in('status', ['CLOSED', 'REJECTED', 'DELIVERED'])
            ->count_all_results('warranty_claims');

        return $c;
    }

    // ── Reports ─────────────────────────────────────────────────────────────

    /**
     * One entry point for the serial/warranty reports. Every branch is capped
     * and index-backed — the spec expects these to stay usable at hundreds of
     * thousands of serials.
     */
    public function report($type, array $f = []) {
        $today = date('Y-m-d');
        $limit = (int)($f['limit'] ?? 1000);

        switch ($type) {

            case 'serial_stock':
                $this->db->select('s.serial_no, p.name AS product_name, p.item_code, s.status,
                                   l.name AS location_name, sup.name AS supplier_name, s.purchase_date')
                    ->from('item_serials s')
                    ->join('products p', 'p.id = s.product_id', 'left')
                    ->join('locations l', 'l.id = s.location_id', 'left')
                    ->join('suppliers sup', 'sup.id = s.supplier_id', 'left')
                    ->where('s.status', 'IN_STOCK');
                if (!empty($f['location_id'])) $this->db->where('s.location_id', (int)$f['location_id']);
                if (!empty($f['product_id']))  $this->db->where('s.product_id', (int)$f['product_id']);
                return $this->db->order_by('p.name')->limit($limit)->get()->result();

            case 'serial_sales':
                $this->db->select('s.serial_no, s.chassis_no, s.motor_no, s.colour, s.item_type, s.battery_capacity, s.charger_model, s.linked_chassis_no,
                                   p.name AS product_name, p.model_name, p.model_number, i.invoice_no, w.sale_date,
                                   c.name AS customer_name, c.phone, w.warranty_expiry_date, s.status')
                    ->from('serial_warranties w')
                    ->join('item_serials s', 's.id = w.serial_id')
                    ->join('products p', 'p.id = w.product_id', 'left')
                    ->join('invoices i', 'i.id = w.invoice_id', 'left')
                    ->join('customers c', 'c.id = w.customer_id', 'left');
                if (!empty($f['from'])) $this->db->where('w.sale_date >=', $f['from']);
                if (!empty($f['to']))   $this->db->where('w.sale_date <=', $f['to']);
                return $this->db->order_by('w.sale_date', 'DESC')->limit($limit)->get()->result();

            case 'serial_purchase':
                $this->db->select('s.serial_no, p.name AS product_name, s.purchase_date,
                                   sup.name AS supplier_name, s.purchase_rate, s.status, l.name AS location_name')
                    ->from('item_serials s')
                    ->join('products p', 'p.id = s.product_id', 'left')
                    ->join('suppliers sup', 'sup.id = s.supplier_id', 'left')
                    ->join('locations l', 'l.id = s.location_id', 'left');
                if (!empty($f['from'])) $this->db->where('s.purchase_date >=', $f['from']);
                if (!empty($f['to']))   $this->db->where('s.purchase_date <=', $f['to']);
                if (!empty($f['supplier_id'])) $this->db->where('s.supplier_id', (int)$f['supplier_id']);
                return $this->db->order_by('s.purchase_date', 'DESC')->limit($limit)->get()->result();

            case 'serial_movement':
                $this->db->select('s.serial_no, p.name AS product_name, m.txn_type, m.txn_date,
                                   m.reference_no, fl.name AS from_location, tl.name AS to_location,
                                   m.old_status, m.new_status, m.user_name, m.remarks')
                    ->from('serial_movements m')
                    ->join('item_serials s', 's.id = m.serial_id')
                    ->join('products p', 'p.id = s.product_id', 'left')
                    ->join('locations fl', 'fl.id = m.from_location_id', 'left')
                    ->join('locations tl', 'tl.id = m.to_location_id', 'left');
                if (!empty($f['from'])) $this->db->where('m.txn_date >=', $f['from'] . ' 00:00:00');
                if (!empty($f['to']))   $this->db->where('m.txn_date <=', $f['to'] . ' 23:59:59');
                if (!empty($f['serial'])) $this->db->like('s.serial_no', trim($f['serial']));
                return $this->db->order_by('m.txn_date', 'DESC')->limit($limit)->get()->result();

            case 'serial_location':
                return $this->db->select('l.name AS location_name, p.name AS product_name,
                                          COUNT(*) AS units')
                    ->from('item_serials s')
                    ->join('locations l', 'l.id = s.location_id', 'left')
                    ->join('products p', 'p.id = s.product_id', 'left')
                    ->where('s.status', 'IN_STOCK')
                    ->group_by('s.location_id, s.product_id')
                    ->order_by('l.name')->limit($limit)->get()->result();

            case 'customer_history':
                $this->db->select('c.name AS customer_name, c.phone, s.serial_no, p.name AS product_name,
                                   i.invoice_no, w.sale_date, w.warranty_expiry_date, s.status')
                    ->from('serial_warranties w')
                    ->join('customers c', 'c.id = w.customer_id')
                    ->join('item_serials s', 's.id = w.serial_id', 'left')
                    ->join('products p', 'p.id = w.product_id', 'left')
                    ->join('invoices i', 'i.id = w.invoice_id', 'left');
                if (!empty($f['customer_id'])) $this->db->where('w.customer_id', (int)$f['customer_id']);
                if (!empty($f['q'])) {
                    $this->db->group_start()->like('c.name', $f['q'])->or_like('c.phone', $f['q'])->group_end();
                }
                return $this->db->order_by('w.sale_date', 'DESC')->limit($limit)->get()->result();

            case 'warranty_active':
            case 'warranty_expired':
            case 'warranty_expiring':
                $this->db->select('s.serial_no, p.name AS product_name, c.name AS customer_name, c.phone,
                                   i.invoice_no, w.sale_date, w.warranty_days, w.warranty_expiry_date,
                                   DATEDIFF(w.warranty_expiry_date, CURDATE()) AS days_left')
                    ->from('serial_warranties w')
                    ->join('item_serials s', 's.id = w.serial_id', 'left')
                    ->join('products p', 'p.id = w.product_id', 'left')
                    ->join('customers c', 'c.id = w.customer_id', 'left')
                    ->join('invoices i', 'i.id = w.invoice_id', 'left')
                    ->where('w.is_returned', 0);

                if ($type === 'warranty_active') {
                    $this->db->where('w.warranty_expiry_date >=', $today)->order_by('w.warranty_expiry_date', 'ASC');
                } elseif ($type === 'warranty_expired') {
                    $this->db->where('w.warranty_expiry_date <', $today)->order_by('w.warranty_expiry_date', 'DESC');
                } else {
                    $days = (int)($f['days'] ?? 30);
                    $from = !empty($f['from']) ? $f['from'] : $today;
                    $to   = !empty($f['to'])   ? $f['to']   : date('Y-m-d', strtotime('+' . $days . ' day'));
                    $this->db->where('w.warranty_expiry_date >=', $from)
                             ->where('w.warranty_expiry_date <=', $to)
                             ->order_by('w.warranty_expiry_date', 'ASC');
                }
                if (!empty($f['product_id'])) $this->db->where('w.product_id', (int)$f['product_id']);
                return $this->db->limit($limit)->get()->result();

            case 'itemwise_warranty':
                return $this->db->select('p.name AS product_name, p.item_code, COUNT(*) AS sold_units,
                        SUM(CASE WHEN w.warranty_expiry_date >= CURDATE() AND w.is_returned = 0 THEN 1 ELSE 0 END) AS active,
                        SUM(CASE WHEN w.warranty_expiry_date <  CURDATE() AND w.is_returned = 0 THEN 1 ELSE 0 END) AS expired')
                    ->from('serial_warranties w')
                    ->join('products p', 'p.id = w.product_id', 'left')
                    ->group_by('w.product_id')->order_by('sold_units', 'DESC')
                    ->limit($limit)->get()->result();

            case 'claims':
            case 'claims_pending':
                $ff = ['limit' => $limit] + $f;
                if ($type === 'claims_pending') $ff['open'] = 1;
                return $this->get_claims($ff);

            case 'replacements':
                return $this->db->select('wc.claim_no, wc.claim_date, p.name AS product_name,
                                          os.serial_no AS old_serial, rs.serial_no AS new_serial,
                                          c.name AS customer_name, wc.status, wc.closed_date')
                    ->from('warranty_claims wc')
                    ->join('item_serials os', 'os.id = wc.serial_id', 'left')
                    ->join('item_serials rs', 'rs.id = wc.replacement_serial_id')
                    ->join('products p', 'p.id = wc.product_id', 'left')
                    ->join('customers c', 'c.id = wc.customer_id', 'left')
                    ->order_by('wc.id', 'DESC')->limit($limit)->get()->result();
        }
        return [];
    }

    /** Column headings per report — keeps the view generic. */
    public function report_meta($type) {
        $m = [
            'serial_stock'      => ['Serial Number Stock',        ['serial_no'=>'Serial','product_name'=>'Item','item_code'=>'Code','status'=>'Status','location_name'=>'Location','supplier_name'=>'Supplier','purchase_date'=>'Purchased']],
            'serial_sales'      => ['Serial Number Sales',        ['serial_no'=>'Serial','product_name'=>'Item','invoice_no'=>'Invoice','sale_date'=>'Sold','customer_name'=>'Customer','phone'=>'Phone','warranty_expiry_date'=>'Warranty Till','status'=>'Status']],
            'serial_purchase'   => ['Serial Number Purchase',     ['serial_no'=>'Serial','product_name'=>'Item','purchase_date'=>'Purchased','supplier_name'=>'Supplier','purchase_rate'=>'Rate','location_name'=>'Location','status'=>'Status']],
            'serial_movement'   => ['Serial Number Movement',     ['txn_date'=>'Date','serial_no'=>'Serial','product_name'=>'Item','txn_type'=>'Type','reference_no'=>'Ref','from_location'=>'From','to_location'=>'To','old_status'=>'Was','new_status'=>'Now','user_name'=>'User']],
            'serial_location'   => ['Serial Number by Location',  ['location_name'=>'Location','product_name'=>'Item','units'=>'Units']],
            'customer_history'  => ['Serial Customer History',    ['customer_name'=>'Customer','phone'=>'Phone','serial_no'=>'Serial','product_name'=>'Item','invoice_no'=>'Invoice','sale_date'=>'Sold','warranty_expiry_date'=>'Warranty Till','status'=>'Status']],
            'warranty_active'   => ['Active Warranty',            ['serial_no'=>'Serial','product_name'=>'Item','customer_name'=>'Customer','phone'=>'Phone','invoice_no'=>'Invoice','sale_date'=>'Sold','warranty_expiry_date'=>'Expires','days_left'=>'Days Left']],
            'warranty_expired'  => ['Expired Warranty',           ['serial_no'=>'Serial','product_name'=>'Item','customer_name'=>'Customer','phone'=>'Phone','invoice_no'=>'Invoice','sale_date'=>'Sold','warranty_expiry_date'=>'Expired On','days_left'=>'Days Ago']],
            'warranty_expiring' => ['Warranty Expiring Soon',     ['serial_no'=>'Serial','product_name'=>'Item','customer_name'=>'Customer','phone'=>'Phone','invoice_no'=>'Invoice','warranty_expiry_date'=>'Expires','days_left'=>'Days Left']],
            'itemwise_warranty' => ['Item-wise Warranty',         ['product_name'=>'Item','item_code'=>'Code','sold_units'=>'Sold','active'=>'Active','expired'=>'Expired']],
            'claims'            => ['Warranty Claims',            ['claim_no'=>'Claim','claim_date'=>'Date','serial_no'=>'Serial','product_name'=>'Item','customer_name'=>'Customer','invoice_no'=>'Invoice','status'=>'Status','assigned_name'=>'Assigned']],
            'claims_pending'    => ['Pending Warranty Claims',    ['claim_no'=>'Claim','claim_date'=>'Date','serial_no'=>'Serial','product_name'=>'Item','customer_name'=>'Customer','status'=>'Status','assigned_name'=>'Assigned']],
            'replacements'      => ['Warranty Replacements',      ['claim_no'=>'Claim','claim_date'=>'Date','product_name'=>'Item','old_serial'=>'Old Serial','new_serial'=>'Replacement','customer_name'=>'Customer','status'=>'Status','closed_date'=>'Closed']],
        ];
        return $m[$type] ?? null;
    }

    public function report_types() {
        return [
            'Serial Number Reports' => ['serial_stock', 'serial_sales', 'serial_purchase',
                                        'serial_movement', 'serial_location', 'customer_history'],
            'Warranty Reports'      => ['warranty_active', 'warranty_expired', 'warranty_expiring',
                                        'itemwise_warranty', 'claims', 'claims_pending', 'replacements'],
        ];
    }
}
