import paramiko
import secrets
import sys
import time

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

host = "172.96.184.3"
password = "yVAXGVEBEjz3qS3WcfPO"
db_pass = "ILkRv_" + secrets.token_hex(8)

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(host, port=22, username="root", password=password, timeout=30)

sftp = ssh.open_sftp()
sftp.put(r"c:/xampp/htdocs/hako/deploy/ilikereview-remote-install.sh", "/root/ilikereview-remote-install.sh")
sftp.close()

cmd = f"chmod +x /root/ilikereview-remote-install.sh && DB_PASS={db_pass} bash /root/ilikereview-remote-install.sh"
stdin, stdout, stderr = ssh.exec_command(cmd, get_pty=True)

while True:
    if stdout.channel.recv_ready():
        chunk = stdout.channel.recv(8192).decode("utf-8", errors="replace")
        print(chunk, end="", flush=True)
    elif stdout.channel.exit_status_ready():
        while stdout.channel.recv_ready():
            chunk = stdout.channel.recv(8192).decode("utf-8", errors="replace")
            print(chunk, end="", flush=True)
        break
    else:
        time.sleep(1)

code = stdout.channel.recv_exit_status()
print("\nEXIT", code)
err = stderr.read().decode("utf-8", errors="replace")
if err.strip():
    print("STDERR:", err[-4000:])

with open(r"c:/xampp/htdocs/hako/deploy/.ilikereview-deploy.local", "w", encoding="utf-8") as f:
    f.write(f"DB_PASS={db_pass}\n")

ssh.close()
sys.exit(code)
