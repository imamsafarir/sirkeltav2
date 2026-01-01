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
        Schema::table('groups', function (Blueprint $table) {
            $table->string('account_email')->nullable();
            $table->string('account_password')->nullable();
            $table->text('additional_info')->nullable(); // Misal: "Pakai Profil 1 ya"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['account_email', 'account_password', 'additional_info']);
        });
    }
};
