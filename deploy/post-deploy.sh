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

# Keep robots.txt sitemap URL aligned with SITE_URL / APP_URL (static file
# is served by nginx before Laravel; regenerate on each deploy).
SITE_URL="$(grep -E '^SITE_URL=' .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
if [[ -z "${SITE_URL}" ]]; then
    SITE_URL="$(grep -E '^APP_URL=' .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
fi
SITE_URL="${SITE_URL%/}"
if [[ -n "${SITE_URL}" ]]; then
    cat > public/robots.txt <<ROBOTS
User-agent: *
Allow: /
Disallow: /admin
Disallow: /admin/
Disallow: /login
Disallow: /register
Disallow: /dashboard
Disallow: /coupons/*/reveal
Disallow: /coupons/*/go

Sitemap: ${SITE_URL}/sitemap.xml
ROBOTS
    chown www-data:www-data public/robots.txt 2>/dev/null || true
fi

php artisan view:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache

echo "Storage link: $(readlink -f public/storage)"
