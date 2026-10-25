import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
BASE = '/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)

# Check what's actually stored for portal credentials
stdin,stdout,_ = c.exec_command("mysql -u u153068796_sandalhouse -pMci8yo@257 u153068796_sandalhouse -e 'SELECT id, name, portal_username, portal_password, portal_active FROM suppliers WHERE portal_username IS NOT NULL AND portal_username != \"\";' 2>/dev/null")
print("Suppliers with portal access:")
print(stdout.read().decode())

# Check the CI log for recent errors
import datetime
today = datetime.date.today().strftime('%Y-%m-%d')
stdin2,stdout2,_ = c.exec_command(f'tail -30 {BASE}application/logs/log-{today}.php 2>/dev/null')
print("\nRecent CI errors:")
print(stdout2.read().decode()[-3000:])

c.close()
