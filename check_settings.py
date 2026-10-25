import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
stdin,stdout,_ = c.exec_command("mysql -u u153068796_sandalhouse -pMci8yo@257 u153068796_sandalhouse -e 'SELECT * FROM company_settings LIMIT 5;' 2>/dev/null")
print(stdout.read().decode())
stdin2,stdout2,_ = c.exec_command("mysql -u u153068796_sandalhouse -pMci8yo@257 u153068796_sandalhouse -e 'DESCRIBE company_settings;' 2>/dev/null")
print(stdout2.read().decode())
c.close()
