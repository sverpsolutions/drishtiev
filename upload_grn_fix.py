import paramiko, os
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
BASE = '/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/'
LOCAL = r'C:\xampp\htdocs\sandalhouse\\'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
s = c.open_sftp()

# Upload fixed routes
s.put(LOCAL + 'application\\config\\routes.php', BASE + 'application/config/routes.php')
print('Uploaded: routes.php')

# Check grn views on server
import stat
try:
    s.stat(BASE + 'application/views/grn/select_po.php')
    print('EXISTS: grn/select_po.php')
except:
    print('MISSING: grn/select_po.php - uploading...')
    s.put(LOCAL + 'application\\views\\grn\\select_po.php', BASE + 'application/views/grn/select_po.php')
    print('Uploaded: grn/select_po.php')

# List all grn views on server
stdin,stdout,_ = c.exec_command(f'ls {BASE}application/views/grn/ 2>/dev/null')
print('\nGRN views on server:')
print(stdout.read().decode())

s.close(); c.close()
