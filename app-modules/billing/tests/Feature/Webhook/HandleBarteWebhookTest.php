<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Billing\Barte\Actions\HandleBarteWebhook;
use TresPontosTech\Billing\Barte\DTOs\BarteWebhookDto;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Models\Plan;

// These tests cover the flamma fix: the Barte webhook must persist the exact price the
// buyer checked out with (carried in `barte_price_id`) so tenant-specific prices such as
// `<uuid>-standalone-user` are not collapsed onto the shared plan id.

function bartePayload(string $planUuid, array $extraMetadata = []): array
{
    $metadata = array_merge([
        'barte_plan_uuid' => $planUuid,
        'quantity' => '1',
    ], $extraMetadata);

    return [
        'uuid' => 'sub_' . uniqid(),
        'domain' => 'SUBSCRIPTION',
        'status' => 'ACTIVE',
        'uuidBuyer' => 'buyer-uuid',
        'metadata' => collect($metadata)
            ->map(fn ($value, $key): array => ['key' => $key, 'value' => $value])
            ->values()
            ->all(),
    ];
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->plan = Plan::factory()->barte()->active()->create(['provider_product_id' => 'plan-uuid']);

    BillingCustomer::factory()->create([
        'billable_type' => $this->user->getMorphClass(),
        'billable_id' => $this->user->getKey(),
        'provider' => BillingProviderEnum::Barte,
        'provider_customer_id' => 'buyer-uuid',
    ]);
});

it('persists the checked-out price id as the subscription external plan id', function (): void {
    Event::fake([SubscriptionActivated::class]);

    $payload = bartePayload('plan-uuid', ['barte_price_id' => 'plan-uuid-standalone-user']);

    resolve(HandleBarteWebhook::class)->handle(BarteWebhookDto::fromArray($payload));

    Event::assertDispatched(
        SubscriptionActivated::class,
        fn (SubscriptionActivated $event): bool => $event->dto->planExternalId === 'plan-uuid-standalone-user'
    );
});

it('falls back to the plan uuid when the webhook carries no price id', function (): void {
    Event::fake([SubscriptionActivated::class]);

    $payload = bartePayload('plan-uuid');

    resolve(HandleBarteWebhook::class)->handle(BarteWebhookDto::fromArray($payload));

    Event::assertDispatched(
        SubscriptionActivated::class,
        fn (SubscriptionActivated $event): bool => $event->dto->planExternalId === 'plan-uuid'
    );
});
