<?php
// ============================================================
// FILE: application/models/Army_terms_model.php
// Army Quotation Management — reusable Terms & Conditions text.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Army_terms_model extends CI_Model {

    // Company-specific default if one exists, else the global default (company_id IS NULL).
    public function get_default($company_id = null) {
        if ($company_id) {
            $row = $this->db->where('company_id', $company_id)->where('is_default', 1)->get('Army_TermsConditions')->row();
            if ($row) return $row->terms_text;
        }
        $row = $this->db->where('company_id', null)->where('is_default', 1)->get('Army_TermsConditions')->row();
        return $row ? $row->terms_text : '';
    }

    public function get_all() {
        return $this->db->order_by('id', 'DESC')->get('Army_TermsConditions')->result();
    }

    public function save_default($terms_text, $company_id = null) {
        $existing = $this->db->where('company_id', $company_id)->where('is_default', 1)->get('Army_TermsConditions')->row();
        if ($existing) {
            return $this->db->where('id', $existing->id)->update('Army_TermsConditions', ['terms_text' => $terms_text]);
        }
        return $this->db->insert('Army_TermsConditions', [
            'company_id' => $company_id,
            'terms_text' => $terms_text,
            'is_default' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
