<?php

return [
    'service_account_credentials' => env('GOOGLE_SERVICE_ACCOUNT_CREDENTIALS'),
    'sync_days_ahead' => (int) env('GOOGLE_CALENDAR_SYNC_DAYS_AHEAD', 60),
];
