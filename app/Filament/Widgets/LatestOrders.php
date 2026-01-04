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
    protected int | string | array $columnSpan = 'full'; // Agar lebar tabel penuh

    protected static ?string $heading = 'Riwayat Transaksi';

    // 1. IZINKAN SEMUA USER LOGIN MELIHAT WIDGET INI
    public static function canView(): bool
    {
        return Auth::check();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    // Optimasi: Load relasi agar query lebih ringan
                    ->with(['user', 'variant.product', 'group'])
                    // 2. LOGIKA FILTER (Admin lihat semua, User lihat punya sendiri)
                    ->when(Auth::user()->role !== 'admin', function ($query) {
                        return $query->where('user_id', Auth::id());
                    })
                    ->latest()
                // HAPUS limit(5) AGAR PAGINATION BERFUNGSI
            )
            // 3. AKTIFKAN PAGINATION (10, 25, 50, 100, All)
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(10)
            ->columns([
                // --- KOLOM INVOICE (TAMBAHAN) ---
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                    ->color('gray'),

                // 4. NAMA CUSTOMER (Hanya tampil untuk Admin)
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->icon('heroicon-m-user')
                    ->weight('bold')
                    ->searchable()
                    ->visible(fn() => Auth::user()->role === 'admin'),

                // 5. PRODUK
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

                // --- METODE PEMBAYARAN (TAMBAHAN) ---
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn($state) => strtoupper($state))
                    ->toggleable(isToggledHiddenByDefault: true), // Bisa di-show/hide user

                // 6. HARGA
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                // 7. STATUS
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid', 'processing' => 'info',
                        'completed' => 'success',
                        'failed', 'canceled', 'expired' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                // 8. WAKTU
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i') // Format lebih jelas daripada since() untuk laporan
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),
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

    // Fungsi Layout Infolist (Detail Modal)
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // 1. DATA AKUN PREMIUM (Hanya muncul jika Completed & Produk)
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

                        // FITUR CATATAN SPESIFIK USER (LOGIKA ANDA SEBELUMNYA)
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
                                return null;
                            })
                            ->visible(fn($state) => !empty($state)),
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

                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->badge()
                            ->color('gray'),

                        Infolists\Components\TextEntry::make('amount')
                            ->label('Total Bayar')
                            ->money('IDR')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

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
