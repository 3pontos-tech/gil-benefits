<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationGoogleCalendar\Console\Commands;

use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Throwable;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\SyncConsultantCalendarJob;
use TresPontosTech\IntegrationGoogleCalendar\Support\LogSanitizer;

class SyncGoogleCalendarsCommand extends Command
{
    protected $signature = 'google-calendar:sync
                            {--full : Force a full sync for every consultant, ignoring the incremental sync token}';

    protected $description = 'Sync Google Calendar events as blocked schedules for all consultants';

    public function handle(): void
    {
        $startedAt = Date::now();
        $forceFullSync = (bool) $this->option('full');
        $dispatched = 0;
        $awaitingFullSync = 0;
        $failed = 0;

        foreach ($this->syncableConsultants()->cursor() as $consultant) {
            if ($this->isDueForFullSync($consultant, $startedAt)) {
                ++$awaitingFullSync;
            }

            try {
                dispatch(new SyncConsultantCalendarJob($consultant, $forceFullSync));
                ++$dispatched;
            } catch (Throwable $throwable) {
                ++$failed;

                Log::error('Google Calendar sync dispatch failed for consultant', [
                    'consultant_id' => $consultant->id,
                    'reason' => LogSanitizer::sanitize($throwable->getMessage()),
                    'failed_at' => Date::now()->toIso8601String(),
                ]);
            }
        }

        Log::info('Google Calendar sync dispatched', [
            'forced_full_sync' => $forceFullSync,
            'consultants_dispatched' => $dispatched,
            'consultants_awaiting_full_sync' => $awaitingFullSync,
            'consultants_failed' => $failed,
            'started_at' => $startedAt->toIso8601String(),
        ]);

        $this->components->info(sprintf(
            'Sync dispatched for %d consultant(s): %d due for a full sync, %d failed to dispatch.',
            $dispatched,
            $awaitingFullSync,
            $failed,
        ));
    }

    /**
     * Consultants eligible for sync, the ones longest without a full sync first, so
     * that whoever is past the full sync interval is queued ahead of the rest.
     *
     * The explicit "IS NULL DESC" is required, not cosmetic: Postgres sorts NULLs
     * last on ASC, which would push never-synced consultants to the back of the
     * queue. Do not collapse it into a plain orderBy - the test suite runs on
     * sqlite, where NULLs sort first, so a regression here would pass CI and only
     * show up in production.
     *
     * @return Builder<Consultant>
     */
    private function syncableConsultants(): Builder
    {
        return Consultant::query()
            ->whereNotNull('email')
            ->orderByRaw('last_full_sync_at IS NULL DESC')
            ->orderBy('last_full_sync_at');
    }

    /**
     * Mirrors SyncConsultantCalendarAction::shouldFullSync so the summary log
     * reports what the jobs will actually do.
     */
    private function isDueForFullSync(Consultant $consultant, CarbonInterface $reference): bool
    {
        return blank($consultant->last_full_sync_at)
            || $consultant->last_full_sync_at->lt(
                $reference->copy()->subHours(config()->integer('google-calendar.full_sync_interval_hours')),
            );
    }
}
