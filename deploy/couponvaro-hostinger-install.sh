#!/usr/bin/env bash
# Shared-hosting (Hostinger / CageFS) install for couponvaro.com — branch coupontracer
#
# Notes for this host:
# - PHP disables proc_open → composer scripts / artisan storage:link via PHP fail
# - PHP disables symlink() → use shell `ln -s` instead
# - No root/sudo; document root is ~/domains/<domain>/public_html
#
# Usage:
#   DB_NAME=... DB_USER=... DB_PASS=... bash couponvaro-hostinger-install.sh
set -euo pipefail

DOMAIN="couponvaro.com"
HOME_DIR="${HOME:-/home/u458776274}"
DOMAIN_DIR="${HOME_DIR}/domains/${DOMAIN}"
APP_DIR="${DOMAIN_DIR}/hako"
PUBLIC_HTML="${DOMAIN_DIR}/public_html"
GIT_BRANCH="coupontracer"
GIT_REPO="https://github.com/tranthanhtuan269/hako.git"
ADMIN_EMAIL="admin@couponvaro.com"
ADMIN_PASS="couponvaro.com@"
SCAN_SITE="couponvaro"

DB_HOST="127.0.0.1"
DB_NAME="${DB_NAME:?DB_NAME required}"
DB_USER="${DB_USER:?DB_USER required}"
DB_PASS="${DB_PASS:?DB_PASS required}"

PHP_BIN="${PHP_BIN:-$(command -v php)}"
COMPOSER_BIN="${COMPOSER_BIN:-$(command -v composer)}"

echo "==> Domain dir: ${DOMAIN_DIR}"
mkdir -p "${DOMAIN_DIR}"
cd "${DOMAIN_DIR}"

if [[ -d "${PUBLIC_HTML}" && ! -L "${PUBLIC_HTML}" ]]; then
  STAMP="$(date +%Y%m%d%H%M%S)"
  echo "==> Backing up existing public_html -> public_html_backup_${STAMP}"
  mv "${PUBLIC_HTML}" "${DOMAIN_DIR}/public_html_backup_${STAMP}"
elif [[ -L "${PUBLIC_HTML}" ]]; then
  rm -f "${PUBLIC_HTML}"
fi

echo "==> Clone/update hako (${GIT_BRANCH})"
if [[ -d "${APP_DIR}/.git" ]]; then
  cd "${APP_DIR}"
  git fetch origin
  git checkout "${GIT_BRANCH}"
  git pull --ff-only origin "${GIT_BRANCH}"
else
  git clone --branch "${GIT_BRANCH}" --depth 1 "${GIT_REPO}" "${APP_DIR}"
  cd "${APP_DIR}"
fi

echo "==> Composer install (no-scripts: proc_open disabled on CageFS)"
COMPOSER_ALLOW_SUPERUSER=1 "${COMPOSER_BIN}" install \
  --no-dev --optimize-autoloader --no-interaction --no-scripts
COMPOSER_ALLOW_SUPERUSER=1 "${COMPOSER_BIN}" dump-autoload -o --no-interaction --no-scripts

# Build Laravel package manifesto without Composer Process hooks
"${PHP_BIN}" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$manifest = $app->make(Illuminate\Foundation\PackageManifest::class);
$ref = new ReflectionClass($manifest);
$m = $ref->getMethod("build");
$m->setAccessible(true);
$m->invoke($manifest);
echo "packages_manifest_ok\n";
'

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

"${PHP_BIN}" artisan key:generate --force
APP_KEY="$(grep '^APP_KEY=' .env | sed 's/^APP_KEY=//')"

cat > .env <<EOF
APP_NAME=CouponVaro
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=https://${DOMAIN}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
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

mkdir -p storage/framework/{cache/data,sessions,views,testing} storage/logs storage/app/public bootstrap/cache
chmod -R u+rwX storage bootstrap/cache

echo "==> Reset MySQL schema for Laravel"
mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" -e "SET FOREIGN_KEY_CHECKS=0;"
mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" -N -e \
  "SELECT CONCAT('DROP TABLE IF EXISTS \\\`', table_name, '\\\`;') FROM information_schema.tables WHERE table_schema='${DB_NAME}';" \
  | mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" || true
mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" -e "SET FOREIGN_KEY_CHECKS=1;"

"${PHP_BIN}" artisan migrate --force
"${PHP_BIN}" artisan db:seed --class=CategorySeeder --force || true

"${PHP_BIN}" artisan tinker --execute="\\App\\Models\\User::updateOrCreate(['email' => '${ADMIN_EMAIL}'], ['name' => 'Admin', 'password' => bcrypt('${ADMIN_PASS}'), 'is_admin' => true]);"

"${PHP_BIN}" artisan tinker --execute="
\\App\\Models\\SiteSetting::set('scan_api_url', 'https://scan.thuoc360.com/api/coupons');
\\App\\Models\\SiteSetting::set('scan_affiliate_signups_api_url', 'https://scan.thuoc360.com/api/affiliate-signups');
\\App\\Models\\SiteSetting::set('scan_api_limit', '20');
\\App\\Models\\SiteSetting::set('scan_site', '${SCAN_SITE}');
"

# Shell symlinks (PHP symlink() is disabled on this host)
rm -rf "${PUBLIC_HTML}"
ln -sfn "${APP_DIR}/public" "${PUBLIC_HTML}"
rm -f "${APP_DIR}/public/storage"
ln -sfn "${APP_DIR}/storage/app/public" "${APP_DIR}/public/storage"

cat > "${APP_DIR}/public/.htaccess" <<'HTACCESS'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS

"${PHP_BIN}" artisan config:cache
"${PHP_BIN}" artisan route:cache || true
"${PHP_BIN}" artisan view:cache || true
chmod -R u+rwX storage bootstrap/cache

echo "DEPLOY_OK ${DOMAIN}"
echo "APP_DIR=${APP_DIR}"
echo "PUBLIC_HTML=${PUBLIC_HTML} -> $(readlink "${PUBLIC_HTML}")"
echo "ADMIN=${ADMIN_EMAIL}"
