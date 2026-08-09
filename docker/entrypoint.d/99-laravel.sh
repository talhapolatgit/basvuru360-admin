#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    public/uploads/genel \
    public/uploads/portal-sayfalar \
    public/uploads/sertifika \
    public/uploads/avatars

# If Coolify mounts an empty public/uploads volume, restore seeded logos from the image.
seed_copy_missing() {
    src="$1"
    dest="$2"
    if [ ! -d "$src" ]; then
        return 0
    fi
    mkdir -p "$dest"
    for f in "$src"/*; do
        [ -e "$f" ] || continue
        base=$(basename "$f")
        if [ ! -e "$dest/$base" ]; then
            cp -a "$f" "$dest/$base"
        fi
    done
}

seed_copy_missing /opt/basvuru360-seed-uploads/genel public/uploads/genel
seed_copy_missing /opt/basvuru360-seed-uploads/portal-sayfalar public/uploads/portal-sayfalar

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is empty. Set APP_KEY in Coolify environment variables (php artisan key:generate --show)." >&2
    exit 1
fi

# storage:link is idempotent enough for restarts
php artisan storage:link --force 2>/dev/null || php artisan storage:link || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    max_attempts="${MIGRATE_MAX_ATTEMPTS:-30}"
    attempt=1
    until php artisan migrate --force --no-interaction; do
        if [ "$attempt" -ge "$max_attempts" ]; then
            echo "ERROR: php artisan migrate failed after ${max_attempts} attempts. Check DB_HOST/DB_* and that MySQL is reachable on the Coolify network." >&2
            exit 1
        fi
        echo "Waiting for database before migrate (attempt ${attempt}/${max_attempts})..."
        attempt=$((attempt + 1))
        sleep 2
    done
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

php artisan package:discover --ansi || true
