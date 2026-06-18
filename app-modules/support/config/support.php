<?php

declare(strict_types=1);

return [
    'emails' => [
        'support_ti' => env('SUPPORT_EMAIL_SUPPORT_TI', 'ti@flamma.com.br'),
        'financial' => env('SUPPORT_EMAIL_FINANCIAL', 'financeiro@flamma.com.br'),
        'commercial' => env('SUPPORT_EMAIL_COMMERCIAL', 'comercial@flamma.com.br'),
        'cs' => env('SUPPORT_EMAIL_CS', 'cs@flamma.com.br'),
        'product' => env('SUPPORT_EMAIL_PRODUCT', 'produto@flamma.com.br'),
    ],
];
