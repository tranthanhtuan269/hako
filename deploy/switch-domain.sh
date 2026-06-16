#!/usr/bin/env bash
set -euo pipefail

APP_KEY_LINE="$(grep '^APP_KEY=' /var/www/thuoc360/.env | head -1 || true)"
cp /tmp/production.env /var/www/thuoc360/.env
if [[ -n "${APP_KEY_LINE}" ]]; then
    sed -i "s|^APP_KEY=.*|${APP_KEY_LINE}|" /var/www/thuoc360/.env
fi

ln -sf /etc/nginx/sites-available/thuoc360 /etc/nginx/sites-enabled/thuoc360
nginx -t
systemctl reload nginx

cd /var/www/thuoc360
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache 2>/dev/null || true

mysql thuoc360 -e "UPDATE users SET email='admin@viktorreview.com' WHERE email='admin@thuoc360.com';" 2>/dev/null || true

echo "HTTP test:"
curl -sI -H 'Host: viktorreview.com' http://127.0.0.1/ | head -6
