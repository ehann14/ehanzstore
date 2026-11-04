<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hapus data pengunjung lama setiap hari. Butuh cron `schedule:run`; bila hosting tanpa cron,
// middleware VisitorTracking menjalankan penghapusan yang sama sekali sehari.
Schedule::command('visitors:prune')->dailyAt('03:00')->withoutOverlapping();