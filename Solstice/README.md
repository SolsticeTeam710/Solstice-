# Solstice Coffee

Aplikasi berbasis web untuk pemesanan dari meja dan pengelolaan operasional kedai Solstice Coffee. Pelanggan membuka menu lewat QR meja, kasir memproses pesanan dan pembayaran, lalu admin mengelola menu, pengguna, stok, serta laporan.


## Fitur

### Pelanggan

- Melihat menu, kategori, harga, dan ketersediaan.
- Memesan dari QR meja 1–4, memilih jumlah, memasukkan nama, dan menambahkan catatan.
- Memilih metode CASH atau QRIS simulasi.
- Melihat status pesanan menggunakan Order ID.

### Kasir

- Masuk menggunakan akun kasir.
- Melihat, mencari, dan membuka detail pesanan.
- Mengonfirmasi pembayaran CASH dengan nominal yang diterima dan perhitungan kembalian.
- Mengonfirmasi QRIS secara manual untuk kebutuhan simulasi.
- Menolak pesanan yang masih menunggu konfirmasi.
- Melihat riwayat/status pesanan dan mencetak struk dari browser.

### Admin

- Mengelola menu dan kategori.
- Mengelola akun admin/kasir dan status akun.
- Mengatur stok menu serta batas minimum stok.
- Melihat stok menipis.
- Melihat ringkasan penjualan berdasarkan periode dan mengunduh CSV.

## Alur Pesanan

1. Pelanggan membuka URL meja, misalnya /pesan?meja=1, lalu memilih menu.
2. Server memvalidasi nomor meja, menu, stok, dan data pesanan. Pesanan yang berhasil dibuat berstatus pending.
3. Kasir memeriksa pesanan lalu mengonfirmasi atau menolaknya.
4. Saat pesanan dikonfirmasi, stok menu dikurangi dan meja dikosongkan. Pesanan berstatus selesai. Jika ditolak, pesanan berstatus ditolak dan meja dikosongkan.
5. Pelanggan dapat memeriksa status dengan Order ID.

### Batasan Pembayaran dan Laporan Saat Ini

- CASH dicatat pada tabel pembayaran, termasuk nominal bayar dan kembalian.
- QRIS pada project ini hanya simulasi pemindaian. Tidak ada payment gateway, transaksi uang nyata, atau pencatatan pembayaran QRIS otomatis.
- Karena laporan dihitung dari data pembayaran, konfirmasi QRIS simulasi saat ini tidak masuk ke omzet dan transaksi pada laporan.
- Migration tabel laporan tersedia, tetapi halaman laporan saat ini menghitung data dari tabel pembayaran, pesanan, dan detail_pesanan; aplikasi tidak mengisi tabel laporan sebagai proses terpisah.
- Stok yang dikelola adalah stok produk/menu, bukan stok bahan baku.

## Teknologi

| Bagian | Teknologi |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12 |
| Database | PostgreSQL |
| Tampilan | Blade, JavaScript, CSS |
| Asset build | Vite 7, Tailwind CSS 4 |
| Pemindaian QR | html5-qrcode |

## Struktur Data

Migration project membuat tabel berikut:

- users — akun dan role admin/kasir.
- kategori — kategori menu.
- menu — harga, status, foto, stok, dan batas minimum.
- pesanan — Order ID, pelanggan, meja, metode pembayaran, total, dan status.
- detail_pesanan — menu, jumlah, harga satuan, dan subtotal per pesanan.
- pembayaran — rincian pembayaran yang dicatat saat proses kasir.
- meja — nomor meja, pesanan aktif, dan status meja.
- laporan — tabel ringkasan yang tersedia pada schema; halaman laporan saat ini menggunakan query dari tabel transaksi.

Relasi dan aturan foreign key ada di database/migrations/ dan model pada app/Models/.

## Persyaratan

- PHP 8.2 atau lebih baru dengan ekstensi Laravel dan pdo_pgsql.
- Composer.
- PostgreSQL lokal atau database PostgreSQL yang dapat diakses, seperti project Supabase.
- Node.js dan npm.

## Menjalankan Secara Lokal

Contoh perintah berikut menggunakan Windows PowerShell.

1. Clone repository dan masuk ke folder aplikasi:

   ~~~powershell
   git clone https://github.com/SolsticeTeam710/Solstice-.git
   Set-Location Solstice-\Solstice
   ~~~

2. Pasang dependensi PHP dan salin konfigurasi environment:

   ~~~powershell
   composer install
   Copy-Item .env.example .env
   ~~~

3. Edit .env. Atur nama aplikasi, URL, dan koneksi PostgreSQL. Contoh nilai utama:

   ~~~dotenv
   APP_NAME="Solstice Coffee"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=solstice
   DB_USERNAME=postgres
   DB_PASSWORD=
   ~~~

   Untuk PostgreSQL terkelola, isi host, nama database, username, password, dan pengaturan SSL sesuai informasi dari penyedia database. Jangan memasukkan kredensial asli ke GitHub.

4. Buat application key, jalankan migration dan data awal, lalu buat symlink storage:

   ~~~powershell
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   ~~~

   Seeder menyiapkan akun demo, katalog contoh, dan meja 1–4. Gunakan database kosong untuk instalasi baru. Jika schema sudah dibuat manual di database, periksa status migration dan schema terlebih dahulu; jangan membuat tabel yang sama lewat SQL manual sekaligus lewat migration.

5. Pasang dan build asset frontend, lalu jalankan server:

   ~~~powershell
   npm install
   npm run build
   php artisan serve
   ~~~

6. Buka http://localhost:8000.

### Akun Demo Lokal

Seeder saat ini membuat akun berikut:

| Role | Username | Password |
| --- | --- | --- |
| Admin | admin | password |
| Kasir | kasir1 | password |

Akun ini hanya untuk pengembangan/demo lokal. Ganti password atau nonaktifkan akun tersebut sebelum aplikasi dibuka ke publik. Jangan menjalankan seeder default pada produksi tanpa meninjau dan mengamankan akun yang dibuatnya.

## URL Penting

| Kegunaan | URL |
| --- | --- |
| Menu pelanggan | /menu |
| Pesan dari meja | /pesan?meja=1 sampai /pesan?meja=4 |
| Pemeriksaan server dan database | /health |
| Simulasi QRIS | /qris |
| Simulasi pemindaian QRIS | /qris/scan |
| Login admin/kasir | /login |
| Cetak QR meja | /qr-meja/cetak.html |

Panel admin berada di /admin/* dan memerlukan role admin. Dashboard kasir berada di /kasir/dashboard. Endpoint kasir dan admin di bawah /api/kasir/* dan /api/admin/* dilindungi autentikasi dan middleware role.

## Membuat dan Mencetak QR Meja

QR meja yang disediakan mengarah ke /pesan?meja=1 sampai /pesan?meja=4. Untuk membuat ulang QR dengan domain publik atau alamat LAN yang benar:

~~~powershell
node scripts/generate_qr_assets.cjs https://domain-aplikasi-anda
~~~

Untuk demo melalui jaringan lokal, gunakan alamat laptop server, misalnya http://192.168.1.100:8000. Setelah dibuat, buka /qr-meja/cetak.html dari aplikasi lalu cetak halaman tersebut. Jalankan generator dengan URL lengkap setiap kali domain atau alamat LAN berubah. QR Cloudflare Quick Tunnel hanya sesuai untuk akses sementara selama tunnel aktif; jangan gunakan URL tunnel sementara untuk QR permanen.

## API dan Route Utama

| Method | Path | Akses | Keterangan |
| --- | --- | --- | --- |
| GET | /api/pesanan/{orderId} | Publik | Melihat status pesanan berdasarkan Order ID. |
| GET | /csrf-token | Publik | Mengambil token CSRF untuk formulir pelanggan. |
| POST | /api/pesanan | Publik dengan CSRF | Membuat pesanan; dibatasi maksimal 10 request per menit. |
| GET | /api/kasir/* | Kasir/Admin | Dashboard dan pencarian pesanan. |
| PUT | /api/kasir/orders/{orderId}/verify | Kasir/Admin | Memproses konfirmasi pembayaran. |
| PUT | /api/kasir/orders/{orderId}/reject | Kasir/Admin | Menolak pesanan yang masih pending. |
| GET | /api/admin/* | Admin | Data dashboard, pengguna, menu, stok, dan laporan. |

Route web dan detail implementasi dapat dilihat di routes/web.php, routes/api.php, serta app/Http/Controllers/.

## Menjalankan di Jaringan Lokal Tanpa Internet

Server Laravel dan database harus sama-sama dapat diakses tanpa internet. Jika memakai Supabase atau database cloud, aplikasi tidak akan dapat membaca/menulis pesanan ketika koneksi internet terputus; siapkan PostgreSQL lokal untuk skenario offline.

1. Hubungkan laptop server dan perangkat penguji ke router/hotspot WiFi yang sama.
2. Siapkan database PostgreSQL lokal dan atur .env ke database tersebut. Atur APP_URL ke alamat LAN laptop, misalnya http://192.168.1.100:8000.
3. Pastikan firewall Windows mengizinkan koneksi masuk ke port 8000.
4. Jalankan:

   ~~~powershell
   php artisan config:clear
   php artisan serve --host 0.0.0.0 --port 8000
   ~~~

5. Buka http://192.168.1.100:8000 dari perangkat lain. Sesuaikan IP dengan alamat laptop saat ujian.
6. Buat QR meja terpisah yang menggunakan alamat LAN tersebut. QR yang mengarah ke domain publik tidak akan bekerja saat internet/tunnel tidak tersedia.

Belum ada skrip peluncur offline otomatis pada repository ini; langkah di atas merupakan prosedur manual.

## Deployment Publik

README ini tidak menyatakan bahwa aplikasi sudah di-deploy. Sebelum rilis ke hosting:

- Atur APP_ENV=production, APP_DEBUG=false, APP_URL ke domain HTTPS, dan APP_KEY yang aman.
- Isi koneksi PostgreSQL produksi melalui environment variables hosting. Jangan commit .env.
- Build asset dengan npm ci dan npm run build.
- Pasang dependensi produksi dengan composer install --no-dev --optimize-autoloader.
- Jalankan migration setelah backup dan memastikan database target benar:

  ~~~bash
  php artisan migrate --force
  php artisan storage:link
  php artisan config:cache
  php artisan view:cache
  ~~~

- Pastikan akun demo tidak memakai password bawaan sebelum membuka aplikasi ke publik.
- Gunakan URL hosting yang stabil untuk QR meja; Quick Tunnel Cloudflare adalah URL sementara.

## Pengujian

Jalankan tes yang tersedia dengan:

~~~powershell
php artisan test
~~~

Saat README ini ditulis, file test yang ada masih berupa contoh bawaan Laravel. Perintah tersebut belum membuktikan seluruh alur Solstice telah lulus QA; siapkan skenario uji khusus untuk pemesanan, stok, pembayaran, penolakan, laporan, dan hak akses sebelum release candidate.

## Keamanan dan Batasan Operasional

- .env berisi konfigurasi rahasia dan tidak boleh dimasukkan ke repository.
- Password disimpan menggunakan hashing Laravel.
- Login dibatasi 5 percobaan per menit dan endpoint pembuatan pesanan dibatasi 10 request per menit.
- QRIS hanya simulasi; aplikasi belum terhubung ke payment gateway atau melakukan verifikasi otomatis dari penyedia pembayaran.
- Saat ini menu dan stok bergantung pada database PostgreSQL yang dikonfigurasi di .env.

## Artefak Sprint 4 yang Perlu Ditambahkan Sebelum Ujian

README dan QR meja sudah tersedia. Lengkapi artefak lain setelah disiapkan tim:

- Tag Git release candidate dan log bug yang sudah ditutup.
- URL staging/hosting publik yang stabil dan prosedur cadangan LAN.
- User Manual bergambar dan Technical Docs/ERD.
- Dokumentasi API atau referensi route/controller.
- Slide presentasi, bukti gladi bersih, video demo cadangan, dan checklist pertanggungjawaban individu.
