<?php class Debug_check2 extends CI_Controller { public function index() { $this->load->database(); $row = $this->db->get('company_settings')->row_array(); print_r($row); } }
