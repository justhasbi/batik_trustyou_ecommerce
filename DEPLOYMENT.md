# Panduan Deploy — Batik TrustYou

Panduan menjalankan aplikasi ini di server, plus rekomendasi hosting hemat
(diutamakan yang gratis) untuk kebutuhan Tugas Akhir.

---

## Arsitektur singkat

Aplikasi ini terdiri dari **3 komponen**, tetapi hanya butuh **2 service** yang
di-deploy karena frontend menyatu dengan backend:

| Komponen | Teknologi | Catatan deploy |
|----------|-----------|----------------|
| Frontend (Vue SPA) | Vue 3 + Vite | **Di-serve oleh Laravel** (lihat `routes/web.php`) — bukan service terpisah |
| Backend (API + admin) | Laravel 12 + Filament | Satu container, meng-handle API, panel admin, dan SPA |
| Chatbot | FastAPI + NLTK | Service internal, dipanggil Laravel via `CHATBOT_URL` |
| Database | MySQL / SQLite | Bisa managed (gratis) atau container |

Karena SPA di-serve dari origin yang sama dengan API, **tidak ada masalah CORS**
dan tidak perlu domain terpisah untuk frontend. Ini menghemat satu service.

```
                    ┌─────────────────────────────┐
   Browser  ───────▶│  app (Laravel)              │
                    │  - Vue SPA + API + Filament  │
                    └──────┬───────────────┬───────┘
                           │               │
                 http (internal)      SQL/mysql
                           │               │
                    ┌──────▼──────┐  ┌─────▼──────┐
                    │  chatbot     │  │  database  │
                    │  (FastAPI)   │  │  (MySQL)   │
                    └──────────────┘  └────────────┘
```

---

## Rekomendasi hosting (diurut: paling hemat → paling mudah)

| Opsi | Biaya | Always-on? | Kesulitan | Cocok untuk |
|------|-------|-----------|-----------|-------------|
| **1. Oracle Cloud Always Free** (1 VM + docker compose) | **Gratis selamanya** | Ya | Sedang (setup Linux) | Demo TA yang perlu selalu hidup, tanpa biaya |
| **2. Render.com + Aiven** | **Gratis** (ada cold start) | Tidak (tidur saat idle) | Mudah | Deploy cepat, tidak mau urus server |
| **3. Railway / Koyeb / Fly.io** | ± $5/bln (ada kredit awal) | Ya | Mudah | Kalau butuh always-on tanpa ngurus VM |

**Rekomendasi untuk TA:**
- Mau **benar-benar gratis dan selalu hidup** (mis. dosen bisa akses kapan saja) →
  **Opsi 1 (Oracle Always Free)**.
- Mau **paling cepat & simpel**, tidak masalah ada jeda ±30–60 detik saat pertama
  diakses setelah idle → **Opsi 2 (Render + Aiven)**.

Untuk **database MySQL gratis yang always-on**, pilihan yang cocok untuk TA:
- **[Aiven](https://aiven.io)** — free plan MySQL (1 node, ~1 GB), managed, tidak tidur.
- **[TiDB Cloud Serverless](https://tidbcloud.com)** — kompatibel MySQL, free tier
  cukup besar & always-on.
- Alternatif paling hemat: **SQLite** atau **MySQL container** di satu VM (Opsi 1),
  tanpa perlu layanan DB terpisah.

---

## A. Deploy dengan Docker Compose (lokal & VPS / Oracle Always Free)

Cara paling universal. Semua service (app + chatbot + MySQL) jalan bareng.
Berlaku untuk laptop sendiri maupun VPS mana pun.

### Prasyarat
- Docker + Docker Compose terpasang.
- **Dua repo berdampingan** dalam satu folder induk:
  ```
  induk/
  ├── batik_trustyou_ecommerce/   ← jalankan perintah dari sini
  └── nltk_chatbot_service/
  ```
  (compose membangun chatbot dari `../nltk_chatbot_service`.)

### Langkah

```bash
cd batik_trustyou_ecommerce

# 1. Siapkan environment
cp .env.docker.example .env.docker

# 2. Build image
docker compose build

# 3. Generate APP_KEY, lalu salin hasilnya ke APP_KEY di .env.docker
docker compose run --rm app php artisan key:generate --show

# 4. Jalankan (RUN_SEED=true di .env.docker akan mengisi data contoh sekali)
docker compose up -d

# 5. Lihat log kalau perlu
docker compose logs -f app
```

Akses:
- Toko: <http://localhost:8000>
- Panel admin: <http://localhost:8000/admin> (akun dari seeder — cek `database/seeders/UserSeeder.php`)

> Setelah data ter-seed sekali, ubah `RUN_SEED=false` di `.env.docker` lalu
> `docker compose up -d` lagi, supaya data tidak dobel tiap restart.

### Menaruhnya di Oracle Cloud Always Free
1. Buat akun di <https://cloud.oracle.com> (butuh kartu untuk verifikasi, **tidak
   ditagih** selama pakai resource "Always Free").
2. Buat **VM Instance** → pilih shape **Always Free** (mis. `VM.Standard.A1.Flex`
   ARM, atau `VM.Standard.E2.1.Micro`). Pilih image **Ubuntu 22.04**.
3. Buka port 80/8000 di **Security List / Ingress Rules**.
4. SSH ke VM, install Docker:
   ```bash
   curl -fsSL https://get.docker.com | sh
   ```
5. `git clone` kedua repo berdampingan, lalu ikuti langkah compose di atas.
6. (Opsional) pasang domain gratis (DuckDNS) + reverse proxy (Caddy/Nginx) untuk
   HTTPS otomatis. Set `APP_URL=https://namamu.duckdns.org`.

---

## B. Deploy di Render.com (paling mudah, ada free tier)

Di PaaS, tiap repo jadi service tersendiri (compose `../` tidak berlaku), jadi
**chatbot** dan **app** di-deploy sebagai dua Web Service terpisah, plus database
MySQL gratis dari Aiven/TiDB.

### 1. Database — Aiven MySQL (gratis, always-on)
1. Daftar di <https://aiven.io>, buat service **MySQL** (free plan).
2. Salin connection detail (host, port, database, user, password). Aiven memakai
   port non-standar (mis. `12345`) dan mewajibkan SSL — catat juga port-nya.

### 2. Service chatbot (repo `nltk_chatbot_service`)
1. Render → **New → Web Service** → hubungkan repo `nltk_chatbot_service`.
2. Runtime: **Docker** (otomatis pakai `Dockerfile`).
3. Instance: **Free**. Setelah live, catat URL-nya
   (mis. `https://batik-chatbot.onrender.com`).

### 3. Service app (repo `batik_trustyou_ecommerce`)
1. Render → **New → Web Service** → hubungkan repo `batik_trustyou_ecommerce`.
2. Runtime: **Docker**. Instance: **Free**.
3. Isi **Environment Variables** (ambil dari Aiven untuk DB):
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=            # generate lokal: php artisan key:generate --show
   APP_URL=https://<nama-app>.onrender.com
   DB_CONNECTION=mysql
   DB_HOST=<host-aiven>
   DB_PORT=<port-aiven>       # Aiven pakai port non-standar, mis. 12345
   DB_DATABASE=<db-aiven>
   DB_USERNAME=<user-aiven>
   DB_PASSWORD=<password-aiven>
   MYSQL_ATTR_SSL_CA=         # kosongkan; atau path CA bila DB mewajibkan SSL verify
   SESSION_DRIVER=database
   QUEUE_CONNECTION=database
   CACHE_STORE=database
   CHATBOT_URL=https://batik-chatbot.onrender.com
   RUN_SEED=true      # set true sekali di deploy pertama, lalu ubah false
   ```
4. Deploy. Container akan migrate + seed otomatis lewat entrypoint.

> **Catatan cold start:** service Free di Render "tidur" setelah ~15 menit idle,
> jadi request pertama setelah nganggur bisa lambat (±30–60 detik). Wajar untuk
> demo; kalau perlu selalu hidup, naik ke paket berbayar atau pakai Opsi 1.

---

## Catatan penting (baca sebelum deploy)

- **APP_KEY wajib di-set** di environment produksi. Kalau dibiarkan kosong,
  entrypoint membuat key sementara yang **berubah tiap restart** → sesi & data
  terenkripsi jadi tidak konsisten. Generate sekali, simpan tetap.

- **Gambar produk (upload dari panel admin)** disimpan di
  `storage/app/public`. Di compose sudah dipersistkan lewat volume
  `storage_public`. Di PaaS dengan disk **ephemeral** (Render Free), file upload
  **hilang saat redeploy**. Untuk TA umumnya cukup; kalau butuh permanen,
  arahkan `FILESYSTEM_DISK` ke object storage gratis seperti **Cloudflare R2**
  atau **Supabase Storage**.

- **Database gratis:** Aiven MySQL (free plan, always-on) direkomendasikan.
  Alternatif: TiDB Cloud Serverless (kompatibel MySQL). Hindari layanan yang
  auto-pause saat idle bila butuh selalu siap.

- **SQLite** juga bisa dipakai (paling hemat, tanpa service DB): set
  `DB_CONNECTION=sqlite`. Tapi butuh disk persisten (Opsi 1), karena file
  `database/database.sqlite` akan hilang di disk ephemeral.

- **Keamanan chatbot:** di Render kedua service punya URL publik. Untuk demo TA
  aman-aman saja; kalau mau ketat, tambahkan shared-secret header antara Laravel
  dan FastAPI.

---

## Ringkasan perintah

```bash
# Build & jalankan semua (compose)
docker compose build && docker compose up -d

# Generate APP_KEY
docker compose run --rm app php artisan key:generate --show

# Migrasi manual (kalau perlu)
docker compose exec app php artisan migrate --force

# Seed manual
docker compose exec app php artisan db:seed --force

# Lihat log
docker compose logs -f app chatbot

# Hentikan
docker compose down          # tambah -v untuk hapus volume (data!)
```
