<?php

namespace App\Filament\Resources\GroupResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Daftar Peserta Patungan'; // Judul Tabel

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Kita buat Read Only saja, karena order dibuat user dari depan
                Forms\Components\TextInput::make('user.name')
                    ->label('Nama Peserta')
                    ->disabled(),

                Forms\Components\TextInput::make('invoice_number')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                // Menampilkan Nama User
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Peserta')
                    ->icon('heroicon-o-user')
                    ->sortable(),

                // Menampilkan Invoice
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->copyable(), // Agar admin mudah copy invoice

                // Menampilkan Kapan Join
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Join')
                    ->dateTime(),

                // Status Pembayaran
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'refunded' => 'danger',
                        'completed' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Kita matikan tombol Create, karena peserta masuk lewat Web (bukan Admin)
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tombol untuk melihat detail atau kick peserta (Hapus)
                Tables\Actions\DeleteAction::make()
                    ->label('Kick Member'),
            ])
            ->bulkActions([
                //
            ]);
    }
}
