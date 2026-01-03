<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'type',             // <--- BARU
        'group_id',
        'product_variant_id',
        'invoice_number',
        'amount',
        'status',
        'payment_url',
        'description',      // <--- BARU
    ];

    // Agar data akun (email/pass) otomatis terenkripsi saat disimpan
    protected $casts = [
        'account_data' => 'encrypted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
