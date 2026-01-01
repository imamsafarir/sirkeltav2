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
        // Ambil 1 order terakhir milik user ini
        $latestOrder = Order::where('user_id', Auth::id())
            ->latest()
            ->first();

        return [
            'order' => $latestOrder,
        ];
    }

    // Hanya tampilkan widget jika user punya order
    public static function canView(): bool
    {
        return Auth::check() && Order::where('user_id', Auth::id())->exists();
    }
}
