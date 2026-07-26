#!/bin/sh
set -e

# ── Install vendor jika belum ada (tertimpa oleh volume mount) ────────────────
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "📦 vendor/ not found, running composer install..."
    composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --ignore-platform-reqs \
        --optimize-autoloader
    echo "✅ Composer install done."
fi

# ── Tunggu sampai database siap ───────────────────────────────────────────────
echo "⏳ Waiting for database..."
until php artisan db:monitor > /dev/null 2>&1; do
  sleep 2
done
echo "✅ Database is ready."

# ── Generate APP_KEY jika belum ada ───────────────────────────────────────────
if grep -q "^APP_KEY=$" /var/www/.env 2>/dev/null || ! grep -q "^APP_KEY=" /var/www/.env 2>/dev/null; then
    echo "🔑 Generating APP_KEY..."
    php artisan key:generate --force
fi

# ── Jalankan migrasi ──────────────────────────────────────────────────────────
echo "🗄️  Running migrations..."
php artisan migrate --force || echo "⚠️  Migration failed, but continuing startup..."

# ── Cache config, routes, views ───────────────────────────────────────────────
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── Buat symlink storage jika belum ada ───────────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

# ── Fix permissions storage ───────────────────────────────────────────────────
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo "🚀 Starting php-fpm..."
exec php-fpm
