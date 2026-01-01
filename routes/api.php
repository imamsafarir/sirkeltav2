<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\XenditWebhookController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WebhookController; // Kita buat sebentar lagi

// 1. Pintu untuk Xendit (Webhook)
// Xendit akan menembak ke: domain-kamu.com/api/xendit/callback
Route::post('/xendit/callback', [XenditWebhookController::class, 'handle']);

Route::post('/xendit/callback', [WebhookController::class, 'handle']);

// 2. Pintu untuk Frontend (Saat User klik "Beli")
// User harus login dulu (auth:sanctum) baru bisa beli
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkout', [TransactionController::class, 'checkout']);
    Route::get('/my-wallet', function (Request $request) {
        return $request->user()->wallet;
    });
});
