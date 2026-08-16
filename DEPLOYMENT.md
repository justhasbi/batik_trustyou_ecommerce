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

## Rekomendasi hosting — GRATIS & tanpa kartu kredit

Semua opsi di bawah **tidak meminta kartu kredit** saat daftar, jadi cocok untuk
mahasiswa. (Catatan: kebijakan platform bisa berubah — cek lagi saat mendaftar.)

### Compute (menjalankan container app + chatbot)

| Platform | Biaya | Docker? | Kartu kredit? | Catatan |
|----------|-------|---------|---------------|---------|
| **Render.com** | Gratis | Ya | **Tidak** | Tidur saat idle (cold start ±30–60 dtk). Paling mudah & populer. |
| **Back4App Containers** | Gratis | Ya | **Tidak** | Deploy image Docker; kuota terbatas tapi cukup untuk demo. |
| **Hugging Face Spaces (Docker)** | Gratis | Ya | **Tidak** | Cocok khususnya untuk service chatbot; storage ephemeral. |

### Database (MySQL gratis, always-on)

| Platform | Biaya | Kartu kredit? | Catatan |
|----------|-------|---------------|---------|
| **[TiDB Cloud Serverless](https://tidbcloud.com)** | Gratis | **Tidak** | Kompatibel MySQL, kuota besar, tidak tidur. Rekomendasi utama. |
| **[Aiven](https://aiven.io)** | Gratis (free plan) | **Tidak** | MySQL managed 1 node (~1 GB). |
| **db4free.net / freesqldatabase.com** | Gratis | **Tidak** | MySQL kecil untuk demo ringan; kurang stabil, jangan untuk produksi. |

> **⚠️ Butuh kartu kredit (hindari sesuai permintaan):** Oracle Cloud Always Free,
> AWS/GCP/Azure free tier, dan Fly.io semuanya minta kartu untuk verifikasi —
> walaupun "gratis". Railway tidak minta kartu di awal tapi butuh bayar setelah
> kredit trial habis.

**Rekomendasi untuk TA (100% gratis, tanpa kartu):**
**Render.com** (untuk `app` + `chatbot`) **+ TiDB Cloud Serverless** (MySQL).
Ikuti langkah di **Bagian B** di bawah — cukup ganti detail DB-nya dari TiDB.

Trade-off yang perlu diterima di jalur gratis ini:
- **Cold start:** service tidur setelah ±15 menit idle. Akali dengan uptime pinger
  gratis (tanpa kartu) seperti **[UptimeRobot](https://uptimerobot.com)** atau
  **[cron-job.org](https://cron-job.org)** yang meng-ping `/up` (Laravel) dan
  `/health` (chatbot) tiap 5 menit supaya tetap "hangat" selama jam demo.
- **Storage ephemeral:** gambar produk yang di-upload dari panel admin hilang saat
  redeploy (lihat catatan di bawah).

---

## A. Deploy dengan Docker Compose (lokal & VPS)

Cara paling universal. Semua service (app + chatbot + MySQL) jalan bareng.
Berlaku untuk laptop sendiri maupun VPS mana pun.

> Catatan: sebagian besar VM "always free" (Oracle Cloud, dsb.) **tetap meminta
> kartu kredit** untuk verifikasi. Kalau syaratnya benar-benar tanpa kartu,
> pakai jalur PaaS di **Bagian B** (Render + TiDB). Bagian A ini paling berguna
> untuk menguji di laptop sendiri atau bila kamu sudah punya VPS.

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

### Menaruhnya di Oracle Cloud Always Free (⚠️ butuh kartu kredit)
> Opsi ini always-on & gratis, tapi Oracle **mewajibkan kartu** untuk verifikasi.
> Lewati kalau syaratmu benar-benar tanpa kartu — pakai Bagian B.

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

## B. Deploy di Render.com + TiDB (gratis, tanpa kartu kredit)

Jalur rekomendasi tanpa kartu. Di PaaS tiap repo jadi service tersendiri (compose
`../` tidak berlaku), jadi **chatbot** dan **app** di-deploy sebagai dua Web
Service terpisah, plus database MySQL gratis dari TiDB (atau Aiven).

### 1. Database — TiDB Cloud Serverless (gratis, tanpa kartu, always-on)
1. Daftar di <https://tidbcloud.com> (SSO Google/GitHub, tanpa kartu), buat
   cluster **Serverless** (kompatibel MySQL).
2. Buka **Connect** → salin detail koneksi: host, port (`4000`), database, user,
   password. TiDB **mewajibkan TLS** — pada Render, set juga
   `MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt`.
3. Alternatif: **Aiven** (<https://aiven.io>, juga tanpa kartu) — buat service
   **MySQL** free plan, salin host/port/user/password (port non-standar + TLS).

### 2. Service chatbot (repo `nltk_chatbot_service`)
1. Render → **New → Web Service** → hubungkan repo `nltk_chatbot_service`.
2. Runtime: **Docker** (otomatis pakai `Dockerfile`).
3. Instance: **Free**. Setelah live, catat URL-nya
   (mis. `https://batik-chatbot.onrender.com`).

### 3. Service app (repo `batik_trustyou_ecommerce`)
1. Render → **New → Web Service** → hubungkan repo `batik_trustyou_ecommerce`.
2. Runtime: **Docker**. Instance: **Free**.
3. Isi **Environment Variables** (ambil dari TiDB untuk DB):
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=            # generate lokal: php artisan key:generate --show
   APP_URL=https://<nama-app>.onrender.com
   DB_CONNECTION=mysql
   DB_HOST=<host-tidb>
   DB_PORT=4000              # TiDB=4000 (Aiven pakai port non-standar, mis. 12345)
   DB_DATABASE=<db-tidb>
   DB_USERNAME=<user-tidb>
   DB_PASSWORD=<password-tidb>
   MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt   # TiDB/Aiven wajib TLS
   SESSION_DRIVER=database
   QUEUE_CONNECTION=database
   CACHE_STORE=database
   CHATBOT_URL=https://batik-chatbot.onrender.com
   RUN_SEED=true      # set true sekali di deploy pertama, lalu ubah false
   ```
4. Deploy. Container akan migrate + seed otomatis lewat entrypoint.

> **Catatan cold start:** service Free di Render "tidur" setelah ~15 menit idle,
> jadi request pertama setelah nganggur bisa lambat (±30–60 detik). Karena Laravel
> memanggil chatbot dengan timeout 5 detik, chatbot yang baru "bangun" bisa bikin
> balasan bot gagal di percobaan pertama. Akali dengan uptime pinger gratis
> (UptimeRobot / cron-job.org, tanpa kartu) yang meng-ping `/up` dan `/health`
> tiap 5 menit supaya kedua service tetap hangat selama jam demo.

---

## C. VPS murah (always-on, tanpa kartu kredit)

Kalau jalur gratis (cold start, storage ephemeral) terasa kurang, VPS murah
memberi server yang **selalu hidup, disk persisten, dan kontrol penuh** — cukup
`docker compose up` sekali (ikuti **Bagian A**). Semua service (app + chatbot +
MySQL) jalan di satu mesin.

**Ukuran yang disarankan: RAM ≥ 2 GB** (buat build image + MySQL 8 + 2 container
jalan nyaman). RAM 512 MB–1 GB bisa kehabisan memori saat `docker compose build`.

### Provider lokal Indonesia (bayar QRIS / transfer / e-wallet — tanpa kartu) ⭐

Paling pas untuk mahasiswa: pembayaran Rupiah tanpa kartu kredit, server di
Indonesia (latency rendah).

| Provider | Harga mulai | Pembayaran |
|----------|-------------|------------|
| **Hideki Hoster** | ± Rp55 rb/bln (2 GB RAM / 2 core) | Transfer, e-wallet |
| **Biznet Gio** | ± Rp50 rb/bln | Transfer, kartu |
| **DomaiNesia** | ± Rp43 rb/bln (paket Lite — cek RAM) | Transfer, QRIS, e-wallet |
| **Qwords** | ± Rp75 rb/bln | Transfer, QRIS, e-wallet |
| **NataNetwork** | ± Rp40 rb/bln | **QRIS**, OVO, VA bank, transfer |

> Cek spesifikasi paketnya — ambil yang **RAM ≥ 2 GB**, meski harganya sedikit
> naik dari harga "mulai dari".

### Provider global (hemat, bisa bayar via PayPal — tanpa kartu)

| Provider | Harga | Spesifikasi | Catatan |
|----------|-------|-------------|---------|
| **Hetzner** (CX22) | ± €4/bln (~Rp70 rb) | 2 vCPU / **4 GB RAM** / 40 GB | Value terbaik; bayar bisa **PayPal** |
| **Contabo** (VPS S) | ± $6/bln | 4 vCPU / 8 GB RAM | RAM besar, disk lebih lambat; PayPal |
| **RackNerd** | ± $11–25/**tahun** | 1 core / 512 MB–1 GB | Termurah, tapi RAM kecil → build image di lokal lalu `docker save`/registry |

> Harga di atas indikatif & sering berubah (promo tahunan). Verifikasi saat
> mendaftar. Hetzner/Contabo menerima PayPal sehingga tetap bisa tanpa kartu.

Setelah punya VPS: SSH masuk, install Docker (`curl -fsSL https://get.docker.com | sh`),
clone kedua repo berdampingan, lalu ikuti **Bagian A**. Untuk HTTPS + domain
gratis, pasang DuckDNS + reverse proxy (Caddy/Nginx).

---

## D. Panduan langkah demi langkah: Deploy ke VPS Hideki Hoster

Panduan lengkap dari nol untuk pemula. Target: aplikasi online di
`http://IP-VPS:8000` (opsional pakai domain + HTTPS di langkah 9).

Semua service (app + chatbot + **MySQL**) jalan di **satu VPS** ini — tidak perlu
hosting database terpisah.

### 1. Pesan VPS di Hideki Hoster
1. Buka <https://hidekihoster.id/layanan-vps/> → pilih **SGP NVMe** (server
   Singapura, dekat ke Indonesia).
2. Ambil paket **RAM ≥ 4 GB** (mis. *Orbit* 6 GB) supaya `docker compose build`
   lega. Minimal 2 GB masih bisa, tapi wajib pakai swap (langkah 5).
3. Saat order, pilih OS **Ubuntu 24.04 LTS** (atau 22.04 LTS).
4. Bayar (transfer / e-wallet — tanpa kartu kredit).
5. Setelah aktif, cek **email / panel Hideki** untuk dapat: **IP publik**,
   user `root`, dan **password**. (Kalau perlu ganti OS, lakukan lewat panel VPS
   mereka — menu *Reinstall/Rebuild*.)

### 2. Login ke VPS lewat SSH
Dari laptop Anda (Windows: buka **PowerShell**; atau pakai PuTTY):

```bash
ssh root@IP-VPS
```
Ketik `yes` saat ditanya fingerprint, lalu masukkan password. Setelah masuk,
prompt berubah jadi `root@...:~#`. **Semua perintah berikut dijalankan di VPS.**

### 3. Update sistem & amankan seadanya
```bash
apt update && apt upgrade -y
# Firewall: izinkan SSH, web, dan port app
apt install -y ufw
ufw allow OpenSSH
ufw allow 80
ufw allow 443
ufw allow 8000
ufw --force enable
```

### 4. (Disarankan) Buat user non-root
```bash
adduser deploy                 # isi password
usermod -aG sudo deploy
```
Nanti setelah Docker terpasang, tambahkan user ke grup docker (langkah 6).

### 5. Tambah swap (penting untuk build, apalagi RAM 2 GB)
```bash
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab   # supaya tetap ada setelah reboot
```

### 6. Install Docker + Docker Compose
```bash
curl -fsSL https://get.docker.com | sh
docker compose version          # pastikan muncul versinya
usermod -aG docker deploy       # agar user deploy bisa docker tanpa sudo
```

### 7. Ambil kode: clone dua repo berdampingan
```bash
apt install -y git
mkdir -p /opt/batik && cd /opt/batik
git clone <URL-repo-ecommerce> batik_trustyou_ecommerce
git clone <URL-repo-chatbot>   nltk_chatbot_service
```
Struktur akhirnya harus:
```
/opt/batik/
├── batik_trustyou_ecommerce/   ← jalankan compose dari sini
└── nltk_chatbot_service/
```
> Kalau repo **private**: pakai `git clone https://<token>@github.com/...`, atau
> upload folder dari laptop pakai **WinSCP**/`scp`.

### 8. Konfigurasi & jalankan
```bash
cd /opt/batik/batik_trustyou_ecommerce

# a. Siapkan environment
cp .env.docker.example .env.docker
nano .env.docker
```
Di dalam `.env.docker`, ubah minimal:
- `APP_URL=http://IP-VPS:8000` (nanti ganti ke domain bila pakai langkah 9)
- `DB_PASSWORD` dan `DB_ROOT_PASSWORD` → **ganti** dari default `secret`/`rootsecret`
- `RUN_SEED=true` (biarkan true untuk pengisian data awal, sekali saja)

Simpan (`Ctrl+O`, `Enter`, `Ctrl+X`), lalu:
```bash
# b. Build image
docker compose build

# c. Generate APP_KEY -> salin hasilnya ke APP_KEY di .env.docker
docker compose run --rm app php artisan key:generate --show
nano .env.docker      # tempel: APP_KEY=base64:....

# d. Nyalakan semua service
docker compose up -d

# e. Pantau log (Ctrl+C untuk berhenti memantau)
docker compose logs -f app
```
Buka browser: **`http://IP-VPS:8000`** (toko) dan **`http://IP-VPS:8000/admin`**
(panel admin — akun dari `database/seeders/UserSeeder.php`).

Setelah data awal masuk, **matikan seeding** agar tidak dobel:
```bash
nano .env.docker           # ubah RUN_SEED=false
docker compose up -d        # terapkan
```

### 9. (Opsional) Domain gratis + HTTPS
Supaya bisa diakses `https://namamu...` tanpa `:8000`:

1. Buat subdomain gratis di **DuckDNS** (<https://www.duckdns.org>), arahkan ke
   **IP VPS** Anda (mis. `batik-ta.duckdns.org`).
2. Tambahkan reverse proxy **Caddy** (otomatis HTTPS). Buat file
   `docker-compose.override.yml` di folder yang sama:
   ```yaml
   services:
     caddy:
       image: caddy:2-alpine
       restart: unless-stopped
       depends_on: [app]
       ports:
         - "80:80"
         - "443:443"
       command: caddy reverse-proxy --from batik-ta.duckdns.org --to app:8000
       volumes:
         - caddy_data:/data
   volumes:
     caddy_data:
   ```
3. Ubah `APP_URL=https://batik-ta.duckdns.org` di `.env.docker`, lalu:
   ```bash
   docker compose up -d
   ```
   Caddy otomatis mengurus sertifikat SSL. Akses: **`https://batik-ta.duckdns.org`**.

### 10. Perawatan sehari-hari
```bash
# Update aplikasi setelah ada perubahan kode
cd /opt/batik/batik_trustyou_ecommerce
git pull && docker compose build && docker compose up -d

# Backup database (jalankan berkala) — password dibaca dari dalam container db
docker compose exec db sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" batik' > backup-$(date +%F).sql

# Restart / lihat status
docker compose restart app
docker compose ps

# Matikan semua (data tetap aman di volume)
docker compose down
```
> Gambar produk yang di-upload lewat panel admin tersimpan di volume
> `storage_public` (persisten). Backup DB + volume ini secara berkala untuk aman.

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

- **Database gratis tanpa kartu:** TiDB Cloud Serverless (kompatibel MySQL,
  always-on) direkomendasikan; alternatif Aiven MySQL. Keduanya **wajib TLS** —
  set `MYSQL_ATTR_SSL_CA` ke path CA bundle (di Render:
  `/etc/ssl/certs/ca-certificates.crt`).

- **SQLite** juga bisa dipakai (paling hemat, tanpa service DB): set
  `DB_CONNECTION=sqlite`. Tapi butuh disk persisten (self-host/VPS), karena file
  `database/database.sqlite` akan hilang di disk ephemeral PaaS gratis.

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
