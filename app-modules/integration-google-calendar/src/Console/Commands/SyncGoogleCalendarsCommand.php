<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Console\Commands;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Console\Command;
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
