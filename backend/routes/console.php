<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily lifecycle jobs — SPEC §6.
Schedule::command('featured:expire')->dailyAt('00:05');
Schedule::command('featured:notify-expiring')->dailyAt('09:00');
Schedule::command('smart-requests:expire')->hourly();
