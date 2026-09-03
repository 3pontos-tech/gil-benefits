<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Actions\SettleCreditOrder;
use TresPontosTech\Credits\Enums\CreditOrderStatusEnum;
use TresPontosTech\Credits\Events\CreditsDelivered;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\Credits\Models\UserCredit;

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

it('links every issued credit back to the order that paid for it', function (): void {
    $order = CreditOrder::factory()->forCompany(Company::factory()->create())->create(['quantity' => 3]);

    settle($order);

    expect($order->credits()->count())->toBe(3)
        ->and(UserCredit::query()->whereNull('credit_order_id')->count())->toBe(0)
        ->and(UserCredit::query()->first()->creditOrder->is($order))->toBeTrue();
});

it('leaves credit_order_id null on credits that did not come from a purchase', function (): void {
    // Crédito de grant e o que já existe em produção nunca terão pedido.
    $credit = UserCredit::factory()->create();

    expect($credit->credit_order_id)->toBeNull()
        ->and($credit->creditOrder)->toBeNull();
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

it('keeps the order pending and retryable when settling fails', function (): void {
    $company = Company::factory()->create();

    $order = CreditOrder::factory()->create([
        'billable_type' => $company->getMorphClass(),
        'billable_id' => '00000000-0000-0000-0000-000000000000',
        'company_id' => $company->getKey(),
        'quantity' => 3,
    ]);

    expect(fn () => settle($order))->toThrow(ModelNotFoundException::class);

    expect(UserCredit::query()->count())->toBe(0)
        ->and($order->refresh()->status)->toBe(CreditOrderStatusEnum::Pending)
        ->and($order->paid_at)->toBeNull();

    $order->update(['billable_id' => $company->getKey()]);

    settle($order);

    expect(UserCredit::query()->count())->toBe(3)
        ->and($order->refresh()->status)->toBe(CreditOrderStatusEnum::Paid);
});

it('ignores an order that no longer exists', function (): void {
    resolve(SettleCreditOrder::class)->handle('00000000-0000-0000-0000-000000000000');

    expect(UserCredit::query()->count())->toBe(0);
});
