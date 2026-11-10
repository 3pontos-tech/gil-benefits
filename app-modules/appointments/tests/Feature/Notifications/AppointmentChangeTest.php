<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Enums\AppointmentChange;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * O encontro como ficou depois de salvo; o "antes" é sempre: confirmado, consultor c-1, dia 12
 * às 15:00, link da sala A.
 *
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

    return AppointmentChange::between(AppointmentStatus::Active, 'c-1', Date::parse('2026-09-12 15:00'), 'https://meet.google.com/aaa', $appointment);
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
    $appointment = Appointment::factory()->make(['status' => AppointmentStatus::Pending, 'consultant_id' => null, 'appointment_at' => Date::parse('2026-09-13 10:00')]);

    expect(AppointmentChange::between(AppointmentStatus::Pending, null, Date::parse('2026-09-12 15:00'), null, $appointment))->toBeNull();
});
