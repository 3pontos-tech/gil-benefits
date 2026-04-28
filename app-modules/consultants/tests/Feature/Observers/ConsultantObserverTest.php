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

        Consultant::factory()->create(['email' => 'shared@example.com']);
        Consultant::factory()->create(['email' => 'shared@example.com']);

        Event::assertDispatchedTimes(UserRegistered::class, 2);
        expect(User::query()->where('email', 'shared@example.com')->count())->toBe(1);
    });
});
