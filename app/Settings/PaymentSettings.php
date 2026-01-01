<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    public string $xendit_secret_key;
    public string $xendit_verification_token;
    public bool $is_production;

    public static function group(): string
    {
        return 'payment';
    }
}
