<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Serial number engine — shared by Purchase, GRN, Billing, Returns and Transfers.
 *
 * Two rules hold everywhere in this class:
 *   1. A serial number is unique for its whole lifetime (enforced by a UNIQUE
 *      index on item_serials.serial_no, not just by these checks).
 *   2. Nothing changes an item_serials row without writing a serial_movements
 *      row in the same breath. History is append-only; move_to()/set_status()
 *      are the only sanctioned way to change a serial, so the audit trail
 *      cannot be bypassed by accident.
 */
class Serial_model extends CI_Model {

    // Statuses a serial can be sold / transferred / returned from.
    const AVAILABLE = 'IN_STOCK';

    /** Is the serial/warranty schema present on this database? */
    public function schema_ready() {
        return $this->db->table_exists('item_serials');
    }

    /** Does this product need per-unit serial numbers? */
    public function is_serialized($product_id) {
        if (!$this->db->field_exists('maintain_serial', 'products')) return false;
        $r = $this->db->select('maintain_serial')->where('id', (int)$product_id)
                      ->get('products')->row();
        return $r && (int)$r->maintain_serial === 1;
    }

    /**
     * Products that need serials, keyed by id — one query instead of one per
     * line, because a purchase can carry a hundred lines.
     */
    public function serialized_map(array $product_ids) {
        $out = [];
        $ids = array_filter(array_map('intval', $product_ids));
        if (!$ids || !$this->db->field_exists('maintain_serial', 'products')) return $out;
        $rows = $this->db->select('id, name, item_code, maintain_serial, warranty_applicable, warranty_days, serial_entry_method')
                         ->where_in('id', array_unique($ids))
                         ->get('products')->result();
        foreach ($rows as $r) $out[(int)$r->id] = $r;
        return $out;
    }

    public function normalize($serial) {
        return strtoupper(trim((string)$serial));
    }

    public function get_by_serial($serial) {
        return $this->db->where('serial_no', $this->normalize($serial))
                        ->get('item_serials')->row();
    }

    /** Full detail for the search screen / billing scan popup. */
    public function get_detail($serial) {
        $s = $this->normalize($serial);
        return $this->db
            ->select('s.*, p.name AS product_name, p.item_code, p.product_type, p.model_name, p.model_number, p.warranty_applicable, p.warranty_days,
                      sup.name AS supplier_name, l.name AS location_name,
                      w.invoice_id, w.sale_date, w.warranty_start_date, w.warranty_days AS sold_warranty_days,
                      w.warranty_expiry_date, w.customer_id,
                      c.name AS customer_name, c.phone AS customer_phone,
                      i.invoice_no')
            ->from('item_serials s')
            ->join('products p',   'p.id = s.product_id', 'left')
            ->join('suppliers sup','sup.id = s.supplier_id', 'left')
            ->join('locations l',  'l.id = s.location_id', 'left')
            ->join('serial_warranties w', 'w.serial_id = s.id AND w.is_returned = 0', 'left')
            ->join('invoices i',   'i.id = w.invoice_id', 'left')
            ->join('customers c',  'c.id = w.customer_id', 'left')
            ->group_start()
                ->where('s.serial_no', $s)
                ->or_where('s.chassis_no', $s)
            ->group_end()
            ->order_by('w.id', 'DESC')
            ->limit(1)
            ->get()->row();
    }

    // ── Validation ──────────────────────────────────────────────────────────

    /**
     * Validates a batch of serials for INBOUND capture (purchase / GRN).
     * Returns ['ok' => bool, 'errors' => [...], 'serials' => [normalized]].
     *
     * Runs every check the spec lists: blank, duplicate-in-batch, already-exists,
     * count mismatch, and over-count. All of it server-side — the browser copy
     * of these checks is a convenience, never the gate.
     */
    public function validate_inbound($product_id, $expected_qty, array $serials, $product_name = '') {
        $errors = [];
        $clean  = [];
        $seen   = [];
        $label  = $product_name !== '' ? '"' . $product_name . '"' : 'item #' . (int)$product_id;

        foreach ($serials as $raw) {
            $sn = $this->normalize($raw);
            if ($sn === '') {
                $errors[] = 'Blank serial number is not allowed for ' . $label . '.';
                continue;
            }
            if (isset($seen[$sn])) {
                $errors[] = 'Duplicate Serial Number. "' . $sn . '" has already been entered for ' . $label . '.';
                continue;
            }
            $seen[$sn] = true;
            $clean[]   = $sn;
        }

        $expected_qty = (int)$expected_qty;
        if (count($clean) > $expected_qty) {
            $errors[] = 'Maximum ' . $expected_qty . ' serial numbers allowed for ' . $label . '.';
        } elseif (count($clean) < $expected_qty) {
            $errors[] = 'Please enter all ' . $expected_qty . ' serial numbers for ' . $label
                      . ' before completing the purchase (' . count($clean) . ' entered).';
        }

        // Existing-serial check in one query rather than one per scan.
        if ($clean) {
            $hits = $this->db->select('serial_no')->where_in('serial_no', $clean)
                             ->get('item_serials')->result();
            foreach ($hits as $h) {
                $errors[] = 'Serial Number ' . $h->serial_no . ' already exists.';
            }
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'serials' => $clean];
    }

    /**
     * Validates one serial for OUTBOUND use (billing / transfer / return).
     * $product_id NULL means "identify the item from the serial" (scan-first
     * billing); passing it enforces the spec's wrong-item rule.
     */
    public function validate_outbound($serial, $product_id = null, $location_id = null, array $allowed_status = ['IN_STOCK']) {
        $sn  = $this->normalize($serial);
        if ($sn === '') return ['ok' => false, 'error' => 'Please scan or enter a serial number.'];

        $row = $this->get_detail($sn);
        if (!$row) return ['ok' => false, 'error' => 'Serial Number ' . $sn . ' does not exist.'];

        if ($product_id !== null && (int)$row->product_id !== (int)$product_id) {
            return ['ok' => false, 'row' => $row,
                    'error' => 'Invalid Serial Number. This serial number belongs to another item ('
                             . $row->product_name . ').'];
        }

        if (!in_array($row->status, $allowed_status, true)) {
            return ['ok' => false, 'row' => $row, 'error' => $this->status_message($sn, $row->status)];
        }

        // Location is only enforced when the caller supplies one — single-location
        // installs pass NULL and skip it.
        if ($location_id !== null && $row->location_id !== null
            && (int)$row->location_id !== (int)$location_id) {
            return ['ok' => false, 'row' => $row,
                    'error' => 'Serial Number is not available at the current location'
                             . ($row->location_name ? ' (it is at ' . $row->location_name . ')' : '') . '.'];
        }

        return ['ok' => true, 'row' => $row];
    }

    /** The spec's exact wording for each blocked status. */
    public function status_message($sn, $status) {
        switch ($status) {
            case 'SOLD':            return 'Serial Number ' . $sn . ' has already been sold.';
            case 'PURCHASE_RETURN': return 'Serial Number ' . $sn . ' was returned to the supplier.';
            case 'SCRAPPED':        return 'Serial Number ' . $sn . ' has been scrapped.';
            case 'UNDER_REPAIR':    return 'Serial Number ' . $sn . ' is currently under repair.';
            case 'UNDER_WARRANTY':  return 'Serial Number ' . $sn . ' is held against a warranty claim.';
            case 'REPLACED':        return 'Serial Number ' . $sn . ' was replaced under warranty.';
            case 'TRANSFERRED':     return 'Serial Number ' . $sn . ' is in transit between locations.';
            default:                return 'Serial Number ' . $sn . ' is not available (status: ' . $status . ').';
        }
    }

    // ── Writes ──────────────────────────────────────────────────────────────

    /**
     * Creates serial rows for an inbound document. Assumes validate_inbound()
     * already passed; the UNIQUE index is the last line of defence if two
     * users save the same serial at the same moment.
     */
    public function create_inbound($serials, array $meta) {
        $now  = date('Y-m-d H:i:s');
        $uid  = $this->session->userdata('user_id');
        $ids  = [];

        $prod = null;
        if (!empty($meta['product_id'])) {
            $prod = $this->db->where('id', (int)$meta['product_id'])->get('products')->row();
        }

        // Map product_type to item_type enum
        $item_type = 'other';
        if ($prod) {
            $pt = $prod->product_type ?? '';
            if ($pt === 'electric_scooter') $item_type = 'scooter';
            elseif (in_array($pt, ['battery', 'charger', 'motor', 'controller', 'spare_part', 'accessory'], true)) {
                $item_type = $pt;
            }
        }

        foreach ($serials as $sn) {
            $sn_str = is_array($sn) ? ($sn['serial_no'] ?? '') : (string)$sn;
            $motor  = is_array($sn) ? ($sn['motor_no'] ?? null) : ($meta['motor_no'] ?? null);
            $clr    = is_array($sn) ? ($sn['colour'] ?? null) : ($meta['colour'] ?? ($prod->colour ?? null));

            $sn_clean = $this->normalize($sn_str);
            if ($sn_clean === '') continue;

            $chassis = ($item_type === 'scooter') ? $sn_clean : ($meta['chassis_no'] ?? null);

            $this->db->insert('item_serials', [
                'product_id'       => $meta['product_id'],
                'serial_no'        => $sn_clean,
                'item_type'        => $item_type,
                'chassis_no'       => $chassis,
                'motor_no'         => $motor ?: ($prod->motor_details ?? null),
                'colour'           => $clr,
                'battery_type'     => $prod->battery_type ?? null,
                'battery_capacity' => $prod->battery_capacity ?? null,
                'charger_model'    => $prod->charger_details ?? null,
                'purchase_id'      => $meta['purchase_id']      ?? null,
                'purchase_item_id' => $meta['purchase_item_id'] ?? null,
                'grn_id'           => $meta['grn_id']           ?? null,
                'grn_item_id'      => $meta['grn_item_id']      ?? null,
                'supplier_id'      => $meta['supplier_id']      ?? null,
                'purchase_date'    => $meta['purchase_date']    ?? null,
                'location_id'      => $meta['location_id']      ?? null,
                'status'           => self::AVAILABLE,
                'batch_no'         => $meta['batch_no']         ?? null,
                'mrp'              => $meta['mrp']              ?? ($prod->mrp ?? null),
                'purchase_rate'    => $meta['rate']             ?? ($prod->purchase_price ?? null),
                'created_at'       => $now,
                'created_by'       => $uid,
            ]);
            $serial_id = $this->db->insert_id();
            $ids[] = $serial_id;

            $this->log_movement($serial_id, [
                'txn_type'       => $meta['txn_type'] ?? 'PURCHASED',
                'reference_id'   => $meta['reference_id'] ?? null,
                'reference_no'   => $meta['reference_no'] ?? null,
                'to_location_id' => $meta['location_id'] ?? null,
                'supplier_id'    => $meta['supplier_id'] ?? null,
                'new_status'     => self::AVAILABLE,
                'remarks'        => $meta['remarks'] ?? null,
            ]);
        }
        return $ids;
    }

    /**
     * The single sanctioned way to change a serial's status/location. Writes the
     * history row itself so a caller cannot move a serial silently.
     */
    public function set_status($serial_id, $new_status, array $ctx = []) {
        $cur = $this->db->where('id', (int)$serial_id)->get('item_serials')->row();
        if (!$cur) return false;

        $update = ['status' => $new_status, 'updated_by' => $this->session->userdata('user_id')];
        if (array_key_exists('location_id', $ctx)) $update['location_id'] = $ctx['location_id'];
        $this->db->where('id', (int)$serial_id)->update('item_serials', $update);

        $this->log_movement($serial_id, [
            'txn_type'         => $ctx['txn_type'] ?? 'STATUS_CHANGE',
            'reference_id'     => $ctx['reference_id'] ?? null,
            'reference_no'     => $ctx['reference_no'] ?? null,
            'from_location_id' => $cur->location_id,
            'to_location_id'   => array_key_exists('location_id', $ctx) ? $ctx['location_id'] : $cur->location_id,
            'customer_id'      => $ctx['customer_id'] ?? null,
            'supplier_id'      => $ctx['supplier_id'] ?? null,
            'old_status'       => $cur->status,
            'new_status'       => $new_status,
            'remarks'          => $ctx['remarks'] ?? null,
        ]);
        return true;
    }

    /** Append-only history. Never updated, never deleted. */
    public function log_movement($serial_id, array $d) {
        $this->db->insert('serial_movements', [
            'serial_id'        => (int)$serial_id,
            'txn_type'         => $d['txn_type'],
            'reference_id'     => $d['reference_id']     ?? null,
            'reference_no'     => $d['reference_no']     ?? null,
            'from_location_id' => $d['from_location_id'] ?? null,
            'to_location_id'   => $d['to_location_id']   ?? null,
            'customer_id'      => $d['customer_id']      ?? null,
            'supplier_id'      => $d['supplier_id']      ?? null,
            'old_status'       => $d['old_status']       ?? null,
            'new_status'       => $d['new_status']       ?? null,
            'txn_date'         => date('Y-m-d H:i:s'),
            'user_id'          => $this->session->userdata('user_id'),
            'user_name'        => $this->session->userdata('name'),
            'ip_address'       => $this->input->ip_address(),
            'remarks'          => $d['remarks'] ?? null,
        ]);
    }

    public function get_history($serial_id) {
        return $this->db
            ->select('m.*, fl.name AS from_location, tl.name AS to_location,
                      c.name AS customer_name, s.name AS supplier_name')
            ->from('serial_movements m')
            ->join('locations fl', 'fl.id = m.from_location_id', 'left')
            ->join('locations tl', 'tl.id = m.to_location_id', 'left')
            ->join('customers c',  'c.id = m.customer_id', 'left')
            ->join('suppliers s',  's.id = m.supplier_id', 'left')
            ->where('m.serial_id', (int)$serial_id)
            ->order_by('m.txn_date', 'ASC')->order_by('m.id', 'ASC')
            ->get()->result();
    }

    /** Serials attached to a document, for reversal when it is deleted. */
    public function get_by_doc($doc_type, $doc_id) {
        return $this->db->select('s.*')
            ->from('serial_txn_links l')
            ->join('item_serials s', 's.id = l.serial_id')
            ->where('l.doc_type', $doc_type)->where('l.doc_id', (int)$doc_id)
            ->get()->result();
    }

    public function link_doc($serial_id, $doc_type, $doc_id, $doc_item_id = null) {
        $this->db->replace('serial_txn_links', [
            'serial_id'   => (int)$serial_id,
            'doc_type'    => $doc_type,
            'doc_id'      => (int)$doc_id,
            'doc_item_id' => $doc_item_id,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Serials + warranty for one invoice, keyed by invoice_item_id, so the
     * printed bill can double as the customer's warranty card.
     *
     * Falls back to keying on product_id for lines saved before
     * invoice_item_id was being recorded, so older bills still print their
     * serials instead of silently showing nothing.
     */
    public function get_for_invoice($invoice_id) {
        $out = ['by_item' => [], 'by_product' => []];
        if (!$this->schema_ready()) return $out;

        $rows = $this->db
            ->select('s.serial_no, s.product_id, w.invoice_item_id, w.warranty_days,
                      w.warranty_start_date, w.warranty_expiry_date')
            ->from('serial_txn_links l')
            ->join('item_serials s', 's.id = l.serial_id')
            ->join('serial_warranties w',
                   'w.serial_id = s.id AND w.invoice_id = ' . (int)$invoice_id, 'left')
            ->where('l.doc_type', 'invoice')
            ->where('l.doc_id', (int)$invoice_id)
            ->order_by('s.serial_no', 'ASC')
            ->get()->result();

        foreach ($rows as $r) {
            if ($r->invoice_item_id) $out['by_item'][(int)$r->invoice_item_id][] = $r;
            $out['by_product'][(int)$r->product_id][] = $r;
        }
        return $out;
    }

    /** Warranty rows for non-serialized warranty items on an invoice. */
    public function warranties_for_invoice($invoice_id) {
        $out = [];
        if (!$this->schema_ready()) return $out;
        $rows = $this->db->select('invoice_item_id, product_id, warranty_days, warranty_expiry_date')
            ->where('invoice_id', (int)$invoice_id)
            ->where('serial_id IS NULL', null, false)
            ->get('serial_warranties')->result();
        foreach ($rows as $r) $out[(int)$r->product_id] = $r;
        return $out;
    }

    // ── Warranty ────────────────────────────────────────────────────────────

    /**
     * Expiry is start + days - 1: a 1-day warranty bought today expires today,
     * so a 365-day warranty sold 11-Aug-2026 expires 10-Aug-2027, matching the
     * spec's worked example.
     */
    public function calc_expiry($start_date, $days) {
        $days = (int)$days;
        if ($days < 1) return null;
        return date('Y-m-d', strtotime($start_date . ' +' . ($days - 1) . ' day'));
    }

    public function warranty_status($expiry_date, $today = null) {
        if (!$expiry_date) return ['status' => 'NONE', 'days' => 0, 'text' => 'No warranty'];
        $today = $today ?: date('Y-m-d');
        $diff  = (int)floor((strtotime($expiry_date) - strtotime($today)) / 86400);
        if ($diff >= 0) {
            return ['status' => 'ACTIVE', 'days' => $diff,
                    'text' => 'Warranty Remaining: ' . $diff . ' Day' . ($diff === 1 ? '' : 's')];
        }
        return ['status' => 'EXPIRED', 'days' => abs($diff),
                'text' => 'Warranty Expired: ' . abs($diff) . ' Day' . ($diff === -1 ? '' : 's') . ' Ago'];
    }
}
