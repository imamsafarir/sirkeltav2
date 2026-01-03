<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentCallbackController;

Route::post('/midtrans-callback', [PaymentCallbackController::class, 'handle']);
