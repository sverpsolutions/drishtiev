import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
import datetime
today = datetime.date.today().strftime('%Y-%m-%d')
stdin,stdout,_ = c.exec_command(f'tail -60 /home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/application/logs/log-{today}.php 2>/dev/null')
print(stdout.read().decode()[-4000:])
c.close()
