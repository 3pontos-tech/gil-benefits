<?php

declare(strict_types=1);

use TresPontosTech\IntegrationStripe\StripeApi;
use TresPontosTech\IntegrationStripe\Testing\FakeStripeApi;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    $this->api = new FakeStripeApi;
    $this->app->instance(StripeApi::class, $this->api);
});

it('registers the endpoint with exactly the events the handler translates', function (): void {
    artisan('stripe:webhook:create', ['url' => 'https://app.test/webhooks/stripe'])
        ->expectsOutputToContain('STRIPE_WEBHOOK_SECRET=whsec_fake')
        ->assertSuccessful();

    expect($this->api->webhookEndpoints)->toBe([[
        'url' => 'https://app.test/webhooks/stripe',
        'events' => [
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
        ],
    ]]);
});

it('refuses a url Stripe cannot deliver to', function (): void {
    artisan('stripe:webhook:create', ['url' => 'http://localhost:8000/webhooks/stripe'])->assertFailed();

    expect($this->api->webhookEndpoints)->toBe([]);
});

it('prints the handled events for the Stripe CLI', function (): void {
    artisan('stripe:webhook:create', ['--list' => true])
        ->expectsOutput('customer.subscription.created,customer.subscription.updated,customer.subscription.deleted,checkout.session.completed,checkout.session.async_payment_succeeded')
        ->assertSuccessful();

    expect($this->api->webhookEndpoints)->toBe([]);
});
