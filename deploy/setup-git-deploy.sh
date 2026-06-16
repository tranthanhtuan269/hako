#!/usr/bin/env bash
# One-time: turn an existing app directory into a git checkout (viktorreview / thuoc360).
# Usage: sudo bash deploy/setup-git-deploy.sh /etc/viktorreview/deploy.env
set -euo pipefail

CONFIG_FILE="${1:-/etc/viktorreview/deploy.env}"
if [[ ! -f "$CONFIG_FILE" ]]; then
    echo "Missing config: $CONFIG_FILE" >&2
    exit 1
fi

# shellcheck disable=SC1090
source "$CONFIG_FILE"

: "${APP_DIR:?}"
GIT_REPO="${GIT_REPO:-https://github.com/tranthanhtuan269/hako.git}"
GIT_BRANCH="${GIT_BRANCH:-main}"

if [[ ! -f "$APP_DIR/.env" ]]; then
    echo "ERROR: $APP_DIR/.env not found. Configure the app before git setup." >&2
    exit 1
fi

ENV_BACKUP="$(mktemp)"
cp "$APP_DIR/.env" "$ENV_BACKUP"

cd "$APP_DIR"

if [[ -d .git ]]; then
    echo "Git already initialized in $APP_DIR"
    git remote set-url origin "$GIT_REPO" 2>/dev/null || git remote add origin "$GIT_REPO"
    git fetch origin "$GIT_BRANCH"
    git checkout -B "$GIT_BRANCH" "origin/$GIT_BRANCH"
    git reset --hard "origin/$GIT_BRANCH"
else
    echo "Initializing git in $APP_DIR..."
    git init
    git remote add origin "$GIT_REPO"
    git fetch origin "$GIT_BRANCH"
    git checkout -B "$GIT_BRANCH" "origin/$GIT_BRANCH"
fi

cp "$ENV_BACKUP" "$APP_DIR/.env"
rm -f "$ENV_BACKUP"

mkdir -p storage/framework/{cache/data,sessions,views,testing} storage/logs storage/app/public

if [[ ! -L public/storage ]]; then
    rm -rf public/storage
    ln -sfn "$APP_DIR/storage/app/public" "$APP_DIR/public/storage"
fi

if [[ ! -d vendor ]]; then
    sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
fi

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

bash "$APP_DIR/deploy/post-deploy.sh" "$APP_DIR"

echo "Git deploy ready at $APP_DIR ($(git rev-parse --short HEAD))"
