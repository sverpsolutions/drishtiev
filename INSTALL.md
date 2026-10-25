# Ezee Needs ERP — v4 Enhancements

## New features in this release
1. PDF Invoice Download (mPDF)
2. Email Invoice with PDF attachment (SMTP)
3. Barcode Billing interface (scan-to-add)
4. Customer Ledger / Account Statement
5. Settings panel (Company, GST, SMTP, thresholds)

## Installation

### Step 1 — Run SQL
```sql
-- In phpMyAdmin, run:
sql/04_v4_additions.sql
```

### Step 2 — Install mPDF (for PDF generation)
```bash
cd C:/xampp/htdocs/ezee_needs
composer require mpdf/mpdf
composer require picqer/php-barcode-generator
```
If Composer not installed: https://getcomposer.org/download/

### Step 3 — Copy files
Copy all `application/` folder contents into your CI3 project:
- `application/libraries/Pdf_generator.php`
- `application/helpers/barcode_helper.php`
- `application/controllers/Settings.php`
- `application/views/billing/pdf_template.php`
- `application/views/billing/email_template.php`
- `application/views/billing/barcode_billing.php`
- `application/views/customers/ledger.php`
- `application/views/settings/index.php`

### Step 4 — Patch existing files

**Billing.php** — Add these to `__construct()`:
```php
$this->load->library('pdf_generator');
$this->load->library('email');
```
Then paste all methods from `PATCH_Billing_add_methods.php` into the Billing class.

**Customers.php** — Replace the `ledger()` method with content from `PATCH_Customers_ledger_method.php`

**billing/invoice.php view** — Replace the button div + add email modal from `PATCH_invoice_and_routes.php`

**layouts/header.php** — Add Settings nav link (see comment in PATCH file)

**config/routes.php** — Add new routes (see comment in PATCH file):
```php
$route['settings']                     = 'Settings/index';
$route['settings/save']                = 'Settings/save';
$route['billing/pdf/(:num)']           = 'Billing/pdf/$1';
$route['billing/email_invoice/(:num)'] = 'Billing/email_invoice/$1';
$route['billing/bulk_pdf']             = 'Billing/bulk_pdf';
$route['billing/scan']                 = 'Billing/scan';
```

### Step 5 — Configure SMTP
Go to **Settings** in the ERP and fill in your SMTP details.
For Gmail, generate an App Password at: https://myaccount.google.com/apppasswords

### Step 6 — Barcode mode
Access fast billing at: http://localhost/ezee_needs/billing/scan
Add a `barcode` column to products table (already in 04_v4_additions.sql)
Product IDs also work as "barcodes" for testing.

## Barcode scanner hardware
Any USB barcode scanner that acts as a keyboard (HID mode) works automatically.
The scan field is auto-focused — just point and scan.
Recommended: Honeywell Voyager 1200g or any Code 128 scanner.

## PDF quality note
mPDF renders A4 GST invoices with proper fonts, rupee symbol, and watermark stamps.
The `pdf_template.php` uses DejaVu Sans for full Unicode support.
