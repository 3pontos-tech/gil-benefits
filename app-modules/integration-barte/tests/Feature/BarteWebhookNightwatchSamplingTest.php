<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Laravel\Nightwatch\Facades\Nightwatch;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    config(['services.barte.webhook_secret' => 'barte-secret']);

    Queue::fake();
    Nightwatch::dontSample();
});

it('always sends the payment notice to Nightwatch, along with the jobs it dispatches', function (): void {
    postJson(route('webhooks.barte', ['token' => 'barte-secret']), [
        'uuid' => 'sub_1',
        'domain' => 'SUBSCRIPTION',
        'status' => 'ACTIVE',
    ])->assertOk();

    expect(Nightwatch::sampling())->toBeTrue()
        ->and(context()->getHidden('nightwatch_should_sample'))->toBeTrue();
});

it('sends the payment notice to Nightwatch even when its token is rejected', function (): void {
    postJson(route('webhooks.barte', ['token' => 'forged']), [
        'uuid' => 'sub_1',
        'domain' => 'SUBSCRIPTION',
        'status' => 'ACTIVE',
    ])->assertUnauthorized();

    expect(Nightwatch::sampling())->toBeTrue();
});
