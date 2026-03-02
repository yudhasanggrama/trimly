# 💈 Trimly

<p align="center">
  <a href="#" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Version-1.0.0--MVP-orange?style=for-the-badge" alt="Version">
  <img src="https://img.shields.io/badge/Laravel-11-red?style=for-the-badge&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/TailwindCSS-3.0-blue?style=for-the-badge&logo=tailwind-css" alt="Tailwind">
  <img src="https://img.shields.io/badge/Alpine.js-3.0-cyan?style=for-the-badge&logo=alpine.js" alt="Alpine">
</p>

<p align="center">
  <b>Stay Sharp, Stay On Time.</b>
</p>

---

## 📌 About Trimly

**Trimly** adalah platform reservasi barbershop modern berbasis web yang mengutamakan kecepatan, efisiensi, dan kenyamanan pengguna.

Dibangun menggunakan **Laravel 11**, **TailwindCSS v3**, dan **Alpine.js**, Trimly menghadirkan pengalaman *single-page booking* yang interaktif dengan visualisasi jadwal real-time yang bersih dan responsif.

Aplikasi ini dirancang untuk meminimalisir antrean fisik dan mencegah double booking melalui sistem validasi backend yang aman.

---

## ✨ Key Features (MVP)

### 👤 Customer Features

- **Interactive Date Slider**  
  Pemilihan tanggal dengan slider horizontal yang dinamis dan responsif.

- **Real-time Time Grid**  
  Visualisasi jam operasional dengan status:
  - 🟢 Tersedia  
  - 🔴 Full  
  - ⚫ End  

- **Digital Ticket Generation**  
  Tiket otomatis dibuat setelah booking berhasil untuk verifikasi di lokasi.

---

### 🛠 Admin Features

- **Live Dashboard Monitoring**  
  Auto-refresh setiap 5 detik untuk memantau antrean aktif.

- **Smart Cancellation System**  
  Jika admin melakukan pembatalan sebelum jadwal dimulai, slot otomatis kembali tersedia.

- **Booking Status Control**
  - `START` → Memulai layanan
  - `FINISH` → Menyelesaikan layanan
  - `CANCEL` → Membatalkan pesanan

---

## 🚀 Installation Guide

### 1️⃣ Requirements

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL / PostgreSQL

---

### 2️⃣ Clone Repository

```bash
git clone https://github.com/yourusername/trimly.git
cd trimly
```

---

### 3️⃣ Install Dependencies

```bash
composer install
npm install
npm run dev
```

---

### 4️⃣ Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

> Jangan lupa sesuaikan konfigurasi database pada file `.env`.

---

### 5️⃣ Database Migration

```bash
php artisan migrate
```

Jika ingin menggunakan seeder:

```bash
php artisan db:seed
```

---

### 6️⃣ Run Application

```bash
php artisan serve
```

Akses aplikasi melalui:

```
http://127.0.0.1:8000
```

---

## 📖 Usage Guide

### 👤 For Customers

1. Pilih bulan & tanggal pada slider.
2. Klik jam yang tersedia (warna terang).
3. Isi form konfirmasi pada modal.
4. Simpan tiket digital sebagai bukti booking.

---

### 🛠 For Admin

Akses melalui:

```
/admin
```

Admin dapat:

- Memulai layanan (`START`)
- Menyelesaikan layanan (`FINISH`)
- Membatalkan booking (`CANCEL`)

---

## 🧠 Technical Highlights

- Server-side validation untuk mencegah double booking.
- Atomic booking protection pada database level.
- UI reaktif menggunakan Alpine.js.
- Clean Blade + Tailwind architecture.
- Optimized for MVP production readiness.

---

## ✅ Definition of Done (DoD)

- [x] Booking tersimpan aman di database  
- [x] Proteksi double booking aktif  
- [x] UI/UX responsif dan interaktif  
- [x] Dashboard admin auto-refresh real-time  
- [x] Smart cancellation mengembalikan slot otomatis  

---

## 📄 License

Trimly adalah open-source software yang dilisensikan di bawah **MIT License**.

---

<p align="center">
  <b>Trimly — Modern Barbershop Booking Solution</b>
</p>