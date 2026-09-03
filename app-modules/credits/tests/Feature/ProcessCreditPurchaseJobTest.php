<?php

declare(strict_types=1);

use Illuminate\Bus\UniqueLock;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Queue;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Events\OrderCreditPurchased;
use TresPontosTech\Credits\Jobs\ProcessCreditPurchaseJob;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\Credits\Models\UserCredit;

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

function makeCreditEvent(int $quantity = 5): OrderCreditPurchased
{
    $order = CreditOrder::factory()
        ->forCompany(Company::factory()->create())
        ->create(['quantity' => $quantity]);

    return new OrderCreditPurchased(creditOrderId: $order->getKey());
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

it('hands the order over to be settled', function (): void {
    dispatch_sync(new ProcessCreditPurchaseJob(makeCreditEvent(3)));

    expect(UserCredit::query()->count())->toBe(3);
});

it('with ShouldBeUnique: a duplicate dispatch while job is pending creates no extra credits', function (): void {
    $event = makeCreditEvent(3);

    $job = new ProcessCreditPurchaseJob($event);

    dispatch_sync(new ProcessCreditPurchaseJob($event));
    expect(UserCredit::query()->count())->toBe(3);

    Cache::lock(UniqueLock::getKey($job), 3600)->get();

    dispatch($job);

    expect(UserCredit::query()->count())->toBe(3);
});
