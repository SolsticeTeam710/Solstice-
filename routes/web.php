<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PesananController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/health', [HealthController::class, 'check']);

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'login'])->name('login.process');
});
Route::post('/logout', [AuthenticatedSessionController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/menu', [AdminController::class, 'menus'])->name('menus');
    Route::post('/menu', [AdminController::class, 'storeMenu'])->name('menus.store');
    Route::put('/menu/{id}', [AdminController::class, 'updateMenu'])->name('menus.update');
    Route::delete('/menu/{id}', [AdminController::class, 'deleteMenu'])->name('menus.delete');
    Route::post('/kategori', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::get('/pengguna', [AdminController::class, 'usersPage'])->name('users');
    Route::post('/pengguna', [AdminController::class, 'storeUser'])->name('users.store');
    Route::put('/pengguna/{id}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/pengguna/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::patch('/pengguna/{id}/status', [AdminController::class, 'toggleUserStatus'])->name('users.status');
    Route::get('/stok', [AdminController::class, 'stockPage'])->name('stock');
    Route::post('/stok', [AdminController::class, 'storeStock'])->name('stock.store');
    Route::put('/stok/{id}', [AdminController::class, 'updateStock'])->name('stock.update');
    Route::get('/stok/kritis', [AdminController::class, 'criticalStockPage'])->name('stock.critical');
    Route::get('/laporan', [AdminController::class, 'reportsPage'])->name('reports');
    Route::get('/laporan/ekspor', [AdminController::class, 'exportPage'])->name('reports.export');
    Route::get('/laporan/unduh', [AdminController::class, 'downloadReport'])->name('reports.download');
});

Route::middleware(['auth', 'role:kasir'])->group(function () {
    Route::get('/kasir/dashboard', fn () => 'Dashboard Kasir SOLSTICE COFFE')->name('kasir.dashboard');
});

Route::get('/menu', [MenuController::class, 'index'])->name('pelanggan.menu');
Route::post('/api/pesanan', [PesananController::class, 'store']);

// Endpoints API yang sudah tersedia.
Route::middleware(['auth', 'role:kasir,admin'])->prefix('api/kasir')->group(function () {
    Route::get('/dashboard', [KasirController::class, 'dashboard']);
    Route::get('/orders', [KasirController::class, 'orders']);
    Route::get('/orders/{orderId}', [KasirController::class, 'search']);
    Route::put('/orders/{orderId}/verify', [KasirController::class, 'verifyPayment']);
});

Route::middleware(['auth', 'role:admin'])->prefix('api/admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboardData']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/menu', [AdminController::class, 'menu']);
    Route::get('/kategori', [AdminController::class, 'kategori']);
    Route::get('/stok', [AdminController::class, 'stok']);
    Route::get('/stok-menipis', [AdminController::class, 'stokMenipis']);
    Route::get('/laporan', [AdminController::class, 'laporan']);
});
