<?php

namespace App\Filament\Widgets;

use App\Models\Group;
use App\Models\Order;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    // Refresh otomatis setiap 10 detik (Biar lebih cepat update)
    protected static ?string $pollingInterval = '3s';

    protected function getStats(): array
    {
        return [
            // 1. TOTAL OMSET (DIPERBAIKI)
            // Menggunakan whereIn agar menghitung uang status Paid, Processing, DAN Completed
            Stat::make('Total Omset', 'Rp ' . number_format(
                Order::whereIn('status', ['paid', 'processing', 'completed'])->sum('amount'),
                0,
                ',',
                '.'
            ))
                ->description('Pemasukan bersih (Real-time)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([7, 3, 10, 5, 15, 10, 20]), // Hiasan grafik kecil

            // 2. Hitung Grup yang statusnya FULL / PROCESSING (Perlu pantauan Admin)
            Stat::make('Perlu Diproses', Group::whereIn('status', ['full', 'processing'])->count())
                ->description('Grup Full & Sedang Dikerjakan')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color(Group::whereIn('status', ['full', 'processing'])->count() > 0 ? 'danger' : 'gray'),

            // 3. Hitung Total User
            Stat::make('Total Member', User::where('role', 'customer')->count())
                ->description('Customer terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }
}
