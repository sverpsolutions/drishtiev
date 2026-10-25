import paramiko, subprocess

# ── Cloud tables ──────────────────────────────────────────────────────────────
HOST,PORT,USER,PASS = '82.25.106.27',65002,'u153068796','Mci8yo@257'
DB = 'u153068796_sandalhouse'
c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
stdin,stdout,_ = c.exec_command(f"mysql -u {DB} -pMci8yo@257 {DB} -e 'SHOW TABLES;' 2>/dev/null")
cloud_tables = set(stdout.read().decode().strip().split('\n')[1:])

# ── Local tables ──────────────────────────────────────────────────────────────
result = subprocess.run(
    ['C:\\xampp\\mysql\\bin\\mysql.exe', '-u', 'root', '-e', 'SHOW TABLES;', 'sandalhouse'],
    capture_output=True, text=True
)
local_raw = result.stdout.strip()
local_tables = set(local_raw.split('\n')[1:]) if local_raw else set()

if not local_tables:
    # try without password
    result2 = subprocess.run(
        ['C:\\xampp\\mysql\\bin\\mysql.exe', '-u', 'root', '--password=', '-e', 'SHOW DATABASES;'],
        capture_output=True, text=True
    )
    print("Local MySQL databases:", result2.stdout)
    print("Local stderr:", result2.stderr)

print(f"\nCloud tables ({len(cloud_tables)}):")
for t in sorted(cloud_tables): print(' ', t)

print(f"\nLocal tables ({len(local_tables)}):")
for t in sorted(local_tables): print(' ', t)

only_cloud = cloud_tables - local_tables
only_local = local_tables - cloud_tables
print(f"\nOn CLOUD only (missing locally): {sorted(only_cloud)}")
print(f"On LOCAL only (missing on cloud): {sorted(only_local)}")
print(f"Common: {len(cloud_tables & local_tables)}")

c.close()
