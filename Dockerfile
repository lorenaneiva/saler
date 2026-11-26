# Production image for Saler (Laravel + FrankenPHP), built for Render's Docker runtime.
# No secrets are baked in: every credential comes from runtime environment variables.

ARG PHP_IMAGE=dunglas/frankenphp:1-php8.5-alpine

# ---------------------------------------------------------------------------
# Base: PHP runtime with the extensions the application needs.
# ---------------------------------------------------------------------------
FROM ${PHP_IMAGE} AS base

RUN install-php-extensions pdo_pgsql intl bcmath opcache \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /app

# ---------------------------------------------------------------------------
# Vendor: production Composer dependencies only.
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --no-progress

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction \
    && php artisan package:discover --ansi

# ---------------------------------------------------------------------------
# Assets: compile CSS/JS with Vite.
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json .npmrc ./
RUN npm install --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
# Tailwind scans these framework views (see resources/css/app.css).
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ---------------------------------------------------------------------------
# Runtime image.
# ---------------------------------------------------------------------------
FROM base AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080 \
    XDG_CONFIG_HOME=/config \
    XDG_DATA_HOME=/data

COPY docker/php/production.ini "$PHP_INI_DIR/conf.d/zz-production.ini"
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/start.sh /usr/local/bin/start-saler

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build

RUN adduser -D -u 1000 saler \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && rm -f public/hot bootstrap/cache/*.php.tmp \
    && chown -R saler:saler storage bootstrap/cache /config /data \
    && chmod -R ug+rwX storage bootstrap/cache \
    && chmod 0755 /usr/local/bin/start-saler

# $PORT is above 1024, so the binary needs no extra capability to run as a non-root user.
RUN setcap -r /usr/local/bin/frankenphp 2>/dev/null || true

USER saler

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD wget -qO- "http://127.0.0.1:${PORT}/up" >/dev/null || exit 1

CMD ["start-saler"]
