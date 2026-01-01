<?php

namespace App\Filament\Pages;

use App\Settings\PaymentSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManagePayment extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Setup API Xendit';
    protected static ?int $navigationSort = 99;

    protected static string $settings = PaymentSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Xendit API')
                    ->schema([
                        Forms\Components\TextInput::make('xendit_secret_key')
                            ->label('Secret Key')
                            ->password()->revealable()->required(),
                        Forms\Components\TextInput::make('xendit_verification_token')
                            ->label('Callback Verification Token')
                            ->required(),
                        Forms\Components\Toggle::make('is_production')
                            ->label('Live Mode'),
                    ])
            ]);
    }
}
