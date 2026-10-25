import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)

# Read DB credentials from server's own config file
cmd = "grep -E \"'username'|'password'|'database'\" /home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/application/config/database.php 2>/dev/null"
stdin,stdout,_ = c.exec_command(cmd)
print("Config:", stdout.read().decode())

# Try with the app's credentials from config
cmd2 = "php -r \"define('BASEPATH','x'); \$db['default']['username']=''; \$db['default']['password']=''; include '/home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/application/config/database.php'; echo \$db['default']['username'].'::'.\$db['default']['password'].'::'.\$db['default']['database'];\" 2>/dev/null"
stdin2,stdout2,_ = c.exec_command(cmd2)
print("PHP extracted:", stdout2.read().decode())

c.close()
