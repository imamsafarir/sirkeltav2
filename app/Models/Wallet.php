<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    //
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // ----------------------------
    // Di app/Models/Wallet.php
    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
