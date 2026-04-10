<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentSchedulingStep;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    Bus::fake();
});

it('dispatches DeleteAppointmentCalendarEventJob on cancel when google_event_id exists', function (): void {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Scheduling,
        'google_event_id' => 'google-event-123',
    ]);
    actingAs($appointment->user);

    $step = new AppointmentSchedulingStep($appointment);
    $step->cancel();

    Bus::assertDispatched(DeleteAppointmentCalendarEventJob::class, function ($job) use ($appointment): bool {
        return $job->appointment->id === $appointment->id;
    });

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('does not dispatch delete job when google_event_id is null', function (): void {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Scheduling,
        'google_event_id' => null,
    ]);
    actingAs($appointment->user);

    $step = new AppointmentSchedulingStep($appointment);
    $step->cancel();

    Bus::assertNotDispatched(DeleteAppointmentCalendarEventJob::class);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});
