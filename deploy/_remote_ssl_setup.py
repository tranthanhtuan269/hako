import paramiko
import sys
import time

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect("172.96.184.3", port=22, username="root", password="yVAXGVEBEjz3qS3WcfPO", timeout=30)

def run(cmd, timeout=300):
    print("===", cmd)
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode("utf-8", errors="replace")
    err = stderr.read().decode("utf-8", errors="replace")
    print(out)
    if err.strip():
        print("STDERR:", err)
    return out, err

run("export DEBIAN_FRONTEND=noninteractive; apt-get install -y -qq certbot python3-certbot-nginx")

# Self-signed cert first so Cloudflare Full mode can connect on 443 immediately
run("""mkdir -p /etc/nginx/ssl && openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/nginx/ssl/ilikereview.key \
  -out /etc/nginx/ssl/ilikereview.crt \
  -subj '/CN=ilikereview.com' 2>/dev/null""")

nginx_ssl = r'''server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ilikereview.com www.ilikereview.com;

    ssl_certificate /etc/nginx/ssl/ilikereview.crt;
    ssl_certificate_key /etc/nginx/ssl/ilikereview.key;

    root /var/www/ilikereview/public;
    index index.php;

    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
'''

sftp = ssh.open_sftp()
with sftp.file("/etc/nginx/sites-available/ilikereview-ssl", "w") as f:
    f.write(nginx_ssl)
sftp.close()

run("ln -sf /etc/nginx/sites-available/ilikereview-ssl /etc/nginx/sites-enabled/ilikereview-ssl")
run("nginx -t && systemctl reload nginx")
run("ss -tlnp | grep ':443'")
run("curl -skI https://127.0.0.1/ -H 'Host: ilikereview.com' | head -8")

ssh.close()
print("DONE")
