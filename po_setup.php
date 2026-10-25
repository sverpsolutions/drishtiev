<?php
// PO System Database Setup — run once via browser: /po_setup.php
// Then delete this file.
defined('BASEPATH') OR define('BASEPATH', true);
$host = 'localhost'; $db = 'u153068796_sandalhouse'; $user = 'u153068796_sandalhouse'; $pass = 'Mci8yo@257';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqls = [];

// ─── Enhance suppliers table ─────────────────────────────────────────────────
$sqls[] = "ALTER TABLE `suppliers`
  ADD COLUMN IF NOT EXISTS `supplier_code`     VARCHAR(30)    DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `company_name`      VARCHAR(150)   DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `whatsapp`          VARCHAR(15)    DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `website`           VARCHAR(100)   DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `country`           VARCHAR(60)    DEFAULT 'India',
  ADD COLUMN IF NOT EXISTS `credit_days`       INT            DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `credit_limit`      DECIMAL(12,2)  DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `opening_balance`   DECIMAL(12,2)  DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `payment_terms`     VARCHAR(100)   DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `currency`          VARCHAR(10)    DEFAULT 'INR',
  ADD COLUMN IF NOT EXISTS `is_preferred`      TINYINT        DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `default_tax_pct`   DECIMAL(5,2)   DEFAULT 18,
  ADD COLUMN IF NOT EXISTS `default_discount`  DECIMAL(5,2)   DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `freight_applicable` TINYINT       DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `moq`               INT            DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `pack_size`         INT            DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `lead_time_days`    INT            DEFAULT 2,
  ADD COLUMN IF NOT EXISTS `delivery_days`     INT            DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `order_cutoff_time` TIME           DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `portal_username`   VARCHAR(60)    DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `portal_password`   VARCHAR(200)   DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `portal_active`     TINYINT        DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `vendor_score`      DECIMAL(5,2)   DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `vendor_tier`       VARCHAR(20)    DEFAULT 'bronze',
  ADD COLUMN IF NOT EXISTS `notes`             TEXT           DEFAULT NULL";

// ─── Supplier visit schedules ─────────────────────────────────────────────────
$sqls[] = "CREATE TABLE IF NOT EXISTS `supplier_visit_schedules` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `supplier_id`     INT NOT NULL,
  `schedule_type`   ENUM('weekly','interval','monthly_dates','daily') DEFAULT 'interval',
  `weekdays`        VARCHAR(20)  DEFAULT NULL COMMENT 'JSON e.g. [1,4] for Mon,Thu',
  `interval_days`   INT          DEFAULT 7,
  `month_dates`     VARCHAR(20)  DEFAULT NULL COMMENT 'JSON e.g. [1,15]',
  `last_visit_date` DATE         DEFAULT NULL,
  `next_visit_date` DATE         DEFAULT NULL,
  `assigned_buyer`  INT          DEFAULT NULL,
  `notes`           TEXT         DEFAULT NULL,
  `created_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX(`supplier_id`), INDEX(`next_visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS `supplier_visit_logs` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `supplier_id`   INT NOT NULL,
  `visit_date`    DATE NOT NULL,
  `buyer_id`      INT DEFAULT NULL,
  `po_value`      DECIMAL(12,2) DEFAULT 0,
  `notes`         TEXT DEFAULT NULL,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`supplier_id`), INDEX(`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// ─── Supplier products (which supplier sells which product) ────────────────
$sqls[] = "CREATE TABLE IF NOT EXISTS `supplier_products` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `supplier_id`       INT NOT NULL,
  `product_id`        INT NOT NULL,
  `supplier_sku`      VARCHAR(60) DEFAULT NULL,
  `last_purchase_price` DECIMAL(12,2) DEFAULT 0,
  `last_purchase_date`  DATE DEFAULT NULL,
  `is_primary`        TINYINT DEFAULT 0,
  `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_sup_prod` (`supplier_id`,`product_id`),
  INDEX(`supplier_id`), INDEX(`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// ─── Supplier documents ────────────────────────────────────────────────────
$sqls[] = "CREATE TABLE IF NOT EXISTS `supplier_documents` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `supplier_id` INT NOT NULL,
  `doc_type`    VARCHAR(60) NOT NULL COMMENT 'GST,PAN,FSSAI,MSME,Cheque,Bank,Agreement,Pricelist,Trade,Insurance',
  `doc_name`    VARCHAR(150) DEFAULT NULL,
  `file_path`   VARCHAR(255) DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `notes`       TEXT DEFAULT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`supplier_id`), INDEX(`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// ─── Purchase Orders ───────────────────────────────────────────────────────
$sqls[] = "CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id`                 INT AUTO_INCREMENT PRIMARY KEY,
  `po_number`          VARCHAR(30) NOT NULL UNIQUE,
  `supplier_id`        INT NOT NULL,
  `location_id`        INT DEFAULT NULL,
  `status`             ENUM('draft','submitted','pending_approval','approved','vendor_confirmed','dispatched','in_transit','received','invoiced','paid','rejected','cancelled') DEFAULT 'draft',
  `approval_level`     INT DEFAULT 0,
  `expected_delivery`  DATE DEFAULT NULL,
  `delivery_address`   TEXT DEFAULT NULL,
  `payment_terms`      VARCHAR(100) DEFAULT NULL,
  `reference`          VARCHAR(100) DEFAULT NULL,
  `remarks`            TEXT DEFAULT NULL,
  `gross_amount`       DECIMAL(12,2) DEFAULT 0,
  `total_discount`     DECIMAL(12,2) DEFAULT 0,
  `total_tax`          DECIMAL(12,2) DEFAULT 0,
  `freight_charges`    DECIMAL(12,2) DEFAULT 0,
  `round_off`          DECIMAL(6,2)  DEFAULT 0,
  `net_total`          DECIMAL(12,2) DEFAULT 0,
  `buyer_id`           INT DEFAULT NULL,
  `approved_by`        INT DEFAULT NULL,
  `approved_at`        DATETIME DEFAULT NULL,
  `vendor_confirmed_at` DATETIME DEFAULT NULL,
  `dispatched_at`      DATETIME DEFAULT NULL,
  `received_at`        DATETIME DEFAULT NULL,
  `visit_schedule_id`  INT DEFAULT NULL,
  `rfq_id`             INT DEFAULT NULL,
  `created_by`         INT DEFAULT NULL,
  `created_at`         DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME DEFAULT NULL,
  INDEX(`supplier_id`), INDEX(`status`), INDEX(`location_id`), INDEX(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `po_id`            INT NOT NULL,
  `product_id`       INT NOT NULL,
  `current_stock`    DECIMAL(10,2) DEFAULT 0,
  `avg_daily_sales`  DECIMAL(10,3) DEFAULT 0,
  `suggested_qty`    DECIMAL(10,2) DEFAULT 0,
  `ordered_qty`      DECIMAL(10,2) DEFAULT 0,
  `received_qty`     DECIMAL(10,2) DEFAULT 0,
  `rate`             DECIMAL(12,2) DEFAULT 0,
  `discount_pct`     DECIMAL(5,2)  DEFAULT 0,
  `discount_amt`     DECIMAL(12,2) DEFAULT 0,
  `gst_pct`          DECIMAL(5,2)  DEFAULT 0,
  `taxable_amt`      DECIMAL(12,2) DEFAULT 0,
  `tax_amt`          DECIMAL(12,2) DEFAULT 0,
  `amount`           DECIMAL(12,2) DEFAULT 0,
  `free_qty`         DECIMAL(10,2) DEFAULT 0,
  `batch_no`         VARCHAR(50)   DEFAULT NULL,
  `expiry_date`      DATE          DEFAULT NULL,
  `unit`             VARCHAR(20)   DEFAULT 'PCS',
  INDEX(`po_id`), INDEX(`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// ─── Purchase Approvals ────────────────────────────────────────────────────
$sqls[] = "CREATE TABLE IF NOT EXISTS `purchase_approvals` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `po_id`       INT NOT NULL,
  `level`       INT NOT NULL COMMENT '1=Store Mgr 2=Purchase Mgr 3=Finance 4=Director',
  `approver_id` INT DEFAULT NULL,
  `action`      ENUM('pending','approved','rejected','forwarded') DEFAULT 'pending',
  `remarks`     TEXT DEFAULT NULL,
  `acted_at`    DATETIME DEFAULT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`po_id`), INDEX(`approver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// ─── GRN ──────────────────────────────────────────────────────────────────
$sqls[] = "CREATE TABLE IF NOT EXISTS `grn` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `grn_number`          VARCHAR(30) NOT NULL UNIQUE,
  `po_id`               INT NOT NULL,
  `supplier_id`         INT NOT NULL,
  `location_id`         INT DEFAULT NULL,
  `received_date`       DATE NOT NULL,
  `vehicle_no`          VARCHAR(30) DEFAULT NULL,
  `driver_name`         VARCHAR(80) DEFAULT NULL,
  `supplier_invoice_no` VARCHAR(50) DEFAULT NULL,
  `supplier_invoice_date` DATE DEFAULT NULL,
  `eway_bill`           VARCHAR(30) DEFAULT NULL,
  `lr_number`           VARCHAR(50) DEFAULT NULL,
  `status`              ENUM('pending','qc_pending','accepted','partial','rejected') DEFAULT 'pending',
  `gross_amount`        DECIMAL(12,2) DEFAULT 0,
  `total_tax`           DECIMAL(12,2) DEFAULT 0,
  `net_total`           DECIMAL(12,2) DEFAULT 0,
  `qc_notes`            TEXT DEFAULT NULL,
  `received_by`         INT DEFAULT NULL,
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`po_id`), INDEX(`supplier_id`), INDEX(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS `grn_items` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `grn_id`        INT NOT NULL,
  `po_item_id`    INT DEFAULT NULL,
  `product_id`    INT NOT NULL,
  `ordered_qty`   DECIMAL(10,2) DEFAULT 0,
  `received_qty`  DECIMAL(10,2) DEFAULT 0,
  `accepted_qty`  DECIMAL(10,2) DEFAULT 0,
  `rejected_qty`  DECIMAL(10,2) DEFAULT 0,
  `rate`          DECIMAL(12,2) DEFAULT 0,
  `gst_pct`       DECIMAL(5,2)  DEFAULT 0,
  `amount`        DECIMAL(12,2) DEFAULT 0,
  `batch_no`      VARCHAR(50)   DEFAULT NULL,
  `expiry_date`   DATE          DEFAULT NULL,
  `rejection_reason` VARCHAR(200) DEFAULT NULL,
  INDEX(`grn_id`), INDEX(`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// ─── RFQ ──────────────────────────────────────────────────────────────────
$sqls[] = "CREATE TABLE IF NOT EXISTS `rfq` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `rfq_number`  VARCHAR(30) NOT NULL UNIQUE,
  `title`       VARCHAR(150) DEFAULT NULL,
  `valid_until` DATE DEFAULT NULL,
  `status`      ENUM('draft','sent','responded','compared','closed','converted') DEFAULT 'draft',
  `buyer_id`    INT DEFAULT NULL,
  `notes`       TEXT DEFAULT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS `rfq_items` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `rfq_id`     INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity`   DECIMAL(10,2) DEFAULT 0,
  `unit`       VARCHAR(20)   DEFAULT 'PCS',
  `specs`      TEXT DEFAULT NULL,
  INDEX(`rfq_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS `rfq_suppliers` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `rfq_id`      INT NOT NULL,
  `supplier_id` INT NOT NULL,
  `status`      ENUM('sent','responded','declined','selected','rejected') DEFAULT 'sent',
  `sent_at`     DATETIME DEFAULT NULL,
  INDEX(`rfq_id`), INDEX(`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS `rfq_quotations` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `rfq_id`          INT NOT NULL,
  `rfq_item_id`     INT NOT NULL,
  `supplier_id`     INT NOT NULL,
  `quoted_price`    DECIMAL(12,2) DEFAULT 0,
  `discount_pct`    DECIMAL(5,2)  DEFAULT 0,
  `gst_pct`         DECIMAL(5,2)  DEFAULT 0,
  `freight`         DECIMAL(10,2) DEFAULT 0,
  `delivery_days`   INT DEFAULT 0,
  `credit_days`     INT DEFAULT 0,
  `moq`             INT DEFAULT 1,
  `notes`           TEXT DEFAULT NULL,
  `submitted_at`    DATETIME DEFAULT NULL,
  `is_selected`     TINYINT DEFAULT 0,
  INDEX(`rfq_id`), INDEX(`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// ─── Vendor Messages ──────────────────────────────────────────────────────
$sqls[] = "CREATE TABLE IF NOT EXISTS `vendor_messages` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `supplier_id`   INT NOT NULL,
  `po_id`         INT DEFAULT NULL,
  `rfq_id`        INT DEFAULT NULL,
  `sender_type`   ENUM('buyer','supplier') NOT NULL,
  `sender_id`     INT DEFAULT NULL,
  `message`       TEXT NOT NULL,
  `attachment`    VARCHAR(255) DEFAULT NULL,
  `is_read`       TINYINT DEFAULT 0,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`supplier_id`), INDEX(`po_id`), INDEX(`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// ─── Vendor Performance ────────────────────────────────────────────────────
$sqls[] = "CREATE TABLE IF NOT EXISTS `vendor_performance` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `supplier_id`         INT NOT NULL UNIQUE,
  `total_orders`        INT DEFAULT 0,
  `ontime_delivery_pct` DECIMAL(5,2) DEFAULT 0,
  `fill_rate_pct`       DECIMAL(5,2) DEFAULT 0,
  `avg_delivery_days`   DECIMAL(5,1) DEFAULT 0,
  `quality_rating`      DECIMAL(3,1) DEFAULT 5,
  `return_pct`          DECIMAL(5,2) DEFAULT 0,
  `price_stability_pct` DECIMAL(5,2) DEFAULT 100,
  `order_acceptance_pct` DECIMAL(5,2) DEFAULT 100,
  `invoice_accuracy_pct` DECIMAL(5,2) DEFAULT 100,
  `overall_score`       DECIMAL(5,2) DEFAULT 0,
  `tier`                ENUM('gold','silver','bronze','review') DEFAULT 'bronze',
  `last_computed`       DATETIME DEFAULT NULL,
  INDEX(`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// ─── Approval Config ───────────────────────────────────────────────────────
$sqls[] = "CREATE TABLE IF NOT EXISTS `po_approval_config` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `level`         INT NOT NULL,
  `label`         VARCHAR(80) DEFAULT NULL,
  `min_amount`    DECIMAL(12,2) DEFAULT 0,
  `max_amount`    DECIMAL(12,2) DEFAULT 0,
  `approver_role` VARCHAR(40) DEFAULT NULL,
  `approver_user_id` INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "INSERT IGNORE INTO `po_approval_config` (`level`,`label`,`min_amount`,`max_amount`,`approver_role`) VALUES
  (1,'Store Manager',0,25000,'manager'),
  (2,'Purchase Manager',25001,100000,'admin'),
  (3,'Finance Head',100001,500000,'admin'),
  (4,'Director',500001,99999999,'superadmin')";

$ok = []; $err = [];
foreach ($sqls as $i => $sql) {
    try {
        $pdo->exec($sql);
        $ok[] = "Query " . ($i+1) . " OK";
    } catch (Exception $e) {
        $err[] = "Query " . ($i+1) . " ERROR: " . $e->getMessage();
    }
}

echo "<h2>PO System DB Setup</h2>";
echo "<h3 style='color:green'>Success (" . count($ok) . ")</h3><ul>";
foreach ($ok as $m) echo "<li>$m</li>"; echo "</ul>";
if ($err) {
    echo "<h3 style='color:red'>Errors (" . count($err) . ")</h3><ul>";
    foreach ($err as $m) echo "<li>$m</li>"; echo "</ul>";
}
echo "<p><b>Delete this file after running!</b></p>";
