<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Group;
use App\Models\Order;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. BUAT ADMIN
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@sirkelta.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'referral_code' => 'ADMIN001'
        ]);

        // Buat Wallet Admin (Penting agar tidak error jika sistem cek wallet)
        Wallet::create(['user_id' => $admin->id, 'balance' => 0]);

        $this->command->info('✅ Admin created.');

        // 2. BUAT 10 CUSTOMER DUMMY (Untuk simulasi peserta)
        $customers = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = User::create([
                'name' => "Customer $i",
                'email' => "user$i@sirkelta.com",
                'password' => Hash::make('password'),
                'role' => 'customer',
                'referral_code' => "USER00$i"
            ]);

            // Isi Saldo Awal (Rp 500.000) biar bisa test beli
            Wallet::create(['user_id' => $user->id, 'balance' => 500000]);

            $customers[] = $user;
        }

        $this->command->info('✅ 10 Customers created with Wallet.');

        // 3. BUAT PRODUK & VARIAN

        // --- A. NETFLIX ---
        $netflix = Product::create([
            'name' => 'Netflix Premium',
            'slug' => 'netflix-premium',
            'description' => 'Nonton film tanpa batas kualitas 4K UHD. Legal dan Bergaransi.',
            'is_active' => true,
        ]);

        $netflixSharing = ProductVariant::create([
            'product_id' => $netflix->id,
            'name' => 'Sharing 1 Bulan (1 Device)',
            'price' => 28000,
            'total_slots' => 5, // 1 Akun dibagi 5 orang
            'duration_days' => 30,
            'features' => "<ul><li>Kualitas 4K UHD</li><li>1 Device / User</li><li>Akun Sharing (1 Profil)</li><li>Legal & Bergaransi</li></ul>",
            'is_active' => true,
        ]);

        $netflixPrivate = ProductVariant::create([
            'product_id' => $netflix->id,
            'name' => 'Private 1 Bulan (5 Profile)',
            'price' => 120000,
            'total_slots' => 1, // 1 Orang beli 1 akun full
            'duration_days' => 30,
            'features' => "<ul><li>Akun Private (Bisa Ubah Password)</li><li>5 Profil Aktif</li><li>Kualitas 4K UHD</li><li>Garansi Full</li></ul>",
            'is_active' => true,
        ]);

        // --- B. SPOTIFY ---
        $spotify = Product::create([
            'name' => 'Spotify Premium',
            'slug' => 'spotify-premium',
            'description' => 'Dengarkan musik tanpa iklan. Mode offline tersedia.',
            'is_active' => true,
        ]);

        $spotifyFam = ProductVariant::create([
            'product_id' => $spotify->id,
            'name' => 'Plan Family 1 Bulan',
            'price' => 15000,
            'total_slots' => 5,
            'duration_days' => 30,
            'features' => "<ul><li>Invite Email Sendiri</li><li>Tanpa Iklan</li><li>Bisa Download Offline</li><li>Garansi Full</li></ul>",
            'is_active' => true,
        ]);

        // --- C. YOUTUBE ---
        $youtube = Product::create([
            'name' => 'Youtube Premium',
            'slug' => 'youtube-premium',
            'description' => 'Nonton video tanpa iklan dan akses Youtube Music.',
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $youtube->id,
            'name' => 'Via Invite 1 Bulan',
            'price' => 10000,
            'total_slots' => 5,
            'duration_days' => 30,
            'features' => "<ul><li>Invite Email Sendiri</li><li>No Ads</li><li>Background Play</li><li>Termasuk YT Music</li></ul>",
            'is_active' => true,
        ]);

        $this->command->info('✅ Products & Variants created.');

        // 4. SIMULASI GRUP & ORDER

        // --- SKENARIO 1: GRUP NETFLIX YANG SUDAH PENUH (FULL) ---
        // Grup ini isinya 5 orang, statusnya FULL, menunggu Admin memproses.
        $groupFull = Group::create([
            'product_variant_id' => $netflixSharing->id,
            'status' => 'full',
            'expired_at' => Carbon::now()->addHours(24),
        ]);

        // Masukkan 5 Customer pertama ke grup ini
        for ($i = 0; $i < 5; $i++) {
            Order::create([
                'type' => 'product',
                'user_id' => $customers[$i]->id,
                'group_id' => $groupFull->id,
                'product_variant_id' => $netflixSharing->id,
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'amount' => $netflixSharing->price,
                'status' => 'paid', // Sudah bayar
                'description' => 'Pembelian Netflix Sharing',
            ]);
        }

        // --- SKENARIO 2: GRUP SPOTIFY YANG MASIH OPEN (Baru 2 orang) ---
        $groupOpen = Group::create([
            'product_variant_id' => $spotifyFam->id,
            'status' => 'open',
            'expired_at' => Carbon::now()->addHours(24),
        ]);

        // Masukkan 2 Customer berikutnya ke grup ini
        for ($i = 5; $i < 7; $i++) {
            Order::create([
                'type' => 'product',
                'user_id' => $customers[$i]->id,
                'group_id' => $groupOpen->id,
                'product_variant_id' => $spotifyFam->id,
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'amount' => $spotifyFam->price,
                'status' => 'paid',
                'description' => 'Pembelian Spotify Family',
            ]);
        }

        $this->command->info('✅ Dummy Groups & Orders created.');
        $this->command->info('---------------------------------------');
        $this->command->info('Login Admin: admin@sirkelta.com | password');
        $this->command->info('Login User: user1@sirkelta.com | password');
    }
}
