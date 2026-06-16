#!/usr/bin/env bash
# Run on the server after rsync to /var/www/thuoc360
set -euo pipefail

APP_DIR="${1:-/var/www/thuoc360}"

cd "$APP_DIR"

mkdir -p storage/framework/{cache/data,sessions,views,testing} storage/logs storage/app/public

rm -f public/storage
ln -sfn "$APP_DIR/storage/app/public" "$APP_DIR/public/storage"

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan view:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache

echo "Storage link: $(readlink -f public/storage)"
