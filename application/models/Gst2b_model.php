<?php
// ============================================================
// FILE: application/models/Gst2b_model.php
// GSTR-2B reconciliation: parse portal JSON, store it, and match it
// against our own purchases to find vendors who billed us but did not
// file their return (or filed with mismatched amounts).
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Gst2b_model extends CI_Model {

    // Amount tolerance (₹) for treating a filed invoice as "matched" despite
    // small rounding differences between our books and the portal filing.
    const AMOUNT_TOLERANCE = 1.00;

    // Parse the raw GSTR-2B JSON (already json_decode'd as assoc array) into
    // a normalized shape: period, gstin, gendt, summary totals, and a flat
    // list of vendor invoice records (one row per invoice line).
    public function parse_json($json) {
        $data = $json['data'] ?? null;
        if (!$data || empty($data['docdata']['b2b']) || empty($data['gstin'])) {
            return null;
        }

        $rtnprd = $data['rtnprd'] ?? '';
        $period = (strlen($rtnprd) === 6)
            ? substr($rtnprd, 2, 4) . '-' . substr($rtnprd, 0, 2)
            : date('Y-m');

        $records = [];
        foreach ($data['docdata']['b2b'] as $vendor) {
            $ctin  = strtoupper(trim($vendor['ctin'] ?? ''));
            $trdnm = $vendor['trdnm'] ?? '';
            $supfildt = $this->_parse_date($vendor['supfildt'] ?? null);
            $supprd   = $vendor['supprd'] ?? '';
            $supprd   = (strlen($supprd) === 6) ? substr($supprd, 2, 4) . '-' . substr($supprd, 0, 2) : null;

            foreach (($vendor['inv'] ?? []) as $inv) {
                $records[] = [
                    'ctin'          => $ctin,
                    'trdnm'         => $trdnm,
                    'supfildt'      => $supfildt,
                    'supprd'        => $supprd,
                    'inum'          => trim($inv['inum'] ?? ''),
                    'inv_date'      => $this->_parse_date($inv['dt'] ?? null),
                    'taxable_value' => (float)($inv['txval'] ?? 0),
                    'cgst'          => (float)($inv['cgst'] ?? 0),
                    'sgst'          => (float)($inv['sgst'] ?? 0),
                    'igst'          => (float)($inv['igst'] ?? 0),
                    'cess'          => (float)($inv['cess'] ?? 0),
                    'invoice_value' => (float)($inv['val'] ?? 0),
                    'itcavl'        => $inv['itcavl'] ?? null,
                ];
            }
        }

        $itc = $data['itcsumm']['itcavl']['nonrevsup'] ?? [];

        return [
            'period'        => $period,
            'gstin'         => strtoupper(trim($data['gstin'])),
            'gendt'         => $this->_parse_date($data['gendt'] ?? null),
            'total_txval'   => (float)($itc['b2b']['txval'] ?? 0),
            'total_cgst'    => (float)($itc['cgst'] ?? 0),
            'total_sgst'    => (float)($itc['sgst'] ?? 0),
            'total_igst'    => (float)($itc['igst'] ?? 0),
            'records'       => $records,
        ];
    }

    private function _parse_date($d) {
        if (!$d) return null;
        $t = DateTime::createFromFormat('d-m-Y', $d);
        return $t ? $t->format('Y-m-d') : null;
    }

    // Store a parsed upload, replacing any existing upload for the same period.
    public function save_upload($parsed, $filename, $uploaded_by) {
        $this->db->trans_start();

        $this->db->where('period', $parsed['period'])->delete('gst2b_uploads');

        $this->db->insert('gst2b_uploads', [
            'period'        => $parsed['period'],
            'company_gstin' => $parsed['gstin'],
            'filename'      => $filename,
            'gendt'         => $parsed['gendt'],
            'total_txval'   => $parsed['total_txval'],
            'total_cgst'    => $parsed['total_cgst'],
            'total_sgst'    => $parsed['total_sgst'],
            'total_igst'    => $parsed['total_igst'],
            'uploaded_by'   => $uploaded_by,
            'uploaded_at'   => date('Y-m-d H:i:s'),
        ]);
        $upload_id = $this->db->insert_id();

        foreach ($parsed['records'] as $r) {
            $r['upload_id'] = $upload_id;
            $r['period']    = $parsed['period'];
            $this->db->insert('gst2b_records', $r);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $upload_id : false;
    }

    public function get_uploads() {
        return $this->db->select('gst2b_uploads.*, (SELECT COUNT(*) FROM gst2b_records WHERE gst2b_records.upload_id = gst2b_uploads.id) AS record_count')
            ->order_by('period', 'DESC')->get('gst2b_uploads')->result();
    }

    public function get_upload_by_period($period) {
        return $this->db->where('period', $period)->get('gst2b_uploads')->row();
    }

    // Normalize an invoice number for matching: uppercase, strip all whitespace.
    // Vendors format the same invoice number inconsistently across their own
    // filing vs. what gets typed into our purchase entry (spaces, dashes).
    private function _norm_inum($s) {
        return strtoupper(preg_replace('/\s+/', '', (string)$s));
    }

    // Core reconciliation: compare our purchases for the period against the
    // vendor-filed GSTR-2B records for that period.
    //
    // Returns:
    //   'rows'     => one row per purchase invoice, each tagged with a status
    //                 (matched / mismatch / not_filed), plus any 2B-only rows
    //                 (extra_in_2b) appended at the end
    //   'summary'  => counts + amount-at-risk
    //   'by_supplier' => issues grouped by supplier_id, for flagging
    public function reconcile($period, $location_id = null) {
        list($year, $month) = explode('-', $period);
        $from = "$year-$month-01";
        $to   = date('Y-m-t', strtotime($from));

        $this->db->select('purchases.id, purchases.invoice_no, purchases.invoice_date,
                purchases.taxable_amount, purchases.total_gst, purchases.total_amount,
                suppliers.id AS supplier_id, suppliers.name AS supplier_name, suppliers.gst_number')
            ->join('suppliers', 'suppliers.id = purchases.supplier_id')
            ->where('purchases.invoice_date >=', $from)
            ->where('purchases.invoice_date <=', $to)
            ->where('purchases.status !=', 'draft')
            ->where('suppliers.gst_number IS NOT NULL')
            ->where('suppliers.gst_number !=', '');
        if ($location_id) $this->db->where('purchases.location_id', $location_id);
        $purchases = $this->db->order_by('purchases.invoice_date', 'ASC')->get('purchases')->result();

        $records = $this->db->where('period', $period)->get('gst2b_records')->result();

        // Index 2B records by normalized ctin|inum for O(1) lookup.
        $index = [];
        foreach ($records as $r) {
            $key = strtoupper(trim($r->ctin)) . '|' . $this->_norm_inum($r->inum);
            $index[$key][] = $r;
        }
        $matched_record_ids = [];

        $rows = [];
        $by_supplier = [];
        $summary = ['matched' => 0, 'mismatch' => 0, 'not_filed' => 0, 'extra_in_2b' => 0, 'amount_at_risk' => 0];

        foreach ($purchases as $p) {
            $key = strtoupper(trim($p->gst_number)) . '|' . $this->_norm_inum($p->invoice_no);
            $candidates = $index[$key] ?? [];

            if (empty($candidates)) {
                $status = 'not_filed';
                $summary['not_filed']++;
                $summary['amount_at_risk'] += (float)$p->total_amount;
                $by_supplier[$p->supplier_id]['name'] = $p->supplier_name;
                $by_supplier[$p->supplier_id]['issues'][] = "Not filed: {$p->invoice_no} (₹" . number_format($p->total_amount, 2) . ')';
                $rows[] = $this->_row($p, null, $status);
                continue;
            }

            // Same key can appear more than once (rare, split filings) — use the
            // closest-amount candidate so a genuine match isn't masked by a
            // coincidental duplicate invoice number from the same vendor.
            $best = null; $best_diff = null;
            foreach ($candidates as $c) {
                $gst2b_total = (float)$c->cgst + (float)$c->sgst + (float)$c->igst;
                $diff = abs((float)$c->taxable_value - (float)$p->taxable_amount) + abs($gst2b_total - (float)$p->total_gst);
                if ($best === null || $diff < $best_diff) { $best = $c; $best_diff = $diff; }
            }

            $gst2b_total = (float)$best->cgst + (float)$best->sgst + (float)$best->igst;
            $taxable_ok  = abs((float)$best->taxable_value - (float)$p->taxable_amount) <= self::AMOUNT_TOLERANCE;
            $gst_ok      = abs($gst2b_total - (float)$p->total_gst) <= self::AMOUNT_TOLERANCE;

            if ($taxable_ok && $gst_ok) {
                $status = 'matched';
                $summary['matched']++;
            } else {
                $status = 'mismatch';
                $summary['mismatch']++;
                $summary['amount_at_risk'] += (float)$p->total_amount;
                $by_supplier[$p->supplier_id]['name'] = $p->supplier_name;
                $by_supplier[$p->supplier_id]['issues'][] = "Amount mismatch: {$p->invoice_no} (ours ₹" . number_format($p->total_amount, 2) . ' vs filed ₹' . number_format($best->taxable_value + $gst2b_total, 2) . ')';
            }

            $matched_record_ids[$best->id] = true;
            $rows[] = $this->_row($p, $best, $status);
        }

        // Vendor-filed invoices we have no matching purchase for at all —
        // informational (could be an unrecorded purchase, or a filing error
        // by the vendor), not something to hold payment over.
        foreach ($records as $r) {
            if (isset($matched_record_ids[$r->id])) continue;
            $summary['extra_in_2b']++;
            $rows[] = $this->_row(null, $r, 'extra_in_2b');
        }

        return ['rows' => $rows, 'summary' => $summary, 'by_supplier' => $by_supplier];
    }

    private function _row($p, $r, $status) {
        return [
            'status'          => $status,
            'supplier_id'     => $p->supplier_id ?? null,
            'supplier_name'   => $p->supplier_name ?? ($r->trdnm ?? ''),
            'gstin'           => $p->gst_number ?? ($r->ctin ?? ''),
            'our_invoice_no'  => $p->invoice_no ?? '',
            'our_date'        => $p->invoice_date ?? null,
            'our_taxable'     => $p->taxable_amount ?? null,
            'our_gst'         => $p->total_gst ?? null,
            'our_total'       => $p->total_amount ?? null,
            'filed_invoice_no'=> $r->inum ?? '',
            'filed_date'      => $r->inv_date ?? null,
            'filed_taxable'   => $r ? (float)$r->taxable_value : null,
            'filed_gst'       => $r ? ((float)$r->cgst + (float)$r->sgst + (float)$r->igst) : null,
            'supfildt'        => $r->supfildt ?? null,
        ];
    }

    // Flag suppliers with unresolved issues for this period — refreshes the
    // note/timestamp each run but does not touch suppliers already 'flagged'
    // status beyond updating their note (clearing is a manual action).
    public function flag_suppliers($period, $by_supplier) {
        foreach ($by_supplier as $supplier_id => $info) {
            $note = 'Period ' . $period . ': ' . implode('; ', $info['issues']);
            $existing = $this->db->where('id', $supplier_id)->get('suppliers')->row();
            $update = [
                'gst_compliance_status' => 'flagged',
                'gst_compliance_note'   => $note,
            ];
            if (!$existing || $existing->gst_compliance_status !== 'flagged') {
                $update['gst_flagged_at'] = date('Y-m-d H:i:s');
            }
            $this->db->where('id', $supplier_id)->update('suppliers', $update);
        }
    }

    public function clear_flag($supplier_id, $cleared_by) {
        $this->db->where('id', $supplier_id)->update('suppliers', [
            'gst_compliance_status' => 'ok',
            'gst_cleared_at'        => date('Y-m-d H:i:s'),
            'gst_cleared_by'        => $cleared_by,
        ]);
    }
}
