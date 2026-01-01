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
        $variant = ProductVariant::find($request->product_variant_id);

        // Bungkus dalam database transaction agar aman
        return DB::transaction(function () use ($user, $variant) {

            // --- A. LOGIKA CARI/BUAT GRUP (TETAP DIPERTAHANKAN) ---
            $group = Group::where('product_variant_id', $variant->id)
                ->where('status', 'open')
                ->where('expired_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($group) {
                $terisi = $group->orders()->whereIn('status', ['paid', 'completed', 'processing'])->count();
                // Jika slot penuh, reset variabel group biar bikin baru
                if ($terisi >= ($variant->total_slots ?? 5)) {
                    $group = null;
                }
            }

            // Jika tidak ada grup yang open, buat baru
            if (!$group) {
                $group = Group::create([
                    'name' => $variant->name . ' - ' . Str::random(5),
                    'product_variant_id' => $variant->id,
                    'status' => 'open',
                    'max_members' => $variant->total_slots ?? 5,
                    'expired_at' => now()->addHours($variant->group_timeout_hours ?? 24)
                ]);
            }

            // --- B. BUAT ORDER LOCAL ---
            $invoiceNumber = 'INV-' . strtoupper(Str::random(6)) . date('dmY');

            $order = Order::create([
                'user_id' => $user->id,
                'group_id' => $group->id,
                'product_variant_id' => $variant->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $variant->price,
                'status' => 'pending', // Status awal pending
                // 'payment_url' => null, // Tidak ada URL pembayaran
            ]);

            // --- C. BYPASS XENDIT (LANGSUNG SUKSES) ---
            // Kita matikan koneksi ke Xendit.
            // Langsung redirect user ke dashboard.

            return redirect()->route('dashboard')->with('success', 'Order berhasil dibuat! Silakan hubungi admin untuk pembayaran manual.');
        });
    }
}
