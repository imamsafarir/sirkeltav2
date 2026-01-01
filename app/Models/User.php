<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = []; // Buka kunci pengaman

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

    protected static function booted()
    {
        static::created(function ($user) {
            // Setelah user jadi, buatkan dompet kosong
            $user->wallet()->create(['balance' => 0]);

            // Update user dengan kode referral unik (Contoh: SIRK-A1B2)
            $user->update([
                'referral_code' => 'SIRK-' . strtoupper(Str::random(5))
            ]);
        });
    }

    // Pastikan relasi ke Wallet ada
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    // Fungsi untuk Top Up / Terima Bonus
    public function deposit($amount, $description = 'Top Up')
    {
        // 1. Tambah saldo
        $this->wallet->increment('balance', $amount);

        // 2. Catat di buku transaksi
        $this->wallet->transactions()->create([
            'amount' => $amount,
            'type' => 'deposit',
            'balance_before' => $this->wallet->balance - $amount,
            'balance_after' => $this->wallet->balance,
            'description' => $description
        ]);
    }

    // Fungsi untuk Bayar
    public function withdraw($amount, $description = 'Payment')
    {
        // Cek dulu uangnya cukup gak?
        if ($this->wallet->balance < $amount) {
            throw new \Exception("Saldo tidak mencukupi!");
        }

        // 1. Kurangi saldo
        $this->wallet->decrement('balance', $amount);

        // 2. Catat di buku transaksi (amount negatif)
        $this->wallet->transactions()->create([
            'amount' => -$amount,
            'type' => 'payment',
            'balance_before' => $this->wallet->balance + $amount,
            'balance_after' => $this->wallet->balance,
            'description' => $description
        ]);
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }
}
