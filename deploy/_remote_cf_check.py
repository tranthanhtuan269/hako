import paramiko
import sys

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect("172.96.184.3", port=22, username="root", password="yVAXGVEBEjz3qS3WcfPO", timeout=30)

cmds = [
    "ss -tlnp | grep -E ':80|:443'",
    "curl -sI http://127.0.0.1/ -H 'Host: ilikereview.com' | head -5",
    "curl -skI https://127.0.0.1/ -H 'Host: ilikereview.com' 2>&1 | head -5",
    "nginx -T 2>/dev/null | grep -E 'listen|server_name' | head -20",
    "command -v certbot && certbot certificates 2>/dev/null || echo 'no certbot certs'",
]

for c in cmds:
    stdin, stdout, stderr = ssh.exec_command(c)
    print("===", c)
    print(stdout.read().decode("utf-8", errors="replace"))
    err = stderr.read().decode("utf-8", errors="replace")
    if err.strip():
        print("STDERR:", err)

ssh.close()
