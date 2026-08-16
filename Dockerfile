# ============================================================================
#  Batik TrustYou — Dockerfile (Laravel API + Vue SPA jadi satu container)
#  Vue SPA disajikan langsung oleh Laravel (lihat routes/web.php), jadi tidak
#  perlu container frontend terpisah.
# ============================================================================

# ---- Stage 1: build asset frontend (Vue + Vite) ----------------------------
FROM node:20-alpine AS frontend
WORKDIR /app

# Install dependency dulu (biar layer-nya ke-cache selama lock tidak berubah)
COPY package.json package-lock.json ./
RUN npm ci

# Copy sisa source lalu build -> hasilnya ke public/build
COPY . .
RUN npm run build


# ---- Stage 2: runtime PHP --------------------------------------------------
# PHP 8.4: composer.lock memakai komponen Symfony 8 yang butuh PHP >= 8.4.1.
FROM php:8.4-cli-bookworm AS app
WORKDIR /var/www/html

# Ekstensi PHP yang dibutuhkan Laravel 12 + Filament + driver DB.
# install-php-extensions otomatis mengurus system library-nya.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
        pdo_pgsql pdo_mysql pdo_sqlite \
        gd zip intl bcmath exif pcntl opcache && \
    rm -rf /var/lib/apt/lists/*

# Composer dari image resmi
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy definisi dependency dulu, install tanpa dev
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Copy seluruh aplikasi
COPY . .

# Copy hasil build asset dari stage frontend
COPY --from=frontend /app/public/build ./public/build

# Selesaikan autoload + optimisasi (jalankan package:discover dsb.)
RUN composer dump-autoload --no-dev --optimize && \
    mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Entrypoint: migrate + cache config lalu jalankan server
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Banyak PaaS (Render/Railway) meng-inject $PORT; default 8000 untuk lokal.
ENV PORT=8000
# `php artisan serve` pakai server built-in PHP. Beberapa worker supaya bisa
# melayani request paralel (mis. saat Laravel menunggu balasan chatbot).
ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
