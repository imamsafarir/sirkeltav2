<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Group;
use Illuminate\Support\Facades\DB;

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
        // 1. Cari Grup yang Expired TAPI statusnya belum 'expired' atau 'closed'
        // Kita cari yang 'open', 'full', 'processing', atau 'completed' yang waktunya sudah lewat
        $expiredGroups = Group::whereNotIn('status', ['expired', 'closed'])
            ->where('expired_at', '<', now())
            ->get();

        $count = $expiredGroups->count();
        if ($count === 0) {
            $this->info('Tidak ada grup expired saat ini.');
            return;
        }

        $this->info("Menemukan {$count} grup expired. Memproses...");

        foreach ($expiredGroups as $group) {
            DB::transaction(function () use ($group) {

                // SKENARIO A: Grup Gagal (Masih Open/Full tapi waktu habis)
                // Tindakan: Kembalikan Uang (Refund)
                if (in_array($group->status, ['open', 'full'])) {

                    $paidOrders = $group->orders()->whereIn('status', ['paid', 'processing'])->get();

                    foreach ($paidOrders as $order) {
                        // Refund Saldo ke Dompet User (Pastikan user punya method deposit/wallet)
                        if ($order->user) {
                            // Cek apakah user pakai package laravel-wallet atau logic manual
                            // Sesuaikan baris ini dengan logic dompet Anda
                            if (method_exists($order->user, 'deposit')) {
                                $order->user->deposit($order->amount, [
                                    'description' => "Refund Otomatis: Grup #{$group->id} Gagal (Expired)"
                                ]);
                            } elseif (method_exists($order->user, 'wallet')) {
                                $order->user->wallet->increment('balance', $order->amount);
                            }
                        }

                        // Tandai order sebagai refunded
                        $order->update(['status' => 'refunded']); // Pastikan 'refunded' ada di enum/pilihan status order
                        $this->info(" -> Refund user {$order->user->name} (Order #{$order->invoice_number})");
                    }

                    $this->warn("Grup #{$group->id} Gagal (Open -> Expired). ID siap direcycle.");
                }

                // SKENARIO B: Grup Selesai (Completed tapi masa aktif habis)
                // Tindakan: Hapus Kredensial (Email/Pass) demi keamanan & Privasi
                elseif ($group->status === 'completed') {
                    $this->info("Grup #{$group->id} Selesai Langganan (Completed -> Expired). ID siap direcycle.");

                    // Kita tidak perlu refund karena mereka sudah menikmati layanannya
                    // Tapi kita tandai order mereka selesai/expired juga
                    $group->orders()->update(['status' => 'completed']);
                }

                // AKHIR PROSES:
                // Ubah status jadi 'expired' & Hapus Data Login agar bersih saat di-recycle nanti
                $group->update([
                    'status' => 'expired',
                    'account_email' => null,     // Hapus data sensitif
                    'account_password' => null,  // Hapus data sensitif
                    'additional_info' => null,   // Hapus catatan profil
                ]);
            });
        }

        $this->info('Semua grup expired berhasilipproses!');
    }
}
