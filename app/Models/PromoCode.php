<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'expired_at' => 'datetime',
    ];
}
