<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Group;
use App\Models\Order;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

class WebOrderController extends Controller
{
    /**
     * LOGIKA 1: BUAT ORDER PRODUK (Checkout)
     */
    public function checkout(Request $request)
    {
        if (!Auth::check()) return redirect()->route('login');

        $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'promo_code' => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $variant = ProductVariant::with('product')->find($request->product_variant_id);

        // Cek Anti Spam (Hanya untuk produk yang sama)
        $existingOrder = Order::where('user_id', $user->id)
            ->where('product_variant_id', $variant->id)
            ->whereIn('status', ['pending', 'paid', 'processing'])
            ->first();

        if ($existingOrder) {
            return back()->withErrors(['error' => "Selesaikan transaksi sebelumnya (Invoice: {$existingOrder->invoice_number})."]);
        }

        try {
            return DB::transaction(function () use ($user, $variant, $request) {
                // Hitung Harga
                $finalPrice = $variant->price;
                $promoUsed = null;

                if ($request->filled('promo_code')) {
                    $code = strtoupper(trim($request->promo_code));
                    $promo = PromoCode::where('code', $code)->lockForUpdate()->first();
                    if (!$promo || !$promo->is_active || ($promo->usage_limit > 0 && $promo->used_count >= $promo->usage_limit)) {
                        throw new \Exception("Kode promo tidak valid.");
                    }
                    if ($promo->type == 'fixed') $finalPrice -= $promo->discount_amount;
                    if ($finalPrice < 0) $finalPrice = 0;
                    $promoUsed = $promo;
                }

                // Cari Grup
                $selectedGroup = $this->findOrCreateGroup($variant);

                // SIMPAN KE TABEL ORDERS (Type: Product)
                $invoiceNumber = 'INV-' . strtoupper(Str::random(6)) . date('dmY');
                $order = Order::create([
                    'user_id' => $user->id,
                    'type' => 'product', // Tanda bahwa ini beli produk
                    'group_id' => $selectedGroup->id,
                    'product_variant_id' => $variant->id,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $finalPrice,
                    'status' => 'pending',
                    'description' => "Pembelian " . $variant->product->name . " - " . $variant->name
                ]);

                if ($promoUsed) $promoUsed->increment('used_count');

                // Jika Gratis
                if ($finalPrice <= 0) {
                    $order->update(['status' => 'paid']);
                    $this->checkGroupFull($selectedGroup, $variant);
                    return redirect()->route('dashboard')->with('success', 'Promo Berhasil! Paket Gratis.');
                }

                return redirect()->route('payment.show', $order->invoice_number);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * LOGIKA 2: BUAT TOP UP (Sekarang Disimpan di Tabel ORDERS)
     */
    public function topUp(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:10000']);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Gunakan prefix TOPUP
        $invoiceNumber = 'TOPUP-' . strtoupper(Str::random(6)) . date('dmY');

        // SIMPAN KE TABEL ORDERS (Type: Topup)
        // Group & Variant ID dibiarkan NULL
        $order = Order::create([
            'user_id' => $user->id,
            'type' => 'topup', // Tanda bahwa ini Top Up
            'invoice_number' => $invoiceNumber,
            'amount' => $request->amount,
            'status' => 'pending',
            'description' => 'Top Up Saldo Dompet'
        ]);

        // Generate Midtrans Token
        $this->configureMidtrans();
        $params = [
            'transaction_details' => ['order_id' => $invoiceNumber, 'gross_amount' => (int) $request->amount],
            'customer_details' => ['first_name' => $user->name, 'email' => $user->email],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            $order->update(['payment_url' => $snapToken]);
            return redirect()->route('payment.show', $invoiceNumber);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * LOGIKA 3: HALAMAN PEMBAYARAN (Unified)
     * Sekarang tidak perlu cek 2 tabel lagi!
     */
    public function showPayment($invoice)
    {
        // Cukup cari di satu tabel saja sekarang. Simpel kan?
        $order = Order::where('invoice_number', $invoice)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return redirect()->route('dashboard')->with('success', 'Transaksi sudah diproses.');
        }

        return view('payment', compact('order'));
    }

    /**
     * LOGIKA 4: BAYAR PAKAI SALDO (Tanpa WalletTransaction)
     */
    public function payWithWallet($invoice)
    {
        $order = Order::where('invoice_number', $invoice)->where('user_id', Auth::id())->firstOrFail();

        // Pastikan ini bayar produk, bukan topup
        if ($order->type !== 'product') return back()->withErrors(['error' => 'Transaksi tidak valid.']);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userWallet = $user->wallet()->lockForUpdate()->first();

        if (!$userWallet || $userWallet->balance < $order->amount) {
            return back()->withErrors(['error' => 'Saldo kurang.']);
        }

        try {
            DB::transaction(function () use ($userWallet, $order) {
                // 1. Potong Saldo User Langsung
                $userWallet->decrement('balance', $order->amount);

                // 2. Update Status Order jadi PAID
                $order->update([
                    'status' => 'paid',
                    'description' => $order->description . ' (Paid via Wallet)'
                ]);

                // 3. Cek Grup
                $this->checkGroupFull($order->group, $order->variant);
            });

            return redirect()->route('dashboard')->with('success', 'Berhasil bayar pakai saldo!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    /**
     * LOGIKA 5: BAYAR PAKAI MIDTRANS (AJAX)
     */
    public function payWithMidtrans($invoice)
    {
        $order = Order::where('invoice_number', $invoice)->where('user_id', Auth::id())->firstOrFail();

        if ($order->payment_url) return response()->json(['snap_token' => $order->payment_url]);

        $this->configureMidtrans();

        // Nama Item tergantung tipe
        $itemName = $order->type === 'topup' ? 'Top Up Saldo' : substr($order->description, 0, 50);

        $params = [
            'transaction_details' => ['order_id' => $order->invoice_number, 'gross_amount' => (int) $order->amount],
            'customer_details' => ['first_name' => Auth::user()->name, 'email' => Auth::user()->email],
            'item_details' => [[
                'id' => $order->type === 'topup' ? 'TOPUP' : $order->product_variant_id,
                'price' => (int) $order->amount,
                'quantity' => 1,
                'name' => $itemName,
            ]]
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            $order->update(['payment_url' => $snapToken]);
            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // --- HELPER FUNCTIONS ---

    private function configureMidtrans()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    // --- GANTI METHOD findOrCreateGroup DENGAN INI ---
    private function findOrCreateGroup($variant)
    {
        // LOGIKA BARU: RECYCLING ID (ID TERKECIL)

        // 1. Cari Grup 'OPEN' dengan ID Terkecil
        $openGroup = Group::where('product_variant_id', $variant->id)
            ->where('status', 'open')
            ->where('expired_at', '>', now()) // Pastikan belum expired secara waktu
            ->orderBy('id', 'asc') // PENTING: Ambil ID terkecil
            ->lockForUpdate()
            ->first();

        // Cek kapasitas grup open tersebut
        if ($openGroup) {
            $currentMembers = $openGroup->orders()
                ->whereIn('status', ['paid', 'processing', 'completed', 'pending'])
                ->count();

            // Jika masih muat, pakai grup ini
            if ($currentMembers < ($variant->total_slots ?? 5)) {
                return $openGroup;
            } else {
                // Jika ternyata sudah penuh, tandai 'full' dan lanjut cari yang lain
                $openGroup->update(['status' => 'full']);
            }
        }

        // 2. Jika tidak ada yang Open, Cari Grup 'EXPIRED' Lama untuk Didaur Ulang (Recycle)
        $expiredGroup = Group::where('product_variant_id', $variant->id)
            ->where('status', 'expired')
            ->orderBy('id', 'asc') // Ambil ID Expired terkecil
            ->lockForUpdate()
            ->first();

        if ($expiredGroup) {
            // BERSIHKAN & RESET GRUP LAMA
            $expiredGroup->update([
                'status' => 'open', // Buka kembali
                'name' => $variant->name . ' - ' . Str::random(5), // Rename biar fresh
                'account_email' => null, // Hapus data login lama
                'account_password' => null,
                'additional_info' => [], // Kosongkan catatan
                'expired_at' => now()->addHours($variant->group_timeout_hours ?? 24) // Reset waktu
            ]);

            // PENTING: Putuskan hubungan dengan order masa lalu
            // (Order lama biarkan, tapi group_id-nya dinull-kan atau biarkan statusnya completed/expired di history)
            // Di sini kita biarkan relasi lama tetap ada untuk history, tapi slot dihitung dari status order yang 'active'
            // Karena order lama statusnya pasti 'expired' atau 'completed' (sudah lewat),
            // maka saat dihitung $currentMembers nanti, order lama tidak akan terhitung sebagai member aktif.

            return $expiredGroup;
        }

        // 3. Jika tidak ada Open dan tidak ada Expired, Buat Grup Baru
        return Group::create([
            'name' => $variant->name . ' - ' . Str::random(5),
            'product_variant_id' => $variant->id,
            'status' => 'open',
            'max_members' => $variant->total_slots ?? 5,
            'expired_at' => now()->addHours($variant->group_timeout_hours ?? 24)
        ]);
    }

    private function checkGroupFull($group, $variant)
    {
        if (!$group) return;
        $currentMembers = $group->orders()->whereIn('status', ['paid', 'processing', 'completed'])->count();
        if ($currentMembers >= ($variant->total_slots ?? 5)) $group->update(['status' => 'full']);
    }

    public function showTopUpForm()
    {
        return view('topup');
    }
}
