# TRIMLY 💈

Sistem manajemen antrean barbershop berbasis web. Dibangun dengan Laravel 10, Alpine.js, Node.js dan Tailwind CSS.

---

## ✨ Fitur

- **Booking Guest** — Pelanggan booking tanpa perlu login, cukup isi nama, nomor WhatsApp, dan email
- **Realtime Slot** — Slot jam otomatis berubah (Tersedia → Penuh → Selesai) tanpa refresh halaman
- **Admin Dashboard** — Manajemen antrean lengkap dengan stats, grafik tren, dan filter
- **Reschedule** — Admin dapat reschedule booking dengan slot yang tersedia secara dinamis
- **Email Notifikasi** — Email otomatis dikirim saat booking berhasil, reschedule, atau dibatalkan
- **Kapasitas Dinamis** — Admin dapat mengatur kapasitas per jam (1–20 barber)
- **Anti Double Booking** — Cek duplikat berdasarkan nomor HP atau email
- **Live Update Admin** — Dashboard admin refresh otomatis setiap 5 detik via JSON endpoint

---

## 🛠 Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 10, PHP 8.2 |
| Frontend | Alpine.js, Tailwind CSS |
| Database | MySQL |
| Queue | Laravel Queue (database driver) |
| Email | SMTP (Gmail / Mailgun / Brevo) |
| Chart | Chart.js 4 |
| Deploy | Railway |

---

## 📁 Struktur Penting

```
app/
├── Http/Controllers/
│   ├── BookingController.php   # Logika booking, reschedule, cancel, admin
│   ├── AuthController.php      # Login admin
│   └── AdminController.php     # Logika untuk admin dashboard
├── Mail/
│   ├── BookingSuccessMail.php
│   ├── BookingRescheduledMail.php
│   └── BookingCancelledMail.php
├── Models/
│   ├── Booking.php
│   ├── Customer.php
│   └── Setting.php
database/
├── migrations/
└── seeders/
    ├── DatabaseSeeder.php
    └── UserSeeder.php
resources/views/
├── home.blade.php              # Halaman booking publik
├── admin.blade.php             # Dashboard admin
└── layouts/app.blade.php       # Layout utama (wajib ada meta csrf-token)
```

---

## ⚙️ Instalasi Lokal

### 1. Clone & Install

```bash
git clone https://github.com/username/trimly.git
cd trimly
composer install
npm install
```

### 2. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME=TRIMLY
APP_ENV=local
APP_URL=http://localhost
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trimly
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=480

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your@gmail.com
MAIL_FROM_NAME=TRIMLY
```

### 3. Migrasi & Seeder

```bash
php artisan migrate
php artisan queue:table
php artisan migrate
php artisan db:seed
```

### 4. Jalankan Queue Worker

```bash
php artisan queue:work
```

### 5. Jalankan Server

```bash
php artisan serve
```

---

## 🗄 Database

### Tabel Utama

| Tabel | Deskripsi |
|---|---|
| `users` | Admin login |
| `customers` | Data pelanggan (nama, phone, email) |
| `bookings` | Data booking (customer_id, date, time, status) |
| `settings` | Konfigurasi kapasitas per jam |
| `jobs` | Queue jobs untuk email |
| `failed_jobs` | Queue jobs yang gagal |
| `sessions` | Session login |

### Status Booking

| Status | Deskripsi |
|---|---|
| `active` | Booking dikonfirmasi, menunggu giliran |
| `on-progress` | Sedang dilayani |
| `completed` | Selesai |

---

## 🔌 Routes Penting

```php
// Guest
GET  /                          # Halaman booking publik
POST /booking                   # Simpan booking baru
GET  /?date=2026-03-06          # Filter tanggal
GET  /?date=2026-03-06&json=1   # Polling realtime slot (JSON)

// Admin
GET  /admin                     # Dashboard admin
GET  /admin/live-data           # Polling realtime data (JSON)
GET  /admin/available-slots     # Slot tersedia untuk reschedule
PUT  /admin/reschedule/{id}     # Reschedule booking
POST /admin/start/{id}          # Ubah status → on-progress
POST /admin/complete/{id}       # Ubah status → completed
POST /admin/cancel/{id}         # Batalkan booking (delete)
POST /admin/settings            # Update kapasitas
GET  /admin/csrf-token          # Refresh CSRF token
```

---

## 📧 Email Queue

Email dikirim via Laravel Queue agar tidak memblok response.

**Penting:** Mail class menggunakan **primitive data** (string) bukan model, untuk menghindari error serialisasi saat model sudah dihapus (kasus cancel booking).

```php
// ✅ Benar — pakai primitive
Mail::to($email)->queue(new BookingCancelledMail(
    $booking->customer->name,   // string
    $booking->booking_date,     // string
    $booking->booking_time      // string
));

// ❌ Salah — model bisa sudah dihapus saat worker jalan
Mail::to($email)->queue(new BookingCancelledMail($booking));
```

**Jalankan worker di lokal:**
```bash
php artisan queue:work --sleep=3 --tries=3
```

**Queue sync (tanpa worker, untuk development):**
```env
QUEUE_CONNECTION=sync
```

---

## 🔐 CSRF Token

Meta tag wajib ada di `layouts/app.blade.php`:

```html
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
```

JavaScript membaca token dari meta tag (bukan hardcode) agar selalu fresh:

```javascript
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}
```

Token di-refresh otomatis setiap 10 menit via endpoint `/admin/csrf-token`.

Set `SESSION_LIFETIME` lebih panjang agar admin tidak sering login ulang:

```env
SESSION_LIFETIME=480  # 8 jam
```

---

## ⚡ Realtime

### Halaman Publik (home.blade.php)

Polling setiap **5 detik** ke `/?date=...&json=1`:

```javascript
setInterval(() => refreshSlots(), 5000);
```

Slot jam dikontrol Alpine.js — reaktif tanpa reload:
- `isBooked(slot)` — cek dari server apakah sudah penuh
- `isPast(slot)` — cek dari waktu server apakah sudah lewat

### Dashboard Admin (admin.blade.php)

Polling setiap **5 detik** ke `/admin/live-data`:
- Update stats cards (Hari Ini, Active, On Progress, Selesai)
- Rebuild tabel desktop dan card mobile via JavaScript
- Menggunakan `AbortController` agar request tidak menumpuk

---

## 🚀 Deploy ke Railway

### 1. Persiapan File

**`Procfile`** (taruh di root project):
```
web: php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT
worker: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

**`nixpacks.toml`** (taruh di root project):
```toml
[phases.setup]
nixPkgs = [
    "php83", "php83Extensions.pdo", "php83Extensions.pdo_mysql",
    "php83Extensions.mbstring", "php83Extensions.tokenizer",
    "php83Extensions.xml", "php83Extensions.ctype",
    "php83Extensions.fileinfo", "php83Extensions.curl",
    "php83Extensions.openssl", "php83Extensions.bcmath", "composer"
]

[phases.install]
cmds = ["composer install --no-dev --optimize-autoloader --no-scripts"]

[phases.build]
cmds = [
    "php artisan config:cache",
    "php artisan route:cache",
    "php artisan view:cache"
]

[start]
cmd = "php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT"
```

### 2. Langkah Deploy

1. Push project ke **GitHub**
2. Buka [railway.app](https://railway.app) → **New Project** → **Deploy from GitHub**
3. Tambah database: **+ New** → **Database** → **Add MySQL**
4. Klik service **web** → tab **Variables** → set environment variables
5. Untuk queue worker: tambah **New Service** dari repo yang sama → ubah start command ke:
   ```
   php artisan queue:work --sleep=3 --tries=3
   ```
6. Railway otomatis deploy setiap `git push`

### 3. Environment Variables di Railway

```env
APP_NAME=TRIMLY
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:xxxx           # php artisan key:generate --show
APP_URL=https://xxxx.railway.app
APP_TIMEZONE=Asia/Jakarta

LOG_CHANNEL=stderr
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=480
CACHE_DRIVER=file

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your@gmail.com
MAIL_FROM_NAME=TRIMLY
```

> **Catatan:** Jangan gunakan tanda kutip `"` pada nilai variabel di Railway dashboard.

### 4. Troubleshooting Deploy

| Error | Solusi |
|---|---|
| `table_schema = 'forge'` | Pastikan `DB_DATABASE` ter-resolve dari MySQL plugin, cek tidak ada `.env` di repo |
| `Connection refused` | Pastikan MySQL plugin sudah dibuat dan variable reference benar (`${{MySQL.MYSQLHOST}}`) |
| `No version available for php 8.1` | Ganti ke `php83` di `nixpacks.toml` dan ubah `composer.json` ke `"php": "^8.2"` |
| `EOF / build timeout` | Clear build cache di Railway Settings, tambah `--no-scripts` di composer install |
| Page Expired (419) | Pastikan meta `csrf-token` ada di `<head>`, token dibaca dari meta tag bukan hardcode |

---

## 👤 Default Admin

Dibuat via `UserSeeder`:

| Field | Value |
|---|---|
| Email | `admin@trimly.com` |
| Password | `admin123` |
| Role | `admin` |

> Ganti password setelah pertama login!

---

## 📝 Catatan Pengembangan

- **Timezone:** Selalu gunakan `Asia/Jakarta` di `config/app.php` dan `.env`
- **Seeder:** Menggunakan `firstOrCreate` agar aman dijalankan berulang saat deploy
- **Queue:** Gunakan `QUEUE_CONNECTION=sync` saat development untuk debugging mudah
- **CSRF:** Jangan hardcode token di JavaScript, selalu baca dari meta tag
- **Model Serialization:** Mail class harus menerima primitive (string), bukan Eloquent model, terutama untuk BookingCancelledMail karena booking sudah dihapus sebelum email dikirim

---

## 📄 Lisensi

MIT License — bebas digunakan dan dimodifikasi.
