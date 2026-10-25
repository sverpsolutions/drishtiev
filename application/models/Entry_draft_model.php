<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Shared draft engine for Purchase / GRN (PO-based) / GRN (direct) entry screens.
// entry_type discriminates the three flows; draft rows are pre-commit scratch
// data only — never touched by the real purchases/grn finalize tables until
// mark_completed() is called after a successful commit.
class Entry_draft_model extends CI_Model {

    private $heartbeat_stale_seconds = 60; // lock considered stale after 2x heartbeat interval (~30s ticks)

    public function find_active_for_user($user_id, $entry_type, $po_id = null) {
        $this->db->where('user_id', $user_id)
                  ->where('entry_type', $entry_type)
                  ->where('status', 'in_progress')
                  ->order_by('last_modified_at', 'DESC');
        if ($po_id !== null) $this->db->where('po_id', $po_id);
        return $this->db->get('entry_drafts')->row();
    }

    // $shared = TRUE lists every user's in-progress draft, not just the caller's.
    // Used by the GRN screens, where a receipt started on one counter has to be
    // finishable from another; concurrent editing is still blocked by the lock.
    public function find_all_visible($user_id, $is_admin, $entry_type = null, $shared = false) {
        $this->db->select('entry_drafts.*, u.name as user_name, lu.name as locked_by_name, s.name as supplier_name,
                            (SELECT COUNT(*) FROM entry_draft_items edi WHERE edi.draft_id = entry_drafts.id) as item_count,
                            (SELECT COALESCE(SUM(total_amount),0) FROM entry_draft_items edi WHERE edi.draft_id = entry_drafts.id) as total_value')
                  ->from('entry_drafts')
                  ->join('users u', 'u.id = entry_drafts.user_id', 'left')
                  ->join('users lu', 'lu.id = entry_drafts.locked_by', 'left')
                  ->join('suppliers s', 's.id = entry_drafts.supplier_id', 'left')
                  ->where('entry_drafts.status', 'in_progress')
                  ->order_by('entry_drafts.last_modified_at', 'DESC');
        if (!$is_admin && !$shared) $this->db->where('entry_drafts.user_id', $user_id);
        if ($entry_type) $this->db->where('entry_drafts.entry_type', $entry_type);
        return $this->db->get()->result();
    }

    public function get_draft($draft_id) {
        return $this->db->where('id', $draft_id)->get('entry_drafts')->row();
    }

    // Joins live product display fields (name/code/hsn/current price+stock) onto
    // each draft item — the draft row itself only stores the user's entered
    // transactional values, not display metadata, so resume always shows fresh data.
    public function get_items($draft_id) {
        return $this->db
            ->select('entry_draft_items.*, products.name as product_name, products.item_code,
                      products.hsn_code, products.selling_price, GREATEST(products.stock_qty, 0) as current_stock_qty,
                      pb.barcode as product_barcode')
            ->join('products', 'products.id = entry_draft_items.product_id', 'left')
            ->join('product_barcodes pb', "pb.product_id = products.id AND pb.unit_type = 'PCS'", 'left')
            ->where('draft_id', $draft_id)
            ->order_by('scan_sequence', 'ASC')
            ->get('entry_draft_items')->result();
    }

    // Ownership check used by resume/autosave/heartbeat/delete endpoints.
    // $shared = TRUE lets any signed-in user open and continue the draft (the
    // GRN flow); delete deliberately never passes it, so a draft can only be
    // thrown away by the person who started it or an admin.
    public function can_access($draft_id, $user_id, $is_admin, $shared = false) {
        $draft = $this->get_draft($draft_id);
        if (!$draft) return false;
        return $is_admin || $shared || (int)$draft->user_id === (int)$user_id;
    }

    private $item_fields = [
        'product_id', 'variant_id', 'variant_sku', 'size_name', 'colour_name', 'po_item_id', 'barcode', 'batch_no', 'expiry_date', 'mrp', 'rate',
        'gst_percent', 'qty', 'free_qty', 'received_qty', 'accepted_qty', 'rejected_qty',
        'rejection_reason', 'discount_percent', 'discount_amount', 'tax_amount', 'total_amount',
    ];

    // Only these are ever accepted from client-supplied header data — everything
    // else (status, user_id, locked_by, completed_ref_id, ...) is server-controlled
    // and must never be settable via the autosave payload.
    private $header_fields = [
        'supplier_id', 'po_id', 'invoice_no', 'invoice_date', 'payment_mode',
        'remarks', 'location_id', 'header_json',
    ];

    // Whitelists a raw (client-supplied) item dict down to known columns, coercing
    // blanks to NULL so numeric columns don't choke on empty strings.
    private function _sanitize_item($raw) {
        $out = [];
        foreach ($this->item_fields as $f) {
            $v = $raw[$f] ?? null;
            $out[$f] = ($v === '' || $v === null) ? null : $v;
        }
        return $out;
    }

    private function _sanitize_header($raw) {
        $out = [];
        foreach ($this->header_fields as $f) {
            $v = $raw[$f] ?? null;
            $out[$f] = ($v === '') ? null : $v;
        }
        return $out;
    }

    // Upsert: insert-or-update header, then delete-and-reinsert items — mirrors
    // the pattern already used by Purchase_model::update(). $header/$items are
    // raw client-supplied data; only whitelisted fields are ever written.
    public function save_draft($draft_id, $header, $items, $user_id, $entry_type) {
        $this->db->trans_start();
        $now = date('Y-m-d H:i:s');
        $header = $this->_sanitize_header($header);

        if (!$draft_id) {
            $header['entry_type']       = $entry_type;
            $header['user_id']          = $user_id;
            $header['status']           = 'in_progress';
            $header['created_at']       = $now;
            $header['last_modified_at'] = $now;
            $header['last_autosaved_at']= $now;
            $this->db->insert('entry_drafts', $header);
            $draft_id = $this->db->insert_id();
        } else {
            $header['last_modified_at']  = $now;
            $header['last_autosaved_at'] = $now;
            $this->db->where('id', $draft_id)->update('entry_drafts', $header);
            $this->db->where('draft_id', $draft_id)->delete('entry_draft_items');
        }

        if (empty($items)) {
            error_log("GHOST DELETION DETECTED: save_draft called with empty items array for draft {$draft_id}. IP: " . $_SERVER['REMOTE_ADDR'] . ". User: {$user_id}");
        }

        $seq = 0;
        foreach ($items as $raw) {
            if (empty($raw['product_id'])) continue;
            $item = $this->_sanitize_item($raw);
            $item['draft_id']      = $draft_id;
            $item['scan_sequence'] = $seq++;
            $this->db->insert('entry_draft_items', $item);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $draft_id : false;
    }

    public function mark_completed($draft_id, $ref_id) {
        if (!$draft_id) return;
        $this->db->where('id', $draft_id)->update('entry_drafts', [
            'status'           => 'completed',
            'completed_ref_id' => $ref_id,
            'last_modified_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function mark_abandoned($draft_id) {
        $this->db->where('id', $draft_id)->update('entry_drafts', ['status' => 'abandoned']);
    }

    public function delete_draft($draft_id, $user_id, $is_admin) {
        if (!$this->can_access($draft_id, $user_id, $is_admin)) return false;
        $this->db->where('id', $draft_id)->delete('entry_drafts'); // items cascade via FK
        return true;
    }

    // Opportunistic cleanup — no cron needed, called as a side effect of loading the drafts list.
    public function cleanup_stale($days = 30) {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $this->db->where('status !=', 'completed')
                  ->where('last_modified_at <', $cutoff)
                  ->delete('entry_drafts'); // items cascade via FK
    }

    public function acquire_lock($draft_id, $user_id) {
        $this->db->where('id', $draft_id)->update('entry_drafts', [
            'locked_by' => $user_id,
            'locked_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function heartbeat($draft_id, $user_id) {
        $this->acquire_lock($draft_id, $user_id);
    }

    public function release_lock($draft_id) {
        $this->db->where('id', $draft_id)->update('entry_drafts', ['locked_by' => null, 'locked_at' => null]);
    }

    // Returns the locking user's name if the draft is actively locked by someone else, else null.
    public function locked_by_other($draft_id, $current_user_id) {
        $draft = $this->get_draft($draft_id);
        if (!$draft || !$draft->locked_by || (int)$draft->locked_by === (int)$current_user_id) return null;
        $stale_cutoff = date('Y-m-d H:i:s', strtotime("-{$this->heartbeat_stale_seconds} seconds"));
        if (!$draft->locked_at || $draft->locked_at < $stale_cutoff) return null; // stale lock, ignore
        $u = $this->db->select('name')->where('id', $draft->locked_by)->get('users')->row();
        return $u ? $u->name : 'another user';
    }
}
