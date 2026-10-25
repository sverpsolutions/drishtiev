<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User_model
 * Handles all DB operations for users table
 */
class User_model extends CI_Model {

    private $table = 'users';

    public function __construct() {
        parent::__construct();
    }

    // -------------------------------------------------------
    // Get user by username (for login)
    // -------------------------------------------------------
    public function get_by_username($username) {
        return $this->db
            ->where('username', $username)
            ->get($this->table)
            ->row();
    }

    // -------------------------------------------------------
    // Get single user by ID
    // -------------------------------------------------------
    public function get_by_id($id) {
        return $this->db
            ->where('id', $id)
            ->get($this->table)
            ->row();
    }

    // -------------------------------------------------------
    // Get all users (for admin panel)
    // -------------------------------------------------------
    public function get_all() {
        return $this->db
            ->order_by('created_at', 'DESC')
            ->get($this->table)
            ->result();
    }

    // -------------------------------------------------------
    // Create new user
    // -------------------------------------------------------
    public function create($data) {
        $data['password']   = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    // -------------------------------------------------------
    // Update user
    // -------------------------------------------------------
    public function update($id, $data) {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    // -------------------------------------------------------
    // Update last login time
    // -------------------------------------------------------
    public function update_last_login($id) {
        return $this->db->where('id', $id)->update($this->table, [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }

    // -------------------------------------------------------
    // Check if username exists (for validation)
    // -------------------------------------------------------
    public function username_exists($username, $exclude_id = null) {
        $this->db->where('username', $username);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get($this->table)->num_rows() > 0;
    }

    // -------------------------------------------------------
    // Delete user
    // -------------------------------------------------------
    public function delete($id) {
        return $this->db->where('id', $id)->delete($this->table);
    }
}
