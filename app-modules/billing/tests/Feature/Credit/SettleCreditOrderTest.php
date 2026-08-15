<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use TresPontosTech\Billing\Core\Actions\Credit\SettleCreditOrder;
use TresPontosTech\Billing\Core\Enums\CreditOrderStatusEnum;
use TresPontosTech\Billing\Core\Events\Credit\CreditsDelivered;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;

function settle(CreditOrder $order): void
{
    resolve(SettleCreditOrder::class)->handle($order->getKey());
}

it('issues the credits and settles the order', function (): void {
    $order = CreditOrder::factory()->forCompany(Company::factory()->create())->create(['quantity' => 3]);

    settle($order);

    expect(UserCredit::query()->count())->toBe(3)
        ->and($order->refresh()->status)->toBe(CreditOrderStatusEnum::Paid)
        ->and($order->paid_at)->not->toBeNull();
});

it('credits the company owner when the buyer is a company', function (): void {
    $company = Company::factory()->create();
    $order = CreditOrder::factory()->forCompany($company)->create(['quantity' => 2]);

    settle($order);

    expect(UserCredit::query()->pluck('owner_id')->unique()->all())->toBe([$company->user_id]);
});

it('does not issue credits twice for an order already settled', function (): void {
    $order = CreditOrder::factory()->forCompany(Company::factory()->create())->create(['quantity' => 3]);

    settle($order);
    settle($order);

    expect(UserCredit::query()->count())->toBe(3);
});

it('announces the delivery', function (): void {
    Event::fake([CreditsDelivered::class]);

    $company = Company::factory()->create();
    $order = CreditOrder::factory()->forCompany($company)->create(['quantity' => 4]);

    settle($order);

    Event::assertDispatched(
        CreditsDelivered::class,
        fn (CreditsDelivered $event): bool => $event->quantity === 4 && $event->ownerId === (string) $company->user_id
    );
});

it('ignores an order that no longer exists', function (): void {
    resolve(SettleCreditOrder::class)->handle('00000000-0000-0000-0000-000000000000');

    expect(UserCredit::query()->count())->toBe(0);
});
