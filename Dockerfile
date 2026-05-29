FROM serversideup/php:8.4-fpm-nginx

# Switch to root for setup tasks
USER root

# Copy application source with correct ownership
COPY --chown=www-data:www-data . /var/www/html

WORKDIR /var/www/html

# Filament/Laravel require ext-intl (and gd/bcmath/exif for image + numeric
# handling) which are not bundled in the base image. install-php-extensions
# ships with the serversideup image.
RUN install-php-extensions intl gd bcmath exif

# Install PHP dependencies without running post-autoload-dump scripts
# (artisan commands in those scripts require APP_KEY + DB which are not
#  available at build time). We run the necessary discovery steps manually.
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts

# Publish package assets and Filament compiled assets into public/
# Requires a minimal APP_KEY so artisan can boot; the key is only used
# for build-time command execution and is NOT baked into the final image.
RUN rm -f bootstrap/cache/*.php && \
    APP_KEY=base64:n+RwZkCIH3lOeth/InyChziL2rq2sD3SK09DpavoUFY= \
    APP_ENV=production \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/tmp/build.sqlite \
    php artisan package:discover --ansi && \
    touch /tmp/build.sqlite && \
    APP_KEY=base64:n+RwZkCIH3lOeth/InyChziL2rq2sD3SK09DpavoUFY= \
    APP_ENV=production \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/tmp/build.sqlite \
    php artisan filament:assets --ansi && \
    rm -f /tmp/build.sqlite

# Ensure storage and bootstrap/cache are writable by www-data
RUN chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache && \
    chmod -R 775 \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache

# SQLite initialisation script — runs at position 20 (after webserver config
# at 10, well before AUTORUN Laravel automations at 50).
# Creates the DB file and storage dirs if missing on a fresh persistent volume.
COPY --chmod=755 docker/entrypoint.d/20-init-sqlite.sh /etc/entrypoint.d/20-init-sqlite.sh

# Default ENV — all overridable by Coolify at runtime
ENV APP_ENV=production \
    APP_DEBUG=false \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    AUTORUN_LARAVEL_STORAGE_LINK=true \
    AUTORUN_LARAVEL_CONFIG_CACHE=true \
    AUTORUN_LARAVEL_ROUTE_CACHE=true \
    AUTORUN_LARAVEL_VIEW_CACHE=true \
    AUTORUN_LARAVEL_EVENT_CACHE=true \
    PHP_OPCACHE_ENABLE=1 \
    LOG_CHANNEL=stderr

# Switch back to non-root for runtime
USER www-data

EXPOSE 8080
