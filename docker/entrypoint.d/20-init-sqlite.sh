#!/bin/sh
# 20-init-sqlite.sh
# Runs before AUTORUN Laravel automations (50-laravel-automations.sh).
# Creates the SQLite database file and required storage directories when the
# persistent volume is mounted but empty (e.g. first boot on Coolify).

set -e

DB_PATH="${DB_DATABASE:-/var/www/html/storage/app/database.sqlite}"
STORAGE_ROOT="/var/www/html/storage/app"

echo "[20-init-sqlite] Ensuring storage directories exist..."
mkdir -p "${STORAGE_ROOT}/public"
mkdir -p "/var/www/html/storage/framework/cache/data"
mkdir -p "/var/www/html/storage/framework/sessions"
mkdir -p "/var/www/html/storage/framework/views"
mkdir -p "/var/www/html/storage/logs"

echo "[20-init-sqlite] Ensuring SQLite database file exists at ${DB_PATH}..."
if [ ! -f "${DB_PATH}" ]; then
    touch "${DB_PATH}"
    echo "[20-init-sqlite] Created new SQLite database file."
else
    echo "[20-init-sqlite] SQLite database file already exists, skipping."
fi

echo "[20-init-sqlite] Setting ownership on storage..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

echo "[20-init-sqlite] Done."
