import paramiko

HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)

# Check CI log
cmd = 'tail -50 /home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/application/logs/log-' + __import__('datetime').date.today().strftime('%Y-%m-%d') + '.php 2>/dev/null'
stdin,stdout,stderr = c.exec_command(cmd)
out = stdout.read().decode()
print("CI Log today:")
print(out[-4000:] if out else "(empty)")

# Also check yesterday
cmd2 = 'ls /home/u153068796/domains/sandalhouse.sverpsolutions.com/public_html/application/logs/ 2>/dev/null | tail -5'
stdin2,stdout2,_ = c.exec_command(cmd2)
print("\nLog files:")
print(stdout2.read().decode())

c.close()
