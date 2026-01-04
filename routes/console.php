<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\ProcessExpiredGroups;


// Jalankan pengecekan grup expired setiap menit
Schedule::command(ProcessExpiredGroups::class)->everyMinute();

// Tips Pemula:
// Nanti di server asli, kamu harus setup Cron Job agar script ini jalan.
// Tapi di localhost, kamu bisa tes manual dengan perintah: php artisan schedule:work
