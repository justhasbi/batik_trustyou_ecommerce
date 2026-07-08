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

### POST `/checkout`

**Terproteksi (wajib login).** Membuat pesanan dari keranjang user, mengurangi stok, lalu mengosongkan keranjang.

| Field | Tipe | Wajib |
|-------|------|-------|
| `recipient_name` | string | ya |
| `recipient_phone` | string | ya |
| `shipping_address` | string | ya |
| `courier` | string | tidak |
| `shipping_cost` | number | tidak |

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
    "courier": "JNE",
    "shipping_cost": 20000
  }'
```

Keranjang kosong → `422` `{ "message": "Keranjang kosong." }`.

Response `200`:

```json
{
  "data": {
    "id": 12,
    "order_number": "INV-AB12CD34",
    "status": "pending",
    "shipping_status": "not_shipped",
    "subtotal": 320000,
    "shipping_cost": 20000,
    "total": 340000,
    "recipient_name": "Budi Santoso",
    "recipient_phone": "081211112222",
    "shipping_address": "Jl. Melati No. 10, Bandung",
    "courier": "JNE",
    "tracking_number": null,
    "created_at": "2026-07-04 10:00:00",
    "items": [
      { "product_name": "Kemeja Batik Pria Mega Mendung", "size": "M", "price": 320000, "quantity": 1, "subtotal": 320000 }
    ]
  }
}
```

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
| POST | `/checkout` | Bearer |
| GET | `/orders` | Bearer |
| GET | `/orders/{id}` | Bearer |
| POST | `/chat/start` | tamu/Bearer |
| POST | `/chat/message` | tamu/Bearer |
| POST | `/chat/admin` | tamu/Bearer |