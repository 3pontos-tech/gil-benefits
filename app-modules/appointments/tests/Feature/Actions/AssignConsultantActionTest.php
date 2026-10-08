<?php

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

it('creates schedule when consultant is available', function (): void {
    $consultant = Consultant::factory()->create();
    $date = Date::now()->addDays(3);

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => $date->copy()->setTime(10, 0),
        'status' => AppointmentStatus::Active,
    ]);

    resolve(AssignConsultantAction::class)->handle($appointment);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::APPOINTMENT)
        ->whereJsonContains('metadata->appointment_id', $appointment->id)
        ->exists()
    )->toBeTrue();
});

it('throws SlotUnavailableException when consultant has conflict', function (): void {
    $consultant = Consultant::factory()->create();
    $date = Date::now()->addDays(3);

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    Zap::for($consultant)
        ->named('Existing Appointment')
        ->appointment()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('10:00', '11:00')
        ->save();

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => $date->copy()->setTime(10, 0),
        'status' => AppointmentStatus::Active,
    ]);

    resolve(AssignConsultantAction::class)->handle($appointment);
})->throws(SlotUnavailableException::class);

it('does nothing when consultant_id is blank', function (): void {
    $appointment = Appointment::factory()->withoutConsultant()->create([
        'status' => AppointmentStatus::Active,
    ]);

    resolve(AssignConsultantAction::class)->handle($appointment);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::APPOINTMENT)
        ->whereJsonContains('metadata->appointment_id', $appointment->id)
        ->exists()
    )->toBeFalse();
});

it('throws SlotUnavailableException on partial overlaps', function (int $newHour, int $newMinute): void {
    $consultant = Consultant::factory()->create();
    $date = Date::now()->addDays(3);

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    // Existing appointment from 10:00 to 11:00
    Zap::for($consultant)
        ->named('Existing Appointment')
        ->appointment()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('10:00', '11:00')
        ->save();

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => $date->copy()->setTime($newHour, $newMinute),
        'status' => AppointmentStatus::Active,
    ]);

    resolve(AssignConsultantAction::class)->handle($appointment);
})->with([
    'new starts inside existing' => [10, 30],
    'new ends inside existing' => [9, 30],
    'new wraps existing start' => [9, 45],
    'exact overlap' => [10, 0],
])->throws(SlotUnavailableException::class);

it('allows assignment when new slot is adjacent to existing one', function (int $newHour, int $newMinute): void {
    $consultant = Consultant::factory()->create();
    $date = Date::now()->addDays(3);

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    Zap::for($consultant)
        ->named('Existing Appointment')
        ->appointment()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('10:00', '11:00')
        ->save();

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => $date->copy()->setTime($newHour, $newMinute),
        'status' => AppointmentStatus::Active,
    ]);

    resolve(AssignConsultantAction::class)->handle($appointment);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::APPOINTMENT)
        ->whereJsonContains('metadata->appointment_id', $appointment->id)
        ->exists()
    )->toBeTrue();
})->with([
    'starts when existing ends' => [11, 0],
    'ends when existing starts' => [9, 0],
]);

it('replaces the existing APPOINTMENT schedule when reassigning the same appointment', function (): void {
    $consultant = Consultant::factory()->create();
    $date = Date::now()->addDays(3);

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => $date->copy()->setTime(10, 0),
        'status' => AppointmentStatus::Active,
    ]);

    resolve(AssignConsultantAction::class)->handle($appointment);

    // Re-run: existing APPOINTMENT schedule is expected to be dropped and re-created
    resolve(AssignConsultantAction::class)->handle($appointment);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::APPOINTMENT)
        ->whereJsonContains('metadata->appointment_id', $appointment->id)
        ->count()
    )->toBe(1);
});

/**
 * Roda a action e devolve quantas linhas a consulta de trava (`... for update` sobre os schedules
 * do consultor) selecionou no momento em que rodou, antes de a action gravar o bloqueio do encontro. O SQLite ignora o FOR UPDATE, então o que dá para provar aqui é que
 * a trava pega as linhas certas; a espera em si é garantida pelo Postgres.
 */
function rowsLockedWhileAssigning(Appointment $appointment): int
{
    $rowsLocked = null;
    $captured = false;

    DB::listen(function (QueryExecuted $query) use (&$rowsLocked, &$captured): void {
        if ($captured || ! str_starts_with($query->sql, 'select') || ! str_contains($query->sql, '"schedulable_id"')) {
            return;
        }

        $captured = true;
        $rowsLocked = count(DB::select($query->sql, $query->bindings));
    });

    resolve(AssignConsultantAction::class)->handle($appointment);

    return $rowsLocked ?? 0;
}

it('locks the open-ended weekly availability the admin registers', function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    $consultant = Consultant::factory()->create();

    Zap::for($consultant)
        ->named('Comercial')
        ->availability()
        ->from('2026-09-01')
        ->weekDays(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], '08:00', '20:00')
        ->save();

    expect(Schedule::query()->where('schedulable_id', $consultant->id)->value('end_date'))->toBeNull();

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => Date::parse('2026-10-08 10:00:00'),
        'status' => AppointmentStatus::Active,
    ]);

    expect(rowsLockedWhileAssigning($appointment))->toBe(1);
});

it('locks a dated availability that covers the day', function (): void {
    $consultant = Consultant::factory()->create();
    $date = Date::now()->addDays(3);

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => $date->copy()->setTime(10, 0),
        'status' => AppointmentStatus::Active,
    ]);

    expect(rowsLockedWhileAssigning($appointment))->toBe(1);
});
