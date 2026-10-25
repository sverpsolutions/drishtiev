import paramiko

host, port, user, pwd = '82.25.106.27', 65002, 'u153068796', 'Mci8yo@257'
base_local = r'C:\xampp\htdocs\sandalhouse'
remote_base = '/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html'

files = [
    'application/views/billing/create.php',
    'application/controllers/Products.php',
    'application/controllers/Customers.php',
]

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(host, port=port, username=user, password=pwd)
sftp = ssh.open_sftp()

for f in files:
    local  = base_local + '\\' + f.replace('/', '\\')
    remote = remote_base + '/' + f
    sftp.put(local, remote)
    print('Uploaded: ' + f)

sftp.close()
ssh.close()
print('Done.')
