<?php
// ============================================================
// FILE: application/libraries/DocumentNumbering.php
// Location Code + Financial Year document numbering.
// Format: {LocationCode}/{FY}/{Serial}  e.g.  WH/25-26/000001
//
// One independent, atomic serial counter per (location, financial year,
// document type) combination — see sql/38_financial_year_numbering.sql.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class DocumentNumbering {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    // Indian financial year: Apr 1 -> Mar 31, labeled "25-26" etc.
    // Auto-creates the financial_years row (status 'open') the first time a given
    // label is seen, so nobody has to pre-configure years before billing works.
    // Throws if the resolved year has been explicitly closed — this is the single
    // enforcement point "no posting into a closed financial year" relies on; every
    // caller of generate() inherits it automatically.
    public function resolve_financial_year($date = null) {
        $ts = strtotime($date ?: date('Y-m-d'));
        $y  = (int)date('Y', $ts);
        $m  = (int)date('n', $ts);
        if ($m >= 4) {
            $start_year = $y;
            $end_year   = $y + 1;
        } else {
            $start_year = $y - 1;
            $end_year   = $y;
        }
        $label = substr((string)$start_year, 2, 2) . '-' . substr((string)$end_year, 2, 2);

        $row = $this->CI->db->where('label', $label)->get('financial_years')->row();
        if (!$row) {
            // This resolve() runs on every page load (header.php calls it to show the FY
            // badge), so two requests can plausibly race here on the very first hit of a
            // new year. INSERT IGNORE makes that race harmless — whichever request wins
            // creates the row, the other's insert is a silent no-op instead of a fatal
            // duplicate-key error against the UNIQUE(label) constraint.
            $this->CI->db->query(
                "INSERT IGNORE INTO financial_years (label, start_date, end_date, status) VALUES (?, ?, ?, 'open')",
                [$label, $start_year . '-04-01', $end_year . '-03-31']
            );
            return $label;
        }
        if ($row->status === 'closed') {
            throw new Exception("Financial Year {$label} is closed — cannot post new documents into it.");
        }
        return $label;
    }

    // $document_type is a short internal key, e.g. 'invoice', 'estimate', 'purchase_order'.
    public function generate($document_type, $location_id, $date = null) {
        $date = $date ?: date('Y-m-d');
        $fy   = $this->resolve_financial_year($date);

        $loc  = $this->CI->db->select('code')->where('id', (int)$location_id)->get('locations')->row();
        $code = $loc ? $loc->code : 'GEN';

        // Atomic increment: race-safe under concurrent requests with a single statement,
        // no app-level transaction/locking needed. LAST_INSERT_ID(1) on the fresh-insert
        // branch is required, not cosmetic — without it, insert_id() below would return
        // this table's own unrelated auto_increment `id` on a brand-new combo instead of
        // the serial, since ON DUPLICATE KEY UPDATE's LAST_INSERT_ID() override only fires
        // on the UPDATE branch.
        $this->CI->db->query(
            "INSERT INTO document_sequences (location_id, financial_year, document_type, last_serial)
             VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE last_serial = LAST_INSERT_ID(last_serial + 1)",
            [(int)$location_id, $fy, $document_type]
        );
        // affected_rows: 1 = fresh row (serial 1), 2 = incremented. On a fresh insert
        // insert_id() is the table's own auto-increment id, not the serial — that was
        // giving the first document of a new combo a number like 000047.
        $serial = $this->CI->db->affected_rows() === 1 ? 1 : (int)$this->CI->db->insert_id();

        $number = $code . '/' . $fy . '/' . str_pad($serial, 6, '0', STR_PAD_LEFT);

        return [
            'number'         => $number,
            'financial_year' => $fy,
            'serial'         => $serial,
            'location_code'  => $code,
        ];
    }
}
