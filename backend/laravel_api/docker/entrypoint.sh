#!/bin/sh
set -e

# Fungsi: tunggu hingga database siap
wait_for_db() {
    echo "Menunggu database siap..."
    local retries=60
    while [ $retries -gt 0 ]; do
        if php /var/www/html/artisan db:show --quiet 2>/dev/null; then
            echo "Database siap!"
            return 0
        fi
        sleep 2
        retries=$((retries - 1))
    done
    echo "ERROR: Database tidak siap setelah 120 detik"
    return 1
}

# Tunggu database
wait_for_db

# Jalankan migrasi
echo "Menjalankan migrasi database..."
php /var/www/html/artisan migrate --force

# Cache konfigurasi untuk production
if [ "${APP_ENV}" = "production" ]; then
    echo "Production mode: caching config & routes..."
    php /var/www/html/artisan config:cache
    php /var/www/html/artisan route:cache
    php /var/www/html/artisan view:cache
fi

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Entrypoint selesai. Menjalankan Supervisor..."
exec "$@"
