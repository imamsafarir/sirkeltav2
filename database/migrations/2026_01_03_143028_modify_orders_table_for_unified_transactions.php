<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        // 1. Hapus Tabel Wallet Transactions (Kita buang permanen)
        Schema::dropIfExists('wallet_transactions');

        // 2. Modifikasi Tabel Orders
        Schema::table('orders', function (Blueprint $table) {
            // Kolom baru untuk membedakan jenis transaksi
            // Enum: 'product' (Beli Netflix dll), 'topup' (Isi Saldo)
            $table->string('type')->default('product')->after('id');

            // Kolom deskripsi tambahan
            $table->string('description')->nullable()->after('status');

            // Jadikan nullable karena Top Up tidak butuh produk/group
            $table->foreignId('product_variant_id')->nullable()->change();
            $table->foreignId('group_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
