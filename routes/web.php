<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
// PENTING: Kita panggil Controller yang sudah ada logika Xendit-nya
use App\Http\Controllers\Api\TransactionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Halaman Depan (Home)
Route::get('/', [HomeController::class, 'index'])->name('home');

// 2. Halaman Detail Produk
// URL: /product/netflix-premium
Route::get('/product/{product}', [HomeController::class, 'show'])->name('product.show');

// 3. Rute Login (Arahkan ke Filament Panel)
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
    // (Sementara redirect ke Admin Panel, nanti bisa buat halaman sendiri)
    Route::get('/dashboard', function () {
        return redirect('/admin');
    })->name('dashboard');

    // 6. CHECKOUT (Ini Kuncinya!)
    // Kita arahkan ke 'TransactionController' method 'checkout'
    // Method ini yang akan melempar user ke Xendit
    Route::post('/checkout', [TransactionController::class, 'checkout'])->name('checkout');
});
