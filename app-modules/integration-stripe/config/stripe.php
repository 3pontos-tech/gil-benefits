<?php

declare(strict_types=1);

return [
    'secret' => env('STRIPE_SECRET'),

    // Signing secret of the webhook endpoint (whsec_…), shown in the Stripe dashboard.
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'webhook_tolerance' => (int) env('STRIPE_WEBHOOK_TOLERANCE', 300),

    'currency' => 'brl',

    // Customer portal configurations (bpc_…): invoices, card, billing details and
    // cancellation at period end; no plan switching, no pause.
    'portal_configurations' => [
        'company' => env('STRIPE_COMPANY_PORTAL_CONFIGURATION'),
        'user' => env('STRIPE_USER_PORTAL_CONFIGURATION'),
    ],

    // Pix refuses a single payment above the equivalent of US$ 3,000, so a checkout above
    // this amount offers card only.
    'pix_max_amount_cents' => (int) env('STRIPE_PIX_MAX_AMOUNT_CENTS', 1_500_000),
];
