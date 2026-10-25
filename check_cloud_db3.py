import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)

# Use exact format that worked before (no space between -p and password)
cmd = "mysql -u u153068796_sandalhouse -pMci8yo@257 u153068796_sandalhouse -e 'SHOW TABLES;' 2>&1"
stdin,stdout,_ = c.exec_command(cmd)
out = stdout.read().decode()
print("Cloud tables:")
print(out[:5000])
c.close()
