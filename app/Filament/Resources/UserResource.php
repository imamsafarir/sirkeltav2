<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash; // WAJIB ADA: Untuk enkripsi password

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Ikon User
    protected static ?string $navigationGroup = 'Management User'; // Mengelompokkan menu
    protected static ?int $navigationSort = 1; // Agar menu ini muncul paling atas

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Lengkap'),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true), // Cek unik kecuali punya diri sendiri

                // Input Password dengan logika canggih (Hanya wajib saat Create)
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => Hash::make($state)) // Enkripsi saat simpan
                    ->dehydrated(fn($state) => filled($state)) // Hanya simpan jika diisi
                    ->required(fn(string $context): bool => $context === 'create')
                    ->label('Password (Isi hanya jika ingin ganti)'),

                Forms\Components\Select::make('role')
                    ->options([
                        'admin' => 'Administrator',
                        'customer' => 'Customer',
                    ])
                    ->default('customer')
                    ->required(),

                // Menampilkan Kode Referral (Read Only saja biar admin ga iseng ubah)
                Forms\Components\TextInput::make('referral_code')
                    ->disabled()
                    ->label('Kode Referral'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                // Menampilkan Role dengan warna badge
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'danger',   // Merah untuk Admin
                        'customer' => 'success', // Hijau untuk Customer
                    }),

                // FITUR KEREN: Intip saldo wallet langsung di tabel user
                Tables\Columns\TextColumn::make('wallet.balance')
                    ->money('IDR')
                    ->label('Saldo Dompet')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
                // Filter untuk memisahkan Admin dan Customer
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Administrator',
                        'customer' => 'Customer',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            // Daftarkan di sini
            RelationManagers\ReferralsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }
}
