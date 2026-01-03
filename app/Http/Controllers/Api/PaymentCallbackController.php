<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('--- CALLBACK MIDTRANS MASUK ---');
        Log::info('Payload:', $request->all());

        $orderId = $request->order_id;
        $status = $request->transaction_status;

        $order = Order::where('invoice_number', $orderId)->first();

        if (!$order) {
            Log::error("Order ID $orderId tidak ditemukan di database.");
            return response()->json(['message' => 'Order not found'], 404);
        }

        // 1. CEK STATUS: Tambahkan pengecekan 'completed' agar tidak diproses 2x
        if ($order->status == 'paid' || $order->status == 'completed') {
            Log::info("Order $orderId sudah lunas sebelumnya. Skip.");
            return response()->json(['message' => 'Already processed']);
        }

        if ($status == 'capture' || $status == 'settlement') {

            // 2. LOGIKA STATUS BERBEDA (Perbaikan Utama)

            if ($order->type === 'topup') {
                // --- KASUS TOP UP: LANGSUNG COMPLETED ---
                $order->update(['status' => 'completed']);

                if ($order->user && $order->user->wallet) {
                    $order->user->wallet->increment('balance', $order->amount);
                    Log::info("Top Up Sukses. Saldo User ID {$order->user_id} bertambah Rp {$order->amount}");
                } else {
                    Log::error("User ID {$order->user_id} tidak memiliki wallet!");
                }
            } elseif ($order->type === 'product') {
                // --- KASUS BELI PRODUK: STATUS PAID (MENUNGGU ADMIN) ---
                $order->update(['status' => 'paid']);

                Log::info("Order Produk $orderId status updated to PAID");
                $this->checkGroupFull($order);
            }
        } else if ($status == 'deny' || $status == 'expire' || $status == 'cancel') {
            $order->update(['status' => 'failed']);
            Log::info("Order $orderId status updated to FAILED");
        } else if ($status == 'pending') {
            $order->update(['status' => 'pending']);
        }

        return response()->json(['message' => 'Callback processed']);
    }

    private function checkGroupFull($order)
    {
        $group = $order->group;
        if ($group && $order->variant) {
            $maxSlots = $order->variant->total_slots ?? 5;
            $paidMembers = $group->orders()
                ->whereIn('status', ['paid', 'processing', 'completed'])
                ->count();

            if ($paidMembers >= $maxSlots) {
                $group->update(['status' => 'full']);
                Log::info("Grup ID {$group->id} sekarang FULL.");
            }
        }
    }
}
