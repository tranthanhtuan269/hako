#!/usr/bin/env bash
# Pull latest code from GitHub and run Laravel post-deploy steps.
# Cron: 0 */6 * * * /var/www/hakoreview/deploy/auto-update.sh
set -euo pipefail

APP_DIR="/var/www/hakoreview"
LOG_FILE="/var/log/hakoreview-auto-update.log"
LOCK_FILE="/var/run/hakoreview-auto-update.lock"
GIT_CREDENTIALS_FILE="/etc/hakoreview/git-credentials"

mkdir -p "$(dirname "$LOG_FILE")" /etc/hakoreview

exec >>"$LOG_FILE" 2>&1
echo "===== $(date -Is) auto-update start ====="

exec 200>"$LOCK_FILE"
if ! flock -n 200; then
    echo "Another update is already running, skipping."
    exit 0
fi

cd "$APP_DIR"

# Drop incidental permission-only changes on tracked storage gitignore files.
git restore bootstrap/cache/.gitignore storage/ 2>/dev/null || true

LOCAL="$(git rev-parse HEAD)"

if [ -f "$GIT_CREDENTIALS_FILE" ]; then
    # shellcheck disable=SC1090
    source "$GIT_CREDENTIALS_FILE"
    : "${GITHUB_USER:?GITHUB_USER missing in $GIT_CREDENTIALS_FILE}"
    : "${GITHUB_TOKEN:?GITHUB_TOKEN missing in $GIT_CREDENTIALS_FILE}"
    GIT_URL="https://${GITHUB_USER}:${GITHUB_TOKEN}@github.com/tranthanhtuan269/hako.git"
    git fetch "$GIT_URL" main
    REMOTE="$(git rev-parse FETCH_HEAD)"
else
    git fetch origin main
    REMOTE="$(git rev-parse FETCH_HEAD)"
fi

if [ "$LOCAL" = "$REMOTE" ]; then
    echo "Already up to date ($LOCAL)."
    exit 0
fi

git merge --ff-only "$REMOTE"

echo "Updated $LOCAL -> $REMOTE"

if git diff --name-only "$LOCAL" "$REMOTE" | grep -qx 'composer.lock'; then
    echo "composer.lock changed — running composer install..."
    sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
fi

if git diff --name-only "$LOCAL" "$REMOTE" | grep -qE '^database/migrations/'; then
    echo "New migrations detected — running migrate..."
    sudo -u www-data php artisan migrate --force --no-interaction
fi

bash "$APP_DIR/deploy/post-deploy.sh" "$APP_DIR"

echo "===== $(date -Is) auto-update done ====="
