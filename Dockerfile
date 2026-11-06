
# Build frontend assets with Vite
FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build


# PHP 8.5 + Nginx
FROM serversideup/php:8.5-frankenphp

WORKDIR /var/www/html

USER root

# Copy Laravel application
COPY . .

# Copy compiled Vite assets
COPY --from=frontend /app/public/build ./public/build

# Install production PHP dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Set Laravel writable directories
RUN chown -R www-data:www-data storage bootstrap/cache

# Boot tasks. The base image runs every executable in /etc/entrypoint.d/ in
# numeric order on each boot; these were previously copied to /usr/local/bin
# and never called, so storage was never linked to the Fly volume and uploaded
# images were lost on every deploy.
COPY docker/init-storage.sh /etc/entrypoint.d/10-init-storage.sh
COPY docker/init-sqlite.sh /etc/entrypoint.d/20-init-sqlite.sh
RUN chmod +x /etc/entrypoint.d/10-init-storage.sh /etc/entrypoint.d/20-init-sqlite.sh

RUN mkdir -p /data \
    && chown -R www-data:www-data /data \
    && chmod 775 /data

USER www-data

EXPOSE 8080

