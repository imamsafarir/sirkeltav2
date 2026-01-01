<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'product_id',
        'name',
        'price',
        'total_slots',
        'duration_days', // <--- Pastikan ada
        'group_timeout_hours',
        'is_active',     // <--- Pastikan ada
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }
}
