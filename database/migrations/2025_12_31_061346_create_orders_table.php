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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('product'); // 'product' atau 'topup'
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('group_id')->nullable()->constrained('groups');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants');
            $table->string('invoice_number')->unique(); // INV-2024...
            $table->integer('amount'); // Nominal bayar
            $table->string('status')->default('pending');
            $table->string('description')->nullable(); // pending, paid, completed, refunded
            $table->string('snap_token')->nullable(); // Token untuk popup Snap
            $table->string('payment_method')->nullable(); // bank_transfer, gopay, dll
            // Data Akun Sharing (Email/Pass) yang diisi admin nanti
            $table->text('account_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
