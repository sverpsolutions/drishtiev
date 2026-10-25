import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
DB = 'u153068796_sandalhouse'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)

indexes = [
    # GRN indexes
    "ALTER TABLE grn ADD INDEX IF NOT EXISTS idx_supplier_id (supplier_id)",
    "ALTER TABLE grn ADD INDEX IF NOT EXISTS idx_location_id (location_id)",
    "ALTER TABLE grn ADD INDEX IF NOT EXISTS idx_status (status)",
    "ALTER TABLE grn ADD INDEX IF NOT EXISTS idx_received_date (received_date)",
    # purchase_orders indexes
    "ALTER TABLE purchase_orders ADD INDEX IF NOT EXISTS idx_status (status)",
    # invoice_items
    "ALTER TABLE invoice_items ADD INDEX IF NOT EXISTS idx_product_id (product_id)",
    # stock_ledger
    "ALTER TABLE stock_ledger ADD INDEX IF NOT EXISTS idx_product_id (product_id)",
    "ALTER TABLE stock_ledger ADD INDEX IF NOT EXISTS idx_txn_type (txn_type)",
    # vendor_messages
    "ALTER TABLE vendor_messages ADD INDEX IF NOT EXISTS idx_is_read (is_read)",
]

for sql in indexes:
    cmd = f'mysql -u {DB} -pMci8yo@257 {DB} -e "{sql}" 2>/dev/null'
    stdin,stdout,stderr = c.exec_command(cmd)
    err = stderr.read().decode().strip()
    if err and 'Duplicate' not in err:
        print(f'ERR: {sql[:60]}... => {err}')
    else:
        print(f'OK : {sql[:70]}')

c.close()
