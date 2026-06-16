#!/usr/bin/env bash
# Pull latest code from GitHub and run Laravel post-deploy steps.
#
# Usage:
#   ./deploy/auto-update.sh /etc/viktorreview/deploy.env
#
# Cron (viktorreview, every 6 hours):
#   0 */6 * * * /var/www/thuoc360/deploy/auto-update.sh /etc/viktorreview/deploy.env
set -euo pipefail

CONFIG_FILE="${1:-${DEPLOY_CONFIG:-}}"

if [[ -n "$CONFIG_FILE" && -f "$CONFIG_FILE" ]]; then
    # shellcheck disable=SC1090
    source "$CONFIG_FILE"
elif [[ -f /etc/viktorreview/deploy.env ]]; then
    # shellcheck disable=SC1090
    source /etc/viktorreview/deploy.env
elif [[ -f /etc/hakoreview/deploy.env ]]; then
    # shellcheck disable=SC1090
    source /etc/hakoreview/deploy.env
else
    # Legacy hakoreview defaults
    SITE_NAME=hakoreview
    APP_DIR="/var/www/hakoreview"
    GIT_REPO="https://github.com/tranthanhtuan269/hako.git"
    GIT_BRANCH="main"
    LOG_FILE="/var/log/hakoreview-auto-update.log"
    LOCK_FILE="/var/run/hakoreview-auto-update.lock"
    GIT_CREDENTIALS_FILE="/etc/hakoreview/git-credentials"
fi

: "${APP_DIR:?APP_DIR is required}"
SITE_NAME="${SITE_NAME:-app}"
GIT_REPO="${GIT_REPO:-https://github.com/tranthanhtuan269/hako.git}"
GIT_BRANCH="${GIT_BRANCH:-main}"
LOG_FILE="${LOG_FILE:-/var/log/${SITE_NAME}-auto-update.log}"
LOCK_FILE="${LOCK_FILE:-/var/run/${SITE_NAME}-auto-update.lock}"
GIT_CREDENTIALS_FILE="${GIT_CREDENTIALS_FILE:-/etc/${SITE_NAME}/git-credentials}"

mkdir -p "$(dirname "$LOG_FILE")" "/etc/${SITE_NAME}"

exec >>"$LOG_FILE" 2>&1
echo "===== $(date -Is) auto-update start ($SITE_NAME) ====="

exec 200>"$LOCK_FILE"
if ! flock -n 200; then
    echo "Another update is already running, skipping."
    exit 0
fi

cd "$APP_DIR"

if [[ ! -d .git ]]; then
    echo "ERROR: $APP_DIR is not a git repository. Run deploy/setup-git-deploy.sh first."
    exit 1
fi

git restore bootstrap/cache/.gitignore storage/ 2>/dev/null || true

LOCAL="$(git rev-parse HEAD)"

if [[ -f "$GIT_CREDENTIALS_FILE" ]]; then
    # shellcheck disable=SC1090
    source "$GIT_CREDENTIALS_FILE"
    : "${GITHUB_USER:?GITHUB_USER missing in $GIT_CREDENTIALS_FILE}"
    : "${GITHUB_TOKEN:?GITHUB_TOKEN missing in $GIT_CREDENTIALS_FILE}"
    GIT_URL="https://${GITHUB_USER}:${GITHUB_TOKEN}@github.com/tranthanhtuan269/hako.git"
    git fetch "$GIT_URL" "$GIT_BRANCH"
    REMOTE="$(git rev-parse FETCH_HEAD)"
else
    git fetch origin "$GIT_BRANCH"
    REMOTE="$(git rev-parse "origin/${GIT_BRANCH}")"
fi

if [[ "$LOCAL" = "$REMOTE" ]]; then
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
