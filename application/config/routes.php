<?php
// ============================================================
// FILE: application/config/routes.php  (UPDATED)
// Added: HSN Master routes, product import routes,
//        inventory adjustment log route.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller']   = 'Shop';  // public storefront at site root; ERP login is /login
$route['translate_uri_dashes'] = FALSE;
$route['404_override']         = 'Shop/not_found';

// Auth
$route['login']   = 'Auth/index';
$route['logout']  = 'Auth/logout';

// Owner App (mobile dashboard)
$route['owner']                = 'OwnerApp/login';
$route['owner/login']          = 'OwnerApp/login';
$route['owner/logout']         = 'OwnerApp/logout';
$route['owner/dashboard']      = 'OwnerApp/dashboard';
$route['owner/sales']          = 'OwnerApp/sales';
$route['owner/purchases']      = 'OwnerApp/purchases';
$route['owner/transfers']      = 'OwnerApp/transfers';
$route['owner/live_stats']     = 'OwnerApp/live_stats';
$route['auth/device_pending']      = 'Auth/device_pending';
$route['auth/check_device_status'] = 'Auth/check_device_status';
$route['auth/choose_location']     = 'Auth/choose_location';
$route['auth/set_location']        = 'Auth/set_location';
$route['auth/get_user_location_ajax'] = 'Auth/get_user_location_ajax';

// Device Access Management
$route['device_access']                  = 'DeviceAccess/index';
$route['device_access/approve/(:num)']   = 'DeviceAccess/approve/$1';
$route['device_access/reject/(:num)']    = 'DeviceAccess/reject/$1';
$route['device_access/revoke/(:num)']    = 'DeviceAccess/revoke/$1';
$route['device_access/delete/(:num)']    = 'DeviceAccess/delete/$1';
$route['device_access/set_label/(:num)'] = 'DeviceAccess/set_label/$1';
$route['device_access/approve_ajax']     = 'DeviceAccess/approve_ajax';
$route['device_access/pending_count']    = 'DeviceAccess/pending_count';

// Dashboard
$route['dashboard'] = 'Dashboard/index';

// Products
$route['products']                          = 'Products/index';
$route['products/add']                      = 'Products/add';
$route['products/edit/(:num)']              = 'Products/edit/$1';
$route['products/delete/(:num)']            = 'Products/delete/$1';
$route['products/bulk_price_update']        = 'Products/bulk_price_update';
$route['products/bulk_price_update_ajax']   = 'Products/bulk_price_update_ajax';
$route['products/bulk_price_template']      = 'Products/bulk_price_template';
$route['products/import']                   = 'Products/import';
$route['products/import_preview']           = 'Products/import_preview';
$route['products/import_confirm']           = 'Products/import_confirm';
$route['products/import_process']           = 'Products/import_process';
$route['products/download_template']        = 'Products/download_template';
$route['products/download_excel_template']  = 'Products/download_excel_template';
$route['products/search_ajax']              = 'Products/search_ajax';
$route['products/next_item_code_ajax']      = 'Products/next_item_code_ajax';

// PLU (Price Look-Up) Master
$route['plu']                              = 'Plu/index';
$route['plu/create']                       = 'Plu/create';
$route['plu/edit/(:num)']                  = 'Plu/edit/$1';
$route['plu/toggle_status/(:num)']         = 'Plu/toggle_status/$1';
$route['plu/import']                       = 'Plu/import';
$route['plu/import_preview']               = 'Plu/import_preview';
$route['plu/import_confirm']               = 'Plu/import_confirm';
$route['plu/import_template']              = 'Plu/import_template';
$route['plu/import_issues']                = 'Plu/import_issues';
$route['plu/get_by_barcode_ajax']          = 'Plu/get_by_barcode_ajax';
$route['plu/get_plu_ajax/(:num)']          = 'Plu/get_plu_ajax/$1';
$route['plu/check_mrp_ajax']               = 'Plu/check_mrp_ajax';

// Enhanced Masters
$route['quick_search/search_ajax']             = 'QuickSearch/search_ajax';
$route['masters/edit_brand/(:num)']           = 'Masters/edit_brand/$1';
$route['masters/sub_categories']              = 'Masters/sub_categories';
$route['masters/delete_sub_category/(:num)']  = 'Masters/delete_sub_category/$1';
$route['masters/groups']                      = 'Masters/groups';
$route['masters/delete_group/(:num)']         = 'Masters/delete_group/$1';
$route['masters/departments']                 = 'Masters/departments';
$route['masters/delete_department/(:num)']    = 'Masters/delete_department/$1';
$route['masters/tags']                        = 'Masters/tags';
$route['masters/delete_tag/(:num)']           = 'Masters/delete_tag/$1';
$route['masters/racks']                       = 'Masters/racks';
$route['masters/edit_rack/(:num)']            = 'Masters/edit_rack/$1';
$route['masters/delete_rack/(:num)']          = 'Masters/delete_rack/$1';
$route['masters/rack_qr/(:num)']              = 'Masters/rack_qr/$1';
$route['masters/rack_qr_bulk']                = 'Masters/rack_qr_bulk';
$route['masters/rack_stock/(:num)']           = 'Masters/rack_stock/$1';

// Schemes
$route['schemes']                         = 'Schemes/index';
$route['schemes/create']                  = 'Schemes/create';
$route['schemes/save']                    = 'Schemes/save';
$route['schemes/edit/(:num)']             = 'Schemes/edit/$1';
$route['schemes/update/(:num)']           = 'Schemes/update/$1';
$route['schemes/view/(:num)']             = 'Schemes/view/$1';
$route['schemes/toggle/(:num)']           = 'Schemes/toggle/$1';
$route['schemes/clone_scheme/(:num)']     = 'Schemes/clone_scheme/$1';
$route['schemes/delete/(:num)']           = 'Schemes/delete/$1';
$route['schemes/simulate']                = 'Schemes/simulate';
$route['schemes/evaluate_ajax']           = 'Schemes/evaluate_ajax';
$route['schemes/report']                  = 'Schemes/report';
$route['schemes/item_list']               = 'Schemes/item_list';
$route['schemes/item_list_excel']         = 'Schemes/item_list_excel';

// Error Logs
$route['error_logs']                      = 'ErrorLogs/index';
$route['error_logs/resolve/(:num)']       = 'ErrorLogs/resolve/$1';
$route['error_logs/unresolve/(:num)']     = 'ErrorLogs/unresolve/$1';
$route['error_logs/delete/(:num)']        = 'ErrorLogs/delete/$1';
$route['error_logs/clear_resolved']       = 'ErrorLogs/clear_resolved';

// HSN Master
$route['hsn']                            = 'Hsn/index';
$route['hsn/add']                        = 'Hsn/add';
$route['hsn/edit/(:num)']                = 'Hsn/edit/$1';
$route['hsn/delete/(:num)']              = 'Hsn/delete/$1';
$route['hsn/get_rates_ajax/(:num)']      = 'Hsn/get_rates_ajax/$1';

// Suppliers
$route['suppliers']                   = 'Suppliers/index';
$route['suppliers/add']               = 'Suppliers/add';
$route['suppliers/edit/(:num)']       = 'Suppliers/edit/$1';
$route['suppliers/view/(:num)']       = 'Suppliers/view/$1';
$route['suppliers/delete/(:num)']     = 'Suppliers/delete/$1';

// Customers
$route['customers']                        = 'Customers/index';
$route['customers/add']                    = 'Customers/add';
$route['customers/edit/(:num)']            = 'Customers/edit/$1';
$route['customers/delete/(:num)']          = 'Customers/delete/$1';
$route['customers/ledger/(:num)']          = 'Customers/ledger/$1';
$route['customers/get_ajax/(:num)']        = 'Customers/get_ajax/$1';
$route['customers/update_mobile_ajax']     = 'Customers/update_mobile_ajax';
$route['customers/mobile_lookup_ajax']     = 'Customers/mobile_lookup_ajax';
$route['customers/wallet_history/(:num)']  = 'Customers/wallet_history/$1';
$route['customers/adjust_wallet/(:num)']   = 'Customers/adjust_wallet/$1';
$route['customers/quick_add_ajax']         = 'Customers/quick_add_ajax';
$route['customers/import']                 = 'Customers/import';
$route['customers/import_template']        = 'Customers/import_template';
$route['customers/migrate_discount_col']   = 'Customers/migrate_discount_col';

// Purchases
$route['purchases']                        = 'Purchases/index';
$route['purchases/create']                 = 'Purchases/create';
$route['purchases/view/(:num)']            = 'Purchases/view/$1';
$route['purchases/edit/(:num)']            = 'Purchases/edit/$1';
$route['purchases/return_create/(:num)']   = 'Purchases/return_create/$1';
$route['purchases/convert_to_invoice/(:num)'] = 'Purchases/convert_to_invoice/$1';

// Billing
$route['billing']                          = 'Billing/index';
$route['billing/create']                   = 'Billing/create';
$route['billing/invoice/(:num)']           = 'Billing/invoice/$1';
$route['billing/print_invoice/(:num)']     = 'Billing/print_invoice/$1';
$route['billing/pdf/(:num)']               = 'Billing/pdf/$1';
$route['billing/email_invoice/(:num)']     = 'Billing/email_invoice/$1';
$route['billing/cancel/(:num)']            = 'Billing/cancel/$1';
$route['billing/update_payment_mode/(:num)'] = 'Billing/update_payment_mode/$1';
$route['billing/scan']                     = 'Billing/scan';
$route['billing/bulk_pdf']                 = 'Billing/bulk_pdf';
$route['billing/get_product_ajax/(:num)']  = 'Billing/get_product_ajax/$1';
$route['billing/get_customer_ajax/(:num)'] = 'Billing/get_customer_ajax/$1';
$route['billing/get_by_barcode_ajax']      = 'Billing/get_by_barcode_ajax';
$route['billing/get_plu_ajax/(:num)']      = 'Billing/get_plu_ajax/$1';
$route['billing/get_customer_by_phone_ajax']   = 'Billing/get_customer_by_phone_ajax';
$route['billing/get_last_invoice_ajax/(:num)'] = 'Billing/get_last_invoice_ajax/$1';
$route['billing/hold_bill_ajax']               = 'Billing/hold_bill_ajax';
$route['billing/get_held_bills_ajax']          = 'Billing/get_held_bills_ajax';
$route['billing/recall_bill_ajax/(:num)']      = 'Billing/recall_bill_ajax/$1';
$route['billing/verify_manager_pin_ajax']      = 'Billing/verify_manager_pin_ajax';
$route['billing/create_customer_ajax']       = 'Billing/create_customer_ajax';
// Bill Modify + Audit
$route['billing/edit/(:num)']              = 'Billing/edit/$1';
$route['billing/audit_log/(:num)']         = 'Billing/audit_log/$1';
$route['billing/all_audit_logs']           = 'Billing/all_audit_logs';

// Estimates
$route['estimates']                              = 'Estimates/index';
$route['estimates/create']                       = 'Estimates/create';
$route['estimates/view/(:num)']                  = 'Estimates/view/$1';
$route['estimates/edit/(:num)']                  = 'Estimates/edit/$1';
$route['estimates/close_cash/(:num)']            = 'Estimates/close_cash/$1';
$route['estimates/cancel/(:num)']                = 'Estimates/cancel/$1';
$route['estimates/convert_to_invoice/(:num)']    = 'Estimates/convert_to_invoice/$1';
$route['estimates/print_estimate/(:num)']        = 'Estimates/print_estimate/$1';
$route['estimates/ajax_stock_check']             = 'Estimates/ajax_stock_check';
$route['estimates/ajax_search_product']          = 'Estimates/ajax_search_product';

// Army Quotation Management (fully independent module)
$route['army_quotations']                        = 'ArmyQuotations/index';
$route['army_quotations/create']                 = 'ArmyQuotations/create';
$route['army_quotations/edit/(:num)']            = 'ArmyQuotations/edit/$1';
$route['army_quotations/view/(:num)']            = 'ArmyQuotations/view/$1';
$route['army_quotations/duplicate/(:num)']       = 'ArmyQuotations/duplicate/$1';
$route['army_quotations/delete/(:num)']          = 'ArmyQuotations/delete/$1';
$route['army_quotations/pdf/(:num)']             = 'ArmyQuotations/pdf/$1';
$route['army_quotations/print/(:num)']           = 'ArmyQuotations/print_view/$1';
$route['army_quotations/send_email/(:num)']      = 'ArmyQuotations/send_email/$1';
$route['army_company_master']                    = 'ArmyCompanyMaster/index';
$route['army_company_master/create']             = 'ArmyCompanyMaster/create';
$route['army_company_master/edit/(:num)']        = 'ArmyCompanyMaster/edit/$1';
$route['army_company_master/toggle_active/(:num)'] = 'ArmyCompanyMaster/toggle_active/$1';
$route['army_company_master/delete/(:num)']      = 'ArmyCompanyMaster/delete/$1';

// Payments
$route['payments']                                   = 'Payments/index';
$route['payments/receive']                           = 'Payments/receive';
$route['payments/pay_supplier']                      = 'Payments/pay_supplier';
$route['payments/get_invoices_by_customer/(:num)']   = 'Payments/get_invoices_by_customer/$1';
$route['payments/get_purchases_by_supplier/(:num)']  = 'Payments/get_purchases_by_supplier/$1';

// WhatsApp — public webhook (Meta calls this directly, no session/CSRF)
$route['whatsapp/webhook']                         = 'WhatsAppWebhook/index';

// WhatsApp Marketing — premium campaign module
$route['marketing']                                = 'Marketing/index';
$route['marketing/campaigns']                      = 'Marketing/campaigns';
$route['marketing/campaign_create']                = 'Marketing/campaign_create';
$route['marketing/campaign_view/(:num)']           = 'Marketing/campaign_view/$1';
$route['marketing/preview_count_ajax']             = 'Marketing/preview_count_ajax';
$route['marketing/templates']                      = 'Marketing/templates';
$route['marketing/template_edit/(:num)']           = 'Marketing/template_edit/$1';
$route['marketing/campaign_start/(:num)']          = 'Marketing/campaign_start/$1';
$route['marketing/campaign_runner/(:num)']         = 'Marketing/campaign_runner/$1';
$route['marketing/process_batch_ajax/(:num)']      = 'Marketing/process_batch_ajax/$1';
$route['marketing/campaign_pause/(:num)']          = 'Marketing/campaign_pause/$1';
$route['marketing/campaign_resume/(:num)']         = 'Marketing/campaign_resume/$1';
$route['marketing/campaign_cancel/(:num)']         = 'Marketing/campaign_cancel/$1';
$route['csrf/refresh']                             = 'Csrf/refresh';
$route['marketing/posters']                        = 'Marketing/posters';
$route['marketing/poster_upload']                  = 'Marketing/poster_upload';
$route['marketing/poster_delete/(:num)']           = 'Marketing/poster_delete/$1';
$route['marketing/poster_generate/(:num)']         = 'Marketing/poster_generate/$1';
$route['marketing/ai_generate_copy_ajax']          = 'Marketing/ai_generate_copy_ajax';
$route['marketing/responses']                      = 'Marketing/responses';
$route['marketing/response_edit/(:num)']           = 'Marketing/response_edit/$1';
$route['marketing/analytics']                      = 'Marketing/analytics';
$route['marketing/campaign_wizard']                = 'Marketing/campaign_wizard';
$route['marketing/wizard_preview_count_ajax']      = 'Marketing/wizard_preview_count_ajax';
$route['marketing/campaign_wizard_submit']         = 'Marketing/campaign_wizard_submit';
$route['marketing/campaign_manual_send/(:num)']            = 'Marketing/campaign_manual_send/$1';
$route['marketing/campaign_manual_mark/(:num)/(:num)/(:any)'] = 'Marketing/campaign_manual_mark/$1/$2/$3';

// Accounting — Chart of Accounts / Journal Entries / Bank & Cash Accounts
$route['accounting']                               = 'Accounting/index';
$route['accounting/chart_of_accounts']             = 'Accounting/chart_of_accounts';
$route['accounting/save_account']                  = 'Accounting/save_account';
$route['accounting/delete_account/(:num)']         = 'Accounting/delete_account/$1';
$route['accounting/journal_entries']               = 'Accounting/journal_entries';
$route['accounting/journal_create']                = 'Accounting/journal_create';
$route['accounting/journal_view/(:num)']           = 'Accounting/journal_view/$1';
$route['accounting/journal_reverse/(:num)']        = 'Accounting/journal_reverse/$1';
$route['accounting/bank_accounts']                 = 'Accounting/bank_accounts';
$route['accounting/save_bank_account']             = 'Accounting/save_bank_account';
$route['accounting/delete_bank_account/(:num)']    = 'Accounting/delete_bank_account/$1';
$route['accounting/payment_mode_mapping']          = 'Accounting/payment_mode_mapping';
$route['accounting/save_payment_mode_mapping']     = 'Accounting/save_payment_mode_mapping';

// Inventory
$route['inventory']                          = 'Inventory/index';
$route['inventory/report']                   = 'Inventory/report';
$route['inventory/movements/(:num)']         = 'Inventory/movements/$1';
$route['inventory/adjust/(:num)']            = 'Inventory/adjust/$1';
$route['inventory/adjustment_log/(:num)']    = 'Inventory/adjustment_log/$1';

// Reports
$route['reports']                       = 'Reports/index';
$route['reports/sales']                 = 'Reports/sales';
$route['reports/purchases']             = 'Reports/purchases';
$route['reports/stock']                 = 'Reports/stock';
$route['reports/stock_location_grid']   = 'Reports/stock_location_grid';
$route['reports/closing_stock_summary'] = 'Reports/closing_stock_summary';
$route['reports/margin_report']         = 'Reports/margin_report';
$route['reports/location_stock']        = 'Reports/location_stock';
$route['reports/stock_movement']        = 'Reports/stock_movement';
$route['reports/rack_stock_report']    = 'Reports/rack_stock_report';
$route['reports/transfers_report']      = 'Reports/transfers_report';
$route['reports/sales_requisition_report'] = 'Reports/sales_requisition_report';
$route['reports/profit_loss']           = 'Reports/profit_loss';
$route['reports/executive_summary']     = 'Reports/executive_summary';
$route['reports/gst']                   = 'Reports/gst';
$route['reports/outstanding']           = 'Reports/outstanding';
$route['reports/party_ledger']          = 'Reports/party_ledger';
$route['reports/party_ledger_print']    = 'Reports/party_ledger_print';
$route['reports/trial_balance']         = 'Reports/trial_balance';
$route['reports/general_ledger']        = 'Reports/general_ledger';
$route['reports/scheme_sales']          = 'Reports/scheme_sales';
// GST reports
$route['reports/gstr1']                 = 'Reports/gstr1';
$route['reports/gstr2']                 = 'Reports/gstr2';
$route['reports/gstr3b']                = 'Reports/gstr3b';
$route['reports/export_gst_excel']      = 'Reports/export_gst_excel';
$route['reports/gstr1_json']            = 'Reports/gstr1_json';
$route['reports/export_gstr1_offline']  = 'Reports/export_gstr1_offline';
$route['reports/hsn_summary_report']    = 'Reports/hsn_summary_report';
$route['reports/export_hsn_summary_report'] = 'Reports/export_hsn_summary_report';
$route['reports/tally_export']          = 'Reports/tally_export';
$route['reports/export_tally_xml']      = 'Reports/export_tally_xml';
$route['reports/gst2b_reconciliation']  = 'Reports/gst2b_reconciliation';
$route['reports/gst2b_clear_flag/(:num)'] = 'Reports/gst2b_clear_flag/$1';
// Analytics & Category Reports
$route['reports/inventory_analytics']   = 'Reports/inventory_analytics';
$route['reports/fast_slow_report']      = 'Reports/fast_slow_report';
$route['reports/category_report']       = 'Reports/category_report';
$route['reports/brand_report']          = 'Reports/brand_report';
$route['reports/supplier_report']        = 'Reports/supplier_report';
$route['reports/media_report']           = 'Reports/media_report';
$route['reports/counter_media_report']   = 'Reports/counter_media_report';
$route['reports/day_end_metrics']        = 'Reports/day_end_metrics';
$route['reports/counter_summary']        = 'Reports/counter_summary';
$route['reports/outlet_sales']           = 'Reports/outlet_sales';
$route['reports/run_report/(:any)']      = 'Reports/run_report/$1';
$route['reports/audit_log']              = 'Reports/audit_log';
$route['reports/credit_sales']           = 'Reports/credit_sales';
$route['reports/refunds_report']         = 'Reports/refunds_report';
$route['reports/wallet_ledger']          = 'Reports/wallet_ledger';
$route['reports/portal_order_funnel']    = 'Reports/portal_order_funnel';
$route['reports/po_grn_variance']        = 'Reports/po_grn_variance';
$route['reports/stock_take_variance']    = 'Reports/stock_take_variance';

// Stock Snapshots
$route['stock_snapshots']                  = 'Stocksnapshots/index';
$route['stock_snapshots/generate']         = 'Stocksnapshots/generate';
$route['stock_snapshots/process_generation'] = 'Stocksnapshots/process_generation';

// Sales Returns (Credit Notes) — GSTR-1 Section 9B
$route['sales_returns']                               = 'SalesReturns/index';
$route['sales_returns/create']                        = 'SalesReturns/create';
$route['sales_returns/save']                          = 'SalesReturns/save';
$route['sales_returns/view/(:num)']                   = 'SalesReturns/view/$1';
$route['sales_returns/print_credit_note/(:num)']      = 'SalesReturns/print_credit_note/$1';
$route['sales_returns/cancel/(:num)']                 = 'SalesReturns/cancel/$1';
$route['sales_returns/get_invoice_ajax/(:num)']       = 'SalesReturns/get_invoice_ajax/$1';
$route['sales_returns/get_invoices_by_customer/(:num)'] = 'SalesReturns/get_invoices_by_customer/$1';

// Purchase Returns (Debit Notes)
$route['purchase_returns']                              = 'PurchaseReturns/index';
$route['purchase_returns/create']                       = 'PurchaseReturns/create';
$route['purchase_returns/save']                         = 'PurchaseReturns/save';
$route['purchase_returns/view/(:num)']                  = 'PurchaseReturns/view/$1';
$route['purchase_returns/print_debit_note/(:num)']      = 'PurchaseReturns/print_debit_note/$1';
$route['purchase_returns/cancel/(:num)']                = 'PurchaseReturns/cancel/$1';
$route['purchase_returns/get_purchase_ajax/(:num)']     = 'PurchaseReturns/get_purchase_ajax/$1';
$route['purchase_returns/get_purchases_by_supplier/(:num)'] = 'PurchaseReturns/get_purchases_by_supplier/$1';
$route['purchase_returns/edit/(:num)']                      = 'PurchaseReturns/edit/$1';
$route['purchase_returns/update/(:num)']                    = 'PurchaseReturns/update/$1';
$route['purchase_returns/search_ajax']                      = 'PurchaseReturns/search_ajax';

// Settings
$route['settings']       = 'Settings/index';
$route['settings/save']  = 'Settings/save';

// Data Reset (admin only)
$route['data_reset']               = 'DataReset/index';
$route['data_reset/execute']       = 'DataReset/execute';
$route['data_reset/execute_store'] = 'DataReset/execute_store';

// User Management (superadmin only)
$route['user_management']                      = 'UserManagement/index';
$route['user_management/add']                  = 'UserManagement/add';
$route['user_management/edit/(:num)']          = 'UserManagement/edit/$1';
$route['user_management/delete/(:num)']        = 'UserManagement/delete/$1';
$route['user_management/toggle_status/(:num)'] = 'UserManagement/toggle_status/$1';

// Order Requests (admin)
$route['order_requests']                          = 'OrderRequests/index';
$route['order_requests/view/(:num)']              = 'OrderRequests/view/$1';
$route['order_requests/update_status/(:num)']     = 'OrderRequests/update_status/$1';
$route['order_requests/convert/(:num)']           = 'OrderRequests/convert/$1';
$route['order_requests/pending_count']            = 'OrderRequests/pending_count';

// Customer Portal
$route['portal']                             = 'Portal/products';
$route['portal/login']                       = 'Portal/login';
$route['portal/logout']                      = 'Portal/logout';
$route['portal/register']                    = 'Portal/register';
$route['portal/send_login_otp']              = 'Portal/send_login_otp';
$route['portal/verify_otp']                  = 'Portal/verify_otp';
$route['portal/dashboard']                   = 'Portal/dashboard';
$route['portal/invoices']                    = 'Portal/invoices';
$route['portal/invoice/(:num)']              = 'Portal/invoice/$1';
$route['portal/products']                    = 'Portal/products';
$route['portal/product_detail/(:num)']       = 'Portal/product_detail/$1';
$route['portal/submit_review']               = 'Portal/submit_review';
$route['portal/order']                       = 'Portal/order';
$route['portal/order_confirmation/(:num)']   = 'Portal/order_confirmation/$1';
$route['portal/my_orders']                   = 'Portal/my_orders';
$route['portal/order_detail/(:num)']         = 'Portal/order_detail/$1';
$route['portal/add_to_cart_ajax']            = 'Portal/add_to_cart_ajax';
$route['portal/update_cart_ajax']            = 'Portal/update_cart_ajax';
$route['portal/remove_from_cart_ajax']       = 'Portal/remove_from_cart_ajax';
$route['portal/get_cart_ajax']               = 'Portal/get_cart_ajax';
$route['portal/search_ajax']                 = 'Portal/search_ajax';
$route['portal/profile']                     = 'Portal/profile';
$route['portal/scan']                        = 'Portal/scan';

// QR Code shortcut — customers scanning QR code at store go here
$route['scan']                               = 'Portal/scan';

// New "Store" storefront — separate from the customer portal above (own prefix,
// own session keys, same underlying tables). See project plan for details.
$route['store']                              = 'Store/home';
$route['store/home']                         = 'Store/home';
$route['store/products']                     = 'Store/products';
$route['store/category/(:num)']              = 'Store/category/$1';
$route['store/product/(:num)']               = 'Store/product_detail/$1';
$route['store/search_ajax']                  = 'Store/search_ajax';
$route['store/submit_review']                = 'Store/submit_review';
$route['store/cart']                         = 'Store/cart';
$route['store/checkout']                     = 'Store/checkout';
$route['store/order_confirmation/(:num)']    = 'Store/order_confirmation/$1';
$route['store/add_to_cart_ajax']             = 'Store/add_to_cart_ajax';
$route['store/update_cart_ajax']             = 'Store/update_cart_ajax';
$route['store/remove_from_cart_ajax']        = 'Store/remove_from_cart_ajax';
$route['store/get_cart_ajax']                = 'Store/get_cart_ajax';
$route['store/login']                        = 'Store/login';
$route['store/logout']                       = 'Store/logout';
$route['store/register']                     = 'Store/register';
$route['store/send_login_otp']               = 'Store/send_login_otp';
$route['store/verify_otp']                   = 'Store/verify_otp';
$route['store/my_orders']                    = 'Store/my_orders';
$route['store/order_detail/(:num)']          = 'Store/order_detail/$1';
$route['store/profile']                      = 'Store/profile';

// Locations (multi-store management)
$route['locations']                          = 'Locations/index';
$route['locations/add']                      = 'Locations/add';
$route['locations/edit/(:num)']              = 'Locations/edit/$1';
$route['locations/stock']                    = 'Locations/stock';
$route['locations/stock/(:num)']             = 'Locations/stock/$1';
$route['locations/switch_location']          = 'Locations/switch_location';

// Financial Years
$route['financial_years']                    = 'FinancialYears/index';
$route['financial_years/add']                = 'FinancialYears/add';
$route['financial_years/close/(:num)']       = 'FinancialYears/close/$1';
$route['financial_years/reopen/(:num)']      = 'FinancialYears/reopen/$1';

// Stock Transfers
$route['stock_transfers']                             = 'StockTransfers/index';
$route['stock_transfers/create']                      = 'StockTransfers/create';
$route['stock_transfers/prev_day_sales_report'] = 'StockTransfers/prev_day_sales_report';
$route['stock_transfers/transfer_in']         = 'StockTransfers/transfer_in';
$route['stock_transfers/transfer_in_view/(:num)'] = 'StockTransfers/transfer_in_view/$1';
$route['stock_transfers/receive/(:num)']      = 'StockTransfers/receive/$1';
$route['stock_transfers/print_transfer_in/(:num)'] = 'StockTransfers/print_transfer_in/$1';
$route['stock_transfers/view/(:num)']                 = 'StockTransfers/view/$1';
$route['stock_transfers/approve/(:num)']              = 'StockTransfers/approve/$1';
$route['stock_transfers/reject/(:num)']               = 'StockTransfers/reject/$1';
$route['stock_transfers/complete/(:num)']             = 'StockTransfers/complete/$1';
$route['stock_transfers/edit/(:num)']                  = 'StockTransfers/edit/$1';
$route['stock_transfers/delete/(:num)']               = 'StockTransfers/delete/$1';
$route['stock_transfers/print_transfer/(:num)']       = 'StockTransfers/print_transfer/$1';
$route['stock_transfers/get_product_stock_ajax']      = 'StockTransfers/get_product_stock_ajax';
$route['stock_transfers/search_grn_ajax']             = 'StockTransfers/search_grn_ajax';
$route['stock_transfers/get_grn_items_ajax/(:num)']   = 'StockTransfers/get_grn_items_ajax/$1';
$route['stock_transfers/migrate_item_remarks']        = 'StockTransfers/migrate_item_remarks';
$route['stock_transfers/get_prev_day_sales_ajax']     = 'StockTransfers/get_prev_day_sales_ajax';
$route['stock_transfers/print/(:num)']                = 'StockTransfers/print_voucher/$1';

// ── Offline Sync (Offline-First POS) ─────────────────────────────────────────
$route['offline_sync/dashboard']     = 'OfflineSync/dashboard';
$route['offline_sync/report']        = 'OfflineSync/report';
$route['offline_sync/get_data']      = 'OfflineSync/get_data';
$route['offline_sync/get_delta']     = 'OfflineSync/get_delta';
$route['offline_sync/push_invoice']  = 'OfflineSync/push_invoice';
$route['offline_sync/push_customer'] = 'OfflineSync/push_customer';
$route['offline_sync/status_json']   = 'OfflineSync/status_json';
$route['offline_sync/retry_failed']  = 'OfflineSync/retry_failed';

// Stock Take (Physical Inventory Count)
$route['stock_take']                 = 'StockTake/index';
$route['stock_take/create']          = 'StockTake/create';
$route['stock_take/count/(:num)']    = 'StockTake/count/$1';
$route['stock_take/save_ajax']           = 'StockTake/save_ajax';
$route['stock_take/lookup_ajax']         = 'StockTake/lookup_ajax';
$route['stock_take/scan_save_ajax']      = 'StockTake/scan_save_ajax';
$route['stock_take/zero_uncounted_ajax'] = 'StockTake/zero_uncounted_ajax';
$route['stock_take/view/(:num)']             = 'StockTake/view/$1';
$route['stock_take/variance_report/(:num)'] = 'StockTake/variance_report/$1';
$route['stock_take/finalize/(:num)']        = 'StockTake/finalize/$1';
$route['stock_take/cancel/(:num)']          = 'StockTake/cancel/$1';
$route['stock_take/import_excel/(:num)']    = 'StockTake/import_excel/$1';
$route['stock_take/download_template']      = 'StockTake/download_template';
$route['stock_take/import_issues/(:num)']   = 'StockTake/import_issues/$1';

// ── Serial numbers / warranty ────────────────────────────────────────────
$route['serials/check_ajax']     = 'Serials/check_ajax';
$route['serials/lookup_ajax']    = 'Serials/lookup_ajax';
$route['serials/available_ajax'] = 'Serials/available_ajax';
$route['serials/dispose_ajax']   = 'Serials/dispose_ajax';

$route['warranty']                       = 'Warranty/index';
$route['warranty/search']                = 'Warranty/search';
$route['warranty/claims']                = 'Warranty/claims';
$route['warranty/claim_create']          = 'Warranty/claim_create';
$route['warranty/claim/(:num)']          = 'Warranty/claim/$1';
$route['warranty/claim_status/(:num)']   = 'Warranty/claim_status/$1';
$route['warranty/claim_replace/(:num)']  = 'Warranty/claim_replace/$1';
$route['warranty/reports']               = 'Warranty/reports';
$route['warranty/reports/(:any)']        = 'Warranty/reports/$1';
$route['warranty/report_export/(:any)']  = 'Warranty/report_export/$1';

// ── Loyalty ──────────────────────────────────────────────────────────────
$route['loyalty']                      = 'Loyalty/index';
$route['loyalty/statement']            = 'Loyalty/statement';
$route['loyalty/statement/(:num)']     = 'Loyalty/statement/$1';
$route['loyalty/earn_redeem']          = 'Loyalty/earn_redeem';

// ── SMS ──────────────────────────────────────────────────────────────────
$route['smsmanager']              = 'Smsmanager/index';
$route['smsmanager/edit/(:num)']  = 'Smsmanager/edit/$1';
$route['smsmanager/test_send']    = 'Smsmanager/test_send';
$route['smsmanager/send_otp']     = 'Smsmanager/send_otp';
$route['smsmanager/verify_otp']   = 'Smsmanager/verify_otp';
$route['smsmanager/logs']         = 'Smsmanager/logs';
$route['loyalty/settings']             = 'Loyalty/settings';
$route['loyalty/adjust']               = 'Loyalty/adjust';
$route['loyalty/run_expiry']           = 'Loyalty/run_expiry';
$route['loyalty/reports']              = 'Loyalty/reports';
$route['loyalty/reports/(:any)']       = 'Loyalty/reports/$1';
$route['loyalty/report_export/(:any)'] = 'Loyalty/report_export/$1';
$route['loyalty/customer_ajax']        = 'Loyalty/customer_ajax';
$route['loyalty/enrol_ajax']           = 'Loyalty/enrol_ajax';

// ── Purchase Order Management System ─────────────────────────────────────────
$route['suppliers/visit_schedule/(:num)']      = 'Suppliers/visit_schedule/$1';
$route['suppliers/log_visit_ajax']             = 'Suppliers/log_visit_ajax';
$route['suppliers/products/(:num)']            = 'Suppliers/products/$1';
$route['suppliers/link_product_ajax']          = 'Suppliers/link_product_ajax';
$route['suppliers/documents/(:num)']           = 'Suppliers/documents/$1';
$route['suppliers/upload_document/(:num)']     = 'Suppliers/upload_document/$1';
$route['suppliers/set_portal_credentials/(:num)'] = 'Suppliers/set_portal_credentials/$1';
$route['suppliers/search_ajax']               = 'Suppliers/search_ajax';

$route['purchase_orders']                                    = 'PurchaseOrders/index';
$route['purchase_orders/create']                             = 'PurchaseOrders/create';
$route['purchase_orders/view/(:num)']                        = 'PurchaseOrders/view/$1';
$route['purchase_orders/edit/(:num)']                        = 'PurchaseOrders/edit/$1';
$route['purchase_orders/approve/(:num)']                     = 'PurchaseOrders/approve/$1';
$route['purchase_orders/do_approve/(:num)']                  = 'PurchaseOrders/do_approve/$1';
$route['purchase_orders/cancel/(:num)']                      = 'PurchaseOrders/cancel/$1';
$route['purchase_orders/print_po/(:num)']                    = 'PurchaseOrders/print_po/$1';
$route['purchase_orders/get_supplier_products_ajax']         = 'PurchaseOrders/get_supplier_products_ajax';
$route['purchase_orders/send_message_ajax']                  = 'PurchaseOrders/send_message_ajax';
$route['purchase_orders/send_approval_email/(:num)']         = 'PurchaseOrders/send_approval_email/$1';
$route['purchase_orders/whatsapp_approval/(:num)']           = 'PurchaseOrders/whatsapp_approval/$1';

$route['grn']                       = 'Grn/index';
$route['grn/create']                = 'Grn/create';
$route['grn/create/(:num)']         = 'Grn/create/$1';
$route['grn/view/(:num)']           = 'Grn/view/$1';
$route['grn/edit/(:num)']           = 'Grn/edit/$1';
$route['grn/accept/(:num)']         = 'Grn/accept/$1';
$route['grn/convert_to_invoice/(:num)'] = 'Grn/convert_to_invoice/$1';
$route['grn/select_po']             = 'Grn/create';

$route['emergency_day_close']              = 'EmergencyDayClose/index';
$route['emergency_day_close/execute']      = 'EmergencyDayClose/execute';
$route['emergency_day_close/history']      = 'EmergencyDayClose/history';
$route['emergency_day_close/view/(:num)']  = 'EmergencyDayClose/view/$1';

$route['rfq']                           = 'Rfq/index';
$route['rfq/create']                    = 'Rfq/create';
$route['rfq/view/(:num)']               = 'Rfq/view/$1';
$route['rfq/compare/(:num)']            = 'Rfq/compare/$1';
$route['rfq/convert_to_po/(:num)']      = 'Rfq/convert_to_po/$1';

$route['purchase_suggestions']          = 'PurchaseSuggestions/index';
$route['purchase_suggestions/ajax']     = 'PurchaseSuggestions/ajax';

// Vendor Portal (hyphenated URL)
$route['vendor-portal']                                  = 'VendorPortal/login';
$route['vendor-portal/login']                            = 'VendorPortal/login';
$route['vendor-portal/logout']                           = 'VendorPortal/logout';
$route['vendor-portal/dashboard']                        = 'VendorPortal/dashboard';
$route['vendor-portal/orders']                           = 'VendorPortal/orders';
$route['vendor-portal/view_po/(:num)']                   = 'VendorPortal/view_po/$1';
$route['vendor-portal/view-po/(:num)']                   = 'VendorPortal/view_po/$1';
$route['vendor-portal/accept_po/(:num)']                 = 'VendorPortal/accept_po/$1';
$route['vendor-portal/accept-po/(:num)']                 = 'VendorPortal/accept_po/$1';
$route['vendor-portal/reject_po/(:num)']                 = 'VendorPortal/reject_po/$1';
$route['vendor-portal/reject-po/(:num)']                 = 'VendorPortal/reject_po/$1';
$route['vendor-portal/send_message']                     = 'VendorPortal/send_message';
$route['vendor-portal/send-message']                     = 'VendorPortal/send_message';
$route['vendor-portal/messages']                         = 'VendorPortal/messages';
$route['vendor-portal/rfqs']                             = 'VendorPortal/rfqs';
$route['vendor-portal/submit_quotation/(:num)']          = 'VendorPortal/submit_quotation/$1';
$route['vendor-portal/submit-quotation/(:num)']          = 'VendorPortal/submit_quotation/$1';
$route['vendor-portal/performance']                      = 'VendorPortal/performance';

// ── Wholesale Billing + GSP Integration ─────────────────────────────────
$route['wholesale_billing']                    = 'WholesaleBilling/index';
$route['wholesale_billing/create']             = 'WholesaleBilling/create';
$route['wholesale_billing/create_ajax']        = 'WholesaleBilling/create_ajax';
$route['wholesale_billing/view/(:num)']        = 'WholesaleBilling/view/$1';
$route['wholesale_billing/print_invoice/(:num)'] = 'WholesaleBilling/print_invoice/$1';
$route['wholesale_billing/generate_ewb_ajax']  = 'WholesaleBilling/generate_ewb_ajax';
$route['wholesale_billing/generate_irn_ajax']  = 'WholesaleBilling/generate_irn_ajax';
$route['wholesale_billing/cancel_ewb_ajax']    = 'WholesaleBilling/cancel_ewb_ajax';
$route['wholesale_billing/cancel_irn_ajax']    = 'WholesaleBilling/cancel_irn_ajax';
$route['wholesale_billing/verify_irn_ajax']     = 'WholesaleBilling/verify_irn_ajax';
$route['wholesale_billing/test_connection_ajax'] = 'WholesaleBilling/test_connection_ajax';
$route['wholesale_billing/api_logs']           = 'WholesaleBilling/api_logs';


// ── Suvasan Clothes storefront (Shop.php) ───────────────────────────────────
$route['shop']                         = 'Shop/listing';
$route['category/(:num)']              = 'Shop/listing/$1';
$route['category/(:num)/(:any)']       = 'Shop/listing/$1';
$route['product/(:any)']               = 'Shop/product/$1';
$route['page/(:any)']                  = 'Shop/page/$1';
$route['wishlist']                     = 'Shop/wishlist';
$route['shop/products_html']           = 'Shop/products_html';
$route['shop/suggest']                 = 'Shop/suggest';
$route['cart']                         = 'Shop/cart';
$route['cart/add']                     = 'Shop/cart_add';
$route['cart/update']                  = 'Shop/cart_update';
$route['cart/coupon']                  = 'Shop/cart_coupon';
$route['checkout']                     = 'Shop/checkout';
$route['checkout/pay/(:any)']          = 'Shop/pay/$1';
$route['checkout/razorpay_verify']     = 'Shop/razorpay_verify';
$route['webhook/razorpay']             = 'Shop/razorpay_webhook';
$route['order/success/(:any)']         = 'Shop/success/$1';
$route['track']                        = 'Shop/track';
$route['account']                      = 'Shop/account';
$route['account/login']                = 'Shop/login';
$route['account/register']             = 'Shop/register';
$route['account/logout']               = 'Shop/logout';
$route['account/order/(:any)']         = 'Shop/account_order/$1';
$route['account/cancel/(:any)']        = 'Shop/account_cancel/$1';
$route['sitemap.xml']                  = 'Shop/sitemap';
$route['robots.txt']                   = 'Shop/robots';

// ── Online store admin (WebAdmin.php, ERP login + mod_webstore) ──────────────
$route['webadmin']                     = 'WebAdmin/index';
$route['webadmin/(:any)']              = 'WebAdmin/$1';
$route['webadmin/(:any)/(:any)']       = 'WebAdmin/$1/$2';
$route['webadmin/(:any)/(:any)/(:any)'] = 'WebAdmin/$1/$2/$3';

// Color Master alias routes
$route['masters/colours'] = 'Garments/colours';
$route['masters/colors']  = 'Garments/colours';
$route['colours']         = 'Garments/colours';
$route['colors']          = 'Garments/colours';
