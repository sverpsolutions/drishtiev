<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_model extends CI_Model {

    public function get_all_brands() {
        return $this->db->order_by('name', 'ASC')->get('brands')->result();
    }

    public function get_all_categories() {
        return $this->db->order_by('display_order', 'ASC')->order_by('name', 'ASC')->get('categories')->result();
    }

}
