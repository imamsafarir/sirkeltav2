<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Formatnya: 'nama_group.nama_variable', 'nilai_default'

        // 1. Secret Key (Default kosong dulu)
        $this->migrator->add('payment.xendit_secret_key', '');

        // 2. Token Verifikasi (Default kosong dulu)
        $this->migrator->add('payment.xendit_verification_token', '');

        // 3. Status Production (Default False/Sandbox)
        $this->migrator->add('payment.is_production', false);
    }
};
