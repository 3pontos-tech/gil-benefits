<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

use function Pest\Livewire\livewire;

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

    it('never gives the new user the email as password', function (): void {
        Event::fake([UserRegistered::class]);

        $consultant = Consultant::factory()->create(['email' => 'nova@example.com']);

        expect(Hash::check('nova@example.com', $consultant->fresh()->user->password))->toBeFalse();

        Event::assertDispatched(
            UserRegistered::class,
            fn (UserRegistered $event): bool => $event->temporaryPassword === null,
        );
    });

    it('hands the new user a working link to set the password on the consultant panel', function (): void {
        Event::fake([UserRegistered::class]);

        $consultant = Consultant::factory()->create(['email' => 'nova@example.com']);
        $user = $consultant->fresh()->user;
        $user->assignRole(Roles::Consultant->value);

        $url = Event::dispatched(UserRegistered::class)->first()[0]->setPasswordUrl;
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        expect($url)->toStartWith(route('filament.consultant.auth.password-reset.reset'))
            ->and(Password::broker()->tokenExists($user, $query['token']))->toBeTrue();

        filament()->setCurrentPanel(FilamentPanel::Consultant->value);

        livewire(ResetPassword::class, ['email' => $query['email'], 'token' => $query['token']])
            ->fillForm(['password' => 'nova-senha-123', 'passwordConfirmation' => 'nova-senha-123'])
            ->call('resetPassword')
            ->assertHasNoFormErrors();

        expect(Hash::check('nova-senha-123', $user->fresh()->password))->toBeTrue();
    });

    it('does not hand a set-password link to a user that already existed', function (): void {
        Event::fake([UserRegistered::class]);
        User::factory()->create(['email' => 'existe@example.com']);

        Consultant::factory()->create(['email' => 'existe@example.com']);

        Event::assertDispatched(
            UserRegistered::class,
            fn (UserRegistered $event): bool => $event->setPasswordUrl === null && $event->temporaryPassword === null,
        );
    });
});
