<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class IncomeChart extends ChartWidget
{
    protected static ?string $heading = 'Pendapatan 7 Hari Terakhir';
    protected static ?int $sort = 2;

    // Refresh otomatis setiap 15 detik
    protected static ?string $pollingInterval = '15s';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Loop 7 hari ke belakang
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            // Label Tanggal
            $labels[] = $date->format('d M');

            // --- PERBAIKAN LOGIKA DISINI ---
            // Hitung total amount dimana statusnya SUDAH BAYAR (Paid, Processing, Completed)
            // Jangan hanya 'paid', nanti kalau diproses admin malah hilang datanya.
            $data[] = Order::whereDate('created_at', $date)
                ->whereIn('status', ['paid', 'processing', 'completed'])
                ->sum('amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan (Rp)',
                    'data' => $data,
                    'borderColor' => '#10B981', // Hijau
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)', // Hijau Transparan
                    'fill' => true,
                    'tension' => 0.4, // Membuat garis melengkung halus (biar keren)
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }
}
