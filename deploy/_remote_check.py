import paramiko
import sys

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect("172.96.184.3", port=22, username="root", password="yVAXGVEBEjz3qS3WcfPO", timeout=30)

cmds = [
    "ps aux | grep ilikereview | grep -v grep",
    "test -d /var/www/ilikereview && echo APP_EXISTS || echo APP_MISSING",
    "ls -la /var/www/ilikereview 2>/dev/null | head -5",
    "systemctl is-active nginx mysql php8.3-fpm",
    "curl -sI http://127.0.0.1/ | head -8",
]

for c in cmds:
    stdin, stdout, stderr = ssh.exec_command(c)
    print("---", c)
    print(stdout.read().decode("utf-8", errors="replace"))
    err = stderr.read().decode("utf-8", errors="replace")
    if err.strip():
        print(err)

ssh.close()
