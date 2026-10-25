import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
DB = 'u153068796_sandalhouse'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)

# Check cloud tables
cmd = f"mysql -u{DB} -pMci8yo@257 {DB} -e 'SHOW TABLES;' 2>&1"
stdin,stdout,_ = c.exec_command(cmd)
out = stdout.read().decode()
print("Cloud DB output:")
print(out[:3000])

# Count rows in key tables if they exist
cmd2 = f"mysql -u{DB} -pMci8yo@257 {DB} -e 'SELECT COUNT(*) FROM products; SELECT COUNT(*) FROM invoices; SELECT COUNT(*) FROM purchase_orders;' 2>&1"
stdin2,stdout2,_ = c.exec_command(cmd2)
print("\nKey table counts:")
print(stdout2.read().decode())

c.close()
