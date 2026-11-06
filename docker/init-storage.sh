#!/bin/sh
set -eu

SOURCE="/var/www/html/storage"
TARGET="/data/persistent"

echo "Initializing persistent storage"

# The Fly volume is only mounted once the machine has booted. If it is not
# there yet, skip rather than failing: a non-zero exit aborts the entrypoint
# and the container would crash-loop.
if ! mountpoint -q /data; then
    echo "SKIP: /data is not a mountpoint (no volume attached yet)."
    exit 0
fi

mkdir -p "$TARGET"

# Preserve whatever the image shipped, without overwriting what is already on
# the volume from a previous boot.
if [ -d "$SOURCE" ] && [ ! -L "$SOURCE" ]; then
    cp -an "$SOURCE/." "$TARGET/" 2>/dev/null || true
fi

# The app writes uploads to storage/app/private (reports, profile photos).
mkdir -p \
    "$TARGET/app/private" \
    "$TARGET/app/public" \
    "$TARGET/framework/cache/data" \
    "$TARGET/framework/sessions" \
    "$TARGET/framework/views" \
    "$TARGET/logs"

chown -R www-data:www-data "$TARGET" 2>/dev/null || true
chmod -R u+rwX,g+rwX "$TARGET" 2>/dev/null || true

# Point the framework at the volume. Guard against clobbering an existing link
# so repeated boots are idempotent.
if [ ! -L "$SOURCE" ]; then
    rm -rf "$SOURCE"
    ln -s "$TARGET" "$SOURCE"
    echo "storage -> $TARGET"
fi

echo "Persistent storage ready"
