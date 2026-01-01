<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Register as BaseRegister; // Kita "bonceng" class aslinya
use Filament\Forms\Form;
use Illuminate\Support\Str;

class Register extends BaseRegister
{
    // 1. Modifikasi Form Tampilan
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),

                // TAMBAHAN: Input Kode Referral
                TextInput::make('referral_code_input')
                    ->label('Kode Referral Teman (Opsional)')
                    ->placeholder('Masukan kode teman kamu (jika ada)')
                    ->maxLength(255)
                    // 1. Definisikan Rule: Cek tabel 'users', kolom 'referral_code'
                    ->exists(table: 'users', column: 'referral_code')
                    // 2. Definisikan Pesan Error di sini (terpisah)
                    ->validationMessages([
                        'exists' => 'Kode referral tidak ditemukan atau salah ketik.',
                    ]),
            ])
            ->statePath('data');
    }

    // 2. Modifikasi Proses Simpan Data
    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Cari User pemilik kode (Upline)
        $upline = null;
        if (!empty($data['referral_code_input'])) {
            $upline = User::where('referral_code', $data['referral_code_input'])->first();
        }

        // Generate Kode Referral Unik untuk User Baru ini (Misal: BUDI-X82A)
        // Kita gabungkan Nama Depan + Random String biar keren
        $namePart = Str::slug(explode(' ', $data['name'])[0]);
        $randomPart = strtoupper(Str::random(5));
        $myReferralCode = strtoupper($namePart . '-' . $randomPart);

        // Simpan User Baru
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Password otomatis di-hash oleh model user (cast) atau filament
            'role' => 'customer', // Default role
            'referral_code' => $myReferralCode, // Kode dia sendiri
            'referred_by' => $upline ? $upline->id : null, // ID teman yang mengajak
        ]);

        return $user;
    }
}
