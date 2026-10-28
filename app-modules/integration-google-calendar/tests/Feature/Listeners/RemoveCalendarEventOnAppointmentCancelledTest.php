<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;

beforeEach(function (): void {
    Bus::fake();
});

it('queues the calendar event removal when the cancelled appointment has one', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Cancelled)
        ->create(['google_event_id' => 'evt-123']);

    event(new AppointmentCancelled($appointment));

    Bus::assertDispatched(DeleteAppointmentCalendarEventJob::class, fn (DeleteAppointmentCalendarEventJob $job): bool => $job->appointment->is($appointment));
});

it('does nothing when the cancelled appointment never reached the calendar', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Cancelled)
        ->create(['google_event_id' => null]);

    event(new AppointmentCancelled($appointment));

    Bus::assertNotDispatched(DeleteAppointmentCalendarEventJob::class);
});
