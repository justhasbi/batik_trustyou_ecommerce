# Instalasi Aplikasi — Batik TrustYou

Dokumentasi instalasi backend (Laravel + Filament), storefront (Vue 3, disajikan oleh Laravel via Vite), dan service chatbot (Python).

## Kebutuhan sistem

| Komponen | Versi minimum |
|----------|---------------|
| PHP | 8.2 |
| Composer | 2.x |
| Node.js | 18.x |
| MySQL / MariaDB | 8.0 / 10.4 |
| Python | 3.10 |

## 1. Backend (Laravel + Filament)

### 1.1 Dependency

```bash
cd batik-trustyou
composer install
```

### 1.2 Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` pada bagian database:

```env
APP_NAME="Batik TrustYou"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=batik_trustyou
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
CHATBOT_URL=http://localhost:8001
```

### 1.3 Database

```bash
mysql -u root -p -e "CREATE DATABASE batik_trustyou CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 1.4 Migrasi

```bash
php artisan migrate --seed
```

> Jika tabel `personal_access_tokens` belum tersedia (Sanctum belum ter-scaffold), jalankan `php artisan install:api` lalu ulangi `php artisan migrate`.

### 1.5 Symbolic link storage

```bash
php artisan storage:link
```

### 1.6 Akun admin

Seeder menyediakan akun admin:

- Email: `admin@batiktrustyou.test`
- Password: `password`

Untuk membuat akun admin baru:

```bash
php artisan make:filament-user
```

Setelah dibuat, Flag sebagai admin:

```bash
php artisan tinker
>>> $u = App\Models\User::where('email','email@anda.test')->first();
>>> $u->is_admin = true; $u->save();
>>> exit
```

## 2. Frontend (Vue 3 storefront)

Storefront disajikan oleh Laravel sendiri (satu origin dengan API dan `/admin`) lewat Vite bawaan Laravel. Tidak perlu project terpisah.

### 2.1 Dependency

```bash
npm install
```

Tidak ada konfigurasi tambahan — storefront otomatis memakai API pada origin yang sama (`/api`) dan token Sanctum untuk autentikasi.

## 3. Menjalankan (dev)

Jalankan dua proses secara bersamaan di dua terminal:

```bash
php artisan serve      # menyajikan storefront + API + /admin
```

```bash
npm run dev             # Vite dev server + hot reload untuk aset frontend
```

| Service | URL |
|---------|-----|
| Storefront | http://localhost:8000 |
| API | http://localhost:8000/api |
| Dashboard admin | http://localhost:8000/admin |

Untuk build produksi (tanpa `npm run dev`):

```bash
npm run build
```

## 4. Service chatbot (Python)

### 4.1 Virtual environment dan dependency

```bash
cd chatbot_service
python -m venv venv
```

Aktifkan:

```bash
# Linux / macOS
source venv/bin/activate
# Windows
venv\Scripts\activate
```

Pasang dependency:

```bash
pip install -r requirements.txt
```

### 4.2 Data NLTK

```bash
python -c "import nltk; nltk.download('punkt'); nltk.download('punkt_tab')"
```

### 4.3 Latih model

```bash
python train.py
```

Perintah ini menghasilkan `model.pkl` dan grafik evaluasi di folder `reports/`.

### 4.4 Jalankan service

```bash
uvicorn main:app --port 8001
```

Verifikasi:

```bash
curl http://localhost:8001/health
```

## 5. Konfigurasi penghubung backend–chatbot

Pastikan `config/services.php` memuat entri berikut:

```php
'chatbot' => [
    'url' => env('CHATBOT_URL', 'http://localhost:8001'),
],
```

Nilai `CHATBOT_URL` diambil dari `.env` (langkah 1.2).