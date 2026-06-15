#!/usr/bin/env bash
# Run on the server after rsync to /var/www/thuoc360
set -euo pipefail

APP_DIR="${1:-/var/www/thuoc360}"

cd "$APP_DIR"

# Never copy a broken absolute storage symlink from another machine.
rm -f public/storage
ln -sfn "$APP_DIR/storage/app/public" "$APP_DIR/public/storage"

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan view:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache

echo "Storage link: $(readlink -f public/storage)"
