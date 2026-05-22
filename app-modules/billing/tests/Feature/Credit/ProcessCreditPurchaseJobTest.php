<?php

declare(strict_types=1);

use Illuminate\Bus\UniqueLock;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\Billing\Core\Events\Credit\OrderCreditPurchased;
use TresPontosTech\Billing\Core\Jobs\ProcessCreditPurchaseJob;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;

/**
 * Test subclass that breaks idempotency: returns a random uniqueId on every
 * dispatch so ShouldBeUnique never detects a duplicate.
 * Used only to document the vulnerability in tests.
 */
class ProcessCreditPurchaseJobWithoutIdempotency extends ProcessCreditPurchaseJob
{
    public function uniqueId(): string
    {
        return uniqid(more_entropy: true);
    }
}

function makeCreditEvent(string $orderUuid = 'order-uuid-test'): OrderCreditPurchased
{
    return new OrderCreditPurchased(
        orderUuid: $orderUuid,
        billableType: 'company',
        billableId: '1',
        companyId: '1',
        quantity: 5,
    );
}

// --- vulnerability tests (documents the bug without the protection) ---

it('without idempotency: duplicate webhooks queue 2 jobs (vulnerability)', function (): void {
    Queue::fake();

    $event = makeCreditEvent('order-uuid-no-protection');

    Concurrency::driver('sync')->run([
        fn (): PendingDispatch => dispatch(new ProcessCreditPurchaseJobWithoutIdempotency($event)),
        fn (): PendingDispatch => dispatch(new ProcessCreditPurchaseJobWithoutIdempotency($event)),
    ]);

    Queue::assertPushed(ProcessCreditPurchaseJobWithoutIdempotency::class, 2);
});

// --- protection tests (ShouldBeUnique + uniqueId) ---

it('dispatches the job normally for a new order', function (): void {
    Queue::fake();

    dispatch(new ProcessCreditPurchaseJob(makeCreditEvent('order-uuid-1')));

    Queue::assertPushed(ProcessCreditPurchaseJob::class, 1);
});

it('does not dispatch a duplicate job for the same order uuid', function (): void {
    Queue::fake();

    $event = makeCreditEvent('order-uuid-duplicate');

    dispatch(new ProcessCreditPurchaseJob($event));
    dispatch(new ProcessCreditPurchaseJob($event));

    Queue::assertPushed(ProcessCreditPurchaseJob::class, 1);
});

it('dispatches distinct jobs for different order uuids', function (): void {
    Queue::fake();

    dispatch(new ProcessCreditPurchaseJob(makeCreditEvent('order-uuid-A')));
    dispatch(new ProcessCreditPurchaseJob(makeCreditEvent('order-uuid-B')));

    Queue::assertPushed(ProcessCreditPurchaseJob::class, 2);
});

it('with ShouldBeUnique: concurrent webhooks with the same uuid queue only 1 job', function (): void {
    Queue::fake();

    $event = makeCreditEvent('order-uuid-concurrent');

    Concurrency::driver('sync')->run([
        fn (): PendingDispatch => dispatch(new ProcessCreditPurchaseJob($event)),
        fn (): PendingDispatch => dispatch(new ProcessCreditPurchaseJob($event)),
    ]);

    Queue::assertPushed(ProcessCreditPurchaseJob::class, 1);
});

it('without idempotency: dispatching twice creates double the credits in the database', function (): void {
    $company = Company::factory()->create();

    $event = new OrderCreditPurchased(
        orderUuid: 'order-uuid-db-vulnerable',
        billableType: 'company',
        billableId: (string) $company->getKey(),
        companyId: (string) $company->getKey(),
        quantity: 3,
    );

    // dispatchSync bypasses ShouldBeUnique entirely → no protection

    dispatch_sync(new ProcessCreditPurchaseJob($event));
    dispatch_sync(new ProcessCreditPurchaseJob($event));

    expect(UserCredit::query()->count())->toBe(6);
});

it('with ShouldBeUnique: a duplicate dispatch while job is pending creates no extra credits', function (): void {
    $company = Company::factory()->create();

    $event = new OrderCreditPurchased(
        orderUuid: 'order-uuid-db-protected',
        billableType: 'company',
        billableId: (string) $company->getKey(),
        companyId: (string) $company->getKey(),
        quantity: 3,
    );

    $job = new ProcessCreditPurchaseJob($event);

    dispatch_sync(new ProcessCreditPurchaseJob($event));
    expect(UserCredit::query()->count())->toBe(3);

    // Simulate the job still pending in queue (lock held by another process/request),
    // as if the first job hasn't finished processing yet in an async queue.

    Cache::lock(UniqueLock::getKey($job), 3600)->get();

    dispatch($job); // lock is held → duplicate is dropped by ShouldBeUnique

    expect(UserCredit::query()->count())->toBe(3); // no extra credits created
});
