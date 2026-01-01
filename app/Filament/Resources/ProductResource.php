<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Forms\Set;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Management Produk'; // Mengelompokkan menu
    protected static ?string $navigationLabel = 'Master Produk';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Brand')
                    ->description('Masukkan data brand aplikasi (contoh: Netflix, Spotify).')
                    ->schema([
                        // --- BARIS 1: NAMA & SLUG ---
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Brand')
                                    ->required()
                                    ->placeholder('Contoh: Netflix')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state))),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug URL')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->unique(Product::class, 'slug', ignoreRecord: true),
                            ]),

                        // --- BARIS 2: LOGO ---
                        Forms\Components\FileUpload::make('image')
                            ->label('Logo Brand')
                            ->image()
                            ->directory('products')
                            ->columnSpanFull(),

                        // --- BARIS 3: DESKRIPSI ---
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi Singkat')
                            ->rows(3)
                            ->columnSpanFull(),

                        // --- BARIS 4: STATUS ---
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktifkan Produk?')
                            ->default(true)
                            ->helperText('Jika dimatikan, semua paket di dalam brand ini tidak akan muncul.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. LOGO
                Tables\Columns\ImageColumn::make('image')
                    ->label('Logo')
                    ->circular(),

                // 2. NAMA BRAND
                Tables\Columns\TextColumn::make('name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                // 3. SLUG (PERBAIKAN DISINI)
                Tables\Columns\TextColumn::make('slug')
                    ->label('URL Slug')
                    ->color('gray')
                    // Saya ganti italic() dengan extraAttributes karena italic() tidak ada
                    ->extraAttributes(['class' => 'italic']),

                // 4. STATUS
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }
}
