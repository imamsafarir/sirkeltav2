<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages;
use App\Filament\Resources\GroupResource\RelationManagers;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\GroupResource\RelationManagers\OrdersRelationManager;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Kelola Grup';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- BAGIAN 1: INFO GRUP (Standard) ---
                Forms\Components\Section::make('Informasi Grup')
                    ->schema([
                        Forms\Components\Select::make('product_variant_id')
                            ->relationship('variant', 'name')
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->product->name} - {$record->name}")
                            ->label('Pilih Paket')
                            ->required()
                            ->disabledOn('edit'),

                        Forms\Components\DateTimePicker::make('expired_at')
                            ->label('Batas Waktu')
                            ->required(),
                    ])->columns(2),

                // --- BAGIAN 2: STATUS & KREDENSIAL (Bagian Penting) ---
                Forms\Components\Section::make('Proses Order')
                    ->description('Isi data akun di sini jika grup sudah penuh.')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'open' => 'Open (Cari Anggota)',
                                'full' => 'Full (Menunggu Admin)',
                                'processing' => 'Processing (Sedang Dikerjakan)',
                                'completed' => 'Completed (Selesai/Aktif)',
                                'expired' => 'Expired (Gagal)',
                            ])
                            ->required()
                            ->reactive(), // Agar form di bawah bisa bereaksi

                        // --- INPUT RAHASIA (Hanya diisi Admin) ---
                        Forms\Components\TextInput::make('account_email')
                            ->email()
                            ->label('Email Akun (Netflix/dll)')
                            ->visible(fn($get) => in_array($get('status'), ['processing', 'completed'])),

                        Forms\Components\TextInput::make('account_password')
                            ->label('Password Akun')
                            ->visible(fn($get) => in_array($get('status'), ['processing', 'completed'])),

                        Forms\Components\Textarea::make('additional_info')
                            ->label('Catatan untuk User')
                            ->placeholder('Contoh: Dilarang ubah PIN, pakai Profil 2.')
                            ->columnSpanFull()
                            ->visible(fn($get) => in_array($get('status'), ['processing', 'completed'])),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Menampilkan Brand (Netflix)
                Tables\Columns\TextColumn::make('variant.product.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),

                // Menampilkan Paket (Premium 4K)
                Tables\Columns\TextColumn::make('variant.name')
                    ->label('Paket')
                    ->searchable(),

                // Menampilkan Status Warna-Warni
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'open' => 'success',    // Hijau
                        'full' => 'warning',    // Kuning (Admin harus notice ini!)
                        'processing' => 'info', // Biru
                        'completed' => 'gray',  // Abu
                        'expired' => 'danger',  // Merah
                    }),

                // Menampilkan Slot: Terisi / Total
                Tables\Columns\TextColumn::make('filled_slots')
                    ->label('Peserta')
                    ->badge()
                    ->color(fn(string $state): string => str_contains($state, 'PENUH') ? 'danger' : 'success')
                    ->getStateUsing(function (Group $record) {
                        // 1. Ambil Total Slot
                        // Pakai tanda tanya (?) biar gak error kalau produk dihapus
                        $maxSlots = $record->variant?->total_slots ?? 0;

                        // 2. Hitung Peserta yang VALID saja
                        // (Jangan hitung yang Failed/Canceled)
                        $currentMembers = $record->orders()
                            ->whereIn('status', ['paid', 'processing', 'completed', 'pending'])
                            ->count();

                        // 3. Cek Status Visual
                        if ($maxSlots > 0 && $currentMembers >= $maxSlots) {
                            return "{$currentMembers} / {$maxSlots} (PENUH)";
                        }

                        return "{$currentMembers} / {$maxSlots}";
                    }),

                Tables\Columns\TextColumn::make('expired_at')
                    ->dateTime()
                    ->label('Berakhir Pada')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
                // Filter biar Admin bisa cepat cari yang "Full" saja
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'full' => 'Full',
                        'completed' => 'Completed',
                        'expired' => 'Expired',
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
            // Daftarkan Relation Manager di sini
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Hitung grup yang FULL (Prioritas Admin!)
        return static::getModel()::where('status', 'full')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'full')->count() > 0 ? 'danger' : 'gray';
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }
}
