<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;

it('carimba o último acesso quando o usuário faz login', function (): void {
    Date::setTestNow('2026-08-27 10:30:00');

    $user = User::factory()->create(['last_login_at' => null]);

    event(new Login('web', $user, false));

    expect($user->fresh()->last_login_at?->toDateTimeString())->toBe('2026-08-27 10:30:00');
});

it('não altera updated_at ao carimbar o acesso', function (): void {
    $user = User::factory()->create(['last_login_at' => null]);
    $updatedAt = $user->updated_at?->toDateTimeString();

    Date::setTestNow(Date::now()->addDay());
    event(new Login('web', $user, false));

    expect($user->fresh()->updated_at?->toDateTimeString())->toBe($updatedAt)
        ->and($user->fresh()->last_login_at)->not->toBeNull();
});

it('sobrescreve o acesso anterior a cada novo login', function (): void {
    Date::setTestNow('2026-08-20 08:00:00');
    $user = User::factory()->create();
    event(new Login('web', $user, false));

    Date::setTestNow('2026-08-27 09:15:00');
    event(new Login('web', $user->fresh(), false));

    expect($user->fresh()->last_login_at?->toDateTimeString())->toBe('2026-08-27 09:15:00');
});
