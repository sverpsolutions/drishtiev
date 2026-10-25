import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
BASE = '/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
s = c.open_sftp()

# Write a PHP checker to the server
checker = """<?php
$con = new mysqli('localhost','u153068796_sandalhouse','Mci8yo@257','u153068796_sandalhouse');
if($con->connect_error){ die('Connect failed: '.$con->connect_error); }
$res = $con->query('SHOW TABLES');
$tables = [];
while($row = $res->fetch_row()) $tables[] = $row[0];
echo implode("\\n", $tables);
$con->close();
?>"""
fh = s.open(BASE + 'db_check.php', 'w')
fh.write(checker.encode())
fh.close()
s.close()
c.close()
print("Created db_check.php")
print("Visit: https://sandalhouse.sverpsolutions.com/db_check.php")
