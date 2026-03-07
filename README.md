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

## 👤 Default Admin

Dibuat via `UserSeeder`:

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
