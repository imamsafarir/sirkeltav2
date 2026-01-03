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
    // Redirect ke Panel Admin/Customer setelah login
    Route::get('/dashboard', function () {
        return redirect('/admin');
    })->name('dashboard');

    // 6. PROSES CHECKOUT (MANUAL)
    // Rute Checkout (Menggunakan WebOrderController)
    Route::post('/checkout', [WebOrderController::class, 'checkout'])->name('checkout');
});
