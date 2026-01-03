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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Kolom untuk menyimpan status pembayaran topup
            $table->string('status')->default('completed')->after('amount'); // pending, paid, failed
            // Kolom untuk menyimpan token midtrans
            $table->string('payment_url')->nullable()->after('status');
            // Kolom Invoice ID khusus Topup
            $table->string('external_id')->nullable()->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            //
        });
    }
};
