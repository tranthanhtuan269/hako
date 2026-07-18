import paramiko
import sys

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect("172.96.184.3", port=22, username="root", password="yVAXGVEBEjz3qS3WcfPO", timeout=30)

cmds = [
    "tail -80 /var/www/ilikereview/storage/logs/laravel.log 2>/dev/null || ls -la /var/www/ilikereview/storage/logs/",
    "ls -la /var/www/ilikereview/storage/logs/",
    "namei -l /var/www/ilikereview/storage/logs/laravel.log 2>/dev/null | tail -5",
]

for c in cmds:
    stdin, stdout, stderr = ssh.exec_command(c)
    print("===", c)
    print(stdout.read().decode("utf-8", errors="replace"))
    print(stderr.read().decode("utf-8", errors="replace"))

ssh.close()
