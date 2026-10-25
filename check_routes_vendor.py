import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
BASE = '/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
stdin,stdout,_ = c.exec_command(f'grep -n "vendor" {BASE}application/config/routes.php')
print(stdout.read().decode())
c.close()
