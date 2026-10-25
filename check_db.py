import paramiko

HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)

cmd = "mysql -u u153068796_sandalhouse -pMci8yo@257 u153068796_sandalhouse -e 'DESCRIBE suppliers;' 2>/dev/null"
stdin,stdout,stderr = c.exec_command(cmd)
print("suppliers columns:")
print(stdout.read().decode())

cmd2 = "mysql -u u153068796_sandalhouse -pMci8yo@257 u153068796_sandalhouse -e 'SHOW TABLES LIKE \"purchase%\";' 2>/dev/null"
stdin2,stdout2,_ = c.exec_command(cmd2)
print("\nPurchase tables:")
print(stdout2.read().decode())

c.close()
