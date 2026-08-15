<?php

declare(strict_types=1);

use Illuminate\Bus\UniqueLock;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\Billing\Core\Enums\CreditOrderStatusEnum;
use TresPontosTech\Billing\Core\Events\Credit\OrderCreditPurchased;
use TresPontosTech\Billing\Core\Jobs\ProcessCreditPurchaseJob;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;

/**
 * Test subclass that breaks idempotency: returns a random uniqueId on every
 * dispatch so ShouldBeUnique never detects a duplicate.
 */
class ProcessCreditPurchaseJobWithoutIdempotency extends ProcessCreditPurchaseJob
{
    public function uniqueId(): string
    {
        return uniqid(more_entropy: true);
    }
}

function makeCreditOrder(int $quantity = 5, ?Company $company = null): CreditOrder
{
    $company ??= Company::factory()->create();

    return CreditOrder::factory()
        ->forCompany($company)
        ->create(['quantity' => $quantity]);
}

function makeCreditEvent(?CreditOrder $order = null): OrderCreditPurchased
{
    return new OrderCreditPurchased(creditOrderId: ($order ?? makeCreditOrder())->getKey());
}

// --- vulnerability tests (documents the bug without the protection) ---

it('without idempotency: duplicate webhooks queue 2 jobs (vulnerability)', function (): void {
    Queue::fake();

    $event = makeCreditEvent();

    Concurrency::driver('sync')->run([
        fn (): PendingDispatch => dispatch(new ProcessCreditPurchaseJobWithoutIdempotency($event)),
        fn (): PendingDispatch => dispatch(new ProcessCreditPurchaseJobWithoutIdempotency($event)),
    ]);

    Queue::assertPushed(ProcessCreditPurchaseJobWithoutIdempotency::class, 2);
});

// --- protection tests (ShouldBeUnique + uniqueId) ---

it('dispatches the job normally for a new order', function (): void {
    Queue::fake();

    dispatch(new ProcessCreditPurchaseJob(makeCreditEvent()));

    Queue::assertPushed(ProcessCreditPurchaseJob::class, 1);
});

it('does not dispatch a duplicate job for the same order', function (): void {
    Queue::fake();

    $event = makeCreditEvent();

    dispatch(new ProcessCreditPurchaseJob($event));
    dispatch(new ProcessCreditPurchaseJob($event));

    Queue::assertPushed(ProcessCreditPurchaseJob::class, 1);
});

it('dispatches distinct jobs for different orders', function (): void {
    Queue::fake();

    dispatch(new ProcessCreditPurchaseJob(makeCreditEvent()));
    dispatch(new ProcessCreditPurchaseJob(makeCreditEvent()));

    Queue::assertPushed(ProcessCreditPurchaseJob::class, 2);
});

it('with ShouldBeUnique: concurrent webhooks for the same order queue only 1 job', function (): void {
    Queue::fake();

    $event = makeCreditEvent();

    Concurrency::driver('sync')->run([
        fn (): PendingDispatch => dispatch(new ProcessCreditPurchaseJob($event)),
        fn (): PendingDispatch => dispatch(new ProcessCreditPurchaseJob($event)),
    ]);

    Queue::assertPushed(ProcessCreditPurchaseJob::class, 1);
});

it('issues the credits and settles the order', function (): void {
    $company = Company::factory()->create();
    $order = makeCreditOrder(3, $company);

    dispatch_sync(new ProcessCreditPurchaseJob(makeCreditEvent($order)));

    expect(UserCredit::query()->count())->toBe(3)
        ->and($order->refresh()->status)->toBe(CreditOrderStatusEnum::Paid)
        ->and($order->paid_at)->not->toBeNull();
});

it('does not issue credits twice even when dispatchSync bypasses ShouldBeUnique', function (): void {
    $order = makeCreditOrder(3);
    $event = makeCreditEvent($order);

    // dispatchSync bypasses ShouldBeUnique entirely: the settled order is what
    // stops the second run.
    dispatch_sync(new ProcessCreditPurchaseJob($event));
    dispatch_sync(new ProcessCreditPurchaseJob($event));

    expect(UserCredit::query()->count())->toBe(3);
});

it('with ShouldBeUnique: a duplicate dispatch while job is pending creates no extra credits', function (): void {
    $order = makeCreditOrder(3);
    $event = makeCreditEvent($order);

    $job = new ProcessCreditPurchaseJob($event);

    dispatch_sync(new ProcessCreditPurchaseJob($event));
    expect(UserCredit::query()->count())->toBe(3);

    Cache::lock(UniqueLock::getKey($job), 3600)->get();

    dispatch($job);

    expect(UserCredit::query()->count())->toBe(3);
});

it('ignores an event pointing at an order that no longer exists', function (): void {
    dispatch_sync(new ProcessCreditPurchaseJob(new OrderCreditPurchased(creditOrderId: '00000000-0000-0000-0000-000000000000')));

    expect(UserCredit::query()->count())->toBe(0);
});
