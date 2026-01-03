<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Order;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;

class LatestOrders extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Transaksi Terbaru';

    // 1. IZINKAN SEMUA USER LOGIN MELIHAT WIDGET INI
    public static function canView(): bool
    {
        return Auth::check();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // 2. LOGIKA FILTER:
                // Jika Admin -> Ambil semua
                // Jika User  -> Ambil punya sendiri
                Order::query()
                    ->when(Auth::user()->role !== 'admin', function ($query) {
                        return $query->where('user_id', Auth::id());
                    })
                    ->latest()
                    ->limit(5)
            )
            ->paginated(false)
            ->columns([
                // 3. NAMA CUSTOMER (Sembunyikan jika yang login adalah Customer)
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->icon('heroicon-m-user')
                    ->weight('bold')
                    ->searchable()
                    ->visible(fn() => Auth::user()->role === 'admin'),

                // 4. PRODUK
                Tables\Columns\TextColumn::make('product_info')
                    ->label('Produk')
                    ->state(function (Order $record) {
                        if ($record->type === 'topup') return 'Top Up Saldo';
                        return $record->variant?->product?->name ?? 'Produk Dihapus';
                    })
                    ->description(function (Order $record) {
                        if ($record->type === 'topup') return 'Deposit Dompet';
                        return $record->variant?->name ?? '-';
                    })
                    ->icon(fn(Order $record) => $record->type === 'topup' ? 'heroicon-o-currency-dollar' : 'heroicon-o-shopping-bag')
                    ->wrap(),

                // 5. HARGA
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                // 6. STATUS
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid', 'processing' => 'info',
                        'completed' => 'success',
                        'failed', 'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                // 7. WAKTU (VERSI BERSIH - TANPA TOOLTIP)
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->since()
                    ->sortable()
                    ->color('gray'),
            ])
            ->actions([
                // Tombol Edit (Hanya Admin)
                Tables\Actions\Action::make('open')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn(Order $record): string => route('filament.admin.resources.orders.edit', $record))
                    ->color('gray')
                    ->button()
                    ->outlined()
                    ->size('xs')
                    ->visible(fn() => Auth::user()->role === 'admin'),

                // Tombol Lihat Detail (Admin & User)
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading('Rincian Pesanan')
                    ->infolist(fn(Infolist $infolist) => self::infolist($infolist)),
            ]);
    }

    // Fungsi Layout Infolist (Sinkron dengan OrderResource)
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // 1. DATA AKUN PREMIUM (Lengkap dengan Catatan Khusus)
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

                        // FITUR CATATAN SPESIFIK USER
                        Infolists\Components\TextEntry::make('custom_note')
                            ->label('Catatan Khusus Untuk Anda')
                            ->icon('heroicon-m-sparkles')
                            ->color('warning')
                            ->weight('bold')
                            ->columnSpanFull()
                            ->state(function (Order $record) {
                                $notes = $record->group->additional_info ?? [];
                                if (is_array($notes)) {
                                    foreach ($notes as $item) {
                                        if (isset($item['user_id']) && $item['user_id'] == $record->user_id) {
                                            return $item['note'];
                                        }
                                    }
                                }
                                return 'Tidak ada catatan khusus.';
                            })
                            ->visible(fn($state) => $state !== 'Tidak ada catatan khusus.'),
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
