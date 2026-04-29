<?php

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;

beforeEach(function (): void {
    Cashier::useCustomerModel(User::class);
});

afterEach(function (): void {
    Cashier::useCustomerModel(User::class);
});

/** @return class-string|null */
function currentCashierModel(): ?string
{
    return Cashier::$customerModel;
}

function webhookPayload(string $type = 'charge.succeeded', array $metadata = []): array
{
    $object = ['id' => 'obj_test_' . uniqid()];

    if ($metadata !== []) {
        $object['metadata'] = $metadata;
    }

    return [
        'id' => 'evt_test_' . uniqid(),
        'type' => $type,
        'data' => ['object' => $object],
    ];
}

it('switches customer model to Company when metadata.model is "company"', function (): void {
    $this->withoutMiddleware(VerifyWebhookSignature::class)
        ->postJson(route('cashier.webhook'), webhookPayload(metadata: ['model' => 'company']))
        ->assertSuccessful();

    expect(currentCashierModel())->toBe(Company::class);
});

it('does not change customer model when metadata key is absent from payload', function (): void {
    $this->withoutMiddleware(VerifyWebhookSignature::class)
        ->postJson(route('cashier.webhook'), webhookPayload())
        ->assertSuccessful();

    expect(currentCashierModel())->toBe(User::class);
});

it('does not change customer model when model key is absent from metadata', function (): void {
    $this->withoutMiddleware(VerifyWebhookSignature::class)
        ->postJson(route('cashier.webhook'), webhookPayload(metadata: ['other_key' => 'some_value']))
        ->assertSuccessful();

    expect(currentCashierModel())->toBe(User::class);
});

it('calls useCustomerModel with null when morph key is not registered', function (): void {
    $this->withoutMiddleware(VerifyWebhookSignature::class)
        ->postJson(route('cashier.webhook'), webhookPayload(metadata: ['model' => 'unregistered_morph']))
        ->assertSuccessful();

    expect(Relation::getMorphedModel('unregistered_morph'))->toBeNull()
        ->and(currentCashierModel())->toBeNull();
});

it('returns 403 when stripe signature is invalid', function (): void {
    config(['cashier.webhook.secret' => 'whsec_test_secret_key_for_validation']);

    $this->postJson(route('cashier.webhook'), webhookPayload(), [
        'Stripe-Signature' => 't=invalid,v1=invalidsignature',
    ])->assertForbidden();
});

it('processes the same event id twice without creating duplicate subscriptions', function (): void {
    $payload = webhookPayload(metadata: ['model' => 'company']);

    $this->withoutMiddleware(VerifyWebhookSignature::class)
        ->postJson(route('cashier.webhook'), $payload)
        ->assertSuccessful();

    $this->withoutMiddleware(VerifyWebhookSignature::class)
        ->postJson(route('cashier.webhook'), $payload)
        ->assertSuccessful();

    expect(Subscription::query()->count())->toBe(0);
});
