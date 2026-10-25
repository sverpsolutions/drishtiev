<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Masters extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Master_model');
        $this->load->library('form_validation');
    }

    public function brands() {
        $data['page_title'] = 'Brands Master';
        $data['brands'] = $this->Master_model->get_all_brands();

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $name = $this->input->post('name', TRUE);
            if (!empty($name)) {
                $image = $this->_handle_brand_image_upload('image');
                $this->db->insert('brands', ['name' => $name, 'image' => $image, 'status' => 1]);
                $this->session->set_flashdata('success', 'Brand added.');
            }
            redirect('masters/brands');
        }

        $this->render('masters/brands', $data);
    }

    public function edit_brand($id = null) {
        if (!$id) redirect('masters/brands');

        $brand = $this->db->where('id', $id)->get('brands')->row();
        if (!$brand) {
            $this->session->set_flashdata('error', 'Brand not found.');
            redirect('masters/brands');
        }

        $data['page_title'] = 'Edit Brand';
        $data['brand'] = $brand;

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $name = $this->input->post('name', TRUE);
            if (!empty($name)) {
                $update_data = ['name' => $name];
                if ($this->input->post('delete_image') == '1') {
                    if ($brand->image && file_exists('./assets/uploads/brands/' . $brand->image)) {
                        @unlink('./assets/uploads/brands/' . $brand->image);
                    }
                    $update_data['image'] = null;
                } else {
                    $update_data['image'] = $this->_handle_brand_image_upload('image', $brand->image);
                }
                $this->db->where('id', $id)->update('brands', $update_data);
                $this->session->set_flashdata('success', 'Brand updated.');
                redirect('masters/brands');
            }
        }

        $this->render('masters/edit_brand', $data);
    }

    public function delete_brand($id) {
        $brand = $this->db->where('id', $id)->get('brands')->row();
        if ($brand && $brand->image && file_exists('./assets/uploads/brands/' . $brand->image)) {
            @unlink('./assets/uploads/brands/' . $brand->image);
        }
        $this->db->where('id', $id)->delete('brands');
        $this->session->set_flashdata('success', 'Brand deleted.');
        redirect('masters/brands');
    }

    private function _handle_brand_image_upload($field_name, $existing_image = null) {
        if (empty($_FILES[$field_name]['name'])) {
            return $existing_image;
        }
        $config['upload_path']   = './assets/uploads/brands/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
        $config['max_size']      = 5120;
        $config['encrypt_name']  = TRUE;

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, TRUE);
        }
        $this->load->library('upload');
        $this->upload->initialize($config, TRUE);
        if (!$this->upload->do_upload($field_name)) {
            $this->session->set_flashdata('error', 'Brand image upload error: ' . $this->upload->display_errors('', ''));
            return $existing_image;
        }
        if ($existing_image && file_exists($config['upload_path'] . $existing_image)) {
            @unlink($config['upload_path'] . $existing_image);
        }
        return $this->upload->data()['file_name'];
    }

    public function categories() {
        $data['page_title'] = 'Categories Master';
        $data['categories'] = $this->Master_model->get_all_categories();

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $name = $this->input->post('name', TRUE);
            $display_order = (int)$this->input->post('display_order', TRUE);
            if (!empty($name)) {
                $image = $this->_handle_category_image_upload('image');
                $this->db->insert('categories', [
                    'name' => $name, 
                    'image' => $image,
                    'display_order' => $display_order,
                    'status' => 1
                ]);
                $this->session->set_flashdata('success', 'Category added.');
            }
            redirect('masters/categories');
        }

        $this->render('masters/categories', $data);
    }

    public function edit_category($id = null) {
        if (!$id) redirect('masters/categories');
        
        $category = $this->db->where('id', $id)->get('categories')->row();
        if (!$category) {
            $this->session->set_flashdata('error', 'Category not found.');
            redirect('masters/categories');
        }

        $data['page_title'] = 'Edit Category';
        $data['category'] = $category;

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $name = $this->input->post('name', TRUE);
            $display_order = (int)$this->input->post('display_order', TRUE);
            
            if (!empty($name)) {
                $update_data = [
                    'name' => $name,
                    'display_order' => $display_order
                ];

                // Category-level loyalty switch (§4). Guarded because
                // sql/45_loyalty.sql is per-database.
                if ($this->db->field_exists('allow_loyalty_points', 'categories')) {
                    $new_allow = $this->input->post('allow_loyalty_points') ? 1 : 0;
                    if ((int)$category->allow_loyalty_points !== $new_allow) {
                        // Switching a whole category off is a big lever — audit it.
                        $this->log_activity('masters', 'category_loyalty',
                            'Category "' . $category->name . '" loyalty points '
                            . ((int)$category->allow_loyalty_points === 1 ? 'ENABLED' : 'DISABLED')
                            . ' → ' . ($new_allow === 1 ? 'ENABLED' : 'DISABLED'), $id, $category->name);
                    }
                    $update_data['allow_loyalty_points'] = $new_allow;
                }

                if ($this->input->post('delete_image') == '1') {
                    if ($category->image && file_exists('./assets/uploads/categories/' . $category->image)) {
                        @unlink('./assets/uploads/categories/' . $category->image);
                    }
                    $update_data['image'] = null;
                } else {
                    $update_data['image'] = $this->_handle_category_image_upload('image', $category->image);
                }

                $this->db->where('id', $id)->update('categories', $update_data);
                $this->session->set_flashdata('success', 'Category updated successfully.');
                redirect('masters/categories');
            }
        }

        $this->render('masters/edit_category', $data);
    }

    public function delete_category($id) {
        $category = $this->db->where('id', $id)->get('categories')->row();
        if ($category && $category->image && file_exists('./assets/uploads/categories/' . $category->image)) {
            @unlink('./assets/uploads/categories/' . $category->image);
        }
        $this->db->where('id', $id)->delete('categories');
        $this->session->set_flashdata('success', 'Category deleted.');
        redirect('masters/categories');
    }

    private function _handle_category_image_upload($field_name, $existing_image = null) {
        if (empty($_FILES[$field_name]['name'])) {
            return $existing_image;
        }

        $config['upload_path']   = './assets/uploads/categories/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
        $config['max_size']      = 5120; // 5MB
        $config['encrypt_name']  = TRUE;

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, TRUE);
        }

        $this->load->library('upload');
        $this->upload->initialize($config, TRUE);

        if (!$this->upload->do_upload($field_name)) {
            $this->session->set_flashdata('error', 'Category image upload error: ' . $this->upload->display_errors('', ''));
            return $existing_image;
        }

        // Delete old image if exists
        if ($existing_image && file_exists($config['upload_path'] . $existing_image)) {
            @unlink($config['upload_path'] . $existing_image);
        }

        $upload_data = $this->upload->data();
        return $upload_data['file_name'];
    }

    // ── Sub-Categories ─────────────────────────────────────────
    public function sub_categories() {
        $data['page_title']      = 'Sub-Categories Master';
        $data['sub_categories']  = $this->db->select('sc.*, c.name AS category_name')
            ->from('sub_categories sc')
            ->join('categories c', 'c.id = sc.category_id', 'left')
            ->order_by('c.name, sc.name')->get()->result();
        $data['categories']      = $this->Master_model->get_all_categories();

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->db->insert('sub_categories', [
                'category_id' => (int)$this->input->post('category_id'),
                'name'        => $this->input->post('name', true),
                'code'        => $this->input->post('code', true),
                'status'      => 1,
            ]);
            $this->session->set_flashdata('success', 'Sub-Category added.');
            redirect('masters/sub_categories');
        }
        $this->render('masters/sub_categories', $data);
    }

    public function delete_sub_category($id) {
        $this->require_admin();
        $this->db->where('id', $id)->delete('sub_categories');
        $this->session->set_flashdata('success', 'Deleted.');
        redirect('masters/sub_categories');
    }

    // ── Groups ─────────────────────────────────────────────────
    public function groups() {
        $data['page_title'] = 'Groups Master';
        $data['groups']     = $this->db->order_by('name')->get('groups')->result();

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->db->insert('groups', [
                'name'   => $this->input->post('name', true),
                'code'   => $this->input->post('code', true),
                'status' => 1,
            ]);
            $this->session->set_flashdata('success', 'Group added.');
            redirect('masters/groups');
        }
        $this->render('masters/groups', $data);
    }

    public function delete_group($id) {
        $this->require_admin();
        $this->db->where('id', $id)->delete('groups');
        $this->session->set_flashdata('success', 'Deleted.');
        redirect('masters/groups');
    }

    // ── Departments ────────────────────────────────────────────
    public function departments() {
        $data['page_title']  = 'Departments Master';
        $data['departments'] = $this->db->order_by('name')->get('departments')->result();

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->db->insert('departments', [
                'name'   => $this->input->post('name', true),
                'code'   => $this->input->post('code', true),
                'status' => 1,
            ]);
            $this->session->set_flashdata('success', 'Department added.');
            redirect('masters/departments');
        }
        $this->render('masters/departments', $data);
    }

    public function delete_department($id) {
        $this->require_admin();
        $this->db->where('id', $id)->delete('departments');
        $this->session->set_flashdata('success', 'Deleted.');
        redirect('masters/departments');
    }

    // ── Tags ───────────────────────────────────────────────────
    public function tags() {
        $data['page_title'] = 'Product Tags Master';
        $data['tags']       = $this->db->order_by('name')->get('product_tags_master')->result();

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->db->insert('product_tags_master', [
                'name'   => $this->input->post('name', true),
                'color'  => $this->input->post('color', true) ?: '#6c757d',
                'status' => 1,
            ]);
            $this->session->set_flashdata('success', 'Tag added.');
            redirect('masters/tags');
        }
        $this->render('masters/tags', $data);
    }

    public function delete_tag($id) {
        $this->require_admin();
        $this->db->where('id', $id)->delete('product_tags_master');
        $this->session->set_flashdata('success', 'Deleted.');
        redirect('masters/tags');
    }

    // ── Racks ──────────────────────────────────────────────────
    public function racks() {
        if (!$this->db->table_exists('racks')) {
            $this->session->set_flashdata('error', 'Rack table not found. Please run sql/59_rack_management.sql');
            redirect('dashboard');
        }
        $data['page_title'] = 'Rack Master';
        $data['racks'] = $this->db->select('racks.*, l.name AS location_name')
            ->from('racks')
            ->join('locations l', 'l.id = racks.location_id', 'left')
            ->order_by('racks.sort_order', 'ASC')
            ->order_by('racks.rack_code', 'ASC')
            ->get()->result();
        $data['locations'] = $this->db->where('status', 1)->order_by('name')->get('locations')->result();

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $code = strtoupper(trim($this->input->post('rack_code', TRUE)));
            $name = $this->input->post('rack_name', TRUE);
            if (!empty($code) && !empty($name)) {
                $exists = $this->db->where('rack_code', $code)->get('racks')->row();
                if ($exists) {
                    $this->session->set_flashdata('error', 'Rack code "' . htmlspecialchars($code) . '" already exists.');
                } else {
                    $this->db->insert('racks', [
                        'rack_code'   => $code,
                        'rack_name'   => $name,
                        'location_id' => $this->input->post('location_id') ?: null,
                        'description' => $this->input->post('description', TRUE),
                        'sort_order'  => (int)$this->input->post('sort_order'),
                        'status'      => 1,
                    ]);
                    $this->session->set_flashdata('success', 'Rack "' . htmlspecialchars($code) . '" added.');
                }
            }
            redirect('masters/racks');
        }

        $this->render('masters/racks', $data);
    }

    public function edit_rack($id = null) {
        if (!$id) redirect('masters/racks');
        $rack = $this->db->where('id', $id)->get('racks')->row();
        if (!$rack) {
            $this->session->set_flashdata('error', 'Rack not found.');
            redirect('masters/racks');
        }

        $data['page_title'] = 'Edit Rack';
        $data['rack'] = $rack;
        $data['locations'] = $this->db->where('status', 1)->order_by('name')->get('locations')->result();

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $code = strtoupper(trim($this->input->post('rack_code', TRUE)));
            $name = $this->input->post('rack_name', TRUE);
            if (!empty($code) && !empty($name)) {
                $dup = $this->db->where('rack_code', $code)->where('id !=', $id)->get('racks')->row();
                if ($dup) {
                    $this->session->set_flashdata('error', 'Rack code "' . htmlspecialchars($code) . '" already used.');
                } else {
                    $this->db->where('id', $id)->update('racks', [
                        'rack_code'   => $code,
                        'rack_name'   => $name,
                        'location_id' => $this->input->post('location_id') ?: null,
                        'description' => $this->input->post('description', TRUE),
                        'sort_order'  => (int)$this->input->post('sort_order'),
                    ]);
                    $this->session->set_flashdata('success', 'Rack updated.');
                    redirect('masters/racks');
                }
            }
        }

        $this->render('masters/edit_rack', $data);
    }

    public function delete_rack($id) {
        $this->require_admin();
        $this->db->where('rack_id', $id)->update('products', ['rack_id' => null]);
        $this->db->where('id', $id)->delete('racks');
        $this->session->set_flashdata('success', 'Rack deleted.');
        redirect('masters/racks');
    }

    public function rack_qr($id = null) {
        if (!$id) redirect('masters/racks');
        $rack = $this->db->select('racks.*, l.name AS location_name')
            ->from('racks')
            ->join('locations l', 'l.id = racks.location_id', 'left')
            ->where('racks.id', $id)
            ->get()->row();
        if (!$rack) show_404();

        $data['rack'] = $rack;
        $data['base_url'] = base_url();
        $this->load->view('masters/rack_qr_print', $data);
    }

    public function rack_qr_bulk() {
        $ids = $this->input->get('ids');
        if (empty($ids)) redirect('masters/racks');
        $id_arr = array_map('intval', explode(',', $ids));
        $data['racks'] = $this->db->select('racks.*, l.name AS location_name')
            ->from('racks')
            ->join('locations l', 'l.id = racks.location_id', 'left')
            ->where_in('racks.id', $id_arr)
            ->order_by('racks.sort_order', 'ASC')
            ->get()->result();
        $data['base_url'] = base_url();
        $this->load->view('masters/rack_qr_print', $data);
    }

    public function rack_stock($id = null) {
        if (!$id) redirect('masters/racks');
        $rack = $this->db->select('racks.*, l.name AS location_name')
            ->from('racks')
            ->join('locations l', 'l.id = racks.location_id', 'left')
            ->where('racks.id', $id)
            ->get()->row();
        if (!$rack) show_404();

        $products = $this->db->select('products.id, products.name, products.item_code, GREATEST(products.stock_qty, 0) AS stock_qty, products.mrp, products.selling_price, products.unit, c.name AS category_name')
            ->from('products')
            ->join('categories c', 'c.id = products.category_id', 'left')
            ->where('products.rack_id', $id)
            ->where('products.status', 1)
            ->order_by('products.name', 'ASC')
            ->get()->result();

        $data['page_title'] = 'Rack Stock — ' . $rack->rack_code;
        $data['rack'] = $rack;
        $data['products'] = $products;
        $this->render('masters/rack_stock', $data);
    }

    public function migrate_racks() {
        $this->require_admin();
        $msgs = [];
        if (!$this->db->table_exists('racks')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `racks` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `rack_code` VARCHAR(20) NOT NULL,
                `rack_name` VARCHAR(100) NOT NULL,
                `location_id` INT DEFAULT NULL,
                `description` VARCHAR(255) DEFAULT NULL,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_rack_code` (`rack_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $msgs[] = 'Created racks table.';
        } else {
            $msgs[] = 'racks table already exists.';
        }
        if (!$this->db->field_exists('rack_id', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `rack_id` INT DEFAULT NULL");
            $msgs[] = 'Added rack_id column to products.';
        } else {
            $msgs[] = 'rack_id column already exists.';
        }
        echo '<h3>Rack Migration</h3><ul><li>' . implode('</li><li>', $msgs) . '</li></ul><p><a href="' . base_url('masters/racks') . '">Go to Rack Master</a></p>';
    }

    public function colours() {
        redirect('garments/colours');
    }

    public function colors() {
        redirect('garments/colours');
    }
}
