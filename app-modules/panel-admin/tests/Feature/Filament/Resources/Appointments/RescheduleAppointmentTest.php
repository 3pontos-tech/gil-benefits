<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\EditAppointment;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    LaravelNotification::fake();
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

it('reschedules the calendar event (patch) when only the time changes for the same consultant', function (): void {
    $day = Date::now()->addDays(3);
    $from = $day->copy()->setTime(10, 0);
    $to = $day->copy()->setTime(15, 0);

    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    ($this->makeAvailable)($day, $consultant);

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => $from,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'evt-keep',
        'meeting_url' => 'https://meet.google.com/keep-this',
    ]);
    resolve(AssignConsultantAction::class)->handle($appointment);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->with('consultant@workspace.com')->andReturn('tok');
    $mockClient->shouldReceive('patchEvent')
        ->once()
        ->with('tok', 'consultant@workspace.com', 'evt-keep', Mockery::type('array'));
    $mockClient->shouldNotReceive('deleteEvent');
    $mockClient->shouldNotReceive('createEvent');

    app()->instance(GoogleCalendarClient::class, $mockClient);

    // The form clears the consultant when the time changes; re-selecting the same one
    // keeps it unchanged, so this is a time-only change.
    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['appointment_at' => $to->toDateTimeString()])
        ->fillForm(['consultant_id' => $consultant->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $appointment->refresh();

    // Same event + link preserved; agenda re-blocked at the new time.
    expect($appointment->google_event_id)->toBe('evt-keep')
        ->and($appointment->meeting_url)->toBe('https://meet.google.com/keep-this')
        ->and($appointment->appointment_at->format('H:i'))->toBe('15:00');

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::APPOINTMENT)
        ->whereJsonContains('metadata->appointment_id', $appointment->id)
        ->exists()
    )->toBeTrue();
});

it('cleans up and reverts to Pending when the consultant is removed', function (): void {
    $day = Date::now()->addDays(3);
    $at = $day->copy()->setTime(10, 0);

    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    ($this->makeAvailable)($day, $consultant);

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => $at,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'evt-orphan',
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    ]);
    resolve(AssignConsultantAction::class)->handle($appointment);

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->with('consultant@workspace.com')->andReturn('tok');
    $mockClient->shouldReceive('deleteEvent')->once()->with('tok', 'consultant@workspace.com', 'evt-orphan');
    app()->instance(GoogleCalendarClient::class, $mockClient);

    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['consultant_id' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    $appointment->refresh();

    expect($appointment->consultant_id)->toBeNull()
        ->and($appointment->status)->toBe(AppointmentStatus::Pending)
        ->and($appointment->google_event_id)->toBeNull()
        ->and($appointment->meeting_url)->toBeNull();

    // The internal agenda slot is freed.
    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::APPOINTMENT)
        ->whereJsonContains('metadata->appointment_id', $appointment->id)
        ->exists()
    )->toBeFalse();
});

it('rolls back the record when the new time is unavailable for the consultant', function (): void {
    $day = Date::now()->addDays(3);
    $from = $day->copy()->setTime(10, 0);
    $conflicting = $day->copy()->setTime(16, 0);

    $consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    ($this->makeAvailable)($day, $consultant);

    // The consultant already has another appointment at the target time.
    Zap::for($consultant)
        ->named('Existing Appointment')
        ->appointment()
        ->from($day->toDateString())
        ->to($day->copy()->addDay()->toDateString())
        ->addPeriod('16:00', '17:00')
        ->save();

    $appointment = Appointment::factory()->create([
        'consultant_id' => $consultant->id,
        'appointment_at' => $from,
        'status' => AppointmentStatus::Active,
        'google_event_id' => 'evt-current',
    ]);
    resolve(AssignConsultantAction::class)->handle($appointment);

    // No Google operation should happen: we bail before touching any calendar.
    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldNotReceive('deleteEvent');
    $mockClient->shouldNotReceive('createEvent');
    $mockClient->shouldNotReceive('patchEvent');

    app()->instance(GoogleCalendarClient::class, $mockClient);

    livewire(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['appointment_at' => $conflicting->toDateTimeString()])
        ->fillForm(['consultant_id' => $consultant->id])
        ->call('save');

    // The save was rolled back: the record keeps its original time, consultant and event.
    $fresh = Appointment::query()->findOrFail($appointment->id);

    expect($fresh->consultant_id)->toBe($consultant->id)
        ->and($fresh->appointment_at->format('H:i'))->toBe('10:00')
        ->and($fresh->google_event_id)->toBe('evt-current')
        ->and($fresh->status)->toBe(AppointmentStatus::Active);

    // And its original agenda slot is intact.
    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::APPOINTMENT)
        ->whereJsonContains('metadata->appointment_id', $appointment->id)
        ->exists()
    )->toBeTrue();
});
