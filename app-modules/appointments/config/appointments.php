<?php

declare(strict_types=1);

return [
    'ai' => [
        'primary' => [
            'provider' => env('APPOINTMENTS_AI_PRIMARY_PROVIDER', 'gemini'),
            'model' => env('APPOINTMENTS_AI_PRIMARY_MODEL', 'gemini-3.1-flash-lite-preview'),
        ],
        'fallback' => [
            'provider' => env('APPOINTMENTS_AI_FALLBACK_PROVIDER', 'gemini'),
            'model' => env('APPOINTMENTS_AI_FALLBACK_MODEL', 'gemini-3-flash-preview'),
        ],
        'circuit_cooldown_minutes' => 3,
        'timeout' => 70,
        'connect_timeout' => 10,
    ],
];
