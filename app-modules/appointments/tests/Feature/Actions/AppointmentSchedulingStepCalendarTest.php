<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentSchedulingStep;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    Bus::fake();
});

it('dispatches CreateAppointmentCalendarEventJob when consultant has email', function (): void {
    $consultant = Consultant::factory()->create(['email' => 'consultant@example.com']);
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Scheduling,
    ]);
    actingAs($appointment->user);

    $step = new AppointmentSchedulingStep($appointment);
    $step->processStep();

    Bus::assertDispatched(CreateAppointmentCalendarEventJob::class, function ($job) use ($appointment): bool {
        return $job->appointment->id === $appointment->id;
    });
});

it('does not dispatch job when consultant has empty email', function (): void {
    $consultant = Consultant::factory()->create(['email' => '']);
    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Scheduling,
    ]);
    actingAs($appointment->user);

    $step = new AppointmentSchedulingStep($appointment);
    $step->processStep();

    Bus::assertNotDispatched(CreateAppointmentCalendarEventJob::class);
});

it('does not dispatch job when no consultant assigned', function (): void {
    $appointment = Appointment::factory()->withoutConsultant()->create([
        'status' => AppointmentStatus::Scheduling,
    ]);
    actingAs($appointment->user);

    $step = new AppointmentSchedulingStep($appointment);
    $step->processStep();

    Bus::assertNotDispatched(CreateAppointmentCalendarEventJob::class);
});
