# Ezee Needs ERP — Complete Package (All Modules)
## Install in 5 steps
1. Copy `application/` folder contents into your CI3 project
2. Run SQL files in ORDER: sql/01_auth.sql → sql/02_products.sql → sql/03_suppliers_...sql
3. Copy `application/config/routes_full.php` content into `application/config/routes.php`
4. Set `application/config/config.php`: base_url, encryption_key, sess_driver=database
5. Set `application/config/database.php`: hostname, username, password, database=ezee_needs_db
6. Login: admin / Admin@123 → change password immediately

## All 10 modules
Auth, Dashboard, Products, Suppliers, Customers, Purchases+PRN, Billing+GST, Payments, Inventory, Reports

## After install
- Update company_settings table with your GSTIN, address, phone, PAN
- Add methods from Payment_model.php comments to Invoice_model.php and Purchase_model.php
