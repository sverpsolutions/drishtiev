import paramiko, os
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
BASE = '/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/'
LOCAL = r'C:\xampp\htdocs\sandalhouse\\'

c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
s = c.open_sftp()

# Upload fixed controller and model
files = [
    'application/controllers/Suppliers.php',
    'application/models/OfflineSync_model.php',
]
for f in files:
    s.put(LOCAL + f.replace('/', os.sep), BASE + f)
    print('Uploaded:', f)

# Create a one-time password fixer PHP file
fixer = """<?php
$pwd = "vendor123";
$hash = password_hash($pwd, PASSWORD_DEFAULT);
$con = mysqli_connect("localhost","u153068796_sandalhouse","Mci8yo@257","u153068796_sandalhouse");
$res = mysqli_query($con, "UPDATE suppliers SET portal_password='".mysqli_real_escape_string($con,$hash)."' WHERE portal_username IS NOT NULL AND portal_username != ''");
echo "<b>Done.</b><br>Rows updated: ".mysqli_affected_rows($con);
echo "<br><br>All portal suppliers can now log in with password: <b>vendor123</b>";
echo "<br>After login works, edit the supplier to set a proper password.";
echo "<br><br><b style='color:red'>DELETE this file after use: fix_portal_pwd.php</b>";
?>"""

fh = s.open(BASE + 'fix_portal_pwd.php', 'w')
fh.write(fixer.encode())
fh.close()
s.close()
c.close()

print('\nDone! Now visit:')
print('https://sandalhouse.sverpsolutions.com/fix_portal_pwd.php')
print('(Delete the file after use)')
