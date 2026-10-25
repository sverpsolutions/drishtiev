===============================================================
  EZEE NEEDS ERP — COMPLETE INSTALLATION PACKAGE
  PHP + CodeIgniter 3 + MySQL | FMCG Billing System
===============================================================

LOGIN AFTER INSTALL:
  URL:       http://localhost/ezee_needs/login
  Username:  admin
  Password:  Admin@123

===============================================================
  INSTALLATION STEPS (5 minutes)
===============================================================

STEP 1 — CREATE DATABASE
  - Open http://localhost/phpmyadmin
  - Click "New" → name it:  ezee_needs_db
  - Collation: utf8_general_ci → Click Create

STEP 2 — IMPORT SQL FILES (in order!)
  - Select ezee_needs_db in phpMyAdmin
  - Click Import tab → Choose file → run each file:
    a) sql/01_run_first_auth.sql
    b) sql/02_run_second_products.sql
    c) sql/03_run_third_all_tables.sql

STEP 3 — COPY PROJECT FILES
  - Copy this entire folder to:
    C:\xampp\htdocs\ezee_needs\
  - Your folder should look like:
    C:\xampp\htdocs\ezee_needs\application\
    C:\xampp\htdocs\ezee_needs\assets\
    C:\xampp\htdocs\ezee_needs\.htaccess
    C:\xampp\htdocs\ezee_needs\sql\

STEP 4 — CONFIGURE (already done, just verify)
  application/config/config.php
    $config['base_url'] = 'http://localhost/ezee_needs/';

  application/config/database.php
    $db['default']['database'] = 'ezee_needs_db';
    $db['default']['username'] = 'root';
    $db['default']['password'] = '';    // blank for XAMPP

STEP 5 — OPEN IN BROWSER
  http://localhost/ezee_needs/login

===============================================================
  IF YOU GET 404 ERROR
===============================================================
  Open: C:\xampp\apache\conf\httpd.conf
  Find: AllowOverride None  (under htdocs section)
  Change to: AllowOverride All
  Restart Apache in XAMPP Control Panel

===============================================================
  MODULES INCLUDED
===============================================================
  1.  Authentication    (login/logout/roles)
  2.  Dashboard         (stats, alerts, recent invoices)
  3.  Products          (CRUD, GST/HSN, stock, expiry)
  4.  Suppliers         (CRUD, GSTIN, purchase history)
  5.  Customers         (retail/wholesale, credit limits)
  6.  Purchases         (stock inward, PRN returns)
  7.  Billing           (GST invoices, CGST/SGST/IGST)
  8.  Payments          (customer receipts, supplier payments)
  9.  Inventory         (stock levels, movements, adjust)
  10. Reports           (Sales, Purchase, Stock, P&L, GST)
  11. Settings          (company profile, SMTP email)

===============================================================
  DEFAULT ADMIN PASSWORD
===============================================================
  Change Admin@123 immediately after first login
  Go to phpMyAdmin → users table → edit admin row
  Generate new hash: https://bcrypt-generator.com

