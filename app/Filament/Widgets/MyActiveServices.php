<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class MyActiveServices extends BaseWidget
{
    protected static ?int $sort = 2; // Muncul di bawah statistik
    protected int | string | array $columnSpan = 'full'; // Lebar penuh
    protected static ?string $heading = 'Akun Premium Saya'; // Judul Tabel

    // HANYA UNTUK CUSTOMER
    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->role === 'customer';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Ambil order milik user ini yang statusnya SUDAH SELESAI (Completed)
                Order::query()
                    ->where('user_id', Auth::id())
                    ->where('status', 'completed')
                    ->latest()
            )
            ->columns([
                // Nama Produk (Netflix)
                Tables\Columns\TextColumn::make('variant.product.name')
                    ->label('Layanan')
                    ->description(fn(Order $record) => $record->variant->name) // Sub: Premium 4K
                    ->icon('heroicon-o-film')
                    ->searchable(),

                // INFO AKUN (Email) - Bisa dicopy
                Tables\Columns\TextColumn::make('group.account_email')
                    ->label('Email Akun')
                    ->icon('heroicon-o-envelope')
                    ->copyable() // User tinggal klik buat copy
                    ->copyMessage('Email disalin!'),

                // PASSWORD (Password) - Bisa dicopy & disembunyikan
                Tables\Columns\TextColumn::make('group.account_password')
                    ->label('Password')
                    ->icon('heroicon-o-key')
                    ->copyable()
                    ->copyMessage('Password disalin!')
                    // ->formatStateUsing(fn ($state) => '•••••••') // Aktifkan ini kalau mau sensor
                    ->color('danger'), // Warna merah biar perhatian

                // Info Tambahan (Profil)
                Tables\Columns\TextColumn::make('group.additional_info')
                    ->label('Catatan')
                    ->limit(30)
                    ->tooltip(fn($state) => $state),

                // Expired Kapan?
                Tables\Columns\TextColumn::make('group.expired_at')
                    ->label('Berakhir')
                    ->dateTime()
                    ->since() // Tampil "29 days from now"
                    ->color('warning'),
            ])
            ->actions([
                // Tombol Komplain (Opsional)
                Tables\Actions\Action::make('lapor')
                    ->label('Lapor Masalah')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->url('https://wa.me/6281234567890?text=Halo admin, akun saya bermasalah', true) // Link ke WA
                    ->color('gray'),
            ]);
    }
}
