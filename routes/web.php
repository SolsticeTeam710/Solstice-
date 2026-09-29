<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\HealthController;

Route::get('/health', [HealthController::class, 'check']);
