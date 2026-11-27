<?php

declare(strict_types=1);

use App\Models\Users\User;

it('takes as the current subscription only one that pays for the person right now', function (string $status, ?string $endsAt, bool $isCurrent): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $user->subscriptions()->create([
        'type' => 'gold',
        'stripe_id' => 'sub_1',
        'stripe_status' => $status,
        'quantity' => 1,
        'ends_at' => $endsAt === null ? null : now()->modify($endsAt),
    ]);

    $current = $user->activeSubscription()->exists();

    expect($current)->toBe($isCurrent);
})->with([
    'active' => ['active', null, true],
    'active, cancelled with the paid period still running' => ['active', '+10 days', true],
    'active, paid period already over' => ['active', '-1 day', false],
    'defaulter, gateway still retrying' => ['defaulter', null, true],
    'defaulter, paid period already over' => ['defaulter', '-1 day', false],
    'pending checkout' => ['pending', null, false],
    'inactive' => ['inactive', null, false],
]);
