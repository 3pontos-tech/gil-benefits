<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use TresPontosTech\Appointments\Jobs\MarkAppointmentsAsCompleted;
use TresPontosTech\Vouchers\Jobs\ExpireProgramCreditsJob;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new MarkAppointmentsAsCompleted)
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::job(new ExpireProgramCreditsJob)
    ->dailyAt('03:00')
    ->withoutOverlapping();
