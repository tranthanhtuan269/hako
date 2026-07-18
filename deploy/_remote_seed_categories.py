import paramiko
import sys

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect("172.96.184.3", port=22, username="root", password="yVAXGVEBEjz3qS3WcfPO", timeout=30)

cmds = [
    "cd /var/www/ilikereview && php artisan db:seed --class=CategorySeeder --force",
    "mysql -u ilikereview -pILkRv_63b28b8daa562e28 ilikereview -e \"SELECT COUNT(*) AS categories FROM categories; SELECT name FROM categories ORDER BY sort_order LIMIT 5;\"",
]

for c in cmds:
    stdin, stdout, stderr = ssh.exec_command(c, timeout=120)
    print("===", c)
    print(stdout.read().decode("utf-8", errors="replace"))
    err = stderr.read().decode("utf-8", errors="replace")
    if err.strip():
        print("STDERR:", err)

ssh.close()
