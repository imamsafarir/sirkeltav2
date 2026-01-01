<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\CheckExpiredGroups;

// Jalankan perintah cek expired setiap menit
Schedule::command(CheckExpiredGroups::class)->everyMinute();

// Tips Pemula:
// Nanti di server asli, kamu harus setup Cron Job agar script ini jalan.
// Tapi di localhost, kamu bisa tes manual dengan perintah: php artisan schedule:work
