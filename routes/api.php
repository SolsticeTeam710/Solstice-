<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/login', fn () => view('login'))->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
