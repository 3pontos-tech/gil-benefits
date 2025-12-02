<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule monitoring tasks
Schedule::command('monitoring:collect-metrics --alert')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('monitor:health')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('monitoring:analyze-performance --hours=1 --recommendations')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
