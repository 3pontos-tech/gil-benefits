<?php

use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Models\BillingCustomer;

function fakeBillingCustomer(): BillingCustomer
{
    $customer = new BillingCustomer;
    $customer->billable_type = 'user';
    $customer->billable_id = 1;

    return $customer;
}

it('uses the provided price id as the external plan id (e.g. flamma standalone-user price)', function (): void {
    $dto = SubscriptionDTO::make(
        fakeBillingCustomer(),
        'sub_uuid',
        'active',
        'plan_uuid',
        null,
        1,
        priceId: 'plan_uuid-standalone-user',
    );

    expect($dto->planExternalId)->toBe('plan_uuid-standalone-user');
});

it('falls back to the plan uuid when no price id is provided', function (): void {
    $dto = SubscriptionDTO::make(
        fakeBillingCustomer(),
        'sub_uuid',
        'active',
        'plan_uuid',
        null,
        1,
    );

    expect($dto->planExternalId)->toBe('plan_uuid');
});

it('prefers the price id over the plan uuid + cycle composition', function (): void {
    $dto = SubscriptionDTO::make(
        fakeBillingCustomer(),
        'sub_uuid',
        'active',
        'plan_uuid',
        'MONTHLY',
        1,
        priceId: 'plan_uuid-standalone-user',
    );

    expect($dto->planExternalId)->toBe('plan_uuid-standalone-user');
});
