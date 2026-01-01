<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Group;
use App\Models\Order;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WebOrderController extends Controller
{
    public function checkout(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/admin/login');
        }

        $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'promo_code' => 'nullable|string',
        ]);

        $user = Auth::user();
        $variant = ProductVariant::find($request->product_variant_id);

        try {
            return DB::transaction(function () use ($user, $variant, $request) {

                // --- LOGIKA 1: CEK PROMO ---
                $finalPrice = $variant->price;
                $promoUsed = null;

                if ($request->filled('promo_code')) {
                    $code = strtoupper(trim($request->promo_code));

                    // Kunci baris promo biar gak balapan (Race Condition)
                    $promo = PromoCode::where('code', $code)->lockForUpdate()->first();

                    // Validasi Ketat
                    if (!$promo) throw new \Exception("Kode '$code' tidak ditemukan.");
                    if (!$promo->is_active) throw new \Exception("Kode promo sedang non-aktif.");
                    if ($promo->expired_at < now()) throw new \Exception("Kode sudah kadaluarsa.");
                    if ($promo->used_count >= $promo->usage_limit) throw new \Exception("Kuota promo habis.");

                    if ($promo->type == 'fixed') {
                        $finalPrice = $variant->price - $promo->discount_amount;
                    }

                    if ($finalPrice < 0) $finalPrice = 0;
                    $promoUsed = $promo;
                }

                // --- LOGIKA 2: CARI GRUP (PRIORITAS ID TERKECIL) ---
                $selectedGroup = null;

                $candidates = Group::where('product_variant_id', $variant->id)
                    ->where('status', 'open')
                    ->where('expired_at', '>', now())
                    ->orderBy('id', 'asc') // PENTING: ID Terkecil dulu
                    ->lockForUpdate()
                    ->get();

                foreach ($candidates as $candidate) {
                    // Hitung slot (termasuk pending biar aman)
                    $terisi = $candidate->orders()
                        ->whereIn('status', ['paid', 'processing', 'completed', 'pending'])
                        ->count();

                    if ($terisi < $variant->total_slots) {
                        $selectedGroup = $candidate;
                        break; // Ketemu! Stop looping.
                    } else {
                        // Sekalian bersih-bersih: Kalau penuh, tandai Full
                        $candidate->update(['status' => 'full']);
                    }
                }

                // Jika tidak ada grup kosong, buat baru
                if (!$selectedGroup) {
                    $selectedGroup = Group::create([
                        'product_variant_id' => $variant->id,
                        'status' => 'open',
                        'expired_at' => now()->addHours($variant->group_timeout_hours)
                    ]);
                }

                $group = $selectedGroup;

                // --- LOGIKA 3: BUAT ORDER ---
                $invoiceNumber = 'INV-' . strtoupper(Str::random(6)) . date('dmY');

                $order = Order::create([
                    'user_id' => $user->id,
                    'group_id' => $group->id,
                    'product_variant_id' => $variant->id,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $finalPrice,
                    'status' => 'pending'
                ]);

                if ($promoUsed) {
                    $promoUsed->increment('used_count');
                }

                // --- LOGIKA 4: PEMBAYARAN ---

                // KASUS A: GRATIS (Rp 0)
                if ($finalPrice <= 0) {
                    $order->update(['status' => 'paid']);

                    // UPDATE STATUS GRUP JADI FULL (JIKA SLOT PENUH)
                    $this->checkGroupFull($group, $variant);

                    return redirect()->route('payment.success')->with('success', 'Promo berhasil! Pesanan gratis.');
                }

                // KASUS B: BAYAR (XENDIT / SIMULASI)
                $secretKey = env('XENDIT_SECRET_KEY');
                $pembayaranUrl = null;

                if ($secretKey) {
                    try {
                        $response = Http::withBasicAuth($secretKey, '')
                            ->post('https://api.xendit.co/v2/invoices', [
                                'external_id' => $invoiceNumber,
                                'amount' => $order->amount,
                                'payer_email' => $user->email,
                                'description' => "Patungan " . $variant->name,
                                'invoice_duration' => 1800,
                                'success_redirect_url' => route('payment.success'),
                                'failure_redirect_url' => route('home'),
                            ]);

                        if ($response->successful()) {
                            $xenditData = $response->json();
                            $pembayaranUrl = $xenditData['invoice_url'];
                            $order->update(['xendit_external_id' => $xenditData['id']]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Xendit Error: ' . $e->getMessage());
                    }
                }

                // FALLBACK: JIKA XENDIT GAGAL / TIDAK ADA KEY (MODE SIMULASI)
                if (!$pembayaranUrl) {
                    $pembayaranUrl = route('payment.success');

                    // Anggap langsung lunas (Simulasi)
                    $order->update(['status' => 'paid']);

                    // [PENTING] UPDATE STATUS GRUP JADI FULL DISINI
                    $this->checkGroupFull($group, $variant);
                }

                $order->update(['payment_url' => $pembayaranUrl]);

                return redirect()->away($pembayaranUrl);
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function success()
    {
        return view('payment-success');
    }

    // --- FUNGSI TAMBAHAN PENTING ---
    // Tugas: Mengecek apakah grup sudah penuh setelah ada pembayaran masuk
    private function checkGroupFull($group, $variant)
    {
        // Hitung ulang jumlah member yang SUDAH BAYAR (Valid)
        $currentMembers = $group->orders()
            ->whereIn('status', ['paid', 'processing', 'completed'])
            ->count();

        // Jika sudah mencapai batas slot -> Ubah status Grup jadi FULL
        if ($currentMembers >= $variant->total_slots) {
            $group->update(['status' => 'full']);
        }
    }
}
