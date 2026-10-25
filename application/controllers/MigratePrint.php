<?php
class MigratePrint extends CI_Controller {
    public function index() {
        $this->db->query("CREATE TABLE IF NOT EXISTS audit_print_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            invoice_no VARCHAR(50) NOT NULL,
            print_date DATETIME NOT NULL,
            user_id INT NOT NULL,
            printer_name VARCHAR(100),
            copies INT DEFAULT 1,
            status VARCHAR(20) DEFAULT 'success',
            error_msg TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $settings = [
            'print_mode' => 'preview',
            'default_printer' => ''
        ];

        foreach ($settings as $k => $v) {
            $exists = $this->db->where('key_name', $k)->count_all_results('company_settings');
            if ($exists == 0) {
                $this->db->insert('company_settings', ['key_name' => $k, 'key_value' => $v]);
            }
        }

        echo "Migration completed.";
    }
}
