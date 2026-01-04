<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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
                // --- BAGIAN 1: INFO GRUP ---
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

                // --- BAGIAN 2: PROSES ORDER ---
                Forms\Components\Section::make('Proses Order & Kredensial')
                    ->description('Isi data akun saat status Processing atau Completed.')
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
                            ->live(),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('account_email')
                                    ->email()
                                    ->label('Email Akun Login')
                                    ->prefixIcon('heroicon-m-envelope'),

                                Forms\Components\TextInput::make('account_password')
                                    ->label('Password Akun')
                                    ->prefixIcon('heroicon-m-key'),
                            ])
                            ->columns(2)
                            ->visible(fn($get) => in_array($get('status'), ['processing', 'completed'])),

                        Forms\Components\Repeater::make('additional_info')
                            ->label('Catatan / Pembagian Profil')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Pilih Peserta')
                                    ->options(function ($record) {
                                        if (!$record) return [];
                                        return $record->orders()
                                            ->with('user')
                                            ->get()
                                            ->pluck('user.name', 'user.id');
                                    })
                                    ->searchable()
                                    ->required(),

                                Forms\Components\TextInput::make('note')
                                    ->label('Catatan Khusus (Misal: Pakai Profil 2)')
                                    ->required(),
                            ])
                            ->itemLabel(fn(array $state): ?string => $state['note'] ?? null)
                            ->columns(2)
                            ->addActionLabel('Tambah Catatan Peserta')
                            ->visible(fn($get) => in_array($get('status'), ['processing', 'completed'])),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('variant.product.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('variant.name')
                    ->label('Paket')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'open' => 'success',
                        'full' => 'warning',
                        'processing' => 'info',
                        'completed' => 'gray',
                        'expired' => 'danger',
                        default => 'secondary', // Fallback color
                    }),

                Tables\Columns\TextColumn::make('filled_slots')
                    ->label('Peserta')
                    ->badge()
                    ->color(fn(string $state): string => str_contains($state, 'PENUH') ? 'danger' : 'success')
                    ->getStateUsing(function (Group $record) {
                        $maxSlots = $record->variant?->total_slots ?? 0;
                        $currentMembers = $record->orders()->whereIn('status', ['paid', 'processing', 'completed', 'pending'])->count();
                        return ($maxSlots > 0 && $currentMembers >= $maxSlots) ? "{$currentMembers} / {$maxSlots} (PENUH)" : "{$currentMembers} / {$maxSlots}";
                    }),

                Tables\Columns\TextColumn::make('expired_at')
                    ->dateTime()
                    ->label('Berakhir Pada')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['open' => 'Open', 'full' => 'Full', 'completed' => 'Completed', 'expired' => 'Expired']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
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

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }

    // --- BAGIAN BADGE NAVIGASI ---
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'full')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
