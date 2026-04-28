<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

describe('ConsultantObserver', function (): void {
    it('reuses existing user with same email', function (): void {
        Event::fake([UserRegistered::class]);

        $existingUser = User::factory()->create(['email' => 'consultor@example.com']);

        $consultant = Consultant::factory()->create(['email' => 'consultor@example.com']);

        expect(User::query()->where('email', 'consultor@example.com')->count())->toBe(1)
            ->and($consultant->fresh()->user->getKey())->toBe($existingUser->getKey());
    });

    it('dispatches UserRegistered with Consultant role', function (): void {
        Event::fake([UserRegistered::class]);

        $consultant = Consultant::factory()->create();

        Event::assertDispatched(
            UserRegistered::class,
            fn (UserRegistered $event): bool => $event->user->email === $consultant->email
                && $event->role === Roles::Consultant
        );
    });

    it('does not create duplicate users when two consultants share the same email', function (): void {
        Event::fake([UserRegistered::class]);

        $first = Consultant::factory()->create(['email' => 'shared@example.com']);
        $second = Consultant::factory()->create(['email' => 'shared@example.com']);

        Event::assertDispatchedTimes(UserRegistered::class, 2);

        $sharedUser = User::query()->where('email', 'shared@example.com')->firstOrFail();

        expect(User::query()->where('email', 'shared@example.com')->count())->toBe(1)
            ->and($first->fresh()->user_id)->toBe($sharedUser->getKey())
            ->and($second->fresh()->user_id)->toBe($sharedUser->getKey());
    });
});
