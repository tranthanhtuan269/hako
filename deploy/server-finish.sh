#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/thuoc360"
PHP_FPM_SOCK="${PHP_FPM_SOCK:-/run/php/php8.3-fpm.sock}"

cd "${APP_DIR}"

mkdir -p storage/framework/{cache/data,sessions,views,testing} storage/logs storage/app/public

composer install --no-dev --optimize-autoloader --no-interaction

php artisan key:generate --force 2>/dev/null || true
php artisan migrate --seed --force
php artisan storage:link 2>/dev/null || ln -sfn "${APP_DIR}/storage/app/public" "${APP_DIR}/public/storage"

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan route:cache 2>/dev/null || true

NGINX_SITE="/etc/nginx/sites-available/thuoc360"
cp deploy/nginx.conf "${NGINX_SITE}"
sed -i "s|unix:/run/php/php8.5-fpm.sock|unix:${PHP_FPM_SOCK}|g" "${NGINX_SITE}"
sed -i 's/server_name thuoc360.com www.thuoc360.com;/server_name viktorreview.com www.viktorreview.com 198.252.109.50;/' "${NGINX_SITE}"

ln -sf "${NGINX_SITE}" /etc/nginx/sites-enabled/thuoc360
rm -f /etc/nginx/sites-enabled/default

nginx -t
systemctl reload nginx

echo "DEPLOY_OK"
