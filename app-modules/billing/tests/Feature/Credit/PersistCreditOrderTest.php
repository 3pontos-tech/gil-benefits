<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldQueue;
use TresPontosTech\Billing\Core\DTOs\CreditOrderDTO;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CreditOrderStatusEnum;
use TresPontosTech\Billing\Core\Events\Credit\CreditOrderPlaced;
use TresPontosTech\Billing\Core\Listeners\Credit\PersistCreditOrder;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;

it('opens the order when a gateway announces the checkout', function (): void {
    $company = Company::factory()->create();

    event(new CreditOrderPlaced(new CreditOrderDTO(
        provider: BillingProviderEnum::Virtu,
        billable: $company,
        company: $company,
        quantity: 2,
        checkoutId: 'checkout_abc',
    )));

    $order = CreditOrder::query()->sole();

    expect($order->provider)->toBe(BillingProviderEnum::Virtu)
        ->and($order->billable_id)->toBe($company->getKey())
        ->and($order->quantity)->toBe(2)
        ->and($order->checkout_id)->toBe('checkout_abc')
        ->and($order->status)->toBe(CreditOrderStatusEnum::Pending);
});

it('prices the order from the credit itself, not from the gateway', function (): void {
    $company = Company::factory()->create();

    event(new CreditOrderPlaced(new CreditOrderDTO(
        provider: BillingProviderEnum::Virtu,
        billable: $company,
        company: $company,
        quantity: 3,
        checkoutId: 'checkout_abc',
    )));

    expect(CreditOrder::query()->sole()->amount_cents)->toBe(UserCredit::priceFor(3));
});

it('runs in the same request as the checkout', function (): void {
    expect(is_subclass_of(PersistCreditOrder::class, ShouldQueue::class))->toBeFalse();
});
