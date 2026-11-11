<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use TresPontosTech\Api\Models\PasswordResetCode;
use TresPontosTech\Appointments\Jobs\MarkAppointmentsAsCompleted;
use TresPontosTech\Appointments\Jobs\SendAppointmentRemindersJob;
use TresPontosTech\Credits\Jobs\WarnExpiringCreditsJob;
use TresPontosTech\Vouchers\Jobs\ExpireVoucherCreditsJob;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new MarkAppointmentsAsCompleted)
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::job(new SendAppointmentRemindersJob, (string) config('api.push.queue'))
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::job(new WarnExpiringCreditsJob, (string) config('api.push.queue'))
    ->hourlyAt(7)
    ->between('09:00', '20:00')
    ->withoutOverlapping();

Schedule::job(new ExpireVoucherCreditsJob)
    ->dailyAt('03:00')
    ->withoutOverlapping();

Schedule::command('sanctum:prune-expired --hours=24')
    ->daily()
    ->withoutOverlapping();

Schedule::command('model:prune', ['--model' => [PasswordResetCode::class]])
    ->daily()
    ->withoutOverlapping();
