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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Contoh: HEMAT100
            $table->integer('discount_amount'); // Contoh: 5000
            $table->string('type')->default('fixed'); // fixed (rupiah) atau percent (%)

            $table->integer('usage_limit')->default(100); // Batas pakai
            $table->integer('used_count')->default(0); // Sudah dipakai berapa kali

            $table->boolean('is_active')->default(true);
            $table->dateTime('expired_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
