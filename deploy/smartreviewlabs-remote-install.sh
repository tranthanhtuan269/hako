#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/smartreviewlabs"
DB_NAME="smartreviewlabs"
DB_USER="smartreviewlabs"
DB_PASS="${DB_PASS:?DB_PASS required}"
DOMAIN="smartreviewlabs.com"
GIT_BRANCH="ontopcoupon"
GIT_REPO="https://github.com/tranthanhtuan269/hako.git"
ADMIN_EMAIL="admin@smartreviewlabs.com"
ADMIN_PASS="smartreviewlabs.com@"
SCAN_SITE="smartreviewlabs"

export DEBIAN_FRONTEND=noninteractive

echo "==> Installing packages..."
apt-get update -qq
apt-get install -y -qq \
    nginx \
    mysql-server \
    php-fpm \
    php-mysql \
    php-mbstring \
    php-xml \
    php-curl \
    php-zip \
    php-gd \
    php-intl \
    php-bcmath \
    php-tokenizer \
    unzip \
    rsync \
    curl \
    git \
    certbot \
    python3-certbot-nginx

if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

if [[ -S /run/php/php8.5-fpm.sock ]]; then
    PHP_FPM_SOCK="/run/php/php8.5-fpm.sock"
elif [[ -S /run/php/php8.3-fpm.sock ]]; then
    PHP_FPM_SOCK="/run/php/php8.3-fpm.sock"
else
    PHP_FPM_SOCK="$(find /run/php -maxdepth 1 -name 'php*-fpm.sock' 2>/dev/null | head -1)"
fi
if [[ -z "${PHP_FPM_SOCK}" ]]; then
    PHP_FPM_SOCK="/run/php/php8.5-fpm.sock"
fi

systemctl enable --now nginx mysql 2>/dev/null || true
systemctl enable --now "php$(basename "${PHP_FPM_SOCK}" | sed 's/php\|\-fpm.sock//g')-fpm" 2>/dev/null || systemctl enable --now php*-fpm 2>/dev/null || true
systemctl start nginx mysql 2>/dev/null || true

echo "==> Database..."
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "==> Clone app..."
mkdir -p "${APP_DIR}"
if [[ -d "${APP_DIR}/.git" ]]; then
    cd "${APP_DIR}"
    git fetch origin
    git checkout "${GIT_BRANCH}"
    git pull --ff-only origin "${GIT_BRANCH}"
else
    git clone --branch "${GIT_BRANCH}" --depth 1 "${GIT_REPO}" "${APP_DIR}"
fi

cd "${APP_DIR}"

echo "==> Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

if [[ ! -f .env ]]; then
    cp .env.example .env
fi

php artisan key:generate --force
APP_KEY="$(grep '^APP_KEY=' .env | sed 's/^APP_KEY=//')"

cat > .env <<EOF
APP_NAME=SmartReviewLabs
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=https://${DOMAIN}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

SITE_DOMAIN=${DOMAIN}
SITE_URL=https://${DOMAIN}
SITE_DESCRIPTION="${DOMAIN} is your hub for verified promo codes and deals."
SITE_OG_IMAGE=

AFFILIATE_PROGRAM_ENABLED=false
AFFILIATE_COMMISSION_RATE=10
AFFILIATE_MIN_PAYOUT=50
AFFILIATE_CURRENCY=USD
AFFILIATE_COOKIE_DAYS=30

GEMINI_ENABLED=false
GEMINI_API_KEY=
EOF

mkdir -p storage/framework/{cache/data,sessions,views,testing} storage/logs storage/app/public

php artisan migrate --force
php artisan db:seed --class=CategorySeeder --force

php artisan tinker --execute="\App\Models\User::updateOrCreate(['email' => '${ADMIN_EMAIL}'], ['name' => 'Admin', 'password' => bcrypt('${ADMIN_PASS}'), 'is_admin' => true]);"

php artisan tinker --execute="
\App\Models\SiteSetting::set('scan_api_url', 'https://scan.thuoc360.com/api/coupons');
\App\Models\SiteSetting::set('scan_affiliate_signups_api_url', 'https://scan.thuoc360.com/api/affiliate-signups');
\App\Models\SiteSetting::set('scan_api_limit', '20');
\App\Models\SiteSetting::set('scan_site', '${SCAN_SITE}');
"

rm -f public/storage
ln -sfn "${APP_DIR}/storage/app/public" "${APP_DIR}/public/storage"

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan route:cache 2>/dev/null || true

# Ensure FPM can write cache/sessions after any root artisan runs above.
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

NGINX_SITE="/etc/nginx/sites-available/smartreviewlabs"
cat > "${NGINX_SITE}" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};

    root ${APP_DIR}/public;
    index index.php;

    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

ln -sf "${NGINX_SITE}" /etc/nginx/sites-enabled/smartreviewlabs
rm -f /etc/nginx/sites-enabled/default

nginx -t
systemctl reload nginx

chown -R www-data:www-data "${APP_DIR}"

echo "DEPLOY_OK ${DOMAIN}"
echo "PHP_FPM_SOCK=${PHP_FPM_SOCK}"
