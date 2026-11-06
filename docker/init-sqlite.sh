
#!/bin/sh
set -eu

DB_PATH="${DB_DATABASE:-/data/database.sql}"
DB_DIR="$(dirname "$DB_PATH")"

echo "=================================="
echo " Initializing Laravel SQLite"
echo "=================================="

# Ensure database directory exists
mkdir -p "$DB_DIR"

# Create SQLite database if missing
if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database: $DB_PATH"
    touch "$DB_PATH"
fi

# Ensure www-data owns the database
chown -R www-data:www-data "$DB_DIR"

# Set permissions
chmod 775 "$DB_DIR"
chmod 664 "$DB_PATH"

# Remove stale Laravel config cache
rm -f /var/www/html/bootstrap/cache/config.php

echo "SQLite database initialized successfully."
echo "Database: $DB_PATH"

# Run Laravel migrations
cd /var/www/html
php artisan migrate --force --seed

echo "Laravel migrations completed."

