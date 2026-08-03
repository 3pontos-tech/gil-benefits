<?php

declare(strict_types=1);

return [
    'service_account_credentials' => env('GOOGLE_SERVICE_ACCOUNT_CREDENTIALS'),
    'sync_days_ahead' => (int) env('GOOGLE_CALENDAR_SYNC_DAYS_AHEAD', 60),
    'default_event_duration' => (int) env('GOOGLE_CALENDAR_EVENT_DURATION_MINUTES', 60),

    /*
     * A consultant whose last successful full sync is older than this - or who never
     * had one - gets a forced full sync on the next pass of google-calendar:sync.
     */
    'full_sync_interval_hours' => (int) env('GOOGLE_CALENDAR_FULL_SYNC_INTERVAL_HOURS', 24) ?: 24,
];
