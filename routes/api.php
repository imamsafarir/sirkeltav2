<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TransactionController;

// 1. Pintu untuk Frontend (Saat User klik "Beli")
// User harus login dulu (auth:sanctum) baru bisa beli
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkout', [TransactionController::class, 'checkout']);
    Route::get('/my-wallet', function (Request $request) {
        return $request->user()->wallet;
    });
});
