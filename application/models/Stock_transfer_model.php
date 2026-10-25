<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_transfer_model extends CI_Model {

    // document_sequences.document_type keys for the two independent series
    const SEQ_OUT = 'stock_transfer_out';
    const SEQ_IN  = 'stock_transfer_in';

    public function __construct()
    {
        parent::__construct();
        $this->_ensure_schema();
    }

    // Self-heal the TRO/TRI schema (sql/62_transfer_out_in_series.sql) so a
    // PHP deploy can never run ahead of an instance's database.
    private function _ensure_schema()
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        if (!$this->db->table_exists('stock_transfers')) return;
        if ($this->db->field_exists('transfer_in_id', 'stock_transfers') && $this->db->table_exists('stock_transfer_ins')) return;

        if (!$this->db->field_exists('financial_year', 'stock_transfers')) {
            $this->db->query("ALTER TABLE stock_transfers ADD COLUMN financial_year VARCHAR(8) NULL AFTER transfer_no");
        }
        if (!$this->db->field_exists('transfer_in_id', 'stock_transfers')) {
            $this->db->query("ALTER TABLE stock_transfers ADD COLUMN transfer_in_id INT(11) NULL DEFAULT NULL, ADD INDEX idx_transfer_in_id (transfer_in_id)");
        }
        $col = $this->db->query("SHOW COLUMNS FROM stock_transfers LIKE 'status'")->row();
        if ($col && stripos($col->Type, 'enum') === 0 && stripos($col->Type, 'in_transit') === false) {
            $this->db->query("ALTER TABLE stock_transfers MODIFY COLUMN status ENUM('draft','pending','approved','rejected','completed','in_transit') NOT NULL DEFAULT 'draft'");
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS stock_transfer_ins (
            id                INT(11)      NOT NULL AUTO_INCREMENT,
            tri_no            VARCHAR(30)  NOT NULL,
            financial_year    VARCHAR(8)   DEFAULT NULL,
            transfer_id       INT(11)      NOT NULL,
            transfer_no       VARCHAR(30)  NOT NULL,
            from_location_id  INT(11)      NOT NULL,
            to_location_id    INT(11)      NOT NULL,
            transfer_date     DATE         NOT NULL,
            status            ENUM('in_transit','received','cancelled') NOT NULL DEFAULT 'in_transit',
            remarks           VARCHAR(255) DEFAULT NULL,
            created_by        INT(11)      DEFAULT NULL,
            created_by_name   VARCHAR(100) DEFAULT NULL,
            created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            received_by       INT(11)      DEFAULT NULL,
            received_by_name  VARCHAR(100) DEFAULT NULL,
            received_at       DATETIME     DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tri_no (tri_no),
            UNIQUE KEY uq_tri_transfer (transfer_id),
            KEY idx_tri_to_loc (to_location_id),
            KEY idx_tri_from_loc (from_location_id),
            KEY idx_tri_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS document_sequences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            location_id INT NOT NULL,
            financial_year VARCHAR(8) NOT NULL,
            document_type VARCHAR(30) NOT NULL,
            last_serial INT NOT NULL DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_seq_combo (location_id, financial_year, document_type),
            INDEX idx_seq_location (location_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // Next number in a location's TRO or TRI series, e.g. TRO-WH-0001 / TRI-ANA-0001.
    // Uses the same atomic document_sequences counter as DocumentNumbering (one
    // statement, race-safe — never MAX()+1). The number carries no FY, so the
    // series is continuous (financial_year key 'ALL') and can never repeat.
    // Called inside the caller's transaction: a rolled-back save gives the number back.
    public function next_doc_no($seq_type, $location_id)
    {
        $prefix = $seq_type === self::SEQ_IN ? 'TRI' : 'TRO';
        $table  = $seq_type === self::SEQ_IN ? 'stock_transfer_ins' : 'stock_transfers';
        $field  = $seq_type === self::SEQ_IN ? 'tri_no' : 'transfer_no';

        $loc  = $this->db->select('code')->where('id', (int)$location_id)->get('locations')->row();
        $code = $loc ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $loc->code)) : '';
        if ($code === '') $code = 'L' . (int)$location_id;

        for ($try = 0; $try < 5; $try++) {
            $this->db->query(
                "INSERT INTO document_sequences (location_id, financial_year, document_type, last_serial)
                 VALUES (?, 'ALL', ?, 1)
                 ON DUPLICATE KEY UPDATE last_serial = LAST_INSERT_ID(last_serial + 1)",
                [(int)$location_id, $seq_type]
            );
            // affected_rows: 1 = fresh row (serial 1), 2 = incremented. On a fresh
            // insert insert_id() is the table's auto-increment id, NOT the serial.
            $serial = $this->db->affected_rows() === 1 ? 1 : (int)$this->db->insert_id();
            $no = $prefix . '-' . $code . '-' . str_pad($serial, 4, '0', STR_PAD_LEFT);
            // Guard against a number already taken (e.g. a location code that was
            // re-used) — skip forward rather than collide with the UNIQUE key.
            if (!$this->db->where($field, $no)->count_all_results($table)) return $no;
        }
        return $no;
    }

    private function _financial_year($date = null)
    {
        $this->load->library('DocumentNumbering');
        return $this->documentnumbering->resolve_financial_year($date);
    }

    public function generate_transfer_no($from_location_id = 0)
    {
        return $this->next_doc_no(self::SEQ_OUT, $from_location_id);
    }

    public function create($data, $items)
    {
        try {
            $fy = $this->_financial_year();
        } catch (Exception $e) {
            return false;
        }
        $this->db->trans_start();
        $data['transfer_no']  = $this->generate_transfer_no($data['from_location_id']);
        $data['financial_year'] = $fy;
        $data['requested_at'] = date('Y-m-d H:i:s');
        $data['created_at']   = date('Y-m-d H:i:s');
        $this->db->insert('stock_transfers', $data);
        $transfer_id = $this->db->insert_id();
        foreach ($items as $item) {
            $item['transfer_id'] = $transfer_id;
            $this->db->insert('stock_transfer_items', $item);
        }
        $this->db->trans_complete();
        return $this->db->trans_status() ? $transfer_id : false;
    }

    public function get_by_id($id)
    {
        return $this->db
            ->select('st.*,
                      fl.name AS from_location_name, fl.type AS from_location_type, fl.code AS from_location_code,
                      tl.name AS to_location_name,   tl.type AS to_location_type,   tl.code AS to_location_code,
                      tri.id AS tri_id, tri.tri_no, tri.status AS tri_status,
                      tri.received_by_name, tri.received_at')
            ->from('stock_transfers st')
            ->join('locations fl', 'fl.id = st.from_location_id', 'left')
            ->join('locations tl', 'tl.id = st.to_location_id',   'left')
            ->join('stock_transfer_ins tri', 'tri.transfer_id = st.id', 'left')
            ->where('st.id', $id)
            ->get()->row();
    }

    // ─── Transfer In (TRI) ──────────────────────────────────────

    public function get_transfer_in($tri_id)
    {
        return $this->db
            ->select('tri.*,
                      fl.name AS from_location_name, fl.type AS from_location_type, fl.code AS from_location_code,
                      tl.name AS to_location_name,   tl.type AS to_location_type,   tl.code AS to_location_code,
                      st.status AS tro_status, st.reason, st.notes,
                      st.requested_by_name, st.requested_at,
                      st.approved_by_name, st.approved_at')
            ->from('stock_transfer_ins tri')
            ->join('stock_transfers st', 'st.id = tri.transfer_id', 'left')
            ->join('locations fl', 'fl.id = tri.from_location_id', 'left')
            ->join('locations tl', 'tl.id = tri.to_location_id',   'left')
            ->where('tri.id', (int)$tri_id)
            ->get()->row();
    }

    public function get_all_transfer_ins($filters = [])
    {
        $this->db
            ->select('tri.*, fl.name AS from_location_name, tl.name AS to_location_name')
            ->from('stock_transfer_ins tri')
            ->join('locations fl', 'fl.id = tri.from_location_id', 'left')
            ->join('locations tl', 'tl.id = tri.to_location_id',   'left');

        if (!empty($filters['status']))           $this->db->where('tri.status', $filters['status']);
        if (!empty($filters['from_location_id'])) $this->db->where('tri.from_location_id', (int)$filters['from_location_id']);
        if (!empty($filters['to_location_id']))   $this->db->where('tri.to_location_id', (int)$filters['to_location_id']);
        if (!empty($filters['date_from']))        $this->db->where('tri.transfer_date >=', $filters['date_from']);
        if (!empty($filters['date_to']))          $this->db->where('tri.transfer_date <=', $filters['date_to']);
        if (!empty($filters['q'])) {
            $this->_search_clause($filters['q'], 'tri.transfer_id');
        }
        return $this->db->order_by('tri.id', 'DESC')->get()->result();
    }

    public function get_in_transit_count($to_location_id = null)
    {
        if (!$this->db->table_exists('stock_transfer_ins')) return 0;
        $this->db->where('status', 'in_transit');
        if ($to_location_id) $this->db->where('to_location_id', (int)$to_location_id);
        return (int)$this->db->count_all_results('stock_transfer_ins');
    }

    // One search box for TRO no, TRI no, item name/code and barcode — a match on
    // either document number returns the linked pair.
    private function _search_clause($q, $transfer_id_col)
    {
        $q = trim($q);
        if ($q === '') return;
        $like = $this->db->escape_like_str($q);
        $e    = "'%{$like}%' ESCAPE '!'";
        $this->db->where("(
            {$transfer_id_col} IN (SELECT s1.id FROM stock_transfers s1 WHERE s1.transfer_no LIKE {$e})
            OR {$transfer_id_col} IN (SELECT i1.transfer_id FROM stock_transfer_ins i1 WHERE i1.tri_no LIKE {$e})
            OR {$transfer_id_col} IN (
                SELECT sti.transfer_id FROM stock_transfer_items sti
                LEFT JOIN products p ON p.id = sti.product_id
                WHERE sti.product_name LIKE {$e} OR p.item_code LIKE {$e}
                   OR EXISTS (SELECT 1 FROM product_barcodes pb WHERE pb.product_id = sti.product_id AND pb.barcode LIKE {$e})
            )
        )", NULL, FALSE);
    }

    public function get_items($transfer_id)
    {
        return $this->db
            ->select('stock_transfer_items.*, products.item_code, products.mrp, COALESCE(pb.barcode,"") AS ean_code')
            ->join('products', 'products.id = stock_transfer_items.product_id', 'left')
            ->join('product_barcodes pb', 'pb.product_id = stock_transfer_items.product_id AND pb.unit_type = "PCS"', 'left')
            ->where('transfer_id', $transfer_id)
            ->get('stock_transfer_items')
            ->result();
    }

    public function get_all($filters = [])
    {
        $this->db
            ->select('st.*,
                      fl.name AS from_location_name,
                      tl.name AS to_location_name,
                      tri.id AS tri_id, tri.tri_no, tri.status AS tri_status,
                      tri.received_by_name, tri.received_at')
            ->from('stock_transfers st')
            ->join('locations fl', 'fl.id = st.from_location_id', 'left')
            ->join('locations tl', 'tl.id = st.to_location_id',   'left')
            ->join('stock_transfer_ins tri', 'tri.transfer_id = st.id', 'left');

        if (!empty($filters['q'])) {
            $this->_search_clause($filters['q'], 'st.id');
        }
        if (!empty($filters['item'])) {
            $this->_search_clause($filters['item'], 'st.id');
        }
        if (!empty($filters['tro_no'])) $this->db->like('st.transfer_no', $filters['tro_no']);
        if (!empty($filters['tri_no'])) $this->db->like('tri.tri_no', $filters['tri_no']);
        if (!empty($filters['status'])) {
            $this->db->where('st.status', $filters['status']);
        }
        if (!empty($filters['from_location_id'])) {
            $this->db->where('st.from_location_id', $filters['from_location_id']);
        }
        if (!empty($filters['to_location_id'])) {
            $this->db->where('st.to_location_id', $filters['to_location_id']);
        }
        if (!empty($filters['location_id'])) {
            // show transfers where this location is either side
            $lid = (int)$filters['location_id'];
            $this->db->where("(st.from_location_id = {$lid} OR st.to_location_id = {$lid})", NULL, FALSE);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('DATE(st.requested_at) >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('DATE(st.requested_at) <=', $filters['date_to']);
        }
        return $this->db->order_by('st.created_at', 'DESC')->get()->result();
    }

    public function get_pending_count($location_id = null)
    {
        $this->db->where('status', 'pending');
        if ($location_id) {
            $lid = (int)$location_id;
            $this->db->where("(from_location_id = {$lid} OR to_location_id = {$lid})", NULL, FALSE);
        }
        return (int)$this->db->count_all_results('stock_transfers');
    }

    public function submit_for_approval($id, $user_id, $user_name)
    {
        $this->db->where('id', $id)->where('status', 'draft')
            ->update('stock_transfers', [
                'status'           => 'pending',
                'requested_by'     => $user_id,
                'requested_by_name'=> $user_name,
                'requested_at'     => date('Y-m-d H:i:s'),
            ]);
        return $this->db->affected_rows();
    }

    public function approve($id, $user_id, $user_name, $approved_items = [])
    {
        $this->db->trans_start();
        $now = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->where('status', 'pending')
            ->update('stock_transfers', [
                'status'           => 'approved',
                'approved_by'      => $user_id,
                'approved_by_name' => $user_name,
                'approved_at'      => $now,
            ]);

        // Update approved_qty per item if provided
        if (!empty($approved_items)) {
            foreach ($approved_items as $item_id => $aqty) {
                $this->db->where('id', $item_id)->where('transfer_id', $id)
                    ->update('stock_transfer_items', ['approved_qty' => (float)$aqty]);
            }
        } else {
            // approved_qty = requested_qty for all items
            $this->db->where('transfer_id', $id)
                ->set('approved_qty', 'requested_qty', FALSE)
                ->update('stock_transfer_items');
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function reject($id, $user_id, $user_name, $reason)
    {
        $this->db->where('id', $id)->where('status', 'pending')
            ->update('stock_transfers', [
                'status'            => 'rejected',
                'rejected_by'       => $user_id,
                'rejected_by_name'  => $user_name,
                'rejected_at'       => date('Y-m-d H:i:s'),
                'rejection_reason'  => $reason,
            ]);
        return $this->db->affected_rows();
    }

    public function complete($id, $user_id, $user_name, $location_model)
    {
        $transfer = $this->get_by_id($id);
        if (!$transfer || $transfer->status !== 'approved') {
            return false;
        }
        $items = $this->get_items($id);
        $this->db->trans_start();

        foreach ($items as $item) {
            $qty = (float)($item->approved_qty ?? $item->requested_qty);
            if ($qty <= 0) continue;

            $this->load->model('Garment_model');
            $before_from = $location_model->get_stock($item->product_id, $transfer->from_location_id);
            if (!empty($item->variant_id)) {
                $this->Garment_model->decrease_variant_stock($item->variant_id, $item->product_id, $transfer->from_location_id, $qty);
            } else {
                $location_model->decrease_stock($item->product_id, $transfer->from_location_id, $qty);
            }
            $after_from  = $location_model->get_stock($item->product_id, $transfer->from_location_id);

            $before_to = $location_model->get_stock($item->product_id, $transfer->to_location_id);
            if (!empty($item->variant_id)) {
                $this->Garment_model->increase_variant_stock($item->variant_id, $item->product_id, $transfer->to_location_id, $qty);
            } else {
                $location_model->increase_stock($item->product_id, $transfer->to_location_id, $qty);
            }
            $after_to  = $location_model->get_stock($item->product_id, $transfer->to_location_id);

            // Log movements
            $location_model->log_movement([
                'product_id'     => $item->product_id,
                'product_name'   => $item->product_name,
                'location_id'    => $transfer->from_location_id,
                'location_name'  => $transfer->from_location_name,
                'movement_type'  => 'transfer_out',
                'quantity'       => $qty,
                'balance_before' => $before_from,
                'balance_after'  => $after_from,
                'reference_type' => 'stock_transfer',
                'reference_id'   => $id,
                'reference_no'   => $transfer->transfer_no,
                'user_id'        => $user_id,
                'user_name'      => $user_name,
            ]);
            $location_model->log_movement([
                'product_id'     => $item->product_id,
                'product_name'   => $item->product_name,
                'location_id'    => $transfer->to_location_id,
                'location_name'  => $transfer->to_location_name,
                'movement_type'  => 'transfer_in',
                'quantity'       => $qty,
                'balance_before' => $before_to,
                'balance_after'  => $after_to,
                'reference_type' => 'stock_transfer',
                'reference_id'   => $id,
                'reference_no'   => $transfer->transfer_no,
                'user_id'        => $user_id,
                'user_name'      => $user_name,
            ]);
        }

        // Serialized units move one by one: the qty above keeps the location
        // totals right, this moves the individual serials that were picked when
        // the transfer was raised. Only units still IN_STOCK at the source are
        // moved — anything sold, under repair, returned or scrapped in the
        // meantime is skipped rather than silently teleported.
        $this->load->model('Serial_model');
        if ($this->Serial_model->schema_ready()) {
            foreach ($this->Serial_model->get_by_doc('stock_transfer', $id) as $srow) {
                if ($srow->status !== 'IN_STOCK') continue;
                if ((int)$srow->location_id !== (int)$transfer->from_location_id) continue;
                $this->Serial_model->set_status($srow->id, 'IN_STOCK', [
                    'txn_type'     => 'TRANSFERRED',
                    'reference_id' => $id,
                    'reference_no' => $transfer->transfer_no,
                    'location_id'  => $transfer->to_location_id,
                    'remarks'      => 'Transferred on ' . $transfer->transfer_no,
                ]);
            }
        }

        $this->db->where('id', $id)->update('stock_transfers', [
            'status'             => 'completed',
            'completed_by'       => $user_id,
            'completed_by_name'  => $user_name,
            'completed_at'       => date('Y-m-d H:i:s'),
        ]);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Approve a pending Transfer Out and dispatch it, all in ONE transaction:
     * approved qty → stock out of the source → TRI number from the DESTINATION's
     * series → linked Transfer In row (status in_transit). Destination stock is
     * NOT added here — that happens once, in receive().
     * The status-guarded UPDATE makes a double-click / refresh / retry a no-op.
     * Returns ['ok'=>bool, 'error'=>string, 'tri_id'=>int, 'tri_no'=>string].
     */
    public function dispatch($id, $user_id, $user_name, $approved_items, $location_model)
    {
        $transfer = $this->get_by_id($id);
        if (!$transfer || $transfer->status !== 'pending') {
            return ['ok' => false, 'error' => 'Transfer is not pending approval (it may already have been approved).'];
        }
        try {
            $fy = $this->_financial_year();
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $now = date('Y-m-d H:i:s');
        $this->db->trans_begin();

        $this->db->where('id', $id)->where('status', 'pending')
            ->update('stock_transfers', [
                'status'           => 'in_transit',
                'approved_by'      => $user_id,
                'approved_by_name' => $user_name,
                'approved_at'      => $now,
            ]);
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Transfer was already processed by another request.'];
        }

        if (!empty($approved_items)) {
            foreach ($approved_items as $item_id => $aqty) {
                $this->db->where('id', (int)$item_id)->where('transfer_id', $id)
                    ->update('stock_transfer_items', ['approved_qty' => max(0, (float)$aqty)]);
            }
        }
        $this->db->where('transfer_id', $id)->where('approved_qty IS NULL', NULL, FALSE)
            ->set('approved_qty', 'requested_qty', FALSE)
            ->update('stock_transfer_items');

        $this->load->model('Garment_model');
        foreach ($this->get_items($id) as $item) {
            $qty = (float)$item->approved_qty;
            if ($qty <= 0) continue;
            $before = $location_model->get_stock($item->product_id, $transfer->from_location_id);
            if (!empty($item->variant_id)) {
                $this->Garment_model->decrease_variant_stock($item->variant_id, $item->product_id, $transfer->from_location_id, $qty);
            } else {
                $location_model->decrease_stock($item->product_id, $transfer->from_location_id, $qty);
            }
            $after  = $location_model->get_stock($item->product_id, $transfer->from_location_id);
            $location_model->log_movement([
                'product_id'     => $item->product_id,
                'product_name'   => $item->product_name,
                'location_id'    => $transfer->from_location_id,
                'location_name'  => $transfer->from_location_name,
                'movement_type'  => 'transfer_out',
                'quantity'       => $qty,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_type' => 'stock_transfer',
                'reference_id'   => $id,
                'reference_no'   => $transfer->transfer_no,
                'user_id'        => $user_id,
                'user_name'      => $user_name,
            ]);
        }

        $tri_no = $this->next_doc_no(self::SEQ_IN, $transfer->to_location_id);
        $this->db->insert('stock_transfer_ins', [
            'tri_no'           => $tri_no,
            'financial_year'   => $fy,
            'transfer_id'      => $id,
            'transfer_no'      => $transfer->transfer_no,
            'from_location_id' => $transfer->from_location_id,
            'to_location_id'   => $transfer->to_location_id,
            'transfer_date'    => date('Y-m-d'),
            'status'           => 'in_transit',
            'remarks'          => $transfer->reason,
            'created_by'       => $user_id,
            'created_by_name'  => $user_name,
            'created_at'       => $now,
        ]);
        $tri_id = (int)$this->db->insert_id();
        $this->db->where('id', $id)->update('stock_transfers', ['transfer_in_id' => $tri_id]);

        if ($this->db->trans_status() === FALSE || !$tri_id) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Database error — nothing was saved, stock unchanged.'];
        }
        $this->db->trans_commit();
        return ['ok' => true, 'tri_id' => $tri_id, 'tri_no' => $tri_no];
    }

    /**
     * Destination receives a Transfer In: stock into the destination, picked
     * serials move over, TRI → received and TRO → completed. One transaction;
     * the status-guarded UPDATE stops a second receive from adding stock twice.
     */
    public function receive($tri_id, $user_id, $user_name, $location_model)
    {
        $tri = $this->get_transfer_in($tri_id);
        if (!$tri || $tri->status !== 'in_transit') {
            return ['ok' => false, 'error' => 'This Transfer In is not in transit (it may already have been received).'];
        }
        $transfer_id = (int)$tri->transfer_id;
        $now = date('Y-m-d H:i:s');
        $this->db->trans_begin();

        $this->db->where('id', (int)$tri_id)->where('status', 'in_transit')
            ->update('stock_transfer_ins', [
                'status'           => 'received',
                'received_by'      => $user_id,
                'received_by_name' => $user_name,
                'received_at'      => $now,
            ]);
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Transfer In was already received by another request.'];
        }

        $this->load->model('Garment_model');
        foreach ($this->get_items($transfer_id) as $item) {
            $qty = (float)($item->approved_qty ?? $item->requested_qty);
            if ($qty <= 0) continue;
            $before = $location_model->get_stock($item->product_id, $tri->to_location_id);
            if (!empty($item->variant_id)) {
                $this->Garment_model->increase_variant_stock($item->variant_id, $item->product_id, $tri->to_location_id, $qty);
            } else {
                $location_model->increase_stock($item->product_id, $tri->to_location_id, $qty);
            }
            $after  = $location_model->get_stock($item->product_id, $tri->to_location_id);
            $location_model->log_movement([
                'product_id'     => $item->product_id,
                'product_name'   => $item->product_name,
                'location_id'    => $tri->to_location_id,
                'location_name'  => $tri->to_location_name,
                'movement_type'  => 'transfer_in',
                'quantity'       => $qty,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_type' => 'stock_transfer',
                'reference_id'   => $transfer_id,
                'reference_no'   => $tri->tri_no,
                'user_id'        => $user_id,
                'user_name'      => $user_name,
            ]);
        }

        // Same rule as complete(): only units still IN_STOCK at the source move.
        $this->load->model('Serial_model');
        if ($this->Serial_model->schema_ready()) {
            foreach ($this->Serial_model->get_by_doc('stock_transfer', $transfer_id) as $srow) {
                if ($srow->status !== 'IN_STOCK') continue;
                if ((int)$srow->location_id !== (int)$tri->from_location_id) continue;
                $this->Serial_model->set_status($srow->id, 'IN_STOCK', [
                    'txn_type'     => 'TRANSFERRED',
                    'reference_id' => $transfer_id,
                    'reference_no' => $tri->tri_no,
                    'location_id'  => $tri->to_location_id,
                    'remarks'      => 'Received on ' . $tri->tri_no . ' (' . $tri->transfer_no . ')',
                ]);
            }
        }

        $this->db->where('id', $transfer_id)->update('stock_transfers', [
            'status'            => 'completed',
            'completed_by'      => $user_id,
            'completed_by_name' => $user_name,
            'completed_at'      => $now,
        ]);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Database error — nothing was saved, stock unchanged.'];
        }
        $this->db->trans_commit();
        return ['ok' => true];
    }

    public function update_items($transfer_id, $items)
    {
        $this->db->where('transfer_id', $transfer_id)->delete('stock_transfer_items');
        foreach ($items as $item) {
            $item['transfer_id'] = $transfer_id;
            $this->db->insert('stock_transfer_items', $item);
        }
    }

    public function delete_draft($id)
    {
        $this->db->where('id', $id)->where('status', 'draft')->delete('stock_transfers');
        $this->db->where('transfer_id', $id)->delete('stock_transfer_items');
        return $this->db->affected_rows();
    }
}
