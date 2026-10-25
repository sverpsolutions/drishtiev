import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
BASE = '/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)

checks = [
    'application/controllers/VendorPortal.php',
    'application/views/vendor_portal',
    'application/views/vendor_portal/login.php',
    'application/views/vendor_portal/dashboard.php',
    'application/views/vendor_portal/view_po.php',
    'application/views/vendor_portal/submit_quotation.php',
    'application/views/vendor_portal/header.php',
]
s = c.open_sftp()
for path in checks:
    try:
        s.stat(BASE + path)
        print(f'EXISTS  : {path}')
    except:
        print(f'MISSING : {path}')
s.close()

# Check routes
stdin,stdout,_ = c.exec_command(f'grep -n "vendor" {BASE}application/config/routes.php 2>/dev/null')
print('\nRoutes:')
print(stdout.read().decode())

# Check vendor_messages table
stdin2,stdout2,_ = c.exec_command("mysql -u u153068796_sandalhouse -pMci8yo@257 u153068796_sandalhouse -e 'DESCRIBE vendor_messages;' 2>/dev/null")
print('\nvendor_messages table:')
print(stdout2.read().decode())

c.close()
