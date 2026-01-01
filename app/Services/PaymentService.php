<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Notifications\PaymentSuccessNotification;

class PaymentService
{
    /**
     * Fungsi ini dipanggil saat Xendit memberi tahu pembayaran SUKSES.
     * Kita pakai 'lockForUpdate' agar tidak ada rebutan slot.
     */
    public function confirmPayment($orderId)
    {
        // Mulai Transaksi Database (Semua sukses atau batal semua)
        DB::transaction(function () use ($orderId) {

            // 1. Ambil data Order
            $order = Order::with('group.variant')->find($orderId);

            // Cek apakah order sudah paid sebelumnya? (Biar ga double process)
            if ($order->status === 'paid' || $order->status === 'completed') {
                return;
            }

            // 2. KUNCI GRUPNYA! (lockForUpdate)
            // Selama proses ini berjalan, user lain tidak bisa mengubah data grup ini.
            $group = Group::where('id', $order->group_id)->lockForUpdate()->first();

            // 3. Cek apakah Slot masih ada?
            // Hitung jumlah yang sudah PAID di grup ini
            $currentPaid = $group->orders()->whereIn('status', ['paid', 'completed'])->count();
            $maxSlots = $group->variant->total_slots;

            if ($currentPaid >= $maxSlots) {
                // Oops, telat! Slot sudah diambil orang lain barusan.
                // Refund uang user ke Wallet otomatis
                $order->user->deposit($order->amount, "Refund Otomatis (Slot Penuh)");
                $order->update(['status' => 'refunded']);

                throw new Exception("Slot grup sudah penuh, dana dikembalikan ke wallet.");
            }

            // 4. Jika Slot Aman, Ubah Status Order jadi PAID
            $order->update(['status' => 'paid']);

            // --- TAMBAHAN BARU: KIRIM NOTIFIKASI ---
            try {
                $order->user->notify(new PaymentSuccessNotification($order));
            } catch (\Exception $e) {
                // Jangan sampai error kirim email membatalkan transaksi database
                // Cukup log error-nya saja
                \Illuminate\Support\Facades\Log::error("Gagal kirim notif: " . $e->getMessage());
            }
            // ---------------------------------------

            // 5. Cek lagi, apakah sekarang jadi FULL?
            // Kita tambah 1 karena order ini barusan jadi paid
            if (($currentPaid + 1) >= $maxSlots) {
                $group->update(['status' => 'full']);
            }
        });
    }
}
