<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\CheckExpiredGroups;
use App\Console\Commands\CleanUpEmptyGroups;

// Jalankan perintah cek expired setiap menit
Schedule::command(CheckExpiredGroups::class)->everyMinute();

// Perintah ini akan jalan setiap hari pada tengah malam
Schedule::command(CleanUpEmptyGroups::class)->daily();

// Tips Pemula:
// Nanti di server asli, kamu harus setup Cron Job agar script ini jalan.
// Tapi di localhost, kamu bisa tes manual dengan perintah: php artisan schedule:work
