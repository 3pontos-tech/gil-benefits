<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Token do aplicativo
    |--------------------------------------------------------------------------
    |
    | Validade, em dias, do token Sanctum emitido no login. Não há renovação:
    | ao expirar, o app pede login de novo. Os expirados saem no
    | `sanctum:prune-expired` diário.
    |
    */

    'token_ttl_days' => (int) env('API_TOKEN_TTL_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Meta de encontros por trimestre civil
    |--------------------------------------------------------------------------
    */

    'quarter_goal' => 4,

    /*
    |--------------------------------------------------------------------------
    | URLs temporárias
    |--------------------------------------------------------------------------
    |
    | Validade, em minutos, das URLs de avatar, logo e arquivos de materiais.
    |
    */

    'media_url_ttl_minutes' => 60,

    /*
    |--------------------------------------------------------------------------
    | Cache dos horários livres
    |--------------------------------------------------------------------------
    */

    'slots_cache_seconds' => 60,

    /*
    |--------------------------------------------------------------------------
    | Limites de requisição
    |--------------------------------------------------------------------------
    |
    | `employee` vale por usuário autenticado (IP quando anônimo); `login` vale
    | por e-mail + IP, alinhado ao rateLimit(5) do login Filament.
    |
    */

    'rate_limits' => [
        'employee_per_minute' => 120,
        'login_per_minute' => 5,
    ],
];
