# ☕ Solstice Coffee — Coffee Shop Order System

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![Database](https://img.shields.io/badge/Database-PostgreSQL-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Project](https://img.shields.io/badge/Project-UKK%20RPL-6B4F3A?style=for-the-badge)](https://github.com/SolsticeTeam710/Solstice-)

> Proyek Uji Kompetensi Keahlian Rekayasa Perangkat Lunak / PPLG — Tahun Ajaran 2026/2027  
> **Tema:** Local Digital Solution & bull 
> **Mitra:** Sistem Pemesanan dan Operasional Kedai Kopi - Solstice Coffee

---

## 📌 1. Latar Belakang dan Solusi

Solstice Coffe melayani minuman kopi, non-kopi, dan makanan. Proses melihat menu, mencatat pesanan, mengelola antrean, memantau stok, serta membuat rekap penjualan masih menjadi masalah yang masih dikelola operasional kedai secara manual.
Solstice Coffee membutuhkan alur yang menghubungkan pemesanan pelanggan, pemrosesan pesanan yang dikelola kasir, pengelolaan menu dan stok, serta pemantauan penjualan. Aplikasi menyediakan menu melalui QR meja, pencatatan pesanan dengan Order ID, verifikasi pesanan, dan panel administrasi.

### Fitur utama

- **Pelanggan:** melihat menu, memesan dari QR meja tanpa login, mengisi nama, catatan (opsional), memilih metode pembayaran CASH atau QRIS, dan memeriksa status pesanan.
- **Kasir:** mencari pesanan, melihat rincian sebelum verifikasi, mengonfirmasi CASH dengan nominal uang yang cukup, mengonfirmasi QRIS secara manual, serta mencetak struk dari browser.
- **Admin:** mengelola menu, kategori, akun staf, stok menu dan batas minimum, serta laporan penjualan dan ekspor CSV.

## 🛠️ 2. Tech Stack & Architecture

| Backend | PHP 8.2+, Laravel 12 |
| frontend | Vite 7, Tailwind CSS 4, Blade, JavaScript, CSS |
| Database | PostgreSQL, termasuk Supabase PostgreSQL |
| Security | Laravel Hash (bcrypt), Eloquent/Query Builder (parameter binding), CSRF Middleware, Authentication & Role Middleware, Rate Limiting. |


## 🚀 3. Instalasi Lokal

Ikuti langkah berikut untuk menjalankan aplikasi di lingkungan pengembang lokal:

### Prasyarat Sistem:

- PHP 8.2+ dengan ekstensi yang diperlukan Laravel dan pdo_pgsql
- Composer
- PostgreSQL lokal atau database PostgreSQL yang dapat diakses
- Node.js, npm, dan Git

### Langkah Installasi:

```powershell
# 1. Clone repository
git clone https://github.com/SolsticeTeam710/Solstice-.git
Set-Location .\Solstice-\Solstice

# 2. Pasang dependensi PHP dan buat file konfigurasi lokal
composer install
Copy-Item .env.example .env

# 3. Salin konfigurasi environment
Copy-Item .env.example .env

# 4. Buat application key
php artisan key:generate
```

```dotenv
# 5. Atur konfigurasi aplikasi dan database pada file .env
APP_NAME="Solstice_Coffe"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=solstice_db
DB_USERNAME=postgres
DB_PASSWORD=password_database
```

Lanjutkan instalasi:

```powershell
# 6. Jalankan migration dan seeder pada database kosong
php artisan migrate --seed

# 7. Hubungkan storage untuk file aplikasi
php artisan storage:link

# 8. Build asset dan jalankan server lokal
npm ci
npm run build
php artisan serve
```
