#!/bin/sh
# ============================================================================
#  Entrypoint container Laravel.
#  Menyiapkan aplikasi (key, migrate, cache) lalu menjalankan web server.
# ============================================================================
set -e

cd /var/www/html

# Jika ada perintah yang dilewatkan (mis. `docker compose run app php artisan ...`),
# jalankan langsung tanpa proses prep server. Tanpa argumen (mis. `docker compose up`),
# lanjut ke penyiapan + menjalankan web server di bawah.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

# Generate APP_KEY bila belum di-set lewat environment (mis. lokal/pertama kali).
if [ -z "$APP_KEY" ]; then
    echo "[entrypoint] APP_KEY kosong -> generate sementara"
    php artisan key:generate --force || true
fi

# Tunggu database siap (khusus koneksi non-sqlite).
if [ "$DB_CONNECTION" != "sqlite" ] && [ -n "$DB_HOST" ]; then
    echo "[entrypoint] Menunggu database $DB_HOST:${DB_PORT:-3306} ..."
    i=0
    until php -r "exit(@fsockopen(getenv('DB_HOST'), (int)(getenv('DB_PORT') ?: 3306)) ? 0 : 1);" 2>/dev/null; do
        i=$((i+1))
        if [ "$i" -ge 30 ]; then
            echo "[entrypoint] Database tidak merespons, lanjut saja..."
            break
        fi
        sleep 2
    done
fi

# Untuk sqlite: pastikan file database ada.
if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    [ -f "$DB_FILE" ] || touch "$DB_FILE"
fi

# Migrasi database (aman dijalankan berulang).
php artisan migrate --force || echo "[entrypoint] migrate gagal/terlewati"

# Seed data awal hanya jika diminta (RUN_SEED=true) — supaya tidak dobel.
if [ "$RUN_SEED" = "true" ]; then
    php artisan db:seed --force || true
fi

# Symlink storage supaya gambar produk (Filament upload) bisa diakses publik.
php artisan storage:link || true

# Cache konfigurasi untuk performa (abaikan bila error).
php artisan config:cache || true
php artisan route:cache || true

echo "[entrypoint] Menjalankan server di 0.0.0.0:${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
