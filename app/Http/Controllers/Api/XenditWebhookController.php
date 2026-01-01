<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService; // Panggil Service yang tadi kita buat
use Illuminate\Http\Request;

class XenditWebhookController extends Controller
{
    public function handle(Request $request, PaymentService $paymentService)
    {
        // 1. Ambil Data dari Xendit
        $data = $request->all();

        // Contoh data dari Xendit: { "external_id": "INV-01", "status": "PAID", ... }
        $invoiceNumber = $data['external_id'] ?? null;
        $status = $data['status'] ?? null;

        // 2. Validasi Token Xendit (Nanti kita pasang di .env)
        if ($request->header('x-callback-token') !== env('XENDIT_CALLBACK_TOKEN')) {
            return response()->json(['message' => 'Invalid Token'], 403);
        }

        // 3. Proses Pembayaran
        if ($status === 'PAID') {
            // Cari order berdasarkan invoice
            $order = Order::where('invoice_number', $invoiceNumber)->first();

            if ($order) {
                try {
                    // Panggil Service Pengaman tadi
                    $paymentService->confirmPayment($order->id);
                    return response()->json(['message' => 'Payment Processed']);
                } catch (\Exception $e) {
                    return response()->json(['message' => $e->getMessage()], 400);
                }
            }
        }

        return response()->json(['message' => 'Order not found or ignored']);
    }
}
