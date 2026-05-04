<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;

it('allows super admin to perform all actions', function (): void {
    $user = User::factory()->superAdmin()->create();

    $record = AppointmentRecord::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', AppointmentRecord::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $record))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', AppointmentRecord::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $record))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $record))->toBeTrue();
});

it('allows consultant to manage records', function (): void {
    $user = User::factory()->consultant()->create();

    $record = AppointmentRecord::factory()->published()->create();

    expect(Gate::forUser($user)->allows('viewAny', AppointmentRecord::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $record))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', AppointmentRecord::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $record))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $record))->toBeTrue();
});

it('allows owner employee to view their own published record', function (): void {
    $employee = User::factory()->employee()->create();

    $appointment = Appointment::factory()->create(['user_id' => $employee->getKey()]);
    $record = AppointmentRecord::factory()->recycle($appointment)->published()->create();

    expect(Gate::forUser($employee)->allows('view', $record))->toBeTrue();
});

it('denies owner employee from viewing their own unpublished record', function (): void {
    $employee = User::factory()->employee()->create();

    $appointment = Appointment::factory()->create(['user_id' => $employee->getKey()]);
    $record = AppointmentRecord::factory()->recycle($appointment)->draft()->create();

    expect(Gate::forUser($employee)->denies('view', $record))->toBeTrue();
});

it('denies employee from viewing published record from another user', function (): void {
    $employeeA = User::factory()->employee()->create();
    $employeeB = User::factory()->employee()->create();

    $appointmentOfB = Appointment::factory()->create(['user_id' => $employeeB->getKey()]);
    $recordOfB = AppointmentRecord::factory()->recycle($appointmentOfB)->published()->create();

    expect(Gate::forUser($employeeA)->denies('view', $recordOfB))->toBeTrue();
});

it('denies employee from creating, updating or deleting records', function (): void {
    $employee = User::factory()->employee()->create();

    $record = AppointmentRecord::factory()->published()->create();

    expect(Gate::forUser($employee)->denies('create', AppointmentRecord::class))->toBeTrue()
        ->and(Gate::forUser($employee)->denies('update', $record))->toBeTrue()
        ->and(Gate::forUser($employee)->denies('delete', $record))->toBeTrue();
});

it('applies the same rules to the plain User role', function (): void {
    $clientUser = User::factory()->user()->create();

    $ownAppointment = Appointment::factory()->create(['user_id' => $clientUser->getKey()]);
    $ownPublished = AppointmentRecord::factory()->recycle($ownAppointment)->published()->create();

    $othersPublished = AppointmentRecord::factory()->published()->create();

    expect(Gate::forUser($clientUser)->allows('view', $ownPublished))->toBeTrue()
        ->and(Gate::forUser($clientUser)->denies('view', $othersPublished))->toBeTrue()
        ->and(Gate::forUser($clientUser)->denies('create', AppointmentRecord::class))->toBeTrue();
});

it('denies default users from create, update and delete', function (): void {
    // Users criados com factory recebem role User automaticamente,
    // que só carrega permissões de view / viewAny para AppointmentRecord.
    $user = User::factory()->create();

    $record = AppointmentRecord::factory()->published()->create();

    expect(Gate::forUser($user)->denies('create', AppointmentRecord::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('update', $record))->toBeTrue()
        ->and(Gate::forUser($user)->denies('delete', $record))->toBeTrue();
});
