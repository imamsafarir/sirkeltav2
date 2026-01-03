<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Order;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class LatestOrders extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Transaksi Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->latest()->limit(5)
            )
            ->paginated(false)
            ->columns([
                // 1. CUSTOMER
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->icon('heroicon-m-user')
                    ->weight('bold')
                    ->searchable(),

                // 2. PRODUK (DIPERBAIKI)
                Tables\Columns\TextColumn::make('product_info')
                    ->label('Produk')
                    ->state(function (Order $record) {
                        if ($record->type === 'topup') {
                            return 'Top Up Saldo';
                        }
                        return $record->variant?->product?->name ?? 'Produk Dihapus';
                    })
                    ->description(function (Order $record) {
                        if ($record->type === 'topup') {
                            return 'Deposit Dompet';
                        }
                        return $record->variant?->name ?? '-';
                    })
                    ->icon(fn(Order $record) => $record->type === 'topup' ? 'heroicon-o-currency-dollar' : 'heroicon-o-shopping-bag')
                    ->wrap(),

                // 3. HARGA
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                // 4. STATUS
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid', 'processing' => 'info',
                        'completed' => 'success',
                        'failed', 'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'pending' => 'heroicon-m-clock',
                        'paid' => 'heroicon-m-banknotes',
                        'processing' => 'heroicon-m-cog',
                        'completed' => 'heroicon-m-check-badge',
                        'failed', 'canceled' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                // 5. WAKTU
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->since()
                    ->sortable()
                    ->color('gray')
                    ->tooltip(fn(Order $record): string => $record->created_at->format('d M Y H:i')),
            ])
            ->actions([
                // Tombol Buka Halaman Full (Edit)
                Tables\Actions\Action::make('open')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn(Order $record): string => route('filament.admin.resources.orders.edit', $record))
                    ->color('gray')
                    ->button()
                    ->outlined()
                    ->size('xs'),

                // --- TOMBOL LIHAT DETAIL (POPUP) ---
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading('Rincian Pesanan')
                    ->infolist(fn(Infolist $infolist) => self::infolist($infolist)),
            ]);
    }

    // Fungsi Layout Infolist (Disamakan dengan OrderResource)
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // 1. DATA AKUN PREMIUM (Hanya muncul jika Completed DAN Produk)
                Infolists\Components\Section::make('Akun Premium')
                    ->description('Data akun untuk customer.')
                    ->icon('heroicon-m-gift')
                    ->iconColor('success')
                    ->schema([
                        Infolists\Components\TextEntry::make('group.account_email')
                            ->label('Email Login')
                            ->icon('heroicon-m-envelope')
                            ->weight('bold')
                            ->copyable()
                            ->color('primary')
                            ->default('Menunggu Admin...'),

                        Infolists\Components\TextEntry::make('group.account_password')
                            ->label('Password')
                            ->icon('heroicon-m-key')
                            ->fontFamily('mono')
                            ->weight('bold')
                            ->copyable()
                            ->color('primary')
                            ->default('Menunggu Admin...'),

                        Infolists\Components\TextEntry::make('group.additional_info')
                            ->label('Catatan')
                            ->icon('heroicon-m-information-circle')
                            ->columnSpanFull()
                            ->markdown()
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->visible(fn($record) => $record->status === 'completed' && $record->type === 'product'),

                // 2. DETAIL TRANSAKSI
                Infolists\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice_number')
                            ->label('No. Invoice')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal')
                            ->dateTime('d M Y H:i'),

                        // PERBAIKAN: Gunakan 'state' untuk handle Top Up
                        Infolists\Components\TextEntry::make('product_name')
                            ->label('Produk')
                            ->state(fn(Order $record) => $record->type === 'topup' ? 'Top Up Saldo' : $record->variant?->product?->name),

                        Infolists\Components\TextEntry::make('variant_name')
                            ->label('Paket')
                            ->state(fn(Order $record) => $record->type === 'topup' ? 'Deposit' : $record->variant?->name),

                        Infolists\Components\TextEntry::make('amount')
                            ->label('Total Bayar')
                            ->money('IDR')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'paid', 'processing' => 'info',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('group.id')
                            ->label('ID Grup')
                            ->icon('heroicon-m-user-group')
                            ->default('Belum Masuk Grup')
                            ->visible(fn($record) => $record->type === 'product'),
                    ])
                    ->columns(2),
            ]);
    }
}
