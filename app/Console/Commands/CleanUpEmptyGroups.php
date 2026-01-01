<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanUpEmptyGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-up-empty-groups';

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
        // Cari grup yang sudah Expired DAN masih Open
        $expiredGroups = \App\Models\Group::where('status', 'open')
            ->where('expired_at', '<', now())
            ->get();

        foreach ($expiredGroups as $group) {
            // Cek apakah ada order sukses di dalamnya?
            $hasPaidOrders = $group->orders()
                ->whereIn('status', ['paid', 'completed'])
                ->exists();

            if (!$hasPaidOrders) {
                // Jika expired & tidak ada yang bayar sama sekali -> HAPUS
                $group->delete();
                $this->info("Grup ID {$group->id} dihapus karena expired dan kosong.");
            } else {
                // Jika expired tapi ada uang masuk -> JANGAN HAPUS (Bahaya), tapi tutup saja
                $group->update(['status' => 'closed']);
            }
        }
    }
}
