<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'pin',    // Opsional (jika nanti pakai PIN)
        'status', // active/blocked
    ];

    /**
     * Relasi: Dompet milik siapa?
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // FUNGSI transactions() KITA HAPUS
    // Karena tabel wallet_transactions sudah tidak ada.
    // Semua riwayat sekarang ada di tabel 'orders'.
}
