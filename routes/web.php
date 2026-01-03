<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WebOrderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Halaman Depan (Home)
Route::get('/', [HomeController::class, 'index'])->name('home');

// 2. Halaman Detail Produk
Route::get('/product/{product}', [HomeController::class, 'show'])->name('product.show');

// 3. Rute Login
Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');

// 4. Rute Logout
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// --- AREA YANG WAJIB LOGIN ---
Route::middleware(['auth'])->group(function () {

    // 5. Dashboard User
    Route::get('/dashboard', function () {
        return redirect('/admin');
    })->name('dashboard');

    // 6. PROSES CHECKOUT
    // User klik Beli -> Masuk ke Controller -> Redirect ke Halaman Pembayaran
    Route::post('/checkout', [WebOrderController::class, 'checkout'])->name('checkout');

    // --- HALAMAN PEMBAYARAN UTAMA ---
    // (Ini halaman yang berisi 2 Kartu: Saldo & Midtrans)
    Route::get('/payment/{invoice}', [WebOrderController::class, 'showPayment'])->name('payment.show');

    // --- AKSI PEMBAYARAN ---
    // 1. Aksi Bayar Pakai Wallet (Form Submit)
    Route::post('/payment/wallet/{invoice}', [WebOrderController::class, 'payWithWallet'])->name('payment.wallet');

    // 2. Aksi Bayar Pakai Midtrans (AJAX Request untuk dapat Snap Token)
    Route::post('/payment/midtrans/{invoice}', [WebOrderController::class, 'payWithMidtrans'])->name('payment.midtrans');

    // --- HALAMAN TOP UP ---
    Route::get('/top-up', [WebOrderController::class, 'showTopUpForm'])->name('topup.form');
    Route::post('/top-up', [WebOrderController::class, 'topUp'])->name('topup.process');

    // ❌ RUTE INI DIHAPUS (SUDAH TIDAK DIPAKAI):
    // Route::get('/payment/select/{invoice}', [WebOrderController::class, 'showPaymentSelection'])->name('payment.selection');
});
