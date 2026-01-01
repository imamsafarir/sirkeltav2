<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Filament\Resources\PromoCodeResource\RelationManagers;
use App\Models\PromoCode;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action; // Pastikan import ini ada di atas, atau gunakan full path seperti di bawah
use Filament\Forms\Set; // Untuk update nilai

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-percent-badge';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Kode Promo')
                    ->placeholder('CONTOH: MERDEKA45')

                    // 1. VISUAL: Memaksa tampilan jadi Huruf Besar lewat CSS
                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])

                    // 2. INTERAKSI: Saat user selesai ngetik (klik luar), otomatis bersihkan
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        // Hapus semua spasi & jadikan huruf besar
                        $cleanState = strtoupper(str_replace(' ', '', $state));
                        $set('code', $cleanState);
                    })

                    // 3. DATABASE: Saat tombol Save ditekan, pastikan data bersih lagi (Jaga-jaga)
                    ->dehydrateStateUsing(fn(?string $state) => strtoupper(str_replace(' ', '', $state))),

                Forms\Components\TextInput::make('discount_amount')
                    ->required()
                    ->numeric()
                    ->label('Nominal Diskon'),

                Forms\Components\Select::make('type')
                    ->options([
                        'fixed' => 'Potongan Rupiah (Rp)',
                        // 'percent' => 'Persentase (%)', // Kita simpan fitur ini utk nanti
                    ])
                    ->default('fixed')
                    ->required(),

                Forms\Components\TextInput::make('usage_limit')
                    ->numeric()
                    ->default(100)
                    ->label('Kuota Kupon'),

                Forms\Components\DateTimePicker::make('expired_at')
                    ->label('Berlaku Sampai')
                    ->required()
                    ->native(false) // Tampilan kalender modern
                    ->seconds(false)
                    // FITUR TOMBOL OTOMATIS
                    ->hint('Set Cepat:') // Label kecil
                    ->hintActions([
                        // Tombol 1 Hari
                        \Filament\Forms\Components\Actions\Action::make('1_hari')
                            ->label('+1 Hari')
                            ->icon('heroicon-m-clock')
                            ->action(function (Set $set) {
                                $set('expired_at', now()->addDay()->toDateTimeString());
                            }),

                        // Tombol 1 Minggu
                        \Filament\Forms\Components\Actions\Action::make('1_minggu')
                            ->label('+1 Minggu')
                            ->icon('heroicon-m-calendar')
                            ->action(function (Set $set) {
                                $set('expired_at', now()->addWeek()->toDateTimeString());
                            }),

                        // Tombol 1 Bulan
                        \Filament\Forms\Components\Actions\Action::make('1_bulan')
                            ->label('+1 Bulan')
                            ->icon('heroicon-m-calendar-days')
                            ->action(function (Set $set) {
                                $set('expired_at', now()->addMonth()->toDateTimeString());
                            }),
                    ]),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktifkan')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')->money('IDR'),
                Tables\Columns\TextColumn::make('used_count')
                    ->label('Terpakai')
                    ->suffix(fn($record) => ' / ' . $record->usage_limit),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('expired_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }
}
