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
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('group_id')->constrained('groups');

            // INI YANG TADI HILANG DAN BIKIN ERROR:
            $table->foreignId('product_variant_id')->constrained('product_variants');

            $table->string('invoice_number')->unique(); // INV-2024...
            $table->integer('amount'); // Nominal bayar
            $table->string('status')->default('pending'); // pending, paid, completed, refunded

            // Data Xendit
            $table->string('payment_url')->nullable();
            $table->string('xendit_external_id')->nullable();

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
