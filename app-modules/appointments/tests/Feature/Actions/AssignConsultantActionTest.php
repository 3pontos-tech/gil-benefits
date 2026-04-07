<?php

use Illuminate\Support\Facades\Date;
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
        'status' => AppointmentStatus::Scheduling,
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
        'status' => AppointmentStatus::Scheduling,
    ]);

    resolve(AssignConsultantAction::class)->handle($appointment);
})->throws(SlotUnavailableException::class);

it('does nothing when consultant_id is blank', function (): void {
    $appointment = Appointment::factory()->withoutConsultant()->create([
        'status' => AppointmentStatus::Scheduling,
    ]);

    resolve(AssignConsultantAction::class)->handle($appointment);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::APPOINTMENT)
        ->count()
    )->toBe(0);
});
