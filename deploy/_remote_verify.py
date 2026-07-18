import paramiko
import sys

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect("172.96.184.3", port=22, username="root", password="yVAXGVEBEjz3qS3WcfPO", timeout=30)

cmds = [
    "cd /var/www/ilikereview && git rev-parse --short HEAD && git branch --show-current",
    "mysql -u ilikereview -e \"SELECT email,is_admin FROM ilikereview.users LIMIT 5\" 2>/dev/null || mysql -e \"SELECT email,is_admin FROM ilikereview.users LIMIT 5\"",
    "curl -sI -H 'Host: ilikereview.com' http://127.0.0.1/login | head -8",
    "curl -s -H 'Host: ilikereview.com' http://127.0.0.1/login | grep -o '<title>[^<]*</title>' | head -1",
]

for c in cmds:
    stdin, stdout, stderr = ssh.exec_command(c)
    print("---", c)
    print(stdout.read().decode("utf-8", errors="replace"))
    err = stderr.read().decode("utf-8", errors="replace")
    if err.strip():
        print(err)

ssh.close()
