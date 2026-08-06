<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Permissions\Roles;

it('allows admins to view evaluations', function (): void {
    $admin = actingAsAdmin();

    $feedback = AppointmentFeedback::factory()->create(['comment' => 'Ótimo atendimento']);

    expect($admin->can('viewAny', AppointmentFeedback::class))->toBeTrue()
        ->and($admin->can('view', $feedback))->toBeTrue();
});

it('allows super admins to view evaluations', function (): void {
    $superAdmin = actingAsSuperAdmin();

    expect($superAdmin->can('viewAny', AppointmentFeedback::class))->toBeTrue();
});

it('denies regular users from viewing evaluations', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Roles::User->value);

    $feedback = AppointmentFeedback::factory()->create(['comment' => 'Ótimo atendimento']);

    expect($user->can('viewAny', AppointmentFeedback::class))->toBeFalse()
        ->and($user->can('view', $feedback))->toBeFalse();
});
