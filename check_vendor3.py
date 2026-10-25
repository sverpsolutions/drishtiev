import paramiko, os
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
BASE = '/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
s = c.open_sftp()

# List all vendor_portal views on server
stdin,stdout,_ = c.exec_command(f'ls {BASE}application/views/vendor_portal/ 2>/dev/null')
print("Server vendor_portal views:")
print(stdout.read().decode())

# List local
local_path = r'C:\xampp\htdocs\sandalhouse\application\views\vendor_portal'
print("Local vendor_portal views:")
if os.path.exists(local_path):
    for f in os.listdir(local_path):
        print(' ', f)
else:
    print("  (directory not found locally)")

s.close()
c.close()
