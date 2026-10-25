import paramiko
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)

# Try different credential combos
combos = [
    ("u153068796_sandal", "Mci8yo@257", "u153068796_sandalhouse"),
    ("u153068796", "Mci8yo@257", "u153068796_sandalhouse"),
    ("u153068796_sandalho", "Mci8yo@257", "u153068796_sandalhouse"),
]
for db_user, db_pass, db_name in combos:
    cmd = f"mysql -u'{db_user}' -p'{db_pass}' '{db_name}' -e 'SELECT COUNT(*) FROM products;' 2>&1"
    stdin,stdout,_ = c.exec_command(cmd)
    out = stdout.read().decode().strip()
    print(f"User={db_user}: {out[:100]}")

# Also check what MySQL users exist
cmd2 = "mysql -u root -e 'SELECT User,Host FROM mysql.user;' 2>&1 | head -20"
stdin2,stdout2,_ = c.exec_command(cmd2)
print("\nMySQL users:", stdout2.read().decode()[:500])

c.close()
