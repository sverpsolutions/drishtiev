<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Serial number AJAX endpoints. These back the scan-capture modal and the
 * billing scan box; both are latency-sensitive, so every query here is served
 * by an index on item_serials (see sql/43_serial_warranty.sql).
 */
class Serials extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Serial_model', 'Invoice_model']);
    }

    private function json($payload) {
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($payload));
    }

    /**
     * Live check while scanning INBOUND serials. The browser also tracks what
     * it has already scanned in this session; this call catches the case the
     * browser cannot see — the serial existing from an earlier document.
     */
    public function check_ajax() {
        if (!$this->Serial_model->schema_ready()) {
            $this->json(['ok' => false, 'error' => 'Serial tracking is not installed on this database.']);
            return;
        }

        $sn = $this->Serial_model->normalize($this->input->post('serial', TRUE));
        if ($sn === '') {
            $this->json(['ok' => false, 'error' => 'Blank serial number is not allowed.']);
            return;
        }

        $existing = $this->Serial_model->get_by_serial($sn);
        if ($existing) {
            $this->json(['ok' => false, 'serial' => $sn,
                         'error' => 'Serial Number ' . $sn . ' already exists.']);
            return;
        }

        $this->json(['ok' => true, 'serial' => $sn]);
    }

    /**
     * OUTBOUND lookup — billing, transfer, returns. Returns the full card the
     * spec asks for (item, dates, warranty, status) so the till can show it
     * before the line is added.
     */
    public function lookup_ajax() {
        if (!$this->Serial_model->schema_ready()) {
            $this->json(['ok' => false, 'error' => 'Serial tracking is not installed on this database.']);
            return;
        }

        $sn         = $this->input->post('serial', TRUE);
        $product_id = $this->input->post('product_id', TRUE) ?: null;
        $enforce_loc= $this->input->post('enforce_location', TRUE);
        $loc        = $enforce_loc ? ($this->active_location_id() ?: null) : null;

        $res = $this->Serial_model->validate_outbound($sn, $product_id, $loc);
        $row = $res['row'] ?? null;

        $payload = ['ok' => $res['ok']];
        if (!$res['ok']) $payload['error'] = $res['error'];

        if ($row) {
            $days   = (int)($row->warranty_days ?: 0);
            $expiry = $row->warranty_expiry_date ?: null;
            $wstat  = $this->Serial_model->warranty_status($expiry);
            $payload['serial'] = [
                'serial_id'     => (int)$row->id,
                'serial_no'     => $row->serial_no,
                'product_id'    => (int)$row->product_id,
                'product_name'  => $row->product_name,
                'item_code'     => $row->item_code,
                'status'        => $row->status,
                'location_id'   => $row->location_id,
                'location_name' => $row->location_name,
                'supplier_name' => $row->supplier_name,
                'purchase_date' => $row->purchase_date,
                'sale_date'     => $row->sale_date,
                'invoice_no'    => $row->invoice_no,
                'customer_name' => $row->customer_name,
                'warranty_days' => $days,
                'warranty_expiry' => $expiry,
                'warranty_status' => $wstat['status'],
                'warranty_text'   => $wstat['text'],
            ];
        }
        $this->json($payload);
    }

    /**
     * Disposition of a returned unit — the spec's "after return, provide
     * options" step. A customer return is deliberately parked in SALES_RETURN
     * until someone decides where it goes, so a faulty motor cannot drift back
     * onto the shelf just because a refund was processed.
     */
    public function dispose_ajax() {
        if (!$this->Serial_model->schema_ready()) {
            $this->json(['ok' => false, 'error' => 'Serial tracking is not installed.']);
            return;
        }
        // Scrapping writes off stock and re-stocking makes a returned unit
        // sellable again; both are judgement calls, not counter work.
        if (!$this->is_admin()) {
            $this->json(['ok' => false, 'error' => 'Manager or admin permission is required to dispose of a returned unit.']);
            return;
        }

        $serial_id = (int)$this->input->post('serial_id', TRUE);
        $action    = $this->input->post('action', TRUE);
        $remarks   = $this->input->post('remarks', TRUE);

        $map = [
            'stock'      => ['IN_STOCK',       'Returned to sellable stock'],
            'repair'     => ['UNDER_REPAIR',   'Sent for repair'],
            'inspection' => ['UNDER_WARRANTY', 'Held for warranty inspection'],
            'scrap'      => ['SCRAPPED',       'Scrapped'],
        ];
        if (!isset($map[$action])) { $this->json(['ok' => false, 'error' => 'Unknown action.']); return; }

        $cur = $this->db->where('id', $serial_id)->get('item_serials')->row();
        if (!$cur) { $this->json(['ok' => false, 'error' => 'Serial not found.']); return; }
        if (!in_array($cur->status, ['SALES_RETURN', 'UNDER_REPAIR', 'UNDER_WARRANTY'], true)) {
            $this->json(['ok' => false,
                'error' => 'Only a returned, under-inspection or under-repair unit can be dispositioned (this one is '
                         . $cur->status . ').']);
            return;
        }

        [$new_status, $label] = $map[$action];
        $ctx = [
            'txn_type' => $action === 'repair' ? 'SENT_TO_REPAIR'
                        : ($action === 'scrap' ? 'SCRAPPED' : 'STATUS_CHANGE'),
            'remarks'  => trim($label . ($remarks ? ' — ' . $remarks : '')),
        ];
        // Only a unit going back on the shelf needs a holding location.
        if ($action === 'stock') $ctx['location_id'] = $this->active_location_id() ?: $cur->location_id;

        $this->Serial_model->set_status($serial_id, $new_status, $ctx);
        $this->log_activity('serials', 'dispose',
            $label . ' — serial ' . $cur->serial_no . ' (' . $cur->status . ' → ' . $new_status . ')',
            $serial_id, $cur->serial_no);

        $this->json(['ok' => true, 'status' => $new_status, 'message' => $label . '.']);
    }

    /**
     * Serials currently available for a product at the active location —
     * feeds the "pick which units" list on transfers and outbound returns.
     * Capped, because a fast-moving part can have thousands in stock and the
     * spec explicitly forbids dumping them all into the browser.
     */
    public function available_ajax() {
        if (!$this->Serial_model->schema_ready()) { $this->json(['ok' => false, 'rows' => []]); return; }

        $product_id = (int)$this->input->post('product_id', TRUE);
        $term       = $this->Serial_model->normalize($this->input->post('q', TRUE));
        $loc        = $this->active_location_id() ?: null;

        $this->db->select('id, serial_no, location_id')
                 ->from('item_serials')
                 ->where('product_id', $product_id)
                 ->where('status', Serial_model::AVAILABLE);
        if ($loc) $this->db->where('location_id', $loc);
        if ($term !== '') $this->db->like('serial_no', $term, 'after');

        $rows = $this->db->order_by('serial_no', 'ASC')->limit(50)->get()->result();
        $this->json(['ok' => true, 'rows' => $rows]);
    }

    /**
     * AJAX endpoint for EV Billing Modal:
     * Returns available Chassis/VINs for a scooter model, available batteries, and available chargers
     * at the current outlet/location.
     */
    public function ev_available_serials_ajax() {
        if (!$this->Serial_model->schema_ready()) {
            $this->json(['ok' => false, 'error' => 'Serial tracking not installed']);
            return;
        }

        $product_id = (int)$this->input->post('product_id', TRUE);
        $loc        = $this->active_location_id() ?: null;
        $colour     = trim((string)$this->input->post('colour', TRUE));

        // Available Chassis / VIN numbers for this scooter
        $this->db->select('s.id, s.serial_no, s.chassis_no, s.motor_no, s.colour, p.name AS product_name, p.model_name, p.model_number')
                 ->from('item_serials s')
                 ->join('products p', 'p.id = s.product_id', 'left')
                 ->where('s.status', Serial_model::AVAILABLE);
        if ($product_id > 0) {
            $this->db->where('s.product_id', $product_id);
        } else {
            $this->db->group_start()
                     ->where('s.item_type', 'scooter')
                     ->or_where('p.product_type', 'electric_scooter')
                     ->group_end();
        }
        if ($loc) $this->db->where('s.location_id', $loc);
        if ($colour !== '') $this->db->where('s.colour', $colour);
        $scooters = $this->db->order_by('s.serial_no', 'ASC')->limit(100)->get()->result();

        // Available Batteries at this location
        $this->db->select('s.id, s.serial_no, s.battery_type, s.battery_capacity, p.name AS product_name')
                 ->from('item_serials s')
                 ->join('products p', 'p.id = s.product_id', 'left')
                 ->where('s.status', Serial_model::AVAILABLE)
                 ->group_start()
                     ->where('s.item_type', 'battery')
                     ->or_where('p.product_type', 'battery')
                 ->group_end();
        if ($loc) $this->db->where('s.location_id', $loc);
        $batteries = $this->db->order_by('s.serial_no', 'ASC')->limit(100)->get()->result();

        // Available Chargers at this location
        $this->db->select('s.id, s.serial_no, s.charger_model, p.name AS product_name')
                 ->from('item_serials s')
                 ->join('products p', 'p.id = s.product_id', 'left')
                 ->where('s.status', Serial_model::AVAILABLE)
                 ->group_start()
                     ->where('s.item_type', 'charger')
                     ->or_where('p.product_type', 'charger')
                 ->group_end();
        if ($loc) $this->db->where('s.location_id', $loc);
        $chargers = $this->db->order_by('s.serial_no', 'ASC')->limit(100)->get()->result();

        // Distinct available colours for this scooter model
        $colours = [];
        if ($product_id > 0) {
            $c_q = $this->db->distinct()->select('colour')->from('item_serials')
                            ->where('product_id', $product_id)
                            ->where('status', Serial_model::AVAILABLE);
            if ($loc) $c_q->where('location_id', $loc);
            $c_rows = $c_q->get()->result();
            foreach ($c_rows as $cr) {
                if (!empty($cr->colour)) $colours[] = $cr->colour;
            }
        }

        $this->json([
            'ok'        => true,
            'scooters'  => $scooters,
            'batteries' => $batteries,
            'chargers'  => $chargers,
            'colours'   => array_values(array_unique($colours)),
        ]);
    }
}
