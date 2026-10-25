import paramiko

HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)

cmd = 'find /home/u153068796 -name "error_log" -o -name "error.log" 2>/dev/null | head -5'
stdin,stdout,stderr = c.exec_command(cmd)
print("Log files found:")
print(stdout.read().decode())

cmd2 = 'tail -80 /home/u153068796/domains/sandalhouse.sverpsolutions.com/logs/error_log 2>/dev/null'
stdin2,stdout2,stderr2 = c.exec_command(cmd2)
out = stdout2.read().decode()
if out:
    print("Error log:")
    print(out[-3000:])
else:
    cmd3 = 'find /home/u153068796 -name "error_log" 2>/dev/null -exec tail -30 {} \;'
    stdin3,stdout3,stderr3 = c.exec_command(cmd3)
    print(stdout3.read().decode()[-3000:])

c.close()
