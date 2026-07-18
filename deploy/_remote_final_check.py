import paramiko
import sys

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect("172.96.184.3", port=22, username="root", password="yVAXGVEBEjz3qS3WcfPO", timeout=30)

cmds = [
    "cd /var/www/ilikereview && git config --global --add safe.directory /var/www/ilikereview && git rev-parse --short HEAD && git branch --show-current",
    "curl -s -H 'Host: ilikereview.com' http://127.0.0.1/ | grep -o '<title>[^<]*</title>' | head -1",
    "curl -s -H 'Host: ilikereview.com' http://127.0.0.1/admin | grep -o '<title>[^<]*</title>' | head -1",
    "mysql -u ilikereview -pILkRv_63b28b8daa562e28 ilikereview -e \"SELECT COUNT(*) AS stores FROM stores; SELECT COUNT(*) AS coupons FROM coupons;\"",
]

for c in cmds:
    stdin, stdout, stderr = ssh.exec_command(c)
    print("===", c)
    print(stdout.read().decode("utf-8", errors="replace"))
    err = stderr.read().decode("utf-8", errors="replace")
    if err.strip():
        print("STDERR:", err)

ssh.close()
