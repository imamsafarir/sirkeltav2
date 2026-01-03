<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasOne; // Tambahkan ini biar VS Code senang

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi: User ini diajak oleh siapa?
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    // Otomatis buat Wallet saat Register
    protected static function booted()
    {
        static::created(function ($user) {
            // 1. Buatkan dompet kosong
            $user->wallet()->create([
                'balance' => 0,
                'status' => 'active'
            ]);

            // 2. Generate kode referral
            $user->update([
                'referral_code' => 'SIRK-' . strtoupper(Str::random(5))
            ]);
        });
    }

    // Relasi ke Wallet
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * FUNGSI DEPOSIT / TOP UP (Disederhanakan)
     * Kita hapus pencatatan transaction karena sudah dicatat di tabel 'orders'
     */
    public function deposit($amount)
    {
        $this->wallet->increment('balance', $amount);
    }

    /**
     * FUNGSI WITHDRAW / BAYAR (Disederhanakan)
     * Kita hapus pencatatan transaction karena sudah dicatat di tabel 'orders'
     */
    public function withdraw($amount)
    {
        // Cek dulu uangnya cukup gak?
        if ($this->wallet->balance < $amount) {
            throw new \Exception("Saldo tidak mencukupi!");
        }

        // Kurangi saldo
        $this->wallet->decrement('balance', $amount);
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }
}
