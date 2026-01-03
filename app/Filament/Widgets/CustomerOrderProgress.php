<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class CustomerOrderProgress extends Widget
{
    // Tentukan lokasi file view
    protected static string $view = 'filament.widgets.customer-order-progress';

    // Atur urutan (paling atas)
    protected static ?int $sort = 1;

    // Lebar widget (Full width biar ganteng)
    protected int | string | array $columnSpan = 'full';

    // Kirim data order ke view
    protected function getViewData(): array
    {
        // PERBAIKAN: Hanya ambil order dengan type 'product'
        // Top Up (type='topup') akan diabaikan oleh widget ini
        $latestOrder = Order::where('user_id', Auth::id())
            ->where('type', 'product') // <--- FILTER INI KUNCINYA
            ->latest()
            ->first();

        return [
            'order' => $latestOrder,
        ];
    }

    // Hanya tampilkan widget jika user punya order PRODUK
    public static function canView(): bool
    {
        // Cek juga di sini, supaya kalau user cuma pernah Top Up, widget ini tidak muncul (karena tidak relevan)
        return Auth::check() &&
            Order::where('user_id', Auth::id())
            ->where('type', 'product') // <--- FILTER INI JUGA
            ->exists();
    }
}
