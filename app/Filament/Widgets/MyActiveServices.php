<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class MyActiveServices extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Akun Premium Saya';

    // PERBAIKAN DI SINI: Izinkan Customer DAN Admin
    public static function canView(): bool
    {
        // Cek apakah user login, DAN role-nya adalah customer ATAU admin
        return Auth::check() && in_array(Auth::user()->role, ['customer', 'admin']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->where('user_id', Auth::id()) // Ini akan mengambil order milik Admin jika yang login Admin
                    ->where('status', 'completed')
                    ->where('type', 'product')
                    ->latest()
            )
            ->columns([
                // Nama Produk (Netflix)
                Tables\Columns\TextColumn::make('variant.product.name')
                    ->label('Layanan')
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
                    ->fontFamily('mono')
                    ->placeholder('Menunggu Admin...'),

                // CATATAN SPESIFIK USER (TAGGING)
                Tables\Columns\TextColumn::make('custom_note')
                    ->label('Catatan Admin')
                    ->state(function (Order $record) {
                        $notes = $record->group->additional_info ?? [];
                        if (is_array($notes)) {
                            foreach ($notes as $item) {
                                // Cek apakah catatan ini untuk user yang sedang login
                                if (isset($item['user_id']) && $item['user_id'] == Auth::id()) {
                                    return $item['note'];
                                }
                            }
                        }
                        return '-';
                    })
                    ->wrap()
                    ->color('warning')
                    ->weight('bold'),

                // Expired Kapan?
                Tables\Columns\TextColumn::make('group.expired_at')
                    ->label('Berakhir')
                    ->dateTime('d M Y')
                    ->description(fn($record) => $record->group?->expired_at?->diffForHumans())
                    ->color('gray'),
            ])
            ->actions([
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
