import paramiko
import sys

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect("172.96.184.3", port=22, username="root", password="yVAXGVEBEjz3qS3WcfPO", timeout=30)

cmds = [
    "cd /var/www/ilikereview && grep '^APP_KEY=' .env || echo 'APP_KEY missing'",
    "cd /var/www/ilikereview && php artisan key:generate --force",
    "cd /var/www/ilikereview && php artisan config:clear",
    "cd /var/www/ilikereview && sudo -u www-data php artisan config:cache",
    "curl -sI -H 'Host: ilikereview.com' http://127.0.0.1/login | head -5",
    "curl -s -H 'Host: ilikereview.com' http://127.0.0.1/login | grep -o '<title>[^<]*</title>' | head -1",
    "ss -tlnp | grep -E ':80|:443' || netstat -tlnp | grep -E ':80|:443'",
    "ufw status 2>/dev/null || echo 'ufw not active'",
    "iptables -L INPUT -n 2>/dev/null | head -15 || true",
]

for c in cmds:
    stdin, stdout, stderr = ssh.exec_command(c)
    print("===", c)
    out = stdout.read().decode("utf-8", errors="replace")
    err = stderr.read().decode("utf-8", errors="replace")
    print(out)
    if err.strip():
        print("STDERR:", err)

ssh.close()
