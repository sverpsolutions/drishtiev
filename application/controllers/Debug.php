<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Debug extends CI_Controller {
    public function index() {
        echo "purchase_items:\n";
        print_r($this->db->list_fields('purchase_items'));
        echo "\npurchases:\n";
        print_r($this->db->list_fields('purchases'));
    }
}
