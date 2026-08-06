#!/usr/bin/env bash
# Rebuild Laravel caches and fix storage ownership for all apps under /var/www.
# Run on the VPS as root after deploying code that adds/changes routes.
set -euo pipefail

WWW_ROOT="${WWW_ROOT:-/var/www}"

for artisan in "${WWW_ROOT}"/*/artisan; do
    [[ -f "${artisan}" ]] || continue
    app_dir="$(dirname "${artisan}")"
    site="$(basename "${app_dir}")"
    echo "==> ${site}"

    chown -R www-data:www-data "${app_dir}/storage" "${app_dir}/bootstrap/cache"
    chmod -R ug+rwx "${app_dir}/storage" "${app_dir}/bootstrap/cache"

    sudo -u www-data php "${app_dir}/artisan" optimize:clear
    sudo -u www-data php "${app_dir}/artisan" config:cache
    sudo -u www-data php "${app_dir}/artisan" view:cache
    sudo -u www-data php "${app_dir}/artisan" route:cache

    # Artisan may create root-owned files if invoked incorrectly; normalize again.
    chown -R www-data:www-data "${app_dir}/storage" "${app_dir}/bootstrap/cache"
    echo "OK ${site}"
done

echo "ALL_DONE"
