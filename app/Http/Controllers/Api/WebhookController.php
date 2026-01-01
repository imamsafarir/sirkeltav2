<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Settings\PaymentSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function handle(Request $request, PaymentSettings $settings)
    {
        // 1. Cek Verifikasi Token (Keamanan)
        // Agar tidak ada orang iseng nembak URL ini selain Xendit
        $incomingToken = $request->header('x-callback-token');

        if ($incomingToken !== $settings->xendit_verification_token) {
            return response()->json(['message' => 'Akses Ditolak! Token Salah.'], 403);
        }

        // 2. Ambil Data dari Xendit
        $data = $request->all();
        $orderId = $data['external_id']; // Ini ID Order yang kita kirim saat checkout
        $status = $data['status'];       // PAID, EXPIRED, dll

        // 3. Cari Order di Database
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan'], 404);
        }

        // 4. Update Status Order
        if ($status === 'PAID') {
            // Gunakan Transaction agar aman
            DB::transaction(function () use ($order) {
                $order->update(['status' => 'paid']);

                // Tambahan: Cek apakah grup sudah penuh?
                // Jika perlu logika update status Group, bisa taruh sini.
            });
        } elseif ($status === 'EXPIRED') {
            $order->update(['status' => 'failed']);
        }

        return response()->json(['message' => 'Status berhasil diupdate']);
    }
}
