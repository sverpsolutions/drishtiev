import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)

# Check database.php on server for current password
cmd = "cat /home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/application/config/database.php 2>/dev/null | grep -E 'username|password|database'"
stdin,stdout,_ = c.exec_command(cmd)
print("DB config on server:")
print(stdout.read().decode())

# List all MySQL config files that might have credentials
cmd2 = "find /home/u153068796 -name '.my.cnf' 2>/dev/null; find /etc -name 'my.cnf' 2>/dev/null | head -3"
stdin2,stdout2,_ = c.exec_command(cmd2)
print("MySQL config files:", stdout2.read().decode())

c.close()
