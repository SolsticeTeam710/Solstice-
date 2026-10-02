<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PesananController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::get('/', fn () => view('welcome'));
Route::get('/health', [HealthController::class, 'check']);

// Autentikasi (Guest / Belum Login)
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('layouts.login'))->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.process');
});

// Logout (Harus Login)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Protected Web Routes (Berdasarkan Role)
|--------------------------------------------------------------------------
*/

// Role: Admin
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return 'Dashboard Admin SOLSTICE COFFE';
    })->name('admin.dashboard');
});

// Role: Kasir
Route::middleware(['auth', 'role:kasir'])->group(function () {
    Route::get('/kasir/dashboard', function () {
        return 'Dashboard Kasir SOLSTICE COFFE';
    })->name('kasir.dashboard');
});

// Public Routes (Bisa diakses langsung oleh Pelanggan via QR Code)
Route::get('/menu', [MenuController::class, 'index'])->name('pelanggan.menu');
Route::post('/api/pesanan', [PesananController::class, 'store']); // Pelanggan kirim pesanan

/*
|--------------------------------------------------------------------------
| Internal API Routes (Terproteksi Middleware Role)
|--------------------------------------------------------------------------
*/

// API Endpoints Kasir
Route::middleware(['auth', 'role:kasir,admin'])->prefix('api/kasir')->group(function () {
    Route::get('/dashboard', [KasirController::class, 'dashboard']);
    Route::get('/orders', [KasirController::class, 'orders']);
    Route::get('/orders/{orderId}', [KasirController::class, 'search']);
    Route::put('/orders/{orderId}/verify', [KasirController::class, 'verifyPayment']);
});

// API Endpoints Admin
Route::middleware(['auth', 'role:admin'])->prefix('api/admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/menu', [AdminController::class, 'menu']);
    Route::get('/kategori', [AdminController::class, 'kategori']);
    Route::get('/stok', [AdminController::class, 'stok']);
    Route::get('/stok-menipis', [AdminController::class, 'stokMenipis']);
    Route::get('/laporan', [AdminController::class, 'laporan']);
});
