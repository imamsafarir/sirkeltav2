<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Group;
use App\Models\Order;
use App\Models\PromoCode; // Pastikan Model PromoCode ada
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class WebOrderController extends Controller
{
    public function checkout(Request $request)
    {
        // 1. Cek Login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // 2. Validasi Input
        $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'promo_code' => 'nullable|string',
        ]);

        $user = Auth::user();
        $variant = ProductVariant::with('product')->find($request->product_variant_id);
        $productId = $variant->product_id;

        // --- A. LOGIKA PEMBATASAN (ANTI SPAM) ---
        // Cek apakah user memiliki order aktif (Pending/Paid/Processing) di PRODUK yang sama
        $existingOrder = Order::where('user_id', $user->id)
            ->whereHas('variant', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->whereIn('status', ['pending', 'paid', 'processing'])
            ->first();

        if ($existingOrder) {
            return redirect()->back()->withErrors([
                'error' => "Anda masih memiliki transaksi aktif untuk layanan " . $variant->product->name . ". Harap selesaikan (Invoice: {$existingOrder->invoice_number}) sebelum membeli lagi."
            ]);
        }

        try {
            return DB::transaction(function () use ($user, $variant, $request) {

                // --- B. LOGIKA PROMO CODE ---
                $finalPrice = $variant->price;
                $promoUsed = null;

                if ($request->filled('promo_code')) {
                    $code = strtoupper(trim($request->promo_code));

                    // Kunci baris promo biar aman dari rebutan (Race Condition)
                    $promo = PromoCode::where('code', $code)->lockForUpdate()->first();

                    // Validasi Promo Ketat
                    if (!$promo) throw new \Exception("Kode promo '$code' tidak ditemukan.");
                    if (!$promo->is_active) throw new \Exception("Kode promo sedang non-aktif.");
                    if ($promo->expired_at && $promo->expired_at < now()) throw new \Exception("Kode promo sudah kadaluarsa.");
                    if ($promo->usage_limit > 0 && $promo->used_count >= $promo->usage_limit) throw new \Exception("Kuota promo sudah habis.");

                    // Hitung Diskon (Fixed Amount)
                    // Jika butuh persentase, tambahkan logika di sini
                    if ($promo->type == 'fixed') {
                        $finalPrice = $variant->price - $promo->discount_amount;
                    }

                    if ($finalPrice < 0) $finalPrice = 0;
                    $promoUsed = $promo;
                }

                // --- C. LOGIKA PENCARIAN GRUP (PRIORITAS ID TERKECIL) ---
                $selectedGroup = null;

                // Ambil semua kandidat grup yang OPEN
                $candidates = Group::where('product_variant_id', $variant->id)
                    ->where('status', 'open')
                    ->where('expired_at', '>', now())
                    ->orderBy('id', 'asc') // Utamakan grup lama dulu
                    ->lockForUpdate()
                    ->get();

                foreach ($candidates as $candidate) {
                    // Hitung slot (termasuk yang pending biar tidak overbook)
                    $terisi = $candidate->orders()
                        ->whereIn('status', ['paid', 'processing', 'completed', 'pending'])
                        ->count();

                    if ($terisi < ($variant->total_slots ?? 5)) {
                        $selectedGroup = $candidate;
                        break; // Ketemu grup kosong! Stop looping.
                    } else {
                        // Kalau ternyata penuh, tandai Full sekalian bersih-bersih
                        $candidate->update(['status' => 'full']);
                    }
                }

                // Jika tidak ada grup kosong, buat baru
                if (!$selectedGroup) {
                    $selectedGroup = Group::create([
                        'name' => $variant->name . ' - ' . Str::random(5),
                        'product_variant_id' => $variant->id,
                        'status' => 'open',
                        'max_members' => $variant->total_slots ?? 5,
                        'expired_at' => now()->addHours($variant->group_timeout_hours ?? 24)
                    ]);
                }

                $group = $selectedGroup;

                // --- D. BUAT ORDER ---
                $invoiceNumber = 'INV-' . strtoupper(Str::random(6)) . date('dmY');

                $order = Order::create([
                    'user_id' => $user->id,
                    'group_id' => $group->id,
                    'product_variant_id' => $variant->id,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $finalPrice, // Pakai harga setelah diskon
                    'status' => 'pending',
                    'payment_url' => null, // Manual
                ]);

                // Update kuota promo jika dipakai
                if ($promoUsed) {
                    $promoUsed->increment('used_count');
                }

                // --- E. LOGIKA PEMBAYARAN (MANUAL / GRATIS) ---

                // KASUS 1: GRATIS (Rp 0 karena Promo)
                if ($finalPrice <= 0) {
                    $order->update(['status' => 'paid']);

                    // Cek apakah grup jadi penuh
                    $this->checkGroupFull($group, $variant);

                    // Redirect ke Sukses (Bukan Dashboard) karena sudah lunas
                    return redirect()->route('dashboard')->with('success', 'Promo berhasil! Paket Anda aktif (Gratis).');
                }

                // KASUS 2: BAYAR (MANUAL VIA WA)
                // Lempar ke Dashboard -> Widget WA akan muncul
                return redirect()->route('dashboard')->with('success', 'Order berhasil! Silakan konfirmasi pembayaran via WhatsApp.');
            });
        } catch (\Exception $e) {
            // Tangkap error (misal promo habis) dan kembalikan ke halaman produk
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Helper: Cek apakah grup sudah penuh, jika ya set status 'full'
     */
    private function checkGroupFull($group, $variant)
    {
        $currentMembers = $group->orders()
            ->whereIn('status', ['paid', 'processing', 'completed']) // Hanya hitung yang pasti bayar
            ->count();

        if ($currentMembers >= ($variant->total_slots ?? 5)) {
            $group->update(['status' => 'full']);
        }
    }
}
