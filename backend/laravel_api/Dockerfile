# =============================================================================
# AUREX - Production Dockerfile
# Multi-stage build with Composer optimization, OPcache, and Laravel caching
# =============================================================================

# =============================================================================
# STAGE 1: Vendor — Install Composer dependencies (cache-friendly layer)
# =============================================================================
FROM composer:2.8 AS vendor

WORKDIR /app

# Copy only dependency files first (maximize Docker layer cache)
COPY composer.json composer.lock ./

# Install production dependencies with full optimization
# --no-dev: exclude dev dependencies
# --prefer-dist: use dist packages (faster)
# --optimize-autoloader: generate classmap for faster autoloading
# --classmap-authoritative: no fallback to filesystem/APC (fastest)
# --no-scripts: skip scripts during vendor build (run in final stage)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative

# =============================================================================
# STAGE 2: Application — Build Laravel optimized cache
# =============================================================================
FROM php:8.3-fpm-alpine AS app

# Install PHP extensions needed for Laravel + Redis
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        gd \
        zip \
        bcmath \
        opcache \
        exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev

# Copy application source code
WORKDIR /var/www/html
COPY . .

# Copy vendor from vendor stage (not the dev tools like composer)
COPY --from=vendor /app/vendor ./vendor

# Generate optimized Laravel cache for production
# Catatan: config/route/view/event cache dijalankan di entrypoint.sh saat runtime
# karena membutuhkan APP_KEY dan kredensial database yang hanya tersedia saat kontainer jalan
RUN : \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && :

# =============================================================================
# STAGE 3: Production — Minimal runtime image
# =============================================================================
FROM php:8.3-fpm-alpine

# Install system dependencies + PHP extensions in one layer
# Dev libraries (libpng-dev, etc.) are installed then removed to minimize image size
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        gd \
        zip \
        bcmath \
        opcache \
        exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev

# Copy PHP production configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy Nginx configuration
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor.d/supervisord.conf

# Copy pre-built application from app stage
# This includes vendor, cached config/routes/views/events
COPY --from=app --chown=www-data:www-data /var/www/html /var/www/html

# Copy entrypoint
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

WORKDIR /var/www/html

# Expose Nginx port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

# Run via entrypoint (handles migrations) → Supervisor (manages Nginx + PHP-FPM + Queue)
ENTRYPOINT ["docker-entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor.d/supervisord.conf"]
