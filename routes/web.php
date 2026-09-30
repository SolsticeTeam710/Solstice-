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
