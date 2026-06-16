#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/thuoc360"
DB_NAME="thuoc360"
DB_USER="thuoc360"
DB_PASS="${DB_PASS:?DB_PASS required}"

export DEBIAN_FRONTEND=noninteractive

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
    git

if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

PHP_FPM_SOCK="$(find /run/php -maxdepth 1 -name 'php*-fpm.sock' 2>/dev/null | head -1)"
if [[ -z "${PHP_FPM_SOCK}" ]]; then
    PHP_FPM_SOCK="/run/php/php8.3-fpm.sock"
fi

systemctl enable --now nginx mysql "php$(basename "${PHP_FPM_SOCK}" | sed 's/php\|\-fpm.sock//g')-fpm" 2>/dev/null || systemctl enable --now php*-fpm 2>/dev/null || true
systemctl start nginx mysql 2>/dev/null || true

mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

mkdir -p "${APP_DIR}"
chown -R www-data:www-data "${APP_DIR}" 2>/dev/null || true

echo "PHP_FPM_SOCK=${PHP_FPM_SOCK}"
