# Batik TrustYou — Dokumentasi API

Referensi endpoint REST API backend Laravel.

- **Base URL (development):** `http://localhost:8000/api`
- **Format:** JSON
- **Autentikasi:** token Bearer (Laravel Sanctum)

---

## Konvensi umum

### Header

| Header | Kapan | Nilai |
|--------|-------|-------|
| `Accept` | selalu | `application/json` |
| `Content-Type` | request dengan body | `application/json` |
| `Authorization` | endpoint terproteksi | `Bearer {token}` |
| `X-Cart-Token` | endpoint keranjang & chat (tamu) | UUID unik per perangkat |

> `X-Cart-Token` dipakai agar tamu (belum login) memiliki keranjang persisten. Saat login, keranjang tamu otomatis digabungkan ke akun.

### Pembungkus `data`

Endpoint yang memakai API Resource membungkus payload dalam `data`. Resource tunggal → `{ "data": { ... } }`. Koleksi (dengan paginasi) → `{ "data": [ ... ], "links": {...}, "meta": {...} }`. Endpoint auth dan chat mengembalikan JSON langsung (tanpa `data`).

### Format error

Validasi gagal (HTTP `422`):

```json
{
  "message": "The email field is required.",
  "errors": { "email": ["The email field is required."] }
}
```

Belum terautentikasi (HTTP `401`): `{ "message": "Unauthenticated." }`
Tidak diizinkan / gagal aturan (HTTP `422`/`403`/`404`): `{ "message": "..." }`

---

## 1. Autentikasi

### POST `/register`

Publik. Membuat akun pelanggan dan mengembalikan token.

| Field | Tipe | Wajib | Catatan |
|-------|------|-------|---------|
| `name` | string | ya | |
| `email` | string | ya | unik |
| `phone` | string | tidak | |
| `password` | string | ya | min 8, harus cocok dengan konfirmasi |
| `password_confirmation` | string | ya | |

Request:

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "phone": "081211112222",
    "password": "rahasia123",
    "password_confirmation": "rahasia123"
  }'
```

Response `201`:

```json
{
  "user": { "id": 5, "name": "Budi Santoso", "email": "budi@example.com", "phone": "081211112222", "is_admin": false },
  "token": "12|aBcD3fGh..."
}
```

### POST `/login`

Publik. Mengembalikan user + token.

| Field | Tipe | Wajib |
|-------|------|-------|
| `email` | string | ya |
| `password` | string | ya |

Request:

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{ "email": "budi@example.com", "password": "rahasia123" }'
```

Response `200` (sama seperti register). Kredensial salah → `422`.

### GET `/me`

Terproteksi. Mengembalikan user saat ini.

Request:

```bash
curl http://localhost:8000/api/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 12|aBcD3fGh..."
```

Response `200`:

```json
{ "user": { "id": 5, "name": "Budi Santoso", "email": "budi@example.com" } }
```

### POST `/logout`

Terproteksi. Menghapus token yang sedang dipakai.

Request:

```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 12|aBcD3fGh..."
```

Response `200`: `{ "message": "Logout berhasil." }`

---

## 2. Produk

### GET `/products`

Publik. Daftar produk aktif (paginasi 12/hal).

| Param (query) | Contoh | Fungsi |
|-------|--------|--------|
| `search` | `?search=parang` | cari berdasarkan nama |
| `category` | `?category=batik-pria` | filter berdasarkan slug kategori |
| `page` | `?page=2` | halaman |

Request:

```bash
# Semua produk
curl "http://localhost:8000/api/products" -H "Accept: application/json"

# Dengan filter & pencarian
curl "http://localhost:8000/api/products?category=batik-pria&search=parang&page=1" \
  -H "Accept: application/json"
```

Response `200`:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Kemeja Batik Pria Parang Klasik",
      "slug": "kemeja-batik-pria-parang-klasik",
      "description": "…",
      "price": 285000,
      "motif": "Parang",
      "fabric_type": "cap",
      "category": { "id": 1, "name": "Batik Pria" },
      "primary_image": "https://…/parang-1.jpg"
    }
  ],
  "links": { "first": "…", "last": "…", "prev": null, "next": "…" },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 12, "total": 8 }
}
```

### GET `/products/{slug}`

Publik. Detail satu produk beserta seluruh gambar dan ukuran.

Request:

```bash
curl "http://localhost:8000/api/products/kemeja-batik-pria-parang-klasik" \
  -H "Accept: application/json"
```

Response `200`:

```json
{
  "data": {
    "id": 1,
    "name": "Kemeja Batik Pria Parang Klasik",
    "slug": "kemeja-batik-pria-parang-klasik",
    "description": "…",
    "price": 285000,
    "motif": "Parang",
    "fabric_type": "cap",
    "category": { "id": 1, "name": "Batik Pria" },
    "primary_image": "https://…/parang-1.jpg",
    "images": [
      { "id": 1, "path": "https://…/parang-1.jpg", "is_primary": true },
      { "id": 2, "path": "https://…/parang-2.jpg", "is_primary": false }
    ],
    "sizes": [
      { "id": 10, "size": "S", "stock": 12 },
      { "id": 11, "size": "M", "stock": 20 },
      { "id": 12, "size": "L", "stock": 18 },
      { "id": 13, "size": "XL", "stock": 10 }
    ]
  }
}
```

Produk tidak ditemukan → `404`.

---

## 3. Keranjang

Berfungsi untuk tamu (kirim `X-Cart-Token`) maupun user login (kirim `Authorization`).

### GET `/cart`

Isi keranjang saat ini.

Request:

```bash
# Sebagai tamu
curl http://localhost:8000/api/cart \
  -H "Accept: application/json" \
  -H "X-Cart-Token: 123e4567-e89b-12d3-a456-426614174000"

# Sebagai user login
curl http://localhost:8000/api/cart \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 12|aBcD3fGh..."
```

Response `200`:

```json
{
  "data": {
    "id": 3,
    "items": [
      {
        "id": 21,
        "product_id": 2,
        "product_name": "Kemeja Batik Pria Mega Mendung",
        "slug": "kemeja-batik-pria-mega-mendung",
        "image": "https://…/mega-1.jpg",
        "size": "M",
        "size_id": 15,
        "price": 320000,
        "quantity": 1,
        "line_total": 320000
      }
    ],
    "count": 1,
    "total": 320000
  }
}
```

### POST `/cart/items`

Menambah item ke keranjang.

| Field | Tipe | Wajib | Catatan |
|-------|------|-------|---------|
| `product_id` | int | ya | harus ada |
| `product_size_id` | int | tidak | untuk produk berukuran |
| `quantity` | int | ya | min 1 |

Request:

```bash
curl -X POST http://localhost:8000/api/cart/items \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Cart-Token: 123e4567-e89b-12d3-a456-426614174000" \
  -d '{ "product_id": 2, "product_size_id": 15, "quantity": 1 }'
```

Stok tidak cukup → `422` `{ "message": "Stok ukuran tidak mencukupi." }`. Sukses `200`: objek keranjang (seperti GET `/cart`).

### PATCH `/cart/items/{item}`

Mengubah jumlah item.

| Field | Tipe | Wajib |
|-------|------|-------|
| `quantity` | int (min 1) | ya |

Request:

```bash
curl -X PATCH http://localhost:8000/api/cart/items/21 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Cart-Token: 123e4567-e89b-12d3-a456-426614174000" \
  -d '{ "quantity": 3 }'
```

Response: objek keranjang terbaru.

### DELETE `/cart/items/{item}`

Menghapus item.

Request:

```bash
curl -X DELETE http://localhost:8000/api/cart/items/21 \
  -H "Accept: application/json" \
  -H "X-Cart-Token: 123e4567-e89b-12d3-a456-426614174000"
```

Response: objek keranjang terbaru.

---

## 4. Checkout & Pesanan

Alur pesanan lengkap: dari checkout, payment, sampai tracking pengiriman. Perlu dicatat, seluruh proses payment dan shipping di sini masih dummy untuk keperluan demo — belum terhubung ke payment gateway atau courier sungguhan.

### GET `/checkout/options`

Terproteksi. Mengembalikan pilihan payment method dan shipping option yang dipakai di halaman checkout.

Response `200`:

```json
{
  "payment_methods": [
    { "id": "qris", "name": "QRIS", "type": "qr", "desc": "...", "channels": [] },
    { "id": "ewallet", "name": "E-Wallet", "type": "qr", "channels": ["GoPay", "OVO", "DANA", "ShopeePay"] },
    { "id": "bank_transfer", "name": "Transfer Bank (Virtual Account)", "type": "va", "channels": ["BCA", "BNI", "BRI", "Mandiri"] },
    { "id": "cod", "name": "Bayar di Tempat (COD)", "type": "cod", "channels": [] }
  ],
  "shipping_options": [
    { "id": "jne_reg", "courier": "JNE", "service": "Reguler", "cost": 15000, "etd": "2-3 hari" }
  ]
}
```

### POST `/checkout`

Terproteksi (wajib login). Mengubah isi keranjang menjadi order: stok dikurangi, keranjang dikosongkan, lalu payment instruction dibuat sesuai metode yang dipilih. Ongkos kirim mengikuti `shipping_option_id` dan dihitung di server, jadi nilainya tidak bisa diubah dari sisi client.

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `recipient_name` | string | ya | |
| `recipient_phone` | string | ya | |
| `shipping_address` | string | ya | |
| `shipping_option_id` | string | ya | salah satu `id` dari `/checkout/options` |
| `payment_method` | string | ya | `qris` \| `ewallet` \| `bank_transfer` \| `cod` |
| `payment_channel` | string | kondisional | wajib bila metode punya `channels` (mis. `BCA`, `GoPay`) |

Request:

```bash
curl -X POST http://localhost:8000/api/checkout \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 12|aBcD3fGh..." \
  -d '{
    "recipient_name": "Budi Santoso",
    "recipient_phone": "081211112222",
    "shipping_address": "Jl. Melati No. 10, Bandung",
    "shipping_option_id": "jne_reg",
    "payment_method": "qris"
  }'
```

Keranjang kosong → `422` `{ "message": "Keranjang kosong." }`.

Response `200` (contoh QRIS):

```json
{
  "data": {
    "id": 12,
    "order_number": "INV-AB12CD34",
    "status": "pending",
    "status_label": "Menunggu pembayaran",
    "shipping_status": "not_shipped",
    "shipping_label": "Belum dikirim",
    "subtotal": 320000,
    "shipping_cost": 15000,
    "total": 335000,
    "courier": "JNE",
    "shipping_method": "Reguler",
    "tracking_number": null,
    "payment_method": "qris",
    "payment_channel": null,
    "transaction_code": "TRX-260816-AB12CD",
    "va_number": null,
    "qr_payload": "BATIKTRUSTYOU|INV-AB12CD34|TRX-260816-AB12CD|335000|qris",
    "payment_expires_at": "2026-08-17 09:00:00",
    "paid_at": null,
    "is_paid": false,
    "shipping_timeline": [ { "key": "not_shipped", "label": "Belum dikirim", "done": true } ],
    "items": [
      { "product_name": "Kemeja Batik Pria Mega Mendung", "size": "M", "price": 320000, "quantity": 1, "subtotal": 320000 }
    ]
  }
}
```

Beberapa catatan soal payment method: untuk `bank_transfer` yang dikembalikan adalah `va_number`, bukan `qr_payload`. Sementara `cod` tidak melewati online payment sama sekali — order-nya langsung berstatus `processing`.

### GET `/orders`

Terproteksi. Daftar pesanan milik user (terbaru dulu).

Request:

```bash
curl http://localhost:8000/api/orders \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 12|aBcD3fGh..."
```

Response: koleksi OrderResource (`{ "data": [ ... ] }`).

### GET `/orders/{id}`

Terproteksi. Detail satu pesanan milik user (termasuk `items`).

Request:

```bash
curl http://localhost:8000/api/orders/12 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 12|aBcD3fGh..."
```

Bukan milik user → `404`.

### POST `/orders/{id}/pay`

Terproteksi. Menandai order sebagai sudah dibayar. Karena payment-nya masih dummy, endpoint inilah yang menggantikan peran callback dari payment gateway: `status` berubah menjadi `paid`, `paid_at` terisi, dan `shipping_status` naik ke `packed`.

```bash
curl -X POST http://localhost:8000/api/orders/12/pay \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 12|aBcD3fGh..."
```

Endpoint menolak dengan `422` bila order sudah dibayar, memakai COD, sudah lewat batas waktu, atau sudah dibatalkan. Kalau berhasil, response-nya mengembalikan data order versi terbaru.

### POST `/orders/{id}/shipping/advance`

Terproteksi. Memajukan shipping status satu langkah, mengikuti urutan `not_shipped` → `packed` → `shipped` → `in_transit` → `delivered`. Endpoint ini dipakai untuk meniru perjalanan paket tanpa courier sungguhan. Tracking number dibuat otomatis begitu status masuk `shipped`, dan saat mencapai `delivered` status order ikut berubah menjadi `completed` — pertanda transaksi sudah selesai.

```bash
curl -X POST http://localhost:8000/api/orders/12/shipping/advance \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 12|aBcD3fGh..."
```

Kalau order belum dibayar atau sudah terlanjur `delivered`, request ditolak dengan `422`.

### POST `/orders/{id}/cancel`

Terproteksi. Membatalkan order yang belum dibayar sekaligus mengembalikan stoknya. Setelah order dibayar atau sudah masuk proses shipping, pembatalan tidak lagi diizinkan (`422`).

---

## 5. Chatbot

Bisa diakses tamu; status login dideteksi otomatis bila token dikirim.

### POST `/chat/start`

Memulai sesi. Menu pra-chat: pilih `bot` atau `admin`.

| Field | Tipe | Wajib | Nilai |
|-------|------|-------|-------|
| `mode` | string | ya | `bot` \| `admin` |

Request:

```bash
curl -X POST http://localhost:8000/api/chat/start \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Cart-Token: 123e4567-e89b-12d3-a456-426614174000" \
  -d '{ "mode": "bot" }'
```

Response `200`:

```json
{ "session_id": 7, "mode": "bot", "reply": "Halo! Saya asisten Batik TrustYou…" }
```

### POST `/chat/message`

Mengirim pesan dalam sesi.

| Field | Tipe | Wajib | Catatan |
|-------|------|-------|---------|
| `session_id` | int | ya | dari `/chat/start` |
| `message` | string | ya | maks 1000 |
| `tinggi` | number | tidak | cm, untuk rekomendasi ukuran |
| `berat` | number | tidak | kg |

Request (rekomendasi ukuran, tamu):

```bash
curl -X POST http://localhost:8000/api/chat/message \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{ "session_id": 7, "message": "tinggi 170 berat 65 ukuran apa?" }'
```

Request (status pengiriman, user login):

```bash
curl -X POST http://localhost:8000/api/chat/message \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 12|aBcD3fGh..." \
  -d '{ "session_id": 7, "message": "pesanan saya sudah dikirim belum?" }'
```

Response mode bot `200`:

```json
{
  "reply": "Untuk tinggi 170 cm dan berat 65 kg, ukuran yang direkomendasikan adalah M.",
  "intent": "rekomendasi_ukuran",
  "suggest_admin": false,
  "require_login": false
}
```

Mode admin `200`: `{ "reply": null, "pending_admin": true }`.

### POST `/chat/admin`

Mengalihkan sesi dari bot ke admin.

| Field | Tipe | Wajib |
|-------|------|-------|
| `session_id` | int | ya |

Request:

```bash
curl -X POST http://localhost:8000/api/chat/admin \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{ "session_id": 7 }'
```

Response `200`: `{ "mode": "admin", "reply": "Baik, Anda saya alihkan ke admin…" }`

---

## Ringkasan endpoint

| Metode | Endpoint | Auth |
|--------|----------|------|
| POST | `/register` | publik |
| POST | `/login` | publik |
| POST | `/logout` | Bearer |
| GET | `/me` | Bearer |
| GET | `/products` | publik |
| GET | `/products/{slug}` | publik |
| GET | `/cart` | tamu/Bearer |
| POST | `/cart/items` | tamu/Bearer |
| PATCH | `/cart/items/{item}` | tamu/Bearer |
| DELETE | `/cart/items/{item}` | tamu/Bearer |
| GET | `/checkout/options` | Bearer |
| POST | `/checkout` | Bearer |
| GET | `/orders` | Bearer |
| GET | `/orders/{id}` | Bearer |
| POST | `/orders/{id}/pay` | Bearer |
| POST | `/orders/{id}/shipping/advance` | Bearer |
| POST | `/orders/{id}/cancel` | Bearer |
| POST | `/chat/start` | tamu/Bearer |
| POST | `/chat/message` | tamu/Bearer |
| POST | `/chat/admin` | tamu/Bearer |