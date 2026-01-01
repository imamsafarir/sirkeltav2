<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckExpiredGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-expired-groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Cari grup yang statusnya OPEN tapi waktunya sudah HABIS
        $expiredGroups = \App\Models\Group::where('status', 'open')
            ->where('expired_at', '<', now())
            ->get();

        foreach ($expiredGroups as $group) {
            $this->info("Memproses Grup Expired ID: " . $group->id);

            // 1. Ubah status jadi expired
            $group->update(['status' => 'expired']);

            // 2. Cari order yang sudah terlanjur bayar (Paid)
            $orders = $group->orders()->where('status', 'paid')->get();

            foreach ($orders as $order) {
                // 3. Kembalikan saldo ke user (Refund)
                $user = $order->user;
                $user->deposit($order->amount, "Refund Grup Expired #" . $group->id);

                // 4. Ubah status order
                $order->update(['status' => 'refunded']);

                $this->info("Refund sukses ke user: " . $user->name);
            }
        }
    }
}
