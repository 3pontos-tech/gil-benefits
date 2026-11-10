<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;
use TresPontosTech\Appointments\Actions\NotifyAppointmentChangedAction;
use TresPontosTech\Appointments\DTO\AppointmentSnapshot;
use TresPontosTech\Appointments\Enums\AppointmentChange;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentChangedNotification;

/**
 * O "antes" de todos os casos: confirmado, consultor c-1, dia 12 às 15:00, link da sala A.
 */
function confirmedSnapshot(): AppointmentSnapshot
{
    return new AppointmentSnapshot(AppointmentStatus::Active, 'c-1', Date::parse('2026-09-12 15:00'), 'https://meet.google.com/aaa');
}

/**
 * @param  array<string, mixed>  $after
 */
function changeAfter(array $after): ?AppointmentChange
{
    $appointment = Appointment::factory()->make([
        'status' => AppointmentStatus::Active,
        'consultant_id' => 'c-1',
        'appointment_at' => Date::parse('2026-09-12 15:00'),
        'meeting_url' => 'https://meet.google.com/aaa',
        ...$after,
    ]);

    return AppointmentChange::between(confirmedSnapshot(), $appointment);
}

it('names the most serious change of a save', function (array $after, ?AppointmentChange $change): void {
    expect(changeAfter($after))->toBe($change);
})->with([
    'back to waiting for confirmation, even with a new time' => [['status' => AppointmentStatus::Pending, 'consultant_id' => null, 'appointment_at' => Date::parse('2026-09-13 10:00')], AppointmentChange::Unconfirmed],
    'a new consultant, which brings a new link' => [['consultant_id' => 'c-2', 'meeting_url' => 'https://meet.google.com/bbb'], AppointmentChange::Consultant],
    'a new time with the same consultant' => [['appointment_at' => Date::parse('2026-09-13 10:00')], AppointmentChange::Time],
    'only the room link' => [['meeting_url' => 'https://meet.google.com/bbb'], AppointmentChange::Link],
    'nothing the employee sees' => [[], null],
]);

it('says nothing about an appointment that was not confirmed yet', function (): void {
    $before = new AppointmentSnapshot(AppointmentStatus::Pending, null, Date::parse('2026-09-12 15:00'), null);
    $appointment = Appointment::factory()->make(['status' => AppointmentStatus::Pending, 'consultant_id' => null, 'appointment_at' => Date::parse('2026-09-13 10:00')]);

    expect(AppointmentChange::between($before, $appointment))->toBeNull();
});

it('tells the employee only about changes made by the team', function (AppointmentHistoryActor $actor, bool $tells): void {
    Notification::fake();
    $employee = User::factory()->create();
    $appointment = Appointment::factory()->make([
        'user_id' => $employee->getKey(),
        'status' => AppointmentStatus::Active,
        'consultant_id' => 'c-1',
        'appointment_at' => Date::parse('2026-09-12 15:00'),
        'meeting_url' => 'https://meet.google.com/bbb',
    ]);

    resolve(NotifyAppointmentChangedAction::class)->handle(confirmedSnapshot(), $appointment, $actor);

    expect(Notification::sent($employee, AppointmentChangedNotification::class)->count())->toBe($tells ? 1 : 0);
})->with([
    'the team' => [AppointmentHistoryActor::Admin, true],
    'the employee' => [AppointmentHistoryActor::User, false],
    'the consultant' => [AppointmentHistoryActor::Consultant, false],
]);
