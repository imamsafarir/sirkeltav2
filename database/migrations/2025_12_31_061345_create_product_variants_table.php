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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('name');
            $table->integer('price');
            $table->integer('total_slots');

            // Saya beri 'default(30)' agar aman jika seeder lupa mengisi kolom ini
            $table->integer('duration_days')->default(30);
            $table->text('features')->nullable();

            $table->integer('group_timeout_hours')->default(24);

            // --- TAMBAHAN PENTING (Agar Seeder tidak error) ---
            $table->boolean('is_active')->default(true);
            // --------------------------------------------------

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
