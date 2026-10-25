<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Warranty dashboard, serial search, claims and reports.
 *
 * Sensitive steps are gated: approving/rejecting a claim, issuing a replacement
 * and overriding a warranty expiry all require admin. Everything else is open
 * to any user who has the module.
 */
class Warranty extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Warranty_model', 'Serial_model', 'Invoice_model']);

        if (!$this->Warranty_model->schema_ready()) {
            $this->session->set_flashdata('error',
                'Warranty tables are not installed on this database. Run sql/43_serial_warranty.sql first.');
            redirect('dashboard');
        }
        if (!$this->can_module('mod_warranty')) {
            $this->session->set_flashdata('error', 'You do not have access to the Warranty module.');
            redirect('dashboard');
        }
    }

    private function json($p) {
        $this->output->set_content_type('application/json')->set_output(json_encode($p));
    }

    // ── Dashboard ───────────────────────────────────────────────────────────
    public function index() {
        $this->render('warranty/dashboard', [
            'page_title' => 'Warranty Dashboard',
            'c'          => $this->Warranty_model->dashboard_counts(),
        ]);
    }

    // ── Serial / warranty search ────────────────────────────────────────────
    public function search() {
        $q = trim((string)$this->input->get('q', TRUE));
        $data = [
            'page_title' => 'Warranty / Serial Number Search',
            'q'          => $q,
            'detail'     => null,
            'history'    => [],
            'rows'       => [],
        ];

        if ($q !== '') {
            // An exact serial gets the full history card; anything else is
            // treated as a customer/invoice/phone/item search.
            $detail = $this->Serial_model->get_detail($q);
            if ($detail) {
                $data['detail']  = $detail;
                $data['history'] = $this->Serial_model->get_history($detail->id);
                $data['warranty'] = $this->Serial_model->warranty_status($detail->warranty_expiry_date);
                $data['claims']  = $this->Warranty_model->get_claims(['q' => $detail->serial_no]);
            } else {
                $data['rows'] = $this->Warranty_model->report('serial_sales', ['limit' => 200]);
                $needle = strtolower($q);
                $data['rows'] = array_values(array_filter($data['rows'], function ($r) use ($needle) {
                    foreach (['serial_no','chassis_no','motor_no','linked_chassis_no','product_name','model_name','model_number','invoice_no','customer_name','phone'] as $f) {
                        if (isset($r->$f) && strpos(strtolower((string)$r->$f), $needle) !== false) return true;
                    }
                    return false;
                }));
            }
        }
        $this->render('warranty/search', $data);
    }

    // ── Claims ──────────────────────────────────────────────────────────────
    public function claims() {
        $f = [
            'status' => $this->input->get('status', TRUE),
            'q'      => $this->input->get('q', TRUE),
            'from'   => $this->input->get('from', TRUE),
            'to'     => $this->input->get('to', TRUE),
        ];
        $this->render('warranty/claims', [
            'page_title' => 'Warranty Claims',
            'claims'     => $this->Warranty_model->get_claims($f),
            'f'          => $f,
        ]);
    }

    public function claim_create() {
        if ($this->input->method() !== 'post') {
            $this->render('warranty/claim_form', [
                'page_title' => 'New Warranty Claim',
                'prefill'    => trim((string)$this->input->get('serial', TRUE)),
            ]);
            return;
        }

        $sn  = $this->Serial_model->normalize($this->input->post('serial_no', TRUE));
        $row = $sn !== '' ? $this->Serial_model->get_detail($sn) : null;
        if (!$row) {
            $this->session->set_flashdata('error', 'Serial Number ' . $sn . ' does not exist.');
            redirect('warranty/claim_create');
        }

        // A claim is allowed on an expired warranty — it is simply recorded as
        // out of warranty, which the inspection step needs to see.
        $w = $this->Serial_model->warranty_status($row->warranty_expiry_date);

        $warranty_row = $this->db->select('id')->where('serial_id', $row->id)
                                 ->where('is_returned', 0)->order_by('id', 'DESC')
                                 ->get('serial_warranties')->row();

        $id = $this->Warranty_model->create_claim([
            'serial_id'          => $row->id,
            'product_id'         => $row->product_id,
            'customer_id'        => $row->customer_id ?: null,
            'invoice_id'         => $row->invoice_id ?: null,
            'warranty_id'        => $warranty_row->id ?? null,
            'claim_date'         => $this->input->post('claim_date', TRUE) ?: date('Y-m-d'),
            'warranty_active_at_claim' => $w['status'] === 'ACTIVE' ? 1 : 0,
            'customer_complaint' => $this->input->post('customer_complaint', TRUE),
            'problem_description'=> $this->input->post('problem_description', TRUE),
            'status'             => 'RECEIVED',
            'location_id'        => $this->active_location_id() ?: null,
            'remarks'            => $this->input->post('remarks', TRUE),
        ]);

        // The unit is physically with us now, so it must not be sellable.
        $this->Serial_model->set_status($row->id, 'UNDER_WARRANTY', [
            'txn_type'     => 'WARRANTY_CLAIM',
            'reference_id' => $id,
            'reference_no' => 'claim',
            'customer_id'  => $row->customer_id ?: null,
            'location_id'  => $this->active_location_id() ?: null,
            'remarks'      => 'Received for warranty claim',
        ]);

        $this->log_activity('warranty', 'claim_create',
            'Warranty claim opened for serial ' . $row->serial_no
            . ' (' . $w['status'] . ')', $id, $row->serial_no);

        $this->session->set_flashdata('success', 'Warranty claim created.');
        redirect('warranty/claim/' . $id);
    }

    public function claim($id = null) {
        $claim = $id ? $this->Warranty_model->get_claim($id) : null;
        if (!$claim) show_404();

        $w = $claim->warranty_expiry_date
            ? $this->Serial_model->warranty_status($claim->warranty_expiry_date)
            : ['status' => 'NONE', 'text' => 'No warranty on record'];

        $this->render('warranty/claim_view', [
            'page_title' => 'Warranty Claim ' . $claim->claim_no,
            'claim'      => $claim,
            'log'        => $this->Warranty_model->get_claim_log($id),
            'history'    => $claim->serial_id ? $this->Serial_model->get_history($claim->serial_id) : [],
            'warranty'   => $w,
            'next'       => $this->Warranty_model->next_statuses($claim->status),
        ]);
    }

    /** Move a claim along its workflow. */
    public function claim_status($id = null) {
        $claim = $id ? $this->Warranty_model->get_claim($id) : null;
        if (!$claim) show_404();

        $new = $this->input->post('status', TRUE);
        if (!in_array($new, $this->Warranty_model->next_statuses($claim->status), true)) {
            $this->session->set_flashdata('error',
                'Cannot move a claim from ' . $claim->status . ' to ' . $new . '.');
            redirect('warranty/claim/' . $id);
        }

        // Approve / reject decides whether the company pays — manager and above.
        if (in_array($new, ['APPROVED', 'REJECTED'], true) && !$this->is_admin()) {
            $this->session->set_flashdata('error', 'Manager or admin permission is required to approve or reject a claim.');
            redirect('warranty/claim/' . $id);
        }

        $extra = [];
        if ($new === 'REJECTED') $extra['rejection_reason'] = $this->input->post('remarks', TRUE);
        if ($new === 'READY' || $new === 'DELIVERED') $extra['resolution'] = $this->input->post('remarks', TRUE);
        if ($this->input->post('assigned_to', TRUE)) $extra['assigned_to'] = (int)$this->input->post('assigned_to', TRUE);

        $this->Warranty_model->set_claim_status($id, $new, $this->input->post('remarks', TRUE), $extra);

        // Keep the physical unit's status honest as the claim moves.
        if ($claim->serial_id) {
            $map = ['REPAIR' => ['UNDER_REPAIR', 'SENT_TO_REPAIR'], 'REJECTED' => ['SALES_RETURN', 'STATUS_CHANGE']];
            if (isset($map[$new])) {
                $this->Serial_model->set_status($claim->serial_id, $map[$new][0], [
                    'txn_type'     => $map[$new][1],
                    'reference_id' => $id,
                    'reference_no' => $claim->claim_no,
                    'remarks'      => 'Claim ' . $claim->claim_no . ' → ' . $new,
                ]);
            }
        }

        $this->log_activity('warranty', 'claim_status',
            'Claim ' . $claim->claim_no . ': ' . $claim->status . ' → ' . $new, $id, $claim->claim_no);
        $this->session->set_flashdata('success', 'Claim moved to ' . $new . '.');
        redirect('warranty/claim/' . $id);
    }

    /**
     * Issue a replacement unit. The original serial is kept and marked REPLACED
     * — the spec is explicit that its history must not be lost — and the new
     * unit is linked to the claim so the chain is traceable both ways.
     */
    public function claim_replace($id = null) {
        $claim = $id ? $this->Warranty_model->get_claim($id) : null;
        if (!$claim) show_404();

        if (!$this->is_admin()) {
            $this->session->set_flashdata('error', 'Manager or admin permission is required to issue a replacement.');
            redirect('warranty/claim/' . $id);
        }
        if (!in_array($claim->status, ['APPROVED', 'REPLACEMENT'], true)) {
            $this->session->set_flashdata('error', 'A replacement can only be issued on an approved claim.');
            redirect('warranty/claim/' . $id);
        }

        $sn  = $this->Serial_model->normalize($this->input->post('replacement_serial', TRUE));
        $chk = $this->Serial_model->validate_outbound($sn, $claim->product_id, $this->active_location_id() ?: null);
        if (!$chk['ok']) {
            $this->session->set_flashdata('error', $chk['error']);
            redirect('warranty/claim/' . $id);
        }
        $new_serial = $chk['row'];

        $this->db->trans_start();

        // Replacement leaves stock against the claim, not a sale.
        $this->Serial_model->set_status($new_serial->id, 'SOLD', [
            'txn_type'     => 'REPLACED',
            'reference_id' => $id,
            'reference_no' => $claim->claim_no,
            'customer_id'  => $claim->customer_id ?: null,
            'location_id'  => null,
            'remarks'      => 'Issued as replacement for ' . ($claim->serial_no ?? '') . ' on ' . $claim->claim_no,
        ]);

        if ($claim->serial_id) {
            $this->Serial_model->set_status($claim->serial_id, 'REPLACED', [
                'txn_type'     => 'REPLACED',
                'reference_id' => $id,
                'reference_no' => $claim->claim_no,
                'location_id'  => null,
                'remarks'      => 'Replaced by ' . $new_serial->serial_no . ' under ' . $claim->claim_no,
            ]);
        }

        // The replacement carries the ORIGINAL warranty period — a replacement
        // does not restart the clock unless the business says so.
        if ($claim->warranty_id) {
            $ow = $this->db->where('id', $claim->warranty_id)->get('serial_warranties')->row();
            if ($ow) {
                $this->db->insert('serial_warranties', [
                    'serial_id'            => $new_serial->id,
                    'product_id'           => $claim->product_id,
                    'invoice_id'           => $ow->invoice_id,
                    'invoice_item_id'      => $ow->invoice_item_id,
                    'customer_id'          => $ow->customer_id,
                    'sale_date'            => $ow->sale_date,
                    'warranty_start_date'  => $ow->warranty_start_date,
                    'warranty_days'        => $ow->warranty_days,
                    'warranty_expiry_date' => $ow->warranty_expiry_date,
                    'created_by'           => $this->session->userdata('user_id'),
                ]);
                $this->db->where('id', $claim->warranty_id)->update('serial_warranties', ['is_returned' => 1]);
            }
        }

        $this->Warranty_model->set_claim_status($id, 'REPLACEMENT',
            'Replacement issued: ' . $new_serial->serial_no,
            ['replacement_serial_id' => $new_serial->id, 'resolution' => 'Replaced with ' . $new_serial->serial_no]);

        $this->db->trans_complete();

        $this->log_activity('warranty', 'claim_replace',
            'Claim ' . $claim->claim_no . ': ' . ($claim->serial_no ?? '?')
            . ' replaced by ' . $new_serial->serial_no, $id, $claim->claim_no);

        $this->session->set_flashdata('success', 'Replacement ' . $new_serial->serial_no . ' issued.');
        redirect('warranty/claim/' . $id);
    }

    // ── Reports ─────────────────────────────────────────────────────────────
    public function reports($type = null) {
        $meta = $type ? $this->Warranty_model->report_meta($type) : null;
        $f = [
            'from'        => $this->input->get('from', TRUE),
            'to'          => $this->input->get('to', TRUE),
            'days'        => $this->input->get('days', TRUE),
            'q'           => $this->input->get('q', TRUE),
            'location_id' => $this->input->get('location_id', TRUE),
            'serial'      => $this->input->get('serial', TRUE),
            'limit'       => 1000,
        ];

        $this->render('warranty/reports', [
            'page_title' => $meta ? $meta[0] : 'Serial & Warranty Reports',
            'type'       => $type,
            'meta'       => $meta,
            'rows'       => $meta ? $this->Warranty_model->report($type, $f) : [],
            'groups'     => $this->Warranty_model->report_types(),
            'all_meta'   => array_map(function ($t) { return $this->Warranty_model->report_meta($t); },
                                array_merge(...array_values($this->Warranty_model->report_types()))),
            'f'          => $f,
        ]);
    }

    /** Same data as the on-screen report, as a .csv the office can keep. */
    public function report_export($type = null) {
        $meta = $type ? $this->Warranty_model->report_meta($type) : null;
        if (!$meta) show_404();

        $rows = $this->Warranty_model->report($type, [
            'from' => $this->input->get('from', TRUE), 'to' => $this->input->get('to', TRUE),
            'days' => $this->input->get('days', TRUE), 'q' => $this->input->get('q', TRUE),
            'limit' => 20000,
        ]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $type . '_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array_values($meta[1]));
        foreach ($rows as $r) {
            $line = [];
            foreach (array_keys($meta[1]) as $k) $line[] = $r->$k ?? '';
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }
}
