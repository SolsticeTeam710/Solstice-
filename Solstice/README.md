# SOLSTICE COFFE

Aplikasi web untuk pemesanan menu dan pengelolaan operasional kedai SOLSTICE COFFE. Pelanggan memesan melalui halaman menu yang dibuka dari QR meja, Kasir memeriksa pesanan dan memverifikasi pembayaran, sedangkan Admin mengelola data kedai dan laporan.

## Fitur

### Pelanggan

- Melihat menu, kategori, harga, dan ketersediaan stok.
- Memilih menu, jumlah, catatan pesanan, nomor meja, dan metode pembayaran Cash atau QRIS.
- Mendapatkan Order ID setelah pesanan berhasil disimpan.
- Melihat status pesanan melalui nama pelanggan.

### Kasir

- Login dengan akun role Kasir.
- Melihat dan mencari pesanan melalui nama pelanggan.
- Memeriksa rincian dan total pesanan.
- Memverifikasi pembayaran Cash atau QRIS.
- Melihat kembalian untuk pembayaran Cash dan mencetak bukti transaksi melalui browser.

### Admin

- Login dengan akun role Admin.
- Mengelola menu, kategori, akun Admin/Kasir, stok menu, dan batas minimum stok.
- Melihat stok menipis.
- Melihat laporan penjualan berdasarkan periode serta mengunduh laporan CSV.

## Alur Pesanan

1. Pelanggan membuka menu dari QR meja, memilih item, lalu mengirim pesanan.
2. Sistem memvalidasi meja, ketersediaan menu, dan stok. Pesanan yang valid disimpan dengan status `pending`, dan meja ditandai terisi.
3. Kasir mencari pesanan dan memverifikasi pembayaran.
4. Jika verifikasi berhasil, pembayaran disimpan, stok menu dikurangi, status pesanan menjadi `selesai`, dan meja dikosongkan.
5. Pelanggan dapat memeriksa status pesanan menggunakan Order ID.

Status yang digunakan pada implementasi saat ini adalah `pending` dan `selesai`. Stok yang dikelola adalah stok menu; stok bahan baku belum dikelola terpisah. QRIS dipindai dari browser pelanggan, sementara pencatatan pembayarannya dikonfirmasi melalui proses Kasir. Aplikasi belum menggunakan payment gateway otomatis.

## Teknologi

- PHP 8.2
- Laravel 12
- SQLite sebagai konfigurasi database bawaan (`.env.example`)
- JavaScript, CSS, dan Blade
- Vite 7 dan Tailwind CSS 4
- `html5-qrcode` untuk pemindaian QRIS dari browser

## Persyaratan

- PHP 8.2+ dengan ekstensi yang dibutuhkan Laravel dan SQLite/PDO SQLite.
- Composer.
- Node.js dan npm.
