<?php

namespace App\Filament\Resources\GroupResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn\TextColumnSize; // Import agar size tidak error
use App\Models\Order;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Daftar Peserta Patungan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        // 1. Nama Peserta
                        Forms\Components\TextInput::make('user_name_view')
                            ->label('Nama Peserta')
                            ->prefixIcon('heroicon-m-user')
                            ->formatStateUsing(fn($record) => $record->user->name ?? '-')
                            ->disabled(),

                        // 2. Email Peserta
                        Forms\Components\TextInput::make('user_email_view')
                            ->label('Email Peserta')
                            ->prefixIcon('heroicon-m-envelope')
                            ->formatStateUsing(fn($record) => $record->user->email ?? '-')
                            ->disabled(),

                        // 3. Invoice
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('No. Invoice')
                            ->prefixIcon('heroicon-m-document-text')
                            ->disabled(),

                        // 4. Nominal Bayar (NAMA KOLOM: amount)
                        Forms\Components\TextInput::make('amount')
                            ->label('Nominal Bayar')
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->disabled(),

                        // 5. Metode Pembayaran (Dari tabel order tidak ada kolom payment_method,
                        // tapi biasanya Xendit pakai payment_channel atau payment_method.
                        // Jika di migrasi tidak ada, kita hidden dulu atau ambil dari relation lain.
                        // Sesuai migrasi Anda, kolom payment_method TIDAK ADA.
                        // Jadi saya ganti menampilkan status saja atau disable field ini jika logicnya ada di controller lain)

                        // KOREKSI: Di migrasi Anda TIDAK ADA kolom 'payment_method'.
                        // Jadi field ini saya HAPUS agar tidak error 'column not found'.

                        Forms\Components\TextInput::make('status')
                            ->label('Status Bayar')
                            ->formatStateUsing(fn(string $state) => ucfirst($state))
                            ->disabled()
                            ->columnSpanFull(), // Biar lebar
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                // 1. Nama
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Peserta')
                    ->description(fn(Order $record) => $record->user->email ?? '-')
                    ->icon('heroicon-o-user')
                    ->sortable()
                    ->searchable(),

                // 2. Invoice
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->copyable()
                    ->searchable()
                    ->fontFamily('mono')
                    ->size(TextColumnSize::ExtraSmall)
                    ->color('gray'),

                // 3. Nominal (NAMA KOLOM: amount)
                Tables\Columns\TextColumn::make('amount')
                    ->label('Bayar')
                    ->money('IDR')
                    ->sortable(),

                // 4. Status
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

                // 5. Tanggal
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
                    ]),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Action::make('kick')
                    ->label('Kick')
                    ->icon('heroicon-m-arrow-right-start-on-rectangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Keluarkan Peserta?')
                    ->modalDescription('Peserta akan dikeluarkan dari grup ini. Data Invoice/Order mereka TIDAK akan dihapus.')
                    ->modalSubmitActionLabel('Ya, Keluarkan')
                    ->action(function (Order $record) {
                        $record->update([
                            'group_id' => null,
                        ]);

                        Notification::make()
                            ->title('Peserta berhasil dikeluarkan')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('kick_bulk')
                    ->label('Kick Terpilih')
                    ->icon('heroicon-m-arrow-right-start-on-rectangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        foreach ($records as $record) {
                            $record->update(['group_id' => null]);
                        }
                        Notification::make()->title('Peserta terpilih dikeluarkan')->success()->send();
                    }),
            ]);
    }
}
