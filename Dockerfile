# -----------------------------------------------------------------------------
# Laravel API — multi-stage production image (Dokploy-ready)
# Base images: Arvan Cloud Docker mirror (docker.arvancloud.ir)
# -----------------------------------------------------------------------------

ARG PHP_VERSION=8.4
ARG COMPOSER_VERSION=2
ARG ARVAN_REGISTRY=docker.arvancloud.ir

# =============================================================================
# Stage 1: Composer dependencies (cached layer)
# =============================================================================
FROM ${ARVAN_REGISTRY}/composer:${COMPOSER_VERSION} AS vendor

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

COPY composer.json composer.lock ./

RUN composer config -g repos.packagist composer https://mirror-composer.runflare.com \
    && composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist \
        --ignore-platform-reqs \
    || (composer config -g --unset repos.packagist \
        && composer install \
            --no-dev \
            --no-scripts \
            --no-autoloader \
            --no-interaction \
            --prefer-dist \
            --ignore-platform-reqs)

COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY artisan ./

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts --no-interaction

# =============================================================================
# Stage 2: Production runtime (PHP-FPM + Nginx + Supervisor)
# =============================================================================
FROM ${ARVAN_REGISTRY}/php:${PHP_VERSION}-fpm-alpine AS production

LABEL org.opencontainers.image.title="3drgb-backend-api" \
      org.opencontainers.image.description="3D RGB Laravel backend API" \
      org.opencontainers.image.source="https://github.com/iranpsc/3drgb"

WORKDIR /var/www/html

# Faster Alpine package installs via Arvan mirror
# Original: https://dl-cdn.alpinelinux.org/alpine/vX.Y/...
# Target:   https://mirror.arvancloud.ir/alpine/vX.Y/...
RUN sed -i 's|https://dl-cdn.alpinelinux.org|https://mirror.arvancloud.ir|g' /etc/apk/repositories \
    || true

# System deps + PHP extensions required by Laravel / Excel / images
RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        bash \
        icu-libs \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
        oniguruma \
        mysql-client \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && apk del .build-deps \
    && rm -rf /tmp/pear /var/cache/apk/*

# PHP / Nginx / Supervisor configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN sed -i 's/user nginx;/user www-data;/' /etc/nginx/nginx.conf \
    && mkdir -p /var/log/supervisor /run/nginx /var/lib/nginx/tmp \
    && chown -R www-data:www-data /var/lib/nginx /var/log/nginx \
    && chmod +x /usr/local/bin/entrypoint.sh

# Application source (ordered for layer reuse)
COPY --chown=www-data:www-data artisan composer.json composer.lock ./
COPY --chown=www-data:www-data app ./app
COPY --chown=www-data:www-data bootstrap ./bootstrap
COPY --chown=www-data:www-data config ./config
COPY --chown=www-data:www-data database ./database
COPY --chown=www-data:www-data lang ./lang
COPY --chown=www-data:www-data public ./public
COPY --chown=www-data:www-data resources ./resources
COPY --chown=www-data:www-data routes ./routes
COPY --chown=www-data:www-data storage ./storage

COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        storage/app/public \
        storage/app/upload \
        storage/app/download \
        public/sitemap \
        bootstrap/cache \
    && chown -R www-data:www-data storage public/sitemap bootstrap/cache \
    && chmod -R ug+rwx storage public/sitemap bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    CACHE_CONFIG=true \
    RUN_MIGRATIONS=false \
    SKIP_DB_WAIT=false

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
