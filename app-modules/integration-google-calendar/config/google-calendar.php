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

    /*
     * In-process retries for network-level failures (DNS, TLS handshake and read timeouts) on the
     * requests that are safe to repeat. A transient blip is healed inside the same job attempt, so
     * it never reaches the queue's retry/report path. HTTP error responses are never retried here.
     */
    'connection_retry_times' => (int) env('GOOGLE_CALENDAR_CONNECTION_RETRY_TIMES', 3) ?: 3,
    'connection_retry_delay_ms' => (int) env('GOOGLE_CALENDAR_CONNECTION_RETRY_DELAY_MS', 500) ?: 500,
];
