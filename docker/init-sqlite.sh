#!/bin/sh
# Note: no `set -e` on the chown below on purpose - a single unreadable entry
# must not abort the container and trip Fly's restart limit.
set -u

DB_PATH="${DB_DATABASE:-/data/database.sql}"
DB_DIR="$(dirname "$DB_PATH")"

echo "=================================="
echo " Initializing Laravel SQLite"
echo "=================================="

mkdir -p "$DB_DIR"

if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database: $DB_PATH"
    touch "$DB_PATH"
fi

# Own only the database and its directory. Never recurse into /data itself:
# the volume contains root-only entries (lost+found) that we cannot read, and
# a recursive chown there fails and kills the boot.
chown www-data:www-data "$DB_PATH" 2>/dev/null || true
chown www-data:www-data "$DB_DIR"  2>/dev/null || true

chmod 775 "$DB_DIR" 2>/dev/null || true
chmod 664 "$DB_PATH" 2>/dev/null || true

rm -f /var/www/html/bootstrap/cache/config.php

echo "SQLite database initialized successfully."
echo "Database: $DB_PATH"

cd /var/www/html
php artisan migrate --force --seed

echo "Laravel migrations completed."
