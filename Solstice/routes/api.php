<?php

use Illuminate\Http\Request;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\MidtransWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/pesanan/{orderId}', [PesananController::class, 'show']);

Route::post('/midtrans/notification', [MidtransWebhookController::class, 'handle'])
    ->name('midtrans.notification');
