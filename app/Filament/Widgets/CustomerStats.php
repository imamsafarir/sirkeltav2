<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerStats extends BaseWidget
{
    // PENTING: Widget ini HANYA muncul untuk Customer
    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->role === 'customer';
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        // Hitung layanan yang statusnya 'Completed' (Sedang aktif dipakai)
        $activeServices = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // Hitung total pengeluaran dia
        $totalSpent = Order::where('user_id', $user->id)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('amount');

        return [
            // Kartu 1: Sisa Saldo
            Stat::make('Saldo Saya', 'Rp ' . number_format($user->wallet->balance ?? 0, 0, ',', '.'))
                ->description('Siap digunakan belanja')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('success'),

            // Kartu 2: Layanan Aktif
            Stat::make('Layanan Aktif', $activeServices . ' Akun')
                ->description('Sedang berjalan')
                ->descriptionIcon('heroicon-m-play-circle')
                ->color('info'),

            // Kartu 3: Total Belanja
            Stat::make('Total Belanja', 'Rp ' . number_format($totalSpent, 0, ',', '.'))
                ->description('Sejak bergabung')
                ->color('gray'),
        ];
    }
}
