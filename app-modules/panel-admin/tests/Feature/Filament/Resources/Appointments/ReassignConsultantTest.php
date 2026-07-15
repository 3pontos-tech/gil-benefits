<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Mail\AppointmentConsultantUnassignedMail;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentHistory;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\EditAppointment;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    LaravelNotification::fake();
    Bus::fake();
    Mail::fake();

    $this->makeAvailable = function (CarbonInterface $date, Consultant $consultant): void {
        Zap::for($consultant)
            ->named('Availability')
            ->availability()
            ->from($date->toDateString())
            ->to($date->copy()->addDay()->toDateString())
            ->addPeriod('08:00', '18:00')
            ->save();
    };
});

it('moves the agenda and calendar event to the new consultant when reassigned', function (): void {
    $date = Date::now()->addDays(3)->setTime(10, 0);

    $previousConsultant = Consultant::factory()->create(['email' => 'previous@workspace.com']);
    $newConsultant = Consultant::factory()->create(['email' => 'new@workspace.com']);

    ($this->makeAvailable)($date, $previousConsultant);
    ($this->makeAvailable)($date, $newConsultant);

    $appointment = Appointment::factory()->create([
        'consultant_id' => $previousConsultant->id,
        'appointment_at' => $date,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'event-on-previous-calendar',
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    ]);

    // The previous consultant's agenda is already blocked for this appointment.
    resolve(AssignConsultantAction::class)->handle($appointment);

    // The old event lives on the previous consultant's calendar and must be deleted there.
    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')
        ->with('previous@workspace.com')
        ->andReturn('fake-access-token');
    $mockClient->shouldReceive('deleteEvent')
        ->once()
        ->with('fake-access-token', 'previous@workspace.com', 'event-on-previous-calendar');
    app()->instance(GoogleCalendarClient::class, $mockClient);

    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['consultant_id' => $newConsultant->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $appointment->refresh();

    expect($appointment->consultant_id)->toBe($newConsultant->id)
        ->and($appointment->google_event_id)->toBeNull()
        ->and($appointment->meeting_url)->toBeNull();

    // Agenda moved: blocked for the new consultant, released for the previous one.
    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::APPOINTMENT)
        ->whereJsonContains('metadata->appointment_id', $appointment->id)
        ->where('schedulable_id', $newConsultant->getKey())
        ->count()
    )->toBe(1);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::APPOINTMENT)
        ->whereJsonContains('metadata->appointment_id', $appointment->id)
        ->where('schedulable_id', $previousConsultant->getKey())
        ->exists()
    )->toBeFalse();

    // A fresh event (and meeting link) is created on the new consultant's calendar.
    Bus::assertDispatched(CreateAppointmentCalendarEventJob::class);

    // The new consultant is notified (same mail as confirmation); the previous one is warned.
    Mail::assertQueued(
        AppointmentScheduledMail::class,
        fn (AppointmentScheduledMail $mail): bool => $mail->hasTo('new@workspace.com')
    );
    Mail::assertQueued(
        AppointmentConsultantUnassignedMail::class,
        fn (AppointmentConsultantUnassignedMail $mail): bool => $mail->hasTo('previous@workspace.com')
    );
});

it('warns on the screen when a calendar operation fails during reassignment', function (): void {
    $date = Date::now()->addDays(3)->setTime(10, 0);

    $previousConsultant = Consultant::factory()->create(['email' => 'previous@workspace.com']);
    $newConsultant = Consultant::factory()->create(['email' => 'new@workspace.com']);

    ($this->makeAvailable)($date, $previousConsultant);
    ($this->makeAvailable)($date, $newConsultant);

    $appointment = Appointment::factory()->create([
        'consultant_id' => $previousConsultant->id,
        'appointment_at' => $date,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'event-on-previous-calendar',
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    ]);
    resolve(AssignConsultantAction::class)->handle($appointment);

    // Google Calendar is failing: the appointment still saves, but the user must be warned.
    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->andReturn('fake-access-token');
    $mockClient->shouldReceive('deleteEvent')->andThrow(new GoogleCalendarApiException('boom', 500));
    app()->instance(GoogleCalendarClient::class, $mockClient);

    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['consultant_id' => $newConsultant->id])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified(__('panel-admin::resources.appointments.actions.calendar_sync_failed'));

    // The consultant swap still persisted (calendar is best-effort, not blocking).
    expect($appointment->refresh()->consultant_id)->toBe($newConsultant->id);
});

it('does not touch the calendar when the consultant is unchanged', function (): void {
    $date = Date::now()->addDays(3)->setTime(10, 0);
    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);

    ($this->makeAvailable)($date, $consultant);

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => $date,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'event-123',
        'meeting_url' => 'https://meet.google.com/keep-this',
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldNotReceive('deleteEvent');

    app()->instance(GoogleCalendarClient::class, $mockClient);

    // Change a field that is not the consultant or the time.
    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['meeting_url' => 'https://meet.google.com/new-link-xyz'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($appointment->refresh()->google_event_id)->toBe('event-123');

    Bus::assertNotDispatched(CreateAppointmentCalendarEventJob::class);
    Mail::assertNotQueued(AppointmentScheduledMail::class);
    Mail::assertNotQueued(AppointmentConsultantUnassignedMail::class);
});

it('changes appointment status to pending when removes a consultant from an appointment', function (): void {
    $date = Date::now()->addDays(3)->setTime(10, 0);

    $previousConsultant = Consultant::factory()->create(['email' => 'previous@workspace.com']);
    ($this->makeAvailable)($date, $previousConsultant);

    $appointment = Appointment::factory()->create([
        'consultant_id' => $previousConsultant->id,
        'appointment_at' => $date,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'event-on-previous-calendar',
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    ]);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')
        ->with('previous@workspace.com')
        ->andReturn('fake-access-token');

    $mockClient->shouldReceive('deleteEvent')
        ->once()
        ->with('fake-access-token', 'previous@workspace.com', 'event-on-previous-calendar');

    app()->instance(GoogleCalendarClient::class, $mockClient);

    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['consultant_id' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    $appointment->refresh();
    expect($appointment->consultant_id)->toBe(null)
        ->and($appointment->status)->toBe(AppointmentStatus::Pending)
        ->and($appointment->google_event_id)->toBe(null)
        ->and($appointment->meeting_url)->toBe(null);

    assertDatabaseHas(AppointmentHistory::class, [
        'appointment_id' => $appointment->id,
        'action_type' => AppointmentHistoryActionType::ConsultantLeft,
        'old_values' => json_encode([
            'consultant_id' => $previousConsultant->id,
        ]),
        'new_values' => json_encode([
            'consultant_id' => null,
        ]),
    ]);
});
