<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Group;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessExpiredGroups extends Command
{
    /**
     * Nama perintah yang akan dijalankan cron job
     */
    protected $signature = 'app:process-expired-groups';

    /**
     * Penjelasan perintah
     */
    protected $description = 'Mengecek grup yang masa waktunya habis. Jika Open -> Refund. Jika Completed -> Archive. Siap untuk recycle ID.';

    public function handle()
    {
        // 1. Cari Grup yang Waktunya Sudah Lewat (expired_at < now)
        // Dan statusnya belum 'expired' atau 'closed'
        $expiredGroups = Group::whereNotIn('status', ['expired', 'closed'])
            ->where('expired_at', '<', now())
            ->with('orders') // Load orders biar query efisien
            ->get();

        $count = $expiredGroups->count();

        if ($count === 0) {
            $this->info('Tidak ada grup expired saat ini.');
            return;
        }

        $this->info("Menemukan {$count} grup expired. Memproses...");

        foreach ($expiredGroups as $group) {
            DB::transaction(function () use ($group) {

                // --- SKENARIO A: GRUP GAGAL (Masih Open/Full tapi waktu habis) ---
                // Artinya: Belum sempat diproses admin, tapi waktu tunggu habis.
                // Tindakan: REFUND UANG USER.
                if (in_array($group->status, ['open', 'full'])) {

                    $this->warn("Grup #{$group->id} Gagal (Open -> Expired). Melakukan refund...");

                    foreach ($group->orders as $order) {
                        // Hanya refund yang statusnya 'paid' atau 'processing'
                        if (in_array($order->status, ['paid', 'processing'])) {

                            // 1. Cari atau Buat Wallet User
                            $wallet = Wallet::firstOrCreate(['user_id' => $order->user_id]);

                            // 2. Kembalikan Saldo
                            $wallet->increment('balance', $order->amount);

                            // 3. Update Status Order jadi Refunded
                            $order->update(['status' => 'refunded']);

                            $this->info(" -> Refund Rp " . number_format($order->amount) . " ke user ID: {$order->user_id}");

                            // Log System
                            Log::info("AUTO-REFUND: Order #{$order->invoice_number} (Grup #{$group->id} Expired)");
                        } else {
                            // Kalau belum bayar (pending), langsung cancel saja
                            $order->update(['status' => 'canceled']);
                        }
                    }
                }

                // --- SKENARIO B: GRUP SELESAI LANGGANAN (Status Completed) ---
                // Artinya: User sudah menikmati layanan sampai habis.
                // Tindakan: Tidak perlu refund, cukup tandai selesai.
                elseif ($group->status === 'completed') {
                    $this->info("Grup #{$group->id} Selesai Langganan. Membersihkan data...");

                    // Tandai semua order di dalamnya sebagai 'completed' (selesai langganan)
                    // Atau bisa dibiarkan 'completed' saja.
                }

                // --- LANGKAH TERAKHIR (UNTUK SEMUA SKENARIO) ---
                // Ubah status grup jadi 'expired' dan hapus data login
                // Ini penting agar ID grup bisa di-recycle (dipakai ulang) nanti jika pakai sistem recycle ID
                $group->update([
                    'status' => 'expired',
                    'account_email' => null,     // Hapus email akun (keamanan)
                    'account_password' => null,  // Hapus password akun (keamanan)
                    'additional_info' => null,   // Hapus catatan
                ]);

                // Lepaskan (Dissociate) semua member dari grup ini
                // Agar slot grup menjadi kosong dan bisa diisi orang baru (jika sistem recycle ID aktif)
                // $group->orders()->update(['group_id' => null]); // OPSI: Uncomment baris ini jika ingin grup benar-benar kosong
            });
        }

        $this->info('Semua grup expired berhasil diproses!');
    }
}
