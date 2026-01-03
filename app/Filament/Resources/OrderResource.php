<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Order';
    protected static ?int $navigationSort = 2;

    // HANYA ADMIN YANG BOLEH BUAT ORDER BARU
    public static function canCreate(): bool
    {
        return Auth::user()->role === 'admin';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()->role === 'customer') {
            return $query->where('user_id', Auth::id());
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- BAGIAN 1: DETAIL ORDER (FORM EDIT) ---
                Forms\Components\Section::make('Detail Order')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Nomor Invoice')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending (Belum Bayar)',
                                'paid' => 'Paid (Menunggu Admin)',
                                'processing' => 'Processing (Sedang Disiapkan)',
                                'completed' => 'Completed (Selesai)',
                                'failed' => 'Failed (Gagal)',
                                'canceled' => 'Canceled (Batal)',
                            ])
                            ->required()
                            ->disabled(fn() => Auth::user()->role !== 'admin'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                // --- PERBAIKAN UTAMA DI SINI ---
                // Jangan akses relasi langsung, pakai logika cek Tipe Order
                Tables\Columns\TextColumn::make('product_info')
                    ->label('Produk / Layanan')
                    ->state(function (Order $record) {
                        if ($record->type === 'topup') {
                            return 'Top Up Saldo';
                        }
                        // Gunakan tanda tanya (?) agar aman (Null Safe)
                        return $record->variant?->product?->name ?? 'Produk Tidak Ditemukan';
                    })
                    ->description(function (Order $record) {
                        if ($record->type === 'topup') {
                            return 'Deposit Dompet';
                        }
                        return $record->variant?->name ?? '-';
                    })
                    ->icon(fn(Order $record) => $record->type === 'topup' ? 'heroicon-o-currency-dollar' : 'heroicon-o-shopping-bag')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid', 'processing' => 'info',
                        'completed' => 'success',
                        'failed', 'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Belum Bayar',
                        'paid' => 'Menunggu Admin',
                        'processing' => 'Sedang Proses',
                        'completed' => 'Selesai',
                        'failed' => 'Gagal',
                        'canceled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'pending' => 'heroicon-m-clock',
                        'paid', 'processing' => 'heroicon-m-user-group',
                        'completed' => 'heroicon-m-check-badge',
                        'failed' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('3s') // Refresh cepat
            ->actions([
                // Tombol Bayar
                Tables\Actions\Action::make('pay_now')
                    ->label('Bayar')
                    ->icon('heroicon-m-credit-card')
                    ->url(fn(Order $record) => $record->payment_url, shouldOpenInNewTab: true)
                    ->color('primary')
                    ->button()
                    ->visible(fn(Order $record) => $record->status === 'pending' && $record->payment_url),

                // Tombol Lihat (Mata) -> Membuka Infolist di bawah
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->modalHeading('Rincian Pesanan'),
            ]);
    }

    // --- TAMPILAN MODAL "VIEW" (MATA) ---
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // 1. DATA AKUN PREMIUM (Hanya muncul jika Completed DAN Tipe Product)
                Infolists\Components\Section::make('Akun Premium Anda')
                    ->description('Silakan gunakan data ini untuk login aplikasi.')
                    ->icon('heroicon-m-gift')
                    ->iconColor('success')
                    ->headerActions([
                        Infolists\Components\Actions\Action::make('copy_all')
                            ->icon('heroicon-m-clipboard')
                            ->label('Salin Info')
                            ->action(fn() => null)
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('group.account_email')
                            ->label('Email Login')
                            ->icon('heroicon-m-envelope')
                            ->weight('bold')
                            ->copyable()
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('group.account_password')
                            ->label('Password')
                            ->icon('heroicon-m-key')
                            ->fontFamily('mono')
                            ->weight('bold')
                            ->copyable()
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('group.additional_info')
                            ->label('Catatan Penting')
                            ->icon('heroicon-m-information-circle')
                            ->columnSpanFull()
                            ->markdown(),
                    ])
                    ->columns(2)
                    // PENTING: Hide jika status belum completed ATAU tipe order adalah topup
                    ->visible(fn($record) => $record->status === 'completed' && $record->type === 'product'),

                // 2. DETAIL TRANSAKSI & GRUP (Selalu Muncul)
                Infolists\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice_number')
                            ->label('No. Invoice')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal Order')
                            ->dateTime('d M Y H:i'),

                        // PERBAIKAN: Gunakan 'state' custom untuk handle Top Up
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

                        // Hanya muncul jika produk (bukan topup)
                        Infolists\Components\TextEntry::make('group.id')
                            ->label('ID Grup Patungan')
                            ->icon('heroicon-m-user-group')
                            ->visible(fn(Order $record) => $record->type === 'product'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    // --- BAGIAN NAVIGATION BADGE ---

    public static function getNavigationBadge(): ?string
    {
        // 1. ADMIN: Melihat jumlah order PENDING
        if (Auth::user()->role === 'admin') {
            $count = Order::where('status', 'pending')->count();
            return $count > 0 ? (string) $count : null;
        }

        // 2. CUSTOMER: Melihat jumlah order COMPLETED
        return (string) Order::query()
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Auth::user()->role === 'admin' ? 'danger' : 'success';
    }
}
