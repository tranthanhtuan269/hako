import paramiko
import sys

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect("172.96.184.3", port=22, username="root", password="yVAXGVEBEjz3qS3WcfPO", timeout=30)

cmd = (
    "certbot --nginx -d ilikereview.com -d www.ilikereview.com "
    "--non-interactive --agree-tos -m admin@ilikereview.com --redirect 2>&1"
)
stdin, stdout, stderr = ssh.exec_command(cmd, timeout=300)
print(stdout.read().decode("utf-8", errors="replace"))
print(stderr.read().decode("utf-8", errors="replace"))

stdin, stdout, stderr = ssh.exec_command("certbot certificates 2>&1")
print(stdout.read().decode("utf-8", errors="replace"))

ssh.close()
