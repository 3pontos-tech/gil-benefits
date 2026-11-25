<?php

declare(strict_types=1);

return [
    'signing_secret' => env('CLAU_SIGNING_SECRET'),
    'timestamp_tolerance' => 300,
    'discount_slug' => env('CLAU_DISCOUNT_SLUG', 'clau-diagnostico'),
];
