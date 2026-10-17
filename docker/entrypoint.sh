#!/bin/sh
set -eu

cd /var/www/html

echo "[entrypoint] Preparing Laravel application..."

# Ephemeral runtime dirs (stay in the container layer — not bind-mounted)
mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/framework/testing \
  storage/logs \
  bootstrap/cache

# Durable dirs (may be host bind mounts under /opt/3dmeta)
mkdir -p \
  storage/app/public \
  storage/app/upload \
  storage/app/download \
  public/sitemap

# Own only ephemeral paths recursively — avoid chown -R on large upload trees
chown -R www-data:www-data storage/framework storage/logs bootstrap/cache || true
chmod -R ug+rwx storage/framework storage/logs bootstrap/cache || true

# Fix mount-point ownership (top-level only) so www-data can write
chown www-data:www-data storage/app public/sitemap \
  storage/app/public storage/app/upload storage/app/download 2>/dev/null || true
chmod ug+rwx storage/app public/sitemap \
  storage/app/public storage/app/upload storage/app/download 2>/dev/null || true

# Generate APP_KEY when missing (safe for first boot)
if [ -z "${APP_KEY:-}" ]; then
  echo "[entrypoint] APP_KEY is empty; generating..."
  export APP_KEY="$(php artisan key:generate --show --no-ansi)"
fi

# Optional DB wait (skip when SKIP_DB_WAIT=true)
if [ "${SKIP_DB_WAIT:-false}" != "true" ] && [ -n "${DB_HOST:-}" ]; then
  DB_PORT="${DB_PORT:-3306}"
  echo "[entrypoint] Waiting for database ${DB_HOST}:${DB_PORT}..."
  i=0
  until php -r "
    try {
      new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'),
        getenv('DB_USERNAME') ?: 'root',
        getenv('DB_PASSWORD') ?: ''
      );
      exit(0);
    } catch (Throwable \$e) {
      exit(1);
    }
  "; do
    i=$((i + 1))
    if [ "$i" -ge 60 ]; then
      echo "[entrypoint] Database not ready after 60s; continuing anyway."
      break
    fi
    sleep 1
  done
fi

# Discover packages (vendor was installed with --no-scripts in the image build).
# Required so deferred providers like CacheServiceProvider are mapped; Redis
# sessions resolve the "cache" binding and otherwise throw ReflectionException.
# Clear host/dev manifests first — they may reference require-dev providers.
echo "[entrypoint] Discovering packages..."
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php || true
php artisan package:discover --ansi --no-interaction || true

# Cache or clear compiled config/routes/views
if [ "${APP_ENV:-production}" = "production" ] || [ "${CACHE_CONFIG:-true}" = "true" ]; then
  echo "[entrypoint] Caching config/routes/views..."
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
else
  echo "[entrypoint] Clearing compiled config/routes (local/dev)..."
  php artisan config:clear || true
  php artisan route:clear || true
  rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/events.php || true
fi

# Artisan above often runs as root — ensure PHP-FPM (www-data) can refresh manifests.
chown -R www-data:www-data bootstrap/cache storage/framework storage/logs || true
chmod -R ug+rwx bootstrap/cache storage/framework storage/logs || true

# Optional migrate on boot (Dokploy / compose)
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "[entrypoint] Running migrations..."
  php artisan migrate --force --no-interaction || true
  chown -R www-data:www-data bootstrap/cache storage/framework storage/logs || true
fi

# Storage symlink for public disk
php artisan storage:link --force --no-interaction 2>/dev/null || true

echo "[entrypoint] Starting supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
