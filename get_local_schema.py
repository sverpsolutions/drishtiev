import subprocess

tables_to_dump = [
    'purchase_orders','purchase_order_items','purchase_approvals','po_approval_config',
    'grn','grn_items','rfq','rfq_items','rfq_quotations','rfq_suppliers',
    'vendor_messages','vendor_performance','supplier_products',
    'supplier_visit_schedules','supplier_visit_logs','supplier_documents',
]

# Check which of these exist locally
result = subprocess.run(
    ['C:\\xampp\\mysql\\bin\\mysql.exe','-u','root','sandalhouse','-e','SHOW TABLES;'],
    capture_output=True, text=True
)
local_tables = set(result.stdout.strip().split('\n')[1:])
print("Local has:", sorted(local_tables))
print("\nMissing locally:", [t for t in tables_to_dump if t not in local_tables])
