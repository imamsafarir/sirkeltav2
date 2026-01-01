<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductVariantResource\Pages;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Actions\Action; // Import Penting 1
use Filament\Forms\Set; // Import Penting 2

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag'; // Ikon Tag lebih cocok
    protected static ?string $navigationGroup = 'Management Produk'; // Mengelompokkan menu
    protected static ?string $navigationLabel = 'Paket Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Paket')
                    ->description('Atur harga, kuota, ve durasi paket di sini.')
                    ->schema([
                        // Baris 1: Brand & Nama
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->required()
                                    ->searchable() // Biar gampang cari kalau produk banyak
                                    ->preload()
                                    ->label('Brand Produk'),

                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->placeholder('Contoh: Premium UHD 4K')
                                    ->label('Nama Paket'),
                            ])->columns(2),

                        // Baris 2: Harga & Slot
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->label('Harga Per Slot'),

                                Forms\Components\TextInput::make('total_slots')
                                    ->required()
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(1)
                                    ->suffix('Orang')
                                    ->label('Kuota Patungan'),
                            ])->columns(2),

                        // Baris 3: Durasi dengan TOMBOL KEREN
                        Forms\Components\TextInput::make('duration_days')
                            ->required()
                            ->numeric()
                            ->label('Durasi Aktif')
                            ->suffix('Hari')
                            ->default(30)
                            ->hint('Set Cepat:')
                            ->hintActions([
                                Action::make('1_hari')
                                    ->label('1 Hari')
                                    ->action(fn(Set $set) => $set('duration_days', 1)),
                                Action::make('1_minggu')
                                    ->label('1 Minggu')
                                    ->action(fn(Set $set) => $set('duration_days', 7)),
                                Action::make('1_bulan')
                                    ->label('1 Bulan')
                                    ->action(fn(Set $set) => $set('duration_days', 30)),
                                Action::make('1_tahun')
                                    ->label('1 Tahun')
                                    ->action(fn(Set $set) => $set('duration_days', 365)),
                            ]),

                        // Toggle Aktif/Nonaktif
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Jika dimatikan, paket ini tidak akan muncul di halaman depan.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Paket')
                    ->searchable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_slots')
                    ->label('Kuota')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Durasi')
                    ->suffix(' Hari'),

                Tables\Columns\ToggleColumn::make('is_active') // Bisa on/off langsung dari tabel
                    ->label('Aktif?'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductVariants::route('/'),
            'create' => Pages\CreateProductVariant::route('/create'),
            'edit' => Pages\EditProductVariant::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }
}
