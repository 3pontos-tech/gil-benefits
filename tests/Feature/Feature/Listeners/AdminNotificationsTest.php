<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Admin\Listeners\NotifyAdminsOfAppointmentBookedListener;
use TresPontosTech\Admin\Listeners\NotifyAdminsOfAppointmentCancelledListener;
use TresPontosTech\Admin\Listeners\NotifyAdminsOfAppointmentCompletedListener;
use TresPontosTech\Admin\Listeners\NotifyAdminsOfUserRegisteredListener;
use TresPontosTech\Appointments\Events\AppointmentBooked;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Events\AppointmentCompleted;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->createQuietly();
    $this->admin->assignRole(Roles::SuperAdmin->value);
});

it('notifies admins when a user registers', function (): void {
    $newUser = User::factory()->createQuietly();

    $listener = new NotifyAdminsOfUserRegisteredListener;
    $listener->handle(new UserRegistered($newUser, Roles::User));

    assertDatabaseHas('notifications', [
        'notifiable_type' => 'users',
        'notifiable_id' => $this->admin->id,
    ]);
});

it('does not send notification if no admins exist', function (): void {
    $this->admin->syncRoles([]);

    $newUser = User::factory()->createQuietly();
    $listener = new NotifyAdminsOfUserRegisteredListener;
    $listener->handle(new UserRegistered($newUser, Roles::User));

    expect(DB::table('notifications')->count())->toBe(0);
});

it('notifies admins when an appointment is booked', function (): void {
    $appointment = Appointment::factory()->create();

    $listener = new NotifyAdminsOfAppointmentBookedListener;
    $listener->handle(new AppointmentBooked($appointment));

    assertDatabaseHas('notifications', [
        'notifiable_type' => 'users',
        'notifiable_id' => $this->admin->id,
    ]);
});

it('notifies admins when an appointment is cancelled', function (): void {
    $appointment = Appointment::factory()->create();

    $listener = new NotifyAdminsOfAppointmentCancelledListener;
    $listener->handle(new AppointmentCancelled($appointment));

    assertDatabaseHas('notifications', [
        'notifiable_type' => 'users',
        'notifiable_id' => $this->admin->id,
    ]);
});

it('notifies admins when an appointment is completed', function (): void {
    $appointment = Appointment::factory()->create();

    $listener = new NotifyAdminsOfAppointmentCompletedListener;
    $listener->handle(new AppointmentCompleted($appointment));

    assertDatabaseHas('notifications', [
        'notifiable_type' => 'users',
        'notifiable_id' => $this->admin->id,
    ]);
});

it('notifies all admin users', function (): void {
    $secondAdmin = User::factory()->createQuietly();
    $secondAdmin->assignRole(Roles::Admin->value);

    $appointment = Appointment::factory()->create();

    DB::table('notifications')->truncate();

    $listener = new NotifyAdminsOfAppointmentBookedListener;
    $listener->handle(new AppointmentBooked($appointment));

    expect(DB::table('notifications')->where('notifiable_type', 'users')->count())->toBe(2);
});
