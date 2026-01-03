<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. BUAT ADMIN
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@sirkelta.com',
            'password' => Hash::make('password'), // Password: password
            'role' => 'admin',
        ]);

        // 2. BUAT CUSTOMER TEST
        User::create([
            'name' => '1 Customer',
            'email' => '1@sirkelta.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'referral_code' => 'BUDI-TEST'
        ]);
        User::create([
            'name' => '2 Customer',
            'email' => '2@sirkelta.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'referral_code' => 'BUDI-TEST'
        ]);
        User::create([
            'name' => '3 Customer',
            'email' => '3@sirkelta.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'referral_code' => 'BUDI-TEST'
        ]);

        // 3. BUAT PRODUK CONTOH (NETFLIX)
        $netflix = Product::create([
            'name' => 'Netflix Premium',
            'slug' => 'netflix-premium',
            'description' => 'Nonton film tanpa batas kualitas 4K UHD.',
            'image' => null, // Nanti upload manual kalau mau gambar
            'is_active' => true,
        ]);

        // 4. BUAT VARIAN (PAKET 1 BULAN)
        ProductVariant::create([
            'product_id' => $netflix->id,
            'name' => 'Sharing 1 Bulan (4K)',
            'price' => 25000,
            'total_slots' => 2,
            'duration_days' => 30, // <--- TAMBAHAN: Wajib ada karena ada di migrasi
            'group_timeout_hours' => 24,
            'is_active' => true,   // <--- TAMBAHAN: Ini yang tadi bikin error
        ]);

        $this->command->info('Database berhasil direset! Admin: admin@sirkelta.com | Pass: password');
    }
}
