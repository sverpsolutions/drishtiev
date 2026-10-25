import paramiko, os
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
BASE = '/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/'
LOCAL = r'C:\xampp\htdocs\sandalhouse\\'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
s = c.open_sftp()
files = [
    'application/config/config.php',
    'application/models/Purchase_return_model.php',
    'application/models/Sales_return_model.php',
]
for f in files:
    s.put(LOCAL + f.replace('/', os.sep), BASE + f)
    print('Uploaded:', f)
s.close(); c.close()
