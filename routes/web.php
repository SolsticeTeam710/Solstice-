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

Route::get('/login', function () {
    return 'Halaman Login SOLSTICE COFFE';
})->name('login');
