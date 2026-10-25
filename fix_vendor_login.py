import paramiko, os
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
BASE = '/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/'
LOCAL = r'C:\xampp\htdocs\sandalhouse\\'

c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
s = c.open_sftp()

files = [
    'application/controllers/Suppliers.php',
    'application/models/OfflineSync_model.php',
]
for f in files:
    s.put(LOCAL + f.replace('/', os.sep), BASE + f)
    print('Uploaded:', f)

s.close()

# Ask user to re-save from form, but also fix DB directly for existing supplier
# The password was NULL — update it via PHP password_hash equivalent
# We'll create a small PHP fixer script on server
fixer = '''<?php
$pwd = "vendor123"; // temporary password
$hash = password_hash($pwd, PASSWORD_DEFAULT);
$con = mysqli_connect("localhost","u153068796_sandalhouse","Mci8yo@257","u153068796_sandalhouse");
mysqli_query($con, "UPDATE suppliers SET portal_password='" . mysqli_real_escape_string($con,$hash) . "' WHERE portal_username IS NOT NULL AND portal_username != ''");
echo "Done. Rows: " . mysqli_affected_rows($con);
echo "<br>Login with your portal_username and password: vendor123";
echo "<br>Then immediately go to edit supplier and set a proper password.";
?>'''

with s2 := c.open_sftp() if False else c.open_sftp():
    f2 = s2.open(BASE + 'fix_portal_pwd.php', 'w')
    f2.write(fixer.encode())
    f2.close()
    print('\nCreated fix_portal_pwd.php on server')
    print('Visit: https://sandalhouse.sverpsolutions.com/fix_portal_pwd.php')
    print('Then DELETE the file after use.')

c.close()
