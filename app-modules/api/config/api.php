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
    | Recuperação de senha pelo app
    |--------------------------------------------------------------------------
    |
    | Código de 6 dígitos por e-mail, trocado por um token que só serve para
    | definir a senha nova (#323). Os erros da conta não zeram com um código
    | novo, só com um reset: com 20, a chance de adivinhar fica em 20 em
    | 1.000.000 no total. O `timebox_ms` iguala o tempo de resposta de quem
    | tem conta e de quem não tem.
    |
    */

    'password_reset' => [
        'code_ttl_minutes' => 15,
        'max_attempts_per_code' => 5,
        'max_failed_attempts_per_account' => 20,
        'resend_cooldown_seconds' => 60,
        'reset_token_ttl_minutes' => 15,
        'timebox_ms' => 400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Limites de requisição
    |--------------------------------------------------------------------------
    |
    | `employee` vale por usuário autenticado. O login tem dois tetos: por
    | e-mail + IP, alinhado ao rateLimit(5) do login Filament, e por IP, mais
    | alto, contra quem testa uma senha em muitos e-mails. O pedido de código
    | segue a mesma ideia numa janela de 15 minutos; conferir o código e
    | definir a senha dividem um teto por IP.
    |
    */

    'rate_limits' => [
        'employee_per_minute' => 120,
        'login_per_minute' => 5,
        'login_per_minute_per_ip' => 20,
        'password_forgot_per_15_minutes' => 3,
        'password_forgot_per_hour_per_ip' => 20,
        'password_verify_per_minute_per_ip' => 10,
    ],
];
