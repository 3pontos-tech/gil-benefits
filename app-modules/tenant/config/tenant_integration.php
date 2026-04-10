<?php

declare(strict_types=1);

return [
    'header' => 'X-Flamma-Access-Key',
    'key_length' => 64, // tamanho de uma uuid
    'log_requests' => env('FLAMMA_TENANT_API_REQUESTS_LOG', false),
];
