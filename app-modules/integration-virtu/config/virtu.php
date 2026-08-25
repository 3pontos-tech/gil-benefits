<?php

declare(strict_types=1);

return [
    // Defaults point at SANDBOX on purpose: a missing env must never silently
    // charge a real card. Production is opted into explicitly via .env.
    'base_url' => env('VIRTU_BASE_URL', 'https://sandbox-virtu-api.pagaa.com.br/api/v1/public'),

    'api_key' => env('VIRTU_API_KEY'),
    'company_id' => env('VIRTU_COMPANY_ID'),

    // Hex string of 64 chars, shown once when the webhook is registered in the
    // panel (Integrações → Webhooks). Rotating it there invalidates the old one
    // immediately, so deploy the new value right after confirming.
    'webhook_secret' => env('VIRTU_WEBHOOK_SECRET'),

    // Host that serves the hosted checkout. Only used to build pre-populated
    // URLs; the link's own `url` field already points here.
    'checkout_url' => env('VIRTU_CHECKOUT_URL', 'https://checkout.pagaa.com.br'),

    // Subscriptions accept credit card only — the API rejects PIX/BOLETO for
    // kind=SUBSCRIPTION. One-off orders (credits) may use both.
    'subscription_methods' => ['CREDIT_CARD'],
    'order_methods' => ['PIX', 'CREDIT_CARD'],

    // AUTO_TRANSFER passes card interest to the buyer, NO_INTEREST absorbs it,
    // CUSTOM requires an interestPlanId and the canUseCustomInterest capability.
    'interest_mode' => env('VIRTU_INTEREST_MODE', 'AUTO_TRANSFER'),
];
