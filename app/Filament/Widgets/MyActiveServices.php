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
                Order::query()
                    ->where('user_id', Auth::id())
                    ->where('status', 'completed')
                    // PERBAIKAN 1: Filter hanya Product (Top Up jangan masuk sini)
                    ->where('type', 'product')
                    ->latest()
            )
            ->columns([
                // Nama Produk (Netflix)
                Tables\Columns\TextColumn::make('variant.product.name')
                    ->label('Layanan')
                    // Gunakan Null Coalescing (??) biar aman
                    ->description(fn(Order $record) => $record->variant->name ?? '-')
                    ->icon('heroicon-o-film')
                    ->searchable(),

                // INFO AKUN (Email)
                Tables\Columns\TextColumn::make('group.account_email')
                    ->label('Email Akun')
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->copyMessage('Email disalin!')
                    ->placeholder('Menunggu Admin...'),

                // PASSWORD
                Tables\Columns\TextColumn::make('group.account_password')
                    ->label('Password')
                    ->icon('heroicon-o-key')
                    ->copyable()
                    ->copyMessage('Password disalin!')
                    ->color('danger')
                    ->fontFamily('mono') // Font ala koding biar jelas beda huruf I dan l
                    ->placeholder('Menunggu Admin...'),

                // PERBAIKAN 2: CATATAN SPESIFIK USER (TAGGING)
                // Kita ganti logika additional_info agar membaca array JSON
                Tables\Columns\TextColumn::make('custom_note')
                    ->label('Catatan Admin')
                    ->state(function (Order $record) {
                        // Ambil data JSON dari grup
                        $notes = $record->group->additional_info ?? [];

                        // Cari catatan untuk user yang sedang login ini
                        if (is_array($notes)) {
                            foreach ($notes as $item) {
                                if (isset($item['user_id']) && $item['user_id'] == Auth::id()) {
                                    return $item['note'];
                                }
                            }
                        }
                        return '-';
                    })
                    ->wrap() // Agar teks panjang turun ke bawah
                    ->color('warning')
                    ->weight('bold'),
                // SAYA SUDAH HAPUS TOOLTIP DISINI AGAR TIDAK ERROR LAGI

                // Expired Kapan?
                Tables\Columns\TextColumn::make('group.expired_at')
                    ->label('Berakhir')
                    ->dateTime('d M Y')
                    ->description(fn($record) => $record->group?->expired_at?->diffForHumans())
                    ->color('gray'),
            ])
            ->actions([
                // Tombol Komplain
                Tables\Actions\Action::make('lapor')
                    ->label('Lapor')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->url('https://wa.me/6281234567890?text=Halo admin, akun saya bermasalah', true)
                    ->color('gray')
                    ->button()
                    ->outlined()
                    ->size('xs'),
            ]);
    }
}
