<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Group;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public function checkout(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
        ]);

        $user = $request->user();
        // Ambil data varian yang mau dibeli
        $variant = ProductVariant::with('product')->find($request->product_variant_id);
        $productId = $variant->product_id;

        // --- LOGIKA PEMBATASAN BERDASARKAN PRODUK ---
        // Cek apakah user punya order yang belum selesai pada PRODUK yang sama
        // Meskipun variannya berbeda (misal: beli 1 bulan vs 3 bulan)
        $existingOrder = Order::where('user_id', $user->id)
            ->whereHas('variant', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->whereIn('status', ['pending', 'paid', 'processing'])
            ->first();

        if ($existingOrder) {
            return redirect()->back()->withErrors([
                'error' => "Anda masih memiliki transaksi aktif untuk layanan " . $variant->product->name . ". Selesaikan pesanan sebelumnya terlebih dahulu."
            ]);
        }
        // --- SELESAI LOGIKA PEMBATASAN ---

        return DB::transaction(function () use ($user, $variant) {
            // ... (Logika pembuatan grup dan order tetap sama seperti sebelumnya) ...

            $group = Group::where('product_variant_id', $variant->id)
                ->where('status', 'open')
                ->where('expired_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($group) {
                $terisi = $group->orders()->whereIn('status', ['paid', 'completed', 'processing'])->count();
                if ($terisi >= ($variant->total_slots ?? 5)) {
                    $group = null;
                }
            }

            if (!$group) {
                $group = Group::create([
                    'name' => $variant->name . ' - ' . Str::random(5),
                    'product_variant_id' => $variant->id,
                    'status' => 'open',
                    'max_members' => $variant->total_slots ?? 5,
                    'expired_at' => now()->addHours($variant->group_timeout_hours ?? 24)
                ]);
            }

            $invoiceNumber = 'INV-' . strtoupper(Str::random(6)) . date('dmY');

            $order = Order::create([
                'user_id' => $user->id,
                'group_id' => $group->id,
                'product_variant_id' => $variant->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $variant->price,
                'status' => 'pending',
                'payment_url' => null,
            ]);

            return redirect()->route('dashboard')->with('success', 'Order berhasil dibuat!');
        });
    }
}
