@echo off
title PELUNCUR SERVER DARURAT OFFLINE - SOLSTICE COFFEE
color 0A
cd /d "%~dp0"

echo ==============================================================
echo     MEMULAI SERVER LOKAL SOLSTICE COFFEE - MODE LAN
echo ==============================================================
echo.
echo Pastikan PostgreSQL lokal sudah aktif dan .env sudah
echo menggunakan database lokal.
echo.

set /p IP=Masukkan IPv4 laptop dari ipconfig:

if "%IP%"=="" (
    echo IP belum diisi.
    pause
    exit /b
)

echo.
echo Alamat server lokal: http://%IP%:8000
echo.

echo [1/3] Membersihkan cache konfigurasi dan route...
php artisan config:clear
php artisan route:clear

echo.
echo [2/3] Memeriksa storage link...
if not exist "public\storage" php artisan storage:link

echo.
echo [3/3] Menjalankan server Solstice...
echo Minta penguji membuka:
echo http://%IP%:8000/pelanggan
echo.
echo Tekan CTRL + C untuk menghentikan server.
echo ==============================================================

php artisan serve --host 0.0.0.0 --port 8000

pause
