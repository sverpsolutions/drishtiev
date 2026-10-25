<?php
// ============================================================
// FILE: application/models/Hsn_model.php
// Manages HSN Master — codes, descriptions, and GST rates.
// Rule: CGST = IGST/2, SGST = IGST/2 (always equal halves).
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Hsn_model extends CI_Model {

    private $table = 'hsn_master';

    // All HSN codes for listing
    public function get_all() {
        return $this->db->order_by('hsn_code', 'ASC')->get($this->table)->result();
    }

    // For dropdown in products form
    public function get_for_select() {
        $rows = $this->db->select('id, hsn_code, description, igst_percent')
            ->order_by('hsn_code', 'ASC')
            ->get($this->table)->result();
        $out = ['' => '-- Select HSN --'];
        foreach ($rows as $r) {
            $label = $r->hsn_code;
            if (!empty($r->description)) $label .= ' — ' . $r->description;
            $label .= ' (' . $r->igst_percent . '%)';
            $out[$r->id] = $label;
        }
        return $out;
    }

    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row();
    }

    public function get_by_code($code) {
        return $this->db->where('hsn_code', $code)->get($this->table)->row();
    }

    // Returns rates as object: igst_percent, cgst_percent, sgst_percent
    public function get_rates($hsn_id) {
        if (!$hsn_id) return null;
        return $this->db
            ->select('igst_percent, cgst_percent, sgst_percent, hsn_code')
            ->where('id', $hsn_id)
            ->get($this->table)
            ->row();
    }

    public function create($data) {
        $data = $this->_sanitise($data);
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $data = $this->_sanitise($data);
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id) {
        // Nullify product links before deleting
        $this->db->where('hsn_id', $id)->update('products', ['hsn_id' => null]);
        return $this->db->where('id', $id)->delete($this->table);
    }

    public function hsn_code_exists($code, $exclude_id = null) {
        $this->db->where('hsn_code', $code);
        if ($exclude_id) $this->db->where('id !=', $exclude_id);
        return $this->db->count_all_results($this->table) > 0;
    }

    // Auto-split IGST into CGST/SGST on save
    private function _sanitise($data) {
        $igst = (float)($data['igst_percent'] ?? 0);
        $data['igst_percent'] = $igst;
        $data['cgst_percent'] = round($igst / 2, 2);
        $data['sgst_percent'] = round($igst / 2, 2);
        return $data;
    }
}
