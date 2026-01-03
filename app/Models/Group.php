<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'status',
        'account_email',
        'account_password',
        'additional_info',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
        'additional_info' => 'array',
    ];

    // --- TAMBAHKAN KODE INI (OTOMATISASI STATUS) ---
    protected static function booted()
    {
        static::updated(function ($group) {
            // 1. Jika Admin ubah Grup jadi 'processing'
            // Ubah semua order 'paid' menjadi 'processing'
            if ($group->isDirty('status') && $group->status === 'processing') {
                $group->orders()
                    ->where('status', 'paid')
                    ->update(['status' => 'processing']);
            }

            // 2. Jika Admin ubah Grup jadi 'completed'
            // Ubah semua order yang 'processing'/'paid' menjadi 'completed'
            if ($group->isDirty('status') && $group->status === 'completed') {
                $group->orders()
                    ->whereIn('status', ['paid', 'processing'])
                    ->update(['status' => 'completed']);
            }

            // 3. Jika Admin ubah Grup jadi 'expired' / 'closed'
            // Tandai order jadi failed/canceled (Opsional)
            if ($group->isDirty('status') && in_array($group->status, ['expired', 'closed'])) {
                $group->orders()
                    ->whereIn('status', ['pending', 'paid'])
                    ->update(['status' => 'failed']);
            }
        });
    }
    // ------------------------------------------------

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // Alias agar tidak error jika kode lama memanggil 'variant'
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
