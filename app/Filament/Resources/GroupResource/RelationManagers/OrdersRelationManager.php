<?php

namespace App\Filament\Resources\GroupResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use App\Models\Order;
use App\Models\Wallet; // Import Wallet Model
use Illuminate\Support\Facades\DB; // Untuk Transaksi Database

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';
    protected static ?string $title = 'Daftar Peserta Patungan';

    public function form(Form $form): Form
    {
        // ... (Kode Form Anda Tetap Sama) ...
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('user_name_view')
                            ->label('Nama Peserta')
                            ->prefixIcon('heroicon-m-user')
                            ->formatStateUsing(fn($record) => $record->user->name ?? '-')
                            ->disabled(),

                        Forms\Components\TextInput::make('user_email_view')
                            ->label('Email Peserta')
                            ->prefixIcon('heroicon-m-envelope')
                            ->formatStateUsing(fn($record) => $record->user->email ?? '-')
                            ->disabled(),

                        Forms\Components\TextInput::make('invoice_number')
                            ->label('No. Invoice')
                            ->prefixIcon('heroicon-m-document-text')
                            ->disabled(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Nominal Bayar')
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->label('Status Bayar')
                            ->formatStateUsing(fn(string $state) => ucfirst($state))
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                // ... (Kode Kolom Anda Tetap Sama) ...
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Peserta')
                    ->description(fn(Order $record) => $record->user->email ?? '-')
                    ->icon('heroicon-o-user')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->copyable()
                    ->searchable()
                    ->fontFamily('mono')
                    ->size(TextColumnSize::ExtraSmall)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Bayar')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'refunded' => 'danger',
                        'completed' => 'info',
                        'expired' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Join')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid' => 'Paid (Lunas)',
                        'pending' => 'Pending (Belum Bayar)',
                        'completed' => 'Completed (Selesai)',
                        'refunded' => 'Refunded (Dikembalikan)',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make(),

                // === LOGIKA KICK + REFUND ===
                Action::make('kick')
                    ->label('Kick & Refund')
                    ->icon('heroicon-m-arrow-right-start-on-rectangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Keluarkan Peserta?')
                    ->modalDescription('Jika status sudah PAID, saldo akan otomatis dikembalikan ke Wallet user. Peserta akan dikeluarkan dari grup.')
                    ->modalSubmitActionLabel('Ya, Keluarkan')
                    ->action(function (Order $record) {

                        DB::transaction(function () use ($record) {
                            // 1. Cek apakah perlu refund (hanya jika paid/processing)
                            if (in_array($record->status, ['paid', 'processing'])) {

                                $wallet = Wallet::firstOrCreate(['user_id' => $record->user_id]);

                                // Kembalikan Saldo
                                $wallet->balance += $record->amount;
                                $wallet->save();

                                // Ubah status jadi refunded
                                $record->status = 'refunded';
                            } else {
                                // Jika pending, ubah jadi canceled atau expired
                                $record->status = 'canceled';
                            }

                            // 2. Lepas dari grup (Kick)
                            $record->group_id = null;
                            $record->save();
                        });

                        Notification::make()
                            ->title('Peserta berhasil dikeluarkan & Saldo direfund (jika paid)')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // Bulk Kick Logic (Opsional: Sama seperti di atas, pakai loop)
            ]);
    }
}
