<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Listeners\SendOnboardingEmailListener;
use TresPontosTech\Appointments\Mail\EmployeeOnboardingMail;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserImported;
use TresPontosTech\User\Events\UserRegistered;

beforeEach(function (): void {
    Mail::fake();
});

it('listens to every way a new employee comes in', function (): void {
    Event::fake();

    Event::assertListening(UserRegistered::class, [SendOnboardingEmailListener::class, 'handleUserRegistered']);
    Event::assertListening(UserImported::class, [SendOnboardingEmailListener::class, 'handleUserImported']);
});

it('sends the onboarding to an employee created with a temporary password', function (): void {
    $user = User::factory()->create(['email' => 'paula@empresa.com']);

    (new SendOnboardingEmailListener)->handleUserRegistered(new UserRegistered($user, Roles::Employee, 'Abc123!@#xyz'));

    Mail::assertQueued(
        EmployeeOnboardingMail::class,
        fn (EmployeeOnboardingMail $mail): bool => $mail->hasTo('paula@empresa.com') && $mail->hasTemporaryPassword,
    );
});

it('sends the onboarding without the temporary password steps to a self registered employee', function (): void {
    $user = User::factory()->create();

    (new SendOnboardingEmailListener)->handleUserRegistered(new UserRegistered($user, Roles::Employee));

    Mail::assertQueued(EmployeeOnboardingMail::class, fn (EmployeeOnboardingMail $mail): bool => ! $mail->hasTemporaryPassword);
});

it('sends the onboarding to a user created by the admin', function (): void {
    $user = User::factory()->create();

    (new SendOnboardingEmailListener)->handleUserRegistered(new UserRegistered($user, Roles::User, 'Abc123!@#xyz'));

    Mail::assertQueued(EmployeeOnboardingMail::class, 1);
});

it('leaves out roles that do not book consultations', function (Roles $role): void {
    $user = User::factory()->create();

    (new SendOnboardingEmailListener)->handleUserRegistered(new UserRegistered($user, $role, mustSetPassword: true));

    Mail::assertNothingQueued();
})->with([
    'consultant' => Roles::Consultant,
    'company owner' => Roles::CompanyOwner,
    'admin' => Roles::Admin,
]);

it('sends the onboarding with the temporary password steps to an imported employee', function (): void {
    $user = User::factory()->create(['email' => 'importado@empresa.com']);

    (new SendOnboardingEmailListener)->handleUserImported(new UserImported($user));

    Mail::assertQueued(
        EmployeeOnboardingMail::class,
        fn (EmployeeOnboardingMail $mail): bool => $mail->hasTo('importado@empresa.com') && $mail->hasTemporaryPassword,
    );
});
