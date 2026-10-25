<?php
// Initialize CodeIgniter safely
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
define('ENVIRONMENT', 'development');
chdir(__DIR__);
$system_path = 'system';
$application_folder = 'application';
if (($_temp = realpath($system_path)) !== FALSE) { $system_path = $_temp.DIRECTORY_SEPARATOR; } else { $system_path = strtr(rtrim($system_path, '/\\'), '/\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR; }
define('BASEPATH', $system_path);
define('FCPATH', dirname(__FILE__).DIRECTORY_SEPARATOR);
define('APPPATH', $application_folder.DIRECTORY_SEPARATOR);
define('VIEWPATH', $application_folder.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR);
require_once BASEPATH.'core/CodeIgniter.php';

// Now that CI is loaded, we can use the DB
$CI =& get_instance();
$CI->load->database();

echo "Starting Cost Price (CP) retroactive update...<br>";

// Get all products
$products = $CI->db->select('id, name')->get('products')->result();
$updated_count = 0;

foreach ($products as $p) {
    // Find the most recent authorized purchase item for this product
    $latest_item = $CI->db->select('pi.qty, pi.total')
                          ->from('purchase_items pi')
                          ->join('purchases p', 'p.id = pi.purchase_id')
                          ->where('pi.product_id', $p->id)
                          ->where_in('p.status', ['received', 'partial'])
                          ->order_by('p.invoice_date', 'DESC')
                          ->order_by('p.id', 'DESC')
                          ->limit(1)
                          ->get()
                          ->row();

    if ($latest_item && $latest_item->qty > 0) {
        $new_cp = round($latest_item->total / $latest_item->qty, 2);
        
        $CI->db->set('cost_price', $new_cp)
               ->where('id', $p->id)
               ->update('products');
               
        echo "Updated '{$p->name}' CP to {$new_cp}<br>";
        $updated_count++;
    }
}

echo "Done! Successfully updated CP for {$updated_count} products.<br>";
echo "<strong>YOU CAN NOW DELETE THIS SCRIPT FOR SECURITY.</strong>";
