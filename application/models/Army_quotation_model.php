<?php
// ============================================================
// FILE: application/models/Army_quotation_model.php
// Army Quotation Management — Quotation header + line items.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Army_quotation_model extends CI_Model {

    // Atomic sequence: INSERT..ON DUPLICATE KEY UPDATE avoids the
    // SELECT-then-UPDATE race condition the existing invoice numbering has.
    // LAST_INSERT_ID(1) must also wrap the VALUES-clause insert (not just the
    // UPDATE clause) — otherwise the very first row for a new year never runs
    // the UPDATE branch at all, LAST_INSERT_ID() stays unset, and insert_id()
    // returns 0 instead of 1.
    public function generate_quotation_no() {
        $year = (int)date('Y');
        $this->db->query(
            "INSERT INTO Army_QuotationSequence (seq_year, last_seq) VALUES (?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_seq = LAST_INSERT_ID(last_seq + 1)",
            [$year]
        );
        $seq = (int)$this->db->insert_id();
        return 'AQ-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function create($header, $items) {
        $this->db->trans_start();

        $header['created_at'] = date('Y-m-d H:i:s');
        $header['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('Army_Quotation', $header);
        $quotation_id = $this->db->insert_id();

        $this->_insert_items($quotation_id, $items);

        $this->db->trans_complete();
        return $this->db->trans_status() ? $quotation_id : false;
    }

    public function update($id, $header, $items) {
        $this->db->trans_start();

        $header['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update('Army_Quotation', $header);

        $this->db->where('quotation_id', $id)->delete('Army_QuotationDetails');
        $this->_insert_items($id, $items);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    private function _insert_items($quotation_id, $items) {
        $sr = 1;
        foreach ($items as $item) {
            $this->db->insert('Army_QuotationDetails', [
                'quotation_id' => $quotation_id,
                'sr_no'        => $sr++,
                'particulars'  => $item['particulars'],
                'qty'          => $item['qty'],
                'unit'         => $item['unit'] ?? 'Nos',
                'rate'         => $item['rate'],
                'amount'       => $item['amount'],
            ]);
        }
    }

    public function get_by_id($id) {
        return $this->db
            ->select('Army_Quotation.*, Army_CompanyMaster.company_name, Army_CompanyMaster.logo_path,
                      Army_CompanyMaster.address AS company_address, Army_CompanyMaster.gst_number AS company_gst_number,
                      Army_CompanyMaster.pan_number AS company_pan_number, Army_CompanyMaster.phone AS company_phone,
                      Army_CompanyMaster.mobile AS company_mobile, Army_CompanyMaster.email AS company_email,
                      Army_CompanyMaster.website AS company_website, Army_CompanyMaster.bank_name, Army_CompanyMaster.account_number,
                      Army_CompanyMaster.ifsc_code, Army_CompanyMaster.branch, Army_CompanyMaster.signatory_name,
                      Army_CompanyMaster.signature_path, Army_CompanyMaster.stamp_path')
            ->join('Army_CompanyMaster', 'Army_CompanyMaster.id = Army_Quotation.company_id')
            ->where('Army_Quotation.id', $id)
            ->get('Army_Quotation')
            ->row();
    }

    public function get_items($quotation_id) {
        return $this->db->where('quotation_id', $quotation_id)->order_by('sr_no', 'ASC')->get('Army_QuotationDetails')->result();
    }

    public function search($filters) {
        $this->db->select('Army_Quotation.*, Army_CompanyMaster.company_name')
            ->join('Army_CompanyMaster', 'Army_CompanyMaster.id = Army_Quotation.company_id');

        if (!empty($filters['quotation_no'])) $this->db->like('Army_Quotation.quotation_no', $filters['quotation_no']);
        if (!empty($filters['customer_name'])) $this->db->like('Army_Quotation.customer_name', $filters['customer_name']);
        if (!empty($filters['company_id'])) $this->db->where('Army_Quotation.company_id', (int)$filters['company_id']);
        if (!empty($filters['mobile'])) $this->db->like('Army_Quotation.mobile', $filters['mobile']);
        if (!empty($filters['status'])) $this->db->where('Army_Quotation.status', $filters['status']);
        if (!empty($filters['from'])) $this->db->where('Army_Quotation.quotation_date >=', $filters['from']);
        if (!empty($filters['to'])) $this->db->where('Army_Quotation.quotation_date <=', $filters['to']);

        return $this->db->order_by('Army_Quotation.id', 'DESC')->get('Army_Quotation')->result();
    }

    public function duplicate($id) {
        $original = $this->get_by_id($id);
        if (!$original) return false;
        $items = $this->get_items($id);

        $header = [
            'quotation_no'   => $this->generate_quotation_no(),
            'quotation_date' => date('Y-m-d'),
            'valid_till'     => $original->valid_till,
            'company_id'     => $original->company_id,
            'customer_name'  => $original->customer_name,
            'contact_person' => $original->contact_person,
            'address'        => $original->address,
            'mobile'         => $original->mobile,
            'email'          => $original->email,
            'subject'        => $original->subject,
            'reference'      => $original->reference,
            'prepared_by'    => $original->prepared_by,
            'sub_total'      => $original->sub_total,
            'gst_percent'    => $original->gst_percent,
            'gst_amount'     => $original->gst_amount,
            'grand_total'    => $original->grand_total,
            'terms_text'     => $original->terms_text,
            'status'         => 'draft',
            'created_by'     => $original->created_by,
        ];
        $item_rows = [];
        foreach ($items as $it) {
            $item_rows[] = ['particulars' => $it->particulars, 'qty' => $it->qty, 'unit' => $it->unit, 'rate' => $it->rate, 'amount' => $it->amount];
        }
        return $this->create($header, $item_rows);
    }

    public function delete($id) {
        return $this->db->where('id', $id)->delete('Army_Quotation'); // Army_QuotationDetails/EmailHistory cascade via FK
    }

    public function set_status($id, $status) {
        return $this->db->where('id', $id)->update('Army_Quotation', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
