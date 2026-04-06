<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationGoogleCalendar\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\PendingDispatch;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\SyncConsultantCalendarJob;

class SyncGoogleCalendarsCommand extends Command
{
    protected $signature = 'google-calendar:sync';

    protected $description = 'Sync Google Calendar events as blocked schedules for all consultants';

    public function handle(): void
    {
        Consultant::query()->whereNotNull('email')
            ->each(fn ($consultant): PendingDispatch => dispatch(new SyncConsultantCalendarJob($consultant)));
    }
}
