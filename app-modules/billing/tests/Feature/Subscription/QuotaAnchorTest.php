<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Billing\Core\Actions\ResolveQuotaAllowance;
use TresPontosTech\Billing\Core\Actions\UpsertSubscription;
use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

use function Pest\Laravel\travelTo;

function subscriptionDtoFor(User $user, string $status): SubscriptionDTO
{
    return new SubscriptionDTO(
        billableType: 'user',
        billableId: $user->getKey(),
        subscriptionExternalId: 'barte_sub_anchor_test',
        status: $status,
        planExternalId: null,
        planSlug: 'default',
        quantity: 1,
        endsAt: null,
    );
}

it('does not anchor a subscription that is only pending', function (): void {
    $user = User::factory()->create();

    resolve(UpsertSubscription::class)->handle(subscriptionDtoFor($user, 'pending'));

    expect(Subscription::query()->firstOrFail()->quota_anchor_at)->toBeNull();
});

it('anchors on the first activation through the barte path', function (): void {
    $user = User::factory()->create();

    travelTo('2026-04-20 09:00');
    resolve(UpsertSubscription::class)->handle(subscriptionDtoFor($user, 'pending'));

    travelTo('2026-04-22 15:30');
    resolve(UpsertSubscription::class)->handle(subscriptionDtoFor($user, 'active'));

    expect(Subscription::query()->firstOrFail()->quota_anchor_at->toDateTimeString())
        ->toBe('2026-04-22 15:30:00');
});

it('anchors a subscription written through the billable relation, as cashier does for stripe', function (): void {
    travelTo('2026-05-07 11:00');
    $user = User::factory()->create();

    $subscription = $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'stripe_sub_anchor_test',
        'stripe_status' => Subscription::STATUS_ACTIVE,
        'quantity' => 1,
    ]);

    expect($subscription->refresh()->quota_anchor_at->toDateTimeString())->toBe('2026-05-07 11:00:00');
});

it('never moves the anchor once it is set', function (): void {
    $user = User::factory()->create();

    travelTo('2026-04-22 15:30');
    resolve(UpsertSubscription::class)->handle(subscriptionDtoFor($user, 'active'));

    travelTo('2026-06-10 08:00');
    resolve(UpsertSubscription::class)->handle(subscriptionDtoFor($user, 'defaulter'));
    resolve(UpsertSubscription::class)->handle(subscriptionDtoFor($user, 'active'));

    expect(Subscription::query()->firstOrFail()->quota_anchor_at->toDateTimeString())
        ->toBe('2026-04-22 15:30:00');
});

it('falls back to created_at for a legacy row with no anchor', function (): void {
    travelTo('2026-02-05 10:00');
    $user = actingAsSubscribedEmployee(3);

    Subscription::query()->whereMorphedTo('owner', $user)->update(['quota_anchor_at' => null]);

    $allowance = resolve(ResolveQuotaAllowance::class)->for($user->fresh());

    expect($allowance->anchor->toDateString())->toBe('2026-02-05');
});

/**
 * A migration já rodou quando a suíte começa, então o teste a executa de novo sobre
 * linhas legadas fabricadas na mão — o banco de teste nasce sem dado antigo.
 */
function runQuotaAnchorBackfill(): void
{
    $migration = require base_path('app-modules/billing/database/migrations/2026_08_16_001500_backfill_quota_anchor_at_on_billing_subscriptions.php');

    $migration->up();
}

function legacySubscriptionWithoutAnchor(User $user, string $status, string $createdAt): int
{
    $subscription = $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'legacy_' . $status . '_' . $createdAt,
        'stripe_status' => $status,
        'quantity' => 1,
    ]);

    DB::table('billing_subscriptions')
        ->where('id', $subscription->getKey())
        ->update(['quota_anchor_at' => null, 'created_at' => $createdAt]);

    return (int) $subscription->getKey();
}

it('backfills the anchor of a legacy active subscription', function (): void {
    $id = legacySubscriptionWithoutAnchor(User::factory()->create(), 'active', '2025-09-12 08:15:00');

    runQuotaAnchorBackfill();

    expect(Subscription::query()->findOrFail($id)->quota_anchor_at->toDateTimeString())
        ->toBe('2025-09-12 08:15:00');
});

it('leaves a legacy pending subscription without an anchor, so activation can set it', function (): void {
    $user = User::factory()->create();
    $id = legacySubscriptionWithoutAnchor($user, 'pending', '2025-09-12 08:15:00');

    runQuotaAnchorBackfill();

    expect(Subscription::query()->findOrFail($id)->quota_anchor_at)->toBeNull();

    travelTo('2026-01-20 17:00');
    Subscription::query()->findOrFail($id)->update(['stripe_status' => Subscription::STATUS_ACTIVE]);

    expect(Subscription::query()->findOrFail($id)->quota_anchor_at->toDateTimeString())
        ->toBe('2026-01-20 17:00:00');
});

it('does not anchor a stripe subscription that is only trialing', function (): void {
    $id = legacySubscriptionWithoutAnchor(User::factory()->create(), 'trialing', '2025-09-12 08:15:00');

    runQuotaAnchorBackfill();

    expect(Subscription::query()->findOrFail($id)->quota_anchor_at)->toBeNull();
});
