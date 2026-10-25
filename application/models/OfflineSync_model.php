<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class OfflineSync_model extends CI_Model {

    // ── Data download for offline cache ──────────────────────────────────────

    public function get_billing_data($location_id = 1) {
        $location_id = (int)$location_id;

        // Products with stock for this location
        $this->db->select('p.id, p.name, p.item_code, p.selling_price, p.mrp, p.gst_percent,
            p.unit, p.hsn_code, p.image1, p.no_discount,
            GREATEST(COALESCE(pls.stock_qty, p.stock_qty, 0), 0) AS stock_qty,
            p.updated_at');
        $this->db->from('products p');
        $this->db->join('product_location_stock pls',
            "pls.product_id = p.id AND pls.location_id = $location_id", 'left');
        $this->db->where('p.status', 'active');
        $products = $this->db->get()->result();

        // PLU / barcode list (table may not exist on all installs)
        $plu_list = [];
        if ($this->db->table_exists('plu_list')) {
            $this->db->select('pl.id, pl.product_id, pl.plu_code, pl.barcode, pl.mrp,
                pl.selling_rate, pl.status,
                GREATEST(COALESCE(pls.stock_qty, 0), 0) AS stock_qty,
                p.name AS item_name, p.gst_percent,
                pl.updated_at');
            $this->db->from('plu_list pl');
            $this->db->join('products p', 'p.id = pl.product_id', 'left');
            $this->db->join('product_location_stock pls',
                "pls.product_id = pl.product_id AND pls.location_id = $location_id", 'left');
            $this->db->where('pl.status', 'active');
            $plu_list = $this->db->get()->result();
        }

        // Customers
        $this->db->select('id, name, phone, email, address, type, balance, credit_limit,
            gst_number, state, wallet_balance, updated_at');
        $this->db->from('customers');
        $this->db->where('status', 'active');
        $customers = $this->db->get()->result();

        // Company/store settings
        $settings_rows = $this->db->get('company_settings')->result();
        $settings = [];
        foreach ($settings_rows as $r) $settings[$r->key_name] = $r->key_value;

        // Company info (from company_settings key/value table)
        $company_rows = $this->db->get('company_settings')->result();
        $company = [];
        foreach ($company_rows as $r) $company[$r->key_name] = $r->key_value;

        // Location info
        $location = $this->db->where('id', $location_id)->get('locations')->row_array();

        return [
            'products'   => $products,
            'plu_list'   => $plu_list,
            'customers'  => $customers,
            'settings'   => $settings,
            'company'    => $company,
            'location'   => $location,
            'synced_at'  => time(),
        ];
    }

    // Delta sync — only records updated after a given timestamp
    public function get_delta($location_id, $since_ts) {
        $location_id = (int)$location_id;
        $since = date('Y-m-d H:i:s', (int)$since_ts);

        $this->db->select('p.id, p.name, p.item_code, p.selling_price, p.mrp,
            p.gst_percent, p.unit, p.hsn_code, p.image1, p.no_discount,
            GREATEST(COALESCE(pls.stock_qty, p.stock_qty, 0), 0) AS stock_qty, p.updated_at');
        $this->db->from('products p');
        $this->db->join('product_location_stock pls',
            "pls.product_id = p.id AND pls.location_id = $location_id", 'left');
        $this->db->where('p.status', 'active');
        $this->db->where('p.updated_at >=', $since);
        $products = $this->db->get()->result();

        $this->db->select('id, name, phone, balance, credit_limit, wallet_balance, updated_at');
        $this->db->from('customers');
        $this->db->where('status', 'active');
        $this->db->where('updated_at >=', $since);
        $customers = $this->db->get()->result();

        $plu_list = [];
        if ($this->db->table_exists('plu_list')) {
            $this->db->select('pl.id, pl.product_id, pl.plu_code, pl.barcode, pl.mrp,
                pl.selling_rate, pl.status,
                GREATEST(COALESCE(pls.stock_qty, 0), 0) AS stock_qty, p.name AS item_name, pl.updated_at');
            $this->db->from('plu_list pl');
            $this->db->join('products p', 'p.id = pl.product_id', 'left');
            $this->db->join('product_location_stock pls',
                "pls.product_id = pl.product_id AND pls.location_id = $location_id", 'left');
            $this->db->where('pl.updated_at >=', $since);
            $plu_list = $this->db->get()->result();
        }

        return [
            'products'  => $products,
            'customers' => $customers,
            'plu_list'  => $plu_list,
            'synced_at' => time(),
        ];
    }

    // ── Offline invoice upload ────────────────────────────────────────────────

    public function is_duplicate(string $offline_ref): bool {
        return $this->db->where('offline_ref', $offline_ref)->count_all_results('offline_sync_queue') > 0
            || $this->db->where('offline_ref', $offline_ref)->count_all_results('offline_invoice_mapping') > 0;
    }

    public function enqueue(array $data): int {
        $this->db->insert('offline_sync_queue', [
            'device_id'   => $data['device_id'],
            'queue_type'  => $data['queue_type'] ?? 'invoice',
            'offline_ref' => $data['offline_ref'],
            'payload'     => json_encode($data['payload']),
            'status'      => 'pending',
            'location_id' => $data['location_id'] ?? 1,
            'user_id'     => $data['user_id'] ?? null,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function get_pending(string $device_id): array {
        return $this->db->where('device_id', $device_id)
            ->where('status', 'pending')
            ->order_by('created_at', 'ASC')
            ->get('offline_sync_queue')->result_array();
    }

    public function mark_synced(int $queue_id, int $cloud_id, string $cloud_ref): void {
        $this->db->where('id', $queue_id)->update('offline_sync_queue', [
            'status'    => 'synced',
            'cloud_id'  => $cloud_id,
            'cloud_ref' => $cloud_ref,
            'synced_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function mark_failed(int $queue_id, string $error): void {
        $this->db->query(
            "UPDATE offline_sync_queue SET status='failed', last_error=?, retry_count=retry_count+1 WHERE id=?",
            [$error, $queue_id]
        );
        // Auto-reset to pending if under retry limit
        $this->db->query(
            "UPDATE offline_sync_queue SET status='pending' WHERE id=? AND retry_count < 5 AND status='failed'",
            [$queue_id]
        );
    }

    public function record_mapping(string $offline_ref, int $cloud_id, string $cloud_no, string $device_id): void {
        $exists = $this->db->where('offline_ref', $offline_ref)->count_all_results('offline_invoice_mapping');
        if (!$exists) {
            $this->db->insert('offline_invoice_mapping', [
                'offline_ref'     => $offline_ref,
                'cloud_invoice_id'=> $cloud_id,
                'cloud_invoice_no'=> $cloud_no,
                'device_id'       => $device_id,
                'synced_at'       => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ── Dashboard stats ───────────────────────────────────────────────────────

    public function get_stats(): array {
        $pending  = $this->db->where('status', 'pending') ->count_all_results('offline_sync_queue');
        $synced   = $this->db->where('status', 'synced')  ->count_all_results('offline_sync_queue');
        $failed   = $this->db->where('status', 'failed')  ->count_all_results('offline_sync_queue');
        $syncing  = $this->db->where('status', 'syncing') ->count_all_results('offline_sync_queue');

        $last_log = $this->db->order_by('id','DESC')->limit(1)->get('offline_sync_log')->row_array();

        // Pending invoice total amount (from payload JSON sum)
        $pending_rows = $this->db->where('status', 'pending')
            ->where('queue_type', 'invoice')
            ->get('offline_sync_queue')->result_array();
        $pending_amount = 0;
        foreach ($pending_rows as $r) {
            $p = json_decode($r['payload'], true);
            $pending_amount += (float)($p['grand_total'] ?? 0);
        }

        return [
            'pending'        => $pending,
            'synced'         => $synced,
            'failed'         => $failed,
            'syncing'        => $syncing,
            'total'          => $pending + $synced + $failed + $syncing,
            'pending_amount' => $pending_amount,
            'last_log'       => $last_log,
        ];
    }

    public function get_recent_queue(int $limit = 50): array {
        return $this->db->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('offline_sync_queue')->result_array();
    }

    public function get_mappings(int $limit = 100): array {
        return $this->db->order_by('synced_at', 'DESC')
            ->limit($limit)
            ->get('offline_invoice_mapping')->result_array();
    }

    public function retry_failed(): int {
        $this->db->where('status', 'failed')
            ->where('retry_count <', 10)
            ->update('offline_sync_queue', ['status' => 'pending', 'last_error' => null]);
        return $this->db->affected_rows();
    }

    // ── Counter for offline invoice numbers ──────────────────────────────────

    public function next_counter(string $device_id, string $location_code): int {
        $row = $this->db->where('device_id', $device_id)->get('offline_counters')->row();
        if (!$row) {
            $this->db->insert('offline_counters', [
                'device_id'     => $device_id,
                'location_code' => $location_code,
                'last_counter'  => 1,
            ]);
            return 1;
        }
        $next = $row->last_counter + 1;
        $this->db->where('device_id', $device_id)->update('offline_counters', ['last_counter' => $next]);
        return $next;
    }
}
