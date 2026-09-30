<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\HealthController;

Route::get('/health', [HealthController::class, 'check']);

Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/admin/dashboard', function () {
        return 'Dashboard Admin SOLSTICE COFFE';
    })->name('admin.dashboard');

});

Route::middleware(['auth', 'role:kasir'])->group(function () {

    Route::get('/kasir/dashboard', function () {
        return 'Dashboard Kasir SOLSTICE COFFE';
    })->name('kasir.dashboard');

});

Route::middleware(['auth', 'role:pelanggan'])->group(function () {

    Route::get('/menu', function () {
        return 'Menu Digital SOLSTICE COFFE';
    })->name('pelanggan.menu');
});



use App\Http\Controllers\AuthController;

Route::get('/login', fn () => view('layouts.login'))->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


use App\Http\Controllers\KasirController;
use App\Http\Controllers\AdminController;

// =========================
// KASIR
// =========================

Route::get('/api/kasir/dashboard', [KasirController::class, 'dashboard']);

Route::get('/api/kasir/orders', [KasirController::class, 'orders']);

Route::get(
    '/api/kasir/orders/{orderId}',
    [KasirController::class, 'search']
);

Route::put(
    '/api/kasir/orders/{orderId}/verify',
    [KasirController::class, 'verifyPayment']
);


// =========================
// ADMIN
// =========================

Route::get(
    '/api/admin/dashboard',
    [AdminController::class, 'dashboard']
);

Route::get(
    '/api/admin/users',
    [AdminController::class, 'users']
);

Route::get(
    '/api/admin/menu',
    [AdminController::class, 'menu']
);

Route::get(
    '/api/admin/kategori',
    [AdminController::class, 'kategori']
);

Route::get(
    '/api/admin/stok',
    [AdminController::class, 'stok']
);

Route::get(
    '/api/admin/stok-menipis',
    [AdminController::class, 'stokMenipis']
);

Route::get(
    '/api/admin/laporan',
    [AdminController::class, 'laporan']
);
