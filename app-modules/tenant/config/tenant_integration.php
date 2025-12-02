<?php

return [
    'header' => 'X-Flamma-Access-Key',
    'key_length' => 64, // tamanho de uma uuid
    'log_requests' => config('FLAMMA_TENANT_API_REQUESTS_LOG', false),
];
